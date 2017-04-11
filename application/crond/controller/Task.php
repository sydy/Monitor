<?php
namespace app\crond\controller;

use controller\BasicCrond;
use think\Db;

/**
 * 价格监控任务执行控制器
 */
class Task extends BasicCrond
{

    /**
     * 执行一个监控任务
     * @param  [type] $id 任务ID
     */
    public function index($id) {
    	$task = Db::name('MonitorTask')->where('id', $id)->find();
        $site = Db::name('MonitorSite')->where('id', $task['site_id'])->find();
    	$site_rule = Db::name('MonitorSiteRule')->where('site_id', $site['id'])->order('number ASC')->select();
        //请求页面地址
    	$request_url = $site['domain'].$site['path'].$task['param'];
    	//获取页面内容
        $content = parent::curlWeb($request_url);
        //内容编码处理
        $data = $this->contentToCoding( $content, $site['return_type'] );
    	//处理过滤规则
        $data = $this->siteRule( $site_rule, $data );
        //价格对比
        $new_price = $data['price']-$task['start_price'];
        //保存运行日志
        Db::name('MonitorTaskLog')
            ->insert([
                'task_id'      => $task['id'],
                'start_price'  => $task['start_price'],
                'online_price' => $data['price'],
                'run_time'     => date('Y-m-d H:i:s',time())
            ]);
        //更新任务时间
        $this->updateRunTime($id);
        // $this->assign('result', $data);
        // return $this->fetch();
    }

    /**
     * 检查当前产品ID是否合法
     * @param string $site_id [description]
     */
    public function check($site_id='', $param = '')
    {
        $site = Db::name('MonitorSite')->where('id', $site_id)->find();
        $site_rule = Db::name('MonitorSiteRule')->where('site_id', $site['id'])->order('number ASC')->select();
        //请求页面地址
        $request_url = $site['domain'].$site['path'].$param;
        //获取页面内容
        $content = parent::curlWeb($request_url);
        //内容编码处理
        $data = $this->contentToCoding( $content, $site['return_type'] );
        //处理过滤规则
        $data = $this->siteRule( $site_rule, $data );
        return $data;
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
        foreach ($site_rule as $k => $rule) {
            if ($rule['pattern_id']=='1') {
                $regular = Db::name('MonitorSiteRuleRegular')->where('rule_id', $rule['id'])->find();
                $content = $data[$regular['content']];
                $pattern = $regular['pattern'];
                preg_match($pattern, $content, $matches);   //执行正则
                $matches_array = explode('|',$regular['matches']);  //拆解正则结果变量名称
                foreach ($matches_array as $mk => $matches_name) {
                    $mk = $mk+1;
                    $data[$matches_name] = $matches[$mk];   
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
                    $original_value = explode('.',$variable['original_value']);
                    foreach ($original_value as $ok => $value) {
                        $original_string = ($ok==0) ? $data[$value] : $original_string[$value] ;
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
     * @return [type]     不返回任何信息
     */
    public function updateRunTime($id='')
    {   
        //运行周期（秒）
        $run_cycle = Db::name('MonitorTask')->where('id', $id)->value('run_cycle');
        //删除旧数据
        Db::name('MonitorCron')->where('task_id', $id)->delete();
        //下次运行时间戳
        $expires_in = time() + $run_cycle;
        //创建新的下次运行记录
        Db::name('MonitorCron')->insert(['task_id' => $id, 'expires_in' => $expires_in]);
        //保存最新运行时间
        Db::name('MonitorTask')->where('id', $id)->update(['run_time' => date('Y-m-d H:i:s',time())]);
    }

}
