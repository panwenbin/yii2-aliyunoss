<?php
/**
 * @author Pan Wenbin <panwenbin@gmail.com>
 */

namespace panwenbin\yii2\aliyunoss;


use OSS\Core\OssException;
use OSS\OssClient;
use Yii;
use yii\base\Component;

/**
 * 阿里云OSS在指定Bucket操作Objects的封装
 * @package panwenbin\yii2\aliyunoss
 */
class OssBucket extends Component
{
    /**
     * @var string OSS访问域名
     */
    public $endPoint;

    /**
     * @var string 访问密钥的id
     */
    public $accessKeyId;

    /**
     * @var string 访问密钥的secret
     */
    public $accessKeySecret;

    /**
     * @var string 存储空间名称
     */
    public $bucket;

    /**
     * @var string 绑定的域名别名
     */
    public $cname;

    /**
     * @var \OSS\OssClient OssClient实例
     */
    protected $ossClient;

    /**
     * 返回OssClient实例(单例)
     * @throws \OSS\Core\OssException
     */
    public function getOssClient()
    {
        if (empty($this->ossClient)) {
            $this->ossClient = new OssClient($this->accessKeyId, $this->accessKeySecret, $this->endPoint);
        }
        return $this->ossClient;
    }

    /**
     * 格式化函数参数用于记录日志
     * @param array $args
     * @return string
     */
    private static function formatArgs(array $args)
    {
        $argStrArray = [];
        foreach ($args as $argKey => $argValue) {
            if (is_array($argValue)) {
                $argStrArray[] = "{$argKey}=" . self::formatArgs($argValue);
            } else {
                $argStrArray[] = "{$argKey}={$argValue}";
            }
        }
        return join(',', $argStrArray);
    }

    /**
     * 列出指定前缀的文件列表
     * @param string $prefix 匹配前缀
     * @param int $maxKeys 最多返回个数
     * @param string $delimiter 分组前缀
     * @param string $marker 从这个文件开始往后
     * @return \OSS\Model\ObjectListInfo
     */
    public function listObjects(string $prefix = '', int $maxKeys = 100, string $delimiter = '/', string $marker = '')
    {
        Yii::info(__FUNCTION__ . ' called with args: ' . self::formatArgs(func_get_args()));
        $options = array(
            'delimiter' => $delimiter,
            'prefix' => $prefix,
            'max-keys' => $maxKeys,
            'marker' => $marker,
        );
        try {
            return $this->getOssClient()->listObjects($this->bucket, $options);
        } catch (OssException $e) {
            Yii::error(__FUNCTION__ . ": FAILED\n" . $e->getMessage() . "\n");
            return null;
        }
    }

    /**
     * 创建虚拟目录(本函数自动在$name后面加'/'，传入参数不需要加'/')
     * @param string $name object文件名
     * @return bool
     */
    public function createObjectDir(string $name)
    {
        Yii::info(__FUNCTION__ . ' called with args: ' . self::formatArgs(func_get_args()));
        try {
            $this->getOssClient()->createObjectDir($this->bucket, $name);
            return true;
        } catch (OssException $e) {
            Yii::error(__FUNCTION__ . ": FAILED\n" . $e->getMessage() . "\n");
            return null;
        }
    }

    /**
     * 上传字符串内容
     * @param string $name object文件名
     * @param string $content 文件内容
     * @return bool
     */
    public function putObject(string $name, string $content)
    {
        Yii::info(__FUNCTION__ . ' called with args: ' . self::formatArgs(func_get_args()));
        try {
            $this->getOssClient()->putObject($this->bucket, $name, $content);
            return true;
        } catch (OssException $e) {
            Yii::error(__FUNCTION__ . ": FAILED\n" . $e->getMessage() . "\n");
            return null;
        }
    }

    /**
     * 上传本地文件
     * @param string $name object文件名
     * @param string $filePath 文件路径
     * @return bool
     */
    public function uploadFile(string $name, string $filePath)
    {
        Yii::info(__FUNCTION__ . ' called with args: ' . self::formatArgs(func_get_args()));
        try {
            $this->getOssClient()->uploadFile($this->bucket, $name, $filePath);
            return true;
        } catch (OssException $e) {
            Yii::error(__FUNCTION__ . ": FAILED\n" . $e->getMessage() . "\n");
            return null;
        }
    }

    /**
     * 以追加写的方式上传字符串，不能对Normal Object进行追加操作
     * 通过Append Object操作创建的Object类型为Appendable Object，而通过Put Object上传的Object是Normal Object。
     * @param string $name object文件名
     * @param string $content 文件内容
     * @param int $position 追加地址，用于校验
     * @return int 下次追加地址
     */
    public function appendObject(string $name, string $content, int $position)
    {
        Yii::info(__FUNCTION__ . ' called with args: ' . self::formatArgs(func_get_args()));
        try {
            return $this->getOssClient()->appendObject($this->bucket, $name, $content, $position);
        } catch (OssException $e) {
            Yii::error(__FUNCTION__ . ": FAILED\n" . $e->getMessage() . "\n");
            return null;
        }
    }

