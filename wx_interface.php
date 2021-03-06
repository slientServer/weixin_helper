<?php
/*
*
*微信插件token处理
*
*/
global $token;

$wechatObj = new wechatCallbackapi($token, get_data());

$valid=$wechatObj->valid();

if($valid){
	$wechatObj->saveMsgHistory();
	$wechatObj->responseMsg();
}else{
	header('Location: '.home_url());
}
exit;

class wechatCallbackapi{

	private $token;
	
	public function __construct($_token, $_data=null){
		$this->token=$_token;
		if($_data!=null){
			$this->data= $_data;
		}
	}

	//debug专用函数
	public function setField($data){
		$messageRow = array("content"=>$data);
		global $wpdb;
		$rows_affected = $wpdb->insert("wpwph_debug", $messageRow);
	}
	
	public function valid(){
		if(isset($_GET["echostr"])){
	    	$echoStr = $_GET["echostr"];
	    }

	    //valid signature , option
	    if($this->checkSignature()){
	    	if(isset($echoStr) && $echoStr!=''){
				ob_clean();//解决url修改后token验证失败的问题
	    		echo $echoStr;
	    		exit;
	    	}
	    	return true;
	    }else{
	    	return false;
	    }
	}

  	public function responseMsg(){
    	//提取数据
		if ($this->checkSignature() && $this->data){

        	$postObj = $this->getHandleredData();
			$msgType=$postObj->MsgType;
			if($msgType=='event'){
				$msg=$this->eventRespon($postObj);
			}else{
				$msg=$this->sendAutoReply($postObj);
			}
			echo $msg;
        }else {
        	echo "";
        	exit;
        }
    }

    private function sendAutoReply($postObj){
	    $fromUsername = $postObj->FromUserName;
	    $toUsername = $postObj->ToUserName;
	    $keyword = trim($postObj->Content);
		$resultStr='';

		$is_match=false;
		if($keyword!=''){
			foreach($this->data as $d){
				if($d->trigger=='default' || $d->trigger=='subscribe'){
					continue;
				}
				$curr_key=$d->key;
				foreach($curr_key as $k){
					if(strtolower($keyword) == strtolower(trim($k))){
						$is_match=true;
					}
				}
				if($is_match){
					$resultStr =$this->get_msg_by_type($d, $fromUsername, $toUsername);
					break;
				}
				
			}
		}
		if(!$is_match){
			foreach($this->data as $d){
				if($d->trigger=='default'){
					$resultStr =$this->get_msg_by_type($d, $fromUsername, $toUsername); 
					break;
				}
			}
		}                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                            
		return $resultStr;
	}

    private function eventRespon($postObj){
		
	    $fromUsername = $postObj->FromUserName;
	    $toUsername = $postObj->ToUserName;
		$eventType=$postObj->Event;
		$eventKey=$postObj->EventKey;
		$resultStr='';
		$is_match=false;
		$is_subscribe=false;

		foreach($this->data as $d){
			if($d->trigger == $eventType){
				$is_subscribe= true;
				$resultStr =$this->get_msg_by_type($d, $fromUsername, $toUsername); 
				break;
			}
		}

		if(!$is_subscribe){
			if($eventKey!=''){
				foreach($this->data as $d){
					if($d->trigger!='event'){
						continue;
					}
					$curr_key=$d->key;
					foreach($curr_key as $k){
						if(strtolower($eventKey) == strtolower(trim($k))){
							$is_match=true;
						}
					}
					if($is_match){
						$resultStr =$this->get_msg_by_type($d, $fromUsername, $toUsername);
						break;
					}
					
				}
			}
			if(!$is_match){
				foreach($this->data as $d){
					if($d->trigger=='default'){
						$resultStr =$this->get_msg_by_type($d, $fromUsername, $toUsername); 
						break;
					}
				}
			}
		}

		return $resultStr;
	}

	private function get_msg_by_type($d, $fromUsername, $toUsername){
		switch($d->type){
			case "news"://图文消息
				$resultStr = $this->sendPhMsg($fromUsername, $toUsername, $d->phmsg);
			break;
			case "recent":
  		  		$messages = $this->getRecentlyPosts($d->remsg);
        		$resultStr = $this->sendMsgBase($fromUsername, $toUsername, $messages);
     		break;
    		case "random": 
        		$messages = $this->getRandomPosts($d->remsg);
        		$resultStr = $this->sendMsgBase($fromUsername, $toUsername, $messages);
			break;
			case "search":
        		$messages = $this->getSearchPosts($d->key[0], $d->remsg);
    			$resultStr = $this->sendMsgBase($fromUsername, $toUsername, $messages);
			break;
			default: //文本消息
				$resultStr = $this->sendMsg($fromUsername, $toUsername, $d->msg);
		}
		
		return $resultStr;
	}

