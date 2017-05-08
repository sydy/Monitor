<?php
namespace app\api\controller;

use controller\BasicCrond;
use think\Db;


/**
 * API操作类
 */
class Jd extends BasicCrond
{
    //API授权
    protected $app_url = 'https://api.jd.com/routerjson';
    protected $app_key = 'ED1D9F9672552367B20CD4012DF40BD2';
    protected $app_secret = '705d567e533a4f30a81c0479b4896254';
    protected $versions = '2.0';



    /**
     * 获取商城价格
     * @param  [type] $id 商品SKU
     * @return [type]     返回当前售价
     */
    public function price($id='') {
        $data['method']            = 'jingdong.ware.price.get';
        $data['app_key']           = $this->app_key;
        $data['timestamp']         = date('Y-m-d H:i:s',time());
        $data['360buy_param_json'] = json_encode(array('sku_id'=>'J_'.$id));
        $data['v']                 = $this->versions;
        $result = parent::curlApi($this->app_url,$data,'GET');
        $result = json_decode($result,true); //返回json信息转换成数组
        $result_data = $result['jingdong_ware_price_get_responce'];
        if ($result_data['code']=='0') {
            return $result_data['price_changes'][0]['price'];
        } else {
            return false;
        } 
    }

    /**
     * 获取产品名称
     * @param  string $id 商品SKU
     * @return [type]     返回产品名称
     */
    public function name($id='')
    {
        $data['method']            = 'jingdong.new.ware.baseproduct.get';
        $data['app_key']           = $this->app_key;
        $data['timestamp']         = date('Y-m-d H:i:s',time());
        $data['360buy_param_json'] = json_encode(array('ids'=>$id, 'basefields'=>'name'));
        $data['v']                 = $this->versions;
        $result = parent::curlApi($this->app_url,$data,'GET');
        $result = json_decode($result,true); //返回json信息转换成数组
        $result_data = $result['jingdong_new_ware_baseproduct_get_responce'];
        if ($result_data['code']=='0') {
            return $result_data['listproductbase_result'][0]['name'];
        } else {
            return false;
        } 
    }
}
