<?php
/**
 * Automatically update theme files/regenerate scss compile based on theme
 * version number
 *
 * @return bool
 */
function pbt_maybe_update_webbook_stylesheet() {
	$theme           = wp_get_theme();
	$current_version = $theme->get( 'Version' );
	$last_version    = get_option( 'pbt_otb_theme_version' );
	if ( version_compare( $current_version, $last_version ) > 0 ) {
		\Pressbooks\Container::get( 'Styles' )->updateWebBookStyleSheet();
		update_option( 'pbt_otb_theme_version', $current_version );

		return true;
	}

	return false;
}

add_action( 'init', 'pbt_maybe_update_webbook_stylesheet' );

/**
 * add BCC Kaltura instance endpoint
 */
add_action(
	'init', function () {
		wp_oembed_add_provider( 'https://video.bccampus.ca/id/*', 'https://video.bccampus.ca/oembed/', false );
	}
);

/**
 * Returns an html blog of meta elements
 *
 * @return string $html metadata
 */
function pbt_get_seo_meta_elements() {
	// map items that are already captured
	$meta_mapping = [

		'citation_title'            => 'pb_title',
		'citation_author'           => 'pb_authors',
		'citation_language'         => 'pb_language',
		'citation_keywords'         => 'pb_keywords_tags',
		'citation_pdf_url'          => pbt_get_citation_pdf_url(),
		'citation_publication_date' => 'pb_publication_date',

	];

	$html     = "<meta name='application-name' content='Pressbooks'>\n";
	$metadata = \Pressbooks\Book::getBookInformation();

	// create meta elements
	foreach ( $meta_mapping as $name => $content ) {
		if ( array_key_exists( $content, $metadata ) ) {
			$html .= "<meta name='" . $name . "' content='" . $metadata[ $content ] . "'>\n";
		} elseif ( 'citation_pdf_url' === $name ) {
			$html .= "<meta name='" . $name . "' content='" . $content . "'>\n";
		}
	}

	return $html;
}

/**
 * @return string
 */
function pbt_get_citation_pdf_url() {
	$url    = '';
	$domain = site_url();

	if ( method_exists( '\Pressbooks\Utility', 'latest_exports' ) ) {
		$files = \Pressbooks\Utility\latest_exports();

		$options = get_option( 'pbt_redistribute_settings' );
		if ( ! empty( $files ) && ( true === $options['latest_files_public'] ) ) {

			foreach ( $files as $filetype => $filename ) {
				if ( 'pdf' === $filetype || 'mpdf' === $filetype ) {
					$filename = preg_replace( '/(-\d{10})(.*)/ui', '$1', $filename );
					// rewrite rule
					$url = $domain . "/open/download?filename={$filename}&type={$filetype}";
				}
			}
		}
	}

	return $url;
}

/**
 * @return string
 */
function pbt_get_microdata_meta_elements() {
	// map items that are already captured
	$html     = '';
	$metadata = '';

	// add elements that aren't captured, and don't need user input
	$edu_align = ( isset( $metadata['pb_bisac_subject'] ) ) ? $metadata['pb_bisac_subject'] : '';

	$lrmi_meta = [
		'educationalAlignment' => $edu_align,
		'educationalUse'       => 'Open textbook study',
		'audience'             => 'student',
		'interactivityType'    => 'mixed',
		'learningResourceType' => 'textbook',
		'typicalAgeRange'      => '17-',
	];

	foreach ( $lrmi_meta as $itemprop => $content ) {
		// @todo parse educationalAlignment items into alignmentOjects
		$html .= "<meta itemprop='" . $itemprop . "' content='" . $content . "' id='" . $itemprop . "'>\n";
	}

	return $html;
}

// removes incorrect notice on epub/pdf export that the book was created on pressbooks.com
$GLOBALS['PB_SECRET_SAUCE']['TURN_OFF_FREEBIE_NOTICES_EPUB'] = 'not_created_on_pb_com';
$GLOBALS['PB_SECRET_SAUCE']['TURN_OFF_FREEBIE_NOTICES_PDF']  = 'not_created_on_pb_com';


