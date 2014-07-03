jQuery(function($) {

  $('.fm-posts').on('click', '.pin-unpin a', function(e) {
    e.preventDefault();

    var stub = $(this).closest('.stub');

    if ( stub.hasClass('fm-pinned') ) {
      stub.removeClass('fm-pinned');
      stub.find('.fm-pin-checkbox').prop('checked', false);
      $(this).text('Pin');
    } else {
      stub.addClass('fm-pinned');
      stub.find('.fm-pin-checkbox').prop('checked', true);
      $(this).text('Unpin');
    }
  });

  $('.fm-posts').on('click', '.hide a', function(e) {
    e.preventDefault();

    var stub = $(this).closest('.stub');

    if ( stub.hasClass('fm-hidden') ) {
      stub.removeClass('fm-hidden');
      stub.find('.fm-hide-checkbox').prop('checked', false);
      $(this).text('Hide');
    } else {
      stub.addClass('fm-hidden');
      stub.find('.fm-hide-checkbox').prop('checked', true);
      $(this).text('Unhide');
    }
  });

  $('.fm-feed-rows').sortable({
    update: function(event, ui) {
      console.log(this, event, ui);
      $(ui.item)
        .addClass('fm-pinned')
        .find('.fm-pin-checkbox').prop('checked', true);
      $(ui.item).find('.pin-unpin a').text('Unpin');
    }
  });

});