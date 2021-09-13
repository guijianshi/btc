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
            ->setDescription('查询任务');
    }

    protected function execute(Input $input, Output $output)
    {
        // 指令输出
        $output->writeln('查看账户总价值');

        $data = (new HuoBi())->assetValuation('USD');
        $output->writeln('美元计价:' . $data['balance']);
        (new ValuationLog())->save($data);

        $data = (new HuoBi())->assetValuation('CNY');
        $output->writeln('人名币计价:' . $data['balance']);
        (new ValuationLog())->save($data);

        $data = (new HuoBi())->assetValuation('BTC');
        (new ValuationLog())->save($data);
        $output->writeln('BTC计价:' . $data['balance']);

        $output->writeln('查询结束' .  json_encode($data));
    }
}
