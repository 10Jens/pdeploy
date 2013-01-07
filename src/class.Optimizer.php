<?php
/**
 * [PLACEHOLDER]
**/

namespace PDeploy;


class Optimizer {

  public function crush($dest, $sources) {
    if (!is_array($sources)) $sources = array($sources); // allow single files or arrays
    $callback    = null;
    $output_text = '';
    $num_files   = 0;
    $input_size  = 0;
    $output_size = 0;
    if (!touch($dest)) \PDeploy::error("Could not touch destination file '%s'.", $file);
    // What kind of content is this?
    $pathinfo = pathinfo($dest);
    switch ($pathinfo['extension']) {
      case 'css': $callback = array($this, 'compressCss');        break;
      case 'js':  $callback = array($this, 'compressJavascript'); break;
      default: \PDeploy::error("Could not determine minification handler based on file extension.");
    }
    // Make sure that we don't already have a mapping for any of these source files (see bug #1).
    foreach ($sources as $source) {
      if (array_key_exists($source, $this->_map)) \PDeploy::error("Cannot crush '%s' into '%s'; it's already been packaged into '%s'.", $source, $dest, $this->_map[$source]);
    }
    // Loop through and compile each source file.
    foreach ($sources as $source) {
      if (!is_readable($source)) \PDeploy::error("'%s' is not readable.", $source);
      if (!filesize($source)) {
        \PDeploy::error("'%s' is empty.", $source);
        continue;
      }
      $compiled    = call_user_func($callback, $source);
      $output_text .= "/* $source */ $compiled\n";
      $input_size  += filesize($source);
      $output_size += strlen($compiled);
      $num_files   += 1;
    }
    if ($num_files > 0) {
      $this->_map = array_merge_recursive($this->_map, array_fill_keys($sources, $dest));
      // Attach some metadata and save the crushed output into $destination.
      $output_text = $this->header($num_files, $input_size, $output_size) . $output_text;
      if (!file_put_contents($dest, $output_text)) \PDeploy::error("Failed to save output to file '%s'.", $dest);
    }
    return;
  }

  public function getMap( ) {
    return $this->_map;
  }

  /************************************************************************************************/
  /* Privates
  /************************************************************************************************/
  private function compressCss($file) {
    $args = "-o '%s';";
    return $this->compress(self::CSS_COMPRESSOR, $file, $args);
  }

  private function compressJavascript($file) {
    $args = "--compilation_level SIMPLE_OPTIMIZATIONS --warning_level QUIET --js_output_file '%s';";
    return $this->compress(self::JS_COMPRESSOR, $file, $args);
  }

  private function compress($jar, $file, $args) {
    if (!($temp = tempnam(sys_get_temp_dir(), 'pdeploy-temp-'))) \PDeploy::error("Could not create a temporary file.");
    $command_format = "java -jar '%s' '%s' $args";
    $command = sprintf($command_format, escapeshellcmd(realpath(dirname(__FILE__)) . '/' . self::BIN_DIR . $jar), escapeshellcmd($file), escapeshellcmd($temp));
    if (system($command) === false) \PDeploy::error("system() error with \"$command\"");
    $retval = str_replace("\n", '', file_get_contents($temp));
    if (!unlink($temp)) \PDeploy::error("Could not delete temporary file '%s'.", $temp);
    return $retval;
  }

  private function header($num_files, $input_size, $output_size) {
    $output = "/** generated " . date('Y-m-d H:i:s e') . "\n";
    $output .= " * files:  $num_files\n";
    $output .= " * input:  " . $this->bytesize($input_size) . "\n";
    $output .= " * output: " . $this->bytesize($output_size) . "\n";
    $output .= " * ratio:  " . floor(100 * $output_size / $input_size) . "%\n";
    $output .= "**/\n";
    return $output;
  }

  private function bytesize($bytesize) {
    $KB = 1024;
    $MB = pow(1024, 2);
    $GB = pow(1024, 3);
    $TB = pow(1024, 4);
    if     ($bytesize >= $TB) return number_format($bytesize / $TB, 2) . ' TB';
    elseif ($bytesize >= $GB) return number_format($bytesize / $GB, 2) . ' GB';
    elseif ($bytesize >= $MB) return number_format($bytesize / $MB, 2) . ' MB';
    elseif ($bytesize >= $KB) return number_format($bytesize / $KB, 2) . ' KB';
    else                      return number_format($bytesize, 2) .  ' B';
  }

  /************************************************************************************************/
  /* State
  /************************************************************************************************/
  const BIN_DIR        = 'bin/';
  const CSS_COMPRESSOR = 'yuicompressor-2.4.7.jar';
  const JS_COMPRESSOR  = 'closure-compiler.jar';

  private $_map = array();

};
