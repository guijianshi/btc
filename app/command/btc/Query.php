<?php
declare (strict_types = 1);

namespace app\command\btc;

use app\dal\api\HuoBi;
use app\model\ValuationLog;
use think\console\Command;
use think\console\Input;
use think\console\input\Argument;
use think\console\input\Option;
use think\console\Output;
use think\Model;

class Query extends Command
{
    protected function configure()
    {
        // 指令配置
        $this->setName('query')
            ->setDescription('查询任务')
            ->addArgument("type", Argument::OPTIONAL, '查询类型1:仅查询,2:查询并写入', '1');
    }

    protected function execute(Input $input, Output $output)
    {
        if ($input->getArgument('type') == 1) {
            $output->info("查询");
            $this->query();
        } else {
            $output->info("查询,并写入");
            $this->queryAndWrite();
        }
    }

    protected function query()
    {
        $data = (new ValuationLog())->selectByValuation('CNY');
        $this->output->info("id  \tplatform\tbalance\t");
        foreach ($data as $item) {
            $id = $item['id'];
            $platform = $item['platform'];
            $balance = $item['balance'];
            $this->output->info("$id  \t$platform    \t$balance  \t");
        }
    }

    protected function queryAndWrite()
    {
        // 指令输出
        $this->output->writeln('查看账户总价值');

        $data = (new HuoBi())->assetValuation('USD');
        $this->output->writeln('美元计价:' . $data['balance']);
        (new ValuationLog())->save($data);

        $data = (new HuoBi())->assetValuation('CNY');
        $this->output->writeln('人名币计价:' . $data['balance']);
        (new ValuationLog())->save($data);

        $data = (new HuoBi())->assetValuation('BTC');
        (new ValuationLog())->save($data);
        $this->output->writeln('BTC计价:' . $data['balance']);

        $this->output->writeln('查询结束' .  json_encode($data));
    }
}
