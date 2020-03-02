<?php


namespace bao\tool;


class IMTool
{

    public static function genSig($identifier)
    {
        $config = config('other_app.im');
        return (new TencentSigAPI($config['sdkappid'], $config['identifier_key']))->genSig($identifier, 60 * 60 * 24);
    }


    private function publicParams($data, $url)
    {
        $config = config('other_app.im');

        $param = $config;

        $usersig = self::genSig('admin');
        $param['contenttype'] = 'json';
        $param['apn'] = '1';
        $url .= '?contenttype=json&usersig=' . $usersig . '&identifier=' . $config['identifier'] . '&sdkappid=' . $config['sdkappid'] . '&random=' . mt_rand(0, 999999);

        //halt($usersig);
        $data = json_encode($data);
//        halt($data);
        $header = [
            'Content-Type' => 'application/json',
            'Content-Length' => strlen($data),
        ];
        //halt($url);
        $response = json_decode(CurlTool::https_post($url, $data, $header), true);

        if ($response['ActionStatus'] == 'FAIL') {
            if (in_array($response['ErrorCode'], ['10021', '10010', '10025'])) {
                return true;
            }
            abort(500, '请求IM服务失败,错误代码:' . $response['ErrorCode'] . $response['ErrorInfo']);
        }
        return $response;
    }

    /**
     * 添加帐号 到im
     * @param $uid
     * @param $nickname
     */
    public function accountImport($uid, $nickname)
    {
        $data = [
            'Identifier' => (string)$uid,
            'Nick' => (string)$nickname,
        ];
        $url = 'https://console.tim.qq.com/v4/im_open_login_svc/account_import';
        $this->publicParams($data, $url);
    }


    /**
     * 用于校验自有帐号是否已导入即时通信 IM
     * @param $uid
     * @return bool
     */
    public function accountCheck($uid)
    {
        $data['CheckItem'][] = [
            'UserID' => (string)$uid,
        ];
        $url = 'https://console.tim.qq.com/v4/im_open_login_svc/account_check';
        $res = $this->publicParams($data, $url);
        foreach ($res['ResultItem'] as $re) {
            if ($re['AccountStatus'] == 'Imported') {
                return true;
            } else {
                return false;
            }
        }

    }

    /**
     * * 创建直播群
     * @param $uid  string 主播uid
     * @param $name
     * @param $groupId string 房间号
     * @param string $type
     * @return mixed
     */
    public function createGroup($uid, $name, $groupId, $type = 'AVChatRoom')
    {
        $data = [
            'Owner_Account' => (string)$uid, // 群主的 UserId（选填）
            'Type' => $type, // 群组类型：Private/Public/ChatRoom/AVChatRoom/BChatRoom（必填）
            'Name' => (string)$name, // 群名称（必填）
            'GroupId' => (string)$groupId, // 用户自定义群组 ID（选填）
        ];
        $url = 'https://console.tim.qq.com/v4/group_open_http_svc/create_group';

        $this->publicParams($data, $url);
        return $data['GroupId'];
    }

    /**
     * 删除群
     * @param $groupId string 群id
     */
    public function destroyGroup($groupId)
    {
        $data = [
            'GroupId' => (string)$groupId, // 用户自定义群组 ID（选填）
        ];
        $url = 'https://console.tim.qq.com/v4/group_open_http_svc/destroy_group';

        $this->publicParams($data, $url);
    }

    /**
     * 设置im资料
     * @param $uid
     * @param $ProfileItem
     */
    public function setIMInfo($uid, $ProfileItem)
    {
        $data = [
            'From_Account' => (string)$uid, // 用户ID
            'ProfileItem' => [$ProfileItem]
        ];

        $url = 'https://console.tim.qq.com/v4/profile/portrait_set';
        $res = $this->publicParams($data, $url);
    }

    /**
     * 禁/解 群发言
     * @param string $GroupId 群id
     * @param array $Members_Account uid数组
     * @param int $time 禁言时间(秒)为0时表示取消禁言
     */
    public function forbidSend($GroupId, array $Members_Account, int $time)
    {
        $data = [
            "GroupId" => $GroupId,
            "Members_Account" => $Members_Account,// 最多支持 500 个
            "ShutUpTime" => $time // 禁言时间，单位为秒
        ];
        $url = 'https://console.tim.qq.com/v4/group_open_http_svc/forbid_send_msg';
        $res = $this->publicParams($data, $url);
    }
}