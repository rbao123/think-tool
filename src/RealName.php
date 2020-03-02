<?php


namespace bao\tool;


class RealName
{
    /**
     * 实名认证
     * @param string $ID_number 身份证
     * @param string $real_name 姓名
     * @param int $type 0不抛出1抛出异常
     * @param string $msg 异常说明
     * @return bool
     */
    public function checkRealName($ID_number, $real_name, $type = 1, $msg = '')
    {
        //实名认证接口请求
        $appkey = "";
        $url = "http://op.juhe.cn/idcard/query";
        $params = array(
            "idcard" => $ID_number,//身份证号码
            "realname" => $real_name,//真实姓名
            "key" => $appkey,
        );
        $paramstring = http_build_query($params);
        $content = CurlTool::https_post($url, $paramstring);
        $result = json_decode($content, true);
        if ($result) {
            if ($result['error_code'] == '0' && $result['result']['res'] == '1') {
                return true;
            }
        } elseif ($type) {
            abort(422, $msg ?: '查无数据');
        } else {
            return false;
        }
    }
}