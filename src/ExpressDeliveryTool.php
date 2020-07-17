<?php


namespace bao\tool;

use app\common\tool\CurlTool;
use think\facade\Cache;

/**
 * 快递查询
 * Class ExpressDelivery
 * @package app\common\tool
 */
class ExpressDeliveryTool
{
    /**
     * 快递查询
     * @param string $num 快递单号
     * @param string $com 快递公司编号
     * @return bool|mixed|string
     * @throws
     */
    public static function index($num, $com = '')
    {
        // state 物流状态：-1：单号或代码错误；0：暂无轨迹；1:快递收件；2：在途中；3：签收；4：问题件 5.疑难件 6.退件签收
        $res = Cache::get($num . $com);
        if (empty($res)) {
            // 云市场分配的密钥Id
            $secretId = '';
            // 云市场分配的密钥Key
            $secretKey = '';
            $source = '';

            // 签名
            $datetime = gmdate('D, d M Y H:i:s T');
            $signStr = sprintf("x-date: %s\nx-source: %s", $datetime, $source);
            $sign = base64_encode(hash_hmac('sha1', $signStr, $secretKey, true));
            $auth = sprintf('hmac id="%s", algorithm="hmac-sha1", headers="x-date x-source", signature="%s"', $secretId, $sign);

            // 请求头
            $header = array(
                'X-Source:' . $source,
                'X-Date:' . $datetime,
                'Authorization:' . $auth,);
            $url = 'https://service-ohohpvok-1300683954.gz.apigw.tencentcs.com/release/express';
            $data = [
                'num' => $num,
            ];
            if (!empty($com)) {
                $data['com'] = $com;
            }

            $res = CurlTool::https_get($url, $data, $header);
            $res = json_decode($res, true);

            if ($res['code'] != 'OK') {
                if ($res['code'] == 205) {
                    //快递缓存
                    Cache::tag('ExpressDelivery')->set($num . $com, $res, (5 * 60 * 60));
                } else {
                    //签收快递缓存10天信息
                    Cache::tag('ExpressDelivery')->set($num . $com, $res, (10 * 24 * 60 * 60));
                }

                abort(422, $res['msg']);
            }
            if (isset($res['state']) && $res['state'] == 3) {
                //签收快递缓存10天信息
                Cache::tag('ExpressDelivery')->set($num . $com, $res, (10 * 24 * 60 * 60));
            } else {
                //其他状态缓存2个小时减少请求
                Cache::tag('ExpressDelivery')->set($num . $com, $res, (2 * 60 * 60));
            }
            return $res;
        }

        if ($res['code'] != 'OK') {
            abort(422, $res['msg']);
        }

        return $res;
    }
}