<?php
namespace Common\Api;
use Common\Api\Api;
use Common\Mongo\TableName;
use Asm\Ansible\Ansible;

class OamApi extends Api{

    const ONCE  = 1;
    const MULTI = 2;

    protected $ansible = null;
    protected $playbook = null;

    protected $ansibleBaseDir   = '/data/ansible-test/php_dev';
    protected $playbookCommand  = '/usr/bin/ansible-playbook';
    protected $galaxyCommand    = '/usr/bin/ansible-galaxy';

    protected $ansibleUser      = 'tanzhifeng';
    protected $ansibleKeyFile   = '/etc/ansible/private_key_nginx';

    protected $hosts = [];
    protected $inventoryFile    = '';
    protected $playbookFile     = '';


    /**
     * 构造方法
     */
    protected function _init(){
        $this->ansibleBaseDir .= '/' . date('Ymd', NOW_TIME);
        if (!is_dir($this->ansibleBaseDir)) {
            mkdir($this->ansibleBaseDir, 0755, true);
        }
        $this->ansible = new Ansible($this->ansibleBaseDir, $this->playbookCommand, $this->galaxyCommand);
        $this->playbook = $this->ansible->playbook();

        $this->inventoryFile = $this->ansibleBaseDir . '/' . uniqid(); // md5(MODULE_NAME . CONTROLLER_NAME . ACTION_NAME . uniqid());
        $this->playbookFile  = $this->ansibleBaseDir . '/' . basename($this->inventoryFile) . '.yml';
    }

    /**
     * 生成inventoryFile
     */
    protected function createInventoryFile($type){
        $select_ip = function($item) {
            return isset($item['access_mode']) && $item['access_mode']=='1' ? $item['private_ip'] : $item['public_ip'];
        };
        switch ($type) {
            case self::ONCE:
                $content = '[servers]' . PHP_EOL . join(PHP_EOL, array_map($select_ip, $this->hosts)) . PHP_EOL;
                break;
            case self::MULTI:
                $groups = $children = '';
                foreach ($this->hosts as $key => $val) {
                    $title = 'm' . $val['id'];
                    $groups .= '[' . $title . ']' . PHP_EOL . $select_ip($val) . PHP_EOL;
                    $children .= $title . PHP_EOL;
                }
                $content = $groups . '[servers:children]' . PHP_EOL . $children;
                break;
            default:
                $content = '';
                break;
        }
        if (empty($content)) {
            return false;
        }
        // 连接信息
        $content .= '[servers:vars]' . PHP_EOL;
        $content .= 'ansible_user="' . $this->ansibleUser . '"' . PHP_EOL;
        $content .= 'ansible_ssh_private_key_file="' . $this->ansibleKeyFile . '"' . PHP_EOL;
        $content .= 'ansible_become=true' . PHP_EOL;
        $content .= 'ansible_become_user=root' . PHP_EOL;

        // 写入文件
        @file_put_contents($this->inventoryFile, $content);
    }

    /**
     * 生成playbook
     */
    protected function createPlayBook(){
        $script = '/usr/local/src/script.sh';
        $content = '---' . PHP_EOL;
        foreach ($this->hosts as $key => $val) {
            $title = 'm' . $val['id'];
            $serverid = join(',', $val['server_id']);
            $content .= PHP_EOL;
            $content .= <<<EOF
- hosts: ${title}
  gather_facts: false
  vars:
    serverlist: [${serverid}]
  tasks:
    - name: run ${script} on ${title}
      script: ${script} {{ item }} &
      with_items: "{{serverlist}}"
EOF;
            $content .= PHP_EOL;
        }
        @file_put_contents($this->playbookFile, $content);
    }

    /**
     * 操作定位
     */
    protected function operations($action){
        $map = [
            'test'  => [ self::MULTI, 'script.sh'],
            'start' => [ self::ONCE, ''],
        ];
        return isset($map[$action]) ? $map[$action] : null;
    }

    /**
     * 日志记录
     */
    protected function log($command, $result){
        $data = [
            'command'   => $command,
            'result'    => $result,
        ];
        O()->table(TableName::ANSIBLE_LOG)->insertOne($data);
    }

    protected function run(){
        if (!file_exists($this->inventoryFile) || !file_exists($this->playbookFile)) {
            return false;
        }
        $result = $this->playbook->inventoryFile(basename($this->inventoryFile))->play(basename($this->playbookFile))->verbose()->execute();
        $this->log($this->playbook->getAnsibleCommandLine(), $result);
    }

    /**
     * 设置要运行的主机
     */
    public function setHosts(array $hosts){
        // 区分格式
        $this->hosts = $hosts;
    }

    /*
     * 执行
     */
    public function script($action = 'show'){
        $this->createInventoryFile(1);
        exit();
        $this->createPlayBook($action);
        exit();
        return $this->run();
    }

    public function execute($action) {
        $oper = $this->operations($action);
        if (empty($oper)) {
            return false;
        }
    }

    public function shell(){

    }

}
