<?php 
/*
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
*/

if (!function_exists('add_action')) die();

$scf_wp_vers = '4.1';
$scf_version = '20151111';
$scf_plugin  = __('Simple Basic Contact Form', 'scf');
$scf_options = get_option('scf_options');
$scf_path    = plugin_basename(__FILE__); // 'simple-basic-contact-form/simple-basic-contact-form.php';
$scf_homeurl = 'https://perishablepress.com/simple-basic-contact-form/';

// date_default_timezone_set('UTC');

// i18n
function scf_i18n_init() {
	load_plugin_textdomain('scf', false, dirname(plugin_basename(__FILE__)).'/languages/');
}
add_action('plugins_loaded', 'scf_i18n_init');

// require minimum version of WordPress
function scf_require_wp_version() {
	global $wp_version, $scf_path, $scf_plugin, $scf_wp_vers;
	if (version_compare($wp_version, $scf_wp_vers, '<')) {
		if (is_plugin_active($scf_path)) {
			deactivate_plugins($scf_path);
			$msg  = '<strong>'. $scf_plugin .'</strong> '. __('requires WordPress ', 'scf') . $scf_wp_vers . __(' or higher, and has been deactivated!', 'scf') .'<br />';
			$msg .= __('Please return to the', 'scf') .' <a href="'. admin_url() .'">'. __('WordPress Admin area', 'scf') .'</a> '. __('to upgrade WordPress and try again.', 'scf');
			wp_die($msg);
		}
	}
}
if (isset($_GET['activate']) && $_GET['activate'] == 'true') {
	add_action('admin_init', 'scf_require_wp_version');
}

// set some strings
$value_name = ''; $value_email = ''; $value_subject = ''; $value_response = ''; $value_message  = '';

if (isset($_POST['scf_name']))     $value_name     = sanitize_text_field($_POST['scf_name']);
if (isset($_POST['scf_email']))    $value_email    = sanitize_email($_POST['scf_email']);
if (isset($_POST['scf_subject']))  $value_subject  = sanitize_text_field($_POST['scf_subject']);
if (isset($_POST['scf_response'])) $value_response = sanitize_text_field($_POST['scf_response']);
if (isset($_POST['scf_message']))  $value_message  = sanitize_text_field($_POST['scf_message']);

$scf_strings = array(
	'name' 	 => '<input name="scf_name" id="scf_name" type="text" size="33" maxlength="99" value="'. $value_name .'" placeholder="' . $scf_options['scf_input_name'] . '" />', 
	'email'    => '<input name="scf_email" id="scf_email" type="text" size="33" maxlength="99" value="'. $value_email .'" placeholder="' . $scf_options['scf_input_email'] . '" />', 
	'subject'  => '<input name="scf_subject" id="scf_subject" type="text" size="33" maxlength="99" value="'. $value_subject .'" placeholder="' . $scf_options['scf_input_subject'] . '" />',
	'response' => '<input name="scf_response" id="scf_response" type="text" size="33" maxlength="99" value="'. $value_response .'" placeholder="' . $scf_options['scf_input_captcha'] . '" />',	
	'message'  => '<textarea name="scf_message" id="scf_message" cols="33" rows="7" placeholder="' . $scf_options['scf_input_message'] . '">'. $value_message .'</textarea>', 
	'error'    => ''
);

// check for bad stuff
function scf_malicious_input($input) {
	$maliciousness = false;
	$denied_inputs = array("\r", "\n", "mime-version", "content-type", "cc:", "to:");
	foreach($denied_inputs as $denied_input) {
		if(strpos(strtolower($input), strtolower($denied_input)) !== false) {
			$maliciousness = true;
			break;
		}
	}
	return $maliciousness;
}

// challenge question
function scf_spam_question($input) {
	global $scf_options;
	$casing   = $scf_options['scf_casing'];
	$response = $scf_options['scf_response'];
	$response = sanitize_text_field($response);
	if ($casing == false) return (strtoupper($input) == strtoupper($response));
	else return ($input == $response);
}

// collect ip address
function scf_get_ip_address() {
	if (isset($_SERVER)) {
		if (isset($_SERVER["HTTP_X_FORWARDED_FOR"])) {
			$ip_address = $_SERVER["HTTP_X_FORWARDED_FOR"];
		} elseif (isset($_SERVER["HTTP_CLIENT_IP"])) {
			$ip_address = $_SERVER["HTTP_CLIENT_IP"];
		} else {
			$ip_address = $_SERVER["REMOTE_ADDR"];
		}
	} else {
		if (getenv('HTTP_X_FORWARDED_FOR')) {
			$ip_address = getenv('HTTP_X_FORWARDED_FOR');
		} elseif (getenv('HTTP_CLIENT_IP')) {
			$ip_address = getenv('HTTP_CLIENT_IP');
		} else {
			$ip_address = getenv('REMOTE_ADDR');
		}
	}
	return $ip_address;
}

