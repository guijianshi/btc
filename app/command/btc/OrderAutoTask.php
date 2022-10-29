<?php
declare (strict_types = 1);

namespace app\command\btc;

use app\dal\api\HuoBi;
use app\exception\APIException;
use app\model\Order;
use app\model\OrderLog;
use think\console\Command;
use think\console\Input;
use think\console\Output;
use const app\dal\api\TypeBuy;
use const app\dal\api\TypeSell;

class OrderAutoTask extends Command
{
    /**
     * @deprecated
     */
    protected function configure()
    {
        // 指令配置
        $this->setName('app\command\orderAutoTask')
            ->setDescription('the app\command\btcorderautotask command');
    }

    protected function execute(Input $input, Output $output)
    {
        // 指令输出
        $orderDal = new Order();
        $orders  = $orderDal->getRunningOrders();
        $huobi = new HuoBi();
        /* @var Order $order */
        $subOrderId = microtime(true) * 10000 % 10;
        foreach ($orders as $order) {
            // 老订单可以更新频率低一些 三天之前 每次更新十分之一
            if ($order['mtime'] < date('Y-m-d H:i:s', strtotime('-3 day')) && $order['id'] % 10 != $subOrderId) {
                continue;
            }
            try {
                if ($order->status == Order::STATUS_WAIT) {
                    $buyResp = $huobi->buy($order->symbol, TypeBuy, $order->buy_price, $order->num);
                    $order->buy_order_id = $buyResp['order_id'];
                    $order->status = Order::STATUS_BUY_ING;
                    if ($order->save()) {
                        $orderLog = new OrderLog();
                        $orderLog->symbol = $order->symbol;
                        $orderLog->buyPrice = $order->buy_price;
                        $orderLog->buyOrderId = $order->buy_order_id;
                        $orderLog->save();
                        $this->output->info("买入成功:" . $order->id);
                    } else {
                        $this->output->error("买入失败:" . $order->id);
                    }
                } elseif ($order->status == Order::STATUS_BUY_ING) {
                    $queryResp = $huobi->query($order->buy_order_id);

                    if ($queryResp['state'] == 'canceled') {
                        // 撤销订单,将购买任务取消
                        $order->cancel();
                    } elseif ('filled' !== $queryResp['state']) {
                        $order->direction = 'sale';
                        $order->status = Order::STATUS_SALE_WAIT;
                        if ($order->save()) {
                            //
                            $this->output->info("订单买入成功:" . $order->id);
                        } else {
                            $this->output->error("订单买入失败:" . $order->id);
                        }
                    }
                } elseif ($order->status == Order::STATUS_SALE_WAIT) {
                    $buyResp = $huobi->buy($order->symbol, TypeSell, $order->sale_price, $order->num);
                    $order->sale_order_id = $buyResp['order_id'];
                    $order->status = Order::STATUS_SALE_ING;
                    if ($order->save()) {
                        $orderLog = (new OrderLog())->FindByBuyOrderId($order->buy_order_id);
                        $orderLog->salePrice = $order->sale_price;
                        $orderLog->saleOrderId = $order->sale_order_id;

                        $this->output->info("创建卖出成功:" . $order->id);
                    } else {
                        $this->output->error("创建卖出失败:" . $order->id);
                    }
                } elseif ($order->status == Order::STATUS_SALE_ING) {
                    $queryResp = $huobi->query($order->sale_order_id);
                    if ($queryResp['state'] == 'canceled') {
                        // 撤销订单,将购买任务取消
                        $order->cancel();
                    } elseif ('filled' !== $queryResp['state']) {
                        $order->direction = 'buy';
                        $order->status = Order::STATUS_WAIT;
                        if ($order->save()) {
                            $this->output->info("订单买入成功:" . $order->id);
                        } else {
                            $this->output->error("订单买入失败:" . $order->id);
                        }
                    }
                    $order->cancel();
                } else {
                    $this->output->error("无效订单:" . $order->id);
                }
            } catch (APIException $e) {
                $this->output->info("发生异常:" . $e->getMessage());
            }
        }
    }
}
