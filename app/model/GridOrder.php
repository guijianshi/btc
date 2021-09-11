<?php
declare (strict_types = 1);

namespace app\model;

use think\Model;

/**
 * @mixin \think\Model
 * @property int id
 * @property string $symbol
 * @property string topPrice
 * @property string gridRate
 * @property string gridNum
 * @property int status
 */
class GridOrder extends Model
{
    //
    public function getRunningOrders()
    {
        $query = $this->where('status', '0');
        return $query->limit(200)->select();
    }
}
