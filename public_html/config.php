<?php
define( 'ABSPATH', dirname(__FILE__).'/');

/*
if ($_SERVER["SERVER_PORT"] != 443) {
    $redir = "Location: https://" . $_SERVER['HTTP_HOST'] . $_SERVER['PHP_SELF'];
    header($redir);
    exit();
}
*/

$asset_version = "3.5";

$dev = 0;
if(!$dev)
	$main_url = 'https://'.'kaisermann.me';
else
	$main_url = 'http://'.'10.0.1.2/new';

?>
