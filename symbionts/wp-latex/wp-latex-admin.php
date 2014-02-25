<?php
/**
 * Copyright 2014 Brad Payne. GPL v2, of course.
 * 
 * This plugin is forked from the original WP Latex v1.8 http://wordpress.org/plugins/wp-latex/ (c) Sidney Markowitz, Automattic, Inc.
 * It modifies the plugin to work with PressBooks, strips unwanted features, adds others — activated at the network level
 *
 */

if ( !defined('ABSPATH') ) exit;

class WP_LaTeX_Admin extends WP_LaTeX {
	var $errors;

	function init() {
		parent::init();
		$this->errors = new WP_Error;
		// since we're activating at the network level, this needs to be called in the constructor
		$this->addOptions();

		add_action( 'admin_menu', array( &$this, 'admin_menu' ) );
	}

	function admin_menu() {
		$hook = add_options_page( 'WP LaTeX', 'WP LaTeX', 'manage_options', 'wp-latex', array( &$this, 'admin_page' ) );
		add_action( "load-$hook", array( &$this, 'admin_page_load' ) );

		if ( 'Automattic_Latex_WPCOM' != $this->options['method'] && !is_writable( WP_CONTENT_DIR . '/latex' ) )
			add_action( 'admin_notices', array( &$this, 'not_writeable_error' ) );

		add_filter( 'plugin_action_links_' . plugin_basename( dirname( __FILE__ ) . '/wp-latex.php' ), array( &$this, 'plugin_action_links' ) );
	}
	
	function not_writeable_error() {
?>
	<div id="latex-chmod" class="error fade"><p><?php printf(
		__( '<code>%s</code> must be writeable for WP LaTeX to work.' ),
		esc_html( WP_CONTENT_DIR . '/latex/' )
	); ?></p></div>
<?php
	}
	
	function plugin_action_links( $links ) {
		array_unshift( $links, '<a href="options-general.php?page=wp-latex">' . __( 'Settings' ) . "</a>" );
		return $links;
	}

	function admin_page_load() {
		if ( !current_user_can( 'manage_options' ) )
			wp_die( __( 'Insufficient LaTeX-fu', 'wp-latex' ) );
	
		add_action( 'admin_head', array( &$this, 'admin_head' ) );

		if ( empty( $_POST['wp_latex'] ) ) {
			if ( $this->options['wrapper'] && ( false !== strpos( $this->options['wrapper'], '%BG_COLOR_RGB%' ) || false !== strpos( $this->options['wrapper'], '%FG_COLOR_RGB%' ) ) )
				$this->errors->add( 'wrapper', __( 'WP LaTeX no longer supports ><code>%BG_COLOR_RGB%</code> or <code>%FG_COLOR_RGB</code> in the LaTeX preamble.  Please remove them.' ), $this->options['wrapper'] );
			return;
		}

		check_admin_referer( 'wp-latex' );
	
		if ( $this->update( stripslashes_deep( $_POST['wp_latex'] ) ) ) {
			wp_safe_redirect( add_query_arg( 'updated', '', wp_get_referer() ) );
			exit;
		}
	}
	
