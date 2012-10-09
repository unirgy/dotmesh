/**
* This file is part of DotMesh.
*
* DotMesh is free software: you can redistribute it and/or modify
* it under the terms of the GNU General Public License as published by
* the Free Software Foundation, either version 3 of the License, or
* any later version.
*
* Foobar is distributed in the hope that it will be useful,
* but WITHOUT ANY WARRANTY; without even the implied warranty of
* MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
* GNU General Public License for more details.
*
* You should have received a copy of the GNU General Public License
* along with DotMesh.  If not, see <http://www.gnu.org/licenses/>.
*
* @package DotMesh (tm)
* @link http://dotmesh.org
* @author Boris Gurvich <boris@unirgy.com>
* @copyright (c) 2012 Boris Gurvich
* @license http://www.gnu.org/licenses/gpl.txt
*/

/**
* Enable CORS in IE8,9
*
* @see https://github.com/tlianza/ajaxHooks/blob/master/src/ajax/xdr.js
*/
(function( $ ) {

    if ( window.XDomainRequest ) {
        $.ajaxTransport(function( s ) {
            if ( s.crossDomain && s.async ) {
                if ( s.timeout ) {
                    s.xdrTimeout = s.timeout;
                    delete s.timeout;
                }
                var xdr;
                return {
                    send: function( _, complete ) {
                        function callback( status, statusText, responses, responseHeaders ) {
                            xdr.onload = xdr.onerror = xdr.ontimeout = xdr.onprogress = $.noop;
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
                            xdr.onerror = $.noop();
                            xdr.abort();
                        }
                    }
                };
            }
        });
    }

    $.fn.setSelectionRange = function(from, to) {
        this.each(function(idx, el) {
            if (from=='end') {
                from = el.tagName=='TEXTAREA' ? el.innerHTML.length : el.value.length;
            }
            if (!to) {
                to = from;
            }
            if (el.setSelectionRange) {
                el.setSelectionRange(from, to);
            } else if (el.createTextRange) {
                var range = el.createTextRange();
                range.collapse(true);
                range.moveEnd('character', to);
                range.moveStart('character', from);
                range.select();
            }
        });
    }
})( jQuery );

