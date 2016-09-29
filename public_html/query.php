<?php 
require_once "config.php";
require_once ABSPATH."functions/helpers.php";

$lang = "br";
if(isset($_GET['lang']))
	$lang = $_GET['lang'];
require_once ABSPATH.'pages/'.$_GET['page'].'.php';
?>