	private function getSearchPosts($keyword, $contentData = null){
	  	if(!$contentData) return null;
	  	$re_type  = isset($contentData['type']) ?$contentData['type'] :"";
		$re_cate  = isset($contentData['cate']) ?$contentData['cate'] :"";
		$re_count = isset($contentData['count'])?$contentData['count']:6;
	    $args = array(
	  		'posts_per_page'      => $re_count,
	  		'orderby'             => 'post_date',
	      	'order'               => 'desc',
	      	's'                   => $keyword,
	        'ignore_sticky_posts'	=> 1,
			);
	    	if($re_type!=""){
		      	$args['post_type'] = $re_type;
		  		if($re_type=="post" && $re_cate!=""){
		        $args['category'] = $re_cate;
	  		}
	    }else{
	      $args['post_type'] = 'any';
	    }
	    $args['post_status'] = "publish";
      
	  $posts = get_posts($args);
	  return $posts;
  }

	private function getRandomPosts($contentData = null){
	  	if(!$contentData) return null;
	  	  $re_type  = isset($contentData['type']) ?$contentData['type'] :"";
		  $re_cate  = isset($contentData['cate']) ?$contentData['cate'] :"";
		  $re_count = isset($contentData['count'])?$contentData['count']:6;
	      $args = array(
	  		'posts_per_page'   => $re_count,
	  		'orderby'          => 'rand',
			);
	    if($re_type!=""){
	      	$args['post_type'] = $re_type;
	  		if($re_type=="post" && $re_cate!=""){
	        	$args['category'] = $re_cate;
	  		}
	    }else{
	      $args['post_type'] = 'any';
	    }
	    $args['post_status'] = "publish";
      
	  $posts = get_posts($args);
	  return $posts;
  }

	private function getRecentlyPosts($contentData = null){
	  	if(!$contentData) return null;
	  	$re_type  = isset($contentData['type']) ?$contentData['type'] :"";
		$re_cate  = isset($contentData['cate']) ?$contentData['cate'] :"";
		$re_count = isset($contentData['count'])?$contentData['count']:6;
	    $args = array(
	  		'posts_per_page'   => $re_count,
	  		'orderby'          => 'post_date',
	  		'order'            => 'desc',
			);
	    if($re_type!=""){
	      $args['post_type'] = $re_type;
	  	  if($re_type=="post" && $re_cate!=""){
	        $args['category'] = $re_cate;
	  	  }
	    }else{
	      $args['post_type'] = 'any';
	    }
	    $args['post_status'] = "publish";
	      
	  $posts = get_posts($args);
	  return $posts;
  	}

    private function sendMsgBase($fromUsername, $toUsername, $messages){
	    if(count($messages)>0){
	        $headerTpl = "<ToUserName><![CDATA[%s]]></ToUserName><FromUserName><![CDATA[%s]]></FromUserName><CreateTime>%s</CreateTime><MsgType><![CDATA[%s]]></MsgType><ArticleCount>%s</ArticleCount>";
				        
	  		$itemTpl = "<item><Title><![CDATA[%s]]></Title><Description><![CDATA[%s]]></Description><PicUrl><![CDATA[%s]]></PicUrl><Url><![CDATA[%s]]></Url></item>";

	  		$itemStr="";
	  		$mediaCount=0;
	  		$i=1;
	  		foreach ($messages as $mediaObject){ 		
	  		  $src_and_text = $this->getImgsSrcInPost($mediaObject->ID,
	                                                $mediaObject->post_content,
	                                                $i,
	                                                $mediaObject->post_type,
	                                                $mediaObject->post_excerpt);

	  			$title = wp_trim_words($mediaObject->post_title,SYNC_TITLE_LIMIT);
	  			$des  = $src_and_text['text'];  // strip_tags or not
	  			$media = $this->parseurl($src_and_text['src']);
		        if ($contentData['type']=="attachment"){
		          $url = home_url('/?attachment_id='.$mediaObject->ID);
		        }else{
		          $url = html_entity_decode(get_permalink($mediaObject->ID));
		        }

	  			$itemStr .= sprintf($itemTpl, $title, $des, $media, $url);
	  			$mediaCount++;
	  			$i++;
	  		}

	  		$msgType = "news";
	  		$time = time();
	  		$headerStr = sprintf($headerTpl,
	                           $fromUsername,
	                           $toUsername,
	                           $time,
	                           $msgType,
	                           $mediaCount);

	  		$resultStr ="<xml>".$headerStr."<Articles>".$itemStr."</Articles></xml>";

	    }else{
	      $textTpl = "<xml>
	        					<ToUserName><![CDATA[%s]]></ToUserName>
	        					<FromUserName><![CDATA[%s]]></FromUserName>
	        					<CreateTime>%s</CreateTime>
	        					<MsgType><![CDATA[%s]]></MsgType>
	        					<Content><![CDATA[%s]]></Content>
	        					<FuncFlag>0</FuncFlag>
	        					</xml>";
	  
	  		$msgType = "text";
	  		$time = time();
	  		$no_result=__('对不起，搜索失败.','WPWPH');
	      $resultStr = sprintf($textTpl,
	                           $fromUsername,
	                           $toUsername,
	                           $time,
	                           $msgType,
	                           $no_result);
	    }
	    return $resultStr;
  	}

