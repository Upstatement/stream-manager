<?php
/**
 * Stream Manager.
 *
 * @package   StreamManager
 * @author    Chris Voll + Upstatement
 * @license   GPL-2.0+
 * @link      http://upstatement.com
 * @copyright 2014 Upstatement
 */

/**
 * @package StreamManager
 * @author  Chris Voll + Upstatement
 */
class StreamManager {

	/**
	 * Plugin version.
	 *
	 * @since   1.0.0
	 *
	 * @var     string
	 */
	const VERSION = '1.0.0';

	/**
	 * Unique plugin identifier.
	 *
	 * @since    1.0.0
	 *
	 * @var      string
	 */
	protected $plugin_slug = 'stream-manager';

	/**
	 * Unique identifier for the stream post type.
	 *
	 * This needs to avoid conflicting with other plugins.
	 *
	 * @since    1.0.0
	 *
	 * @var      string
	 */
	protected $post_type_slug = 'sm_stream';

	/**
	 * Instance of this class.
	 *
	 * @since    1.0.0
	 *
	 * @var      object
	 */
	protected static $instance = null;

	/**
	 * Streams cache.
	 *
	 * @since    1.0.0
	 *
	 * @var      object
	 */
	public $streams = null;

	/**
	 * Initialize the plugin
	 *
	 * @since     1.0.0
	 */
	private function __construct() {

		// Ensure that Timber is loaded
		if ( !self::check_dependencies() ) return;
		require_once( plugin_dir_path( __FILE__ ) . 'timber-stream.php' );

		add_action( 'init', array( $this, 'define_post_types' ), 0 );
		add_filter('post_updated_messages', array( $this, 'define_post_type_messages' ) );
		add_filter('get_twig', array($this, 'add_timber_filters_functions'));
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
	 * plugins are activated, Timber may be loaded after the Stream
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
	 * Create the Stream post type, add to admin
	 *
	 * @since     1.0.0
	 */
	public function define_post_types() {
	  $labels = array(
	    'name'                => 'Streams',
	    'singular_name'       => 'Stream',
	    'menu_name'           => 'Streams',
	    'parent_item_colon'   => 'Parent Stream',
	    'all_items'           => 'Streams',
	    'view_item'           => 'View Stream',
	    'add_new_item'        => 'Add New Stream',
	    'add_new'             => 'Add New',
	    'edit_item'           => 'Edit Stream',
	    'update_item'         => 'Update Stream',
	    'search_items'        => 'Search Stream',
	    'not_found'           => 'Not found',
	    'not_found_in_trash'  => 'Not found in Trash',
	  );
	  $args = array(
	    'label'               => $this->post_type_slug,
	    'description'         => 'Stream',
	    'labels'              => $labels,
	    'supports'            => array( 'title' ),
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
	 * Add Stream post type messages.
	 *
	 * @since     1.0.0
	 */
	function define_post_type_messages($messages) {
		global $post, $post_ID;
		$post_type = get_post_type( $post_ID );

		$obj = get_post_type_object($post_type);
		$singular = $obj->labels->singular_name;

		$messages[$this->post_type_slug] = array(
			0 => '',
			1 => __($singular . ' updated.'),
			2 => __('Custom field updated.'),
			3 => __('Custom field deleted.'),
			4 => __($singular . ' updated.'),
			5 => isset($_GET['revision']) ? sprintf( __($singular.' restored to revision from %s'), wp_post_revision_title( (int) $_GET['revision'], false ) ) : false,
			6 => __($singular . ' published.'),
			7 => __('Page saved.'),
			8 => __($singular . ' submitted.'),
			9 => sprintf( __($singular.' scheduled for: <strong>%1$s</strong>.'), date_i18n( __( 'M j, Y @ G:i' ) ) ),
			10 => __($singular . ' draft updated.'),
		);
		return $messages;
	}

	/**
	 * Retrieve all streams from the database.
	 *
	 * @since     1.0.0
	 *
	 * @return    array     Collection of TimberStream objects
	 */
	public function get_streams( $query = array(), $PostClass = 'TimberStream' ) {
		if ($this->streams) return $this->streams;
		$query = array_merge( $query, array(
			'post_type' => $this->post_type_slug,
			'nopaging'  => true
		));
		return $this->streams = Timber::get_posts( $query, $PostClass );
	}

	function add_timber_filters_functions($twig) {
		$twig->addFunction(new Twig_SimpleFunction('TimberStream', function ($pid, $StreamClass = 'TimberStream') {
            if (is_array($pid) && !TimberHelper::is_array_assoc($pid)) {
                foreach ($pid as &$p) {
                    $p = new $StreamClass($p);
                }
                return $pid;
            }
            return new $StreamClass($pid);
        }));
        return $twig;
	}

}
