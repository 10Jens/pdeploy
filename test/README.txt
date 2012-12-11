In order to run the test suite, you must create a plain file in this directory called "db-creds.php"

It must contain the following definitions (changed appropriate to your installation):

define('DB_ENGINE',   'mysql');
define('DB_HOSTNAME', 'localhost');
define('DB_PORT',     '3306');
define('DB_USERNAME', 'me');
define('DB_PASSWORD', 'mypassword');
define('DB_DATABASE', 'myschema');
