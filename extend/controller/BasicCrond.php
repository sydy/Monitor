<?php
namespace controller;

use think\Controller;
use think\db\Query;
use think\Db;

class BasicCrond extends Controller
{

	/**
	 * 通过curl请求数据
	 * @param  string  $url     获取的地址链接
	 * @param  string  $headers Herders头信息
	 * @param  array   $data    POSH提交的数据
	 * @param  integer $timeout 超时时间
	 * @return [type]           获取的页面内容
	 */
	public function curlWeb($url='' , $headers='', $data=array(), $timeout=600) 
	{
		if (empty($url)) { return ''; }
		$ch = curl_init();
		//是否POST数据
		if (!empty($data)) {
			curl_setopt($ch, CURLOPT_POST, 1);// 发送一个常规的Post请求
			curl_setopt($ch, CURLOPT_POSTFIELDS, $data);// Post提交的数据包
		}
		if (!empty($headers)) {
	    	curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);	// 设置Referer
	    }
		curl_setopt($ch, CURLOPT_URL, $url);// 要访问的地址
	    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 0); // 对认证证书来源的检查
	    curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 0); // 从证书中检查SSL加密算法是否存在
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);// 获取的信息以文件流的形式返回
	    curl_setopt($ch, CURLOPT_USERAGENT,"Mozilla/5.0 (Windows NT 6.1; WOW64; rv:49.0) Gecko/20100101 Firefox/49.0");
		curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, $timeout); //发起连接前等待的时间
		curl_setopt($ch, CURLOPT_TIMEOUT, $timeout);// 设置超时限制防止死循环
		$result = curl_exec($ch);// 执行操作
		curl_close($ch);
		return $result;
	}

	/**
	 * 发送短信内容
	 * @param  string $name 任务名称
	 * @param  string $num  接收信息号码
	 * @return [type]       返回发送结果
	 */
	public function sendSMS($name='空', $num='15919733389')
	{
		$sms_url = 'http://sms.harker.cn/index.php?verify=harker&num='.$num.'&name='.$name;
		return $sms_url;
	}

	/**
	 * 通过curl发送API请求
	 * @param  string  $url     获取的地址链接
	 * @param  string  $headers Herders头信息
	 * @param  array   $data    POSH提交的数据
	 * @param  array   $request_type    请求类型
	 * @param  integer $timeout 超时时间
	 * @return [type]           获取的页面内容
	 */
	public function curlApi($url='' , $add_data=array(), $request_type = 'POST', $timeout=600)
	{
	    $add_string = http_build_query($add_data);  
	    $ch = curl_init();
	    if ($request_type=='POST') {
		    curl_setopt($ch, CURLOPT_POST, true);// 发送一个常规的Post请求
	        curl_setopt($ch, CURLOPT_POSTFIELDS, $add_string);// Post提交的数据包
	    }
	    if ($request_type=='GET') {
	    	$url = $url.'?'.$add_string;
	    }

	    curl_setopt($ch, CURLOPT_URL, $url);// 要访问的地址
	    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 0); // 对认证证书来源的检查
	    curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 0); // 从证书中检查SSL加密算法是否存在
	    curl_setopt($ch, CURLOPT_AUTOREFERER, 1); // 自动设置Referer
	    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);// 获取的信息以文件流的形式返回
	    curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, $timeout); //发起连接前等待的时间
	    curl_setopt($ch, CURLOPT_TIMEOUT, $timeout);// 设置超时限制防止死循环
	    $result = curl_exec($ch);// 执行操作
	    curl_close($ch);
		return $result;
	}
}
