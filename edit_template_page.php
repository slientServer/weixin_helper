<?php

//添加、编辑模板
function redirect(){
	header("Location:".menu_page_url(WPWPH_TEMPLATE_PAGE,false));
	exit();
}

if(isset($_GET['action']) && $_GET['action']== 'edit' && isset($_GET['record'])){
	$current_id=$_GET['record'][0];
}else{
	$current_id='';
}

if(isset($_GET['delete'])){
	$current_id=$_GET['delete'];
	if($current_id!=''){
		wp_delete_post($current_id,true);
	}
	redirect();
}


/*
 * 保存模板
 */

if(isset($_POST['submit-save']) || isset($_POST['submit-save-back'])){

	$_post_title=isset($_POST['post_title'])?$_POST['post_title']:'';
	$_post_status=isset($_POST['post_status'])?'publish':'draft';

	if($current_id==''){
		$_post = array(
			  'post_title'    => $_post_title,
			  'post_status'   => $_post_status,
			  'post_type'     => 'wpwph_template',
			  'comment_status' => 'closed',
		);
		$current_id=wp_insert_post( $_post, true);
	}else{
		$_post = array(
			  'ID'    		  => $current_id,
			  'post_title'    => $_post_title,
			  'post_status'   => $_post_status,
			  'post_type'     => 'wpwph_template',
			  'comment_status' => 'closed',
		);
		wp_update_post($_post);
	}

	if(isset($_POST['key'])){
		$_key=str_replace( ' ', '',$_POST['key']);
		$_key=str_replace( '，', ',',$_key);
		update_post_meta($current_id, '_keyword',strtolower($_key));
	}

	if(isset($_POST['trigger'])){
		//make sure only one 'default' and 'subscribe'.
		$current_trigger=$_POST['trigger'];
		if($current_trigger!=''){
			$args = array(
					'post_type' => 'wpwph_template',
					'posts_per_page' => -1,
					'orderby' => 'date',
					'post_status' => 'any',
					'order'=> 'DESC',
					'meta_query' => array(
							array(
								'key' => '_trigger',
								'value' => '-',
								'compare'=> '!='
							)
					)
			);

			$raw=get_posts($args);


			foreach($raw as $p){

				if($p->ID!=$current_id){

					$target_trigger=get_post_meta($p->ID, '_trigger',TRUE);
					if($current_trigger==$target_trigger){
						update_post_meta($p->ID, '_trigger','-');//replace duplicate to '-' which is default trigger by keyword.
					}
				}
			}
			update_post_meta($current_id, '_trigger',$_POST['trigger']);
		}
	}

	if(isset($_POST['type']))
		update_post_meta($current_id, '_type',$_POST['type']);


	if(isset($_POST['content'])){
		update_post_meta($current_id, '_content',$_POST['content']);
	}

	$_phmsg_group=array();

	if(isset($_POST['title']) && isset($_POST['pic']) && isset($_POST['des']) && isset($_POST['url'])){
		$phmsg_length=count($_POST['title']);
		for($i=0; $i<$phmsg_length; $i++){
			$_phmsg_group[$i]=array('title'=>urlencode($_POST['title'][$i]),'pic'=>urlencode($_POST['pic'][$i]),'des'=>urlencode($_POST['des'][$i]),'url'=>urlencode($_POST['url'][$i]));
		}
	}
	delete_post_meta($current_id, '_phmsg_item');
	foreach($_phmsg_group as $_phmsg_item){
		add_post_meta($current_id, '_phmsg_item',json_encode($_phmsg_item));
	}
	
  //response source
  if(isset($_POST['re_type'])){
		update_post_meta($current_id, '_re_type',$_POST['re_type']);
	}
	if(isset($_POST['re_cate'])){
		update_post_meta($current_id, '_re_cate',$_POST['re_cate']);
	}
	if(isset($_POST['re_count'])){
		update_post_meta($current_id, '_re_count',$_POST['re_count']);
	}

	if(isset($_POST['submit-save-back'])){
		redirect();
	}
}

/*
 * Read contents
 */

$post=get_post($current_id);
$_date=isset($post->post_title)?mysql2date('Y.m.d - G:i', $post->post_date):'-';
$_post_title=isset($post->post_title)?$post->post_title:'';

$_key=get_post_meta($current_id,'_keyword',TRUE);

//trigger
$_trigger=get_post_meta($current_id,'_trigger',TRUE);
if($_trigger==''){
	$_trigger='-';
}

$trigger_options=array(
				'-'=>__('普通(关键字)','WPWPH'),
				'default'=>__('默认','WPWPH'),
				'subscribe'=>__('订阅','WPWPH'),
				'event'=>__('事件','WPWPH'),
				);

//text message
$_content=get_post_meta($current_id,'_content',TRUE);

//photo message

