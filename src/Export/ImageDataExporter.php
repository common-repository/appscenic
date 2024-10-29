<?php

namespace AppScenic\Export;

use AppScenic\Config;

class ImageDataExporter extends Exporter {

	protected $action = 'export_image_data';

	function task( $item ) {

		$http_args = $this->get_default_http_args();

		$http_args['body'] = trim( wp_json_encode( [
			'appscenic_id' => $item['appscenic_id'],
			'platform_id'  => $item['platform_id'],
		] ) );

		$http_args['headers']['X-WC-Webhook-Source']    = home_url( '/' );
		$http_args['headers']['X-WC-Webhook-Topic']     = 'product.updated';
		$http_args['headers']['X-WC-Webhook-Resource']  = 'product';
		$http_args['headers']['X-WC-Webhook-Event']     = 'updated';
		$http_args['headers']['X-WC-Webhook-Signature'] = $this->generate_signature( $http_args['body'] );

		$response = wp_safe_remote_post( Config::get( 'webhook_product_updated_url' ), $http_args );

		if ( is_wp_error( $response ) || $response['response']['code'] > 202 ) {

			$item['retry'] = $item['retry'] ?? 0;

			// Return the item to the queue to retry
			if ( $item['retry'] < 5 ) {

				$item['retry'] = $item['retry'] + 1;

				// Don't use sleep if on Windows
				if ( strtoupper( substr( php_uname( 's' ), 0, 3 ) ) !== 'WIN' ) {

					// If sleep is not disabled
					if ( function_exists( 'sleep' ) ) {
						sleep( 60 );
					}

				}

				return $item;

			}

			// After all the retries, log the error and remove the item from the queue
			$code = is_wp_error( $response ) ? $response->get_error_code()    : $response['response']['code'];
			$msg  = is_wp_error( $response ) ? $response->get_error_message() : $response['response']['message'];

			error_log( print_r( esc_html__( "Image data export failed", 'appscenic' ) . ": $code $msg.", true ) );

		    return false;

		}

		return false;

	}

}
