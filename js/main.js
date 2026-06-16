(function($) {
    "use strict";

    var spinner = function() {
        setTimeout(function() {
            if ($('#spinner').length > 0) {
                $('#spinner').removeClass('show');
            }
        }, 1);
    };
    spinner(0);

    function initWow() {
        new WOW().init();
    }

    function initStickyNavbar() {
        $(window).scroll(function() {
            if ($(this).scrollTop() > 45) {
                $('.nav-bar').addClass('sticky-top shadow-sm').css('top', '0px');
            } else {
                $('.nav-bar').removeClass('sticky-top shadow-sm').css('top', '-100px');
            }
        });
    }

    function initHeaderCarousel() {
        $(".header-carousel").owlCarousel({
            animateOut: 'fadeOut',
            items: 1,
            margin: 0,
            stagePadding: 0,
            autoplay: true,
            smartSpeed: 500,
            dots: true,
            loop: true,
            nav: true,
            navText: [
                '<i class="bi bi-arrow-left"></i>',
                '<i class="bi bi-arrow-right"></i>'
            ],
        });
    }

    function initTestimonialCarousel() {
        $(".testimonial-carousel").owlCarousel({
            autoplay: true,
            smartSpeed: 1500,
            center: false,
            dots: false,
            loop: true,
            margin: 25,
            nav: true,
            navText: [
                '<i class="fa fa-arrow-right"></i>',
                '<i class="fa fa-arrow-left"></i>'
            ],
            responsiveClass: true,
            responsive: {
                0: { items: 1 },
                576: { items: 1 },
                768: { items: 2 },
                992: { items: 2 },
                1200: { items: 2 }
            }
        });
    }

    function initCounterUp() {
        $('[data-toggle="counter-up"]').counterUp({
            delay: 5,
            time: 2000
        });
    }

    function initBackToTop() {
        $(window).scroll(function() {
            if ($(this).scrollTop() > 300) {
                $('.back-to-top').fadeIn('slow');
            } else {
                $('.back-to-top').fadeOut('slow');
            }
        });

        $('.back-to-top').click(function() {
            $('html, body').animate({ scrollTop: 0 }, 1500, 'easeInOutExpo');
            return false;
        });
    }

    // Inisialisasi semua komponen saat DOM siap
    $(document).ready(function() {
        initWow();
        initStickyNavbar();
        initHeaderCarousel();
        initTestimonialCarousel();
        initCounterUp();
        initBackToTop();

        $('[data-bs-toggle="tooltip"]').tooltip();
        $('[data-bs-toggle="popover"]').popover();

        console.log("Semua komponen berhasil diinisialisasi.");
    });

})(jQuery);