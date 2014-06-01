<?php
require_once 'config.php';
require_once 'facebook.php';
$facebook = new Facebook(array(
	'appId'  => FACEBOOK_APP_ID,
	'secret' => FACEBOOK_SECRET
));

$avoid_pending = 1;

$view = $_REQUEST['view'];
if(!$view) $view = 'index';
//playing a game?
$game = $_REQUEST['game'];

// Get User ID
$uid = $facebook->getUser();

// We may or may not have this data based on whether the user is logged in.
//
// If we have a $user id here, it means we know the user is logged into
// Facebook, but we don't know if the access token is valid. An access
// token is invalid if the user logged out of Facebook.

if($uid) {
  try {
    // Proceed knowing you have a logged in user who's authenticated.
    $me = $facebook->api('/me');
  } catch (FacebookApiException $e) {
    error_log($e);
    $uid = null;
  }
}

// login or logout url will be needed depending on current user state.
if($me) {
	checkUser($uid, $_REQUEST['installed']);
	/*
	$fql_query = "SELECT ".EXTENDED_PERMISSIONS." FROM permissions WHERE uid = $uid";
	$param = array('method' => 'fql.query', 'query' => $fql_query, 'callback' => '');
	$fql_result = $facebook->api($param);
	$publish_stream = $fql_result[0]['publish_stream'];
	if($publish_stream == 0) {
		$loginUrl = $facebook->getLoginUrl(array('scope' => EXTENDED_PERMISSIONS, 'redirect_uri' => APP_CALLBACK_URL."index.php" ));
		redirectParent($loginUrl);
	}
	*/
	$logoutUrl = $facebook->getLogoutUrl();
} else {
	$loginUrl = $facebook->getLoginUrl(array('redirect_uri' => APP_CALLBACK_URL."index.php?installed=1&game=$game" ));
	redirectParent($loginUrl);
}

//get credits and tillbonus
$result = calldb("SELECT wins, loses FROM fbuser WHERE uid = $uid; ", 1);
$wins = $result[0]['wins'];
$loses = $result[0]['loses'];
/*
//calculate winning percentage
if($wins + $loses > 0) {
	if($loses == 0)
		$percentage = 100;
	else
		$percentage = round(100 * $wins / ($wins + $loses), 1);
	$percentage_str = " ".$percentage."%";	
}
*/

if($game)
	$view = 'friends';
	
//get pending games with friends
$avoid = array();
$pending = calldb("SELECT * FROM draw WHERE to_pick = 0 AND (uid_from = $uid OR uid_to = $uid) ORDER BY id DESC", 1);
$num_pending = sizeof($pending);
for($i = 0; $i < $num_pending; $i++) {
	if($pending[$i]['uid_to'] == $uid) {
		if(!in_array($pending[$i]['uid_from'], $avoid))
			array_push($avoid, $pending[$i]['uid_from']);
	} else {
		if(!in_array($pending[$i]['uid_to'], $avoid))
			array_push($avoid, $pending[$i]['uid_to']);
	}
}

if($view == 'index') {
	//get friends from facebook
	$friends = $facebook->api('/me/friends');
	$friends = $friends['data'];
	
	//is he targeting a friend?
	$opponent = $_REQUEST['opponent'];
	$hasopponent = 0;
	if($opponent) {
		//are we friends?
		$fql_query = "SELECT uid2 FROM friend WHERE uid1 = $uid AND uid2 = $opponent";
		$param = array('method' => 'fql.query', 'query' => $fql_query, 'callback' => '');
		$fql_result = $facebook->api($param);
		$opponent_check = $fql_result[0]['uid2'];
					
		if($opponent == $opponent_check && (($avoid_pending && !in_array($opponent, $avoid)) || !$avoid_pending )) {
			//select name
			$opponent_info = $facebook->api('/'.$opponent);
			$friend_id = $opponent_info['id'];
			$friend_name = $opponent_info['name'];
			$hasopponent = 1;
			$nofriends = 0;
		}
	}
	
	//choose a random friend
	if($friends && $hasopponent == 0) {
		$nofriends = 1;
		$temp = 0;
		while($temp < 50) {
			$rand_key = array_rand($friends);
			$friend_id = $friends[$rand_key]['id'];
			$friend_name = $friends[$rand_key]['name'];
			if($avoid_pending) {
				if(!in_array($friend_id, $avoid)) {
					$nofriends = 0;
					break;
				} else {
					$friend_id = NULL;
					$friend_name = NULL;	
				}
			} else {
				$nofriends = 0;
				break;
			}
			$temp++;
		}
	}
}

