(function($) {

    // Check for confirmation URL parameter.

    // https://stackoverflow.com/questions/19491336/how-to-get-url-parameter-using-jquery-or-plain-javascript

    var getUrlParameter = function getUrlParameter(sParam) {
        var sPageURL = window.location.search.substring(1),
            sURLVariables = sPageURL.split('&'),
            sParameterName,
            i;
    
        for (i = 0; i < sURLVariables.length; i++) {
            sParameterName = sURLVariables[i].split('=');
    
            if (sParameterName[0] === sParam) {
                return typeof sParameterName[1] === undefined ? true : decodeURIComponent(sParameterName[1]);
            }
        }
        return false;
    };

    var popupConfirmation = getUrlParameter('gfcnf');

    if (popupConfirmation) {

        var message = atob(popupConfirmation);

        var popupMarkup = '<div id="gf-popup-confirmation" aria-modal="true" role="dialog"><a class="close">&times;</a><div class="message">' + message + '</div><a class="button" rel="nofollow" href="javascript:void(0);">OK</a></div>';

        // Clear message URL parameter.
        
        const url = new URL(location);
        url.searchParams.delete('gfcnf');
        history.replaceState(null, null, url);

        $('body').append( '<div id="gfcnf-overlay"></div>' );

        $('#gfcnf-overlay').append( popupMarkup );

        $('body').addClass('gfcnf-confirmation');

        trapFocus( $('.message-sent #gf-popup-confirmation') ); // Trap focus.        

    }

    // Close the modal

    function closeModal() {

        $("#gfcnf-overlay, #gf-popup-confirmation").fadeOut("normal", function() {

            $(this).remove();

        });        

    }

    $("#gf-popup-confirmation a").click(function() {

        closeModal();

    });

    $(document).on('click',function(e){

        if(!(($(e.target).closest("#gf-popup-confirmation").length > 0 ))){

            closeModal();

       }
       
    });
    
    $( document ).on( 'keydown', function ( e ) {

        if( $('#overlay').is(":visible") ) {

            if ( e.keyCode === 27 ) { // ESC

                closeModal();
    
            }

        }

    });    

    // // Clear all entered values upon form load. This will make sure the form is clear when reloaded for the confirmation.

    // $(document).on("gform_post_render", function(event, form_id, current_page) {

    //     $(".gform_confirmation_wrapper input").each( function() {

    //         $(this).val("");

    //     });

    //     $(".gform_confirmation_wrapper textarea").each( function() {

    //         $(this).val("");

    //     });

    // });   

})(jQuery);