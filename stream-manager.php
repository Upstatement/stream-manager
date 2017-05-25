<?php
/**
 * Stream Manager
 *
 * @package   StreamManager
 * @author    Upstatement
 * @license   MIT
 * @link      http://upstatement.com
 * @copyright 2015 Upstatement
 *
 * @wordpress-plugin
 * Plugin Name:       Stream Manager
 * Description:       Conquerer of Streams.
 * Version:           1.3.4
 * Author:            Upstatement
 * Author URI:        http://upstatement.com
 * Text Domain:       stream-manager
 * License:           GPL-2.0+
 * License URI:       http://www.gnu.org/licenses/gpl-2.0.txt
 * Domain Path:       /languages
 * GitHub Plugin URI: https://github.com/upstatement/stream-manager
 */

// If this file is called directly, abort.
if ( ! defined( 'WPINC' ) ) die;


////////////////////////////////////////////
//
//  Dependencies
//
////////////////////////////////////////////


// Check if Timber is installed, and include it before any stream manager
// things are initiated. This is needed for the TimberStream class.
if ( !class_exists('Timber') ) {

  // WP plugin repo version
  if ( is_dir( plugin_dir_path(__DIR__).'timber-library') ) {
    include_once( plugin_dir_path(__DIR__).'timber-library/timber.php' );
  } 
  // old development copy
  elseif( is_dir( plugin_dir_path(__DIR__).'timber' ) ) {
    include_once( plugin_dir_path(__DIR__).'timber/timber.php' );
  } 
  // included directly in the theme via composer
  elseif ( is_dir( get_template_directory() . '/vendor/timber' ) ) {
    include_once( get_template_directory() . '/vendor/autoload.php');
  } 

  // Timber is nowhere to be found, throw a notice and return
  else {
    add_action('admin_notices', function() {
      echo('<div class="error"><p>Please install <a href="http://upstatement.com/timber/">Timber</a> to use Stream Manager.</p></div>');
    });
    return;
  }
} 



////////////////////////////////////////////
//
//  Public-Facing Functionality
//
////////////////////////////////////////////

require_once( plugin_dir_path( __FILE__ ) . 'includes/class-stream-manager-utilities.php' );
require_once( plugin_dir_path( __FILE__ ) . 'includes/class-stream-manager-ajax-helper.php' );
require_once( plugin_dir_path( __FILE__ ) . 'includes/class-stream-manager.php' );
require_once( plugin_dir_path( __FILE__ ) . 'includes/timber-stream.php' );
require_once( plugin_dir_path( __FILE__ ) . 'includes/class-stream-manager-manager.php');
require_once( plugin_dir_path( __FILE__ ) . 'includes/class-stream-manager-api.php');




add_action( 'plugins_loaded', array( 'StreamManager', 'get_instance' ) );
add_action( 'plugins_loaded', array( 'StreamManagerManager', 'get_instance' ) );


////////////////////////////////////////////
//
//  Dashboard & Administrative Functionality
//
////////////////////////////////////////////

 if ( is_admin() ) {
	require_once( plugin_dir_path( __FILE__ ) . 'includes/class-stream-manager-admin.php' );
  
	add_action( 'plugins_loaded', array( 'StreamManagerAdmin', 'get_instance' ) );
}


