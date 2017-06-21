<?php
require_once 'config.php';
require_once ABSPATH . 'functions/helpers.php';

$lang = 'br';
if ( isset( $_GET['lang'] ) ) {
	$lang = $_GET['lang'];
}

if ( isset( $_GET['page'] ) ) {
	require_once ABSPATH . 'pages/' . $_GET['page'] . '.php';
} else {
	die( 'Hey ;)' );
}
