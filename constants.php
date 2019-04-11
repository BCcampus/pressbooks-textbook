<?php
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

// Hide PB cover promotion
define( 'PB_HIDE_COVER_PROMO', true );
