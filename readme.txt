=== WP e-Commerce Cross Sales (Also Bought) ===
Contributors: husobj
Tags: e-commerce, wp-e-commerce, shop, cart, ecommerce, products, related
Requires at least: 3.0
Tested up to: 3.1
Stable tag: 0.1
License: GPLv2 or later

This plugin displays cross sales in WP e-Commerce. It provides the same functionality as in earlier versions of WP e-Commerce plus a little bit more.

== Description ==

This plugin displays cross sales in WP e-Commerce. It provides the same functionality as in earlier versions of WP e-Commerce plus a little bit more.

Doesn't do anything yet but will replace the current WP e-Commerce 'also bought' functionality in version 3.9+

New features include:

* Option to set cross sale image sizes.
* Remove hardcoded styling so it's easier to style via css.
* Improved code structure.

PS: You'll obviously need WP e-Commerce installed for this to do anything ;)

== Installation ==

Upload the plugin and activate it.
Simple.

== Changelog ==

= 0.1 =

* Require WPEC 3.9+
* Use wpsc_cross_sales() instead of wpsc_also_bought() - although it will still work for the time being.
* Add support for languages (.pot file not populated yet though)
* Added in options to set image size and populate default option values on install.
* Remove hardcoded styling.
* Moved code to being class-based.
