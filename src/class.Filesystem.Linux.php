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

  public function assertSymlink($name, $target = null) {
    $rl = @readlink($name);
    if (!$rl) \PDeploy::error("'%s' is not a symlink.", $name);
    if ($target !== null && $rl !== $target) \PDeploy::error("'%s' is not a symlink to '%s'.", $name, $target);
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

  public function symlink($target, $name) {
    if (!\symlink($target, $name)) \PDeploy::error("Failed to create symlink '%s' -> '%s'.", $target, $name);
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

  /**
   * If $dirname is given as an array, when installFile() is called, each directory in the array
   * is scanned for the desired file - the file used will be from the first such directory to contain
   * a match. The directories are scanned in the order provided to this method.
   * This is useful for having defaults/overrides in cases where conditional builds are required.
   */
  public function setFileDepot($dirname) {
    if (!is_array($dirname)) $dirname = array($dirname);
    foreach ($dirname as $d) {
      $this->assertDirectory($d);
      $this->assertWritable($d);
      if (substr($d, -1) !== '/') $d .= '/';
      $this->_file_depot[] = $d;
    }
    return;
  }

  public function getFileDepot( ) {
    return $this->_file_depot;
  }

  /**
   * See notes for setFileDepot() for usage with multiple depots.
   */
  public function installFile($file, $directory, $ownership = 0755) {
    foreach ($this->getFileDepot() as $depot) {
      $src = $depot . $file;
      if (is_readable($src)) {
        $this->assertFile($src);
        $this->assertDirectory($directory);
        $this->assertWritable($directory);
        $dest = $directory . (substr($directory, -1) !== '/' ? '/' : '') . $file;
        if (!is_file($dest)) $this->copy($src, $dest);
        if (!chmod($dest, $ownership)) \PDeploy::error("Could not change permissions on '%s' to %o.", $dest, $ownership);
        return;
      }
    }
    \PDeploy::error("Could not find file '%s' in any configured depot location ('%s').", $file, implode(',', $this->getFileDepot()));
  }

  public function installDirectory($directory, $ownership = 0755) {
    if (!is_dir($directory)) $this->mkdir($directory);
    if (!chmod($directory, $ownership)) \PDeploy::error("Could not change permissions on '%s' to '%o.", $directory, $ownership);
    return;
  }

  public function installSymlink($target, $name) {
    if (($current_target = @readlink($name)) != $target) {
      if ($current_target != false) $this->delete($name);
      $this->symlink($target, $name);
    }
    return;
  }

  private $_file_depot = array();

};
