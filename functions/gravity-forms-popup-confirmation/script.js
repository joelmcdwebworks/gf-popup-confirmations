(function($) {

    // Move the confirmation message to the modal, open, and trap focus

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

    // Clear all entered values upon form load. This will make sure the form is clear when reloaded for the confirmation.

    $(document).on("gform_post_render", function(event, form_id, current_page) {

        $(".gform_confirmation_wrapper input").each( function() {

            $(this).val("");

        });

        $(".gform_confirmation_wrapper textarea").each( function() {

            $(this).val("");

        });

    });   

})(jQuery);