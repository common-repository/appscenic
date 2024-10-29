<?php

namespace AppScenic\API\Controllers;

use WC_REST_Controller;
use WP_Error;
use WP_REST_Server;

class Webhook extends WC_REST_Controller {

	protected $namespace = 'wc/v3/appscenic/v1';
	protected $rest_base = 'webhook';

	public function register_routes() {

		register_rest_route(
			$this->namespace,
			'/' . $this->rest_base,
			[
				[
					'methods'             => WP_REST_Server::CREATABLE,
					'callback'            => [$this, 'create_item'],
					'permission_callback' => [$this, 'create_item_permissions_check'],
				],
			]
		);

	}

	public function create_item( $request ) {

		if ( ! isset( $request['secret'] ) ) {
			return new WP_Error( 'appscenic_rest_webhook_missing_secret', __( 'Secret is missing.', 'appscenic' ), ['status' => 400] );
		}

		update_option( 'appscenic_webhook_secret', $request['secret'], false );

		return [];

	}

	public function create_item_permissions_check( $request ) {

		if ( ! wc_rest_check_manager_permissions( 'webhooks', 'create' ) ) {
			return new WP_Error( 'woocommerce_rest_cannot_create', __( 'Sorry, you are not allowed to create resources.', 'woocommerce' ), ['status' => rest_authorization_required_code()] );
		}

		return true;

	}

}
