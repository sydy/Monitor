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
            $vo['run_cycle'] = $this->sec2Time($vo['run_cycle']);
        }
    }

    /**
     * 表单数据默认处理
     * @param array $data
     */
    public function _form_filter(&$data) {
        if ($this->request->isPost()) {

            if (!(strlen($data['name'])>=1 && strlen($data['name'])<=10)) {
                $this->error('任务名称不能超过10字,小于1字！');
            }

            if ($data['run_cycle']<60) {
                $this->error('检查周期时间不能小于一分钟！');
            }

            if ($data['run_cycle']>2678400) {
                $this->error('检查周期时间不能超过一个月！');
            }

            if (isset($data['url'])) {
                if (empty($data['id'])) {
                    if (Db::name($this->table)->where(['uid' => session('user.id'), 'url' => $data['url']])->count()) {
                        $this->error('当前产品已在监控队列！');
                    }
                }
                preg_match("/^(http:\/\/)?(https:\/\/)?(.+)(\/)/i", $data['url'], $matches);
                if (empty($matches[3])) {
                    $this->error('链接错误，请检查后重试！');
                }
                $site_web = Db::name('MonitorSiteWeb')->where('url', $matches[3])->find();
                if (empty($site_web)) {
                    $this->error('当前站点未设置！');
                }
                preg_match($site_web['match_rule'], $data['url'], $matches);
                if (empty($matches[$site_web['match_rule_num']])) {
                    $this->error('产品链接格式无法识别！');
                }
                $html_data = action(
                    'crond/Task/check', 
                    ['url' => $data['url'], 'site_id' => $site_web['site_id'], 'param' => $matches[$site_web['match_rule_num']]]
                );

                if (!empty($html_data['price'])) {
                    $data['start_price'] = $html_data['price'];
                } else {
                    $this->error('当前产品无法监控价格！');
                }
                if ($html_data['price'] < $data['goal_price']) {
                    $this->error('当前售价'.$html_data['price'].'低于目标价格！');
                }
                //获取产品标题
                $data['title'] = $html_data['title'];
            } else {
                $this->error('请填写产品链接！');
            }
            Db::name($this->table);
            //补充字段
            $data['param']       = $matches['2'];
            $data['site_id']     = $site_web['site_id'];
            if (empty($data['id'])) {
                $data['uid']         = session('user.id');
                $data['create_time'] = date('Y-m-d H:i:s',time());
            }
            print_r($data);
            exit;
 
        } else {
            $this->assign('default_phone', Db::name('SystemUser')->where(['id' => session('user.id')])->value('phone'));
        }
    }

    /**
     * 保存表单后操作
     * @param  [type] &$result 
     */
    public function _form_result(&$result) {
        if ($result !== false) {
            $taskId = Db::name($this->table)->getLastInsID();
            if (isset($taskId)) {
                action('crond/Cron/add', ['id' => $taskId]);
            }
        }
    }

    /**
     * 删除任务
     */
    public function del() {
        if (DataService::update($this->table)) {
            $ids = explode(',', input("post.id", ''));
            foreach ($ids as $id) {
                action('crond/Cron/del', ['id' => $id]);
            }
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
            $ids = explode(',', input("post.id", ''));
            foreach ($ids as $id) {
                action('crond/Cron/del', ['id' => $id]);
            }
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
            $ids = explode(',', input("post.id", ''));
            foreach ($ids as $id) {
                action('crond/Cron/add', ['id' => $id]);
            }
            $this->success("任务启用成功！", '');
        } else {
            $this->error("任务启用失败，请稍候再试！");
        }
    }

    /**
     * 立即检查
     */
    public function check() {
        $id = input('get.id');
        $uid = Db::name($this->table)->where('id', $id)->value('uid');
        if ($uid == session('user.id')) {
            action('crond/Task/index', ['id' => $id]);
            $this->success("检查成功！", '');
        } else {
            $this->error("检查失败，请稍候再试！");
        }
    }

    /**
     * 秒数转换为文本时间
     * @return bool
     */
    public function sec2Time($time='60') {
        if(is_numeric($time)){
            $value = array(
                "years" => 0, "days" => 0, "hours" => 0,
                "minutes" => 0, "seconds" => 0,
            );
            if($time >= 31556926){
                $value["years"] = floor($time/31556926);
                $time = ($time%31556926);
            }
            if($time >= 86400){
                $value["days"] = floor($time/86400);
                $time = ($time%86400);
            }
            if($time >= 3600){
                $value["hours"] = floor($time/3600);
                $time = ($time%3600);
            }
            if($time >= 60){
                $value["minutes"] = floor($time/60);
                $time = ($time%60);
            }
            $value["seconds"] = floor($time);
            $t = '';
            if (!empty($value["years"])) {
                $t .= $value["years"] ."年";
            }
            if (!empty($value["days"])) {
                $t .= $value["days"] ."天";
            }
            if (!empty($value["hours"])) {
                $t .= $value["hours"] ."小时";
            }
            if (!empty($value["minutes"])) {
                $t .= $value["minutes"] ."分";
            }
            if (!empty($value["seconds"])) {
                $t .= $value["seconds"]."秒";
            }
            return $t;
        }else{
            return false;
        }
    }
}
