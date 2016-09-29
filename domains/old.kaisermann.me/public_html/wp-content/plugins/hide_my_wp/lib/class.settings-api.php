<?php

/**
 * By Hassan Jahangiri (http://wpwave.com)
 * Mainly from weDevs Settings API wrapper class by Tareq Hasan <tareq@weDevs.com> (http://tareq.weDevs.com)  
 */



class PP_Settings_API {

    /**
     * settings sections array
     *
     * @var array
     */
    private $settings_sections = array();

    /**
     * Settings fields array
     *
     * @var array
     */
    private $settings_fields = array();

    /**
     * Settings fields array
     *
     * @var array
     */
    private $settings_menu = array();
    
    /**
     * Singleton instance
     *
     * @var object
     */
    private static $_instance;
    

    public function __construct($fields, $sections, $menu='') {
        //set sections and fields
        //if (!is_admin())
        //    return;
            
        $this->set_sections( $sections );
        $this->set_fields( $fields );
        
        if ($menu)  {
            $this->set_menu($menu);
            
            if ($menu['multisite_only'])
                add_action( 'network_admin_menu',  array(&$this, 'register_menu') );  
            else
                add_action( 'admin_menu',  array(&$this, 'register_menu') );  
        }
        
        
        add_action( 'init',  array(&$this, 'filter_settings') );  
        
        //$this->admin_init();
        //$this->register_menu();
        
        if ($this->settings_menu['action_link'])
            add_filter( 'plugin_action_links_'.$this->settings_menu['plugin_file'], array(&$this, 'plugin_actions_links'), -10);
                                                                    
        add_action( 'admin_init', array(&$this, 'admin_init') );
        
        
    }

    public function register_menu() {
        $role= ($this->settings_menu['role']) ? $this->settings_menu['role'] :  'manage_options';
        if ($this->settings_menu['multisite_only']) 
            add_submenu_page( 'settings.php', $this->settings_menu['title'],  $this->settings_menu['title'], $role, $this->settings_menu['name'], array(&$this, 'render_option_page') );
        else
            add_options_page($this->settings_menu['title'],  $this->settings_menu['title'], $role, $this->settings_menu['name'], array(&$this, 'render_option_page'));
    }
    
    public function filter_settings(){
        $options_file = (is_multisite()) ? 'network/settings.php' : 'options-general.php';
        $page_url = admin_url(add_query_arg('page', $this->settings_menu['name'], $options_file));

        $can_deactive= false;
        if (isset($_COOKIE['hmwp_can_deactivate']) && preg_replace("/[^a-zA-Z]/", "", substr(NONCE_SALT, 0, 8)) == preg_replace("/[^a-zA-Z]/", "",$_COOKIE['hmwp_can_deactivate']))
            $can_deactive= true;

        if ($can_deactive && is_admin()  && isset($_POST['action']) && $_POST['action']=='update' && isset($_POST['option_page']) && $_POST['option_page']==$this->settings_menu['name']) {

            //to fix problem with default on checkbox
            $def_keys = array_keys( $this->get_defaults() );
            if (is_array($def_keys))
                foreach ($def_keys as $key)
                    if (!isset($_POST[$this->settings_menu['name']][$key]))
                        $_POST[$this->settings_menu['name']][$key]='';

            $_POST = apply_filters('pp_settings_api_filter', $_POST);
            
            if (isset($_POST['import_field']) && $_POST['import_field'] && check_admin_referer( $this->settings_menu['name'] . '-options' ) ) {
                //delete_option( $this->settings_menu['name'] );
                $new_settings = stripslashes($_POST['import_field']);
                $new_settings = json_decode($new_settings, true);
                $new_settings = str_replace('[new_line]', "\r\n", $new_settings);
                $new_settings = str_replace('[double_slashes]', "\/", $new_settings);
                $new_settings = str_replace('[quotation]','"', $new_settings);
                $new_settings = str_replace('[o_cb]','{', $new_settings);
                $new_settings = str_replace('[c_cb]','}', $new_settings);
              
                update_option($this->settings_menu['name'], $new_settings);
                
                // 	if ( !count( get_settings_errors() ) )
                //	add_settings_error('general', 'settings_imported', __('Settings Imported.'), 'updated');
                //	set_transient('settings_errors', get_settings_errors(), 30);
            
            	/**
            	 * Redirect back to the settings page that was submitted
            	 */
                 $goback = add_query_arg( array('settings-imported'=>'true'),  $page_url );
                 wp_redirect( $goback );
       	         exit;
            	
                
            }
            
            if (isset ($_POST[$this->settings_menu['name']]['li']) && $_POST[$this->settings_menu['name']]['li'] && (strlen($_POST[$this->settings_menu['name']]['li']) <= 35 || strlen($_POST[$this->settings_menu['name']]['li']) > 40)) {
                $goback = add_query_arg( array('wrong-number'=>'true'), $page_url );
                wp_redirect( $goback );
       	        exit;
            }
            
            //check to see if the options were reset
            if ( isset ( $_POST['reset-defaults'] ) && check_admin_referer( $this->settings_menu['name'] . '-options' )) {

               // foreach ($this->settings_sections as $section)
               delete_option( $this->settings_menu['name'] );
               $this->update_defaults();

               do_action('pp_settings_api_reset');

               $goback = add_query_arg( array('settings-reseted'=>'true'),  $page_url );
               wp_redirect( $goback );
       	       exit;
            }
            

            $clean_options_page = remove_query_arg(array('settings-reseted', 'wrong-number', 'settings-imported' ) , $page_url);
            
            $_SERVER['HTTP_REFERER'] = $clean_options_page;
            $_REQUEST['_wp_original_http_referer'] = $clean_options_page;
            $_REQUEST['_wp_http_referer'] = $clean_options_page;
        }
    }
    