/**
 * @param $content
 *
 * @return null|string|string[]
 */
function pbt_fix_img_relative( $content ) {
	static $searches = [
		'#<(?:img) .*?src=[\'"]\Khttp://[^\'"]+#i',
	];
	$content         = preg_replace_callback( $searches, 'pbt_fix_img_relative_callback', $content );

	return $content;
}

/**
 * @param $matches
 *
 * @return string
 */
function pbt_fix_img_relative_callback( $matches ) {
	$avoid = 'http://s.wordpress.com';

	if ( 0 === strcmp( $avoid, substr( $matches[0], 0, 22 ) ) ) {
		$protocol = $matches[0];
	} else {
		$protocol = '' . substr( $matches[0], 5 );
	}

	return $protocol;
}

if ( ! empty( $_SERVER['HTTPS'] ) ) {
	add_filter( 'the_content', 'pbt_fix_img_relative', 9999 );
}

/**
 * adds metadata to head element
 */
function pbt_add_metadata() {
	if ( is_front_page() ) {
		echo pbt_get_seo_meta_elements();
		echo pbt_get_microdata_meta_elements();
	} else {
		echo pbt_get_microdata_meta_elements();
	}
}

add_action( 'wp_head', 'pbt_add_metadata' );

/**
 * adds content to the footer
 */
function pbt_add_openstax() {
	// tmp fix, rush job
	$openstax = get_bloginfo( 'url' );
	if ( 'https://opentextbc.ca/anatomyandphysiology' === $openstax ) {
		echo "<small class='aligncenter'>";
		__( 'Download for free at http://cnx.org/contents/14fb4ad7-39a1-4eee-ab6e-3ef2482e3e22@8.24', 'pressbooks-textbook' );
		echo '</small>';
	}
}

add_action( 'wp_footer', 'pbt_add_openstax' );

/**
 * using Matomo for tracking downloads
 */
add_filter(
	'pressbooks_download_tracking_code', function ( $tracking, $filetype ) {
		return "_paq.push(['trackEvent','exportFiles','Downloads','{$filetype}']);";
	}, 10, 2
);

/**
 * Converts a_string_with_underscores to
 * A String With Underscores
 *
 * @param string $string A string with underscores to be converted
 * @param string $exclude exclude first or last word from results
 *
 * @return string
 */
function pbt_explode_on_underscores( $string, $exclude = '' ) {
	$result   = '';
	$expected = [
		'first',
		'last',
	];
	// not a string, force it
	if ( ! is_string( $string ) ) {
		$string = strval( $string );
	}
	// no underscore present, return original string
	$parts = explode( '_', strtolower( $string ) );
	if ( false === $parts ) {
		return $string;
	}
	// exclude the first or the last element
	if ( in_array( $exclude, $expected, true ) ) {
		if ( 0 === strcasecmp( 'first', $exclude ) && count( $parts ) >= 2 ) {
			array_shift( $parts );
		}
		if ( 0 === strcasecmp( 'last', $exclude ) && count( $parts ) >= 2 ) {
			array_pop( $parts );
		}
	}
	foreach ( $parts as $part ) {
		$result .= ucfirst( $part ) . ' ';
	}
	// rm trailing space
	rtrim( $result, ' ' );

	return $result;
}

/**
 * Keep the "kitchen sink" open
 *
 * @param $in
 *
 * @return mixed
 */
add_filter(
	'tiny_mce_before_init', function ( $in ) {
		$in['wordpress_adv_hidden'] = false;

		return $in;
	}
);

/**
 * Insert tabs content before a single (front matter, part, chapter, back
 * matter) page footer.
 */
add_action(
	'pb_book_content_before_footer', function () {
		get_template_part( 'tabs', 'content' );
	}
);

/*
|--------------------------------------------------------------------------
| Include tab functionality
|--------------------------------------------------------------------------
|
| Tabs
|
|
*/

