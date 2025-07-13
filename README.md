# Gravity Forms Popup Confirmations

A WordPress plugin that provides popup modal functionality for Gravity Forms confirmations with enhanced accessibility and modern browser support.

## Features

- **Native HTML Dialog Support**: Uses the modern `<dialog>` element for better accessibility and reduced JavaScript complexity
- **Backward Compatibility**: Falls back to custom modal implementation for older browsers
- **Admin Interface**: Easy-to-use admin interface to enable popup confirmations without CSS classes
- **Legacy Support**: Maintains compatibility with existing CSS class method (`gf_confirmation_popup`)
- **Enhanced Accessibility**: Automatic focus management, screen reader announcements, and keyboard navigation

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

## Installation

1. Upload the plugin files to `/wp-content/plugins/gf-popup-confirmations/`
2. Activate the plugin through the 'Plugins' menu in WordPress
3. Configure popup confirmations in your Gravity Forms settings

## Usage

### Method 1: Admin Interface (Recommended)

1. Go to **Forms > Settings > Confirmations** in your Gravity Forms form
2. Select "Text" as the confirmation type
3. Check the "Display this message as a popup/modal" option
4. Save your form

### Method 2: CSS Class (Legacy)

Add the CSS class `gf_confirmation_popup` to your form's CSS Class field in the form settings.

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
- Screen reader announcements
- Native ARIA semantics

#### Legacy Modal (Older Browsers)
- Custom focus trapping
- Manual ESC key handling
- Proper ARIA attributes
- Keyboard navigation support

## Development

### File Structure

```
gf-popup-confirmations/
├── functions/
│   └── gravity-forms-popup-confirmation/
│       ├── function.php          # Main functionality
│       ├── admin.php             # Admin interface
│       ├── admin.js              # Admin JavaScript
│       ├── admin.css             # Admin styles
│       ├── script.js             # Frontend JavaScript
│       ├── style.css             # Frontend styles
│       └── js/
│           └── trapfocus.js      # Focus trapping for legacy modal
└── gf-popup-confirmations.php    # Plugin header
```

### Key Functions

- `gf_popup_confirmations_should_use_popup()`: Determines if a form should use popup confirmations
- `supportsDialog()`: Checks browser support for native dialog element
- `createNativeDialog()`: Creates and shows native dialog modal
- `createLegacyDialog()`: Creates and shows legacy div-based modal

## Changelog

### Version 2.0.0
- **NEW**: Native HTML `<dialog>` element support for modern browsers
- **IMPROVED**: Enhanced accessibility with automatic focus management
- **IMPROVED**: Reduced JavaScript complexity
- **MAINTAINED**: Full backward compatibility with existing implementations
- **MAINTAINED**: Legacy CSS class method support

### Version 1.x.x
- Initial release with CSS class-based popup confirmations
- Admin interface for popup settings
- Custom modal implementation

## Support

For support and feature requests, please visit the plugin's support page or create an issue in the repository.

## License

This plugin is licensed under the GPL v2 or later.
