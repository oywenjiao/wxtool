<?php
/**
 * Created by : PhpStorm
 * User: OuYangWenJiao
 * Date: 2019/11/6
 * Time: 11:55
 */

namespace Wj\WxTool;


use GuzzleHttp\Client;

class WxBase
{
    protected $appId;
    protected $secret;
    protected $client;
    public $session;
    public $access;
    public $error;
    public $decrypted_data;

    public function __construct($appId, $secret)
    {
        $this->appId = $appId;
        $this->secret = $secret;
        if (!isset($this->client)) {
            $this->client = new Client();
        }
    }

    public function getAppId()
    {
        return $this->appId;
    }

    public function getSecret()
    {
        return $this->secret;
    }

    /**
     * 获取access_token
     * @return bool|mixed
     */
    public function access()
    {
        $url = "https://api.weixin.qq.com/cgi-bin/token?grant_type=client_credential&appid={$this->appId}&secret={$this->secret}";
        $response = $this->client->get($url, ['timeout' => 30, 'verify' => false]);
        $body = $response->getBody();
        $result = json_decode($body, true);
        if (isset($result['errcode'])) {
            $this->error = $result['errmsg'];
            return false;
        }
        $this->access = $result;
        return $result;
    }

    /**
     * 使用code换取session_key
     * @param $code
     * @return bool|mixed
     * {
     *  "openid": "OPENID",
     *  "session_key": "SESSIONKEY",
     *  "unionid": "UNIONID"   只有绑定开放平台并且关注了公众号才能这样得到，不然需要授权并解密加密数据
     * }
     */
    public function session($code)
    {
        $url = "https://api.weixin.qq.com/sns/jscode2session?appid={$this->appId}&secret={$this->secret}&js_code={$code}&grant_type=authorization_code";
        // verify => false 是否验证安全证书
        $response = $this->client->get($url, ['timeout' => 30, 'verify' => false]);
        $body = $response->getBody();
        $result = json_decode($body, true);
        if (isset($result['errcode'])) {
            $this->error = $result['errmsg'];
            return false;
        }
        //如果不存在unionid， 赋值为null，在外面就可以直接判断，不然会提示index不存在
        if (!isset($result['unionid'])) {
            $result['unionid'] = null;
        }
        $this->session = $result;
        return $result;
    }

    /**
     * 微信授权获取用户信息，网页版通过code换取信息
     * @param $code
     * @return bool|mixed|\Psr\Http\Message\ResponseInterface
     */
    public function oauthInfo($code)
    {
        $url = "https://api.weixin.qq.com/sns/oauth2/access_token?appid={$this->appId}&secret={$this->secret}&code={$code}&grant_type=authorization_code";
        $response = $this->client->get($url, ['timeout' => 30, 'verify' => false]);
        $body = $response->getBody();
        $result = json_decode($body);
        if (isset($result->errcode)) {
            $this->error = $result->errmsg;
            return false;
        }
        $user_url = "https://api.weixin.qq.com/sns/userinfo?access_token={$result->access_token}&openid={$result->openid}&lang=zh_CN";
        $userResponse = $this->client->get($user_url, ['timeout' => 30, 'verify' => false]);
        $userBody = $userResponse->getBody();
        $user_info = json_decode($userBody, true);
        return $user_info;
    }

    /**
     * 解密小程序中的加密信息
     * @param $encryptedData
     * @param $iv
     * @param $code
     * @return bool|mixed
     * 返回数据示例
     * {
     * "openId": "OPENID",
     * "nickName": "NICKNAME",
     * "gender": GENDER,
     * "city": "CITY",
     * "province": "PROVINCE",
     * "country": "COUNTRY",
     * "avatarUrl": "AVATARURL",
     * "unionId": "UNIONID",
     * "watermark":
     * {
     * "appid":"APPID",
     * "timestamp":TIMESTAMP
     * }
     * }
     */
    public function decryptData($session_key, $encryptedData, $iv)
    {
        if (strlen($session_key) != 24) {
            $this->error = 'session_key length error!';
            return false;
        }
        $aesKey = base64_decode($session_key);
        if (strlen($iv) != 24) {
            $this->error = 'iv length error!';
            return false;
        }
        $aesIV = base64_decode($iv);
        $aesCipher = base64_decode($encryptedData);
        $result = openssl_decrypt($aesCipher, "AES-128-CBC", $aesKey, 1, $aesIV);
        $dataObj = json_decode($result);
        if ($dataObj == null) {
            $this->error = 'decode data null!';
            return false;
        }
        if ($dataObj->watermark->appid != $this->appId) {
            $this->error = 'appid error!';
            return false;
        }
        $data = json_decode($result, true);
        $this->decrypted_data = $data;
        return $data;
    }
}