$_phmsg_group=get_post_meta($current_id,'_phmsg_item');
if($_phmsg_group==''){
	$_phmsg_group=array();
}

$_phmsg_main=new stdClass();
if(isset($_phmsg_group[0])){
	$_phmsg_main=json_decode($_phmsg_group[0]);
}

$default_pic=WPWPH_HELPER_URL.'/img/trans.png';

if(!isset($_phmsg_main->title)){
	$_phmsg_main->title='';
	$_phmsg_main->pic='';
	$_phmsg_main->des='';
	$_phmsg_main->url='';
}else{
	$_phmsg_main->title=urldecode($_phmsg_main->title);
	$_phmsg_main->pic=urldecode($_phmsg_main->pic);
	$_phmsg_main->des=urldecode($_phmsg_main->des);
	$_phmsg_main->url=urldecode($_phmsg_main->url);
}
$_current_pic=$_phmsg_main->pic==''?$default_pic:$_phmsg_main->pic;


array_shift($_phmsg_group);
$_tmp_phmsg_group=array();
foreach($_phmsg_group as $item){
	$_tmp_item=json_decode($item);
	$_tmp_item->title=urldecode($_tmp_item->title);
	$_tmp_item->pic=urldecode($_tmp_item->pic);
	$_tmp_item->des=urldecode($_tmp_item->des);
	$_tmp_item->url=urldecode($_tmp_item->url);

	$_tmp_phmsg_group[]=$_tmp_item;
}
$_phmsg_group=$_tmp_phmsg_group;
//recent message
$_re_type=get_post_meta($current_id,'_re_type',TRUE);
$_re_cate=get_post_meta($current_id,'_re_cate',TRUE);
$_re_count=get_post_meta($current_id,'_re_count',TRUE);

$args_cate = array(
		'type'                     => 'post',
		'orderby'                  => 'name',
		'order'                    => 'ASC',
		'taxonomy'                 => 'category',
		'pad_counts'               => false 
		);
$defauleCate = new stdClass();
$defauleCate->term_id = "";
$defauleCate->cat_name = __("所有分类","WPWPH");
$_re_cates = array_merge(array($defauleCate), get_categories($args_cate));
$_re_cate_show = ($_re_type=="post"||$_re_type=="") ? false:true;
$_re_count_label = __("数量","WPWPH");
// global $wp_post_types; //get all post types
$args = array(
   'public'   => true,
   'show_ui'  => true
);
$output = 'objects'; // names or objects, note names is the default
$operator = 'and'; // 'and' or 'or'

$defauleTypes = new stdClass();
$defauleTypes->key = "";
$defauleTypes->labels = new stdClass();
$defauleTypes->labels -> name = __("所有类型","WPWPH");

$_re_types = array_merge(array($defauleTypes),
                         get_post_types( $args, $output, $operator ));

//switch type
$type=get_post_meta($current_id,'_type',TRUE);
$type_options=array(
				'text'=>__('文本模板消息','WPWPH'),
				'news'=>__('图文模板消息','WPWPH'),
				'recent' =>__("最近模板","WPWPH"),
        		'random' =>__("随机模板","WPWPH"),
				'search' =>__("搜索关键字","WPWPH")
				);

if($type=="recently"){
  $type = "recent";
}

switch($type){
	case "news":
		$display_resp_msg='style="display:none"';
		$display_resp_phmsg='style="display:block"';
		$display_resp_remsg='style="display:none"';
	break;
	case "recent":
		$display_resp_msg='style="display:none"';
		$display_resp_phmsg='style="display:none"';
		$display_resp_remsg='style="display:block"';
	break;
	case "random":
		$display_resp_msg='style="display:none"';
		$display_resp_phmsg='style="display:none"';
		$display_resp_remsg='style="display:block"';
	break;
	case "search":
		$display_resp_msg='style="display:none"';
		$display_resp_phmsg='style="display:none"';
		$display_resp_remsg='style="display:block"';
	break;
	default:
	  $display_resp_msg='style="display:block"';
		$display_resp_phmsg='style="display:none"';
		$display_resp_remsg='style="display:none"';
}

//switch status
$_post_status=isset($post->post_status)?$post->post_status:'';
$_status=($_post_status=='publish')?'checked':'';

