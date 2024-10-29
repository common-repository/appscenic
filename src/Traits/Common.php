<?php

namespace AppScenic\Traits;

use AppScenic\Config;

trait Common {

	protected function is_settings_page(): bool {

		global $pagenow;

		return $pagenow === 'admin.php' && isset( $_GET['page'] ) && $_GET['page'] === Config::get( 'plugin_slug' );

	}

}
