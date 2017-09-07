<?php
/**
 * Date: 2017-09-06
 * Licensed under MIT
 *
 * @author Lumen Learning
 * @package CandelaCitation
 * @copyright (c) Lumen Learning
 */

namespace Candela;

class Citation {

	/**
	 * Takes care of registering our hooks and setting constants.
	 */
	public static function init() {

		self::update_db();
		add_action( 'admin_menu', array( __CLASS__, 'admin_menu' ) );
		add_action( 'add_meta_boxes', array( __CLASS__, 'add_meta_boxes' ) );
		add_action( 'save_post', array( __CLASS__, 'save' ) );
		add_filter( 'pb_import_metakeys', array( __CLASS__, 'get_import_metakeys' ) );
		add_filter( 'pb_append_front_matter_content', array( __CLASS__, 'add_citations' ), 10, 2 );
		add_filter( 'pb_append_chapter_content', array( __CLASS__, 'add_citations' ), 10, 2 );
		add_filter( 'pb_append_back_matter_content', array( __CLASS__, 'add_citations' ), 10, 2 );
	}

	/**
	 *
	 */
	public static function update_db() {
		$version = get_option( CANDELA_CITATION_DB_OPTION, '' );

		if ( empty( $version ) ) {
			update_option( CANDELA_CITATION_DB_OPTION, CANDELA_CITATION_DB_VERSION );
			self::update_to_json_encode();
		}
	}

	/**
	 * Previously citations were stored as serialized values.
	 */
	public static function update_to_json_encode() {
		// Get all post citation data and then update from serialize to json_encode
		// This avoids several issues with serialize when certain meta characters
		// are in the value.
		$types = self::postTypes();

		foreach ( $types as $type ) {
			$posts = get_posts( array( 'post_type' => $type, 'post_status' => 'any', 'posts_per_page' => '-1' ) );
			foreach ( $posts as $post ) {
				$citations = get_post_meta( $post->ID, CANDELA_CITATION_FIELD, true );
				$citations = unserialize( $citations );
				update_post_meta( $post->ID, CANDELA_CITATION_FIELD, json_encode( $citations ) );
			}
		}
	}

	/**
	 * Return post content w/ appended citations content
	 *
	 * @param $content
	 * @param $post_id
	 *
	 * @return string
	 */
	public static function add_citations( $content, $post_id ) {

		return $content .= self::renderCitation( $post_id );

	}

	/**
	 * Return an array of post types to add citations to.
	 */
	public static function postTypes() {
		return array(
			'back-matter',
			'chapter',
			'front-matter',
		);
	}

	/**
	 * Attach custom meta fields.
	 *
	 * @see http://codex.wordpress.org/Function_Reference/add_meta_box
	 */
	public static function add_meta_boxes() {
		$types = self::postTypes();
		foreach ( $types as $type ) {
			add_meta_box( 'citations', 'Attributions', array( __CLASS__, 'add_citation_meta' ), $type, 'normal' );
		}
	}

	/**
	 * @param $post
	 * @param $metabox
	 */
	public static function add_citation_meta( $post, $metabox ) {
		$citations = self::get_citations( $post->ID );

		$rows = array();
		foreach ( $citations as $citation ) {
			$rows[] = self::get_meta_row( $citation );
		}

		$rows[] = self::get_meta_row();
		self::citations_table( $rows );
	}

	public static function get_citations( $post_id ) {
		$citations = array();
		$meta      = get_post_meta( $post_id, CANDELA_CITATION_FIELD, true );

		if ( ! empty( $meta ) ) {
			$citations = json_decode( stripslashes( $meta ), true );
			if ( ! is_array( $citations ) || empty( $citations ) ) {
				$citations = array();
			}
		}

		return $citations;
	}