    /**
     * Display the plugin settings options page
     */
    public function render_option_page() {
        
        if ($this->settings_menu['template_file']) {
            include_once($this->settings_menu['template_file']);
            
        }else {            
            echo '<div class="wrap settings_api_class_page" id="'.$this->settings_menu['name'].'_settings" >';
            $icon='';
            if (isset($this->settings_menu['icon_path']) && $this->settings_menu['icon_path'])
                $icon= ' style="background: url('.$this->settings_menu['icon_path'].') no-repeat ;" ';
                
            echo '<div id="icon-options-general" class="icon32" '.$icon.'><br /></div>';
            
            echo '<h2>'. $this->settings_menu['title'] .'</h2>';
            
            //echo '<br />';
            //settings_errors();
            if (isset($_GET['settings-reseted']) && $_GET['settings-reseted'])
                echo '<div class="updated fade"><p><strong>'.__('Settings was rested successfully!', $this->settings_menu['name']).'</p></strong></div>' ;
                
            if (isset($_GET['settings-imported']) && $_GET['settings-imported'])
                echo '<div class="updated fade"><p><strong>'.__('Settings was imported successfully!', $this->settings_menu['name']).'</p></strong></div>';
                
            if (isset($_GET['wrong-number']) && $_GET['wrong-number'])
                echo '<div class="error fade"><p><strong>'.__('Purchase code is invalid :-|<br> Read <a target="_blank" href="http://wpwave.com/envato/purchase_code_1200.png">Help</a> or check out <a href="http://codecanyon.net/item/hide-my-wp-no-one-can-know-you-use-wordpress/4177158" target="_blank">Plugin page</a>.', $this->settings_menu['name']).'</strong></p></div>';
            
            do_action('pp_settings_api_header', $this->settings_menu);
            

            $this->show_navigation();
            $this->show_forms();  
        
            do_action('pp_settings_api_footer', $this->settings_menu);
        
            echo '</div>';
        }
    }
    public function plugin_actions_links($links) {
        if ($this->settings_menu['action_link'])
            $links[] = '<a href="'.admin_url("options-general.php?page=".$this->settings_menu['name']).'" >'.
            $this->settings_menu['action_link'].'</a>';
          return $links;
    }
    
    public static function getInstance() {
        if ( !self::$_instance ) {
            self::$_instance = new WeDevs_Settings_API();
        }

        return self::$_instance;
    }

    /**
     * Set settings sections
     *
     * @param array $sections setting sections array
     */
    function set_sections( $sections ) {
        $this->settings_sections = $sections;
    }

    /**
     * Set settings fields
     *
     * @param array $fields settings fields array
     */
    function set_fields( $fields ) {
        $this->settings_fields = $fields;
    }

    /**
     * Set settings fields
     *
     * @param array $fields settings fields array
     */
    function set_menu( $menu ) {
        $this->settings_menu = $menu;
    }

