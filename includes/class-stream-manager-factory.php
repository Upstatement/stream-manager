<?php
/**
 * StreamManagerFactory.
 *
 * This makes streams programatically instead of via the admin
 *
 * @package  StreamManager
 * @author   Chris Voll + Jared Novack + Upstatement
 * @link   http://upstatement.com
 * @copyright 2014 Upstatement
 */

class StreamManagerFactory {

	var $slug;
	var $plugin = null;

	function __construct( $slug ) {
		$this->slug = $slug;
		$this->plugin = StreamManager::get_instance();
		add_action( 'admin_init', array( $this, 'create_post' ) );
	}

	function does_stream_exist( $slug ) {
		$streams = $this->plugin->get_streams( array( 'name' => $slug ) );
		if ( count( $streams ) && is_array( $streams ) ) {
			return $streams[0];
		}
	}

	function create_post( ) {
		$stream = $this->does_stream_exist( $this->slug );
		if ( $stream ) {
			$this->ID = $stream->ID;
			return;
		}

		$post_data = array( 'post_type' => $this->plugin->get_post_type_slug(), 'post_name' => $this->slug, 'post_status' => 'publish' );
		$post = wp_insert_post( $post_data );
		$this->ID = $post->ID;

	}

	function set_post_type( $post_type ) {
		add_filter( 'stream-manager/options/slug='.$this->slug, function( $options, $stream ) use ( $post_type ) {
				$options['query']['post_type'] = $post_type;
				return $options;
			}, 10, 2 );
	}

	function set_labels( $labels ) {
		add_action( 'admin_init', function() use ( $labels ) {
				if ( is_string( $labels ) ) {
					wp_update_post( array( 'ID' => $this->ID, 'post_title' => $labels ) );
				}
			}, 11 );

	}

}
