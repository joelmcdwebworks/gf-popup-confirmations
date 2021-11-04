(function($) {

    // Move the confirmation message to the modal, open, and trao focus

    $("#gform-notification").detach().appendTo("#overlay");

    $("body").addClass("message-sent"); // Display the modal.

    trapFocus( $('.message-sent #gform-notification') ); // Trap focus.

    // Close the modal

    function closeModal() {

        $("#overlay,#gform-notification").fadeOut("normal", function() {

            $(this).remove();

        });        

    }

    $("#gform-notification a").click(function() {

        closeModal();

    });

    $(document).on('click',function(e){

        if(!(($(e.target).closest("#gform-notification").length > 0 ))){

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

    // Clear all entered values upon form load. This will make sure the form is
    // clear when reloaded for the confirmation.

    function clearValues( elem ) {

        if($(elem).is(":visible")) {

            $(elem).val("");

        }
        
    }

    $(".gform-body input").each( function() {

        clearValues( $(this) );

    });

    $(".gform-body textarea").each( function() {

        clearValues( $(this) );

    });    

})(jQuery);