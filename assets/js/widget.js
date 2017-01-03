$(document).ready(function () {
    console.log('loading widget...');
    $('.masala-widget').hover(function () {
        $(this).stop().animate({right: '220'}, 'slow');
    }, function () {
        $(this).stop().animate({right: '-420'}, 'medium');
    }, 500);
});