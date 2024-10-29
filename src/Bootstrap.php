<?php

namespace AppScenic;

use AppScenic\Admin\Settings;
use AppScenic\API\API;
use AppScenic\AsyncRequests\DeleteImageProcess;
use AppScenic\Auth\OAuth;
use AppScenic\Export\ImageDataExporter;
use AppScenic\Export\WebhookExporter;
use AppScenic\Import\ProductImporter;
use AppScenic\Import\ProductVariationImporter;

class Bootstrap {

	private $services = [
		Settings::class,
		API::class,
		OAuth::class,
		Misc::class,
		Upgrade::class,
	];

	private $processes = [
		ProductImporter::class,
		ProductVariationImporter::class,
		ImageDataExporter::class,
		WebhookExporter::class,
		DeleteImageProcess::class,
	];

	public function register() {

	    add_action( 'woocommerce_loaded', [$this, 'load_services'] );

		$this->load_processes();

    }

	public function load_services() {

		foreach ( $this->services as $service ) {
			(new $service)->register();
		}

	}

	public function load_processes() {

		foreach ( $this->processes as $process ) {

			$instance = new $process;

			add_filter( $instance->get_prefix() . '_' . $instance->get_action(), function() use ( $instance ) {
				return $instance;
			} );

		}

	}

}
