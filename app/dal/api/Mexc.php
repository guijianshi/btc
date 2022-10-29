<?php


namespace app\dal\api;


use app\exception\APIException;
use think\facade\Log;

class Mexc implements IBTCAPI
{
    private $host;

    private $accessKey;
    private $secret;

    /**
     * HuoBi constructor.
     */
    public function __construct()
    {
        $this->host = env('mexc.host');
        $this->accessKey = env('mexc.access_key');
        $this->secret = env('mexc.secret');
    }


    /**
     * @param string $symbol
     * @param int $type 'buy-limit'| 'sell-limit'
     * @param string $price 单价
     * @param string $amount 数量
     * @return array ["data" => {order_id}, "code" => 200]
     */
    public function buy(string $symbol, int $type, string $price, string $amount)
    {
        $path = '/open/api/v2/order/place';
        $post = [
            "symbol" => $symbol,
            "price" => $price,
            "quantity" => $amount,
            "trade_type" => "ASK", // BID，ASK
            "order_type" => "LIMIT_ORDER", //
        ];
        return $this->post($path, $post);

    }

    public function getAvg(string $symbol)
    {
        $path = "/api/v3/avgPrice";
        $param = [
            "symbol" => $symbol,
        ];
        return $this->get($path, $param);

    }

    public function account()
    {
        $path = '/api/v3/account';
        $param = [
            'timestamp' => intval(microtime(true) * 1000)
        ];
        return $this->get($path, $param);
    }

    public function query($order_id)
    {
        $path = '/open/api/v2/order/query';
        $param = [
            "order_ids" => $order_id,
        ];

        return $this->get($path, $param);
    }

    public function assetValuation($valuation = 'USD')
    {
        // TODO: Implement assetValuation() method.
    }

    function info()
    {
        $path = '/open/api/v2/account/info';
        return $this->get($path, []);
    }

    private function post($path, $param)
    {
        $curl = curl_init();
        $time = time() * 1000;
        if (!empty($param)) {
            $param = json_encode($param);
        } else {
            $param = '';
        }

        $what = $this->secret . $time . $param;
        $sign = hash_hmac("sha256", $what, $this->accessKey);


        curl_setopt_array($curl, array(
            CURLOPT_URL => 'https://www.mexc.com' . $path,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_ENCODING => '',
            CURLOPT_MAXREDIRS => 10,
            CURLOPT_TIMEOUT => 1000,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
            CURLOPT_CUSTOMREQUEST => 'POST',
            CURLOPT_POSTFIELDS => $param,
            CURLOPT_HTTPHEADER => array(
                'Content-Type: application/json',
                'ApiKey: ' . $this->secret,
                'Request-Time: ' . $time,
                'Signature: ' . $sign,
            ),
        ));

        $response = curl_exec($curl);

        curl_close($curl);
        return json_decode($response, true);
    }

    private function isSuccess($resp) {
        return isset($resp['code']) && 200 == $resp['code'];
    }

    private function get($path, $param)
    {
        $curl = curl_init();
        $time = time() * 1000;
        if (!empty($param)) {
            $signStr = implode('&', $this->urlSort($param));
        } else {
            $signStr = '';
        }

        $what = $this->secret . $time . $signStr;
        $sign = hash_hmac("sha256", $what, $this->accessKey);

        $url = 'https://www.mexc.com' . $path;
        if (!empty($param)) {
            $url = 'https://www.mexc.com' . $path . '?' . http_build_query($param);
            echo $url . PHP_EOL;
        }
        curl_setopt_array($curl, array(
            CURLOPT_URL => $url,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_ENCODING => '',
            CURLOPT_MAXREDIRS => 10,
            CURLOPT_TIMEOUT => 1000,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
            CURLOPT_CUSTOMREQUEST => 'GET',
            CURLOPT_HTTPHEADER => array(
                'Content-Type: application/json',
                'ApiKey: ' . $this->secret,
                'Request-Time: ' . $time,
                'Signature: ' . $sign
            ),
        ));

        $response = curl_exec($curl);

        curl_close($curl);
        return json_decode($response, true);
    }

    private function urlSort($param)
    {
        $u = [];
        $sort_rank = [];
        foreach ($param as $k => $v) {
            $u[] = $k . "=" . urlencode($v);
            $sort_rank[] = ord($k);
        }
        asort($u);

        return $u;
    }
}