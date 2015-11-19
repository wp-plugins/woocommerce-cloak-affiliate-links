=== WooCommerce Cloak Affiliate Links ===

Contributors: datafeedr.com
Tags: woocommerce, affiliate, links, cloak, mask, redirect, external, url, urls, rewrite, datafeedr, sellfire, popshops
Donate link: https://www.paypal.com/cgi-bin/webscr?cmd=_s-xclick&hosted_button_id=9945A9PLQ7P46
License: GPLv2 or later
License URI: http://www.gnu.org/licenses/gpl-2.0.html
Requires at least: 3.8
Tested up to: 4.4
Stable tag: 1.0.5

Cloak your WooCommerce external & affiliate links.

== Description ==

The *WooCommerce Cloak Affiliate Links* plugin allows you to mask all external links in your WooCommerce store.

For example, change this...
`merchant.com/index.php?aff_id=123&product_id=456`

... into this:
`yoursite.com/go/123`

Configure the status code for the redirect to either 301, 302 or 307.

The plugin also adds a "Disallow" to your robots.txt file to prevent bots from following those external links.

== Installation ==

This section describes how to install the plugin and get it working.

1. Upload the `woocommerce-cloak-affiliate-links` folder to the `/wp-content/plugins/` directory.
1. Activate the *WooCommerce Cloak Affiliate Links* plugin through the 'Plugins' menu in WordPress.
1. Configure the plugin by going to Settings > WC Cloak Links.

== Frequently Asked Questions ==

= What redirect status should I use, 301, 302 or 307? =

There's a lot of debate about this.  I would suggest Googling this and seeing which works best for you.  The default status is 302.

== Screenshots ==

1. Settings
2. Permalinks

== Changelog ==

= 1.0.5 =
* Just updating readme.

= 1.0.4 =
* Added user_trailingslashit() function to returned redirect URL. (#9588)

= 1.0.3 =
* Updated 'tested up to' tag.

= 1.0.2 =
* Added "static" to static methods to meet Strict Standards.
* Updated plugin information.

= 1.0.1 =
* Changed author name to "datafeedr.com"
* Flushed rewrite rules on plugin activation/deactivation.

= 1.0.0 =
* Initial release.

== Upgrade Notice ==

*None*

