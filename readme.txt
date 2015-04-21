=== Datafeedr Product Sets ===

Contributors: datafeedr.com
Tags: datafeedr, product sets, dfrapi, dfrps, import, products
License: GPLv2 or later
License URI: http://www.gnu.org/licenses/gpl-2.0.html
Requires at least: 3.8
Tested up to: 4.2
Stable tag: 1.2.3

Build sets of products to import into your website.

== Description ==

**NOTE:** The *Datafeedr Product Sets* plugin requires the [Datafeedr API plugin](http://wordpress.org/plugins/datafeedr-api/).

**What is a Product Set?**

A Product Set is a collection of related products. Once you create a Product Set, the products in that set will be imported into your website (via an importer plugin). The Product Set is also responsible for updating those imported products with the newest information at an interval you choose.

The *Datafeedr Product Sets* plugin currently integrates with the following plugins:

* [Datafeedr WooCommerce Importer](http://wordpress.org/plugins/datafeedr-woocommerce-importer/)
* [Datafeedr API](http://wordpress.org/plugins/datafeedr-api/)

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
* PHP's `CURL` support must be enabled.
* An [importer plugin](http://wordpress.org/plugins/datafeedr-woocommerce-importer/) to handle importing products from your Product Sets into your website.

== Installation ==

This section describes how to install and configure the plugin:

1. Upload the `datafeedr-product-sets` folder to the `/wp-content/plugins/` directory.
1. Activate the *Datafeedr Product Sets* plugin through the 'Plugins' menu in WordPress.
1. Configure your Product Sets settings here: WordPress Admin Area > Product Sets > Configuration
1. Add a Product Set here: WordPress Admin Area > Product Sets > Add Product Set

== Frequently Asked Questions ==

= Where can I get help?  =

Our support area can be found here: [https://v4.datafeedr.com/support](https://v4.datafeedr.com/support?utm_campaign=dfrpsplugin&utm_medium=referral&utm_source=wporg). This support area is open to everyone.

== Screenshots ==

1. Product search form
2. Product Set Dashboard
3. List of Product Sets and their update status
4. Configuration: Search Settings
5. Configuration: Update Settings
6. Configuration: Advanced Update Settings

== Changelog ==

= 1.2.3 - 2015/04/15 =
* Renamed temp product table from 'dfrps_product_data' to 'dfrps_temp_product_data'.
* Added function to DROP dfrps_temp_product_data table after update is complete.
* DROP'd 'dfrps_product_data' table manually as it would have been stranded.
* Added new action 'dfrps_update_reset' to dfrps_reset_update() function.

= 1.2.2 - 2015/04/06 =
* Typecasted '_dfrps_product_check_image' as (int) value so it could be compared to 0.
* Added new icon to admin menu.
* Added new 128x128 and 256x256 plugin icons.
* Fixed broken URL to admin menu icons that have existed since the beginning of time.

= 1.2.1 - 2015/03/23 =
* Changed varchar(255) to varchar(50) in Update/Create Table statement to avoid "Specified key was too long; max key length is 767 bytes for query" errors (#10701).
* Fixed some grammar in the "Fix Missing Images" message.
* Replace spaces in Image URLs with "%20". This prevents images with spaces in the URL from failing to be imported.
* Fixed bug where default options were not being set upon plugin being activated which caused products to not be returned in searches.

= 1.2.0 - 2015/03/16 =
* Fixed bug where configuration settings were being saved at the wrong time.
* Added a filter to filter Product Sets by what CPT they import into.
* Fixed formatting (removed p tags) in admin notices.
* Added new parameter to cron SQL to check filter Product Sets by only active CPTs (ie. 'product').
* Readied code for adding additional importers to be added (#9167)

= 1.1.14 =
* Added a check/fix for mime types that include the type of encoding. Example: "image/jpeg;charset=UTF-8" 

= 1.1.13 =
* Fixed a hard-coded table name in bulk image importer SQL statement.

= 1.1.12 =
* Added back the 'dfrps_invalid_image' action hook which was inadvertently removed in version 1.1.10.

= 1.1.11 =
* Fixed a bug with the bulk image importer where it would import images for products which were having their '_dfrps_product_set_id' value deleted because they were being moved to the Trash. Now the bulk image importer only processes images for products where '_dfrps_product_set_id' does exist.

= 1.1.10 =
* Complete rewrite of image importer script. Now, allow_url_fopen is NOT required! :)
* Fixed bug with links generated under the bulk image importer not working for WordPress installed as a sub-directory.

= 1.1.9 =
* Fixed bug where extra postmeta data was being saved for non-productset post types.

= 1.1.8 =
* Changed most occurrences of unserialize() to maybe_unserialize() to deal with changes to get_metadata() in WP 4.1.0.
* Removed 2nd argument from dfrps_upload_images() to deal with changes to deal with do_action_ref_array() introducing the $this parameter in 4.1.0.
* Fixed bug where large Product Sets made up of lots of individually added products were not importing or updating all products in the Product Set. This only affected individually added products in Product Sets with over 100 products.

= 1.1.7 =
* Replaced get_the_post_thumbnail() with get_post_thumbnail_id() in image processing script.

= 1.1.6 =
* Added plugin icon for WordPress 4.0+.
* Fixed dashed tab styling for product sets.

= 1.1.5 =
* Fixed undefined 'price' index in html.php file.

= 1.1.4 =
* Changed dfrps_product_data's "data" column from TEXT to LONGTEXT.

= 1.1.3 =
* Added 'dfrps_set_update_complete' action when update is complete.

= 1.1.2 =
* Changed add_option to update_option in upgrade.php file.
* Added a new action to image.php file: "dfrps_invalid_image"

= 1.1.1 =
* Fixed issue with the sale price not displaying on 'single products' tab after set has updated. (#9210)

= 1.1.0 =
* Modified the 'Updater' class. Products are now inserted into a temporary table directly from the API query. Then the updater iterates over the temporary table until all products are processed and imported into WP. This change will make the update process slightly longer however it will prevent wasted API requests. It will also work to prevent import timeouts by separating the API Request and the Import into 2 different stages.
* Added upgrade.php file to track upgrades between versions.

= 1.0.10 =
* Fixed code if $links in ajax.php was not set.
* Added 'Searching X products...' to loading area when searching for products.

= 1.0.9 =
* Set update_phase to 0 when Product Set is moved to Trash. (#8705)
* Fixed undefined indexes.

= 1.0.8 =
* Updated 'tested up to' tag.

= 1.0.7 =
* Modified comment text.
* Fixed issue in dfrps_get_existing_post() related to 32-bit systems. Changed %d to %s.

= 1.0.6 =
* Forgot to update version in main plugin file.

= 1.0.5 =
* Tweaked search form css.
* Added help text to help tab for new Tools page.
* Changed default product update settings.

= 1.0.4 =
* Fixed "Requires at least" and "Tested up to" fields of the readme.txt file. Oops!
* Changes to a lot of help text on all pages.
* Readded Javascript regarding input#title which was accidentally removed in version 1.0.2.
* Fixed undefined indexes.

= 1.0.3 =
* Fixed commit.

= 1.0.2 =
* Changed contents of 'product set updates disabled' email.
* Converted emails sent from plain text to HTML.
* Fixed undefined indexes.
* Added filter to $postmeta in image.php.
* Removed screen_icon() from config page.
* Removed filesize check from functions/image.php because we already make sure it's an image with getimagesize().
* Added check in cron to see if at least 1 network and 1 merchant is selected before running update.
* Added new "Tools" page to perform different actions such as reset cron and bulk import images.
* Replaced Javascript on CPT pages to prevent conflict on onReady with other broken plugins.

= 1.0.1 =
* Fixed undefined indexes.
* Added do_action() to the beginning and end of each phase in the Update and Delete class.

= 1.0.0 =
* Updated "Contributors" and "Author" fields to match WP.org username.

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

