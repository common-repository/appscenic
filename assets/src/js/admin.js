(function($) {

    const $play   = $('#play');
    const $video  = $('#video');
    const $iframe = $('#iframe');

    $play.on('click', (e) => {
        e.preventDefault();
        $iframe.attr('src', $iframe.attr('data-src') + '?autoplay=1');
        $video.show();
        $play.hide();
    });

    $(document).on('heartbeat-send', function(event, data) {
        data.appscenic_is_connected = '?';
    });

    $(document).on('heartbeat-tick', function(event, data) {
        if ( ! data.appscenic_is_connected_html ) {
            return;
        }
        $('.appscenic-body-top').html(data.appscenic_is_connected_html);
    });

}(jQuery));