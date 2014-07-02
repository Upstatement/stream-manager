<?php
/**
 * The WordPress Plugin Boilerplate.
 *
 * A foundation off of which to build well-documented WordPress plugins that
 * also follow WordPress Coding Standards and PHP best practices.
 *
 * @package   Feed_Manager
 * @author    Chris Voll + Upstatement
 * @license   GPL-2.0+
 * @link      http://upstatement.com
 * @copyright 2014 Upstatement
 *
 * @wordpress-plugin
 * Plugin Name:       Feed Manager
 * Plugin URI:        http://upstatement.com/feed-manager
 * Description:       Manager of Feeds.
 * Version:           1.0.0
 * Author:            Chris Voll + Upstatement
 * Author URI:        http://upstatement.com
 * Text Domain:       feed-manager
 * License:           GPL-2.0+
 * License URI:       http://www.gnu.org/licenses/gpl-2.0.txt
 * Domain Path:       /languages
 * GitHub Plugin URI: https://github.com/upstatement/not-feed-manager
 * WordPress-Plugin-Boilerplate: v2.6.1
 */

// If this file is called directly, abort.
if ( ! defined( 'WPINC' ) ) {
	die;
}

/*----------------------------------------------------------------------------*
 * Public-Facing Functionality
 *----------------------------------------------------------------------------*/


require_once( plugin_dir_path( __FILE__ ) . 'includes/post-types.php' );
require_once( plugin_dir_path( __FILE__ ) . 'public/class-feed-manager.php' );

/*
 * Register hooks that are fired when the plugin is activated or deactivated.
 * When the plugin is deleted, the uninstall.php file is loaded.
 */
register_activation_hook( __FILE__, array( 'Feed_Manager', 'activate' ) );
register_deactivation_hook( __FILE__, array( 'Feed_Manager', 'deactivate' ) );

add_action( 'plugins_loaded', array( 'Feed_Manager', 'get_instance' ) );

/*----------------------------------------------------------------------------*
 * Dashboard and Administrative Functionality
 *----------------------------------------------------------------------------*/

/*
 * If you want to include Ajax within the dashboard, change the following
 * conditional to:
 *
 * if ( is_admin() ) {
 *   ...
 * }
 *
 * The code below is intended to to give the lightest footprint possible.
 */
if ( is_admin() && ( ! defined( 'DOING_AJAX' ) || ! DOING_AJAX ) ) {

	require_once( plugin_dir_path( __FILE__ ) . 'admin/class-feed-manager-admin.php' );
	add_action( 'plugins_loaded', array( 'Feed_Manager_Admin', 'get_instance' ) );

}
