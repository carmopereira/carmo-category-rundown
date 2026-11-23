<?php
/**
 * Server-side render of the category carousel.
 *
 * The following variables are exposed to the file:
 *     $attributes (array): The block attributes.
 *     $content (string): The block default content.
 *     $block (WP_Block): The block instance.
 *
 * Each card is one link to the category: its thumbnail as a full-bleed
 * background and its name in a pill. On desktop with a mouse, hovering the
 * card swaps the pill for the name in large type, the category description and
 * a "see all" button (a styled span, since the whole card is already a link).
 *
 * @package CarmoCategoryRundown
 */

defined( 'ABSPATH' ) || exit;

if ( ! taxonomy_exists( 'product_cat' ) ) {
	if ( current_user_can( 'activate_plugins' ) ) {
		echo '<p>' . esc_html__( 'WooCommerce must be active to show this category carousel.', 'carmo-category-rundown' ) . '</p>';
	}
	return;
}

$is_editor_preview = defined( 'REST_REQUEST' ) && REST_REQUEST;

$allowed_sources = array( 'top', 'children', 'handpicked' );
$source          = isset( $attributes['source'] ) && in_array( $attributes['source'], $allowed_sources, true ) ? $attributes['source'] : 'top';

$parent_id          = isset( $attributes['parentId'] ) ? absint( $attributes['parentId'] ) : 0;
$categories_to_show = isset( $attributes['categoriesToShow'] ) ? max( 1, absint( $attributes['categoriesToShow'] ) ) : 12;
$hide_empty         = ! isset( $attributes['hideEmpty'] ) || (bool) $attributes['hideEmpty'];
$image_fallback     = ! isset( $attributes['imageFallback'] ) || (bool) $attributes['imageFallback'];
$columns_desktop    = isset( $attributes['columnsDesktop'] ) ? max( 1, absint( $attributes['columnsDesktop'] ) ) : 4;
$columns_mobile     = isset( $attributes['columnsMobile'] ) ? max( 1, absint( $attributes['columnsMobile'] ) ) : 1;
$description_length = isset( $attributes['descriptionLength'] ) ? absint( $attributes['descriptionLength'] ) : 160;

$card_ratio = isset( $attributes['cardRatio'] ) && in_array( $attributes['cardRatio'], array( '1/1', '3/4', '4/3', '16/9' ), true ) ? $attributes['cardRatio'] : '1/1';

$title_tag = isset( $attributes['titleTag'] ) && in_array( $attributes['titleTag'], array( 'h1', 'h2', 'h3', 'span' ), true ) ? $attributes['titleTag'] : 'h2';

$card_title_tag = isset( $attributes['cardTitleTag'] ) && in_array( $attributes['cardTitleTag'], array( 'h2', 'h3', 'h4', 'span' ), true ) ? $attributes['cardTitleTag'] : 'h3';

$button_text = isset( $attributes['buttonText'] ) ? trim( wp_strip_all_tags( (string) $attributes['buttonText'] ) ) : '';
if ( '' === $button_text ) {
	$button_text = __( 'See all', 'carmo-category-rundown' );
}

// Query arguments for each source.
$term_args = array(
	'taxonomy'   => 'product_cat',
	'hide_empty' => $hide_empty,
);

$category_ids = array();

if ( 'handpicked' === $source ) {
	if ( ! empty( $attributes['categoryIds'] ) && is_array( $attributes['categoryIds'] ) ) {
		$category_ids = array_values( array_unique( array_filter( array_map( 'absint', $attributes['categoryIds'] ) ) ) );
	}

	if ( ! $category_ids ) {
		if ( $is_editor_preview ) {
			echo '<p>' . esc_html__( 'Choose categories in the block settings to show them in this carousel.', 'carmo-category-rundown' ) . '</p>';
		}
		return;
	}

	// Picked categories keep the order they were picked in.
	$term_args['include'] = $category_ids;
	$term_args['orderby'] = 'include';
} else {
	if ( 'children' === $source ) {
		if ( ! $parent_id ) {
			if ( $is_editor_preview ) {
				echo '<p>' . esc_html__( 'Choose the parent category in the block settings.', 'carmo-category-rundown' ) . '</p>';
			}
			return;
		}
		$term_args['parent'] = $parent_id;
	} else {
		$term_args['parent'] = 0;

		// WooCommerce's "Uncategorized" fallback category is not one to promote.
		$default_category = absint( get_option( 'default_product_cat', 0 ) );
		if ( $default_category ) {
			$term_args['exclude'] = array( $default_category );
		}
	}

	$term_args['number'] = $categories_to_show;

	$order_by = isset( $attributes['orderBy'] ) ? $attributes['orderBy'] : 'menu_order';
	if ( 'name' === $order_by ) {
		$term_args['orderby'] = 'name';
		$term_args['order']   = 'ASC';
	} elseif ( 'count' === $order_by ) {
		$term_args['orderby'] = 'count';
		$term_args['order']   = 'DESC';
	} else {
		// WooCommerce turns this into the drag-and-drop order of the
		// Products > Categories screen.
		$term_args['orderby'] = 'menu_order';
		$term_args['order']   = 'ASC';
	}
}

