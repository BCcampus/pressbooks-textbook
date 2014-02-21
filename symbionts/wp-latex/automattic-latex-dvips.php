<?php
/*
Version: 1.1
Copyright: Automattic, Inc.
Copyright: Sidney Markowitz.
License: GPL2+
*/

/*
Must define the following constants
AUTOMATTIC_LATEX_DVIPS_PATH
AUTOMATTIC_LATEX_CONVERT_PATH
*/

require_once( dirname( __FILE__ ) . '/automattic-latex-dvipng.php' );

class Automattic_Latex_DVIPS extends Automattic_Latex_DVIPNG {
	function dvi_file2ps_file( $dvi_file ) {
		if ( !defined( 'AUTOMATTIC_LATEX_DVIPS_PATH' ) || !file_exists( AUTOMATTIC_LATEX_DVIPS_PATH ) )
			return new WP_Error( 'dvips_path', __( 'dvips path not specified.', 'automattic-latex' ) );
		
		$ps_file = preg_replace( '/[.]dvi$/', '', $dvi_file ) . '.ps';
		$output_resolution = round( 100 * $this->zoom );

		$dvips_exec = AUTOMATTIC_LATEX_DVIPS_PATH . ' -D ' . intval( $output_resolution ) . ' -E ' . escapeshellarg( $dvi_file ) . ' -o ' . escapeshellarg( $ps_file );
		exec( "$dvips_exec > /dev/null 2>&1", $dvips_out, $dps );
		if ( 0 != $dps )
			return new WP_Error( 'dvips_exec', __( 'Cannot create image', 'automattic-latex' ), $dvips_exec );

		return $ps_file;
	}

	function ps_file2png_file( $ps_file, $png_file = false ) {
		if ( !defined( 'AUTOMATTIC_LATEX_CONVERT_PATH' ) || !file_exists( AUTOMATTIC_LATEX_CONVERT_PATH ) )
			return new WP_Error( 'convert_path', __( 'convert path not specified.', 'automattic-latex' ) );

		if ( !$png_file )
			$png_file = preg_replace( '/[.]ps$/', '', $ps_file ) . '.png';

		if ( !wp_mkdir_p( dirname( $png_file ) ) )
			return new WP_Error( 'mkdir', sprintf( __( 'Could not create subdirectory <code>%s</code>.  Check your directory permissions.', 'automattic-latex' ), dirname( $png_file ) ) );

		// convert -density 100 -flatten test.ps -size 1x2 gradient:red-green -fx 'v.p{0,0}*u+v.p{0,1}*(1-u)' test.png
		// convert -density 100 test.ps -size 1x1 xc:red -fx '1-(1-v.p{0,0})*(1-u)' test.png

		if ( 'T' == $this->fg_hex )
			$this->fg_hex = '000000';

		$output_resolution = round( 100 * $this->zoom );

		$convert_exec = AUTOMATTIC_LATEX_CONVERT_PATH . ' -units PixelsPerInch -density ' . intval( $output_resolution ) . ' ';
		if ( 'T' != $this->bg_hex )
			$convert_exec .= '-flatten ';
		$convert_exec .= escapeshellarg( "$this->tmp_file.ps" );

		if ( 'T' == $this->bg_hex ) {
			if ( '000000' != $this->fg_hex ) {
				$convert_exec .= " -size 1x1 xc:#$this->fg_hex -fx '1-(1-v.p{0,0})*(1-u)'";
			}
		} else {
			if ( '000000' == $this->fg_hex && 'ffffff' == $this->bg_hex ) { // [sic]
			} elseif ( '000000' == $this->fg_hex ) {
				$convert_exec .= " -size 1x1 xc:#$this->bg_hex -fx 'u*v.p{0,0}'";
			} elseif ( 'ffffff' == $this->bg_hex ) {
				$convert_exec .= " -size 1x1 xc:#$this->fg_hex -fx '1-(1-v.p{0,0})*(1-u)'";
			} else {
				$convert_exec .= " -size 1x2 gradient:#$this->bg_hex-#$this->fg_hex -fx 'v.p{0,0}*u+v.p{0,1}*(1-u)'";
			}
		}

		$convert_exec .= ' ' . escapeshellarg( $png_file );

		exec( "$convert_exec > /dev/null 2>&1", $convert_out, $c );
		if ( 0 != $c )
			return new WP_Error( 'convert_exec', __( 'Cannot create image', 'automattic-latex' ), $convert_exec );

		return $png_file;
	}

	function dvi_file2png_file( $dvi_file, $png_file = false ) {
		$ps_file = $this->dvi_file2ps_file( $dvi_file );
		if ( is_wp_error( $ps_file ) )
			return $ps_file;

		return $this->ps_file2png_file( $ps_file, $png_file );
	}

	function unlink_tmp_files() {
		if ( !parent::unlink_tmp_files() )
			return false;

		@unlink( "$this->tmp_file.ps" );
	}
}
