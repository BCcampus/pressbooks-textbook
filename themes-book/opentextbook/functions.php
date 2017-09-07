<?php
/*
|--------------------------------------------------------------------------
| Include tab functionality
|--------------------------------------------------------------------------
|
| Tabs
|
|
*/
require get_stylesheet_directory() . '/inc/tab-functions.php';

/**
 * Returns an html blog of meta elements
 *
 * @return string $html metadata
 */
function pbt_get_seo_meta_elements() {
	// map items that are already captured
	$meta_mapping = array(

		'citation_title'            => 'pb_title',
		'citation_author'           => 'pb_authors_file_as',
		'citation_language'         => 'pb_language',
		'citation_keywords'         => 'pb_keywords_tags',
		'citation_pdf_url'          => pbt_get_citation_pdf_url(),
		'citation_publication_date' => 'pb_publication_date',

	);

	$html     = "<meta name='application-name' content='Pressbooks'>\n";
	$metadata = \Pressbooks\Book::getBookInformation();

	// create meta elements
	foreach ( $meta_mapping as $name => $content ) {
		if ( array_key_exists( $content, $metadata ) ) {
			$html .= "<meta name='" . $name . "' content='" . $metadata[ $content ] . "'>\n";
		} elseif ( 'citation_pdf_url' == $name ) {
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
		if ( ! empty( $files ) && ( true == $options['latest_files_public'] ) ) {

			foreach ( $files as $filetype => $filename ) {
				if ( 'pdf' == $filetype || 'mpdf' == $filetype ) {
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
	$html = $metadata = '';

	// add elements that aren't captured, and don't need user input
	$edu_align = ( isset( $metadata['pb_bisac_subject'] ) ) ? $metadata['pb_bisac_subject'] : '';

	$lrmi_meta = array(
		'educationalAlignment' => $edu_align,
		'educationalUse'       => 'Open textbook study',
		'audience'             => 'student',
		'interactivityType'    => 'mixed',
		'learningResourceType' => 'textbook',
		'typicalAgeRange'      => '17-',
	);

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
 *
 * @staticvar array $searches
 *
 * @param type $content
 *
 * @return type
 */
function pbt_fix_img_relative( $content ) {
	static $searches = array(
		'#<(?:img) .*?src=[\'"]\Khttp://[^\'"]+#i', // fix image and iframe elements
	);
	$content = preg_replace_callback( $searches, 'pbt_fix_img_relative_callback', $content );

	return $content;
}

/**
 *
 * @param type $matches
 *
 * @return type
 */
function pbt_fix_img_relative_callback( $matches ) {
	$avoid    = 'http://s.wordpress.com';

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
	if ( 'https://opentextbc.ca/anatomyandphysiology' == $openstax ) {
		echo "<small class='aligncenter'>";
		__( 'Download for free at http://cnx.org/contents/14fb4ad7-39a1-4eee-ab6e-3ef2482e3e22@8.24', 'pressbooks-textbook' );
		echo '</small>';
	}
}

add_action( 'wp_footer', 'pbt_add_openstax' );

add_filter( 'pressbooks_download_tracking_code', function ( $tracking, $filetype ) {
	return "_paq.push(['trackEvent','exportFiles','Downloads','{$filetype}']);";
}, 10, 2 );

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
	$expected = array(
		'first',
		'last',
	);
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
	if ( in_array( $exclude, $expected ) ) {
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
