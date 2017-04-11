<?php
namespace app\crond\controller;

use controller\BasicCrond;
use think\Db;

/**
 * 定时任务控制器
 */
class Cron extends BasicCrond
{

    /**
     * 获取任务列表
     * @param  [type] $timestamp 时间戳
     * @return [type]       返回任务ID
     */
    public function index() {
        $timestamp = time();
        $cron = Db::name('MonitorCron')->where(['expires_in'=>['<=',$timestamp],'status'=>0])->select();
        foreach ($cron as $k => $val) {
            $task_id[] = $val['task_id'];
            Db::name('MonitorCron')->where('id', $val['id'])->update(['status' => '1']);
        }
        if (!empty($task_id)) {
            return implode(' ', $task_id);
        }
    }

}
