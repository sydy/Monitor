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
     * 指定当前数据表
     * @var string
     */
    protected $table = 'MonitorCron';

    /**
     * 获取任务列表
     * @param  [type] $timestamp 时间戳
     * @return [type]       返回任务ID
     */
    public function index() {
        $timestamp = time();
        $cron = Db::name($this->table)->where(['expires_in'=>['<=',$timestamp],'status'=>0])->select();
        foreach ($cron as $k => $val) {
            $task_id[] = $val['task_id'];
            Db::name($this->table)->where('id', $val['id'])->update(['status' => '1']);
        }
        if (!empty($task_id)) {
            return implode(' ', $task_id);
        }
    }

    /**
     * 新增任务到自动执行队列
     * @param string $id 任务编号
     */
    public function add($id='')
    {
        $expires_in = time();
        if (!Db::name($this->table)->where('task_id', $id)->count()) {
            Db::name($this->table)->insert(['task_id' => $id, 'expires_in' => $expires_in]);
        }
    }

    /**
     * 删除任务队列
     * @param string $id 任务编号
     */
    public function del($id='')
    {
        Db::name($this->table)->where('task_id', $id)->delete();
    }
}
