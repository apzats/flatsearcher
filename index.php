<?php


$client_id = '!!!Your app id goes here!!!';
$redirect_uri = 'https://oauth.vk.com/blank.html';
$display = 'page';
$scope = 'offline,friends,groups';
$response_type = 'code';

echo "<a href=\"https://oauth.vk.com/authorize?client_id={$client_id}&display=page&redirect_uri=https://oauth.vk.com/blank.html&scope={$scope}&response_type=token&v=5.37\">Push the button</a>";