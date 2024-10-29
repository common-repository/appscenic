<?php

namespace AppScenic\AsyncRequests;

class DeleteImageProcess extends BackgroundProcess {

	protected $action = 'delete_image';

	public function task( $item ) {

		wp_delete_attachment( $item, true );

		return false;

	}

}
