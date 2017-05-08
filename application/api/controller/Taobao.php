<?php
namespace app\api\controller;

use controller\BasicCrond;
use think\Db;


/**
 * API操作类
 */
class Taobao extends BasicCrond
{

    /**
     * 获取商城价格
     * @param  [type] $id 商品SKU
     * @return [type]     返回当前售价
     */
    public function price($param='536867340061:3204941837726') {
        $param = explode(':',$param); 
        $request_url = 'https://item.taobao.com/item.htm?id='.$param[0];
        $content = parent::curlWeb($request_url);

        $pattern = '/<strong id="J_StrPrice"><em class="tb-rmb">(.+)<\/em><em class="tb-rmb-num">(.+)<\/em><\/strong>/';
        preg_match($pattern, $content, $matches);
        $price = $matches[2];
        $cart = $matches[1];
        if (!empty($param[1])) {
            $pattern = '/price":"(\d+\.\d+)","skuId":"'.$param[1].'"/';
            preg_match($pattern, $content, $matches);
            $price = $matches[1];
        }
        echo $price;
        echo $cart;

        exit;
    }


}
