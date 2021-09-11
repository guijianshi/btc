<?php


namespace app\model;


use app\dal\api\HuoBi;
use think\console\Output;


class BuyCmd
{
    private $command;

    private $symbol;

    private $buyPrice;

    private $salePrice;

    private $total;

    private $num;

    private $confirm;

    private $gridRate;

    private $gridNum;

    private $avg;

    /**
     * @var string 最高金额用于网格交易
     */
    private $topPrice;

    /**
     * @return mixed
     */
    public function getCommand()
    {
        return $this->command;
    }

    /**
     * @var Output
     */
    private $output;

    public function __construct(Output $output)
    {
        $this->output = $output;
    }

    public function PutCommand()
    {
        while (true) {
            $command = strtolower(trim($this->getInput("请输入操作命令:")));
            if (in_array($command, ['a', 'b', 'e', 'g'])) {
                $this->command = $command;
                break;
            }
            $this->output->info("支持该命令");
        }
    }

    /**
     */
    public function setSymbol()
    {
        $symbol = strtolower(trim($this->getInput('请输入购买虚拟币名称,小写字母:')));
        $this->symbol = $symbol . 'usdt';
        return;
    }

    /**
     */
    public function setAvg(): void
    {
        $avg = (new HuoBi())->getAvg($this->symbol);
        $this->output->info("{$this->symbol} 最新价格: {$avg}");
        $this->avg = $avg;
    }

    /**
     */
    public function setSalePrice(): void
    {
        while (true) {
            $salePrice = $this->getInput("请输入卖出单价:");
            if ($salePrice < $this->buyPrice * 1.01) {
                $this->output->info("买入价:{$this->buyPrice}, 卖出价:{$salePrice}");
                $this->output->info("您输入的卖出价低于买入价");
            } else {
                $this->salePrice = $salePrice;
                break;
            }
        }
    }

    /**
     */
    public function setBuyPrice(): void
    {
        $buyPrice = trim($this->getInput('请输入买入单价:'));
        $this->buyPrice = $buyPrice;
    }


    /**
     */
    public function setTotal(callable $check): void
    {
        while (true) {
            $total = $this->getInput("请输入买入金额:");
            $msg = $check($total);
            if (!empty($msg)) {
                $this->output->info($msg);
            } else {
                $this->total = $total;
                break;
            }

        }
    }

    private function getBuyNum($total, $buyPrice)
    {
        $num = sprintf("%.10f", $total / $buyPrice);
        $map = [
            'btc' => '%.6f',
            'ar' => '%.2f',
            'eth' => '%.4f',
            'cspr' => '%.1f',
        ];
        $coin = substr($this->symbol, 0, -4);
        if (isset($map[$coin])) {
            $num = sprintf($map[$coin], $num) ;
            return $num;
        }
        if ($num < 0.0001) {
            $num = sprintf("%.5f7", $num) ;
        } elseif ($num < 0.1) {
            $num = sprintf("%.3f7", $num);
        } elseif ($num < 10) {
            $num = sprintf("%.2f", $num);
        } elseif ($num < 50) {
            $num = sprintf("%.1f", $num);
        } else {
            $num = intval($num);
        }
        return $num;
    }

    private function formatPrice($price)
    {
        if ($price > 100) {
            $price = sprintf("%.1f7", $price);
        } elseif ($price >= 1) {
            $price = sprintf("%.2f7", $price);
        } elseif ($price >= 0.02) {
            $price = sprintf("%.3f7", $price);
        } else {
            $i = 2;
            while (true) {
                if ($price[$i] != 0) {
                    break;
                }
            }
            $lastPoint = $i + 1;
            $price = sprintf("%.{$lastPoint}f7", $price);
        }
        return $price;
    }

    public function getInput($msg)
    {
        fwrite(STDOUT, $msg);
        return trim(fgets(STDIN));
    }

