<?php

namespace Renakdup\WPHelperLibrary;

class Mailer {
	/**
	 * Example for Beget hosting:
	 *
	 * SMTP::enable('smtp.beget.com', '2525', '<password>', '<email@wp-yoda.com>', '<email@wp-yoda.com>', '<email@wp-yoda.com>', '<FromBlogName>');
	 *
	 * @param $host
	 * @param $port
	 * @param $password
	 * @param $username
	 * @param $from
	 * @param $sender
	 *
	 * @return void
	 */
	public static function enable( $host, $port, string $password, string $username, string $from, string $sender, string $fromName ) {
		add_action( 'phpmailer_init', function ( $phpmailer ) {
			/** @var \PHPMailer\PHPMailer\PHPMailer $phpmailer */

			$phpmailer->isSMTP();
			$phpmailer->SMTPAuth   = true;
			$phpmailer->SMTPSecure = 'tls';
			$phpmailer->Password   = $password;
			$phpmailer->Username   = $username;

			$phpmailer->From     = $from;
			$phpmailer->Sender   = $sender;
			$phpmailer->FromName = $fromName;
		} );
	}

	public static function write_failed_mails_logs() {
		add_action( 'wp_mail_failed', function ( $wp_error ) {
			error_log( print_r( $wp_error, true ) );
		} );
	}

	public static function test_send_mail( string $to, string $subj = 'Test message', string $text = 'Text for testing mail sending' ) {
		add_action( 'init', function () use ( $to, $subj, $text ) {
			$res = wp_mail( $to, $subj . ' - ' . time(), $text );
			var_dump( $res );
			exit;
		} );
	}
}




