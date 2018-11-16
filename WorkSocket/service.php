<?php
header('content-type: text/html; charset=utf-8');
use Workerman\Worker;
require_once __DIR__ . '/Workerman/Autoloader.php';
class WebIM{
    
       static $clients = [];
        public function __construct(){
                // 创建一个Worker监听2000端口，使用websocket协议通讯
                $http_worker = new Worker("http://0.0.0.0:2000");

                // 启动4个进程对外提供服务
                $http_worker->count = 4;
                // 接收到浏览器发送的数据时回复hello world给浏览器
                $http_worker->onMessage = function($connection, $data)
                {
//                    将对象转化为数组
                    $actionData = json_decode($data,true);
//                    var_dump($actionData);
//                    判断连接类型
                    switch($actionData['action']){
                        case 'online':
//                            登录提醒
                                $this->online($connection, $actionData);
                            break;
                        case 'message':
//                                发送信息
                                $this->message($connection, $actionData);
                            break;
                        default:
                            break;
                    }
                    
//                    // 向浏览器发送hello world
//                    $connection->send('收到你发送的消息');
                };
                $http_worker->onConnect = function($connection){
                    $this->connect($connection);
                };
                 $http_worker->onClose = function($connection) {
                        $this->outline($connection);
                };
                // 运行worker
                Worker::runAll();
        }
//          存储连接对象
          private function connect($client){
                  self::$clients[$client->id]['client'] = $client;
          }
//          上线提醒
          private  function online($connection,$actionData){
                self::$clients[$connection->id]['nickname'] = $actionData['nickname'];
                $sendData = [
                    'action' => 'online',
                    'nickname' => $actionData['nickname'],
                    'uid' => $connection->id,
                    'userlist' => array_column(self::$clients, 'nickname')
                ];
                foreach(self::$clients as $client){
                    $this->send($client['client'], $sendData);
                }
          }
//          发送数据
          private function send($client, $actionData){
            $client->send(json_encode($actionData));   
          }
//          聊天
          private function message($connection,$actionData){
                      $sendData = [
                            'action' => 'message',
                            'from' => $connection->id,
                            'nickname' => self::$clients[$connection->id]['nickname'],
                            'message' => $actionData['message'],
                            'time' => date('Y-m-d H:i:s')
                        ];
                        foreach(self::$clients as $client){
                            $this->send($client['client'], $sendData);
                        }
          }
//          退出删除数据
              private function outline($client){
                     unset(self::$clients[$client->id]);
             } 

}

new WebIM();


