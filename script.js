(function($) {

    /**
    *
    *  Base64 encode / decode
    *  http://www.webtoolkit.info/
    **/

    var Base64 = {

        // private property
        _keyStr : "ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz0123456789+/=",

        // public method for encoding
        encode : function (input) {
        var output = "";
        var chr1, chr2, chr3, enc1, enc2, enc3, enc4;
        var i = 0;

        input = Base64._utf8_encode(input);

        while (i < input.length) {

            chr1 = input.charCodeAt(i++);
            chr2 = input.charCodeAt(i++);
            chr3 = input.charCodeAt(i++);

            enc1 = chr1 >> 2;
            enc2 = ((chr1 & 3) << 4) | (chr2 >> 4);
            enc3 = ((chr2 & 15) << 2) | (chr3 >> 6);
            enc4 = chr3 & 63;

            if (isNaN(chr2)) {
            enc3 = enc4 = 64;
            } else if (isNaN(chr3)) {
            enc4 = 64;
            }

            output = output +
            this._keyStr.charAt(enc1) + this._keyStr.charAt(enc2) +
            this._keyStr.charAt(enc3) + this._keyStr.charAt(enc4);

        }

        return output;
        },

        // public method for decoding
        decode : function (input) {
        var output = "";
        var chr1, chr2, chr3;
        var enc1, enc2, enc3, enc4;
        var i = 0;

        input = input.replace(/[^A-Za-z0-9\+\/\=]/g, "");

        while (i < input.length) {

            enc1 = this._keyStr.indexOf(input.charAt(i++));
            enc2 = this._keyStr.indexOf(input.charAt(i++));
            enc3 = this._keyStr.indexOf(input.charAt(i++));
            enc4 = this._keyStr.indexOf(input.charAt(i++));

            chr1 = (enc1 << 2) | (enc2 >> 4);
            chr2 = ((enc2 & 15) << 4) | (enc3 >> 2);
            chr3 = ((enc3 & 3) << 6) | enc4;

            output = output + String.fromCharCode(chr1);

            if (enc3 != 64) {
            output = output + String.fromCharCode(chr2);
            }
            if (enc4 != 64) {
            output = output + String.fromCharCode(chr3);
            }

        }

        output = Base64._utf8_decode(output);

        return output;

        },

        // private method for UTF-8 encoding
        _utf8_encode : function (string) {
        string = string.replace(/\r\n/g,"\n");
        var utftext = "";

        for (var n = 0; n < string.length; n++) {

            var c = string.charCodeAt(n);

            if (c < 128) {
            utftext += String.fromCharCode(c);
            }
            else if((c > 127) && (c < 2048)) {
            utftext += String.fromCharCode((c >> 6) | 192);
            utftext += String.fromCharCode((c & 63) | 128);
            }
            else {
            utftext += String.fromCharCode((c >> 12) | 224);
            utftext += String.fromCharCode(((c >> 6) & 63) | 128);
            utftext += String.fromCharCode((c & 63) | 128);
            }

        }

        return utftext;
        },

        // private method for UTF-8 decoding
        _utf8_decode : function (utftext) {
        var string = "";
        var i = 0;
        var c = c1 = c2 = 0;

        while ( i < utftext.length ) {

            c = utftext.charCodeAt(i);

            if (c < 128) {
            string += String.fromCharCode(c);
            i++;
            }
            else if((c > 191) && (c < 224)) {
            c2 = utftext.charCodeAt(i+1);
            string += String.fromCharCode(((c & 31) << 6) | (c2 & 63));
            i += 2;
            }
            else {
            c2 = utftext.charCodeAt(i+1);
            c3 = utftext.charCodeAt(i+2);
            string += String.fromCharCode(((c & 15) << 12) | ((c2 & 63) << 6) | (c3 & 63));
            i += 3;
            }

        }

        return string;
        }

    }

    // Prevent multiple executions of this script
    if (window.gfPopupConfirmationExecuted) {
        return;
    }
    window.gfPopupConfirmationExecuted = Date.now();
    
    // Store the element that had focus before modal opened
    var elementBeforeModal = null;
    
    // Clear any stale popup timestamps on page load
    var pageLoadTime = Date.now();
    var lastPageLoad = sessionStorage.getItem('gf_popup_page_load');
    if (!lastPageLoad || (pageLoadTime - parseInt(lastPageLoad)) > 5000) {
        sessionStorage.removeItem('gf_popup_last_time');
        sessionStorage.setItem('gf_popup_page_load', pageLoadTime.toString());
    }

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

    // Helper function to clean up URL parameters properly
    var cleanUrlParameters = function(url, parameterToRemove) {
        // Remove the specified parameter
        var cleanedUrl = url.replace(new RegExp('[?&]' + parameterToRemove + '=[^&#]*'), '');
        
        // Fix URL structure: ensure ? precedes the first parameter
        // If the URL now starts with & (because the removed parameter was the first), replace & with ?
        if (cleanedUrl.indexOf('&') !== -1 && cleanedUrl.indexOf('?') === -1) {
            cleanedUrl = cleanedUrl.replace('&', '?');
        }
        
        // Clean up any double ? or & characters
        cleanedUrl = cleanedUrl.replace(/\?&/, '?').replace(/&&/, '&');
        
        // Remove trailing ? if no other parameters
        cleanedUrl = cleanedUrl.replace(/\?$/, '');
        
        return cleanedUrl;
    };

    // Check if browser supports native dialog element
    function supportsDialog() {
        return typeof HTMLDialogElement !== 'undefined' && 
               typeof HTMLDialogElement.prototype.showModal === 'function';
    }

    // Create and show modal using native dialog element
    function createNativeDialog(message) {
        var sanitizedMessage = sanitizeMessage(message);
        
        // Store the currently focused element before opening modal
        elementBeforeModal = document.activeElement;
        
        // Create live region for screen reader announcements
        var liveRegion = document.getElementById('gf-popup-live-region');
        if (!liveRegion) {
            liveRegion = document.createElement('div');
            liveRegion.id = 'gf-popup-live-region';
            liveRegion.setAttribute('aria-live', 'polite');
            liveRegion.setAttribute('aria-atomic', 'true');
            liveRegion.style.position = 'absolute';
            liveRegion.style.left = '-10000px';
            liveRegion.style.width = '1px';
            liveRegion.style.height = '1px';
            liveRegion.style.overflow = 'hidden';
            document.body.appendChild(liveRegion);
        }
        
        var dialogMarkup = '<dialog id="gf-popup-confirmation" aria-labelledby="gf-popup-title">' +
            '<button type="button" class="close" aria-label="Close confirmation dialog" title="Close confirmation dialog"><span class="icon-close" aria-hidden="true"></span></button>' +
            '<div class="message" id="gf-popup-title" tabindex="-1">' + sanitizedMessage + '</div>' +
            '<button class="wp-element-button gf-popup-button">OK</button>' +
            '</dialog>';

        $('body').append(dialogMarkup);

        var dialog = document.getElementById('gf-popup-confirmation');
        
        // Add event listeners
        $(dialog).find('.gf-popup-button').click(function() {
            closeNativeDialog();
        });

        $(dialog).find('.close').click(function() {
            closeNativeDialog();
        });

        // Add keyboard event listeners for native dialog
        $(dialog).on('keydown', function(e) {
            if (e.key === 'Tab') {
                var focusableElements = dialog.querySelectorAll('button, [href], input, select, textarea, [tabindex]:not([tabindex="-1"])');
                var firstElement = focusableElements[0];
                var lastElement = focusableElements[focusableElements.length - 1];
                
                if (e.shiftKey) {
                    if (document.activeElement === firstElement) {
                        lastElement.focus();
                        e.preventDefault();
                    }
                } else {
                    if (document.activeElement === lastElement) {
                        firstElement.focus();
                        e.preventDefault();
                    }
                }
            } else if (e.key === 'Escape') {
                closeNativeDialog();
            }
        });

        // Show the dialog
        dialog.showModal();
        
        // Announce modal opening to screen readers
        if (liveRegion) {
            liveRegion.textContent = 'Confirmation dialog opened';
        }
        
        // Focus the OK button (primary action) when dialog opens
        var okButton = dialog.querySelector('.gf-popup-button');
        if (okButton) {
            okButton.focus();
        }
    }

    // Create and show modal using legacy div-based approach
    function createLegacyDialog(message) {
        var sanitizedMessage = sanitizeMessage(message);
        
        // Store the currently focused element before opening modal
        elementBeforeModal = document.activeElement;
        
        var popupMarkup = '<div id="gf-popup-confirmation" aria-modal="true" role="dialog" aria-labelledby="gf-popup-title">' +
            '<button type="button" class="close" aria-label="Close confirmation dialog" title="Close confirmation dialog"><span class="icon-close" aria-hidden="true"></span></button>' +
            '<div class="message" id="gf-popup-title" tabindex="-1">' + sanitizedMessage + '</div>' +
            '<button class="wp-element-button gf-popup-button">OK</button>' +
            '</div>';

        $('body').append('<div id="gfcnf-overlay"></div>');
        $('#gfcnf-overlay').append(popupMarkup);
        $('body').addClass('gfcnf-confirmation');

        // Use existing trapFocus function for legacy implementation
        if (typeof trapFocus === 'function') {
            trapFocus($('#gf-popup-confirmation'));
        }

        // Add event listeners for legacy modal
        $("#gf-popup-confirmation button").click(function() {
            closeLegacyDialog();
        });

        $("#gf-popup-confirmation .close").click(function() {
            closeLegacyDialog();
        });

        // Close on overlay click
        $(document).on('click', function(e) {
            if (!($(e.target).closest("#gf-popup-confirmation").length > 0)) {
                closeLegacyDialog();
            }
        });

        // Close on ESC key
        $(document).on('keydown', function(e) {
            if ($('#gfcnf-overlay').is(":visible")) {
                if (e.keyCode === 27) { // ESC
                    closeLegacyDialog();
                }
            }
        });
    }

    // Sanitize message to prevent XSS and convert line breaks to paragraphs
    function sanitizeMessage(message) {
        var tempDiv = $('<div>').html(message);
        
        // Remove script tags and event handlers
        tempDiv.find('script').remove();
        tempDiv.find('*').removeAttr('onclick onload onerror onmouseover onfocus onblur');
        
        // Remove any javascript: URLs
        tempDiv.find('a[href^="javascript:"]').removeAttr('href');
        
        // Convert <br> tags to <p> tags for better semantic structure
        var htmlContent = tempDiv.html();
        
        // Check if content already contains block-level elements
        var hasBlockElements = /<(p|div|h[1-6]|section|article|header|footer|main|aside|nav|blockquote|pre|table|ul|ol|li|form|fieldset|legend|address|figure|figcaption|details|summary)>/i.test(htmlContent);
        
        // Split by <br> tags (case insensitive)
        var parts = htmlContent.split(/<br\s*\/?>/i);
        
        if (parts.length > 1) {
            var newContent = '';
            for (var i = 0; i < parts.length; i++) {
                var part = parts[i].trim();
                if (part) {
                    // Only wrap in <p> if the part doesn't already contain block elements
                    if (!/<(p|div|h[1-6]|section|article|header|footer|main|aside|nav|blockquote|pre|table|ul|ol|li|form|fieldset|legend|address|figure|figcaption|details|summary)>/i.test(part)) {
                        newContent += '<p>' + part + '</p>';
                    } else {
                        newContent += part;
                    }
                }
            }
            tempDiv.html(newContent);
        } else if (!hasBlockElements) {
            // If no <br> tags and no existing block elements, wrap the entire content in a paragraph
            var content = tempDiv.html().trim();
            if (content) {
                tempDiv.html('<p>' + content + '</p>');
            }
        }
        // If content already has block elements, leave it as-is
        
        return tempDiv.html();
    }

    // Close native dialog
    function closeNativeDialog() {
        var dialog = document.getElementById('gf-popup-confirmation');
        if (dialog) {
            // Announce modal closing to screen readers
            var liveRegion = document.getElementById('gf-popup-live-region');
            if (liveRegion) {
                liveRegion.textContent = 'Confirmation dialog closed';
            }
            
            dialog.close();
            $(dialog).remove();
            
            // Restore focus to the element that had focus before modal opened
            if (elementBeforeModal && elementBeforeModal.focus) {
                elementBeforeModal.focus();
            }
        }
    }

    // Close legacy dialog
    function closeLegacyDialog() {
        $("#gfcnf-overlay, #gf-popup-confirmation").fadeOut("normal", function() {
            $(this).remove();
            
            // Restore focus to the element that had focus before modal opened
            if (elementBeforeModal && elementBeforeModal.focus) {
                elementBeforeModal.focus();
            }
        });
    }

    var popupConfirmation = getUrlParameter('gfcnf');

    if (popupConfirmation) {
        // Clear the parameter immediately to prevent it from persisting
        var newUrl = cleanUrlParameters(window.location.href, 'gfcnf');
        
        // Update the URL without the gfcnf parameter
        window.history.replaceState({}, document.title, newUrl);
        
        // If an inline confirmation is present, do not show the popup
        if ($('.gform_confirmation_message:visible').length > 0) {
            // Clear session storage related to popup
            sessionStorage.removeItem('gf_popup_last_time');
            popupConfirmation = null;
            return;
        }

        // Check if this parameter was just set (within the last few seconds)
        var currentTime = Date.now();
        var lastPopupTime = sessionStorage.getItem('gf_popup_last_time');
        
        if (lastPopupTime) {
            var timeDiff = currentTime - parseInt(lastPopupTime);
            
            // If less than 2 seconds, this might be a stale parameter
            if (timeDiff < 2000) {
                return;
            }
        }
        
        // Store the current time
        sessionStorage.setItem('gf_popup_last_time', currentTime.toString());

        var message = Base64.decode(popupConfirmation);

        // Use native dialog if supported, otherwise fall back to legacy
        if (supportsDialog()) {
            createNativeDialog(message);
        } else {
            createLegacyDialog(message);
        }

        popupConfirmation = null;
    }
    
    // Clean up any forms on the page that have the gfcnf parameter in their action
    $('form').each(function(index) {
        var $form = $(this);
        var formAction = $form.attr('action') || '';
        if (formAction.indexOf('gfcnf') !== -1) {
            // Clean up the form action to remove gfcnf parameter
            var cleanAction = cleanUrlParameters(formAction, 'gfcnf');
            $form.attr('action', cleanAction);
        }
    });

})(jQuery);