  	private function getImgsSrcInPost($post_id=null,
                                    $post_content='',
                                    $i=1,
                                    $type='',
                                    $post_excerpt=''){
	  	$imageSize = $i == 1 ? "sup_wechat_big":"sup_wechat_small";
	  	$text = "";
	  	$rimg = WPWPH_HELPER_URL."/img/".$imageSize.".png";
	  	if($type=="attachment"){
	  	   $tmp_img_obj= wp_get_attachment_image_src($post_id,$imageSize);
	       $rimg = $tmp_img_obj[0];
	  	}else{	  		
	    	if(get_the_post_thumbnail($post_id)!=''){
		        $_tmp_id = get_post_thumbnail_id($post_id);
		        $tmp_img_obj=wp_get_attachment_image_src($_tmp_id, 
		                                                 $imageSize);
		        $rimg = $tmp_img_obj[0];
	  		}else{
	  			$attachments = get_posts( array(
	  				'post_type' => 'attachment',
	  				'posts_per_page' => -1,
	  				'post_parent' => $post_id,
	  				'exclude'     => get_post_thumbnail_id($post_id)
	  			));

	  			if(count($attachments)>0){
		  			$tmp_img_obj=wp_get_attachment_image_src($attachments[0]->ID,
		                                                   $imageSize);
		  			$rimg=$tmp_img_obj[0];
	  			}

	  		}
	  	}

	  	if(trim($post_excerpt)!=""){
	      $text = wp_trim_words($post_excerpt,SYNC_EXCERPT_LIMIT);
	    }else if(trim($post_content!="")){
	      $text = wp_trim_words($post_content,SYNC_EXCERPT_LIMIT);
		}

	  	$result = array("src"=>$rimg,"text"=>$text);

	    return $result;
  	}

	private function sendPhMsg($fromUsername, $toUsername, $contentData){
		if($contentData==''){
			return '';
		}

    	$headerTemplate= "<ToUserName><![CDATA[%s]]></ToUserName><FromUserName><![CDATA[%s]]></FromUserName><CreateTime>%s</CreateTime>
        	        <MsgType><![CDATA[%s]]></MsgType><ArticleCount>%s</ArticleCount>";
			        
		$itemTemplate=  "<item><Title><![CDATA[%s]]></Title><Description><![CDATA[%s]]></Description><PicUrl><![CDATA[%s]]></PicUrl>
      					<Url><![CDATA[%s]]></Url></item>";
		$itemStr="";
		$mediaCount=0;
		foreach ($contentData as $mediaObject){
			$title=$mediaObject->title;
			$des= $mediaObject->des;
			$media=$this->parseurl($mediaObject->pic);
			$url=$mediaObject->url;
			$itemStr .= sprintf($itemTemplate, $title, $des, $media, $url);
			$mediaCount++;
		}

		$msgType = "news";
		$time = time();
		$headerStr = sprintf($headerTemplate,
                         $fromUsername,
                         $toUsername,
                         $time,
                         $msgType,
                         $mediaCount);

		$resultStr ="<xml>".$headerStr."<Articles>".$itemStr."</Articles></xml>";

		return $resultStr;
	}

	private function parseurl($url=""){
	    $url = rawurlencode($url);
	    $a = array("%3A", "%2F", "%40");
	    $b = array(":", "/", "@");
	    $url = str_replace($a, $b, $url);
	    return $url;
    }

	private function sendMsg($fromUsername, $toUsername, $contentData){

		if($contentData==''){
			return '';
		}
	
    	$textTpl = "<xml>
          			<ToUserName><![CDATA[%s]]></ToUserName>
          			<FromUserName><![CDATA[%s]]></FromUserName>
          			<CreateTime>%s</CreateTime>
          			<MsgType><![CDATA[%s]]></MsgType>
          			<Content><![CDATA[%s]]></Content>
          			</xml>";

		$msgType = "text";
		$time = time();
		$resultStr = sprintf($textTpl,
                         $fromUsername,
                         $toUsername,
                         $time,
                         $msgType,
                         $contentData);
		return $resultStr;
	}	

