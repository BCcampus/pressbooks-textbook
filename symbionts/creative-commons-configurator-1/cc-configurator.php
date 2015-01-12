<?php
/*
Plugin Name: Creative Commons Configurator, Derivative works
Description: A very slightly modified version of the original CCC plugin that allows declaring derivative works. Helps you publish your content under the terms of a Creative Commons license.
Version: 1.0.0
Author: Brad Payne
Original Author: George Notaras
License: Apache License v2
Text Domain: cc-configurator
Domain Path: /languages/
License URI: http://www.gnu.org/licenses/gpl-2.0.txt
*/


/**
 *  This plugin is forked from the original Creative Commons Configurator v1.5.2 https://wordpress.org/plugins/creative-commons-configurator-1/ (c) George Notaras
 * 
 *  This fork modifies the original only slightly in order to include the option to declare derivative works.
 *  
 */


/*
Creative Commons Icon Selection.
"0" : 88x31.png
"1" : somerights20.png
"2" : 80x15.png
*/
$default_button = "0";


// Store plugin directory
define('BCCL_DIR', dirname(__FILE__));

// Import modules
require_once( join( DIRECTORY_SEPARATOR, array( BCCL_DIR, 'bccl-settings.php' ) ) );
require_once( join( DIRECTORY_SEPARATOR, array( BCCL_DIR, 'bccl-admin-panel.php' ) ) );
require_once( join( DIRECTORY_SEPARATOR, array( BCCL_DIR, 'bccl-template-tags.php' ) ) );
require_once( join( DIRECTORY_SEPARATOR, array( BCCL_DIR, 'bccl-utils.php' ) ) );
require_once( join( DIRECTORY_SEPARATOR, array( BCCL_DIR, 'bccl-licenses.php' ) ) );


/*
 * Translation Domain
 *
 * Translation files are searched in: wp-content/plugins
 */
load_plugin_textdomain('cc-configurator', false, dirname( plugin_basename( __FILE__ ) ) . '/languages/');


/**
 * Settings Link in the ``Installed Plugins`` page
 */
function bccl_plugin_actions( $links, $file ) {
    // if( $file == 'creative-commons-configurator-1/cc-configurator.php' && function_exists( "admin_url" ) ) {
    if( $file == plugin_basename(__FILE__) && function_exists( "admin_url" ) ) {
        $settings_link = '<a href="' . admin_url( 'options-general.php?page=cc-configurator-options' ) . '">' . __('Settings') . '</a>';
        // Add the settings link before other links
        array_unshift( $links, $settings_link );
    }
    return $links;
}
add_filter( 'plugin_action_links', 'bccl_plugin_actions', 10, 2 );



/**
 * Returns Full TEXT hyperlink to License <a href=...>...</a>
 */
function bccl_get_license_text_hyperlink() {

    $cc_settings = get_option("cc_settings");

    // If there is no global license, stop here
    if ( empty($cc_settings['license_url']) ) {
        return '';
    }

    $license_url = $cc_settings["license_url"];
    $license_name = $cc_settings["license_name"];
    
    $text_link_format = '<a rel="license" href="%s">%s %s %s</a>';
    return sprintf($text_link_format, $license_url, __('Creative Commons', 'cc-configurator'), trim($license_name), __('License', 'cc-configurator'));
}


/**
 * Returns Full IMAGE hyperlink to License <a href=...><img.../></a>
 *
 * Creative Commons Icon Selection
 * "0" : 88x31.png
 * "1" : http://creativecommons.org/images/public/somerights20.png
 * "2" : 80x15.png
 *
 * CSS customization via "cc-button" class.
 */
