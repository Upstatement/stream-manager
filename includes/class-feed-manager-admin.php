<?php
/**
 * Feed Manager.
 *
 * @package   FeedManagerAdmin
 * @author    Chris Voll + Upstatement
 * @license   GPL-2.0+
 * @link      http://upstatement.com
 * @copyright 2014 Upstatement
 */

/**
 * @package FeedManagerAdmin
 * @author  Chris Voll + Upstatement
 */
class FeedManagerAdmin {

	/**
	 * Instance of this class.
	 *
	 * @since    1.0.0
	 *
	 * @var      object
	 */
	protected static $instance = null;

	/**
	 * Instance of FeedManager.
	 *
	 * @since    1.0.0
	 *
	 * @var      object
	 */
	public $plugin = null;

	/**
	 * Initialize the plugin
	 *
	 * @since     1.0.0
	 */
	private function __construct() {
		$this->plugin = FeedManager::get_instance();
		$this->plugin_slug    = $this->plugin->get_plugin_slug();
		$this->post_type_slug = $this->plugin->get_post_type_slug();

		// Load admin styles and scripts
		add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_admin_styles' ) );
		add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_admin_scripts' ) );

		// Feed edit page metaboxes
		add_action( 'add_meta_boxes', array( $this, 'add_meta_boxes' ) );

		// Saving Feeds
		add_action( 'save_post', array( $this, 'save_feed' ) );

		// Saving Posts (= updating feeds)
		add_action( 'transition_post_status',  array( $this, 'on_save_post' ), 10, 3 );

		// Help text
		add_action( 'admin_head', array( $this, 'add_help_text' ), 10, 3 );
	}

	public static function is_active() {
		return get_current_screen()->id == "fm_feed";
	}

	/**
	 * Return an instance of this class.
	 *
	 * @since     1.0.0
	 *
	 * @return    object    A single instance of this class.
	 */
	public static function get_instance() {

		// If the single instance hasn't been set, set it now.
		if ( null == self::$instance ) {
			self::$instance = new self;
		}

		return self::$instance;
	}

	/**
	 * Register and enqueue admin-specific style sheet.
   *
	 * @since     1.0.0
	 *
	 * @return    null    Return early if no settings page is registered.
	 */
	public function enqueue_admin_styles() {
		if ( !$this->is_active() ) return;

		wp_enqueue_style(
			$this->plugin_slug .'-admin-styles',
			plugins_url( '../assets/css/style.css', __FILE__ ),
			array(),
			FeedManager::VERSION
		);
	}

	/**
	 * Register and enqueue admin-specific JavaScript.
	 *
	 * @since     1.0.0
	 *
	 * @return    null    Return early if no settings page is registered.
	 */
	public function enqueue_admin_scripts() {
		if ( !$this->is_active() ) return;

		wp_enqueue_script(
			$this->plugin_slug . '-admin-script',
			plugins_url( '../assets/js/script.js', __FILE__ ),
			array( 'jquery', 'backbone', 'underscore' ),
			FeedManager::VERSION
		);
	}


	/**
	 * Add meta boxes to Feed edit page
	 *
	 * @since     1.0.0
	 */
	public function add_meta_boxes() {
		add_meta_box(
			'feed_box_feed',
			'Feed',
			array( $this, 'meta_box_feed'  ),
			$this->post_type_slug,
			'normal'
		);
		add_meta_box(
			'feed_box_add',
			'Add Post',
			array( $this, 'meta_box_add' ),
			$this->post_type_slug,
			'side'
		);
		add_meta_box(
			'feed_box_rules',
			'Rules',
			array( $this, 'meta_box_rules' ),
			$this->post_type_slug,
			'side'
		);
	}


	/**
	 * Render Feed metabox
	 *
	 * @since     1.0.0
	 */
	public function meta_box_feed( $post ) {
		$feed_post = new TimberFeed( $post->ID );

		Timber::render('views/feed.twig', array(
			'posts' => $feed_post->get_posts( array( 'show_hidden' => true ) ),
			'nonce' => wp_nonce_field('fm_feed_nonce', 'fm_feed_meta_box_nonce', true, false)
		));
	}


