<?php
declare (strict_types = 1);

namespace app\command\btc;

use app\dal\api\HuoBi;
use think\console\Command;
use think\console\Input;
use think\console\Output;

class orderAutoTask extends Command
{
    protected function configure()
    {
        // 指令配置
        $this->setName('app\command\orderAutoTask')
            ->setDescription('the app\command\btcorderautotask command');
    }

    protected function execute(Input $input, Output $output)
    {
        // 指令输出
        $res = (new HuoBi())->kline("btcusdt");
        $output->writeln('app\command\orderAutoTask');
        $output->writeln(json_encode($res));
    }
}
