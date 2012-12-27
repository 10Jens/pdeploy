<?php
/**
 * [PLACEHOLDER]
**/

require 'class.Exception.php';
require 'class.Filesystem.Linux.php';
require 'class.Database.MySQL.php';
require 'class.Optimizer.php';


class PDeploy {

  /************************************************************************************************/
  /* Meta
  /************************************************************************************************/
  public static function init($filesystem = 'linux', $database = 'mysql') {
    $fs = $db = null;
    switch ($filesystem) {
      case 'linux': $fs = new PDeploy\Filesystem_Linux(); break;
      default: self::error("Filesystem '$filesystem' not recognized.\n");
    }
    switch ($database) {
      case 'mysql': $db = new PDeploy\Database_MySQL();   break;
      default: self::error("Database '$database' not recognized.\n");
    }
    return new PDeploy($fs, $db);
  }

  public function filesystem( ) {
    return $this->_filesystem;
  }

  public function database( ) {
    return $this->_database;
  }

  public function optimizer( ) {
    return $this->_optimizer;
  }

  /************************************************************************************************/
  /* Deployment tools
  /************************************************************************************************/
  public function requireVersion($version) {
    if (version_compare(PHP_VERSION, $version) < 0) self::error('PHP version %s required - found version %s', $version, PHP_VERSION);
    return;
  }

  public function requireExtension($extension_name) {
    if (!is_array($extension_name)) $extension_name = array($extension_name);
    foreach ($extension_name as $ext) if (!extension_loaded($ext)) self::error('Extension %s required.', $ext);
    return;
  }

  public function shell($command) {
    return shell_exec(escapeshellcmd($command));
  }

  /************************************************************************************************/
  /* Internal goodies
  /************************************************************************************************/
  const OUTPUT_PREFIX    = 'PDeploy Installer: ';

  private $_filesystem  = null;
  private $_database    = null;
  private $_optimizer   = null;

  private function __construct($filesystem, $database) {
    $this->_filesystem  = $filesystem;
    $this->_database    = $database;
    $this->_optimizer   = new PDeploy\Optimizer();
    return;
  }

  public function __call($name, $args) {
    $lookup = array(
      $this->_filesystem,
      $this->_database,
      $this->_optimizer,
    );
    foreach ($lookup as $object) if (method_exists($object, $name)) return call_user_method_array($name, $object, $args);
    self::error("No method named '%s'.", $name);
    return;
  }

  public static function output(/* ... */) {
    if (defined('IS_PHPUNIT_TEST')) return;
    $message = call_user_func_array('sprintf', func_get_args());
    echo self::OUTPUT_PREFIX, $message, "\n";
    return;
  }

  public static function error(/* ... */) {
    $bt       = debug_backtrace();
    $src_file = $bt[0]['file'];
    $src_line = $bt[0]['line'];
    $message  = call_user_func_array('sprintf', func_get_args()) . "\n\t@ $src_file:$src_line\n";
    throw new PDeploy\Exception($message);
    return;
  }

};
