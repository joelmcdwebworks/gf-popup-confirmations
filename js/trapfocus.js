// Thanks to https://uxdesign.cc/how-to-trap-focus-inside-modal-to-make-it-ada-compliant-6a50f9a70700

(function($) {

    var trapFocus;

    trapFocus = function(elem) {

        const modal = document.querySelector('#' + $(elem).attr('id')); // select the modal by it's id

        if(!modal) {

            return;

        }

        // add all the elements inside modal which you want to make focusable

        const  focusableElements =
            'button, [href], input, select, textarea, [tabindex]:not([tabindex="-1"])';

        const focusableContent = modal.querySelectorAll(focusableElements);
        
        if (focusableContent.length === 0) {
            return;
        }

        const firstFocusableElement = focusableContent[0]; // get first element to be focused inside modal

        const lastFocusableElement = focusableContent[focusableContent.length - 1]; // get last element to be focused inside modal

        // Focus the OK button (primary action) when modal opens
        const okButton = modal.querySelector('.gf-popup-button');
        if (okButton) {
            okButton.focus();
        } else {
            // Fallback to first focusable element if OK button not found
            firstFocusableElement.focus();
        }

        document.addEventListener('keydown', function(e) {

            let isTabPressed = e.key === 'Tab';

            if (!isTabPressed) {

                return;

            }

            if (e.shiftKey) { // if shift key pressed for shift + tab combination

                if (document.activeElement === firstFocusableElement) {

                lastFocusableElement.focus(); // add focus for the last focusable element

                e.preventDefault();

                }

            } else { // if tab key is pressed

                if (document.activeElement === lastFocusableElement) { // if focused has reached to last focusable element then focus first focusable element after pressing tab

                firstFocusableElement.focus(); // add focus for the first focusable element

                e.preventDefault();

                } // if

            } // if

        }); // Key listener

    } // trapFocus

    window.trapFocus = trapFocus;

})(jQuery);