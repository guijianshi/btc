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
 * @property string $sale_price
 * @property string $num
 * @property string $buy_order_id
 * @property string $sale_order_id
 * @property string $status
 * @property string $direction
 */
class Order extends Model
{
    protected $table = "orders";
}
