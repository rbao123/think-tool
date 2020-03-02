<?php


namespace bao\phoneNumberAuth;

use AlibabaCloud\Client\AlibabaCloud;
use AlibabaCloud\Client\Exception\ClientException;
use AlibabaCloud\Client\Exception\ServerException;
use think\facade\Env;

class PhoneNumberAuthClient
{
    /**
     * @param $accessToken string 客户端授权返回的token
     * @param $outId
     * @return array
     * @throws ClientException
     * @throws ServerException
     */
    public static function getMobile($accessToken, $outId) {
        $accessKeyId = config('other_app.aliyun.phone_number_auth.access_key_id');
        $accessSecret = config('other_app.aliyun.phone_number_auth.access_secret');
        $regionId = 'cn-hangzhou';

        AlibabaCloud::accessKeyClient($accessKeyId, $accessSecret)
            ->regionId($regionId)
            ->asDefaultClient()
        ;

        $result = AlibabaCloud::rpc()
            ->product('Dypnsapi')
            ->scheme('https')
            ->version('2017-05-25')
            ->action('GetMobile')
            ->method('POST')
            ->host('dypnsapi.aliyuncs.com')
            ->options([
                'query' => [
                    'RegionId' => $regionId,
                    'AccessToken' => $accessToken,
                    'OutId' => $outId,
                ],
            ])
            ->request()
        ;
        return $result->toArray();
    }
}