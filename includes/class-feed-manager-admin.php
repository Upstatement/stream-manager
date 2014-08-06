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


		// Admin Page Helpers
		// ------------------

		// Load admin styles and scripts
		add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_admin_styles' ) );
		add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_admin_scripts' ) );

		// Feed edit page metaboxes
		add_action( 'add_meta_boxes', array( $this, 'add_meta_boxes' ) );

		// Help text
		add_action( 'admin_head', array( $this, 'add_help_text' ), 10, 3 );


		// Feed Manipulation
		// -----------------

		// Saving Feeds
		add_action( 'save_post', array( $this, 'save_feed' ) );

		// Saving Posts (= updating feeds)
		add_action( 'transition_post_status',  array( $this, 'on_save_post' ), 10, 3 );


		// AJAX Helpers
		// ------------

		// Heartbeat
		add_filter( 'heartbeat_received', array( $this, 'ajax_heartbeat' ), 10, 3 );

		// Retrieve rendered post stubs AJAX
		add_filter( 'wp_ajax_fm_feed_request', array( $this, 'ajax_retrieve_posts' ) );

		// Search posts AJAX
		add_filter( 'wp_ajax_fm_feed_search', array( $this, 'ajax_search_posts' ) );
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
	  $ids    = array_keys( $feed_post->filter_feed('pinned', false) );
	  $pinned = array_keys( $feed_post->filter_feed('pinned', true ) );

		Timber::render('views/feed.twig', array(
			'posts' => $feed_post->get_posts( array( 'show_hidden' => true ) ),
			'post_ids'    => implode( ',', $ids ),
			'post_pinned' => implode( ',', $pinned ),
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

	    $feed->repopulate_feed();
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
	  if ( 'fm_feed' != $screen->post_type ) return;

	  // Setup help tab args.
	  $tabs = array(
	  	array(
		    'id'      => 'fm_feed_1',
		    'title'   => 'About Feeds',
		    'content' => '<h3>About Feeds</h3><p>Help content</p>',
		  ),
	  	array(
		    'id'      => 'fm_feed_2',
		    'title'   => 'How to Use',
		    'content' => '<h3>How to Use</h3><p>Help content</p>',
		  ),
	  	array(
		    'id'      => 'fm_feed_3',
		    'title'   => 'Use in Theme',
		    'content' => implode("\n", array(
		    	'<h3>Use in Theme</h3>',
		    	'<p>',
		    		'<pre>$context[\'feed\'] = new TimberFeed(' . get_the_ID() . ');</pre>',
		    	'</p>',
		    	'<p>In your view file (twig):</p>',
		    	'<p><pre>{% for post in feed %}',
		    	'  {{ post.title }}',
		    	'{% endfor %}</pre></p>'
		    ))
		  )
		);
	  
	  // Add the help tab.
	  foreach ( $tabs as $tab ) {
		  $screen->add_help_tab( $tab );
		}
	}



	public function ajax_heartbeat( $response, $data, $screen_id ) {

		if( $screen_id == 'fm_feed' && isset( $data['wp-refresh-post-lock'] ) ) {
		  $feed_post = new TimberFeed( $data['wp-refresh-post-lock']['post_id'] );
		  $ids = array_keys( $feed_post->filter_feed('pinned', false) );
		  $response['fm_feed_ids'] = implode( ',', $ids );

		  $pinned = array_keys( $feed_post->filter_feed('pinned', true) );
		  $response['fm_feed_pinned'] = implode( ',', $pinned );
		}

		return $response;
	}


	public function ajax_retrieve_posts( $request ) {
		if ( !isset( $_POST['queue'] ) ) $this->ajax_respond( 'error' );

		$queue = $_POST['queue'];
		$output = array();

		foreach($queue as $i => $item) {
			$post = new TimberPost( $item['id'] );
			if ( !$post ) continue;
			$post->pinned = false;
			$output[ $item['id'] ] = array(
				'position' => $item['position'],
				'object' => Timber::compile('views/stub.twig', array(
					'post' => $post
				))
			);
		}
		$this->ajax_respond( 'success', $output );
	}


	public function ajax_search_posts( $request ) {
		if ( !isset( $_POST['query'] ) ) $this->ajax_respond( 'error' );

		// Search!
		$posts = Timber::get_posts(array(
			's' => $_POST['query'],
			'post_type' => 'post',
			'post_status' => 'publish',
			'posts_per_page' => 10
		));

		$output = array();

		foreach ( $posts as $post ) {
			$output[] = array(
				'id' => $post->ID,
				'title' => $post->title,
				'date' => $post->post_date,
				'human_date' => human_time_diff( strtotime( $post->post_date ) )
			);
		}

		$this->ajax_respond( 'success', $output );
	}


	public function ajax_respond( $status = 'error', $data = array() ) {
		echo( json_encode( array(
			'status' => $status,
			'data' => $data
		)));
		die();
	}


}
