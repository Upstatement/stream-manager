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
   * @todo  allow creating a TimberFeed w/out database
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
    if ( isset($this->posts) ) return $this->posts;

    // @debug
    // echo("<pre>");
    // print_r($this->fm_feed);
    // echo("</pre>");

    // Create an array of just post IDs, and identify
    // which items are pinned.
    $query = array_merge( $query, $this->query );
    $query['post__in'] = array();
    foreach ( $this->fm_feed['data'] as $item ) {
      $query['post__in'][] = $item['id'];
    }

    $posts = Timber::get_posts($query, $PostClass);

    $pinned = array_keys($this->get_pinned());

    foreach ($posts as &$post) {
      $post->pinned = in_array( $post->ID, $pinned );
    }

    return $this->posts = $posts;
  }

  // @todo: this may be better off as a more generalized method,
  // like filter_feed($attribute)
  public function get_pinned() {
    $pinned = array();

    foreach ( $this->fm_feed['data'] as $position => $item ) {
      $item['position'] = $position;
      if ( $item['pinned'] ) $pinned[$item['id']] = $item;
    }

    return $pinned;
  }


  // call this whenever a post is saved
  // when a post is added, insert it in the same slot it would
  // be in from the original query without any reordering. eventually,
  // we can try to make this smarter by looking at the posts around it.
  public function repopulate_feed() {

  }


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

  /**
   * Checks if a post exists in a feed
   *
   * @since     1.0.0
   *
   * @return    array    returns the data saved in the feed, plus its position
   */
  public function check_post ( $post_id ) {
    foreach ( $this->fm_feed['data'] as $position => $item ) {
      if ( $item['id'] == $post_id ) {
        $item['position'] = $position;
        return $item;
      }
    }
    return false;
  }

  /**
   * Removes a post from a feed and, by default, fills
   * in the empty space at the end.
   *
   * @since     1.0.0
   */
  public function remove_post ( $post_id, $repopulate = true ) {
    $post = $this->check_post( $post_id );
    if ( $post ) {
      $this->remove_pinned();
      unset($this->fm_feed['data'][$post['position']]);
      $this->reinsert_pinned();
      $this->save_feed();
    }
  }

  /**
   * Inserts a post in the feed
   *
   * @since     1.0.0
   */
  public function insert_post ( $post_id ) {
    // Does it already exist? If so, remove it, and we'll reinsert it
    if ( $this->check_post( $post_id ) ) {
      $this->remove_post( $post_id, false );
    }

    // Determine where it is in the original query (if at all),
    // minus any pinned items
    $query = array_merge( $this->query, array(
      'post__not_in' => array_keys($this->get_pinned())
    ));
    $posts = Timber::get_posts( $query );
    $in_feed = false;

    foreach ( $posts as $i => $post ) {
      if ( $post->ID == $post_id ) $in_feed = $i;
    }

    // If it's not in the feed, bail
    if ( $in_feed === false ) return;

    // Remove pinned items from the feed...
    $this->remove_pinned();

    // ... then insert this post ...
    array_splice( $this->fm_feed['data'], $in_feed, 0, array( array (
      'id' => $post_id,
      'pinned' => false
    ) ) );

    // ... and then reinsert the pinned items
    $this->reinsert_pinned();

    $this->save_feed();
  }

  /**
   * Temporarily removes pinned items from the feed, for the
   * purpose of modifying the auto-flowing feed.
   *
   * @since     1.0.0
   */
  public function remove_pinned() {
    $this->pinned = $this->get_pinned();
    foreach ( $this->pinned as $pin ) {
      unset ( $this->fm_feed['data'][$pin['position']] );
    }
  }

  /**
   * Place the pinned items back in the feed in their appropriate
   * locations
   *
   * @since     1.0.0
   */
  public function reinsert_pinned() {
    foreach ( $this->pinned as $pin ) {
      $position = $pin['position'];
      unset( $pin['position'] );
      array_splice( $this->fm_feed['data'], $position, 0, array( $pin ) );
    }
  }


  /**
   * Get the list of feed posts without accounting for
   * any hidden or pinned items.
   *
   * @todo      replace this with a function that returns
   *            a raw collection of posts from the original
   *            query
   *
   * @since     1.0.0
   *
   * @return    array    collection of TimberPost objects
   */
  // public function get_unfiltered_posts($query = array(), $PostClass = 'TimberPost') {

  //   if ( isset($this->unfiltered_posts) ) {
  //     return $this->unfiltered_posts;
  //   }

  //   $feed = $this->fm_feed;

  //   $query = array_merge( $this->query, $query );

  //   if ( $query['show_hidden'] === false && !$query['post__not_in'] ) {
  //     $query['post__not_in'] = $feed['hidden'];
  //   }

  //   // Get the posts list
  //   $posts = Timber::get_posts($query);

  //   // Set some defaults
  //   foreach ( $posts as &$post ) {
  //     $post->hidden = false;
  //     $post->pinned = false;
  //   }

  //   // If we're showing hidden, mark them as such for the admin
  //   if ( $query['show_hidden'] === true ) {
  //     foreach ( $posts as &$post ) {
  //       if ( in_array( $post->ID, $feed['hidden'] ) ) {
  //         $post->hidden = true;
  //       }
  //     }
  //   }

  //   return $this->unfiltered_posts = $posts;
  // }


  /**
   * Save the feed metadata
   *
   * @since     1.0.0
   */
  public function save_feed() {
    update_post_meta( $this->ID, 'fm_feed', $this->fm_feed );
  }

}
