<?php
/**
 * Modifications on this page include adding options to store values for 
 * derivative works. 2014, Brad Payne.
 */

/**
 * Module containing settings related functions.
 */


/**
 * Returns an array with the default options.
 */
function bccl_get_default_options() {
    return array(
        "settings_version"  => 2,       // IMPORTANT: SETTINGS UPGRADE: Every time settings are added or removed this has to be incremented for auto upgrade of settings.
        "license_url"       => "",
        "license_name"      => "",
        "license_button"    => "",
        "deed_url"          => "",
        "cc_head"       => "0",
        "cc_feed"       => "0",
        "cc_body"       => "0",
        "cc_body_pages" => "0",
        "cc_body_attachments"   => "0",
        "cc_body_img"   => "0",
        "cc_extended"   => "0",
        "cc_creator"    => "blogname",
        "cc_perm_url"   => "",
        "cc_color"      => "#000000",
        "cc_bgcolor"    => "#eef6e6",
        "cc_brdr_color" => "#cccccc",
        "cc_no_style"   => "0",
        "cc_i_have_donated" => "0",
        "cc_derivative" => "0",
        "cc_derivative_orig_title" => "",
        "cc_derivative_orig_author" => "",
        "cc_derivative_orig_src" => "",
        "cc_derivative_orig_lic" => "",
    );
}



/**
 * Performs upgrade of the plugin settings.
 */
function bccl_plugin_upgrade() {

    // First we try to determine if this is a new installation or if the
    // current installation requires upgrade.

    // Default CC-Configurator Settings
    $default_options = bccl_get_default_options();

    // Try to get the current CC-Configurator options from the database
    $stored_options = get_option('cc_settings');
    if ( empty($stored_options) ) {
        // This is the first run, so set our defaults.
        update_option('cc_settings', $default_options);
        return;
    }

    // Check the settings version

    // If the settings version of the default options matches the settings version
    // of the stored options, there is no need to upgrade.
    if (array_key_exists('settings_version', $stored_options) &&
            ( intval($stored_options["settings_version"]) == intval($default_options["settings_version"]) ) ) {
        // Settings are up to date. No upgrade required.
        return;
    }

    // On any other case a settings upgrade is required.

    // 1) Add any missing options to the stored CC-Configurator options
    foreach ($default_options as $opt => $value) {
        // Always upgrade the ``settings_version`` option
        if ($opt == 'settings_version') {
            $stored_options['settings_version'] = $value;
        }
        // Add missing options
        elseif ( ! array_key_exists($opt, $stored_options) ) {
            $stored_options[$opt] = $value;
        }
        // Existing stored options are untouched here.
    }

    // 2) Migrate any current options to new ones.
    // Migration rules should go here.

    // Version 1.4.2 (settings_version 1->2)
    // Settings from $cc_settings['options'] inner array moved to $cc_settings root
    // Migration is required.
    if ( array_key_exists( 'options', $stored_options ) ) {
        // Step 1: All options saved in $cc_settings['options'] are moved to $cc_settings root
        foreach ( $stored_options['options'] as $opt => $value ) {
            $stored_options[$opt] = $value;
        }
        // Step 2: Delete $stored_options['options']
        unset( $stored_options['options'] );
    }
    
    // Version X.X.X (settings_version N->N)
    // Add other migration here

    // 3) Clean stored options.
    foreach ($stored_options as $opt => $value) {
        if ( ! array_key_exists($opt, $default_options) ) {
            // Remove any options that do not exist in the default options.
            unset($stored_options[$opt]);
        }
    }

    // Finally save the updated options.
    update_option('cc_settings', $stored_options);

}
//add_action('plugins_loaded', 'bccl_plugin_upgrade');
// See function bccl_admin_init() in bccl-admin-panel.php



/**
 * Returns an array containing only those settings related to the license.
 * SHOULD ONLY BE CALLED AFTER A LICENSE HAS BEEN SET
 */
function bccl_get_base_license_settings() {
    $license_options = array();
    $stored_options = get_option('cc_settings');
    if ( ! empty($stored_options) ) {
        $license_options['license_url'] = $stored_options['license_url'];
        $license_options['license_name'] = $stored_options['license_name'];
        $license_options['license_button'] = $stored_options['license_button'];
        $license_options['deed_url'] = $stored_options['deed_url'];
    }
    return $license_options;
}


/**
 * Saves the new settings in the database.
 * Accepts the POST request data.
 */
