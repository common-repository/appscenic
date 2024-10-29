<?php

namespace AppScenic\Traits;

trait Webhook {

	protected function get_default_http_args(): array {

		return [
			'timeout'     => MINUTE_IN_SECONDS,
			'redirection' => 0,
			'httpversion' => '1.0',
			'blocking'    => true,
			'user-agent'  => sprintf( 'WooCommerce/%s Hookshot (WordPress/%s)', WC()->version, $GLOBALS['wp_version'] ),
			'cookies'     => [],
			'headers'     => [
				'Content-Type' => 'application/json',
			],
		];

	}

	protected function generate_signature( $payload ): string {

		$hash_algo = apply_filters( 'appscenic_webhook_hash_algorithm', 'sha256', $payload );

		return base64_encode( hash_hmac( $hash_algo, $payload, wp_specialchars_decode( $this->get_secret(), ENT_QUOTES ), true ) );

	}

	private function get_secret(): string {

		return get_option( 'appscenic_webhook_secret' );

	}

}
