<?php
/*
 * 菜单管理页面
 *
 */
global $token;
$token= getAccessToken();

function requestMenu(){
	global $token;
	$url= "https://api.weixin.qq.com/cgi-bin/menu/get?access_token=".$token;
    $ch= curl_init($url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_BINARYTRANSFER, true) ;
    $res= curl_exec($ch);
    curl_close($ch);
    return $res;
}

if(isset($_POST['sectionCount'])){
	$sectionCount= $_POST['sectionCount'];
	$menu= [];
	for ($idx=0; $idx < $sectionCount; $idx++) {
		$subMenu= [];
		for($idy=0; $idy< 5; $idy++){
			if(!empty($_POST[$idx.'_subName_'.$idy]) && ($_POST[$idx.'_subName_'.$idy]!= 'none')){
				$subMenu[$idy]= getCorrectSubmenu($idx, $idy);
			}
		}
		if(!empty($_POST[$idx.'_menu'])){
			$menu[$idx]= getCorrectMainMenu($idx, $subMenu);
		}
	}
	$menuData= urldecode(json_encode(array('button'=>$menu)));
	createMenu($menuData);
}

function createMenu($menu){
	global $token;
	$url= "https://api.weixin.qq.com/cgi-bin/menu/create?access_token=".$token;
    $ch= curl_init($url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_BINARYTRANSFER, true) ;
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, $menu);
    $res= curl_exec($ch);
    curl_close($ch);
    return $res;
}


function getCorrectSubmenu($idx, $idy){
	$subMenu=[];
	if($_POST[$idx.'_subType_'.$idy]=='view'){
		$subMenu= array(
			'type'=> $_POST[$idx.'_subType_'.$idy],
			'name'=> urlencode($_POST[$idx.'_subName_'.$idy]),
			'url'=> urlencode($_POST[$idx.'_subKey_'.$idy]),
			'sub_button'=> []
		);
	}else if($_POST[$idx.'_subType_'.$idy]=='media_id' || $_POST[$idx.'_subType_'.$idy]=='view_limited'){
		$subMenu=array(
			'type'=> $_POST[$idx.'_subType_'.$idy],
			'name'=> urlencode($_POST[$idx.'_subName_'.$idy]),
			'media_id'=> $_POST[$idx.'_subKey_'.$idy],
			'sub_button'=> []
		);
	}else if($_POST[$idx.'_subType_'.$idy]!='none'){
		$subMenu=array(
			'type'=> $_POST[$idx.'_subType_'.$idy],
			'name'=> urlencode($_POST[$idx.'_subName_'.$idy]),
			'key'=> urlencode($_POST[$idx.'_subKey_'.$idy]),
			'sub_button'=> []
		);
	}
	return $subMenu; 
}

function getCorrectMainMenu($idx, $subMenu){
	if($_POST[$idx.'_type']!='none'){
		if($_POST[$idx.'_type']=='view'){
			return array(
				'type'=> $_POST[$idx.'_type'],
				'name'=> urlencode($_POST[$idx.'_menu']),
				'url'=> urlencode($_POST[$idx.'_key']),
				'sub_button'=> []
			);
		}else if($_POST[$idx.'_type']=='media_id' || $_POST[$idx.'_type']=='view_limited'){
			return array(
				'type'=> $_POST[$idx.'_type'],
				'name'=> urlencode($_POST[$idx.'_menu']),
				'media_id'=> $_POST[$idx.'_key'],
				'sub_button'=> []
			);
		}else{
			return array(
				'type'=> $_POST[$idx.'_type'],
				'name'=> urlencode($_POST[$idx.'_menu']),
				'key'=> urlencode($_POST[$idx.'_key']),
				'sub_button'=> []
			);
		}
	}else{
		return array(
			'name'=> urlencode($_POST[$idx.'_menu']),
			'sub_button'=> $subMenu
		);
	}
}

?>
<link href="<?php echo WPWPH_HELPER_URL;?>/css/style.css" rel="stylesheet">
<div class="wrap">
	<span class= "headerFont"><?php _e('微信菜单管理','WPWPH')?></span>
	<span class= "hintColor"><?php _e('[最多可以创建三组一级菜单， 每组一级菜单最多可以创建五组子菜单，一级菜单最多4个汉字，二级菜单最多7个汉字，多出来的部分将会以“...”代替]', 'WPWPH')?></span>
	<input class= "addNewMenu" value=<?php _e('添加菜单组') ?> type='button' width='20px'></input>
	<hr>
	<form action="" method="POST">
		<input type="hidden" name="sectionCount" id="sectionCount"></input> 
		<div id= 'menuSection'></div>
			<?php submit_button(); ?>
	</form>