function bccl_save_settings($post_payload) {
    
    // Default CC-Configurator Settings
    $default_options = bccl_get_default_options();

    // Construct the new settings array
    // Initial settings include only the license related settings, which
    // should have been stored during the initial license selection.
    // This happens because these settings are not passed from the form of
    // admin panel when saving the option, so they do not exist in the $post_payload.
    // They are only set in the 'new license' selection dialog.
    $cc_settings = bccl_get_base_license_settings();

    // First add the already stored license info

    foreach ( $default_options as $def_key => $def_value ) {

        // **Always** use the ``settings_version`` from the defaults
        if ($def_key == 'settings_version') {
            $cc_settings['settings_version'] = $def_value;
        }

        // Add options from the POST request (saved by the user)
        elseif ( array_key_exists($def_key, $post_payload) ) {

            // Validate and sanitize input before adding to 'cc_settings'
            if ( in_array( $def_key, array( 'license_url', 'license_button', 'deed_url', 'cc_perm_url' ) ) ) {
                $cc_settings[$def_key] = esc_url_raw( stripslashes( $post_payload[$def_key] ), array( 'http', 'https') );
            } else {
                $cc_settings[$def_key] = sanitize_text_field( stripslashes( $post_payload[$def_key] ) );
            }
        }
        
        // If missing (eg checkboxes), use the default value, except for the case
        // those checkbox settings whose default value is 1.
        else {

            // We exclude the license related settings from this check.
            // These do not exist in the $post_payload when settings are saved
            if ( ! in_array( $def_key, array( 'license_url', 'license_name', 'license_button', 'deed_url' ) ) ) {

                // The following settings have a default value of 1, so they can never be
                // deactivated, unless the following check takes place.
                if (
                    $def_key == 'SOME_CHECKBOX_WITH_DEFAULT_VALUE_1' ||
                    $def_key == 'SOME_OTHER_CHECKBOX_WITH_DEFAULT_VALUE_1'
                ) {
                    if( ! isset($post_payload[$def_key]) ){
                        $cc_settings[$def_key] = "0";
                    }
                } else {
                    // Else save the default value in the db.
                    $cc_settings[$def_key] = $def_value;
                }
            }
        }
    }

    // Forcing 'syndicate content' to off
    // It creates invalid EPUB output, plus there is no 'feed' in PB
    $cc_settings['cc_feed'] = 0;
    
    // Finally update the CC-Configurator options.
    update_option('cc_settings', $cc_settings);

    //var_dump($post_payload);
    //var_dump($cc_settings);

    bccl_show_info_msg(__('CC-Configurator options saved', 'cc-configurator'));
}


/**
 * Set new license.
 * Saves the new license settings to database.
 */
function bccl_set_new_license_settings( $query_args ) {

    // Get the current CC-Configurator options from the database
    $cc_settings = get_option('cc_settings');

    // Replace the base CC license settings
    $cc_settings['license_url'] = esc_url_raw( rawurldecode( stripslashes( $query_args['license_url'] ) ), array( 'http', 'https' ) );
    $cc_settings['license_name'] = sanitize_text_field( stripslashes( $query_args['license_name'] ) );
    $cc_settings['license_button'] = esc_url_raw( rawurldecode( stripslashes( $query_args['license_button'] ) ), array( 'http', 'https' ) );
    $cc_settings['deed_url'] = esc_url_raw( rawurldecode( stripslashes( $query_args['deed_url'] ) ), array( 'http', 'https' ) );
    
    update_option('cc_settings', $cc_settings);
    bccl_show_info_msg(__('Creative Commons license saved.', 'cc-configurator'));
}


/**
 * Reset settings to the defaults.
 *
 * This function does not affect the license related settings:
 * license_url, license_name, license_button, deed_url
 *
 * Resets all other settings (which are available in the settings form) to
 * their default values.
 */
function bccl_reset_settings() {
    // Default CC-Configurator Settings
    $default_options = bccl_get_default_options();
    // Array with only the license settings, which are not saved with "Save Settings"
    $license_settings = bccl_get_base_license_settings();

    $cc_settings = array_merge( $default_options, $license_settings );

    delete_option('cc_settings');
    update_option('cc_settings', $cc_settings);
    bccl_show_info_msg(__('CC-Configurator options were reset to defaults', 'cc-configurator'));
}


/**
 * Reset license settings.
 *
 * This function sets the license_url, license_name, license_button, deed_url
 * to empty strings.
 */
function bccl_reset_license_settings() {

    // Get the current CC-Configurator options from the database
    $cc_settings = get_option('cc_settings');

    // Reset license settings
    $cc_settings['license_url'] = '';
    $cc_settings['license_name'] = '';
    $cc_settings['license_button'] = '';
    $cc_settings['deed_url'] = '';

    delete_option('cc_settings');
    update_option('cc_settings', $cc_settings);
    bccl_show_info_msg(__('CC-Configurator license has been removed.', 'cc-configurator'));
}

