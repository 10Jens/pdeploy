<?php
/**
 * [PLACEHOLDER]
**/

class Test_Class_Optimizer extends PDeploy_Unit_Test {

  private $o = null;

  public function setUp( ) {
    $this->o = new PDeploy\Optimizer();
    return;
  }

  public function tearDown( ) {
    $this->o = null;
    return;
  }

  public function test_crush_css( ) {
    $dest = 'client-assets/style.min.css';
    $this->o->crush($dest, array(
      'client-assets/global.css',
      'client-assets/page.css',
    ));
    $this->assertTrue(is_file($dest));
    $this->assertTrue(is_readable($dest));
    $this->assertGreaterThan(0, filesize($dest));
    $timestamp_pattern = "\d{4}-\d{2}-\d{2} \d{2}:\d{2}:\d{2} UTC";
    $expected = <<<EOF
/** generated TIMESTAMP_PATTERN
 * files:  2
 * input:  127.00 B
 * output: 77.00 B
 * ratio:  60%
**/
/* client-assets/global.css */ html,body{margin:0;padding:0;color:#0f0}
/* client-assets/page.css */ div{background-color:#f00;color:#00f}
EOF;
    $expected = str_replace('TIMESTAMP_PATTERN', $timestamp_pattern, preg_quote($expected, '/'));
    $actual = file_get_contents($dest);
    $this->assertRegExp("/$expected/", $actual);
    unlink($dest);
    return;
  }

  public function test_crush_javascript( ) {
    $dest = 'client-assets/script.min.js';
    $this->o->crush($dest, array(
      'client-assets/script-1.js',
      'client-assets/script-2.js',
    ));
    $this->assertTrue(is_file($dest));
    $this->assertTrue(is_readable($dest));
    $this->assertGreaterThan(0, filesize($dest));
    $timestamp_pattern = "\d{4}-\d{2}-\d{2} \d{2}:\d{2}:\d{2} UTC";
    $expected = <<<EOF
/** generated TIMESTAMP_PATTERN
 * files:  2
 * input:  166.00 B
 * output: 68.00 B
 * ratio:  40%
**/
/* client-assets/script-1.js */ var x=2;alert(x);
/* client-assets/script-2.js */ function foo(a,b){document.write(a+":"+b)}foo(1,2);
EOF;
    $expected = str_replace('TIMESTAMP_PATTERN', $timestamp_pattern, preg_quote($expected, '/'));
    $actual   = file_get_contents($dest);
    $this->assertRegExp("/$expected/", $actual);
    unlink($dest);
    return;
  }

};