	/**
	 * @param $post_id
	 *
	 * @return string
	 */
	public static function renderCitation( $post_id ) {
		$citations = self::get_citations( $post_id );

		$grouped = array();
		$fields  = self::citation_fields();
		$license = self::getOptions( 'license' );

		foreach ( $citations as $citation ) {
			$parts = array();

			foreach ( $fields as $field => $info ) {

				if ( ! empty( $citation[ $field ] ) && $field != 'type' ) {
					switch ( $field ) {
						case 'license':
							if ( ! empty( $license[ $citation[ $field ] ]['link'] ) ) {
								$parts[] = $info['prefix'] . '<a rel="license" href="' . $license[ $citation[ $field ] ]['link'] . '">' . esc_html( $license[ $citation[ $field ] ]['label'] ) . '</a>' . $info['suffix'];
							} else {
								$parts[] = $info['prefix'] . esc_html( $license[ $citation[ $field ] ]['label'] ) . $info['suffix'];
							}
							break;
						case 'url':
							if ( ! empty( $citation[ $field ] ) ) {
								$parts[] = $info['prefix'] . '<a href="' . esc_url( $citation[ $field ] ) . '">' . esc_url( $citation[ $field ] ) . '</a>' . $info['suffix'];
							} else {
								$parts[] = $info['prefix'] . esc_url( $citation[ $field ] ) . $info['suffix'];
							}
							break;
						default:
							$parts[] = $info['prefix'] . esc_html( $citation[ $field ] ) . $info['suffix'];
							break;
					}
				}
			}
			$grouped[ $citation['type'] ][] = implode( CANDELA_CITATION_SEPARATOR, $parts );
		}

		$output = '';
		if ( ! empty( $grouped ) ) {
			$output .= '<div class="licensing">';
			$types  = self::getOptions( 'type' );
			foreach ( $types as $type => $info ) {
				if ( ! empty( $grouped[ $type ] ) ) {
					$output .= '<div class="license-attribution-dropdown-subheading">' . $info['label'] . '</div>';
					$output .= '<ul class="citation-list"><li>';
					$output .= implode( '</li><li>', $grouped[ $type ] );
					$output .= '</li></ul>';
				}
			}
			$output .= '</div>';
		}

		return $output;
	}

	/**
	 * @return array
	 */
	public static function citation_fields() {
		return array(
			'type'          => array(
				'type'   => 'select',
				'label'  => __( 'Type', 'candela-citation' ),
				'prefix' => '',
				'suffix' => '',
			),
			'description'   => array(
				'type'   => 'text',
				'label'  => __( 'Description', 'candela-citation' ),
				'prefix' => '',
				'suffix' => '',
			),
			'author'        => array(
				'type'   => 'text',
				'label'  => __( 'Author', 'candela-citation' ),
				'prefix' => '<strong>' . __( 'Authored by', 'candela-citation' ) . '</strong>: ',
				'suffix' => '',
			),
			'organization'  => array(
				'type'   => 'text',
				'label'  => __( 'Organization', 'candela-citation' ),
				'prefix' => '<strong>' . __( 'Provided by', 'candela-citation' ) . '</strong>: ',
				'suffix' => '',
			),
			'url'           => array(
				'type'   => 'text',
				'label'  => __( 'URL', 'candela-citation' ),
				'prefix' => '<strong>' . __( 'Located at', 'candela-citation' ) . '</strong>: ',
				'suffix' => '',
			),
			'project'       => array(
				'type'   => 'text',
				'label'  => __( 'Project', 'candela-citation' ),
				'prefix' => '<strong>' . __( 'Project', 'candela-citation' ) . '</strong>: ',
				'suffix' => '',
			),
			'license'       => array(
				'type'   => 'select',
				'label'  => __( 'Licensing', 'candela-citation' ),
				'prefix' => '<strong>' . __( 'License', 'candela-citation' ) . '</strong>: <em>',
				'suffix' => '</em>',
			),
			'license_terms' => array(
				'type'   => 'text',
				'label'  => __( 'License terms', 'candela-citation' ),
				'prefix' => '<strong>' . __( 'License Terms', 'candela-citation' ) . '</strong>: ',
				'suffix' => '',
			),
		);
	}

	/**
	 * @param array $citation
	 *
	 * @return array
	 */
	public static function get_meta_row( $citation = array() ) {

		$fields = self::citation_fields();
		if ( empty( $citation ) ) {
			$citation = array_fill_keys( array_keys( $fields ), '' );
		}

		foreach ( $fields as $key => $widget ) {
			switch ( $widget['type'] ) {
				case 'select':
					if ( is_array( $citation[ $key ] ) ) {
						$fields[ $key ]['options'] = self::GetOptions( $key, $citation[ $key ] );
					} else {
						$fields[ $key ]['options'] = self::GetOptions( $key, array( $citation[ $key ] ) );
					}
					break;
				default:
					$fields[ $key ]['value'] = empty( $citation[ $key ] ) ? '' : $citation[ $key ];
					break;
			}
		}

		$row = array();
		foreach ( $fields as $key => $widget ) {
			$id = 'citation-' . esc_attr( $key ) . '[%%INDEX%%]';
			switch ( $widget['type'] ) {
				case 'select':
					$markup = '<select name="' . $id . '" id="' . $id . '">';
					foreach ( $widget['options'] as $value => $option ) {
						$markup .= '<option value="' . esc_attr( $value ) . '" ' . ( $option['selected'] ? 'selected' : '' ) . '>' . esc_html( $option['label'] ) . '</option>';
					}
					$markup      .= '</select>';
					$row[ $key ] = array(
						'widget'     => $markup,
						'label'      => $widget['label'],
						'label-html' => '<label for="citation-' . esc_attr( $key ) . '[%%INDEX%%]">' . $widget['label'] . '</label>',
					);
					break;
				default:
					$regex       = '[^&quot;&#x5C;&#x5C;]+';
					$row[ $key ] = array(
						'widget'     => '<input name="' . $id . '" id="' . $id . '" type="' . $widget['type'] . '" value="' . esc_attr( $widget['value'] ) . '"pattern="' . $regex . '" title="Sorry, no quotes or backslashes.">',
						'label'      => $widget['label'],
						'label-html' => '<label for="citation-' . esc_attr( $key ) . '[%%INDEX%%]">' . $widget['label'] . '</label>',
					);
					break;
			}
		}

		return $row;
	}

