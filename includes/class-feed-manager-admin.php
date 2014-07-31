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
	 * Slug of the plugin screen.
	 *
	 * @since    1.0.0
	 *
	 * @var      string
	 */
	protected $plugin_screen_hook_suffix = null;

	/**
	 * Initialize the plugin by loading admin scripts & styles and adding a
	 * settings page and menu.
	 *
	 * @since     1.0.0
	 */
	private function __construct() {

		/*
		 * Call $plugin_slug from public plugin class.
		 */
		$plugin = FeedManager::get_instance();
		$this->plugin_slug = $plugin->get_plugin_slug();
		$this->post_type_slug = $plugin->get_post_type_slug();

		// Create post type


		// Load admin style sheet and JavaScript.
		add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_admin_styles' ) );
		add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_admin_scripts' ) );

		// Add the options page and menu item.
		//add_action( 'admin_menu', array( $this, 'add_plugin_admin_menu' ) );

		// Feed edit page metaboxes
		add_action( 'add_meta_boxes', array( $this, 'add_meta_boxes' ) );

		// Saving Feeds
		add_action( 'save_post', array( $this, 'save_feed' ) );

		// Add an action link pointing to the options page.
		$plugin_basename = plugin_basename( plugin_dir_path( realpath( dirname( __FILE__ ) ) ) . $this->plugin_slug . '.php' );
		add_filter( 'plugin_action_links_' . $plugin_basename, array( $this, 'add_action_links' ) );

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

		wp_enqueue_style( $this->plugin_slug .'-admin-styles', plugins_url( '../assets/css/style.css', __FILE__ ), array(), FeedManager::VERSION );

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
	 * Add settings action link to the plugins page.
	 *
	 * @since    1.0.0
	 */
	public function add_action_links( $links ) {

		return array_merge(
			array(
				'manage' => '<a href="' . admin_url( 'edit.php?post_type=' . $this->post_type_slug ) . '">' . __( 'Manage Feeds', $this->plugin_slug ) . '</a>'
			),
			$links
		);

	}


	/**
	 * Add meta boxes to Feed edit page
	 *
	 * @since     1.0.0
	 */
	public function add_meta_boxes() {
		add_meta_box('feed_box_feed',  'Feed',  array( $this, 'meta_box_feed'  ), $this->post_type_slug);
		add_meta_box('feed_box_rules', 'Rules', array( $this, 'meta_box_rules' ), $this->post_type_slug, 'side');
	}


	/**
	 * Render feed list metabox
	 *
	 * @since     1.0.0
	 */
	public function meta_box_feed( $post ) {
		$feed_post = new TimberFeed( $post->ID );
		$context = array();

		// Get the full feed
		$context['posts'] = $feed_post->get_posts( array( 'show_hidden' => true ) );

		// Get what the feed would be without stickied posts
		$unaltered_posts = $feed_post->get_unfiltered_posts( array( 'show_hidden' => true ) );
		$unaltered_post_ids = array();
		foreach ($unaltered_posts as $post) {
			$unaltered_post_ids[] = $post->ID;
		}
		$context['unaltered_posts'] = implode(",", $unaltered_post_ids);

		Timber::render('views/feed.twig', $context);
	}


	/**
	 * Render feed rules metabox
	 *
	 * @since     1.0.0
	 *
	 * @todo      Use TimberFeed here
	 */
	public function meta_box_rules( $post ) {
		$fields = get_post_custom( $post->ID );
		$rules = isset( $fields['fm_feed_rules'] ) ? esc_attr( $fields['fm_feed_rules'][0] ) : '';

		$context = Timber::get_context();
		$context['fm_feed_rules'] = $rules;
		$context['nonce'] = wp_nonce_field('fm_feed_nonce', 'fm_feed_meta_box_nonce', true, false);
		Timber::render('views/rules.twig', $context);
	}


	/**
	 * Save the feed metadata
	 *
	 * @since     1.0.0
	 *
	 * @todo      Move this to TimberFeed
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


	    $feed = array(
	    	'data'   => $data,
	    	'hidden' => $hidden
	    );

	    update_post_meta( $feed_id, 'fm_feed', $feed );
	  }
	}

}
