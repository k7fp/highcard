<?
//By Kevin Fung

require( 'config.php' );

try {

	//create tables and indexes
	$sql .= " DROP TABLE IF EXISTS `fbuser`; ";
	$sql .= " CREATE TABLE `fbuser`  (";
	$sql .= "   uid					BIGINT NOT NULL, ";
	$sql .= "   installed			BIGINT NOT NULL DEFAULT 0, ";
	$sql .= "   removed				BIGINT NOT NULL DEFAULT 0, ";
	$sql .= "   lastActive			BIGINT NOT NULL DEFAULT 0, ";
	$sql .= "   wins				BIGINT NOT NULL DEFAULT 0, ";
	$sql .= "   loses				BIGINT NOT NULL DEFAULT 0, ";
	$sql .= " PRIMARY KEY(uid) ";
	$sql .= " ) ENGINE = InnoDB ; ";
	
	$sql .= " DROP TABLE IF EXISTS `draw`; ";
	$sql .= " CREATE TABLE `draw`  (";
	$sql .= "   id          	BIGINT NOT NULL AUTO_INCREMENT, ";
	$sql .= "   uid_from		BIGINT NOT NULL DEFAULT 0, ";
	$sql .= "   uid_to			BIGINT NOT NULL DEFAULT 0, ";
	$sql .= "   time			BIGINT NOT NULL DEFAULT 0, ";
	$sql .= "   from_pick		INT(2) NOT NULL DEFAULT 0, ";
	$sql .= "   to_pick			INT(2) NOT NULL DEFAULT 0, ";
	$sql .= " PRIMARY KEY (id), ";
	$sql .= " FOREIGN KEY (uid_from) REFERENCES fbuser(uid) ON UPDATE CASCADE ON DELETE CASCADE ";
	$sql .= " ) ENGINE = InnoDB ; ";
	$sql .= " CREATE INDEX uid_to USING BTREE ON post(uid_to) ; ";
	
	$result = calldb($sql);
	
} catch (exception $e) {

	//catch exception and die
	print "Error!: " . $e->getMessage() . "<br/>";
   	die;
	
}

//output success message
print"<center><br><br><br><br><br>Table Created, delete this file.</center>";
die;

/*
add stats view manually into database
=================
CREATE VIEW stats AS SELECT (SELECT COUNT(*) FROM fbuser WHERE installed > 0) AS totalusers,
						    (SELECT COUNT(*) FROM fbuser WHERE UNIX_TIMESTAMP() - installed < 86400) AS installed24,
						    (SELECT COUNT(*) FROM fbuser WHERE UNIX_TIMESTAMP() - removed < 86400) AS removed24,
						    (SELECT COUNT(*) FROM fbuser WHERE UNIX_TIMESTAMP() - lastActive < 86400) AS lastactive24,

						    (SELECT COUNT(*) FROM fbuser WHERE UNIX_TIMESTAMP() - installed < 2629744) AS installedmonth,
						    (SELECT COUNT(*) FROM fbuser WHERE UNIX_TIMESTAMP() - removed < 2629744) AS removedmonth,
						    (SELECT COUNT(*) FROM fbuser WHERE UNIX_TIMESTAMP() - lastActive < 2629744) AS lastactivemonth,

						    (SELECT COUNT(*) FROM checkin) AS checkins,
						    (SELECT COUNT(*) FROM selfcheckin) AS selfcheckins
=================
*/
?>