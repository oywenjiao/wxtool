<?php
/**
 * Created by : PhpStorm
 * User: OuYangWenJiao
 * Date: 2019/12/11
 * Time: 13:33
 */

namespace Wj\WxTool\Unit\ImageManager;


use GuzzleHttp\Client;

class ImageManager
{
    /**
     * @param $data
     * $data['img_base64'] 图片base64编码
     * $data['type']   图片分类
     * @param string $filePath 图片存储路径
     * @return mixed
     */
    public function upload($data, $filePath = 'Public/upload/source/')
    {
        //获取base64位图片
        $imgBase64 = $data['img_base64'];
        $result['status'] = 'success';
        //图片分类
        $type = $data['type'];
        //对图片MD5加密
        $md5 = md5($imgBase64);
        //获取base64图片的信息
        $imgInfo = $this->getImgInfo($imgBase64);
        //图片后缀名
        $imgExt = strtolower($imgInfo['ext']);
        //图片内容
        $imgContent = base64_decode($imgInfo['content']);
        //获取图片路径
        $imgPath = $this->getImgPath($type,$md5);
        //对图片进行重命名
        $imgFileName = $this->getImgFileName($md5,$imgExt);
        //指定图片
        $file = $filePath . $imgPath . $imgFileName;
        if (file_exists($file)) {
            $result['code'] = $md5.$imgExt;
        } else {
            $this->checkPath($file);
            if (file_put_contents($file,$imgContent)) {
                $result['code'] = $md5.$imgExt;
            } else {
                $result['status'] ='file_put_contents error!';
            }
        }
        $result['url'] = $filePath . $imgPath . $imgFileName;
        $result['size'] = getimagesize($file);
        return $result;
    }

    public function getImageBase64($imgUrl)
    {
        $size = getimagesize($imgUrl);
        $imgType = 'png';
        switch ($size[2]) {//判读图片类型
            case 1:
                $imgType = "gif";
                break;
            case 2:
                $imgType = "jpg";
                break;
            case 3:
                $imgType = "png";
                break;
        }
        $client = new Client();
        $response = $client->get($imgUrl, ['timeout' => 30, 'verify' => false]);
        $body = $response->getBody();
        $imgContent = base64_encode($body);
        $imageBase64 = 'data:image/' . $imgType . ';base64,' . $imgContent;//合成图片的base64编码
        return $imageBase64;
    }

    /*
    * 插入图片 data:image 格式的图片数据
    * return 图片32md5+图片扩展名
    * $back = false :直接返回图片代码
    */
    public function uploadImage($imgBase64, $cate='other', $back=false)
    {
        if (stristr($imgBase64,'http://')) {
            $imgBase64 = $this->getImageBase64($imgBase64);
        }
        $postData = array();
        $postData['type'] = $cate;
        $postData['img_base64'] = $imgBase64;
        $results = $this->upload($postData);
        if ($cate == 'product') {
            $results['code'].=1;
        } elseif ($cate == 'ad') {
            $results['code'].=2;
        } elseif($cate == 'article') {
            $results['code'].=3;
        } elseif($cate == 'user') {
            $results['code'].=4;
        } else {
            $results['code'].=5;
        }
        if ($results['status'] == 'success') {
            if ($back == false) {
                return $results['code'];
            } else {
                return $results;
            }
        }
        return false;
    }

    /**
     * 获取图片信息,得到图片后缀及图片内容信息
     * @param $imgBase64
     * @return mixed
     */
    private function getImgInfo($imgBase64)
    {
        $imgBase64Arr = explode(';',$imgBase64);
        $ext = str_replace('data:image/', '', $imgBase64Arr[0]);
        if ($ext == 'jpeg') {
            $ext = 'jpg';
        }
        $info['ext'] = $ext;
        $info['content'] = str_replace('base64,', '', $imgBase64Arr[1]);
        return $info;
    }

    /**
     * 通过type值和MD5值拼装图片存储路径
     * @param $type
     * @param $md5
     * @return string
     */
    private function getImgPath($type, $md5)
    {
        $path = $type . '/';
        $path .= substr($md5,0,2) . '/';
        $path .= substr($md5,2,3) . '/';
        return $path;
    }

    /**
     * 通过MD5值及图片后缀，进行重命名
     * @param $md5
     * @param $ext
     * @return string
     */
    private function getImgFileName($md5, $ext)
    {
        $fileName = substr($md5,5);
        $fileName .= '.' . $ext;
        return $fileName;
    }

    /**
     * 验证图片路径
     * @param $filename
     * @return bool
     */
    private function checkPath($filename)
    {
        //检查路径
        $arr_path = explode ('/', $filename );
        $path = '';
        $cnt = count ($arr_path) - 1;
        if($cnt >= 0 && $arr_path[0] == '') {
            chdir('/');
        }
        for ($i = 0; $i < $cnt; $i ++) {
            if ($arr_path [$i] == '') {
                continue;
            }
            $path .= $arr_path[$i] . '/';
            if (!is_dir($path) && !mkdir($path, 0755)) {
                return false;
            }
        }
        return true;
    }
}