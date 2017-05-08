<?php
namespace app\crond\controller;

use controller\BasicCrond;
use think\Db;

/**
 * 价格监控任务执行控制器
 */
class Task extends BasicCrond
{
    //属性编码
    protected $attribute = '';

    /**
     * 执行一个监控任务
     * @param  [type] $id 任务ID
     */
    public function index($id) {
    	$task = Db::name('MonitorTask')->where('id', $id)->find();
        $site = Db::name('MonitorSite')->where('id', $task['site_id'])->find();
    	$site_rule = Db::name('MonitorSiteRule')->where('site_id', $site['id'])->order('number ASC')->select();
        if ($site['return_type']=='api') {
            //调用API获取价格
            $data['price'] = action('crond/Api/'.$site['abbreviated'], ['id' => $task['param'], 'type'=>'price']);
        } else {

            //请求页面地址
            $request_url = $site['domain'].$site['path'].$this->urlParam($task['param']);
            //获取页面内容
            $content = parent::curlWeb($request_url);
            //内容编码处理
            $data = $this->contentToCoding( $content, $site['return_type'] );
            //处理过滤规则
            $data = $this->siteRule( $site_rule, $data );
        }
        //价格检查
        $check_price = ($data['price'] <= $task['goal_price']) ? true : false ;
        //保存运行日志
        Db::name('MonitorTaskLog')
            ->insert([
                'task_id'      => $task['id'],
                'start_price'  => $task['start_price'],
                'online_price' => $data['price'],
                'run_time'     => date('Y-m-d H:i:s',time())
            ]);
        //价格变化了
        //发送短信提醒
        if ($check_price===true) {
            if (empty($task['phone'])) {
                $phone = $task['phone'];
            } else {
                $phone = Db::name('SystemUser')->where(['id' => $task['uid']])->value('phone');
            }
            parent::curlWeb(parent::sendSMS($task['name'],$phone));
        }
        //更新任务时间
        $this->updateRunTime($id,$data['price'],$check_price);
    }

    /**
     * 检查当前产品ID是否合法
     * @param string $site_id [description]
     */
    public function check($url='' ,$site_id='', $param = '')
    {
        $site = Db::name('MonitorSite')->where('id', $site_id)->find();
        if ($site['return_type']=='api') {
            //调用API获取价格和名称
            $data['price'] = action('crond/Api/'.$site['abbreviated'], ['id' => $param, 'type'=>'price']);
            $data['title'] = action('crond/Api/'.$site['abbreviated'], ['id' => $param, 'type'=>'name']);
        } else {
            $site_rule = Db::name('MonitorSiteRule')->where('site_id', $site['id'])->order('number ASC')->select();
            //请求页面地址
            $request_url = $site['domain'].$site['path'].$param;
            //获取页面内容
            $content = parent::curlWeb($request_url);
            //内容编码处理
            $data = $this->contentToCoding( $content, $site['return_type'] );
            //处理过滤规则
            $data = $this->siteRule( $site_rule, $data );
            //获取页面标题
            $data['title'] = $this->getTitle($url);

        }
        return $data;
    }

    /**
     * 获取页面标题
     * @param  string $url 产品页面地址
     * @return [type]      标题名称
     */
    public function getTitle($url='')
    {
        //获取页面内容
        $content = parent::curlWeb($url);
        preg_match("/<title>(.+)<\/title>/", $content, $matches);
        $title = $matches[1];
        $encode = mb_detect_encoding($title);
        $title = iconv($encode,"UTF-8//IGNORE",$title);
        return $title;
    }

    /**
     * 内容编码处理
     * @param  string $content     页面内容
     * @param  string $return_type 处理编码
     * @return [type]              返回网页数组
     */
    private function contentToCoding($content='', $return_type='')
    {
        if ( $return_type == 'html' ) {
            $data['content'] = $content;
        }
        if ( $return_type == 'json' ) {
            $data = json_decode($content,true);
        }
        if ( $return_type == 'xml' ) {
            $data = simplexml_load_string($content);
            $data = json_decode(json_encode($data),TRUE);
        }
        return $data;
    }

