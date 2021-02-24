<?php


namespace bao\tool;


use app\common\model\User;


class WechatTool
{
    //通过code获取openid app使用
    public function getOpenId($code, $is_info = 1)
    {
        $config = config('other_app.Wechat');
        $url = 'https://api.weixin.qq.com/sns/oauth2/access_token';
        $data = [
            'appid' => $config['app']['appid'],
            'secret' => $config['app']['appsecret'],
            'code' => $code,
            'grant_type' => 'authorization_code',
        ];
        $openid = CurlTool::https_post($url, $data);
        $openid = json_decode($openid, true);
        if (!isset($openid['openid'])) {
            abort(422, '授权失败');
        }
        return $openid;
    }

    //小程序
    public function getOpenIdApplets($code, $param, $is_unionid = 1)
    {
//        dump('请求开始'.time());
        $config = config('other_app.Wechat');
        $url = 'https://api.weixin.qq.com/sns/jscode2session';
        $data = [
            'appid' => $config['applets']['appid'],
            'secret' => $config['applets']['appsecret'],
            'js_code' => $code,
            'grant_type' => 'authorization_code',
        ];
        $openid = CurlTool::https_post($url, $data);
        $openid = json_decode($openid, true);
//       dump('请求end'.time());
        if (!isset($openid['openid'])) {
            abort(422, '授权失败');
        }
        if (!isset($openid['unionid']) && $is_unionid) {
            $this->decryptData($openid, $param);
        }
        return $openid;
    }

    //获取用户信息--app
    public function getWxUserInfo($access_token)
    {
        $url = 'https://api.weixin.qq.com/sns/userinfo';
        $data = [
            'access_token' => $access_token['access_token'],
            'openid' => $access_token['openid'],
        ];
        $user_info = CurlTool::https_post($url, $data);
        $user_info = json_decode($user_info, true);
        return $user_info;
    }

    //小程序解密
    public function decryptData($openid, &$param)
    {
//        dump('解密开始'.time());
        $sessionKey = $openid['session_key'];
        $appid = config('other_app.Wechat.applets.appid');
        if (strlen($sessionKey) != 24) {
            abort(500, '解密失败');
        }
        $aesKey = base64_decode($sessionKey);

        if (strlen($param['iv']) != 24) {
            abort(500, '解密失败');
        }
        $aesIV = base64_decode($param['iv']);
        $aesCipher = base64_decode($param['encryptedData']);
        $result = openssl_decrypt($aesCipher, "AES-128-CBC", $aesKey, 1, $aesIV);
        $dataObj = json_decode($result, true);
        if ($dataObj == NULL) {
            abort(500, '解密失败');
        }
        if ($dataObj['watermark']['appid'] != $appid) {
            abort(500, '解密失败');
        }
        $param['openid'] = @$dataObj['openId'];
        $param['unionid'] = @$dataObj['unionId'];
        return $dataObj;
    }

//    public function getAccessToken(&$data)
//    {
//        $url = 'https://api.weixin.qq.com/sns/oauth2/refresh_token';
//        $data = [
//            'appid' => config('other_app.Wechat.app.appid'),
//            'grant_type' => 'refresh_token',
//            'refresh_token' => $data['refresh_token'],
//        ];
//        $response = CurlTool::https_post($url, $data);
//        $response = json_decode($response, true);
//        $data['access_token'] = $response['access_token'];
//        return $response;
//    }

    /**
     * 获取小程序access_token
     * @return bool|mixed|string
     */
    public function getAccessToken()
    {
        $url = 'https://api.weixin.qq.com/cgi-bin/token';
        $data = [
            'appid' => config('other_app.Wechat.applets.appid'),
            'grant_type' => 'client_credential',
            'secret' => config('other_app.Wechat.applets.appsecret'),
        ];
        $response = CurlTool::https_get($url, $data);
        $response = json_decode($response, true);
        if (isset($response['access_token'])) {
            return $response['access_token'];
        } else {
            abort(422, '获取失败errcode' . $response['errcode'] . $response['errmsg']);
        }
    }

    /**
     * 获取小程序二维码
     * @param $scene string 自定义参数
     * @param $page string 小程序存在的页面
     * @return mixed
     */
    public function getUnlimited($scene, $page = null)
    {
        //参考https://developers.weixin.qq.com/miniprogram/dev/api-backend/open-api/qr-code/wxacode.getUnlimited.html

        $url = 'https://api.weixin.qq.com/wxa/getwxacodeunlimit?access_token=';

        $access_token = $this->getAccessToken();
        //dump($access_token);
        $url .= $access_token;
        $data = [
            'scene' => $scene,
        ];
        if (!empty($page)) {
            $data['page'] = $page;
        }
        $header = [
            'Content-Type' => 'application/json'
        ];
        $response = CurlTool::https_post($url, json_encode($data), $header);

        $result = json_decode($response, true);
        if (!empty($result['errcode'])) {
            abort(422, $result['errmsg']);
        }

        return $response;
    }
}