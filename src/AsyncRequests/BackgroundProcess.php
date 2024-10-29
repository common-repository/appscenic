<?php

namespace AppScenic\AsyncRequests;

use AppScenic\AsyncRequests\Library\WP_Background_Process;

abstract class BackgroundProcess extends WP_Background_Process {

	protected $prefix = 'appscenic';

	public function maybe_handle() {

		// Don't lock up other requests while processing.
		session_write_close();

		if ( $this->is_processing() ) {
			// Background process already running.
			return $this->maybe_wp_die();
		}

		if ( $this->is_cancelled() ) {
			$this->clear_scheduled_event();
			$this->delete_all();

			return $this->maybe_wp_die();
		}

		if ( $this->is_paused() ) {
			$this->clear_scheduled_event();
			$this->paused();

			return $this->maybe_wp_die();
		}

		if ( $this->is_queue_empty() ) {
			// No data to process.
			return $this->maybe_wp_die();
		}

		$this->handle();

		return $this->maybe_wp_die();

	}

	public function get_prefix() {
		return $this->prefix;
	}

	public function get_action() {
		return $this->action;
	}

}