    /**
     * Initialize and registers the settings sections and fileds to WordPress
     *
     * Usually this should be called at `admin_init` hook.
     *
     * This function gets the initiated settings sections and fields. Then
     * registers them to WordPress and ready for use.
     */
    public function admin_init() {
        //Disable Drag
        if (isset($_GET['page']) && $_GET['page']==$this->settings_menu['name'])
            wp_deregister_script('postbox');

        if ( !get_option( $this->settings_menu['name'] ) || !$this->get_option('db_ver') || $this->get_option('db_ver') < $this->settings_menu['version'] )
           $this->update_defaults();
            //add_option( $this->settings_menu['name'] );
        
            
        //register settings sections
        foreach ($this->settings_sections as $section) {
            

            add_settings_section( $section['id'], $section['title'], '__return_false', $this->settings_menu['name'] );
        }

        //register settings fields
        foreach ($this->settings_fields as $section => $field) {
            foreach ($field as $option) {
                $args = array(
                    'id' => $option['name'],
                    'desc' => $option['desc'],
                    'name' => $option['label'],
                    'section' => $section,
                    'class' => isset( $option['class'] ) ? $option['class'] : null,
                    'options' => isset( $option['options'] ) ? $option['options'] : '',
                    'std' => isset( $option['default'] ) ? $option['default'] : ''
                );
                add_settings_field( $option['name'] , $option['label'], array($this, 'callback_' . $option['type']), $this->settings_menu['name'], $section, $args );  
            }
        }

        // creates our settings in the options table
        //foreach ($this->settings_sections as $section) {
            register_setting( $this->settings_menu['name'], $this->settings_menu['name'], array (&$this, 'admin_settings_validate') );
        //}
        
        
       
    }
    // validate our settings
    
    function admin_settings_validate($input) {
        
         do_action('pp_settings_api_validate', $input);
         
        
        
//      if (empty($input['sample_text'])) {
//
//                add_settings_error(
//                    'sample_text',           // setting title
//                    'sample_text_error',            // error ID
//                    'Please enter some sample text',   // error message
//                    'error'                        // type of message
//                );
//
//       }
         return $input;
    }
    
    
    /**
     * Displays a text field for a settings field
     *
     * @param array $args settings field args
     */
    function callback_text( $args ) {

        $value = esc_attr( $this->get_option( $args['id'], $this->settings_menu['name'] ) );
       // $class = isset( $args['class'] ) && !is_null( $args['class'] ) ? $args['class'] : 'regular';
        
        $html = sprintf( '<input type="text" class="regular-text %1$s" id="%4$s" name="%2$s[%4$s]" value="%5$s"/>', $args['class'], $this->settings_menu['name'], $args['section'],$args['id'], $value );
        $html .= sprintf( '<span class="description"> %s</span>', $args['desc'] );

        echo $html;
    }

    function callback_number( $args ) {

        $value = esc_attr( $this->get_option( $args['id'], $this->settings_menu['name'] ) );
        // $class = isset( $args['class'] ) && !is_null( $args['class'] ) ? $args['class'] : 'regular';

        $html = sprintf( '<input type="text" class="regular-number %1$s" id="%4$s" name="%2$s[%4$s]" value="%5$s" style="width:50px;"/>', $args['class'], $this->settings_menu['name'], $args['section'],$args['id'], $value );
        $html .= sprintf( '<span class="description"> %s</span>', $args['desc'] );

        echo $html;
    }

    function callback_hidden( $args ) {
        echo '</td></tr><tr valign="top" style="display:none;"><td colspan="2"><span class="'.$args['class'].'">' . $args['desc'].'</span>';
    }

    function callback_html( $args ) {
              echo '</td></tr><tr valign="top"><td colspan="2"><span class="'.$args['class'].'">' . $args['desc'].'</span>';
    }
    
    function callback_custom( $args ) {
              echo '<div class="'.$args['class'].'">' . $args['desc'].'</div>';
    }
    
    function callback_wp_editor($args) {
       // $value = esc_attr( $this->get_option( $args['id'], $this->settings_menu['name'], $args['std'] ) );
        
        $value =  $this->get_option( $args['id'], $this->settings_menu['name'] ) ;
         
        echo wp_editor( $value, $this->settings_menu['name'].'_'.$args['id'] , array( 'textarea_name' => $this->settings_menu['name'] . '[' . $args['id'] . ']', 'textarea_rows' => '5','wpautop'=>false, 'dfw' => false, 'media_buttons' => true, 'quicktags' => true, 'tinymce' => true,'editor_class'=> $args['class'], 'teeny' => false  ) );
        echo sprintf( '<span class="description"> %s</span>', $args['desc'] );
    }
    
