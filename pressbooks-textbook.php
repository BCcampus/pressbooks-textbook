<?php
/**
 * Plugin Name:       Textbooks for Pressbooks
 * Description:       A plugin that extends Pressbooks for textbook authoring
 * Version:           4.3.1
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

		// the constants below should be available in the init hook
		if ( ! version_compare( PB_PLUGIN_VERSION, '5.0.0', '>=' ) ) {
			add_action(
				'admin_notices', function () {
					echo '<div id="message" class="error fade"><p>' . __( 'Textbooks for Pressbooks requires Pressbooks 5.0.0 or greater.', 'pressbooks-textbook' ) . '</p></div>';
				}
			);

			return;
		}

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
} else {
	require_once( __DIR__ . '/autoloader.php' );
}

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
require __DIR__ . '/constants.php';
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

