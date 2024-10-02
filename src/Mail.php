<?php

namespace Renakdup\WPHelperLibrary;

//( new Mailer() )->enableSMTP( 
//	'smtp.beget.com', 
//	'2525', 
//	'<password>', 
//	'<email@wp-yoda.com>', 
//	'<email@wp-yoda.com>', 
//	'<email@wp-yoda.com>', 
//	'<FromBlogName>'
//)
//->test_send_mail();

class Mailer {
	private $default_from = 'test@default.local';

	public function __construct() {
		$this->write_failed_mails_logs();
	}

	/**
	 * Example for Beget hosting:
	 *
	 * Mailer::enable();
	 *
	 * @param $host
	 * @param $port
	 * @param $password
	 * @param $username
	 * @param $from
	 * @param $sender
	 */
	public function enableSMTP(
		$host,
		$port,
		string $password,
		string $username,
		string $from,
		string $sender,
		string $fromName
	) {
		add_action( 'phpmailer_init', function ( $phpmailer ) use ( $password, $username, $from, $sender, $fromName ) {
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

		return $this;
	}

	private function write_failed_mails_logs() {
		add_action( 'wp_mail_failed', function ( $wp_error ) {
			error_log( print_r( $wp_error, true ) );
		} );
	}

	public function test_send_mail(
		string $to = 'to-test@mail.loc',
		string $subj = 'Your message can be here 123.',
		string $text = 'Your real text can be here.'
	) {
		add_action( 'init', function () use ( $to, $subj, $text ) {
			$res = wp_mail( $to, $subj . ' - ' . time(), $text );
			var_dump( $res );
			exit;
		} );

		return $this;
	}

	/**
	 * Whenever you send mails and get errors with wrong `from` address, this
	 * code can be used to fix it.
	 */
	public function fix_mail_from() {
		add_filter( 'wp_mail_from', function ( $mail ) {
			return $this->default_from;
		}, PHP_INT_MAX );

		return $this;
	}
}
