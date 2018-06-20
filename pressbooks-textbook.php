<?php

/**
 * Textbooks for Pressbooks
 *
 * @package   Pressbooks_Textbook
 * @author    Brad Payne
 * @license   GPL-2.0+
 * @copyright 2014 Brad Payne
 *
 * @wordpress-plugin
 * Plugin Name:       Textbooks for Pressbooks
 * Description:       A plugin that extends Pressbooks for textbook authoring
 * Version:           4.0.5
 * Author:            Brad Payne
 * Author URI:        http://github.com/bdolor
 * Text Domain:       pressbooks-textbook
 * License:           GPL-2.0+
 * License URI:       http://www.gnu.org/licenses/gpl-2.0.txt
 * Domain Path:       /languages
 * GitHub Plugin URI: https://github.com/BCcampus/pressbooks-textbook
 * Tags: pressbooks, OER, publishing, textbooks
 * Pressbooks tested up to: 5.3.3
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
| Minimum requirements before either PB or PBT objects are instantiated
|--------------------------------------------------------------------------
|
|
|
|
*/
function pb_compatibility() {
	$min_pb_compatibility_version = '5.0.0';

	if ( ! @include_once( WP_PLUGIN_DIR . '/pressbooks/compatibility.php' ) ) {
		add_action(
			'admin_notices', function () {
				echo '<div id="message" class="error fade"><p>' . __( 'PBT cannot find a Pressbooks install.', 'pressbooks-textbook' ) . '</p></div>';
			}
		);

		return;
	}

	if ( function_exists( 'pb_meets_minimum_requirements' ) ) {
		if ( ! pb_meets_minimum_requirements() ) { // This PB function checks for both multisite, PHP and WP minimum versions.
			add_action(
				'admin_notices', function () {
					echo '<div id="message" class="error fade"><p>' . __( 'Your PHP or WP version may not be up to date.', 'pressbooks-textbook' ) . '</p></div>';
				}
			);

			return;
		}
	}

	if ( ! version_compare( PB_PLUGIN_VERSION, $min_pb_compatibility_version, '>=' ) ) {
		add_action(
			'admin_notices', function () {
				echo '<div id="message" class="error fade"><p>' . __( 'Textbooks for Pressbooks requires Pressbooks 5.0.0 or greater.', 'pressbooks-textbook' ) . '</p></div>';
			}
		);

		return;
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

// Load Composer Dependencies
$composer = PBT_PLUGIN_DIR . 'vendor/autoload.php';
if ( file_exists( $composer ) ) {
	require_once( $composer );
}

/*
|--------------------------------------------------------------------------
| Other requirements
|--------------------------------------------------------------------------
|
|
|
|
*/
require PBT_PLUGIN_DIR . 'inc/pbt-settings.php';

/*
|--------------------------------------------------------------------------
| All Your Base Are Belong To Us
|--------------------------------------------------------------------------
|
|
|
|
*/
\PBT\Textbook::get_instance();
if ( is_admin() ) {
	new \PBT\Admin\TextbookAdmin;
}
