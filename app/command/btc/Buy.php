<?php
declare (strict_types = 1);

namespace app\command\btc;

use app\model\Order;
use think\console\Command;
use think\console\Input;
use think\console\input\Argument;
use think\console\input\Option;
use think\console\Output;

class Buy extends Command
{
    protected function configure()
    {
        // 指令配置
        $this->setName('buy')
            ->setDescription('the buy command');
    }

    protected function execute(Input $input, Output $output)
    {
        // 指令输出
        $symbol = $this->getSymbolByInput();
        $buyPrice = $this->getInput("请输入买入单价:");
        while (true) {
            $salePrice = $this->getInput("请输入卖出单价:");
            if ($salePrice < $buyPrice * 1.01) {
                $output->info("买入价:{$buyPrice}, 卖出价:{$salePrice}");
                $output->info("您输入的卖出价低于买入价");
            } else {
                break;
            }
        }
        while (true) {
            $total = $this->getInput("请输入买入金额:");
            if ($total < 5.01) {
                $output->info("单笔交易额不得小于5.01usdt");
            } else {
                break;
            }
        }
        // (卖出单价 - 买入单价) * 买入数量
        $num = $this->symbolNum($symbol, $this->getBuyNum($total, $buyPrice));
        $earn = sprintf("%.2f", ($salePrice - $buyPrice) * $num * 0.99);

        $output->info("您将买入:{$symbol}");
        $output->info("买入价:{$buyPrice}, 卖出价:{$salePrice}, 买入金额:{$total}usdt");

        $output->info("买入数量:{$num}, 预计盈利:{$earn}usdt");

        $order = new Order();
        $order->symbol = $symbol;
        $order->buy_price = $buyPrice;
        $order->sale_price = $salePrice;
        $order->num = $num;

        if ($order->save()) {
            $output->info("添加成功");
        } else {
            $output->info("添加失败");
        }
    }

    public function getSymbolByInput()
    {
        fwrite(STDOUT, "请输入购买虚拟币名称,小写字母:");
        $symbol = trim(fgets(STDIN)) . 'usdt';  // 从控制台读取输入
        $this->output->writeln($symbol);

        return $symbol;
    }

    public function getInput($msg)
    {
        fwrite(STDOUT, $msg);
        return trim(fgets(STDIN));
    }

    public function getBuyNum($total, $buyPrice)
    {
        $num = sprintf("%.10f", $total / $buyPrice);
        if ($num < 0.0001) {
            $num = sprintf("%.5f7", $num) ;
        } elseif ($num < 0.1) {
            $num = sprintf("%.3f7", $num);
        } elseif ($num < 10) {
            $num = sprintf("%.2f", $num);
        } elseif ($num < 100) {
            $num = sprintf("%.1f", $num);
        } else {
            $num = intval($num);
        }
        return $num;
    }

    public function symbolNum($symbol, $num)
    {
        if ($symbol == 'arusdt') {
            $num = sprintf("%.2f", $num);
        }
        return $num;
    }
}
