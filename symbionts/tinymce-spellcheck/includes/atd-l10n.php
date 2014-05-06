<?php
/*
 * loads AtD localization strings (shared between Visual and HTML Editors)
 */
function TSpell_init_l10n_js() {
	if ( !TSpell_should_load_on_page() ) {
		return;
	}

	// load localized strings for AtD
	wp_localize_script( 'TSpell_settings', 'TSpell_l10n_r0ar', array (
		'menu_title_spelling'         => __( 'Spelling', 'tinymce-spellcheck' ),
		'menu_title_repeated_word'    => __( 'Repeated Word', 'tinymce-spellcheck' ),

		'menu_title_no_suggestions'   => __( 'No suggestions', 'tinymce-spellcheck' ),

		'menu_option_explain'         => __( 'Explain...', 'tinymce-spellcheck' ),
		'menu_option_ignore_once'     => __( 'Ignore suggestion', 'tinymce-spellcheck' ),
		'menu_option_ignore_always'   => __( 'Ignore always', 'tinymce-spellcheck' ),
		'menu_option_ignore_all'      => __( 'Ignore all', 'tinymce-spellcheck' ),

		'menu_option_edit_selection'  => __( 'Edit Selection...', 'tinymce-spellcheck' ),

		'button_proofread'            => __( 'proofread', 'tinymce-spellcheck' ),
		'button_edit_text'            => __( 'edit text', 'tinymce-spellcheck' ),
		'button_proofread_tooltip'    => __( 'Proofread Writing', 'tinymce-spellcheck' ),

		'message_no_errors_found'     => __( 'No writing errors were found.', 'tinymce-spellcheck' ),
		'message_server_error'        => __( 'There was a problem communicating with the Proofreading service. Try again in one minute.', 'tinymce-spellcheck' ),
		'message_server_error_short'  => __( 'There was an error communicating with the proofreading service.', 'tinymce-spellcheck' ),

		'dialog_replace_selection'    => __( 'Replace selection with:', 'tinymce-spellcheck' ),
		'dialog_confirm_post_publish' => __( "The proofreader has suggestions for this post. Are you sure you want to publish it?\n\nPress OK to publish your post, or Cancel to view the suggestions and edit your post.", 'tinymce-spellcheck' ),
		'dialog_confirm_post_update'  => __( "The proofreader has suggestions for this post. Are you sure you want to update it?\n\nPress OK to update your post, or Cancel to view the suggestions and edit your post.", 'tinymce-spellcheck' ),
	) );

	wp_enqueue_script( 'TSpell_l10n', plugins_url( '/js/install_atd_l10n.js', dirname( __FILE__ ) ), array( 'TSpell_settings', 'jquery' ), TSPELL_VERSION );
}

add_action( 'admin_print_scripts', 'TSpell_init_l10n_js' );
