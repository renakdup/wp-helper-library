<?php

namespace Renakdup\WPHelperLibrary;

class Helper {
	function get_template( string $path_to_template, array $data = [] ): string {
		ob_start();

		include $path_to_template;

		return ob_get_clean();
	}

	/**
	 * Calculates file version based on filemtime().
	 * If the provided absolute url is internal link
	 * or based on global $wp_version in case the url is external.
	 *
	 * @param string $source URL of static file, the same that usually used for wp_enqueue_script(), wp_enqueue_style().
	 *
	 * @return string       Version string.
	 */
	public function get_file_version( string $source ): string {
		global $wp_version;

		$path_to = str_replace( content_url(), WP_CONTENT_DIR, $source );

		return (string) ( file_exists( $path_to ) ? filemtime( $path_to ) : $wp_version );
	}
}