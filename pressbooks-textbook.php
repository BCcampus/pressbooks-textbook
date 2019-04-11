<?php
/**
 * Plugin Name:       Textbooks for Pressbooks
 * Description:       A plugin that extends Pressbooks for textbook authoring
 * Version:           4.2.3
 * Author:            BCcampus
 * Author URI:        http://github.com/BCcampus
 * Text Domain:       pressbooks-textbook
 * License:           GPL-2.0+
 * License URI:       http://www.gnu.org/licenses/gpl-2.0.txt
 * Domain Path:       /languages
 * GitHub Plugin URI: https://github.com/BCcampus/pressbooks-textbook
 * Tags: pressbooks, OER, publishing, textbooks
 * Pressbooks tested up to: 5.7.0
 */

// If file is called directly, abort.
if ( ! defined( 'WPINC' ) ) {
	return;
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

if ( ! include_once( WP_PLUGIN_DIR . '/pressbooks/compatibility.php' ) ) {
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

add_filter(
	'init', function () {

	if ( ! version_compare( PB_PLUGIN_VERSION, '5.0.0', '>=' ) ) {
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
);

/*
|--------------------------------------------------------------------------
| autoload classes
|--------------------------------------------------------------------------
|
|
|
|
*/
if ( function_exists( '\HM\Autoloader\register_class_path' ) ) {
	\HM\Autoloader\register_class_path( 'PBT', __DIR__ . '/inc' );
}

// Load Composer Dependencies
$composer = __DIR__ . '/vendor/autoload.php';
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
require __DIR__ . '/inc/settings/namespace.php';

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
	\PBT\Admin\TextbookAdmin::get_instance();
}

