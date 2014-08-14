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
			array( 'jquery', 'underscore' ),
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
	 *
	 * @param     object  $post  WordPress post object
	 */
	public function meta_box_feed( $post ) {
		$feed_post = new TimberFeed( $post->ID );
	  $ids    = array_keys( $feed_post->filter_feed('pinned', false) );
	  $pinned = array_keys( $feed_post->filter_feed('pinned', true ) );

		Timber::render('views/feed.twig', array(
			'posts' => $feed_post->get_posts( array( 'show_hidden' => true ) ),
			'post_ids'    => implode( ',', $ids ),
			'post_pinned' => implode( ',', $pinned ),
			'nonce' => wp_nonce_field('fm_feed_nonce', 'fm_feed_meta_box_nonce', true, false),
			'feed_meta' => array(
				// 0 => 'Top Story',
				// 1 => 'Secondary Story',
				// 2 => 'Videos',
				// 10 => 'Recent Stories'
			)
		));
	}


	/**
	 * Render Post Add metabox
	 *
	 * @since     1.0.0
	 *
	 * @param     object  $post  WordPress post object
	 */
	public function meta_box_add( $post ) {
		Timber::render('views/add.twig');
	}


	/**
	 * Render Rules metabox
	 *
	 * @since     1.0.0
	 *
	 * @param     object  $post  WordPress post object
	 */
	public function meta_box_rules( $post ) {
		$feed_post = new TimberFeed( $post->ID );

		// $taxonomies = get_taxonomies(array('public' => true), 'objects');
		// foreach ($taxonomies as $slug => &$taxonomy) {
		// 	if ( $taxonomy->meta_box_cb != 'post_tags_meta_box' ) {
		// 		$taxonomy->terms = Timber::get_terms( $slug );
		// 	}
		// }

		$context = array(
			'post' => $feed_post,
			'rules' => $feed_post->fm_feed_rules,
			'query' => $feed_post->query

			// 'taxonomies' => $taxonomies,
			// 'post_types' => get_post_types(array(
			// 	'public' => true
			// )),
		);
		//print_r($context);

		Timber::render('views/rules.twig', array_merge(Timber::get_context(), $context));
	}


	/**
	 * Save the feed metadata
	 *
	 * @since     1.0.0
	 *
	 * @param     integer  $feed_id  Feed Post ID
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

    $feed = new TimberFeed( $feed_id );

    if ( isset( $_POST['fm_feed_rules'] ) ) {
    	$feed->fm_feed_rules = $_POST['fm_feed_rules'];

    	$feed->query['tax_query'] = array('relation' => 'OR');
    	$categories = explode( ',', $_POST['fm_feed_rules']['categories'] );
    	$tags       = explode( ',', $_POST['fm_feed_rules']['tags'] );

    	if ( $categories ) {
    		$feed->query['tax_query'][] = array(
    			'taxonomy' => 'category',
    			'field' => 'id',
    			'terms' => $this->encode_terms( 'category', $categories )
    		);
    	}

    	if ( $tags ) {
    		$feed->query['tax_query'][] = array(
    			'taxonomy' => 'post_tag',
    			'field' => 'id',
    			'terms' => $this->encode_terms( 'post_tag', $tags )
    		);
    	}
    }

    if ( isset( $_POST['fm_sort'] ) ) {
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
	  }

	   $feed->save_feed();
	}


	/**
	 * Convert comma-separated list of terms to term IDs
	 *
	 * @since     1.0.0
	 *
	 * @param     string   $taxonomy        taxonomy slug (category, post_tag, etc.)
	 * @param     string   $terms           comma-separated list of term slugs
	 * @param     boolean  $return_objects  return term objects if true, IDs if false
	 *
	 * @return    array   array of term IDs
	 */
	public function encode_terms( $taxonomy, $terms, $return_objects = false ) {
		if ( !is_array($terms) ) $terms = explode( ",", $terms );

		$output = array();

		foreach ( $terms as &$term ) {
			$term = trim($term);
			$term = get_term_by( 'slug', $term, $taxonomy );
			$output[] = $term->term_id;
		}

		return $return_objects ? $terms : $output;
	}

	/**
	 * Convert term IDs to a usable array with stubs, names, and IDs
	 *
	 * @since     1.0.0
	 *
	 * @param     string  $taxonomy  taxonomy slug (category, post_tag, etc.)
	 * @param     string  $terms     comma-separated list of term IDs
	 *
	 * @return    array   array of arrays with taxonomy name, slug, and id
	 */
	public function decode_terms( $taxonomy, $terms ) {

	}


	/**
	 * Update feeds whenever any post status is changed
	 *
	 * @since     1.0.0
	 *
	 * @param     string  $new   new post status
	 * @param     string  $old   old post status
	 * @param     object  $post  WordPress post object
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


	/**
	 * Add help text to feed edit page
	 *
	 * @since     1.0.0
	 */
	function add_help_text() {
	  $screen = get_current_screen();

	  // Return early if we're not on the book post type.
	  if ( 'fm_feed' != $screen->id ) return;

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


	/**
	 * Respond to admin heartbeat with feed IDs
	 *
	 * @since     1.0.0
	 *
	 * @param     array   $response   default WordPress heartbeat response
	 * @param     array   $data       data included with WordPress heartbeat request
	 * @param     string  $screen_id  admin screen slug
	 *
	 * @return    array   WordPress heartbeat response
	 */
	public function ajax_heartbeat( $response, $data, $screen_id ) {

		if ( $screen_id == 'fm_feed' && isset( $data['wp-refresh-post-lock'] ) ) {
		  $feed_post = new TimberFeed( $data['wp-refresh-post-lock']['post_id'] );
		  $ids = array_keys( $feed_post->filter_feed('pinned', false) );
		  $response['fm_feed_ids'] = implode( ',', $ids );

		  $pinned = array_keys( $feed_post->filter_feed('pinned', true) );
		  $response['fm_feed_pinned'] = implode( ',', $pinned );
		}

		return $response;
	}


	/**
	 * Retrieve rendered post stubs
	 *
	 * @since     1.0.0
	 *
	 * @param     array   $request   AJAX request (uses $_POST instead)
	 */
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


	/**
	 * Retrieve search results
	 *
	 * @since     1.0.0
	 *
	 * @param     array   $request   AJAX request (uses $_POST instead)
	 */
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


	/**
	 * Send AJAX response
	 *
	 * @since     1.0.0
	 *
	 * @param     string   $status   AJAX status (error|success)
	 * @param     array    $data     data with which to respond
	 */
	public function ajax_respond( $status = 'error', $data = array() ) {
		echo( json_encode( array(
			'status' => $status,
			'data' => $data
		)));
		die();
	}


}