// filter input
function scf_input_filter() {
	global $scf_options, $scf_strings;
	$pass  = true;
	if (!isset($_POST['scf_key'])) return false;
	
	$scf_name = ''; $scf_email = ''; $scf_subject = ''; $scf_message = ''; $sfc_response = '';
	
	if (isset($_POST['scf_name']))     $scf_name     = sanitize_text_field($_POST['scf_name']);
	if (isset($_POST['scf_email']))    $scf_email    = sanitize_email($_POST['scf_email']);
	if (isset($_POST['scf_subject']))  $scf_subject  = sanitize_text_field($_POST['scf_subject']);
	if (isset($_POST['scf_message']))  $scf_message  = sanitize_text_field($_POST['scf_message']);
	if (isset($_POST['scf_response'])) $sfc_response = sanitize_text_field($_POST['scf_response']);
	
	$sfc_style         = $scf_options['scf_style'];
	$sfc_input_name    = $scf_options['scf_input_name'];
	$sfc_input_mail    = $scf_options['scf_input_email'];
	$sfc_input_subject = $scf_options['scf_input_subject'];
	$sfc_input_captcha = $scf_options['scf_input_captcha'];
	$sfc_input_message = $scf_options['scf_input_message'];
	$sfc_hide_subject  = $scf_options['scf_subject'];
	
	if (!isset($_POST['scf-nonce']) || !wp_verify_nonce($_POST['scf-nonce'], 'scf-nonce')) {
		$pass = false;
		$fail = 'nonce';
	}
	if (empty($scf_name)) {
		$pass = false;
		$fail = 'empty';
		$scf_strings['name'] = '<input class="scf_error" name="scf_name" id="scf_name" type="text" size="33" maxlength="99" value="'. $scf_name .'" '. $sfc_style .' placeholder="'. $sfc_input_name .'" />';
	}
	if (!is_email($scf_email)) {
		$pass = false; 
		$fail = 'empty';
		$scf_strings['email'] = '<input class="scf_error" name="scf_email" id="scf_email" type="text" size="33" maxlength="99" value="'. $scf_email .'" '. $sfc_style .' placeholder="'. $sfc_input_mail .'" />';
	}
	if (empty($sfc_hide_subject) && empty($scf_subject)) {
		$pass = false; 
		$fail = 'empty';
		$scf_strings['subject'] = '<input class="scf_error" name="scf_subject" id="scf_subject" type="text" size="33" maxlength="99" value="'. $scf_subject .'" '. $sfc_style .' placeholder="'. $sfc_input_subject .'" />';
	}
	if ($scf_options['scf_captcha'] == 1) {
		if (empty($sfc_response)) {
			$pass = false; 
			$fail = 'empty';
			$scf_strings['response'] = '<input class="scf_error" name="scf_response" id="scf_response" type="text" size="33" maxlength="99" value="'. $sfc_response .'" '. $sfc_style .' placeholder="'. $sfc_input_captcha .'" />';
		}
		if (!scf_spam_question($sfc_response)) {
			$pass = false;
			$fail = 'wrong';
			$scf_strings['response'] = '<input class="scf_error" name="scf_response" id="scf_response" type="text" size="33" maxlength="99" value="'. $sfc_response .'" '. $sfc_style .' placeholder="'. $sfc_input_captcha .'" />';
		}
	}
	if (empty($scf_message)) {
		$pass = false; 
		$fail = 'empty';
		$scf_strings['message'] = '<textarea class="scf_error" name="scf_message" id="scf_message" cols="33" rows="7" '. $sfc_style .' placeholder="' . $sfc_input_message .'">'. $scf_message .'</textarea>';
	}
	if (scf_malicious_input($scf_name) || scf_malicious_input($scf_email) || scf_malicious_input($scf_subject)) {
		$pass = false; 
		$fail = 'malicious';
	}
	if ($pass == true) {
		return true;
	} else {
		if ($fail == 'malicious') {
			$scf_strings['error'] = '<p class="scf_error">'. __('Please do not include any of the following in the Name, Email, or Subject fields: linebreaks, or the phrases "mime-version", "content-type", "cc:" or "to:".', 'scf') .'</p>';
		
		} elseif ($fail == 'nonce') {
			$scf_strings['error'] = '<p class="scf_error">'. __('Invalid nonce value! Please try again or contact the administrator for help.', 'scf') .'</p>';
		
		} elseif ($fail == 'empty') {
			$scf_strings['error'] = $scf_options['scf_error'];
		
		} elseif ($fail == 'wrong') {
			$scf_strings['error'] = $scf_options['scf_spam'];
		} 
		return false;
	}
}


// shortcode to display contact form
add_shortcode('simple_contact_form','scf_shortcode');
function scf_shortcode() {
	if (scf_input_filter()) {
		return scf_process_contact_form();
	} else {
		return scf_display_contact_form();
	}
}

// template tag to display contact form
function simple_contact_form() {
	if (scf_input_filter()) {
		echo scf_process_contact_form();
	} else {
		echo scf_display_contact_form();
	}
}

// simple function to sanitize text
function scf_sanitize_text($string) {
	return stripslashes(strip_tags(trim($string)));
}

// simple function to sanitize message content
function scf_sanitize_message($string) {
	return stripslashes(trim($string));
}

// process contact form
function scf_process_contact_form($content = '') {
	global $scf_options, $scf_strings;
	
	$topic     = $scf_options['scf_subject'];
	$recipient = $scf_options['scf_email'];
	$recipname = $scf_options['scf_name'];
	$recipsite = $scf_options['scf_website'];
	$success   = $scf_options['scf_success'];
	$carbon    = $scf_options['scf_carbon'];
	$offset    = $scf_options['scf_offset'];
	$prepend   = $scf_options['scf_prepend'];
	$append    = $scf_options['scf_append'];
	$styles    = $scf_options['scf_css'];
	
	$email     = isset($_POST['scf_email'])   ? sanitize_email($_POST['scf_email'])         : '';
	$name      = isset($_POST['scf_name'])    ? scf_sanitize_text($_POST['scf_name'])       : '';
	$subject   = isset($_POST['scf_subject']) ? scf_sanitize_text($_POST['scf_subject'])    : '';
	$message   = isset($_POST['scf_message']) ? scf_sanitize_message($_POST['scf_message']) : '';
	
	$agent     = isset($_SERVER['HTTP_USER_AGENT']) ? scf_sanitize_text($_SERVER['HTTP_USER_AGENT'])            : __('[ undefined ]', 'scf');
	$form      = isset($_SERVER['HTTP_REFERER'])    ? scf_sanitize_text($_SERVER['HTTP_REFERER'])               : __('[ undefined ]', 'scf');
	$host      = isset($_SERVER['REMOTE_ADDR'])     ? scf_sanitize_text(gethostbyaddr($_SERVER['REMOTE_ADDR'])) : __('[ undefined ]', 'scf');
	
	$senderip  = scf_sanitize_text(scf_get_ip_address());
	
	$date = date_i18n(get_option('date_format'), current_time('timestamp')) .' @ '. date_i18n(get_option('time_format'), current_time('timestamp'));
	
	$scf_custom = (!empty($styles)) ? '<style>' . $styles . '</style>' : '';
	
	$topic = (!empty($subject)) ? $subject : $topic;
	
	$headers  = 'X-Mailer: Simple Basic Contact Form'. "\n";
	$headers .= 'From: '. $name .' <'. $email .'>'. "\n";
	$headers .= 'Content-Type: text/plain; charset="'. get_option('blog_charset') .'"'. "\n";
	
	
	
	$fullmsg = __('Hello ', 'scf') . $recipname . ', ' . "\n\n" . 
__('You are being contacted via ', 'scf') . $recipsite . ': ' . "\n\n" . 

__('Name: ',    'scf') . $name  . "\n" . 
__('Email: ',   'scf') . $email . "\n" . 
__('Message: ', 'scf') . "\n\n" . $message . "\n\n" . 

__('-----------------------',  'scf') . "\n\n" . 
__('Additional Information: ', 'scf') . "\n\n" . 

__('Site: ',  'scf') . $recipsite . "\n" . 
__('URL: ',   'scf') . $form      . "\n" . 
__('Date: ',  'scf') . $date      . "\n" . 
__('IP: ',    'scf') . $senderip  . "\n" . 
__('Host: ',  'scf') . $host      . "\n" . 
__('Agent: ', 'scf') . $agent     . "\n\n";
	
	$fullmsg = apply_filters('scf_full_message', $fullmsg);
	
	
	
	if ($scf_options['scf_mail_function']) {
		mail($recipient, $topic, $fullmsg, $headers);
		if ($carbon) mail($email, $topic, $fullmsg, $headers);
	} else {
		wp_mail($recipient, $topic, $fullmsg, $headers);
		if ($carbon) wp_mail($email, $topic, $fullmsg, $headers);
	}
	do_action('scf_send_email', $recipient, $topic, $fullmsg, $headers);
	
	
	
	$name    = htmlentities($name, ENT_QUOTES, get_option('blog_charset', 'UTF-8'));
	$topic   = htmlentities($topic, ENT_QUOTES, get_option('blog_charset', 'UTF-8'));
	$message = htmlentities($message, ENT_QUOTES, get_option('blog_charset', 'UTF-8'));
	
	$reset_link = '<p class="scf_reset">'. __('[ ', 'scf') .'<a href="'. $form .'">'. __('Click here to reset the form', 'scf') .'</a>'. __(' ]', 'scf') .'</p></div>'. $scf_custom . $append;
	
	$short_results = $prepend .'<div id="scf_success" class="scf">'. $success .'<pre>'. __('Message: ', 'scf') . "\n\n" . $message .'</pre>'. $reset_link;
	
	$full_results = $prepend .'<div id="scf_success" class="scf">'. $success .'
<pre>'. __('Name: ', 'scf') . $name  . "\n" . 
__('Email: ',   'scf') . $email   . "\n" . 
__('Subject: ', 'scf') . $topic   . "\n" . 
__('Date: ',    'scf') . $date    . "\n" . 
__('Message: ', 'scf') . "\n\n" . $message .'</pre>'. $reset_link;
	
	$short_results = apply_filters('scf_short_results', $short_results);
	$full_results  = apply_filters('scf_full_results', $full_results);
	
	
	
	if ($scf_options['scf_success_details']) echo $full_results;
	else echo $short_results;
}



