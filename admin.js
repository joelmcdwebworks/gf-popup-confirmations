(function($) {
    'use strict';
    
    /**
     * Admin functionality for Gravity Forms Popup Confirmations
     */
    
    $(document).ready(function() {
        
        // Check if we're on a Gravity Forms confirmation settings page
        function isConfirmationSettingsPage() {
            var url = window.location.href;
            var isConfirmationPage = (
                url.indexOf('gf_edit_forms') !== -1 && 
                url.indexOf('view=settings') !== -1 && 
                url.indexOf('subview=confirmation') !== -1
            );
            
            return isConfirmationPage;
        }
        
        // Only run on confirmation settings pages
        if (!isConfirmationSettingsPage()) {
            return;
        }
        
        // Function to add popup option to confirmation settings
        function addPopupOptionToConfirmations() {
            
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
                    return false; // break forEach
                }
            });
            
            if ($radios.length === 0) {
                
                // Fallback: Look for any form settings and try to add popup option
                var $settingsContainers = $('.gform-settings-panel, .gform-settings-field, .gform-settings-section, .gform-settings-group');
                
                $settingsContainers.each(function() {
                    var $container = $(this);
                    var containerText = $container.text().toLowerCase();
                    
                    // Check if this container has confirmation-related content
                    if (containerText.indexOf('confirmation') !== -1 || 
                        containerText.indexOf('message') !== -1 ||
                        containerText.indexOf('type') !== -1) {
                        
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
                    return;
                }
                
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
            
            var popupHtml = createPopupSettingHtml(confirmationId, isMessageType);
            $container.append(popupHtml);
            
            // Show/hide based on confirmation type
            updatePopupSettingVisibility($container);
            
            // First check if popup setting is already in the current form data
            if (checkCurrentPopupSetting($container)) {
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
            
            if (confirmationId) {
                return confirmationId;
            }
            
            // Look for hidden input with confirmation ID
            var $confirmationInput = $container.find('input[name="confirmation_id"], input[name="_gform_setting_id"]');
            if ($confirmationInput.length) {
                var inputValue = $confirmationInput.val();
                return inputValue;
            }
            
            // Look for confirmation ID in the form
            var $form = $container.closest('form');
            if ($form.length) {
                var formAction = $form.attr('action') || '';
                var match = formAction.match(/confirmation_id=([^&]+)/);
                if (match) {
                    return match[1];
                }
                
                // Also check for confirmation ID in form inputs
                var $formConfirmationInput = $form.find('input[name="confirmation_id"], input[name="_gform_setting_id"]');
                if ($formConfirmationInput.length) {
                    var formInputValue = $formConfirmationInput.val();
                    return formInputValue;
                }
            }
            
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
            
            if (isMessageType) {
                $popupSetting.show();
                // Restore the checkbox state from the hidden input when switching back to message
                var $hiddenInput = $popupSetting.find('input[name="_gform_setting_displayModal"]');
                var $checkbox = $popupSetting.find('input[type="checkbox"]');
                if ($hiddenInput.length && $checkbox.length) {
                    var shouldBeChecked = $hiddenInput.val() === '1';
                    $checkbox.prop('checked', shouldBeChecked);
                    
                    // Show/hide Query String field based on restored checkbox state
                    if (shouldBeChecked) {
                        showHideQueryStringField(true);
                    }
                }
            } else {
                $popupSetting.hide();
                // Don't uncheck the popup option when switching away from message
                // Just hide it to preserve the state
            }
        }
        
        // Handle confirmation type changes
        $(document).on('change', 'input[value="message"], input[name="_gform_setting_type"], input[name="type"]', function() {
            var $container = $(this).closest('.gform-settings-panel, .gform-settings-field, .gform-settings-section');
            var isMessageType = $(this).val() === 'message';
            var previousType = $(this).data('previous-value');
            
            // Store the current value for next change
            $(this).data('previous-value', $(this).val());
            
            // If switching away from message type, preserve the checkbox state in the hidden input
            if (previousType === 'message' && !isMessageType) {
                var $popupSetting = $container.find('.gform_popup_confirmation_setting');
                var $checkbox = $popupSetting.find('input[type="checkbox"]');
                var $hiddenInput = $popupSetting.find('input[name="_gform_setting_displayModal"]');
                
                if ($checkbox.length && $hiddenInput.length) {
                    var wasChecked = $checkbox.is(':checked');
                    $hiddenInput.val(wasChecked ? '1' : '0');
                }
            }
            
            updatePopupSettingVisibility($container);
            
            // If switching to message type, check if popup is enabled and show Query String field
            if (isMessageType) {
                var $popupCheckbox = $container.find('.popup-modal-checkbox');
                if ($popupCheckbox.length && $popupCheckbox.is(':checked')) {
                    showHideQueryStringField(true);
                }
            }
        });
        
        // Handle popup checkbox changes - sync with hidden input and show/hide Query String field
        $(document).on('change', '.popup-modal-checkbox', function() {
            var $checkbox = $(this);
            var confirmationId = $checkbox.data('confirmation-id');
            var displayModal = $checkbox.is(':checked');
            
            // Update the hidden input field
            var $hiddenInput = $checkbox.siblings('input[name="_gform_setting_displayModal"]');
            if ($hiddenInput.length) {
                $hiddenInput.val(displayModal ? '1' : '0');
            }
            
            // Show/hide the Query String field based on popup checkbox state
            showHideQueryStringField(displayModal);
        });
        
        // Function to show/hide Query String field
        function showHideQueryStringField(showField) {
            
            $(".gform-settings-label").each(function() {
                if ($(this).text().trim().indexOf('Pass Field Data via Query String') !== -1) {
                    var $field = $(this).closest('.gform-settings-field');
                    if (showField) {
                        $field.show();
                    } else {
                        $field.hide();
                    }
                }
            });
        }
        
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
            
            if (formId && confirmationId) {
                var ajaxData = {
                    action: 'gf_load_popup_confirmation',
                    nonce: gfPopupConfirmations.nonce,
                    form_id: formId,
                    confirmation_id: confirmationId
                };
                
                $.ajax({
                    url: gfPopupConfirmations.ajaxUrl,
                    type: 'POST',
                    data: ajaxData,
                    success: function(response) {
                        
                        if (response.success && response.data.displayModal) {
                            var $checkbox = $container.find('.popup-modal-checkbox');
                            var $hiddenInput = $container.find('input[name="_gform_setting_displayModal"]');
                            
                            $checkbox.prop('checked', true);
                            if ($hiddenInput.length) {
                                $hiddenInput.val('1');
                            }
                            
                            // Show the Query String field since popup is enabled
                            showHideQueryStringField(true);
                            
                            // Populate the Query String field if there's existing data
                            if (response.data.queryString) {
                                $(".gform-settings-label").each(function() {
                                    if ($(this).text().trim().indexOf('Pass Field Data via Query String') !== -1) {
                                        var $field = $(this).closest('.gform-settings-field');
                                        var $input = $field.find('input[type="text"], textarea');
                                        if ($input.length) {
                                            $input.val(response.data.queryString);
                                        }
                                    }
                                });
                            }
                        }
                    },
                    error: function(xhr, status, error) {
                        
                    }
                });
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
                    var $checkbox = $container.find('.popup-modal-checkbox');
                    $checkbox.prop('checked', true);
                    return true;
                }
                
                // Check if the form action or other indicators suggest popup is enabled
                var formAction = $form.attr('action') || '';
                var formHtml = $form.html();
                
                // Look for any existing confirmation data that might indicate popup is enabled
                var $confirmationInputs = $form.find('input[name*="confirmation"], textarea[name*="confirmation"]');
                $confirmationInputs.each(function() {
                    var $input = $(this);
                    var inputName = $input.attr('name');
                    var inputValue = $input.val();
                });
            }
            
            return false;
        }
        
        // Function to save popup setting via AJAX - REMOVED
        // Popup settings will be saved with the confirmation settings form
        function savePopupSetting(formId, confirmationId, displayModal) {
        }
        
        // Initialize popup options on page load
        addPopupOptionToConfirmations();
        
        // Initialize previous values for confirmation type radio buttons
        $('input[value="message"], input[name="_gform_setting_type"], input[name="type"]').each(function() {
            $(this).data('previous-value', $(this).val());
        });
        
        // Check if popup checkbox is checked on page load and show Query String field if needed
        setTimeout(function() {
            var $popupCheckbox = $('.popup-modal-checkbox');
            if ($popupCheckbox.length && $popupCheckbox.is(':checked')) {
                showHideQueryStringField(true);
            }
        }, 500);
        
        // Handle dynamic content loading
        $(document).on('DOMNodeInserted', function(e) {
            if ($(e.target).hasClass('gform-settings-panel') || 
                $(e.target).hasClass('gform-settings-field') || 
                $(e.target).hasClass('gform-settings-section')) {
                setTimeout(function() {
                    addPopupOptionToConfirmations();
                    
                    // Initialize previous values for any new confirmation type radio buttons
                    $('input[value="message"], input[name="_gform_setting_type"], input[name="type"]').each(function() {
                        if (!$(this).data('previous-value')) {
                            $(this).data('previous-value', $(this).val());
                        }
                    });
                    
                    // Check if popup checkbox is checked and restore Query String field visibility
                    var $popupCheckbox = $('.popup-modal-checkbox');
                    if ($popupCheckbox.length && $popupCheckbox.is(':checked')) {
                        showHideQueryStringField(true);
                    }
                }, 100);
            }
        });
        
        // Also try again after a short delay to catch any late-loading content
        setTimeout(function() {
            addPopupOptionToConfirmations();
            
            // Initialize previous values for any new confirmation type radio buttons
            $('input[value="message"], input[name="_gform_setting_type"], input[name="type"]').each(function() {
                if (!$(this).data('previous-value')) {
                    $(this).data('previous-value', $(this).val());
                }
            });
            
            // Check and restore Query String field visibility
            var $popupCheckbox = $('.popup-modal-checkbox');
            if ($popupCheckbox.length && $popupCheckbox.is(':checked')) {
                showHideQueryStringField(true);
            }
        }, 1000);
        
        // Additional check: inspect the page for any existing popup settings
        setTimeout(function() {
            
            // Look for any hidden inputs or data that might indicate popup is enabled
            var $allHiddenInputs = $('input[type="hidden"]');
            $allHiddenInputs.each(function() {
                var $input = $(this);
                var name = $input.attr('name');
                var value = $input.val();
                
                if (name && (name.indexOf('displayModal') !== -1 || name.indexOf('popup') !== -1)) {
                }
            });
            
            // Also check for any JavaScript variables that might contain form data
            if (typeof window.gform !== 'undefined' && window.gform.formData) {
            }
            
            // Check for any data attributes that might contain confirmation info
            var $confirmationElements = $('[data-confirmation], [data-gf-confirmation]');
            $confirmationElements.each(function() {
                var $element = $(this);
            });
        }, 2000);
        
        // Try a more aggressive approach - look for any form with confirmation settings
        setTimeout(function() {
            
            // Look for any form that might be a confirmation settings form
            var $forms = $('form');
            
            $forms.each(function(index) {
                var $form = $(this);
                var formAction = $form.attr('action') || '';
                var formHtml = $form.html();
                
                // If this looks like a confirmation form, try to add popup option
                if (formAction.indexOf('gf_edit_forms') !== -1 || 
                    formHtml.indexOf('confirmation') !== -1) {
                    
                    var $messageFields = $form.find('input[value="message"], select option[value="message"]');
                    if ($messageFields.length > 0) {
                        $messageFields.each(function() {
                            var $field = $(this);
                            var $container = $field.closest('.gform-settings-panel, .gform-settings-field, .gform-settings-section, .gform-settings-group, .gform-settings-row');
                            
                            if ($container.length > 0 && $container.find('.gform_popup_confirmation_setting').length === 0) {
                                addPopupSettingToContainer($container);
                            }
                        });
                    }
                }
            });
            
            // Final check for Query String field visibility
            var $popupCheckbox = $('.popup-modal-checkbox');
            if ($popupCheckbox.length && $popupCheckbox.is(':checked')) {
                showHideQueryStringField(true);
            }
        }, 2000);
        
        // Add a periodic check to ensure Query String field visibility is maintained
        setInterval(function() {
            var $popupCheckbox = $('.popup-modal-checkbox');
            if ($popupCheckbox.length && $popupCheckbox.is(':checked')) {
                // Check if Query String field is hidden when it should be visible
                $(".gform-settings-label").each(function() {
                    if ($(this).text().trim().indexOf('Pass Field Data via Query String') !== -1) {
                        var $field = $(this).closest('.gform-settings-field');
                        if ($field.is(':hidden')) {
                            $field.show();
                        }
                    }
                });
            }
        }, 3000);
        
        // Move the admin warning before the #gform-settings form and show it
        var $adminWarning = $('#gf-popup-warning-admin');
        var $settingsForm = $('#gform-settings');
        if ($adminWarning.length && $settingsForm.length) {
            $adminWarning.insertBefore($settingsForm);
            $adminWarning.show();
        } else if ($adminWarning.length) {
            // Fallback: append to the main confirmation panel
            $('.gform-settings-panel, .gform-settings-field, .gform-settings-section').first().append($adminWarning);
            $adminWarning.show();
        }
        
    });
    
})(jQuery); 