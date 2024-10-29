<?php

namespace AppScenic\Export;

use WC_Webhook;

class WebhookExporter extends Exporter {

	protected $action = 'export_webhook';

	protected function task( $item ) {

		extract( $item );

		$webhook = new WC_Webhook( $webhook_id );
		$webhook->deliver( $arg );

		return false;

	}

}