    function callback_file($args){
        $value = esc_attr( $this->get_option( $args['id'], $this->settings_menu['name'] ) );
        
        $html = sprintf( '<input type="text" class="regular-text image-upload-url %1$s" id="%3$s" name="%2$s[%3$s]" value="%4$s" />', $args['class'], $this->settings_menu['name'], $args['id'], $value );
        $html .= sprintf( '<input id="st_upload_button" class="image-upload-button" type="button" name="upload_button" value="%s" />', __('Select', $this->settings_menu['name']) );
              
        $html .= sprintf( '<span class="description"> %s</span>', $args['desc'] );
        
        echo $html;
    }
    
    /**
     * Displays a checkbox for a settings field
     *
     * @param array $args settings field args
     */
    function callback_checkbox( $args ) {

        $value = esc_attr( $this->get_option( $args['id'], $this->settings_menu['name'] ) );

        $html = sprintf( '<input type="checkbox" class="checkbox %1$s" id="%3$s" name="%2$s[%3$s]" value="on"%4$s/>', $args['class'],  $this->settings_menu['name'], $args['id'], checked( 'on', $value, false ) );
        $html .= sprintf( '<label for="%2$s"> %3$s</label>', $this->settings_menu['name'], $args['id'], $args['desc'] );

        echo $html;
    }

    /**
     * Displays a multicheckbox a settings field
     *
     * @param array $args settings field args
     */
    function callback_multicheck( $args ) {

        $value = $this->get_option( $args['id'], $this->settings_menu['name'] );

        if (!$args['options'])
            return;
        //option name should not be 0 to work correctly with empty option    
        $html = '';
        foreach ($args['options'] as $key => $label) {
            $checked = isset( $value[$key] ) ? $value[$key] : '0';
            $html .= sprintf( '<input type="checkbox" class="checkbox %1$s" id="%3$s_%4$s" name="%2$s[%3$s][%4$s]" value="%4$s"%5$s />',$args['class'], $this->settings_menu['name'], $args['id'], $key, checked( $checked, $key, false ) );
            $html .= sprintf( '<label for="%2$s_%4$s"> %3$s</label><br>',$this->settings_menu['name'], $args['id'], $label, $key );
        }
        $html .= sprintf( '<span class="description"> %s</label>', $args['desc'] );
        
        echo $html;
    }

    /**
     * Displays a multicheckbox a settings field
     *
     * @param array $args settings field args
     */
    function callback_radio( $args ) {

        $value = $this->get_option( $args['id'], $this->settings_menu['name'] );

        $html = '';
        foreach ($args['options'] as $key => $label) {
            $html .= sprintf( '<input type="radio" class="radio %1$s" id="%3$s_%4$s" name="%2$s[%3$s]" value="%4$s"%5$s />', $args['class'], $this->settings_menu['name'], $args['id'], $key, checked( $value, $key, false ) );
            $html .= sprintf( '<label for="%2$s_%4$s"> %3$s</label><br>', $this->settings_menu['name'], $args['id'], $label, $key );
        }
        $html .= sprintf( '<span class="description"> %s</label>', $args['desc'] );

        echo $html;
    }

    /**
     * Displays a selectbox for a settings field
     *
     * @param array $args settings field args
     */
    function callback_select( $args ) {


        $value = esc_attr( $this->get_option( $args['id'], $this->settings_menu['name'] ) );
        //$class = isset( $args['class'] ) && !is_null( $args['class'] ) ? $args['class'] : 'regular';
        
        $html = sprintf( '<select class="regular pages_list selectbox %1$s" name="%2$s[%3$s]" id="%3$s">', $args['class'], $this->settings_menu['name'], $args['id'] );
        foreach ($args['options'] as $key => $label) {
            $html .= sprintf( '<option value="%s"%s>%s</option>', $key, selected( $value, $key, false ), $label );
        }
        $html .= sprintf( '</select>' );
        $html .= sprintf( '<span class="description"> %s</span>', $args['desc'] );

        echo $html;
    }
    
