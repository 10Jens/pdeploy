<?php
/**
 * [PLACEHOLDER]
**/

class DBUnit_Mods_ArrayDataSet extends PHPUnit_Extensions_Database_DataSet_AbstractDataSet {

  protected $_tables = array();

  public function __construct($data) {
    foreach ($data as $tableName => $rows) {
      $columns = array();
      if (isset($rows[0])) $columns = array_keys($rows[0]);
      $metaData = new PHPUnit_Extensions_Database_DataSet_DefaultTableMetaData($tableName, $columns);
      $table    = new PHPUnit_Extensions_Database_DataSet_DefaultTable($metaData);
      foreach ($rows as $row) $table->addRow($row);
      $this->_tables[$tableName] = $table;
    }
    return;
  }

  protected function createIterator($reverse = false) {
    return new PHPUnit_Extensions_Database_DataSet_DefaultTableIterator($this->_tables, $reverse);
  }

  public function getTable($tableName) {
    if (!isset($this->_tables[$tableName])) {
      throw new InvalidArgumentException("$tableName is not a table in the current database.");
    }
    return $this->_tables[$tableName];
  }

};
