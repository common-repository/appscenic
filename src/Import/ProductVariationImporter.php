<?php

namespace AppScenic\Import;

use WC_REST_Exception;

class ProductVariationImporter extends Importer {

	protected $action = 'import_variation_image';

	protected function task( $item ) {

		extract( $item );

		// Check if file already exists
		$attachment_id = $this->get_attachment_id_by_file_name( $image['src'] );

		$variation = wc_get_product( $variation_id );

		// If the variation no longer exists when this task starts
		if ( ! $variation ) {
			return false;
		}

		if ( $attachment_id === 0 ) {
			$this->import_image( $variation, $image );
		} else {
			$this->attach_image( $variation, $image, $attachment_id );
		}

		return false;

	}

	public function import_image( $variation, $image ) {

		if ( ! $variation ) {
			return;
		}

		try {

			if ( ! isset( $image['src'] ) ) {
				return;
			}

			$upload = wc_rest_upload_image_from_url( esc_url_raw( $image['src'] ) );

			if ( is_wp_error( $upload ) ) {

				if ( ! apply_filters( 'woocommerce_rest_suppress_image_upload_error', false, $upload, $variation->get_id(), array( $image ) ) ) {
					throw new WC_REST_Exception( 'woocommerce_variation_image_upload_error', $upload->get_error_message(), 400 );
				}

			}

			$attachment_id = wc_rest_set_uploaded_image_as_attachment( $upload, $variation->get_id() );

			if ( ! wp_attachment_is_image( $attachment_id ) ) {
				throw new WC_REST_Exception( 'woocommerce_variation_invalid_image_id', sprintf( __( '#%s is an invalid image ID.', 'woocommerce' ), $attachment_id ), 400 );
			}

			$this->attach_image( $variation, $image, $attachment_id );

		} catch ( WC_REST_Exception $e ) {

			error_log( print_r( $e->getMessage(), true ) );

		}

		$variation->save();

	}

	public function attach_image( $variation, $image, $attachment_id ) {

		$variation->set_image_id( $attachment_id );

		$this->set_attachment_alt( $attachment_id, $image );
		$this->set_attachment_name( $attachment_id, $image );
		$this->set_attachment_meta( $attachment_id, $image );
		$this->set_remote_id( $attachment_id, $image );

	}

}
