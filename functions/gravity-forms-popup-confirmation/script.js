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

        // var message = decodeURI(atob(popupConfirmation));

        var message = Base64.decode(popupConfirmation);

        var popupMarkup = '<div id="gf-popup-confirmation" aria-modal="true" role="dialog"><a class="close">&times;</a><div class="message">' + message + '</div><button class="wp-element-button gf-popup-button">OK</button></div>';

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

    $("#gf-popup-confirmation button").click(function() {

        closeModal();

    });

    $("#gf-popup-confirmation .close").click(function() {

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

})(jQuery);
