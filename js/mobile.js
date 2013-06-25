
$(document).ready(function() {
//    var nav = $(".nav a.ajaxA");
//    var nextUrl = nav.get(($('a.ajaxA[href*="' + location.pathname + '"]').parent().index() + 1) % nav.parent().size());
//    alert(nextUrl);

    if (/Android|webOS|iPhone|iPad|iPod|BlackBerry/i.test(navigator.userAgent)) {

        // Hides mobile browser's address bar when page is done loading.
        window.addEventListener('load', function(e) {
            setTimeout(function() {
                window.scrollTo(0, 1);
            }, 1);
        }, false);
//
//        $(".wipeBox").touchwipe({
//            wipeLeft: function() {
//
//                alert("left");
//            },
//            wipeRight: function() {
//                alert("right");
//                event.preventDefault();
//                if ($.active) return;
//                var nav = $(".nav a.ajaxA");
//                var nextUrl = nav.get(($('a.ajaxA[href*="' + location.pathname + '"]').parent().index() + 1) % nav.parent().size());
//                $.post($.nette.href = nextUrl, $.nette.success);
//            },
//            min_move_x: 30,
//            min_move_y: 30,
//            preventDefaultEvents: true
//        });

    }
});