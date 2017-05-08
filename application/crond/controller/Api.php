<?php
namespace app\crond\controller;

use think\Controller;
use think\Db;


/**
 * API操作类
 */
class Api extends Controller
{
    /**
     * 京东产品售价
     * @param  [type] $id 产品SKU
     * @return [type]     返回价格
     */
    public function jd($id='',$type='') {
        if ($type=='price') {
            return action('api/Jd/price', ['id' => $id]);
        }
        if ($type=='name') {
            return action('api/Jd/name', ['id' => $id]);
        }
        
    }

 
}
