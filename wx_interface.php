<?php
/*
*
*微信插件token处理
*
*/
global $token;

$wechatObj = new wechatCallbackapi($token);

$valid=$wechatObj->valid();

if($valid){
	$wechatObj->saveMsgHistory();
	$wechatObj->responseMsg(get_data());
}else{
	header('Location: '.home_url());
}
exit;

class wechatCallbackapi{

	private $token;
	
	public function __construct($_token, $_data=null){
		$this->token=$_token;
		if($_data!=null){
			$this->load($_data);
		}
	}
	
	public function valid(){
		if(isset($_GET["echostr"])){
	    	$echoStr = $_GET["echostr"];
	    }
	    //valid signature , option
	    if($this->checkSignature()){
	    	if(isset($echoStr) && $echoStr!=''){
	    		echo $echoStr;
	    		exit;
	    	}
	    	return true;
	    }else{
	    	return false;
	    }
	}

  public function responseMsg($_data=null){
  
		$postStr = $GLOBALS["HTTP_RAW_POST_DATA"];

    //提取数据
		if (!empty($postStr) && $this->checkSignature()){
                
        $postObj = simplexml_load_string($postStr,
                                         'SimpleXMLElement',
                                         LIBXML_NOCDATA);
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
