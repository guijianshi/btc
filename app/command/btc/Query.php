<?php
declare (strict_types = 1);

namespace app\command\btc;

use app\dal\api\HuoBi;
use think\console\Command;
use think\console\Input;
use think\console\input\Argument;
use think\console\input\Option;
use think\console\Output;

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
        $output->writeln('buy');
        $data = (new HuoBi())->valuation();
        $output->writeln('data:' .  json_encode($data));
    }
}
