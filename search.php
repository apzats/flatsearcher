<?php

//Config params are set separately ($myid, $token, $db_path);
require("conf.php");

$db = new SQLite3();
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
	$request[]=
	[
		'group_id' => $row["IDgroup"],
		'count' => $row["HowMany"]
	];
}

$db->close();

foreach ($request as $item) {
	$url = "https://api.vk.com/method/wall.get?owner_id=-{$item['group_id']}&count={$item['count']}&access_token=$token&v=5.37";
 	$raw_data=file_get_contents($url);
 	echo '<pre>';
 	$pars=json_decode($raw_data, true);
 	$ads=$pars['response']['items'];
 	foreach ($ads as $key => $value) {
 		$ads_body = cleaner($value['text']);
 		foreach ($keywords as $meaning) {
 			if (mb_stripos($ads_body, $meaning)!==false) {
 				$url2="https://vk.com/wall-{$item['group_id']}_{$value['id']}";
 				$reason = urlencode(" - $meaning");
 				$message="https://api.vk.com/method/messages.send?user_id=$myid&message=$url2$reason&access_token=$token&v=5.37";
 				var_dump($message);
 				$result = file_get_contents($message);
 				var_dump($result);

 			}
 		}
 	}

}

function cleaner($text) {
	return preg_replace("#[^А-яЁё ]#u",'', $text);

}