        /**
     * Displays a selectbox for a settings field
     *
     * @param array $args settings field args
     */
    function callback_rolelist( $args ) {
        global $wp_roles;
       
        if ($wp_roles->roles)
            foreach ($wp_roles->roles as $key=>$val)
                if ($key!='administrator')
                    $args['options'][$key]=$wp_roles->roles[$key]['name'];
              
        $value = $this->get_option( $args['id'], $this->settings_menu['name'] );
        $html = '';
        foreach ($args['options'] as $key => $label) {
            $checked = isset( $value[$key] ) ? $value[$key] : '0';
            
            $html .= sprintf( '<input type="checkbox" class="checkbox user_roles_checkbox %1$s" id="%3$s_%4$s" name="%2$s[%3$s][%4$s]" value="%4$s"%5$s />',$args['class'], $this->settings_menu['name'], $args['id'], $key, checked( $checked, $key, false ) );
            $html .= sprintf( '<label for="%2$s_%4$s"> %3$s</label><br>',$this->settings_menu['name'], $args['id'], $label, $key );
        }
        $html .= sprintf( '<span class="description"> %s</label>', $args['desc'] );

        echo $html;      
    }
    
    function callback_pagelist( $args ) {
        $value = esc_attr( $this->get_option( $args['id'], $this->settings_menu['name'] ) );
        $name=sprintf('%1$s[%2$s]', $this->settings_menu['name'], $args['id']);
        
    	$q = array(
    		'depth' => 0, 'child_of' => 0,
    		'selected' => $value, 'echo' => 0,
    		'name' => $name, 'id' => $args['id'],
    		'show_option_none' => '', 'show_option_no_change' => '',
    		'option_none_value' => ''
    	);
        
        $html = wp_dropdown_pages($q );     
        $html = str_replace('<select','<select class="'.$args['class'].'" ', $html ) ; 

        $html .= sprintf( '<span class="description"> %s</span>', $args['desc'] );
        echo $html;
    }
    
    
    /**
     * Displays a textarea for a settings field
     *
     * @param array $args settings field args
     */
    function callback_textarea( $args ) {

        $value = esc_textarea( $this->get_option( $args['id'], $this->settings_menu['name'] ) );
        //$class = isset( $args['class'] ) && !is_null( $args['class'] ) ? $args['class'] : 'regular';
             
        $html = sprintf( '<textarea rows="5" cols="65" class="regular-text %1$s" id="%3$s" name="%2$s[%3$s]">%4$s</textarea>', $args['class'], $this->settings_menu['name'], $args['id'], $value );
        $html .= sprintf( '<br><span class="description"> %s</span>', $args['desc'] );
                                                  
        echo $html;         
    }
    
    
    function callback_export( $args ) {
        
        if (isset($_GET['export_settings']) && $_GET['export_settings'])  {
            
            $empty_keys = array_keys(array_diff_key( $this->get_defaults(), get_option($this->settings_menu['name']))); 
            $empty_keys = array_fill_keys($empty_keys, '');
            $value = get_option($this->settings_menu['name']);
            $value = str_replace(array("\r\n","\n","\r"), '[new_line]', $value);
            $value = str_replace( "\/", '[double_slashes]', $value);

            $value = str_replace( '"', '[quotation]', $value);
            $value = str_replace('{', '[o_cb]', $value);
            $value = str_replace('}', '[c_cb]', $value);
            //$value = str_replace('[', '[o_b]', $value);
          //  $value = str_replace(']', '[c_b]', $value);

            $value = esc_textarea(stripslashes(json_encode( array_merge($value, $empty_keys)    ) ));
            
            
            
            //$class = isset( $args['class'] ) && !is_null( $args['class'] ) ? $args['class'] : 'regular';
            $html = sprintf( '<strong> %s </strong><br/>', $args['desc'] );
            $html .= sprintf( '<textarea readonly="readonly" onclick="this.focus();this.select()" rows="5" cols="65" class="regular-text %1$s" id="%2$s" name="%2$s" style="%4$s">%3$s</textarea>', $args['class'],'export_field', $value, 'width:95% !important;height:400px !important' );
            
                                                      
            echo $html;
        }else{
            echo '<a href="'.add_query_arg(array('export_settings'=>true)).'" class="button">'.__('Export Current Settings', $this->settings_menu['name']).'</a>' ;
            echo sprintf( '<br><span class="description"> %s</span>', $args['desc'] );
        }
    }
    
