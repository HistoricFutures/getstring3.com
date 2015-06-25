$( document ).ready(function() {
        // Side drawer
        $( ".main-nav" ).clone().insertAfter( ".header" ).addClass("side-nav");
        
        $( ".menu-toggle" ).click(function() {

          $( ".header" ).toggleClass( "open" );
          $( ".side-nav" ).toggleClass( "open" );
          return false;

        });
      // Hide header on scroll downwards (unless navigation open)
      var previousScroll = 0;

      $(document).scroll(function(){
        var currentScroll = $(this).scrollTop();
         if ($(".header").hasClass("open")){

         } else {
           if (currentScroll > previousScroll){
                $('.header').fadeOut();
           } else {
                $('.header').fadeIn();
                $('.header').addClass('fixed');
           };
         };
         previousScroll = currentScroll;
      });
    
      // Same for on mobile (when scroll area is scrolling)
      $("#scroll-area").scroll(function(){
        var currentScroll = $(this).scrollTop();
         if ($(".header").hasClass("open")){

         } else {
           if (currentScroll > previousScroll){
                $('.header').fadeOut();
           } else {
                $('.header').fadeIn();
                $('.header').addClass('fixed');
           };
         };
         previousScroll = currentScroll;
      });
    });
      
      //SVG fallback
    
      $( document ).ready(function() {
          if (!Modernizr.svg) {
    var imgs = document.getElementsByTagName('img');
    var svgExtension = /.*\.svg$/
    var l = imgs.length;
    for(var i = 0; i < l; i++) {
        if(imgs[i].src.match(svgExtension)) {
            imgs[i].src = imgs[i].src.slice(0, -3) + 'png';
            console.log(imgs[i].src);
        }
    }
}
        });
