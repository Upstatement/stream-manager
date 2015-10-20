<?php
/**
 * Stream Manager.
 *
 * @package   StreamManagerAdmin
 * @author    Chris Voll + Upstatement
 * @license   GPL-2.0+
 * @link      http://upstatement.com
 * @copyright 2014 Upstatement
 */

/**
 * @package StreamManagerAdmin
 * @author  Chris Voll + Upstatement
 */

class StreamManagerAdmin {

	/**
	 * Instance of this class.
	 *
	 * @since    1.0.0
	 *
	 * @var      object
	 */
	protected static $instance = null;

	/**
	 * Instance of StreamManager.
	 *
	 * @since    1.0.0
	 *
	 * @var      object
	 */
	public $plugin = null;

	public $default_query = array(
    	'post_type' => 'post',
    	'post_status' => 'publish',
    	'has_password' => false,
    	'ignore_sticky_posts' => true,

    	'posts_per_page' => 100,
    	'orderby' => 'post__in'
  	);

	/**
	 * Initialize the plugin
	 *
	 * @since     1.0.0
	 */
	private function __construct() {
		$this->plugin = StreamManager::get_instance();
		$this->plugin_slug    = $this->plugin->get_plugin_slug();
		$this->post_type_slug = $this->plugin->get_post_type_slug();


		// Admin Page Helpers
		// ------------------

		// Load admin styles and scripts
		add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_admin_styles' ) );
		add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_admin_scripts' ) );

		// Stream edit page metaboxes
		add_action( 'add_meta_boxes', array( $this, 'add_meta_boxes' ) );

		// Help text
		add_action( 'admin_head', array( $this, 'add_help_text' ), 10, 3 );


		// Stream Manipulation
		// -----------------

		// Saving Streams
		add_action( 'save_post', array( $this, 'save_stream' ) );

		// AJAX Helpers
		// ------------

		// Heartbeat
		add_filter( 'heartbeat_received', array( $this, 'ajax_heartbeat' ), 10, 3 );

		// Retrieve rendered post stubs AJAX
		add_filter( 'wp_ajax_sm_request', array( $this, 'ajax_retrieve_posts' ) );

		// Search posts AJAX
		add_filter( 'wp_ajax_sm_search', array( $this, 'ajax_search_posts' ) );

		// Retrieve posts for stream reload
		add_filter( 'wp_ajax_sm_reload', array( $this, 'ajax_retrieve_reload_posts' ) );

		add_filter( 'wp_terms_checklist_args', array( $this, 'stream_categories_helper' ), 10, 2 );
	}

	public static function is_active() {
		if ( function_exists( 'get_current_screen' ) ) {
			$current_screen = get_current_screen();
			if ( isset($current_screen) ) {
				return $current_screen->id == "sm_stream";
			}
		}
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
			StreamManager::VERSION
		);

