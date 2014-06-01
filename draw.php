<?php
require_once 'config.php';
require_once 'facebook.php';
$facebook = new Facebook(array(
	'appId'  => FACEBOOK_APP_ID,
	'secret' => FACEBOOK_SECRET
));

// Get User ID
$uid = $facebook->getUser();

if($uid) {
  try {
    // Proceed knowing you have a logged in user who's authenticated.
    $me = $facebook->api('/me');
  } catch (FacebookApiException $e) {
    error_log($e);
    $uid = null;
  }
} else {
	$output['success'] = 'noauth';
	print json_encode($output);
	die;
}

$return = array();
//see what we are doing
switch($_REQUEST['todo']) {
	case 'draw':
	
		$friendid = $_REQUEST['friendid'];
		//pick a card, but don't let him pick the highest (ace of clubs), or he'd know he won
		$from_pick = rand(2,51);
		$time = time();
		
		calldb("INSERT INTO `draw` (uid_from, uid_to, time, from_pick) VALUES ($uid, $friendid, $time, $from_pick);", 1);
		
		//what was the last inserted id?
		$result = calldb("SELECT id FROM `draw` WHERE uid_from = $uid AND uid_to = $friendid AND time = $time AND from_pick = $from_pick LIMIT 1;", 1);
		$id = $result[0]['id'];
		
		//increment that friend's count
		try {
			$facebook->api(array('method' => 'dashboard.incrementCount', 'uid' => $friendid, 'access_token' => ACCESS_TOKEN));
		} catch (Exception $e) { }
		
		//get friend's gender
		$opponent = $facebook->api('/'.$friendid);
		switch($opponent['gender']) {
			case 'male':
				$opponent_pro = 'he is';
				$opponent_obj = 'him';
				$opponent_adj = 'his';
			break;
			case 'female':
				$opponent_pro = 'she is';
				$opponent_obj = 'her';
				$opponent_adj = 'her';
			break;
			default:
				$opponent_pro = 'they are';
				$opponent_obj = 'them';
				$opponent_adj = 'their';
		}
		
		$return['success'] = 'yes';
		$return['from_pick'] = $from_pick;
		$return['draw_id'] = $id;
		$return['opponent_pro'] = $opponent_pro;
		$return['opponent_obj'] = $opponent_obj;
		$return['opponent_adj'] = $opponent_adj;
		$return['card_name'] = cardname($from_pick);
		$return['opponent_firstname'] = $opponent['first_name'];
		
	break;
	case 'answer':
		$id = $_REQUEST['id'];
		//get data
		$result = calldb("SELECT id, uid_from, from_pick FROM `draw` WHERE id = $id AND uid_to = $uid", 1);
		$id = $result[0]['id'];
		if(!$id)
			$return['error'] = 'yes';
		else {
			$uid_from = $result[0]['uid_from'];
			$from_pick = $result[0]['from_pick'];
			//select a card that isn't already in the deck
			while(true) {
				$to_pick = rand(1,52);
				if($to_pick != $from_pick)
					break;
			}
			
			//get friend's gender
			$opponent = $facebook->api('/'.$uid_from);
			switch($opponent['gender']) {
				case 'male':
					$opponent_pro = 'he is';
					$opponent_obj = 'him';
					$opponent_adj = 'his';
				break;
				case 'female':
					$opponent_pro = 'she is';
					$opponent_obj = 'her';
					$opponent_adj = 'her';
				break;
				default:
					$opponent_pro = 'they are';
					$opponent_obj = 'them';
					$opponent_adj = 'their';
			}
			
			//update db
			if($from_pick > $to_pick)
				$winlose_sql = "UPDATE fbuser SET wins = wins + 1 WHERE uid = $uid_from; UPDATE fbuser SET loses = loses + 1 WHERE uid = $uid; ";
			else
				$winlose_sql = "UPDATE fbuser SET wins = wins + 1 WHERE uid = $uid; UPDATE fbuser SET loses = loses + 1 WHERE uid = $uid_from; ";
			calldb("$winlose_sql UPDATE `draw` SET to_pick = $to_pick WHERE id = $id;", 1);
			
			//return
			$return['success'] = 'yes';
			$return['from_pick'] = $from_pick;
			$return['to_pick'] = $to_pick;
			$return['opponent_pro'] = $opponent_pro;
			$return['opponent_obj'] = $opponent_obj;
			$return['opponent_adj'] = $opponent_adj;
			$return['fromcard_name'] = cardname($from_pick);
			$return['tocard_name'] = cardname($to_pick);
			$return['opponent_firstname'] = $opponent['first_name'];
			$return['friendid'] = $uid_from;
		}
	break;
}
die(json_encode($return));
?>