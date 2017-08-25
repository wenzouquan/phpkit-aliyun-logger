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

        $request = new \Aliyun_Log_Models_PutLogsRequest($this->project, $this->logstore, $this->topic, null, $this->logitems);
        $this->logitems = array();
        $this->falg = 0;
        try {
            $response = $this->client->putLogs($request);
           return $response;
        } catch (Aliyun_Log_Exception $ex) {
            throw new \Exception($ex->getMessage());
        } catch (Exception $ex) {
            throw new \Exception($ex->getMessage());
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
            $this->commit();
        }
    }

}