function dotmeshMediaLinks(parent) {
    $('.image-link', parent).each(function(idx, el) {
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
    $('.youtube-link', parent).each(function(idx, el) {
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
}

function addMessage(status, message) {
    var ul = $('.messages-container ul'), li = $('<li></li>').addClass(status).html(message);
    ul.append(li);
    setTimeout(function() { removeMessage(li) }, 10000);
}
function removeMessage(item) {
    $(item).fadeOut(function() { $(this).remove() });
}

$(function() {
    $('#top-password').hide();
    $('#top-password-trigger').on('click', function(event) {
        $('#top-login').slideUp('fast');
        $('#top-password').slideDown('fast');
        return false;
    });
    $('#top-login-trigger').on('click', function(event) {
        $('#top-login').slideDown('fast');
        $('#top-password').slideUp('fast');
        return false;
    });

    $('.hover-delay').each(function(idx, el) {
        var $el = $(el), delayOpen = false, delayTimer;
        $el.on('mouseout', function(event) {
            $el.addClass('hover');
            clearTimeout(delayTimer);
            delayTimer = setTimeout(function() { $el.removeClass('hover') }, 500);
        });
    });
    $('.subscription-state').each(function(idx, el) {
        var hoverLabel = $(this).data('hover-label'), label = $('.label', this), defLabel = label.html();
        $(el).on('mouseover', function(event) { label.html(hoverLabel); });
        $(el).on('mouseout', function(event) { label.html(defLabel); });
    });

    $(document).on('click', '.timeline .preview-expand', function(event) {
        $(event.target).closest('.timeline-item').addClass('expanded');
        return false;
    });
    $(document).on('click', '.timeline .contents-collapse', function(event) {
        $(event.target).closest('.timeline-item').removeClass('expanded');
        return false;
    });
    $(document).on('click', '.timeline .actions-group-1 button,.timeline .actions-group-3 button', function(event) {
        var el = $(this), form = el.closest('form'), f = el.attr('name');
        var postVars = {
            type: 'feedback',
            field: f,
            value: el.val(),
            post_uri: $('input[name=post_uri]', form).val(),
            user_uri: $('input[name=user_uri]', form).val(),
            remote_signature_ip: $('input[name=remote_signature_ip]', form).val(),
        };
        $.post(form.attr('action')+'/api1.json', postVars, function(response, status) {
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
            if (form.data('is-local')=='0') {
                postVars.remote_signature_ip = $('input[name=local_signature_ip]', form).val();
                postVars.total = response.total;
                $.post(form.data('local-uri'), postVars, function(resp, stat) {
console.log(resp);
                });
            }
        });
        return false;
    });

    dotmeshMediaLinks(document);

    $('input[name=is_private]:checkbox').on('change', function(event) {
        var container = $(this).closest('.new-post-block');
        if ($(this).attr('checked')) container.addClass('private-post'); else container.removeClass('private-post');
    });

    var timelineCurPage = 1, timelineLoading = false;
    function timelineNextPage() {
        //if (timelineLoading) return;
        var el = $('.timeline-loadmore');
        var uri = el.data('uri-pattern');
        ++timelineCurPage;
        timelineLoading = true;
        el.addClass('loading');
        $.get(uri.replace(/p=PAGE/, 'p='+timelineCurPage), function(response, status) {
            if (!response.replace(/^\s+|\s+$/, '').length) {
                el.hide();
            } else {
                el.before(response);
            }
            timelineLoading = false;
            el.removeClass('loading');
        });
    }
    function checkNextPageAboveFold() {
        var el = $('.timeline-loadmore');
        if (!el.length) {
            return;
        }
        if (!timelineLoading) {
            var et = el.offset().top, w = $(window), wt = w.scrollTop(), wh = w.height();
            if (et < wt+wh) {
                timelineNextPage();
            }
        }
        if ($('.timeline-loadmore:visible').length) {
            setTimeout(checkNextPageAboveFold, 200);
        }
    }
    checkNextPageAboveFold();

    $('.timeline-loadmore').on('click mouseover', timelineNextPage);
    //$(window).on('scroll resize', checkNextPageAboveFold);

    $('.tiptip-title').tipTip({delay:0, fadeIn:0, fadeOut:100});

    $('.new-post-block form').on('submit', function(event) {
        var form = $(event.target), postVars = form.serializeArray();
        postVars.push({name:'do', value:'new'});
        $.post(form.attr('action'), postVars, function(response, status) {
            if (response.remote_nodes) {
                var nodesCnt = response.remote_nodes.length, corsEl = $('.cors-requests'), apiVars = {
                    type: 'new',
                    post_uri: response.post.post_uri,
                    user_uri: response.post.user_uri,
                    post: response.post
                };
                $(response.remote_nodes).each(function(idx, node) {
                    var loader = $('<span>Please wait, sending</span>').appendTo(corsEl);
                    var indicator = $('<span>.</span>').appendTo(corsEl);
                    apiVars.remote_signature_ip = node.remote_signature_ip;
                    $.post(node.api_uri, apiVars, function(resp, stat) {
                        indicator.remove();
                        nodesCnt--;
                        if (!nodesCnt) {
                            loader.remove();
                        }
                    });
                });
            }
            $('#user-posts-cnt').html(response.user_posts_cnt);
            var newHtml = $(response.timeline_html).hide();
            $('.timeline-container').prepend(newHtml);
            newHtml.slideDown('slow');
            addMessage(response.status, response.message);
            event.target.reset();
        });
        return false;
    });

    setTimeout(function() { removeMessage('.messages-container li') }, 10000);
});