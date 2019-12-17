# wxtool
微信生态圈类库

## 安装

> composer require oywenjiao/wxtool

## 使用

```php
// 实例化公共类
$base = new WxBase($appId, $appSecret);

// 调用统一下单接口
$wx_order = new Order($base);
$wx_order->setMchID($mchId)->setKey($key);
$res = $wx->unifiedOrder($openid, $trade_sn, $price, $notify, $body);
```
