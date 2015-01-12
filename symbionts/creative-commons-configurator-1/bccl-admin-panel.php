<?php
/**
 * Modifications on this page include adding fields to the admin interface to 
 * allow for declarations of derivative works. 2014, Brad Payne.
 */

function bccl_show_info_msg($msg) {
    echo '<div id="message" class="updated fade"><p>' . $msg . '</p></div>';
}


/*
* Construct the Creative Commons Configurator administration panel under Settings->License
*/
add_action( 'admin_init', 'bccl_admin_init' );
add_action( 'admin_menu', 'bccl_admin_menu');


function bccl_admin_init() {

    // Here we just add some dummy variables that contain the plugin name and
    // the description exactly as they appear in the plugin metadata, so that
    // they can be translated.
    $bccl_plugin_name = __('Creative Commons Configurator', 'cc-configurator');
    $bccl_plugin_description = __('Helps you publish your content under the terms of a Creative Commons license.', 'cc-configurator');

    // Perform automatic settings upgrade based on settings version.
    // Also creates initial default settings automatically.
    bccl_plugin_upgrade();

    // Register scripts and styles

    /* Register our script. */
    // wp_register_script( 'my-plugin-script', plugins_url( '/script.js', __FILE__ ) );
    /* Register our stylesheet. */
    // wp_register_style( 'myPluginStylesheet', plugins_url('stylesheet.css', __FILE__) );

}


function bccl_admin_menu() {
    /* Register our plugin page */
    $page_hook_suffix = add_options_page(
        __('License Settings', 'cc-configurator'),
        __('License', 'cc-configurator'),
        'manage_options',
        'cc-configurator-options',
        'bccl_options_page'
    );

    /*
     * Use the retrieved $page_hook_suffix to hook the function that links our script.
     * This hook invokes the function only on our plugin administration screen,
     * see: http://codex.wordpress.org/Administration_Menus#Page_Hook_Suffix
     */
    add_action( 'admin_print_scripts-' . $page_hook_suffix, 'bccl_admin_scripts');
    /* Again use $page_hook_suffix to hook the function that links our stylesheet. */
    add_action( 'admin_print_styles-' . $page_hook_suffix, 'bccl_admin_styles' );
}


function bccl_admin_scripts() {
    // Link our already registered script to a page
    //wp_enqueue_script( 'my-plugin-script' );
}
function bccl_admin_styles() {
    // It will be called only on your plugin admin page, enqueue our stylesheet here
    //wp_enqueue_style( 'myPluginStylesheet' );
}


function bccl_options_page() {
    // Permission Check
    if ( ! current_user_can( 'manage_options' ) )  {
        wp_die( __( 'You do not have sufficient permissions to access this page.' ) );
    }

    if (isset($_POST['info_update'])) {

        bccl_save_settings( $_POST );

    } elseif ( isset( $_POST['info_reset'] ) ) {

        bccl_reset_settings();

    } elseif ( isset( $_GET['new_license'] ) ) {

        bccl_set_new_license_settings( $_GET );

    } elseif ( isset( $_POST['license_reset'] ) ) {

        bccl_reset_license_settings();

    }

    // Try to get the options from the DB.
    $cc_settings = get_option('cc_settings');
    //var_dump($cc_settings);

    if ( ! empty( $cc_settings['license_url'] ) ) {

        bccl_set_license_options($cc_settings);

    } else {

        bccl_select_license();

    }

}


/** Enqueue scripts and styles
 *  From: http://codex.wordpress.org/Plugin_API/Action_Reference/admin_enqueue_scripts#Example:_Target_a_Specific_Admin_Page
 *  For a better way to add custom scripts and styles:
 *  - http://codex.wordpress.org/Function_Reference/wp_enqueue_script#Link_Scripts_Only_on_a_Plugin_Administration_Screen
 *  - http://codex.wordpress.org/Function_Reference/wp_enqueue_style#Load_stylesheet_only_on_a_plugin.27s_options_page
 */
