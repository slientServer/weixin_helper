<?php
	
	require_once( 'class-wpwph-history-table.php' );

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

	$wp_list_table->prepare_items();

?>
<div>
	<hr>
	<h2>
	 <?php _e('微信历史消息统计','WPWPH');?>
     <form action="" method="get" style="float:right;">
     <input type="hidden" name="page" value="<?php echo WPWPH_HISTORY_PAGE;?>" />
	 <button  id="clear_all_records" type="submit" name="clear_all_records" value="all" class="add-new-h2"><?php _e("清空所有记录","WPWPH");?></button>
	 </form>
	</h2>
    <br>
	<form action="" method="get">
		<input type="hidden" name="page" value="<?php echo WPWPH_HISTORY_PAGE;?>" />
		<input type="hidden" name="per_page" value="<?php _e($per_page); ?>" />
		<?php $wp_list_table->display();?>
	</form>
</div>