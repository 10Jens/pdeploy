<?php
/**
 * [PLACEHOLDER]
**/

class Test_Class_Optimizer extends PDeploy_Unit_Test {

  const STYLE_GLOBAL = 'client-assets/global.css';
  const STYLE_PAGE   = 'client-assets/page.css';
  const STYLE_MIN    = 'client-assets/style.min.css';
  const SCRIPT_1     = 'client-assets/script_1.js';
  const SCRIPT_2     = 'client-assets/script_2.js';
  const SCRIPT_3     = 'client-assets/script_3.js'; // empty file
  const SCRIPT_MIN_1 = 'client-assets/script_1.min.js';
  const SCRIPT_MIN_2 = 'client-assets/script_2.min.js';

  private static $o = null;

  public static function setUpBeforeClass( ) {
    self::$o = new PDeploy\Optimizer();
    return;
  }

  public function test_crush_css( ) {
    $dest = self::STYLE_MIN;
    self::$o->crush($dest, array(
      self::STYLE_GLOBAL,
      self::STYLE_PAGE,
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
    $this->assertTrue(unlink($dest));
    return;
  }

  public function test_crush_javascript( ) {
    $dest = self::SCRIPT_MIN_1;
    self::$o->crush($dest, array(
      self::SCRIPT_1,
      self::SCRIPT_2,
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
/* client-assets/script_1.js */ var x=2;alert(x);
/* client-assets/script_2.js */ function foo(a,b){document.write(a+":"+b)}foo(1,2);
EOF;
    $expected = str_replace('TIMESTAMP_PATTERN', $timestamp_pattern, preg_quote($expected, '/'));
    $actual   = file_get_contents($dest);
    $this->assertRegExp("/$expected/", $actual);
    $this->assertTrue(unlink($dest));
    return;
  }

  /**
   * @depends test_crush_css
   * @depends test_crush_javascript
  **/
  public function test_getMap( ) {
    $expected = array(
      self::STYLE_GLOBAL => self::STYLE_MIN,
      self::STYLE_PAGE   => self::STYLE_MIN,
      self::SCRIPT_1     => self::SCRIPT_MIN_1,
      self::SCRIPT_2     => self::SCRIPT_MIN_1,
    );
    $this->assertSame($expected, self::$o->getMap());
    return;
  }

  /**
   * Bug: If you mapped the same source file into two different .min files, the content map will hold the
   * destination 'Array' for that source file.
   *
   * Fix: There is no logical fix for this because every time that source file is requested, there
   * would need to be a disambiguation as to which .min file should be included (the assumption
   * being that each alternative .min for that source could/will include other, conflicting, content
   * as well - otherwise, there wouldn't be a duplicate).
   *
   * @expectedException         PDeploy\Exception
   * @expectedExceptionMessage  it's already been packaged into
  **/
  public function test_bug_1( ) {
    self::$o->crush('fail.js', self::SCRIPT_1); // already crushed into self::SCRIPT_MIN_1
    return;
  }

  /**
   * Bug: If you try to crush() an empty file, PHP will throw a divide by zero error when attempting
   * to generate the pretty meta comment in the header() method.
   *
   * Fix: 1) Silently skip empty files.
   *      2) Only output the "ratio" header if the denominator for the calculation is nonzero.
  **/
  public function test_bug_2( ) {
    self::$o->crush(self::SCRIPT_MIN_2, self::SCRIPT_3); // SCRIPT_3 is empty
    $this->assertTrue(unlink(self::SCRIPT_MIN_2));
    return;
  }

};
