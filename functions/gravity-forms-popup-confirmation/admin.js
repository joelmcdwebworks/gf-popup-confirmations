(function($) {
    'use strict';
    
    /**
     * Admin functionality for Gravity Forms Popup Confirmations
     */
    
    $(document).ready(function() {
        
        // Debug function to log what we find
        function debugLog(message, data) {
            if (typeof console !== 'undefined' && console.log) {
                console.log('GF Popup Confirmations:', message, data);
            }
        }
        
        // Check if we're on a Gravity Forms confirmation settings page
        function isConfirmationSettingsPage() {
            var url = window.location.href;
            var isConfirmationPage = (
                url.indexOf('gf_edit_forms') !== -1 && 
                url.indexOf('view=settings') !== -1 && 
                url.indexOf('subview=confirmation') !== -1
            );
            
            debugLog('Checking if confirmation settings page:', {
                url: url,
                isConfirmationPage: isConfirmationPage
            });
            
            return isConfirmationPage;
        }
        
        // Only run on confirmation settings pages
        if (!isConfirmationSettingsPage()) {
            debugLog('Not on confirmation settings page, skipping initialization');
            return;
        }
        
        // Function to add popup option to confirmation settings
        function addPopupOptionToConfirmations() {
            debugLog('Looking for confirmation type radio buttons...');
            
            // Log all radio buttons on the page for debugging
            var allRadios = $('input[type="radio"]');
            debugLog('All radio buttons found:', allRadios.length);
            allRadios.each(function(index) {
                var $radio = $(this);
                debugLog('Radio ' + index + ':', {
                    name: $radio.attr('name'),
                    value: $radio.attr('value'),
                    checked: $radio.is(':checked'),
                    parent: $radio.parent().text().substring(0, 50)
                });
            });
            
            // Try multiple selectors to find confirmation type radio buttons
            var selectors = [
                'input[name="_gform_setting_type"][value="message"]',
                'input[name="type"][value="message"]',
                'input[type="radio"][value="message"]',
                '.gform-settings-panel input[value="message"]',
                'input[value="message"]'
            ];
            
            var $radios = $();
            selectors.forEach(function(selector) {
                var $found = $(selector);
                if ($found.length > 0) {
                    $radios = $found;
                    debugLog('Found radios with selector:', selector, $found.length);
                    return false; // break forEach
                }
            });
            
            if ($radios.length === 0) {
                debugLog('No confirmation type radio buttons found, trying fallback approach');
                
                // Fallback: Look for any form settings and try to add popup option
                var $settingsContainers = $('.gform-settings-panel, .gform-settings-field, .gform-settings-section, .gform-settings-group');
                debugLog('Found settings containers:', $settingsContainers.length);
                
                $settingsContainers.each(function() {
                    var $container = $(this);
                    var containerText = $container.text().toLowerCase();
                    
                    // Check if this container has confirmation-related content
                    if (containerText.indexOf('confirmation') !== -1 || 
                        containerText.indexOf('message') !== -1 ||
                        containerText.indexOf('type') !== -1) {
                        
                        debugLog('Found potential confirmation container:', {
                            text: containerText.substring(0, 100),
                            container: $container
                        });
                        
                        if ($container.find('.gform_popup_confirmation_setting').length === 0) {
                            addPopupSettingToContainer($container);
                        }
                    }
                });
                
                return;
            }
            
            $radios.each(function() {
                var $radio = $(this);
                var $container = $radio.closest('.gform-settings-panel, .gform-settings-field, .gform-settings-section');
                
                if ($container.length === 0) {
                    debugLog('No container found for radio button');
                    return;
                }
                
                debugLog('Found container:', $container);
                
                // Only add if not already added
                if ($container.find('.gform_popup_confirmation_setting').length === 0) {
                    addPopupSettingToContainer($container);
                }
            });
        }
        
        // Function to add popup setting to a specific container
        function addPopupSettingToContainer($container) {
            var confirmationId = getConfirmationIdFromContainer($container);
            var isMessageType = isConfirmationTypeMessage($container);
            
            debugLog('Adding popup setting to container:', {
                confirmationId: confirmationId,
                isMessageType: isMessageType,
                container: $container
            });
            
            var popupHtml = createPopupSettingHtml(confirmationId, isMessageType);
            $container.append(popupHtml);
            
            // Show/hide based on confirmation type
            updatePopupSettingVisibility($container);
            
            // First check if popup setting is already in the current form data
            if (checkCurrentPopupSetting($container)) {
                debugLog('Found existing popup setting in current form data');
            } else {
                // Load existing popup setting from server
                loadExistingPopupSetting($container, confirmationId);
            }
        }
        
        // Function to get confirmation ID from container
        function getConfirmationIdFromContainer($container) {
            // Try to find confirmation ID from URL or form data
            var urlParams = new URLSearchParams(window.location.search);
            var confirmationId = urlParams.get('confirmation_id');
            
            debugLog('URL confirmation_id:', confirmationId);
            
            if (confirmationId) {
                return confirmationId;
            }
            
            // Look for hidden input with confirmation ID
            var $confirmationInput = $container.find('input[name="confirmation_id"], input[name="_gform_setting_id"]');
            if ($confirmationInput.length) {
                var inputValue = $confirmationInput.val();
                debugLog('Hidden input confirmation_id:', inputValue);
                return inputValue;
            }
            
            // Look for confirmation ID in the form
            var $form = $container.closest('form');
            if ($form.length) {
                var formAction = $form.attr('action') || '';
                var match = formAction.match(/confirmation_id=([^&]+)/);
                if (match) {
                    debugLog('Form action confirmation_id:', match[1]);
                    return match[1];
                }
                
                // Also check for confirmation ID in form inputs
                var $formConfirmationInput = $form.find('input[name="confirmation_id"], input[name="_gform_setting_id"]');
                if ($formConfirmationInput.length) {
                    var formInputValue = $formConfirmationInput.val();
                    debugLog('Form input confirmation_id:', formInputValue);
                    return formInputValue;
                }
            }
            
            debugLog('Using default confirmation_id');
            return 'default';
        }
        
        // Function to check if confirmation type is message
        function isConfirmationTypeMessage($container) {
            var selectors = [
                'input[name="_gform_setting_type"][value="message"]',
                'input[name="type"][value="message"]',
                'input[type="radio"][value="message"]'
            ];
            
            var isMessage = false;
            selectors.forEach(function(selector) {
                var $radio = $container.find(selector);
                if ($radio.length && $radio.is(':checked')) {
                    isMessage = true;
                    return false; // break forEach
                }
            });
            
            return isMessage;
        }
        
        // Function to create popup setting HTML
        function createPopupSettingHtml(confirmationId, isMessageType) {
            var displayStyle = isMessageType ? 'block' : 'none';
            
            return '<div class="gform_popup_confirmation_setting" style="display: ' + displayStyle + ';">' +
                   '<h4>Popup Confirmation</h4>' +
                   '<div class="gform_popup_confirmation_field">' +
                   '<label>' +
                   '<input type="checkbox" class="popup-modal-checkbox" data-confirmation-id="' + confirmationId + '" />' +
                   '<input type="hidden" name="_gform_setting_displayModal" value="0" />' +
                   'Display this message as a popup/modal.' +
                   '</label>' +
                   '</div>' +
                   '<p class="description">When enabled, this confirmation message will be displayed in a popup modal instead of inline.</p>' +
                   '</div>';
        }
        
        // Function to update popup setting visibility
        function updatePopupSettingVisibility($container) {
            var isMessageType = isConfirmationTypeMessage($container);
            var $popupSetting = $container.find('.gform_popup_confirmation_setting');
            
            debugLog('Updating popup setting visibility:', {
                isMessageType: isMessageType,
                popupSettingFound: $popupSetting.length
            });
            
            if (isMessageType) {
                $popupSetting.show();
            } else {
                $popupSetting.hide();
                // Uncheck the popup option when switching away from message
                $popupSetting.find('input[type="checkbox"]').prop('checked', false);
            }
        }
        
        // Handle confirmation type changes
        $(document).on('change', 'input[value="message"], input[name="_gform_setting_type"], input[name="type"]', function() {
            var $container = $(this).closest('.gform-settings-panel, .gform-settings-field, .gform-settings-section');
            debugLog('Confirmation type changed:', {
                value: $(this).val(),
                container: $container
            });
            updatePopupSettingVisibility($container);
        });
        
        // Handle popup checkbox changes - sync with hidden input
        $(document).on('change', '.popup-modal-checkbox', function() {
            var $checkbox = $(this);
            var confirmationId = $checkbox.data('confirmation-id');
            var displayModal = $checkbox.is(':checked');
            
            debugLog('Popup checkbox changed:', {
                confirmationId: confirmationId,
                displayModal: displayModal
            });
            
            // Update the hidden input field
            var $hiddenInput = $checkbox.siblings('input[name="_gform_setting_displayModal"]');
            if ($hiddenInput.length) {
                $hiddenInput.val(displayModal ? '1' : '0');
                debugLog('Updated hidden input value:', $hiddenInput.val());
            }
        });
        
        // Test function to manually check the checkbox (for debugging)
        window.testPopupCheckbox = function() {
            debugLog('Testing popup checkbox...');
            var $checkbox = $('.popup-modal-checkbox');
            if ($checkbox.length) {
                $checkbox.prop('checked', true);
                var $hiddenInput = $checkbox.siblings('input[name="_gform_setting_displayModal"]');
                if ($hiddenInput.length) {
                    $hiddenInput.val('1');
                }
                debugLog('Manually checked popup checkbox');
            } else {
                debugLog('No popup checkbox found');
            }
        };
        
        // Function to get form ID from page
        function getFormIdFromPage() {
            var urlParams = new URLSearchParams(window.location.search);
            var formId = urlParams.get('id');
            
            if (formId) {
                return formId;
            }
            
            // Fallback: look for hidden input
            var $formInput = $('input[name="form_id"]');
            if ($formInput.length) {
                return $formInput.val();
            }
            
            return null;
        }
        
        // Function to load existing popup setting
        function loadExistingPopupSetting($container, confirmationId) {
            var formId = getFormIdFromPage();
            
            debugLog('Loading existing popup setting:', {
                formId: formId,
                confirmationId: confirmationId
            });
            
            if (formId && confirmationId) {
                var ajaxData = {
                    action: 'gf_load_popup_confirmation',
                    nonce: gfPopupConfirmations.nonce,
                    form_id: formId,
                    confirmation_id: confirmationId
                };
                
                debugLog('Sending AJAX request with data:', ajaxData);
                
                $.ajax({
                    url: gfPopupConfirmations.ajaxUrl,
                    type: 'POST',
                    data: ajaxData,
                    success: function(response) {
                        debugLog('Load response:', response);
                        
                        if (response.success && response.data.displayModal) {
                            var $checkbox = $container.find('.popup-modal-checkbox');
                            var $hiddenInput = $container.find('input[name="_gform_setting_displayModal"]');
                            
                            $checkbox.prop('checked', true);
                            if ($hiddenInput.length) {
                                $hiddenInput.val('1');
                            }
                            
                            debugLog('Loaded existing popup setting: checked');
                        } else {
                            debugLog('No existing popup setting found or not enabled');
                        }
                    },
                    error: function(xhr, status, error) {
                        debugLog('AJAX error details:', {
                            status: status,
                            error: error,
                            responseText: xhr.responseText,
                            statusCode: xhr.status,
                            readyState: xhr.readyState
                        });
                        
                        console.error('Failed to load popup setting:', {
                            status: status,
                            error: error,
                            response: xhr.responseText
                        });
                    }
                });
            } else {
                debugLog('Missing formId or confirmationId for loading');
            }
        }
        
        // Function to check if popup setting is already enabled in the current form data
        function checkCurrentPopupSetting($container) {
            // Look for any indication that popup is already enabled
            var $form = $container.closest('form');
            if ($form.length) {
                // Check if there's already a hidden input with the value
                var $existingHidden = $form.find('input[name="_gform_setting_displayModal"][value="1"]');
                if ($existingHidden.length > 0) {
                    debugLog('Found existing popup setting in form');
                    var $checkbox = $container.find('.popup-modal-checkbox');
                    $checkbox.prop('checked', true);
                    return true;
                }
                
                // Check if the form action or other indicators suggest popup is enabled
                var formAction = $form.attr('action') || '';
                var formHtml = $form.html();
                
                debugLog('Checking form for popup indicators:', {
                    action: formAction,
                    hasPopupInHtml: formHtml.indexOf('displayModal') !== -1
                });
                
                // Look for any existing confirmation data that might indicate popup is enabled
                var $confirmationInputs = $form.find('input[name*="confirmation"], textarea[name*="confirmation"]');
                $confirmationInputs.each(function() {
                    var $input = $(this);
                    var inputName = $input.attr('name');
                    var inputValue = $input.val();
                    
                    debugLog('Confirmation input found:', {
                        name: inputName,
                        value: inputValue ? inputValue.substring(0, 100) : 'empty'
                    });
                });
            }
            
            return false;
        }
        
        // Function to save popup setting via AJAX - REMOVED
        // Popup settings will be saved with the confirmation settings form
        function savePopupSetting(formId, confirmationId, displayModal) {
            debugLog('Popup setting save function called but disabled - will save with form');
        }
        
        // Initialize popup options on page load
        debugLog('Initializing popup confirmations admin...');
        addPopupOptionToConfirmations();
        
        // Handle dynamic content loading
        $(document).on('DOMNodeInserted', function(e) {
            if ($(e.target).hasClass('gform-settings-panel') || 
                $(e.target).hasClass('gform-settings-field') || 
                $(e.target).hasClass('gform-settings-section')) {
                setTimeout(function() {
                    addPopupOptionToConfirmations();
                }, 100);
            }
        });
        
        // Also try again after a short delay to catch any late-loading content
        setTimeout(function() {
            debugLog('Retrying popup option addition...');
            addPopupOptionToConfirmations();
        }, 1000);
        
        // Additional check: inspect the page for any existing popup settings
        setTimeout(function() {
            debugLog('Inspecting page for existing popup settings...');
            
            // Look for any hidden inputs or data that might indicate popup is enabled
            var $allHiddenInputs = $('input[type="hidden"]');
            $allHiddenInputs.each(function() {
                var $input = $(this);
                var name = $input.attr('name');
                var value = $input.val();
                
                if (name && (name.indexOf('displayModal') !== -1 || name.indexOf('popup') !== -1)) {
                    debugLog('Found potential popup-related hidden input:', {
                        name: name,
                        value: value
                    });
                }
            });
            
            // Also check for any JavaScript variables that might contain form data
            if (typeof window.gform !== 'undefined' && window.gform.formData) {
                debugLog('Found gform.formData:', window.gform.formData);
            }
            
            // Check for any data attributes that might contain confirmation info
            var $confirmationElements = $('[data-confirmation], [data-gf-confirmation]');
            $confirmationElements.each(function() {
                var $element = $(this);
                debugLog('Found confirmation element:', {
                    element: $element[0].tagName,
                    data: $element.data()
                });
            });
        }, 2000);
        
        // Try a more aggressive approach - look for any form with confirmation settings
        setTimeout(function() {
            debugLog('Trying aggressive approach...');
            
            // Look for any form that might be a confirmation settings form
            var $forms = $('form');
            debugLog('Found forms:', $forms.length);
            
            $forms.each(function(index) {
                var $form = $(this);
                var formAction = $form.attr('action') || '';
                var formHtml = $form.html();
                
                debugLog('Form ' + index + ':', {
                    action: formAction,
                    hasConfirmation: formHtml.indexOf('confirmation') !== -1,
                    hasMessage: formHtml.indexOf('message') !== -1
                });
                
                // If this looks like a confirmation form, try to add popup option
                if (formAction.indexOf('gf_edit_forms') !== -1 || 
                    formHtml.indexOf('confirmation') !== -1) {
                    
                    var $messageFields = $form.find('input[value="message"], select option[value="message"]');
                    if ($messageFields.length > 0) {
                        debugLog('Found message fields in form:', $messageFields.length);
                        $messageFields.each(function() {
                            var $field = $(this);
                            var $container = $field.closest('.gform-settings-panel, .gform-settings-field, .gform-settings-section, .gform-settings-group, .gform-settings-row');
                            
                            if ($container.length > 0 && $container.find('.gform_popup_confirmation_setting').length === 0) {
                                debugLog('Adding popup setting to form container');
                                addPopupSettingToContainer($container);
                            }
                        });
                    }
                }
            });
        }, 2000);
        
    });
    
})(jQuery); 