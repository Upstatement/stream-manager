<?php
/**
 * Feed Manager.
 *
 * @package   FeedManager
 * @author    Chris Voll + Upstatement
 * @license   GPL-2.0+
 * @link      http://upstatement.com
 * @copyright 2014 Upstatement
 */

/**
 * Plugin class. This class should ideally be used to work with the
 * public-facing side of the WordPress site.
 *
 * If you're interested in introducing administrative or dashboard
 * functionality, then refer to `class-plugin-name-admin.php`
 *
 * @package FeedManager
 * @author  Chris Voll + Upstatement
 */
class FeedManager {

	/**
	 * Plugin version, used for cache-busting of style and script file references.
	 *
	 * @since   1.0.0
	 *
	 * @var     string
	 */
	const VERSION = '1.0.0';

	/**
	 * Unique identifier for your plugin.
	 *
	 *
	 * The variable name is used as the text domain when internationalizing strings
	 * of text. Its value should match the Text Domain file header in the main
	 * plugin file.
	 *
	 * @since    1.0.0
	 *
	 * @var      string
	 */
	protected $plugin_slug = 'feed-manager';


	/**
	 * Unique identifier for the feed post type.
	 *
	 * This needs to avoid conflicting with other plugins.
	 *
	 * @since    1.0.0
	 *
	 * @var      string
	 */
	protected $post_type_slug = 'fm_feed';

	/**
	 * Instance of this class.
	 *
	 * @since    1.0.0
	 *
	 * @var      object
	 */
	protected static $instance = null;

	public $feeds = null;

	/**
	 * Initialize the plugin
	 *
	 * @since     1.0.0
	 */
	private function __construct() {

		// Ensure that Timber is loaded
		if ( !self::check_dependencies() ) return;

		require_once( plugin_dir_path( __FILE__ ) . 'timber-feed.php' );

		add_action( 'init', array( $this, 'define_post_types' ), 0 );

		add_filter('post_updated_messages', array( $this, 'define_post_type_messages' ) );

		
	}

	/**
	 * Return the plugin slug.
	 *
	 * @since    1.0.0
	 *
	 * @return    Plugin slug variable.
	 */
	public function get_plugin_slug() {
		return $this->plugin_slug;
	}

	/**
	 * Return the post type slug.
	 *
	 * @since    1.0.0
	 *
	 * @return    Post type slug variable.
	 */
	public function get_post_type_slug() {
		return $this->post_type_slug;
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
	 * Ensure that Timber is loaded. Depending on the order that the
	 * plugins are activated, Timber may be loaded after the Feed
	 * Manager and needs to be loaded manually.
	 *
	 * @since     1.0.0
	 *
	 * @return    boolean    True if dependencies are met, false if not
	 */
	public function check_dependencies() {
		return class_exists('Timber');
	}

	/**
	 * Create the Feed post type, add to admin
	 *
	 * @since     1.0.0
	 */
	public function define_post_types() {
	  $labels = array(
	    'name'                => 'Feeds',
	    'singular_name'       => 'Feed',
	    'menu_name'           => 'Feeds',
	    'parent_item_colon'   => 'Parent Feed',
	    'all_items'           => 'Feeds',
	    'view_item'           => 'View Feed',
	    'add_new_item'        => 'Add New Feed',
	    'add_new'             => 'Add New',
	    'edit_item'           => 'Edit Feed',
	    'update_item'         => 'Update Feed',
	    'search_items'        => 'Search Feed',
	    'not_found'           => 'Not found',
	    'not_found_in_trash'  => 'Not found in Trash',
	  );
	  $args = array(
	    'label'               => $this->post_type_slug,
	    'description'         => 'Feed',
	    'labels'              => $labels,
	    'supports'            => array( 'title', 'revisions', ),
	    'hierarchical'        => false,
	    'public'              => false,
	    'show_ui'             => true,
	    'show_in_menu'        => true,
	    'show_in_nav_menus'   => false,
	    'show_in_admin_bar'   => false,
	    'menu_position'       => 5,
	    'menu_icon'           => 'dashicons-list-view',
	    'can_export'          => true,
	    'has_archive'         => false,
	    'exclude_from_search' => true,
	    'publicly_queryable'  => true,
	    'capability_type'     => 'post',
	  );
	  register_post_type( $this->post_type_slug, $args );
	}

	/**
	 * Add Feed post type messages
	 *
	 * @since     1.0.0
	 */
	function define_post_type_messages($messages) {
		global $post, $post_ID;
		$post_type = get_post_type( $post_ID );

		$obj = get_post_type_object($post_type);
		$singular = $obj->labels->singular_name;

		$messages[$this->post_type_slug] = array(
		0 => '', // Unused. Messages start at index 1.
		1 => sprintf( __($singular.' updated. <a href="%s">View '.strtolower($singular).'</a>'), esc_url( get_permalink($post_ID) ) ),
		2 => __('Custom field updated.'),
		3 => __('Custom field deleted.'),
		4 => __($singular.' updated.'),
		5 => isset($_GET['revision']) ? sprintf( __($singular.' restored to revision from %s'), wp_post_revision_title( (int) $_GET['revision'], false ) ) : false,
		6 => sprintf( __($singular.' published. <a href="%s">View '.strtolower($singular).'</a>'), esc_url( get_permalink($post_ID) ) ),
		7 => __('Page saved.'),
		8 => sprintf( __($singular.' submitted. <a target="_blank" href="%s">Preview '.strtolower($singular).'</a>'), esc_url( add_query_arg( 'preview', 'true', get_permalink($post_ID) ) ) ),
		9 => sprintf( __($singular.' scheduled for: <strong>%1$s</strong>. <a target="_blank" href="%2$s">Preview '.strtolower($singular).'</a>'), date_i18n( __( 'M j, Y @ G:i' ), strtotime( $post->post_date ) ), esc_url( get_permalink($post_ID) ) ),
		10 => sprintf( __($singular.' draft updated. <a target="_blank" href="%s">Preview '.strtolower($singular).'</a>'), esc_url( add_query_arg( 'preview', 'true', get_permalink($post_ID) ) ) ),
		);
		return $messages;
	}

	/**
	 * Retrieve all feeds from the database
	 *
	 * @since     1.0.0
	 *
	 * @return    array     Collection of TimberFeed objects
	 */
	public function get_feeds( $query = array(), $PostClass = 'TimberFeed' ) {
		if ($this->feeds) return $this->feeds;
		$query = array_merge( $query, array(
			'post_type' => $this->post_type_slug,
			'nopaging'  => true
		));
		return $this->feeds = Timber::get_posts( $query, $PostClass );
	}

}
