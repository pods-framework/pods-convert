=== Pods Convert ===
Contributors: sc0ttkclark, pglewis
Donate link: http://podsfoundation.org/donate/
Tags: pods, storage type, migrate, storage type, convert
Requires at least: 4.2
Tested up to: 4.2.2
Stable tag: 0.1
License: GPLv2 or later
License URI: http://www.gnu.org/licenses/gpl-2.0.html

Pods Convert is a plugin to convert a Pod type or Storage type to another.

== Description ==

Requires WP-CLI and access to the command line.

Run `wp pods-convert migrate --pod=your_pod`

Additional options can be:

* Change the pod type: `--new_type=post_type` (For example, when it was an Advanced Content Type "pod" before)
* Change the storage type: `--new_storage=meta` (For example, when it was Table-based storage "table" before)
* Change the pod name used for new pod: `--new_name=my_new_pod` (This will bypass the attempt to create a temporary pod / delete the old one, you will end up with two different pods which are identical and have the same content)
* Additional fields mapping: Coming soon
* Verbose logging in terminal: `--verbose`

The migration will create a temporary pod from the pod, migrate all items from that pod 100 at a time, then attempt to delete the pod and rename the temporary pod back to the original pod name.

== Installation ==

1. Unpack the entire contents of this plugin zip file into your `wp-content/plugins/` folder locally
1. Upload to your site
1. Navigate to `wp-admin/plugins.php` on your site (your WP Admin plugin page)
1. Activate this plugin

OR you can just install it with WordPress by going to Plugins >> Add New >> and type this plugin's name

== Changelog ==

= 0.1 - July 14th, 2015 =
* First release