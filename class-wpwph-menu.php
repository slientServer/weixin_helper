<?php
//菜单管理
class WPWPH_Menu{

    private $menu_page='menu_page.php';

    private static $_instance;

    public static function get_instance(){
        if(!isset(self::$_instance)){
            $c=__CLASS__;
            self::$_instance= new $c;
        }
        return self::$_instance;
    }

    public function __clone(){
        trigger_error('禁止克隆', E_USER_ERROR);
    }

    private function __construct(){
        add_action('admin_menu', array($this, 'add_menu_page'));
    }

    /*
    * 添加历史页
    */
    public function add_menu_page(){
        $parent_slug=WPWPH_GENERAL_PAGE;
        $page_title=__('微信菜单管理', 'WPWPH');
        $menu_title=__('微信菜单管理', 'WPWPH');
        $capability='edit_pages';
        $menu_slug=WPWPH_MENU_PAGE;
        add_submenu_page( 
            $parent_slug,
            $page_title,
            $menu_title,
            $capability,
            $menu_slug,
            array( $this, 'create_menu_page' )
        );
    }

    public function create_menu_page(){
        require_once($this->menu_page);
    }

}
?>