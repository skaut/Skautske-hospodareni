 
 $(document).ready(function() {
    if( /Android|webOS|iPhone|iPad|iPod|BlackBerry/i.test(navigator.userAgent) ) {
        
        // Hides mobile browser's address bar when page is done loading.
        window.addEventListener('load', function(e) {
          setTimeout(function() { window.scrollTo(0, 1); }, 1);
        }, false);
        
        
    }
 });