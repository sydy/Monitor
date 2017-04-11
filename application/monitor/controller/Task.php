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

            if (isset($data['run_cycle']) && $data['run_cycle']<60) {
                $this->error('检查周期时间不能小于一分钟！');
            }

            if (isset($data['run_cycle']) && $data['run_cycle']>2678400) {
                $this->error('检查周期时间不能超过一个月！');
            }

            if (isset($data['url'])) {
                preg_match("/^(http:\/\/)?(https:\/\/)?(.+)(\/)/i", $data['url'], $matches);
                $site_web = Db::name('MonitorSiteWeb')->where('url', $matches[3])->find();
                if (empty($site_web)) {
                    $this->error('当前站点未设置！');
                }
                preg_match($site_web['match_rule'], $data['url'], $matches);
                if (empty($matches[2])) {
                    $this->error('产品链接格式无法识别！');
                }
                $html_data = action('Crond/Task/check', ['site_id' => $site_web['site_id'], 'param' => $matches['2']]);
                if (!empty($html_data['price'])) {
                    $data['start_price'] = $html_data['price'];
                } else {
                    $this->error('当前产品无法监控价格！');
                }
            } else {
                $this->error('请填写产品链接！');
            }
            if (!empty($data['title'])) {
                $id = Db::name($this->table)->where(['site_id' => $site_web['site_id'], 'title' => $data['title']])->value('id');
                if (!empty($id)) {
                    $this->error('短标题重复，请换个名称！');
                }
            } else {
                $this->error('请填写短标题！');
            }

            $data['param']       = $matches['2'];
            $data['site_id']     = $site_web['site_id'];
            $data['uid']         = session('user.id');
            $data['create_time'] = date('Y-m-d H:i:s',time());
 
        } else {
 
        }
    }

    /**
     * 删除任务
     */
    public function del() {
        if (DataService::update($this->table)) {
            $this->success("任务删除成功！", '');
        } else {
            $this->error("任务删除失败，请稍候再试！");
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
