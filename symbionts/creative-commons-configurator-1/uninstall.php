<?php

if ( !defined( 'ABSPATH') && !defined('WP_UNINSTALL_PLUGIN') ) {
    exit();
 }

delete_option('cc_settings');

?>