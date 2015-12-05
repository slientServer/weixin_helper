<?php
/*
Plugin Name: WP Weixin Platform Helper
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
define('DB_TABLE_WPWPH_HISTORY', 'weixin_platform_helper_history');

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
    keyword  varchar(255) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL,
    is_match char(1)   NOT NULL,
    time     datetime  NOT NULL
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

     WPWPH_Init::get_instance();
     WPWPH_History::get_instance();
         
    }
}


?>