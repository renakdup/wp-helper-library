<?php

namespace Renakdup\WPHelperLibrary;

class Autoloader {
	/**
	 * @param string $pattern example: __DIR__ . '/auto-include/*.php'
	 *
	 * @return void
	 */
	public static function glob( string $directory_path ) {
		foreach ( glob( rtrim( $directory_path, '/' ) . '/*.php' ) as $file ) {
			require_once $file;
		}
	}

	/**
	 * @param string $namespace
	 * @param string $src_dir example: __DIR__ . '/src'
	 *
	 * @return void
	 */
	public static function register_spl_autoloader( string $namespace, string $src_dir ) {
		spl_autoload_register(
			function ( $class ) use ( $namespace, $src_dir ) {
				if ( strpos( $class, $namespace ) !== 0 ) {
					return;
				}

				$path   = str_replace( [ $namespace, '\\' ], [ $src_dir, '/' ], $class );

				require_once "$path.php";
			}
		);
	}
}