<?php
/*
Plugin Name: Hide My WP
Plugin URI: http://hide-my-wp.wpwave.com/
Description: An excellent security plugin packed with some of the coolest and most unique features in the community.
Author: AUB Media
Author URI: http://wpwave.com
Version: 5.01
Text Domain: hide_my_wp
Domain Path: /lang
License: GPL2
License URI: http://www.gnu.org/licenses/gpl-2.0.html
Network: True
*/

//todo: better child themes

/**
 *   ++ Credits ++
 *   Copyright 2015 Hassan Jahangiri
 *   Some code from dxplugin base by mpeshev, plugin base v2 by Brad Vincent, weDevs Settings API by Tareq Hasan, rootstheme by Ben Word and Minify by Stephen Clay and Mute Scemer by ampt
 */
 
 
define( 'HMW_TITLE', 'Hide My WP');
define( 'HMW_VERSION', '5.01' );
define( 'HMW_SLUG', 'hide_my_wp'); //use _
define( 'HMW_PATH', dirname( __FILE__ ) );
define( 'HMW_DIR', basename( HMW_PATH ));
define( 'HMW_URL', plugins_url() . '/' . HMW_DIR );
define( 'HMW_FILE', plugin_basename( __FILE__ ) );


if (is_ssl()){
    define( 'HMW_WP_CONTENT_URL', str_replace ('http:','https:', WP_CONTENT_URL) );
    define( 'HMW_WP_PLUGIN_URL', str_replace ('http:','https:', WP_PLUGIN_URL) );
}else {
    define( 'HMW_WP_CONTENT_URL', WP_CONTENT_URL );
    define( 'HMW_WP_PLUGIN_URL',  WP_PLUGIN_URL );
}

class HideMyWP {
    const title = HMW_TITLE;
    const ver = HMW_VERSION;
    const slug = HMW_SLUG;
    const path = HMW_PATH;
    const dir = HMW_DIR;
    const url= HMW_URL;
    const main_file= HMW_FILE;

    private $s;
    private $sub_folder;
    private $is_subdir_mu;
    private $blog_path;

    private $trust_key;
    private $short_prefix;


    private $post_replace_old=array();
    private $post_replace_new=array();

    private $post_preg_replace_new=array();
    private $post_preg_replace_old=array();

    private $partial_replace_old=array();
    private $partial_replace_new=array();

    private $top_replace_old=array();
    private $top_replace_new=array();

    private $partial_preg_replace_new=array();
    private $partial_preg_replace_old=array();

    private $replace_old=array();
    private $replace_new=array();

    private $preg_replace_old=array();
    private $preg_replace_new=array();

    private $admin_replace_old=array();
    private $admin_replace_new=array();

   /**
   * HideMyWP::__construct()
   *
   * @return
   */
   function __construct() {
        //Let's start, Bismillah!
        require_once('load.php');
    }

    /**
     * HideMyWP::bp_uri()
     * Fix buddypress pages URL when page_base is enabled
     *
     * @return
     */
    function bp_uri($uri){
        if(trim($this->opt('page_base') ,' /'))
            return str_replace(trim($this->opt('page_base') ,' /').'/','', $uri);
        else
            return $uri;
    }

    function access_cookie(){
        return preg_replace("/[^a-zA-Z]/", "", substr(SECURE_AUTH_SALT, 2, 8));
    }
    /**
     * HideMyWP::replace_admin_url()
     * Filter to replace old and new admin URL
     *
     * @return
     */
    function replace_admin_url($url, $path = '', $scheme='admin'){
        if (trim( $this->opt('new_admin_path') ,'/ ') && trim( $this->opt('new_admin_path') ,'/ ') != 'wp-admin' )
            $url = str_replace( 'wp-admin/', trim( $this->opt('new_admin_path') ,'/ ').'/', $url);
        return $url;
    }

    function netMatch($network, $ip) {
        $network=trim($network);
        $orig_network = $network;
        $ip = trim($ip);
        if ($ip == $network) {
            //echo "used network ($network) for ($ip)\n";
            return TRUE;
        }
        $network = str_replace(' ', '', $network);
        if (strpos($network, '*') !== FALSE) {
            if (strpos($network, '/') !== FALSE) {
                $asParts = explode('/', $network);
                $network = @ $asParts[0];
            }
            $nCount = substr_count($network, '*');
            $network = str_replace('*', '0', $network);
            if ($nCount == 1) {
                $network .= '/24';
            } else if ($nCount == 2) {
                $network .= '/16';
            } else if ($nCount == 3) {
                $network .= '/8';
            } else if ($nCount > 3) {
                return TRUE; // if *.*.*.*, then all, so matched
            }
        }

       // echo "from original network($orig_network), used network ($network) for ($ip)\n";

        $d = strpos($network, '-');
        if ($d === FALSE) {
            $ip_arr = explode('/', $network);
            if (!preg_match("@\d*\.\d*\.\d*\.\d*@", $ip_arr[0], $matches)) {
                $ip_arr[0] .= ".0";    // Alternate form 194.1.4/24
            }
            $network_long = ip2long($ip_arr[0]);
            if (isset($ip_arr[1])){
                $x = ip2long($ip_arr[1]);
                $mask = long2ip($x) == $ip_arr[1] ? $x : (0xffffffff << (32 - $ip_arr[1]));
                $ip_long = ip2long($ip);
                return ($ip_long & $mask) == ($network_long & $mask);
            }
        } else {
            $from = trim(ip2long(substr($network, 0, $d)));
            $to = trim(ip2long(substr($network, $d+1)));
            $ip = ip2long($ip);
            return ($ip>=$from and $ip<=$to);
        }
    }

    /**
     * HideMyWP::admin_notices()
     * Displays necessary information in admin panel
     *
     * @return
     */
    function admin_notices()
    {
        global $current_user;

        $this->h->register_messages();
        $options_file = (is_multisite()) ? 'network/settings.php' : 'options-general.php';
        $page_url = admin_url(add_query_arg('page', self::slug, $options_file));
        $show_access_message = true;

        //Update hmw_all_plugins list whenever a theme or plugin activate
        if ((isset($_GET['page']) && ($_GET['page'] == self::slug)) || isset($_GET['deactivate']) || isset($_GET['activate']) || isset($_GET['activated']) || isset($_GET['activate-multi']))
            update_option('hmw_all_plugins', array_keys(get_plugins()));

        if (isset($_GET['page']) && $_GET['page'] == self::slug && function_exists('bulletproof_security_load_plugin_textdomain')) {
            echo __('<div class="error"><p>You use BulletProof security plugin. To make it work correctly you need to configure Hide My WP manually. <a target="_blank" href="'.add_query_arg(array('die_message'=>'single')).'" class="button">'.__('Manual Configuration', self::slug).'</a>. (If you already did that ignore this message).', self::slug) . '</p></div>';
            $show_access_message = false;
        }

        if (isset($_GET['page']) && $_GET['page'] == self::slug && isset($_GET['new_admin_action']) && $_GET['new_admin_action'] == 'configured') {

            if (is_multisite()) {
                $opts = (array) get_blog_option(BLOG_ID_CURRENT_SITE, self::slug);
                $opts['new_admin_path'] = get_option('hmwp_temp_admin_path');
                update_blog_option(BLOG_ID_CURRENT_SITE, self::slug, $opts);
            } else {
                $opts = (array) get_option(self::slug);
                $opts['new_admin_path'] = get_option('hmwp_temp_admin_path');
                update_option(self::slug, $opts);
            }
            delete_option('hmwp_temp_admin_path');
            wp_redirect(add_query_arg('new_admin_action', 'redirect_to_new', $page_url));
        }

        if (isset($_GET['page']) && $_GET['page'] == self::slug && isset($_GET['new_admin_action']) && $_GET['new_admin_action'] == 'redirect_to_new') {
            //wp_logout();
            wp_redirect(wp_login_url('', true)); //true means force auth
        }

        if (isset($_GET['page']) && $_GET['page'] == self::slug && isset($_GET['new_admin_action']) && $_GET['new_admin_action'] == "abort") {
           ///update_option('hmwp_temp_admin_path', $this->opt('new_admin_path'));
            delete_option('hmwp_temp_admin_path');
            wp_redirect(add_query_arg('new_admin_action', 'aborted_msg', $page_url));
        }

        if (isset($_GET['page']) && $_GET['page'] == self::slug && isset($_GET['new_admin_action']) && $_GET['new_admin_action'] == "aborted_msg") {
            echo '<div class="error"><p>Change of admin path is cancelled!</p></div>';
        }


        if (trim(get_option('hmwp_temp_admin_path'), ' /'))
            $new_admin_path = trim(get_option('hmwp_temp_admin_path'), ' /');
        elseif (trim($this->opt('new_admin_path'), '/ '))
            $new_admin_path = trim($this->opt('new_admin_path'), '/ ');
        else
            $new_admin_path = 'wp-admin';

       //echo 'sss '.$new_admin_path;
       /* $admin_rule = '';
        if ($new_admin_path && $new_admin_path != 'wp-admin')
            $admin_rule = 'RewriteRule ^' . $new_admin_path . '/(.*) /' . $this->sub_folder . 'wp-admin/$1'.$this->trust_key.' [QSA,L]' . "\n";*/



     //   if (is_multisite() && $this->is_subdir_mu)
     //       $admin_rule = 'RewriteRule ^([_0-9a-zA-Z-]+/)?' . $new_admin_path . '/(.*) /' . $this->sub_folder . 'wp-admin/$1 [QSA,L]' . "\n";


        //$multi_site_rule = '';
       // if (true || is_multisite())
         //   $multi_site_rule = "1) If you enabled multi-site or manually configured your server (Nginx, IIS) you MUST RE-CONFIGURE it now. If HMWP works automatically just go to next step.";
//echo $current_cookie . ' sss '.$new_admin_path;

        if ($this->admin_current_cookie() != $new_admin_path && is_super_admin()){
            if (!isset($_GET['new_admin_action']) && !isset($_GET['die_message'])) {
                $page_url = str_replace($this->admin_current_cookie(),'wp-admin', $page_url);

                if ($new_admin_path == 'wp-admin')
                    wp_redirect(add_query_arg(array('die_message' => 'revert_admin'), $page_url));
                else
                    wp_redirect(add_query_arg(array('die_message' => 'new_admin'), $page_url));

            }

     //echo '<style>#adminmenumain,#wpadminbar{display:none;}</style>';
    // exit();
}
        //Good place to flush! We really need this.
        if (is_super_admin() && !function_exists('bulletproof_security_load_plugin_textdomain') && !$this->opt('customized_htaccess'))
            flush_rewrite_rules(true);

        if (is_multisite() && is_network_admin()) {
            global $wpdb;
            $sites = $wpdb->get_results("SELECT blog_id, domain FROM {$wpdb->blogs} WHERE archived = '0' AND spam = '0' AND deleted = '0' ORDER BY blog_id");

            //Loop through them
            foreach ($sites as $site) {
                global $wp_rewrite;
                //switch_to_blog($site->blog_id);
                delete_blog_option($site->blog_id, 'rewrite_rules');
                //$wp_rewrite->init();
                //$wp_rewrite->flush_rules();
            }

        }

        $home_path = get_home_path();
        if ((!file_exists($home_path . '.htaccess') && is_writable($home_path)) || is_writable($home_path . '.htaccess'))
            $writable = true;
        else
            $writable = false;

        if (isset($_GET['page']) && $_GET['page'] == self::slug && !$this->is_permalink()) {
            if (!is_multisite())
                echo '<div class="error"><p>' . __('Your <a href="options-permalink.php">permalink structure</a> is off. In order to get all features of this plugin please enable it.', self::slug) . '</p></div>';
            else
                echo '<div class="error"><p>' . __('Please enable WP permalink structure (Settings -> Permalink ) in your sites.', self::slug) . '</p></div>';
            $show_access_message = false;
        }

        if (isset($_GET['page']) && $_GET['page'] == self::slug && (isset($_GET['settings-updated']) || isset($_GET['settings-imported'])) && is_multisite()) {
            echo '<div class="error"><p>' . __('You have enabled Multisite. It\'s require to (re)configure Hide My WP after changing settings or activating new plugin or theme. <br><br><a target="_blank" href="'.add_query_arg(array('die_message'=>'multisite')).'" class="button">'.__('Multisite Configuration', self::slug).'</a>', self::slug) . '</p></div>';
            $show_access_message = false;
        }


        if (isset($_GET['page']) && $_GET['page'] == self::slug && isset($_GET['settings-updated']) && (stristr($_SERVER['SERVER_SOFTWARE'], 'nginx') || stristr($_SERVER['SERVER_SOFTWARE'], 'wpengine'))) {
            echo '<div class="error"><p>' . __('You use Nginx web server. It\'s require to (re)configure Hide My WP  after changing settings or activating new plugin or theme. <br><br><a target="_blank" href="'.add_query_arg(array('die_message'=>'nginx')).'" class="button">'.__('Nginx Configuration', self::slug).'</a>', self::slug) . '</p></div>';
            $show_access_message = false;
        }

        if (isset($_GET['page']) && $_GET['page'] == self::slug && stristr($_SERVER['SERVER_SOFTWARE'], 'iis') || stristr($_SERVER['SERVER_SOFTWARE'], 'Windows')){
            echo '<div class="error"><p>' . __('You use Windows (IIS) web server. It\'s require to (re)configure Hide My WP after changing settings or activating new plugin or theme. <br><br><a target="_blank" href="'.add_query_arg(array('die_message'=>'iis')).'" class="button">'.__('IIS Configuration', self::slug).'</a>', self::slug) . '</p></div>';
            $show_access_message = false;
        }


        if (isset($_GET['page']) && $_GET['page']==self::slug && isset($_GET['undo_config']) && $_GET['undo_config'])
            echo '<div class="updated fade"><p>' . __('Previous settings have been restored!', self::slug ) . '</p></div>';

        if (isset($_GET['page']) && $_GET['page']==self::slug  && !$writable && !function_exists('bulletproof_security_load_plugin_textdomain')) {
            echo '<div class="error"><p>' . __('It seems there is no writable htaccess file in your WP directory. In order to get all features of this plugin please change permission of your htaccess file.', self::slug) . '</p></div>';
            $show_access_message=false;
        }

        if (basename($_SERVER['PHP_SELF']) == 'options-permalink.php' && $this->is_permalink() && isset($_POST['permalink_structure']))
            echo '<div class="updated"><p>' . sprintf(__('We are refreshing this page in order to implement changes. %s', self::slug ), '<a href="options-permalink.php">Manual Refresh</a>' ). '<script type="text/JavaScript"><!--  setTimeout("window.location = \'options-permalink.php\';", 5000);   --></script></p> </div>';


        if (isset($_GET['page']) && $_GET['page']=="w3tc_minify")
            echo '<div class="error"><p>' . __('In order to enable minify beside Hide My WP you need a small change in W3 Total Cache. If you already did it ignore this message. <a target="_blank" href="http://codecanyon.net/item/hide-my-wp-no-one-can-know-you-use-wordpress/4177158/faqs/17774">Read more</a>', self::slug ) . '</p></div>';

        if (isset($_GET['page']) && $_GET['page']==self::slug && (isset($_GET['settings-updated']) || isset($_GET['settings-imported'])) && $show_access_message && !$this->access_test())
            echo '<div class="error"><p>' . __('HMWP guesses it broke your site. If it doesn\'t ignore this messsage otherwise read <a href="http://codecanyon.net/item/hide-my-wp-no-one-can-know-you-use-wordpress/4177158/faqs/18136" target="_blank"><strong>this FAQ</strong></a> to solve the problem or revert settings to default.', self::slug ) . '</p></div>';

        if (isset($_GET['page']) && $_GET['page']==self::slug && (isset($_GET['settings-updated']) || isset($_GET['settings-imported'])) && (WP_CACHE  || function_exists('hyper_cache_sanitize_uri') || class_exists('WpFastestCache') || defined('QUICK_CACHE_ENABLE') || defined('CACHIFY_FILE') || defined('WP_ROCKET_VERSION') ))
            echo '<div class="updated"><p>' . __('It seems you use a caching plugin alongside Hide My WP. Good, just please make sure to flush it to see changes! (consider browser cache, too!)', self::slug ) . '</p></div>';
    }

    function access_test(){
        $response = wp_remote_get($this->partial_filter(get_stylesheet_uri()));

        if (200 !== wp_remote_retrieve_response_code( $response )
            AND 'OK' !== wp_remote_retrieve_response_message( $response )
            AND is_wp_error( $response ))
            return false;

        return true;
    }
    /**
     * HideMyWP::email_from_name()
     *
     * Change mail name
     * @return
     */
  	function email_from_name(){
		return $this->opt('email_from_name');
  	}

     /**
     * HideMyWP::email_from_address()
     *
     * Change mail address
     * @return
     */
  	function email_from_address(){
		return $this->opt('email_from_address');
  	}

    function hash($key){
        return hash('crc32', preg_replace("/[^a-zA-Z]/", "",substr(NONCE_KEY, 2, 6)) . $key);
    }

    function ecrypt($str, $key){
        //$key = "abc123 as long as you want bla bla bla";
        $result='';
        for($i=0; $i<strlen($str); $i++) {
            $char = substr($str, $i, 1);
            $keychar = substr($key, ($i % strlen($key))-1, 1);
            $char = chr(ord($char)+ord($keychar));
            $result.=$char;
        }
        return urlencode(base64_encode($result));
    }


