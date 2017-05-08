<?php
namespace app\api\controller;

use controller\BasicCrond;
use think\Db;


/**
 * API操作类
 */
class Tb extends BasicCrond
{
    //API授权
    protected $app_url = 'https://eco.taobao.com/router/rest';
    protected $app_key = '23305023';
    protected $app_secret = '5601e929b03f8e40aaf395b50ab47069';
    protected $versions = '2.0';



    /**
     * 获取商城价格
     * @param  [type] $id 商品SKU
     * @return [type]     返回当前售价
     */
    public function price($id='') {
        $data['method']      = 'taobao.product.get';
        $data['app_key']     = $this->app_key;
        $data['sign_method'] = 'md5';
        $data['timestamp']   = date('Y-m-d H:i:s',time());
        $data['format']      = 'json';
        $data['v']           = $this->versions;
        $data['fields']      = 'product_id,outer_id';
        $data['product_id']  = '541161521502';
        $data['sign']        = $this->generateSign($data);
        
        $result = parent::curlApi($this->app_url,$data,'GET');
        $result = json_decode($result,true); //返回json信息转换成数组
        echo '<pre>';
        print_r($result);
        echo '</pre>';
        exit;
        if ($result_data['code']=='0') {
            return $result_data['price_changes'][0]['price'];
        } else {
            return false;
        } 
    }


    public function getItem($id='') {
        //方法名
        $data['method']  = 'taobao.product.get';
        //公共参数
        $data            = $this->publicParams($data);
        //业务参数
        $data['fields']  = 'num_iid,title,price';
        $data['product_id'] = '541161521502';
        //签名
        $data['sign']    = $this->generateSign($data);
        //发送请求
        $result = parent::curlApi($this->app_url,$data,'GET');
        $result = json_decode($result,true); //返回json信息转换成数组
        echo '<pre>';
        print_r($result);
        echo '</pre>';
        exit;
        if ($result_data['code']=='0') {
            return $result_data['price_changes'][0]['price'];
        } else {
            return false;
        } 
    }


    public function createAccount($value='')
    {
        //方法名
        $data['method']  = 'taobao.open.account.create';
        //公共参数
        $data            = $this->publicParams($data);
        //业务参数
        $data['login_id']    = '失意的羊0';
        $data['login_pwd']    = 'ocean@';
        //签名
        $data['sign']    = $this->generateSign($data);
        //发送请求
        $result = parent::curlApi($this->app_url,$data,'POST');
        //$result = json_decode($result,true); //返回json信息转换成数组
        echo '<pre>';
        print_r($result);
        echo '</pre>';
        exit;
    }

    public function applyToken() {
        //方法名
        $data['method']  = 'taobao.open.account.list';
        //公共参数
        $data            = $this->publicParams($data);
        //业务参数

        //签名
        $data['sign']    = $this->generateSign($data);
        //发送请求
        $result = parent::curlApi($this->app_url,$data,'GET');
        $result = json_decode($result,true); //返回json信息转换成数组
        echo '<pre>';
        print_r($result);
        echo '</pre>';
        exit;
        if ($result_data['code']=='0') {
            return $result_data['price_changes'][0]['price'];
        } else {
            return false;
        } 
    }

    /**
     * 设置公共参数
     * @param  string $data 参数数组
     * @return [type]       返回追加公共参数的数组
     */
    protected function publicParams($data='')
    {
        $data['app_key']     = $this->app_key;
        $data['sign_method'] = 'md5';
        $data['timestamp']   = date('Y-m-d H:i:s',time());
        $data['format']      = 'json';
        $data['v']           = $this->versions;
        return $data;
    }

    /**
     * 生成签名
     * @param  [type] $params 参数
     * @return [type]         签名
     */
    protected function generateSign($params)
    {
        ksort($params);

        $stringToBeSigned = $this->app_secret;
        foreach ($params as $k => $v)
        {
            if(is_string($v) && "@" != substr($v, 0, 1))
            {
                $stringToBeSigned .= "$k$v";
            }
        }
        unset($k, $v);
        $stringToBeSigned .= $this->app_secret;

        return strtoupper(md5($stringToBeSigned));
    }


}
