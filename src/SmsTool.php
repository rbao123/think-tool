<?php


namespace bao\tool;

use think\facade\Cache;
use think\facade\Config;
use think\facade\Request;

/**
 * Class SmsTool 腾讯短信
 * @package app\common\tool
 */
class SmsTool
{
    private $appid;
    private $appkey;
    private $sign;

    public function __construct()
    {
        $this->appid = config('other_app.sms.sdkappid');
        $this->appkey = config('other_app.sms.appkey');
        $this->sign = config('other_app.sms.sign');
    }


    /**
     * 生成签名
     * @param string $appkey sdkappid对应的appkey
     * @param string $random 随机正整数
     * @param string $curTime 当前时间
     * @param array|string $phoneNumbers 手机号码
     * @return string  签名结果
     */
    public function calculateSigForTemplAndPhoneNumbers($appkey, $random, $curTime, $phoneNumbers)
    {
        if (!is_array($phoneNumbers)) {
            $phoneNumbers = array($phoneNumbers);
        }
        $phoneNumbersString = $phoneNumbers[0];
        for ($i = 1; $i < count($phoneNumbers); $i++) {
            $phoneNumbersString .= ("," . $phoneNumbers[$i]);
        }
        return hash("sha256", "appkey=" . $appkey . "&random=" . $random
            . "&time=" . $curTime . "&mobile=" . $phoneNumbersString);
    }

    public function phoneNumbersToArray($nationCode, $phoneNumbers)
    {
        $i = 0;
        $tel = array();
        do {
            $telElement = new \stdClass();
            $telElement->nationcode = $nationCode;
            $telElement->mobile = $phoneNumbers[$i];
            array_push($tel, $telElement);
        } while (++$i < count($phoneNumbers));
        return $tel;
    }


    /**
     * 指定模板单发
     * @param string $phoneNumber 不带国家码的手机号
     * @param int $templId 模板 id
     * @param array $params 模板参数列表，如模板 {1}...{2}...{3}，那么需要带三个参数
     * @param string $sign 签名，如果填空串，系统会使用默认签名
     * @param string $nationCode 国家码，如 86 为中国
     * @param string $extend 扩展码，可填空串
     * @param string $ext 服务端原样返回的参数，可填空串
     * @return string 应答json字符串，详细内容参见腾讯云协议文档
     */
    public function sendWithParam($phoneNumber, $templId, $params, $sign = "", $nationCode = 86, $extend = "", $ext = "")
    {
        $url = 'https://yun.tim.qq.com/v5/tlssmssvr/sendsms';
        $random = mt_rand(100000, 999999);
        $curTime = time();
        $wholeUrl = $url . "?sdkappid=" . $this->appid . "&random=" . $random;
        // 按照协议组织 post 包体
        $data = new \stdClass();
        $tel = new \stdClass();
        $tel->nationcode = "" . $nationCode;
        $tel->mobile = "" . $phoneNumber;
        $data->tel = $tel;
        $data->sig = $this->calculateSigForTemplAndPhoneNumbers($this->appkey, $random, $curTime, $phoneNumber);
        $data->tpl_id = $templId;
        $data->params = $params;
        $data->sign = $sign;
        $data->time = $curTime;
        $data->extend = $extend;
        $data->ext = $ext;
        return json_decode(CurlTool::https_post($wholeUrl, json_encode($data)),true);
    }

    /**
     * 指定模板群发
     * @param string $nationCode 国家码，如 86 为中国
     * @param array $phoneNumbers 不带国家码的手机号列表
     * @param int $templId 模板id
     * @param array $params 模板参数列表，如模板 {1}...{2}...{3}，那么需要带三个参数
     * @param string $sign 签名，如果填空串，系统会使用默认签名
     * @param string $extend 扩展码，可填空串
     * @param string $ext 服务端原样返回的参数，可填空串
     * @return string 应答json字符串，详细内容参见腾讯云协议文档
     */
    public function sendWithParamMulti($phoneNumbers, $templId, $params, $sign = "", $nationCode = 86, $extend = "", $ext = "")
    {
        $url = "https://yun.tim.qq.com/v5/tlssmssvr/sendmultisms2";
        $random = mt_rand(100000, 999999);
        $curTime = time();
        $wholeUrl = $url . "?sdkappid=" . $this->appid . "&random=" . $random;
        $data = new \stdClass();
        $data->tel = $this->phoneNumbersToArray($nationCode, $phoneNumbers);
        $data->sign = $sign;
        $data->tpl_id = $templId;
        $data->params = $params;
        $data->sig = $this->calculateSigForTemplAndPhoneNumbers($this->appkey, $random, $curTime, $phoneNumbers);
        $data->time = $curTime;
        $data->extend = $extend;
        $data->ext = $ext;
        return json_decode(CurlTool::https_post($wholeUrl, json_encode($data)),true);
    }

    //发送短信
    public function sendCode($phone)
    {
        // 判断是否重复请求
        $ip=Request::instance()->ip();
        if (Cache::get("$phone.ip") == $ip) {
            abort(422, '请60秒后再发送!');
        }

        $code = mt_rand(100000, 999999);
        $TemplateParam = [
            $code,
            4,
        ];
        $SignName = Config::get('other_app.sms.sign');
        $info = $this->sendWithParam($phone, 01556, $TemplateParam, $SignName);
        if ($info['result'] != '0') {
            abort(422, $info['errmsg']);
        }
        // 信息放入缓存里
        $code_Cache = [
            'code' => $code,    //验证码
            'num' => 0,         //验证次数
            'time' => time(),   //发送时间
        ];
        //var_dump($code_Cache);
        Cache::set($phone, $code_Cache, 60 * 5);
        Cache::set("$phone.ip", $ip, 60);
    }

    //验证短信验证码
    public static function checkCode($phone, $code)
    {
//        return true;
        
        // 判断验证码合法性(短信可使用三次，超过无论对错都拒绝)
        $user_code = Cache::get($phone);
        if (empty($user_code)) {
            abort(422, '验证码错误!');
        }
        if ($user_code['code'] != $code) {
            $user_code['num'] += 1;
            //更新剩余验证码时间
            $time = (60 * 5) - (time() - $user_code['time']);
            Cache::set($phone, $user_code, $time);
            abort(422, '验证码错误!');
        } else if($user_code['num'] >= 3) {
            abort(422, '错误次数过多,请重新发送验证码!');
        } else {
            Cache::delete($phone);
        }
    }
}