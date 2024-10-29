<?php

namespace AppScenic;

use AppScenic\Traits\Webhook;

class Upgrade {

	use Webhook;

	private $db_version;
	private $db_version_option_name = 'appscenic_version';

	public function __construct() {
		$this->db_version = $this->get_db_version();
	}

	public function register() {

		// Migrations go here, before setting the new version in the database

		$this->update_version();

	}

	private function get_db_version() {
		return get_option( $this->db_version_option_name );
	}

	private function set_db_version() {
		update_option( $this->db_version_option_name, Config::get( 'plugin_version' ) );
	}

	private function update_version() {

		if ( $this->db_version && version_compare( Config::get( 'plugin_version' ), $this->db_version, '=' ) ) {
			return;
		}

		$http_args = $this->get_default_http_args();

		$http_args['timeout'] = 5;

		$http_args['body'] = trim( wp_json_encode( [
			'store_url'           => home_url(),
			'integration_version' => Config::get( 'plugin_version' ),
		] ) );

		$http_args['headers']['X-WC-Webhook-Source']    = home_url( '/' );
		$http_args['headers']['X-WC-Webhook-Signature'] = $this->generate_signature( $http_args['body'] );

		$response = wp_safe_remote_post( Config::get( 'webhook_store_updated_url' ), $http_args );

		if ( is_wp_error( $response ) || $response['response']['code'] > 202 ) {

			$code = is_wp_error( $response ) ? $response->get_error_code()    : $response['response']['code'];
			$msg  = is_wp_error( $response ) ? $response->get_error_message() : $response['response']['message'];

			error_log( print_r( esc_html__( 'Store updated webhook failed', 'appscenic' ) . ": $code $msg.", true ) );

		} else {

			$this->set_db_version();

		}

	}

}
