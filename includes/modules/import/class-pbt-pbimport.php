<?php

/**
 * Uses the v1/API to search titles based on a user defined search term
 *
 * Provides an interface to turn an instance of PressBooks into a remix 'ecosystem' 
 *
 * @package PressBooks_Textbook
 * @author Brad Payne <brad@bradpayne.ca>
 * @license   GPL-2.0+
 * 
 * @copyright 2014 Brad Payne
 */

namespace PBT\Import;

require_once( ABSPATH . 'wp-admin/includes/image.php' );
require_once( ABSPATH . 'wp-admin/includes/file.php' );
require_once( ABSPATH . 'wp-admin/includes/media.php' );

class PBImport {

	/**
	 * Chapters to be imported
	 *  
	 * @var array 
	 */
	protected $chapters = array();

	/**
	 * Metadata not covered by the API
	 * 
	 * @var array 
	 */
	protected $accepted_meta = array(
	    'pb_short_title',
	    'pb_subtitle',
	);

	public function __construct() {
		
	}

	/**
	 *  Imports user selected chapters from an instance of PB 
	 * 
	 * @param array $chapters
	 * Array(
	  [5] => Array(
	    [222] => chapter
	    )
	  [14] => Array(
	    [164] => front-matter
	    )
	  )
	 * @return type
	 */
	function import( array $chapters ) {

		$this->chapters = $chapters;

		$chapters_to_import = $this->getChapters();

		libxml_use_internal_errors( true );

		foreach ( $chapters_to_import as $new_post ) {

			// Load HTMl snippet into DOMDocument using UTF-8 hack
			$utf8_hack = '<?xml version="1.0" encoding="UTF-8"?>';
			$doc = new \DOMDocument();
			$doc->loadHTML( $utf8_hack . $new_post['post_content'] );

			// Download images, change image paths
			$doc = $this->scrapeAndKneadImages( $doc );

			$html = $doc->saveXML( $doc->documentElement );

			// Remove auto-created <html> <body> and <!DOCTYPE> tags.
			$html = preg_replace( '/^<!DOCTYPE.+?>/', '', str_replace( array( '<html>', '</html>', '<body>', '</body>' ), array( '', '', '', '' ), $html ) );

			$import_post = array(
			    'post_title' => $new_post['post_title'],
			    'post_content' => $html,
			    'post_type' => $new_post['post_type'],
			    'post_status' => $new_post['post_status'],
			);

			// set post parent	
			if ( 'chapter' == $new_post['post_type'] ) {
				$post_parent = $this->getChapterParent();
				$import_post['post_parent'] = $post_parent;
			}

			// woot, woot!
			$pid = wp_insert_post( $import_post );

			// check for errors, redirect and record 
			if ( is_wp_error( $pid ) ) {
				error_log( '\PBT\Import\PBImport()->import error at `wp_insert_post()`: ' . $pid->get_error_message() );
				\PBT\Search\ApiSearch::revokeCurrentImport();
				\PressBooks\Redirect\location( get_bloginfo( 'url' ) . '/wp-admin/admin.php?page=api_search_import' );
			}

			// set post metadata
			$this->setPostMeta( $pid, $new_post );

			\PressBooks\Book::consolidatePost( $pid, get_post( $pid ) );
		}

		return \PBT\Search\ApiSearch::revokeCurrentImport();
	}

	/**
	 * 
	 * @param type $pid
	 * @param array $metadata
	 */
	protected function setPostMeta( $pid, array $metadata ) {

		if ( ! empty( $metadata['pb_section_author'] ) ) {
			update_post_meta( $pid, 'pb_section_author', $metadata['pb_section_author'] );
		}

		if ( ! empty( $metadata['pb_section_license'] ) ) {
			update_post_meta( $pid, 'pb_section_license', $metadata['pb_section_license'] );
		}

		foreach ( $this->accepted_meta as $meta_key ) {

			if ( isset( $metadata['meta'][$meta_key] ) ) {
				update_post_meta( $pid, $meta_key, $metadata['meta'][$meta_key][0] );
			}
		}

		update_post_meta( $pid, 'pb_show_title', 'on' );
		update_post_meta( $pid, 'pb_export', 'on' );
	}

	/**
	 * Parse HTML snippet, save all found <img> tags using media_handle_sideload(), return the HTML with changed <img> paths.
	 *
	 * @param \DOMDocument $doc
	 *
	 * @return \DOMDocument
	 */
	protected function scrapeAndKneadImages( \DOMDocument $doc ) {

		$images = $doc->getElementsByTagName( 'img' );

		foreach ( $images as $image ) {
			// Fetch image, change src
			$old_src = $image->getAttribute( 'src' );

			$new_src = $this->fetchAndSaveUniqueImage( $old_src );

			if ( $new_src ) {
				// Replace with new image
				$image->setAttribute( 'src', $new_src );
			} else {
				// Tag broken image
				$image->setAttribute( 'src', "{$old_src}#fixme" );
			}
		}

		return $doc;
	}

