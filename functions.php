<?
// by Kevin Fung

//helper function to use either standard mysql functions or PDO
function calldb($query, $return = 0) {
	global $dbh, $mysql_link;
	try { 
		if(QUERY_TYPE == 'PDO') {
			if($return == 1) {
				$statement = $dbh->prepare($query);
				$statement->execute();
				$result = $statement->fetchAll();
				return $result;
			} else
			if($dbh->exec($query) !== false)
				return true;
		} else {
			if($return == 1) {
				$result = array();
				$temp = mysql_query($query, $mysql_link);
				while($row = mysql_fetch_assoc($temp))
					array_push($result, $row);
				$result[0] = $result;
				return $result[0];
			} else
			if(mysql_query($query) !== false)
				return true;
		}
	} catch (Exception $e) {
		print "Server too busy. Please try again shortly";	
	}
	return false;
}

//check if user is app user and/or if uid matches that of its key
function checkUser($uid, $installed = 0) {
	if($installed)
		$installedstr = "installed = UNIX_TIMESTAMP(), ";
	calldb("INSERT IGNORE INTO fbuser (uid, installed, lastActive, removed) VALUES ($uid, UNIX_TIMESTAMP(), UNIX_TIMESTAMP(), 0) ON DUPLICATE KEY UPDATE $installedstr lastActive = UNIX_TIMESTAMP(), removed = 0;");
}

//function to redirect user to facebook's login page
function redirectParent($url) {
	print"
	<script language='JavaScript' type='text/javascript'>
	<!--
	parent.location.href = '$url';
	//-->
	</script>
    ";
	print $url;
	die;
}

//get card name
function cardname($num) {
	
	$card_array = array(
		1 => '2 of Diamonds',
		2 => '2 of Clubs',
		3 => '2 of Hearts',
		4 => '2 of Spades',
		
		5 => '3 of Diamonds',
		6 => '3 of Clubs',
		7 => '3 of Hearts',
		8 => '3 of Spades',
		
		9 => '4 of Diamonds',
		10 => '4 of Clubs',
		11 => '4 of Hearts',
		12 => '4 of Spades',
		
		13 => '5 of Diamonds',
		14 => '5 of Clubs',
		15 => '5 of Hearts',
		16 => '5 of Spades',
		
		17 => '6 of Diamonds',
		18 => '6 of Clubs',
		19 => '6 of Hearts',
		20 => '6 of Spades',
		
		21 => '7 of Diamonds',
		22 => '7 of Clubs',
		23 => '7 of Hearts',
		24 => '7 of Spades',
		
		25 => '8 of Diamonds',
		26 => '8 of Clubs',
		27 => '8 of Hearts',
		28 => '8 of Spades',
		
		29 => '9 of Diamonds',
		30 => '9 of Clubs',
		31 => '9 of Hearts',
		32 => '9 of Spades',
		
		33 => '10 of Diamonds',
		34 => '10 of Clubs',
		35 => '10 of Hearts',
		36 => '10 of Spades',
		
		37 => 'Jack of Diamonds',
		38 => 'Jack of Clubs',
		39 => 'Jack of Hearts',
		40 => 'Jack of Spades',
		
		41 => 'Queen of Diamonds',
		42 => 'Queen of Clubs',
		43 => 'Queen of Hearts',
		44 => 'Queen of Spades',
		
		45 => 'King of Diamonds',
		46 => 'King of Clubs',
		47 => 'King of Hearts',
		48 => 'King of Spades',
		
		49 => 'Ace of Diamonds',
		50 => 'Ace of Clubs',
		51 => 'Ace of Hearts',
		52 => 'Ace of Spades'
	);
	
	return $card_array[$num];
}

/* returns a result form url */
function curl_get_result($url, $post = 0, $data = NULL) {
	
	$ch = curl_init();
	$timeout = 5;
	curl_setopt($ch,CURLOPT_URL,$url);
	curl_setopt($ch,CURLOPT_RETURNTRANSFER,1);
	curl_setopt($ch,CURLOPT_CONNECTTIMEOUT,$timeout);
	if($post) {
		curl_setopt($ch, CURLOPT_POST, 1);
		curl_setopt($ch, CURLOPT_POSTFIELDS, $data);	
	}
	$data = curl_exec($ch);
	curl_close($ch);
	
	return $data;
}

//get all ads
function getadsarray() {
	$adsarray = array();
	if(file_exists(ADS_FILE) && filesize(ADS_FILE) > 0) {
		$fp = fopen(ADS_FILE,'r');
		$adscompressed = fread($fp, filesize(ADS_FILE));
		$adsarray = unserialize(base64_decode($adscompressed));
		fclose($fp);
	}
	return $adsarray;
}

//get ads for a specified section
function getad($section) {
	return '';
}

//get time since
function timedifference($time) {
	$time = time() - $time;
	if($time < 60)
		$interval = "s";
	elseif($time >= 60 && $time<60*60)
		$interval = "n";
	elseif($time >= 60*60 && $time<60*60*24)
		$interval = "h";
	//elseif($time >= 60*60*24 && $time<60*60*24*7)
	elseif($time >= 60*60*24*365)
		$interval = "y";
	else
		$interval = "d";
	/*
	elseif($time >= 60*60*24*7 && $time <60*60*24*30)
		$interval = "ww";
	elseif($time >= 60*60*24*30 && $time <60*60*24*365)
		$interval = "m";
	
	*/
	switch($interval) {
		case "m":
			$months_difference = floor($time / 60 / 60 / 24 / 29);
			while (mktime(date("H", $datefrom), date("i", $datefrom),date("s", $datefrom), date("n", $datefrom)+($months_difference),date("j", $dateto), date("Y", $datefrom)) < $dateto)
				$months_difference++;
			$datediff = $months_difference;
			if($datediff==12)
				$datediff--;
			$res = ($datediff==1) ? "$datediff month" : "$datediff months";
		break;
		
		case "y":
			$datediff = floor($time / 60 / 60 / 24 / 365);
			$res = ($datediff==1) ? "$datediff year" : "$datediff years";
		break;
		
		case "ww":
			$datediff = floor($time / 60 / 60 / 24 / 7);
			$res = ($datediff==1) ? "$datediff week" : "$datediff weeks";
		break;
		
		case "d":
			$datediff = floor($time / 60 / 60 / 24);
			$res = ($datediff==1) ? "$datediff day" : "$datediff days";
		break;
		
		case "h":
			$datediff = floor($time / 60 / 60);
			$res = ($datediff==1) ? "$datediff hour" : "$datediff hours";
		break;
		
		case "n":
			$datediff = floor($time / 60);
			$res = ($datediff==1) ? "$datediff minute" : "$datediff minutes";
		break;
		
		case "s":
			$datediff = $time;
			$res = ($datediff==1) ? "$datediff second" : "$datediff seconds";
		break;
	}
	return $res;
}
?>