    private function getHandleredData(){
    	$postStr = $GLOBALS["HTTP_RAW_POST_DATA"];

    	//提取数据
		if (!empty($postStr) && $this->checkSignature()){
                
	        $postObj = simplexml_load_string($postStr,
	                                         'SimpleXMLElement',
	                                         LIBXML_NOCDATA);

        	return $postObj;
    	}else{
    		return '';
    	}
    }

    public function format($data){
    	$str='';
    	foreach ($data as $key => $value) {
    		$str= $str.$key.'/'.$value.';';
    	}
    	return $str;
    }

	public function saveMsgHistory(){
		$postObj= $this->getHandleredData();
		if($postObj){
			switch ($postObj->MsgType) {
				case 'text':
					$messageRow = array(
						"openid"=>$postObj->FromUserName,
	                    "content"=>'{Content:'.trim($postObj->Content).'}',
	                    "msgType"=>$postObj->MsgType,
	                    "msgId"=>$postObj->MsgId,
	                    "createTime"=>$postObj->CreateTime);
					break;
				case 'image':
					$messageRow = array(
						"openid"=>$postObj->FromUserName,
	                    "content"=>'{PicUrl:'.$postObj->PicUrl.', MediaId:'.$postObj->MediaId.'}',
	                    "msgType"=>$postObj->MsgType,
	                    "msgId"=>$postObj->MsgId,
	                    "createTime"=>$postObj->CreateTime
						);
					break;
				case 'voice':
					$messageRow = array(
						"openid"=>$postObj->FromUserName,
	                    "content"=>'{Format:'.$postObj->Format.', MediaId:'.$postObj->MediaId.'}',
	                    "msgType"=>$postObj->MsgType,
	                    "msgId"=>$postObj->MsgId,
	                    "createTime"=>$postObj->CreateTime
						);
					break;
				case 'video':
					$messageRow = array(
						"openid"=>$postObj->FromUserName,
	                    "content"=>'{ThumbMediaId:'.$postObj->ThumbMediaId.', MediaId:'.$postObj->MediaId.'}',
	                    "msgType"=>$postObj->MsgType,
	                    "msgId"=>$postObj->MsgId,
	                    "createTime"=>$postObj->CreateTime
						);
					break;
				case 'shortvideo':
					$messageRow = array(
						"openid"=>$postObj->FromUserName,
	                    "content"=>'{ThumbMediaId:'.$postObj->ThumbMediaId.', MediaId:'.$postObj->MediaId.'}',
	                    "msgType"=>$postObj->MsgType,
	                    "msgId"=>$postObj->MsgId,
	                    "createTime"=>$postObj->CreateTime
						);
					break;
				case 'location':
					$messageRow = array(
						"openid"=>$postObj->FromUserName,
	                    "content"=>'{Location_X:'.$postObj->Location_X.', Location_Y:'.$postObj->Location_Y.', Scale:'.$postObj->Scale.', Label:'.$postObj->Label.'}',
	                    "msgType"=>$postObj->MsgType,
	                    "msgId"=>$postObj->MsgId,
	                    "createTime"=>$postObj->CreateTime
						);
					break;
				case 'link':
					$messageRow = array(
						"openid"=>$postObj->FromUserName,
	                    "content"=>'{Title:'.$postObj->Title.', Description:'.$postObj->Description.', url:'.$postObj->url.'}',
	                    "msgType"=>$postObj->MsgType,
	                    "msgId"=>$postObj->MsgId,
	                    "createTime"=>$postObj->CreateTime
						);
					break;
				case 'event':
					$messageRow= $this->saveEventMsg($postObj);
					break;
				default:
					# code...
					break;
			}
		}

	    global $wpdb;
		$rows_affected = $wpdb->insert(DB_TABLE_WPWPH_HISTORY, $messageRow);
	}

