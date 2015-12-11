<?php
	
	require_once( 'class-wpwph-history-table.php' );
	global $wpdb;

	$wp_list_table = new WPWPH_History_Table();

	if(isset($_GET['clear_all_records']) && $_GET['clear_all_records']=='all'){
		$wp_list_table->delete_all();
		header("Location:?page=".WPWPH_HISTORY_PAGE);
	}

	if(isset($_GET['action']) || isset($_GET['action2'])){
		if($_GET['action']=='delete' || $_GET['action2']=='delete'){
			if(isset($_GET['record'])){
		        foreach($_GET['record'] as $r){
		        	 $wp_list_table->delete_record($r);
		        }
	        }
		}
	}

	$paged = isset($_GET['paged']) ? $_GET['paged'] : 1;
	$start = ($paged-1)*COUNT_PER_PAGE;
	$order= $wp_list_table->results_order();
	$data= array();
	if(isset($_POST['s']) && !empty($_POST['s'])){
		$search_key= $_POST['s'];
		$sql_str= $wpdb->prepare('select * from '.DB_TABLE_WPWPH_HISTORY.' where openid like "%%%s%%" order by '.$order, $search_key);
		$rawData= $wpdb->get_results($sql_str);
		$sql_where= $wpdb->prepare(' where openid like "%%%s%%"', $search_key);
		$total = $wpdb->get_results('select count(id) as total from '.DB_TABLE_WPWPH_HISTORY.$sql_where);
		$per_page= $total[0]->total;
	}else{
		$sql_str= 'select id, openid, content, msgType, msgId, createTime from '.DB_TABLE_WPWPH_HISTORY.' order by '.$order.' limit '.$start.','.COUNT_PER_PAGE;
		$rawData= $wpdb->get_results($sql_str);
		$total = $wpdb->get_results('select count(id) as total from '.DB_TABLE_WPWPH_HISTORY);
		$per_page= COUNT_PER_PAGE;
	}
	foreach($rawData as $d){
		$data[]=array('id'=> $d->id, 'openid'=>$d->openid, 'content'=>$d->content, 'msgType' =>$d->msgType, 'msgId'=>$d->msgId, 'createTime'=>$d->createTime);
	}
	$wp_list_table->set_pagination_args( array(
		'total_items' => $total[0]->total,                  //WE have to calculate the total number of items
		'per_page'    => $per_page                    //WE have to determine how many items to show on a page
	));

	$wp_list_table->prepare_items($data);

?>
<div>
	<hr>
	<h2>
	 <?php _e('微信历史消息统计','WPWPH');?>
     <form action='' method='post'>
	 	<input type='hidden' name='page' value='<?php echo WPWPH_HISTORY_PAGE;?>'/>
	 	<?php $wp_list_table->search_box(__('按发送者搜索'), 'search_id');?>
	 </form>
	</h2>
    <br>
	<form action="" method="get">
		<input type="hidden" name="page" value="<?php echo WPWPH_HISTORY_PAGE;?>" />
		<input type="hidden" name="per_page" value="<?php _e($per_page); ?>" />
		<?php $wp_list_table->display();?>
	</form>
	<form action="" method="get" style="float:right;">
     <input type="hidden" name="page" value="<?php echo WPWPH_HISTORY_PAGE;?>" />
	 <button  id="clear_all_records" type="submit" name="clear_all_records" value="all" class="add-new-h2"><?php _e("清空所有记录","WPWPH");?></button>
	</form>
</div>