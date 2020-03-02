<?php


namespace bao\tool;


use Yansongda\Pay\Pay as yPay;

class PayTool
{
    public static function getAlipayConfig()
    {
        $app_config = config('other_app.pay.alipay');
        $config = [
            'app_id' => $app_config['app_id'],
            'notify_url' => '',
            //'return_url' => 'http://yansongda.cn/return.php',
            'ali_public_key' => $app_config['ali_public_key'],            // 加密方式： **RSA2**
            'private_key' => $app_config['private_key'],
            'log' => [ // optional
                'file' => './logs/alipay.log',
                'level' => 'debug', // 建议生产环境等级调整为 info，开发环境为 debug
                'type' => 'single', // optional, 可选 daily.
                'max_file' => 30, // optional, 当 type 为 daily 时有效，默认 30 天
            ],
            'http' => [ // optional
                'timeout' => 5.0,
                'connect_timeout' => 5.0,
                // 更多配置项请参考 [Guzzle](https://guzzle-cn.readthedocs.io/zh_CN/latest/request-options.html)
            ],
//            'mode' => 'dev', // optional,设置此参数，将进入沙箱模式
        ];
        return $config;
    }

    public static function getWechatConfig()
    {
        $app_config = config('other_app.pay.wechat');
        $config = [
            'appid' => $app_config['appid'], // APP APPID
            'app_id' => $app_config['appid'], // 公众号 APPID
            'miniapp_id' => config('other_app.pay.applets.appid'), // 小程序 APPID
            'mch_id' => $app_config['mch_id'],
            'key' => $app_config['key'], //微信支付签名秘钥
            'notify_url' => '',
//            'cert_client' => $app_config['cert_client'], // optional，退款等情况时用到
//            'cert_key' => $app_config['cert_key'],// optional，退款等情况时用到
            'log' => [ // optional
                'file' => './logs/wechat.log',
                'level' => 'debug', // 建议生产环境等级调整为 info，开发环境为 debug
                'type' => 'single', // optional, 可选 daily.
                'max_file' => 30, // optional, 当 type 为 daily 时有效，默认 30 天
            ],
            'http' => [ // optional
                'timeout' => 5.0,
                'connect_timeout' => 5.0,
                // 更多配置项请参考 [Guzzle](https://guzzle-cn.readthedocs.io/zh_CN/latest/request-options.html)
            ],
            //'mode' => 'dev', // optional, dev/hk;当为 `hk` 时，为香港 gateway。
        ];
        return $config;
    }

    public static function alipay(array $config_s = [])
    {
        $config = self::getAlipayConfig();
        foreach ($config_s as $key => $v) {
            $config[$key] = $v;
        }
        $alipay = yPay::alipay($config);
        return $alipay;
    }

    public static function wechat(array $config_s = [])
    {
        $config = self::getWechatConfig();
        foreach ($config_s as $key => $v) {
            $config[$key] = $v;
        }
        $wechat = yPay::wechat($config);
        return $wechat;
    }
}