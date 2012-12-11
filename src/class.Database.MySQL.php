<?php
/**
 * [PLACEHOLDER]
**/

namespace PDeploy;

require 'interface.Database.php';


class Database_MySQL implements DatabaseInterface {

  /************************************************************************************************/
  /* DatabaseInterface methods
  /************************************************************************************************/
  public function updateDatabase($structure_file, $structure_dir, $data_dir) {
    if (!$this->exists()) $this->create();
    $this->connectDatabase();
    if ($this->isNew()) {
      $this->initMetadata();
      $this->createStructure($structure_file);
    }
    $this->updateStructure($structure_dir);
    $this->importData($data_dir);
    return;
  }

  public function exists($dbname = null) {
    $pdo = new \PDO($this->_dsn, $this->_username, $this->_password);
    $query = sprintf("SELECT `schema_name` FROM `information_schema`.`schemata` WHERE `schema_name` = '%s';", $dbname ?: $this->_database);
    $r = $pdo->query($query);
    return !!$r->fetchColumn();
  }

  public function create($dbname = null) {
    $pdo = new \PDO($this->_dsn, $this->_username, $this->_password);
    $query = sprintf("CREATE DATABASE `%s`;", $dbname ?: $this->_database);
    if ($pdo->exec($query) === false) return false;
    else return true;
  }

  public function isNew( ) {
    $res = $this->_pdo->query('SHOW TABLES;');
    return !$res->fetchColumn();
  }

  public function initMetadata( ) {
    $this->executeFile(dirname(__FILE__) . '/' . self::METADATA_DIRECTORY . 'mysql.sql');
    return;
  }

  public function executeFile($filename, array $replacements = array()) {
    if (!is_readable($filename)) \PDeploy::error("Couldn't read file '%s' for execution.", $filename);
    $pathinfo = pathinfo($filename);
    switch ($pathinfo['extension']) {
      case 'sql':
        $queries = file_get_contents($filename);
        foreach ($replacements as $search => $replace) $queries = str_replace($search, $replace, $queries);
        if ($this->_pdo->exec($queries) === false) {
          $error = $this->_pdo->errorInfo();
          \PDeploy::error('PDO[%d]: %s.', $this->_pdo->errorCode(), $error[2]);
        }
        break;
      case 'php':
        require $filename;
        break;
      default:
        \PDeploy::error('Unknown file type (acceptable types are: .sql and .php).');
    }
    return true;
  }

  public function createStructure($filename) {
    $this->executeFile($filename);
    return;
  }

  public function updateStructure($dirname) {
    if (!is_dir($dirname)) \PDeploy::error("Couldn't find directory '%s'.", $dirname);
    // Scan for patch files.
    $files = array();
    $num_patches = 0;
    $version = $current_version = $this->getVersion();
    while (true) {
      $sql = sprintf($dirname . self::PATCH_FILE_SQL, $version);
      $php = sprintf($dirname . self::PATCH_FILE_PHP, $version);
      $sql_found = false;
      $php_found = false;
      $file = '';
      // Matthew 7:7 ("...seek, and ye shall find...")
      if (is_readable($sql)) $sql_found = true;
      if (is_readable($php)) $php_found = true;
      // Are we done?
      if (!$sql_found && !$php_found) break;
      // We don't allow the ambiguity of both SQL and PHP in a single version.
      if ($sql_found && $php_found) \PDeploy::error("Both a SQL and PHP patch were found for version $version.\n\tWe don't allow this because of the potential for ambiguity.\n\tNote: This may have the result of a recent merge; check the Git log and blame the other guy.");
      // Record the file.
      $files[$version] = $sql_found ? $sql : $php;
      // Increment for the next loop.
      ++$num_patches;
      ++$version;
    }
    foreach ($files as $f) $this->executeFile($f);
    if ($version != $current_version) {
      // Update the database version number.
      $stmt = $this->_pdo->prepare('UPDATE `pdeploy_deployment_config` SET `value` = ? WHERE `key` = ? LIMIT 1;');
      $stmt->execute(array($version, 'version'));
    }
    return;
  }

