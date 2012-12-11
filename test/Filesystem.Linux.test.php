<?php
/**
 * [PLACEHOLDER]
**/

class Test_Class_Filesystem_Linux extends PDeploy_Unit_Test {

  private $pd = null;

  public function setUp( ) {
    parent::setUp();
    $this->pd = PDeploy::init();
    return;
  }

  public function tearDown( ) {
    $this->pd = null;
    parent::tearDown();
    return;
  }

  public function test_assertReadable_positive( ) {
    $this->assertNull($this->pd->assertReadable('bootstrap.php'));
    return;
  }

  /**
   * @expectedException         PDeploy\Exception
   * @expectedExceptionMessage  unreadable
   */
  public function test_assertReadable_negative( ) {
    $this->pd->assertReadable('/proc/kcore');
    return;
  }

  public function test_assertWritable_positive( ) {
    $this->assertNull($this->pd->assertWritable('bootstrap.php'));
    return;
  }

  /**
   * @expectedException         PDeploy\Exception
   * @expectedExceptionMessage  unwritable
   */
  public function test_assertWritable_negative( ) {
    $this->pd->assertWritable('/proc/meminfo');
    return;
  }

  public function test_assertFile_positive( ) {
    $this->assertNull($this->pd->assertFile('bootstrap.php'));
    return;
  }

  /**
   * @expectedException         PDeploy\Exception
   * @expectedExceptionMessage  not a file
   */
  public function test_assertFile_negative( ) {
    $this->pd->assertFile('/');
    return;
  }

  public function test_assertDirectory_positive( ) {
    $this->pd->assertDirectory('.');
    return;
  }

  /**
   * @expectedException         PDeploy\Exception
   * @expectedExceptionMessage  not a directory
   */
  public function test_assertDirectory_negative( ) {
    $this->pd->assertDirectory('bootstrap.php');
    return;
  }

  public function test_touch( ) {
    $file = 'test-doesnt-exist-yet';
    $this->assertFalse(is_file($file));
    $this->pd->touch($file);
    $this->assertTrue(is_file($file));
    $this->assertTrue(unlink($file));
    return;
  }

  public function test_copy( ) {
    $a = 'test-file-a';
    $b = 'test-file-b';
    $this->assertFalse(is_file($a));
    $this->assertFalse(is_file($b));
    touch($a);
    $this->assertTrue(is_file($a));
    $this->pd->copy($a, $b);
    $this->assertTrue(is_file($b));
    $this->assertTrue(unlink($a));
    $this->assertTrue(unlink($b));
    return;
  }

  public function test_move( ) {
    $a = 'test-file-a';
    $b = 'test-file-b';
    $this->assertFalse(is_file($a));
    $this->assertFalse(is_file($b));
    touch($a);
    $this->assertTrue(is_file($a));
    $this->pd->move($a, $b);
    $this->assertFalse(is_file($a));
    $this->assertTrue(is_file($b));
    $this->assertTrue(unlink($b));
    return;
  }

  public function test_delete( ) {
    $foo = 'test-file-foo';
    $this->assertFalse(is_file($foo));
    touch($foo);
    $this->assertTrue(is_file($foo));
    $this->pd->delete($foo);
    $this->assertFalse(is_file($foo));
    return;
  }

  public function test_mkdir( ) {
    $dirname = 'test-directory';
    $this->assertFalse(is_dir($dirname));
    $this->pd->mkdir($dirname);
    $this->assertTrue(is_dir($dirname));
    $this->assertTrue(is_readable($dirname));
    $this->assertTrue(is_writable($dirname));
    $this->assertTrue(rmdir($dirname));
    return;
  }

  public function test_tempFile( ) {
    $temp_file = $this->pd->tempFile();
    $pathinfo = pathinfo($temp_file);
    $this->assertTrue(is_file($temp_file));
    $this->assertTrue(is_readable($temp_file));
    $this->assertTrue(is_writable($temp_file));
    $this->assertSame($pathinfo['dirname'], sys_get_temp_dir());
    $this->assertTrue(unlink($temp_file));
    return;
  }

  public function test_tempDir( ) {
    $temp_dir = $this->pd->tempDir();
    $pathinfo = pathinfo($temp_dir);
    $this->assertTrue(is_dir($temp_dir));
    $this->assertTrue(is_readable($temp_dir));
    $this->assertTrue(is_writable($temp_dir));
    $this->assertSame($pathinfo['dirname'], sys_get_temp_dir());
    $this->assertTrue(rmdir($temp_dir));
    return;
  }

  public function test_setFileDepot_positive( ) {
    $depot = 'test-depot';
    $this->assertTrue(mkdir($depot));
    $this->pd->setFileDepot($depot);
    $this->assertTrue(rmdir($depot));
    return;
  }

  /**
   * @expectedException         PDeploy\Exception
   * @expectedExceptionMessage  not a directory
   */
  public function test_setFileDepot_negative( ) {
    $depot = 'test-depot';
    $this->assertFalse(is_dir($depot));
    $this->pd->setFileDepot($depot);
    return;
  }

  /**
   * @depends test_setFileDepot_positive
   */
  public function test_getFileDepot( ) {
    $depot = 'test-depot';
    $this->assertTrue(mkdir($depot));
    $this->pd->setFileDepot($depot);
    $this->assertSame($depot . '/', $this->pd->getFileDepot());
    $this->assertTrue(rmdir($depot));
    return;
  }

  public function test_installFile( ) {
    $file = 'some-data.txt';
    $dir = './';
    $path = $dir . $file;
    $this->pd->setFileDepot('file-depot');
    $this->pd->installFile($file, $dir);
    $this->pd->assertFile($path);
    $this->assertTrue(unlink($path));
    return;
  }

};
