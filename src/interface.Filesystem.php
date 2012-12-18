<?php
/**
 * [PLACEHOLDER]
**/

namespace PDeploy;


interface FilesystemInterface {

  const TEMP_FILE_PREFIX = 'pdi-';

  public function assertReadable($file);
  public function assertWritable($file);
  public function assertFile($file);
  public function assertDirectory($dir);
  public function touch($file, $ownership);
  public function copy($from, $to);
  public function move($from, $to);
  public function delete($file);
  public function mkdir($name, $ownership, $recursive);
  public function symlink($target, $link);
  public function tempFile( );
  public function tempDir( );
  public function setFileDepot($dirname);
  public function getFileDepot( );
  public function installFile($file, $directory, $ownership);

};
