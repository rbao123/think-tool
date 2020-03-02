<?php

namespace bao\tool;

use app\common\model\PushLog;
/**
 * Class GetuiTool 个推
 * @package app\common\tool
 */
class GetuiTool
{
    private $appid;
    private $appkey;
    private $masterSecret;
    private $host;

    function __construct()
    {
        $this->appid = config('other_app.getui.appid');
        $this->appkey = config('other_app.getui.appkey');
        $this->masterSecret = config('other_app.getui.masterSecret');
        $this->host = config('other_app.getui.host');
    }

    //1.透传模板
    protected function iGtTransmissionTemplateCustomize($data)
    {
        $template =  new \IGtTransmissionTemplate();
        $template->set_appId($this->appid);//应用appid
        $template->set_appkey($this->appkey);//应用appkey
        $template->set_transmissionType(1);//透传消息类型
        $template->set_transmissionContent($data['content']);//透传内容
        return $template;
    }

    //2.通知弹框下载模板
    protected function iGtNotyPopLoadTemplateCustomize($data)
    {
        $template =  new \IGtNotyPopLoadTemplate();
        $template ->set_appId($this->appid);//应用appid
        $template ->set_appkey($this->appkey);//应用appkey
        //通知栏
        $template ->set_notyTitle($data['notyTitle']);//通知栏标题
        $template ->set_notyContent($data['notyContent']);//通知栏内容
        $template ->set_notyIcon($data['notyIcon']);//通知栏logo
        $template ->set_isBelled(true);//是否响铃
        $template ->set_isVibrationed(true);//是否震动
        $template ->set_isCleared(true);//通知栏是否可清除
        //弹框
        $template ->set_popTitle($data['popTitle']);//弹框标题
        $template ->set_popContent($data['popContent']);//弹框内容
        $template ->set_popImage($data['popImage']);//弹框图片
        $template ->set_popButton1($data['popLBtn']);//左键
        $template ->set_popButton2($data['popRBtn']);//右键
        //下载
        $template ->set_loadIcon($data['loadIcon']);//弹框图片
        $template ->set_loadTitle($data['loadTitle']);
        $template ->set_loadUrl($data['loadUrl']);
        $template ->set_isAutoInstall(false);
        $template ->set_isActived(true);
        //$template->set_notifyStyle(0);
        //$template->set_duration(BEGINTIME,ENDTIME); //设置ANDROID客户端在此时间区间内展示消息
        return $template;
    }

    //3.通知链接模板
    protected function iGtLinkTemplateCustomize($data)
    {
        $template =  new \IGtLinkTemplate();
        $template ->set_appId($this->appid);//应用appid
        $template ->set_appkey($this->appkey);//应用appkey
        $template ->set_title($data['title']);//通知栏标题
        $template ->set_text($data['content']);//通知栏内容
        $template ->set_logo($data['logo']);//通知栏logo
        $template ->set_isRing(true);//是否响铃
        $template ->set_isVibrate(true);//是否震动
        $template ->set_isClearable(true);//通知栏是否可清除
        $template ->set_url($data['url']);//打开连接地址
        //$template->set_duration(BEGINTIME,ENDTIME); //设置ANDROID客户端在此时间区间内展示消息
        return $template;
    }

    //4.通知透传模板
    protected function iGtNotificationTemplateCustomize($data)
    {
        $template =  new \IGtNotificationTemplate();
        $template->set_appId($this->appid);//应用appid
        $template->set_appkey($this->appkey);//应用appkey
        $template->set_transmissionType(1);//透传消息类型
        $template->set_transmissionContent($data['transContent']);//透传内容
        $template->set_title($data['title']);//通知栏标题
        $template->set_text($data['text']);//通知栏内容
        $template->set_logo($data['logo']);//通知栏logo
        $template->set_isRing(true);//是否响铃
        $template->set_isVibrate(true);//是否震动
        $template->set_isClearable(true);//通知栏是否可清除
        //$template->set_duration(BEGINTIME,ENDTIME); //设置ANDROID客户端在此时间区间内展示消息
        return $template;
    }

    //APN推送设置
    protected function setApn($data)
    {
        $apn = new \IGtAPNPayload();
        $alertmsg = new \DictionaryAlertMsg();
        $alertmsg->body = $data['content'];
        $alertmsg->title = $data['title'];
        $alertmsg->subtitleLocArgs = array();
        $apn->alertMsg = $alertmsg;
        $apn->badge = 1;
        $apn->contentAvailable = 1;
        $apn->category = "ACTIONABLE";
        return $apn;
    }

