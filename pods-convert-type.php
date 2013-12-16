<?php
/**
 * The WordPress Plugin Boilerplate.
 *
 * A foundation off of which to build well-documented WordPress plugins that
 * also follow WordPress Coding Standards and PHP best practices.
 *
 * @package   Pods_Convert_Type
 *
 * Plugin Name:       Pods Convert Type
 * Plugin URI:        http://pods.io/
 * Description:       Convert a Pod to a different Pod type
 * Version:           0.9
 * Author:            Pods Framework Team
 * Author URI:        http://pods.io/
 */

// If this file is called directly, abort.
if ( ! defined( 'WPINC' ) ) {
	die;
}

/*----------------------------------------------------------------------------*
 * Public-Facing Functionality
 *----------------------------------------------------------------------------*/

require_once( plugin_dir_path( __FILE__ ) . 'public/class-pods-convert-type.php' );

/*
 * Register hooks that are fired when the plugin is activated or deactivated.
 * When the plugin is deleted, the uninstall.php file is loaded.
 *
 */
register_activation_hook( __FILE__, array( 'Pods_Convert_Type', 'activate' ) );
register_deactivation_hook( __FILE__, array( 'Pods_Convert_Type', 'deactivate' ) );

add_action( 'plugins_loaded', array( 'Pods_Convert_Type', 'get_instance' ) );

/*----------------------------------------------------------------------------*
 * Dashboard and Administrative Functionality
 *----------------------------------------------------------------------------*/

if ( is_admin() && ( ! defined( 'DOING_AJAX' ) || ! DOING_AJAX ) ) {

	require_once( plugin_dir_path( __FILE__ ) . 'admin/class-pods-convert-type-admin.php' );
	add_action( 'plugins_loaded', array( 'Pods_Convert_Type_Admin', 'get_instance' ) );

}