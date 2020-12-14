define([
    "jquery","jquery-ui-modules/tooltip", "cdz_slider", 'domReady!', "toggleAdvanced", "matchMedia", 'mage/tabs','Codazon_ProductFilter/js/productfilter'
], function($) {
    $.fn._buildToggle = function() {
        $("[data-cdz-toggle]").each(function() {
            $(this).toggleAdvanced({
                selectorsToggleClass: "active",
                baseToggleClass: "expanded",
                toggleContainers: $(this).data('cdz-toggle'),
            });
        });

    };
    $.fn._buildTabs = function() {
        if ($('.cdz-tabs').length > 0) {
            $('.cdz-tabs').each(function() {
                var $tab = $(this);
                mediaCheck({
                    media: '(min-width: 768px)',
                    // Switch to Desktop Version
                    entry: function() {
                        $tab.tabs({
                            openedState: "active",
                            openOnFocus: true,
                            collapsible: false,
                        });
                    },
                    // Switch to Mobile Version
                    exit: function() {
                        $tab.tabs({
                            openedState: "active",
                            openOnFocus: false,
                            collapsible: true
                        });
                    }
                });
            });
        }
    };

    $.fn._buildSlider = function() {
        if ($('.cdz-slider').length > 0) {
            $('.cdz-slider').each(function() {
                var $owl = $(this);
                if ((typeof $owl.data('no_slider') == 'undefined') || (!$owl.data('noslider'))) {
                    $owl.addClass('owl-carousel');
                    var sliderItem = typeof($owl.data('items')) !== 'undefined' ? $owl.data('items') : 5;
                    $owl.owlCarousel({
                        loop: typeof($owl.data('loop')) !== 'undefined' ? $owl.data('loop') : true,
                        margin: typeof($owl.data('margin')) !== 'undefined' ? $owl.data('margin') : 0,
                        responsiveClass: true,
                        nav: typeof($owl.data('nav')) !== 'undefined' ? $owl.data('nav') : true,
                        dots: typeof($owl.data('dots')) !== 'undefined' ? $owl.data('dots') : false,
                        autoplay: typeof($owl.data('autoplay')) !== 'undefined' ? $owl.data('autoplay') : false,
                        autoplayTimeout: typeof($owl.data('autoplayTimeout')) !== 'undefined' ? $owl.data('autoplayTimeout') : 1000,
                        autoplayHoverPause: typeof($owl.data('autoplayHoverPause')) !== 'undefined' ? $owl.data('autoplayHoverPause') : false,
                        items: sliderItem,
                        autoWidth: typeof($owl.data('autoWidth')) !== 'undefined' ? $owl.data('autoWidth') : false,
                        rtl: ThemeOptions.rtl_layout == 1 ? true : false,
                        responsive: {
                            0: {
                                items: typeof($owl.data('items-0')) !== 'undefined' ? $owl.data('items-0') : sliderItem
                            },
                            480: {
                                items: typeof($owl.data('items-480')) !== 'undefined' ? $owl.data('items-480') : sliderItem
                            },
                            768: {
                                items: typeof($owl.data('items-768')) !== 'undefined' ? $owl.data('items-768') : sliderItem
                            },
                            1024: {
                                items: typeof($owl.data('items-1024')) !== 'undefined' ? $owl.data('items-1024') : sliderItem
                            },
                            1280: {
                                items: typeof($owl.data('items-1280')) !== 'undefined' ? $owl.data('items-1280') : sliderItem
                            },
                            1440: {
                                items: typeof($owl.data('items-1440')) !== 'undefined' ? $owl.data('items-1440') : sliderItem
                            }
                        }
                    });
                    var center = typeof($owl.data('center')) !== 'undefined' ? $owl.data('center') : null;
                    if(center != null){
                        $owl.on('changed.owl.carousel', function(e) {
                            $owl.find(".owl-item").removeClass('center');
                            var item = $owl.find(".owl-item").get(e.item.index+1);
                            $(item).addClass('center');
                        });
                        var item = $owl.find(".owl-item").get(center);
                        $(item).addClass('center');
                    }
                }
                $owl.removeClass("cdz-slider");
            });
        }
    };

    $.fn._tooltip = function() {
        var iOS = /iPad|iPhone|iPod/.test(navigator.userAgent) && !window.MSStream;
        if(iOS == false){
            $('.show-tooltip').each(function() {
                $(this).tooltip({
                    position: {
                        my: "center top-80%",
                        at: "center top",
                        using: function(position, feedback) {
                            $(this).css(position);
                            $(this).addClass("cdz-tooltip");
                        }
                    }
                });
            })
        }
    };

    $.fn._fixBlogSearch = function(){
        $("#blog_search_mini_form button.search").prop("disabled", false);
    }

    $.fn._fixHoverIos = function(){
        $('a.product-item-photo').on('click touchend', function(e) {
            var el = $(this);
            var link = el.attr('href');
            window.location = link;
        });
    }

    $.fn._buildMobileDropdown = function(){
        if ($('.cdz-mobiledropdown').length > 0) {
            $('.cdz-mobiledropdown').each(function() {
                var $drop = $(this);
                $drop.mobiledropdown();
            });
        }
    }

    $.fn._enlargeImg = function(){
            var self = this;
            var imgsObj = $('.enlargeImg .openimg');//需要放大的图像
                if(imgsObj){
                    $.each(imgsObj,function(){
                        $(this).click(function(){
                            var currImg = $(this);
                            var lc = $('<div class=enlargeContainer></div>');//图片容器
                            var ww=$(window).width();
                            var wh=$(window).height()*1;
                            var stickmenu = 0;
                            if(ww < 768){
                                stickmenu = 15;
                                var wh=wh*1 -15*1;
                            }else{
                                if($('.sticky-menu.active').length){
                                    stickmenu = 70;
                                    var wh=wh*1 -85*1;
                                }else{
                                    stickmenu = 15;
                                    var wh=wh*1 -15*1;
                                }
                            }
                            lc.appendTo("body");
                            // var ol = $('<div class=overlayer></div>');
                            // ol.appendTo(lc);
                            var orignImg = new Image();
                            var realSrc = currImg.parent("p").find('.sourceImg').val();
                            // console.log(realSrc);
                            orignImg.src =realSrc ;
                            var cw= 1275;
                            var ch = 1650;
                            lc.html('<div class=overlayer></div><div class="openimgcont" style="height:'+wh+'px;"><div style="margin:0 auto;"><img style="width:1000px;" border=0 src=' + realSrc + '></div></div>');
                            // self._overlayer(1);
                            $.fn._overlayer(1);
                            lc.css('padding-top',stickmenu);                            
                            lc.click(function(){
                                $(this).remove();
                                $('.overlayer').remove();
                                // self._overlayer(0);
                                $.fn._overlayer(0);
                            });
                        });
                    });
                }
                else{
                    return false;
                }
        }
        $.fn._overlayer = function(tag){ 
            if(tag==1){
                $('.overlayer').css({
                    'height':$(document).height(),
                    'display':'block',
                    'opacity':1,
                    "background-color":"#FFFFFF",
                    "background-color":"rgba(0,0,0,0.5)"
                });
            }
            else{
                $('.overlayer').css('display','none');
            }
        }

    $.fn._enlargeImg();
    $.fn._buildSlider();
    $.fn._buildTabs();
    $.fn._tooltip();
    $.fn._buildToggle();
    $.fn._fixBlogSearch();
    $.fn._buildMobileDropdown();
    //$.fn._fixHoverIos();
    setTimeout($.fn._fixBlogSearch,500);


});
