<?php

namespace AppScenic;

use WC_Data;
use WP_Error;
use WP_REST_Request;

class Misc {

	public function register() {

		add_action( 'woocommerce_order_details_after_order_table', [$this, 'show_order_tracking_number'] );
		add_action( 'woocommerce_email_order_meta', [$this, 'show_order_tracking_number'] );
		add_filter( 'woocommerce_rest_allowed_image_mime_types', [$this, 'add_additional_wc_mime_types'], 1 );
		add_filter( 'woocommerce_rest_pre_insert_product_object', [$this, 'product_mark_as_draft'], 10, 3 );
		remove_action( 'shutdown', 'wc_webhook_execute_queue' );
		add_action( 'shutdown', [$this, 'add_webhooks_to_queue'] );
		add_action( 'woocommerce_rest_delete_product_object', [$this, 'delete_product_attachments'], 10, 3 );
		add_action( 'woocommerce_rest_delete_product_variation_object', [$this, 'delete_variation_attachment'], 10, 3 );

	}

	public function show_order_tracking_number( $order ) {

		if ( ! $tracking_number = $order->get_meta( 'Tracking Number' ) ) {
			return;
		}

		$array = array_map( 'trim', explode( ',', $tracking_number ) );
		$count = count( $array );

		echo '<p class="appscenic-order-tracking-number"><strong>' . esc_html( _n( 'Tracking number', 'Tracking numbers', $count, 'appscenic' ) ) . ':</strong> ' . esc_html( implode( ', ', $array ) ) . '</p>';

	}

	function add_additional_wc_mime_types( $mime_types ) {

		$mime_types['webp'] = 'image/webp';

		return $mime_types;

	}

	/**
	 * This runs on product creation and update after AppScenic\API\Controllers\Products::set_product_images
	 *
	 * @param WC_Data|WP_Error $product
	 * @param WP_REST_Request $request
	 * @param bool $creating
	 *
	 * @return WC_Data|WP_Error
	 */
	public function product_mark_as_draft( $product, WP_REST_Request $request, bool $creating ) {

		if ( is_wp_error( $product ) ) {
			return $product;
		}

		if ( $creating ) {
			$product->set_status( 'draft' );
		}

		return $product;

	}

	public function add_webhooks_to_queue() {

		global $wc_queued_webhooks;

		if ( empty( $wc_queued_webhooks ) ) {
			return;
		}

		$exporter = apply_filters( 'appscenic_export_webhook', null );

		foreach ( $wc_queued_webhooks as $data ) {

			if ( apply_filters( 'woocommerce_webhook_deliver_async', true, $data['webhook'], $data['arg'] ) ) {

				$queue_args = array(
					'webhook_id' => $data['webhook']->get_id(),
					'arg'        => $data['arg'],
				);

				$exporter->push_to_queue( $queue_args );

			} else {

				$data['webhook']->deliver( $data['arg'] );

			}

		}

		$exporter->save()->dispatch();

	}

	/**
	 * Deletes product images (including variation images) if the product is deleted
	 *
	 * It also deletes them only if they come from AppScenic (appscenic_id meta is set)
	 *
	 * @param $object
	 * @param $response
	 * @param $request
	 *
	 * @return void
	 */
	public function delete_product_attachments( $object, $response, $request ) {

		if ( ! isset( Config::get( 'options' )['image_cleanup'] ) ) {
			return;
		}

		$delete_image_process = apply_filters( 'appscenic_delete_image', null );

		$image_id          = $object->get_image_id();
		$gallery_image_ids = $object->get_gallery_image_ids();
		$children_ids      = $object->get_children();

		if ( get_post_meta( $image_id, 'appscenic_id', true ) ) {
			$delete_image_process->push_to_queue( $image_id );
		}

		foreach ( $gallery_image_ids as $gallery_image_id ) {

			if ( get_post_meta( $gallery_image_id, 'appscenic_id', true ) ) {
				$delete_image_process->push_to_queue( $gallery_image_id );
			}

		}

		if ( $children_ids ) {

			foreach ( $children_ids as $child_id ) {

				$variation          = wc_get_product( $child_id );
				$variation_image_id = $variation->get_image_id();

				if ( $variation_image_id ) {

					if ( get_post_meta( $variation_image_id, 'appscenic_id', true ) ) {
						$delete_image_process->push_to_queue( $variation_image_id );
					}

				}

			}

		}

		$delete_image_process->save()->dispatch();

	}

	/**
	 * Deletes the variation image if it's not being used by the parent product and the variation is deleted
	 *
	 * It also deletes them only if they come from AppScenic (appscenic_id meta is set)
	 *
	 * @param $object
	 * @param $response
	 * @param $request
	 *
	 * @return void
	 */
	public function delete_variation_attachment( $object, $response, $request ) {

		if ( ! isset( Config::get( 'options' )['image_cleanup'] ) ) {
			return;
		}

		$delete_image_process = apply_filters( 'appscenic_delete_image', null );

		$image_id         = $object->get_image_id();
		$parent           = wc_get_product( $object->get_parent_id() );
		$parent_image_ids = array_merge( [$parent->get_image_id()], $parent->get_gallery_image_ids() );

		// Don't delete the image if it's used by the parent product
		if ( in_array( $image_id, $parent_image_ids ) ) {
			return;
		}

		if ( get_post_meta( $image_id, 'appscenic_id', true ) ) {
			$delete_image_process->push_to_queue( $image_id );
		}

		$delete_image_process->save()->dispatch();

	}

}
