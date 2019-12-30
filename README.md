# wxtool
微信生态圈类库

## 安装

> composer require oywenjiao/wxtool

## 文件说明
+ WxBase 微信基础操作
+ Core 
  + Order 订单相关操作。(包含有原生支付（扫码支付）/ 公众号支付 / H5支付 / 现金红包 / 企业付款到零钱 等功能)
  + TemplateMessage 微信模板消息发送
+ Qrcode
  + QRcode 生成二维码

## 使用

```php
// 实例化公共类
$base = new WxBase($appId, $appSecret);

// 调用统一下单接口
$wx_order = new Order($base);
$wx_order->setMchID($mchId)->setKey($key);
$res = $wx_order->unifiedOrder($openid, $trade_sn, $price, $notify, $body);

// 调用企业付款 
$wx_order = new Order($base);
$wx_order->setMchID($obj->mchId)->setKey($obj->key)->setCert($cert)->setSslKey($key);
$res = $wx_order->payToUser($openid, $trade_sn, $price, $desc);
```
