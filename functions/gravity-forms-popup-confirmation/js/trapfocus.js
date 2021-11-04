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

        const firstFocusableElement = modal.querySelectorAll(focusableElements)[0]; // get first element to be focused inside modal

        const focusableContent = modal.querySelectorAll(focusableElements);

        const lastFocusableElement = focusableContent[focusableContent.length - 1]; // get last element to be focused inside modal


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

        firstFocusableElement.focus();

    } // trapFocus

    window.trapFocus = trapFocus;

})(jQuery);