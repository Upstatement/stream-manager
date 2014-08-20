<?php
/**
 * Stream Manager
 *
 * @package   StreamManager
 * @author    Chris Voll + Upstatement
 * @license   GPL-2.0+
 * @link      http://upstatement.com
 * @copyright 2014 Upstatement
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
 * GitHub Plugin URI: https://github.com/upstatement/not-stream-manager
 * WordPress-Plugin-Boilerplate: v2.6.1
 */

// If this file is called directly, abort.
if ( ! defined( 'WPINC' ) ) die;


/*----------------------------------------------------------------------------*
 * Dependencies
 *----------------------------------------------------------------------------*/

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


/*----------------------------------------------------------------------------*
 * Public-Facing Functionality
 *----------------------------------------------------------------------------*/

require_once( plugin_dir_path( __FILE__ ) . 'includes/class-stream-manager.php' );
require_once( plugin_dir_path( __FILE__ ) . 'includes/timber-stream.php' );

add_action( 'plugins_loaded', array( 'StreamManager', 'get_instance' ) );


/*----------------------------------------------------------------------------*
 * Dashboard and Administrative Functionality
 *----------------------------------------------------------------------------*/

if ( is_admin() ) {

	require_once( plugin_dir_path( __FILE__ ) . 'includes/class-stream-manager-admin.php' );
	add_action( 'plugins_loaded', array( 'StreamManagerAdmin', 'get_instance' ) );

}