    function decrypt($str, $key){
        $str = base64_decode(urldecode($str));
        $result = '';
        //$key = "must be same key as in encrypt";
        for($i=0; $i<strlen($str); $i++) {
            $char = substr($str, $i, 1);
            $keychar = substr($key, ($i % strlen($key))-1, 1);
            $char = chr(ord($char)-ord($keychar));
            $result.=$char;
        }
        return $result;
    }
   /**
     * HideMyWP::wp()
     *
     * Disable WP components when permalink is enabled
     * @return
     */
    function wp(){

        //delete_option('pp_important_messages_last');

        /*echo 'last raw:'.get_option('pp_important_messages_last');
        echo '<br>last timestap +70';
       // strtotime( '+70 hours', strtotime($recent_message_last)
        echo strtotime( '+70 hours', strtotime(get_option('pp_important_messages_last')));
        echo ' again str:';
echo date('Y-m-d H:i:s', strtotime( '+70 hours', strtotime(get_option('pp_important_messages_last'))));
        echo '<p>';
echo current_time('timestamp', 1) < strtotime( '+70 hours', strtotime(get_option('pp_important_messages_last')));*/


        if ((is_feed() || is_comment_feed())&& !isset($_GET['feed']) && !$this->opt('feed_enable'))
            $this->block_access();
        if (is_author() && !isset($_GET['author']) && !isset($_GET['author']) && !$this->opt('author_enable'))
            $this->block_access();
        if (is_search() && !isset($_GET['s']) && !$this->opt('search_enable'))
            $this->block_access();
        if (is_paged() && !isset($_GET['paged']) && !$this->opt('paginate_enable'))
            $this->block_access();
        if (is_page() && !isset($_GET['page_id']) && !isset($_GET['pagename']) && !$this->opt('page_enable'))
            $this->block_access();
        if (is_single() && !isset($_GET['p']) && !$this->opt('post_enable'))
            $this->block_access();
        if (is_category() && !isset($_GET['cat']) && !$this->opt('category_enable'))
            $this->block_access();
        if (is_tag() && !isset($_GET['tag']) && !$this->opt('tag_enable'))
            $this->block_access();
        if ((is_date() || is_time()) && !isset($_GET['monthnum']) && !isset($_GET['m'])  && !isset($_GET['w']) && !isset($_GET['second']) && !isset($_GET['year']) && !isset($_GET['day']) && !isset($_GET['hour']) && !isset($_GET['second']) && !isset($_GET['minute']) && !isset($_GET['calendar']) && $this->opt('disable_archive'))
            $this->block_access();

        if ((is_tax() || is_post_type_archive() || is_trackback() || is_comments_popup() || is_attachment()) && !isset($_GET['post_type']) && !isset($_GET['taxonamy']) && !isset($_GET['attachment']) && !isset($_GET['attachment_id']) && !isset($_GET['preview']) && $this->opt('disable_other_wp'))
            $this->block_access();

        if (isset($_SERVER['HTTP_USER_AGENT']) && !is_404() && !is_home() && (stristr($_SERVER['HTTP_USER_AGENT'], 'BuiltWith') || stristr($_SERVER['HTTP_USER_AGENT'], '2ip.ru')) )
            wp_redirect(home_url());

        if ($this->opt('remove_other_meta')){
            if (function_exists('header_remove'))
                header_remove('X-Powered-By'); // PHP 5.3+
            else
                header('X-Powered-By: ');
        }

    }

