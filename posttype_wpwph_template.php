<?php
// 定制post模板
custom_posttype_wpwph_template();

function custom_posttype_wpwph_template() {
	//Set up labels
	$labels = array('name' => 'WPWPH Template',
	'singular_name' => 'Template');
	
	$fields = array('labels' => $labels,
	'public' => false,
	'publicly_queryable' => false,
	'show_ui' => false, 
	'query_var' => false,
	'rewrite' => array('slug' => 'wpwph_template'),
	'capability_type' => 'page',
	'hierarchical' => false,
	'menu_position' => 60,
	'supports' => array('title')); 
	
	register_post_type('wpwph_template', $fields);
}

 ?>