    function callback_import( $args ) {
        $html='';
        
        if ($args['options']) {
            $html .= sprintf( '<select class="regular selectbox %1$s" name="import_options" id="%3$s">', $args['class'], $this->settings_menu['name'], $args['id'] );
        
            $html .= sprintf( '<option value="" selected="selected">- Select Scheme -</option>' );
            foreach ($args['options'] as $key => $settings_value) 
                $html .= sprintf( '<option value="%s">%s</option>', esc_textarea(stripslashes($settings_value)), ucfirst($key) );
        
        
            $html .= sprintf( '</select>' );
            $html .='<br>';            
        }
        
        $html .= sprintf( '<span class="description">%s</span>', $args['desc'] );
        $html .='<br>';
            
        $value = '';
        //$class = isset( $args['class'] ) && !is_null( $args['class'] ) ? $args['class'] : 'regular';

        $html .= sprintf( '<textarea rows="5" cols="65" class="regular-text %1$s" id="%2$s" name="%2$s">%3$s</textarea>', $args['class'],'import_field', $value );
       // $html .= sprintf( '<br><span class="description"> %s</span>', $args['desc'] );
                                                  
        echo $html;
    }
    
    function callback_debug_report( $args ) {
        global $wp_version;
        
        if (!isset($_GET['debug_report']) )  {                                          
            echo '<a href="'. add_query_arg(array('debug_report'=>true)) . '" class="button">'.__('Generate Debug Report', $this->settings_menu['name']).'</a>' ;
            echo sprintf( '<br><span class="description"> %s</span>', $args['desc'] );
        }else{
            
            /* Get from WooCommerce by WooThemes http://woothemes.com  */
            $active_plugins = (array) get_option( 'active_plugins', array() );
            if ( is_multisite() )
            	$active_plugins = array_merge( $active_plugins, get_site_option( 'active_sitewide_plugins', array() ) );
            
            $active_plugins = array_map( 'strtolower', $active_plugins );
            $pp_plugins = array();
            
            foreach ( $active_plugins as $plugin ) {
            		$plugin_data = @get_plugin_data( WP_PLUGIN_DIR . '/' . $plugin );
            		if ( ! empty( $plugin_data['Name'] ) ) {
            			$pp_plugins[] = $plugin_data['Name'] .' '. $plugin_data['Version']. ' [' . $plugin_data['PluginURI'] . "]";
            		}
            }
            
            if ( $pp_plugins ) 
                $plugin_list=implode( "\n", $pp_plugins );
                            
             
            $wp_info= ( is_multisite() ) ? 'WPMU ' . $wp_version :  'WP ' . $wp_version; 
            $wp_debug = ( defined('WP_DEBUG') && WP_DEBUG ) ? 'true' : 'false';
            $is_ssl = ( is_ssl() ) ? 'true' : 'false';
            $is_rtl = ( is_rtl() ) ? 'true' : 'false';
            $fsockopen = ( function_exists( 'fsockopen' ) ) ? 'true' : 'false';
            $curl = ( function_exists( 'curl_init' ) ) ? 'true' : 'false';
			$max_upload_size = (function_exists('size_format')) ? size_format(wp_max_upload_size()) : wp_convert_bytes_to_hr( wp_max_upload_size() );
			
            if ( function_exists( 'phpversion' ) ) { 
                $php_info=  phpversion();
                $max_server_upload= ini_get('upload_max_filesize');
                $post_max_size= ini_get('post_max_size') ;
            }
    
            $empty_keys = array_keys(array_diff_key( $this->get_defaults(), get_option($this->settings_menu['name'])));
            $empty_keys = array_fill_keys($empty_keys, '');
                        
            $value = '
===========================================================
 WP Settings
===========================================================
WordPress version: 	'.$wp_info.'
Home URL: 	'. home_url(). '
Site URL: 	'. site_url(). '
Is SSL: 	'. $is_ssl.'
Is RTL: 	'. $is_rtl.'                                          
Permalink: 	'. get_option('permalink_structure'). '

============================================================
 Server Environment
============================================================
PHP Version:     	'. $php_info .'
Server Software: 	'. $_SERVER['SERVER_SOFTWARE'].'
WP Max Upload Size: '. $max_upload_size.'
Server upload_max_filesize:     '.$max_server_upload.'
Server post_max_size: 	'.$post_max_size.'
WP Memory Limit: 	'. WP_MEMORY_LIMIT .'
WP Debug Mode: 	    '. $wp_debug.'
CURL:               '. $curl.'
fsockopen:          '. $fsockopen.'

============================================================
 Active plugins   
============================================================
'.$plugin_list.'

============================================================
 Plugin Option
============================================================
'. esc_textarea(stripslashes(json_encode(   array_merge( get_option($this->settings_menu['name']), $empty_keys )  ) )) .'
    ';
            
    
            $html = sprintf( '<textarea readonly="readonly" rows="5" cols="65" style="%4$s" class="%1$s" id="%2$s" name="%2$s">%3$s</textarea>', $args['class'],'debug_report', $value , 'width:95% !important;height:400px !important');
            $html .= sprintf( '<br><span class="description"> %s</span>', $args['desc'] );
            
            echo $html;
        }   
           
    }
    


