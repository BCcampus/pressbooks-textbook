<?php

/**
 * Returns an html blog of meta elements 
 * 
 * @return string $html metadata
 */
function pbt_get_seo_meta_elements() {
	// map items that are already captured
	$meta_mapping = array(

		'citation_title' => 'pb_title',
		'citation_author' => 'pb_authors_file_as',
		'citation_language' => 'pb_language',
		'citation_keywords' => 'pb_keywords_tags',
		'citation_pdf_url' => pbt_get_citation_pdf_url(),
		'citation_publication_date' => 'pb_publication_date',

	);

	$html = "<meta name='application-name' content='Pressbooks'>\n";
	$metadata = \Pressbooks\Book::getBookInformation();

	// create meta elements
	foreach ( $meta_mapping as $name => $content ) {
		if ( array_key_exists( $content, $metadata ) ) {
			$html .= "<meta name='" . $name . "' content='" . $metadata[$content] . "'>\n";
		}
		elseif( 'citation_pdf_url' == $name ){
			$html .= "<meta name='" . $name . "' content='" . $content . "'>\n";
		}
	}

	return $html;
}

function pbt_get_citation_pdf_url() {
	$url = '';
	$domain = site_url();

	if ( method_exists( '\Pressbooks\Utility', 'latest_exports' ) ) {
		$files = \Pressbooks\Utility\latest_exports();

		$options = get_option( 'pbt_redistribute_settings' );
		if ( ! empty( $files ) && ( true == $options['latest_files_public'] ) ) {

			foreach ( $files as $filetype => $filename ) {
				if ( 'pdf' == $filetype || 'mpdf' == $filetype ) {
					$filename = preg_replace( '/(-\d{10})(.*)/ui', "$1", $filename );
					// rewrite rule
					$url = $domain . "/open/download?filename={$filename}&type={$filetype}";
				}
			}
		}
	}
	return $url;
}

function pbt_get_microdata_meta_elements() {
	// map items that are already captured
	$html = '';

	// add elements that aren't captured, and don't need user input
	$edu_align = ( isset( $metadata['pb_bisac_subject'] ) ) ? $metadata['pb_bisac_subject'] : '';
	
	$lrmi_meta = array(
	    'educationalAlignment' => $edu_align,
	    'educationalUse' => 'Open textbook study',
	    'audience' => 'student',
	    'interactivityType' => 'mixed',
	    'learningResourceType' => 'textbook',
	    'typicalAgeRange' => '17-',
	);

	foreach ( $lrmi_meta as $itemprop => $content ) {
		// @todo parse educationalAlignment items into alignmentOjects
		$html .= "<meta itemprop='" . $itemprop . "' content='" . $content . "' id='" . $itemprop . "'>\n";
	}
	return $html;
}

/**
 * Modifies 'chapters' to 'page' for text processed in __() to avoid confusion. 
 * Lightly modified function, original author Lumen Learning
 * https://github.com/lumenlearning/candela
 * 
 * 
 * @param type $translated
 * @param type $original
 * @param type $domain
 * @return type
 */
function pbt_terminology_modify( $translated, $original, $domain ) {

	if ( 'pressbooks' == $domain ) {
		$modify = array(
		    "Chapter Metadata" => "Page Metadata",
		    "Chapter Short Title (appears in the PDF running header)" => "Page Short Title (appears in the PDF running header)",
		    "Chapter Subtitle (appears in the Web/ebook/PDF output)" => "Page Subtitle (appears in the Web/ebook/PDF output)",
		    "Chapter Author (appears in Web/ebook/PDF output)" => "Page Author (appears in Web/ebook/PDF output)",
		    "Chapter Copyright License (overrides book license on this page)" => "Page Copyright License (overrides book license on this page)",
		    "Promote your book, set individual chapters privacy below." => "Promote your book, set individual page's privacy below.",
		    "Add Chapter" => "Add Page",
		    "Reordering the Chapters" => "Reordering the Pages",
		    "Chapter 1" => "Page 1",
		    "Imported %s chapters." => "Imported %s pages.",
		    "Chapters" => "Pages",
		    "Chapter" => "Page",
		    "Add New Chapter" => "Add New Page",
		    "Edit Chapter" => "Edit Page",
		    "New Chapter" => "New Page",
		    "View Chapter" => "View Page",
		    "Search Chapters" => "Search Pages",
		    "No chapters found" => "No pages found",
		    "No chapters found in Trash" => "No pages found in Trash",
		    "Chapter numbers" => "Page numbers",
		    "display chapter numbers" => "display page numbers",
		    "do not display chapter numbers" => "do not display page numbers",
		    "Chapter Numbers" => "Page Numbers",
		    "Display chapter numbers" => "Display page numbers",
		    "This is the first chapter in the main body of the text. You can change the " => "This is the first page in the main body of the text. You can change the ",
		    "text, rename the chapter, add new chapters, and add new parts." => "text, rename the page, add new pages, and add new parts.",
		    "Only users you invite can see your book, regardless of individual chapter " => "Only users you invite can see your book, regardless of individual page ",
		);

		if ( isset( $modify[$original] ) ) {
			$translated = $modify[$original];
		}
	}

	return $translated;
}

/**
 * Modifies 'chapter' to 'page' for text processed in _x()
 * Lightly modified function, original author Lumen Learning
 * https://github.com/lumenlearning/candela
 * 
 * @param type $translated
 * @param type $original
 * @param type $context
 * @param type $domain
 * @return type
 */
function pbt_terminology_modify_context( $translated, $original, $context, $domain ) {
	if ( 'pressbooks' == $domain && 'book' == $context ) {
		$translated = pbt_terminology_modify( $translated, $original, $domain );
	}
	return $translated;
}

/**** Removing these filters Jume 2015 to remain consistent with the PB documentation *****/
//add_filter( 'gettext', 'pbt_terminology_modify', 11, 3 );
//add_filter( 'gettext_with_context', 'pbt_terminology_modify_context', 11, 4 );

// removes incorrect notice on epub/pdf export that the book was created on pressbooks.com
$GLOBALS['PB_SECRET_SAUCE']['TURN_OFF_FREEBIE_NOTICES_EPUB'] = 'not_created_on_pb_com';
$GLOBALS['PB_SECRET_SAUCE']['TURN_OFF_FREEBIE_NOTICES_PDF'] = 'not_created_on_pb_com';


/**
 * 
 * @staticvar array $searches
 * @param type $content
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
 * @return type
 */
function pbt_fix_img_relative_callback($matches){
	$avoid = 'http://s.wordpress.com';
	$protocol = '';

	if ( 0 === strcmp( $avoid, substr( $matches[0],0,22 ) ) ) {
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
function pbt_add_metadata()
{
	if (is_front_page()) {
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
		echo "</small>";
	}
}

add_action( 'wp_footer', 'pbt_add_openstax' );

add_filter( 'pressbooks_download_tracking_code', function ( $tracking, $filetype ) {
	return "_paq.push(['trackEvent','exportFiles','Downloads','{$filetype}']);";
}, 10, 2 );
