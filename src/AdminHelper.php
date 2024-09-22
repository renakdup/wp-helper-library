<?php

namespace Renakdup\WPHelperLibrary;

class AdminHelper {

	/**
	 * Check important activated plugins OR show message in admin-panel.
	 *
	 * //$is_dependency_activated = check_are_activated_plugins_or_show_message_in_adminpanel(
	 * //    [
	 * //        'advanced-custom-fields-pro/advanced-custom-fields-pro.php',
	 * //    ],
	 * //    'There are no activated mandatory plugins for <CURRENT> plugin,
	 * //     plugin doesn't work.',
	 * //    'warning'
	 * //);
	 * //
	 * //if ( $is_dependency_activated ) {
	 * //    add_action(
	 * //        'plugins_loaded',
	 * //        function () {
	 * //            require_once __DIR__ . '/inc/<PLUGIN-FILES.php>';
	 * //        }
	 * //    );
	 * //}
	 *
	 * @param array $plugins_list
	 * @param string $message
	 * @param string $type
	 *
	 * @return bool false if not activated at least one plugin.
	 */
	public static function check_important_activated_plugins_or_show_admin_message(
		array $plugins_list,
		string $message = 'There are no activated mandatory plugins:',
		string $type = 'error'
	): bool {

		if ( ! $plugins_list ) {
			return false;
		}

		if ( ! function_exists( 'is_plugin_active' ) ) {
			require_once ABSPATH . 'wp-admin/includes/plugin.php';
		}

		$not_activated_plugins = array_reduce(
			$plugins_list,
			static function ( $carry, $item ) {
				if ( is_plugin_active( $item ) ) {
					return $carry;
				}

				$carry[] = $item;

				return $carry;
			},
			[]
		);

		if ( ! $not_activated_plugins ) {
			return true;
		}

		if ( ! is_admin() ) {
			return false;
		}

		$list = [];
		foreach ( $not_activated_plugins as $plug_basename ) {
			$list[] = ' - ' . esc_html( $plug_basename ) . '<br/>';
		}
		$plugins_list_message = implode( ', ', $list );

		$message = "<b>{$message}</b> <br/><b>List of mandatory plugins:</b> <br/>{$plugins_list_message}";

		self::show_admin_notice( $message, $type );

		return false;
	}

	public static function show_admin_notice( string $message, string $type, bool $is_hiding = false ): void {
		if ( ! is_admin() ) {
			return;
		}

		$available_types = [
			'error'   => 'notice-error', // red
			'warning' => 'notice-warning', // yellow
			'info'    => 'notice-info', // blue
			'success' => 'notice-success', // green
		];

		add_action(
			'admin_notices',
			function () use ( $available_types, $message, $type, $is_hiding ) {
				$notice_class = $available_types[ $type ] ?? $available_types[ array_key_first( $available_types ) ];
				$notice_class = $is_hiding ? $notice_class . ' is-dismissible' : $notice_class;

				echo '<div class="notice ' . esc_attr( $notice_class ) . '">';
				echo '<p>' . wp_kses_post( $message ) . '</p>';
				echo '</div>';
			}
		);
	}
}