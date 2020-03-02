<?php


namespace bao\tool;


use TencentCloud\Common;
use TencentCloud\Common\Exception\TencentCloudSDKException;
use TencentCloud\Common\Profile;
use TencentCloud\Live\V20180801;
use TencentCloud\Live\V20180801\Models;
use TencentCloud\Vod\V20180717\Models as VodModels;
use TencentCloud\Vod\V20180717;

class LiveTool
{
    private static function getConfig()
    {
        return config('other_app.live');
    }


    //获取推流地址
    static function getPushUrl($appName, $streamName)
    {
        $time = date('Y-m-d H:i:s', strtotime('+ 2day'));
        $config = self::getConfig();
        $domain = $config['push_domain'];
        $key = $config['push_key'];
        if ($key && $time) {
            $txTime = strtoupper(base_convert(strtotime($time), 10, 16));
            //txSecret = MD5( KEY + streamName + txTime )
            $txSecret = md5($key . $streamName . $txTime);
            $ext_str = "?" . http_build_query(array(
                    "txSecret" => $txSecret,
                    "txTime" => $txTime
                ));
        }
        return "rtmp://" . $domain . "/" . $appName . "/" . $streamName . (isset($ext_str) ? $ext_str : "");
    }

    //获取播放地址
    static function getPlayUrl($appName, $streamName, $time = null)
    {
        $config = self::getConfig();
        $domain = $config['play_domain'];
//        $key = $config['play_key'];
//        if ($key && $time) {
//            $txTime = strtoupper(base_convert(strtotime($time), 10, 16));
//            //txSecret = MD5( KEY + streamName + txTime )
//            $txSecret = md5($key . $streamName . $txTime);
//            $ext_str = "?" . http_build_query(array(
//                    "txSecret" => $txSecret,
//                    "txTime" => $txTime
//                ));
//        }
        return [
            'RTMP' => "rtmp://" . $domain . "/" . $appName . "/" . $streamName,
            'FLV' => "http://" . $domain . "/" . $appName . "/" . $streamName . '.flv',
            'HLS' => "http://" . $domain . "/" . $appName . "/" . $streamName . '.m3u8',
        ];
    }

    //初始化
    private function initializeTencentCloud()
    {
        $config = self::getConfig();

        $cred = new Common\Credential($config['secretId'], $config['secretKey']);
        $httpProfile = new Profile\HttpProfile();
        $httpProfile->setEndpoint('live.tencentcloudapi.com');
        $clientProfile = new Profile\ClientProfile();
        $clientProfile->setHttpProfile($httpProfile);
        $client = new V20180801\LiveClient($cred, "ap-guangzhou", $clientProfile);
        return $client;
    }


    //设置录播规则
    public function CreateLiveRecordRule($TemplateId, $AppName = '', $StreamName = '')
    {
        $config = self::getConfig();
        $client = $this->initializeTencentCloud();
        $req = new Models\CreateLiveRecordRuleRequest();
        $params = [
            'DomainName' => $config['push_domain'],
            'TemplateId ' => $TemplateId,
        ];
        if (!empty($AppName)) {
            $params['AppName'] = $AppName;
        }
        if (!empty($StreamName)) {
            $params['StreamName'] = $StreamName;
        }

        $req->fromJsonString(json_encode($params));


        $resp = $client->CreateLiveRecordRule($req);

        return $resp;
    }

    /**
     * 禁播
     * @param $StreamName string 直播房间号
     * @param $ResumeTime int 禁播时间
     * @param string $AppName string 直播域名后缀
     * @param null $Reason sting 禁播理由
     */
    public function ForbidLiveStream($StreamName, $ResumeTime, $AppName = 'live', $Reason = null)
    {
        $config = self::getConfig();
        $client = $this->initializeTencentCloud();
        $req = new Models\ForbidLiveStreamRequest();
        $params = [
            'AppName' => $AppName,
            'DomainName' => $config['push_domain'],
            'StreamName' => (string)$StreamName,
            'ResumeTime' => date('c', (time() + $ResumeTime) - (8 * 60 * 60)),
            'Reason' => $Reason,
        ];
        if (!empty($Reason)) {
            $params['Reason'] = $Reason;
        }
        $req->fromJsonString(json_encode($params));
        $resp = $client->ForbidLiveStream($req);

        return $resp;
    }

    /**
     * 恢复直播
     * @param $StreamName string 直播房间号
     * @param string $AppName string 直播域名后缀
     * @return Models\ResumeLiveStreamResponse
     */
    public function ResumeLiveStream($StreamName, $AppName)
    {
        $config = self::getConfig();
        $client = $this->initializeTencentCloud();
        $req = new Models\ResumeLiveStreamRequest();
        $params = [
            'AppName' => (string)$AppName,
            'DomainName' => $config['push_domain'],
            'StreamName' => (string)$StreamName,
        ];
        $req->fromJsonString(json_encode($params));
        $resp = $client->ResumeLiveStream($req);
        return $resp;
    }

    /**
     * 获取禁播列表
     * @param $PageNum string 页数
     * @param string $PageSize string 数量
     * @return
     */
    public function getForbidStreamList($PageNum, $PageSize)
    {
        $config = self::getConfig();
        $client = $this->initializeTencentCloud();
        $req = new Models\DescribeLiveForbidStreamListRequest();
        $params = [
            'PageNum' => (int)$PageNum,
            'PageSize' => (int)$PageSize
        ];
        $req->fromJsonString(json_encode($params));
        $resp = $client->DescribeLiveForbidStreamList($req);
        $resp = json_decode($resp->toJsonString(), true);
        return $resp['ForbidStreamList'];
    }


