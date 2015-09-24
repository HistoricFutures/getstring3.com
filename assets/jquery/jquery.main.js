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


/// Compare plans

$(document).ready(function() {
      //SETUP
      // remove expanded class from toggleable rows
    	$( '.compare-plans .expanded' ).removeClass('expanded');
      // hide description
      $( '.compare-plans .show-hide' ).hide();
      // remove rowspan attribute
      $( '.compare-plans .toggle' ).closest('th').removeAttr('rowspan');

      //ONCLICK
      $( '.compare-plans .toggle' ).on( 'click', function () {
        // Toggle expanded class on rows
        $( this ).closest('tr').toggleClass( 'expanded' );
        // Toggle rowspan attribute
        if ($( this ).closest('th').attr('rowspan')) {
            $( this ).closest('th').removeAttr('rowspan');
        } else {
            $( this ).closest('th').attr( 'rowspan','2' );
        }
        // Toggle description hiding and showing
        $( this ).closest('tr').next('.show-hide').toggle();
    });

});

/// Expandable list

$(document).ready(function() {
      //SETUP
      // remove expanded class from toggleable rows
    	$( '.expandable-list .expanded' ).removeClass('expanded');
      // hide description
      $( '.expandable-list .show-hide' ).hide();
      // remove expanded class from toggleable rows
    	$( '.expandable-single-item' ).removeClass('expanded');
      // hide description
      $(  '.expandable-single-item .show-hide' ).hide();


      //ONCLICK
      $( '.expandable-list .toggle').on( 'click', function () {
        // Toggle expanded class on list
        $( this ).closest('li').toggleClass( 'expanded' );

        // Toggle description hiding and showing
        $( this ).next('.show-hide').slideToggle();
    });

    $( '.expandable-single-item .toggle').on( 'click', function () {
      // Toggle expanded class on list
      $( this ).closest('.expandable-single-item').toggleClass( 'expanded' );

      // Toggle description hiding and showing
      $( this ).next('.show-hide').slideToggle();
  });

});