	private function saveEventMsg($postObj){
		switch ($postObj->Event) {
			case 'subscribe':
			case 'unsubscribe':
				if(isset($postObj->EventKey)){
					$messageRow = array(
						"openid"=>$postObj->FromUserName,
	                    "content"=>'{Event:'.$postObj->Event.', EventKey:'.$postObj->EventKey.', Ticket:'.$postObj->Ticket.'}',
	                    "msgType"=>$postObj->MsgType,
	                    "msgId"=>$postObj->Event,
	                    "createTime"=>$postObj->CreateTime
						);//未关注扫描二维码关注
				}else{
					$messageRow = array(
						"openid"=>$postObj->FromUserName,
	                    "content"=>'{Event:'.$postObj->Event.'}',
	                    "msgType"=>$postObj->MsgType,
	                    "msgId"=>$postObj->Event,
	                    "createTime"=>$postObj->CreateTime
						);//关注
				}
				break;
			case 'SCAN':
				$messageRow = array(
						"openid"=>$postObj->FromUserName,
	                    "content"=>'{Event:'.$postObj->Event.', EventKey:'.$postObj->EventKey.', Ticket:'.$postObj->Ticket.'}',
	                    "msgType"=>$postObj->MsgType,
	                    "msgId"=>$postObj->Event,
	                    "createTime"=>$postObj->CreateTime
						);//已关注扫描二维码
				break;	
			case 'LOCATION':
				$messageRow = array(
						"openid"=>$postObj->FromUserName,
	                    "content"=>'{Event:'.$postObj->Event.', Latitude:'.$postObj->Latitude.', Longitude:'.$postObj->Longitude.', Precision:'.$postObj->Precision.'}',
	                    "msgType"=>$postObj->MsgType,
	                    "msgId"=>$postObj->Event,
	                    "createTime"=>$postObj->CreateTime
						);
				break;
			case 'CLICK':
				$messageRow = array(
						"openid"=>$postObj->FromUserName,
	                    "content"=>'{Event:'.$postObj->Event.', EventKey:'.$postObj->EventKey.'}',
	                    "msgType"=>$postObj->MsgType,
	                    "msgId"=>$postObj->Event,
	                    "createTime"=>$postObj->CreateTime
						);
				break;
			case 'VIEW':
				$messageRow = array(
						"openid"=>$postObj->FromUserName,
	                    "content"=>'{Event:'.$postObj->Event.', EventKey:'.$postObj->EventKey.'}',
	                    "msgType"=>$postObj->MsgType,
	                    "msgId"=>$postObj->Event,
	                    "createTime"=>$postObj->CreateTime
						);
				break;
			default:
				# code...
				break;
		}
		return $messageRow;

	}

	private function checkSignature(){
		$signature =isset($_GET["signature"])?$_GET["signature"]:sha1( $this->token);
		$timestamp =isset($_GET["timestamp"])?$_GET["timestamp"]:'';
    	$nonce = isset($_GET["nonce"])?$_GET["nonce"]:'';	
        
		$token = $this->token;
		$tmpArr = array($token, $timestamp, $nonce);
		sort($tmpArr,SORT_STRING);
		$tmpStr = implode( $tmpArr );
		$tmpStr = sha1( $tmpStr );

		if( $tmpStr == $signature ){
			return true;
		}else{
			return false;
		}
	}
}

function get_data(){
	$args = array(
			'post_type' => 'wpwph_template',
			'posts_per_page' => -1,
			'orderby' => 'date',
			'post_status' => 'publish',
			'order'=> 'DESC'
	);
	
	$raw=get_posts($args);

	$data = array();
	
	foreach($raw as $p){

		$_gp=get_post_meta($p->ID,'_phmsg_item');
		$phmsg_group=array();
		
		foreach($_gp as $_item){
			$_tmp_item=json_decode($_item);
			
			$_tmp_item->title=urldecode($_tmp_item->title);
			$_tmp_item->pic=urldecode($_tmp_item->pic);
			$_tmp_item->des=urldecode($_tmp_item->des);
			$_tmp_item->url=urldecode($_tmp_item->url);
		
			$phmsg_group[]=$_tmp_item;
		}
		$tmp_key=trim(get_post_meta($p->ID,'_keyword',TRUE));
		$array_key=explode(',', $tmp_key);
		
		
		$tmp_msg=new stdClass();
		
		$tmp_msg->title=$p->post_title;
		$tmp_msg->type=get_post_meta($p->ID,'_type',TRUE);
		$tmp_msg->key=$array_key;
		$tmp_msg->trigger=get_post_meta($p->ID,'_trigger',TRUE);
		$tmp_msg->msg=get_post_meta($p->ID,'_content',TRUE);
		$tmp_msg->phmsg=$phmsg_group;
		
		//response source
		$tmp_msg->remsg=array(
			                  "type"=>get_post_meta($p->ID,'_re_type',TRUE),
			                  "cate"=>get_post_meta($p->ID,'_re_cate',TRUE),
			                  "count"=>get_post_meta($p->ID,'_re_count',TRUE)
			                  );

    $data[]=$tmp_msg;
	}
	return $data;
}
?>
