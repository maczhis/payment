<?php
/**
 * @author: helei
 * @createTime: 2016-07-25 14:49
 * @description:
 */

namespace Payment\Common\Ali\Data\Charge;


use Payment\Common\AliConfig;
use Payment\Config;
use Payment\Utils\ArrayUtil;

class AppChargeData extends ChargeBaseData
{
    /**
     * 构建 APP支付 加密数据
     * @author helei
     */
    protected function buildData()
    {
        $timeExpire = $this->timeExpire;

        if ($this->version) {
            $signData = $this->alipay2_0Data($timeExpire);
        } else {
            $signData = $this->alipay1_0Data($timeExpire);
        }

        // 移除数组中的空值
        $this->retData = ArrayUtil::paraFilter($signData);
    }

    /**
     * 老版本  app 支付数据
     * @param string $timeExpire  该笔订单允许的最晚付款时间
     *
     * @return array
     */
    private function alipay1_0Data($timeExpire = '')
    {
        $signData = [
            // 基本参数
            'service'   => '"' . 'mobile.securitypay.pay' . '"',
            'partner'   => '"' . trim($this->partner) . '"',
            '_input_charset'   => '"' . trim($this->inputCharset) . '"',
            'sign_type'   => '"' . trim($this->signType) . '"',
            'notify_url'    => '"' . trim($this->notifyUrl) . '"',

            // 业务参数
            'out_trade_no'  => '"' . trim($this->order_no) . '"',
            'subject'   => '"' . trim($this->subject) . '"',
            'payment_type'  => '"' . 1 . '"',
            'seller_id' => '"' . trim($this->partner) . '"',
            'total_fee' => '"' . trim($this->amount) . '"',
            'body'  => '"' . trim($this->body) . '"',
            'goods_type'    => '"' . 1 . '"', //默认为实物类型
        ];

        if (! empty($timeExpire)) {
            $signData['it_b_pay'] = '"' . trim($this->timeExpire) . 'm"';// 超时时间 统一使用分钟计算
        }

        return $signData;
    }

    /**
     * 新版本 app  支付数据
     *
     * @param string $timeExpire  该笔订单允许的最晚付款时间
     *
     * @return array
     */
    private function alipay2_0Data($timeExpire = '')
    {
        $signData = [
            // 公共参数
            'app_id'        => $this->appId,
            'method'        => Config::ALI_TRADE_APP,
            'format'        => $this->format,
            'charset'       => $this->inputCharset,
            'sign_type'     => $this->signType,
            'timestamp'     => $this->timestamp,
            'version'       => $this->version,
            'notify_url'    => $this->notifyUrl,

            // 业务参数  新版支付宝，将所有业务参数设置到改字段中了，  这样不错
            'biz_content'   => $this->getBizContent($timeExpire),
        ];

        return $signData;
    }

    /**
     * 业务请求参数的集合，最大长度不限，除公共参数外所有请求参数都必须放在这个参数中传递
     *
     * @param string $timeExpire 订单过期时间，  分钟
     *
     * @return string
     */
    private function getBizContent($timeExpire = '')
    {
        $content = [
            'body'          => $this->body,
            'subject'       => $this->subject,
            'out_trade_no'  => $this->order_no,
            'total_amount'  => $this->amount,

            // 销售产品码，商家和支付宝签约的产品码，为固定值QUICK_MSECURITY_PAY
            'product_code'  => 'QUICK_MSECURITY_PAY',
            'goods_type'    => 1,
        ];

        if (! empty($timeExpire)) {
            $content['timeout_express'] = $this->timeExpire . 'm';// 超时时间 统一使用分钟计算
        }

        $partner = $this->partner;
        if (! empty($partner)) {
            $content['seller_id'] = $partner;
        }

        return json_encode($content, JSON_UNESCAPED_SLASHES);
    }
}