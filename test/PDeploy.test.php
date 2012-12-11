<?php
/**
 * [PLACEHOLDER]
**/

class Test_Class_PDeploy extends PDeploy_Unit_Test {

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

  public function test_requireVersion_positive( ) {
    $this->pd->requireVersion('1.2.3'); // obviously wayyy old
    return;
  }

  /**
   * @expectedException         PDeploy\Exception
   * @expectedExceptionMessage  required - found version
   */
  public function test_requireVersion_negative( ) {
    $this->pd->requireVersion('8.9.7'); // not even in the forseeable future
    return;
  }

  public function test_requireExtension_positive( ) {
    $this->pd->requireExtension('pcre'); // could be any extension which is supposed to be enabled by default
    return;
  }

  /**
   * @expectedException         PDeploy\Exception
   * @expectedExceptionMessage  Extension abcdef required.
   */
  public function test_requireExtension_negative( ) {
    $this->pd->requireExtension('abcdef');
    return;
  }

};