    /**
     * Get the value of a settings field
     *
     * @param string $option settings field name
     * @param string $option_page the $option_page name this field belongs to
     * @param string $default default text if it's not found
     * @return string
     */
    function get_option( $option, $option_page='', $_disabled_default = '' ) {
        if(! $option_page)
            $option_page=$this->settings_menu['name'];

        $options = $this->get_options( $option_page );
        
        if ( isset( $options[$option] ) ) 
            return $options[$option];
        
        return false;
    }
    
    public function get_defaults(){
        $defaults_val='';
        foreach ($this->settings_fields  as $tabs => $field) {
            foreach ($field as $opt){
                if (isset($opt['name']))  {
                    if (isset($opt['default'])) {
                        $temp = str_replace('[new_line]', "\r\n", $opt['default']);
                        $temp = str_replace('[double_slashes]', "\/", $temp);
                        $temp = str_replace('[o_cb]','{', $temp);
                        $temp = str_replace('[c_cb]','}', $temp);
                    
                        $defaults_val[$opt['name']] = str_replace('[quotation]','"', $temp);

                    }else {
                        $defaults_val[$opt['name']] = '';
                    }
                }
            }
        }
        return $defaults_val;
    }

    public function update_options($main_key, $options){

        if (is_multisite())
            update_blog_option(BLOG_ID_CURRENT_SITE, $main_key, $options);
        else
            update_option($main_key, $options);
    }

    public function get_options(){
        $main_key=$this->settings_menu['name'];

        if (is_multisite())
            $current_options = get_blog_option(BLOG_ID_CURRENT_SITE, $main_key);
        else
            $current_options = get_option($main_key);

        return $current_options;

    }

    public function update_defaults(){
        $defaults_options=$this->get_defaults();

        $main_key=$this->settings_menu['name'];

        $prev_options =$this->get_options();


        // Do previous options exist? Merge them, this way we keep existing options
        // and if an update adds new options they get added too.
        if ( is_array( $prev_options ) ) {
            $options = array_merge( $defaults_options, $prev_options );
        }else{
            $options=$defaults_options;
        }


        $options['db_ver'] = $this->settings_menu['version'];

        $this->update_options($main_key, $options);

    }


    /**
     * Show navigations as tab
     *
     * Shows all the settings section labels as tab
     */
    function show_navigation() {
        $html = '<h2 class="nav-tab-wrapper">';

        foreach ($this->settings_sections as $tab) {
            $html .= sprintf( '<a href="#%1$s" class="nav-tab" style="font-size:18px;" id="%1$s-tab">%2$s</a>', $tab['id'], $tab['title'] );
        }

        $html .= '</h2>';

        echo $html;          
    }
    function do_settings_sections_for_tab($page, $sections) {
        global $wp_settings_sections, $wp_settings_fields;

        if ( !isset($wp_settings_sections) || !isset($wp_settings_sections[$page]) )
            return;
         
        foreach ( (array) $wp_settings_sections[$page] as $section ) {
            if (in_array($section['id'], $sections)) {
                echo "<h3>{$section['title']}</h3>\n";
                call_user_func($section['callback'], $section);
                if ( !isset($wp_settings_fields) || !isset($wp_settings_fields[$page]) || !isset($wp_settings_fields[$page][$section['id']]) )
                    continue;
                echo '<table class="form-table">';
                do_settings_fields($page, $section['id']);
                echo '</table>';
            }
        }
    }
    /**
     * Show the section settings forms
     *
     * This function displays every sections in a different form
     */
    function show_forms() {
      
   
            if ($this->settings_menu['display_metabox'] )
                echo '<div class="" style="width:77%; background: white;padding: 7px 15px;margin-top: 5px; border: 1px solid #ddd;
"><div class="">';
        
            if (is_network_admin())
                echo '<form method="post" action="../options.php">';
            else
                echo '<form method="post" action="options.php">';
                
                settings_fields( $this->settings_menu['name'] );
               // do_settings_sections( $this->settings_menu['name']);
                foreach ($this->settings_sections as $form) {  ?>
                    <div id="<?php echo $form['id']; ?>" class="group">
                        

                            <?php
                            $this->do_settings_sections_for_tab($this->settings_menu['name'], $form);
                            // ?>
                            <?php //do_settings_sections( $this->settings_menu['name']); ?>

                            
                            
                        
                    </div>
                    

                <?php } ?>
                
                <span style="padding:0 10px;" class="alignleft">
                    <?php submit_button('Save Settings'); ?>
                </span>
                
                <span style="padding:0 10px;" class="alignright">
                    <p class="submit">
                        <input name="reset-defaults" onclick="return confirm('<?php _e('Are you sure you want to restore all settings back to their default values?', $this->settings_menu['name']); ?>');" class="button-secondary" type="submit" value="<?php _e('Reset Settings to WP', $this->settings_menu['name']); ?>" />                      </p>
                </span> 
                
                 
                 
               <div class="clear"></div>
                            
                </form>
                <?php
                 
         
         if ($this->settings_menu['display_metabox'] )
            echo '</div></div>';
        
        $this->script();
    }