    /**
     * 站点内容处理规则
     * @param  array  $site_rule 当前站点过滤内容规则
     * @param  array  $data      待处理的页面内容
     * @return [type]            返回处理后的数组
     */
    private function siteRule( $site_rule=array(), $data=array() )
    {
        $data['attribute'] = $this->attribute;      //属性编码
        foreach ($site_rule as $k => $rule) {
            if ($rule['pattern_id']=='1') {
                $regular = Db::name('MonitorSiteRuleRegular')->where('rule_id', $rule['id'])->find();
                $condition = $this->regularCondition($regular['condition']);
                if ($condition===true) {
                    $content = $data[$regular['content']];
                    $pattern = $this->regularPattern($regular['pattern'], $data);
                    preg_match($pattern, $content, $matches);   //执行正则
                    $matches_array = explode('|',$regular['matches']);  //拆解正则结果变量名称
                    foreach ($matches_array as $mk => $matches_name) {
                        $mk = $mk+1;
                        $data[$matches_name] = $matches[$mk];   
                    }
                }
            }
            if ($rule['pattern_id']=='2') {
                $replace = Db::name('MonitorSiteRuleReplace')->where('rule_id', $rule['id'])->find();
                $content = $data[$replace['content']];
                $content = str_replace($replace['find'], $replace['replace'], $content);
                $data[$replace['content']] = $content;
            }
            if ($rule['pattern_id']=='3') {
                $variable = Db::name('MonitorSiteRuleVariable')->where('rule_id', $rule['id'])->find();
                preg_match("/\'(.+)\'/", $variable['original_value'], $matches);   //执行正则
                if (!empty($matches[1])) {
                    //固定文本
                    $original_string = $matches[1];
                } else {
                    //数组赋值
                    $original_value = explode(".",$variable['original_value']);
                    foreach ($original_value as $ok => $value) {
                        $original_string = (empty($original_string)) ? $data[$value] : $original_string[$value] ;
                    }
                }
                $data[$variable['value']] = $original_string;
            }
        }
        return $data;
    }

    /**
     * 更新运行时间
     * @param  string $id 任务编号
     * @param  string $price 当前售价
     * @param  boolean $check_price 对比后价格状态
     * @return [type]     不返回任何信息
     */
    public function updateRunTime($id='',$price='',$check_price=false)
    {   
        //运行周期（秒）
        $run_cycle = Db::name('MonitorTask')->where('id', $id)->value('run_cycle');
        //删除旧数据
        Db::name('MonitorCron')->where('task_id', $id)->delete();
        //更新任务内容
        $task_date['run_time'] = date('Y-m-d H:i:s',time());
        if (!empty($price)) {
            $task_date['current_price'] = $price;
        }
        if ($check_price===false) {
            //下次运行时间戳
            $expires_in = time() + $run_cycle;
            //创建新的下次运行记录
            Db::name('MonitorCron')->insert(['task_id' => $id, 'expires_in' => $expires_in]);
            //保存最新运行时间
            Db::name('MonitorTask')->where('id', $id)->update($task_date);
        } else {
            //禁用产品
            $task_date['is_disable'] = 1;
            Db::name('MonitorTask')->where('id', $id)->update($task_date);
        }

    }

    /**
     * URL参数设置
     * @param  string $param 参数内容
     */
    private function urlParam($param='')
    {
        if (strstr($param,'::')) {
            $param = explode('::',$param); 
            //设置属性编码
            $this->attribute = $param[1];
            //返回产品参数
            return $param[0];
        } else {
            return $param;
        }
    }

    /**
     * 正则规则启动条件检查
     * @param string $condition 条件规则
     */
    private function regularCondition($condition='')
    {
        $condition_status = false;
        if (!empty($condition)) {
            $condition_array = json_decode($condition);
            foreach ($condition_array as $name => $value) {
                if ($value=='true' && !empty($data[$name]) || $value=='false' && empty($data[$name])) {
                    $condition_status = true;
                }
            }
        } else {
            $condition_status = true;
        }
        return $condition_status;
    }

    /**
     * 规则变量替换
     * @param  string $pattern 规则字符串
     * @param  array  $data    变量数组
     * @return [type]          替换后的规则字符串
     */
    private function regularPattern( $pattern='', $data=array() )
    {
        preg_match_all("/\{\{(\w+)\}\}/", $pattern, $matches);   //执行正则
        if (!empty($matches[0])) {
            foreach ($matches[0] as $k => $val) {
                $pattern = str_replace($val,$data[$matches[1][$k]],$pattern);
            }
        }
        return $pattern;
    }

}
