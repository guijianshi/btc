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
    const STATUS_WAIT = 0; // 初始化
    const STATUS_BUY_ING = 1; // 购买中
    const STATUS_SALE_WAIT = 2; // 待出售
    const STATUS_SALE_ING = 3; // 出售中
    const STATUS_CANCEL = 4; // 取消

    protected $table = "orders";

    public function getRunningOrders($symbol = '')
    {
        $query = $this->whereIn('status', '0,1,2,3');
        if (!empty($symbol)) {
            $query = $query->where('symbol', $symbol);
        }
        return $query->limit(300)->select();
    }

    public function delOldCancelTask():bool
    {
        $query = $this->where('status', 4)
            ->where('grid_id', 0)
            ->where('mtime', '<', date('Y-m-d', strtotime("-1week")));

        return $query->limit(20)->delete();
    }

    public function cancel()
    {
        $this->status = 4;
    }
}
