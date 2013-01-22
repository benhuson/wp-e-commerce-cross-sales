=== WP e-Commerce Cross Sales (Also Bought) ===
Contributors: husobj
Tags: e-commerce, wp-e-commerce, shop, cart, ecommerce, products, related
Requires at least: 3.1
Tested up to: 3.5
Stable tag: 0.3
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

= 0.3 =

* Update cross_sales() and wpsc_submit_checkout() based on current WPEC code.
* Set default options.
* Don't use theme file for better compatibility - WPEC doesn't at the moment. Maybe later.
* Update compatibility messages.

= 0.2 =

* Output using WP_Query().
* Get also bought products instead of product variations with latest WPEC 3.9 dev.
* Only show admin notice on plugins page if WPEC not installed or older version.

= 0.1 =

* Require WPEC 3.9+
* Use wpsc_cross_sales() instead of wpsc_also_bought() - although it will still work for the time being.
* Add support for languages (.pot file not populated yet though)
* Added in options to set image size and populate default option values on install.
* Remove hardcoded styling.
* Moved code to being class-based.
