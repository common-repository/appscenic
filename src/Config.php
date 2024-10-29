<?php

namespace AppScenic;

class Config {

	public static function get( $key ) {

		$auth_base_url    = 'https://retailer.appscenic.com';
		$webhook_base_url = 'https://webhooks.appscenic.com';

		$config = [
			'plugin_path'                 => plugin_dir_path( dirname( __FILE__ ) ),
			'plugin_url'                  => plugin_dir_url( dirname( __FILE__ ) ),
			'plugin_version'              => '1.2.2',
			'plugin_slug'                 => 'appscenic',
			'auth_url'                    => $auth_base_url . '#quick-start/createWooCom',
			'webhook_product_updated_url' => $webhook_base_url . '/woo-store/image/updated',
			'webhook_store_connected_url' => $webhook_base_url . '/woo-store/store/connected',
			'webhook_store_updated_url'   => $webhook_base_url . '/woo-store/store/updated',
			'check_store_connection'      => true,
			'options'                     => get_option( 'appscenic_option' ) ?: [],
		];

		return $config[$key];

	}

}
