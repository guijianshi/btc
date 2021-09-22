<?php
declare (strict_types = 1);

namespace app\model;

use think\Model;

/**
 * @mixin \think\Model
 * @property string platform
 * @property string valuation
 * @property string balance
 * @property string timestamp
 */
class ValuationLog extends Model
{
    //
    protected $table = "valuation_log";

    public function selectByValuation(string $valuation)
    {
        return $this->where('valuation', $valuation)
            ->order('ctime', 'desc')
            ->limit(100)
            ->select();
    }
}