	/**
	 * Load remote url of image into WP using media_handle_sideload()
	 * Will return an empty string if something went wrong.
	 *
	 * @param string $url 
	 *
	 * @see media_handle_sideload
	 *
	 * @return string filename
	 */
	protected function fetchAndSaveUniqueImage( $url ) {

		if ( ! filter_var( $url, FILTER_VALIDATE_URL ) ) {
			return '';
		}

		$remote_img_location = $url;

		// Cheap cache
		static $already_done = array();
		if ( isset( $already_done[$remote_img_location] ) ) {
			return $already_done[$remote_img_location];
		}

		/* Process */

		// Basename without query string
		$filename = explode( '?', basename( $url ) );
		$filename = array_shift( $filename );

		$filename = sanitize_file_name( urldecode( $filename ) );

		if ( ! preg_match( '/\.(jpe?g|gif|png)$/i', $filename ) ) {
			// Unsupported image type
			$already_done[$remote_img_location] = '';
			return '';
		}

		$tmp_name = download_url( $remote_img_location );
		if ( is_wp_error( $tmp_name ) ) {
			// Download failed
			$already_done[$remote_img_location] = '';
			return '';
		}

		if ( ! \PressBooks\Image\is_valid_image( $tmp_name, $filename ) ) {

			try { // changing the file name so that extension matches the mime type
				$filename = $this->properImageExtension( $tmp_name, $filename );

				if ( ! \PressBooks\Image\is_valid_image( $tmp_name, $filename ) ) {
					throw new \Exception( 'Image is corrupt, and file extension matches the mime type' );
				}
			} catch ( \Exception $exc ) {
				// Garbage, don't import
				$already_done[$remote_img_location] = '';
				unlink( $tmp_name );
				return '';
			}
		}

		$pid = media_handle_sideload( array( 'name' => $filename, 'tmp_name' => $tmp_name ), 0 );
		$src = wp_get_attachment_url( $pid );
		if ( ! $src ) $src = ''; // Change false to empty string
		$already_done[$remote_img_location] = $src;
		@unlink( $tmp_name );

		return $src;
	}

	/**
	 * Expects the array structure: 
	  Array(
	    [103] => Array (
	      [6] => Array(
	      [type] => chapter
	      [license] => cc-by
	      [author] => Brad Payne
	      )
	    )
	  )
	 * @return array of posts to import
	 */
	protected function getChapters() {

		if ( ! is_array( $this->chapters ) ) {
			return false;
		}

		$i = 0;
		$posts_to_import = array();

		foreach ( $this->chapters as $blog_id => $chapters ) {

			switch_to_blog( $blog_id );

			foreach ( $chapters as $post_id => $info ) {

				$old_post = get_post( $post_id );
				$old_meta = get_post_meta( $post_id );

				$posts_to_import[$i]['post_title'] = $old_post->post_title;
				$posts_to_import[$i]['post_content'] = $old_post->post_content;
				$posts_to_import[$i]['post_type'] = $info['type'];
				$posts_to_import[$i]['pb_section_author'] = $info['author'];
				$posts_to_import[$i]['pb_section_license'] = $info['license'];
				$posts_to_import[$i]['post_status'] = 'draft';
				$posts_to_import[$i]['meta'] = $old_meta;

				$i ++;
			}

			restore_current_blog();
		}
		return $posts_to_import;
	}

	/**
	 * Get a valid Part id to act as post_parent to a Chapter
	 *
	 * @return int
	 */
	protected function getChapterParent() {

		$q = new \WP_Query();

		$args = array(
		    'post_type' => 'part',
		    'post_status' => 'publish',
		    'posts_per_page' => 1,
		    'orderby' => 'menu_order',
		    'order' => 'ASC',
		    'no_found_rows' => true,
		);

		$results = $q->query( $args );

		return absint( $results[0]->ID );
	}

	/**
	 * 
	 * @param type $id
	 * @return boolean
	 */
	static function flaggedForImport( $id ) {

		if ( ! is_array( $_POST['chapters'] ) ) {
			return false;
		}

		if ( ! isset( $_POST['chapters'][$id]['import'] ) ) {
			return false;
		}

		return ( 1 == $_POST['chapters'][$id]['import'] ? true : false );
	}

}
