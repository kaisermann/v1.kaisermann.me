=== Simple Basic Contact Form ===

Plugin Name: Simple Basic Contact Form
Plugin URI: https://perishablepress.com/simple-basic-contact-form/
Description: Delivers a clean, secure, plug-n-play contact form for WordPress.
Tags: contact, form, contact form, email, mail, captcha
Author: Jeff Starr
Author URI: http://monzilla.biz/
Donate link: http://m0n.co/donate
Contributors: specialk
Requires at least: 4.1
Tested up to: 4.4
Stable tag: trunk
Version: 20151111
Text Domain: scf
Domain Path: /languages/
License: GPL v2 or later

Simple Basic Contact Form is a clean, secure, plug-n-play contact form for WordPress.

== Description ==

[Simple Basic Contact Form](https://perishablepress.com/simple-basic-contact-form/) is a clean, secure, plug-n-play contact form for WordPress. Minimal yet flexible, SBCF delivers clean code, solid performance, and ease of use. No frills, no gimmicks, just a straight-up contact form that's easy to set up and customize.

**Overview**

* Plug-n-play: use shortcode or template tag to display the form anywhere on your site
* Sweet emails: SBCF sends descriptive, well-formatted email messages in plain-text
* Safe and secure: SBCF blocks spam and protects against malicious content
* Ultra-clean code: SBCF is lightweight, standards-compliant, semantic, valid markup
* Fully customizable: SBCF is easy to configure and style from the Settings page

**Features**

* Slick, toggling-panel Settings Page makes it easy to customize
* Style the form via the Settings Page using your own custom CSS
* Provides template tag to display SBCF anywhere in your theme
* Provides shortcode to display SBCF on any post or page
* Displays customizable confirmation message to the sender
* Customizable placeholder text for input fields
* Option to use either PHP's mail() or WP's wp_mail() (default)
* Option to display message only in the success message

**Anti-spam &amp; Security**

* Captcha: includes challenge question/answer (w/ option to disable for users)
* Firewall: secure form processing protects against bad bots and malicious input
* User-friendly: same-page error messages to help users complete required fields

**Clean Codes**

* Squeaky-clean PHP: every line like a fine wine
* Crispy-clean markup: clean source code with proper formatting, alignment and spacing
* Shiny-clean emails: delivered emails deliver descriptive, well-formatted content
* Better performance: custom CSS styles load only where the contact form is displayed

**More Features**

* Works perfectly without JavaScript.
* Option to reset default settings
* Options to customize many aspects of the form
* Options to customize success, error, and spam messages
* Option to enable and disable CSS styles
* Email message includes IP, host, agent, and other user details
* Customizable form-field captions, error messages, and success message

Plus much more! :)

Simple Basic Contact Form supports translation into any language. Current translations include:

* Dutch
* German
* Spanish

== Installation ==

**Installation**

Typical plugin install: upload, activate, and customize in the WP Admin.

1. Unzip and upload the entire directory to your "plugins" folder and activate.
2. Use the shortcode to display SBCF on any post or page, or:
3. Use the template tag to display the SBCF anywhere in your theme.
4. Visit the SBCF Settings Page to configure your options and for more information.

Usage: Visit the plugin's settings page for shortcodes, template tags, and more information.

