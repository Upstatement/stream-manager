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
 * Plugin URI:        http://upstatement.com/stream-manager
 * Description:       Conquerer of Streams.
 * Version:           1.0.0
 * Author:            Chris Voll + Upstatement
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
  if ( !is_dir( ABSPATH . 'wp-content/plugins/timber-library' ) ) {
    add_action('admin_notices', function() {
      echo('<div class="error"><p>Please install <a href="http://upstatement.com/timber/">Timber</a> to use Stream Manager.</p></div>');
    });
  } else {
    include_once( ABSPATH . 'wp-content/plugins/timber-library/timber.php' );
  }
}



////////////////////////////////////////////
//
//  Public-Facing Functionality
//
////////////////////////////////////////////

require_once( plugin_dir_path( __FILE__ ) . 'includes/class-stream-manager-utilities.php' );
require_once( plugin_dir_path( __FILE__ ) . 'includes/class-stream-manager.php' );
require_once( plugin_dir_path( __FILE__ ) . 'includes/timber-stream.php' );
require_once( plugin_dir_path( __FILE__ ) . 'includes/class-stream-manager-manager.php');




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

/**
 * Creating a new stream programatically, with the option to pass a query array to filter the stream
 *
 * @param string $slug
 * @param string $title
 * @param array $query_array
 *
 */

function stream_manager_insert_stream( $slug, $title = NULL, $query_array = NULL) {
	$post_title = $title?: $slug;
	$pid = wp_insert_post(array('post_type' => 'sm_stream', 'post_name' => $slug, 'post_title' => $post_title ));
	if ( $query_array ) {
		add_filter('stream-manager/options/'.$slug, function($defaults) use ($query_array) {
		  $defaults['query'] = array_merge( $defaults['query'], $query_array );
		  return $defaults;
		});
	}	
	return $pid;
}
