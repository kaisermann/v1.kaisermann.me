<?php
require_once ABSPATH . '/config.php';

$langfile = json_decode( file_get_contents( ABSPATH . 'assets/langs.json' ), true );

function is_lang( $langid ) {
	global $lang; return strcmp( $langid, $lang ) == 0; }
function lang( $id, $echo = true ) {
	global $lang, $langfile;

	if ( ! array_key_exists( $id,$langfile ) || ! array_key_exists( $lang,$langfile[ $id ] ) ) {
		$ret = 'Error: ' . $id;
	} else { $ret = $langfile[ $id ][ $lang ];
	}

	if ( ! $echo ) {
		return $ret;
	}

	echo $ret;
}