// display contact form
function scf_display_contact_form() {
	global $scf_options, $scf_strings;
	
	$question = $scf_options['scf_question'];
	$nametext = $scf_options['scf_nametext'];
	$subjtext = $scf_options['scf_subjtext'];
	$mailtext = $scf_options['scf_mailtext'];
	$messtext = $scf_options['scf_messtext'];
	$captcha  = $scf_options['scf_captcha'];
	$offset   = $scf_options['scf_offset'];
	
	if ($scf_options['scf_preform'] !== '') {
		$scf_preform = $scf_options['scf_preform'];
	} else { $scf_preform = ''; }
	
	if ($scf_options['scf_appform'] !== '') {
		$scf_appform = $scf_options['scf_appform'];
	} else { $scf_appform = ''; }
	
	if ($scf_options['scf_css'] !== '') {
		$scf_custom = '<style>' . $scf_options['scf_css'] . '</style>';
	} else { $scf_custom = ''; }
	
	if (empty($scf_options['scf_subject'])) {
		$scf_subject = '
				<fieldset class="scf-subject">
					<label for="scf_subject">'. $subjtext .'</label>
					'. $scf_strings['subject'] .'
				</fieldset>';
	} else { $scf_subject = ''; }
	
	if ($captcha == 1) {
		$captcha_box = '
				<fieldset class="scf-response">
					<label for="scf_response">'. $question .'</label>
					'. $scf_strings['response'] .'
				</fieldset>';
	} else { $captcha_box = ''; }
	
	$scf_form = ($scf_preform . $scf_strings['error'] . '
		<div id="simple-contact-form" class="scf">
			<form action="" method="post">
				<fieldset class="scf-name">
					<label for="scf_name">'. $nametext .'</label>
					'. $scf_strings['name'] .'
				</fieldset>
				<fieldset class="scf-email">
					<label for="scf_email">'. $mailtext .'</label>
					'. $scf_strings['email'] .'
				</fieldset>'. 
					$scf_subject . $captcha_box .'
				<fieldset class="scf-message">
					<label for="scf_message">'. $messtext .'</label>
					'. $scf_strings['message'] .'
				</fieldset>
				<div class="scf-submit">
					<input type="submit" name="Submit" id="scf_contact" value="' . __('Send email', 'scf') . '">
					<input type="hidden" name="scf_key" value="process">
					'. wp_nonce_field('scf-nonce', 'scf-nonce', false, false) .'
				</div>
			</form>
		</div>
		' . $scf_custom . $scf_appform);
	
	return apply_filters('scf_filter_contact_form', $scf_form);
}

// display settings link on plugin page
add_filter ('plugin_action_links', 'scf_plugin_action_links', 10, 2);
function scf_plugin_action_links($links, $file) {
	global $scf_path;
	if ($file == $scf_path) {
		$scf_links = '<a href="'. get_admin_url() .'options-general.php?page='. $scf_path .'">'. __('Settings', 'scf') .'</a>';
		array_unshift($links, $scf_links);
	}
	return $links;
}

// rate plugin link
function add_scf_links($links, $file) {
	if ($file == plugin_basename(__FILE__)) {
		$rate_url = 'http://wordpress.org/support/view/plugin-reviews/' . basename(dirname(__FILE__)) . '?rate=5#postform';
		$links[] = '<a target="_blank" href="'. $rate_url .'" title="Click here to rate and review this plugin on WordPress.org">Rate this plugin</a>';
	}
	return $links;
}
add_filter('plugin_row_meta', 'add_scf_links', 10, 2);

// delete plugin settings
function scf_delete_plugin_options() {
	delete_option('scf_options');
}
if ($scf_options['default_options'] == 1) {
	register_uninstall_hook (__FILE__, 'scf_delete_plugin_options');
}

// define default settings
register_activation_hook (__FILE__, 'scf_add_defaults');
function scf_add_defaults() {
	$user_info = get_userdata(1);
	if ($user_info == true) {
		$admin_name = $user_info->user_login;
	} else {
		$admin_name = 'Neo Smith';
	}
	$site_title = get_bloginfo('name');
	$admin_mail = get_bloginfo('admin_email');
	$tmp = get_option('scf_options');
	if(($tmp['default_options'] == '1') || (!is_array($tmp))) {
		$arr = array(
			'default_options'     => 0,
			'scf_name'            => $admin_name,
			'scf_website'         => $site_title,
			'scf_email'           => $admin_mail,
			'scf_offset'          => '0',
			'scf_subject'         => __('Message sent from your contact form.', 'scf'),
			'scf_question'        => __('1 + 1 =', 'scf'),
			'scf_response'        => __('2', 'scf'),
			'scf_casing'          => 0,
			'scf_nametext'        => __('Name (Required)', 'scf'),
			'scf_mailtext'        => __('Email (Required)', 'scf'),
			'scf_subjtext'        => __('Subject (Required)', 'scf'),
			'scf_messtext'        => __('Message (Required)', 'scf'),
			'scf_success'         => '<p class=\'scf_success\'><strong>' . __('Success!', 'scf') . '</strong> ' . __('Your message has been sent.', 'scf') . '</p>',
			'scf_error'           => '<p class=\'scf_error\'>' . __('Please complete the required fields.', 'scf') . '</p>',
			'scf_spam'            => '<p class=\'scf_spam\'>' . __('Incorrect response for challenge question. Please try again.', 'scf') . '</p>',
			'scf_style'           => 'style=\'border: 1px solid #CC0000;\'',
			'scf_prepend'         => '',
			'scf_append'          => '',
			'scf_css'             => '#simple-contact-form fieldset { width: 100%; overflow: hidden; margin: 5px 0; border: 0; } #simple-contact-form fieldset input { float: left; width: 60%; } #simple-contact-form textarea { float: left; clear: both; width: 95%; } #simple-contact-form label { float: left; clear: both; width: 30%; margin-top: 3px; line-height: 1.8; font-size: 90%; }',
			'scf_preform'         => '',
			'scf_appform'         => '<div style=\'clear:both;\'>&nbsp;</div>',
			'scf_captcha'         => 1,
			'scf_carbon'          => 1,
			'scf_input_name'      => __('Your Name', 'scf'),
			'scf_input_email'     => __('Your Email', 'scf'),
			'scf_input_subject'   => __('Email Subject', 'scf'),
			'scf_input_captcha'   => __('Correct Response', 'scf'),
			'scf_input_message'   => __('Your Message', 'scf'),
			'scf_mail_function'   => 1,
			'scf_success_details' => 1,
		);
		update_option('scf_options', $arr);
	}
}

// whitelist settings
add_action ('admin_init', 'scf_init');
function scf_init() {
	register_setting('scf_plugin_options', 'scf_options', 'scf_validate_options');
}

// sanitize and validate input
function scf_validate_options($input) {

	if (!isset($input['default_options'])) $input['default_options'] = null;
	$input['default_options'] = ($input['default_options'] == 1 ? 1 : 0);

	$input['scf_name']     = wp_filter_nohtml_kses($input['scf_name']);
	$input['scf_website']  = wp_filter_nohtml_kses($input['scf_website']);
	$input['scf_email']    = wp_filter_nohtml_kses($input['scf_email']);
	$input['scf_offset']   = wp_filter_nohtml_kses($input['scf_offset']);
	$input['scf_subject']  = wp_filter_nohtml_kses($input['scf_subject']);
	$input['scf_question'] = wp_filter_nohtml_kses($input['scf_question']);
	$input['scf_response'] = wp_filter_nohtml_kses($input['scf_response']);

	if (!isset($input['scf_casing'])) $input['scf_casing'] = null;
	$input['scf_casing'] = ($input['scf_casing'] == 1 ? 1 : 0);

	$input['scf_nametext'] = wp_filter_nohtml_kses($input['scf_nametext']);
	$input['scf_mailtext'] = wp_filter_nohtml_kses($input['scf_mailtext']);
	$input['scf_subjtext'] = wp_filter_nohtml_kses($input['scf_subjtext']);
	$input['scf_messtext'] = wp_filter_nohtml_kses($input['scf_messtext']);

	// dealing with kses
	global $allowedposttags;
	$allowed_atts = array('align'=>array(), 'class'=>array(), 'id'=>array(), 'dir'=>array(), 'lang'=>array(), 'style'=>array(), 'xml:lang'=>array(), 'src'=>array(), 'alt'=>array(), 'href'=>array(), 'title'=>array());

	$allowedposttags['strong'] = $allowed_atts;
	$allowedposttags['small'] = $allowed_atts;
	$allowedposttags['span'] = $allowed_atts;
	$allowedposttags['abbr'] = $allowed_atts;
	$allowedposttags['code'] = $allowed_atts;
	$allowedposttags['div'] = $allowed_atts;
	$allowedposttags['img'] = $allowed_atts;
	$allowedposttags['h1'] = $allowed_atts;
	$allowedposttags['h2'] = $allowed_atts;
	$allowedposttags['h3'] = $allowed_atts;
	$allowedposttags['h4'] = $allowed_atts;
	$allowedposttags['h5'] = $allowed_atts;
	$allowedposttags['ol'] = $allowed_atts;
	$allowedposttags['ul'] = $allowed_atts;
	$allowedposttags['li'] = $allowed_atts;
	$allowedposttags['em'] = $allowed_atts;
	$allowedposttags['p'] = $allowed_atts;
	$allowedposttags['a'] = $allowed_atts;

	$input['scf_success'] = wp_kses_post($input['scf_success'], $allowedposttags);
	$input['scf_error']   = wp_kses_post($input['scf_error'], $allowedposttags);
	$input['scf_spam']    = wp_kses_post($input['scf_spam'], $allowedposttags);
	$input['scf_style']   = wp_kses_post($input['scf_style'], $allowedposttags);
	$input['scf_prepend'] = wp_kses_post($input['scf_prepend'], $allowedposttags);
	$input['scf_append']  = wp_kses_post($input['scf_append'], $allowedposttags);
	$input['scf_preform'] = wp_kses_post($input['scf_preform'], $allowedposttags);
	$input['scf_appform'] = wp_kses_post($input['scf_appform'], $allowedposttags);

	$input['scf_css'] = wp_filter_nohtml_kses($input['scf_css']);

	if (!isset($input['scf_captcha'])) $input['scf_captcha'] = null;
	$input['scf_captcha'] = ($input['scf_captcha'] == 1 ? 1 : 0);

	if (!isset($input['scf_carbon'])) $input['scf_carbon'] = null;
	$input['scf_carbon'] = ($input['scf_carbon'] == 1 ? 1 : 0);

	$input['scf_input_name'] = wp_filter_nohtml_kses($input['scf_input_name']);
	$input['scf_input_email'] = wp_filter_nohtml_kses($input['scf_input_email']);
	$input['scf_input_subject'] = wp_filter_nohtml_kses($input['scf_input_subject']);
	$input['scf_input_captcha'] = wp_filter_nohtml_kses($input['scf_input_captcha']);
	$input['scf_input_message'] = wp_filter_nohtml_kses($input['scf_input_message']);

	if (!isset($input['scf_mail_function'])) $input['scf_mail_function'] = null;
	$input['scf_mail_function'] = ($input['scf_mail_function'] == 1 ? 1 : 0);

	if (!isset($input['scf_success_details'])) $input['scf_success_details'] = null;
	$input['scf_success_details'] = ($input['scf_success_details'] == 1 ? 1 : 0);

	return $input;
}

// add the options page
add_action ('admin_menu', 'scf_add_options_page');
function scf_add_options_page() {
	global $scf_plugin;
	add_options_page($scf_plugin, 'SBCF', 'manage_options', __FILE__, 'scf_render_form');
}

// create the options page
function scf_render_form() {
	global $scf_plugin, $scf_options, $scf_path, $scf_homeurl, $scf_version; ?>

	<style type="text/css">
		.mm-panel-overview { padding-left: 150px; background: url(<?php echo plugins_url(); ?>/simple-basic-contact-form/scf-logo.png) no-repeat 15px 0; }
		
		#mm-plugin-options h1 small { font-size: 60%; }
		#mm-plugin-options h2 { margin: 0; padding: 12px 0 12px 15px; font-size: 16px; cursor: pointer; }
		#mm-plugin-options h3 { margin: 20px 15px; font-size: 14px; }
		
		#mm-plugin-options p { margin-left: 15px; }
		#mm-plugin-options ul { margin: 15px 15px 25px 40px; line-height: 16px; }
		#mm-plugin-options li { margin: 8px 0; list-style-type: disc; }
		#mm-plugin-options abbr { cursor: help; border-bottom: 1px dotted #dfdfdf; }
		
		.mm-table-wrap { margin: 15px; }
		.mm-table-wrap td,
		.mm-table-wrap th { padding: 15px; vertical-align: middle; }
		.mm-item-caption { margin: 3px 0 0 3px; font-size: 11px; color: #777; line-height: 17px; }
		.mm-code { background-color: #fafae0; color: #333; font-size: 14px; }

		#setting-error-settings_updated { margin: 10px 0; }
		#setting-error-settings_updated p { margin: 5px; }
		#mm-plugin-options .button-primary { margin: 0 0 15px 15px; }

		#mm-panel-toggle { margin: 5px 0; }
		#mm-credit-info { margin-top: -5px; }
		#mm-iframe-wrap { width: 100%; height: 250px; overflow: hidden; }
		#mm-iframe-wrap iframe { width: 100%; height: 100%; overflow: hidden; margin: 0; padding: 0; }
	</style>

	<div id="mm-plugin-options" class="wrap">
		<?php screen_icon(); ?>

		<h1><?php echo $scf_plugin; ?> <small><?php echo 'v' . $scf_version; ?></small></h1>
		<div id="mm-panel-toggle"><a href="<?php get_admin_url() . 'options-general.php?page=' . $scf_path; ?>"><?php _e('Toggle all panels', 'scf'); ?></a></div>

		<form method="post" action="options.php">
			<?php $scf_options = get_option('scf_options'); settings_fields('scf_plugin_options'); ?>

			<div class="metabox-holder">
				<div class="meta-box-sortables ui-sortable">
					<div id="mm-panel-overview" class="postbox">
						<h2><?php _e('Overview', 'scf'); ?></h2>
						<div class="toggle">
							<div class="mm-panel-overview">
								<p>
									<strong><?php echo $scf_plugin; ?></strong> <?php _e('(SBCF) is a simple basic contact form for your WordPress-powered website. Automatically sends a carbon copy to the sender.', 'scf'); ?>
									<?php _e('Simply choose your options, then add the shortcode to any post or page to display the contact form. For a contact form with more options try ', 'scf'); ?> 
									<a href="https://perishablepress.com/contact-coldform/">Contact Coldform</a>.
								</p>
								<ul>
									<li><?php _e('To configure the contact form, visit the', 'scf'); ?> <a id="mm-panel-primary-link" href="#mm-panel-primary"><?php _e('Options panel', 'scf'); ?></a>.</li>
									<li><?php _e('For the shortcode and template tag, visit', 'scf'); ?> <a id="mm-panel-secondary-link" href="#mm-panel-secondary"><?php _e('Shortcodes &amp; Template Tags', 'scf'); ?></a>.</li>
									<li><?php _e('To restore default settings, visit', 'scf'); ?> <a id="mm-restore-settings-link" href="#mm-restore-settings"><?php _e('Restore Default Options', 'scf'); ?></a>.</li>
									<li>
										<?php _e('For more information check the', 'scf'); ?> <a target="_blank" href="<?php echo plugins_url('/simple-basic-contact-form/readme.txt', dirname(__FILE__)); ?>">readme.txt</a> 
										<?php _e('and', 'scf'); ?> <a target="_blank" href="<?php echo $scf_homeurl; ?>"><?php _e('SBCF Homepage', 'scf'); ?></a>.
									</li>
									<li><?php _e('If you like this plugin, please', 'scf'); ?> 
										<a target="_blank" href="http://wordpress.org/support/view/plugin-reviews/<?php echo basename(dirname(__FILE__)); ?>?rate=5#postform" title="<?php _e('Click here to rate and review this plugin on WordPress.org', 'scf'); ?>">
											<?php _e('give it a 5-star rating at the Plugin Directory', 'scf'); ?>&nbsp;&raquo;
										</a>
									</li>
								</ul>
							</div>
						</div>
					</div>
					<div id="mm-panel-primary" class="postbox">
						<h2><?php _e('Options', 'scf'); ?></h2>
						<div class="toggle<?php if (!isset($_GET["settings-updated"])) { echo ' default-hidden'; } ?>">
							<p><?php _e('Configure the contact form..', 'scf'); ?></p>
							<h3><?php _e('General options', 'scf'); ?></h3>
							<div class="mm-table-wrap">
								<table class="widefat mm-table">
									<tr>
										<th scope="row"><label class="description" for="scf_options[scf_name]"><?php _e('Your Name', 'scf'); ?></label></th>
										<td><input type="text" size="50" maxlength="200" name="scf_options[scf_name]" value="<?php echo $scf_options['scf_name']; ?>" />
										<div class="mm-item-caption"><?php _e('How would you like to be addressed in messages sent from the contact form?', 'scf'); ?></div></td>
									</tr>
									<tr>
										<th scope="row"><label class="description" for="scf_options[scf_email]"><?php _e('Your Email', 'scf'); ?></label></th>
										<td><input type="text" size="50" maxlength="200" name="scf_options[scf_email]" value="<?php echo $scf_options['scf_email']; ?>" />
										<div class="mm-item-caption"><?php _e('Where would you like to receive messages sent from the contact form?', 'scf'); ?></div></td>
									</tr>
									<tr>
										<th scope="row"><label class="description" for="scf_options[scf_website]"><?php _e('Your Site', 'scf'); ?></label></th>
										<td><input type="text" size="50" maxlength="200" name="scf_options[scf_website]" value="<?php echo $scf_options['scf_website']; ?>" />
										<div class="mm-item-caption"><?php _e('From where should the contact messages indicate they were sent?', 'scf'); ?></div></td>
									</tr>
									<tr>
										<th scope="row"><label class="description" for="scf_options[scf_subject]"><?php _e('Default Subject', 'scf'); ?></label></th>
										<td><input type="text" size="50" maxlength="200" name="scf_options[scf_subject]" value="<?php echo $scf_options['scf_subject']; ?>" />
										<div class="mm-item-caption"><?php _e('Specify any value here to hide the Subject field (or leave blank to display the Subject field).', 'scf'); ?></div></td>
									</tr>
									<tr>
										<th scope="row"><label class="description" for="scf_options[scf_captcha]"><?php _e('Enable Captcha', 'scf'); ?></label></th>
										<td><input type="checkbox" name="scf_options[scf_captcha]" value="1" <?php if (isset($scf_options['scf_captcha'])) { checked('1', $scf_options['scf_captcha']); } ?> /> 
										<?php _e('Check this box if you want to enable the captcha (challenge question/answer).', 'scf'); ?></td>
									</tr>
									<tr>
										<th scope="row"><label class="description" for="scf_options[scf_question]"><?php _e('Challenge Question', 'scf'); ?></label></th>
										<td><input type="text" size="50" maxlength="200" name="scf_options[scf_question]" value="<?php echo $scf_options['scf_question']; ?>" />
										<div class="mm-item-caption"><?php _e('What question should be answered correctly before the message is sent?', 'scf'); ?></div></td>
									</tr>
									<tr>
										<th scope="row"><label class="description" for="scf_options[scf_response]"><?php _e('Challenge Response', 'scf'); ?></label></th>
										<td><input type="text" size="50" maxlength="200" name="scf_options[scf_response]" value="<?php echo $scf_options['scf_response']; ?>" />
										<div class="mm-item-caption"><?php _e('What is the <em>only</em> correct answer to the challenge question?', 'scf'); ?></div></td>
									</tr>
									<tr>
										<th scope="row"><label class="description" for="scf_options[scf_casing]"><?php _e('Case-sensitive?', 'scf'); ?></label></th>
										<td><input type="checkbox" name="scf_options[scf_casing]" value="1" <?php if (isset($scf_options['scf_casing'])) { checked('1', $scf_options['scf_casing']); } ?> /> 
										<?php _e('Check this box if you want the challenge response to be case-sensitive.', 'scf'); ?></td>
									</tr>
									<tr>
										<th scope="row"><label class="description" for="scf_options[scf_offset]"><?php _e('Time Offset', 'scf'); ?></label></th>
										<td>
											<input type="text" size="50" maxlength="200" name="scf_options[scf_offset]" value="<?php echo $scf_options['scf_offset']; ?>" />
											<div class="mm-item-caption">
												<?php _e('Please specify any time offset here. For example, "+7" or "-7". If no offset or unsure, enter "0" (zero, default).', 'scf'); ?><br />
												<?php _e('Current time:', 'scf'); ?> <?php echo date("l, F jS, Y @ g:i a", time() + $scf_options['scf_offset']*60*60); ?>
											</div>
										</td>
									</tr>
									<tr>
										<th scope="row"><label class="description" for="scf_options[scf_carbon]"><?php _e('Enable Carbon Copies?', 'scf'); ?></label></th>
										<td><input type="checkbox" name="scf_options[scf_carbon]" value="1" <?php if (isset($scf_options['scf_carbon'])) { checked('1', $scf_options['scf_carbon']); } ?> /> 
										<?php _e('Check this box if you want to enable the automatic sending of carbon-copies to the sender.', 'scf'); ?></td>
									</tr>
									<tr>
										<th scope="row"><label class="description" for="scf_options[scf_mail_function]"><?php _e('Mail Function', 'scf'); ?></label></th>
										<td><input type="checkbox" name="scf_options[scf_mail_function]" value="1" <?php if (isset($scf_options['scf_mail_function'])) { checked('1', $scf_options['scf_mail_function']); } ?> /> 
										<?php _e('Check this box if you want to use PHP&rsquo;s mail() function instead of WP&rsquo;s wp_mail() (default).', 'scf'); ?></td>
									</tr>
									<tr>
										<th scope="row"><label class="description" for="scf_options[scf_success_details]"><?php _e('Success Message', 'scf'); ?></label></th>
										<td><input type="checkbox" name="scf_options[scf_success_details]" value="1" <?php if (isset($scf_options['scf_success_details'])) { checked('1', $scf_options['scf_success_details']); } ?> /> 
										<?php _e('Check this box to display verbose success message (default), or uncheck for brief success message.', 'scf'); ?></td>
									</tr>
								</table>
							</div>
							<h3><?php _e('Appearance', 'scf'); ?></h3>
							<div class="mm-table-wrap">
								<table class="widefat mm-table">
									<tr>
										<th scope="row"><label class="description" for="scf_options[scf_css]"><?php _e('Custom CSS styles', 'scf'); ?></label></th>
										<td><textarea class="textarea" rows="7" cols="55" name="scf_options[scf_css]"><?php echo esc_textarea($scf_options['scf_css']); ?></textarea>
										<div class="mm-item-caption"><?php _e('Add some CSS to style the contact form. Note: do not include the <code>&lt;style&gt;</code> tags.<br />
											Note: visit <a href="http://m0n.co/i" target="_blank">m0n.co/i</a> for complete list of CSS hooks.', 'scf'); ?></div></td>
									</tr>
								</table>
							</div>
							<h3><?php _e('Field Captions &amp; Placeholders', 'scf'); ?></h3>
							<div class="mm-table-wrap">
								<table class="widefat mm-table">
									<tr>
										<th scope="row"><label class="description" for="scf_options[scf_nametext]"><?php _e('Caption for Name Field', 'scf'); ?></label></th>
										<td><input type="text" size="50" maxlength="200" name="scf_options[scf_nametext]" value="<?php echo $scf_options['scf_nametext']; ?>" />
										<div class="mm-item-caption"><?php _e('This is the caption that corresponds with the Name field.', 'scf'); ?></div></td>
									</tr>
									<tr>
										<th scope="row"><label class="description" for="scf_options[scf_mailtext]"><?php _e('Caption for Email Field', 'scf'); ?></label></th>
										<td><input type="text" size="50" maxlength="200" name="scf_options[scf_mailtext]" value="<?php echo $scf_options['scf_mailtext']; ?>" />
										<div class="mm-item-caption"><?php _e('This is the caption that corresponds with the Email field.', 'scf'); ?></div></td>
									</tr>
									<tr>
										<th scope="row"><label class="description" for="scf_options[scf_subjtext]"><?php _e('Caption for Subject Field', 'scf'); ?></label></th>
										<td><input type="text" size="50" maxlength="200" name="scf_options[scf_subjtext]" value="<?php echo $scf_options['scf_subjtext']; ?>" />
										<div class="mm-item-caption"><?php _e('This is the caption that corresponds with the Subject field.', 'scf'); ?></div></td>
									</tr>
									<tr>
										<th scope="row"><label class="description" for="scf_options[scf_messtext]"><?php _e('Caption for Message Field', 'scf'); ?></label></th>
										<td><input type="text" size="50" maxlength="200" name="scf_options[scf_messtext]" value="<?php echo $scf_options['scf_messtext']; ?>" />
										<div class="mm-item-caption"><?php _e('This is the caption that corresponds with the Message field.', 'scf'); ?></div></td>
									</tr>
									<tr>
										<th scope="row"><label class="description" for="scf_options[scf_input_name]"><?php _e('Placeholder for Name Field', 'scf'); ?></label></th>
										<td><input type="text" size="50" maxlength="200" name="scf_options[scf_input_name]" value="<?php echo $scf_options['scf_input_name']; ?>" />
										<div class="mm-item-caption"><?php _e('This is the text appearing as the input placeholder for the Name field.', 'scf'); ?></div></td>
									</tr>
									<tr>
										<th scope="row"><label class="description" for="scf_options[scf_input_email]"><?php _e('Placeholder for Email Field', 'scf'); ?></label></th>
										<td><input type="text" size="50" maxlength="200" name="scf_options[scf_input_email]" value="<?php echo $scf_options['scf_input_email']; ?>" />
										<div class="mm-item-caption"><?php _e('This is the text appearing as the input placeholder for the Email field.', 'scf'); ?></div></td>
									</tr>
									<tr>
										<th scope="row"><label class="description" for="scf_options[scf_input_subject]"><?php _e('Placeholder for Subject Field', 'scf'); ?></label></th>
										<td><input type="text" size="50" maxlength="200" name="scf_options[scf_input_subject]" value="<?php echo $scf_options['scf_input_subject']; ?>" />
										<div class="mm-item-caption"><?php _e('This is the text appearing as the input placeholder for the Subject field.', 'scf'); ?></div></td>
									</tr>
									<tr>
										<th scope="row"><label class="description" for="scf_options[scf_input_captcha]"><?php _e('Placeholder for Captcha Field', 'scf'); ?></label></th>
										<td><input type="text" size="50" maxlength="200" name="scf_options[scf_input_captcha]" value="<?php echo $scf_options['scf_input_captcha']; ?>" />
										<div class="mm-item-caption"><?php _e('This is the text appearing as the input placeholder for the Captcha field.', 'scf'); ?></div></td>
									</tr>
									<tr>
										<th scope="row"><label class="description" for="scf_options[scf_input_message]"><?php _e('Placeholder for Message Field', 'scf'); ?></label></th>
										<td><input type="text" size="50" maxlength="200" name="scf_options[scf_input_message]" value="<?php echo $scf_options['scf_input_message']; ?>" />
										<div class="mm-item-caption"><?php _e('This is the text appearing as the input placeholder for the Message field.', 'scf'); ?></div></td>
									</tr>
								</table>
							</div>
							<h3><?php _e('Success &amp; error messages', 'scf'); ?></h3>
							<p><?php _e('Note: use single quotes for attributes. Example: <code>&lt;span class=\'error\'&gt;</code>', 'scf'); ?></p>
							<div class="mm-table-wrap">
								<table class="widefat mm-table">
									<tr>
										<th scope="row"><label class="description" for="scf_options[scf_success]"><?php _e('Success Message', 'scf'); ?></label></th>
										<td><input type="text" size="50" maxlength="200" name="scf_options[scf_success]" value="<?php echo $scf_options['scf_success']; ?>" />
										<div class="mm-item-caption"><?php _e('When the form is sucessfully submitted, this message will be displayed to the sender.', 'scf'); ?></div></td>
									</tr>
									<tr>
										<th scope="row"><label class="description" for="scf_options[scf_error]"><?php _e('Error Message', 'scf'); ?></label></th>
										<td><input type="text" size="50" maxlength="200" name="scf_options[scf_error]" value="<?php echo $scf_options['scf_error']; ?>" />
										<div class="mm-item-caption"><?php _e('If the user skips a required field, this message will be displayed.', 'scf'); ?></div></td>
									</tr>
									<tr>
										<th scope="row"><label class="description" for="scf_options[scf_spam]"><?php _e('Incorrect Response', 'scf'); ?></label></th>
										<td><input type="text" size="50" maxlength="200" name="scf_options[scf_spam]" value="<?php echo $scf_options['scf_spam']; ?>" />
										<div class="mm-item-caption"><?php _e('When the challenge question is answered incorrectly, this message will be displayed.', 'scf'); ?></div></td>
									</tr>
									<tr>
										<th scope="row"><label class="description" for="scf_options[scf_style]"><?php _e('Error Fields', 'scf'); ?></label></th>
										<td><input type="text" size="50" maxlength="200" name="scf_options[scf_style]" value="<?php echo $scf_options['scf_style']; ?>" />
										<div class="mm-item-caption"><?php _e('Here you may specify the default CSS for error fields, or add other attributes.', 'scf'); ?></div></td>
									</tr>
									<tr>
										<th scope="row"><label class="description" for="scf_options[scf_preform]"><?php _e('Custom content before the form', 'scf'); ?></label></th>
										<td><textarea class="textarea" rows="3" cols="55" name="scf_options[scf_preform]"><?php echo esc_textarea($scf_options['scf_preform']); ?></textarea>
										<div class="mm-item-caption"><?php _e('Add some text/markup to appear <em>before</em> the submitted contact form (optional).', 'scf'); ?></div></td>
									</tr>
									<tr>
										<th scope="row"><label class="description" for="scf_options[scf_appform]"><?php _e('Custom content after the form', 'scf'); ?></label></th>
										<td><textarea class="textarea" rows="3" cols="55" name="scf_options[scf_appform]"><?php echo esc_textarea($scf_options['scf_appform']); ?></textarea>
										<div class="mm-item-caption"><?php _e('Add some text/markup to appear <em>after</em> the submitted contact form (optional).', 'scf'); ?></div></td>
									</tr>
									<tr>
										<th scope="row"><label class="description" for="scf_options[scf_prepend]"><?php _e('Custom content before results', 'scf'); ?></label></th>
										<td><textarea class="textarea" rows="3" cols="55" name="scf_options[scf_prepend]"><?php echo esc_textarea($scf_options['scf_prepend']); ?></textarea>
										<div class="mm-item-caption"><?php _e('Add some text/markup to appear <em>before</em> the success message (optional).', 'scf'); ?></div></td>
									</tr>
									<tr>
										<th scope="row"><label class="description" for="scf_options[scf_append]"><?php _e('Custom content after results', 'scf'); ?></label></th>
										<td><textarea class="textarea" rows="3" cols="55" name="scf_options[scf_append]"><?php echo esc_textarea($scf_options['scf_append']); ?></textarea>
										<div class="mm-item-caption"><?php _e('Add some text/markup to appear <em>after</em> the success message (optional).', 'scf'); ?></div></td>
									</tr>
								</table>
							</div>
							<input type="submit" class="button-primary" value="<?php _e('Save Settings', 'scf'); ?>" />
						</div>
					</div>
					<div id="mm-panel-secondary" class="postbox">
						<h2><?php _e('Shortcodes &amp; Template Tags', 'scf'); ?></h2>
						<div class="toggle<?php if (!isset($_GET["settings-updated"])) { echo ' default-hidden'; } ?>">
							<h3><?php _e('Shortcode', 'scf'); ?></h3>
							<p><?php _e('Use this shortcode to display the contact form on a post or page:', 'scf'); ?></p>
							<p><code class="mm-code">[simple_contact_form]</code></p>
							<h3><?php _e('Template tag', 'scf'); ?></h3>
							<p><?php _e('Use this template tag to display the form anywhere in your theme template:', 'scf'); ?></p>
							<p><code class="mm-code">&lt;?php if (function_exists('simple_contact_form')) simple_contact_form(); ?&gt;</code></p>
						</div>
					</div>
					
					<div id="mm-restore-settings" class="postbox">
						<h2><?php _e('Restore Default Options', 'scf'); ?></h2>
						<div class="toggle<?php if (!isset($_GET["settings-updated"])) { echo ' default-hidden'; } ?>">
							<p>
								<input name="scf_options[default_options]" type="checkbox" value="1" id="mm_restore_defaults" <?php if (isset($scf_options['default_options'])) { checked('1', $scf_options['default_options']); } ?> /> 
								<label class="description" for="scf_options[default_options]"><?php _e('Restore default options upon plugin deactivation/reactivation.', 'scf'); ?></label>
							</p>
							<p>
								<small>
									<?php _e('<strong>Tip:</strong> leave this option unchecked to remember your settings. Or, to go ahead and restore all default options, check the box, save your settings, and then deactivate/reactivate the plugin.', 'scf'); ?>
								</small>
							</p>
							<input type="submit" class="button-primary" value="<?php _e('Save Settings', 'scf'); ?>" />
						</div>
					</div>
					<div id="mm-panel-current" class="postbox">
						<h2><?php _e('Updates &amp; Info', 'scf'); ?></h2>
						<div class="toggle">
							<div id="mm-iframe-wrap">
								<iframe src="https://perishablepress.com/current/index-scf.html"></iframe>
							</div>
						</div>
					</div>
				</div>
			</div>
			<div id="mm-credit-info">
				<a target="_blank" href="<?php echo $scf_homeurl; ?>" title="<?php echo $scf_plugin; ?> Homepage"><?php echo $scf_plugin; ?></a> <?php _e('by', 'scf'); ?> 
				<a target="_blank" href="https://twitter.com/perishable" title="Jeff Starr on Twitter">Jeff Starr</a> @ 
				<a target="_blank" href="http://monzilla.biz/" title="Obsessive Web Design &amp; Development">Monzilla Media</a>
			</div>
		</form>
	</div>
	<script type="text/javascript">
		jQuery(document).ready(function(){
			// toggle panels
			jQuery('.default-hidden').hide();
			jQuery('#mm-panel-toggle a').click(function(){
				jQuery('.toggle').slideToggle(300);
				return false;
			});
			jQuery('h2').click(function(){
				jQuery(this).next().slideToggle(300);
			});
			jQuery('#mm-panel-primary-link').click(function(){
				jQuery('.toggle').hide();
				jQuery('#mm-panel-primary .toggle').slideToggle(300);
				return true;
			});
			jQuery('#mm-panel-secondary-link').click(function(){
				jQuery('.toggle').hide();
				jQuery('#mm-panel-secondary .toggle').slideToggle(300);
				return true;
			});
			jQuery('#mm-restore-settings-link').click(function(){
				jQuery('.toggle').hide();
				jQuery('#mm-restore-settings .toggle').slideToggle(300);
				return true;
			});
			// prevent accidents
			if(!jQuery("#mm_restore_defaults").is(":checked")){
				jQuery('#mm_restore_defaults').click(function(event){
					var r = confirm("<?php _e('Are you sure you want to restore all default options? (this action cannot be undone)', 'scf'); ?>");
					if (r == true){  
						jQuery("#mm_restore_defaults").attr('checked', true);
					} else {
						jQuery("#mm_restore_defaults").attr('checked', false);
					}
				});
			}
		});
	</script>

<?php }
