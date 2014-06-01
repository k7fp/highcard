<?
// by Kevin Fung

//master switch for mysql queries, can use regular mysql query or pdo query: 0 = PDO, 1 = mysql
define('QUERY_TYPE', 'PDO');

define('DATABASE_USERNAME', '');
define('DATABASE_PASSWORD', '');
define('DATABASE_NAME', '');
define('DATABASE_HOST', 'localhost');

//connect to database
try {
	if(QUERY_TYPE == 'PDO')
		$dbh = new PDO("mysql:host=".DATABASE_HOST.";dbname=".DATABASE_NAME, DATABASE_USERNAME, DATABASE_PASSWORD, array(PDO::ATTR_PERSISTENT => false, PDO::ATTR_EMULATE_PREPARES => true, PDO::MYSQL_ATTR_USE_BUFFERED_QUERY => true));
	else {
		$mysql_link = mysql_connect(DATABASE_HOST, DATABASE_USERNAME, DATABASE_PASSWORD);
		mysql_select_db(DATABASE_NAME);
	}
} catch (exception $e) {}

//define a few things here
define('FACEBOOK_APP_ID', '');
//define('FACEBOOK_API_KEY', '');
define('FACEBOOK_SECRET', '');
//define('ACCESS_TOKEN', '');
$http = 'http';
if($_SERVER['HTTPS'] == 'on')
	$http = 'https';
define('APP_CALLBACK_URL', "$http://apps.facebook.com/highcard/");
define('HOST_URL', "");
define('DECK_URL', "cards");
define('EXTENDED_PERMISSIONS', '');
define('ADS_FILE', 'ads.dat');
define('NUM_HEADER_ADS', 3);
define('NUM_FOOTER_ADS', 3);
define('NUM_BIG_ADS', 3);

$adminids = array();

//require functions file
require_once 'functions.php';

//get ads
//$adsarray = getadsarray();
?>