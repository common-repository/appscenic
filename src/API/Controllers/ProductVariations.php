<?php

namespace AppScenic\API\Controllers;

use AppScenic\Config;
use WC_Product_Variation;
use WC_REST_Product_Variations_Controller;

class ProductVariations extends WC_REST_Product_Variations_Controller {

	protected $namespace = 'wc/v3/appscenic/v1';

	protected function set_variation_image( $variation, $image ): WC_Product_Variation {

		$this->maybe_delete_image( $variation, $image );

		if ( empty( $image ) ) {

			$variation->set_image_id();

			return $variation;

		}

		if ( ! $variation->get_id() ) {
			// Save variation to get an id
			$variation->save();
		}

		$importer = apply_filters( 'appscenic_import_variation_image', null );

		$args = [
			'variation_id' => $variation->get_id(),
			'image' => $image,
		];

		$attachment_id = isset( $image['id'] ) ? absint( $image['id'] ) : 0;

		if ( $attachment_id === 0 ) {
			// Check if file already exists
			$attachment_id = $importer->get_attachment_id_by_file_name( $image['src'] );
		}

		if ( $attachment_id === 0 ) {

			$importer->push_to_queue( $args );
			$importer->save()->dispatch();

		} else {

			$importer->attach_image( $variation, $image, $attachment_id );

		}

		return $variation;

	}

	/**
	 * Deletes the variation image if it's not being used by the parent product and if $image provides a new image (provides a URL not ID) or none
	 *
	 * It also deletes them only if they come from AppScenic (appscenic_id meta is set)
	 *
	 * Note: if "image" is not set in the request body then this function doesn't run
	 *
	 * @param $variation
	 * @param $image
	 *
	 * @return void
	 */
	function maybe_delete_image( $variation, $image ) {

		if ( ! isset( Config::get( 'options' )['image_cleanup'] ) ) {
			return;
		}

		$importer             = apply_filters( 'appscenic_import_variation_image', null );
		$delete_image_process = apply_filters( 'appscenic_delete_image', null );

		$image_id         = $variation->get_image_id();
		$parent           = wc_get_product( $variation->get_parent_id() );
		$parent_image_ids = array_merge( [$parent->get_image_id()], $parent->get_gallery_image_ids() );

		// Don't delete the image if it's used by the parent product
		if ( in_array( $image_id, $parent_image_ids ) ) {
			return;
		}

		if ( is_array( $image ) ) {

			if ( isset( $image['id'] ) && $image['id'] === $image_id ) {
				return;
			}

			if ( isset( $image['src'] ) ) {

				$attachment_id = $importer->get_attachment_id_by_file_name( $image['src'] );

				if ( $attachment_id === $image_id ) {
					return;
				}

			}

		}

		if ( get_post_meta( $image_id, 'appscenic_id', true ) ) {

			$delete_image_process->push_to_queue( $image_id );
			$delete_image_process->save()->dispatch();

		}

	}

}