	/**
	 * @param $field
	 * @param array $selected
	 *
	 * @return array
	 */
	public static function GetOptions( $field, $selected = array() ) {
		switch ( $field ) {
			case 'type':
				// Note that the order here determines order on output. See renderCitation
				$options = array(
					''                  => array(
						'label' => __( 'Choose citation type', 'candela-citation' ),
					),
					'original'          => array(
						'label' => __( 'CC licensed content, Original', 'candela-citation' ),
					),
					'cc'                => array(
						'label' => __( 'CC licensed content, Shared previously', 'candela-citation' ),
					),
					'cc-attribution'    => array(
						'label' => __( 'CC licensed content, Specific attribution', 'candela-citation' ),
					),
					'copyrighted_video' => array(
						'label' => __( 'All rights reserved content', 'candela-citation' ),
					),
					'pd'                => array(
						'label' => __( 'Public domain content', 'candela-citation' ),
					),
					'lumen'             => array(
						'label' => __( 'Lumen Learning authored content', 'candela-citation' ),
					),
				);
				break;
			case 'license':
				$options = array(
					'pd'          => array(
						'label' => __( 'Public Domain: No Known Copyright', 'candela-citation' ),
						'link'  => 'https://creativecommons.org/about/pdm',
					),
					'cc0'         => array(
						'label' => __( 'CC0: No Rights Reserved', 'candela-citation' ),
						'link'  => 'https://creativecommons.org/about/cc0',
					),
					'cc-by'       => array(
						'label' => __( 'CC BY: Attribution', 'candela-citation' ),
						'link'  => 'https://creativecommons.org/licenses/by/4.0/',
					),
					'cc-by-sa'    => array(
						'label' => __( 'CC BY-SA: Attribution-ShareAlike', 'candela-citation' ),
						'link'  => 'https://creativecommons.org/licenses/by-sa/4.0/',
					),
					'cc-by-nd'    => array(
						'label' => __( 'CC BY-ND: Attribution-NoDerivatives', 'candela-citation' ),
						'link'  => 'https://creativecommons.org/licenses/by-nd/4.0/',
					),
					'cc-by-nc'    => array(
						'label' => __( 'CC BY-NC: Attribution-NonCommercial', 'candela-citation' ),
						'link'  => 'https://creativecommons.org/licenses/by-nc/4.0/',
					),
					'cc-by-nc-sa' => array(
						'label' => __( 'CC BY-NC-SA: Attribution-NonCommercial-ShareAlike', 'candela-citation' ),
						'link'  => 'https://creativecommons.org/licenses/by-nc-sa/4.0/',
					),
					'cc-by-nc-nd' => array(
						'label' => __( 'CC BY-NC-ND: Attribution-NonCommercial-NoDerivatives ', 'candela-citation' ),
						'link'  => 'https://creativecommons.org/licenses/by-nc-nd/4.0/',
					),
					'arr'         => array(
						'label' => __( 'All Rights Reserved', 'candela-citation' ),
					),
					'other'       => array(
						'label' => __( 'Other', 'candela-citation' ),
					),
				);
				break;
		}

		foreach ( $options as $option => $label ) {
			$options[ $option ]['selected'] = ( in_array( $option, $selected ) ? true : false );
		}

		return $options;
	}

	/**
	 * Add Candela Citation to to-import meta
	 *
	 * @param $fields
	 *
	 * @return array
	 */
	public static function get_import_metakeys( $fields ) {
		$fields[] = CANDELA_CITATION_FIELD;

		return $fields;
	}

