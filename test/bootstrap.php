<?php
/**
 * [PLACEHOLDER]
**/

define('ROOT_DIR',    realpath('..') . '/');
define('SRC_DIR',     ROOT_DIR . 'src/');
define('TEST_DIR',    ROOT_DIR . 'test/');
define('DBMODS_DIR',  ROOT_DIR . 'externals/dbunit-mods/');

define('IS_PHPUNIT_TEST', true);

require TEST_DIR . 'db-creds.php';
require TEST_DIR . 'class.array_dataset.php';
require TEST_DIR . 'class.dbunit-mods.php';
require TEST_DIR . 'class.PDeployUnitTest.php';

require ROOT_DIR . 'src/class.PDeploy.php';
