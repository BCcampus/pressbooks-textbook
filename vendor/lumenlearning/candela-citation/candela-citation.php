<?php
/**
 * @wordpress-plugin
 * Plugin Name:       Candela Attributions
 * Description:       Creative Commons Attributions for Candela/Pressbooks
 * Version:           0.2.1
 * Author:            Lumen Learning
 * Author URI:        http://lumenlearning.com
 * Text Domain:       lti
 * License:           MIT
 * GitHub Plugin URI: https://github.com/lumenlearning/candela-citation
 */

// If file is called directly, abort.
use Candela\Citation;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
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
if ( ! defined( 'CANDELA_CITATION_FIELD' ) ) {
	define( 'CANDELA_CITATION_FIELD', '_candela_citation' );
}

if( ! defined( 'CANDELA_CITATION_DB_VERSION') ) {
	define( 'CANDELA_CITATION_DB_VERSION', '1.0' );
}

if( ! defined( 'CANDELA_CITATION_SEPARATOR') ) {
	define( 'CANDELA_CITATION_SEPARATOR', '. ' );
}

if ( ! defined( 'CANDELA_CITATION_DB_OPTION')) {
	define( 'CANDELA_CITATION_DB_OPTION', 'candela_citation_db_version' );
}

if ( ! defined( 'CANDELA_PLUGIN_DIR' ) ) {
	define( 'CANDELA_PLUGIN_DIR', __DIR__ . '/' );
}

/*
|--------------------------------------------------------------------------
| autoload
|--------------------------------------------------------------------------
|
|
|
|
*/
require CANDELA_PLUGIN_DIR . 'autoloader.php';

// Do our necessary plugin setup and add_action routines.
Citation::init();


