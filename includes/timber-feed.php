<?

class TimberFeed extends TimberPost {

  public $posts;

  // Post limit for the feed. This will eventually be configurable
  public $limit = 100;

  // also eventually configurable
  public $query = array(
    'post_type' => 'post',
    'post_status' => 'publish',
    'has_password' => false,
    'ignore_sticky_posts' => true,

    'posts_per_page' => 100,
    'orderby' => 'post__in'
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

    // Create an array of just post IDs
    $query = array_merge( $query, $this->query );
    $query['post__in'] = array();
    foreach ( $this->fm_feed['data'] as $item ) {
      $query['post__in'][] = $item['id'];
    }

    $posts = Timber::get_posts($query, $PostClass);

    $pinned = array_keys($this->filter_feed('pinned', true));

    foreach ($posts as &$post) {
      $post->pinned = in_array( $post->ID, $pinned );
    }

    return $this->posts = $posts;
  }

  /**
   * Filter posts in the feed, returning only the filtered
   * posts (including their position).
   *
   * @since     1.0.0
   *
   * @return    array
   */
  public function filter_feed($attribute, $value) {
    $items = array();

    foreach ( $this->fm_feed['data'] as $position => $item ) {
      $item['position'] = $position;
      if ( $item[$attribute] == $value ) $items[$item['id']] = $item;
    }

    return $items;
  }


  /**
   * Enforce the feed length.
   *
   * If there are fewer posts than allowed, add some from the base query.
   * If there are more, remove them.
   *
   * @since     1.0.0
   *
   * @todo      do this
   */
  public function repopulate_feed() {

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
      'post__not_in' => array_keys($this->filter_feed('pinned', true))
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
    $this->pinned = $this->filter_feed('pinned', true);
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
   * Save the feed metadata
   *
   * @since     1.0.0
   */
  public function save_feed() {
    update_post_meta( $this->ID, 'fm_feed', $this->fm_feed );
  }

}