</div>
<script>
jQuery(document).ready(function($){
	var menuData= <?php echo requestMenu();?>;
	var menuCount=0;

	function createMenuSection(idx, data){
		var menuData= data? data: '';
		var subData= menuData? menuData.sub_button: [{},{},{},{},{}];
		var container= document.createElement('div');
		container.setAttribute('class', 'block');
		container.innerHTML='<div class="inlineDisplay"><span class="firstMenu"><?php _e("一级菜单：", "WPWPH") ?></span>'+
							createMainInputGroup(idx, menuData.name, menuData.key, menuData.type)+'<input class= "deleteNewMenu" value=<?php _e("删除") ?> type="button" width="20px"></input></div>'+
							'<div class="inlineDisplay"><span class="subMenu"><?php _e("二级菜单：", "WPWPH") ?></span><div>'+
							createSubInputGroup(idx, 0, subData[0]?subData[0].name: '', getCorrectKey(subData, 0), subData[0]?subData[0].type: '')+
							createSubInputGroup(idx, 1, subData[1]?subData[1].name: '', getCorrectKey(subData, 1), subData[1]?subData[1].type: '')+
							createSubInputGroup(idx, 2, subData[2]?subData[2].name: '', getCorrectKey(subData, 2), subData[2]?subData[2].type: '')+
							createSubInputGroup(idx, 3, subData[3]?subData[3].name: '', getCorrectKey(subData, 3), subData[3]?subData[3].type: '')+
							createSubInputGroup(idx, 4, subData[4]?subData[4].name: '', getCorrectKey(subData, 4), subData[4]?subData[4].type: ''); 
		$('#menuSection').append(container);
		$('.deleteNewMenu').unbind("click");
		$('.deleteNewMenu').click(
			function(){
				this.parentElement.parentElement.remove()
				menuCount--;
				$('#sectionCount').val(menuCount);
				if (menuCount<3) {
					$('.addNewMenu').attr('disabled', false);
				}
			}
		)
	}

	function getCorrectKey(subData, idx){
		var curType= subData[idx]?subData[idx].type: '';
		if(curType== 'media_id' || curType== 'view_limited'){
			return subData[idx].media_id;
		}else if(curType== 'url'){
			return subData[idx].url;
		}else{
			return subData[idx]? subData[idx].key: '';
		}
	}

	//idx 主菜单序号，suff子菜单序号
	function createSubInputGroup(idx, suff, name, key, type){
		var menuName= name? name: '';
		var menuKey= key? key: '';
		var menuType= type? type: 'none';
		return '<div class="inlineDisplay">'+createTypeList(idx, menuType, suff)+'<input placeholder=<?php _e("请输入菜单名字", "WPWPH") ?> value="'+menuName+'" class="menuInput" type="input" name="'+idx+'_subName_'+suff+'"><input placeholder=<?php _e("菜单key/url(view类型)/media_id(素材类型)", "WPWPH") ?> class="menuInput" type="input" title="'+menuKey+'" value="'+menuKey+'" name="'+idx+'_subKey_'+suff+'">';
	}

	function createMainInputGroup(idx, name, key, type){
		var menuName= name? name: '';
		var menuKey= key? key: '';
		var menuType= type? type: 'none';
		return createTypeList(idx, menuType)+'<input placeholder=<?php _e("请输入菜单名字", "WPWPH") ?> class="menuInput" value="'+menuName+'" type="input" name="'+idx+'_menu"><input placeholder=<?php _e("菜单key/url(view类型)/media_id(素材类型)", "WPWPH") ?> class="menuInput" type="input" title="'+menuKey+'" value="'+menuKey+'" name="'+idx+'_key">';
	}

	function createTypeList(idx, menuType, suff){
		var typeName= typeof(suff)!='undefined'? idx+"_subType_"+suff: idx+"_type";
		return '<select name='+typeName+' class="typeList">'+
				'<option value="none"'+(menuType=="none"?"selected": "")+'><?php _e("无类型", "WPWPH") ?></option>'+
				'<option value="click"'+(menuType=="click"?"selected": "")+'><?php _e("点击类型(click)", "WPWPH") ?></option>'+
				'<option value="view"'+(menuType=="view"?"selected": "")+'><?php _e("跳转URL(view)", "WPWPH") ?></option>'+
				'<option value="scancode_push"'+(menuType=="scancode_push"?"selected": "")+'><?php _e("扫码类型(scancode_push)", "WPWPH") ?></option>'+
				'<option value="scancode_waitmsg"'+(menuType=="scancode_waitmsg"?"selected": "")+'><?php _e("扫码并上传类型(scancode_waitmsg)", "WPWPH") ?></option>'+
				'<option value="pic_sysphoto"'+(menuType=="pic_sysphoto"?"selected": "")+'><?php _e("拍照类型(pic_sysphoto)", "WPWPH") ?></option>'+
				'<option value="pic_photo_or_album"'+(menuType=="pic_photo_or_album"?"selected": "")+'><?php _e("拍照或选择类型(pic_photo_or_album)", "WPWPH") ?></option>'+
				'<option value="pic_weixin"'+(menuType=="pic_weixin"?"selected": "")+'><?php _e("微信相册类型(pic_weixin)", "WPWPH") ?></option>'+
				'<option value="location_select"'+(menuType=="location_select"?"selected": "")+'><?php _e("地理位置选择类型(location_select)", "WPWPH") ?></option>'+
				'<option value="media_id"'+(menuType=="media_id"?"selected": "")+'><?php _e("素材类型(media_id)", "WPWPH") ?></option>'+
				'<option value="view_limited"'+(menuType=="view_limited"?"selected": "")+'><?php _e("图文素材类型(view_limited)", "WPWPH") ?></option>'+
				'</select>';
	}

	$('.addNewMenu').click(
		function(){
			createMenuSection(menuCount);
			menuCount++;
			$('#sectionCount').val(menuCount);
			if (menuCount>=3) {
				$('.addNewMenu').attr('disabled', true);
			}
		}
	)

	function initMenuSection(menuData){
		if(menuData){
			for(var idx=0; idx< menuData['menu']['button'].length; idx++){
				createMenuSection(menuCount, menuData['menu']['button'][idx]);
				menuCount++;
				$('#sectionCount').val(menuCount);
			}
		}else{
			createMenuSection(menuCount);
			menuCount++;
			$('#sectionCount').val(menuCount);
		}
		
	}

	initMenuSection(menuData);
})

</script>