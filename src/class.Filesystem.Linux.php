<?php
/**
 * [PLACEHOLDER]
**/

namespace PDeploy;

require 'interface.Filesystem.php';


class Filesystem_Linux implements FilesystemInterface {

  public function assertReadable($file) {
    if (!is_readable($file)) \PDeploy::error("File '%s' unreadable.", $file);
    return;
  }

  public function assertWritable($file) {
    if (!is_writable($file)) \PDeploy::error("File '%s' unwritable.", $file);
    return;
  }

  public function assertFile($file) {
    if (!is_file($file)) \PDeploy::error("'%s' is not a file.", $file);
    return;
  }

  public function assertDirectory($dir) {
    if (!is_dir($dir)) \PDeploy::error("'%s' is not a directory.", $dir);
    return;
  }

  public function touch($file, $ownership = 0755) {
    if (!\touch($file)) \PDeploy::error("Failed to touch '%s'.", $file);
    if (!chmod($file, $ownership)) \PDeploy::error("Could not change permissions on '%s' to %o.", $file, $ownership);
    return;
  }

  public function copy($from, $to) {
    if (!\copy($from, $to)) \PDeploy::error("Failed to copy '%s' to '%s'.", $from, $to);
    return;
  }

  public function move($from, $to) {
    if (!rename($from, $to)) \PDeploy::error("Failed to move '%s' to '%s'.", $from, $to);
    return;
  }

  public function delete($file) {
    if (!unlink($file)) \PDeploy::error("Failed to delete '%s'.", $file);
    return;
  }

  public function mkdir($name, $ownership = 0755, $recursive = true) {
    if (!\mkdir($name, $ownership, $recursive)) \PDeploy::error("Failed to create directory '%s'.", $name);
    return;
  }

  public function symlink($target, $link) {
    if (!\symlink($target, $link)) \PDeploy::error("Failed to create symlink '%s' -> '%s'.", $target, $link);
    return;
  }

  public function tempFile( ) {
    $name = tempnam(sys_get_temp_dir(), self::TEMP_FILE_PREFIX);
    $this->assertFile($name);
    $this->assertWritable($name);
    return $name;
  }

  public function tempDir( ) {
    $name = $this->tempFile();
    $this->delete($name);
    $this->mkdir($name);
    return $name;
  }

  public function setFileDepot($dirname) {
    $this->assertDirectory($dirname);
    $this->assertWritable($dirname);
    $this->_file_depot = $dirname;
    if (substr($this->_file_depot, -1) !== '/') $this->_file_depot .= '/';
    return;
  }

  public function getFileDepot( ) {
    return $this->_file_depot;
  }

  public function installFile($file, $directory, $ownership = 0755) {
    $src = $this->getFileDepot() . $file;
    $this->assertFile($src);
    $this->assertReadable($src);
    $this->assertDirectory($directory);
    $this->assertWritable($directory);
    $dest = $directory . (substr($directory, -1) !== '/' ? '/' : '') . $file;
    if (!is_file($dest)) $this->copy($src, $dest);
    if (!chmod($dest, $ownership)) \PDeploy::error("Could not change permissions on '%s' to %o.", $dest, $ownership);
    return;
  }

  private $_file_depot = null;

};
