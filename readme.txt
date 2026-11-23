=== Carmo Category Rundown ===
Contributors: carmopereira
Donate link: https://ko-fi.com/carmopereira
Tags: woocommerce, categories, carousel, block, product category
Requires at least: 6.9
Tested up to: 7.0
Stable tag: 0.1.6
Requires PHP: 8.2
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

A Gutenberg block that displays a carousel of WooCommerce product categories as image cards, with configurable columns for desktop and mobile.

== Description ==

Carmo Category Rundown adds a category carousel block to the WordPress block editor. Each card is a link to a WooCommerce product category: the category thumbnail fills the card and the category name sits in a pill at the bottom. On desktop with a mouse, hovering a card darkens it, shows the name in large type, the category description and a "See all" button.

It is the sibling of Carmo Category Product, which does the same for products.

**Features**

* Three sources: top-level categories, subcategories of a chosen category, or a hand-picked list shown in the order you pick them
* Order by WooCommerce's custom order (drag and drop in Products > Categories), name or number of products
* Optionally hide empty categories; the "Uncategorized" default category is never listed among top-level categories
* Configurable number of columns for desktop and mobile, and the card proportion (1:1, 3:4, 4:3, 16:9)
* Category description with a configurable character limit (never cuts a word in half)
* Custom text for the "See all" button
* Optional title (defaults to the parent category's name when listing subcategories) and subtitle, with selectable HTML tags
* Swipe and scroll-snap on touch devices; prev/next buttons on desktop that hide at either end
* Wide and full alignment support

**Requirements**

WooCommerce must be installed and active.

== Installation ==

1. Upload the plugin files to `/wp-content/plugins/carmo-category-rundown`, or install the plugin through the WordPress plugins screen.
2. Activate the plugin through the "Plugins" screen in WordPress.
3. In the block editor, add the "Carmo Category Rundown" block and choose which categories to show in the block settings sidebar.

== Frequently Asked Questions ==

= Where does the card image come from? =

From the category's thumbnail (Products > Categories > Thumbnail). For a category without one, the block can use the featured image of the first visible product in that category or its subcategories (on by default; a block setting). That choice is cached per category and refreshed whenever a product or category changes. With no product image either, the WooCommerce placeholder is shown.

= Where does the description come from? =

From the category's description field. Set the character limit to 0 to hide it.

== Changelog ==

= 0.1.6 =
* On mobile the carousel now shows one large card by default, with a sliver of the next one so it reads as a carousel (like Carmo Category Product).

= 0.1.5 =
* All texts are now in English and translatable (text domain carmo-category-rundown, POT file in /languages). Translations in /languages are loaded for both the block settings and the front end.

= 0.1.4 =
* On hover, a category without a description gets the same space between the name and the button as one with a description has between the name and the description.

= 0.1.3 =
* The category description is always light on the card, whatever the theme's link colours.

= 0.1.2 =
* Categories without a thumbnail can show a product image from the category instead, cached until a product or category changes.

= 0.1.1 =
* First version: category carousel with top-level, subcategory and hand-picked sources.
