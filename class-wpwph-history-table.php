<?php

//继承WP_List_Table

class WPWPH_History_Table extends WP_List_Table {

	public $db_table= 'weixin_platform_helper_history';
	    
    function __construct(){
        global $status, $page;
                
        //Set parent defaults
        parent::__construct( array(
            'singular'  => 'message',     //singular name of the listed records
            'plural'    => 'messages',    //plural name of the listed records
            'ajax'      => false        //does this table support ajax?
        ) );
        
    }

    function no_items(){
    	_e('没有历史信息', 'WPWPH');
    }

    function column_openid($item){
        
        //Build row actions
        $actions = array(
            'delete' => sprintf('<a href="?page=%s&action=%s&record[]=%s">删除</a>',$_REQUEST['page'],'delete',$item['id'])
        );
        
        //Return the openId contents
        return sprintf('%1$s %2$s',
            /*$1%s*/ $item['openid'],
            /*$2%s*/ $this->row_actions($actions)
        );
    }

    function column_createTime($item){
        
        return sprintf('%1$s', date('Y-n-j G:i:s', ($item['createTime']+ 3600*8)));
    }

	function column_default($item, $column_name){
        switch($column_name){
            case 'openid':
            case 'content':
            case 'msgType':
            case 'msgId':
            case 'createTime':
                return $item[$column_name];
            default:
                return print_r($item,true); //Show the whole array for troubleshooting purposes
        }
    }


	public function get_sortable_columns() {
		$sortable_columns = array(
							'openid'  => array('openid',false),
							'content' => array('content',false),
							'msgType' => array('msgType',false),
							'msgId' => array('msgId',false),
							'createTime' => array('createTime',false));
		return $sortable_columns;
	}

	public function get_columns(){
	    $columns = array(
					'cb'        => '<input type="checkbox" />',
					'openid' => __( '发送者', 'WPWPH' ),
					'content'  => __( '发送内容','WPWPH' ),
					'msgType'  => __( '消息类型', 'WPWPH' ),
					'msgId'  => __( '消息ID', 'WPWPH' ),
					'createTime'  => __( '发送时间', 'WPWPH' )
					);
	    return $columns;
	}

	public function column_cb($item) {
	    return sprintf(
	        '<input type="checkbox" name="record[]" value="%s" />', $item['id']
	    );    
	}
	public function get_bulk_actions() {
		$actions = array(
			'delete'    => __('删除','WPWPH')
		);
		return $actions;
	}

	public function results_order() {
		$orderby = ( ! empty( $_GET['orderby'] ) ) ? $_GET['orderby'] : 'createTime';
		$order = ( ! empty($_GET['order'] ) ) ? $_GET['order'] : 'desc';
		return $orderby." ".$order;
	}

	public	function delete_record($id){
		global $wpdb;
	    $wpdb->query("delete from ".$this->db_table." where id=".$id);
	}

	public	function delete_all(){
		global $wpdb;
	    $wpdb->query("delete from ".$this->db_table);
	}
	
	public function prepare_items() {
		$request_total_count=10;
		$paged = isset($_GET['paged']) ? $_GET['paged'] : 1;
		$start = ($paged-1)*$request_total_count;
		$order= $this->results_order();
		$columns = $this->get_columns();
        $hidden = array();
        $sortable = $this->get_sortable_columns();
		$this->_column_headers = array($columns, $hidden, $sortable);
		global $wpdb;
		$rawData= $wpdb->get_results('select id, openid, content, msgType, msgId, createTime from '.$this->db_table.' order by '.$order.' limit '.$start.','.$request_total_count);
		$data= array();
		foreach($rawData as $d){
		 $data[]=array('id'=> $d->id, 'openid'=>$d->openid, 'content'=>$d->content, 'msgType' =>$d->msgType, 'msgId'=>$d->msgId, 'createTime'=>$d->createTime);
		}
		$total = $wpdb->get_results('select count(id) as total from '.$this->db_table);
		$this->set_pagination_args( array(
				'total_items' => $total[0]->total,                  //WE have to calculate the total number of items
				'per_page'    => $request_total_count                    //WE have to determine how many items to show on a page
				));
		$this->items  = $data;
	}
}
 
?>