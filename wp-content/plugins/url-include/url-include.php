<?php
/*
Plugin Name: URL Includer
Plugin URI: http://www.sitepoint.com/
Description: Include distant url files using a shortcode
Version: 1.0
Author: Craig Buckler
Author URI: http://optimalworks.net/
License: Use this how you like!
*/
function URLInclude($params = array()) {

	extract(shortcode_atts(array(
	    'url' => 'http://www.google.com'
	), $params));

	$args = array(
    	'timeout'     => 60,
    );

	$option = '';
	$parse = parse_url($url);
	$local_domains = array('teachers.milonga.local','teachers.milonga.be');
	if( in_array($parse['host'], $local_domains) ){
		$option = '--resolve ' . $parse['host'] . ':80:127.0.0.1';
	}
	$data = shell_exec("curl ". $option ." \"" . $url . "\"" );
	return $data;
}

add_shortcode('urlinclude', 'URLInclude');