<?php
/**
 * Plugin Name:       Carmo Category Rundown
 * Plugin URI:        https://github.com/carmopereira/carmo-category-rundown
 * Description:       Adds a WooCommerce product category carousel block: image cards with the category name, and the description plus a "see all" button on hover.
 * Version:           0.1.10
 * Requires at least: 6.9
 * Requires PHP:      8.2
 * Requires Plugins:  woocommerce
 * WC requires at least: 8.3
 * WC tested up to:   9.0
 * Author:            João Carmo Pereira
 * Author URI:        https://profiles.wordpress.org/carmopereira/
 * License:           GPLv2 or later
 * License URI:       https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain:       carmo-category-rundown
 * Domain Path:       /languages
 *
 * @package CarmoCategoryRundown
 */

declare(strict_types=1);

defined( 'ABSPATH' ) || exit;

/**
 * Registers the block(s) from the `blocks-manifest.php` metadata collection.
 *
 * @see https://make.wordpress.org/core/2025/03/13/more-efficient-block-type-registration-in-6-8/
 */
function create_block_carmo_category_rundown_block_init() {
	// Translations shipped in /languages (WordPress.org language packs in
	// wp-content/languages/plugins still take precedence).
	load_plugin_textdomain( 'carmo-category-rundown', false, dirname( plugin_basename( __FILE__ ) ) . '/languages' );

	wp_register_block_types_from_metadata_collection( __DIR__ . '/build', __DIR__ . '/build/blocks-manifest.php' );

	// Core registers the editor script's translations for language packs
	// only; point it at /languages too, so JSON files shipped with the
	// plugin are found.
	wp_set_script_translations(
		generate_block_asset_handle( 'create-block/carmo-category-rundown', 'editorScript' ),
		'carmo-category-rundown',
		__DIR__ . '/languages'
	);
}
add_action( 'init', 'create_block_carmo_category_rundown_block_init' );

/**
 * Declares compatibility with WooCommerce features.
 *
 * The block only reads product categories, so it is compatible with
 * High-Performance Order Storage and the cart/checkout blocks.
 */
function carmo_category_rundown_declare_wc_compatibility() {
	if ( ! class_exists( \Automattic\WooCommerce\Utilities\FeaturesUtil::class ) ) {
		return;
	}

	\Automattic\WooCommerce\Utilities\FeaturesUtil::declare_compatibility( 'custom_order_tables', __FILE__, true );
	\Automattic\WooCommerce\Utilities\FeaturesUtil::declare_compatibility( 'cart_checkout_blocks', __FILE__, true );
}
add_action( 'before_woocommerce_init', 'carmo_category_rundown_declare_wc_compatibility' );

/**
 * Option holding the fallback image cache "generation". Bumping it makes every
 * cached fallback stale at once; the old transients simply expire.
 */
const CARMO_CATEGORY_RUNDOWN_CACHE_OPTION = 'carmo_category_rundown_fallback_gen';

/**
 * How long a fallback image choice is cached when nothing invalidates it.
 */
const CARMO_CATEGORY_RUNDOWN_CACHE_TTL = WEEK_IN_SECONDS;

/**
 * Image for a category that has no thumbnail of its own: the featured image
 * of a product in that category (subcategories included).
 *
 * Only visible, published products with a featured image count; out-of-stock
 * ones are skipped when the store hides them. The pick is the product first in
 * WooCommerce's own order (menu order, then newest), so it stays stable until
 * the catalogue changes.
 *
 * The result (including "none found") is cached per category in a transient,
 * so the query runs once per category until a product or category changes.
 *
 * @param int $term_id Product category ID.
 * @return int Attachment ID, or 0 when no product in the category has an image.
 */
function carmo_category_rundown_get_fallback_image_id( int $term_id ): int {
	if ( $term_id <= 0 ) {
		return 0;
	}

	$generation = (int) get_option( CARMO_CATEGORY_RUNDOWN_CACHE_OPTION, 1 );
	$cache_key  = sprintf( 'carmo_rundown_img_%d_%d', $generation, $term_id );
	$cached     = get_transient( $cache_key );

	if ( false !== $cached ) {
		return (int) $cached;
	}

	$tax_query = array(
		array(
			'taxonomy'         => 'product_cat',
			'field'            => 'term_id',
			'terms'            => array( $term_id ),
			'include_children' => true,
		),
	);

	// Leave out products hidden from the catalogue (and out-of-stock ones when
	// the store hides those), as the shop itself does.
	if ( function_exists( 'wc_get_product_visibility_term_ids' ) ) {
		$visibility_terms = wc_get_product_visibility_term_ids();
		$excluded_terms   = array();

		if ( ! empty( $visibility_terms['exclude-from-catalog'] ) ) {
			$excluded_terms[] = (int) $visibility_terms['exclude-from-catalog'];
		}
		if ( 'yes' === get_option( 'woocommerce_hide_out_of_stock_items' ) && ! empty( $visibility_terms['outofstock'] ) ) {
			$excluded_terms[] = (int) $visibility_terms['outofstock'];
		}

		if ( $excluded_terms ) {
			$tax_query[] = array(
				'taxonomy' => 'product_visibility',
				'field'    => 'term_taxonomy_id',
				'terms'    => $excluded_terms,
				'operator' => 'NOT IN',
			);
		}
	}

	$product_ids = get_posts(
		array(
			'post_type'              => 'product',
			'post_status'            => 'publish',
			'posts_per_page'         => 1,
			'fields'                 => 'ids',
			'orderby'                => array(
				'menu_order' => 'ASC',
				'date'       => 'DESC',
			),
			'tax_query'              => $tax_query, // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_tax_query
			'meta_query'             => array( // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_query
				array(
					'key'     => '_thumbnail_id',
					'compare' => 'EXISTS',
				),
			),
			'no_found_rows'          => true,
			'ignore_sticky_posts'    => true,
			'update_post_term_cache' => false,
			'suppress_filters'       => true,
		)
	);

	$image_id = $product_ids ? (int) get_post_thumbnail_id( (int) $product_ids[0] ) : 0;

	set_transient( $cache_key, $image_id, CARMO_CATEGORY_RUNDOWN_CACHE_TTL );

	return $image_id;
}

