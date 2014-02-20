=== Datafeedr Product Sets ===

Contributors: datafeedr
Tags: datafeedr, product sets, dfrapi, dfrps, import, products
Requires at least: 3.8
Tested up to: 3.8.1
Stable tag: 0.9.5
License: GPLv2 or later
License URI: http://www.gnu.org/licenses/gpl-2.0.html

Build sets of products to import into your website.

== Description ==

**NOTE:** The *Datafeedr Product Sets* plugin requires the [Datafeedr API plugin](http://wordpress.org/plugins/datafeedr-api/).

**What is a Product Set?**

A Product Set is a collection of related products. Once you create a Product Set, the products in that set will be imported into your website (via an importer plugin). The Product Set is also responsible for updating those imported products with the newest information at an interval you choose.

**How does it work?**

1. Create a new Product Set by performing a product search for specific keywords.  In this example lets use "rock climbing shoes" as our keywords.

1. The Datafeedr Product Sets plugin connects to the Datafeedr API and makes an API request querying 250 million affiliate products in the Datafeedr database for the keywords "rock climbing shoes".

1. The Datafeedr API returns the products in the database that match your search keywords.

1. At this point, you have 2 choices: You can "save" your search (so that all products returned are added to your Product Set) or you can pick and choose specific products to add to your Product Set.

1. After your Product Set has some products in it, you choose what WordPress Post Type and Category to import the Product Set into. For example, you could import all of the rock climbing shoes into your WooCommerce store in the "Climbing Shoes" product category.

1. Within a few seconds the Product Set will attempt to import those products into your WooCommerce product category. It will do so by getting all of the products in the Product Set and passing them to an importer plugin (in this case the [Datafeedr WooCommerce Importer plugin](http://wordpress.org/plugins/datafeedr-woocommerce-importer/)).

1. After a few minutes (depending on how many products are in your set and your update settings) your "Climbing Shoes" product category will be filled with products from your Product Set.

1. Lastly, at an interval you configure, the Product Set will trigger a product update. At this time, products no longer available via the Datafeedr API will be removed from your WooCommerce store, all product information will be updated and any new products that match your "saved search" will be added to your store.

The *Datafeedr Product Sets* plugin requires at least one importer plugin to import products from a Product Set into your blog.

We currently have one importer which imports products from your Product Sets into your WooCommerce store: [Datafeedr WooCommerce Importer plugin](http://wordpress.org/plugins/datafeedr-woocommerce-importer/). Additional importers will be developed over the coming months. Custom importers may also be written. Product Sets can be imported into one or more WordPress Post Types.

**Requirements**

* WordPress Cron enabled.
* 64MB of memory ([instructions](http://codex.wordpress.org/Editing_wp-config.php#Increasing_memory_allocated_to_PHP)).
* PHP's `allow_url_fopen` must be `On`.
* An [importer plugin](http://wordpress.org/plugins/tags/dfrpsimporter) to handle importing products from your Product Sets into your website.

== Installation ==

This section describes how to install and configure the plugin:

1. Upload the `datafeedr-product-sets` folder to the `/wp-content/plugins/` directory.
1. Activate the *Datafeedr Product Sets* plugin through the 'Plugins' menu in WordPress.
1. Configure your Product Sets settings here: WordPress Admin Area > Product Sets > Configuration
1. Add a Product Set here: WordPress Admin Area > Product Sets > Add Product Set

== Frequently Asked Questions ==

= Where can I get help?  =

Our support area can be found here: [https://v4.datafeedr.com/support](https://v4.datafeedr.com/support). This support area is open to everyone.

== Screenshots ==

1. Product search form
2. Product Set Dashboard
3. List of Product Sets and their update status
4. Configuration: Search Settings
5. Configuration: Update Settings
6. Configuration: Advanced Update Settings

== Changelog ==

= 0.9.6 =
* Fixed more undefined indexes.

= 0.9.5 =
* Fixed more undefined indexes.
* Updated plugin information.

= 0.9.4 =
* Added a nag if a default CPT had not been selected.
* Fixed undefined indexes.

= 0.9.3 =
* Initial release.

== Upgrade Notice ==

*None*

