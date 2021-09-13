<?php


namespace app\dal\api;


use app\exception\APIException;
use think\facade\Log;

class Mexc
{
    private $host;

    private $accessKey;
    private $secret;

    /**
     * HuoBi constructor.
     */
    public function __construct()
    {
        $this->host = env('huobi.host');
        $this->accessKey = env('huobi.access_key');
        $this->secret = env('huobi.secret');
    }


    /**
     * @param string $symbol
     * @param string $type 'buy-limit'| 'sell-limit'
     * @param string $price 单价
     * @param string $amount 数量
     * @return array
     */
    public function buy(string $symbol, string $type, string $price, string $amount)
    {
        $path = '/open/api/v2/order/place';
        $post = [
            'symbol' => $symbol,
            'price' => $price,
            'trade_type' => 'BID',
            'type' => $type, // 限价买入
            'amount' => $amount,
            'client_order_id' => ACCOUNT_ID,
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
}