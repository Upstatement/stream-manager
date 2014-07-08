<?

class TimberFeed extends TimberPost {


  /**
   * Get filtered & sorted collection of posts in the feed
   *
   * @since     1.0.0
   *
   * @return    array    collection of TimberPost objects
   */
  public function get_posts($query = array(), $PostClass = 'TimberPost') {

    if ( isset($this->posts ) ) {
      return $this->posts;
    }

    if ( !isset($this->fm_feed) ) {
      $this->fm_feed = array(
        'pinned' => array(),
        'hidden' => array()
      );
    }

    $feed = $this->fm_feed;

    $posts = $this->get_unfiltered_posts($query, $PostClass);

    // Filter out pinned, but cache the post instances
    $pinned = array();
    foreach ( $posts as $key => $post ) {
      $pin_key = array_search( $post->ID, $feed['pinned'] );

      if ( $pin_key !== false ) {
        $post->pinned = true;
        $pinned[$pin_key] = $post;
        unset( $posts[$key] );
        unset( $feed['pinned'][$pin_key] );
      }
    }

    // Add any additional pinned items that weren't in the
    // original query
    foreach ( $feed['pinned'] as $pin_key => $pin ) {
      $pinned[$pin_key] = new TimberPost( $pin );
    }

    // Add pinned in proper locations
    foreach ( $pinned as $pin_key => $pin ) {
      array_splice( $posts, $pin_key, 0, array( $pin ) );
    }

    return $this->posts = $posts;
  }


  /**
   * Get the list of feed posts without accounting for
   * any hidden or pinned items.
   *
   * @since     1.0.0
   *
   * @return    array    collection of TimberPost objects
   */
  public function get_unfiltered_posts($query = array(), $PostClass = 'TimberPost') {

    if ( isset($this->unfiltered_posts) ) {
      return $this->unfiltered_posts;
    }

    $feed = $this->fm_feed;

    $query = array_merge( array(
      'post_type' => 'post',
      'posts_per_page' => 20,
      'orderby' => 'date',
      'ignore_sticky_posts' => true,
      'show_hidden' => false // not a WP_Query param, but we'll use it below
    ), $query );

    if ( $query['show_hidden'] === false ) {
      $query['post__not_in'] = $feed['hidden'];
    }

    // Get the posts list
    $posts = Timber::get_posts($query);

    // Set some defaults
    foreach ( $posts as &$post ) {
      $post->hidden = false;
      $post->pinned = false;
    }

    // If we're showing hidden, mark them as such for the admin
    if ( $query['show_hidden'] === true ) {
      foreach ( $posts as &$post ) {
        if ( in_array( $post->ID, $feed['hidden'] ) ) {
          $post->hidden = true;
        }
      }
    }

    return $this->unfiltered_posts = $posts;
  }


  /**
   * Save the feed metadata
   *
   * @since     1.0.0
   *
   * @todo
   */
  public function save_feed( $pinned = array(), $hidden = array() ) {

  }


  /**
   * Update the list of cached items in the database
   *
   * @since     1.0.0
   *
   * @todo
   */
  public function rebuild_cache() {

  }

}