    /**
     * 查询直播中的流
     * @param $AppName
     * @param null $StreamName
     * @param int $PageNum
     * @param int $PageSize
     */
    public function getLiveOnlineList($PageNum = 1, $PageSize = 10, $AppName = null, $StreamName = null)
    {
        $config = self::getConfig();
        $client = $this->initializeTencentCloud();
        $req = new Models\DescribeLiveStreamOnlineListRequest();
        $params = [
            'PageNum' => (int)$PageNum,
            'PageSize' => (int)$PageSize,
        ];
        if (!empty($StreamName)) {
            $params['StreamName'] = $StreamName;
        }
        if (!empty($AppName)) {
            $params['AppName'] = $AppName;
        }
        $req->fromJsonString(json_encode($params));
        $resp = $client->DescribeLiveStreamOnlineList($req);
        $resp = json_decode($resp->toJsonString(), true);
        return $resp['OnlineInfo'];
    }

    /**
     * 创建直播间房间号 并创建IM直播群 群主为主播自己
     * @param $uid
     * @return array
     */
    public function builderLiveRoom($uid)
    {
        $room_no = $uid + 10000;
        //创建直播聊天群
        $GroupId = (new IMTool())->createGroup($uid, 'live' . $room_no, $room_no);
        return ['room_no' => $uid + 10000, 'GroupId' => $GroupId];
    }

    /**
     * 获取直播播放信息（在线人数）
     * @param $AppName 频道名
     * @param $StreamName 房间号
     * @param $StartTime time 开始时间
     * @return mixed
     */
    public function PlayInfoList($AppName, $StreamName, $StartTime)
    {
        //$StartTime = date('Y-m-d H:i:s');
        $EndTime = date('Y-m-d H:i:s');

        $config = self::getConfig();
        $client = $this->initializeTencentCloud();
        $req = new  Models\DescribeStreamPlayInfoListRequest();

        $params = [
            'DomainName' => $config['push_domain'],
            'AppName' => $AppName,
            'StreamName' => $StreamName,
            'StartTime' => $StartTime,
            'EndTime' => $EndTime,
        ];
        $req->fromJsonString(json_encode($params));

        $resp = $client->DescribeStreamPlayInfoList($req);
        if (!empty($resp['Response']['DataInfoList'])) {
            return $resp['Response']['DataInfoList'];
        }
    }

    /**
     * 为频道创建回调规则
     * @param $AppName 频道名
     * @param $TemplateId 模版id
     * @return mixed
     */
    public function CreateLiveCallbackRule($AppName, $TemplateId = 2046)
    {
        $config = self::getConfig();
        $client = $this->initializeTencentCloud();
        $req = new  Models\CreateLiveCallbackRuleRequest();
        $params = [
            'DomainName' => $config['push_domain'],
            'AppName' => $AppName,
            'TemplateId' => $TemplateId,
        ];
        $req->fromJsonString(json_encode($params));
        $resp = $client->CreateLiveCallbackRule($req);
    }

    /**
     * 删除频道回调规则
     * @param $AppName 频道名
     * @return mixed
     */
    public function DeleteLiveCallbackRule($AppName)
    {
        try {
            $config = self::getConfig();
            $client = $this->initializeTencentCloud();
            $req = new  Models\DeleteLiveCallbackRuleRequest();
            $params = [
                'DomainName' => $config['push_domain'],
                'AppName' => $AppName,
            ];
            $req->fromJsonString(json_encode($params));
            $resp = $client->DeleteLiveCallbackRule($req);
        } catch (\Exception $e) {
            if (($e instanceof TencentCloudSDKException)) {
                return [];
            } else {
                abort(500, $e);
            }
        }
    }

    /**
     * 断开推流（可用于警告主播）
     * @param $AppName string 频道名
     * @param $StreamName string 房间号
     * @return mixed
     */
    public function DropLiveStream($AppName, $StreamName)
    {
        $config = self::getConfig();
        $client = $this->initializeTencentCloud();
        $req = new  Models\DropLiveStreamRequest();
        $params = [
            'DomainName' => $config['push_domain'],
            'AppName' => $AppName,
            'StreamName' => $StreamName,
        ];
        $req->fromJsonString(json_encode($params));
        $resp = $client->DropLiveStream($req);
    }

    /**
     * 删除回放文件
     * @param $fileId
     */
    public function DeleteMediaRequest($fileId)
    {
        $config = self::getConfig();
        $cred = new Common\Credential($config['secretId'], $config['secretKey']);
        $httpProfile = new Profile\HttpProfile();
        $httpProfile->setEndpoint("vod.tencentcloudapi.com");
        $clientProfile = new Profile\ClientProfile();
        $clientProfile->setHttpProfile($httpProfile);
        $client = new V20180717\VodClient($cred, "ap-guangzhou", $clientProfile);
        $req = new VodModels\DeleteMediaRequest();
        $params = [
            'FileId' => (string)$fileId
        ];
        $req->fromJsonString(json_encode($params));
        $resp = $client->DeleteMedia($req);
    }
}