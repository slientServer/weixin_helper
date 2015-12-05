<?php
/*
 * Settings Page
 *
 */

$options=$this->options;

$fields=array('token');

foreach($fields as $field){
	if(!isset($options[$field])){
		$options[$field]='';
	}
}
$interface_url=isset($options['token']) && $options['token']!=''?home_url().'/?'.$options['token']:'请输入Token';

?>
<link href="<?php echo WPWPH_HELPER_URL;?>/css/style.css" rel="stylesheet">
<div class="wrap">
	<h2><?php _e('微信公众平台初始化设置','WPWPH')?></h2>
	<form action="options.php" method="POST">
		<?php settings_fields( $this->option_group );?>
		<?php do_settings_sections( $this->page_slug );?>
		<hr>
		<h4><?php _e('Token设置','WPWPH')?></h4>
		<table class="form-table">  
	        <tr valign="top">
		        <th scope="row"><label>Token</label></th>
		        <td>
		        	<input type="text" size="30" name="<?php echo $this->option_name ;?>[token]" value="<?php echo $options['token'];?>" class="regular-text"/>
		        	<p class="description"><?php _e('请输入微信公众平台Token。','WPWPH')?></p>
		        </td>
	        </tr>
	        <tr valign="top">
		        <th scope="row"><label>URL</label></th>
		        <td>
		        	<h4><?php echo $interface_url;?></h4>
		        	<p class="description"><?php _e('请输入微信公众平台Token,保存设置以后使用这个URL绑定到你的微信公众平台订阅号。','WPWPH')?></p>
		        </td>
	        </tr>
	    </table>
		
		<?php submit_button(); ?>
	</form>
</div>