	function update( $new ) {
		if ( !is_array( $this->options ) )
			$this->options = array();
		extract( $this->options, EXTR_SKIP );
	
		if ( isset( $new['method'] ) ) {
			if ( empty( $this->methods[$new['method']] ) ) {
				$this->errors->add( 'method', __( 'Invalid LaTeX generation method', 'wp-latex' ), $new['method'] );
			} else {
				$method = $new['method'];
			}
		}

		if ( isset( $new['fg'] ) ) {
			$fg = strtolower( substr( preg_replace( '/[^0-9a-f]/i', '', $new['fg'] ), 0, 6 ) );
			if ( 6 > $l = strlen( $fg ) ) {
				$this->errors->add( 'fg', __( 'Invalid text color', 'wp-latex' ), $new['fg'] );
				$fg .= str_repeat( '0', 6 - $l );
			}
		}
	
		if ( isset( $new['bg'] ) ) {
			if ( 'transparent' == trim( $new['bg'] ) ) {
				$bg = 'transparent';
			} else {
				$bg = substr( preg_replace( '/[^0-9a-f]/i', '', $new['bg'] ), 0, 6 );
				if ( 6 > $l = strlen( $bg ) ) {
					$this->errors->add( 'bg', __( 'Invalid background color', 'wp-latex' ), $new['bg'] );
					$bg .= str_repeat( '0', 6 - $l );
				}
			}
		}
		
		if ( isset( $new['css'] ) ) {
			$css = str_replace( array( "\n", "\r" ), "\n", $new['css'] );
			$css = trim( preg_replace( '/[\n]+/', "\n", $css ) );
		}
	
		if ( isset( $new['wrapper'] ) ) {
			$wrapper = str_replace( array("\n", "\r"), "\n", $new['wrapper'] );
			if ( !$wrapper = trim( preg_replace('/[\n]+/', "\n", $new['wrapper'] ) ) )
				$wrapper = false;

		}
		if ( $wrapper && ( false !== strpos( $wrapper, '%BG_COLOR_RGB%' ) || false !== strpos( $wrapper, '%FG_COLOR_RGB%' ) ) )
			$this->errors->add( 'wrapper', __( 'WP LaTeX no longer supports ><code>%BG_COLOR_RGB%</code> or <code>%FG_COLOR_RGB</code> in the LaTeX preamble.  Please remove them.' ), $new['wrapper'] );
	
		if ( isset( $new['latex_path'] ) ) {
			$new['latex_path'] = trim( $new['latex_path'] );
			if ( ( !$new['latex_path'] || !file_exists( $new['latex_path'] ) ) && 'Automattic_Latex_WPCOM' != $method )
				$this->errors->add( 'latex_path', __( '<code>latex</code> path not found.', 'wp-latex' ), $new['latex_path'] );
			else
				$latex_path = $new['latex_path'];
		}
	
		if ( isset( $new['dvipng_path'] ) ) {
			$new['dvipng_path'] = trim( $new['dvipng_path'] );
			if ( ( !$new['dvipng_path'] || !file_exists( $new['dvipng_path'] ) ) && 'Automattic_Latex_DVIPNG' == $method )
				$this->errors->add( 'dvipng_path', __( '<code>dvipng</code> path not found.', 'wp-latex' ), $new['dvipng_path'] );
			else
				$dvipng_path = $new['dvipng_path'];
		}
	
		if ( isset( $new['dvips_path'] ) ) {
			$new['dvips_path'] = trim( $new['dvips_path'] );
			if ( ( !$new['dvips_path'] || !file_exists( $new['dvips_path'] ) ) && 'Automattic_Latex_DVIPS' == $method )
				$this->errors->add( 'dvips_path', __( '<code>dvips</code> path not found.', 'wp-latex' ), $new['dvips_path'] );
			else
				$dvips_path = $new['dvips_path'];
		}
	
		if ( isset( $new['convert_path'] ) ) {
			$new['convert_path'] = trim( $new['convert_path'] );
			if ( ( !$new['convert_path'] || !file_exists( $new['convert_path'] ) ) && 'Automattic_Latex_DVIPS' == $method )
				$this->errors->add( 'convert_path', __( '<code>convert</code> path not found.', 'wp-latex' ), $new['convert_path'] );
			else
				$convert_path = $new['convert_path'];
		}
	
		$this->options = compact( 'bg', 'fg', 'css', 'latex_path', 'dvipng_path', 'dvips_path', 'convert_path', 'wrapper', 'method' );
		update_option( 'wp_latex', $this->options );
		return !count( $this->errors->get_error_codes() );
	}
	
