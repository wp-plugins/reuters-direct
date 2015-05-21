<?php
/*
 * Plugin Name: Reuters WordPress Direct
 * Version: 2.4.2
 * Description: A full-featured news aggregator, powered by Reuters Connect: Web Services, which ingests Reuters news and picture content directly into a WordPress platform.
 * Author: Reuters News Agency 
 * Requires at least: 3.8
 * Tested up to: 4.2.2
 * Written by: Esthove
 */

if ( ! defined( 'ABSPATH' ) ) exit;

// Include plugin class files
require_once( 'includes/class-reuters-direct.php' );
require_once( 'includes/class-reuters-direct-settings.php' );

function Reuters_Direct () {
	$instance = Reuters_Direct::instance( __FILE__, '2.4.2' );
	if( is_null( $instance->settings ) ) {
		$instance->settings = Reuters_Direct_Settings::instance( $instance );
	}
	return $instance;
}

Reuters_Direct();


