<?php

namespace Wj\WxTool\Unit\ImageManage;

use GuzzleHttp\Client;
use GuzzleHttp\Exception\GuzzleException;

class QiniuManage
{
    protected $accessKey;
    protected $secretKey;
    protected $bucket;
    protected $client;

    public function __construct($accessKey, $secretKey, $bucket)
    {
        $this->accessKey = $accessKey;
        $this->secretKey = $secretKey;
        $this->bucket = $bucket;
        if (!isset($this->client)) {
            $this->client = new Client();
        }
    }

    /*
     * 七牛计算安全base64
     */
    private function safeBase64($str){
        $find = array("+","/");
        $replace = array("-", "_");
        return str_replace($find, $replace, base64_encode($str));
    }

    /*
     * 计算七牛token
     */
    private function makeUploadToken($fileName)
    {
        $data=[];
        $data['scope'] = $this->bucket.':'.$fileName;
        $data['deadline'] = time() + 3600;
        $data['returnBody'] = '{"name": $(fname),"size": $(fsize),"w": $(imageInfo.width),"h": $(imageInfo.height),"hash": $(etag)}';
        $putPolicy = json_encode($data);
        $encodedPutPolicy = $this->safeBase64($putPolicy);
        $sign = hash_hmac('sha1',$encodedPutPolicy, $this->secretKey,true);
        $encodedSign = $this->safeBase64($sign);
        $uploadToken = $this->accessKey . ':' . $encodedSign . ':' . $encodedPutPolicy;
        return $uploadToken;
    }

    /*
     * 获取所有图片
     */
    public function getList()
    {
        $host = 'rsf.qbox.me';
        $postUrl = '/list?bucket=' . $this->bucket.'&marker=&limit=1000&prefix=&delimiter=';
        $sign = hash_hmac('sha1',$postUrl."\n", $this->secretKey,true);
        $encodedSign = $this->safeBase64($sign);
        $accessToken = $this->accessKey . ":" . $encodedSign;
        $header = array (
            "POST {$postUrl} HTTP/1.1",
            "Host: rsf.qbox.me",
            "Content-Type: application/x-www-form-urlencoded",
            "Authorization: QBox {$accessToken}"
        );
        try {
            $response = $this->client->request('get', $host . $postUrl, array('headers' => $header));
            $result = $response->getBody();
            //$result = getCurl($host.$postUrl,false,$header);
            if (false !== $result) {
                return $result;
            }
        } catch (GuzzleException $e) {

        }
    }

    /*
     * 七牛文件上传
     */
    public function uploadByForm($fileName, $file)
    {
        //$fileName = md5($file);
        $postData = [];
        $postData['key'] = $fileName;
        $postData['token'] = $this->makeUploadToken($fileName);
        $postData['file'] = $file;
        //$result = postCurl($postData, 'http://upload-z2.qiniu.com/');
        $response = $this->client->post('http://upload-z2.qiniu.com/', $postData);
        $body = $response->getBody();
        return $body;
    }

    /*
     * 七牛第三方资源抓去
     */
    public function uploadByFetch($imageUrl)
    {
        $md5 = md5($imageUrl);
        $fileName = $this->getPathByMd5($md5);
        $host = 'http://iovip.qbox.me';
        $postUrl = '/fetch/';
        $postUrl .= $this->safeBase64($imageUrl);
        $postUrl .= '/to/';
        $postUrl .= $this->safeBase64($this->bucket.':'.$fileName);
        $sign = hash_hmac('sha1',$postUrl."\n", $this->secretKey,true);
        $encodedSign = $this->safeBase64($sign);
        $accessToken = $this->accessKey.":".$encodedSign;
        $header = array (
            "POST {$postUrl} HTTP/1.1",
            "Host: iovip.qbox.me",
            "Content-Type: application/x-www-form-urlencoded",
            "Accept: */*",
            "Authorization: QBox {$accessToken}"
        );
        try {
            $response = $this->client->request('get', $host . $postUrl, array('headers' => $header));
            $result = $response->getBody();
            if (false !== $result) {
                return $md5;
            }
        } catch (GuzzleException $e) {

        }
    }


    /**
     * @param $img
     * @return bool|string
     * 暴露的图片上传方法
     */
    public function putImage($img, $dirName)
    {
        if (stristr($img,'http://') || stristr($img,'https://')) {
            $imgMd5 = $this->uploadByFetch($img);
            return $imgMd5;
        }
        $imgContent = $this->base64ToContent($img);
        if (!$imgContent || !isset($imgContent)) {
            return false;
        }
        $imgMd5 = md5($imgContent);
        $imagePath = $this->getPathByMd5($imgMd5, $dirName);
        $results = $this->uploadByForm($imagePath,$imgContent);
        $results = json_decode($results, true);
        if (isset($results['hash'])) {
            $result['status'] = 'success';
            $result['code'] = $imgMd5;
            $result['url'] = $this->getUrlByMd5($dirName, $imgMd5);
            $result['size'] = $results['size'];
            return $result;
        }
        return false;
    }

    /*
     * 将base64的图片转换成二进制数据
     */
    public function base64ToContent($imageBase64)
    {
        $imageBase64Array = explode(',',$imageBase64);
        if (count($imageBase64Array) > 0) {
            return base64_decode($imageBase64Array[1]);
        }
    }

    /*
     * 根据md5获取图片存储路径
     */
    public function getPathByMd5($md5,$dirName)
    {
        $imagePath = $dirName.'/';
        $imagePath .= substr($md5,0,2).'/';
        $imagePath .= substr($md5,2,3).'/';
        $imagePath .= substr($md5,5,27);
        return $imagePath;
    }

    /*
     * 根据md5获取图片url
     */
    public function getUrlByMd5($dirName, $md5, $width=null, $height=null)
    {
        $imgUrl = 'https://spicy.cdn.qiaokouyudu.com/';
        $imgUrl .= $this->getPathByMd5($md5, $dirName);
        if ($width && $height) {
            $imgUrl .= '?imageView2/1/w/' . $width . '/h/' . $height;
        }
        return $imgUrl;
    }

    /*
     * 根据url计算md5
     */
    public function getMd5ByUrl($imgUrl)
    {
        preg_match('@(/[0-9a-z]{2}/[0-9a-z]{3}/[0-9a-z]{27})@isU', $imgUrl,$result);
        $imgMd5=str_replace('/', '', $result[1]);
        if (stristr($imgUrl,'.jpg')) {
            $imgMd5 .= 'jpg';
        } elseif (stristr($imgUrl,'.png')) {
            $imgMd5 .= 'png';
        }
        return $imgMd5;
    }
}