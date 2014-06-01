<?
require_once 'config.php';
require_once 'facebook.php';
$facebook = new Facebook(array(
	'appId'  => FACEBOOK_APP_ID,
	'secret' => FACEBOOK_SECRET
));

/*
$fp = fopen("text.txt",'w');
foreach($_REQUEST as $key => $value) {
fwrite($fp, $key.": ");
fwrite($fp, $value."\n");
}
fwrite($fp, $uid);
*/

$request = $facebook->getSignedRequest();
$uid = $request['user_id'];

if($uid) {
	//set remove time in database
	calldb("UPDATE fbuser SET removed = UNIX_TIMESTAMP(), wins = 0, loses = 0 WHERE uid = $uid; ");
}
?>