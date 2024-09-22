<?php

namespace Renakdup\WPHelperLibrary;

class XMLRPC {
	public static function disable() {
		add_filter( 'xmlrpc_enabled', '__return_false' );
		remove_action( 'wp_head', 'rsd_link' );
		remove_action( 'wp_head', 'wlwmanifest_link' );
	}
}