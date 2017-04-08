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
        // 获取到所有GET参数
        $get = $this->request->get();
        // 实例Query对象
        $task_id = Db::name('MonitorTask')->where('uid', session('user.id'))->column('id');
        $db = Db::name($this->table)->whereIn('task_id', $task_id);
        // 应用搜索条件
        if (isset($get['title']) && $get['title'] !== '') {
            $db->where('title', 'like', "%{$get['title']}%");
        }
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
            $this->success("用户删除成功！", '');
        } else {
            $this->error("用户删除失败，请稍候再试！");
        }
    }

}
