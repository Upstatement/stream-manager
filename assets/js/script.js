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

  $('.fm-feed-rows').sortable({
    start: function(event, ui) {
      $(document).trigger('fm/sortable_start', ui.item);
    },
    stop: function(event, ui) {
      $(document).trigger('fm/sortable_stop', ui.item);
    },
    axis: 'y'
  });

});