	// Attempts to use current settings to generate a temporory image (new with every page load)
	function test_image() {
		if ( 'Automattic_Latex_WPCOM' != $this->options['method'] && !is_writable( WP_CONTENT_DIR . '/latex' ) )
			return false;
	
		if ( is_array( $this->options ) )
			extract( $this->options, EXTR_SKIP );
	
		if ( 'Automattic_Latex_DVIPNG' == $method && ( !$latex_path || !$dvipng_path ) )
			return;
		if ( 'Automattic_Latex_DVIPS' == $method && ( !$latex_path || !$dvips_path || !$convert_path ) )
			return;
	
		@unlink( WP_CONTENT_DIR . '/latex/test.png' );
	
		$latex_object = $this->latex( '\displaystyle P_\nu^{-\mu}(z)=\frac{\left(z^2-1\right)^{\frac{\mu}{2}}}{2^\mu \sqrt{\pi}\Gamma\left(\mu+\frac{1}{2}\right)}\int_{-1}^1\frac{\left(1-t^2\right)^{\mu -\frac{1}{2}}}{\left(z+t\sqrt{z^2-1}\right)^{\mu-\nu}}dt', $bg, $fg, 3 );
		if ( !empty( $wrapper ) )
			$latex_object->wrapper( $wrapper );
	
		$message = '';
	
		$r = false;

		$url = $latex_object->url();
		if ( !empty( $latex_object->tmp_file ) )
			exec( 'mv ' . escapeshellarg( "$latex_object->tmp_file.log" ) . ' ' . WP_CONTENT_DIR . '/latex/test.log' );

		if ( is_wp_error( $url ) ) {
			$code = $url->get_error_code();
			if ( false !== strpos( $code, '_exec' ) ) :
				$message = "<div class='error'>\n";
				$exec = $url->get_error_data( $code );
				exec( $exec, $out, $r );
				$message .= "<h4>Command run:</h4>\n";
				$message .= "<div class='pre'><code>$exec</code></div>\n";
				$out = preg_replace( '/tex_.+?\.log/i', '<strong><a href="' . clean_url( content_url( 'latex/test.log' ) ) . '">test.log</a></strong>', join("\n", $out));
				$message .= "<h4>Result:</h4>\n";
				$message .= "<div class='pre'><code>$out</code></div>\n";
				$message .= "<p>Exit code: $r</p>\n";
				$message .= "</div>";
			else :
				$message = '<div class="error"><p>' . $url->get_error_message() . "</p></div>\n";
			endif;
			echo $message;
		} else {
			if ( !empty( $latex_object->file ) ) {
				exec( 'mv ' . escapeshellarg( "$latex_object->file" ) . ' ' . WP_CONTENT_DIR . '/latex/test.png' );
				$url = content_url( 'latex/test.png' ) . "?" . mt_rand();
			}
			@unlink( WP_CONTENT_DIR . '/latex/test.log' );
			$alt = attribute_escape( __( 'Test Image', 'wp-latex' ) );
			echo "<img class='test-image' src='" . clean_url( $url ) . "' alt='$alt' />\n";
			echo "<p class='test-image'>" . __( 'If you can see a big integral, all is well.', 'wp-latex' ) . '</p>';
			$r = true;
		}
		return $r;
	}
	
	function admin_head() {
		$current_method = $this->methods[$this->options['method']] ? $this->methods[$this->options['method']] : 'wpcom';
?>
<script type="text/javascript">
/* <![CDATA[ */
jQuery( function($) {
	$( '#wp-latex-method-switch :radio' ).change( function() {
		$( '.wp-latex-method' ).hide().css( 'background-color', '' );
		$( '.' + this.id ).show().css( 'background-color', '#ffffcc' );
	} );
} );
/* ]]> */
</script>
<style type="text/css">
/* <![CDATA[ */
p.test-image {
	text-align: center;
	font-size: 1.4em;
}
img.test-image {
	display: block;
	margin: 0 auto 1em;
}
.syntax p {
	margin-top: 0;
}
.syntax code {
	white-space: nowrap;
}
.wp-latex-method {
	display: none;
}
tr.wp-latex-method-<?php echo $current_method; ?> {
	display: block;
}
tr.wp-latex-method-<?php echo $current_method; ?> {
	display: table-row;
}
/* ]]> */
</style>
<?php
	}