    public function setConfirm()
    {
        $confirm = strtoupper($this->getInput("确认操作Y/N:"));
        if ($confirm === 'Y') {
            $this->confirm = true;
        } else {
            $this->confirm = false;
        }
        return;
    }

    /**
     */
    public function setNum(): void
    {
        // (卖出单价 - 买入单价) * 买入数量
        $num = $this->getBuyNum($this->total, $this->buyPrice);
        $earn = sprintf("%.2f", ($this->salePrice - $this->buyPrice) * $num * 0.99);

        $this->output->info("您将买入:{$this->symbol}");
        $this->output->info("买入价:{$this->buyPrice}, 卖出价:{$this->salePrice}, 买入金额:{$this->total}usdt");

        $this->output->info("买入数量:{$num}, 预计盈利:{$earn} usdt");
        $this->num = $num;
        return;
    }

    /**
     */
    public function setTopPrice(): void
    {
        $price = $this->getInput("请输入网格最高价:");
        $this->topPrice = $price;
    }

    /**
     */
    public function setGridRate(): void
    {
        while (true) {
            $gridRate = intval($this->getInput("输入每个网格利率%:"));
            if ($gridRate < 1) {
                $this->output->warning("每格利率至少1%,请输入大于1的整数数值");
            } else {
                $this->gridRate = $gridRate;
                break;
            }
        }
    }

    /**
     */
    public function setGridNum(): void
    {
        while (true) {
            $gridNum = intval($this->getInput("请输入网格个数:"));
            if ($gridNum < 2) {
                $this->output->warning("网格数量必须大于1,请重新输入");
            } else {
                $this->gridNum = $gridNum;
                break;
            }
        }
    }

    public function commonBuy():array
    {
        $this->setSymbol();
        $this->setAvg();
        $this->setBuyPrice();
        $this->setSalePrice();
        $this->setTotal(function ($total) {
           if ($total < 5.01) {
               return '单笔买入金额不得小于5.01 usdt';
           }
           return '';
        });
        $this->setNum();
        $this->setConfirm();
        if (!$this->confirm) {
           return [];
        }
        return [
            'symbol' => $this->symbol,
            'buy_price' => $this->buyPrice,
            'sale_price' => $this->salePrice,
            'num' => $this->num,
            'direction' => 'buy',
        ];
    }

    public function gridBuy(): array
    {
        $this->setSymbol();
        $this->setAvg();
        $this->setTopPrice();
        $this->setGridRate();
        $this->setGridNum();
        $this->setTotal(function ($total) {
            if ($total / $this->gridNum < 5.01) {
                $minTotal = sprintf("%.2f", $this->gridNum * 5.01);
                return "单笔买入金额不得小于{$minTotal} usdt";
            }
            return '';
        });
        $top = $this->topPrice;

        $this->output->info("买入信息如下:");

        $subOrder = [];
        $itemTotal = sprintf("%.1f7", $this->total / $this->gridNum);
        for ($i = 1; $i <= $this->gridNum; $i++) {
            $bottomPrice = $top * (doubleval(100 - $this->gridRate)) / 100;
            $bottomPrice = $this->formatPrice($bottomPrice);

            $num = $this->getBuyNum($itemTotal, $bottomPrice);

            $this->output->info("{$i}: 买入金额{$bottomPrice}, 卖出金额{$top}, 买入数量 {$num}");

            $subOrder[] = [
                'symbol' => $this->symbol,
                'buy_price' => $bottomPrice,
                'sale_price' => $top,
                'num' => $num,
            ];

            $top = $bottomPrice;
        }

        $this->output->info("=====================");
        $this->output->warning("{$this->symbol}:最高金额{$this->topPrice}, 最低金额{$bottomPrice}");
        $this->setConfirm();
        if (!$this->confirm) {
            return [];
        }
        return [
            'symbol' => $this->symbol,
            'top_price' => $this->topPrice,
            'grid_rate' => $this->gridRate,
            'grid_num' => $this->gridNum,
            'sub_order_list' => $subOrder,
        ];
    }
}

