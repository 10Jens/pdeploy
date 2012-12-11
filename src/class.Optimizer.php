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
    $input_size  = 0;
    $output_size = 0;
    if (!touch($dest)) trigger_error("Could not touch destination file '$file'.", E_USER_ERROR);
    // What kind of content is this?
    $pathinfo = pathinfo($dest);
    switch ($pathinfo['extension']) {
      case 'css': $callback = array($this, 'compressCss');        break;
      case 'js':  $callback = array($this, 'compressJavascript'); break;
      default: trigger_error("Could not determine minification handler based on file extension.", E_USER_ERROR);
    }
    // Loop through and compile each source file.
    foreach ($sources as $source) {
      if (!is_readable($source)) trigger_error("'$source' is not readable.", E_USER_ERROR);
      $compiled    = call_user_func($callback, $source);
      $output_text .= "/* $source */ $compiled\n";
      $input_size  += filesize($source);
      $output_size += strlen($compiled);
    }
    // Attach some metadata and save the crushed output into $destination.
    $output_text = $this->header(count($sources), $input_size, $output_size) . $output_text;
    if (!file_put_contents($dest, $output_text)) trigger_error("Failed to save output to file '$dest'.", E_USER_ERROR);
    return;
  }

  /************************************************************************************************/
  /* Privates
  /************************************************************************************************/
  private function compressCss($file) {
    if (!($temp = tempnam(sys_get_temp_dir(), 'pdeploy-temp-'))) trigger_error("Could not create a temporary file.", E_USER_ERROR);
    $cmd = "java -jar '%s' '%s' -o '%s';";
    $cmd = sprintf($cmd, escapeshellcmd(realpath(dirname(__FILE__)) . '/' . self::YUI_COMPRESSOR), escapeshellcmd($file), escapeshellcmd($temp));
    if (system($cmd) === false) trigger_error("system() error with \"$cmd\"", E_USER_ERROR);
    $retval = str_replace("\n", '', file_get_contents($temp));
    if (!unlink($temp)) trigger_errer("Could not delete temporary file '$temp'.", E_USER_WARNING);
    return $retval;
  }

  private function compressJavascript($file) {
    // Make the Closure POST request.
    $source_code = file_get_contents($file);
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, 'http://closure-compiler.appspot.com/compile');
    curl_setopt($ch, CURLOPT_HEADER, 0);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query(array(
      'compilation_level' => 'SIMPLE_OPTIMIZATIONS',
      'output_format'     => 'json',
      'output_info'       => 'compiled_code',
      'warning_level'     => 'VERBOSE',
      'js_code'           => $source_code,
    )));
    // Check the response.
    $response_object = json_decode(curl_exec($ch));
    return str_replace("\n", '', $response_object->compiledCode);
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
    $TB = 1099511627776;
    $GB = 1073741824;
    $MB = 1048576;
    $KB = 1024;
    if     ($bytesize >= $TB) return number_format($bytesize / $TB, 2) . ' TB';
    elseif ($bytesize >= $GB) return number_format($bytesize / $GB, 2) . ' GB';
    elseif ($bytesize >= $MB) return number_format($bytesize / $MB, 2) . ' MB';
    elseif ($bytesize >= $KB) return number_format($bytesize / $KB, 2) . ' KB';
    else                      return number_format($bytesize, 2) .  ' B';
  }

  /************************************************************************************************/
  /* State
  /************************************************************************************************/
  const YUI_COMPRESSOR = 'yuicompressor-2.4.7.jar';
  private $_delete     = array();

};
