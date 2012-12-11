<?php
/**
 * [PLACEHOLDER]
**/

abstract class DBUnit_Mods_Test extends PHPUnit_Extensions_Database_TestCase {

  // only instantiate pdo once for test clean-up/fixture load
  private static $pdo = null;
  // only instantiate PHPUnit_Extensions_Database_DB_IDatabaseConnection once per test
  private $conn = null;

  final public function getConnection( ) {
    global $db;
    if ($this->conn === null) {
      if (self::$pdo == null) self::$pdo = new PDO(DB_ENGINE . ':host=' . DB_HOSTNAME . ';port=' . DB_PORT . ';dbname=' . DB_DATABASE . ';', DB_USERNAME, DB_PASSWORD);
      $this->conn = $this->createDefaultDBConnection(self::$pdo, DB_DATABASE);
    }
    return $this->conn;
  }

  public function getDataSet( ) {
    // Default to an empty data set
    // overload to pre-populate with data
    return new PHPUnit_Extensions_Database_DataSet_DefaultDataSet();
  }

  public function assertArrayEqualsTable($array_expected, $sql_actual) {
    $tbl_expected = new DBUnit_Mods_ArrayDataSet(array('foo' => $array_expected));
    $tbl_query = $this->getConnection()->createQueryTable('foo', $sql_actual);
    $this->assertTablesEqual($tbl_expected->getTable('foo'), $tbl_query);
    return;
  }

  public function assertTableCount($count, $table) {
    $this->assertSame($count, $this->getConnection()->getRowCount($table), "Database table '$table' does not contain $count rows.");
    return;
  }

  public function assertTableEmpty($tables) {
    if (!is_array($tables)) $tables = array($tables);
    foreach ($tables as $name) $this->assertSame(0, $this->getConnection()->getRowCount($name), "Database table '$name' is not empty.");
    return;
  }

  public function assertTableNotEmpty($tables) {
    if (!is_array($tables)) $tables = array($tables);
    foreach ($tables as $name) $this->assertNotSame(0, $this->getConnection()->getRowCount($name), "Database table '$name' is empty.");
    return;
  }

};
