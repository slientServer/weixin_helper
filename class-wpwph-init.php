<?php
//Weixin init setting
class WPWPH_Init{
	
	private $page_slug=WPWPH_SETTINGS_PAGE;
	private $capability='edit_pages';
	
	private $option_group='wpwph_settings_option_group';
	private $option_name=WPWPH_SETTINGS_OPTION;
	
	private $setting_page='setting_page.php';

	private static $_instance;
	
    /**
     * Holds the values to be used in the fields callbacks
     */
    private $options;

    /**
     * Start up
     */
     
    public static function get_instance(){
	    if(!isset(self::$_instance)){
	    	$c=__CLASS__;
	    	self::$_instance=new $c;
	    }
	    return self::$_instance;
    }
    
    public function __clone(){
    	trigger_error('不能克隆' ,E_USER_ERROR);
    }
    
    private function __construct(){
        add_action( 'admin_menu', array( $this, 'add_init_page' ));
    }

    /**
     * Add options page
     */
    public function add_init_page(){
        $page_title=__('微信公众助手初始化', 'WPWPH');
        $menu_title=__('微信公众助手初始化', 'WPWPH');
        $capability='manage_options';
        $menu_slug=WPWPH_SETTINGS_PAGE;
        
        add_options_page(
        	$page_title,
        	$menu_title,
        	$capability,
        	$menu_slug,
        	array( $this, 'create_admin_page' )
        );
        
        $this->page_init();
    }

    /**
     * Options page callback
     */
    public function create_admin_page(){
        $this->options = get_option( $this->option_name );
		require_once( $this->setting_page );
    }

    /**
     * Register and add settings
     */
    public function page_init(){        
        register_setting(
            $this->option_group, // Option group
            $this->option_name, // Option name
            array( $this, 'sanitize' ) // Sanitize
        ); 
    }

    /**
     * Sanitize each setting field as needed
     *
     * @param array $input Contains all settings fields as array keys
     */
    public function sanitize( $input ){
        $new_input = array();

        foreach($input as $key=>$obj){        	
        	if(isset( $input[$key])){
        		if($key=='token'){
        			$obj=trim($obj);
        			$obj=str_replace( ' ', '',$obj);
        			$obj = preg_replace('/[^A-Za-z0-9\-_]/','',$obj);
        		}
        	    $new_input[$key] = sanitize_text_field( $obj );
        	}
        }
        return $new_input;
    }
}

?>