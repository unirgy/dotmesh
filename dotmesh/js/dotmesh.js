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
});