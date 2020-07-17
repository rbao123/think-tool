<?php


namespace bao\tool;

use app\common\tool\TencentCOSSTS\STS;
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
        return true;
        $Objects = [];
        foreach ($url as $value) {
            if (strpos($value, 'defalt') === false) {
//                $key = str_ireplace('http://' . $this->cosurl, '', $value);
                $key = str_ireplace($this->cosurl, '', $value);
                $Objects[] = ['Key' => (string)$key];
            }

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

    /**
     * 生成临时密钥
     * @return mixed
     * @throws \Exception
     */
    public function getCredential()
    {
        $config = array(
            'url' => 'https://sts.tencentcloudapi.com/',
            'domain' => 'sts.tencentcloudapi.com',
            //'proxy' => null,  //设置网络请求代理,若不需要设置，则为null
            'secretId' => $this->config['secretId'], // 云 API 密钥 secretId
            'secretKey' => $this->config['secretKey'], // 云 API 密钥 secretKey
            'bucket' => $this->bucket, // 换成你的 bucket
            'region' => $this->config['region'], // 换成 bucket 所在地区
            'durationSeconds' => 600, // 密钥有效期
            'allowPrefix' => 'admin/video/*', // 这里改成允许的路径前缀，可以根据自己网站的用户登录态判断允许上传的具体路径，例子： a.jpg 或者 a/* 或者 * (使用通配符*存在重大安全风险, 请谨慎评估使用)
            // 密钥的权限列表。简单上传和分片需要以下的权限，其他权限列表请看 https://cloud.tencent.com/document/product/436/31923
            'allowActions' => array(
                // 简单上传
                'name/cos:PutObject',
                // 表单上传
                'name/cos:PostObject',
                // 分片上传： 初始化分片
                'name/cos:InitiateMultipartUpload',
                // 分片上传： 查询 bucket 中未完成分片上传的UploadId
                "name/cos:ListMultipartUploads",
                // 分片上传： 查询已上传的分片
                "name/cos:ListParts",
                // 分片上传： 上传分片块
                "name/cos:UploadPart",
                // 分片上传： 完成分片上传
                "name/cos:CompleteMultipartUpload"
            )
        );
        //创建 sts
        $sts = new STS();

        // 获取临时密钥，计算签名
        $tempKeys = $sts->getTempKeys($config);

        return $tempKeys;
    }
}