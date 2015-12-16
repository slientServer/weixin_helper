<?php
//模板定制功能
class WPWPH_Template{
	private $template_page='template_page.php';
	private $edit_template_page='edit_template_page.php';

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
		add_action('admin_menu', array($this, 'add_template_page'));
	}

	//添加定制模板页面
	public function add_template_page(){
		$parent_slug=WPWPH_GENERAL_PAGE;
        $page_title=__('微信公众平台助手', 'WPWPH');
        $menu_title=__('自动回复模板定制', 'WPWPH');
        $capability='edit_pages';
        $menu_slug=WPWPH_TEMPLATE_PAGE;
        add_submenu_page( 
        	$parent_slug,
        	$page_title,
        	$menu_title,
        	$capability,
        	$menu_slug,
        	array( $this, 'create_template_page' )
        );
	}

	public function create_template_page(){
		if(isset($_GET['action']) && $_GET['action']== 'edit'){
			require_once($this->edit_template_page);
		}else{
			require_once($this->template_page);

		}
	}

}
?>