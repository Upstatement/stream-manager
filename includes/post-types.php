<?

// Register Custom Post Type
function feed_manager_post_types() {

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
    'label'               => 'fm_feed',
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
  register_post_type( 'fm_feed', $args );

}

add_action( 'init', 'feed_manager_post_types', 0 );