?>
<link href="<?php echo WPWPH_HELPER_URL;?>/css/style.css" rel="stylesheet">
<link href="<?php echo WPWPH_HELPER_URL;?>/css/modal.css" rel="stylesheet">
<div class="wrap">
	<h2><?php _e('编辑回复模板','WPWPH');?></h2>
	<br>

		<div class="postbox">
			<div class="inside">
				<form action="" method="post" class="edit-template-form">
					<input type="hidden" name="edit" value="<?php echo $current_id;?>" />
					<input type="hidden" name="page" value="<?php echo WPWPH_TEMPLATE_PAGE;?>" />
						<h3><?php _e('基本设置','WPWPH');?></h3>
						<table class="form-table">
						    <tr valign="top">
							    <th scope="row"><label><?php _e('模板标题','WPWPH');?></label></th>
							    <td>
							    	<input type="text" name="post_title" value="<?php echo $_post_title;?>" class="large-text"/>
							    	<p class="description"><?php _e('仅用于方便模板管理，不会被发送','WPWPH');?></p>
							    </td>
						    </tr>
						    <tr valign="top">
						        <th scope="row"><label><?php _e('创建日期','WPWPH');?></label></th>
						        <td>
						        	<p><?php echo $_date;?></p>
						        </td>
						    </tr>
						    <tr valign="top">
						        <th scope="row"><label><?php _e('关键词','WPWPH');?></label></th>
						        <td>
						        	<input type="text" name="key" value="<?php echo $_key;?>" class="large-text"/>
						        	<p class="description"><?php _e('订阅者输入打关键字如果和这个设置的关键字匹配，那么将自动回复这条消息。允许设置多个关键字，每个关键字用 “, ”逗号分开。关键字只能是单个的字或词，中间没有空格，不对大小写敏感。','WPWPH');?></p>
						        </td>
						    </tr>
						    <tr valign="top">
						        <th scope="row"><label><?php _e('触发方式','WPWPH');?></label></th>
						        <td>
						        		<?php foreach($trigger_options as $key=>$val):?>
						        		<?php $checked=($key==$_trigger)?'checked':'';?>
						        		<label><input id="trigger-way-<?php echo $key;?>" type="radio" name="trigger" value="<?php echo $key;?>" <?php echo $checked;?> class="trigger-way"><?php echo $val;?></label>&nbsp;&nbsp;
						        		<?php endforeach;?>
						        	<p class="description"><?php _e('除了通过关键字，还能使用其他条件触发这条消息，特殊触发的优先级比关键字更高。','WPWPH');?></p>
						        	<ul class="description deslist">
						        		<li><?php _e('&quot;普通&quot;: 通过关键字触发。','WPWPH');?></li>
						        		<li><?php _e('&quot;默认&quot;: 关键字触发失败时发送。','WPWPH');?></li>
						        		<li><?php _e('&quot;订阅&quot;: 陌生人成为订阅者时触发。','WPWPH');?></li>
						        		<li><?php _e('&quot;事件&quot;: 按照事件key触发。','WPWPH');?></li>
						        	</ul>
						        </td>
						    </tr>
						    <tr valign="top">
						        <th scope="row"><label><?php _e('发布','WPWPH');?></label></th>
						        <td>
						        	<label>
						        		<input type="checkbox" name="post_status" value="publish" <?php echo $_status;?>/>
						        		<?php _e('发布该模板 ?','WPWPH');?>
						        	</label>
						        	<p class="description"><?php _e('勾上&quot;发布&quot;就会激活这条回复消息，否则的话他会被保存成为一个&quot;草稿&quot;，草稿不会响应订阅者的任何操作。','WPWPH');?></p>
						        </td>
						    </tr>
						    <tr valign="top">
						        <th scope="row"><label><?php _e('类型','WPWPH');?></label></th>
						        <td>
						        	<select name="type" id="msg_type">
						        		<?php foreach($type_options as $key=>$val):?>
						        		<?php $selected=($key==$type)?'selected':'';?>
						        		<option value="<?php echo $key;?>" <?php echo $selected;?>><?php echo $val ;?></option>
						        		<?php endforeach;?>
						        	</select>
						        	<p class="description"><?php _e('选择一个回复消息类型。','WPWPH');?></p>
						        </td>
						    </tr>
						</table>
						<div id="resp_msg" <?php echo $display_resp_msg;?>>
							<hr>
							<h3><?php _e('文本模板消息','WPWPH');?></h3>
							<div class="msg-box">
								<table class="form-table">
								    <tr valign="top">
								    	<th scope="row"><label><?php _e('内容','WPWPH');?></label></th>
									    <td>
									    	<textarea id="resp_msg_textarea" name="content" rows="10" class="large-text"><?php echo $_content;?></textarea>
									    	<p class="description"><button type='button' rtype="posts" tid='resp_msg_textarea' class="button alert_dialog_include_posts"><?php _e('插入内容','WPWPH'); ?></button>&nbsp;<?php _e('只能是纯文字，不能带任何脚本语言。','WPWPH');?></p>
									    </td>
								    </tr>
								</table>
							</div>
						</div>
						<div id="resp_phmsg" <?php echo $display_resp_phmsg;?>>
							<hr>
							<h3><?php _e('图文模板消息','WPWPH');?></h3>
							<div id="phmsg-base">
								<div class="msg-box">
									
									
									<div class="func-msg-box"><a href="#" class="up-msg-box-btn">&nbsp;</a>&nbsp;<a href="#" class="down-msg-box-btn">&nbsp;</a></div>
									<div class="clear"></div>
									<table class="form-table">
									    <tr valign="top">
											<th scope="row">
												<h3 rel="title" class="msg-box-title" data-subtitle="<?php _e('Sub','WPWPH');?>"><?php _e('图文内容','WPWPH');?></h3>
											</th>
											<td>
												<div class='phmsg_sync_link'><a href="javascript:;" rtype='phmsg' class="button button-primary alert_dialog_include_posts insert_resp_phmsg">&nbsp;&nbsp;<img width="16" height="16" src="<?php _e(WPWPH_HELPER_URL) ?>/img/sync.png">&nbsp;<span><?php _e('图文同步','WPWPH');?></span>&nbsp;&nbsp;</a></div>
											</td>
										</tr>
										<tr valign="top">
											<th scope="row">
												<label><?php _e('标题','WPWPH');?></label>
											</th>
											<td>
												<input type="text" name="title[]" value="<?php echo $_phmsg_main->title;?>" class="large-text"/>
												<p class="description"><?php _e('只能是纯文字，不能带任何脚本语言。','WPWPH');?></p>
											</td>
										</tr>
										<tr valign="top">
											<th scope="row">
												<label><?php _e('图像链接','WPWPH');?></label>
											</th>
											<td>
												<div class="preview-box">
													<img src="<?php echo $_current_pic;?>" data-default_pic="<?php echo $default_pic;?>"/>
													<a href="#" class="remove-pic-btn"><?php _e('移除','WPWPH');?></a>
												</div>
												<input type="hidden" name="pic[]" value="<?php echo $_phmsg_main->pic;?>" rel="img-input" class="img-input large-text"/>
												<button class='custom_media_upload button'><?php _e('上传','WPWPH');?></button>
												<p class="description"><?php _e('给这条模板添加一个配图。','WPWPH');?></p>
											</td>
										</tr>
										<tr valign="top">
											<th scope="row">
												<label><?php _e('描述','WPWPH');?></label>
											</th>
											<td>
												<input type="text" name="des[]" value="<?php echo $_phmsg_main->des;?>" class="large-text"/>
												<p class="description"><?php _e('如果你不希望出现消息简述，那么只要在这里留空。只能是纯文字，不能带任何脚本语言。','WPWPH');?></p>
											</td>
										</tr>
										<tr valign="top">
											<th scope="row">
												<label><?php _e('链接','WPWPH');?></label>
											</th>
											<td>
												<input type="text" name="url[]" value="<?php echo $_phmsg_main->url;?>" class="phmsg-base-input-url large-text"/>
												<p class="description"><button type="button" rtype='urls' class="button alert_dialog_include_posts"><?php _e('插入链接','WPWPH'); ?></button>&nbsp;<?php _e('点击消息下方面的阅读原文将连接到这个链接。','WPWPH');?></p>
											</td>
										</tr>
									</table>
									<div class="func-footer"><a href="#" class="remove-msg-box-btn"><?php _e('移除','WPWPH');?></a>&nbsp;&nbsp;</div>
								</div>
							</div>
							<div id="phmsg-group"></div>
							<?php _e('你可以在一个图文消息中，添加多组图文，但是只有第一组会显示大图。除去第一组图文，你还可以添加最多9组。你可以通过右侧的箭头上下移动附加图文。图文消息的URL可以直接链接到任何地址，但是为了保证正常使用，希望你填写地址前确认地址可以打开，尽可能使用简单的URL。');?>
							<div class="add-phmsg">
								<a href="#" class="button button-large" id="add-phmsg-btn">
                  <?php _e('添加更多附加消息','WPWPH');?>
                </a>
							</div>
						</div>

						<div id="resp_remsg" <?php echo $display_resp_remsg;?>>
						<hr>
						<h3 class="resp_remsg_recent">
			                <?php _e('最近模板','WPWPH');?>
			             </h3>
						<h3 class="resp_remsg_random">
			                <?php _e('随机模板','WPWPH');?>
			            </h3>
						<h3 class="resp_remsg_search">
			                <?php _e('搜索关键字','WPWPH');?>
			            </h3>
							<div class="msg-box">
								<table class="form-table">
								    <tr valign="top">
								    	<th scope="row">
                        <label><?php _e('描述','WPWPH');?></label>
                      </th>
									    <td>
                        <p class="resp_remsg_recent">
									        <?php _e('根据选项，自动返回最新添加的文章或页面。','WPWPH');?>
                        </p>
                        <p class="resp_remsg_random">
                          <?php _e('自动回复随机的文章。','WPWPH');?>
                        </p>
                        <p class="resp_remsg_search">
                          <?php _e('使用关键字搜索文章，并且回复搜索结果。必须将触发机制设置成－默认－才能有效。','WPWPH');?>
                        </p>
									    </td>
								    </tr>
								    <tr valign="top">
								    	<th scope="row">
                        <label><?php _e('类型','WPWPH');?></label>
                      </th>
									    <td>
									      <select name="re_type" id="re_type_select">
  									      <?php foreach($_re_types as $key=>$val):?>
  						        		  <?php $selected=($key==$_re_type)?'selected':'';?>
  						        		  <option value="<?php echo $key;?>" <?php echo $selected;?>><?php echo $val->labels->name ;?></option>
  						        		<?php endforeach;?>
									    </select>
									    </td>
								    </tr>
								    <tr valign="top" <?php if($_re_cate_show):?>style="display:none;"<?php endif; ?> id="re_cate_tr">
								    	<th scope="row">
                        <label><?php _e('分类','WPWPH');?></label>
                      </th>
									    <td>
									    <select name="re_cate">
									    <?php 
									    foreach($_re_cates as $val):?>
						        		<?php $selected=($val->term_id==$_re_cate)?'selected':'';?>
						        		<option value="<?php echo $val->term_id;?>" <?php echo $selected;?>><?php echo $val->cat_name ;?></option>
						        		<?php endforeach;?>	
									    </select>
									    </td>
								    </tr>
								    <tr valign="top">
								    	<th scope="row"><label><?php _e('数量', 'WPWPH');?></label></th>
									    <td>
									    <select name="re_count">
									    <?php 
									    $_re_counts = array("1"=>1,"2"=>2,"3"=>3,"4"=>4,"5"=>5,"6"=>6,"7"=>7,"8"=>8,"9"=>9,"10"=>10);
									    foreach($_re_counts as $key=>$val):?>
						        		<?php $selected=($key==$_re_count)?'selected':'';?>
						        		<option value="<?php echo $key;?>" <?php echo $selected;?>><?php echo $val ;?></option>
						        		<?php endforeach;?>	
									    </select>
									    </td>
								    </tr>
								</table>
							</div>
						</div>

					<hr>
					<div class="func-submit">
						<?php submit_button(__('保存并返回','WPWPH'),'primary','submit-save-back', false); ?>&nbsp;
						<?php submit_button(__('保存','WPWPH'),'secondary','submit-save', false); ?>&nbsp;
						<a href="<?php echo menu_page_url(WPWPH_TEMPLATE_PAGE,false);?>" class="button secondary"><?php _e('取消并返回','WPWPH');?></a>
					</div>
					<div class="clear"></div>
					<?php if($current_id!=''):?>
					<div class="func-delete">
						<a href="<?php echo menu_page_url(WPWPH_TEMPLATE_PAGE,false).'&delete='.$current_id;?>"><?php _e('删除','WPWPH');?></a>
					</div>
					<?php endif;?>
				</form>
			</div>
		</div>