    /**
     * 选择模板：
     * 1.透传功能模板
     * 2.通知弹框下载模板
     * 3.通知链接模板
     * 4.通知透传模板
     * @param int $type 模板编号
     * @param array $data 模板参数
     * @return object|null
     */
    protected function selectTemplate($type, $data)
    {
        $template = null;
        switch ($type) {
            case 1:
                $template = $this->iGtTransmissionTemplateCustomize($data);
                break;
            case 2:
                $template = $this->iGtNotyPopLoadTemplateCustomize($data);
                break;
            case 3:
                $template = $this->iGtLinkTemplateCustomize($data);
                break;
            case 4:
                $template = $this->iGtNotificationTemplateCustomize($data);
                break;
        }

        if (empty($template)) {
            abort(422, 'invalid template');
        }

        $apn = $this->setApn($data);
        $template->set_apnInfo($apn);
        return $template;
    }

    /**
     * 单推
     * @param int $templateType 模板编号
     * @param array $data 模板参数
     * @param string $cid 用户clientId
     * @param int $expireTime 离线时间
     * @param bool $isOffline 是否离线
     * @return array
     */
    public function pushMessageToSingle($templateType, $data, $cid, $expireTime = 43200000, $isOffline = true)
    {
        $igt = new \IGeTui($this->host,$this->appkey,$this->masterSecret);
        $template = $this->selectTemplate($templateType, $data);
        //个推信息体
        $message = new \IGtSingleMessage();
        $message->set_isOffline($isOffline);//是否离线
        $message->set_offlineExpireTime($expireTime);//离线时间
        $message->set_data($template);//设置推送消息类型
        //$message->set_PushNetWorkType(0);//设置是否根据WIFI推送消息，1为wifi推送，0为不限制推送
        $target = new \IGtTarget();
        $target->set_appId($this->appid);
        $target->set_clientId($cid);
        $rep = $igt->pushMessageToSingle($message, $target);
        return $rep;
    }


    /**
     * 多推
     * @param int $templateType 模板编号
     * @param array $data 模板参数
     * @param array $cidList  用户列表
     * @param string $groupName 组名
     * @param int $expireTime 离线时间
     * @param bool $isOffline 是否离线
     * @return array
     */
    public function pushMessageToList($templateType, $data, $cidList, $groupName = '', $expireTime = 43200000, $isOffline = true)
    {
        putenv("gexin_pushList_needDetails=true");
        putenv("gexin_pushList_needAsync=true");

        $igt = new \IGeTui($this->host, $this->appkey, $this->masterSecret);
        $template = $this->selectTemplate($templateType, $data);
        //个推信息体
        $message = new \IGtListMessage();
        $message->set_isOffline($isOffline);//是否离线
        $message->set_offlineExpireTime($expireTime);//离线时间
        $message->set_data($template);//设置推送消息类型
        //$message->set_PushNetWorkType(1);	//设置是否根据WIFI推送消息，1为wifi推送，0为不限制推送
        //$contentId = $igt->getContentId($message);
        $contentId = $igt->getContentId($message, $groupName);	//根据TaskId设置组名，支持下划线，中文，英文，数字
        //接收方
        $targetList = [];
        if (is_array($cidList)) {
            foreach ($cidList as $cid) {
                $target = new \IGtTarget();
                $target->set_appId($this->appid);
                $target->set_clientId($cid);
                $targetList[] = $target;
            }
        }
        $rep = $igt->pushMessageToList($contentId, $targetList);
        return $rep;
    }

    /**
     * 群推
     * @param int $templateType 模板编号
     * @param array $data 模板参数
     * @param int $expireTime 离线时间
     * @param bool $isOffline 是否离线
     * @return mixed|null
     */
    public function pushMessageToApp($templateType, $data, $expireTime = 43200000, $isOffline = true)
    {
        $igt = new \IGeTui($this->host, $this->appkey, $this->masterSecret);
        $template = $this->selectTemplate($templateType, $data);
        $message = new \IGtAppMessage();
        $message->set_isOffline($isOffline);
        $message->set_offlineExpireTime(3600 * 1000 * 2);//离线时间单位为毫秒，例，两个小时离线为3600*1000*2
        $message->set_data($template);
        $appIdList=array($this->appid);
        $message->set_appIdList($appIdList);
        $rep = $igt->pushMessageToApp($message);
        return $rep;
    }

    /**
     * 停止推送任务
     * @param string $taskId 任务id
     * @return boolean
     */
    public function stopTask($taskId)
    {
        $igt = new \IGeTui($this->host, $this->appkey, $this->masterSecret);
        return $igt->stop($taskId);
    }

    /**
     * 日志
     * @param array $result 推送返回的结果
     * @param array $param  推送的内容
     */
    public function pushLog($result, $param)
    {
        if (!empty($result['contentId']) || !empty($result['taskId'])) {
            $data = [
                'result' => json_encode($result, 256),
                'content' => json_encode($param, 256)
            ];
            PushLog::create($data);
        }
    }
}