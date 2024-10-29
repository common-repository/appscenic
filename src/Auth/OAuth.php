<?php

namespace AppScenic\Auth;

use AppScenic\Config;
use AppScenic\Traits\Common;

class OAuth {

	use Common;

	public function register() {

		add_action( 'admin_init', [$this, 'auth'] );

	}

	public function auth() {

		if ( ! $this->is_request_valid() || ! isset( $_POST['_wpnonce'] ) ) {
			return;
		}

		if ( ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['_wpnonce'] ) ), 'appscenic_auth' ) ) {

			wp_admin_notice( esc_html__( 'The link has expired. Please try again.', 'appscenic' ), [
				'type' => 'error',
				'dismissible' => true,
			] );

			return;

		}

		wp_redirect( $this->get_auth_url() );

		exit;

	}

	private function get_auth_url(): string {

		$keys = $this->create_keys( 'AppScenic', get_current_user_id(), 'read_write' );

		$path_parameters = [
			'store_name' => sanitize_text_field( get_bloginfo( 'name' ) ),
			'shop_url'   => get_site_url(),
			'api_key'    => $keys['consumer_key'],
			'api_secret' => $keys['consumer_secret'],
		];

		$base64_encoded_json = base64_encode( json_encode( $path_parameters ) );
		$none = base64_encode( 'null' );

		return implode( '/', [Config::get( 'auth_url' ), $base64_encoded_json, $none] );

	}

	private function create_keys( $app_name, $app_user_id, $scope ): array {

		global $wpdb;

		$description = sprintf(
			'%s - API (%s)',
			wc_trim_string( wc_clean( $app_name ), 170 ),
			gmdate( 'Y-m-d H:i:s' )
		);

		$user = wp_get_current_user();

		$permissions     = in_array( $scope, ['read', 'write', 'read_write'], true ) ? sanitize_text_field( $scope ) : 'read';
		$consumer_key    = 'ck_' . wc_rand_hash();
		$consumer_secret = 'cs_' . wc_rand_hash();

		$wpdb->insert(
			$wpdb->prefix . 'woocommerce_api_keys',
			[
				'user_id'         => $user->ID,
				'description'     => $description,
				'permissions'     => $permissions,
				'consumer_key'    => wc_api_hash( $consumer_key ),
				'consumer_secret' => $consumer_secret,
				'truncated_key'   => substr( $consumer_key, -7 ),
			],
			[
				'%d',
				'%s',
				'%s',
				'%s',
				'%s',
				'%s',
			]
		);

		return [
			'key_id'          => $wpdb->insert_id,
			'user_id'         => $app_user_id,
			'consumer_key'    => $consumer_key,
			'consumer_secret' => $consumer_secret,
			'key_permissions' => $permissions,
		];

	}

	private function is_request_valid(): bool {

		return $this->is_settings_page() && current_user_can( 'manage_options' );

	}

}