		/*
		 * this limits the number of visible stream items via css
		 * when one is deleted, the stubs "move up", so they one at a time become visible
		 * https://github.com/Upstatement/stream-manager/issues/28
		 */
		//+ 1 because css is greedy! (it encompasses the number, so we want the *next* element)
		$display_limit = (int) apply_filters( $this->plugin_slug . '/stub_display_limit', 15 ) + 1;
		$display_limit_css = ".sm-posts .content:nth-of-type(n+{$display_limit}){
			opacity:0.4;
		}";
		wp_add_inline_style( $this->plugin_slug .'-admin-styles', $display_limit_css );
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
			StreamManager::VERSION
		);
	}


	/**
	 * Add meta boxes to Stream edit page
	 *
	 * @since     1.0.0
	 */
	public function add_meta_boxes() {
		add_meta_box(
			'stream_box_stream',
			'Stream',
			array( $this, 'meta_box_stream'  ),
			$this->post_type_slug,
			'normal'
		);
		add_meta_box(
			'stream_box_add',
			'Add Post',
			array( $this, 'meta_box_add' ),
			$this->post_type_slug,
			'side'
		);
		add_meta_box(
			'stream_box_zones',
			'Zones',
			array( $this, 'meta_box_zones' ),
			$this->post_type_slug,
			'side'
		);
	}


	/**
	 * Render Stream metabox
	 *
	 * @since     1.0.0
	 *
	 * @param     object  $post  WordPress post object
	 */
	public function meta_box_stream( $post ) {
		$stream_post = new TimberStream( $post->ID );
	  	$ids    = array_keys( $stream_post->filter_stream('pinned', false) );
	  	$pinned = array_keys( $stream_post->filter_stream('pinned', true ) );
	  	$layouts = $stream_post->get('layouts');
	  	$layout = $layouts['layouts'][ $layouts['active'] ];

		Timber::render('views/stream.twig', array(
			'posts' => $stream_post->get_posts( array( 'show_hidden' => true ) ),
			'post_ids'    => implode( ',', $ids ),
			'post_pinned' => implode( ',', $pinned ),
			'nonce' => wp_nonce_field('sm_nonce', 'sm_meta_box_nonce', true, false),
			'layout' => $layout
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
	 * Render Layout metabox
	 *
	 * @since     1.0.0
	 *
	 * @param     object  $post  WordPress post object
	 */
	public function meta_box_zones( $post ) {
		$stream_post = new TimberStream( $post->ID );
		$layouts = $stream_post->get('layouts');

		$context = array(
			'post'         => $stream_post,
			'layouts'      => $layouts,
			'layouts_json' => JSON_encode( $layouts )
		);

		Timber::render('views/zones.twig', array_merge(Timber::get_context(), $context));
	}


	/**
	 * Render Rules metabox
	 *
	 * @since     1.0.0
	 *
	 * @param     object  $post  WordPress post object
	 */
	public function meta_box_rules( $post ) {
		$stream_post = new TimberStream( $post->ID );

		$context = array(
			'post'    => $stream_post,
			'rules'   => $stream_post->sm_rules,
		);

		Timber::render('views/rules.twig', array_merge(Timber::get_context(), $context));
	}


	/**
	 * Save the stream metadata
	 *
	 * @since     1.0.0
	 * @param     integer  $stream_id  Stream Post ID
	 * @todo      Move Rules update to TimberStream::save_stream
	 */
	public function save_stream( $stream_id, $apply_security_checks = true ) {
	    // Bail if we're doing an auto save
	    if( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) return;

	    if ( $apply_security_checks ) {

	    	// if our nonce isn't there, or we can't verify it, bail
		    if( !isset( $_POST['sm_meta_box_nonce'] ) || !wp_verify_nonce( $_POST['sm_meta_box_nonce'], 'sm_nonce' ) ) return;

		    // if our current user can't edit this post, bail
		    if( !current_user_can( 'edit_post', $stream_id ) ) return;
		}

	    $stream = new TimberStream( $stream_id );

	  	$stream->sm_rules = array();

	  	$tax_input = apply_filters('stream-manager/taxonomy/'.$stream->slug, array());

	  	if ( $tax_input ) {
	  		foreach ( $tax_input as $taxonomy => $terms ) {
	  			$stream->sm_rules[$taxonomy] = $terms;
	  		}
	  	}

	  	$stream->sm_query = array_merge($this->default_query, $stream->sm_query);
	  	$stream->sm_query = $this->default_query;

	  	$stream->sm_query['tax_query'] = StreamManagerUtilities::build_tax_query( $stream->sm_rules );
	  	$stream->set('query', $stream->sm_query);

	  	// Sorting
	    if ( isset( $_POST['sm_sort'] ) ) {
		    $data = array();

		    foreach ( $_POST['sm_sort'] as $i => $post_id ) {
		    	$data[] = array(
		    		'id' => $post_id,
		    		'pinned' => isset($_POST['sm_pin'][$post_id])
		    	);
		    }

		    $stream->set('stream', $data);
		    $stream->repopulate_stream();
	  	}

		// Layouts
		if ( isset( $_POST['sm_layouts'] ) ) {
			$stream->set('layouts', JSON_decode( stripslashes($_POST['sm_layouts']), true ) );
		}

	  	// Save the stream, and prevent and infinite loop
		remove_action( 'save_post', array( $this, 'save_stream' ) );
	  	$stream->save_stream();
	  	add_action( 'save_post', array( $this, 'save_stream' ) );
	}


	/**
	 * Add help text to stream edit page
	 *
	 * @since     1.0.0
	 */
	function add_help_text() {
	  $screen = get_current_screen();

	  // Return early if we're not on the book post type.
	  if ( 'sm_stream' != $screen->id ) return;

	  // Setup help tab args.
	  $tabs = array(
	  	array(
		    'id'      => 'sm_stream_1',
		    'title'   => 'About Streams',
		    'content' => '<h3>About Streams</h3><p>Help content</p>',
		  ),
	  	array(
		    'id'      => 'sm_stream_2',
		    'title'   => 'How to Use',
		    'content' => '<h3>How to Use</h3><p>Help content</p>',
		  ),
	  	array(
		    'id'      => 'sm_stream_3',
		    'title'   => 'Use in Theme',
		    'content' => implode("\n", array(
		    	'<h3>Use in Theme</h3>',
		    	'<p>',
		    		'<pre>$context[\'stream\'] = new TimberStream(' . get_the_ID() . ');</pre>',
		    	'</p>',
		    	'<p>In your view file (twig):</p>',
		    	'<p><pre>{% for post in stream %}',
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

	public function stream_categories_helper( $args, $post_id ) {
		if ( $this->is_active() ) {
			$stream = new TimberStream( $post_id );
			if ( isset($stream->sm_rules['category']) ) {
				$args['selected_cats'] = $stream->sm_rules['category'];
			}
		}
		return $args;
	}


	/**
	 * Respond to admin heartbeat with stream IDs
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

		if ( $screen_id == 'sm_stream' && isset( $data['wp-refresh-post-lock'] ) ) {
		  $stream_post = new TimberStream( $data['wp-refresh-post-lock']['post_id'] );
		  $ids = array_keys( $stream_post->filter_stream('pinned', false) );
		  $response['sm_ids'] = implode( ',', $ids );

		  $pinned = array_keys( $stream_post->filter_stream('pinned', true) );
		  $response['sm_pinned'] = implode( ',', $pinned );
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
	 * Retrieve rendered post stubs when reloading all posts in
	 * in the admin UI
	 *
	 * @since     1.0.0
	 *
	 * @param     array   $request   AJAX request (uses $_POST instead)
	 */
	public function ajax_retrieve_reload_posts( $request ) {
		if ( !isset( $_POST['stream_id'] ) || !isset( $_POST['taxonomies'] ) ) $this->ajax_respond( 'error' );

		$stream = new TimberPost( $_POST['stream_id'] );
		$output = array();

		// Build the query
		$query = ($stream && $stream->sm_query) ? $stream->sm_query : $this->default_query;
		$query['tax_query'] = StreamManagerUtilities::build_tax_query( $_POST['taxonomies'] );


		if ( isset($_POST['exclude']) ) {
			$query['post__not_in'] = $_POST['exclude'];
		}
		$query = apply_filters('stream-manager/query', $query);
		$query = apply_filters('stream-manager/query/slug='.$stream->slug, $query);
		$posts = Timber::get_posts($query);
		foreach ( $posts as $post ) {
			$output[] = Timber::compile('views/stub.twig', array( 'post' => $post ));
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


	public function ajax_search_terms( $request ) {
		if ( !isset( $_POST['query'] ) || !isset( $_POST['taxonomy'] ) ) $this->ajax_respond( 'error' );

		// Search terms!
		$terms = Timber::get_terms( $_POST['taxonomy'], array(
			'name__like' => $_POST['query']
		));

		$output = array();

		foreach ( $terms as $term ) {
			$output[] = array(
				'id' => $term->term_id,
				'slug' => $term->slug,
				'name' => $term->name
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
