<?php add_action( 'init', 'custom_post_type', 0 );
function custom_post_type() {

	$labels = array(
		'name'                => _x( 'Projetos', 'Post Type General Name', 'cowp' ),
		'singular_name'       => _x( 'Projeto', 'Post Type Singular Name', 'cowp' ),
		'menu_name'           => __( 'Projetos', 'cowp' ),
		'parent_item_colon'   => __( 'Item pai:', 'cowp' ),
		'all_items'           => __( 'Todos os projetos', 'cowp' ),
		'view_item'           => __( 'Ver projeto', 'cowp' ),
		'add_new_item'        => __( 'Adicionar projeto', 'cowp' ),
		'add_new'             => __( 'Adicionar novo', 'cowp' ),
		'edit_item'           => __( 'Editar projeto', 'cowp' ),
		'update_item'         => __( 'Atualizar projeto', 'cowp' ),
		'search_items'        => __( 'Procurar projeto', 'cowp' ),
		'not_found'           => __( 'Projeto não encontrado', 'cowp' ),
		'not_found_in_trash'  => __( 'Projeto não encontrado no lixo', 'cowp' ),
	);
	$args = array(
		'labels'              => $labels,
		'supports'            => array( 'title', 'editor', 'thumbnail' ),
		'taxonomies'          => array( 'category' ),
		'hierarchical'        => false,
		'public'              => true,
		'show_ui'             => true,
		'show_in_menu'        => true,
		'show_in_nav_menus'   => true,
		'show_in_admin_bar'   => true,
		'menu_position'       => 5,
		'can_export'          => true,
		'has_archive'         => true,
		'exclude_from_search' => false,
		'publicly_queryable'  => true,
		'capability_type'     => 'page',
	);
	register_post_type( 'projeto', $args );
}