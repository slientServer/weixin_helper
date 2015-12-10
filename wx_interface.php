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

    private function getHanderedData(){
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
		$postObj= $this->getHanderedData();
		if($postObj){
			switch ($postObj->MsgType) {
				case 'text':
					$messageRow = array(
							"openid"=>$postObj->FromUserName,
	                        "content"=>trim($postObj->Content),
	                        "msgType"=>$postObj->MsgType,
	                        "msgId"=>$postObj->MsgId,
	                        "createTime"=>$postObj->CreateTime);
					break;
				case 'image':
					$messageRow = array(
						"openid"=>$postObj->FromUserName,
	                    "content"=>($postObj->PicUrl.'$$'.$postObj->MediaId),
	                    "msgType"=>$postObj->MsgType,
	                    "msgId"=>$postObj->MsgId,
	                    "createTime"=>$postObj->CreateTime
						);
					break;
				case 'voice':
					$messageRow = array(
						"openid"=>$postObj->FromUserName,
	                    "content"=>($postObj->Format.'$$'.$postObj->MediaId),
	                    "msgType"=>$postObj->MsgType,
	                    "msgId"=>$postObj->MsgId,
	                    "createTime"=>$postObj->CreateTime
						);
					break;
				case 'video':
					$messageRow = array(
						"openid"=>$postObj->FromUserName,
	                    "content"=>($postObj->ThumbMediaId.'$$'.$postObj->MediaId),
	                    "msgType"=>$postObj->MsgType,
	                    "msgId"=>$postObj->MsgId,
	                    "createTime"=>$postObj->CreateTime
						);
					break;
				case 'shortvideo':
					$messageRow = array(
						"openid"=>$postObj->FromUserName,
	                    "content"=>($postObj->ThumbMediaId.'$$'.$postObj->MediaId),
	                    "msgType"=>$postObj->MsgType,
	                    "msgId"=>$postObj->MsgId,
	                    "createTime"=>$postObj->CreateTime
						);
					break;
				case 'location':
					$messageRow = array(
						"openid"=>$postObj->FromUserName,
	                    "content"=>($postObj->Location_X.'$$'.$postObj->Location_Y.'$$'.$postObj->Scale.'$$'.$postObj->Label),
	                    "msgType"=>$postObj->MsgType,
	                    "msgId"=>$postObj->MsgId,
	                    "createTime"=>$postObj->CreateTime
						);
					break;
				case 'link':
					$messageRow = array(
						"openid"=>$postObj->FromUserName,
	                    "content"=>($postObj->Title.'$$'.$postObj->Description.'$$'.$postObj->url),
	                    "msgType"=>$postObj->MsgType,
	                    "msgId"=>$postObj->MsgId,
	                    "createTime"=>$postObj->CreateTime
						);
					break;	
				default:
					# code...
					break;
			}
		}
	  
	    global $wpdb;
		$rows_affected = $wpdb->insert(DB_TABLE_WPWPH_HISTORY,$messageRow);
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
?>
