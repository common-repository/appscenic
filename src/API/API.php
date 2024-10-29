<?php

namespace AppScenic\API;

use AppScenic\API\Controllers\Products;
use AppScenic\API\Controllers\ProductVariations;
use AppScenic\API\Controllers\Version;
use AppScenic\API\Controllers\Webhook;

class API {

	private $namespaces = [
		'products'           => Products::class,
		'product-variations' => ProductVariations::class,
		'version'            => Version::class,
		'webhook'            => Webhook::class,
	];

	public function register() {
		add_filter( 'woocommerce_rest_api_get_rest_namespaces', [$this, 'register_namespaces'] );
	}

	public function register_namespaces( $namespaces ): array {

		foreach ( $this->namespaces as $key => $class ) {
			$namespaces['wc/v3/appscenic/v1'][$key] = $class;
		}

		return $namespaces;

	}

}
