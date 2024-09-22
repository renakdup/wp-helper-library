<?php

namespace Renakdup\WPHelperLibrary;

/**
 * Description: Disables all WordPress comment functionality on the entire network.
 * Version: 1.1.2 (changed)
 * Plugin Changed URI: https://gist.github.com/doiftrue/ab931c1d866cb113b4ff318a5faeb3b3
 * Plugin URI: https://github.com/WPDevelopers/disable-comments-mu
 * GitHub Plugin URI: https://github.com/WPDevelopers/disable-comments-mu
 * Author: WPDeveloper, Renakdup
 * Author URI: https://wpdeveloper.com
 * License: GPL2
 */
class DisableComments {

	public function __construct() {
		// these need to happen now
		add_action( 'widgets_init', [ $this, 'disable_rc_widget' ] );
		add_filter( 'wp_headers', [ $this, 'filter_wp_headers' ] );
		add_action( 'template_redirect', [ $this, 'filter_query' ], 9 ); // before redirect_canonical

		// Admin bar filtering has to happen here since WP 3.6
		add_action( 'add_admin_bar_menus', [ $this, 'filter_admin_bar' ], 0 );
		add_action( 'admin_init', [ $this, 'filter_admin_bar' ] );

		// these can happen later
		add_action( 'wp_loaded', [ $this, 'setup_filters' ] );

		add_action( 'enqueue_block_editor_assets', [ $this, 'filter_gutenberg_blocks' ] );
		add_filter( 'rest_endpoints', [ $this, 'filter_rest_endpoints' ] );
		add_filter( 'xmlrpc_methods', [ $this, 'disable_xmlrc_comments' ] );
		add_filter( 'rest_pre_insert_comment', [ $this, 'disable_rest_api_comments' ], 10, 2 );
		add_filter( 'comments_array', '__return_empty_array', 20 );
	}

	public function setup_filters() {
		$types = array_keys( get_post_types( [ 'public' => true ], 'objects' ) );
		if ( ! empty( $types ) ) {
			foreach ( $types as $type ) {
				// we need to know what native support was for later
				if ( post_type_supports( $type, 'comments' ) ) {
					remove_post_type_support( $type, 'comments' );
					remove_post_type_support( $type, 'trackbacks' );
				}
			}
		}

		// Filters for the admin only
		if ( is_admin() ) {
			add_action( 'admin_menu', [ $this, 'filter_admin_menu' ], 9999 );    // do this as late as possible
			add_action( 'admin_print_styles-index.php', [ $this, 'admin_css' ] );
			add_action( 'admin_print_styles-profile.php', [ $this, 'admin_css' ] );
			add_action( 'wp_dashboard_setup', [ $this, 'filter_dashboard' ] );
			add_filter( 'pre_option_default_pingback_flag', '__return_zero' );
		} // phpcs:ignore
		// Filters for front end only
		else {
			add_action( 'template_redirect', [ $this, 'check_comment_template' ] );
			add_filter( 'comments_open', '__return_false', 20 );
			add_filter( 'pings_open', '__return_false', 20 );

			// remove comments links from feed
			add_filter( 'post_comments_feed_link', '__return_false' );
			add_filter( 'comments_link_feed', '__return_false' );
			add_filter( 'comment_link', '__return_false' );

			// remove comment count from feed
			add_filter( 'get_comments_number', '__return_false' );

			// Remove feed link from header
			add_filter( 'feed_links_show_comments_feed', '__return_false' );
		}
	}

	public function check_comment_template() {
		if ( is_singular() ) {
			// Kill the comments' template. This will deal with themes that don't check comment stati properly!
			add_filter( 'comments_template', '__return_empty_string', 20 );
			// Remove comment-reply script for themes that include it indiscriminately
			wp_deregister_script( 'comment-reply' );
			// Remove feed action
			remove_action( 'wp_head', 'feed_links_extra', 3 );
		}
	}

	public function filter_wp_headers( $headers ) {
		unset( $headers['X-Pingback'] );

		return $headers;
	}

	public function filter_query() {
		if ( is_comment_feed() ) {
			wp_die( esc_html__( 'Comments are closed.' ), '', [ 'response' => 403 ] );
		}
	}

	public function filter_admin_bar() {
		remove_action( 'admin_bar_menu', 'wp_admin_bar_comments_menu', 60 );
		if ( is_multisite() ) {
			add_action( 'admin_bar_menu', [ $this, 'remove_network_comment_links' ], 500 );
		}
	}

	public function remove_network_comment_links( $wp_admin_bar ) {
		if ( is_user_logged_in() ) {
			foreach ( (array) $wp_admin_bar->user->blogs as $blog ) {
				$wp_admin_bar->remove_menu( 'blog-' . $blog->userblog_id . '-c' );
			}
		}
	}

	public function filter_admin_menu() {
		global $pagenow;

		if ( in_array( $pagenow, [ 'comment.php', 'edit-comments.php', 'options-discussion.php' ], true ) ) {
			wp_die( esc_html__( 'Comments are closed.' ), '', [ 'response' => 403 ] );
		}

		remove_menu_page( 'edit-comments.php' );
		remove_submenu_page( 'options-general.php', 'options-discussion.php' );
	}

	public function filter_dashboard() {
		remove_meta_box( 'dashboard_recent_comments', 'dashboard', 'normal' );
	}

	public function admin_css() {
		?>
		<style>
            #dashboard_right_now .comment-count,
            #dashboard_right_now .comment-mod-count,
            #latest-comments,
            #welcome-panel .welcome-comments,
            .user-comment-shortcuts-wrap{
                display:none !important;
            }
		</style>
		<?php
	}

	public function disable_rc_widget() {
		// This widget has been removed from the Dashboard in WP 3.8 and can be removed in a future version
		unregister_widget( 'WP_Widget_Recent_Comments' );
		/**
		 * The widget has added a style action when it was constructed - which will
		 * still fire even if we now unregister the widget... so filter that out
		 */
		add_filter( 'show_recent_comments_widget_style', '__return_false' );
	}

	public function filter_gutenberg_blocks( $hook ) {
		add_action( 'admin_footer', [ $this, 'print_footer_scripts' ] );
	}

	public function print_footer_scripts() {
		?>
		<script>
            wp.domReady( () => {
                const blockType = 'core/latest-comments';
                if( wp.blocks && wp.data && wp.data.select( 'core/blocks' ).getBlockType( blockType ) ){
                    wp.blocks.unregisterBlockType( blockType );
                }
            } );
		</script>
		<?php
	}

	/**
	 * Remove the comments endpoint for the REST API
	 */
	public function filter_rest_endpoints( $endpoints ) {
		if ( isset( $endpoints['comments'] ) ) {
			unset( $endpoints['comments'] );
		}
		if ( isset( $endpoints['/wp/v2/comments'] ) ) {
			unset( $endpoints['/wp/v2/comments'] );
		}
		if ( isset( $endpoints['/wp/v2/comments/(?P<id>[\d]+)'] ) ) {
			unset( $endpoints['/wp/v2/comments/(?P<id>[\d]+)'] );
		}

		return $endpoints;
	}

	public function disable_xmlrc_comments( $methods ) {
		unset( $methods['wp.newComment'] );

		return $methods;
	}

	public function disable_rest_api_comments( $prepared_comment, $request ) {
		return new \WP_Error( 'rest_comment_disabled', 'Commenting is disabled.', [ 'status' => 403 ] );
	}

}