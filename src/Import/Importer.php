<?php

namespace AppScenic\Import;

use AppScenic\AsyncRequests\BackgroundProcess;

abstract class Importer extends BackgroundProcess {

	public function get_attachment_id_by_file_name( $url ) {

		global $wpdb;

		$identifier = $this->get_file_name( $url ) . $this->get_query_param( $url );

		$results = $wpdb->get_results(
			$wpdb->prepare(
				"SELECT post_id
				FROM $wpdb->postmeta
				WHERE meta_key = 'appscenic_attachment_identifier'
				AND meta_value = %s
				LIMIT 1",
				$identifier
			),
			ARRAY_A
		);

		if ( ! $results || ! isset( $results[0] ) || ! isset( $results[0]['post_id'] ) ) {
			return 0;
		}

		$attachment_id = (int) $results[0]['post_id'];

		if ( $identifier !== get_post_meta( $attachment_id, 'appscenic_attachment_identifier', true ) ) {
			return 0;
		}

		return $attachment_id;

	}

	protected function set_attachment_alt( $attachment_id, $image ) {

		if ( ! empty( $image['alt'] ) ) {
			update_post_meta( $attachment_id, '_wp_attachment_image_alt', sanitize_text_field( $image['alt'] ) );
		}

	}

	protected function set_attachment_name( $attachment_id, $image ) {

		if ( ! empty( $image['name'] ) ) {

			wp_update_post( [
				'ID'         => $attachment_id,
				'post_title' => sanitize_text_field( $image['name'] ),
			] );

		}

	}

	protected function set_remote_id( $attachment_id, $image ) {

		if ( ! isset( $image['appscenic_id'] ) || ! is_numeric( $image['appscenic_id'] ) ) {
			return;
		}

		update_post_meta( $attachment_id, 'appscenic_id', sanitize_text_field( $image['appscenic_id'] ) );

	}

	protected function set_attachment_meta( $attachment_id, $image ) {

		$identifier = $this->get_file_name( $image['src'] ) . $this->get_query_param( $image['src'] );

		update_post_meta( $attachment_id, 'appscenic_attachment_identifier', $identifier );

	}

	protected function get_file_name( $url ) {

		return sanitize_text_field( basename( parse_url( $url, PHP_URL_PATH ) ) );

	}

	protected function get_query_param( $url ) {

		$url_query = parse_url( $url, PHP_URL_QUERY );

		if ( ! $url_query ) {
			return '';
		}

		$query_params = [];

		foreach ( explode( '&', $url_query ) as $str ) {
			$arr = explode( '=', $str );
			$query_params[ $arr[0] ] = $arr[1];
		}

		if ( ! isset( $query_params['h'] ) ) {
			return '';
		}

		return sanitize_text_field( $query_params['h'] );

	}

}
