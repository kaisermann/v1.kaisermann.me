<?php

require_once "includes/functions-post-type.php"; 

add_filter('show_admin_bar', '__return_false');
add_filter( 'wpcf7_load_js', '__return_false' );
add_filter( 'wpcf7_load_css', '__return_false' );

remove_action( 'wp_head', 'print_emoji_detection_script', 7 );
remove_action( 'admin_print_scripts', 'print_emoji_detection_script' );
remove_action( 'wp_print_styles', 'print_emoji_styles' );
remove_action( 'admin_print_styles', 'print_emoji_styles' );

add_action( 'after_setup_theme', 'cowp_setup' );
function cowp_setup() {
	register_nav_menu( 'principal', __( 'Menu Principal', 'cowp' ) );
	add_theme_support( 'post-thumbnails' );
    add_image_size( 'front-thumb', 600, 600, array('center, center')); 
    add_image_size( 'sobre-thumb', 240, 320, array('center, center')); 
}

add_action( 'wp_enqueue_scripts', 'cowp_scripts_styles' );
function cowp_scripts_styles() 
{
	wp_enqueue_style( 'style', get_template_directory_uri().'/assets/css/main.min.css' );
    if( !is_admin()){
        wp_deregister_script('jquery');
        wp_register_script('jquery', ("http://code.jquery.com/jquery-1.11.1.min.js"), false, '1.11.1', true);
        wp_enqueue_script('jquery');
    }
    wp_enqueue_script( 'mainjs', get_template_directory_uri().'/assets/js/main.min.js', array(), '1.0.0', true );
}

add_action('wp_footer', 'eps_footer');
function eps_footer() {
	echo "<script>var ajax_request_url = '".admin_url( 'admin-ajax.php' )."'</script>";
}

add_action( 'wp_ajax_nopriv_get_post_ajax', 'get_post_ajax' );
add_action( 'wp_ajax_get_post_ajax', 'get_post_ajax' );

function get_post_ajax() 
{
	if ( isset($_GET['url']) )
	{
		$id = url_to_postid( $_GET['url'] );
		$post = get_post( $id, "object", "display" );
		header( "Content-Type: application/json" );
		$post->acf = array();
		foreach(get_fields($id) as $key => $value)
			$post->acf[$key] = $value;

        $post->content = do_shortcode(apply_filters('the_content', $post->post_content));
        echo json_encode($post);

    } else {
      header( "Content-Type: application/json" );
      echo json_encode( array('error' => 'bad request') );
  }
  exit;
}

/* Custom ajax loader */
add_filter('wpcf7_ajax_loader', 'my_wpcf7_ajax_loader');
function my_wpcf7_ajax_loader () {
    return  get_bloginfo('stylesheet_directory') . '/assets/img/ajax-loader.gif';
}

function the_image($img, $size, $arr, $returl = false)
{

    // Img null e tem thumbnail
    if($img==NULL)
        if(has_post_thumbnail())
        {
            if($returl)
            {
                $thumb = wp_get_attachment_image_src(get_post_thumbnail_id(), $size);
                return $thumb['0'];
            }
            the_post_thumbnail($size, $arr); 
            return;
        }
        else
            if($size==NULL)
            {
                echo "[NO IMAGE FOUND]";
                return;
            }

    // Vamos ver se o $size existe nos tamanhos registrados
            global $_wp_additional_image_sizes;
            $imgsizes = $_wp_additional_image_sizes;

            if($size != null)
                if (!isset($imgsizes[$size]))
                {
            // Se não existir o tamanho, leva em conta que o tamanho passado foi Largura x Altura
                    $s = explode("x", $size);
                    $w = $s[0];
                    $h = $s[1];
                }
                else
                {
            // Se existir, puxa o tamanho registrado com o nome passado
                    $w = $imgsizes[$size]['width'];
                    $h = $imgsizes[$size]['height'];
                }

                $alt = $tamanho = '';

                if ($img == null)
                    $url = 'http://placehold.it/' . $w . 'x' . $h;
                else
                    $url = ($size == null) ? $img['url'] : $img['sizes'][$size];

                if($returl)
                    return $url;


                if(isset($w) && isset($h))
                    $tamanho = 'width="' . $w . '"  height="' . $h . '"';

                if($img != null && strlen($img['alt'])>0 && !isset($arr['alt']))
                    $alt = 'alt="'.$img['alt'].'"';

                $attr = "";
                if(is_array($arr))
                    foreach ($arr as $atk => $atv)
                        $attr .= $atk.'="'.$atv.'" ';

                    $attrs = $alt . ' ' . $tamanho . ' ' . $attr;
                    echo '<img src="' . $url . '" ' . $attrs . ' />';
                }
                ?>