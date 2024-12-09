<?php
namespace tinymeng\mailer;

/**
 * Class Name: PHP Mailer类
 * @author Tinymeng <666@majiameng.com>
 * @date: 2019/9/26 16:49
 * @method static \tinymeng\mailer\Gateways\Smtp smtp(array $config) SMTP发送邮件
 * @package tinymeng\mailer
 */
class Email
{
    /**
     * Description:  init
     * @author: JiaMeng <666@majiameng.com>
     * Updater:
     * @param $gateway
     * @param null $config
     * @return mixed
     * @throws \Exception
     */
    protected static function init($gateway, $config = null)
    {
        $class = __NAMESPACE__ . '\\Gateways\\' . ucfirst(strtolower($gateway));
        if (class_exists($class)) {
            $app = new $class($config);
            return $app;
        }
        throw new \Exception("发送Mailer基类 [$gateway] 不存在");
    }

    /**
     * Description:  __callStatic
     * @author: JiaMeng <666@majiameng.com>
     * Updater:
     * @param $gateway
     * @param $config
     * @return mixed
     */
    public static function __callStatic($gateway, $config)
    {
        return self::init($gateway, ...$config);
    }

}
