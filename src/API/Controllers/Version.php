<?php

namespace AppScenic\API\Controllers;

use AppScenic\Config;
use WC_REST_Controller;
use WP_Error;
use WP_REST_Server;

class Version extends WC_REST_Controller {

	protected $namespace = 'wc/v3/appscenic/v1';
	protected $rest_base = 'version';

	public function register_routes() {

		register_rest_route(
			$this->namespace,
			'/' . $this->rest_base,
			[
				[
					'methods'             => WP_REST_Server::READABLE,
					'callback'            => [$this, 'get_items'],
					'permission_callback' => [$this, 'get_items_permissions_check'],
				],
			]
		);

	}

	public function get_items( $request ): array {

		return [
			'version' => Config::get( 'plugin_version' ),
		];

	}

	public function get_items_permissions_check( $request ) {

		if ( ! wc_rest_check_post_permissions( 'product' ) ) {
			return new WP_Error( 'woocommerce_rest_cannot_view', __( 'Sorry, you cannot list resources.', 'woocommerce' ), ['status' => rest_authorization_required_code()] );
		}

		return true;

	}

}
