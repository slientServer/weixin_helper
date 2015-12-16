<?php
/*
Plugin Name: 微信公众平台助手
Plugin URI: http://wugubaike.com
Description: 微信公众平台管理插件，提供微信公众平台的管理功能
Version: 1.0.0
Author: Brian
Author URI: http://wugubaike.com/wordpress-plugins/
*/

if ( !defined( 'ABSPATH' ) ) exit;
include 'ChromePhp.php';
define('WPWPH_HELPER_URL', plugins_url('', __FILE__));
define('WPWPH_SETTINGS_OPTION', 'wpwph_settings_option');
define('WPWPH_GENERAL_PAGE', 'wpwph_general_page');
define('WPWPH_SETTINGS_PAGE', 'wpwph_settings_page');
define('WPWPH_HISTORY_PAGE', 'wpwph_history_page');
define('WPWPH_TEMPLATE_PAGE', 'wpwph_template_page');
define('DB_TABLE_WPWPH_HISTORY', 'weixin_platform_helper_history');
define('COUNT_PER_PAGE', 10);
define('SELECT_ROWS_AMOUNT', 100);
define('SYNC_TITLE_LIMIT', 50);
define('SYNC_CONTENT_LIMIT', 300);
define('SYNC_EXCERPT_LIMIT', 100);
define('MAX_SEARCH_LIMIT', 6);


//Weixin Interface
$options=get_option(WPWPH_SETTINGS_OPTION);
global $token;
$token=isset($options['token'])?$options['token']:'';
add_action('parse_request', 'load_wx_interface');
function load_wx_interface(){
    global $token;
    if($token!='' && isset($_GET[$token])){
    	require( 'wx_interface.php' );
    }
}

//数据库建表
add_action( 'plugins_loaded', 'create_history_table' );
function create_history_table(){
    global $wpdb;
    $table_name =DB_TABLE_WPWPH_HISTORY; 
    $sql = "CREATE TABLE $table_name (
    id bigint(20) NOT NULL KEY AUTO_INCREMENT,  
    openid   varchar(100) NOT NULL,
    content  varchar(2500) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL,
    msgType varchar(10)   NOT NULL,
    msgId varchar(30)   NOT NULL,
    createTime  varchar(15)  NOT NULL
    );";
    
    require_once( ABSPATH . 'wp-admin/includes/upgrade.php' );
    dbDelta($sql);
}


//微信公众平台管理
add_action('_admin_menu', 'add_wx_admin_page');
function add_wx_admin_page(){
     global $user_level;
     if($user_level>=5){
     require_once( 'posttype_wpwph_template.php' );

     $page_title=__('微信公众平台助手', 'WPWPH');
     $menu_title=__('微信公众平台助手', 'WPWPH');
     $capability='edit_pages';
     $menu_slug=WPWPH_GENERAL_PAGE;
     $function='';
     add_object_page( $page_title, $menu_title, $capability, $menu_slug, $function, $icon_url );

     require_once('class-wpwph-init.php');
     require_once('class-wpwph-history.php');
     require_once('class-wpwph-template.php');

     WPWPH_Init::get_instance();
     WPWPH_History::get_instance();
     WPWPH_Template::get_instance();
         
    }
}


add_action('admin_init', 'ajax_handle', 999);
function ajax_handle(){
    require_once( 'ajax_request_handle.php');
}

//引入js文件
add_action('admin_print_scripts', 'custom_admin_scripts');
function custom_admin_scripts() {
    wp_enqueue_script('jquery');
    wp_enqueue_media();
    wp_register_script('custom-upload', WPWPH_HELPER_URL.'/js/custom_upload.js', array('jquery','media-upload','thickbox'),"2.0");
    wp_enqueue_script('custom-upload');
    wp_register_script('modal', WPWPH_HELPER_URL.'/js/modal.js',array(),"2.0");
    wp_enqueue_script('modal');
}


?>