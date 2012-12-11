<?php
/**
 * [PLACEHOLDER]
**/

class Test_Class_Database_MySQL extends PDeploy_Unit_Test {

  public static $pd       = null;
  public static $dsn      = '';
  public static $full_dsn = '';

  public static function setUpBeforeClass( ) {
    parent::setUpBeforeClass();
    self::$dsn      = 'mysql:host=' . DB_HOSTNAME . ';port=' . DB_PORT . ';';
    self::$full_dsn = self::$dsn . ';dbname=' . DB_DATABASE . ';';
    self::$pd       = PDeploy::init();
    self::$pd->initCredentials(DB_HOSTNAME, DB_PORT, DB_USERNAME, DB_PASSWORD, DB_DATABASE);
    return;
  }

  public static function tearDownAfterClass( ) {
    self::$pd->pdo()->exec('DROP TABLE `pdeploy_deployment_config`, `pdeploy_deployment_log`;');
    self::$pd->pdo()->exec('DROP TABLE `state`, `user`, `zip_code`;');
    return;
  }

  public function test_exists( ) {
    $this->assertTrue(self::$pd->exists());
    $this->assertFalse(self::$pd->exists('doesntexist_' . mt_rand()));
    return;
  }

  /**
   * @depends test_exists
   */
  public function test_create( ) {
    $foo = 'test_db_' . substr(md5(time()), -10);
    $this->assertTrue(self::$pd->create($foo), "Failed to create database '$foo'.");
    $pdo = new PDO('mysql:host=' . DB_HOSTNAME . ';port=' . DB_PORT . ';', DB_USERNAME, DB_PASSWORD);
    $query = sprintf('DROP DATABASE `%s`;', $foo);
    if ($pdo->exec($query) === false) $this->fail("Failed to drop test database '$foo'.");
    return;
  }

  /**
   * @depends test_create
   */
  public function test_isNew( ) {
    self::$pd->connectDatabase();
    $this->assertTrue(self::$pd->isNew(), "Database is not empty.");
    return;
  }

  /**
   * @depends test_isNew
   * @covers executeFile
   */
  public function test_initMetadata( ) {
    self::$pd->initMetadata();
    // Make sure all three config rows exist.
    $expected = array(
      array('key' => 'created'),
      array('key' => 'modified'),
      array('key' => 'version'),
    );
    $sql = 'SELECT `key` FROM `pdeploy_deployment_config` ORDER BY `key` ASC;';
    $this->assertArrayEqualsTable($expected, $sql);
    // Make sure the version is properly set.
    $expected = array(array(
      'key'   => 'version',
      'value' => 0,
    ));
    $sql = "SELECT `key`, `value` FROM `pdeploy_deployment_config` WHERE `key` = 'version';";
    $this->assertArrayEqualsTable($expected, $sql);
    return;
  }

  /**
   * @depends test_isNew
   * @covers executeFile
   */
  public function test_createStructure( ) {
    self::$pd->createStructure('database-assets/structure.sql');
    $this->assertTableCount(0, 'user');
    return;
  }

  /**
   * @depends test_initMetadata
   */
  public function test_getVersion( ) {
    $this->assertEquals(0, self::$pd->getVersion());
    return;
  }

  /**
   * @depends test_getVersion
   */
  public function test_setVersion( ) {
    $old_version = self::$pd->getVersion();
    $new_version = $old_version + 2;
    self::$pd->setVersion($new_version);
    $this->assertEquals($new_version, self::$pd->getVersion());
    self::$pd->setVersion($old_version);
    $this->assertEquals($old_version, self::$pd->getVersion());
    return;
  }

  /**
   * @depends test_setVersion
   */
  public function test_updateStructure( ) {
    self::$pd->updateStructure('database-assets/patch-files/');
    $this->assertTableCount(0, 'state');
    $this->assertTableCount(0, 'zip_code');
    return;
  }

  /**
   * @depends test_updateStructure
   */
  public function test_importData( ) {
    self::$pd->importData('database-assets/seed-data/');
    $expected = array(
      array(
        'zip'   => '19019',
        'state' => 'PA',
        'city'  => 'Philadelphia',
      ),
      array(
        'zip'   => '60601',
        'state' => 'IL',
        'city'  => 'Chicago',
      ),
      array(
        'zip'   => '94123',
        'state' => 'CA',
        'city'  => 'San Fransisco',
      ),

    );
    $actual = 'SELECT `zip`, `state`, `city` FROM `zip_code` ORDER BY `zip` ASC;';
    $this->assertArrayEqualsTable($expected, $actual);
    // Since we checked zip_code thoroughly, just do a row count check for state.
    $this->assertTableCount(50, 'state');
    // Check the user table to verify that patch-1.php ran correctly.
    $expected = array(array(
      'email'   => 'person2@example.com',
    ));
    $actual = "SELECT `email` FROM `user` WHERE `user_id` = '2';";
    $this->assertArrayEqualsTable($expected, $actual);
    return;
  }

};