    /**
     * Tabbable JavaScript codes
     *
     * This code uses localstorage for displaying active tabs
     */
    function script() {
        ?>
        <style type="text/css">
        <!--
        	.postbox h3{cursor: auto!important;}
        -->
        </style>
        
        <script>
            jQuery(document).ready(function($) {
                // Switches option sections
                $('.group').hide();
                
                $('#import_options').change(function(e){
                    
                    if (confirm('You may lose your current settings. Is it OK?')==true)
                        $('#import_field').val($(this).val());
                    else
                        $('#import_field').val('');  
                });
                
                $('.opener').change(function(e){ 
                    
                    var this_obj=$(this);
                    var id= this_obj.attr('id');
                    var name= this_obj.attr('name');
                      
                    if (this_obj.attr('type')=='checkbox' ) { 
                          
                        if (this_obj.is(':checked'))                
                            $('.open_by_'+id ).parentsUntil('tbody').slideDown('150');  
                        else
                            $('.open_by_'+id ).parentsUntil('tbody').slideUp('150');
   
                    }else if ( this_obj.attr('type')=='radio'){
                         
                        $('.open_by_'+ $('input[name="'+name+'"]:checked').attr('id') ).parentsUntil('tbody').slideDown('150');
                        //hide other   
                        $('.open_by_'+ $('input[name="'+name+'"]:not(:checked)').attr('id') ) .parentsUntil('tbody').slideUp('150');
                    } else if (this_obj.hasClass('selectbox')){
                        
                        $('.open_by_'+ id+'_'+this_obj.val() ).parentsUntil('tbody').slideDown('150');
                        //hide other   
                        $("[class^='open_by_"+ id+"_'],[class*=' open_by_"+ id+"_']").not('.open_by_'+ id +'_'+this_obj.val()).parentsUntil('tbody').slideUp();
                         
                    }    
                            
                });
                
                 
                //first time load should be after change
                $('.opener').trigger('change');
                    
                
                var activetab = '';
                if (typeof(localStorage) != 'undefined' ) {
                    activetab = localStorage.getItem("activetab");
                }
                if (activetab != '' && $(activetab).length ) {
                    $(activetab).fadeIn();
                } else {
                    $('.group:first').fadeIn();
                }
                $('.group .collapsed').each(function(){
                    $(this).find('input:checked').parent().parent().parent().nextAll().each(
                    function(){
                        if ($(this).hasClass('last')) {
                            $(this).removeClass('hidden');
                            return false;
                        }
                        $(this).filter('.hidden').removeClass('hidden');
                    });
                });

                if (activetab != '' && $(activetab + '-tab').length ) {
                    $(activetab + '-tab').addClass('nav-tab-active');
                }
                else {
                    $('.nav-tab-wrapper a:first').addClass('nav-tab-active');
                }
                $('.nav-tab-wrapper a').click(function(evt) {
                    $('.nav-tab-wrapper a').removeClass('nav-tab-active');
                    $(this).addClass('nav-tab-active').blur();
                    var clicked_group = $(this).attr('href');
                    if (typeof(localStorage) != 'undefined' ) {
                        localStorage.setItem("activetab", $(this).attr('href'));
                    }
                    $('.group').hide();
                    $(clicked_group).fadeIn();
                    evt.preventDefault();
                });
            });
        </script>
        <?php
    }

}