<?php
/*
 * Enqueue Scripts and Style
 * Jan. 16, 2015
 */

function wptabsy_scripts_method() {
	wp_enqueue_style( 'tabsy-css', plugins_url( 'assets/css/tabsy.css' , dirname(__FILE__) ) , array(), null );
	wp_enqueue_style( 'tabsy-fontawesome', plugins_url( 'assets/font-awesome-4.3.0/css/font-awesome.min.css' , dirname(__FILE__) ) , array(), null );
	wp_enqueue_script(
		'jquery-tabsy',
		plugins_url( 'assets/js/jquery.tabsy.js' , dirname(__FILE__) ),
		array( 'jquery' ),
		'',
		true
	);
	wp_enqueue_script(
		'wptabsy',
		plugins_url( 'assets/js/wptabsy.js' , dirname(__FILE__) ),
		array( 'jquery', 'jquery-tabsy' ),
		'',
		true
	);
}

add_action( 'wp_enqueue_scripts', 'wptabsy_scripts_method' );
?>