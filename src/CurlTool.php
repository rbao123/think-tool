<?php


namespace bao\tool;


class CurlTool
{
    /** curl 获取 https 请求
     * @param String $url 请求的url
     * @param Array $data 要發送的數據
     * @param Array $header 请求时发送的header
     * @param int $timeout 超时时间，默认30s
     * @param bool $debug 是否打印错误信息，默认false
     */
    static function https_post($url, $data = array(), $header = array(), $timeout = 30, $debug = false)
    {

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false); // 跳过证书检查
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);  // 从证书中检查SSL加密算法是否存在
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_HTTPHEADER, $header);
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'POST');
        curl_setopt($ch, CURLOPT_POSTFIELDS, $data);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_TIMEOUT, $timeout);

        $response = curl_exec($ch);
        if ($error = curl_error($ch)) {
            die($error);
        }

        // 打印错误信息
        if ($debug) {

            echo '=====info=====' . "\r\n";
            print_r(curl_getinfo($ch));

            echo '=====error=====' . "\r\n";
            print_r(curl_error($ch));

            echo '=====$response=====' . "\r\n";
            print_r($response);

        }
        curl_close($ch);


        return $response;

    }

    public static function https_get($url, $data = array(), $header = array(), $timeout = 30, $debug = false)
    {

        $url .= '?';
        foreach ($data as $key => $value) {
            $url .= $key . '=' . $value . '&';
        }

        $curl = curl_init(); // 启动一个CURL会话
        curl_setopt($curl, CURLOPT_URL, $url);
        curl_setopt($curl, CURLOPT_HEADER, 0);
        curl_setopt($curl, CURLOPT_HTTPHEADER, $header);
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, false); // 跳过证书检查
        curl_setopt($curl, CURLOPT_SSL_VERIFYHOST, false);  // 从证书中检查SSL加密算法是否存在


        $response = curl_exec($curl);
        if ($error = curl_error($curl)) {
            die($error);
        }

        // 打印错误信息
        if ($debug) {

            echo '=====info=====' . "\r\n";
            print_r(curl_getinfo($curl));

            echo '=====error=====' . "\r\n";
            print_r(curl_error($curl));

            echo '=====$response=====' . "\r\n";
            print_r($response);

        }
        curl_close($curl);


        return $response;

    }
}