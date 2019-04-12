<?php
/**
 * Uses the v1/API to search titles based on a user defined search term
 *
 * Provides an interface to turn an instance of Pressbooks into a remix
 * 'ecosystem'
 *
 * @package Pressbooks_Textbook
 * @author Brad Payne
 * @license   GPL-2.0+
 *
 * @copyright Brad Payne
 */

namespace PBT\Modules\Import;

use PBT\Modules;
use Pressbooks\Book;
use Pressbooks\Redirect;

class PBImport {

	/**
	 * Chapters to be imported
	 *
	 * @var array
	 */
	protected $chapters = [];

	/**
	 * Metadata not covered by the API
	 *
	 * @var array
	 */
	protected $accepted_meta = [
		'pb_short_title',
		'pb_subtitle',
	];

	public function __construct() {
		if ( ! function_exists( 'media_handle_sideload' ) ) {
			require_once( ABSPATH . 'wp-admin/includes/image.php' );
			require_once( ABSPATH . 'wp-admin/includes/file.php' );
			require_once( ABSPATH . 'wp-admin/includes/media.php' );
		}
	}

	/**
	 * Imports user selected chapters from an instance of PB
	 *
	 * @param array $chapters
	 *
	 * @return bool
	 */
	function import( array $chapters ) {

		$this->chapters = $chapters;

		$chapters_to_import = $this->getChapters();

		libxml_use_internal_errors( true );

		foreach ( $chapters_to_import as $new_post ) {

			// Load HTMl snippet into DOMDocument using UTF-8 hack
			$utf8_hack = '<?xml version="1.0" encoding="UTF-8"?>';
			$doc       = new \DOMDocument();
			$doc->loadHTML( $utf8_hack . $new_post['post_content'] );

			// Download images, change image paths
			$doc = $this->scrapeAndKneadImages( $doc );

			$html = $doc->saveXML( $doc->documentElement );

			// Remove auto-created <html> <body> and <!DOCTYPE> tags.
			$html = preg_replace(
				'/^<!DOCTYPE.+?>/', '', str_replace(
					[
						'<html>',
						'</html>',
						'<body>',
						'</body>',
					], [ '', '', '', '' ], $html
				)
			);

			$import_post = [
				'post_title'   => $new_post['post_title'],
				'post_content' => $html,
				'post_type'    => $new_post['post_type'],
				'post_status'  => $new_post['post_status'],
			];

			// set post parent
			if ( 'chapter' === $new_post['post_type'] ) {
				$post_parent                = $this->getChapterParent();
				$import_post['post_parent'] = $post_parent;
			}

			// woot, woot!
			$pid = wp_insert_post( $import_post );

			// check for errors, redirect and record
			if ( is_wp_error( $pid ) ) {
				error_log( '\PBT\Modules\Import\PBImport()->import error at `wp_insert_post()`: ' . $pid->get_error_message() ); //@codingStandardsIgnoreLine
				Modules\Search\ApiSearch::revokeCurrentImport();
				Redirect\location( get_bloginfo( 'url' ) . '/wp-admin/admin.php?page=api_search_import' );
			}

			// set post metadata
			$this->setPostMeta( $pid, $new_post );

			Book::consolidatePost( $pid, get_post( $pid ) );
		}

		return Modules\Search\ApiSearch::revokeCurrentImport();
	}

	/**
	 *
	 * @param type $pid
	 * @param array $metadata
	 */
	protected function setPostMeta( $pid, array $metadata ) {

		if ( ! empty( $metadata['pb_authors'] ) ) {
			update_post_meta( $pid, 'pb_authors', $metadata['pb_authors'] );
		}

		if ( ! empty( $metadata['pb_section_license'] ) ) {
			update_post_meta( $pid, 'pb_section_license', $metadata['pb_section_license'] );
		}

		foreach ( $this->accepted_meta as $meta_key ) {

			if ( isset( $metadata['meta'][ $meta_key ] ) ) {
				update_post_meta( $pid, $meta_key, $metadata['meta'][ $meta_key ][0] );
			}
		}

		update_post_meta( $pid, 'pb_show_title', 'on' );
		update_post_meta( $pid, 'pb_export', 'on' );
	}