/**
 * enable footer tabs
 */
function pbt_enqueue_scripts() {

	// scripts only required if on a single page and user has configured theme options
	if ( is_single() && ! empty( pbt_get_web_options_tab() ) ) {
		wp_enqueue_script( 'jquery-ui-tabs' );
		wp_enqueue_script(
			'pb-tabs', get_stylesheet_directory_uri() . '/assets/js/tabs.js', [
				'jquery',
				'jquery-ui-tabs',
			], null, true
		);
		wp_enqueue_style( 'jquery-ui-css', '//code.jquery.com/ui/1.12.0/themes/base/jquery-ui.css', '', '', 'screen, print' );
		wp_enqueue_style( 'revisions', ABSPATH . '/wp-admin/css/revisions.css' );
	}
}

add_action( 'wp_enqueue_scripts', 'pbt_enqueue_scripts' );

/**
 * @param $post
 *
 * @return string
 */
function pbt_tab_revision_history( $post ) {
	$html    = '';
	$args    = [
		'order'         => 'DESC',
		'orderby'       => 'date ID',
		'check_enabled' => false,
	];
	$enabled = wp_revisions_enabled( $post );
	$limit   = 3;
	$i       = 0;
	if ( false === $enabled ) {
		$html .= '<p>' . __( 'Revisions are not enabled', 'pressbooks' ) . '</p>';

		// these are not the revisions you're looking for
		return $html;
	}

	// wp_get_post_revisions returns an empty array if there are no revisions
	$revisions = wp_get_post_revisions( $post->ID, $args );

	// could be empty
	if ( empty( $revisions && true === $enabled ) ) {
		$html .= '<p>' . __( 'There are currently no revisions', 'pressbooks' ) . '</p>';

		return $html;
	}

	foreach ( $revisions as $revision ) {

		// skip autosave revisions
		if ( true === wp_is_post_autosave( $revision->ID ) ) {
			continue;
		}
		// save revision id
		$ids[] = $revision->ID;

		// special if it's the first loop
		if ( 0 === $i ) {
			$prev = 0;
			$new  = $post->post_content;
		} else {
			$prev = $i - 1;
			$new  = $revision->post_content;
		}

		// get previous revision
		$old_rev = wp_get_post_revision( $ids[ $prev ] );

		$diff = wp_text_diff( $new, $old_rev->post_content );

		if ( ! empty( $diff ) ) {
			$human_readable_date = date( 'M j, Y', strtotime( $revision->post_date_gmt ) );
			$html               .= "<b>{$human_readable_date}</b>{$diff}";
		}

		$i ++;
		if ( $limit === $i ) {
			break;
		}
	}

	return $html;
}

/**
 * Displays some book information
 *
 * @return string
 */
function pbt_tab_book_info() {
	$html      = '';
	$book_meta = \Pressbooks\Book::getBookInformation();
	$expected  = [
		'pb_title',
		'pb_authors',
		'pb_contributors',
		'pb_editors',
		'pb_short_title',
		'pb_subtitle',
		'pb_publisher',
		'pb_publisher_city',
		'pb_copyright_year',
		'pb_copyright_holder',
		'pb_book_licence',
		'pb_keywords_tags',
		'pb_bisac_subject',
	];
	$html     .= '<dl class="dl-horizontal">';
	foreach ( $book_meta as $key => $val ) {
		// skip stuff we don't want
		if ( ! in_array( $key, $expected, true ) ) {
			continue;
		}
		$title = pbt_explode_on_underscores( $key, 'first' );
		$html .= "<dt>{$title}</dt>";
		$html .= "<dd>{$val}</dd>";
	}
	$html .= '</dl>';

	return $html;
}

/**
 *
 * @return string
 */
function pbt_tab_attributions() {
	global $post;
	$html = '';

	if ( class_exists( 'Candela\Citation' ) ) {
		$citation = \Candela\Citation::renderCitation( $post->ID );
		if ( $citation ) {
			$html .= '<section role="contentinfo"><div class="post-citations">' . $citation . '</div></section>';
		}
	}

	return $html;
}

