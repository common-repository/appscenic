<?php

namespace AppScenic\Admin;

use AppScenic\Admin\Utils\Field;
use AppScenic\Admin\Utils\Section;
use AppScenic\Config;
use AppScenic\Traits\Common;
use AppScenic\Traits\Webhook;

class Settings {

	use Common;
	use Webhook;

	private $is_connected = null;

    public function register() {

		// Set is_connected to a boolean value so that we don't make requests during development
		if ( ! Config::get( 'check_store_connection' ) ) {
			$this->is_connected = false;
		}

        add_action( 'admin_menu', [$this, 'add_admin_menu'] );
	    add_action( 'admin_init', [$this, 'register_settings'] );
		add_filter( 'plugin_action_links_appscenic/appscenic.php', [$this, 'settings_link'] );
		add_action( 'admin_enqueue_scripts', [$this, 'enqueue_assets' ] );
	    add_filter( 'heartbeat_received', [$this, 'heartbeat_received'], 10, 2 );

    }

    public function add_admin_menu() {

		$svg = file_get_contents( Config::get( 'plugin_path' ) . '/assets/dist/svg/logo-symbol.svg' );

        add_menu_page( 'AppScenic', 'AppScenic', 'manage_options', Config::get( 'plugin_slug' ), [$this, 'load_template'], 'data:image/svg+xml;base64,' . base64_encode( $svg ) );

    }

    public function load_template() {

        include Config::get( 'plugin_path' ) . 'views/admin/pages/settings.php';

    }

	public function register_settings() {

		register_setting( Config::get( 'plugin_slug' ) . '_options', Config::get( 'plugin_slug' ) . '_option' );

		$sections = [
			[
				'id'       => Config::get( 'plugin_slug' ) . '_settings',
				'title'    => esc_html__( 'Settings', 'appscenic' ),
				'template' => 'settings',
				'page'     => Config::get( 'plugin_slug' ),
				'args'     => [],
			],
		];

		foreach ( $sections as $args ) {
			new Section( $args );
		}

		$fields = [
			[
				'id'          => 'image_cleanup',
				'title'       => esc_html__( 'Image cleanup', 'appscenic' ),
				'type'        => 'checkbox',
				'page'        => Config::get( 'plugin_slug' ),
				'section'     => Config::get( 'plugin_slug' ) . '_settings',
				'args'        => [],
				'option_name' => Config::get( 'plugin_slug' ) . '_option',
				'description' => esc_html__( 'Delete product image files on product update or delete. If you use the product images on other pages, leave this unchecked.', 'appscenic' ),
			],
		];

		foreach ( $fields as $args ) {
			new Field( $args );
		}

	}

	public function settings_link( $links ) {

		array_unshift( $links, '<a href="' . esc_url( admin_url( 'admin.php?page=appscenic' ) ) . '">Settings</a>' );

		return $links;

	}

	public function is_connected(): bool {

		if ( is_bool( $this->is_connected ) ) {
			return $this->is_connected;
		}

		$http_args = $this->get_default_http_args();

		$http_args['timeout'] = 5;

		$http_args['body'] = trim( wp_json_encode( [
			'store_url'           => home_url(),
			'integration_version' => Config::get( 'plugin_version' ),
		] ) );

		$http_args['headers']['X-WC-Webhook-Source']    = home_url( '/' );
		$http_args['headers']['X-WC-Webhook-Signature'] = $this->generate_signature( $http_args['body'] );

		$response = wp_safe_remote_post( Config::get( 'webhook_store_connected_url' ), $http_args );

		if ( is_wp_error( $response ) || $response['response']['code'] > 202 ) {

			if ( is_wp_error( $response ) ) {

				add_action( 'appscenic_admin_notices', function() use ( $response ) {

					wp_admin_notice( esc_html( $response->get_error_message() ), [
						'type' => 'error',
					] );

				} );

			}

			$this->is_connected = false;

		} else {

			$this->is_connected = true;

		}

		return $this->is_connected;

	}

	public function enqueue_assets() {

		if ( ! $this->is_settings_page() ) {
			return;
		}

		$handle    = Config::get( 'plugin_slug' ) . '-admin';
		$css_path  = 'assets/dist/css/admin.css';
		$js_path   = 'assets/dist/js/admin.js';
		$css_mtime = filemtime( Config::get( 'plugin_path' ) . $css_path );
		$js_mtime  = filemtime( Config::get( 'plugin_path' ) . $js_path );

		wp_register_style( $handle, Config::get( 'plugin_url' ) . $css_path, [], $css_mtime, 'screen' );
		wp_register_script( $handle, Config::get( 'plugin_url' ) . $js_path, [], $js_mtime, true );

		wp_enqueue_style( $handle );
		wp_enqueue_script( $handle );

	}

	public function heartbeat_received( $response, $data ) {

		if ( empty( $data['appscenic_is_connected'] ) ) {
			return $response;
		}

		ob_start();

		if ( $this->is_connected() ) {
			include Config::get( 'plugin_path' ) . '/views/admin/parts/store-connected.php';
		} else {
			include Config::get( 'plugin_path' ) . '/views/admin/parts/store-disconnected.php';
		}

		$html = ob_get_clean();

		$response['appscenic_is_connected_html'] = $html;

		return $response;

	}

}
