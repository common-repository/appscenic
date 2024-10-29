<?php

namespace AppScenic\Import;

use WC_REST_Exception;

class ProductImporter extends Importer {

	protected $action = 'import_product_image';

	protected function task( $item ) {

		extract( $item );

		// Check if file already exists
		$attachment_id = $this->get_attachment_id_by_file_name( $image['src'] );

		$product = wc_get_product( $product_id );

		// If the product no longer exists when this task starts
		if ( ! $product ) {
			return false;
		}

		if ( $attachment_id === 0 ) {
			$this->import_image( $index, $product, $image, $total );
		} else {
			$this->attach_image( $index, $product, $image, $attachment_id, $total );
		}

		return false;

	}

	public function import_image( $index, $product, $image, $total ) {

		if ( ! $product ) {
			return;
		}

		try {

			if ( ! isset( $image['src'] ) ) {
				return;
			}

			$upload = wc_rest_upload_image_from_url( esc_url_raw( $image['src'] ) );

			if ( is_wp_error( $upload ) ) {

				if ( ! apply_filters( 'woocommerce_rest_suppress_image_upload_error', false, $upload, $product->get_id(), [$image] ) ) {
					throw new WC_REST_Exception( 'woocommerce_product_image_upload_error', $upload->get_error_message(), 400 );
				} else {
					return;
				}

			}

			$attachment_id = wc_rest_set_uploaded_image_as_attachment( $upload, $product->get_id() );

			if ( ! wp_attachment_is_image( $attachment_id ) ) {
				throw new WC_REST_Exception( 'woocommerce_product_invalid_image_id', sprintf( __( '#%s is an invalid image ID.', 'woocommerce' ), $attachment_id ), 400 );
			}

			$this->attach_image( $index, $product, $image, $attachment_id, $total );

		} catch ( WC_REST_Exception $e ) {

			error_log( print_r( $e->getMessage(), true ) );

		}

		$product->save();

	}

	public function attach_image( $index, $product, $image, $attachment_id, $total ) {

		if ( $index > 0 ) {
			// This bypasses the caching mechanism
			$gallery = explode( ',', get_post_meta( $product->get_id(), '_product_image_gallery', true ) );
		} else {
			$gallery = [];
		}

		if ( $index === 0 || ( isset( $image['position'] ) && $image['position'] === 0 ) ) {

			// Remove the image from the gallery, if it's there
			if ( in_array( $attachment_id, $gallery ) ) {
				unset( $gallery[ array_search( $attachment_id, $gallery ) ] );
			}

			// Set the image as featured
			$product->set_image_id( $attachment_id );

		} else {

			// Add the image to the gallery, in the correct position
			array_splice( $gallery, $index - 1, 0, $attachment_id );

		}

		$this->set_attachment_alt( $attachment_id, $image );
		$this->set_attachment_name( $attachment_id, $image );
		$this->set_attachment_meta( $attachment_id, $image );
		$this->set_remote_id( $attachment_id, $image );

		if ( $index > 0 ) {
			// This is the only way it works
			update_post_meta( $product->get_id(), '_product_image_gallery', implode( ',', array_filter( $gallery ) ) );
		}

		$gallery = explode( ',', get_post_meta( $product->get_id(), '_product_image_gallery', true ) );

		// Publish product if all images have been imported
		if ( $total === 1 && $product->get_image_id() ) {
			$product->set_status( 'publish' );
		} else if ( $total > 1 && $product->get_image_id() && is_countable( $gallery ) && count( $gallery ) === $total - 1 ) {
			$product->set_status( 'publish' );
		}

		$product->save();

		$this->trigger_image_data_export( $attachment_id );

	}

	private function trigger_image_data_export( $attachment_id ) {

		$payload = [
			'appscenic_id' => (int) get_post_meta( $attachment_id, 'appscenic_id', true ),
			'platform_id'  => $attachment_id,
		];

		$exporter = apply_filters( 'appscenic_export_image_data', null );

		$exporter->push_to_queue( $payload );
		$exporter->save()->dispatch();

	}

}