function bccl_get_license_image_hyperlink($button = "default") {

    global $default_button;
    
    $cc_settings = get_option("cc_settings");

    // If there is no global license, stop here
    if ( empty($cc_settings['license_url']) ) {
        return '';
    }

    $license_url = $cc_settings["license_url"];
    $license_name = $cc_settings["license_name"];
    $license_button = $cc_settings["license_button"];
    
    // Available buttons
    $buttons = array(
        "0" => dirname($license_button) . "/88x31.png",
        "1" => "http://creativecommons.org/images/public/somerights20.png",
        "2" => dirname($license_button) . "/80x15.png"
        );
    
    // Modify button
    if ($button == "default") {
        if (array_key_exists($default_button, $buttons)) {
            $license_button = $buttons[$default_button];
        }
    } elseif (array_key_exists($button, $buttons)){
        $license_button = $buttons[$button];
    }
    
    // Finally check whether the WordPress site is served over the HTTPS protocol
    // so as to use https in the image source. Creative Commons makes license
    // images available over HTTPS as well.
    if (is_ssl()) {
        $license_button = str_replace('http://', 'https://', $license_button);
    }

    $image_link_format = "<a rel=\"license\" href=\"%s\"><img alt=\"%s\" src=\"%s\" class=\"cc-button\" /></a>";
    return sprintf($image_link_format, $license_url, __('Creative Commons License', 'cc-configurator'), $license_button);

}


/**
 * Returns only the license URL.
 */
function bccl_get_license_url() {
    $cc_settings = get_option("cc_settings");
    // If there is no global license, stop here
    if ( empty($cc_settings['license_url']) ) {
        return '';
    }
    return $cc_settings["license_url"];
}


/**
 * Returns only the license deed URL.
 */
function bccl_get_license_deed_url() {
    $cc_settings = get_option("cc_settings");
    // If there is no global license, stop here
    if ( empty($cc_settings['license_url']) ) {
        return '';
    }
    return $cc_settings["deed_url"];
}


/**
 * Returns the full HTML code of the license
 */
function bccl_get_full_html_license($button = "default") {
    $cc_settings = get_option("cc_settings");
    // If there is no global license, stop here
    if ( empty($cc_settings['license_url']) ) {
        return '';
    }
    return bccl_get_license_image_hyperlink($button) . "<br />" . bccl_get_license_text_hyperlink();
}


/**
 *  Return license text for widget
 */
function bccl_get_widget_output() {

    global $post;

    $widget_html = '';

    $cc_settings = get_option("cc_settings");
    // If there is no global license, stop here
    if ( empty($cc_settings['license_url']) ) {
        return '';
    }

    // Get content specific license from the custom field
    $bccl_license = get_post_meta( $post->ID, '_bccl_license', true );
    if ( empty( $bccl_license ) ) {
        // Set to default
        $bccl_license = 'default';
    }

    // DEFAULT LICENSE
    // If no custom license has been set for this content
    if ( $bccl_license == 'default' ) {
        $widget_html = bccl_get_license_image_hyperlink("default") . "<br /><br />" . bccl_get_license_text_hyperlink();
    }

    // CC0
    elseif ( $bccl_license == 'cc0' ) {

        $license = bccl_get_license( 'cc0' );

        // TODO: fix this. Make up your mind 1) Use Partner Interface 2) Drop down menu for license selection. THEN create a function that creates the image link!!
        // License button inclusion
        $button_code = '';
        if ( $cc_settings["cc_body_img"] == '1' ) {
            if (is_ssl()) {
                $license['button_url'] = str_replace('http://', 'https://', $license['button_url']);
            }
            $button_code = sprintf( "<a rel=\"license\" href=\"%s\"><img alt=\"%s\" src=\"%s\" class=\"cc-button\" /></a><br />", $license['url'], 'CC0', $license['button_url'] );
            $button_code .= '<br />';
        }

        $license_text = $license['text'];

        $widget_html = $button_code . $license_text;
    }
    
    // ARR
    elseif ( $bccl_license == 'arr' ) {
        $widget_html = '';
    }

    // Manual licensing
    elseif ( $bccl_license == 'manual' ) {
        $widget_html = '';
    }

    // Allow filtering of the widget HTML
    $widget_html = apply_filters( 'bccl_widget_html', $widget_html );

    return $widget_html;
}


/**
 * This function should not be used in template tags.
 *
 * $work: The work that is licensed can be defined by the user.
 * $show_button: (default, yes, no) - no explanation (TODO possibly define icon URL)
 * $button: The user can se the desired button (hidden feature): "0", "1", "2"
 *
 */