	/**
	 * Parse HTML snippet, save all found <img> tags using
	 * media_handle_sideload(), return the HTML with changed <img> paths.
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
		static $already_done = [];
		if ( isset( $already_done[ $remote_img_location ] ) ) {
			return $already_done[ $remote_img_location ];
		}

		/* Process */

		// Basename without query string
		$filename = explode( '?', basename( $url ) );
		$filename = array_shift( $filename );

		$filename = sanitize_file_name( urldecode( $filename ) );

		if ( ! preg_match( '/\.(jpe?g|gif|png)$/i', $filename ) ) {
			// Unsupported image type
			$already_done[ $remote_img_location ] = '';

			return '';
		}

		$tmp_name = download_url( $remote_img_location );
		if ( is_wp_error( $tmp_name ) ) {
			// Download failed
			$already_done[ $remote_img_location ] = '';

			return '';
		}

		if ( ! \Pressbooks\Image\is_valid_image( $tmp_name, $filename ) ) {

			try { // changing the file name so that extension matches the mime type
				$filename = $this->properImageExtension( $tmp_name, $filename );

				if ( ! \Pressbooks\Image\is_valid_image( $tmp_name, $filename ) ) {
					throw new \Exception( 'Image is corrupt, and file extension matches the mime type' );
				}
			} catch ( \Exception $exc ) {
				// Garbage, don't import
				$already_done[ $remote_img_location ] = '';
				unlink( $tmp_name );

				return '';
			}
		}

		$pid = media_handle_sideload(
			[
				'name'     => $filename,
				'tmp_name' => $tmp_name,
			], 0
		);
		$src = wp_get_attachment_url( $pid );
		if ( ! $src ) {
			$src = ''; // Change false to empty string
		}
		$already_done[ $remote_img_location ] = $src;
		unlink( $tmp_name );

		return $src;
	}

	/**
	 * Expects the array structure:
	 * Array(
	 * [103] => Array (
	 * [6] => Array(
	 * [type] => chapter
	 * [license] => cc-by
	 * [author] => Brad Payne
	 * )
	 * )
	 * )
	 *
	 * @return array of posts to import
	 */
	protected function getChapters() {

		if ( ! is_array( $this->chapters ) ) {
			return false;
		}

		$i               = 0;
		$posts_to_import = [];

		foreach ( $this->chapters as $blog_id => $chapters ) {

			switch_to_blog( $blog_id );

			foreach ( $chapters as $post_id => $info ) {

				$old_post = get_post( $post_id );
				$old_meta = get_post_meta( $post_id );

				$posts_to_import[ $i ]['post_title']         = $old_post->post_title;
				$posts_to_import[ $i ]['post_content']       = $old_post->post_content;
				$posts_to_import[ $i ]['post_type']          = $info['type'];
				$posts_to_import[ $i ]['pb_authors']         = $info['author'];
				$posts_to_import[ $i ]['pb_section_license'] = $info['license'];
				$posts_to_import[ $i ]['post_status']        = 'draft';
				$posts_to_import[ $i ]['meta']               = $old_meta;

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

		$args = [
			'post_type'      => 'part',
			'post_status'    => 'publish',
			'posts_per_page' => 1,
			'orderby'        => 'menu_order',
			'order'          => 'ASC',
			'no_found_rows'  => true,
		];

		$results = $q->query( $args );

		return absint( $results[0]->ID );
	}

	/**
	 * @param $id
	 *
	 * @return bool
	 */
	static function flaggedForImport( $id ) {
		// phpcs:disable WordPress.CSRF.NonceVerification.NoNonceVerification

		if ( ! is_array( $_POST['chapters'] ) ) {
			return false;
		}

		if ( ! isset( $_POST['chapters'][ $id ]['import'] ) ) {
			return false;
		}

		// phpcs:enable WordPress.CSRF.NonceVerification.NoNonceVerification

		return ( 1 == $_POST['chapters'][ $id ]['import'] ? true : false ); //@codingStandardsIgnoreLine
	}

}
