<?php
namespace app\monitor\controller;

use controller\BasicAdmin;
use service\DataService;
use think\Db;

/**
 * 监控任务管理控制器
 */
class Task extends BasicAdmin {

    /**
     * 指定当前数据表
     * @var string
     */
    protected $table = 'MonitorTask';

    /**
     * 任务列表
     */
    public function index() {
        // 设置页面标题
        $this->title = '监控任务列表管理';
        // 获取到所有GET参数
        $get = $this->request->get();
        // 实例Query对象
        $db = Db::name($this->table)->where('uid', session('user.id'));
        // 应用搜索条件
        if (isset($get['title']) && $get['title'] !== '') {
            $db->where('title', 'like', "%{$get['title']}%");
        }
        // 实例化并显示
        parent::_list($db);
    }

    /**
     * 任务添加
     */
    public function add() {
        return $this->_form($this->table, 'form');
    }

    /**
     * 任务编辑
     */
    public function edit() {
        return $this->add();
    }

    /**
     * 列表数据处理
     * @param $data
     */
    protected function _index_data_filter(&$data) {
        foreach ($data as &$vo) {
            $vo['site_name'] = Db::name('MonitorSite')->where('id', $vo['site_id'])->value('title');
        }
    }

    /**
     * 表单数据默认处理
     * @param array $data
     */
    public function _form_filter(&$data) {
        if ($this->request->isPost()) {
            if (isset($data['authorize']) && is_array($data['authorize'])) {
                $data['authorize'] = join(',', $data['authorize']);
            }
            if (isset($data['id'])) {
                unset($data['username']);
            } elseif (Db::name($this->table)->where('username', $data['username'])->find()) {
                $this->error('用户账号已经存在，请使用其它账号！');
            }
        } else {
            $data['authorize'] = explode(',', isset($data['authorize']) ? $data['authorize'] : '');
            $this->assign('authorizes', Db::name('SystemAuth')->select());
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

    /**
     * 任务禁用
     */
    public function forbid() {
        if (DataService::update($this->table)) {
            $this->success("任务禁用成功！", '');
        } else {
            $this->error("任务禁用失败，请稍候再试！");
        }
    }

    /**
     * 任务启用
     */
    public function resume() {
        if (DataService::update($this->table)) {
            $this->success("任务启用成功！", '');
        } else {
            $this->error("任务启用失败，请稍候再试！");
        }
    }

}
