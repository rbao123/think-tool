<?php


namespace bao\tool;

use Qcloud\Cos\Client;


class COSTool
{
    private $cosClient;
    private $bucket;
    private $cosurl;

    public function __construct()
    {
        $config = config('other_app.cos');
        $this->cosurl = $config['cos_url'];
        $this->bucket = $config['bucket'];
        $this->cosClient = new Client([
            'region' => $config['region'],//设置一个默认的存储桶地域
            'schema' => 'https', //协议头部，默认为http
            'credentials' => [
                'secretId' => $config['secretId'],//"云 API 密钥 SecretId"
                'secretKey' => $config['secretKey'],//"云 API 密钥 SecretKey"
            ]
        ]);
    }

    /**
     * @param $object string 保存位置
     * @param $path string 本地文件位置
     * @return  bool
     */
    public function uploadFile($object, $path)
    {
        try {
            $result = $this->cosClient->Upload(
                $bucket = $this->bucket, //格式：BucketName-APPID
                $key = $object,
                $body = fopen($path, 'rb')
            /*
            $options = array(
                'ACL' => 'string',
                'CacheControl' => 'string',
                'ContentDisposition' => 'string',
                'ContentEncoding' => 'string',
                'ContentLanguage' => 'string',
                'ContentLength' => integer,
                'ContentType' => 'string',
                'Expires' => 'string',
                'GrantFullControl' => 'string',
                'GrantRead' => 'string',
                'GrantWrite' => 'string',
                'Metadata' => array(
                    'string' => 'string',
                ),
                'ContentMD5' => 'string',
                'ServerSideEncryption' => 'string',
                'StorageClass' => 'string'
            )
            */
            );
            if (is_resource($body)) {
                fclose($body);
            }
            // 请求成功
            return true;
        } catch (\Exception $e) {
            // 请求失败
            abort(422, '上传错误');
        }
    }

    //上传文件/字符串
    public function putObject($object, $path)
    {
//        try {
        $result = $this->cosClient->putObject([
            'Bucket' => $this->bucket, //格式：BucketName-APPID
            'Key' => $object,
            'Body' => $path,
            /*
            'ACL' => 'string',
            'CacheControl' => 'string',
            'ContentDisposition' => 'string',
            'ContentEncoding' => 'string',
            'ContentLanguage' => 'string',
            'ContentLength' => integer,
            'ContentType' => 'string',
            'Expires' => 'string',
            'GrantFullControl' => 'string',
            'GrantRead' => 'string',
            'GrantWrite' => 'string',
            'Metadata' => array(
            'string' => 'string',
            ),
            'ContentMD5' => 'string',
            'ServerSideEncryption' => 'string',
            'StorageClass' => 'string'
            */
        ]);
        // 请求成功
        return true;
//        } catch (\Exception $e) {
//            // 请求失败
//            echo($e);
//        }
    }

    public function GETBucket($Prefix = '')
    {
//        try {
        $result = $this->cosClient->listObjects([
            'Bucket' => $this->bucket, //格式：BucketName-APPID
//                'Delimiter' => $Delimiter,
            'EncodingType' => 'url',
//                'Marker' => 'doc/picture.jpg',
            'Prefix' => $Prefix,
            'MaxKeys' => 1000,
        ]);
        // 请求成功
        return $result;
//        } catch (\Exception $e) {
//            // 请求失败
//            echo($e);
//        }
    }

    /**
     * 删除cos文件
     * @param $url array 地址
     */
    public function deleteObject(array $url)
    {
        $Objects = [];
        foreach ($url as $value) {
//            $key = str_ireplace('http://' . $this->cosurl, '', $value);
            $key = str_ireplace( $this->cosurl, '', $value);
            $Objects[] = ['Key' => (string)$key];
        }

//        halt($Objects);
        //清除oss地址

        try {
            $result = $this->cosClient->deleteObjects(array(
                'Bucket' => $this->bucket, //格式：BucketName-APPID
                'Objects' => $Objects
                //'Key' => $key,
            ));
        } catch (\Exception $e) {
//            abort(422, '删除文件错误');
        }
    }
}