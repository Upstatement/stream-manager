jQuery(function($) {

  // Setup
  var $feed = $('.fm-posts');


  ////////////////////////////////////////////
  // 
  // Heartbeat - avoid feed collisions by
  //             loading feed updates from
  //             the database
  //
  // Avoid feed collisions by loading feed
  // updates from the database. For the purpose
  // of more accurate placements, pinned posts
  // are excluded from the list of IDs that
  // are passed around.
  // 
  ////////////////////////////////////////////

  // Hook into the heartbeat-send
  $(document).on('heartbeat-send', function(e, data) {
    
  });

  // Listen for the custom event "heartbeat-tick" on $(document).
  $(document).on( 'heartbeat-tick', function(e, data) {
    console.log( data );

    if ( data.fm_feed_ids !== $feed.attr('data-ids') ) {
      console.log('discrepency!!!!');

      var front = $feed.attr('data-ids').split(',');
      var back  = data.fm_feed_ids.split(',');

      var published_posts = [];
      // var deleted_posts   = [];

      for (i in back) {
        if ( ! $.inArray(back[i], front) ) {
          post_queue.insert(back[i], i);
        }
      }

      // for (i in front) {
      //   if ( ! $.inArray(front[i], back) ) deleted_posts.push(front[i]);
      // }

    } else {
      console.log('no discrepency');
    }
  });


  ////////////////////////////////////////////
  // 
  // Post Manipulation
  // 
  ////////////////////////////////////////////

  // Pin Post
  var pin_post = function(e) {
    e.preventDefault();

    var stub = $(this);
    if (!stub.hasClass('stub')) stub = $(this).closest('.stub');

    if ( stub.hasClass('fm-pinned') ) {
      stub.removeClass('fm-pinned');
      stub.find('.fm-pin-checkbox').prop('checked', false);
    } else {
      stub.addClass('fm-pinned');
      stub.find('.fm-pin-checkbox').prop('checked', true);
    }
  };

  // (by clicking the pin, or double clicking on the stub)
  $('.fm-posts').on('click',    '.pin-unpin', pin_post);
  $('.fm-posts').on('dblclick', '.stub',      pin_post);


  // Remove Post
  var remove_post = function(e) {
    e.preventDefault();

    var object = $(this).closest('.stub');
    undo_cache.push({
      position: object.index(),
      object: object
    });
    $(object).remove();
  };

  $('.fm-posts').on('click', '.remove', remove_post);


  // Reorder Post
  $('.fm-posts').sortable({
    start: function(event, ui) {
      $(document).trigger('fm/sortable_start', ui.item);
      $(ui.placeholder).height($(ui.item).height());
    },
    stop: function(event, ui) {
      $(document).trigger('fm/sortable_stop', ui.item);
    },
    axis: 'y'
  });


  ////////////////////////////////////////////
  // 
  // Post insertion queue
  //
  // Provides a general API for use by both
  // the collision management system (heartbeat)
  // and search feature for inserting posts into
  // (and removing them from) the feed UI.
  //
  // -----------------------------------------
  // 
  // When a discrepency is detected, posts
  // will be added to a queue and the user
  // will be notified. Clicking a button will
  // insert the posts into the feed.
  // 
  ////////////////////////////////////////////

  var post_queue = {

    queue: {},
    pinned_cache: {},

    insert: function(id, position) {
      this.queue[id] = position;
    },

    get_ids: function() {
      var ids = [];
      for (id in this.queue) {
        ids.push(id);
      }
      return ids;
    },

    // retrieves all posts and inserts them
    retrieve_posts: function() {
      if (this.queue.length < 1) return;
      var that = this;

      // Insert new posts
      var request = {
        action: 'fm_feed_request',
        ids: this.get_ids()
      }
      $.post(ajaxurl, request, function (response) {

        // Temporarily remove pinned posts
        var pinned = [];
        $feed.find('.stub').each( function (i) {
          if ($(this).hasClass('fm-pinned')) {
            pinned.push({
              id: $(this).attr('data-id'),
              obj: this,
              position: i
            });
            $(this).remove();
          }
        });

        // Insert new posts
        var data = JSON.parse(response);

        for ( id in that.queue ) {
          var position = that.queue[id];
          if ( data[id] ) {
            that.inject( position, data[id] );
          }
          delete that.queue[id];
        }

        // Remove deleted posts

        // Remove deleted pinned posts

        // Reinsert pinned items
        for (i in pinned) {
          that.inject(pinned[i].position, pinned[i].obj);
        }
      });

    },

    // insert a post object into the feed
    inject: function (position, object) {
      if ( position == 0 ) {
        $feed.prepend( object );
      } else {
        $feed.find( '.stub:nth-child(' + position + ')' ).after( object );
      }
    }

  }

  window.post_queue = post_queue;

});

// Remove undo
// @TODO: move this somewhere else
var undo_cache = [];

var undo_remove = function() {
  var object = undo_cache.pop();
  if (!object) return;
  var container = jQuery('.fm-posts');

  if (object.position == 0) {
    container.prepend(object.object);
  } else {
    container.find('.stub:nth-child(' + object.position + ')').after(object.object);
  }
}
