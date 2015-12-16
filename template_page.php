<?php
//模板添加页面
require_once('class-wpwph-template-table.php');

$wp_list_table= new WPWPH_Template_Table();

if(isset($_GET['action']) || isset($_GET['action2'])){
	if($_GET['action']=='delete' || $_GET['action2']=='delete'){
		if(isset($_GET['record'])){
	        foreach($_GET['record'] as $record){
	        	$wp_list_table->delete_template($record);
	        }
        }
	}
}

$args = array(
		'post_type' => 'wpwph_template',
		'posts_per_page' => -1,
		'orderby' => 'post_date',
		'post_status' => 'any',
		'order'=> 'ASC'
);

$rawData= get_posts($args);

$data= array();
foreach ($rawData as $d) {
	$status= $d->post_status;

	$key=trim(get_post_meta($d->ID,'_keyword',TRUE));
	$array_key=explode(',', $tmp_key);

	if(count($array_key)>0){
		foreach($array_key as $k){
			if($k!=''){
				foreach($rawData as $e){
					if($d->ID == $e->ID){
						continue;
					}
					if(get_post_meta($e->ID,'_trigger',TRUE)!='-'){
						continue;
					}
					$tmp_key2=trim(get_post_meta($e->ID,'_keyword',TRUE));
					$array_key2=explode(',', $tmp_key2);
					foreach($array_key2 as $k2){
						if(strtolower(trim($k))==strtolower(trim($k2))){
							$key=__('<span class="msg_conflict">'.__('冲突','WPWPH').'</span><br>','WPWPH').'<i>'.$e->post_title.'</i>';
							break;
						}
					}
				}
			}
		}
	}

	$type=get_post_meta($d->ID,'_type',TRUE);
	$_trigger=get_post_meta($d->ID,'_trigger',TRUE);

	switch($_trigger){
		case 'default':
			$key='<span class="msg_highlight">'.__('*默认(唯一)*','WPWPH').'</span>';
		break;
		case 'subscribe':
			$key='<span class="msg_highlight">'.__('*订阅(唯一)*','WPWPH').'</span>';
		break;
	}
	if($d->post_status!='publish'){
		$key='<span class="msg_disabled">'.__('*未激活*','WPWPH').'</span>';
	}
	$post_title=$d->post_title?$d->post_title:__('(空)','WPWPH');
	$data[]=array('ID'=>$d->ID, 'title'=>$post_title, 'type'=>$type, 'date'=>mysql2date('Y.m.d', $d->post_date), 'trigger_method' => $key);
}

//Prepare Table of elements 
$wp_list_table->prepare_items($data);

?>

<link href="<?php echo WPWPH_HELPER_URL;?>/css/style.css" rel="stylesheet">
<link href="<?php echo WPWPH_HELPER_URL;?>/css/modal.css" rel="stylesheet">
<div>
	<p class="header_func">
		<?php if(current_user_can('manage_options')):?>
		<a href="<?php menu_page_url(WPWPH_SETTINGS_PAGE);?>"><?php _e('公众平台助手配置','WPWPH');?></a>
		<?php endif;?>	
		&nbsp;&nbsp;&nbsp;&nbsp;<a href="http://www.wugubaike.com" target="_blank"><?php _e('帮助','WPWPH');?></a>
	</p>
	<hr>
	<h2>
	<?php _e('自动回复模板定制','WPWPH');?>
	<a href="<?php menu_page_url(WPWPH_TEMPLATE_PAGE);?>&action=edit"><?php _e('添加回复模板','WPWPH');?></a>
	</h2>
	<br>

	<form action="" method="get">
		<input type="hidden" name="page" value="<?php echo WPWPH_TEMPLATE_PAGE;?>" />
		<?php $wp_list_table->display(); ?>
	</form>
</div>