	/**
	 * Render Post Add metabox
	 *
	 * @since     1.0.0
	 */
	public function meta_box_add( $post ) {
		$feed_post = new TimberFeed( $post->ID );

		Timber::render('views/add.twig', array(
			//'posts' => $feed_post->get_posts( array( 'show_hidden' => true ) ),
		));
	}


	/**
	 * Render Rules metabox
	 *
	 * @since     1.0.0
	 */
	public function meta_box_rules( $post ) {
		$feed_post = new TimberFeed( $post->ID );

		Timber::render('views/rules.twig', array(
			'fm_feed_rules' => $feed_post->fm_feed_rules
		));
	}


	/**
	 * Save the feed metadata
	 *
	 * @since     1.0.0
	 *
	 * @todo      Move Rules update to TimberFeed::save_feed
	 */
	public function save_feed( $feed_id ) {
    // Bail if we're doing an auto save
    if( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) return;
     
    // if our nonce isn't there, or we can't verify it, bail
    if( !isset( $_POST['fm_feed_meta_box_nonce'] ) || !wp_verify_nonce( $_POST['fm_feed_meta_box_nonce'], 'fm_feed_nonce' ) ) return;
     
    // if our current user can't edit this post, bail
    if( !current_user_can( 'edit_post' ) ) return;

    if ( isset( $_POST['fm_feed_rules'] ) ) {
    	update_post_meta( $feed_id, 'fm_feed_rules', $_POST['fm_feed_rules'] );
    }

    if ( isset( $_POST['fm_sort'] ) ) {
    	$feed = new TimberFeed( $feed_id );
	    $data   = array();
	    $hidden = array();

	    foreach ( $_POST['fm_sort'] as $i => $post_id ) {
	    	if ( isset($_POST['fm_hide'][$post_id]) ) {
	    		$hidden[] = $post_id;
	    	} else {
		    	$data[] = array(
		    		'id' => $post_id,
		    		'pinned' => isset($_POST['fm_pin'][$post_id])
		    	);
		    }
	    }

	    $feed->fm_feed = array(
	    	'data' => $data,
	    	'hidden' => $hidden
	    );

	    $feed->save_feed();
	  }
	}


	/**
	 * Update feeds whenever any post status is changed
	 *
	 * @since     1.0.0
	 */
	public function on_save_post( $new, $old, $post ) {
		if ( $post->post_type == 'fm_feed' ) return;

		if ( $old == 'publish' && $new != 'publish' ) {
			// Remove from feeds
			$feeds = $this->plugin->get_feeds();
			foreach ( $feeds as $feed ) {
				$feed->remove_post( $post->ID );
			}
		}

		if ( $old != 'publish' && $new == 'publish' ) {
			// Add to feeds
			$feeds = $this->plugin->get_feeds();
			foreach ( $feeds as $feed ) {
				$feed->insert_post( $post->ID );
			}
		}
	}




	function add_help_text() {
	  $screen = get_current_screen();

	  // Return early if we're not on the book post type.
	  if ( 'fm_feed' != $screen->post_type )
	    return;

	  // Setup help tab args.
	  $tabs = array(
	  	array(
		    'id'      => 'fm_feed_1', //unique id for the tab
		    'title'   => 'Arranging Posts', //unique visible title for the tab
		    'content' => '<h3>Arranging Posts</h3><p>Help content</p>',  //actual help text
		  ),
	  	array(
		    'id'      => 'fm_feed_2', //unique id for the tab
		    'title'   => 'Adding Posts', //unique visible title for the tab
		    'content' => '<h3>Adding &amp; Removing Posts</h3><p>Help content</p>',  //actual help text
		  ),
	  	array(
		    'id'      => 'fm_feed_3', //unique id for the tab
		    'title'   => 'Whatever Else', //unique visible title for the tab
		    'content' => '<h3>Whatever Else</h3><p>Help content</p>',  //actual help text
		  )
		);
	  
	  // Add the help tab.
	  foreach ( $tabs as $tab ) {
		  $screen->add_help_tab( $tab );
		}
	}

}
