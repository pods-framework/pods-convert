<?php
/**
 * Plugin Name:       Pods Convert
 * Plugin URI:        http://pods.io/
 * Description:       Convert a Pod to a different Pod type or storage type
 * Version:           0.1
 * Author:            Pods Framework Team
 * Author URI:        http://pods.io/
 */

namespace Pods\Convert;

// If this file is called directly, abort.
if ( ! defined( 'WPINC' ) ) {
	die;
}

if ( defined( 'WP_CLI' ) && WP_CLI ) {
	include_once 'wp-cli.php';
}