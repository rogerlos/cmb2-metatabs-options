=== CMB2 Metatabs Options ===
Contributors: rogerlos
Donate link: http://rogerlos.com
Tags: cmb2, metaboxes, forms, fields, options, settings, tabs, cmo
Requires at least: 3.8.0
Tested up to: 4.4.1
Stable tag: 1.0.1
License: GPLv2 or later
License URI: http://www.gnu.org/licenses/gpl-2.0.html

Extends CMB2--create WordPress options pages with multiple metaboxes,
support for tabs, and flexible menu locations.

== Description ==

CMB2 Metatabs Options (CMO) is a plugin for developers using CMB2 to manage metaboxes and fields.
CMO makes it easy to create options pages with multiple metaboxes--and optional WordPress admin tabs.
You can attach your option page(s) to any existing Wordpress menu or add them as a new
top-level menu.

This plugin requires the [CMB2 Plugin](http://wordpress.org/plugins/cmb2/), or your project
must already utilize the [CMB2](https://github.com/WebDevStudios/CMB2) library. CMB2 is *not* included.

Please see the wiki at CMO's github repository for a
[detailed user's guide](https://github.com/rogerlos/cmb2-metatabs-options/wiki).

Thanks to the folks maintaining CMB2 for their continued development of that library, and providing the
starting point for this plugin.

== Installation ==

Download the plugin zip file and add via Plugins->Add New->Upload. Or FTP the unzipped plugin folder to
your wp_content/plugins directory. Activate the plugin within WP admin.

Note this plugin does nothing by default other than give you access to the Cmb2_Metatabs_Options() class.

You can see an example of what this plugin does by using the WP plugin editor and uncommenting the line
in the main plugin file which reads "include 'example.php';".

== Frequently Asked Questions ==

See the [wiki](https://github.com/rogerlos/cmb2-metatabs-options/wiki/Troubleshooting) troubleshooting page.

== Screenshots ==

Nothing to see.

== Upgrade Notice ==

None.

== Changelog ==

= 1.0.1 =
code\cmb2_metatabs_options.php revisions:
* settings_notices() : updated text domain
* should_save() : added method
* metabox_callback() : refactored save
* add_scripts() : always added postbox toggle to footer
* build_menu_args() : removed 'menuargs' validatation
* validate_props() : added 'menuargs' validation
code\cmb2multiopts.js revisions:
* removed postboxes toggle

= 1.0.0 =
* Initial release.