	function admin_page() {
		if ( !current_user_can( 'manage_options' ) )
			wp_die( __( 'Insufficient LaTeX-fu', 'wp-latex' ) );
	
		$default_wrappers = array();
		foreach ( $this->methods as $class => $method ) {
			if ( 'Automattic_Latex_WPCOM' == $class )
				continue;
			require_once( dirname( __FILE__ ) . "/automattic-latex-$method.php" );
			$latex_object = new $class( '\LaTeX' );
			$default_wrappers[$method] = $latex_object->wrapper();
		}
		unset( $class, $method, $latex_object );
		
		if ( !is_array( $this->options ) )
			$this->options = array();

		$values = $this->options;
	
		$errors = array();
		if ( $errors = $this->errors->get_error_codes() ) :
			foreach ( $errors as $e )
				$values[$e] = $this->errors->get_error_data( $e );
	?>
	<div id='latex-config-errors' class='error'>
		<ul>
		<?php foreach ( $this->errors->get_error_messages() as $m ) : ?>
			<li><?php echo $m; ?></li>
		<?php endforeach; ?>
		</ul>
	</div>
	<?php	endif; ?>
	
	<div class='wrap'>
	<h2><?php _e( 'WP LaTeX Options', 'wp-latex' ); ?></h2>
	
	<?php if ( empty( $errors ) ) $this->test_image(); ?>
	
	<form action="<?php echo clean_url( remove_query_arg( 'updated' ) ); ?>" method="post">

	<table class="form-table">
	<tbody>
		<?php if ( empty( $errors ) ): ?>
		<tr>
			<th scope="row"><?php _e( 'Syntax' ); ?></th>
			<td class="syntax">
				<p><?php printf( __( 'You may use either the shortcode syntax %s<br /> or the &#8220;inline&#8221; syntax %s OR %s<br /> to insert LaTeX into your posts.', 'wp-latex' ),
					'<code>[latex]e^{\i \pi} + 1 = 0[/latex]</code>',
					'<code>$latex e^{\i \pi} + 1 = 0$</code>',
					'<code>$$ e^{\i \pi} + 1 = 0 $$</code>'
				); ?></p>
				<p><?php _e( 'For more information, see the <a href="http://wordpress.org/extend/plugins/wp-latex/faq/">FAQ</a>' ); ?></p>
			</td>
		</tr>
		<?php endif; ?>
		<tr<?php if ( in_array( 'method', $errors ) ) echo ' class="form-invalid"'; ?>>
			<th scope="row"><?php _e( 'LaTeX generation method', 'wp-latex' ); ?></th>
			<td>
				<ul id="wp-latex-method-switch">
					<li><label for="wp-latex-method-wpcom"><input type="radio" name="wp_latex[method]" id="wp-latex-method-wpcom" value='Automattic_Latex_WPCOM'<?php checked( 'Automattic_Latex_WPCOM', $values['method'] ); ?> /> <?php printf( _c( '%s LaTeX server (recommended)|WordPress.com LaTeX Server (recommended)', 'wp-latex' ), '<a href="http://wordpress.com/" target="_blank">WordPress.com</a>' ); ?></label></li>
					<li><label for="wp-latex-method-dvipng"><input type="radio" name="wp_latex[method]" id="wp-latex-method-dvipng" value='Automattic_Latex_DVIPNG'<?php checked( 'Automattic_Latex_DVIPNG', $values['method'] ); ?> /> <?php _e( 'Local LaTeX installation using <code>dvipng</code>', 'wp-latex' ); ?></label></li>
					<li><label for="wp-latex-method-dvips"><input type="radio" name="wp_latex[method]" id="wp-latex-method-dvips" value='Automattic_Latex_DVIPS'<?php checked( 'Automattic_Latex_DVIPS', $values['method'] ); ?> /> <?php _e( 'Local LaTeX installation using <code>dvips</code> and <code>convert</code>', 'wp-latex' ); ?></label></li>
				</ul>
			</td>
		</tr>

		<tr class="wp-latex-method wp-latex-method-dvipng wp-latex-method-dvips<?php if ( in_array( 'latex_path', $errors ) ) echo ' form-invalid'; ?>">
			<th scope="row"><label for="wp-latex-latex-path"><?php _e( '<code>latex</code> path' ); ?></label></th>
			<td><input type='text' name='wp_latex[latex_path]' value='<?php echo attribute_escape( $values['latex_path'] ); ?>' id='wp-latex-latex-path' /><?php
				if ( !$this->options['latex_path'] ) {
					$guess_latex_path = trim( @exec( 'which latex' ) );
					if ( $guess_latex_path && file_exists( $guess_latex_path ) )
						printf( ' ' . _c( 'Try: <code>%s</code>|Try: guess_latex_path', 'wp-latex' ), $guess_latex_path );
					else
						echo ' ' . __( 'Not found.  Enter full path to <code>latex</code> or choose another LaTeX generation method.', 'wp-latex' );
				}
			?></td>
		</tr>
		<tr class="wp-latex-method wp-latex-method-dvipng<?php if ( in_array( 'dvipng_path', $errors ) ) echo ' form-invalid'; ?>">
			<th scope="row"><label for="wp-latex-dvipng-path"><?php _e( '<code>dvipng</code> path' ); ?></label></th>
			<td><input type='text' name='wp_latex[dvipng_path]' value='<?php echo attribute_escape( $values['dvipng_path'] ); ?>' id='wp-latex-dvipng-path' /><?php
				if ( !$this->options['dvipng_path'] ) {
					$guess_dvipng_path = trim( @exec( 'which dvipng' ) );
					if ( $guess_dvipng_path && file_exists( $guess_dvipng_path ) )
						printf( ' ' . _c( 'Try: <code>%s</code>|Try: guess_dvipng_path', 'wp-latex' ), $guess_dvipng_path );
					else
						echo ' ' . __(  'Not found.  Enter full path to <code>dvipng</code> or choose another LaTeX generation method.', 'wp-latex' );
				}
			?></td>
		</tr>
		<tr class="wp-latex-method wp-latex-method-dvips<?php if ( in_array( 'dvips_path', $errors ) ) echo ' form-invalid'; ?>">
			<th scope="row"><label for="wp-latex-dvips-path"><?php _e( '<code>dvips</code> path' ); ?></label></th>
			<td><input type='text' name='wp_latex[dvips_path]' value='<?php echo attribute_escape( $values['dvips_path'] ); ?>' id='wp-latex-dvips-path' /><?php
				if ( !$this->options['dvips_path'] ) {
					$guess_dvips_path = trim( @exec( 'which dvips' ) );
					if ( $guess_dvips_path && file_exists( $guess_dvips_path ) )
						printf( ' ' . _c( 'Try: <code>%s</code>|Try: guess_dvips_path', 'wp-latex' ), $guess_dvips_path );
					elseif ( !$this->options['dvipng_path'] )
						echo ' ' . __( 'Not found.  Enter full path to <code>dvips</code> or choose another LaTeX generation method.', 'wp-latex' );
				}
			?></td>
		</tr>
		<tr class="wp-latex-method wp-latex-method-dvips<?php if ( in_array( 'convert_path', $errors ) ) echo ' form-invalid'; ?>">
			<th scope="row"><label for="wp-latex-convert-path"><?php _e( '<code>convert</code> path', 'wp-latex' ); ?></label></th>
			<td><input type='text' name='wp_latex[convert_path]' value='<?php echo attribute_escape( $values['convert_path'] ); ?>' id='wp-latex-convert-path' /><?php
				if ( !$this->options['convert_path'] ) {
					$guess_convert_path = trim( @exec( 'which convert' ) );
					if ( $guess_convert_path && file_exists( $guess_convert_path ) )
						printf( ' ' . _c( 'Try: <code>%s</code>|Try: guess_convert_path', 'wp-latex' ), $guess_convert_path );
					elseif ( !$this->options['dvipng_path'] )
						echo ' ' . __( 'Not found.  Enter full path to <code>convert</code> or choose another LaTeX generation method.', 'wp-latex' );
				}
			?></td>
		</tr>

		<tr<?php if ( in_array( 'fg', $errors ) ) echo ' class="form-invalid"'; ?>>
			<th scope="row"><label for="wp-latex-fg"><?php _e( 'Default text color', 'wp-latex' ); ?></label></th>
			<td>
				<input type='text' name='wp_latex[fg]' value='<?php echo attribute_escape( $values['fg'] ); ?>' id='wp-latex-fg' />
				<?php _e( 'A six digit hexadecimal number like <code>000000</code> or <code>ffffff</code>' ); ?>
			</td>
		</tr>
		<tr<?php if ( in_array( 'bg', $errors ) ) echo ' class="form-invalid"'; ?>>
			<th scope="row"><label for="wp-latex-bg"><?php _e( 'Default background color', 'wp-latex' ); ?></label></th>
			<td>
				<input type='text' name='wp_latex[bg]' value='<?php echo attribute_escape( $values['bg'] ); ?>' id='wp-latex-bg' />
				<?php _e( 'A six digit hexadecimal number like <code>000000</code> or <code>ffffff</code>, or <code>transparent</code>' ); ?>
			</td>
		</tr>
		<tr>
			<th scope="row"><label for="wp-latex-css"><?php _e( 'Custom CSS to use with the LaTeX images', 'wp-latex' ); ?></label></th>
			<td>
				<textarea name='wp_latex[css]' id='wp-latex-css' rows="8" cols="50"><?php echo wp_specialchars( $values['css'] ); ?></textarea>
			</td>
		</tr>

		<tr class="wp-latex-method wp-latex-method-dvipng wp-latex-method-dvips<?php if ( in_array( 'wrapper', $errors ) ) echo ' form-invalid	'; ?>">
			<th scope="row"><label for="wp-latex-wrapper"><?php _e( 'LaTeX Preamble', 'wp-latex' ); ?></label></th>
			<td>
				<textarea name='wp_latex[wrapper]' rows='8' cols="50" id='wp-latex-wrapper'><?php echo wp_specialchars( $values['wrapper'] ); ?></textarea>
			</td>
		</tr>
	<?php foreach ( $default_wrappers as $method => $default_wrapper ) : ?>
		<tr class="wp-latex-method wp-latex-method-<?php echo $method; ?>">
			<th></th>
			<td>
				<h4>Leaving the above blank will use the following default preamble.</h4>
				<div class="pre"><code><?php echo $default_wrapper; ?></code></div>
			</td>
		</tr>
	<?php endforeach; ?>
	</tbody>
	</table>
	
	
	<p class="submit">
		<input type="submit" class="button-primary" value="<?php echo attribute_escape( __( 'Update LaTeX Options', 'wp-latex' ) ); ?>" />
		<?php wp_nonce_field( 'wp-latex' ); ?>
	</p>
	</form>
	</div>
	<?php
	}
	