    /**
     * 以追加写的方式上传文件，不能对Normal Object进行追加操作
     * @param string $name object文件名
     * @param string $filePath 文件路径
     * @param int $position 追加地址，用于校验
     * @return int 下次追加地址
     */
    public function appendFile(string $name, string $filePath, int $position)
    {
        Yii::info(__FUNCTION__ . ' called with args: ' . self::formatArgs(func_get_args()));
        try {
            return $this->getOssClient()->appendFile($this->bucket, $name, $filePath, $position);
        } catch (OssException $e) {
            Yii::error(__FUNCTION__ . ": FAILED\n" . $e->getMessage() . "\n");
            return null;
        }
    }

    /**
     * 拷贝一个在OSS上已经存在的object成另外一个object
     * 该操作适用于拷贝小于1GB的文件，当拷贝一个大于1GB的文件时，必须使用Multipart Upload操作
     * Copy操作的源Bucket和目标Bucket必须是同一个Region
     * 该操作不能拷贝通过Append追加上传方式产生的object
     * @param string $fromName 源object文件名
     * @param string $toName 目标object文件名
     * @param string $toBucket 目标Bucket
     * @return bool
     */
    public function copyObject(string $fromName, string $toName, string $toBucket = '')
    {
        Yii::info(__FUNCTION__ . ' called with args: ' . self::formatArgs(func_get_args()));
        if (empty($toBucket)) {
            $toBucket = $this->bucket;
        }
        try {
            $this->getOssClient()->copyObject($this->bucket, $fromName, $toBucket, $toName);
            return true;
        } catch (OssException $e) {
            Yii::error(__FUNCTION__ . ": FAILED\n" . $e->getMessage() . "\n");
            return null;
        }
    }

    /**
     * 获取Object的Meta信息
     * @param string $name object文件名
     * @return array
     */
    public function getObjectMeta(string $name)
    {
        Yii::info(__FUNCTION__ . ' called with args: ' . self::formatArgs(func_get_args()));
        try {
            return $this->getOssClient()->getObjectMeta($this->bucket, $name);
        } catch (OssException $e) {
            Yii::error(__FUNCTION__ . ": FAILED\n" . $e->getMessage() . "\n");
            return null;
        }
    }

    /**
     * 删除某个Object
     * @param string $name object文件名
     * @return bool
     */
    public function deleteObject(string $name)
    {
        Yii::info(__FUNCTION__ . ' called with args: ' . self::formatArgs(func_get_args()));
        try {
            $this->getOssClient()->deleteObject($this->bucket, $name);
            return true;
        } catch (OssException $e) {
            Yii::error(__FUNCTION__ . ": FAILED\n" . $e->getMessage() . "\n");
            return null;
        }
    }

    /**
     * 删除同一个Bucket中的多个Object
     * @param array $names object文件名数组
     * @return \OSS\Http\ResponseCore
     */
    public function deleteObjects(array $names)
    {
        Yii::info(__FUNCTION__ . ' called with args: ' . self::formatArgs(func_get_args()));
        try {
            return $this->getOssClient()->deleteObjects($this->bucket, $names);
        } catch (OssException $e) {
            Yii::error(__FUNCTION__ . ": FAILED\n" . $e->getMessage() . "\n");
            return null;
        }
    }

    /**
     * 获得Object内容
     * @param string $name object文件名
     * @return string 文件内容
     */
    public function getObject(string $name)
    {
        Yii::info(__FUNCTION__ . ' called with args: ' . self::formatArgs(func_get_args()));
        try {
            return $this->getOssClient()->getObject($this->bucket, $name);
        } catch (OssException $e) {
            Yii::error(__FUNCTION__ . ": FAILED\n" . $e->getMessage() . "\n");
            return null;
        }
    }

    /**
     * 检测Object是否存在
     * @param string $name object文件名
     * @return bool
     */
    public function doesObjectExist(string $name)
    {
        Yii::info(__FUNCTION__ . ' called with args: ' . self::formatArgs(func_get_args()));
        try {
            return $this->getOssClient()->doesObjectExist($this->bucket, $name);
        } catch (OssException $e) {
            Yii::error(__FUNCTION__ . ": FAILED\n" . $e->getMessage() . "\n");
            return null;
        }
    }

    /**
     * 分片上传
     * @param string $name object文件名
     * @param string $filePath
     * @return bool
     */
    public function multiuploadFile(string $name, string $filePath)
    {
        Yii::info(__FUNCTION__ . ' called with args: ' . self::formatArgs(func_get_args()));
        try {
            $this->getOssClient()->multiuploadFile($this->bucket, $name, $filePath);
            return true;
        } catch (OssException $e) {
            Yii::error(__FUNCTION__ . ": FAILED\n" . $e->getMessage() . "\n");
            return null;
        }
    }

    /**
     * 支持生成get和put签名, 用户可以生成一个具有一定有效期的
     * @param string $name object文件名
     * @param int $timeout 过期时间(秒)
     * @param string $method GET/PUT
     * @return string 签名过的url
     */
    public function signUrl(string $name, int $timeout = 60, string $method = OssClient::OSS_HTTP_GET)
    {
        Yii::info(__FUNCTION__ . ' called with args: ' . self::formatArgs(func_get_args()));
        try {
            return $this->getOssClient()->signUrl($this->bucket, $name, $timeout, $method);
        } catch (OssException $e) {
            Yii::error(__FUNCTION__ . ": FAILED\n" . $e->getMessage() . "\n");
            return null;
        }
    }
}