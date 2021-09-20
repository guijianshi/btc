<?php


namespace app\model;


use think\Model;

/**
 * @property int id
 * @property string $symbol
 * @property string buyPrice
 * @property string buyTotal
 * @property string buyOrderId
 * @property string buyTime
 * @property string salePrice
 * @property string saleTotal
 * @property string saleOrderId
 * @property string saleTime
 * @property string num
 * @property int status
 * @property string diff
 */
class OrderLog extends Model
{
    protected $table = 'orders_log';

    public function FindByBuyOrderId(string $buyOrderId)
    {
        $this->where('buy_order_id', $buyOrderId)->find();
    }
}