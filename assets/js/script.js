jQuery(function($) {


  ////////////////////////////////////////////
  // 
  // Heartbeat - avoid feed collisions by
  //             loading feed updates from
  //             the database
  // 
  ////////////////////////////////////////////

  // Hook into the heartbeat-send
  $(document).on('heartbeat-send', function(e, data) {
    
  });

  // Listen for the custom event "heartbeat-tick" on $(document).
  $(document).on( 'heartbeat-tick', function(e, data) {
    console.log( data );
  });


  ////////////////////////////////////////////
  // 
  // Post Manipulation
  // 
  ////////////////////////////////////////////

  // Pin Post
  $('.fm-posts').on('click', '.pin-unpin a', function(e) {
    e.preventDefault();

    var stub = $(this).closest('.stub');

    if ( stub.hasClass('fm-pinned') ) {
      stub.removeClass('fm-pinned');
      stub.find('.fm-pin-checkbox').prop('checked', false);
    } else {
      stub.addClass('fm-pinned');
      stub.find('.fm-pin-checkbox').prop('checked', true);
    }
  });


  // Remove Post
  $('.fm-posts').on('click', '.remove a', function(e) {
    e.preventDefault();
    var object = $(this).closest('.stub');
    undo_cache.push({
      position: object.index(),
      object: object
    });
    $(object).remove();
  })


  // Reorder Post
  $('.fm-feed-rows').sortable({
    start: function(event, ui) {
      $(document).trigger('fm/sortable_start', ui.item);
      $(ui.placeholder).height($(ui.item).height());
    },
    stop: function(event, ui) {
      $(document).trigger('fm/sortable_stop', ui.item);
    },
    axis: 'y'
  });

});

// Remove undo
// @TODO: move this somewhere else
var undo_cache = [];

var undo_remove = function() {
  var object = undo_cache.pop();
  if (!object) return;
  var container = jQuery('.fm-feed-rows');

  if (object.position == 0) {
    container.prepend(object.object);
  } else {
    container.find('.stub:nth-child(' + object.position + ')').after(object.object);
  }
}
