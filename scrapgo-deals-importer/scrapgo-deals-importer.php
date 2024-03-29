<?php
/*
Plugin Name: ScrapGo Deals Importer
Description: Imports deals from ScrapGo API and saves them into the WordPress database.
Version: 1.0
 * Author:            Aragrow
 * Author URI:        https://aragrow.me
 * Plugin URI: 		  https://aragrow.me/plugins/scrapgo-deals-importer
 *
 * Here's a brief overview of the files and their purposes:

 *	scrapgo-deals-importer.php: The main plugin file where you define plugin metadata and include necessary files.
 *	includes/ScrapgoDealsSetup.php: 
 *		- Code to add custom post type and taxonomy
 *		- Code to add menu items and submenus.
 *		- Code to create cron job.
 *	includes/ScrapgoDealsImporter.php: Logic for interacting with the Scrapgo API, fetching data, and importing it into WordPress.
 *	dist: Directory for CSS and JavaScript files used in the plugin.
 */

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! defined( 'SCRAPGO_VERSION' ) ) {
	/**
	 * Plugin version.
	 *
	 * @since 1.0.0
	 */
	define( 'SCRAPGO_VERSION', '1.0.0.0' );
}

// Plugin Folder Path.
if ( ! defined( 'SCRAPGOPLUGIN_DIR' ) ) {
	define( 'SCRAPGO_PLUGIN_DIR', plugin_dir_path( __FILE__ ) );
}

// Plugin Folder URL.
if ( ! defined( 'SCRAPGO_PLUGIN_URL' ) ) 
	define( 'SCRAPGO_PLUGIN_URL', plugin_dir_url( __FILE__ ) );


// Plugin Root File.
if ( ! defined( 'SCRAPGO_PLUGIN_FILE' ) ) {
	define( 'SCRAPGO_PLUGIN_FILE', plugin_dir_path(__FILE__) );
}

// Plugin Root File.
if ( ! defined( 'SCRAPGO_WITH_CLASSES_FILE' ) ) {
	define( 'SCRAPGO_WITH_CLASSES_FILE', __FILE__ );
}

// Plugin Root File.
if ( ! defined( 'SCRAPGO_ITEMS_PER_PAGE' ) ) {
	define( 'SCRAPGO_ITEMS_PER_PAGE', 50 );
}

if (get_option('scrapgo_debug')) define( 'SCRAPGO_DEBUG', 1 );
else define( 'SCRAPGO_DEBUG', 0 );

// Get the current date and time in a specific format
$current_date_time = date('Y-m-d H:i:s');
if(SCRAPGO_DEBUG) error_log(' ######################## PLUGIN LOADS at: '.$current_date_time);

// Define the class and the function.
if (!class_exists('ScrapgoDealUtilities')) {
    require_once SCRAPGO_PLUGIN_DIR . 'includes/Utilities.php';
}

// Define the class and the function.
if (!class_exists('ScrapgoDealsSetup')) {
    require_once SCRAPGO_PLUGIN_DIR . 'includes/Setup.php';
}

// Define the class and the function.
if (!class_exists('ScrapgoDealsImporter')) {
    require_once SCRAPGO_PLUGIN_DIR . 'includes/Importer.php';
}