function bccl_get_license_block( $work='', $css_class='', $show_button='default', $button='default' ) {
    
    global $post;

    $cc_block = "LICENSE BLOCK ERROR";
    $cc_settings = get_option("cc_settings");

    // If there is no global license, stop here
    // Since this may be called from the templates, we perform this check here once again.
    if ( empty($cc_settings['license_url']) ) {
        return '';
    }

    // Set CSS class
    if (empty($css_class)) {
        $css_class = 'cc-block';
    }

    // Get content specific license from the custom field
    $bccl_license = get_post_meta( $post->ID, '_bccl_license', true );
    if ( empty( $bccl_license ) ) {
        // Set to default
        $bccl_license = 'default';
    }

    // DEFAULT LICENSE
    // If no custom license has been set for this content
    if ( $bccl_license == 'default' ) {

        // License button inclusion
        $button_code = '';
        if ($show_button == "default") {
            if ($cc_settings["cc_body_img"]) {
                $button_code = bccl_get_license_image_hyperlink($button) . "<br />";
            }
        } elseif ($show_button == "yes") {
            $button_code = bccl_get_license_image_hyperlink($button) . "<br />";
        } elseif ($show_button == "no") {
            $button_code = "";
        } else {
            $button_code = "ERROR";
        }
    
        // Work analysis
        if ( empty($work) ) {
            // Proceed only if the user has not defined the work.
            if ( $cc_settings["cc_extended"] == '1' ) {
                $creator = bccl_get_the_creator($cc_settings["cc_creator"]);
                $author_archive_url = get_author_posts_url( get_the_author_meta( 'ID' ) );
                $work = "<em><a href=\"" . get_permalink() . "\">" . get_the_title() . "</a></em>";
                $by = "<em><a href=\"" . $author_archive_url . "\">" . $creator . "</a></em>";
                $work = sprintf("%s %s %s", $work, __("by", 'cc-configurator'), $by);
            } else {
                $work = __('This work', 'cc-configurator');
            }
        }
        $work .= sprintf(", ".__('unless otherwise expressly stated', 'cc-configurator').", ".__('is licensed under a', 'cc-configurator')." %s.", bccl_get_license_text_hyperlink());
    
        // Additional Permissions
        if ( ! empty( $cc_settings["cc_perm_url"] ) ) {
            $additional_perms = " ".__('Terms and conditions beyond the scope of this license may be available at', 'cc-configurator')." <a href=\"" . $cc_settings["cc_perm_url"] . "\">" . $_SERVER["HTTP_HOST"] . "</a>.";
        } else {
            $additional_perms = "";
        }

        $license_text = $work . $additional_perms;

        // Derivatives 
        // @see: http://wiki.creativecommons.org/Best_practices_for_attribution
        if ( ! empty($cc_settings['cc_derivative']) && "1" == $cc_settings['cc_derivative'] ) {
                $orig_title = (empty( $cc_settings['cc_derivative_orig_title'] )) ? '' : 'of <cite>' . $cc_settings['cc_derivative_orig_title'] . '</cite>';
                // hyperlink if both aren't empty
                $orig_title = ( ! empty( $orig_title ) && ! empty( $cc_settings['cc_derivative_orig_src'] )) ? 'of <cite><a href="' . $cc_settings['cc_derivative_orig_src'] . '">' . $cc_settings['cc_derivative_orig_title'] . '</a></cite>' : $orig_title;
                $orig_author = (empty( $cc_settings['cc_derivative_orig_author'] )) ? '' : 'by ' . $cc_settings['cc_derivative_orig_author'];
                $orig_license = (empty( $cc_settings['cc_derivative_orig_lic'] )) ? '' : 'used under ' . $cc_settings['cc_derivative_orig_lic'];

                $derivative_text = sprintf( 'This is a derivative %s %s %s. ', $orig_title, $orig_author, $orig_license );
                $license_text = $derivative_text . $license_text;
        }
        
        // $pre_text = 'Copyright &copy; ' . get_the_date('Y') . ' - Some Rights Reserved' . '<br />';
        $license_text = apply_filters( 'bccl_cc_license_text', $license_text );

        $cc_block = sprintf( "<p class=\"%s\">%s%s</p>", $css_class, $button_code, $license_text );

    }

    // CC0
    elseif ( $bccl_license == 'cc0' ) {

        $license = bccl_get_license( 'cc0' );

        // License button inclusion
        $button_code = '';
        if ( $cc_settings["cc_body_img"] == '1' ) {
            if (is_ssl()) {
                $license['button_url'] = str_replace('http://', 'https://', $license['button_url']);
            }
            $button_code = sprintf( "<a rel=\"license\" href=\"%s\"><img alt=\"%s\" src=\"%s\" class=\"cc-button\" /></a><br />", $license['url'], 'CC0', $license['button_url'] );
        }

        if ( $cc_settings["cc_extended"] == '1' ) {
            $creator = bccl_get_the_creator($cc_settings["cc_creator"]);
            $author_archive_url = get_author_posts_url( get_the_author_meta( 'ID' ) );
            $creator_link = "<em><a href=\"" . $author_archive_url . "\">" . $creator . "</a></em>";
            $work_link = "<em><a href=\"" . get_permalink() . "\">" . get_the_title() . "</a></em>";
            $license_text = sprintf( $license['extended_text'], $creator_link, $work_link );
        } else {
            $license_text = $license['text'];
        }

        $license_text = apply_filters( 'bccl_cc0_license_text', $license_text );

        $cc_block = sprintf("<p class=\"%s\">%s%s</p>", $css_class, $button_code, $license_text);
    }

    // ARR
    elseif ( $bccl_license == 'arr' ) {

        $license = bccl_get_license( 'arr' );

        $license_text = sprintf( $license['text'], get_the_date('Y') );

        // Additional Permissions
        if ( ! empty( $cc_settings["cc_perm_url"] ) ) {
            $additional_perms = $license['additional_perms_text'] . " <a href=\"" . $cc_settings["cc_perm_url"] . "\">" . $cc_settings["cc_perm_url"] . "</a>.";
        } else {
            $additional_perms = "";
        }

        $license_text = $license_text . $additional_perms;

        $license_text = apply_filters( 'bccl_arr_license_text', $license_text );

        $cc_block = sprintf( "<p class=\"%s\">%s</p>", $css_class, $license_text );
    }

    // NO LICENSE
    elseif ( $bccl_license == 'manual' ) {
        $cc_block = '';
    }

    return $cc_block;
}



