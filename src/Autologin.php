<?php

namespace Renakdup\WPHelperLibrary;

class Autologin {
	public static function is_user_request() {
		if ( ( defined( 'DOING_CRON' ) && DOING_CRON ) ||
		     ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) ||
		     ( defined( 'DOING_AJAX' ) && DOING_AJAX ) ||
		     ( defined( 'REST_REQUEST' ) && REST_REQUEST ) ||
		     ( strpos( $_SERVER['REQUEST_URI'], '/wp-admin/upgrade.php' ) !== false )
		) {
			return false; // not origin user request.
		}

		return true;
	}

	public static function force_login() {
		add_action( 'init', function () {
			if ( ! self::is_user_request() ) {
				return;
			}

			if ( ! in_array( wp_get_environment_type(), [ 'loc', 'local' ] ) ) {
				throw new \RuntimeException( 'Login functionality can be run just for [loc, local] wp_get_environment_type().' );
			}

			if ( is_user_logged_in() ) {
				return;
			}

			$super_admins = get_super_admins();

			if ( $super_admins ) {
				$user_login = $super_admins[0] ?? null;
			} else {
				$admins = get_users( [ 'role' => 'administrator' ] );

				if ( ! $admins ) {
					$user_login = null;
				} else {
					$user_login = $admins[0]->data->user_login;
				}
			}

			if ( $user_login === null ) {
				throw new \RuntimeException( 'There are no any admin and super admin users!' );
			}

			$user = get_user_by( 'login', $user_login );

			wp_clear_auth_cookie();
			wp_set_current_user( $user->ID );
			wp_set_auth_cookie( $user->ID );

			// if you have any troubles with loop redirect comment it.
			wp_safe_redirect( '/wp-admin/' );
			exit();
		} );
	}
}
