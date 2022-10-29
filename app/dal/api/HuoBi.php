<?php

namespace app\dal\api;

use app\exception\APIException;
use think\facade\Log;

class HuoBi implements IBTCAPI
{
    private $host;

    private $accessKey;
    private $secret;
    private $accountId;

    /**
     * HuoBi constructor.
     */
    public function __construct()
    {
        $this->host = env('huobi.host');
        $this->accessKey = env('huobi.access_key');
        $this->secret = env('huobi.secret');
        $this->accountId = env('huobi.$this->accountId');
    }

    public function kline(string $symbol, string $period = '1min', int $size = 10)
    {
        $path = '/market/history/kline';
        $param = [
            'symbol' => $symbol,
            'period' => $period,
            'size' => $size,
        ];
        $param = $this->makeSign("GET", $this->host, $path, $param);
        $res = $this->getQuery($this->getRealUrl($path, $param));

        return $res['data'];
    }

    public function orderHistory()
    {
        $path = '/v1/order/history';
        $param = [
        ];
        $param = $this->makeSign("GET", $this->host, $path, $param);
        $res = $this->getQuery($this->getRealUrl($path, $param));
        return $res['data'];
    }

    /**
     * 获取均值
     * @param string $symbol
     * @return int|mixed
     */
    public function getAvg(string $symbol)
    {
        $res = $this->kline($symbol, '1min', 1);
        return $res[0]['high'];
    }


    /**
     * @param string $symbol
     * @param int $type TypeBuy TypeSell
     * @param string $price 单价
     * @param string $amount 数量
     * @return array
     * @throws APIException
     */
    public function buy(string $symbol, int $type, string $price, string $amount): array
    {
        $path = '/v1/order/orders/place';
        $post = [
            'account-id' => $this->accountId,
            'symbol'     => $symbol,
            'type'       => $type === TypeBuy ? "buy-limit" : "sell-limit", // 限价买入
            'amount'     => $amount,
            'price'      => $price,
        ];
        $param = $this->makeSign("POST", $this->host, $path, []);

        $curl = curl_init();

        curl_setopt_array($curl, array(
            CURLOPT_URL => $this->getRealUrl($path, $param),
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_ENCODING => "",
            CURLOPT_MAXREDIRS => 10,
            CURLOPT_TIMEOUT => 30,
            CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
            CURLOPT_CUSTOMREQUEST => "POST",
            CURLOPT_POSTFIELDS => json_encode($post),
            CURLOPT_HTTPHEADER => array(
                "Content-Type: application/json",
            ),
        ));

        $response = curl_exec($curl);
        $err = curl_error($curl);

        curl_close($curl);

        if ($err) {
            Log::info('生成订单返回错误:' . $err);
            throw new APIException('生成订单返回错误:' . $err);
        } else {
            Log::info('生成订单返回:' . $response);
            $res = json_decode($response, true);
            Log::info('生成订单返回:', $res);
            return ['order_id' => $res['data']];
        }
    }


    public function accounts()
    {
        $path = '/v1/account/accounts';
        $method = 'GET';
        $param = [
        ];
        $param = $this->makeSign($method, $this->host, $path, $param);
        $res = $this->getQuery($this->getRealUrl($path, $param));
        return $res['data'];
    }

    public function balance()
    {
        $path = sprintf('/v1/account/accounts/%s/balance', $this->accountId);
        $param = [

        ];
        $param = $this->makeSign("GET", $this->host, $path, $param);
        $res = $this->getQuery($this->getRealUrl($path, $param));

        return $res['data'];
    }

    public function query($order_id)
    {
        $path = '/v1/order/orders/' . $order_id;
        $param = [
        ];
        $param = $this->makeSign("GET", $this->host, $path, $param);
        $res = $this->getQuery($this->getRealUrl($path, $param));
        $state = $res['data']['state'];
        $amount = sprintf('%.4f', $res['data']['field-cash-amount']);
        return ['state' => $state, 'field_cash_amount' => $amount];
    }

    public function assetValuation($valuation = 'USD'):array
    {
        $path = '/v2/account/asset-valuation';
        $param = [
            'accountType' => 'spot',
            'valuationCurrency' => $valuation,  // BTC、CNY、USD、JPY、KRW、GBP、TRY、EUR、RUB、VND、HKD、TWD、MYR、SGD、AED、SAR
        ];
        $param = $this->makeSign("GET", $this->host, $path, $param);
        $res = $this->getQuery($this->getRealUrl($path, $param));
        $balance = $res['data']['balance'];
        $timestamp = $res['data']['timestamp'];
        return ['balance' => $balance, 'timestamp' => $timestamp, 'platform' => 'huobi', 'valuation' => $valuation];
    }

    private function getRealUrl($path, $paramSign)
    {
        $url = 'https://' . $this->host . $path . '?' . http_build_query($paramSign);
        fwrite(STDOUT, $url . PHP_EOL);
        return $url;
    }

    private function getQuery($url)
    {
        $res_str = file_get_contents($url);
        Log::info(sprintf('请求返回: %s', $res_str));
        $res = json_decode($res_str, true);
        if ((isset($res['status']) && 'ok' !== $res['status']) || (isset($res['code']) && $res['code'] !== 200)) {
            throw new APIException($res['err-msg']?? '请求错误' . json_encode($res));
        }
        return $res;
    }

    private function makeSign($method, $baseUrl, $path, $param)
    {
        $date = implode('T', explode(' ', date('Y-m-d H:i:s', time())));
        $param['AccessKeyId'] = $this->accessKey;
        $param['SignatureMethod'] = 'HmacSHA256';
        $param['SignatureVersion'] = '2';
        $param['Timestamp'] = $date;
        ksort($param);
        $param_str = http_build_query($param);
        $param_str = "$method\n$baseUrl\n$path\n$param_str";
        $sign = base64_encode(hash_hmac('sha256', $param_str, $this->secret, true));
        $param['Signature'] = $sign;
        return $param;
    }
}