[More info on installing WP plugins](http://codex.wordpress.org/Managing_Plugins#Installing_Plugins)

**Shortcode**

	[simple_contact_form]

**Template tag**

	<?php if (function_exists('simple_contact_form')) simple_contact_form(); ?>

**Resources**

* Visit the [SBCF Homepage](https://perishablepress.com/simple-basic-contact-form/)
* View a [complete set of CSS hooks](https://perishablepress.com/simple-basic-contact-form/#markup-styles)

== Upgrade Notice ==

To upgrade the plugin, remove old version and replace with new version. Nothing else needs done.

== Screenshots ==

Screenshots available at the [SBCF Homepage](https://perishablepress.com/simple-basic-contact-form/#screenshots).

== Changelog ==

**20151111**

* Updated German translation (Thanks to Sven Bamberger)
* Updated heading hierarchy in plugin settings
* Refined scf_process_contact_form()
* Added scf_sanitize_text()
* Added scf_sanitize_message()
* Added scf_full_message filter hook
* Added scf_short_results filter hook
* Added scf_full_results filter hook
* Added scf_send_email action hook
* Updated translation template file
* Updated minimum version requirement
* Tested on WordPress 4.4 beta
* Optimized email headers

**20150808**

* Tested on WordPress 4.3
* Updated minimum version requirement

**20150507**

* Tested with WP 4.2 + 4.3 (alpha)
* Changed a few "http" links to "https"
* Added Dutch translation; thanks to [Martijn van Es](https://github.com/devanes)
* Bugfix: HTML attributes were being stripped from custom error messages

**20150317**

* New! added subject field to the form
* Tested with latest version of WP (4.1)
* Increased minimum version to WP 3.8
* Removed deprecated screen_icon()
* Added $scf_wp_vers for version check
* Added UTF-8 as default for WP option used in htmlentities()
* Replace sanitize_text_field() and filter_var() with sanitize_email() for email address
* Streamline/fine-tune plugin code
* Added nonce security to the form
* Localized some missing strings
* Added Reply-To and Return-Path to email headers
* Added Text Domain and Domain Path to file header
* Replaced default .mo/.po templates with .pot template

**20140925**

* Tested on latest version of WordPress (4.0)
* Increased min-required version to WP 3.7
* Added conditional check to min-version function
* Reorganized the plugin settings page
* Added .scf class to both form div and success div
* Added scf_filter_contact_form filter to form output
* Fixed case-sensitivity bug for challenge question
* Replaced 'UTF-8' with get_option('blog_charset') in scf_process_contact_form()
* Replaced stripslashes(), htmlentities(), filter_var() with sanitize_text_field()
* Fixed weird character issue and backslash issue (related)
* Applied i18n to email content and success message
* Generated new mo/po translation files

**20140305**

* Added default templates for translation/localization
* Added language support for Spanish
* Changed default option for Time Offset

**20140123**

* Tested with latest WordPress (3.8)
* Added trailing slash to load_plugin_textdomain()
* Fixed 3 incorrect _e() tags in core file
* Localized default options

**20131107**

* Renamed `add_plugin_links` to `add_scf_links`
* Revised "Welcome" panel in plugin settings

**20131106**

* Added option to hide extra infos displayed in the success message
* Fixed logic for using `mail()` vs `wp_mail()`
* Removed "&Delta;" from `die()` for better security
* Added i18n/localization support
* Added "rate this plugin" links
* Added uninstall.php file
* Added parameters to `htmlentities` (fixes weird characters issue)
* Replaced `get_permalink()` with empty value in the form
* Changed `$date` to use WordPress settings and format
* Added German translation; thanks to [Benedikt Quirmbach](http://www.LivingDocs.de)
* Fixed character encoding via `filter_var` and `html_entity_decode` in `scf_process_contact_form()`
* Tested on latest version of WordPress (3.7)
* General code cleanup and maintenance

**Version 20130725**

* Tightened form security
* Tightened plugin security

**Version 20130712**

* Fix time offset setting
* Defined UTC as default time
* Improved localization support
* Replaced some deprecated functions
* Added options to customize placeholder text for form inputs
* Added option to use either PHP's mail() or WP's wp_mail() (default)
* Overview and Updates panels now toggled open by default
* General code check n clean

**Version 20130104**

* "Send email" (submit) button now available for translation
* Added option to disable the Captcha (challenge question/response)
* Added option to disable the automatic carbon copy
* Added margin to submit button (now required in 3.5)
* Fixed "Undefined index" warning

**Version 20121205**

* Now hides ugly fieldset borders by default
* Errors now include placeholder attributes
* Anti-spam placeholder now displays challenge question
* Removed blank line from successful message results
* You can now use markup in custom prepend/append content
* Custom CSS now loads on successful result output
* Wrapped successful result output with div #scf_success
* Segregated custom content for form and success results
* Cleaned up some code formatting
* Moved .clear div to optional custom content
* Added link to SBCF CSS Hooks in Appearance options
* Fixed the plugin's built-in time offset

**Version 20121103**

Initial release.

== Frequently Asked Questions ==

Note: for a contact form with more options and features, check out [Contact Coldform](https://perishablepress.com/contact-coldform/).

To ask a question, visit the [SBCF Homepage](https://perishablepress.com/simple-basic-contact-form/) or [contact me](https://perishablepress.com/contact/).

== Support development of this plugin ==

I develop and maintain this free plugin with love for the WordPress community. To show support, you can [make a donation](http://m0n.co/donate) or purchase one of my books: 

* [The Tao of WordPress](https://wp-tao.com/)
* [Digging into WordPress](https://digwp.com/)
* [.htaccess made easy](https://htaccessbook.com/)
* [WordPress Themes In Depth](https://wp-tao.com/wordpress-themes-book/)

And/or purchase one of my premium WordPress plugins:

* [BBQ Pro](https://plugin-planet.com/bbq-pro/) - Pro version of Block Bad Queries
* [SES Pro](https://plugin-planet.com/ses-pro/) - Super-simple &amp; flexible email signup forms
* [USP Pro](https://plugin-planet.com/usp-pro/) - Pro version of User Submitted Posts

Links, tweets and likes also appreciated. Thanks! :)

