<?php
/*
 *   Display the configuration options for AtD
 */

/*
 *   A convienence function to display the HTML for an AtD option
 */
function TSpell_print_option( $name, $value, $options ) {
	// Attribute-safe version of $name
	$attr_name = sanitize_title($name); // Using sanitize_title since there's no comparable function for attributes
?>
   <input type="checkbox" id="atd_<?php echo ($attr_name) ?>" name="<?php echo $options['name'] ?>[<?php echo $name; ?>]" value="1" <?php checked( '1', isset( $options[$name] ) ? $options[$name] : false ); ?>> <label for="atd_<?php echo $attr_name ?>"><?php echo $value; ?></label>
<?php
}

/*
 *  Save AtD options
 */
function TSpell_process_options_update() {

	$user = wp_get_current_user();

	if ( ! $user || $user->ID == 0 )
		return;

	TSpell_update_options( $user->ID, 'TSpell_options' );
	TSpell_update_options( $user->ID, 'TSpell_check_when' );
	TSpell_update_options( $user->ID, 'TSpell_guess_lang' );
}

/*
 *  Display the various AtD options
 */
function TSpell_display_options_form() {

	// grab our user and validate their existence
	$user = wp_get_current_user();
	if ( ! $user || $user->ID == 0 )
		return;

	$options_show_types = TSpell_get_options( $user->ID, 'TSpell_options' );
	$options_check_when = TSpell_get_options( $user->ID, 'TSpell_check_when' );
	$options_guess_lang = TSpell_get_options( $user->ID, 'TSpell_guess_lang' );
?>
   <table class="form-table">
      <tr valign="top">
         <th scope="row"> <a id="atd"></a> <?php _e( 'Proofreading', 'tinymce-spellcheck' ); ?></th>
		 <td>
   <p><?php _e( 'Automatically proofread content when:', 'tinymce-spellcheck' ); ?>

   <p><?php
		TSpell_print_option( 'onpublish', __('a post or page is first published', 'tinymce-spellcheck'), $options_check_when );
		echo '<br />';
		TSpell_print_option( 'onupdate', __('a post or page is updated', 'tinymce-spellcheck'), $options_check_when );
   ?></p>

   <p style="font-weight: bold"><?php _e('English Options', 'tinymce-spellcheck'); ?></font>

   <p><?php _e('Enable proofreading for the following grammar and style rules when writing posts and pages:', 'tinymce-spellcheck'); ?></p>

   <p><?php
		TSpell_print_option( 'Bias Language', __('Bias Language', 'tinymce-spellcheck'), $options_show_types );
		echo '<br />';
		TSpell_print_option( 'Cliches', __('Clich&eacute;s', 'tinymce-spellcheck'), $options_show_types );
		echo '<br />';
		TSpell_print_option( 'Complex Expression', __('Complex Phrases', 'tinymce-spellcheck'), $options_show_types );
		echo '<br />';
		TSpell_print_option( 'Diacritical Marks', __('Diacritical Marks', 'tinymce-spellcheck'), $options_show_types );
		echo '<br />';
		TSpell_print_option( 'Double Negative', __('Double Negatives', 'tinymce-spellcheck'), $options_show_types );
		echo '<br />';
		TSpell_print_option( 'Hidden Verbs', __('Hidden Verbs', 'tinymce-spellcheck'), $options_show_types );
		echo '<br />';
		TSpell_print_option( 'Jargon Language', __('Jargon', 'tinymce-spellcheck'), $options_show_types );
		echo '<br />';
		TSpell_print_option( 'Passive voice', __('Passive Voice', 'tinymce-spellcheck'), $options_show_types );
		echo '<br />';
		TSpell_print_option( 'Phrases to Avoid', __('Phrases to Avoid', 'tinymce-spellcheck'), $options_show_types );
		echo '<br />';
		TSpell_print_option( 'Redundant Expression', __('Redundant Phrases', 'tinymce-spellcheck'), $options_show_types );
   ?></p>
   <p><?php printf( __( '<a href="%s">Learn more</a> about these options.', 'tinymce-spellcheck' ), 'http://support.wordpress.com/proofreading/' );
?></p>

   <p style="font-weight: bold"><?php _e( 'Language', 'tinymce-spellcheck' ); ?></font>

   <p><?php printf(
	_x( 'The proofreader supports English, French, German, Portuguese, and Spanish. Your <a href="%1$s">%2$s</a> value is the default proofreading language.', '%1$s = http://codex.wordpress.org/Installing_WordPress_in_Your_Language, %2$s = WPLANG', 'tinymce-spellcheck' ),
	'http://codex.wordpress.org/Installing_WordPress_in_Your_Language',
	'WPLANG'
   ); ?></p>

   <p><?php
	TSpell_print_option( 'true', __('Use automatically detected language to proofread posts and pages', 'tinymce-spellcheck' ), $options_guess_lang );
   ?></p>

<?php
}

/*
 *  Returns an array of AtD user options specified by $name
 */
function TSpell_get_options( $user_id, $name ) {
	$options_raw = TSpell_get_setting( $user_id, $name, 'single' );

	$options = array();
	$options['name'] = $name;

	if ( $options_raw )
		foreach ( explode( ',', $options_raw ) as $option )
			$options[ $option ] = 1;

	return $options;
}

/*
 *  Saves set of user options specified by $name from POST data
 */
function TSpell_update_options( $user_id, $name ) {
	// We should probably run $_POST[name] through an esc_*() function...
	if ( isset( $_POST[$name] ) && is_array( $_POST[$name] ) ) {
		$copy = array_map( 'strip_tags', array_keys( $_POST[$name] ) );
		TSpell_update_setting( $user_id, TSpell_sanitize( $name ), implode( ',', $copy )  );
	} else {
		TSpell_update_setting( $user_id, TSpell_sanitize( $name ), '');
	}

	return;
}