// Action

function bccl_add_to_header() {
    /*
    Adds a link element with "license" relation in the web page HEAD area.
    
    Also, adds style for the license block, only if the user has:
     * enabled the display of such a block
     * not disabled internal license block styling
     * if it is single-post view
    */
    $post = get_queried_object();

    $cc_settings = get_option("cc_settings");

    // If there is no global license, stop here
    if ( empty($cc_settings['license_url']) ) {
        return '';
    }

    if ( is_singular() && ! is_front_page() ) { // The license link is not appended to static front page content.

        // Print our comment
        echo PHP_EOL . "<!-- BEGIN Creative Commons License added by Creative-Commons-Configurator plugin for WordPress -->" . PHP_EOL;

        // Internal style. If the user has not deactivated our internal style, print it too
        if ( $cc_settings["cc_no_style"] != "1" ) {
            // Adds style for the license block
            $color = $cc_settings["cc_color"];
            $bgcolor = $cc_settings["cc_bgcolor"];
            $brdrcolor = $cc_settings["cc_brdr_color"];
            $bccl_default_block_style = "clear: both; width: 90%; margin: 8px auto; padding: 4px; text-align: center; border: 1px solid $brdrcolor; color: $color; background-color: $bgcolor;";
            $style = "<style type=\"text/css\"><!--" . PHP_EOL . "p.cc-block { $bccl_default_block_style }" . PHP_EOL . "--></style>" . PHP_EOL;
            echo $style;
        }

        // If the addition of data in the head section has been enabled
        if ( $cc_settings["cc_head"] == "1" ) {

            // Get content specific license from the custom field
            $bccl_license = get_post_meta( $post->ID, '_bccl_license', true );
            if ( empty( $bccl_license ) ) {
                // Set to default
                $bccl_license = 'default';
            }

            // DEFAULT LICENSE
            // If no custom license has been set for this content
            if ( $bccl_license == 'default' ) {
                // Adds a link element with "license" relation in the web page HEAD area.
                echo "<link rel=\"license\" type=\"text/html\" href=\"" . bccl_get_license_url() . "\" />";
            }

            // CC0
            elseif ( $bccl_license == 'cc0' ) {
                $license = bccl_get_license( 'cc0' );
                // Adds a link element with "license" relation in the web page HEAD area.
                echo "<link rel=\"license\" type=\"text/html\" href=\"" . $license['url'] . "\" />";
            }

        }

        // Closing comment
        echo PHP_EOL . "<!-- END Creative Commons License added by Creative-Commons-Configurator plugin for WordPress -->" . PHP_EOL . PHP_EOL;
    }
}



