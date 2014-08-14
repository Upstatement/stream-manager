<?
/**
 * TimberFeed
 *
 * @package   TimberFeed
 * @author    Chris Voll + Upstatement
 * @license   GPL-2.0+
 * @link      http://upstatement.com
 * @copyright 2014 Upstatement
 *
 * Usage:
 * > $posts = new TimberPost( $pid );
 * > foreach ( $posts as $post ) {
 * >   echo ( $post->title );
 * > }
 */

class TimberFeed extends TimberPost {

  /**
   * Feed post cache.
   *
   * @since    1.0.0
   *
   * @var      array
   */
  public $posts;

  /**
   * Feed post limit.
   * Will be overridden by database.
   *
   * @since    1.0.0
   *
   * @var      array
   */
  public $limit = 100;

  /**
   * WP_Query query array.
   * Will be overridden by database.
   *
   * @since    1.0.0
   *
   * @var      array
   */
  public $query = array(
    'post_type' => 'post',
    'post_status' => 'publish',
    'has_password' => false,
    'ignore_sticky_posts' => true,

    'posts_per_page' => 100,
    'orderby' => 'post__in'
  );

  /**
   * Feed data.
   * Overridden by database.
   *
   * @since    1.0.0
   *
   * @var      array
   */
  public $fm_feed = array(
    'data' => array(),
    'hidden' => array()
  );

  public $fm_feed_rules = array();

  /**
   * Init Feed object
   *
   * @param integer|boolean|string  $pid  Post ID or slug
   *
   * @todo  allow creating a TimberFeed w/out database
   */
  public function __construct($pid = null) {
    parent::__construct($pid);
  }

  /**
   * Get filtered & sorted collection of posts in the feed
   *
   * @since    1.0.0
   *
   * @param    array   $query      WP_Query query argument
   * @param    string  $PostClass  Timber post class
   *
   * @return   array   collection of TimberPost objects
   */
  public function get_posts($query = array(), $PostClass = 'TimberPost') {
    if ( isset($this->posts) ) return $this->posts;

    // Create an array of just post IDs
    $query = array_merge( $this->query, $query );
    $query['post__in'] = array();
    foreach ( $this->fm_feed['data'] as $item ) {
      $query['post__in'][] = $item['id'];
    }

    // Remove any taxonomy limitations, since those would remove any
    // posts from the feed that were added by searching in the UI.
    unset($query['tax_query']);

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
   * @return    array  filtered posts
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
   * @todo      it's possible for a pinned item to go above the limit
   */
  public function repopulate_feed() {

    // Determine how many over/under we are
    $difference = count($this->fm_feed['data']) - $this->limit;

    if ( $difference < 0 ) {

      // Under -- add pinned posts to the end
      $query = $this->query;
      $ids = array();
      foreach ( $this->fm_feed['data'] as $post ) {
        $ids[] = $post['id'];
      }
      $query['post__not_in'] = $ids;
      $query['posts_per_page'] = $difference * -1;
      $posts = Timber::get_posts($query);

      $this->remove_pinned();

      foreach ( $posts as $post ) {
        $this->fm_feed['data'][] = array(
          'id' => $post->ID,
          'pinned' => false
        );
      }

      $this->reinsert_pinned();

    } else if ( $difference > 0 ) {

      // Over -- remove non-pinned posts at the end
      $this->remove_pinned();
      for ( $i = 1; $i <= $difference; $i++ ) {
        array_pop( $this->fm_feed['data'] );
      }
      $this->reinsert_pinned();

    }
  }


  /**
   * Checks if a post exists in a feed
   *
   * @since     1.0.0
   *
   * @param     integer  $post_id  Post ID
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
   *
   * @param     integer  $post_id     Post ID
   * @param     boolean  $repopulate  add/remove posts to enforce feed length
   */
  public function remove_post ( $post_id, $repopulate = true ) {
    $post = $this->check_post( $post_id );
    if ( $post ) {
      $this->remove_pinned();

      // Remove non-pinned
      unset($this->fm_feed['data'][$post['position']]);

      // Remove pinned
      foreach ( $this->pinned as $i => $pinned ) {
        if ( $pinned['id'] == $post_id ) {
          unset( $this->pinned[$i] );
        }
      }
      $this->reinsert_pinned();
      if ( $repopulate ) $this->repopulate_feed();
      $this->save_feed();
    }
  }

  /**
   * Inserts a post in the feed
   *
   * @since     1.0.0
   *
   * @param     integer  $post_id  Post ID
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
    $this->repopulate_feed();
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
    update_post_meta( $this->ID, 'fm_feed',       $this->fm_feed );
    update_post_meta( $this->ID, 'fm_feed_rules', $this->fm_feed_rules );
    update_post_meta( $this->ID, 'query',         $this->query );
  }

}
