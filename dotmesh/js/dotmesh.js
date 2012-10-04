/**
* Enable CORS in IE8,9
*
* @see https://github.com/tlianza/ajaxHooks/blob/master/src/ajax/xdr.js
*/
(function( jQuery ) {

if ( window.XDomainRequest ) {
    jQuery.ajaxTransport(function( s ) {
        if ( s.crossDomain && s.async ) {
            if ( s.timeout ) {
                s.xdrTimeout = s.timeout;
                delete s.timeout;
            }
            var xdr;
            return {
                send: function( _, complete ) {
                    function callback( status, statusText, responses, responseHeaders ) {
                        xdr.onload = xdr.onerror = xdr.ontimeout = xdr.onprogress = jQuery.noop;
                        xdr = undefined;
                        complete( status, statusText, responses, responseHeaders );
                    }
                    xdr = new XDomainRequest();
                    xdr.open( s.type, s.url );
                    xdr.onload = function() {
                        callback( 200, "OK", { text: xdr.responseText }, "Content-Type: " + xdr.contentType );
                    };
                    xdr.onerror = function() {
                        callback( 404, "Not Found" );
                    };
                    xdr.onprogress = function() {};
                    if ( s.xdrTimeout ) {
                        xdr.ontimeout = function() {
                            callback( 0, "timeout" );
                        };
                        xdr.timeout = s.xdrTimeout;
                    }
                    xdr.send( ( s.hasContent && s.data ) || null );
                },
                abort: function() {
                    if ( xdr ) {
                        xdr.onerror = jQuery.noop();
                        xdr.abort();
                    }
                }
            };
        }
    });
}
})( jQuery );

$(function() {
    $('.timeline .image-link').each(function(idx, el) {
        var span = $('<span class="image-inline"></span>').insertAfter(el);
        var handle = $('<a href="#" class="image-expand">[+]</a>').appendTo(span);
        var image;
        handle.on('click', function(event) {
            span.toggleClass('shown');
            if (!image) {
                image = $('<img class="image-embed"/>').attr('src', $(el).data('src')).appendTo(span);
            }
            return false;
        });
    });
    $('.timeline .youtube-link').each(function(idx, el) {
        var span = $('<span class="youtube-inline"></span>').insertAfter(el);
        var handle = $('<a href="#" class="youtube-expand">[+]</a>').appendTo(span);
        var video;
        handle.on('click', function(event) {
            span.toggleClass('shown');
            if (!video) {
                video = $('<iframe class="youtube-player" type="text/html" width="440" height="280" frameborder="0"/>')
                    .attr('src', $(el).data('src')).appendTo(span);
            }
            return false;
        });
    });
    $('.timeline .preview-expand').on('click', function(event) {
        $(event.target).closest('.timeline-item').addClass('expanded');
        return false;
    });
    $('.timeline .contents-collapse').on('click', function(event) {
        $(event.target).closest('.timeline-item').removeClass('expanded');
        return false;
    });
    $('.timeline .actions-group-1 button').on('click', function(event) {
        var el = $(this), form = el.closest('form'), f = el.attr('name');
        var postVars = {type:'feedback', field:f, value:el.val()};
        $.post(form.attr('action')+'/api1.json', postVars, function(response, status, xhr) {
            var parent = el.parent(), i;
            for (i in response.value) {
                var btn = parent.find('.button-'+i);
                btn.val(1-response.value[i]);
                if (1*response.value[i]) btn.addClass('active'); else btn.removeClass('active');
            }
            for (i in response.total) {
                var total = parent.find('.total-'+i);
                total.html(response.total[i]);
                if (1*response.total[i]) total.removeClass('zero'); else total.addClass('zero');
            }
        });
        return false;
    });

    $('input[name=is_private]:checkbox').on('change', function(event) {
        var container = $(this).closest('.new-post-block');
        if ($(this).attr('checked')) container.addClass('private-post'); else container.removeClass('private-post');
    });

    var timelineCurPage = 1, timelineLoading = false;
    function timelineNextPage() {
        if (timelineLoading) return;
        var el = $('.timeline-loadmore');
        var uri = el.data('uri-pattern');
        ++timelineCurPage;
        timelineLoading = true;
        el.addClass('loading');
        $.get(uri.replace(/p=PAGE/, 'p='+timelineCurPage), function(response, status, xhr) {
            if (!response) {
                el.hide();
            } else {
                el.before(response);
            }
            timelineLoading = false;
            el.removeClass('loading');
        });
    }
    function checkNextPageAboveFold() {
        var el = $('.timeline-loadmore'), et = el.offset().top, w = $(window), wt = w.scrollTop(), wh = w.height();
        if (et < wt+wh) timelineNextPage();
        if ($('.timeline-loadmore:visible').length) setTimeout(checkNextPageAboveFold, 500);
    }
    checkNextPageAboveFold();

    $('.timeline-loadmore').on('click mouseover', timelineNextPage);
    //$(window).on('scroll resize', checkNextPageAboveFold);

    setTimeout(function() { $('.messages-container').fadeOut(); }, 2000);

    $('.tiptip-title').tipTip({delay:0, fadeIn:0, fadeOut:100});
});