$terms = get_terms( $term_args );

if ( is_wp_error( $terms ) || ! $terms ) {
	if ( $is_editor_preview ) {
		echo '<p>' . esc_html__( 'No categories to show with these settings.', 'carmo-category-rundown' ) . '</p>';
	}
	return;
}

// Heading: the custom title, or the parent's name when listing subcategories.
$carousel_title = isset( $attributes['customTitle'] ) ? trim( wp_strip_all_tags( (string) $attributes['customTitle'] ) ) : '';
if ( '' === $carousel_title && 'children' === $source ) {
	$parent_term = get_term( $parent_id, 'product_cat' );
	if ( $parent_term instanceof WP_Term ) {
		$carousel_title = $parent_term->name;
	}
}

$eyebrow         = isset( $attributes['eyebrow'] ) ? trim( wp_strip_all_tags( (string) $attributes['eyebrow'] ) ) : '';
$eyebrow_classes = 'carmo-rundown__eyebrow';
if ( ! empty( $attributes['eyebrowClass'] ) ) {
	foreach ( preg_split( '/\s+/', (string) $attributes['eyebrowClass'], -1, PREG_SPLIT_NO_EMPTY ) as $extra_class ) {
		$extra_class = sanitize_html_class( $extra_class );
		if ( '' !== $extra_class ) {
			$eyebrow_classes .= ' ' . $extra_class;
		}
	}
}
if ( 'carmo-rundown__eyebrow' === $eyebrow_classes ) {
	$eyebrow_classes .= ' carmo-rundown__eyebrow--default';
}

// <hgroup> must contain a heading; with a span title a plain div is used.
$heading_tag = ( $carousel_title && 'span' !== $title_tag ) ? 'hgroup' : 'div';

$wrapper_classes = 'carmo-rundown';
if ( 1 === $columns_mobile ) {
	$wrapper_classes .= ' carmo-rundown--peek-mobile';
}
if ( 1 === $columns_desktop ) {
	$wrapper_classes .= ' carmo-rundown--peek-desktop';
}
$cards_align = isset( $attributes['cardsAlign'] ) ? $attributes['cardsAlign'] : 'left';
if ( in_array( $cards_align, array( 'center', 'right' ), true ) ) {
	$wrapper_classes .= ' carmo-rundown--cards-' . $cards_align;
}

$wrapper_attributes = get_block_wrapper_attributes(
	array(
		'class' => $wrapper_classes,
		'style' => sprintf(
			'--carmo-rundown-columns-desktop:%d;--carmo-rundown-columns-mobile:%d;--carmo-rundown-ratio:%s;',
			$columns_desktop,
			$columns_mobile,
			$card_ratio
		),
	)
);

// Image `sizes`: roughly one column of the viewport, so the browser does not
// fetch a full-width image for a quarter-width card.
$image_sizes = sprintf(
	'(max-width: 781px) %dvw, %dvw',
	(int) ceil( 100 / $columns_mobile ),
	(int) ceil( 100 / $columns_desktop )
);

$placeholder_src = function_exists( 'wc_placeholder_img_src' ) ? wc_placeholder_img_src( 'woocommerce_single' ) : '';

/*
 * Category description cut to the character limit without splitting a word.
 * A closure (not a named function) so the template can render more than once
 * per page without a "cannot redeclare" error.
 */
