<?php
/**
 * Feed Manager.
 *
 * @package   Feed_Manager_Admin
 * @author    Chris Voll + Upstatement
 * @license   GPL-2.0+
 * @link      http://upstatement.com
 * @copyright 2014 Upstatement
 */

/**
 * Plugin class. This class should ideally be used to work with the
 * administrative side of the WordPress site.
 *
 * If you're interested in introducing public-facing
 * functionality, then refer to `class-plugin-name.php`
 *
 * @package Feed_Manager_Admin
 * @author  Chris Voll + Upstatement
 */
class Feed_Manager_Admin {

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
		$plugin = Feed_Manager::get_instance();
		$this->plugin_slug = $plugin->get_plugin_slug();

		// Load admin style sheet and JavaScript.
		add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_admin_styles' ) );
		add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_admin_scripts' ) );

		// Add the options page and menu item.
		add_action( 'admin_menu', array( $this, 'add_plugin_admin_menu' ) );

		// Feed edit page metaboxes
		add_action( 'add_meta_boxes', array( $this, 'add_meta_boxes' ) );

		// Saving Feeds
		add_action( 'save_post', array( $this, 'save_feed' ) );

		// Add an action link pointing to the options page.
		$plugin_basename = plugin_basename( plugin_dir_path( realpath( dirname( __FILE__ ) ) ) . $this->plugin_slug . '.php' );
		add_filter( 'plugin_action_links_' . $plugin_basename, array( $this, 'add_action_links' ) );

		/*
		 * Define custom functionality.
		 *
		 * Read more about actions and filters:
		 * http://codex.wordpress.org/Plugin_API#Hooks.2C_Actions_and_Filters
		 */
		//add_action( '@TODO', array( $this, 'action_method_name' ) );
		//add_filter( '@TODO', array( $this, 'filter_method_name' ) );

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

		wp_enqueue_style( $this->plugin_slug .'-admin-styles', plugins_url( '../assets/css/style.css', __FILE__ ), array(), Feed_Manager::VERSION );

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

