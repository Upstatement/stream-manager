<?

class TimberFeed extends TimberPost {

  public $posts;

  // Post limit for the feed. This will eventually be configurable
  public $limit = 100;

  // also eventually configurable
  public $query = array(
    'post_type' => 'post',
    'posts_per_page' => 100,
    'orderby' => 'post__in',
    'ignore_sticky_posts' => true
  );

  public $fm_feed = array(
    'data' => array(),
    'hidden' => array()
  );

  /**
   * @todo  eventually, this can be initiated with a collection
   *        of options instead of a pid, so that themes/plugins can
   *        create feeds without the admin interface
   * @param int|bool $pid
   */
  public function __construct($pid = null) {
    parent::__construct($pid);
  }

  /**
   * Get filtered & sorted collection of posts in the feed
   *
   * @since     1.0.0
   *
   * @return    array    collection of TimberPost objects
   */
  public function get_posts($query = array(), $PostClass = 'TimberPost') {
    if ( isset($this->posts ) ) return $this->posts;

    // @debug
    // echo("<pre>");
    // print_r($this->fm_feed);
    // echo("</pre>");

    $query = array_merge( $query, $this->query );
    $query['post__in'] = array();
    $pinned = array();
    foreach ( $this->fm_feed['data'] as $item ) {
      $query['post__in'][] = $item['id'];
      if ( $item['pinned'] == true ) $pinned[] = $item['id'];
    }
    $posts = Timber::get_posts($query, $PostClass);


    foreach ($posts as &$post) {
      $post->pinned = in_array( $post->ID, $pinned );
    }

    return $this->posts = $posts;
  }

  // call this whenever a post is saved
  // when a post is added, insert it in the same slot it would
  // be in from the original query without any reordering. eventually,
  // we can try to make this smarter by looking at the posts around it.
  public function repopulate_feed() {

  }


  // public function filter_posts($posts) {
  //   $num_posts = count($posts);
  //   $min_pos = 0;
  //   $max_pos = $num_posts;

  //   $query_posts = array();

  //   foreach ( $this->fm_feed['pinned'] as $post_id => $position ) {
  //     if ( $position <= $max_pos ) $query_posts[$post_id] = $position;
  //   }
  //   foreach ( $this->fm_feed['reordered'] as $post_id => $position ) {
  //     if ( $position <= $max_pos ) $query_posts[$post_id] = $position;
  //   }

  //   $inserted_posts = Timber::get_posts(array(
  //     'post__in' => array_keys($query_posts)
  //   ));

  //   foreach ( $inserted_posts as $post ) {
  //     $post->pinned    = isset($this->fm_feed['pinned'][$post->ID]);
  //     $post->reordered = isset($this->fm_feed['reordered'][$post->ID]);

  //     array_splice( $posts, $query_posts[$post->ID], 0, array( $post) );
  //   }

  //   return $posts;
  // }




  // /**
  //  * When a post is added high up in a feed, increase
  //  * the positions of all the saved posts below it.
  //  *
  //  * @todo handle potential collisions with absolute
  //  *
  //  * @since     1.0.0
  //  */
  // public function increment_relative($after = 0) {
  //   foreach($this->fm_feed['relative'] as &$item) {
  //     if ($item > $after) $item++;
  //   }
  // }

  // /**
  //  * When a post is removed high up in a feed, decrease
  //  * the positions of all the saved posts below it.
  //  *
  //  * @todo handle potential collisions with absolute
  //  *
  //  * @since     1.0.0
  //  */
  // public function decrement_relative($after = 0) {
  //   foreach($this->fm_feed['relative'] as &$item) {
  //     if ($item > $after) $item--;
  //   }
  // }


  /**
   * When a post is updated, run the feed query to determine
   * if the feed will be affected by the post.
   *
   * @since     1.0.0
   *
   * @return    array    collection of TimberPost objects
   */
  public function on_post_before_changed ($post) {
    // Check if the post exists somewhere in the feed

    // 1. Is it in relative?
    // 2. If no, is it in absolute?
    // 3. If no, does it appear elsewhere in the feed?

  }

  public function on_post_after_changed ($post) {

  }


  public function on_post_added () {

  }


  public function on_post_removed () {

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

    $query = array_merge( $this->query, $query );

    if ( $query['show_hidden'] === false && !$query['post__not_in'] ) {
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

}