/**
 * Makes every cached fallback image stale. Called whenever a product, its
 * categories, its image or its visibility may have changed.
 */
function carmo_category_rundown_flush_fallback_images() {
	static $flushed = false;

	// Saving one product fires several of the hooks below: bump once.
	if ( $flushed ) {
		return;
	}
	$flushed = true;

	update_option(
		CARMO_CATEGORY_RUNDOWN_CACHE_OPTION,
		(int) get_option( CARMO_CATEGORY_RUNDOWN_CACHE_OPTION, 1 ) + 1,
		true
	);
}

/**
 * Flushes when a product is saved, trashed, restored or deleted.
 *
 * @param int $post_id Post ID.
 */
function carmo_category_rundown_maybe_flush_on_post( $post_id ) {
	if ( 'product' === get_post_type( $post_id ) ) {
		carmo_category_rundown_flush_fallback_images();
	}
}
add_action( 'save_post_product', 'carmo_category_rundown_flush_fallback_images' );
add_action( 'before_delete_post', 'carmo_category_rundown_maybe_flush_on_post' );
add_action( 'trashed_post', 'carmo_category_rundown_maybe_flush_on_post' );
add_action( 'untrashed_post', 'carmo_category_rundown_maybe_flush_on_post' );

/**
 * Flushes when a product's categories or visibility change (also covers
 * quick edit, bulk edit and imports that set terms directly).
 *
 * @param int    $object_id Object ID.
 * @param array  $terms     Terms.
 * @param array  $tt_ids    Term taxonomy IDs.
 * @param string $taxonomy  Taxonomy slug.
 */
function carmo_category_rundown_maybe_flush_on_terms( $object_id, $terms, $tt_ids, $taxonomy ) {
	if ( in_array( $taxonomy, array( 'product_cat', 'product_visibility' ), true ) ) {
		carmo_category_rundown_maybe_flush_on_post( $object_id );
	}
}
add_action( 'set_object_terms', 'carmo_category_rundown_maybe_flush_on_terms', 10, 4 );

/**
 * Flushes when a product's featured image is set, changed or removed.
 *
 * @param int|array $meta_ids  Meta ID(s).
 * @param int       $object_id Post ID.
 * @param string    $meta_key  Meta key.
 */
function carmo_category_rundown_maybe_flush_on_meta( $meta_ids, $object_id, $meta_key ) {
	if ( '_thumbnail_id' === $meta_key ) {
		carmo_category_rundown_maybe_flush_on_post( $object_id );
	}
}
add_action( 'added_post_meta', 'carmo_category_rundown_maybe_flush_on_meta', 10, 3 );
add_action( 'updated_post_meta', 'carmo_category_rundown_maybe_flush_on_meta', 10, 3 );
add_action( 'deleted_post_meta', 'carmo_category_rundown_maybe_flush_on_meta', 10, 3 );

// Category tree changes (a subcategory moved, deleted, ...).
add_action( 'edited_product_cat', 'carmo_category_rundown_flush_fallback_images' );
add_action( 'delete_product_cat', 'carmo_category_rundown_flush_fallback_images' );

/**
 * Flushes on stock status changes, which only matter when the store hides
 * out-of-stock products (otherwise every sale would needlessly flush).
 */
function carmo_category_rundown_maybe_flush_on_stock() {
	if ( 'yes' === get_option( 'woocommerce_hide_out_of_stock_items' ) ) {
		carmo_category_rundown_flush_fallback_images();
	}
}
add_action( 'woocommerce_product_set_stock_status', 'carmo_category_rundown_maybe_flush_on_stock' );
add_action( 'woocommerce_variation_set_stock_status', 'carmo_category_rundown_maybe_flush_on_stock' );