		wp_enqueue_script( $this->plugin_slug . '-admin-script', plugins_url( '../assets/js/script.js', __FILE__ ), array( 'jquery' ), Feed_Manager::VERSION );

	}

	/**
	 * Register the administration menu for this plugin into the WordPress Dashboard menu.
	 *
	 * @since    1.0.0
	 */
	public function add_plugin_admin_menu() {

		/*
		 * Add a settings page for this plugin to the Settings menu.
		 *
		 * NOTE:  Alternative menu locations are available via WordPress administration menu functions.
		 *
		 *        Administration Menus: http://codex.wordpress.org/Administration_Menus
		 *
		 * @TODO:
		 *
		 * - Change 'Page Title' to the title of your plugin admin page
		 * - Change 'Menu Text' to the text for menu item for the plugin settings page
		 * - Change 'manage_options' to the capability you see fit
		 *   For reference: http://codex.wordpress.org/Roles_and_Capabilities
		 */
		$this->plugin_screen_hook_suffix = add_options_page(
			__( 'Page Title', $this->plugin_slug ),
			__( 'Menu Text', $this->plugin_slug ),
			'manage_options',
			$this->plugin_slug,
			array( $this, 'display_plugin_admin_page' )
		);

	}

	/**
	 * Render the settings page for this plugin.
	 *
	 * @since    1.0.0
	 */
	public function display_plugin_admin_page() {
		//include_once( 'views/admin.php' );
	}

	/**
	 * Add settings action link to the plugins page.
	 *
	 * @since    1.0.0
	 */
	public function add_action_links( $links ) {

		return array_merge(
			array(
				'settings' => '<a href="' . admin_url( 'options-general.php?page=' . $this->plugin_slug ) . '">' . __( 'Settings', $this->plugin_slug ) . '</a>'
			),
			$links
		);

	}


	public function add_meta_boxes() {
		add_meta_box('feed_box_feed',  'Feed',  array( $this, 'meta_box_feed'  ), 'fm_feed');
		add_meta_box('feed_box_rules', 'Rules', array( $this, 'meta_box_rules' ), 'fm_feed');
	}

	public function meta_box_feed($post) {
		$feed = get_post_meta( $post->ID, 'fm_feed' )[0];
		echo("<pre>");
		print_r($feed);
		echo("</pre>");

		$context = Timber::get_context();

		// Get the full feed
		$context['posts'] = $this->build_feed( $feed );

		// Get what the feed would be without stickied posts
		$unaltered_posts = Timber::get_posts(array(
			'posts_per_page' => 10
		));
		$unaltered_post_ids = array();
		foreach ($unaltered_posts as $post) {
			$unaltered_post_ids[] = $post->ID;
		}
		$context['unaltered_posts'] = implode(",", $unaltered_post_ids);

		Timber::render('views/feed.twig', $context);
	}

	public function meta_box_rules($post) {
		$fields = get_post_custom( $post->ID );
		$rules = isset( $fields['fm_feed_rules'] ) ? esc_attr( $fields['fm_feed_rules'][0] ) : '';

		$context = Timber::get_context();
		$context['fm_feed_rules'] = $rules;
		$context['nonce'] = wp_nonce_field('fm_feed_nonce', 'fm_feed_meta_box_nonce', true, false);
		Timber::render('views/rules.twig', $context);
	}


	public function save_feed( $post_id ) {
    // Bail if we're doing an auto save
    if( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) return;
     
    // if our nonce isn't there, or we can't verify it, bail
    if( !isset( $_POST['fm_feed_meta_box_nonce'] ) || !wp_verify_nonce( $_POST['fm_feed_meta_box_nonce'], 'fm_feed_nonce' ) ) return;
     
    // if our current user can't edit this post, bail
    if( !current_user_can( 'edit_post' ) ) return;

    if ( isset( $_POST['fm_feed_pinned'] ) ) {
    	update_post_meta( $post_id, 'fm_feed_pinned', $_POST['fm_feed_pinned'] );
    }

    if ( isset( $_POST['fm_feed_rules'] ) ) {
    	update_post_meta( $post_id, 'fm_feed_rules', $_POST['fm_feed_rules'] );
    }

    if (isset( $_POST['fm_sort'] ) ) {

	    $pinned = array();
	    $hidden = array();
	    $cached = array();

	    foreach($_POST['fm_sort'] as $i => $item) {
	    	if (isset($_POST['fm_pin'][$item])) {
	    		$pinned[$i] = $item;
	    	}
	    	if (isset($_POST['fm_hide'][$item])) {
	    		$hidden[] = $item;
	    	}
		    $cached[] = $item;
	    }

	    $feed = array(
	    	'pinned' => $pinned,
	    	'hidden' => $hidden,
	    	'cached' => $cached
	    );

	    update_post_meta( $post_id, 'fm_feed', $feed );
	  }
	}

	public function build_feed( $feed ) {
		if ( isset($feed['cached'] ) ) {

			$posts = Timber::get_posts(array(
				'post__in' => $feed['cached'],
				'orderby' => 'post__in'
			));

			foreach ($posts as &$post) {
				if ( in_array( $post->ID, $feed['pinned'] ) ) {
					$post->pinned = true;
				}
				if ( in_array( $post->ID, $feed['hidden'] ) ) {
					$post->hidden = true;
				}
			}
		} else {

			$posts = Timber::get_posts(array(
				'posts_per_page' => 10
			));

		}

		return $posts;
	}













	/**
	 * NOTE:     Actions are points in the execution of a page or process
	 *           lifecycle that WordPress fires.
	 *
	 *           Actions:    http://codex.wordpress.org/Plugin_API#Actions
	 *           Reference:  http://codex.wordpress.org/Plugin_API/Action_Reference
	 *
	 * @since    1.0.0
	 */
	public function action_method_name() {
		// @TODO: Define your action hook callback here
	}

	/**
	 * NOTE:     Filters are points of execution in which WordPress modifies data
	 *           before saving it or sending it to the browser.
	 *
	 *           Filters: http://codex.wordpress.org/Plugin_API#Filters
	 *           Reference:  http://codex.wordpress.org/Plugin_API/Filter_Reference
	 *
	 * @since    1.0.0
	 */
	public function filter_method_name() {
		// @TODO: Define your filter hook callback here
	}

}
