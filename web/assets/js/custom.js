$(function() {
    
    jQuery.fn.getNotice = function(){
        $.getJSON($(this).eq(0).data('link'), function(data) {            
//            $('.notice-contaner').data('notreadnoticescount',data.notreadnotices);
//            if( data.notreadnotices == '0') {
//                $('.notice-contaner .notice-count').empty();
//            }else{
//                if( $( this ).data('notreadnoticescount') != data.notreadnotices)
//                {
//                    alert('have new notice');
//                }
//                $('.notice-contaner .notice-count').text( data.notreadnotices );
//            }
        });  
        return this;
    };
    $('.notice-contaner').getNotice();
    
    setInterval(function() {
        $('.notice-contaner').getNotice();
    }, 30000);
    
    $(window).scroll(function() {
        var scroll = $(window).scrollTop();
        if (scroll > 300) {
            $("nav#mainNav").addClass("shadow");
        } else {
            $("nav#mainNav").removeClass("shadow");
        }
    });
    $(window).trigger('scroll');
});

// Register/ Login modal

$(document).ready(function() {

    if ($(window).width() < 769) {
        $('.open-modal.register.btn.btn-fineart').css('display', 'none');
        $('.small-device.register.btn.btn-fineart').css('display', 'block');
     }
     else {
        $('.open-modal.register.btn.btn-fineart').css('display', 'block');
        $('.small-device.register.btn.btn-fineart').css('display', 'none');
     }
    
    
    // Register/ login modal
                
    $('.open-modal').click(function () {
        $('#my-modal').css('display', 'block');
    });

    $('.close-modal').click(function () {
        $('#my-modal').css('display', 'none');
    });

    $('#my-modal').click(function() {
        $('#my-modal').css('display', 'none');
    });

    $('.modal-content').click(function(event){
        event.stopPropagation();
    });
    
    $('.register').click(function () {
        $('.tab1').css('display', 'none');
        $('.tab2').css('display', 'none');
        $('.tab3').css('display', 'block');
    });
    
    $('.login').click(function () {
        $('.tab1').css('display', 'block');
        $('.tab2').css('display', 'none');
        $('.tab3').css('display', 'none');
    });
    
    $('.to-register .button2').click(function () {
        $('.tab1').css('display', 'none');
        $('.tab2').css('display', 'none');
        $('.tab3').css('display', 'block');
    });
    
    $('.to-login .button2').click(function () {
        $('.tab1').css('display', 'block');
        $('.tab2').css('display', 'none');
        $('.tab3').css('display', 'none');
    });
    
    $('.to-login2 .button2').click(function () {
        $('.tab1').css('display', 'block');
        $('.tab3').css('display', 'none');
        $('.tab2').css('display', 'none');
    });
    
    $('.to-form').click(function () {
        $('.tab2').css('display', 'none');
        $('.tab3').css('display', 'none');
        $('.tab1').css('display', 'block');
    });
    
    
    // Modal Ask about that artwork - single work assigned to the auction
    
    $('.close').click(function () {
        $('#myModal').removeClass(' in').css('display', 'none');
        $('body').removeClass('modal-open').css('padding-right', '');
        $('.modal-backdrop').remove();
    });
    $('.modal-footer button.btn.btn-default').click(function () {
        $('#myModal').removeClass(' in').css('display', 'none');
        $('body').removeClass('modal-open').css('padding-right', '');
        $('.modal-backdrop').remove();
    });
    
    // Bank transfer modal - view reports
//    
//
//    $('#bankTransfer .close').click(function () {
//        $('#bankTransfer').removeClass(' in').css('display', 'none');
//        $('body').removeClass('modal-open').css('padding-right', '');
//        $('.modal-backdrop').remove();
//    });
//    
//    $('#bankTransfer button.btn.btn-default').click(function () {
//        $('#bankTransfer').removeClass(' in').css('display', 'none');
//        $('body').removeClass('modal-open').css('padding-right', '');
//        $('.modal-backdrop').remove();
//    });
//    
//    $('#bankTransfer button.transferModal').click(function () {
//        $('#bankTransfer').addClass(' in').css('display', 'block');
//        $('body').addClass('modal-open').css('padding-right', '17px');
//    });



    var $_GET = {};

    document.location.search.replace(/\??(?:([^=]+)=([^&]*)&?)/g, function () {
        function decode(s) {
            return decodeURIComponent(s.split("+").join(" "));
        }

        $_GET[decode(arguments[1])] = decode(arguments[2]);
    });

    var login = $_GET["fos_user_registration_form[email]"];
    var fullName = $_GET["fos_user_registration_form[fullname]"];
    var checkbox = $_GET["fos_user_registration_form[terms]"];

    $('input#fos_user_registration_form_email').val(login); 
    $('input#fos_user_registration_form_fullname').val(fullName); 
    
    if(login != null) {
        if(checkbox == 1) {
            $('input#fos_user_registration_form_terms').attr('checked', 'checked');
        }
    }


 
    
    




// end
});

