<?php

//继承WP_List_Table

class WPWPH_Template_Table extends WP_List_Table {
	    
    function __construct(){
        global $status, $page;
                
        //Set parent defaults
        parent::__construct( array(
            'singular'  => 'template',     //singular name of the listed records
            'plural'    => 'templates',    //plural name of the listed records
            'ajax'      => false        //does this table support ajax?
        ) );
        
    }

    function no_items(){
    	_e('没有历史信息', 'WPWPH');
    }

	function column_default($item, $column_name){
        switch($column_name){
            case 'title':
            case 'type':
            case 'date':
            case 'trigger_method':
                return $item[$column_name];
            default:
                return print_r($item,true); //Show the whole array for troubleshooting purposes
        }
    }


	public function get_sortable_columns() {
		$sortable_columns = array(
							'title'  => array('title',false),
							'type' => array('type',false),
							'date' => array('date',false),
							'trigger_method' => array('trigger_method',false));
		return $sortable_columns;
	}

	public function get_columns(){
	    $columns = array(
					'cb'        => '<input type="checkbox" />',
					'title' => __( '标题', 'WPWPH' ),
					'type'  => __( '类型','WPWPH' ),
					'date'  => __( '创建日期', 'WPWPH' ),
					'trigger_method'  => __( '触发方式', 'WPWPH' )
					);
	    return $columns;
	}

	public function column_cb($item) {
	    return sprintf(
	        '<input type="checkbox" name="record[]" value="%s" />', $item['ID']
	    );    
	}
	public function get_bulk_actions() {
		$actions = array(
			'delete'    => __('删除','WPWPH')
		);
		return $actions;
	}

	public function results_order() {
		$orderby = ( ! empty( $_GET['orderby'] ) ) ? $_GET['orderby'] : 'date';
		$order = ( ! empty($_GET['order'] ) ) ? $_GET['order'] : 'desc';
		return $orderby." ".$order;
	}

	public function delete_template($id){
		if(!is_wp_error(get_post($id))){
			wp_delete_post($id,true);
		}
	}

	function column_title($item){
        
        //Build row actions
          $actions = array(
            'edit' => sprintf('<a href="?page=%s&action=%s&record[]=%s">'.__("编辑", "WPWPH").'</a>',$_REQUEST['page'],'edit',$item['ID']),
            'delete' => sprintf('<a href="?page=%s&action=%s&record[]=%s">'.__("删除", "WPWPH").'</a>',$_REQUEST['page'],'delete',$item['ID'])
        );
        
        return sprintf('%1$s %2$s',
            /*$1%s*/ $item['title'],
            /*$2%s*/ $this->row_actions($actions)
        );
    }
	
	public function prepare_items($data) {
		$request_total_count=10;
		$columns = $this->get_columns();
        $hidden = array();
        $sortable = $this->get_sortable_columns();
		$this->_column_headers = array($columns, $hidden, $sortable);
		$this->items  = $data;
	}
}
 
?>