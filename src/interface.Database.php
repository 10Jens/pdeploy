<?php
/**
 * [PLACEHOLDER]
**/

namespace PDeploy;


interface DatabaseInterface {

  const METADATA_DIRECTORY  = 'metadata/';
  const PATCH_FILE_SQL      = 'patch-%u.sql';
  const PATCH_FILE_PHP      = 'patch-%u.php';

  public function updateDatabase($structure_file, $structure_dir, $data_dir);
  public function exists($dbname);
  public function create($dbname);
  public function isNew( );
  public function initMetadata( );
  public function executeFile($filename, array $replacements);
  public function createStructure($filename);
  public function updateStructure($dirname);
  public function importData($dirname);
  public function getVersion( );
  public function setVersion($version);

};
