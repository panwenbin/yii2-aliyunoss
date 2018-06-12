## 阿里云OSS在指定Bucket操作Objects的封装
> 大部分情况我们程序只是使用OSS的存储功能，对于bucket管理用的比较少  
> 所以对常用的部分做了封装，减少了bucket参数传递，处理了异常（异常时返回null）

## 在Yii2中使用
在配置文件增加
```php
return [
    'components' => [
        'oss' => [
            'class' => 'panwenbin\yii2\aliyunoss\OssBucket',
            'endPoint' => 'oss-cn-shanghai.aliyuncs.com',
            'accessKeyId' => 'abcdefghijklmn',
            'accessKeySecret' => '1234567890',
            'bucket' => 'panwenbin',
            'cname' => 'oss.panwenbin.com',
        ],
    ],
];
```
使用
```php
$oss = Yii::$app->get('oss');
$isSuccess = $oss->putObject('somefile.txt', 'This is a test file!');
```