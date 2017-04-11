<?php
namespace app\monitor\controller;

use controller\BasicAdmin;
use service\DataService;
use think\Db;

/**
 * 监控任务日志控制器
 */
class Log extends BasicAdmin {

    /**
     * 指定当前数据表
     * @var string
     */
    protected $table = 'MonitorTaskLog';

    /**
     * 任务列表
     */
    public function index() {
        // 设置页面标题
        $this->title = '监控任务日志列表';
        // 指定用户ID
        $search['uid'] = session('user.id');
        // 获取到所有GET参数
        $get = $this->request->get();
        // 应用搜索条件
        if (isset($get['site_title']) && $get['site_title'] !== '') {
            $site_id = Db::name('MonitorSite')->where('title', $get['site_title'])->value('id');
            if (!empty($site_id)) {
                $search['site_id'] = $site_id;
            }
        }
        if (isset($get['task_title']) && $get['task_title'] !== '') {
            $search['title'] = $get['task_title'];
        }
        // 实例Query对象
        $task_id = Db::name('MonitorTask')->where($search)->column('id');
        $db = Db::name($this->table)->whereIn('task_id', $task_id)->order('run_time desc');
        // 实例化并显示
        parent::_list($db);
    }

    /**
     * 列表数据处理
     * @param $data
     */
    protected function _index_data_filter(&$data) {
        foreach ($data as &$vo) {
            $vo['task'] = Db::name('MonitorTask')->where('id', $vo['task_id'])->find();
            $vo['task']['site_name'] = Db::name('MonitorSite')->where('id', $vo['task']['site_id'])->value('title');
        }
    }

    /**
     * 删除任务
     */
    public function del() {
        if (DataService::update($this->table)) {
            $this->success("日志删除成功！", '');
        } else {
            $this->error("日志删除失败，请稍候再试！");
        }
    }

}