$truncate = static function ( string $text, int $limit ): string {
	$text = trim( preg_replace( '/\s+/u', ' ', wp_strip_all_tags( strip_shortcodes( $text ) ) ) );

	if ( '' === $text || mb_strlen( $text ) <= $limit ) {
		return $text;
	}

	$cut        = mb_substr( $text, 0, $limit + 1 );
	$last_space = mb_strrpos( $cut, ' ' );
	$cut        = false !== $last_space && $last_space > 0 ? mb_substr( $cut, 0, $last_space ) : mb_substr( $text, 0, $limit );

	return rtrim( $cut, " \t\n\r\0\x0B.,;:!?-–—" ) . '…';
};
?>
<div <?php echo $wrapper_attributes; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>>
	<?php if ( $carousel_title || $eyebrow ) : ?>
		<<?php echo esc_html( $heading_tag ); ?> class="carmo-rundown__heading">
			<?php if ( $eyebrow ) : ?>
				<p class="<?php echo esc_attr( $eyebrow_classes ); ?>"><?php echo esc_html( $eyebrow ); ?></p>
			<?php endif; ?>
			<?php if ( $carousel_title ) : ?>
				<<?php echo esc_html( $title_tag ); ?> class="carmo-rundown__title"><?php echo esc_html( $carousel_title ); ?></<?php echo esc_html( $title_tag ); ?>>
			<?php endif; ?>
		</<?php echo esc_html( $heading_tag ); ?>>
	<?php endif; ?>
	<div class="carmo-rundown__viewport">
		<button type="button" class="carmo-rundown__nav carmo-rundown__nav--prev" aria-label="<?php esc_attr_e( 'Previous categories', 'carmo-category-rundown' ); ?>">
			<span aria-hidden="true">&#10094;</span>
		</button>
		<ul class="carmo-rundown__track">
			<?php
			foreach ( $terms as $term ) :
				$link = get_term_link( $term );
				if ( is_wp_error( $link ) ) {
					continue;
				}

				// Image: the category thumbnail; failing that (none set, or its
				// file was deleted), a product image from the category (cached);
				// failing that, the WooCommerce placeholder.
				$image_attrs = array(
					'class'   => 'carmo-rundown__image',
					'alt'     => '',
					'sizes'   => $image_sizes,
					'loading' => 'lazy',
				);
				$image_html  = '';

				$thumbnail_id = absint( get_term_meta( $term->term_id, 'thumbnail_id', true ) );
				if ( $thumbnail_id ) {
					$image_html = wp_get_attachment_image( $thumbnail_id, 'large', false, $image_attrs );
				}

				if ( '' === $image_html && $image_fallback && function_exists( 'carmo_category_rundown_get_fallback_image_id' ) ) {
					$fallback_id = carmo_category_rundown_get_fallback_image_id( (int) $term->term_id );
					if ( $fallback_id ) {
						$image_html = wp_get_attachment_image( $fallback_id, 'large', false, $image_attrs );
					}
				}

				$description = $description_length ? $truncate( (string) $term->description, $description_length ) : '';
				?>
				<li class="carmo-rundown__card">
					<a class="carmo-rundown__link" href="<?php echo esc_url( $link ); ?>">
						<span class="carmo-rundown__media" aria-hidden="true">
							<?php
							if ( '' !== $image_html ) {
								echo $image_html; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- wp_get_attachment_image() output.
							} elseif ( $placeholder_src ) {
								printf(
									'<img class="carmo-rundown__image carmo-rundown__image--placeholder" src="%s" alt="" loading="lazy" decoding="async" />',
									esc_url( $placeholder_src )
								);
							}
							?>
						</span>
						<div class="carmo-rundown__content">
							<<?php echo esc_html( $card_title_tag ); ?> class="carmo-rundown__name"><?php echo esc_html( $term->name ); ?></<?php echo esc_html( $card_title_tag ); ?>>
							<div class="carmo-rundown__reveal">
								<div class="carmo-rundown__reveal-inner">
									<?php if ( $description ) : ?>
										<span class="carmo-rundown__description"><?php echo esc_html( $description ); ?></span>
									<?php endif; ?>
									<span class="carmo-rundown__button">
										<?php echo esc_html( $button_text ); ?>
										<svg aria-hidden="true" focusable="false" width="12" height="12" viewBox="0 0 12 12"><path d="M3 9 9 3M4 3h5v5" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"/></svg>
									</span>
								</div>
							</div>
						</div>
					</a>
				</li>
			<?php endforeach; ?>
		</ul>
		<button type="button" class="carmo-rundown__nav carmo-rundown__nav--next" aria-label="<?php esc_attr_e( 'Next categories', 'carmo-category-rundown' ); ?>">
			<span aria-hidden="true">&#10095;</span>
		</button>
	</div>
</div>
