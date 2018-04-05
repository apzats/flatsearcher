<?php

require("conf.php");

$db = new SQLite3(__DIR__.'/flatsearcher.db');
if(!$db) {
  echo $db->lastErrorMsg();
}

$ret = $db->query('SELECT * FROM keywords');
$keywords = [];
while($row = $ret->fetchArray(SQLITE3_ASSOC) ) {
	$keywords[]=$row["keyword"];
}

$ret = $db->query('SELECT * FROM groups');
$request = [];
while($row = $ret->fetchArray(SQLITE3_ASSOC) ) {
	$ret2 = $db->query("SELECT * FROM group_topic where id_group = {$row['IDgroup']}");
	$topics = [];
	while($row2 = $ret2->fetchArray(SQLITE3_ASSOC) ) {
		$topics[] = $row2['id_topic'];
	}
	$request[]=
	[
		'group_id' => $row["IDgroup"],
		'topics' => $topics,
		'count' => $row["HowMany"],
		'nowall' => $row["no_wall"]
	];
}

// $request = [
// 	[
// 		'group_id' => 13178749,
// 		'topics' => [35647131],
// 		'count' => 10,
// 		'nowall' => 0
// 	]
// ];

// $keywords = [
// 	'арбатск', 'минимализм', 'лубянка', 'чувви', 'сокольники', 'видное', 'кантемировск'
// ];

foreach ($request as $item) {
	if ($item['nowall']==0) {
		wallGet($db, $item['group_id'],$item['count'],$config,$keywords);
	}
	if (!empty($item['topics'])) {
		boardGetComments($db, $item['group_id'], $item['topics'][0],100,$config, $keywords);
	}	
}

$db->close();

function boardGetComments($db, $groupid,$topicid,$howMany,$config,$keywords)
{
	$url = "https://api.vk.com/method/board.getComments?group_id={$groupid}&topic_id={$topicid}&count={$howMany}&sort=desc&access_token={$config['token']}&v={$config['v']}";
	// $raw_data=file_get_contents($url);
	$raw_data=getSSLPage($url);
 	$pars=json_decode($raw_data, true);
 	$ads=$pars['response']['items'];
 	foreach ($ads as $key => $value) {
 		$ads_body = cleaner($value['text']);

 		foreach ($keywords as $meaning) {
 			if (mb_stripos($ads_body, $meaning)!==false) {
 				$message="https://vk.com/topic-{$groupid}_{$topicid}?post={$value['id']}";
 				sendMessage($db, $message, $meaning, $config);
 			}
 		}
 	}

}

function wallGet($db, $groupid,$howMany,$config,$keywords) 
{
	$url = "https://api.vk.com/method/wall.get?owner_id=-{$groupid}&count={$howMany}&access_token={$config['token']}&v={$config['v']}";
 	// $raw_data=file_get_contents($url);
 	$raw_data=getSSLPage($url);
 	$pars=json_decode($raw_data, true);
 // 	print "<pre>";
	// print_r($pars);
	// print "</pre>";
 // 	die();
 	$ads=$pars['response']['items'];
 	foreach ($ads as $key => $value) {
 		$ads_body = cleaner($value['text']);
	 // 	print "<pre>";
		// print_r($ads_body);
		// print "</pre>";
	 // 	print "<br>";
	 // 	print "<br>";
 		foreach ($keywords as $meaning) {
 			if (mb_stripos($ads_body, $meaning)!==false) {
 				$message = "https://vk.com/wall-{$groupid}_{$value['id']}";
				sendMessage($db, $message, $meaning, $config);
 			}
 		}
 	}
} 

function sendMessage($db, $message, $keyword, $config)
{
	$keyword = urlencode(" - $keyword");
	$ret = $db->query("SELECT * FROM Sentmessages where link = '$message'");
	$row = $ret->fetchArray(SQLITE3_ASSOC);
	if (!$row) {
		$sent_message="https://api.vk.com/method/messages.send?user_id={$config['myid']}&message=$message$keyword&access_token={$config['token']}&v={$config['v']}";
		$result = getSSLPage($sent_message);
		$ret2 = $db->query("INSERT INTO Sentmessages (id,link) VALUES (NULL,'$message');");
		if (!$ret2) {
			return false;
		}
	}
	else {
		return false;	
	}
	return $result;
}

function cleaner($text) {
	return preg_replace("#[^А-яЁё ]#u",'', $text);

}

function getSSLPage($url) {
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_HEADER, false);
    curl_setopt($ch, CURLOPT_URL, $url);
	curl_setopt( $ch, CURLOPT_SSL_VERIFYPEER, false );
	curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
    // curl_setopt($ch, CURLOPT_SSLVERSION,3); 
    $result = curl_exec($ch);
    curl_close($ch);
    return $result;
}