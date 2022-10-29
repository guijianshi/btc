<?php
declare (strict_types = 1);

namespace app\command\btc;

use app\dal\api\Mexc;
use think\console\Command;
use think\console\Input;
use think\console\input\Argument;
use think\console\input\Option;
use think\console\Output;

class Test extends Command
{
    protected function configure()
    {
        // 指令配置
        $this->setName('app\command\btc\test')
            ->setDescription('测试信息');
    }

    protected function execute(Input $input, Output $output)
    {
        $output->writeln('进入测试');
        $data = (new Mexc())->info();
        $output->info(json_encode($data));
        $data = (new Mexc())->account();
        $output->info(json_encode($data));
        $data = (new Mexc())->getAvg("BTCUSDT");
        $output->info(json_encode($data));
    }
}