</div><!--wrap-->

<!-- model -->
		<div id="hide-modal" style="display: none; width:800px; position:absolute;" class="hide-modal-content">
        <div class="hide-modal-body"> 
           
        </div>
        </div>
<script>
var limit_phmsg=9;
var count_phmsg=0;
jQuery(document).ready(function ($) {
  var init_msg_type = function(){
		var val = $('#msg_type').val();
		switch(val){
			case 'text':
				$('#resp_msg').show();
				$('#resp_phmsg').hide();
				$('#resp_remsg').hide();
			break;
			case 'news':
				$('#resp_msg').hide();
				$('#resp_phmsg').show();
				$('#resp_remsg').hide();
			break;
			case 'recent':
				$('#resp_msg').hide();
				$('#resp_phmsg').hide();
				$('#resp_remsg').show();
        $('.resp_remsg_recent').show();
        $('.resp_remsg_random').hide();
				$('.resp_remsg_search').hide();
			break;
			case 'random':
				$('#resp_msg').hide();
				$('#resp_phmsg').hide();
				$('#resp_remsg').show();
        $('.resp_remsg_recent').hide();
        $('.resp_remsg_random').show();
				$('.resp_remsg_search').hide();
			break;
			case 'search':
		    $('#resp_msg').hide();
		    $('#resp_phmsg').hide();
		    $('#resp_remsg').show();
        $('.resp_remsg_recent').hide();
        $('.resp_remsg_random').hide();
				$('.resp_remsg_search').show();
        
			  $('.trigger-way').removeAttr("checked")
        .attr("disabled","disabled");
        
        $("#trigger-way-default").attr("checked","checked")
        .removeAttr("disabled");

			break;
		}
		if(val!='search'){
      $('.trigger-way').removeAttr("disabled");
			$("#"+$cur_trigger_way).click();
	  }
  }
  
  init_msg_type();
  
  var $cur_trigger_way = $(".trigger-way:checked").attr("id");;
	if($('#msg_type').length>0){
		$('#msg_type').change(function(){
			init_msg_type();
		});
	}
  
  $(".trigger-way").click(function(){
      $cur_trigger_way = $(this).attr('id');
  });
  
	$('.remove-pic-btn').click(function(){
		var input=$(this).parent().next('input[rel="img-input"]');

		input.val('');
		input.trigger("change");
		return false;
	});

	$('input[rel="img-input"]').each(function(){
		$(this).change(function(){
			var img=$($(this).parent().children('.preview-box').children('img'));
			if($(this).val()==''){
				var pic_url=img.data('default_pic');
				img.next('.remove-pic-btn').hide();
			}else{
				var pic_url=$(this).val();
//				console.log(img.next('.remove-pic-btn'));
				img.next('.remove-pic-btn').show();
			}
			img.attr('src', pic_url);
		});
	});

	$('#add-phmsg-btn').click(function(){
		add_phmsg_box();
		sort_phmsg_box();
		return false;
	});

	$('.remove-msg-box-btn').click(function(){
		remove_phmsg_box($(this).parent().parent());
		sort_phmsg_box();
		return false;
	});

	$('.up-msg-box-btn').click(function(){
		move_phmsg_box($(this).parent().parent(),true);
		sort_phmsg_box();
		return false;
	});

	$('.down-msg-box-btn').click(function(){
		move_phmsg_box($(this).parent().parent(),false);
		sort_phmsg_box();
		return false;
	});
	//create unquid   var id = new UUID();
    function UUID(){this.id=this.createUUID()}UUID.prototype.valueOf=function(){return this.id};UUID.prototype.toString=function(){return this.id};UUID.prototype.createUUID=function(){var c=new Date(1582,10,15,0,0,0,0);var f=new Date();var h=f.getTime()-c.getTime();var i=UUID.getIntegerBits(h,0,31);var g=UUID.getIntegerBits(h,32,47);var e=UUID.getIntegerBits(h,48,59)+"2";var b=UUID.getIntegerBits(UUID.rand(4095),0,7);var d=UUID.getIntegerBits(UUID.rand(4095),0,7);var a=UUID.getIntegerBits(UUID.rand(8191),0,7)+UUID.getIntegerBits(UUID.rand(8191),8,15)+UUID.getIntegerBits(UUID.rand(8191),0,7)+UUID.getIntegerBits(UUID.rand(8191),8,15)+UUID.getIntegerBits(UUID.rand(8191),0,15);return i+g+e+b+d+a};UUID.getIntegerBits=function(f,g,b){var a=UUID.returnBase(f,16);var d=new Array();var e="";var c=0;for(c=0;c<a.length;c++){d.push(a.substring(c,c+1))}for(c=Math.floor(g/4);c<=Math.floor(b/4);c++){if(!d[c]||d[c]==""){e+="0"}else{e+=d[c]}}return e};UUID.returnBase=function(c,d){var e=["0","1","2","3","4","5","6","7","8","9","A","B","C","D","E","F","G","H","I","J","K","L","M","N","O","P","Q","R","S","T","U","V","W","X","Y","Z"];if(c<d){var b=e[c]}else{var f=""+Math.floor(c/d);var a=c-f*d;if(f>=d){var b=this.returnBase(f,d)+e[a]}else{var b=e[f]+e[a]}}return b};UUID.rand=function(a){return Math.floor(Math.random()*a)}; 
	//when DOM is  ready, create a new UUID() for $('#phmsg-base .msg-box')[0]
	var oneid =  new UUID();
	var twoid =  new UUID();
	$($('#phmsg-base .msg-box')[0]).find("button.alert_dialog_include_posts").attr("tid",oneid);
	$($('#phmsg-base .msg-box')[0]).find(".phmsg-base-input-url:first").attr("id",oneid);
	$($('#phmsg-base .msg-box')[0]).find(".insert_resp_phmsg:first").attr("tid",twoid);
	$($('#phmsg-base .msg-box')[0]).attr("id",twoid);
	function add_phmsg_box(title,pic,des,url){
		var title = typeof title !== 'undefined' ? title : '';
		var pic = typeof pic !== 'undefined' ? pic : '';
		var des = typeof des !== 'undefined' ? des : '';
		var url = typeof url !== 'undefined' ? url : '';
        
		count_phmsg++;
		if(count_phmsg<=limit_phmsg && count_phmsg>0){
			var tpl=$($('#phmsg-base .msg-box')[0]);
			var clone=tpl.clone(true);
			var subtitle=clone.children('h3[rel="title"]').data('subtitle');
			clone.children('h3[rel="title"]').html(subtitle+'.'+count_phmsg);
            
			clone.find('.preview-box img').each(function(){
				$(this).attr('src', '');
			});
			//set button id className is .alert_dialog_include_posts when clone 
			var oneid =  new UUID();
			var twoid =  new UUID();
            clone.find("button.alert_dialog_include_posts").attr("tid",oneid);
            clone.find(".phmsg-base-input-url:first").attr("id",oneid);
            clone.find(".insert_resp_phmsg:first").attr("tid",twoid);
	        clone.attr("id",twoid).attr("wechat-small","yes");
			clone.find('input').each(function(){
				if($(this).attr('name')=='title[]'){
					$(this).val(title);
				}
				if($(this).attr('name')=='pic[]'){
					$(this).val(pic);
					$(this).trigger("change");
				}
				if($(this).attr('name')=='des[]'){
					$(this).val(des);
				}
				if($(this).attr('name')=='url[]'){
					$(this).val(url);
				}
			});
			$('#phmsg-group').append(clone);
		}
		if(count_phmsg>=limit_phmsg){
			$('#add-phmsg-btn').hide();
		}
	}

	function remove_phmsg_box(obj){
		obj.remove();
		count_phmsg--;
	}

	function move_phmsg_box(obj,direct){

		if(direct){
			var prv=obj.prev('.msg-box');
			if(prv!=''){
				prv.before(obj);
			}
		}else{
			var nex=obj.next('.msg-box');
			if(nex!=''){
				nex.after(obj);
			}
		}
	}

	function sort_phmsg_box(){
		var length=$('#phmsg-group .msg-box').length;
		for(var i=0; i<length;i++){
			var cur=$($('#phmsg-group .msg-box')[i]);
			var subtitle=cur.children('h3[rel="title"]').data('subtitle');
			var id = cur.attr("id");
			cur.children('h3[rel="title"]').html(subtitle+'.'+(i+1));
		}
	}

	<?php foreach($_phmsg_group as $item):?>
		add_phmsg_box('<?php echo $item->title;?>','<?php echo $item->pic;?>','<?php echo $item->des;?>','<?php echo $item->url;?>');
	<?php endforeach;?>


//set ajax request
    $(".alert_dialog_include_posts").live("click",function(e){
       var $this = $(this);
       var data = {
       	   action: 'add_foobar',
       	   tid   : $this.attr("tid"),
       	   rtype : $this.attr('rtype')
       }
       var admin_url = <?php echo "'".admin_url( 'admin-ajax.php' )."'";?>;
        $("#hide-modal").find(".hide-modal-body").html('<div id="dialog_content__container" style="width:inherit;margin:0px auto;border-radius:5px;"><table class="wp-list-table widefat fixed posts" style="min-height:100px;"><thead><tr><th style="text-align:center;height: 77px;">loading....</th></tr></thead></table></div>');
        $(this).attr("href","#hide-modal");
        $.fn.custombox( this, {
            effect: 'fadein',
            overlaySpeed : "100"
        });

        jQuery.get(admin_url,data,function(d,s){
            $("#dialog_content__container").html(d);
		    $("#paginate_div").find(".page-numbers").live("click",function(){
		       var $this = $(this);
		       var cur = $this.attr("href") ? ($this.attr("href")).substr(1) : "";
		           cur = cur ==""?1:cur;	
		       var data = {
		       	   action: 'add_foobar',
		       	   tid   : $("#hidden_post_tid").val(),
		       	   rtype : $("#hidden_post_type").val(),
		       	   ptype : $("#select_type_action").val(),
		       	   catid : $("#select_cate_action").val(),
		       	   key   : $("#hidden_search_key").val(),
		       	   cur : cur
		       }	
		       var admin_url = <?php echo "'".admin_url( 'admin-ajax.php' )."'";?>;
		       $.get(admin_url,data,function(d,s){
		           $("#dialog_content__container").html(d);
		           bindEvents();
		           return false;
		       });
		       return false;
		    });
            
            bindEvents();
	        });
            e.preventDefault();

        });
    $("#easydialog_close").live("click",function(){
    	$.fn.custombox('close');
    	return false;
    });
    //ajax to get content or url
    $(".insert_content_to_input").live("click",function(){
       var $this = $(this);
       var data = {
       	   action  :  'get_insert_content',
       	   postid  :  $this.attr("postid"),
       	   rtype   :  $("#hidden_post_type").val()
       }
       var tid = $this.attr('tid');
       if($("#"+tid).attr("wechat-small")=="yes"){
       	   data.imagesize = "small";
       }
       var admin_url = <?php echo "'".admin_url( 'admin-ajax.php' )."'";?>;
       jQuery.get(admin_url,data,function(d,s){
        try{
          d = JSON.parse(d)
        } catch(err){
          d = false
          throw "AJAX Sync Modal Load Json data faild";
        }
       	if(d){
       	   if(d.status="success"){
       	   	  if(data.rtype=="phmsg"){
                var $container = $("#"+tid);	
                if(d.data.pic && d.data.pic!="none"){
                  $container.find(".preview-box img").attr("src",d.data.pic);
                }
         	   	  $container.find("input[name='title[]']")
                .val(d.data.post_title);
         	   	  
                $container.find("input[name='pic[]']")
                .val(d.data.pic);
                
         	   	  $container.find("input[name='des[]']")
                .val(d.data.post_content);

         	   	  $container.find("input[name='url[]']").val(d.data.url);
       	   	  }else{
       	   	   $("#"+tid).val(d.data);
       	   	  }
       	   	  $.fn.custombox('close');
       	   }else{
       	   	  alert("Error:"+d.data);
       	   }
       	}else{
       		alert("Error:"+d);
       	}
       });
    }); 
    $("#post-search-key").live("focus",function(){
    	$(this).keypress(function(e){
    		if(e.which==13){
    			   var key = $("#post-search-key").val();
			       if($.trim(key)!=""){
			       $("#dialog_content__container").find("table:first").html("<thead><tr><th style='text-align:center;height: 77px;'>loading....</th></tr></thead>");
			       var data = {
					       	   action: 'add_foobar',
					       	   tid   : $("#hidden_post_tid").val(),
					       	   rtype   :  $("#hidden_post_type").val(),
					       	   key   : key
					       }
					var admin_url = <?php echo "'".admin_url( 'admin-ajax.php' )."'";?>;
					$.get(admin_url,data,function(d,s){
					$("#dialog_content__container").html(d);
					bindEvents();
					$("#post-search-key").select();
					    return false;
					});	
					}
    		}
    	});
    }); 
    //search posts
    $("#post-search-submit").live("click",function(){
       var key = $("#post-search-key").val();
       if($.trim(key)!=""){
       $("#dialog_content__container").find("table:first").html("<thead><tr><th style='text-align:center;height: 77px;'>loading....</th></tr></thead>");	
       var data = {
		       	   action: 'add_foobar',
		       	   tid   : $("#hidden_post_tid").val(),
		       	   rtype   :  $("#hidden_post_type").val(),
		       	   key   : key
		       }
		var admin_url = <?php echo "'".admin_url( 'admin-ajax.php' )."'";?>;
		$.get(admin_url,data,function(d,s){
		$("#dialog_content__container").html(d);
		bindEvents();
		$("#post-search-key").select();
		    return false;
		});	
		}
    });
    /***************
     *message type : recent
     ***************/
     $("#re_type_select").change(function(){
        var val = $(this).attr("value");
        var $tr = $("#re_cate_tr");
        switch(val){
        	case "post": $tr.show();break;
        	default    : $tr.hide();
        }
     });
    /***************
     *message type : random
     ***************/
     $("#rand_type_select").change(function(){
        var val = $(this).attr("value");
        var $tr = $("#rand_cate_tr");
        switch(val){
        	case "post": $tr.show();break;
        	default    : $tr.hide();
        }
     });
     /***************
     *message type : search
     ***************/
     $("#sh_type_select").change(function(){
        var val = $(this).attr("value");
        var $tr = $("#sh_cate_tr");
        switch(val){
        	case "post": $tr.show();break;
        	default    : $tr.hide();
        }
     });
    /**********
     *
     **********/
     function bindEvents(){
     	//set pagetype select option event
            $("#select_type_action").change(function(){
            	$("#dialog_content__container").find("table:first").html("<thead><tr><th style='text-align:center;height: 77px;'>loading....</th></tr></thead>");
            	var val = $(this).val();
            	var data = {
		       	   action: 'add_foobar',
		       	   tid   : $("#hidden_post_tid").val(),
		       	   rtype : $("#hidden_post_type").val(),
		       	   ptype : val
		       }
		       var admin_url = <?php echo "'".admin_url( 'admin-ajax.php' )."'";?>;
		       $.get(admin_url,data,function(d,s){
		           $("#dialog_content__container").html(d);
		           bindEvents();
		           return false;
		       });	 
            });

            //set cates select option event
            $("#select_cate_action").change(function(){
            	$("#dialog_content__container").find("table:first").html("<thead><tr><th style='text-align:center;height: 77px;'>loading....</th></tr></thead>");
            	var val = $(this).val();
            	var data = {
		       	   action: 'add_foobar',
		       	   tid   : $("#hidden_post_tid").val(),
		       	   rtype : $("#hidden_post_type").val(),
		       	   catid   : val
		       }
		       var admin_url = <?php echo "'".admin_url( 'admin-ajax.php' )."'";?>;
		       $.get(admin_url,data,function(d,s){
		           $("#dialog_content__container").html(d);
		           bindEvents();
		           return false;
		       });	 
            });
       }

});
</script>
