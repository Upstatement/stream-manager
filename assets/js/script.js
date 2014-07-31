

jQuery(function($) {

  $('.fm-posts').on('click', '.pin-unpin a', function(e) {
    e.preventDefault();

    var stub = $(this).closest('.stub');

    if ( stub.hasClass('fm-pinned') ) {
      stub.removeClass('fm-pinned');
      stub.find('.fm-pin-checkbox').prop('checked', false);
      $(this).text('Pin');
      $('.fm-posts').trigger('reflow');
    } else {
      stub.addClass('fm-pinned');
      stub.find('.fm-pin-checkbox').prop('checked', true);
      $(this).text('Unpin');
    }
  });



  $('.fm-posts').on('click', '.remove a', function(e) {
    e.preventDefault();
    var object = $(this).closest('.stub');
    undo_cache.push({
      position: object.index(),
      object: object
    });
    $(object).remove();
    console.log(undo_cache);
  })

  $('.fm-feed-rows').sortable({
    start: function(event, ui) {
      $(document).trigger('fm/sortable_start', ui.item);
      console.log(ui);
      $(ui.placeholder).height($(ui.item).height());
    },
    stop: function(event, ui) {
      $(document).trigger('fm/sortable_stop', ui.item);
    },
    helper: function(e, tr) {
      var $originals = tr.children();
      var $helper = tr.clone();
      $helper.children().each(function(index)
      {
        // Set helper cell sizes to match the original sizes
        $(this).width($originals.eq(index).width());
      });
      return $helper;
    },
    axis: 'y'
  });

});

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
