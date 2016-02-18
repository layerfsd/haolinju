<?php

namespace Justsy\OpenAPIBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Request;
use Justsy\BaseBundle\Common\Utils;
use Justsy\BaseBundle\Common\DES;
use Justsy\InterfaceBundle\Common\ReturnCode;
use Justsy\BaseBundle\Management\Staff;
use Justsy\BaseBundle\Common\Cache_Enterprise;
use SoapClient;

class SsoExmailController  extends Controller  implements ISso
{

	public static $bind_type = "Exmail";
	public static function ssoAction($controller,$conn,$appid,$openid,$token,$encrypt)
	{
		//重新授权
		$app = new \Justsy\BaseBundle\Management\App($controller->container);
		$appdata = $app->getappinfo(array("appid"=>$appid));
		if(empty($appdata))
		{
			$resp = new Response("无效的APPID");
	   		$resp->headers->set('Content-Type', 'text/html');
	   		return $resp;
		}
		$agent = $appdata["clientid"];
		if(empty($agent))
		{
			$resp = new Response("未正确配置认证信息的appkey项");
	   		$resp->headers->set('Content-Type', 'text/html');
	   		return $resp;
		}		
		//判断是否绑定
		$bindinfo = $app->getappbind(array(
	            	"appid"=>$appid,
	            	"openid"=>$openid
	            ));
	    if(empty($bindinfo))
	    {
	    	//$controller->get("logger")->err("================not bind");
	    	//重定向到绑定页面
	    	return $controller->render("JustsyBaseBundle:AppCenter:h5bundle.html.twig",
  	 	      array('appid'=> $appid,
  	 	      'openid'=>$openid,
  	 	      'ssomodule'=>self::$bind_type."Controller"));
	    }

		$ldap_uid = $bindinfo["bind_uid"];

		$cacheKey = md5($appid.$openid);
		$data=Cache_Enterprise::get(Cache_Enterprise::$EN_OAUTH2,$cacheKey,$this->containerObj);
		$acctoken = $data["access_token"];
		//获取authkey
		$url = "http://openapi.exmail.qq.com:12211/openapi/mail/authkey";
		$authkey = Utils::do_post_request($url,"alias=".$ldap_uid."&access_token=".$acctoken);
    	if(empty($authkey))
    	{
			$resp = new Response("腾讯企业邮箱登录失败");
	   		$resp->headers->set('Content-Type', 'text/html');
	   		return $resp;    		
    	}
    	$authkey = json_decode($authkey,true);
    	if(!isset($authkey))
    	{
			$resp = new Response("腾讯企业邮箱登录失败:<br>".json_encode($authkey));
	   		$resp->headers->set('Content-Type', 'text/html');
	   		return $resp;    		
    	}
    	$authkey = $authkey["auth_key"];
    	$login_url = "https://exmail.qq.com/cgi-bin/login?fun=bizopenssologin&method=bizauth&agent=".$agent."&user=".$ldap_uid."&ticket=".$authkey;
    	return Utils::http_redirect($login_url);
	}

	public static function tokenAction($controller,$con,$appid,$openid,$encrypt)
	{

	}
	public static function bindTitleAction($controller,$con,$appid,$openid,$encrypt)
	{
		return "请输入员工工号：";
	}
	public static function directUrlAction($container)
	{
		return self::$direct_url;
	}
	public static function bindAction($controller,$con,$appid,$openid,$params)
	{
		$re = array("returncode"=>"0000");
		try
		{
			$bindinfo = $params->get("auth");
			$bindinfo = explode(",", $bindinfo);
			$bind_uid = $bindinfo[0];
			$authkey = count($bindinfo)==1?"":DES::encrypt($bindinfo[1]);			
			$app = new \Justsy\BaseBundle\Management\App($controller->container);
		    
			$appdata = $app->getappinfo(array("appid"=>$appid));//获取应用信息
			//自动身份认证			
			$cookie_key= self::$bind_type."_".$openid;
			$loginUrl = $appdata["authorization_url"];
			if(!empty($loginUrl))
			{
				$authResult = Utils::do_get_request_cookie($loginUrl."&".http_build_query(array("uid"=>$bind_uid,"upwd"=>md5(DES::decrypt($authkey)))),
		            	null,
		            	null,
		            	$cookie_key);
				$authResult = json_decode($authResult,true);
				if(!isset($authResult["islogin"]) || $authResult["islogin"]!="1")
				{
			        return $controller->render("JustsyBaseBundle:AppCenter:h5bundle.html.twig",
	  	 	      		array(	'appid'=> $appid,
	  	 	      				'openid'=>$openid,
	  	 	      				'errormsg'=>'绑定的帐号或密码不正确',
	  	 	      				'ssomodule'=>self::$bind_type."Controller"));
				}
			}
			$app->setappbind(array(	"appid"=>$appid,
		        					"openid"=>$openid,
		        					"bind_type"=>self::$bind_type,
		        					"bind_uid"=>$bind_uid,
		        					"authkey"=>$authkey
		    ));
		}
		catch(\Exception $e)
		{
			$response = new Response($e->getMessage());
        	$response->headers->set('Content-Type', 'text/html');
        	return $response;
		}
		return self::responseJson(json_encode($re));
	}
	public static function bindBatAction($controller,$con,$appid,$eno,$encrypt,$params)
	{		
	}

	public static function rest($controller,$user,$re,$parameters,$need_params)
	{
		$appid = $parameters["appid"];
		$openid = $user->openid;
		$cookie_key= self::$bind_type."_".$openid;
		$restUrl = $re["inf_url"];
		$str_para = array();
		$app = new \Justsy\BaseBundle\Management\App($controller);
		$bindinfo = $app->getappbind(array("appid"=>$appid,"openid"=>$openid));
		if (!empty($parameters) )
	    {
	        //将参数数组转化为字符串
	        if ( is_array($parameters) && !empty($need_params))
	        {
	        	$parameters["uid"] = $bindinfo["bind_uid"];
	            for ($i=0; $i <count($need_params) ; $i++) {
	              	$pname = $need_params[$i]["paramname"];
	              	$val = isset($parameters[$pname]) ? $parameters[$pname] : $need_params[$i]["paramvalue"];	              	 
	                $str_para[$pname]=$val;
	            }
	        }
	    }
	    if(strpos($restUrl,"?")===false)
	    {
	    	$restUrl = $restUrl."?".http_build_query($str_para);
	    }
		else
		{
			$restUrl = $restUrl."&".http_build_query($str_para);
		}
	    $controller->get("logger")->err("===============restUrl:".$restUrl);
		$re = Utils::do_post_request_cookie($restUrl,null,null,$cookie_key);
		//session过期时自动登录
		/*$sessionActive = true;
		if(!$sessionActive)
		{
			
			$appinfo = $app->getappinfo(array("appid"=>$appid));			
			$loginUrl = $appdata["authorization_url"];
			//登录
			$authResult = Utils::do_get_request_cookie($loginUrl."&".http_build_query(array("uid"=>$bindinfo["bind_uid"],"upwd"=>md5(DES::decrypt($bindinfo["authkey"])))),
	            	null,
	            	null,
	            	$cookie_key);
			//重新提交
	        $re = Utils::do_post_request_cookie($restUrl."&".http_build_query($str_para),null,null,$cookie_key);
		}*/
		return $re;
	}

	private function responseJson($re){
		$re = "<script>if(navigator.userAgent.indexOf('Android')!=-1) {window.wefafa.onAuthResult('".$re."');} else {onAuthResult('".$re."');}</script>";
        $response = new Response($re);
        $response->headers->set('Content-Type', 'text/html');
        return $response;
    }	
}