/*
|--------------------------------------------------------------------------
| Tab Settings
|--------------------------------------------------------------------------
|
| Displays in Theme Options -> Web
|
|
*/
/**
 * Add our field to settings section
 *
 * @param $_page
 */
function pbt_theme_options_web_add_settings_fields( $_page ) {

	add_settings_field(
		'tabbed_content',
		__( 'Tabbed Content', 'open-textbooks' ),
		'pbt_tabbed_content_callback',
		$_page,
		'web_options_section'
	);

}

add_action( 'pb_theme_options_web_add_settings_fields', 'pbt_theme_options_web_add_settings_fields' );

/**
 * Displays tabbed content options in web options
 */
function pbt_tabbed_content_callback() {
	$options = get_option( 'pressbooks_theme_options_web' );

	// add default if not set
	if ( ! isset( $options['tab_revision_history'] ) ) {
		$options['tab_revision_history'] = 0;
	}
	if ( ! isset( $options['tab_book_info'] ) ) {
		$options['tab_book_info'] = 0;
	}
	if ( ! isset( $options['tab_attributions'] ) || ! class_exists( 'Candela\Citation' ) ) {
		$options['tab_attributions'] = 0;
	}

	// revision history
	$html  = '<input type="checkbox" id="tab_revision_history" name="pressbooks_theme_options_web[tab_revision_history]" value="1" ' . checked( 1, $options['tab_revision_history'], false ) . '/>';
	$html .= '<label for="tab_revision_history"> ' . __( 'Display revision history for each chapter with everyone.', 'opentextbooks' ) . '</label><br/>';

	// book info
	$html .= '<input type="checkbox" id="tab_book_info" name="pressbooks_theme_options_web[tab_book_info]" value="1"  ' . checked( 1, $options['tab_book_info'], false ) . '/>';
	$html .= '<label for="tab_book_info"> ' . __( 'Display book information for each chapter with everyone.', 'opentextbooks' ) . '</label><br/>';

	// tab citations
	if ( class_exists( 'Candela\Citation' ) ) {
		$html .= '<input type="checkbox" id="tab_attributions" name="pressbooks_theme_options_web[tab_attributions]" value="1"  ' . checked( 1, $options['tab_attributions'], false ) . '/>';
		$html .= '<label for="tab_attributions"> ' . __( 'Display page attributions and licenses.', 'opentextbooks' ) . '</label>';
	}

	echo $html;
}

/**
 * Add our defaults to pb hook
 *
 * @param array $args
 *
 * @return mixed
 */
function pbt_web_defaults( $args ) {

	$args['tab_revision_history'] = 1;
	$args['tab_book_info']        = 1;
	$args['tab_attributions']     = 1;

	return $args;
}

add_filter( 'pb_theme_options_web_defaults', 'pbt_web_defaults' );

/**
 * Add our boolean options to pb hook
 *
 * @param $args
 *
 * @return mixed
 */
function pbt_boolean_options( $args ) {
	array_push( $args, 'tab_revision_history', 'tab_book_info', 'tab_attributions' );

	return $args;
}

add_filter( 'pb_theme_options_web_booleans', 'pbt_boolean_options' );

/*
|--------------------------------------------------------------------------
| Utility
|--------------------------------------------------------------------------
|
|
|
|
*/
/**
 * Return an array of web theme options related to tabbed content
 *
 * @return array
 */
function pbt_get_web_options_tab() {
	$options         = get_option( 'pressbooks_theme_options_web' );
	$web_option_keys = array_keys( $options );
	$prefix          = 'tab_';
	$length          = strlen( $prefix );
	$tabs            = [];

	// compare first four characters and check tab option is true
	foreach ( $web_option_keys as $key ) {
		if ( strncmp( $prefix, $key, $length ) === 0 && $options[ $key ] === 1 ) {
			$tabs[ $key ] = $options[ $key ];

		}
	}

	return $tabs;
}