/**
 * Adds the CC RSS module namespace declaration.
 */
function bccl_add_cc_ns_feed() {

    $cc_settings = get_option("cc_settings");

    // If there is no global license, stop here
    if ( empty($cc_settings['license_url']) ) {
        return '';
    }

    if ( $cc_settings["cc_feed"] == "1" ) {
        echo "xmlns:creativeCommons=\"http://backend.userland.com/creativeCommonsRssModule\"" . PHP_EOL;
    }
}


/**
 * Adds the CC URL to the feed.
 */
function bccl_add_cc_element_feed() {

    $cc_settings = get_option("cc_settings");

    // If there is no global license, stop here
    if ( empty($cc_settings['license_url']) ) {
        return '';
    }

    if ( $cc_settings["cc_feed"] == "1" ) {
        echo "\t<creativeCommons:license>" . bccl_get_license_url() . "</creativeCommons:license>" . PHP_EOL;
    }
}


/**
 * Adds the CC URL to the feed items.
 */
function bccl_add_cc_element_feed_item() {

    global $post;

    $cc_settings = get_option("cc_settings");
    
    // If there is no global license, stop here
    if ( empty($cc_settings['license_url']) ) {
        return '';
    }

    // If the addition of data in the feeds has been enabled
    if ( $cc_settings["cc_feed"] == "1" ) {

        // Get content specific license from the custom field
        $bccl_license = get_post_meta( $post->ID, '_bccl_license', true );
        if ( empty( $bccl_license ) ) {
            // Set to default
            $bccl_license = 'default';
        }

        // DEFAULT LICENSE
        // If no custom license has been set for this content
        if ( $bccl_license == 'default' ) {
            echo "\t<creativeCommons:license>" . bccl_get_license_url() . "</creativeCommons:license>" . PHP_EOL;
        }

        // CC0
        elseif ( $bccl_license == 'cc0' ) {
            $license = bccl_get_license( 'cc0' );
            echo "\t\t<creativeCommons:license>" . $license['url'] . "</creativeCommons:license>" . PHP_EOL;
        }

    }
}


/*
 * Adds the license block under the published content.
 *
 * The check if the user has chosen to display a block under the published
 * content is performed in bccl_get_license_block(), in order not to retrieve
 * the saved settings two timesor pass them between functions.
 */
function bccl_append_to_post_body($PostBody) {

    // Get global settings
    $cc_settings = get_option("cc_settings");

    // If there is no global license, stop here
    if ( empty($cc_settings['license_url']) ) {
        return $PostBody;
    }

    if ( is_singular() && ! is_front_page() ) { // The license block is not appended to static front page content.

        if ( is_attachment() ) {
            if ( $cc_settings["cc_body_attachments"] != "1" ) {
                return $PostBody;
            }
        } elseif ( is_page() ) {
            if ( $cc_settings["cc_body_pages"] != "1" ) {
                return $PostBody;
            }
        } elseif ( is_single() ) {
            if ( $cc_settings["cc_body"] != "1" ) {
                return $PostBody;
            }
        }

        // Append the license block to the content
        $cc_block = bccl_get_license_block("", "", "default", "default");
        if ( ! empty($cc_block) ) {
            $PostBody .= bccl_add_placeholders($cc_block);
        }

    }
    return $PostBody;
}



// ACTION

add_action('wp_head', 'bccl_add_to_header', 10);

add_filter('the_content', 'bccl_append_to_post_body', 250);

// Feeds

add_action('rdf_ns', 'bccl_add_cc_ns_feed');
add_action('rdf_header', 'bccl_add_cc_element_feed');
add_action('rdf_item', 'bccl_add_cc_element_feed_item');

add_action('rss2_ns', 'bccl_add_cc_ns_feed');
add_action('rss2_head', 'bccl_add_cc_element_feed');
add_action('rss2_item', 'bccl_add_cc_element_feed_item');

add_action('atom_ns', 'bccl_add_cc_ns_feed');
add_action('atom_head', 'bccl_add_cc_element_feed');
add_action('atom_entry', 'bccl_add_cc_element_feed_item');

?>