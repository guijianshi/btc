<?php
declare (strict_types = 1);

namespace app\model;

use think\Model;

/**
 * @mixin \think\Model
 */

/**
 * Class Order
 * @package app\model
 * @property string $symbol
 * @property string $buy_price
 * @property int grid_id
 * @property string $sale_price
 * @property string $num
 * @property string $buy_order_id
 * @property string $sale_order_id
 * @property string $status
 * @property string $direction
 * @property string $parentId
 */
class Order extends Model
{
    protected $table = "orders";

    public function getRunningOrders($symbol = '')
    {
        $query = $this->whereIn('status', '0,1,2,3');
        if (!empty($symbol)) {
            $query = $query->where('symbol', $symbol);
        }
        return $query->limit(200)->select();
    }
}