  public function importData($dirname, $ignore = null) {
    if (!is_dir($dirname)) \PDeploy::error("Couldn't find directory '%s'.", $dirname);
    $data_files = 0;
    if ($ignore != null && is_string($ignore)) $ignore = explode(',', str_replace(', ', ',', $ignore));
    foreach (glob($dirname . '*.sql') as $sql) {
      $replacements = array();
      $pathinfo = pathinfo($sql);
      $filter_found = false;
      foreach ($ignore as $i) {
        if (stripos($i, $pathinfo['filename']) !== false) {
          $filter_found = true;
          break;
        }
      }
      if ($filter_found) continue;
      $stmt = $this->_pdo->prepare(sprintf("SELECT COUNT(*) AS `c` FROM `%s` LIMIT 1;", $pathinfo['filename']));
      $stmt->execute();
      $r = $stmt->fetch();
      if ($r['c'] == 0) {
        $csv = $dirname . $pathinfo['filename'] . '.csv';
        $csvzip = $csv . '.zip';
        // Is there a matching CSV file we'll be working with? (or a .zip we first need to extract?)
        if (is_readable($csvzip)) $this->deleteFileLater($this->unzip($csvzip, $dirname));
        if (is_readable($csv)) $replacements['%SQL_DATA_DIRECTORY%'] = realpath($dirname) . '/';
        // Finally, we can execute the SQL - Gosh!
        $this->executeFile($sql, $replacements);
        ++$data_files;
      }
    }
    return;
  }

  public function getVersion( ) {
    $query = "SELECT `value` FROM `pdeploy_deployment_config` WHERE `key` = 'version' LIMIT 1;";
    $stmt = $this->_pdo->query($query);
    return $stmt->fetchColumn();
  }

  public function setVersion($version) {
    $stmt = $this->_pdo->prepare("UPDATE `pdeploy_deployment_config` SET `value` = ? WHERE `key` = ? LIMIT 1");
    return $stmt->execute(array($version, 'version'));
  }

  /************************************************************************************************/
  /* MySQL-specific methods and state
  /************************************************************************************************/
  public function __destruct( ) {
    foreach ($this->_dead as $dead) unlink($dead);
    return;
  }

  public function initCredentials($host, $port, $username, $password, $database) {
    $this->_host      = $host;
    $this->_port      = $port;
    $this->_username  = $username;
    $this->_password  = $password;
    $this->_database  = $database;
    $this->_dsn       = "mysql:host={$this->_host};port={$this->_port};";
    $this->_full_dsn  = $this->_dsn . "dbname={$this->_database};";
    return;
  }

  public function connectDatabase( ) {
    $this->_pdo = new \PDO($this->_full_dsn, $this->_username, $this->_password, array(
      \PDO::MYSQL_ATTR_LOCAL_INFILE => 1,
    ));
    return;
  }

  public function pdo( ) {
    return $this->_pdo;
  }

  public function deleteFileLater($filename) {
    if (!in_array($filename, $this->_dead)) $this->_dead[] = $filename;
    return;
  }

  public function unzip($zipname, $dirname) {
    $pathinfo = pathinfo(substr($zipname, 0, -4)); // remove the .zip; then you have the "real" file name.
    $name     = $pathinfo['basename'];
    $archive  = new \ZipArchive();
    if (!($archive->open($zipname)))            PDeploy\error("Failed to open zip file '$zipname'.");
    if (!$archive->extractTo($dirname, $name))  PDeploy\error("Failed to extract '$name' from zip archive '$zipname'.");
    if (!$archive->close())                     PDeploy\error("Failed to close zip file '$zipname'.");
    return $dirname . $name;
  }

  private $_host      = '';
  private $_port      = '';
  private $_username  = '';
  private $_password  = '';
  private $_database  = '';
  private $_dsn       = '';
  private $_full_dsn  = '';
  private $_pdo       = null;
  private $_dead      = array();

};