//decrement count
//$facebook->api(array('method' => 'dashboard.setCount', 'uid' => $uid, 'count' => 10, 'access_token' => ACCESS_TOKEN));
?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Strict//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-strict.dtd">
<html xmlns="http://www.w3.org/1999/xhtml" xmlns:fb="http://www.facebook.com/2008/fbml">
<head>
<meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
<title>High Card Draw</title> 
<script type='text/javascript' src='js/jquery.js'></script>
<style>
body{
	margin:0;
	font-family:Verdana, Geneva, sans-serif;
	font-size:12px;
}

a{
	color:#3b5998;
	text-decoration:none;
	outline: 0;
}

a:hover{
	text-decoration:underline;	
}

            /* Facebook Tab Style */
            .fbtab
            {
                padding: 8px;
                color: #ffffff;
                font-weight: bold;
				font-size:15px;
                float: left;
                text-decoration: none;
				margin:6px 0px 0 0;
				display:block;
				clear:both;
            }
            .fbtab:hover
            {
                text-decoration: underline;
                cursor: pointer;
				outline:0;
            }
            .fbgreybox
            {
                background-color: #f7f7f7;
                border: 1px solid #cccccc;
                color: #333333;
                padding: 10px;
                font-size: 13px;
                font-weight: bold;
            }

            .fbinfobox
            {
                background-color: #fff9d7;
                border: 1px solid #e2c822;
                color: #333333;
                padding: 10px;
                font-size: 13px;
                font-weight: bold;
            }
            .fbbluebox
            {
                background-color: #eceff6;
                border: 1px solid #d4dae8;
                color: #333333;
                padding: 10px;
                font-size: 13px;
                font-weight: bold;
            }
            .fberrorbox
            {
                background-color: #ffebe8;
                border: 1px solid #dd3c10;
                color: #333333;
                padding: 10px;
                font-size: 13px;
                font-weight: bold;
            }
			#aline a:hover{text-decoration:underline;}