	/**
	 * Save a post submitted via form.
	 *
	 * @param $post_id
	 *
	 * @return mixed
	 */
	public static function save( $post_id ) {
		error_log( var_export( $_POST, 1 ) );
		if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) {
			return $post_id;
		}

		if ( ! current_user_can( 'edit_page', $post_id ) ) {
			return $post_id;
		}

		$types = self::postTypes();

		if ( isset( $_POST['post_type'] ) && in_array( $_POST['post_type'], $types ) ) {
			$citations = self::process_citations();
			update_post_meta( $post_id, CANDELA_CITATION_FIELD, json_encode( $citations ) );
		}

	}

	/**
	 * @return array
	 */
	public static function process_citations() {
		$citations = array();
		$fields    = self::citation_fields();

		// Use the first citation field to determine if citation fields were submitted.
		$key = key( $fields );

		$post_field = 'citation-' . $key;
		if ( isset( $_POST[ $post_field ] ) ) {
			// We have field data for citations, iterate over
			foreach ( $_POST[ $post_field ] as $index => $junk ) {
				foreach ( $fields as $field => $info ) {
					// Re-associate fields per citation
					$citations[ $index ][ $field ] = $_POST[ 'citation-' . $field ][ $index ];
				}
				// Citation type is required
				if ( empty( $citations[ $index ]['type'] ) ) {
					unset( $citations[ $index ] );
				}
			}
		}

		return $citations;
	}

	/**
	 *
	 */
	public static function admin_menu() {
		add_options_page(
			__( 'Candela Attributions', 'candela-citation' ),
			__( 'Candela Attributions', 'candela-citation' ),
			'manage_options',
			'candela-citation',
			array( __CLASS__, 'global_citation_page' )
		);
	}

	/**
	 *
	 */
	public static function global_citation_page() {

		// Process incoming form and preload previous citations.
		$rows = array();
		if ( ! empty( $_POST['__citation'] ) ) {
			self::process_global_form();

			if ( ! empty( $citations ) ) {
				foreach ( $citations as $citation ) {
					$rows[] = self::get_meta_row( $citation );
				}
			}
		}

		print '<div class="wrap">';
		print '<div>' . __( 'Global Attributions', 'candela-citation' ) . '</div>';
		print '<form method="POST" action="' . get_permalink() . '">';
		print '<input type="hidden" name="__citation" value="1" >';

		$rows[] = self::get_meta_row();
		self::citations_table( $rows );

		print '<input type="submit" id="citation-add-all" name="citation-add-all" value="' . __( 'Add attributions to every page', 'candela-citation' ) . '">';
		print '<input type="submit" id="citation-replace-all" name="citation-replace-all" value="' . __( 'OVERWRITE attributions on every page', 'candela-citation' ) . '">';

		print "<script type=\"text/javascript\">
      jQuery( document ).ready( function( $ ) {
        $('#citation-add-all').click(function() {
          if (!confirm(\"Are you sure you want to add attributions to *every* page in this book?\")) {
            return false;
          }
        });
      });
    </script>";

		print "<script type=\"text/javascript\">
      jQuery( document ).ready( function( $ ) {
        $('#citation-replace-all').click(function() {
          if (!confirm(\"Are you sure you want to replace all attributions in *every* page in this book?\")) {
            return false;
          }
        });
      });
    </script>";

		print '</form>';
		print '</div>';

		// Show citations for every book
		$structure = pb_get_book_structure();
		if ( ! empty( $structure['__order'] ) ) {
			$grouped = array();
			$headers = array( 'title' => __( 'Post', 'candela-citation' ) );
			foreach ( $structure['__order'] as $id => $info ) {
				$post = get_post( $id );

				$citations = self::get_citations( $id );
				$fields    = self::citation_fields();

				foreach ( $citations as $citation ) {
					$parts          = array();
					$parts['title'] = '<a href="' . get_permalink( $post->ID ) . '">' . $post->post_title . '</a>';
					foreach ( $fields as $field => $info ) {
						if ( empty( $headers[ $field ] ) ) {
							$headers[ $field ] = $info['label'];
						}
						if ( ! empty( $citation[ $field ] ) ) {
							$parts[ $field ] = esc_html( $citation[ $field ] );
						}
					}
					$grouped[ $id ][ $citation['type'] ][] = $parts;
				}
			}

			if ( ! empty( $grouped ) ) {
				print '<div class="wrap"><table>';
				print '<thead><tr>';

				$order = array(
					'title',
					'license',
					'license_terms',
					'author',
					'organization',
					'url',
					'project',
					'type',
					'description',
				);

				$new_header_order = array();
				foreach ( $order as $index ) {
					array_push( $new_header_order, $headers[ $index ] );
				}

				foreach ( $new_header_order as $title ) {
					print '<th>' . $title . '</th>';
				}
				print '</tr></thead>';

				print '<tbody>';
				foreach ( $grouped as $id => $citations ) {
					foreach ( $citations as $type => $parts ) {
						foreach ( $parts as $row ) {
							print '<tr>';
							foreach ( $order as $field ) {
								if ( ! empty( $row[ $field ] ) ) {
									switch ( $field ) {
										case 'url':
											print '<td><a href="' . esc_url( $row[ $field ] ) . '">' . esc_url( $row[ $field ] ) . '</a></td>';
											break;
										default:
											print '<td>' . $row[ $field ] . '</td>';
											break;
									}
								} else {
									print '<td></td>';
								}
							}
							print '</tr>';
						}
					}
				}
				print '</tbody>';

				print '</table></div>';
			} else {
				print '';
			}
		}
	}

	/**
	 * @return array
	 */
	public static function process_global_form() {
		$citations = self::process_citations();
		if ( ! empty( $citations ) ) {
			if ( isset( $_POST['citation-replace-all'] ) ) {
				self::replace_all_citations( $citations );
			}

			if ( isset( $_POST['citation-add-all'] ) ) {
				self::add_all_citations( $citations );
			}
		}

		return $citations;
	}

	/**
	 * @param $citations
	 */
	public static function replace_all_citations( $citations ) {
		$types = self::postTypes();

		foreach ( $types as $type ) {
			$posts = get_posts( array( 'post_type' => $type, 'post_status' => 'any', 'posts_per_page' => '-1' ) );
			foreach ( $posts as $post ) {
				update_post_meta( $post->ID, CANDELA_CITATION_FIELD, json_encode( $citations ) );
			}
		}
	}

	/**
	 * @param $citations
	 */
	public static function add_all_citations( $citations ) {
		$types = self::postTypes();

		foreach ( $types as $type ) {
			$posts = get_posts( array( 'post_type' => $type, 'post_status' => 'any', 'posts_per_page' => '-1' ) );
			foreach ( $posts as $post ) {
				// Get existing citations and append new ones.
				$existing = self::get_citations( $post->ID );
				if ( ! empty( $existing ) ) {
					$new = array_merge( $existing, $citations );
				} else {
					$new = $citations;
				}
				update_post_meta( $post->ID, CANDELA_CITATION_FIELD, json_encode( $new ) );
			}
		}
	}

	/**
	 * Add our citation processing vars so that wordpress "understands" them.
	 *
	 * @param $query_vars
	 *
	 * @return array
	 */
	public static function query_vars( $query_vars ) {
		$query_vars[] = '__citation';

		return $query_vars;
	}

	/**
	 * @param $rows
	 */
	public static function citations_table( $rows ) {
		$first  = true;
		$fields = self::citation_fields();
		echo '<div id="citation-table">';
		$i = 0;
		foreach ( $rows as $fields ) {
			$row = array();
			foreach ( $fields as $field ) {
				$row[] = $field['label-html'] . '&nbsp;&nbsp;' . $field['widget'];
			}

			echo '<div class="postbox"><div class="handlediv" title="Click to toggle"><br /></div><div class="hndle">' . __( 'Attribution', 'candela-citation' ) . '</div><div class="inside"><div class="custom-metadata-field text">';
			echo implode( '</div><div class="custom-metadata-field text">', str_replace( '%%INDEX%%', $i, $row ) );
			echo '</div></div></div>';
			$i ++;
		}
		echo '</div>';
		echo '<button id="citation-add-more-button" type="button">';
		_e( 'Add more attributions' );
		echo '</button>';
		echo '<script type="text/javascript">
      jQuery( document ).ready( function( $ ) {
        var citationIndex = ' . $i . ';
        citationWidgets = \'<div class="postbox"><div class="handlediv" title="Click to toggle"><br /></div><div class="hndle">' . __( 'Attribution', 'candela-citation' ) . '</div><div class="inside"><div class="custom-metadata-field text">' . implode( '</div><div class="custom-metadata-field text">', $row ) . '</div></div></div>\';
        $( "#citation-add-more-button" ).click(function() {
          newWidgets = citationWidgets.split("%%INDEX%%").join(citationIndex);
          $( "#citation-table").append(newWidgets);
          citationIndex++;
        });
      });
    </script>';
	}
}