# Gravity Forms Popup Notifications
Adds the ability for form submission confirmations to be displayed on modal popups for Gravity Forms.

**Warning** This plugin is in development. The developer makes no garauntees about functionality or compatibility. The developer assumes no responsibility for its use. Please use at your own risk.

## Overview

[![Gravity Forms Popup Confirmations Plugin Overview](https://img.youtube.com/vi/weQ6UwUsfZ4/0.jpg)](https://www.youtube.com/watch?v=weQ6UwUsfZ4 "Gravity Forms Popup Confirmations Plugin Overview")

The image above is a link to a video overview and demo of this plugin. I recommend opening in a new tab or window.

This plugin allows Gravity Form confirmations to be displayed in a modal popup.

## Installation

1. [Download Plugin using this link](https://mcdwebworks.com/plugins/gravity-forms-popup-confirmations/) or by cliking then selecting *Download ZIP*.

2. In your WordPress dashboard, click *Add New* and then *Upload Plugin*.

3. Find the downloaded zip file for the plugin, and then click *Open*.

## Use

1. In the confirmation settings for your form, select *Text* as the confirmation type for the default confirmation or any other confirmation you may need for the form.

2. In the form settings for your form, add *gf_confirmation_popup* to the CSS class for your form.

3. Test your form.

4. Optional: Use CSS to style the popup and buttons.

## Adding URL Parameters

You may find yourself wanting to pass URL parameters along with the confirmation. Because we're not using the standard Gravity Forms redirect settings, a way to add URL parameters via the form's CSS has been added. For example, to add the URL parameter "success=1", add the following CSS class to the form's settings, "urlparam-success-1".

## Updates

Plugin updates will be automatically available as stable releases are published on Github.
