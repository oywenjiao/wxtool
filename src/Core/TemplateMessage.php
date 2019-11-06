<?php
/**
 * Created by : PhpStorm
 * User: OuYangWenJiao
 * Date: 2019/11/6
 * Time: 16:39
 */

namespace Wj\WxTool\Core;

use Wj\WxTool\WxBase;

class TemplateMessage
{
    protected $base;

    public function __construct(WxBase $base)
    {
        $this->base = $base;
    }

    /**
     * 发送小程序模板消息
     * @param $openid  string 用户唯一码 open_id
     * @param $template_id  string 模板id
     * @param $params  array 消息内容主体
     * @param $form_id  string 消息发送key
     * @param string $page  string 跳转页面地址
     * @param string $emphasis_keyword  需要放大显示的字段
     * @return bool|mixed
     */
    public function sendXcxMessage(
        $openid,
        $template_id,
        $params,
        $form_id,
        $page = 'pages/index/index',
        $emphasis_keyword = ''
    ) {
        $access_token = $this->base->access();
        $url = "https://api.weixin.qq.com/cgi-bin/message/wxopen/template/send?access_token={$access_token}";
        $post_data = [
            'touser'      => $openid,
            'template_id' => $template_id,
            'form_id'     => $form_id,
            'page'        => $page,
            'data'        => $params,
        ];
        if ($emphasis_keyword) {
            $post_data['emphasis_keyword'] = $emphasis_keyword;
        }
        $curl = curl_init();
        curl_setopt_array($curl, [
            CURLOPT_URL            => $url,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_MAXREDIRS      => 10,
            CURLOPT_TIMEOUT        => 30,
            CURLOPT_CUSTOMREQUEST  => "POST",
            CURLOPT_SSL_VERIFYPEER => false,
            CURLOPT_POSTFIELDS     => json_encode($post_data, JSON_UNESCAPED_UNICODE)
        ]);
        $response = curl_exec($curl);
        $err = curl_error($curl);
        curl_close($curl);
        if ($err) {
            return false;
        } else {
            return json_decode($response, true);
        }
    }
}