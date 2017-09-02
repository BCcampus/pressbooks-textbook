<?php

/**
 * Pressbooks Textbook
 *
 * @package   Pressbooks_Textbook
 * @author    Brad Payne <brad@bradpayne.ca>
 * @license   GPL-2.0+
 * @copyright 2014 Brad Payne
 *
 * @wordpress-plugin
 * Plugin Name:       Pressbooks Textbook
 * Description:       A plugin that extends Pressbooks for textbook authoring
 * Version:           3.1.1
 * Author:            Brad Payne
 * Author URI:        http://bradpayne.ca
 * Text Domain:       pressbooks-textbook
 * License:           GPL-2.0+
 * License URI:       http://www.gnu.org/licenses/gpl-2.0.txt
 * Domain Path:       /languages
 * GitHub Plugin URI: https://github.com/BCcampus/pressbooks-textbook
 */

// If file is called directly, abort.
if ( ! defined( 'WPINC' ) ) {
	die();
}

/*
|--------------------------------------------------------------------------
| Constants
|--------------------------------------------------------------------------
|
|
|
|
*/
if ( ! defined( 'PBT_PLUGIN_DIR' ) ) {
	define( 'PBT_PLUGIN_DIR', __DIR__ . '/' );
}
if ( ! defined( 'PBT_PLUGIN_URL' ) ) {
	define( 'PBT_PLUGIN_URL', plugin_dir_url( __FILE__ ) );
}

// Must have trailing slash!
if ( ! defined( 'PB_PLUGIN_DIR' ) ) {
	define( 'PB_PLUGIN_DIR', WP_PLUGIN_DIR . '/pressbooks/' );
}

// Allow override in wp-config.php
if ( ! defined( 'WP_DEFAULT_THEME' ) ) {
	define( 'WP_DEFAULT_THEME', 'opentextbook' );
};

// Hide PB cover promotion
define( 'PB_HIDE_COVER_PROMO', true );

/*
|--------------------------------------------------------------------------
| Miniumum requirements before either PB or PBT objects are instantiated
|--------------------------------------------------------------------------
|
|
|
|
*/
function pb_compatibility() {
	$min_pb_compatibility_version = '4.0.0';

	if ( ! @include_once( WP_PLUGIN_DIR . '/pressbooks/compatibility.php' ) ) {
		add_action( 'admin_notices', function () {
			echo '<div id="message" class="error fade"><p>' . __( 'PBT cannot find a Pressbooks install.', 'pressbooks-textbook' ) . '</p></div>';
		} );

	} elseif ( ! pb_meets_minimum_requirements() ) { // This PB function checks for both multisite, PHP and WP minimum versions.
		add_action( 'admin_notices', function () {
			echo '<div id="message" class="error fade"><p>' . __( 'Your PHP version may not be supported by PressBooks.'
			                                                      . ' If you suspect this is the case, it can be overridden, so long as it is remains above PHP 5.4.0. Add a line to wp-config.php as follows: $pb_minimum_php = "5.4.0"; ', $this->plugin_slug ) . '</p></div>';
		} );

	} elseif ( ! version_compare( PB_PLUGIN_VERSION, $min_pb_compatibility_version, '>=' ) ) {
		add_action( 'admin_notices', function () {
			echo '<div id="message" class="error fade"><p>' . __( 'PB Textbook requires Pressbooks 4.0.0 or greater.', 'pressbooks-textbook' ) . '</p></div>';
		} );
	}
	// need version number outside of init hook
	update_site_option( 'pbt_pb_version', PB_PLUGIN_VERSION );

}

add_action( 'init', 'pb_compatibility' );

/*
|--------------------------------------------------------------------------
| autoload classes
|--------------------------------------------------------------------------
|
|
|
|
*/
require PBT_PLUGIN_DIR . 'autoloader.php';
$loader = new \BCcampus\Autoloader();
$loader->register();
$loader->addNamespace( 'PBT', PBT_PLUGIN_DIR . 'inc' );

// Load Composer Dependencies
if ( file_exists( $composer = PBT_PLUGIN_DIR . 'vendor/autoload.php' ) ) {
	require_once( $composer );
}
/*
|--------------------------------------------------------------------------
| All Your Base Are Belong To Us
|--------------------------------------------------------------------------
|
|
|
|
*/
if ( get_site_option( 'pressbooks-activated' ) ) {
	//require 'inc/class-textbook.php';
	$pbt = \PBT\Textbook::get_instance();
	if ( is_admin() ) {
		$pbt = new \PBT\Admin\TextbookAdmin;
	}
}
