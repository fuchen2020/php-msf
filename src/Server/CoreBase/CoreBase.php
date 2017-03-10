<?php
/**
 * 内核基类
 *
 * @author camera360_server@camera360.com
 * @copyright Chengdu pinguo Technology Co.,Ltd.
 */

namespace PG\MSF\Server\CoreBase;

use Monolog\Logger;
use Noodlehaus\Config;
use PG\MSF\Server\Pack\IPack;

class CoreBase extends Child
{
    /**
     * 销毁标志
     * @var bool
     */
    public $is_destroy = false;

    /**
     * @var Loader
     */
    public $loader;
    /**
     * @var Logger
     */
    public $logger;
    /**
     * @var swoole_server
     */
    public $server;
    /**
     * @var Config
     */
    public $config;
    /**
     * @var IPack
     */
    public $pack;


    /**
     * Task constructor.
     */
    public function __construct()
    {
        if (!empty(get_instance())) {
            $this->loader = get_instance()->loader;
            $this->logger = get_instance()->log;
            $this->server = get_instance()->server;
            $this->config = get_instance()->config;
            $this->pack = get_instance()->pack;
        }
    }

    /**
     * 销毁，解除引用
     */
    public function destroy()
    {
        parent::destroy();
        $this->is_destroy = true;
    }

    /**
     * 对象复用
     */
    public function reUse()
    {
        $this->is_destroy = false;
    }
}