	// Sets up default options
	function addOptions() {
		if ( is_array( $this->options ) )
			extract( $this->options, EXTR_SKIP );
	
		global $themecolors;
	
		if ( empty($bg) )
			$bg = isset( $themecolors['bg'] ) ? $themecolors['bg'] : 'transparent';
		if ( empty($fg) )
			$fg = isset( $themecolors['text'] ) ? $themecolors['text'] : '000000';
	
		if ( empty( $method ) )
			$method = 'Automattic_Latex_WPCOM';
	
		if ( empty( $css ) )
			$css = 'img.latex { vertical-align: middle; border: none; background: none; }';
	
		if ( empty( $latex_path ) )
			$latex_path = trim( @exec( 'which latex' ) );
		if ( empty( $dvipng_path ) )
			$dvipng_path = trim( @exec( 'which dvipng' ) );
		if ( empty( $dvips_path ) )
			$dvips_path = trim( @exec( 'which dvips' ) );
		if ( empty( $convert_path ) )
			$convert_path = trim( @exec( 'which convert' ) );
	
		$latex_path   = $latex_path   && @file_exists( $latex_path )   ? $latex_path   : false;
		$dvipng_path  = $dvipng_path  && @file_exists( $dvipng_path )  ? $dvipng_path  : false;
		$dvips_path   = $dvips_path   && @file_exists( $dvips_path )   ? $dvips_path   : false;
		$convert_path = $convert_path && @file_exists( $convert_path ) ? $convert_path : false;
	
		if ( empty( $wrapper ) )
			$wrapper = false;
	
//		$activated = true;

		$this->options = compact( 'bg', 'fg', 'method', 'css', 'latex_path', 'dvipng_path', 'dvips_path', 'convert_path', 'wrapper' );
		update_option( 'wp_latex', $this->options );
	}
}