    function die_message(){
        //already checked to be super admin
        if (!isset($_GET['die_message']))
            return;

        $options_file = (is_multisite()) ? 'network/settings.php' : 'options-general.php';
        $page_url = admin_url(add_query_arg('page', self::slug, $options_file));

        if (trim(get_option('hmwp_temp_admin_path'), ' /'))
            $new_admin_path = trim(get_option('hmwp_temp_admin_path'), ' /');
        elseif (trim($this->opt('new_admin_path'), '/ '))
            $new_admin_path = trim($this->opt('new_admin_path'), '/ ');
        else
            $new_admin_path = 'wp-admin';

        $page_url = str_replace($this->admin_current_cookie(), 'wp-admin', $page_url);

        switch ($_GET['die_message']){
            case 'nginx':
                $title="Nginx Configuration";
                $_GET['nginx_config'] = 1;
                $content = $this->nginx_config();
                break;
            case 'single':
                $title="Manual Configuration";
                $_GET['single_config'] = 1;
                $content = $this->single_config();
                break;
            case 'multisite':
                $title="Multisite Configuration";
                $_GET['multisite_config'] = 1;
                $content = $this->multisite_config();
                break;
            case 'iis':
                $title="IIS Configuration";
                $_GET['iis_config'] = 1;
                $content = $this->iis_config();
                break;
            case 'new_admin':
                $title= "Custom Admin Path";
                $content = sprintf(__('<div class="error"><p>Do not click back or close this tab.<br> Follow these steps <strong>IMMEDIATELY</strong> to enable new admin path or <a href="'.add_query_arg(array('new_admin_action' => 'abort'), $page_url).'">Cancel</a> and try later. <br><br><strong>1) Re-configure server: (if require)</strong> <br> If you don\'t have a writable htaccess or enabled multi-site choose appropriate setup otherwise, HMWP updates your htaccess automatically and you can go to next step<br/><a target="_blank" href="'.add_query_arg(array('die_message'=>'single'),$page_url).'" class="button">'.__('Manual Configuration', self::slug).'</a> <a target="_blank" href="'.add_query_arg(array('die_message'=>'multisite'),$page_url).'" class="button">'.__('Multisite Configuration (Apache)', self::slug).'</a> <a target="_blank" href="'.add_query_arg(array('die_message'=>'nginx'),$page_url).'" class="button">'.__('Nginx Configuration', self::slug).'</a> <a target="_blank" href="'.add_query_arg(array('die_message'=>'iis'),$page_url).'" class="button">'.__('IIS Configuration', self::slug).'</a>
                 <br><br><strong> 2) <span style="color: #ee0000">Edit /wp-config.php  </span></strong><br>  Open wp-config.php using FTP and add following line somewhere before require_once(...) (if it already exist replace it with new code): <br><i><code>define("ADMIN_COOKIE_PATH",  "%1$s");</code></i><br><br>%4$s<a class="button " href="%3$s">Cancel and Use Current Admin Path</a>  <a class="button" target="_blank" href="%2$s">I Did it! (Login to New Dashboard)</a> </p></div>', self::slug), preg_replace('|https?://[^/]+|i', '', get_option('siteurl') . '/') . $new_admin_path, add_query_arg(array('new_admin_action' => 'configured'), $page_url), add_query_arg(array('new_admin_action' => 'abort'), $page_url), '')  ;
                break;
            case 'revert_admin':
                $title = "Reset Default Admin Path";
                $content =  sprintf(__('<div class="error">Do not click back or close this tab. <br>Follow these steps <strong>IMMEDIATELY</strong> to enable new admin path or <a href="'.add_query_arg(array('new_admin_action' => 'abort'), $page_url).'">Cancel</a> and try later.<p><strong><span style="color: #ee0000">Edit /wp-config.php: </span></strong><br>  Open wp-config.php using FTP and <span style="color: #ee0000"><strong>DELETE or comment (//)</strong></span> line which starts with following code: <br><code><i>define("ADMIN_COOKIE_PATH",  "...</i></code><br><br> <a class="button" href="%3$s">Cancel and Use Current Admin Path</a> <a class="button" href="%2$s" target="_blank">I Did it! (Login to Default Admin)</a></p></div>', self::slug), '', add_query_arg(array('new_admin_action' => 'configured'), $page_url), add_query_arg(array('new_admin_action' => 'abort'), $page_url));
                break;
        }
        wp_die('<h3>'.$title.'</h3>'.$content );
    }
    /**
     * HideMyWP::admin_css_js()
     *
     * Adds admin.js to options page
     * @return
     */
    function admin_css_js(){

        if (isset($_GET['page']) && $_GET['page']==self::slug){
            wp_enqueue_script( 'jquery' );
    		wp_register_script( self::slug.'_admin_js', self::url. '/js/admin.js' , array('jquery'), self::ver, false );
            wp_enqueue_script(  self::slug.'_admin_js');
	    }

       //wp_register_style( self::slug.'_admin_css', self::url. '/css/admin.css', array(), self::ver, 'all' );
	   //wp_enqueue_style( self::slug.'_admin_css' );
    }

    /**
     * HideMyWP::pp_settings_api_reset()
     * Filter after reseting Options
     * @return
     */
    function pp_settings_api_reset(){
        delete_option('hmw_all_plugins');
        delete_option('pp_important_messages');
        delete_option('trust_network_rules');
        update_option('hmwp_temp_admin_path', 'wp-admin');
        flush_rewrite_rules();

    }

    /**
     * HideMyWP::pp_settings_api_filter()
     * Filter after updateing Options
     * @param mixed $post
     * @return
     */
    function pp_settings_api_filter($post){
        global $wp_rewrite;


        update_option(self::slug.'_undo', get_option(self::slug));

        if ((isset($post[self::slug]['admin_key']) && $this->opt('admin_key')!=$post[self::slug]['admin_key']) || (isset($post[self::slug]['login_query']) && $this->opt('login_query')!=$post[self::slug]['login_query']) ) {
          $body = "Hi-\nThis is %s plugin. Here is your new WordPress login address:\nURL: %s\n\nBest Regards,\n%s";

            if (isset($post[self::slug]['login_query']) && $post[self::slug]['login_query'])
                $login_query=  $post[self::slug]['login_query'];
            else
                $login_query = 'hide_my_wp';

            $new_url= site_url('wp-login.php');
            if ($this->h->str_contains($new_url, 'wp-login.php'))
       		   $new_url = add_query_arg($login_query, $post[self::slug]['admin_key'], $new_url);

            $body = sprintf(__($body, self::slug), self::title, $new_url, self::title );
            $subject = sprintf(__('[%s] Your New WP Login!', self::slug), self::title);
            wp_mail(get_option('admin_email'), $subject, $body);
        }

        if (!trim($this->opt('new_admin_path'), ' /') || trim($this->opt('new_admin_path'),' /') == 'wp-admin')
            $current_admin_path ='wp-admin';
        else
            $current_admin_path = trim($this->opt('new_admin_path'), ' /');

        if (isset($post['import_field']) && $post['import_field']) {
            $import_field = stripslashes($post['import_field']);
            $import_field = json_decode($import_field, true);
            $new_admin_path_input = (isset($import_field['new_admin_path']) && trim($import_field['new_admin_path'], '/ ')) ? $import_field['new_admin_path'] : 'wp-admin';
        }else{
            $new_admin_path_input = (isset($post[self::slug]['new_admin_path'])) ? $post[self::slug]['new_admin_path'] : '';
        }

        if (!trim($new_admin_path_input, ' /') || trim($new_admin_path_input,' /') == 'wp-admin')
            $new_admin_path ='wp-admin';
        else
            $new_admin_path = trim($new_admin_path_input, ' /');

        if ($new_admin_path != $current_admin_path ) {
            //save temp value and return everything back whether it was enter by user or import fields
            if (isset($post['import_field']) && $post['import_field'])
                $post['import_field']= str_replace('\"new_admin_path\":\"'.$new_admin_path.'\"','\"new_admin_path\":\"'.$current_admin_path.'\"');
            else
                $post[self::slug]['new_admin_path'] = $current_admin_path;

            update_option('hmwp_temp_admin_path', $new_admin_path);
        }


        if (!is_multisite()) {
            $wp_rewrite->set_permalink_structure(trim($post[self::slug]['post_base'], ' '));
            $wp_rewrite->set_category_base(trim($post[self::slug]['category_base'], '/ '));
            $wp_rewrite->set_tag_base(trim($post[self::slug]['tag_base'], '/ '));
        }


        if (isset ($post[self::slug]['li']) && (strlen($post[self::slug]['li']) > 35 || strlen($post[self::slug]['li']) < 40))
            delete_option('pp_important_messages');

        flush_rewrite_rules();


        if (isset($post['replace_in_html1']) && $post['replace_in_html1']){
            $i=0;
            foreach ($post['replace_in_html1'] as $old) {

                //bslash done by javascript or user hisself and will be saved automatically
                $old = str_replace( array('=', "\r\n", "\n", "\r"), array('[equal]', '[new_line]', '[new_line]','[new_line]'), $old);
                $new = str_replace( array('=', "\r\n", "\n", "\r"), array('[equal]', '[new_line]', '[new_line]','[new_line]'), $post['replace_in_html2'][$i]);

                //$new = htmlentities(stripslashes($new));

                $post[self::slug]['replace_in_html'] .= $old.'='.$new."\n";
                $i++;

            }
        }

        if (isset($post['replace_urls1']) && $post['replace_urls1']){
            $i=0;
            foreach ($post['replace_urls1'] as $old) {

                $old = str_replace( array( '\\'), array( '[bslash]'), $old);
                $new = str_replace(array('\\'), array('[bslash]'),$post['replace_urls2'][$i]);
                $post[self::slug]['replace_urls'] .= $old.'=='.$new."\n";
                $i++;

                //print_r($post);exit;

            }
        }

        return $post;
    }

    /**
     * HideMyWP::add_login_key_to_action_from()
     * Add admin key to links in wp-login.php
     * @param string $url
     * @param string $path
     * @param string $scheme
     * @param int $blog_id
     * @return
     */
    function add_login_key_to_action_from($url, $path, $scheme, $blog_id ){
        if ($this->opt('login_query'))
            $login_query = $this->opt('login_query');
        else
            $login_query = 'hide_my_wp';

	  	if ($url && $this->h->str_contains($url, 'wp-login.php'))
        	if ($scheme=='login' || $scheme=='login_post' )
            	return add_query_arg($login_query, $this->opt('admin_key'), $url);

        return $url;
    }

    /**
     * HideMyWP::add_key_login_to_url()
     * Add admin key to wp-login url
     * @param mixed $url
     * @param string $redirect
     * @return
     */
    function add_key_login_to_url($url, $redirect='0'){
        if ($this->opt('login_query'))
            $login_query = $this->opt('login_query');
        else
            $login_query = 'hide_my_wp';

        if ($this->opt('admin_key'))
            $admin_key = $this->opt('admin_key');
        else
            $admin_key = '1234';

	  	if ($url && $this->h->str_contains($url, 'wp-login.php') && !$this->h->str_contains($url, $login_query) && !$this->h->str_contains($url,$admin_key ) && !$this->h->str_contains($url, 'ref_url'))
       		return add_query_arg($login_query, $this->opt('admin_key'), $url);

        return $url;
    }


    function add_key_login_to_messages($msg){
        if ($this->opt('login_query'))
            $login_query = $this->opt('login_query');
        else
            $login_query = 'hide_my_wp';

        if ($this->opt('admin_key'))
            $admin_key = $this->opt('admin_key');
        else
            $admin_key = '1234';

        if ($msg && $this->h->str_contains($msg, '/comment.php?') && !$this->h->str_contains($msg, $login_query.'='.$admin_key) )
            return str_replace('/comment.php?', '/comment.php?'.$login_query.'='.$admin_key, $msg);

        return $msg;
    }

    function correct_logout_redirect(){
        $url =  $_SERVER['PHP_SELF'];

        if ($this->opt('login_query'))
            $login_query = $this->opt('login_query');
        else
            $login_query = 'hide_my_wp';

        if ($this->h->ends_with($url, 'wp-login.php') && isset($_REQUEST['action']) && $_REQUEST['action']=='logout') {
            $redirect_to = !empty( $_REQUEST['redirect_to'] ) ? $_REQUEST['redirect_to'] : 'wp-login.php?loggedout=true&'.$login_query.'='.$this->opt('admin_key');
        	wp_safe_redirect( $redirect_to );
        	exit();
        }
    }

    /**
     * HideMyWP::ob_starter()
     *
     * @return
     */
    function ob_starter(){
        return ob_start(array(&$this, "global_html_filter")) ;
    }

    /**
     * HideMyWP::custom_404_page()
     *
     * @param mixed $templates
     * @return
     */
    function custom_404_page($templates){
        global $current_user;
        $visitor=esc_attr((is_user_logged_in()) ? $current_user->user_login : $_SERVER["REMOTE_ADDR"]);

        if (is_multisite())
            $permalink = get_blog_permalink(BLOG_ID_CURRENT_SITE, $this->opt('custom_404_page')) ;
        else
            $permalink = get_permalink($this->opt('custom_404_page'));
        //$permalink = home_url('?'.$this->opt('page_query').'='.$this->opt('custom_404_page'));
        if ($this->opt('custom_404') && $this->opt('custom_404_page'))
            wp_redirect(add_query_arg( array('by_user'=>$visitor, 'ref_url'=> urldecode($_SERVER["REQUEST_URI"])), $permalink )) ;
        else
            return $templates;

        die();

    }

    /**
     * HideMyWP::do_feed_base()
     *
     * @param boolean $for_comments
     * @return
     */
    function do_feed_base( $for_comments ) {
    	if ( $for_comments )
   		   load_template( ABSPATH . WPINC . '/feed-rss2-comments.php' );
    	else
	       load_template( ABSPATH . WPINC . '/feed-rss2.php' );
    }
    /**
     * HideMyWP::is_permalink()
     * Is permalink enabled?
     * @return
     */
    function is_permalink(){
        global $wp_rewrite;
        if (!isset($wp_rewrite) || !is_object($wp_rewrite) || !$wp_rewrite->using_permalinks())
            return false;
        return true;
    }

    /**
     * HideMyWP::block_access()
     *
     * @return
     */
    function block_access(){
        global $wp_query, $current_user;
        include_once(ABSPATH . '/wp-includes/pluggable.php');


        if (function_exists('is_user_logged_in') && is_user_logged_in())
            $visitor = $current_user->user_login;
        else
            $visitor = $_SERVER["REMOTE_ADDR"];

        $url=esc_url('http'.(empty($_SERVER['HTTPS'])?'':'s').'://'.$_SERVER['SERVER_NAME']. $_SERVER['REQUEST_URI']);
        // $wp_query->set('page_id', 2);
        // $wp_query->query($wp_query->query_vars);

        if ($this->opt('spy_notifier')) {
            $body = "Hi-\nThis is %s plugin. We guess someone is researching about your WordPress site.\n\nHere is some more details:\nVisitor: %s\nURL: %s\nUser Agent: %s\n\nBest Regards,\n%s";
            $body = sprintf(__($body, self::slug), self::title, $visitor, $url, $_SERVER['HTTP_USER_AGENT'], self::title);
            $subject = sprintf(__('[%s] Someone is mousing!', self::slug), self::title);
            wp_mail(get_option('admin_email'), $subject, $body);
        }

        status_header( 404 );
        nocache_headers();

        $headers = array('X-Pingback' => get_bloginfo('pingback_url'));
        $headers['Content-Type'] = get_option('html_type') . '; charset=' . get_option('blog_charset');
        foreach( (array) $headers as $name => $field_value )
			@header("{$name}: {$field_value}");

		//if ( isset( $headers['Last-Modified'] ) && empty( $headers['Last-Modified'] ) && function_exists( 'header_remove' ) )
		//	@header_remove( 'Last-Modified' );


        //wp-login.php wp-admin and direct .php access can not be implemented using 'wp' hook block_access can't work correctly with init hook so we use wp_remote_get to fix the problem
        if ( $this->h->str_contains($_SERVER['PHP_SELF'], '/wp-admin/') || $this->h->ends_with($_SERVER['PHP_SELF'], '.php')) {

            if ($this->opt('custom_404') && $this->opt('custom_404_page') )   {
                wp_redirect(add_query_arg( array('by_user'=>$visitor, 'ref_url'=> urldecode($_SERVER["REQUEST_URI"])), home_url( '?'.$this->opt('page_query').'=' . $this->opt('custom_404_page') ))) ;
            }else{
                $response = @wp_remote_get( home_url('/nothing_404_404'.$this->trust_key) );

                if ( ! is_wp_error($response) )
                    echo $response['body'];
                else
                    wp_redirect( home_url('/404_Not_Found')) ;
            }

        }else{
            if(get_404_template())
                require_once( get_404_template() );
            else
                require_once(get_single_template());
        }

        die();
    }

    /**
     * HideMyWP::nice_search_redirect()
     *
     * @return
     */
    function nice_search_redirect() {
        global $wp_rewrite;
        if (!isset($wp_rewrite) || !is_object($wp_rewrite) || !$wp_rewrite->using_permalinks())
            return;

        if ($this->opt('nice_search_redirect') && $this->is_permalink()){
            $search_base = $wp_rewrite->search_base;

            if (is_search() && strpos($_SERVER['REQUEST_URI'], "/{$search_base}/") === false) {
                if (isset($_GET['s']))
                    $keyword= get_query_var('s');

                if (isset($_GET[$this->opt('search_query')]))
                    $keyword= get_query_var($this->opt('search_query'));

                wp_redirect(home_url("/{$search_base}/" . urlencode($keyword)));
                exit();
            }
        }
    }


    /**
     * HideMyWP::remove_menu_class()
     *
     * @param array $classes
     * @return
     */
    function remove_menu_class($classes) {
	  	$new_classes=array();
        if (is_array($classes)) {
             foreach($classes as $class){
                if ($this->h->starts_with( $class, 'current_'))
				  $new_classes[]=$class;

             }
        }else{
            $new_classes='';
        }

        return $new_classes;

    }


    /**
     * HideMyWP::partial_filter()
     * Filter partial HTML
     * @param mixed $content
     * @return
     */
    function partial_filter($content){

        if ($this->top_replace_old)
            $content = str_replace($this->top_replace_old, $this->top_replace_new, $content);

        if ($this->partial_replace_old)
            $content = str_replace($this->partial_replace_old, $this->partial_replace_new, $content);

        if ($this->partial_preg_replace_old)
            $content = preg_replace($this->partial_preg_replace_old, $this->partial_preg_replace_new, $content);

        return $content;
    }

    /**
     * HideMyWP::reverse_partial_filter()
     * Reverse partial Replace to fix W3 TotalCache Minification
     * @param mixed $content
     * @return
     */
    function reverse_partial_filter($content){

        if ($this->top_replace_old)
            $content = str_replace($this->top_replace_new, $this->top_replace_old, $content);

        if ($this->partial_replace_old)
            $content = str_replace($this->partial_replace_new, $this->partial_replace_old, $content);

        return $content;
    }

    /**
     * HideMyWP::post_filter()
     * Filter post HTML
     * @param mixed $content
     * @return
     */
    function post_filter($content){
        if ($this->post_replace_old)
            $content = str_replace($this->post_replace_old, $this->post_replace_new, $content);

        if ($this->post_preg_replace_old)
            $content = preg_replace($this->post_preg_replace_old, $this->post_preg_replace_new, $content);

        return $content;
    }

    function replace_field($type='replace_in_html'){
        $output = '<div class="field_wrapper '.$type.'">';

        $replace_type=$this->h->replace_newline(trim($this->opt($type),' '),'|');
        $replace_lines=explode('|', $replace_type);

        if ($replace_lines) {
            foreach ($replace_lines as $line) {
                if ($type=='replace_in_html')
                    $replace_word=explode('=', $line);
                else
                    $replace_word=explode('==', $line);


                if (isset($replace_word[0]) && isset($replace_word[1])) {
                    $replace_word[0] = str_replace(array('[equal]','[bslash]','[new_line]'), array('=',"\\","\n"), $replace_word[0]);
                    $replace_word[1] = str_replace(array('[equal]','[bslash]','[new_line]',), array('=',"\\", "\n"), $replace_word[1]);

                    $remove_checked = '';
                    $replace_checked = ' checked="checked" ';
                    $remove_hidden='';
                    if (!$replace_word[1] && $type=='replace_in_html'){
                        $remove_checked = ' checked="checked" ';
                        $replace_checked = '';
                        $remove_hidden='hidden';
                    }elseif ($replace_word[1]=='nothing_404_404' && $type=='replace_urls'){
                        $remove_checked = ' checked="checked" ';
                        $replace_checked = '';
                        $remove_hidden='hidden';
                    }

                    $rand= rand(1,10000);

                    $output .=  '<div class="hmwp_field_row">';
                    $output .=  '<textarea name="'.$type.'1[]" class="first_field"/>'.  $replace_word[0].'</textarea>';

                    $output .=  '<div class="action_field">';
                    if ($type=='replace_in_html'){
                        $output .='<label><input type="radio" '.$replace_checked.' class="html_actiontype radio" value="replace" name="html_actiontype_'.$rand.'" >Replace</label>
<br>';
                        $output .= '<label><input type="radio" '.$remove_checked.' class="html_actiontype radio" value="remove" name="html_actiontype_'.$rand.'" >Remove</label>
</div>';
                    }else{
                        $output .='<label><input type="radio" '.$replace_checked.' class="url_actiontype radio" value="replace" name="urls_actiontype_'.$rand.'" >Replace</label>
<br>';
                        $output .= '<label><input type="radio" '.$remove_checked.' class="url_actiontype radio" value="remove" name="urls_actiontype_'.$rand.'" >Hide (404)</label>
</div>';
                    }

                        $output .=  '<textarea style="visibility:'.$remove_hidden.'" name="'.$type.'2[]" class="second_field"/>'.$replace_word[1].'</textarea>';

                    $output .= '<a href="javascript:void(0);" class="button hmwp_action hmwp_remove_button" title="Remove Rule"><img src="'.self::url.'/img/delete.png" width="12"/>
                  </a>
';

                    $output .=  '</div><div class="clear"></div>';


                }
            }
        }
        $output.='<style>.first_field,.second_field, .action_field{float:left;}
.action_field{padding:10px;}
.hmwp_field_row{ margin:10px 3px;}
.hmwp_action{margin: 4px !important;}
</style>';

        $output .= '<a href="javascript:void(0);" class="button hmwp_action htmwp_add_button " title="Add Rule">
                               <img src="'.self::url.'/img/add.png" width="12" />
                               Add
                          </a>';
        $output .=  '</div>';

        if ($type=='replace_in_html'){
            $output .= "<br/><span class='description'>Do not use this to change URLs<br>Use<code>[bslash]</code> for '\'<br>Base on OSes multiple lines queries may work or not so please check.'</span>";
        }else{
            $output .= "<br/><span class='description'>Use this only to change URLs. <br>Releative path base on WP directory. e.g. wp-content/plugins/woocommerce/assets/css/woocommerce.css Replace ec.css<br>Add '/' at the end of the first path to change all files at the folder.  </span>";
        }

        return $output;
    }
    /**
     * HideMyWP::global_html_filter()
     * Filter output HTML
     * @param mixed $buffer
     * @return
     */
    function global_html_filter( $buffer){
      		
        if (is_admin() && $this->admin_replace_old && !isset($_GET['die_message'])) {
            $buffer = str_replace($this->admin_replace_old, $this->admin_replace_new, $buffer);
            return $buffer;
        }
        
	    if ($this->opt('replace_in_ajax')){
            if (is_admin() && !defined('DOING_AJAX'))
                return $buffer;
        }else{
            if (is_admin())
                return $buffer;
        }

        //first minify rocket then change other URLS
        if (function_exists('rocket_minify_process'))
            $buffer = rocket_minify_process($buffer);

        if ($this->opt('remove_html_comments') && !defined('DOING_AJAX'))  {
            if ( $this->opt('remove_html_comments')=='simple')  {
                $this->preg_replace_old[]='/<!--(.*?)-->/';
                $this->preg_replace_new[]= ' ';
                $this->preg_replace_old[]="%(\n){2,}%";
                $this->preg_replace_new[]= "\n";

            }elseif ($this->opt('remove_html_comments')=='quick') {
                //comments and more than 2 space or line break will be remove. Simple & quick but not perfect!
                $this->preg_replace_old[]='!/\*.*?\*/!s';
                $this->preg_replace_new[]=' ';
                $this->preg_replace_old[]='/\n\s*\n/';
                $this->preg_replace_new[]=' ';
                $this->preg_replace_old[]='/<!--(.*?)-->/';
                $this->preg_replace_new[]= ' ';
                $this->preg_replace_old[]="%(\s){3,}%";
                $this->preg_replace_new[]= ' ';
            }elseif ( $this->opt('remove_html_comments')=='safe')  {
                require_once('lib/class.HTML-minify.php');
                $min = new Minify_HTML($buffer, array('xhtml'=>true));
                $buffer = $min->process();
            }
        }

        if ($this->top_replace_old)
            $buffer = str_replace($this->top_replace_old, $this->top_replace_new, $buffer);

        if ($this->opt('replace_in_html')){
            $replace_in_html=$this->h->replace_newline(trim($this->opt('replace_in_html'),' '),'|');
            $replace_lines=explode('|', $replace_in_html);
            if ($replace_lines) {
                foreach ($replace_lines as $line)  {
                    $replace_word=explode('=', $line);

                    if (isset($replace_word[0]) && isset($replace_word[1])) {
                        $replace_word[0]=str_replace(array('[equal]','[bslash]','[new_line]'), array('=',"\\", "\n"), $replace_word[0]);

                        $replace_word[1]=str_replace(array('[equal]','[bslash]','[new_line]'), array('=',"\\", "\n"), $replace_word[1]);

                        $this->replace_old[]=trim($replace_word[0], ' ');
                        $this->replace_new[]=trim($replace_word[1], ' ');


                    }
                }
            }
        }






        if ($this->opt('cdn_path')){

            if (trim($this->opt('new_theme_path'),' /')) {
                $this->replace_old[] = site_url(trim($this->opt('new_theme_path'), ' /'));
                $this->replace_new[] = trim($this->opt('cdn_path'), '/ ') . '/' . trim($this->opt('new_theme_path'), ' /');

            }else {
                $this->replace_old[] = site_url('wp-content/themes');
                $this->replace_new[]= trim($this->opt('cdn_path'), '/ ') . '/' . 'wp-content/themes';
            }

            if (trim($this->opt('new_plugin_path'),' /')) {
                $this->replace_old[] = site_url(trim($this->opt('new_plugin_path'), ' /'));
                $this->replace_new[] = trim($this->opt('cdn_path'), '/ ') . '/' . trim($this->opt('new_plugin_path'), ' /');

            }else {
                $this->replace_old[] = site_url('wp-content/plugins');
                $this->replace_new[]= trim($this->opt('cdn_path'), '/ ') . '/' . 'wp-content/plugins';
            }

            if (trim($this->opt('new_include_path'),' /')) {
                $this->replace_old[] = site_url(trim($this->opt('new_include_path'), ' /'));
                $this->replace_new[] = trim($this->opt('cdn_path'), '/ ') . '/' . trim($this->opt('new_include_path'), ' /');

            }else {
                $this->replace_old[] = site_url('wp-includes');
                $this->replace_new[]= trim($this->opt('cdn_path'), '/ ') . '/' . 'wp-includes';
            }


            if (trim($this->opt('new_upload_path'),' /')) {
                $this->replace_old[] = site_url(trim($this->opt('new_upload_path'), ' /'));
                $this->replace_new[] = trim($this->opt('cdn_path'), '/ ') . '/' . trim($this->opt('new_upload_path'), ' /');

            }else {
                $this->replace_old[] = site_url('wp-content/uploads');
                $this->replace_new[]= trim($this->opt('cdn_path'), '/ ') . '/' . 'wp-content/uploads';
            }
        }


        if ($this->opt('replace_mode')=='safe' && $this->partial_replace_old)
            $buffer = str_replace($this->partial_replace_old, $this->partial_replace_new, $buffer);

        if ($this->opt('replace_mode')=='safe' && $this->partial_preg_replace_old)
            $buffer = preg_replace($this->partial_preg_replace_old, $this->partial_preg_replace_new, $buffer);


        if ($this->replace_old)
            $buffer = str_replace($this->replace_old, $this->replace_new, $buffer);

        if ($this->preg_replace_old)
            $buffer = preg_replace($this->preg_replace_old, $this->preg_replace_new, $buffer);

        return $buffer;

    }
    /**
     * HideMyWP::remove_ver_scripts()
     *
     * @param string $src
     * @return
     */
    function remove_ver_scripts($src){
        if ( strpos( $src, 'ver=' ) )
            $src = remove_query_arg( 'ver', $src );
        return $src;
    }

    function spam_blocker_fake_field($fields){
        $fake ='<input type="text" name="author" data-hwm="" value="" class="f_author_hm"> <style type="text/css">.f_author_hm{display:none;}</style>';
        $fields ['author'] = str_replace('</label>', '</label>'.$fake, $fields ['author']);
        return $fields;
    }

    /**
     * HideMyWP::spam_blocker()
     * Check queries before saving comment
     * @param string $src
     * @return
     */
    function spam_blocker($post_id){

        (array) $counter = get_option('hmwp_spam_counter');

        if (!isset($counter['1']))
            $counter['1']='';

        if (!isset($counter['2']))
            $counter['2']='';

        if ($this->opt('login_query'))
            $login_query = $this->opt('login_query');
        else
            $login_query = 'hide_my_wp';

        $spam= false;
        if ($this->is_permalink() && $this->opt('replace_comments_post') && (!isset($_GET[$this->short_prefix.$login_query]) || $_GET[$this->short_prefix.$login_query]!=$this->opt('admin_key'))) {
            $counter['1']++;
            $spam = true;
        }

        if (isset($_POST['email']) && !isset($_POST['authar'])){
            $counter['2']++;
            $spam = true;
        }

        if (isset($_POST['author']) && strlen($_POST['author'])>0){
            $counter['2']++;
            $spam = true;
        }

        if ($spam){
            update_option('hmwp_spam_counter', $counter);
            die('You\re spam! Isn\'t you?');
        }

        if (isset($_POST['authar']) && $_POST['authar'])
            $_POST['author'] = $_POST['authar'];

    }


    /**
     * HideMyWP::global_css_filter()
     * Generate new style from main file
     * @return
     */
    function global_css_filter(){
        global $wp_query;

        if ($this->opt('login_query'))
            $login_query = $this->opt('login_query');
        else
            $login_query = 'hide_my_wp';

        $new_style_path=trim($this->opt('new_style_name'),' /');
        //$this->h->ends_with($_SERVER["REQUEST_URI"], 'main.css') ||   <- For multisite
        if ( (isset($wp_query->query_vars['style_wrapper']) && $wp_query->query_vars['style_wrapper'] && $this->is_permalink() ) ){


            if ($this->opt('full_hide')&& $this->opt('admin_key')) {
                if (!isset($_GET[$this->short_prefix . $login_query]) || $_GET[$this->short_prefix . $login_query] != $this->opt('admin_key'))
                    return false;

            }

            if (is_multisite() && isset($wp_query->query_vars['template_wrapper']))
                $css_file = str_replace(get_stylesheet(), $wp_query->query_vars['template_wrapper'], get_stylesheet_directory()).'/style.css';
            else
                $css_file = get_stylesheet_directory().'/style.css';


            status_header( 200 );
            //$expires = 60*60*24; // 1 day
            $expires = 60*60*24*3; //3 day
            header("Pragma: public");
            header("Cache-Control: maxage=".$expires);
            header('Expires: ' . gmdate('D, d M Y H:i:s', time()+$expires) . ' GMT');
            header('Content-type: text/css; charset=UTF-8');

            $css = file_get_contents($css_file);

            if ($this->opt('minify_new_style') )  {
                if ($this->opt('minify_new_style')=='quick' )  {
                    $to_remove=array ('%\n\r%','!/\*.*?\*/!s', '/\n\s*\n/',"%(\s){1,}%");
                    $css = preg_replace($to_remove, ' ', $css);
                }elseif ($this->opt('minify_new_style')=='safe') {
                    require_once('lib/class.CSS-minify.php');
                    $css = Minify_CSS_Compressor::process($css, array());
                }


            }

            if ($this->opt('clean_new_style') )  {
                if (strpos($css, 'alignright')===false ){  //Disable it if it uses import or so on
                    if (is_multisite()) {
                        $opts = get_blog_option(BLOG_ID_CURRENT_SITE, self::slug);
                        $opts['clean_new_style']='';
                        update_blog_option(BLOG_ID_CURRENT_SITE, self::slug, $opts);
                    }else{
                        $opts = get_option(self::slug);
                        $opts['clean_new_style']='';
                        update_option(self::slug, $opts);
                    }
                }else{
                    $old = array ('wp-caption', 'alignright', 'alignleft','alignnone', 'aligncenter');
                    $new = array ('x-caption', 'x-right', 'x-left','x-none', 'x-center');
                    $css = str_replace($old, $new, $css);
                }
			    //We replace HTML, too
            }

           // if (is_child_theme())
           //     $css = str_replace('/thematic/', '/parent/', $css);

            echo $css;

            //  if(extension_loaded('zlib'))
            //     ob_end_flush();

            exit;
        }

    }


    function redirect_canonical($req){
        print_r($req);
       // return $output;
    }
    /**
     * HideMyWP::init()
     *
     * @return
     */
    function init(){
        require_once('init.php');
    }
    /**
     * HideMyWP::remove_default_description()
     *
     * @param mixed $bloginfo
     * @return
     */
    function remove_default_description($bloginfo) {
        return ($bloginfo == 'Just another WordPress site') ? '' : $bloginfo;
    }


        /**
     * HideMyWP::body_class_filter()
     * Only store page class
     * @param mixed $bloginfo
     * @return
     */
    function body_class_filter($classes){
        $new_classes=array();
        if (is_array($classes)) {
             foreach($classes as $class){
                if ( $class=='home' || $class=='blog' || $class=='category' || $class=='tag' || $class=='rtl' || $class=='author' || $class=='archive' || $class=='single' || $class=='attachment' || $class=='search' || $class=='custom-background')
                    $new_classes[]=$class;

             }
        }else{
            $new_classes='';
        }

        return $new_classes;
    }

    /**
     * HideMyWP::post_class_filter()
     * Only store post format, post_types and sticky
     * @param mixed $bloginfo
     * @return
     */
    function post_class_filter($classes){
        $post_types=get_post_types();
        $new_classes=array();
        if (is_array($classes)) {
             foreach($classes as $class){
                if ( ($class!='format-standard' && $this->h->starts_with( $class, 'format-')) || $class=='sticky')
                    $new_classes[]=$class;
                foreach ($post_types as $post_type)
                    if ($class==$post_type)
				        $new_classes[]=$class;

             }
        }else{
            $new_classes='';
        }

        return $new_classes;
    }

    function admin_current_cookie(){
        $current_cookie = str_replace(SITECOOKIEPATH, '', ADMIN_COOKIE_PATH);

        //For non-sudomain and with pathes mu:
        if (!$current_cookie)
            $current_cookie = 'wp-admin';

        return $current_cookie;
    }
    /**
     * HideMyWP::add_rewrite_rules()
     *
     * @param mixed $wp_rewrite
     * @return
     */
    function add_rewrite_rules( $wp_rewrite )
    {
        global $wp_rewrite, $wp;

        if (is_multisite()){
	        global $current_blog;
            $sitewide_plugins = array_keys( (array) get_site_option( 'active_sitewide_plugins', array() ));
            $active_plugins= array_merge((array) get_blog_option(BLOG_ID_CURRENT_SITE, 'active_plugins'), $sitewide_plugins);
        }else{
            $active_plugins = get_option('active_plugins');
        }




        if ($this->opt('rename_plugins')=='all')
             $active_plugins = get_option('hmw_all_plugins');


        if ($this->opt('replace_urls')){
            $replace_urls=$this->h->replace_newline(trim($this->opt('replace_urls'),' '),'|');
            $replace_lines=explode('|', $replace_urls);
            if ($replace_lines) {
                foreach ($replace_lines as $line)  {

                    $replace_word = explode('==', $line);
                    if (isset($replace_word[0]) && isset($replace_word[1])) {

                        //Check whether last character is / or not to recgnize folders
                        $is_folder= false;
                        if (substr($replace_word[0], strlen($replace_word[0])-1 , strlen($replace_word[0]))=='/')
                            $is_folder= true;

                        $replace_word[0]=trim($replace_word[0], '/ ');
                        $replace_word[1]=trim($replace_word[1], '/ ');

                        $is_block= false;
                        if ($replace_word[1] == 'nothing_404_404')
                            $is_block= true;


                        if (!$is_block) {
                            $this->top_replace_old[]=$replace_word[0];
                            $this->top_replace_new[]=$replace_word[1];
                        }

                        if ($is_block){
                            //Swap words to make theme unavailable
                            $temp = $replace_word[0];
                            $replace_word[0] = $replace_word[1];
                            $replace_word[1] = $temp;
                        }

                        $replace_word[0] = str_replace(array( 'amp;', '%2F','//', '.' ), array('', '/', '/','.'), $replace_word[0]);
                        $replace_word[1] = str_replace(array('.','amp;'), array('\.',''), $replace_word[1]);

                        if ($is_folder){
                            $new_non_wp_rules[$replace_word[1].'/(.*)'] = $this->sub_folder . $replace_word[0].'/$1'.$this->trust_key;
                        }else{
                            $new_non_wp_rules[$replace_word[1]] = $this->sub_folder . $replace_word[0].$this->trust_key;
                        }
                    }
                }
            }
        }


        //Order is important
        if ($this->opt('rename_plugins') && $this->opt('new_plugin_path') && $this->is_permalink()) {
            foreach ((array) $active_plugins as $active_plugin)  {

                //Ignore itself or a plugin without folder
                if ( !$this->h->str_contains($active_plugin,'/') || $active_plugin==self::main_file)
                    continue;

                $new_plugin_path = trim($this->opt('new_plugin_path'), '/ ') ;

                $codename_this_plugin=  $this->hash($active_plugin);

                $rel_this_plugin_path = trim(str_replace(site_url(),'', plugin_dir_url($active_plugin)), '/');
                //Allows space in plugin folder name
                $rel_this_plugin_path= $this->sub_folder . str_replace(' ','\ ', $rel_this_plugin_path);

                $new_this_plugin_path = $new_plugin_path . '/' . $codename_this_plugin ;
                $new_non_wp_rules[$new_this_plugin_path.'/(.*)'] = $rel_this_plugin_path.'/$1'.$this->trust_key;

                if (is_multisite()){
                    if ($this->is_subdir_mu)
                        $new_this_plugin_path = '/'.$new_this_plugin_path;
                    $rel_this_plugin_path = $this->blog_path . str_replace($this->sub_folder,'',$rel_this_plugin_path);
                }

                $this->partial_replace_old[]=$rel_this_plugin_path.'/';
                $this->partial_replace_new[]=$new_this_plugin_path.'/';

                if ($this->opt('replace_javascript_path')> 1) {
                    $this->replace_old[]= str_replace('/', '\/', $rel_this_plugin_path.'/');
                    $this->replace_new[]= str_replace('/', '\/', $new_this_plugin_path.'/');
                }


            }
        }

        if ($this->opt('new_include_path') && $this->is_permalink()){
            $rel_include_path = $this->sub_folder . trim(WPINC);
            $new_include_path = trim($this->opt('new_include_path'), '/ ') ;

            $new_non_wp_rules[$new_include_path.'/(.*)'] = $rel_include_path.'/$1'.$this->trust_key;

            if (is_multisite()){
                $rel_include_path = $this->blog_path .str_replace($this->sub_folder,'',$rel_include_path);
                if ($this->is_subdir_mu)
                    $new_include_path = '/'.$new_include_path;
            }

            $this->partial_replace_old[]=$rel_include_path;
            $this->partial_replace_new[]=$new_include_path;
        }

        $rel_admin_path = $this->sub_folder . 'wp-admin';
        $new_admin_path = trim($this->opt('new_admin_path'), '/ ');


        if ($new_admin_path && $new_admin_path!='wp-admin' && $this->is_permalink() ){

          /*  if (trim(get_option('hmwp_temp_admin_path'), ' /'))
                $new_admin_path = trim(get_option('hmwp_temp_admin_path'), ' /');
            else
                $new_admin_path = trim($this->opt('new_admin_path'), '/ ');*/

            $new_non_wp_rules[$new_admin_path.'/(.*)'] = $rel_admin_path.'/$1'.$this->trust_key;

            if (is_multisite()){
                if ($this->is_subdir_mu)
                    $new_admin_path = '/'.$new_admin_path;
                $rel_admin_path = $this->blog_path .str_replace($this->sub_folder,'', $rel_admin_path);
            }
            //Add / to fix stylesheet and other 'wp-admin'
            //will break all Replace URLs to wp-admin plus all urls of it
            $this->admin_replace_old[]=$rel_admin_path .'/';
            $this->admin_replace_new[]=$new_admin_path.'/';


            //Fix config code for HMWP nginx / multisite, etc
            if (isset($_GET['page']) && $_GET['page']==self::slug) {
                $this->admin_replace_old[]=$new_admin_path .'/$';
                $this->admin_replace_new[]=$rel_admin_path .'/$';

                $this->admin_replace_old[]=$new_admin_path .'/admin-ajax.php [QSA';
                $this->admin_replace_new[]='wp-admin/admin-ajax.php'.$this->trust_key.' [QSA';

                $this->admin_replace_old[]=$new_admin_path .'/(!network';
                $this->admin_replace_new[]='wp-admin/(!network';

                $this->admin_replace_old[]=$new_admin_path .'/admin-ajax.php last;';
                $this->admin_replace_new[]='wp-admin/admin-ajax.php'.$this->trust_key.' last;';
            }

        }


        if ($this->opt('new_upload_path') && $this->is_permalink()){
            $upload_path=wp_upload_dir();

            if (is_ssl())
                $upload_path['baseurl']= str_replace('http:','https:', $upload_path['baseurl']);

            if (is_multisite() && $current_blog->blog_id!=BLOG_ID_CURRENT_SITE){

                $upload_path_array = explode('/', $upload_path['baseurl']);
                array_pop($upload_path_array);
                array_pop($upload_path_array);
                $upload_path['baseurl'] = implode('/', $upload_path_array);

            }

            $rel_upload_path = $this->sub_folder . trim(str_replace(site_url(),'', $upload_path['baseurl']), '/');;
            $new_upload_path = trim($this->opt('new_upload_path'), '/ ') ;
            $new_non_wp_rules[$new_upload_path.'/(.*)'] = $rel_upload_path.'/$1'.$this->trust_key;

            if (is_multisite()){
		$rel_upload_path = str_replace($this->sub_folder,'',$rel_upload_path);
                if ($this->is_subdir_mu)
                    $new_upload_path = str_replace($this->blog_path, '/', home_url($new_upload_path));
            }


            $this->replace_old[]= home_url($rel_upload_path) ;  //Fix external images problem

            if (is_multisite())
                    $this->replace_new[]= $new_upload_path; //already added home_url!
            else
                $this->replace_new[]= home_url($new_upload_path);

            if ($this->opt('replace_javascript_path')> 2) {
                $this->replace_old[]= str_replace('/', '\/', $rel_upload_path);
                $this->replace_new[]= str_replace('/', '\/', $new_upload_path);
            }
        }


        if ($this->opt('new_plugin_path') && $this->is_permalink()){
            $rel_plugin_path = $this->sub_folder .trim(str_replace(site_url(),'', HMW_WP_PLUGIN_URL), '/');

            $new_plugin_path = trim($this->opt('new_plugin_path'), '/ ') ;
            $new_non_wp_rules[$new_plugin_path.'/(.*)'] = $rel_plugin_path.'/$1'.$this->trust_key;

            if (is_multisite()){
                if ($this->is_subdir_mu)
                    $new_plugin_path = '/'.$new_plugin_path;
                $rel_plugin_path = $this->blog_path .str_replace($this->sub_folder,'', $rel_plugin_path);
            }

            $this->partial_replace_old[]=$rel_plugin_path;
            $this->partial_replace_new[]=$new_plugin_path;

            if ($this->opt('replace_javascript_path')> 1) {
                $this->replace_old[]= str_replace('/', '\/', $rel_plugin_path);
                $this->replace_new[]= str_replace('/', '\/', $new_plugin_path);
            }
        }

        if ($this->opt('new_style_name') && $this->opt('new_theme_path')) {
                $new_style_path = trim($this->opt('new_theme_path'),' /') . '/' .trim($this->opt('new_style_name'), '/ ') ;
                $new_style_path = str_replace('.', '\.', $new_style_path) ;
                $rel_theme_path = $this->sub_folder . trim(str_replace(site_url(),'', get_stylesheet_directory_uri()), '/');
                if ($this->sub_folder)
                    $new_non_wp_rules[$new_style_path] = add_query_arg('style_wrapper', '1', $this->sub_folder) . str_replace('?','&', $this->trust_key);
                else
                    $new_non_wp_rules[$new_style_path] = '/index.php?style_wrapper=1'.str_replace('?','&', $this->trust_key);

                if (trim($this->opt('new_style_name'),' /')!='style.css'){
                    $old_style=trim($this->opt('new_theme_path'),' /') . '/'.'style.css';
                    $new_non_wp_rules[$old_style] = 'nothing_404_404'.$this->trust_key;
                }
        }


        if ($this->opt('new_theme_path') && $this->is_permalink() && !isset($_POST['wp_customize'])){
            $rel_theme_path = $this->sub_folder . trim(str_replace(site_url(),'', get_stylesheet_directory_uri()), '/');

            $new_theme_path = trim($this->opt('new_theme_path'), '/ ') ;
            $new_non_wp_rules[$new_theme_path.'/(.*)'] = $rel_theme_path.'/$1'.$this->trust_key;

            if (is_multisite()){
                if ($this->is_subdir_mu)
                    $new_theme_path = '/'.$new_theme_path;
                $rel_theme_path_with_theme = trim(str_replace(site_url(),'', get_stylesheet_directory_uri()), '/');
                $rel_theme_path = $this->blog_path . str_replace('/'.get_stylesheet(), '', $rel_theme_path_with_theme); //without theme
            }

            $this->partial_replace_old[]=$rel_theme_path;
            $this->partial_replace_new[]=$new_theme_path;

            if ($this->opt('replace_javascript_path')> 0) {
                $this->replace_old[]= str_replace('/', '\/', $rel_theme_path);
                $this->replace_new[]= str_replace('/', '\/', $new_theme_path);
            }

            if (is_child_theme()){
                 //remove the end folder so we can replace it with parent theme
                $path_array =  explode('/', $new_theme_path) ;
                array_pop($path_array);
                $path_string = implode('/', $path_array);

                if ($path_string)
                    $path_string=$path_string.'/' ;

                $parent_theme_new_path = $path_string .get_template() ;
                $rel_parent_theme_path = $this->sub_folder . trim(str_replace(site_url(),'', get_template_directory_uri()), '/');
                $parent_theme_new_path_with_main = $new_theme_path . '_main';

                $new_non_wp_rules[$parent_theme_new_path.'/(.*)'] = $rel_parent_theme_path.'/$1'.$this->trust_key;
                $new_non_wp_rules[$parent_theme_new_path_with_main.'/(.*)'] = $rel_parent_theme_path.'/$1'.$this->trust_key;

                if (!is_multisite())  {
                    $this->partial_replace_old[]=$rel_parent_theme_path;
                    $this->partial_replace_new[]=$parent_theme_new_path_with_main;
                }

                if ($this->opt('replace_javascript_path')> 0) {
                    $this->replace_old[]= str_replace('/', '\/', $rel_parent_theme_path);
                    $this->replace_new[]= str_replace('/', '\/', $parent_theme_new_path_with_main);
                }
            }
        }


        if ($this->opt('replace_admin_ajax') && trim($this->opt('replace_admin_ajax'), '/ ')!='admin-ajax.php' && trim($this->opt('replace_admin_ajax') )!='wp-admin/admin-ajax.php' && $this->is_permalink())  {
            $rel_admin_ajax = $this->sub_folder . 'wp-admin/admin-ajax.php';
            $new_admin_ajax = trim($this->opt('replace_admin_ajax'), '/ ');

            $admin_ajax = str_replace('.','\\.', $new_admin_ajax);

            $new_non_wp_rules[$admin_ajax] = $rel_admin_ajax.$this->trust_key;

            if (is_multisite()){
            	$rel_admin_ajax =  str_replace($this->sub_folder,'',$rel_admin_ajax);
		        $new_admin_ajax =  $new_admin_ajax;
            }

            $this->replace_old[]= $rel_admin_ajax;
            $this->replace_new[]= $new_admin_ajax;

            $this->replace_old[]= str_replace('/', '\/', $rel_admin_ajax);
            $this->replace_new[]= str_replace('/', '\/', $new_admin_ajax);
        }

        if (trim($this->opt('new_content_path'),' /') && trim($this->opt('new_content_path'), '/ ')!='wp-content'){
            $new_content_path = trim($this->opt('new_content_path'),' /');
            $rel_content_path = $this->sub_folder . trim(str_replace(site_url(),'', HMW_WP_CONTENT_URL), '/');

            $new_non_wp_rules[$new_content_path.'/(.*)'] = $rel_content_path.'/$1'.$this->trust_key;

            $this->replace_old[]= str_replace('/', '\/', $rel_content_path);
            $this->replace_new[]= str_replace('/', '\/', $new_content_path);
        }

        if ($this->opt('replace_comments_post') && trim($this->opt('replace_comments_post'), '/ ')!='wp-comments-post.php' && $this->is_permalink())        {

            $rel_comments_post = $this->sub_folder . 'wp-comments-post.php' ;
            $new_comments_post = trim($this->options['replace_comments_post'], '/ ');
            $comments_post = str_replace('.','\\.', $new_comments_post );

            $new_non_wp_rules[$comments_post] = $rel_comments_post .$this->trust_key;

            if (is_multisite()){
                $new_comments_post = $new_comments_post;
                $rel_comments_post = str_replace($this->sub_folder,'', $rel_comments_post);
            }

            $this->replace_old[]= $rel_comments_post;
            $this->replace_new[]= $new_comments_post;
        }



        if ($this->opt('antispam') ) {
            $this->preg_replace_old[]= "%name(\s*)=(\s*)('|\")author('|\")(?!\sdata-hwm)%";
            $this->preg_replace_new[]= "name='authar'";
        }

        if ($this->opt('hide_other_wp_files') && $this->is_permalink()){
            $rel_content_path = $this->sub_folder . trim(str_replace(site_url(),'', HMW_WP_CONTENT_URL), '/');
            $rel_plugin_path = $this->sub_folder . trim(str_replace(site_url(),'', HMW_WP_PLUGIN_URL), '/');
            $rel_include_path = $this->sub_folder .trim(WPINC);

            //Fix an anoying strange bug in some webhosts (bright).
            $screenshot='';
            if (! is_multisite()){
                $rel_theme_path_with_theme = $this->sub_folder . trim(str_replace(site_url(),'', get_stylesheet_directory_uri()), '/');
                $rel_theme_path= str_replace('/'.get_stylesheet(), '', $rel_theme_path_with_theme);
                $screenshot = $rel_theme_path_with_theme.'/screenshot\.png|';
            }

            $style_path_reg='';
          //  if (!is_multisite() && $this->opt('new_style_name') && $this->opt('new_style_name') != 'style.css' && !isset($_POST['wp_customize']))
          //      $style_path_reg = '|'.$rel_theme_path_with_theme.'/style\.css';

            //|'.$rel_plugin_path.'/index\.php|'.$rel_theme_path.'/index\.php'
            $new_non_wp_rules[$screenshot .$this->sub_folder .'readme\.html|'.$this->sub_folder .'license\.txt|'.$rel_content_path.'/debug\.log'.$style_path_reg.'|'.$rel_include_path.'/$'] = 'nothing_404_404'.$this->trust_key;
        }

        if ($this->opt('disable_directory_listing') && $this->is_permalink()) {
            $rel_content_path = $this->sub_folder . trim(str_replace(site_url(),'', HMW_WP_CONTENT_URL), '/');
            $rel_include_path = $this->sub_folder .trim(WPINC);

            $new_non_wp_rules['((('.$rel_content_path.'|'.$rel_include_path.')/([A-Za-z0-9\-\_\/]*))|(wp-admin/(!network\/?)([A-Za-z0-9\-\_\/]+)))(\.txt|/)$'] = 'nothing_404_404'.$this->trust_key;
        }

        if ($this->opt('avoid_direct_access') )  {
            $rel_plugin_path = $this->sub_folder . trim(str_replace(site_url(),'', HMW_WP_PLUGIN_URL), '/');
            $rel_theme_path_with_theme = $this->sub_folder . trim(str_replace(site_url(),'', get_stylesheet_directory_uri()), '/');

            $white_list= explode(",", $this->opt('direct_access_except'));
            $white_list[]='wp-login.php';
            $white_list[]='index.php';
            $white_list[]='wp-admin/';

            if ($this->opt('exclude_theme_access'))
                $white_list[]= $rel_theme_path_with_theme.'/';
            if ($this->opt('exclude_plugins_access'))
                $white_list[]= $rel_plugin_path.'/';

            $block = true;
            $white_regex = '';
            foreach ($white_list as $white_file) {
                 $white_regex.= $this->sub_folder . str_replace(array('.', ' '), array('\.',''), $white_file ).'|';                     //make \. remove spaces
            }
            $white_regex=substr($white_regex, 0 ,strlen($white_regex)-1); //remove last |
            $white_regex = str_replace(array("\n", "\r\n", "\r"), '', $white_regex);
            //ToDo: Maybe this is a better rule. but harder to implement with WP (Because of RewriteCond):
            //RewriteCond %{REQUEST_URI} !(index\.php|wp-content/repair\.php|wp-includes/js/tinymce/wp-tinymce\.php|wp-comments-post\.php|wp-login\.php|index\.php|wp-admin/)(.*)

            $new_non_wp_rules['('.$white_regex.')(.*)'] = '$1$2'.$this->trust_key;
            $new_non_wp_rules[$this->sub_folder . '(.*)\.php(.*)'] = 'nothing_404_404'.$this->trust_key;

        }
        add_filter('mod_rewrite_rules', array(&$this, 'mod_rewrite_rules'),10, 1);


        if (isset($new_non_wp_rules) && $this->is_permalink())
            $wp_rewrite->non_wp_rules = array_merge($wp_rewrite->non_wp_rules, $new_non_wp_rules);

        return $wp_rewrite;

    }
    /**
     * HideMyWP::mod_rewrite_rules()
     * Fix WP generated rules
     * @param mixed $key
     * @return
     */
    function mod_rewrite_rules($rules){
        $home_root = parse_url(home_url());

		if ( isset( $home_root['path'] ) )
			$home_root = trailingslashit($home_root['path']);
		else
			$home_root = '/';

        if ($this->opt('avoid_direct_access') )
            $rules=str_replace('(.*) '.$home_root.'$1$2', '(.*) $1$2', $rules);

        if ($this->opt('full_hide')&& $this->opt('admin_key')){
            $slashed_home      = trailingslashit( get_option( 'home' ) );
            $base = parse_url( $slashed_home, PHP_URL_PATH );

            if (!$this->sub_folder && $base && $base!='/')
                $sub_install= trim($base,' /').'/';
            else
                $sub_install='';

            $trust_key = str_replace('?','',$this->trust_key); //remove ?

           // $this->sub_folder;
            $new_rules= "RewriteRule ^index\\.php$ - [L]
RewriteCond %{HTTP_COOKIE} !".$this->access_cookie()."=1\nRewriteCond %{QUERY_STRING} !".str_replace('?','',$this->trust_key)."\nRewriteRule ^((wp-content|wp-includes|wp-admin)/(.*)) /".$sub_install."nothing_404_404".$this->trust_key." [QSA,L]
";
            $rules = str_replace('RewriteRule ^index\\.php$ - [L]',$new_rules,$rules);
        }

        return $rules;
    }


	/**
	 * HideMyWP::on_activate_callback()
	 *
	 * @return
	 */
	function on_activate_callback() {
        flush_rewrite_rules();
	}

	/**
	 * Register deactivation hook
	 * HideMyWP::on_deactivate_callback()
	 *
	 * @return
	 */
	function on_deactivate_callback() {
        delete_option(self::slug);
        delete_option('hmwp_temp_admin_path');
        delete_option('trust_network_rules');
        flush_rewrite_rules();
	}

    /**
     * HideMyWP::opt()
     * Get options value
     * @param mixed $key
     * @return
     */
    function opt($key){
        if (isset($this->options[$key]))
            return $this->options[$key];
        return false;
    }


    function set_opt($key, $value){
        if (is_multisite()) {
            $opts = get_blog_option(BLOG_ID_CURRENT_SITE, self::slug);
            $opts[$key]= $value;
            update_blog_option(BLOG_ID_CURRENT_SITE, self::slug, $opts);
        }else{
            $opts = get_option(self::slug);
            $opts[$key]= $value;
            update_option(self::slug, $opts);
        }
    }

    function update_attr($query){
        $query['li'] = $this->opt('li');
        return $query;
    }

    function undo_config(){
        $html= '<a href="'.add_query_arg(array('undo_config'=>true)).'" class="button">'.__('Undo Previous Settings', self::slug).'</a>' ;
        $html.= sprintf( '<br><span class="description"> %s</span>', "Click above to restore previous saved settings!" );

        if (isset($_GET['undo_config']) && $_GET['undo_config'] && !isset($_GET['undo'])) {

            $previous = get_option(self::slug . '_undo');

            if (!$previous['new_admin_path'])
                $previous['new_admin_path'] = 'wp-admin';

            update_option('hmwp_temp_admin_path', $previous['new_admin_path']);

            $previous['new_admin_path']= trim($this->opt('new_admin_path'),' /');

            update_option(self::slug, $previous);

            wp_redirect(add_query_arg(array('undo_config'=>true, 'undo'=>'done')));
        }
        return $html;
    }
    function nginx_config(){
        $new_theme_path = trim($this->opt('new_theme_path') ,'/ ') ;
        $new_plugin_path = trim($this->opt('new_plugin_path') ,'/ ') ;
        $new_upload_path = trim($this->opt('new_upload_path') ,'/ ') ;
        $new_include_path = trim($this->opt('new_include_path') ,'/ ') ;
        $new_style_name = trim($this->opt('new_style_name') ,'/ ') ;
        $new_content_path = trim($this->opt('new_content_path') ,'/ ') ;

        if (trim(get_option('hmwp_temp_admin_path'), ' /'))
            $new_admin_path = trim(get_option('hmwp_temp_admin_path'), ' /');
        else
            $new_admin_path = trim($this->opt('new_admin_path'), '/ ');

        $replace_admin_ajax = trim($this->opt('replace_admin_ajax'), '/ ') ;
        $replace_admin_ajax_rule = str_replace('.','\\.', $replace_admin_ajax) ;
        $replace_comments_post= trim($this->opt('replace_comments_post'), '/ ') ;
        $replace_comments_post_rule = str_replace('.','\\.', $replace_comments_post) ;

        $upload_path=wp_upload_dir();

        //not required for nginx
        $sub_install='';

        if (is_ssl())
                $upload_path['baseurl']= str_replace('http:','https:', $upload_path['baseurl']);

        $rel_upload_path = $this->sub_folder . trim(str_replace(site_url(),'', $upload_path['baseurl']), '/');
        $rel_include_path = $this->sub_folder . trim(WPINC);
        $rel_plugin_path = $this->sub_folder .trim(str_replace(site_url(),'', HMW_WP_PLUGIN_URL), '/');
        $rel_theme_path = $this->sub_folder . trim(str_replace(site_url(),'', get_stylesheet_directory_uri()), '/');
        $rel_comments_post = $this->sub_folder . 'wp-comments-post.php';
        $rel_admin_ajax = $this->sub_folder . 'wp-admin/admin-ajax.php';


        $rel_content_path = $this->sub_folder . trim(str_replace(site_url(),'', HMW_WP_CONTENT_URL), '/');
        $rel_theme_path_no_template = str_replace('/'.get_stylesheet(), '', $rel_theme_path);

        $style_path_reg='';
        //if ($this->opt('new_style_name') && $this->opt('new_style_name') != 'style.css' && !isset($_POST['wp_customize']))
        //    $style_path_reg = '|'.$rel_theme_path.'/style\.css';

        //|'.$rel_plugin_path.'/index\.php|'.$rel_theme_path_no_template.'/index\.php'


	    $hide_other_file_rule = $this->sub_folder .'readme\.html|'.$this->sub_folder .'license\.txt|'.$rel_content_path.'/debug\.log'.$style_path_reg.'|'.$rel_include_path.'/$';

        $disable_directoy_listing = '((('.$rel_content_path.'|'.$rel_include_path.')/([A-Za-z0-9\-\_\/]*))|(wp-admin/(!network\/?)([A-Za-z0-9\-\_\/]+)))(\.txt|/)$';

        if ($this->opt('login_query') && $this->opt('login_query'))
            $login_query=  $this->opt('login_query');
        else
            $login_query = 'hide_my_wp';

        if ($this->opt('antispam') && $this->opt('admin_key'))
            $antispam = '?'.$login_query.'='.$this->opt('admin_key');
        else
            $antispam = '';

        if ($this->opt('avoid_direct_access')){

            $rel_theme_path_with_theme = $this->sub_folder . trim(str_replace(site_url(),'', get_stylesheet_directory_uri()), '/');

            $white_list= explode(",", $this->opt('direct_access_except'));
            $white_list[]='wp-login.php';
            $white_list[]='index.php';
            $white_list[]='wp-admin/';

            if ($this->opt('exclude_theme_access'))
                $white_list[]= $rel_theme_path_with_theme.'/';
            if ($this->opt('exclude_plugins_access'))
                $white_list[]= $rel_plugin_path.'/';

            $block = true;
            $white_regex = '';
            foreach ($white_list as $white_file) {
                $white_regex.= $this->sub_folder . str_replace(array('.', ' '), array('\.',''), $white_file ).'|';  //make \. remove spaces
            }
            $white_regex=substr($white_regex, 0 ,strlen($white_regex)-1); //remove last |
            $white_regex = str_replace(array("\r", "\r\n", "\n"), '', $white_regex);
        }


        $output='';

        if ($this->opt('full_hide')){
            //ignored: condition 0
            //todo: wp-content|includes
            $full_hide= '
if ($args !~ "'.str_replace('?','',$this->trust_key).'"){
	set $rule_0 1;
}
if ($http_cookie !~* "'.$this->access_cookie().'=1"){
	set $rule_0 "${rule_0}2";
}
if ($rule_0 = "12"){
	rewrite ^/((wp-content|wp-includes|wp-admin)/(.*)) /nothing_404_404'.$this->trust_key.' last;
}
';

            $output =  $full_hide . $output;
        }

        if ($this->opt('replace_urls')){
            $replace_urls=$this->h->replace_newline(trim($this->opt('replace_urls'),' '),'|');
            $replace_lines=explode('|', $replace_urls);
            if ($replace_lines) {
                foreach ($replace_lines as $line)  {

                    $replace_word = explode('==', $line);
                    if (isset($replace_word[0]) && isset($replace_word[1])) {

                        //Check whether last character is / or not to recgnize folders
                        $is_folder= false;
                        if (substr($replace_word[0], strlen($replace_word[0])-1 , strlen($replace_word[0]))=='/')
                            $is_folder= true;

                        $replace_word[0]=trim($replace_word[0], '/ ');
                        $replace_word[1]=trim($replace_word[1], '/ ');

                        $is_block= false;
                        if ($replace_word[1] == 'nothing_404_404')
                            $is_block= true;


                        if ($is_block){
                            //Swap words to make theme unavailable
                            $temp = $replace_word[0];
                            $replace_word[0] = $replace_word[1];
                            $replace_word[1] = $temp;
                        }

                        $replace_word[0] = str_replace(array( 'amp;', '%2F','//', '.' ), array('', '/', '/','.'), $replace_word[0]);
                        $replace_word[1] = str_replace(array('.','amp;'), array('\.',''), $replace_word[1]);

                        if ($is_folder){
                            $output.='rewrite ^/'.$replace_word[1]. '/(.*) /'. $sub_install . $replace_word[0]. '/$1'.$this->trust_key.' last;'."\n";
                        }else{
                            $output.='rewrite ^/'.$replace_word[1]. ' /'. $sub_install . $replace_word[0]. $this->trust_key.' last;'."\n";
                        }
                    }
                }
            }
        }


	    if ( is_multisite() ){
            $sitewide_plugins = array_keys( (array) get_site_option( 'active_sitewide_plugins', array() ));
            $active_plugins= array_merge((array) get_blog_option(BLOG_ID_CURRENT_SITE, 'active_plugins'), $sitewide_plugins);
        }else{
            $active_plugins = get_option('active_plugins');
        }

        if ($this->opt('rename_plugins')=='all')
             $active_plugins = get_option('hmw_all_plugins');

	    $pre_plugin_path='';
        if ($this->opt('rename_plugins') && $new_plugin_path) {
            foreach ((array) $active_plugins as $active_plugin)  {

                //Ignore itself or a plugin without folder
                if ( !$this->h->str_contains($active_plugin,'/') || $active_plugin==self::main_file)
                    continue;

                $new_plugin_path = trim($new_plugin_path, '/ ') ;

                $codename_this_plugin=  $this->hash($active_plugin);

                $rel_this_plugin_path = trim(str_replace(site_url(),'', plugin_dir_url($active_plugin)), '/');
                //Allows space in plugin folder name
                $rel_this_plugin_path = $this->sub_folder . str_replace(' ','\ ', $rel_this_plugin_path);

                $new_this_plugin_path = $new_plugin_path . '/' . $codename_this_plugin ;
                $pre_plugin_path.= 'rewrite ^/'.$new_this_plugin_path. '/(.*) /'. $rel_this_plugin_path. '/$1'.$this->trust_key.' last;'."\n";
            }
        }




        if (is_child_theme()){
                 //remove the end folder of so we can replace it with parent theme
                $path_array =  explode('/', $new_theme_path) ;
                array_pop($path_array);
                $path_string = implode('/', $path_array);

                if ($path_string)
                    $path_string=$path_string.'/' ;

                $parent_theme_new_path = $path_string .get_template() ;
                $rel_parent_theme_path = $this->sub_folder . trim(str_replace(site_url(),'', get_template_directory_uri()), '/');
                $output.='rewrite ^/'.$parent_theme_new_path. '/(.*) /'. $rel_parent_theme_path. '/$1'.$this->trust_key.' last;'."\n";
                $parent_theme_new_path_with_main = $new_theme_path . '_main';
                $output.='rewrite ^/'.$parent_theme_new_path_with_main. '/(.*) /'. $rel_parent_theme_path. '/$1 last;'."\n";
        }


        if ($new_admin_path && $new_admin_path!='wp-admin')
            $output.='rewrite ^/'.$new_admin_path. '/(.*) /'. $this->sub_folder. 'wp-admin/$1'.$this->trust_key.' last;'."\n";

        if ($new_include_path)
            $output.='rewrite ^/'.$new_include_path. '/(.*) /'. $rel_include_path. '/$1'.$this->trust_key.' last;'."\n";

        if ($new_upload_path)
            $output.='rewrite ^/'.$new_upload_path. '/(.*) /'. $rel_upload_path. '/$1'.$this->trust_key.' last;'."\n";

        if ($new_plugin_path && $pre_plugin_path)
            $output.= $pre_plugin_path;

        if ($new_plugin_path)
            $output.='rewrite ^/'.$new_plugin_path. '/(.*) /'. $rel_plugin_path. '/$1'.$this->trust_key.' last;'."\n";

        if ($new_style_name)
            $output.='rewrite ^/'.$new_theme_path. '/'.str_replace('.','\.', $new_style_name).' /?style_wrapper=1'.str_replace('?','&', $this->trust_key).' last;'."\n";

        if (trim($this->opt('new_style_name'),' /') && trim($this->opt('new_style_name'),' /')!='style.css'){
            $old_style= $new_theme_path . '/'.'style.css';
            $output.='rewrite ^/'.$old_style. ' /nothing_404_404'.$this->trust_key.' last;'."\n";
        }

        if ($new_theme_path)
            $output.='rewrite ^/'.$new_theme_path. '/(.*) /'. $rel_theme_path. '/$1'.$this->trust_key.' last;'."\n";

        if ($replace_comments_post && $replace_comments_post != 'wp-comments-post.php')
            $output.='rewrite ^/'.$replace_comments_post_rule. ' /'. $rel_comments_post. $this->trust_key.' last;'."\n";

        if ($replace_admin_ajax_rule && $replace_admin_ajax_rule != 'wp-admin/admin-ajax.php')
            $output.='rewrite ^/'.$replace_admin_ajax_rule. ' /'. $rel_admin_ajax.$this->trust_key.' last;'."\n";

        if ($new_content_path)
            $output.='rewrite ^/'.$new_content_path. '/(.*) /'. $rel_content_path. '/$1'.$this->trust_key.' last;'."\n";


        if ($this->opt('hide_other_wp_files'))
            $output.='rewrite ^/('.$hide_other_file_rule. ') /nothing_404_404'.$this->trust_key.' last;'."\n";

        if ($this->opt('disable_directory_listing') )
            $output.='rewrite ^/'.$disable_directoy_listing. ' /nothing_404_404'.$this->trust_key.' last;'."\n";

        if ($this->opt('avoid_direct_access')){

            $output.="\n".'#If you have a block with "location ~ \.php$"  add following two lines to the top of that block otherwise leave it unchanged'."\n";
            $output.='rewrite ^/('.$white_regex.')(.*)'. ' /$1$2'.$this->trust_key. ' break;'."\n";
            $output.='rewrite ^/(.*).php(.*)'. ' /nothing_404_404'.$this->trust_key.' last;'."\n\n";
        }

        if ($output)
            //$output='if (!-e $request_filename) {'. "\n" .  $output . "     break;\n}";
            $output="# BEGIN Hide My WP\n\n" . $output ."\n# END Hide My WP";
        else
            $output=__('Nothing to add for current settings.', self::slug);

        $html='';
        $desc = __( 'Add to Nginx config file to get all features of the plugin. <br>', self::slug ) ;

        if (isset($_GET['nginx_config']) && $_GET['nginx_config'])  {

            $html= sprintf( '%s ', $desc );
            $html.= sprintf( '<span class="description">
        <ol style="color:#ff9900">
        <li>Nginx config file usually located in /etc/nginx/nginx.conf or /etc/nginx/conf/nginx.conf</li>
        <li>You may need to re-configure the server whenever you change settings or activate a new theme or plugin.</li>
        <li>If you use sub-directory for WP block you should add that directory before all of below pathes (e.g. rewrite ^/wordpress/lib/(.*) /wordpress/wp-includes/$1 or rewrite ^/wordpress/(.*).php(.*) /wordpress/nothing_404_404)</li></ol></span><textarea readonly="readonly" onclick="" rows="5" cols="55" class="regular-text %1$s" id="%2$s" name="%2$s" style="%4$s">%3$s</textarea>', 'nginx_config_class','nginx_config', esc_textarea($output), 'width:95% !important;height:400px !important' );


        }else{
            $html= '<a target="_blank" href="'.add_query_arg(array('die_message'=>'nginx')).'" class="button">'.__('Nginx Configuration', self::slug).'</a>' ;
            $html.= sprintf( '<br><span class="description"> %s</span>', $desc );
        }
        return $html;
      //rewrite ^/assets/css/(.*)$ /wp-content/themes/roots/assets/css/$1 last;


    }

    function iis_config(){
        $new_theme_path = trim($this->opt('new_theme_path') ,'/ ') ;
        $new_plugin_path = trim($this->opt('new_plugin_path') ,'/ ') ;
        $new_upload_path = trim($this->opt('new_upload_path') ,'/ ') ;
        $new_include_path = trim($this->opt('new_include_path') ,'/ ') ;
        $new_style_name = trim($this->opt('new_style_name') ,'/ ') ;
        $new_content_path= trim($this->opt('new_content_path') ,'/ ') ;

        if (trim(get_option('hmwp_temp_admin_path'), ' /'))
            $new_admin_path = trim(get_option('hmwp_temp_admin_path'), ' /');
        else
            $new_admin_path = trim($this->opt('new_admin_path'), '/ ');

        $replace_admin_ajax = trim($this->opt('replace_admin_ajax'), '/ ') ;
        $replace_admin_ajax_rule = str_replace('.','\\.', $replace_admin_ajax) ;
        $replace_comments_post= trim($this->opt('replace_comments_post'), '/ ') ;
        $replace_comments_post_rule = str_replace('.','\\.', $replace_comments_post) ;

        $upload_path=wp_upload_dir();

        //not required for nginx
        $sub_install='';

        $page_query = ($this->opt('page_query')) ? $this->opt('page_query') : 'page_id';

        $iis_not_found = 'index.php?'.$page_query . '=999999999';

        if (is_ssl())
            $upload_path['baseurl']= str_replace('http:','https:', $upload_path['baseurl']);

        $rel_upload_path = $this->sub_folder . trim(str_replace(site_url(),'', $upload_path['baseurl']), '/');
        $rel_include_path = $this->sub_folder . trim(WPINC);
        $rel_plugin_path = $this->sub_folder .trim(str_replace(site_url(),'', HMW_WP_PLUGIN_URL), '/');
        $rel_theme_path = $this->sub_folder . trim(str_replace(site_url(),'', get_stylesheet_directory_uri()), '/');
        $rel_comments_post = $this->sub_folder . 'wp-comments-post.php';
        $rel_admin_ajax = $this->sub_folder . 'wp-admin/admin-ajax.php';


        $rel_content_path = $this->sub_folder . trim(str_replace(site_url(),'', HMW_WP_CONTENT_URL), '/');
        $rel_theme_path_no_template = str_replace('/'.get_stylesheet(), '', $rel_theme_path);

        $style_path_reg='';
        //if ($this->opt('new_style_name') && $this->opt('new_style_name') != 'style.css' && !isset($_POST['wp_customize']))
        //    $style_path_reg = '|'.$rel_theme_path.'/style\.css';

        //|'.$rel_plugin_path.'/index\.php|'.$rel_theme_path_no_template.'/index\.php'


        $hide_other_file_rule = $this->sub_folder .'readme\.html|'.$this->sub_folder .'license\.txt|'.$rel_content_path.'/debug\.log'.$style_path_reg.'|'.$rel_include_path.'/$';

        //Customized for iis! removed 2\ and replaced ? and removed /
        $disable_directoy_listing = '((('.$rel_content_path.'|'.$rel_include_path.')([A-Za-z0-9-_\/]*))|(wp-admin/(?!network\/)([A-Za-z0-9-_\/]+)))(\.txt|/)$';

        if ($this->opt('login_query') && $this->opt('login_query'))
            $login_query=  $this->opt('login_query');
        else
            $login_query = 'hide_my_wp';

        if ($this->opt('antispam') && $this->opt('admin_key'))
            $antispam = '?'.$login_query.'='.$this->opt('admin_key');
        else
            $antispam = '';

        if ($this->opt('avoid_direct_access')){

            $rel_theme_path_with_theme = $this->sub_folder . trim(str_replace(site_url(),'', get_stylesheet_directory_uri()), '/');

            $white_list= explode(",", $this->opt('direct_access_except'));
            $white_list[]='wp-login.php';
            $white_list[]='index.php';
            $white_list[]='wp-admin/';

            if ($this->opt('exclude_theme_access'))
                $white_list[]= $rel_theme_path_with_theme.'/';
            if ($this->opt('exclude_plugins_access'))
                $white_list[]= $rel_plugin_path.'/';

            $block = true;
            $white_regex = '';
            foreach ($white_list as $white_file) {
                $white_regex.= $this->sub_folder . str_replace(array('.', ' '), array('\.',''), $white_file ).'|';  //make \. remove spaces
            }
            $white_regex=substr($white_regex, 0 ,strlen($white_regex)-1); //remove last |
            $white_regex = str_replace(array("\r", "\r\n", "\n"), '', $white_regex);
        }


        $output='';

        if ($this->opt('replace_urls')){
            $replace_urls=$this->h->replace_newline(trim($this->opt('replace_urls'),' '),'|');
            $replace_lines=explode('|', $replace_urls);
            if ($replace_lines) {
                foreach ($replace_lines as $line)  {

                    $replace_word = explode('==', $line);
                    if (isset($replace_word[0]) && isset($replace_word[1])) {

                        //Check whether last character is / or not to recgnize folders
                        $is_folder= false;
                        if (substr($replace_word[0], strlen($replace_word[0])-1 , strlen($replace_word[0]))=='/')
                            $is_folder= true;

                        $replace_word[0]=trim($replace_word[0], '/ ');
                        $replace_word[1]=trim($replace_word[1], '/ ');

                        $is_block= false;
                        if ($replace_word[1] == 'nothing_404_404')
                            $is_block= true;


                        if ($is_block){
                            //Swap words to make theme unavailable
                            $temp = $replace_word[0];
                            $replace_word[0] = $replace_word[1];
                            $replace_word[1] = $temp;
                        }

                        $replace_word[0] = str_replace(array( 'amp;', '%2F','//', '.' ), array('', '/', '/','.'), $replace_word[0]);
                        $replace_word[1] = str_replace(array('.','amp;'), array('\.',''), $replace_word[1]);

                        if ($is_folder){
                            $output.='<rule name="HMWP Replace'.rand(0,9999).'" stopProcessing="true">'."\n\t".'<match url="^'.$replace_word[1]. '/(.*)"  />'."\n\t".'<action type="Rewrite" url="'. $sub_install . $replace_word[0].$this->trust_key. '/{R:1}"  appendQueryString="true" />'."\n".'</rule>'."\n";

                        }else{
                            $output.='<rule name="rule HMWP_Replace'.rand(0,9999).'" stopProcessing="true">'."\n\t".'<match url="^'.$replace_word[1]. '"  />'."\n\t".'<action type="Rewrite" url="'. $sub_install . $replace_word[0].$this->trust_key. '"  appendQueryString="true" />'."\n".'</rule>'."\n";
                        }
                    }
                }
            }
        }


        if ( is_multisite() ){
            $sitewide_plugins = array_keys( (array) get_site_option( 'active_sitewide_plugins', array() ));
            $active_plugins= array_merge((array) get_blog_option(BLOG_ID_CURRENT_SITE, 'active_plugins'), $sitewide_plugins);
        }else{
            $active_plugins = get_option('active_plugins');
        }

        if ($this->opt('rename_plugins')=='all')
            $active_plugins = get_option('hmw_all_plugins');

        $pre_plugin_path='';
        if ($this->opt('rename_plugins') && $new_plugin_path) {
            foreach ((array) $active_plugins as $active_plugin)  {

                //Ignore itself or a plugin without folder
                if ( !$this->h->str_contains($active_plugin,'/') || $active_plugin==self::main_file)
                    continue;

                $new_plugin_path = trim($new_plugin_path, '/ ') ;

                //$codename_this_plugin=  hash('crc32', $this->ecrypt($active_plugin, substr(NONCE_SALT, 4, 12))  );
                $codename_this_plugin=  $this->hash($active_plugin);

                $rel_this_plugin_path = trim(str_replace(site_url(),'', plugin_dir_url($active_plugin)), '/');
                //Allows space in plugin folder name
                $rel_this_plugin_path = $this->sub_folder . str_replace(' ','\ ', $rel_this_plugin_path);

                $new_this_plugin_path = $new_plugin_path . '/' . $codename_this_plugin ;
                $pre_plugin_path.='<rule name="HMWP Plugin'.rand(0,9999).'" stopProcessing="true">'."\n\t".'<match url="^'.$new_this_plugin_path. '/(.*)"  />'."\n\t".'<action type="Rewrite" url="'. $rel_this_plugin_path. '/{R:1}'.$this->trust_key.'"  appendQueryString="true" />'."\n".'</rule>'."\n";
            }
        }


        if (is_child_theme()){
            //remove the end folder of so we can replace it with parent theme
            $path_array =  explode('/', $new_theme_path) ;
            array_pop($path_array);
            $path_string = implode('/', $path_array);

            if ($path_string)
                $path_string=$path_string.'/' ;

            $parent_theme_new_path = $path_string .get_template() ;
            $rel_parent_theme_path = $this->sub_folder . trim(str_replace(site_url(),'', get_template_directory_uri()), '/');

            $output.='<rule name="HMWP Theme'.rand(0,9999).'" stopProcessing="true">'."\n\t".'<match url="^'.$parent_theme_new_path. '/(.*)"  />'."\n\t".'<action type="Rewrite" url="'. $rel_parent_theme_path. '/{R:1}'.$this->trust_key.'"  appendQueryString="true" />'."\n".'</rule>'."\n";

            $parent_theme_new_path_with_main = $new_theme_path . '_main';
            $output.='<rule name="HMWP Theme'.rand(0,9999).'" stopProcessing="true">'."\n\t".'<match url="^'.$parent_theme_new_path_with_main. '/(.*)"  />'."\n\t".'<action type="Rewrite" url="'. $rel_parent_theme_path.'/{R:1}'.$this->trust_key.'"  appendQueryString="true" />'."\n".'</rule>'."\n";
        }


        if ($new_admin_path && $new_admin_path!='wp-admin')
            $output.='<rule name="HMWP Admin'.rand(0,9999).'" stopProcessing="true">'."\n\t".'<match url="^'.$new_admin_path. '/(.*)"  />'."\n\t".'<action type="Rewrite" url="'.  $this->sub_folder. 'wp-admin/{R:1}'.$this->trust_key.'"  appendQueryString="true" />'."\n".'</rule>'."\n";


        if ($new_include_path)
            $output.='<rule name="HMWP Include'.rand(0,9999).'" stopProcessing="true">'."\n\t".'<match url="^'.$new_include_path. '/(.*)"  />'."\n\t".'<action type="Rewrite" url="'. $rel_include_path. '/{R:1}'.$this->trust_key.'"  appendQueryString="true" />'."\n".'</rule>'."\n";

        if ($new_upload_path)
            $output.='<rule name="HMWP Upload'.rand(0,9999).'" stopProcessing="true">'."\n\t".'<match url="^'.$new_upload_path. '/(.*)"  />'."\n\t".'<action type="Rewrite" url="'. $rel_upload_path. '/{R:1}'.$this->trust_key.'"  appendQueryString="true" />'."\n".'</rule>'."\n";


        if ($new_plugin_path && $pre_plugin_path)
            $output.= $pre_plugin_path;

        if ($new_plugin_path)
            $output.='<rule name="HMWP Plugin_Dir'.rand(0,9999).'" stopProcessing="true">'."\n\t".'<match url="^'.$new_plugin_path. '/(.*)"  />'."\n\t".'<action type="Rewrite" url="'. $rel_plugin_path.'/{R:1}'.$this->trust_key.'"  appendQueryString="true" />'."\n".'</rule>'."\n";


        if ($new_style_name)
            $output.='<rule name="HMWP Style'.rand(0,9999).'" stopProcessing="true">'."\n\t".'<match url="^'.$new_theme_path. '/'.str_replace('.','\.', $new_style_name).'"  />'."\n\t".'<action type="Rewrite" url="'. '/index.php?style_wrapper=1' .str_replace('?','&amp;', $this->trust_key). '"  appendQueryString="true" />'."\n".'</rule>'."\n";

        if (trim($this->opt('new_style_name'),' /') && trim($this->opt('new_style_name'),' /')!='style.css'){
            $old_style= $new_theme_path . '/'.'style.css';
            //$output.='rewrite ^/'.$old_style. '/nothing_404_404'.$this->trust_key.' last;'."\n";
            $output.='<rule name="HMWP Other_WP'.rand(0,9999).'" stopProcessing="true">'."\n\t".'<match url="^'.$old_style. '"  />'."\n\t".'<action type="Rewrite" url="'. $iis_not_found .'"  appendQueryString="true" />'."\n".'</rule>'."\n";
        }

        if ($new_theme_path)
            $output.='<rule name="HMWP Theme'.rand(0,9999).'" stopProcessing="true">'."\n\t".'<match url="^'.$new_theme_path. '/(.*)"  />'."\n\t".'<action type="Rewrite" url="'. $rel_theme_path. '/{R:1}'.$this->trust_key.'"  appendQueryString="true" />'."\n".'</rule>'."\n";


        if ($replace_comments_post && $replace_comments_post != 'wp-comments-post.php')
            $output.='<rule name="HMWP Comment'.rand(0,9999).'" stopProcessing="true">'."\n\t".'<match url="^'.$replace_comments_post_rule.'"  />'."\n\t".'<action type="Rewrite" url="'. '/'.$rel_comments_post.$this->trust_key.'"  appendQueryString="true" />'."\n".'</rule>'."\n";

        if ($replace_admin_ajax_rule && $replace_admin_ajax_rule != 'wp-admin/admin-ajax.php')
            $output.='<rule name="HMWP AJAX'.rand(0,9999).'" stopProcessing="true">'."\n\t".'<match url="^'.$replace_admin_ajax_rule.'"  />'."\n\t".'<action type="Rewrite" url="'. '/'.$rel_admin_ajax.$this->trust_key.'"  appendQueryString="true" />'."\n".'</rule>'."\n";

        if ($new_content_path)
            $output.='<rule name="HMWP Content'.rand(0,9999).'" stopProcessing="true">'."\n\t".'<match url="^'.$new_content_path. '/(.*)"  />'."\n\t".'<action type="Rewrite" url="'. $rel_content_path.'/{R:1}'.$this->trust_key.'"  appendQueryString="true" />'."\n".'</rule>'."\n";

        if ($this->opt('hide_other_wp_files'))
            $output.='<rule name="HMWP Other_WP'.rand(0,9999).'" stopProcessing="true">'."\n\t".'<match url="^('.$hide_other_file_rule. ')"  />'."\n\t".'<action type="Rewrite" url="'. $iis_not_found .'"  appendQueryString="true" />'."\n".'</rule>'."\n";

        if ($this->opt('disable_directory_listing') )
            $output.='<rule name="HMWP Dir_List'.rand(0,9999).'" stopProcessing="true">'."\n\t".'<match url="^'.$disable_directoy_listing. '"  />'."\n\t".'<action type="Rewrite" url="'. $iis_not_found .'"  appendQueryString="true" />'."\n".'</rule>'."\n";

        if ($this->opt('avoid_direct_access')){
            $output.='<rule name="HMWP Excerpt_PHP'.rand(0,9999).'" stopProcessing="true">'."\n\t".'<match url="^('.$white_regex.')(.*)"  />'."\n\t".'<action type="Rewrite" url="/{R:1}{R:2}"  appendQueryString="true" />'."\n".'</rule>'."\n";
            $output.='<rule name="HMWP Avoid_PHP'.rand(0,9999).'" stopProcessing="true">'."\n\t".'<match url="^(.*).php(.*)"  />'."\n\t".'<action type="Rewrite" url="'. $iis_not_found .'"  appendQueryString="true" />'."\n".'</rule>'."\n";
        }

        if ($output)
            //$output='if (!-e $request_filename) {'. "\n" .  $output . "     break;\n}";
            $output="# BEGIN Hide My WP\n\n" . $output ."\n# END Hide My WP";
        else
            $output=__('Nothing to add for current settings.', self::slug);

        $html='';
        $desc = __( 'Add to web.config to get all features of the plugin<br>', self::slug ) ;

        if (isset($_GET['iis_config']) && $_GET['iis_config'])  {

            $html= sprintf( '%s ', $desc );
            $html.= sprintf( '<span class="description">
        <ol style="color:#ff9900">
        <li>Web.config file is located in WP root directory</li>
        <li>Add it to right before <strong>&lt;rule name="wordpress" patternSyntax="Wildcard"&gt; </strong></li>
        <li>You may need to re-configure the server whenever you change settings or activate a new theme or plugin.</li>
        </ol></span><textarea readonly="readonly" onclick="" rows="5" cols="55" class="regular-text %1$s" id="%2$s" name="%2$s" style="%4$s">%3$s</textarea>', 'iis_config_class','iis_config', esc_textarea($output), 'width:95% !important;height:400px !important' );


        }else{
            $html= '<a target="_blank" href="'.add_query_arg(array('die_message'=>'iis')).'" class="button">'.__('Windows Configuration (IIS)', self::slug).'</a>' ;
            $html.= sprintf( '<br><span class="description"> %s</span>', $desc );
        }
        return $html;

    }

    function single_config(){
        $slashed_home      = trailingslashit( get_option( 'home' ) );
        $base = parse_url( $slashed_home, PHP_URL_PATH );

        if (!$this->sub_folder && $base && $base!='/')
            $sub_install= trim($base,' /').'/';
        else
            $sub_install='';

        $new_theme_path = trim($this->opt('new_theme_path') ,'/ ') ;
        $new_plugin_path = trim($this->opt('new_plugin_path') ,'/ ') ;
        $new_upload_path = trim($this->opt('new_upload_path') ,'/ ') ;
        $new_include_path = trim($this->opt('new_include_path') ,'/ ') ;
        $new_style_name = trim($this->opt('new_style_name') ,'/ ') ;
        $new_content_path = trim($this->opt('new_content_path') ,'/ ') ;

        if (trim(get_option('hmwp_temp_admin_path'), ' /'))
            $new_admin_path = trim(get_option('hmwp_temp_admin_path'), ' /');
        else
            $new_admin_path = trim($this->opt('new_admin_path'), '/ ');

        $replace_admin_ajax = trim($this->opt('replace_admin_ajax'), '/ ') ;
        $replace_admin_ajax_rule = str_replace('.','\\.', $replace_admin_ajax) ;
        $replace_comments_post= trim($this->opt('replace_comments_post'), '/ ') ;
        $replace_comments_post_rule = str_replace('.','\\.', $replace_comments_post) ;

        $upload_path=wp_upload_dir();

        if (is_ssl())
                $upload_path['baseurl']= str_replace('http:','https:', $upload_path['baseurl']);

        $rel_upload_path = $sub_install . trim(str_replace(site_url(),'', $upload_path['baseurl']), '/');

        $rel_plugin_path = $sub_install .trim(str_replace(site_url(),'', HMW_WP_PLUGIN_URL), '/');
        $rel_theme_path = $sub_install . trim(str_replace(site_url(),'', get_stylesheet_directory_uri()), '/');
        $rel_comments_post = $sub_install . 'wp-comments-post.php';
        $rel_admin_ajax = $sub_install . 'wp-admin/admin-ajax.php';
        $rel_include_path2 = $sub_install . trim(WPINC); //To use in second part


        //Only use it if you want subfoler in first part
        $rel_include_path = $this->sub_folder . trim(WPINC);
        $rel_theme_path_with_subfolder= $this->sub_folder . trim(str_replace(site_url(),'', get_stylesheet_directory_uri()), '/');
        $rel_content_path = $this->sub_folder . trim(str_replace(site_url(),'', HMW_WP_CONTENT_URL), '/');
        $rel_theme_path_no_template = str_replace('/'.get_stylesheet(), '', $rel_theme_path);


        $style_path_reg='';
        //if ($new_style_name && $new_style_name != 'style.css' && !isset($_POST['wp_customize']))
         //   $style_path_reg = '|'.$rel_theme_path.'/style\.css';

        //|'.$rel_plugin_path.'/index\.php|'.$rel_theme_path_no_template.'/index\.php'
        $hide_other_file_rule = $this->sub_folder .'readme\.html|'.$this->sub_folder .'license\.txt|'.$rel_content_path.'/debug\.log'.$style_path_reg.'|'.$rel_include_path.'/$';

        $disable_directoy_listing = '((('.$rel_content_path.'|'.$rel_include_path.')/([A-Za-z0-9\-\_\/]*))|(wp-admin/(!network\/?)([A-Za-z0-9\-\_\/]+)))(\.txt|/)$';

        if ($this->opt('login_query') && $this->opt('login_query'))
            $login_query=  $this->opt('login_query');
        else
            $login_query = 'hide_my_wp';



        if ($this->opt('avoid_direct_access')){
            $rel_theme_path_with_theme = $sub_install . trim(str_replace(site_url(),'', get_stylesheet_directory_uri()), '/');

            $white_list= explode(",", $this->opt('direct_access_except'));
            $white_list[]='wp-login.php';
            $white_list[]='index.php';
            $white_list[]='wp-admin/';

            if ($this->opt('exclude_theme_access'))
                $white_list[]= $rel_theme_path_with_theme.'/';
            if ($this->opt('exclude_plugins_access'))
                $white_list[]= $rel_plugin_path.'/';

            $block = true;
            $white_regex = '';
            foreach ($white_list as $white_file) {
                $white_regex.= $sub_install . str_replace(array('.', ' '), array('\.',''), $white_file ).'|';  //make \. remove spaces
            }
            $white_regex=substr($white_regex, 0 ,strlen($white_regex)-1); //remove last |
            $white_regex = str_replace(array("\n", "\r\n", "\r"), '', $white_regex);
        }

        $output='';

        if ($this->opt('full_hide')){

            $full_hide= "
RewriteCond %{HTTP_COOKIE} !".$this->access_cookie()."=1
RewriteCond %{QUERY_STRING} !".str_replace('?','',$this->trust_key)."
RewriteRule ^((wp-content|wp-includes|wp-admin)/(.*)) /".$sub_install."nothing_404_404".$this->trust_key." [QSA,L]
";

            $output =  $full_hide . $output;
        }

        if ($this->opt('replace_urls')){
            $replace_urls=$this->h->replace_newline(trim($this->opt('replace_urls'),' '),'|');
            $replace_lines=explode('|', $replace_urls);
            if ($replace_lines) {
                foreach ($replace_lines as $line)  {

                    $replace_word = explode('==', $line);
                    if (isset($replace_word[0]) && isset($replace_word[1])) {

                        //Check whether last character is / or not to recgnize folders
                        $is_folder= false;
                        if (substr($replace_word[0], strlen($replace_word[0])-1 , strlen($replace_word[0]))=='/')
                            $is_folder= true;

                        $replace_word[0]=trim($replace_word[0], '/ ');
                        $replace_word[1]=trim($replace_word[1], '/ ');

                        $is_block= false;
                        if ($replace_word[1] == 'nothing_404_404')
                            $is_block= true;


                        if ($is_block){
                            //Swap words to make theme unavailable
                            $temp = $replace_word[0];
                            $replace_word[0] = $replace_word[1];
                            $replace_word[1] = $temp;
                        }

                        $replace_word[0] = str_replace(array( 'amp;', '%2F','//', '.' ), array('', '/', '/','.'), $replace_word[0]);
                        $replace_word[1] = str_replace(array('.','amp;'), array('\.',''), $replace_word[1]);

                        if ($is_folder){
                            $output.='RewriteRule ^'.$replace_word[1]. '/(.*) /'. $sub_install . $replace_word[0]. '/$1'.$this->trust_key.' [QSA,L]'."\n";
                        }else{
                            $output.='RewriteRule ^'.$replace_word[1]. ' /'. $sub_install . $replace_word[0] .$this->trust_key.' [QSA,L]'."\n";
                        }
                    }
                }
            }
        }


        $active_plugins = get_option('active_plugins');

        if ($this->opt('rename_plugins')=='all')
             $active_plugins = get_option('hmw_all_plugins');

	    $pre_plugin_path='';
        if ($this->opt('rename_plugins') && $new_plugin_path) {
            foreach ((array) $active_plugins as $active_plugin)  {

                //Ignore itself or a plugin without folder
                if ( !$this->h->str_contains($active_plugin,'/') || $active_plugin==self::main_file)
                    continue;

                $new_plugin_path = trim($new_plugin_path, '/ ') ;

                $codename_this_plugin=  $this->hash($active_plugin);

                $rel_this_plugin_path = trim(str_replace(site_url(),'', plugin_dir_url($active_plugin)), '/');
                //Allows space in plugin folder name
                $rel_this_plugin_path = $sub_install . str_replace(' ','\ ', $rel_this_plugin_path);

                $new_this_plugin_path = $new_plugin_path . '/' . $codename_this_plugin ;
                $pre_plugin_path.= 'RewriteRule ^'.$new_this_plugin_path. '/(.*) /'. $rel_this_plugin_path. '/$1'.$this->trust_key.' [QSA,L]'."\n";
            }
        }



        if (is_child_theme()){
                 //remove the end folder of so we can replace it with parent theme
                $path_array =  explode('/', $new_theme_path) ;
                array_pop($path_array);
                $path_string = implode('/', $path_array);

                if ($path_string)
                    $path_string=$path_string.'/' ;

                $parent_theme_new_path = $path_string .get_template() ;
                $rel_parent_theme_path = $sub_install . trim(str_replace(site_url(),'', get_template_directory_uri()), '/');
                $output.='RewriteRule ^'.$parent_theme_new_path. '/(.*) /'. $rel_parent_theme_path. '/$1'.$this->trust_key.' [QSA,L]'."\n";
                $parent_theme_new_path_with_main = $new_theme_path . '_main';
                $output.='RewriteRule ^'.$parent_theme_new_path_with_main. '/(.*) /'. $rel_parent_theme_path. '/$1'.$this->trust_key.' [QSA,L]'."\n";
        }

        if ($new_admin_path && $new_admin_path!='wp-admin' )
            $output.='RewriteRule ^'.$new_admin_path. '/(.*) /'. $sub_install. 'wp-admin/$1'.$this->trust_key.' [QSA,L]'."\n";


        if ($new_include_path)
            $output.='RewriteRule ^'.$new_include_path. '/(.*) /'. $rel_include_path2. '/$1'.$this->trust_key.' [QSA,L]'."\n";

        if ($new_upload_path)
            $output.='RewriteRule ^'.$new_upload_path. '/(.*) /'. $rel_upload_path. '/$1'.$this->trust_key.' [QSA,L]'."\n";

        if ($new_plugin_path && $pre_plugin_path)
            $output.= $pre_plugin_path;

        if ($new_plugin_path)
            $output.='RewriteRule ^'.$new_plugin_path. '/(.*) /'. $rel_plugin_path. '/$1'.$this->trust_key.' [QSA,L]'."\n";

        if ($new_style_name)
            if ($sub_install)
                $output.='RewriteRule ^'.$new_theme_path. '/'.str_replace('.','\.', $new_style_name).' /'.add_query_arg('style_wrapper', '1', $sub_install).str_replace('?','&', $this->trust_key).' [QSA,L]'."\n";
            else
                $output.='RewriteRule ^'.$new_theme_path. '/'.str_replace('.','\.', $new_style_name).' /index.php?style_wrapper=1'.str_replace('?','&', $this->trust_key).' [QSA,L]'."\n";


            if (trim($this->opt('new_style_name'),' /') && trim($this->opt('new_style_name'),' /')!='style.css') {
                $old_style = $new_theme_path . '/' . 'style.css';
                $output .= 'RewriteRule ^' . $old_style . ' /' . $sub_install . 'nothing_404_404' . $this->trust_key . ' [QSA,L]' . "\n";
            }


        if ($new_theme_path)
            $output.='RewriteRule ^'.$new_theme_path. '/(.*) /'. $rel_theme_path. '/$1'.$this->trust_key.' [QSA,L]'."\n";

        if ($replace_comments_post && $replace_comments_post != 'wp-comments-post.php')
            $output.='RewriteRule ^'.$replace_comments_post_rule. ' /'. $rel_comments_post.$this->trust_key.' [QSA,L]'."\n";

        if ($replace_admin_ajax_rule && $replace_admin_ajax_rule != 'wp-admin/admin-ajax.php')
            $output.='RewriteRule ^'.$replace_admin_ajax_rule. ' /'. $rel_admin_ajax.$this->trust_key.' [QSA,L]'."\n";

        if ($new_content_path)
            $output.='RewriteRule ^'.$new_content_path. '/(.*) /'. $rel_content_path. '/$1'.$this->trust_key.' [QSA,L]'."\n";

        if ($this->opt('hide_other_wp_files'))
            $output.='RewriteRule ^('.$hide_other_file_rule. ') /'.$sub_install.'nothing_404_404'.$this->trust_key.' [QSA,L]'."\n";

        if ($this->opt('disable_directory_listing'))
            $output.='RewriteRule ^'.$disable_directoy_listing. ' /'.$sub_install.'nothing_404_404'.$this->trust_key.' [QSA,L]'."\n";

        if ($this->opt('avoid_direct_access'))  {

            //RewriteCond %{REQUEST_URI} !(index\.php|wp-content/repair\.php|wp-includes/js/tinymce/wp-tinymce\.php|wp-comments-post\.php|wp-login\.php|index\.php|wp-admin/)(.*)

            $output.='RewriteCond %{REQUEST_URI} !('.$white_regex.')(.*)'."\n";
            $output.='RewriteRule ^(.*).php(.*)'. ' /nothing_404_404'.$this->trust_key.' [QSA,L]'."\n";
        }

        if (!$output)
            $output=__('Nothing to add for current settings!', self::slug);
        else
            $output="# BEGIN Hide My WP\n\n" . $output ."\n# END Hide My WP";

        $html='';
        $desc = __( 'In rare cases you need to configure it manually.<br>', self::slug ) ;

        if (isset($_GET['single_config']) && $_GET['single_config'])  {
            $html= sprintf( ' %s ', $desc );
            $html.= sprintf( '<span class="description">
        <ol style="color:#ff9900">
             <li> If you use <strong>BulletProof Security</strong> plugin first secure htaccess file using it  and then add below lines to your htaccess file using FTP. </li>
            <li> You may need to re-configure server whenever you change settings or activate a new theme or plugin. </li>
            <li>Add these lines right before: <strong>RewriteCond %{REQUEST_FILENAME} !-f</strong>. Next you may want to change htaccess permission to read-only (e.g. 666)</li>
        </ol></span><textarea readonly="readonly" onclick="" rows="5" cols="55" class="regular-text %1$s" id="%2$s" name="%2$s" style="%4$s">%3$s</textarea>', 'single_config_class','single_config', esc_textarea($output), 'width:95% !important;height:400px !important' );


        }else{
            $html= '<a target="_blank" href="'.add_query_arg(array('die_message'=>'single')).'" class="button">'.__('Manual Configuration', self::slug).'</a>' ;
            $html.= sprintf( '<br><span class="description"> %s</span>', $desc );
        }
        return $html ;
      //rewrite ^/assets/css/(.*)$ /wp-content/themes/roots/assets/css/$1 last;


    }



    function multisite_config(){
        $slashed_home      = trailingslashit( get_option( 'home' ) );
        $base = parse_url( $slashed_home, PHP_URL_PATH );

        $new_theme_path = trim($this->opt('new_theme_path') ,'/ ') ;
        $new_plugin_path = trim($this->opt('new_plugin_path') ,'/ ') ;
        $new_upload_path = trim($this->opt('new_upload_path') ,'/ ') ;
        $new_include_path = trim($this->opt('new_include_path') ,'/ ') ;
        $new_style_name = trim($this->opt('new_style_name') ,'/ ') ;
        $new_content_path = trim($this->opt('new_content_path'), '/ ') ;

        if (trim(get_option('hmwp_temp_admin_path'), ' /'))
            $new_admin_path = trim(get_option('hmwp_temp_admin_path'), ' /');
        else
            $new_admin_path = trim($this->opt('new_admin_path'), '/ ');

        $replace_admin_ajax = trim($this->opt('replace_admin_ajax'), '/ ') ;
        $replace_admin_ajax_rule = str_replace('.','\\.', $replace_admin_ajax) ;
        $replace_comments_post= trim($this->opt('replace_comments_post'), '/ ') ;
        $replace_comments_post_rule = str_replace('.','\\.', $replace_comments_post) ;

        $upload_path=wp_upload_dir();

        if (is_ssl())
                $upload_path['baseurl']= str_replace('http:','https:', $upload_path['baseurl']);

        $rel_upload_path = $this->sub_folder . trim(str_replace(site_url(),'', $upload_path['baseurl']), '/');
        $rel_include_path = $this->sub_folder . trim(WPINC);
        $rel_plugin_path = $this->sub_folder .trim(str_replace(site_url(),'', HMW_WP_PLUGIN_URL), '/');
        $rel_theme_path = $this->sub_folder . trim(str_replace(site_url(),'', get_stylesheet_directory_uri()), '/');
        $rel_comments_post = $this->sub_folder . 'wp-comments-post.php';
        $rel_admin_ajax = $this->sub_folder . 'wp-admin/admin-ajax.php';


        $rel_content_path = $this->sub_folder . trim(str_replace(site_url(),'', HMW_WP_CONTENT_URL), '/');
        $rel_theme_path_no_template = str_replace('/'.get_stylesheet(), '', $rel_theme_path);


        $style_path_reg='';
        //if ($new_style_name && $new_style_name != 'style.css' && !isset($_POST['wp_customize']))
         //   $style_path_reg = '|'.$rel_theme_path.'/style\.css';

        //|'.$rel_plugin_path.'/index\.php|'.$rel_theme_path_no_template.'/index\.php'

		if (!$this->sub_folder && $base && $base!='/')
            $sub_install= trim($base,' /').'/';
        else
            $sub_install='';


        if ($this->is_subdir_mu)
             $hide_other_file_rule = 'readme\.html|'.'license\.txt|'.str_replace($this->sub_folder,'',$rel_content_path).'/debug\.log'. str_replace($this->sub_folder,'',$style_path_reg) .'|'.str_replace($this->sub_folder,'', $rel_include_path).'/$';
        else
             $hide_other_file_rule = $this->sub_folder .'readme\.html|'.$this->sub_folder .'license\.txt|'.$rel_content_path.'/debug\.log'.$style_path_reg.'|'.$rel_include_path.'/$';

        $disable_directoy_listing = '((('.$rel_content_path.'|'.$rel_include_path.')/([A-Za-z0-9\-\_\/]*))|(wp-admin/(!network\/?)([A-Za-z0-9\-\_\/]+)))(\.txt|/)$';

        if ($this->opt('login_query') && $this->opt('login_query'))
            $login_query=  $this->opt('login_query');
        else
            $login_query = 'hide_my_wp';

        $output='';

        if ($this->opt('avoid_direct_access')){
            $rel_theme_path_with_theme = $this->sub_folder . trim(str_replace(site_url(),'', get_stylesheet_directory_uri()), '/');

            $white_list= explode(",", $this->opt('direct_access_except'));
            $white_list[]='wp-login.php';
            $white_list[]='index.php';
            $white_list[]='wp-admin/';

            if ($this->opt('exclude_theme_access'))
                $white_list[]= $rel_theme_path_with_theme.'/';
            if ($this->opt('exclude_plugins_access'))
                $white_list[]= $rel_plugin_path.'/';

            $block = true;
            $white_regex = '';
            foreach ($white_list as $white_file) {
                $white_regex.= $this->sub_folder . str_replace(array('.', ' '), array('\.',''), $white_file ).'|';  //make \. remove spaces
            }
            $white_regex=substr($white_regex, 0 ,strlen($white_regex)-1); //remove last |
            $white_regex = str_replace(array("\n", "\r\n", "\r"), '', $white_regex);
        }

        if ($this->opt('full_hide')){

            $full_hide= "
RewriteCond %{HTTP_COOKIE} !".$this->access_cookie()."=1
RewriteCond %{QUERY_STRING} !".str_replace('?','',$this->trust_key)."
RewriteRule ^((wp-content|wp-includes|wp-admin)/(.*)) ".$this->sub_folder.'/nothing_404_404'.$this->trust_key." [QSA,L]
";

            $output =  $full_hide . $output;
        }

        if ($this->opt('replace_urls')){
            $replace_urls=$this->h->replace_newline(trim($this->opt('replace_urls'),' '),'|');
            $replace_lines=explode('|', $replace_urls);
            if ($replace_lines) {
                foreach ($replace_lines as $line)  {

                    $replace_word = explode('==', $line);
                    if (isset($replace_word[0]) && isset($replace_word[1])) {

                        //Check whether last character is / or not to recgnize folders
                        $is_folder= false;
                        if (substr($replace_word[0], strlen($replace_word[0])-1 , strlen($replace_word[0]))=='/')
                            $is_folder= true;

                        $replace_word[0]=trim($replace_word[0], '/ ');
                        $replace_word[1]=trim($replace_word[1], '/ ');

                        $is_block= false;
                        if ($replace_word[1] == 'nothing_404_404')
                            $is_block= true;


                        if ($is_block){
                            //Swap words to make theme unavailable
                            $temp = $replace_word[0];
                            $replace_word[0] = $replace_word[1];
                            $replace_word[1] = $temp;
                        }

                        $replace_word[0] = str_replace(array( 'amp;', '%2F','//', '.' ), array('', '/', '/','.'), $replace_word[0]);
                        $replace_word[1] = str_replace(array('.','amp;'), array('\.',''), $replace_word[1]);

						if ($this->is_subdir_mu)
							 $sub_install2 =  $sub_install .  $this->sub_folder;
						else
							 $sub_install2 =  $sub_install ;

                        if ($is_folder){

                            $output.='RewriteRule ^'.$replace_word[1]. '/(.*) /'. $sub_install2 . $replace_word[0]. '/$1'.$this->trust_key.' [QSA,L]'."\n";
                        }else{
                            $output.='RewriteRule ^'.$replace_word[1]. ' /'. $sub_install2 . $replace_word[0].$this->trust_key.' [QSA,L]'."\n";
                        }
                    }
                }
            }
        }

	    if ( is_multisite() ){
            $sitewide_plugins = array_keys( (array) get_site_option( 'active_sitewide_plugins', array() ));
            $active_plugins= array_merge((array) get_blog_option(BLOG_ID_CURRENT_SITE, 'active_plugins'), $sitewide_plugins);
        }else{
            $active_plugins = get_option('active_plugins');
        }

        if ($this->opt('rename_plugins')=='all')
             $active_plugins = get_option('hmw_all_plugins');

	$pre_plugin_path='';
        if ($this->opt('rename_plugins') && $new_plugin_path) {
            foreach ((array) $active_plugins as $active_plugin)  {

                //Ignore itself or a plugin without folder
                if ( !$this->h->str_contains($active_plugin,'/') || $active_plugin==self::main_file)
                    continue;

                $new_plugin_path = trim($new_plugin_path, '/ ') ;

                $codename_this_plugin=  $this->hash($active_plugin);

                $rel_this_plugin_path = trim(str_replace(site_url(),'', plugin_dir_url($active_plugin)), '/');
                //Allows space in plugin folder name
                $rel_this_plugin_path = $this->sub_folder . str_replace(' ','\ ', $rel_this_plugin_path);

                $new_this_plugin_path = $new_plugin_path . '/' . $codename_this_plugin ;
                $pre_plugin_path.= 'RewriteRule ^'.$new_this_plugin_path. '/(.*) /'. $rel_this_plugin_path. '/$1'.$this->trust_key.' [QSA,L]'."\n";
            }
        }

        if ($new_admin_path && $new_admin_path!='wp-admin')
            $output.='RewriteRule ^'.$new_admin_path. '/(.*) /'. $this->sub_folder . 'wp-admin/$1'.$this->trust_key.' [QSA,L]'."\n";

        if ($new_include_path)
            $output.='RewriteRule ^'.$new_include_path. '/(.*) /'. $rel_include_path. '/$1'.$this->trust_key.' [QSA,L]'."\n";

        if ($new_upload_path)
            $output.='RewriteRule ^'.$new_upload_path. '/(.*) /'. $rel_upload_path. '/$1'.$this->trust_key.' [QSA,L]'."\n";

        if ($new_plugin_path && $pre_plugin_path)
            $output.= $pre_plugin_path;

        if ($new_plugin_path)
            $output.='RewriteRule ^'.$new_plugin_path. '/(.*) /'. $rel_plugin_path. '/$1'.$this->trust_key.' [QSA,L]'."\n";

        if ($new_style_name)
            $output.='RewriteRule ^'.$new_theme_path. '/([_0-9a-zA-Z-]+)/'.$new_style_name.' /'.$this->sub_folder.'index.php?style_wrapper=true&template_wrapper=$1'.str_replace('?','&', $this->trust_key).' [QSA,L]'."\n";

        if (trim($this->opt('new_style_name'),' /') && trim($this->opt('new_style_name'),' /')!='style.css') {

            if ($this->is_subdir_mu)
                $output.='RewriteRule ^'.$new_theme_path. '/([_0-9a-zA-Z-]+)/style\.css /'.$this->sub_folder.'nothing_404_404'.$this->trust_key.' [QSA,L]'."\n";
            else
                $output.='RewriteRule ^'.$new_theme_path. '/([_0-9a-zA-Z-]+)/style\.css /nothing_404_404'.$this->trust_key.' [QSA,L]'."\n";

        }

        if ($new_theme_path)
            $output.='RewriteRule ^'.$new_theme_path. '/(.*) /'. str_replace('/'.get_stylesheet(), '', $rel_theme_path). '/$1'.$this->trust_key.' [QSA,L]'."\n";

        if ($replace_comments_post && $replace_comments_post != 'wp-comments-post.php')
            if ($this->is_subdir_mu)
                $output.='RewriteRule ^([_0-9a-zA-Z-]+/)?'.$replace_comments_post_rule. ' /'. $rel_comments_post. $this->trust_key.' [QSA,L]'."\n";
            else
                $output.='RewriteRule ^'.$replace_comments_post_rule. ' /'. $rel_comments_post.$this->trust_key.' [QSA,L]'."\n";


        if ($replace_admin_ajax_rule && $replace_admin_ajax_rule != 'wp-admin/admin-ajax.php') {
 	        if ($this->is_subdir_mu)
            	$output.='RewriteRule ^([_0-9a-zA-Z-]+/)?'.$replace_admin_ajax_rule. ' /'. $rel_admin_ajax.$this->trust_key.' [QSA,L]'."\n";
	        else
		        $output.='RewriteRule ^'.$replace_admin_ajax_rule. ' /'. $rel_admin_ajax.$this->trust_key.' [QSA,L]'."\n";
        }

        if ($new_content_path)
            $output.='RewriteRule ^'.$new_content_path. '/(.*) /'. $rel_content_path. '/$1'.$this->trust_key.' [QSA,L]'."\n";

        if ($this->opt('hide_other_wp_files'))
	        if ($this->is_subdir_mu)
            	$output.='RewriteRule ^('.$hide_other_file_rule. ') /'.$this->sub_folder.'nothing_404_404'.$this->trust_key.' [QSA,L]'."\n";
            else
		        $output.='RewriteRule ^('.$hide_other_file_rule. ') /nothing_404_404'.$this->trust_key.' [QSA,L]'."\n";

        if ($this->opt('disable_directory_listing') )
            if ($this->is_subdir_mu)
                $output.='RewriteRule ^'.$disable_directoy_listing. ' /'.$this->sub_folder.'nothing_404_404'.$this->trust_key.' [QSA,L]'."\n";
            else
                $output.='RewriteRule ^'.$disable_directoy_listing. ' /nothing_404_404'.$this->trust_key.' [QSA,L]'."\n";

        if ($this->opt('avoid_direct_access'))  {

            //RewriteCond %{REQUEST_URI} !(index\.php|wp-content/repair\.php|wp-includes/js/tinymce/wp-tinymce\.php|wp-comments-post\.php|wp-login\.php|index\.php|wp-admin/)(.*)

	    if ($this->is_subdir_mu) {
 	        $output.='RewriteCond %{REQUEST_URI} !('. str_replace($this->sub_folder,'',$white_regex) .')(.*)'."\n";
                $output.='RewriteRule ^(.*).php(.*)'. ' /'.$this->sub_folder.'nothing_404_404'.$this->trust_key.' [QSA,L]'."\n";
	    }else{
                $output.='RewriteCond %{REQUEST_URI} !('.$white_regex.')(.*)'."\n";
                $output.='RewriteRule ^(.*).php(.*)'. ' /nothing_404_404'.$this->trust_key.' [QSA,L]'."\n";
	    }
        }

        if (!$output)
            $output=__('Nothing to add for current settings!', self::slug);
        else
            $output="# BEGIN Hide My WP\n\n" . $output ."\n# END Hide My WP";

        $html='';
        $desc = __( 'Add following lines to your .htaccess file to get all features of the plugin.<br>', self::slug ) ;
        if (isset($_GET['multisite_config']) && $_GET['multisite_config'])  {

            $html= sprintf( '%s ', $desc );
            $html.= sprintf( '<span class="description">
            <ol style="color:#ff9900">
            <li>Add below lines right before <strong>RewriteCond %{REQUEST_FILENAME} !-f [OR]</strong> </li>
            <li>You may need to re-configure the server whenever you change settings or activate a new plugin.</li> </ol></span>.
        <textarea readonly="readonly" onclick="" rows="5" cols="55" class="regular-text %1$s" id="%2$s" name="%2$s" style="%4$s">%3$s</textarea>', 'multisite_config_class','multisite_config', esc_textarea($output), 'width:95% !important;height:400px !important' );


        }else{
            $html= '<a target="_blank" href="'.add_query_arg(array('die_message'=>'multisite')).'" class="button">'.__('Multi-site Configuration', self::slug).'</a>' ;
            $html.= sprintf( '<br><span class="description"> %s</span>', $desc );
        }
        return $html;
      //rewrite ^/assets/css/(.*)$ /wp-content/themes/roots/assets/css/$1 last;
    }


    /**
	 * Register settings page
	 *
	 */
	/**
	 * HideMyWP::register_settings()
	 *
	 * @return
	 */
	function register_settings() {
	   require_once('admin-settings.php');
    }

    function load_this_plugin_first() {
        // ensure path to this file is via main wp plugin path
        $wp_path_to_this_file = preg_replace('/(.*)plugins\/(.*)$/', WP_PLUGIN_DIR."/$2", __FILE__);
        $this_plugin = plugin_basename(trim($wp_path_to_this_file));
        if (is_multisite()){
            global $current_blog;
            $active_plugins= array_keys(get_site_option( 'active_sitewide_plugins', array()));
            $codes =  array_values(get_site_option( 'active_sitewide_plugins', array()));
        }else{
            $active_plugins = get_option('active_plugins', array());
        }

        $this_plugin_key = array_search($this_plugin, $active_plugins);

        if (in_array($this_plugin, $active_plugins) && $active_plugins[0] != $this_plugin ) {
            array_splice($active_plugins, $this_plugin_key, 1);
            array_unshift($active_plugins, $this_plugin);
            if (is_multisite()) {
                $this_plugin_code = $codes[$this_plugin_key];
                array_splice($codes, $this_plugin_key, 1);
                array_unshift($codes, $this_plugin_code);

                update_site_option('active_sitewide_plugins', array_combine($active_plugins,$codes));
            }else {
                update_option('active_plugins', $active_plugins);
            }

        }

    }
}

$HideMyWP = new HideMyWP();
;
/**
 *  Open wp-content/plugins/w3-total-cache/inc/define.php using FTP, your host file manager or WP plugin editor and rename 'w3_normalize_file_minify' function to something else. Replace:
 * function w3_normalize_file_minify($file) {
 * with:
 * function w3_normalize_file_minify0($file) {
 */

function fix_w3tc_hmwp(){
    if (defined('W3TC') && !function_exists('w3_normalize_file_minify')) {
        function w3_normalize_file_minify($file){
            global $wp_rewrite;

            $hmwp= new HideMyWP();
            $hmwp->init();
            $hmwp->add_rewrite_rules($wp_rewrite);
            $file = $hmwp->reverse_partial_filter($file);

            if (w3_is_url($file)) {
                if (strstr($file, '?') === false) {
                    $domain_url_regexp = '~' . w3_get_domain_url_regexp() . '~i';
                    $file = preg_replace($domain_url_regexp, '', $file);
                }
            }

            if (!w3_is_url($file)) {
                $file = w3_path($file);
                $file = str_replace(w3_get_document_root(), '', $file);
                $file = ltrim($file, '/');
            }

            return $file;
        }




    }
}
add_action('init', 'fix_w3tc_hmwp', 1000);




?>