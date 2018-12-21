<?php
/**
 * PSR-4 compliant autoload.
 *
 * @modified from https://github.com/php-fig/fig-standards/blob/master/accepted/PSR-4-autoloader-examples.md
 *
 * @param string $class The fully-qualified class name.
 *
 * @return void
 */
\spl_autoload_register(
	function ( $class ) {

		// project-specific namespace prefix
		$prefix = 'PBT';

		// base directory for the namespace prefix
		$base_dir = __DIR__ . '/inc';

		// does the class use the namespace prefix?
		$len = \strlen( $prefix );

		if ( \strncmp( $prefix, $class, $len ) !== 0 ) {
			// no, move to the next registered autoloader
			return;
		}

		// get the relative class name
		$relative_class = \substr( $class, $len );
		$last_ns_pos    = strripos( $relative_class, '\\' );

		if ( false !== $last_ns_pos ) {
			$namespace = substr( $relative_class, 0, $last_ns_pos );
			$class     = substr( $relative_class, $last_ns_pos + 1 );
			$file      = str_replace( '\\', DIRECTORY_SEPARATOR, $namespace ) . DIRECTORY_SEPARATOR;
		}
		$file .= 'class-' . str_replace( '_', '-', $class ) . '.php';

		$path = $base_dir . strtolower( $file );

		// if the file exists, require it
		if ( \file_exists( $path ) ) {
			require $path;
		}
	}
);
