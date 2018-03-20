<?php
namespace phpkit\aliyunLogger;
use MongoDB\Driver\Exception\Exception;

require_once dirname(__FILE__) . '/Log_Autoload.php';

class Logger
{
    public $endpoint;
    public $accessKeyId;
    public $accessKey;
    public $project;
    public $logstore;
    public $token;
    public $client;
    public $logitems = array();
    public $falg = 0;
    public $topic = "TestTopic";

    /**
     * Logger constructor.
     * @param string $accessKey
     */
    public function __construct($params = array())
    {

        $this->accessKey = $params['accessKey'];
        $this->accessKeyId = $params['accessKeyId'];
        $this->endpoint = $params['endpoint'];
        $this->project = $params['project'];
        $this->logstore = $params['logstore'];
        $this->topic = isset($params['topic'])?$params['topic']:$this->topic;
        $this->client = new \Aliyun_Log_Client($this->endpoint, $this->accessKeyId, $this->accessKey, $this->token);
    }

    public function begin()
    {
        $this->falg = 1;
    }

    public function commit()
    {

      //var_dump($this->logitems);
        if(!empty($this->logitems)){
            $request = new \Aliyun_Log_Models_PutLogsRequest($this->project, $this->logstore, $this->topic, null, $this->logitems);
            //if(empty($this->logitems))
            try {
                $response = $this->client->putLogs($request);
                $this->logitems = array();
                $this->falg = 0;
               return $response;
            } catch (\Aliyun_Log_Exception $ex) {
                throw new \Exception($ex->getMessage());
            } catch (\Exception $ex) {
                throw new \Exception($ex->getMessage());
            }
        }else{
             $this->logitems = array();
                $this->falg = 0;
            
        }
        


    }

    public function rollback()
    {
        $this->logitems = array();
        $this->falg = 0;
    }

    public function log($arr = array())
    {
        $logItem = new \Aliyun_Log_Models_LogItem();
        $logItem->setTime(time());
        $logItem->setContents($arr);
        $this->logitems[] = $logItem;
        if( $this->falg ===0 ){
           return $this->commit();
        }
    }


     /**
     * 查询日志
     * @param $start_time int 开始时间
     * @param $end_time int 结束时间
     * @param $query string 过滤条件
     * @param null $page_num int 页码
     * @param null $page_size int 每页条数
     * @return \Aliyun_Log_Models_GetLogsResponse|null
     *
     * Exp:
     * $query = '*';
     * $start_time = time() - 86400;
     * $end_time = time();
     *
     * $client = new AliLogger($args);
     * $res = $client->search($start_time, $end_time, $query, null, null);
     *
     * $count = $res->getCount();
     * $is_completed = $res->isCompleted();
     * $logs = $res->getLogs();
     *
     * foreach ($logs as $log) var_dump($log->getContents());
     */
    public function search($start_time, $end_time, $query="*", $page_num = 1, $page_size = 10) {

        $req = new \Aliyun_Log_Models_GetLogsRequest($this->project, $this->logstore, $start_time, $end_time, $this->topic, $query, $page_size, ($page_num - 1) * $page_size, False);
         //var_dump($req);
       
        $client= new \Aliyun_Log_Client($this->endpoint, $this->accessKeyId, $this->accessKey, $this->token);
        $response = $client->getLogs($req);
        $list=[];
        try{
            foreach($response -> getLogs() as $log){
                $list[] = $log -> getContents();
            }
        }catch (\Aliyun_Log_Exception $ex) {
            return ['error_code'=>1,'msg'=>$ex->getMessage()];
        } catch (\Exception $ex) {
            return ['error_code'=>1,'msg'=>$ex->getMessage()];
        }
        return ['error_code'=>'0','recordsFiltered'=>$response->getCount(),'recordsTotal'=>$response->getCount(),'list'=>$list];
         
    }

}

