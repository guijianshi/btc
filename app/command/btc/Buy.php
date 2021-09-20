<?php
declare (strict_types = 1);

namespace app\command\btc;

use app\model\BuyCmd;
use app\model\GridOrder;
use app\model\Order;
use think\console\Command;
use think\console\Input;
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

        $output->info("b:新增买入");
        $output->info("g:新增网格任务");
        $output->info("e:编辑普通任务");
        $output->info("a:查看所有任务");
        $output->info("c:清除冗余任务");
        $buyCmd = new BuyCmd($output);
        $buyCmd->PutCommand();
        switch ($buyCmd->getCommand()) {
            case 'b':
                $this->buyCmd($buyCmd);
                break;
            case 'g':
                $this->addGrid($buyCmd);
                break;
            case 'a':
                $this->getAll();
                break;
            case 'c':
                $this->clear($buyCmd);
                break;
            case 'e':
                $this->edit($buyCmd);
                break;
            default:
                $output->info("命令错误");
                break;
        }
    }

    public function edit(BuyCmd $buyCmd)
    {
        $orders = (new Order())->getCancelOrders();
        $this->printOrders($orders);
        $id = $buyCmd->getInput("请输入id:");
        $order = (new Order())->findById($id);
        if (empty($order) || $order->status != Order::STATUS_CANCEL) {
            $this->output->error("id不存在");
            return;
        }
        $buyCmd->editByOrder($order);;

    }

    public function clear(BuyCmd $buyCmd)
    {
        if ($buyCmd->setConfirm()) {
            if ((new Order())->delOldCancelTask()) {
                $this->output->info("删除成功!");
            } else {
                $this->output->error("删除失败!");
            }
        }
    }

    public function getAll()
    {
        $mod = $this->getInputWithCheckInArr(['m', 'g'], "请输入查看模式(标准/网格) m/g:");
        if ($mod === 'm') {
            $this->output->info("打印普通任务列表");
            $orders = (new Order())->getRunningOrders();
            $this->printOrders($orders);
        } else {
            $this->output->info("打印网格任务列表");
            $orders = (new GridOrder())->getRunningOrders();
            $this->output->info("id  \tsymbol  \ttop_price  \trate\tnum\t");
            $ids = [];
            foreach ($orders as $order) {
                $ids[] = $order['id'];
                $id = str_pad((string)$order['id'], 4);
                $symbol = str_pad($order['symbol'], 8);
                $top_price = str_pad($order['top_price'], 10);
                $rate = str_pad((string)$order['grid_rate'], 4);
                $num = str_pad((string)$order['grid_num'], 3);
                $this->output->info("$id\t{$symbol}\t{$top_price}\t{$rate}\t{$num}");
            }
        }
    }

    public function getInputWithCheckInArr($arr, $msg)
    {
        while (true) {
            $input = $this->getInput("$msg");
            if (in_array($input, $arr)) {
                return $input;
            } else {
                $this->output->info("输入错误请重新输入:");
            }
        }
        return '';
    }

    public function addGrid(BuyCmd $buyCmd)
    {
        // 指令输出
        $data = $buyCmd->gridBuy();
        if (empty($data)) {
            $this->output->info("取消操作");
            return;
        }
        $gridOrder = new GridOrder();
        $subOrders = $data['sub_order_list'];
        unset($data['sub_order_list']);

        if (!$gridOrder->save($data)) {
            $this->output->info("添加失败");
            return;
        }
        foreach ($subOrders as $key => $subOrder) {
            $subOrders[$key]['grid_id'] = $gridOrder->id;
        }
        $order = new Order();
        $order->saveAll($subOrders);
        $this->output->info("添加成功:" . $gridOrder->id);
    }

    public function buyCmd(BuyCmd $buyCmd)
    {
        // 指令输出
        $data = $buyCmd->commonBuy();
        if (empty($data)) {
            $this->output->info("取消操作");
            return;
        }
        $order = new Order();

        if ($order->save($data)) {
            $this->output->info("添加成功");
        } else {
            $this->output->info("添加失败");
        }
    }

    public function getInput($msg)
    {
        fwrite(STDOUT, $msg);
        return trim(fgets(STDIN));
    }

    public function printOrders($orders)
    {
        $this->output->info("id  \tsymbol  \tbuy_price  \tsale_price  \tstatus\t");
        /* @var $order Order */
        foreach ($orders as $order) {
            $id = str_pad((string)$order['id'], 4);
            $symbol = str_pad($order['symbol'], 8);
            $buy_price = str_pad($order['buy_price'], 10);
            $sale_price = str_pad($order['sale_price'], 10);
            $status = str_pad((string)$order['status'], 5);
            $this->output->info("$id\t{$symbol}\t{$buy_price}\t{$sale_price}\t{$status}");
        }
    }
}
