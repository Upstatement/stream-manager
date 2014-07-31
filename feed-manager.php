<?php
/**
 * Feed Manager
 *
 * @package   FeedManager
 * @author    Chris Voll + Upstatement
 * @license   GPL-2.0+
 * @link      http://upstatement.com
 * @copyright 2014 Upstatement
 *
 * @wordpress-plugin
 * Plugin Name:       Feed Manager
 * Plugin URI:        http://upstatement.com/feed-manager
 * Description:       Conquerer of Feeds.
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
if ( ! defined( 'WPINC' ) ) die;


/*----------------------------------------------------------------------------*
 * Dependencies
 *----------------------------------------------------------------------------*/

// Check if Timber is installed, and include it before any feed manager
// things are initiated. This is needed for the TimberFeed class.
if ( !class_exists('Timber') ) {
  if ( !is_dir( ABSPATH . 'wp-content/plugins/timber-library' ) ) {
    add_action('admin_notices', function() {
      echo('<div class="error"><p>Please install <a href="http://upstatement.com/timber/">Timber</a> to use Feed Manager.</p></div>');
    });
  } else {
    include_once( ABSPATH . 'wp-content/plugins/timber-library/timber.php' );
  }
}


/*----------------------------------------------------------------------------*
 * Public-Facing Functionality
 *----------------------------------------------------------------------------*/

require_once( plugin_dir_path( __FILE__ ) . 'includes/class-feed-manager.php' );
require_once( plugin_dir_path( __FILE__ ) . 'includes/timber-feed.php' );

add_action( 'plugins_loaded', array( 'FeedManager', 'get_instance' ) );


/*----------------------------------------------------------------------------*
 * Dashboard and Administrative Functionality
 *----------------------------------------------------------------------------*/

if ( is_admin() && ( ! defined( 'DOING_AJAX' ) || ! DOING_AJAX ) ) {

	require_once( plugin_dir_path( __FILE__ ) . 'includes/class-feed-manager-admin.php' );
	add_action( 'plugins_loaded', array( 'FeedManagerAdmin', 'get_instance' ) );

}
