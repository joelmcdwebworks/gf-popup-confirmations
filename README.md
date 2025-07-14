# Gravity Forms Popup Confirmations

A WordPress plugin that provides popup modal functionality for Gravity Forms confirmations with enhanced accessibility and modern browser support.

## Introduction

If you're like me, you LOVE Gravity Forms and the fun and effective things you can do with it. I created this plugin to add functionality requested by a client: displaying form confirmations in a modal, in addition to the text/message, page, or redirect options that Gravity Forms provides out of the box. In 2021, I made the plugin that I developed following that request available for anyone to download at [https://mcdwebworks.com/plugins/gravity-forms-popup-confirmations/](https://mcdwebworks.com/plugins/gravity-forms-popup-confirmations/). Since then, the plugin has been downloaded over 100 times... which is pretty cool for a plugin developed by a virtual unknown and hosted on his own site. I've also used this plugin on a majority of the sites I've worked on for clients... in addition to my own site.

The new version includes the addition of fields to the Gravity Forms admin UI when creating/editing form confirmations and improvements to the accessibility of the popup generated.

## Installation & Usage

1. Get the latest version from [https://mcdwebworks.com/plugins/gravity-forms-popup-confirmations/](https://mcdwebworks.com/plugins/gravity-forms-popup-confirmations/)
2. Install and activate the plugin.
3. When setting up a text confirmation, check the box to enable the confirmation to display in a popup. This will have your text display in a popup while reloading the form.
4. Optionally, add URL parameters to be included in the URL when the form is reloaded.

Backward Compatibility: The previous versions of this plugin required the addition of the class "gf_confirmation_popup" to the form settings. This is no longer a requirement. The plugin still works using this method though, but you may not be able to use conditional confirmations. I recommend updating your forms to remove this class and use the checkbox as described above to take advantage of features like conditional confirmations and adding URL parameters to the confirmation.

## Features

- **Native HTML Dialog Support**: Uses the modern `<dialog>` element for better accessibility and reduced JavaScript complexity
- **Backward Compatibility**: Falls back to custom modal implementation for older browsers
- **Admin Interface**: Easy-to-use admin interface to enable popup confirmations without CSS classes
- **Legacy Support**: Maintains compatibility with existing CSS class method (`gf_confirmation_popup`)
- **Enhanced Accessibility**: Automatic focus management, screen reader announcements, and keyboard navigation
- **XSS Protection**: Built-in message sanitization to prevent cross-site scripting attacks
- **URL Parameter Support**: Handles query string parameters from confirmation settings and legacy CSS classes

## Browser Support

### Native Dialog Element Support
- **Chrome**: 37+ (2014)
- **Firefox**: 98+ (2022)
- **Safari**: 15.4+ (2022)
- **Edge**: 79+ (2020)

### Fallback Support
For older browsers that don't support the native `<dialog>` element, the plugin automatically falls back to a custom modal implementation with:
- Custom focus trapping
- Manual ESC key handling
- Overlay-based positioning


## Usage in Detail

### Method 1: Admin Interface (Recommended)

1. Go to **Forms > Settings > Confirmations** in your Gravity Forms form
2. Select "Text" as the confirmation type
3. Check the "Display this message as a popup/modal" option
4. Save your form

### Method 2: CSS Class (Legacy)

Add the CSS class `gf_confirmation_popup` to your form's CSS Class field in the form settings.

### URL Parameters

The plugin supports URL parameters in confirmation messages:

#### Admin Interface Method
Add query string parameters in the confirmation settings using merge tags:
```
param1={Entry ID}&param2={Field:1}
```

#### Legacy CSS Class Method
Use CSS classes with the format `urlparam-{key}-{value}`:
```
gf_confirmation_popup urlparam-param1-{Entry ID} urlparam-param2-{Field:1}
```

## Technical Implementation

### Native Dialog Implementation

For modern browsers, the plugin uses the HTML `<dialog>` element:

```javascript
// Browser detection
function supportsDialog() {
    return typeof HTMLDialogElement !== 'undefined' && 
           typeof HTMLDialogElement.prototype.showModal === 'function';
}

// Native dialog creation
if (supportsDialog()) {
    createNativeDialog(message);
} else {
    createLegacyDialog(message);
}
```

### Accessibility Features

#### Native Dialog (Modern Browsers)
- Automatic focus management
- Built-in ESC key handling
- Screen reader announcements via ARIA live regions
- Native ARIA semantics
- Keyboard navigation with Tab trapping

#### Legacy Modal (Older Browsers)
- Custom focus trapping using trapfocus.js
- Manual ESC key handling
- Proper ARIA attributes (`aria-modal`, `role="dialog"`)
- Keyboard navigation support
- Overlay click to close

### Security Features

- **XSS Protection**: Message sanitization removes script tags and event handlers
- **Nonce Verification**: Admin AJAX requests are protected with WordPress nonces
- **Input Sanitization**: All user inputs are properly sanitized
- **Capability Checks**: Admin functions check for appropriate user permissions

## Development

### File Structure

```
gf-popup-confirmations/
├── gf-popup-confirmations.php    # Plugin header and initialization
├── function.php                  # Main functionality and hooks
├── admin.php                     # Admin interface functionality
├── admin.js                      # Admin JavaScript for UI interactions
├── admin.css                     # Admin styles
├── script.js                     # Frontend JavaScript (dialog/modal logic)
├── style.css                     # Frontend styles
├── js/
│   └── trapfocus.js              # Focus trapping for legacy modal
├── assets/
│   └── close-md-svgrepo-com.svg  # Close button icon
├── plugin-update-checker/        # GitHub update checker
└── languages/                    # Translation files
```

### Key Functions

#### PHP Functions
- `gf_popup_confirmations_should_use_popup()`: Determines if a form should use popup confirmations
- `gf_popup_confirmations_confirmation_should_popup()`: Checks if a specific confirmation should display as popup
- `gf_popup_confirmations_get_selected_confirmation()`: Gets the actual confirmation object
- `redirect_with_confirmation()`: Handles confirmation redirection with URL parameters

#### JavaScript Functions
- `supportsDialog()`: Checks browser support for native dialog element
- `createNativeDialog()`: Creates and shows native dialog modal
- `createLegacyDialog()`: Creates and shows legacy div-based modal
- `sanitizeMessage()`: Sanitizes message content to prevent XSS
- `closeNativeDialog()` / `closeLegacyDialog()`: Close modal functions

### Hooks and Filters

- `gform_confirmation`: Filters confirmation output to redirect with popup
- `gform_enqueue_scripts`: Enqueues scripts and styles for forms with popup confirmations
- `gform_form_args`: Disables AJAX for forms with popup confirmations
- `gform_after_save_form`: Saves popup confirmation settings
- `wp_ajax_gf_load_popup_confirmation`: AJAX handler for loading popup settings

## Changelog

### Version 2.0.0
- **NEW**: Native HTML `<dialog>` element support for modern browsers
- **NEW**: Admin interface for popup confirmation settings
- **IMPROVED**: Enhanced accessibility with automatic focus management
- **IMPROVED**: XSS protection with message sanitization
- **IMPROVED**: URL parameter support for both admin and legacy methods
- **IMPROVED**: Reduced JavaScript complexity
- **MAINTAINED**: Full backward compatibility with existing implementations
- **MAINTAINED**: Legacy CSS class method support

### Version 1.x.x
- Initial release with CSS class-based popup confirmations
- Custom modal implementation

## Support

For support and feature requests, please create an issue on [Github](https://github.com/joelmcdwebworks/gf-popup-confirmations).

## License

This plugin is licensed under the GPL v2 or later.
