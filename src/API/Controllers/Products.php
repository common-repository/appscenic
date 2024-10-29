<?php

namespace AppScenic\API\Controllers;

use AppScenic\Config;
use WC_Product;
use WC_REST_Products_Controller;

class Products extends WC_REST_Products_Controller {

	protected $namespace = 'wc/v3/appscenic/v1';

	protected function set_product_images( $product, $images ): WC_Product {

		$images = is_array( $images ) ? array_filter( $images ) : [];

		$this->maybe_delete_images( $product, $images );

		// Return early if no images
		if ( empty( $images ) ) {

			$product->set_image_id();
			$product->set_gallery_image_ids( [] );

			return $product;

		}

		if ( ! $product->get_id() ) {
			// Save product to get an id
			$product->save();
		}

		$importer = apply_filters( 'appscenic_import_product_image', null );

		$is_queue_empty = true;
		$images_count   = count( $images );

		foreach ( $images as $index => $image ) {

			$args = [
				'index'      => $index,
				'product_id' => $product->get_id(),
				'image'      => $image,
				'total'      => $images_count,
			];

			$attachment_id = isset( $image['id'] ) ? absint( $image['id'] ) : 0;

			if ( $attachment_id === 0 ) {
				// Check if file already exists
				$attachment_id = $importer->get_attachment_id_by_file_name( $image['src'] );
			}

			if ( $attachment_id === 0 ) {

				$importer->push_to_queue( $args );

				if ( $is_queue_empty ) {
					$is_queue_empty = false;
				}

			} else {
				$importer->attach_image( $index, $product, $image, $attachment_id, $images_count );
			}

		}

		if ( $is_queue_empty ) {
			return $product;
		}

		$importer->save()->dispatch();

		return $product;

	}

	/**
	 * Deletes product images if not used by the child variations and any of the following are true:
	 * 1. $images is an empty array
	 * 2. The existing attachment IDs are not in the $images array
	 *
	 * It also deletes them only if they come from AppScenic (appscenic_id meta is set)
	 *
	 * @param $product
	 * @param $images
	 *
	 * @return void
	 */
	function maybe_delete_images( $product, $images ) {

		if ( ! isset( Config::get( 'options' )['image_cleanup'] ) ) {
			return;
		}

		$importer             = apply_filters( 'appscenic_import_product_image', null );
		$delete_image_process = apply_filters( 'appscenic_delete_image', null );

		$image_id          = $product->get_image_id();
		$gallery_image_ids = $product->get_gallery_image_ids();
		$variations        = false;

		if ( $product->get_type() === 'variable' ) {
			$variations = $product->get_available_variations( 'objects' );
		}

		$variation_image_ids = [];

		if ( $variations ) {

			foreach ( $variations as $variation ) {

				// Pass "edit" as the context argument to not get the parent image id
				if ( $variation_image_id = $variation->get_image_id( 'edit' ) ) {
					$variation_image_ids[] = $variation_image_id;
				}

			}

		}

		if ( empty( $images ) ) {

			if ( ! in_array( $image_id, $variation_image_ids ) && get_post_meta( $image_id, 'appscenic_id', true ) ) {
				$delete_image_process->push_to_queue( $image_id );
			}

			foreach ( $gallery_image_ids as $gallery_image_id ) {

				if ( ! in_array( $gallery_image_id, $variation_image_ids ) && get_post_meta( $gallery_image_id, 'appscenic_id', true ) ) {
					$delete_image_process->push_to_queue( $gallery_image_id );
				}

			}

		} else {

			$existing_image_ids = array_merge( [$image_id], $gallery_image_ids );

			$new_image_ids = [];

			foreach ( $images as $image ) {

				if ( isset( $image['id'] ) ) {

					$new_image_ids[] = $image['id'];

				} else if ( isset( $image['src'] ) ) {

					$attachment_id = $importer->get_attachment_id_by_file_name( $image['src'] );

					if ( $attachment_id ) {
						$new_image_ids[] = $attachment_id;
					}

				}

			}

			foreach ( $existing_image_ids as $existing_image_id ) {

				if ( ! in_array( $existing_image_id, $new_image_ids ) ) {

					if ( ! in_array( $existing_image_id, $variation_image_ids ) && get_post_meta( $existing_image_id, 'appscenic_id', true ) ) {
						$delete_image_process->push_to_queue( $existing_image_id );
					}

				}

			}

		}

		$delete_image_process->save()->dispatch();

	}

}