function bccl_my_enqueue($hook) {
    //var_dump($hook);
    if ( 'settings_page_cc-configurator-options' != $hook ) {
        return;
    }
    wp_enqueue_script('thickbox');
    wp_enqueue_style('thickbox');
}
//add_action( 'admin_enqueue_scripts', 'bccl_my_enqueue' );



function bccl_select_license() {
    /*
     * License selection using the partner interface.
     * http://wiki.creativecommons.org/Partner_Interface
     */

    // Determine the protocol
    $proto = 'http';
    if ( is_ssl() ) {
        $proto = 'https';
    }
    // Partner Interface URL
    $cc_partner_interface_url = "$proto://creativecommons.org/license/";

    // Collect Query Arguments
    $partner = urlencode( 'WordPress-Creative-Commons-Configurator-Plugin' );
    $partner_icon_url = admin_url( 'images/wordpress-logo.png' );
    $jurisdiction_choose = '1';
    $lang = urlencode( get_bloginfo('language') );
    $exit_url = urlencode( admin_url( 'options-general.php?page=cc-configurator-options' ) . 
        "&license_url=[license_url]&license_name=[license_name]&license_button=[license_button]&deed_url=[deed_url]&new_license=1" );

    // Construct Query String
    $cc_partner_interface_query_string = "partner=$partner&partner_icon_url=$partner_icon_url&jurisdiction_choose=$jurisdiction_choose&lang=$lang&exit_url=$exit_url";

    // Not currently used. Could be utilized to present the partner interace in an iframe.
    //$Partner_Interface_URI = htmlspecialchars("$proto://creativecommons.org/license/?");
    //$Partner_Interface_URI = "$proto://creativecommons.org/license/?" . $cc_partner_interface_query_string;


    print('
    <div class="wrap">
        <div id="icon-options-general" class="icon32"><br /></div>
        <h2>'.__('License Settings', 'cc-configurator').'</h2>
        <p>'.__('Welcome to the administration panel of the Creative-Commons-Configurator plugin for WordPress.', 'cc-configurator').'</p>

        <h2>'.__('Select License', 'cc-configurator').'</h2>
        <p>'.__('A license has not been set for your content. By pressing the following link you will be taken to the license selection wizard, hosted by the Creative Commons organization. Once you have completed the license selection process, you will be redirected back to this page.', 'cc-configurator').'</p>

        <form name="formnewlicense" id="bccl-new-license-form" method="get" action="' . $cc_partner_interface_url . '">
            <input type="hidden" name="partner" value="' . $partner . '" />
            <input type="hidden" name="partner_icon_url" value="' . $partner_icon_url . '" />
            <input type="hidden" name="jurisdiction_choose" value="' . $jurisdiction_choose . '" />
            <input type="hidden" name="lang" value="' . $lang . '" />
            <input type="hidden" name="exit_url" value="' . $exit_url . '" />

            <p class="submit">
                <input id="submit" class="button-primary" type="submit" value="'.__('New License', 'cc-configurator').'" name="new-license-button" />
            </p>
        </form>

    </div>');

    /**
     * See here for info about displaying the CC Partner Interface in thickbox:
     * http://www.codetrax.org/issues/1111
     */

}


function bccl_set_license_options($cc_settings) {
    /*
    CC License Options
    */
    global $wp_version;

    print('
    <div class="wrap">
        <div id="icon-options-general" class="icon32"><br /></div>
        <h2>'.__('License Settings', 'cc-configurator').'</h2>

        <p style="text-align: center;"><big>' . bccl_get_full_html_license() . '</big></p>
        <form name="formlicense" id="bccl_reset" method="post" action="' . admin_url( 'options-general.php?page=cc-configurator-options' ) . '">
            <fieldset>
                <legend class="screen-reader-text"><span>'.__('Current License', 'cc-configurator').'</span></legend>
                <p>'.__('A license has been set and will be used to license your work.', 'cc-configurator').'</p>
                <p>'.__('If you need to set a different license, press the <em>Reset License</em> button below.', 'cc-configurator').'</p>
            </fieldset>
            <p class="submit">
                <input type="submit" class="button-primary" name="license_reset" value="'.__('Reset License', 'cc-configurator').'" />
            </p>
        </form>
    </div>

    <div class="wrap" style="background: #EEF6E6; padding: 1em 2em; border: 1px solid #E4E4E4;' . (($cc_settings["cc_i_have_donated"]=="1") ? ' display: none;' : '') . '">
        <h2>'.__('Message from the author', 'cc-configurator').'</h2>
        <p style="font-size: 1.2em; padding-left: 2em;"><em>CC-Configurator</em> is released under the terms of the <a href="http://www.apache.org/licenses/LICENSE-2.0.html">Apache License version 2</a> and, therefore, is <strong>Free software</strong>.</p>
        <p style="font-size: 1.2em; padding-left: 2em;">However, a significant amount of <strong>time</strong> and <strong>energy</strong> has been put into developing this plugin, so, its production has not been free from cost. If you find this plugin useful and, if it has made your life easier, you can show your appreciation by buying me an <a href="http://bit.ly/1aoPaow">extra cup of coffee</a>.</p>
        <p style="font-size: 1.2em; padding-left: 2em;">Thank you in advance,<br />George Notaras</p>
        <div style="text-align: right;"><small>'.__('This message can de deactivated in the settings below.', 'cc-configurator').'</small></div>
    </div>

    <div class="wrap">
        <h2>'.__('Configuration', 'cc-configurator').'</h2>
        <p>'.__('Here you can choose where and how license information should be added to your blog.', 'cc-configurator').'</p>

        <form name="formbccl" method="post" action="' . admin_url( 'options-general.php?page=cc-configurator-options' ) . '">

        <table class="form-table">
        <tbody>

        <tr>
        <th scope="row">' . __( 'Derivative Work', 'cc-configurator' ) . '</th>
        <td>
        <fieldset>
            <legend class="screen-reader-text"><span>' . __( 'Derivative Work', 'cc-configurator' ) . '</span></legend>
            <input id="cc_derivative" type="checkbox" value="1" name="cc_derivative"' . (($cc_settings["cc_derivative"] == "1") ? ' checked="checked"' : '') . '" />
            <label for="cc_derivative">
            ' . __( 'Is this a derivative of another Creative Commons Licensed work?', 'cc-configurator' ) . '
            </label><br>
            
            <p>                
                <input type="text" id="cc_derivative_orig_title" name="cc_derivative_orig_title" value="' . $cc_settings["cc_derivative_orig_title"] . '" size="50" />
                <label for="cc_derivative_orig_title">
                ' . __( '<b>Original title</b> <small><i>(from which you are creating a derivative work)</i></small>', 'cc-configurator' ) . '
                </label>
            </p>
            <p>
                <input type="text" id="cc_derivative_orig_author" name="cc_derivative_orig_author" value="' . $cc_settings["cc_derivative_orig_author"] . '" size="50" />
                <label for="cc_derivative_orig_author">
                ' . __( '<b>Original author</b> <small><i>(from which you are creating a derivative work)</i></small>', 'cc-configurator' ) . '
                </label>
            </p>
            <p>
                <input type="text" id="cc_derivative_orig_src" name="cc_derivative_orig_src" value="' . $cc_settings["cc_derivative_orig_src"] . '" size="50" />
                <label for="cc_derivative_orig_src">
                ' . __( '<b>Original source</b> <small><i>(URL or hyperlink where the original material resides)</i></small>', 'cc_configurator' ) . '
                </label>
            </p>
            <p>
                <label for="cc_derivative_orig_lic">
                ' . __( '<b>Original license</b> <small><i>(from which you are creating a derivative of)</i></small>', 'cc-configurator' ) . '
                </label><br>
                <input name="cc_derivative_orig_lic" type="radio" value="CC BY"' . (($cc_settings["cc_derivative_orig_lic"] == "CC BY") ? ' checked="checked"' : '') . '>CC BY <small><i>(Attribution)</i></small></input><br>
                <input name="cc_derivative_orig_lic" type="radio" value="CC BY-SA"' . (($cc_settings["cc_derivative_orig_lic"] == "CC BY-SA") ? ' checked="checked"' : '') . ' >CC BY-SA <small><i>(Attribution-ShareAlike)</i></small></input><br>
                <input name="cc_derivative_orig_lic" type="radio" value="CC BY-NC"' . (($cc_settings["cc_derivative_orig_lic"] == "CC BY-NC") ? ' checked="checked"' : '') . ' >CC BY-NC <small><i>(Attribution-NonCommercial)</i></small></input><br>
                <input name="cc_derivative_orig_lic" type="radio" value="CC BY-NC-SA"' . (($cc_settings["cc_derivative_orig_lic"] == "CC BY-NC-SA") ? ' checked="checked"' : '') . ' >CC BY-NC-SA <small><i>(Attribution-NonCommercial-ShareAlike)</i></small></input>
		<input id="cc_feed" type="hidden" value="0" name="cc_feed"" />
</p>
        </fieldset>
        </td>
        </tr>
      
            <tr valign="top">
            <th scope="row">'.__('Page Head HTML', 'cc-configurator').'</th>
            <td>
            <fieldset>
                <legend class="screen-reader-text"><span>'.__('Page Head HTML', 'cc-configurator').'</span></legend>
                <input id="cc_head" type="checkbox" value="1" name="cc_head" '. (($cc_settings["cc_head"]=="1") ? 'checked="checked"' : '') .'" />
                <label for="cc_head">
                '.__('Include license information in the page\'s HTML head. This will not be visible to human visitors, but search engine bots will be able to read it. Note that the insertion of license information in the HTML head is done in relation to the content types (posts, pages or attachment pages) on which the license text block is displayed (see the <em>text block</em> settings below). (<em>Recommended</em>)', 'cc-configurator').'
                </label>
                <br />
            </fieldset>
            </td>
            </tr>

            <tr valign="top">
            <th scope="row">'.__('Text Block', 'cc-configurator').'</th>
            <td>
            <fieldset>
                <legend class="screen-reader-text"><span>'.__('Text Block', 'cc-configurator').'</span></legend>

                <p>'.__('By enabling the following options, a small block of text, which contains links to the author, the work and the used license, is appended to the published content.', 'cc-configurator').'</p>

                <input id="cc_body" type="checkbox" value="1" name="cc_body" '. (($cc_settings["cc_body"]=="1") ? 'checked="checked"' : '') .'" />
                <label for="cc_body">
                '.__('Posts: Add the text block with license information under the published posts. (<em>Recommended</em>)', 'cc-configurator').'
                </label>
                <br />

                <input id="cc_body_pages" type="checkbox" value="1" name="cc_body_pages" '. (($cc_settings["cc_body_pages"]=="1") ? 'checked="checked"' : '') .'" />
                <label for="cc_body_pages">
                '.__('Pages: Add the text block with license information under the published pages.', 'cc-configurator').'
                </label>
                <br />

                <input id="cc_body_attachments" type="checkbox" value="1" name="cc_body_attachments" '. (($cc_settings["cc_body_attachments"]=="1") ? 'checked="checked"' : '') .'" />
                <label for="cc_body_attachments">
                '.__('Attachments: Add the text block with license information under the attached content in attachment pages.', 'cc-configurator').'
                </label>
                <br />

                <p>'.__('By enabling the following option, the license image is also included in the license text block.', 'cc-configurator').'</p>

                <input id="cc_body_img" type="checkbox" value="1" name="cc_body_img" '. (($cc_settings["cc_body_img"]=="1") ? 'checked="checked"' : '') .'" />
                <label for="cc_body_img">
                '.__('Include the license image in the text block.', 'cc-configurator').'
                </label>
                <br />
            </fieldset>
            </td>
            </tr>

            <tr valign="top">
            <th scope="row">'.__('Extra Text Block Customization', 'cc-configurator').'</th>
            <td>
            <p>'.__('The following settings have an effect only if the text block containing licensing information has been enabled above.', 'cc-configurator').'</p>
            <fieldset>
                <legend class="screen-reader-text"><span>'.__('Extra Text Block Customization', 'cc-configurator').'</span></legend>

                <input id="cc_extended" type="checkbox" value="1" name="cc_extended" '. (($cc_settings["cc_extended"]=="1") ? 'checked="checked"' : '') .'" />
                <label for="cc_extended">
                '.__('Include extended information about the published work and its creator. By enabling this option, hyperlinks to the published content and its creator/publisher are also included into the license statement inside the block. This, by being an attribution example itself, will generally help others to attribute the work to you.', 'cc-configurator').'
                </label>
                <br />
                <br />

                <select name="cc_creator" id="cc_creator">');
                $creator_arr = bccl_get_creator_pool();
                foreach ($creator_arr as $internal => $creator) {
                    if ($cc_settings["cc_creator"] == $internal) {
                        $selected = ' selected="selected"';
                    } else {
                        $selected = '';
                    }
                    printf('<option value="%s"%s>%s</option>', $internal, $selected, $creator);
                }
                print('</select>
                <br />
                <label for="cc_creator">
                '.__('If extended information about the published work has been enabled, then you can choose which name will indicate the creator of the work. By default, the blog name is used.', 'cc-configurator').'
                </label>
                <br />
                <br />

                <input name="cc_perm_url" type="text" id="cc_perm_url" class="code" value="' . $cc_settings["cc_perm_url"] . '" size="100" maxlength="1024" />
                <br />
                <label for="cc_perm_url">
                '.__('If you have added any extra permissions to your license, provide the URL to the webpage that contains them. It is highly recommended to use absolute URLs.', 'cc-configurator').'
                <br />
                <strong>'.__('Example', 'cc-configurator').'</strong>: <code>http://www.example.org/ExtendedPermissions</code>
                </label>
                <br />

            </fieldset>
            </td>
            </tr>

            
            <tr valign="top">
            <th scope="row">'.__('Colors of the text block', 'cc-configurator').'</th>
            <td>
            <p>'.__('The following settings have an effect only if the text block containing licensing information has been enabled above.', 'cc-configurator').'</p>
            <fieldset>
                <legend class="screen-reader-text"><span>'.__('Colors of the text block', 'cc-configurator').'</span></legend>

                <input name="cc_color" type="text" id="cc_color" class="code" value="' . $cc_settings["cc_color"] . '" size="7" maxlength="7" />
                <label for="cc_color">
                '.__('Set a color for the text that appears within the block (does not affect hyperlinks).', 'cc-configurator').'
                <br />
                <strong>'.__('Default', 'cc-configurator').'</strong>: <code>#000000</code>
                </label>
                <br />
                <br />

                <input name="cc_bgcolor" type="text" id="cc_bgcolor" class="code" value="' . $cc_settings["cc_bgcolor"] . '" size="7" maxlength="7" />
                <label for="cc_bgcolor">
                '.__('Set a background color for the block.', 'cc-configurator').'
                <br />
                <strong>'.__('Default', 'cc-configurator').'</strong>: <code>#eef6e6</code>
                </label>
                <br />
                <br />

                <input name="cc_brdr_color" type="text" id="cc_brdr_color" class="code" value="' . $cc_settings["cc_brdr_color"] . '" size="7" maxlength="7" />
                <label for="cc_brdr_color">
                '.__('Set a color for the border of the block.', 'cc-configurator').'
                <br />
                <strong>'.__('Default', 'cc-configurator').'</strong>: <code>#cccccc</code>
                </label>
                <br />
                <br />

                <input id="cc_no_style" type="checkbox" value="1" name="cc_no_style" '. (($cc_settings["cc_no_style"]=="1") ? 'checked="checked"' : '') .'" />
                <label for="cc_no_style">
                '.__('Disable the internal formatting of the license block. If the internal formatting is disabled, then the color selections above have no effect any more. You can still format the license block via your own CSS. The <em>cc-block</em> and <em>cc-button</em> classes have been reserved for formatting the license block and the license button respectively.', 'cc-configurator').'
                </label>
                <br />

            </fieldset>
            </td>
            </tr>

            <tr valign="top">
            <th scope="row">'.__('Donations', 'cc-configurator').'</th>
            <td>
            <fieldset>
                <legend class="screen-reader-text"><span>'.__('Donations', 'cc-configurator').'</span></legend>
                <input id="cc_i_have_donated" type="checkbox" value="1" name="cc_i_have_donated" '. (($cc_settings["cc_i_have_donated"]=="1") ? 'checked="checked"' : '') .'" />
                <label for="cc_i_have_donated">
                '. sprintf( __('By checking this, the <em>message from the author</em> above goes away. Thanks for <a href="%s">donating</a>!', 'cc-configurator'), 'http://bit.ly/1aoPaow' ) .'
                </label>
                <br />

            </fieldset>
            </td>
            </tr>


        </tbody>
        </table>

        <p class="submit">
            <input id="submit" class="button-primary" type="submit" value="'.__('Save Changes', 'cc-configurator').'" name="info_update" />
            <input id="reset" class="button-primary" type="submit" value="'.__('Reset to defaults', 'cc-configurator').'" name="info_reset" />
        </p>

        </form>

    </div>

    ');
}





/**
 * Adds Bccl_Widget widget.
 */
class Bccl_Widget extends WP_Widget {

	/**
	 * Register widget with WordPress.
	 */
	function __construct() {
		parent::__construct(
			'bccl_widget', // Base ID
			__('Creative Commons License', 'cc-configurator'), // Name
			array( 'description' => __( 'Licensing information', 'cc-configurator' ), ) // Description
		);
	}

	/**
	 * Front-end display of widget.
	 *
	 * @see WP_Widget::widget()
	 *
	 * @param array $args     Widget arguments.
	 * @param array $instance Saved values from database.
	 */
	public function widget( $args, $instance ) {

        $cc_settings = get_option("cc_settings");

        // Check whether we should display the widget content or not.
        // In general, if the license block is set to be displayed under the content,
        // then the widget is suppressed.
        if ( is_singular() && ! is_front_page() ) { // In static front pages we still want to display the widget and not append the license block to the text of the page.
            if ( is_attachment() ) {
                if ( $cc_settings["cc_body_attachments"] == "1" ) {
                    return;
                }
            } elseif ( is_page() ) {
                if ( $cc_settings["cc_body_pages"] == "1" ) {
                    return;
                }
            } elseif ( is_single() ) {
                if ( $cc_settings["cc_body"] == "1" ) {
                    return;
                }
            }
        }

        $widget_output = bccl_get_widget_output();
        if ( empty( $widget_output ) ) {
            return;
        }

		$title = apply_filters( 'widget_title', $instance['title'] );

		echo $args['before_widget'];
		if ( ! empty( $title ) )
			echo $args['before_title'] . $title . $args['after_title'];
        echo $widget_output;
		echo $args['after_widget'];
	}

	/**
	 * Back-end widget form.
	 *
	 * @see WP_Widget::form()
	 *
	 * @param array $instance Previously saved values from database.
	 */
	public function form( $instance ) {
		if ( isset( $instance[ 'title' ] ) ) {
			$title = $instance[ 'title' ];
		}
		else {
			$title = __( 'License', 'cc-configurator' );
		}
		?>
		<p>
		<label for="<?php echo $this->get_field_id( 'title' ); ?>"><?php _e( 'Title:' ); ?></label> 
		<input class="widefat" id="<?php echo $this->get_field_id( 'title' ); ?>" name="<?php echo $this->get_field_name( 'title' ); ?>" type="text" value="<?php echo esc_attr( $title ); ?>" />
		</p>
		<?php 
	}

	/**
	 * Sanitize widget form values as they are saved.
	 *
	 * @see WP_Widget::update()
	 *
	 * @param array $new_instance Values just sent to be saved.
	 * @param array $old_instance Previously saved values from database.
	 *
	 * @return array Updated safe values to be saved.
	 */
	public function update( $new_instance, $old_instance ) {
		$instance = array();
		$instance['title'] = ( ! empty( $new_instance['title'] ) ) ? strip_tags( $new_instance['title'] ) : '';

		return $instance;
	}

} // class Bccl_Widget

// register Bccl_Widget widget
function register_bccl_widget() {
    register_widget( 'Bccl_Widget' );
}
add_action( 'widgets_init', 'register_bccl_widget' );
























/**
 * Meta box in post/page editing panel.
 */

/* Define the custom box */
add_action( 'add_meta_boxes', 'bccl_add_license_box' );

/**
 * Adds a box to the main column of the editing panel of the supported post types.
 * See the bccl_get_post_types_for_metabox() docstring for more info on the supported types.
 */
function bccl_add_license_box() {
    $supported_types = bccl_get_post_types_for_metabox();

    // Add an CC-Configurator meta box to all supported types
    foreach ($supported_types as $supported_type) {
        add_meta_box( 
            'bccl-license-box',
            __( 'License', 'cc-configurator' ),
            'bccl_inner_license_box',
            $supported_type,
            'advanced',
            'high'
        );
    }

}


/**
 * Load CSS and JS for license box.
 * The editing pages are post.php and post-new.php
 */
function bccl_license_box_css_js () {
    // $supported_types = bccl_get_supported_post_types();
    // See: #900 for details

    // Using included Jquery UI
//    wp_enqueue_script('jquery');
//    wp_enqueue_script('jquery-ui-core');
//    wp_enqueue_script('jquery-ui-widget');
//    wp_enqueue_script('jquery-ui-tabs');

    //wp_register_style( 'bccl-jquery-ui-core', plugins_url('css/jquery.ui.core.css', __FILE__) );
    //wp_enqueue_style( 'bccl-jquery-ui-core' );
    //wp_register_style( 'bccl-jquery-ui-tabs', plugins_url('css/jquery.ui.tabs.css', __FILE__) );
    //wp_enqueue_style( 'bccl-jquery-ui-tabs' );
//    wp_register_style( 'bccl-metabox-tabs', plugins_url('css/bccl-metabox-tabs.css', __FILE__) );
//    wp_enqueue_style( 'bccl-metabox-tabs' );

}
// add_action('admin_print_styles-post.php', 'bccl_license_box_css_js');
// add_action('admin_print_styles-post-new.php', 'bccl_license_box_css_js');


/* For future reference - Add data to the HEAD area of post editing panel */
function bccl_metabox_script_caller() {
    print('
    <script>
        jQuery(document).ready(function($) {
        $("#bccl-metabox-tabs .hidden").removeClass(\'hidden\');
        $("#bccl-metabox-tabs").tabs();
        });
    </script>
    ');
}
// add_action('admin_head-post.php', 'bccl_metabox_script_caller');
// add_action('admin_head-post-new.php', 'bccl_metabox_script_caller');
// OR
// add_action('admin_footer-post.php', 'bccl_metabox_script_caller');
// add_action('admin_footer-post-new.php', 'bccl_metabox_script_caller');


/* Prints the box content */
function bccl_inner_license_box( $post ) {

    // Use a nonce field for verification
    wp_nonce_field( plugin_basename( __FILE__ ), 'bccl_noncename' );

    // Get the post type. Will be used to customize the displayed notes.
    $post_type = get_post_type( $post->ID );

    // Display the meta box HTML code.

    //
    //  Custom field: _bccl_license
    //
    //  Contains: a slug
    //
    //  Supported Slugs:
    //      'default': use default license
    //      'cc0': Creative Commons CC0, No Rights Reserved
    //      'arr': All Rights Reserved
    //      'manual': do not add the license information automatically
    //
    
    // Retrieve the field data from the database.
    $bccl_license_field_value = get_post_meta( $post->ID, '_bccl_license', true );
    if ( empty( $bccl_license_field_value ) ) {
        // Set to default
        $bccl_license_field_value = 'default';
    }

    //var_dump( $bccl_license_field_value );

    print('
        <p>
            <input type="radio" id="bccl_default" name="bccl_license_slug" value="default" '. (($bccl_license_field_value=='default') ? 'checked' : '') .'/> 
            <label for="bccl_default">'.__('Default Creative Commons license &ndash; Use the license that has been set in the general license settings.', 'cc-configurator').'</label>
            <br />

            <input type="radio" id="bccl_cc0" name="bccl_license_slug" value="cc0"'. (($bccl_license_field_value=='cc0') ? 'checked' : '') .'/> ' . __('', 'cc-configurator') . '
            <label for="bccl_cc0">'.__('No Rights Reserved &ndash; Release this work to the <a href="http://wiki.creativecommons.org/Public_domain">Public Domain</a> using the <a href="http://creativecommons.org/about/cc0">Creative Commons CC0</a>, a form of copyright and related rights waiver.', 'cc-configurator').'</label>
            <br />

            <input type="radio" id="bccl_arr" name="bccl_license_slug" value="arr"'. (($bccl_license_field_value=='arr') ? 'checked' : '') .'/>
            <label for="bccl_arr">'.__('All Rights Reserved &ndash; Reserve all rights provided by copyright law.', 'cc-configurator').'</label>
            <br />

            <input type="radio" id="bccl_manual" name="bccl_license_slug" value="manual" '. (($bccl_license_field_value=='manual') ? 'checked' : '') .'/>
            <label for="bccl_manual">'.__('No automatic license &ndash; Let me add licensing information manually.', 'cc-configurator').'</label>

        </p>
    ');

}




/* Manage the entered data */
add_action( 'save_post', 'bccl_save_postdata', 10, 2 );

/* When the post is saved, saves our custom description and keywords */
function bccl_save_postdata( $post_id, $post ) {

    // Verify if this is an auto save routine. 
    // If it is our form has not been submitted, so we dont want to do anything
    if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) 
        return;

    /* Verify the nonce before proceeding. */
    // Verify this came from the our screen and with proper authorization,
    // because save_post can be triggered at other times
    if ( !isset($_POST['bccl_noncename']) || !wp_verify_nonce( $_POST['bccl_noncename'], plugin_basename( __FILE__ ) ) )
        return;

    /* Get the post type object. */
	$post_type_obj = get_post_type_object( $post->post_type );

    /* Check if the current user has permission to edit the post. */
	if ( !current_user_can( $post_type_obj->cap->edit_post, $post_id ) )
		return;

    // OK, we're authenticated: we need to find and save the data

    //
    // Get value for custom field, Sanitize user input.
    $bccl_license_field_value = sanitize_text_field( stripslashes( $_POST['bccl_license_slug'] ) );   // slug (unique for each license)

    //var_dump( $bccl_license_field_value );

    // We only save the field, if the slug is other than 'default'
    // If the slug is 'default', then we just delete the custom field associated with this post
    if ( $bccl_license_field_value == 'default' ) {
        delete_post_meta( $post_id, '_bccl_license' );
    } else {
        update_post_meta( $post_id, '_bccl_license', $bccl_license_field_value);
    }
    
}


