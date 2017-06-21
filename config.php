<?php
define( 'ABSPATH', dirname( __FILE__ ) . '/' );

$main_url = 'https://kaisermann.me';
$asset_version = '3.6';
$dev = 0;

if ( $dev ) {
	$main_url = 'http://10.0.1.2/new';
}
