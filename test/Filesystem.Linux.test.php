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

  public function test_assertSymlink_positive( ) {
    $target = 'boostrap.php';
    $name = 'l';
    $this->assertTrue(symlink($target, $name));
    $this->pd->assertSymlink($name);
    $this->pd->assertSymlink($name, $target);
    $this->assertTrue(unlink($name));
    return;
  }

  /**
   * @expectedException         PDeploy\Exception
   * @expectedExceptionMessage  not a symlink
   */
  public function test_assertSymlink_negative( ) {
    $this->pd->assertSymlink('notalink');
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

  public function test_symlink( ) {
    $target = 'test-directory';
    $name   = 'link-to-dir';
    $this->assertFalse(is_dir($target));
    $this->assertFalse(file_exists($name));
    mkdir($target);
    $this->assertTrue(is_dir($target));
    $this->pd->symlink($target, $name);
    $this->assertSame($target, readlink($name));
    $this->assertTrue(unlink($name));
    $this->assertTrue(rmdir($target));
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
    $depot_1 = 'test-depot-1';
    $depot_2 = 'test-depot-2';
    $this->assertTrue(mkdir($depot_1));
    $this->assertTrue(mkdir($depot_2));
    $this->pd->setFileDepot(array($depot_1, $depot_2));
    $this->assertTrue(rmdir($depot_1));
    $this->assertTrue(rmdir($depot_2));
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
    $this->assertSame(array($depot . '/'), $this->pd->getFileDepot());
    $this->assertTrue(rmdir($depot));
    return;
  }

  public function test_installFile( ) {
    $file = 'some-data.txt';
    $dir = './';
    $path = $dir . $file;
    $this->pd->setFileDepot('file-depot-1');
    $this->pd->installFile($file, $dir);
    $this->pd->assertFile($path);
    $this->assertTrue(unlink($path));
    return;
  }

  public function test_installFile_with_multiple_depots( ) {
    $depots = array(
      'file-depot-1',
      'file-depot-2',
    );
    $files = array(
      'multiple-depot-test-1.txt',
      'multiple-depot-test-2.txt',
    );
    $dir = './';
    $path_1 = $dir . $files[0];
    $path_2 = $dir . $files[1];
    $this->pd->setFileDepot($depots);
    // Install & verify the first file (should come out of depot-1)
    $this->pd->installFile($files[0], $dir);
    $this->pd->assertFile($path_1);
    $this->assertRegExp('/a/', file_get_contents($path_1));
    // Install & verify the second file (should come out of depot-2)
    $this->pd->installFile($files[1], $dir);
    $this->pd->assertFile($path_2);
    $this->assertRegExp('/c/', file_get_contents($path_2));
    // Clean up.
    $this->assertTrue(unlink($path_1));
    $this->assertTrue(unlink($path_2));
    return;
  }

  public function test_installDirectory( ) {
    $dir = 'testing';
    $this->assertFalse(is_dir($dir));
    // Give it a whirl.
    $this->pd->installDirectory($dir);
    $this->assertTrue(is_dir($dir));
    // Try it again to make sure it doesn't whine.
    $this->pd->installDirectory($dir);
    $this->assertTrue(is_dir($dir));
    // Clean up.
    $this->assertTrue(rmdir($dir));
    return;
  }

  /**
   * @depends test_assertSymlink_positive
   * @depends test_assertSymlink_negative
   * @depends test_symlink
   */
  public function test_installSymlink( ) {
    $target     = 'bootstrap.php';
    $target2    = 'phpunit.xml';
    $name       = 'test-link';
    $this->assertTrue(is_file($target));
    $this->assertTrue(is_file($target2));
    $this->assertFalse(@readlink($name));
    // See if we can get a symlink created.
    $this->pd->installSymlink($target, $name);
    $this->pd->assertSymlink($name, $target);
    // We try it again to make sure there isn't an error.
    $this->pd->installSymlink($target, $name);
    $this->pd->assertSymlink($name, $target);
    // Give it a different target and make sure it updates the link.
    $this->pd->installSymlink($target2, $name);
    $this->pd->assertSymlink($name, $target2);
    // Clean up after ourselves.
    $this->assertTrue(unlink($name));
    return;
  }

};