.tabs{padding:0;border-bottom:1px solid #898989}
.ff2 .tabs{padding:3px 0}
.tabs.top{background:#f7f7f7}
.tabs .left_tabs{padding-left:10px;float:left}
.tabs .right_tabs{padding-right:10px;float:right}
.tabs .back_links{padding-right:20px;float:right}
.toggle_tabs{text-align:center;margin-bottom:-1px}
.ff2 .toggle_tabs{margin-bottom:0}
.toggle_tabs li{display:inline;padding:2px 0 3px;background:#f1f1f1 url(/rsrc.php/z3HLG/hash/4x24xq7f.gif) top left repeat-x}
.ie6 .toggle_tabs li,
.ie7 .toggle_tabs li{background-position:left 3px}
.toggle_tabs li a{border:1px solid #898989;border-left:0;color:#333;font-weight:bold;padding:2px 8px 3px 9px; margin-left:-5px; display:inline-block}
.toggle_tabs li a small{font-size:11px;font-weight:normal}
.toggle_tabs li a:focus{outline:0}
.toggle_tabs li.first a{border:1px solid #898989; margin-left:-0;}
.toggle_tabs li a.selected{margin-left:-1px;background:#6d84b4;border:1px solid #3b5998;border-left:1px solid #5973a9;border-right:1px solid #5973a9;color:#fff}
.toggle_tabs li.last a.selected{margin-left:-1px;border-left:1px solid #5973a9;border-right:1px solid #36538f}
.toggle_tabs li.first a.selected{margin:0;border-left:1px solid #36538f;border-right:1px solid #5973a9}
.toggle_tabs li.first.last a.selected{border:1px solid #36538f}
.toggle_tabs li a.selected:hover{text-decoration:none}
.toggle_tabs li a.disabled{color:#999;cursor:default}
.toggle_tabs li a.disabled:hover{text-decoration:none}
.toggle_tabs .hidden{display:none}

.clearfix:after{clear:both;content:".";display:block;font-size:0;height:0;line-height:0;visibility:hidden}
.clearfix{display:block;zoom:1}

ul{list-style-type:none;margin:0;padding:0}
</style>
<script>
var count = 0;
var from_pick;
var card_name;
var draw_id;
var opponent_pro;
var opponent_obj;
var opponent_adj;
var opponent_firstname;
var friendid;

$(document).ready(function() {
	
});

function draw(friendid) {
	$('#drawclick').removeAttr("onclick");
	$('#clickanywhere').html("<div style='width:100%; height:32px; background:url(images/ajax-loader.gif) center no-repeat;'></div>");
	$.ajax({url:'draw.php', data:{todo: 'draw', friendid: friendid, signed_request: '<?=$_REQUEST['signed_request']?>'}, cache: false, dataType:'json', success:function(data) { if(data.success == 'yes') {
		//post?
		from_pick = data.from_pick;
		card_name = data.card_name;
		draw_id = data.draw_id;
		opponent_pro = data.opponent_pro;
		opponent_obj = data.opponent_obj;
		opponent_adj = data.opponent_adj;
		friendid = friendid;
		opponent_firstname = data.opponent_firstname;
		postfeed(draw_id, from_pick, card_name, friendid, opponent_firstname, opponent_pro);
		var temp = parseInt($('#num_pending').html());
		temp++;
		$('#num_pending').text(temp);
		$('#display_normal').hide();
		$('.card_name').text(card_name);
		$('.opponent_firstname').text(opponent_firstname);
		$('.opponent_obj').text(opponent_obj);
		$('.opponent_adj').text(opponent_adj);
		$('#yourcard').attr('src', '<?=HOST_URL?>images/<?=DECK_URL?>/'+from_pick+'.png');
		$('#theircard').attr('src', '<?=HOST_URL?>images/<?=DECK_URL?>/question.png');
		$('#display_drew').css('display','inline');
		//parent.location.href = '<?=APP_CALLBACK_URL?>?view=index&drew=1&id='+data.id+'&friend='+friendid;
	}}});
}

function draw_answer(id) {
	$('.answerclick_'+id).removeAttr("onclick");
	$.ajax({url:'draw.php', data:{todo: 'answer', id: id, signed_request: '<?=$_REQUEST['signed_request']?>'}, cache: false, dataType:'json', success:function(data) { if(data.success == 'yes') {
		//post?
		from_pick = data.from_pick;
		to_pick = data.to_pick;
		fromcard_name = data.fromcard_name;
		tocard_name = data.tocard_name;
		friendid = data.friendid;
		opponent_pro = data.opponent_pro;
		opponent_obj = data.opponent_obj;
		opponent_adj = data.opponent_adj;
		opponent_firstname = data.opponent_firstname;
		postanswer(from_pick, to_pick, fromcard_name, tocard_name, friendid, opponent_firstname, opponent_pro, opponent_obj);
		var temp = parseInt($('#num_pending').html());
		temp--;
		$('#num_pending').text(temp);
		$('#game_'+id).hide();
		if(to_pick > from_pick) {
			var temp = parseInt($('#record_wins').html());
			temp++;
			$('#record_wins').text(temp);
		} else {
			var temp = parseInt($('#record_loses').html());
			temp++;
			$('#record_loses').text(temp);
		}
	} else if(data.error == 'yes') {
		alert('An error has occured. Please refresh page and try again');	
	}}});
}

function action(friendid, num) {
	var message = $('#message').val();
	var publish = 0;
	if($('#publishfeed').attr('checked') == 'checked') publish = 1;
	$('#theform').css('margin-top', '20');
	$('#theform').html("<div style='width:760px; height:100px; background:url(images/ajax-loader.gif) center no-repeat;'></div>");
	$.ajax({url:'answer.php', data:{todo: 'answered', friendid: friendid, num: num, message: message, publish: publish, signed_request: '<?=$_REQUEST['signed_request']?>'}, cache: false, dataType:'json', success:function(data) { if(data.success == 'yes') {
		parent.location.href = '<?=APP_CALLBACK_URL?>';
	}}});
}

function invite() {
	FB.ui(
		{
			method: 'apprequests',
			message: 'Play a game of High Card! Can you beat your friends by picking the high card in a single card draw game?',
			title: 'Invite your friends to play High Card Draw!',
			filters: ['app_non_users'],
			data: ''
		}
	);
}

function privacy() {
	alert("High Card does not collect any personal information. No information is shared with or sold to third parties.");	
}

function repost() {
	postfeed(draw_id, from_pick, card_name, friendid, opponent_firstname, opponent_pro);
}

function postfeed(draw_id, from_pick, card_name, friendid, opponent_firstname, opponent_pro) {
	FB.ui({
		to: friendid,
		method: 'feed',
		name: '<?=$me['first_name']?> drew the '+card_name+'!',
		link: '<?=APP_CALLBACK_URL?>?game='+draw_id,
		picture: '<?=HOST_URL?>images/<?=DECK_URL?>/'+from_pick+'.png',
		caption: ' ',
		description: "<?=$me['first_name']?> challenges you to a game of high card draw! Play now and see if you have the high card!"
	},
	function(response) {
		if (response && response.post_id) {
			parent.location.href = '<?=APP_CALLBACK_URL?>';
		} else {
			alert(opponent_firstname+" will probably not respond as "+opponent_pro+" is not notified!");
			count++;
			if(count < 2)
				postfeed(draw_id, from_pick, card_name, friendid, opponent_firstname, opponent_pro);
		}
	});
}

function reminder(draw_id, from_pick, card_name, friendid) {
	FB.ui({
		to: friendid,
		method: 'feed',
		name: '<?=$me['first_name']?> drew the '+card_name+'!',
		link: '<?=APP_CALLBACK_URL?>?game='+draw_id,
		picture: '<?=HOST_URL?>images/<?=DECK_URL?>/'+from_pick+'.png',
		caption: ' ',
		description: "<?=$me['first_name']?> challenged you to a game of high card draw! You have yet to respond to the challenge. Play now to see if you have the high card!"
	},
	function(response) {
		if(response && response.post_id) {
			alert('Reminder Sent!');
		}
	});
}

function postanswer(from_pick, to_pick, fromcard_name, tocard_name, friendid, opponent_firstname, opponent_pro, opponent_obj) {
	var caption;
	var description;
	if(to_pick > from_pick) {
		caption = '<?=$me['first_name']?> Wins!';
		description = "<?=$me['first_name']?> drew the high card! <?=$me['first_name']?>'s card beats your "+fromcard_name+". Play "+opponent_obj+" again by clicking on the card on the left.";
	} else {
		caption = opponent_firstname+' Wins!';
		description = "<?=$me['first_name']?> drew the "+tocard_name+" which is lower than the "+fromcard_name+" you drew. Play "+opponent_obj+" again by clicking on the card on the left.";
	}
	FB.ui({
		to: friendid,
		method: 'feed',
		name: '<?=$me['first_name']?> drew the '+tocard_name,
		link: '<?=APP_CALLBACK_URL?>?opponent='+friendid,
		picture: '<?=HOST_URL?>images/<?=DECK_URL?>/'+to_pick+'.png',
		caption: caption,
		description: description
	},
	function(response) {
		if (response && response.post_id) {
			count = 0;
		} else {
			count++;
			if(count < 3) {
				alert(opponent_firstname+" won't know as "+opponent_pro+" is not notified of the result!");
				postanswer(from_pick, to_pick, fromcard_name, tocard_name, friendid, opponent_firstname, opponent_pro, opponent_obj);
			}
		}
	});
}
</script>
</head>
<body>
		<div style='width:728px; height:90px; margin-left:16px; padding:10px 0 10px 0; border-bottom:1px solid #ccc; margin-bottom:20px;'>
		<!-- begin ryad tag -->
        <div id='_ryad_0199155307'></div>
        <script type='text/javascript'>
          _ryadConfig = new Object();
          _ryadConfig.placeguid='0199155307';
          _ryadConfig.type='Leaderboard';
          _ryadConfig.popup=1;
          _ryadConfig.thirdPartyId='';
        </script>
        <script type='text/javascript' src='http://cdn.rockyou.com/apps/ams/tag_os.js'></script>
        <!-- end ryad tag -->
    </div>
    <div style='position:relative; width:760px; height:85px;'>
    	<div style='position:absolute; width:233px; height:60px;'>
        	<a href='<?=APP_CALLBACK_URL?>' target='_parent'><img src='images/logo.jpg' border='0' /></a>
        </div>
    	<div style='position:absolute; width:100px; top:15px; left:350px;'>
        	<fb:like href="http://www.facebook.com/apps/application.php?id=<?=FACEBOOK_APP_ID?>" send="false" layout="button_count" width="100" show_faces="false" font="verdana"></fb:like>
        </div>
        <div style='position:absolute; width:200px; height:29px; left:540px; border:1px solid #e2c822; background:#fff9d7; font-size:15px; font-weight:bold; text-align:center; padding-top:10px;'>
        	Your Record: <span id='record_wins'><?=$wins?></span> - <span id='record_loses'><?=$loses?></span><?=$percentage_str?>
        </div>
    </div>
    
	<div class="fb_protected_wrapper" fb_protected="true">
        <div class="tabs clearfix">
            <center>
                <div class="left_tabs">
                    <ul id="toggle_tabs_unused" class="toggle_tabs">
                        <li class="first">
                            <a class="<? if ($view == 'index') echo "selected";?>" href="<?=APP_CALLBACK_URL?>" target="_parent">Play</a>
                        </li>
                        <li>
                            <a class="<? if ($view == 'friends') echo "selected";?>" href="<?=APP_CALLBACK_URL?>?view=friends" target="_parent">Pending Games<? print " (<span id='num_pending'>$num_pending</span>)";?></span></a>
                        </li>
                        <li>
                            <a class="<? if ($view == 'record') echo "selected";?>" href="<?=APP_CALLBACK_URL?>?view=record" target="_parent">Record</a>
                        </li>
                        <li class="last">
                            <a href="#" onclick="javascript:invite(); return false;">Invite Friends</a>
                        </li>
                    </ul>
                </div>
            </center>
        </div>
    </div>
	<? if($_REQUEST['installed'] == 1) { ?>
        <div style='text-align:center; margin:10px 0 10px 0;'>
            <h1>Welcome <?=$me['first_name']?>!</h1>
            <h4>You can play High Card Draw right away!<br /></h4>
        </div>
    <? } ?>
    <?
    switch($view) {
		case 'index':
			?>
            <div id='display_drew' style='display:none;'>
                <div style="width:738px; text-align:justify; margin-top:15px;" class='fbbluebox'>  
                    <div style='font-size:17px; text-align:center; padding-bottom:20px;'>You Drew The <span class='card_name'></span>!</div>
                    <a href='#' onclick='javascript: repost(); return false;'>Now it's <span class='opponent_firstname'></span> turn to draw! Let <span class='opponent_obj'></span> know by posting to <span class='opponent_adj'></span> wall!</a><br /><br /><a href="<?=APP_CALLBACK_URL?>" target="_parent">Find next opponent!</a>
                </div>
                <div style='width:750px; text-align:center; margin:20px 0 0 5px;'>
                    <div style='float:left; width:200px; padding-right:50px;'>
                    	<div style='font-size:20px; font-weight:bold; padding:5px 0 15px 0;'><?=$friend_name?></div>
                   		<img src='http://graph.facebook.com/<?=$friend_id?>/picture?type=large' />
                   	</div>
                        <div style='float:left; width:250px; text-align:center; '>
                            Your Card:<br /><br />
                            <img id='yourcard'  />
                        </div>
                        <div style='float:left; width:250px; text-align:center;'>
                            <span class='opponent_firstname'></span>'s card:<br /><br />
                            <img id='theircard'  />
                        </div>
                   	<div style='clear:both;'></div>
                </div>
            </div>
            <div id='display_normal'>
                <div id='theform' style='width:760px; margin-top:20px;'>
                    <? if($nofriends != 1) { ?>
                        <div style='width:750px; text-align:center; margin:20px 0 0 5px;'>
                            <div style='float:left; width:200px;'>
                                Opponent:
                                <div style='font-size:20px; font-weight:bold; padding:5px 0 5px 0;'><?=$friend_name?></div>
                                <div style='clear:both; padding-bottom:15px; font-size:9px;'>
                                    <a href='<?=APP_CALLBACK_URL?>' target='_parent'>(Skip)</a>
                                </div>
                                <img src='http://graph.facebook.com/<?=$friend_id?>/picture?type=large' />
                            </div>
                            <div style='float:right; width:530px; margin-left:20px; height:96px; margin-bottom:24px; background:url(images/cards.jpg) no-repeat center;'>
                                <a id='drawclick' href='#' onclick='javascript:draw(<?=$friend_id?>); return false;'><img src='images/spacer.gif' width='530' height='96' border='0' alt='Deck' /></a>
                            </div>
                            <div style='clear:right; float:right; width:530px; padding-top:20px; text-align:center;'>
                                <div id='clickanywhere'>Click anywhere on the shuffled deck above to draw a card</div>
                            </div>
                            <div style='clear:both;'></div>
                        </div>
                        <div style='clear:both;'></div>
                        <div style='width:760px; border-top:1px solid #F3F3F3; margin-top:45px;text-align:center;'>
                        </div>
                    <? } else { ?>
                        <div style="width:738px; text-align:justify" class='fberrorbox'>  
                            You either have no friends or have pending games against all of your friends. Please <a href='<?=APP_CALLBACK_URL?>?view=friends' target='_parent'>view your pending games</a> to remind your friends to play.
                        </div>
                    <? } ?>
                </div>
            </div>
		<?
        break;
		case 'friends':
			if($num_pending) {
				?>
				<div style="margin:15px 0 25px 0; width:738px; text-align:justify" class='fbgreybox'>  
					Below are the pending games you have with your friends. You will be able to draw a card for games which you have not drawn a card yet.
				</div>
				<?
			}
			for($i = 0; $i < $num_pending; $i++) {
				$id = $pending[$i]['id'];
				$uid_from = $pending[$i]['uid_from'];
				$uid_to = $pending[$i]['uid_to'];
				$time = $pending[$i]['time'];
				$from_pick = $pending[$i]['from_pick'];
				
				//is the user the challenger? if so, he doesn't need to do anything here
				if($uid == $uid_from) {
					$to = $facebook->api("/$uid_to");
					$to_name = $to['name'];
					$from_html .= "
					<a name='game_$id'></a>
					<div style='margin-bottom:15px; padding-bottom:15px; width:740px; margin-left:10px; border-bottom:1px solid #CCC;'>
						<div style='float:left; width:100px; margin-right:20px;'>
                            <a href='http://facebook.com/profile.php?id=$uid_to' target='_blank'><img src='http://graph.facebook.com/$uid_to/picture?type=normal' border='0'/></a>
						</div>
                        <div style='float:left;'>
							<span style='color:#999999'>You challenged:</span> <a href='http://facebook.com/profile.php?id=$uid_to' target='_blank'>$to_name</a>
                            <div style='margin:5px;'></div>
							<span style='color:#999999'>When:</span> ".timedifference($time)." ago
                            <div style='margin:5px;'></div>
                            <span style='color:#999999'>You Drew:</span> ".cardname($from_pick)."
                            <div style='margin:5px;'></div>
                            <span style='color:#999999'>They Drew:</span> Pending
                            <div style='margin:5px;'></div>
                            <span style='color:#999999'>Action:</span> <a href='#' onclick=\"javascript: reminder($id, $from_pick, '".cardname($from_pick)."', '$uid_to') ; return false;\">Send Reminder Dialog</a>
						</div>
                        <div style='float:right;'>
                            <div style='float:right; width:110px; text-align:center; '>
                                Their Card:<br /><br />
                                <img src='".HOST_URL."images/".DECK_URL."/question.png'  />
                            </div>
                            <div style='float:right; width:110px; text-align:center;'>
                                Your card:<br /><br />
                                <img src='".HOST_URL."images/".DECK_URL."/$from_pick.png'  />
                            </div>
                        </div>
                        <div style='clear:both;'></div>
					</div>
					";
				} else {
					//otherwise, let the user draw a card
					$from = $facebook->api("/$uid_from");
					$from_name = $from['name'];
					
					$to_html .= "
					<a name='game_$id'></a>
					<div id='game_$id'>
					<div style='margin-bottom:15px; padding-bottom:15px; width:740px; margin-left:10px; border-bottom:1px solid #CCC;'>
						<div style='float:left; width:100px; margin-right:20px;'>
                            <a href='http://facebook.com/profile.php?id=$uid_from' target='_blank'><img src='http://graph.facebook.com/$uid_from/picture?type=normal' border='0'/></a>
						</div>
                        <div style='float:left;'>
							<span style='color:#999999'>Challenger:</span> <a href='http://facebook.com/profile.php?id=$uid_from' target='_blank'>$from_name</a>
                            <div style='margin:5px;'></div>
							<span style='color:#999999'>When:</span> ".timedifference($time)." ago
                            <div style='margin:5px;'></div>
                            <span style='color:#999999'>They Drew:</span> ".cardname($from_pick)."
                            <div style='margin:5px;'></div>
                            <span style='color:#999999'>Action:</span> <a href='#' class='answerclick_$id' onclick=\"javascript: draw_answer($id); return false;\"><b>Draw Now!</b></a>
						</div>
                        <div style='float:right;'>
                            <div style='float:right; width:110px; text-align:center; '>
                                Their Card:<br /><br />
                                <img src='".HOST_URL."images/".DECK_URL."/$from_pick.png'  />
                            </div>
                            <div style='float:right; width:110px; text-align:center;'>
                                Your card:<br /><br />
                                <a href='#' class='answerclick_$id' onclick=\"javascript: draw_answer($id); return false;\"><img src='".HOST_URL."images/".DECK_URL."/back1.png' border='0' /></a>
                            </div>
                        </div>
                        <div style='clear:both;'></div>
					</div>
					</div>
					";
				}
			}
			print $to_html.$from_html;
		break;
		case 'record':
			//get all played
			$played = calldb("SELECT * FROM draw WHERE to_pick != 0 AND (uid_from = $uid OR uid_to = $uid) ORDER BY id DESC LIMIT 10", 1);
			if(sizeof($played)) {
				?>
				<div style="margin:15px 0 25px 0; width:738px; text-align:justify" class='fbgreybox'>  
					Below are your last 10 completed games against your friends.
				</div>
				<?
			} else {
				?>
                <div style="width:738px; margin: 15px 0 25px 0; text-align:justify" class='fberrorbox'>  
                	You have no completed games. <a href='<?=APP_CALLBACK_URL?>' target='_parent'>Play a game against a friend!</a>
                </div>
                <?	
			}
			for($i = 0; $i < sizeof($played); $i++) {
				$id = $played[$i]['id'];
				$uid_from = $played[$i]['uid_from'];
				$uid_to = $played[$i]['uid_to'];
				$from_pick = $played[$i]['from_pick'];
				$to_pick = $played[$i]['to_pick'];
				
				//user is the challenger
				if($uid == $uid_from) {
					$to = $facebook->api("/$uid_to");
					$to_name = $to['name'];
					//who won?
					if($to_pick > $from_pick)
						$winner = $to_name;
					else
						$winner = 'You';
					$html .= "
					<div style='margin-bottom:15px; padding-bottom:15px; width:740px; margin-left:10px; border-bottom:1px solid #CCC;'>
						<div style='float:left; width:100px; margin-right:20px;'>
                            <a href='http://facebook.com/profile.php?id=$uid_to' target='_blank'><img src='http://graph.facebook.com/$uid_to/picture?type=normal' border='0'/></a>
						</div>
                        <div style='float:left;'>
							<span style='color:#999999'>You Challenged:</span> <a href='http://facebook.com/profile.php?id=$uid_to' target='_blank'>$to_name</a>
                            <div style='margin:5px;'></div>
                            <span style='color:#999999'>You Drew:</span> ".cardname($from_pick)."
                            <div style='margin:5px;'></div>
                            <span style='color:#999999'>They Drew:</span> ".cardname($to_pick)."
                            <div style='margin:5px;'></div>
                            <span style='color:#999999'>Winner:</span> <b>$winner</b>
						</div>
                        <div style='float:right;'>
                            <div style='float:right; width:110px; text-align:center; '>
                                Their Card:<br /><br />
                                <img src='".HOST_URL."images/".DECK_URL."/$to_pick.png'  />
                            </div>
                            <div style='float:right; width:110px; text-align:center;'>
                                Your card:<br /><br />
                                <img src='".HOST_URL."images/".DECK_URL."/$from_pick.png'  />
                            </div>
                        </div>
                        <div style='clear:both;'></div>
					</div>
					";
				} else {
					//otherwise, let the user draw a card
					$from = $facebook->api("/$uid_from");
					$from_name = $from['name'];
					//who won?
					if($to_pick > $from_pick)
						$winner = 'You';
					else
						$winner = $from_name;
						
					$html .= "
					<div style='margin-bottom:15px; padding-bottom:15px; width:740px; margin-left:10px; border-bottom:1px solid #CCC;'>
						<div style='float:left; width:100px; margin-right:20px;'>
                            <a href='http://facebook.com/profile.php?id=$uid_from' target='_blank'><img src='http://graph.facebook.com/$uid_from/picture?type=normal' border='0'/></a>
						</div>
                        <div style='float:left;'>
							<span style='color:#999999'>Challenged By:</span> <a href='http://facebook.com/profile.php?id=$uid_from' target='_blank'>$from_name</a>
                            <div style='margin:5px;'></div>
                            <span style='color:#999999'>You Drew:</span> ".cardname($to_pick)."
                            <div style='margin:5px;'></div>
                            <span style='color:#999999'>They Drew:</span> ".cardname($from_pick)."
                            <div style='margin:5px;'></div>
                            <span style='color:#999999'>Winner:</span> <b>$winner</b>
						</div>
                        <div style='float:right;'>
                            <div style='float:right; width:110px; text-align:center; '>
                                Their Card:<br /><br />
                                <img src='".HOST_URL."images/".DECK_URL."/$from_pick.png'  />
                            </div>
                            <div style='float:right; width:110px; text-align:center;'>
                                Your card:<br /><br />
                                <img src='".HOST_URL."images/".DECK_URL."/$to_pick.png'  />
                            </div>
                        </div>
                        <div style='clear:both;'></div>
					</div>
					";
				}
			}
			print $html;
		break;
	} ?>
    <div style='clear:both; width:728px; height:250px; margin-left:13px; padding-top:10px; border-top:1px solid #ccc; margin-top:40px; text-align:center;'>
        <!-- begin ryad tag -->
        <div id='_ryad_5FB5755308'></div>
        <script type='text/javascript'>
          _ryadConfig = new Object();
          _ryadConfig.placeguid='5FB5755308';
          _ryadConfig.type='CrossSell';
          _ryadConfig.popup=1;
          _ryadConfig.thirdPartyId='';
        </script>
        <script type='text/javascript' src='http://cdn.rockyou.com/apps/ams/tag_os.js'></script>
        <!-- end ryad tag -->
    </div>
<div style='margin-top:50px; width:760px; font-size:9px; text-align:center;'>
	<a href='#' onclick='javascript:privacy(); return false;'>Privacy Policy</a>
</div>
<!--
We use the JS SDK to provide a richer user experience. For more info,
look here: http://github.com/facebook/connect-js
-->
<div id="fb-root"></div>
<script>
	window.fbAsyncInit = function() {
        FB.init({
          appId   : '<?php echo $facebook->getAppId(); ?>',
          session : <?php echo json_encode($session); ?>, // don't refetch the session when PHP already has it
          status  : true, // check login status
          cookie  : true, // enable cookies to allow the server to access the session
          xfbml   : true // parse XFBML
        });
        //set canvas to resize automatically
		FB.Canvas.setAutoResize();
     };

    (function() {
        var e = document.createElement('script');
        e.src = document.location.protocol + '//connect.facebook.net/en_US/all.js';
        e.async = true;
        document.getElementById('fb-root').appendChild(e);
	}());
</script>
</body>
</html>
