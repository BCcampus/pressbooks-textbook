<?php

/* ------------------------------------------------------------------------ *
 * Google Webfonts
 * ------------------------------------------------------------------------ */

function fitzgerald_enqueue_styles() {
	wp_enqueue_style( 'fitzgerald-fonts', 'http://fonts.googleapis.com/css?family=Crimson+Text:400,400italic,700|Roboto+Condensed:400,300,300italic,400italic' );
}

add_action( 'wp_print_styles', 'fitzgerald_enqueue_styles' );

function pbt_enqueue_child_theme_styles() {
    wp_enqueue_style( 'pressbooks-book' );
    wp_enqueue_style( 'open-textbook' );
}

add_action( 'wp_enqueue_scripts', 'pbt_enqueue_child_theme_styles', 100 );

/**
 * Returns an html blog of meta elements 
 * 
 * @return string $html metadata
 */
function pbt_get_seo_meta_elements() {
	// map items that are already captured
	$meta_mapping = array(
	    'author' => 'pb_author',
	    'description' => 'pb_about_50',
	    'keywords' => 'pb_keywords_tags',
	    'publisher' => 'pb_publisher'
	);

	$html = "<meta name='application-name' content='PressBooks'>\n";
	$metadata = \PressBooks\Book::getBookInformation();

	// create meta elements
	foreach ( $meta_mapping as $name => $content ) {
		if ( array_key_exists( $content, $metadata ) ) {
			$html .= "<meta name='" . $name . "' content='" . $metadata[$content] . "'>\n";
		}
	}

	return $html;
}

function pbt_get_microdata_meta_elements() {
	// map items that are already captured
	$html = '';
	$micro_mapping = array(
	    'about' => 'pb_bisac_subject',
	    'alternativeHeadline' => 'pb_subtitle',
	    'author' => 'pb_author',
	    'contributor' => 'pb_contributing_authors',
	    'copyrightHolder' => 'pb_copyright_holder',
	    'copyrightYear' => 'pb_copyright_year',
	    'datePublished' => 'pb_publication_date',
	    'description' => 'pb_about_50',
	    'editor' => 'pb_editor',
	    'image' => 'pb_cover_image',
	    'inLanguage' => 'pb_language',
	    'keywords' => 'pb_keywords_tags',
	    'publisher' => 'pb_publisher',
	);
	$metadata = \PressBooks\Book::getBookInformation();

	// create microdata elements
	foreach ( $micro_mapping as $itemprop => $content ) {
		if ( array_key_exists( $content, $metadata ) ) {
			if ( 'pb_publication_date' == $content ) {
				$content = date( 'Y-m-d', $metadata[$content] );
			} else {
				$content = $metadata[$content];
			}
			$html .= "<meta itemprop='" . $itemprop . "' content='" . $content . "' id='" . $itemprop . "'>\n";
		}
	}

	// add elements that aren't captured, and don't need user input
	$lrmi_meta = array(
	    'educationalAlignment' => $metadata['pb_bisac_subject'],
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