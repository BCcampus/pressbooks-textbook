<?php
/*
Plugin Name: WP LaTeX
Plugin URI: http://automattic.com/code/
Description: Converts inline latex code into PNG images that are displayed in your blog posts and comments.  Use either [latex]e^{\i \pi} + 1 = 0[/latex] or $latex e^{\i \pi} + 1 = 0$ syntax.
Version: 1.8
Author: Automattic, Inc.
Author URI: http://automattic.com/

Copyright: Automattic, Inc.
Copyright: Sidney Markowitz.
License: GPL2+
*/

if ( !defined('ABSPATH') ) exit;

class WP_LaTeX {
	var $options;
	var $methods = array(
		'Automattic_Latex_WPCOM' => 'wpcom',
		'Automattic_Latex_DVIPNG' => 'dvipng',
		'Automattic_Latex_DVIPS' => 'dvips'
	);

	function init() {
		$this->options = get_option( 'wp_latex' );
	
		@define( 'AUTOMATTIC_LATEX_LATEX_PATH', $this->options['latex_path'] );
		@define( 'AUTOMATTIC_LATEX_DVIPNG_PATH', $this->options['dvipng_path'] );
		@define( 'AUTOMATTIC_LATEX_DVIPS_PATH', $this->options['dvips_path'] );
		@define( 'AUTOMATTIC_LATEX_CONVERT_PATH', $this->options['convert_path'] );
	
		add_action( 'wp_head', array( &$this, 'wp_head' ) );

		add_filter( 'the_content', array( &$this, 'inline_to_shortcode' ), 8 );
		add_shortcode( 'latex', array( &$this, 'shortcode' ) );

		// This isn't really correct.  This adds all shortcodes to comments, not just LaTeX
		if ( !has_filter( 'comment_text', 'do_shortcode' ) && $this->options['comments'] ) {
			add_filter( 'comment_text', array( &$this, 'inline_to_shortcode' ) );
			add_filter( 'comment_text', 'do_shortcode', 31 );
		}
	}

	function wp_head() {
		if ( !$this->options['css'] )
			return;
?>
<style type="text/css">
/* <![CDATA[ */
<?php echo $this->options['css']; ?>

/* ]]> */
</style>
<?php
	}

	// [latex size=0 color=000000 background=ffffff]\LaTeX[/latex]
	// Shortcode -> <img> markup.  Creates images as necessary.
	function shortcode( $_atts, $latex ) {
		$atts = shortcode_atts( array(
			'size' => 0,
			'color' => false,
			'background' => false,
		), $_atts );
	
		$latex = preg_replace( array( '#<br\s*/?>#i', '#</?p>#i' ), ' ', $latex );

		$latex = str_replace(
			array( '&lt;', '&gt;', '&quot;', '&#8220;', '&#8221;', '&#039;', '&#8125;', '&#8127;', '&#8217;', '&#038;', '&amp;', "\n", "\r", "\xa0", '&#8211;' ),
			array( '<',    '>',    '"',      '``',       "''",     "'",      "'",       "'",       "'",       '&',      '&',     ' ',  ' ',  ' ',    '-' ),
			$latex
		);

		$latex_object = $this->latex( $latex, $atts['background'], $atts['color'], $atts['size'] );

		$url = clean_url( $latex_object->url );
		$alt = attribute_escape( is_wp_error($latex_object->error) ? $latex_object->error->get_error_message() . ": $latex_object->latex" : $latex_object->latex );
	
		return "<img src='$url' alt='$alt' title='$alt' class='latex' />";
	}
	
	function sanitize_color( $color ) {
		$color = substr( preg_replace( '/[^0-9a-f]/i', '', $color ), 0, 6 );
		if ( 6 > $l = strlen($color) )
			$color .= str_repeat('0', 6 - $l );
		return $color;
	}		

	function &latex( $latex, $background = false, $color = false, $size = 0 ) {
		if ( empty( $this->methods[$this->options['method']] ) )
			return false;

		if ( !$background )
			$background = empty( $this->options['bg'] ) ? 'ffffff' : $this->options['bg'];
		if ( !$color )
			$color = empty( $this->options['fg'] ) ? '000000' : $this->options['fg'];

		require_once( dirname( __FILE__ ) . "/automattic-latex-{$this->methods[$this->options['method']]}.php" );
		$latex_object = new $this->options['method']( $latex, $background, $color, $size, WP_CONTENT_DIR . '/latex', WP_CONTENT_URL . '/latex' );
		if ( isset( $this->options['wrapper'] ) )
			$latex_object->wrapper( $this->options['wrapper'] );
		$latex_object->url();

		return $latex_object;
	}
	
	function inline_to_shortcode( $content ) {
		if ( false === strpos( $content, '$latex' ) )
			return $content;

		return preg_replace_callback( '#(\s*)\$latex[= ](.*?[^\\\\])\$(\s*)#', array( &$this, 'inline_to_shortcode_callback' ), $content );
	}

	function inline_to_shortcode_callback( $matches ) {
		$r = "{$matches[1]}[latex";

		if ( preg_match( '/.+((?:&#038;|&amp;)s=(-?[0-4])).*/i', $matches[2], $s_matches ) ) {
			$r .= ' size="' . (int) $s_matches[2] . '"';
			$matches[2] = str_replace( $s_matches[1], '', $matches[2] );
		}

		if ( preg_match( '/.+((?:&#038;|&amp;)fg=([0-9a-f]{6})).*/i', $matches[2], $fg_matches ) ) {
			$r .= ' color="' . $fg_matches[2] . '"';
			$matches[2] = str_replace( $fg_matches[1], '', $matches[2] );
		}
	
		if ( preg_match( '/.+((?:&#038;|&amp;)bg=([0-9a-f]{6})).*/i', $matches[2], $bg_matches ) ) {
			$r .= ' background="' . $bg_matches[2] . '"';
			$matches[2] = str_replace( $bg_matches[1], '', $matches[2] );
		}

		return "$r]{$matches[2]}[/latex]{$matches[3]}";
	}
}

if ( is_admin() ) {
	require( dirname( __FILE__ ) . '/wp-latex-admin.php' );
	$wp_latex = new WP_LaTeX_Admin;
//	register_activation_hook( __FILE__, array( &$wp_latex, 'activation_hook' ) );
} else {
	$wp_latex = new WP_LaTeX;
}

add_action( 'init', array( &$wp_latex, 'init' ) );
