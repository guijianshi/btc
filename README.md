# btc
代码垃圾不重要，能够赚钱的就是好代码

创建订单可以在app看见,app上点击取消,则任务就会自动停止


### 基本说明
1. 需要外网服务器,api墙内无法访问
2. 服务器需要添加白名单
3. 本脚本适合b市上下浮动
4. 环境依赖: PHP(推荐>7), MySQL(推荐5.7)
其他说明
// 收益率>7%会收益在投资,否则不会
// 收益率>10%,会截流2%不出售,留在账面上做种子
流程图
```
graph LR
创建任务-->买入挂单
买入挂单 --> 买入成功
买入成功 --> 卖出挂单
卖出挂单 --> 卖出成功
卖出成功 --> 买入挂单
APP取消 --> 流程中断
```


### 使用说明

1. 执行创建表语句
```sql
create database btc ;
use btc;
CREATE TABLE `orders` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT COMMENT 'id',
  `symbol` varchar(255) NOT NULL COMMENT '交易对',
  `buy_price` varchar(20) NOT NULL COMMENT '买入价格',
  `sale_price` varchar(20) NOT NULL COMMENT '卖出价格',
  `buy_order_id` varchar(20) NOT NULL DEFAULT '' COMMENT '买入订单号',
  `sale_order_id` varchar(20) NOT NULL DEFAULT '' COMMENT '卖出订单号',
  `num` varchar(10) NOT NULL DEFAULT '0' COMMENT '购买数量(万分之一个)',
  `status` tinyint(4) unsigned NOT NULL DEFAULT '0' COMMENT '状态0',
  `direction` varchar(20) NOT NULL DEFAULT '' COMMENT '交易方向,pay/sale',
  `parent_id` varchar(20) NOT NULL DEFAULT '' COMMENT '根订单号,空未无,其他为有',
  `ctime` datetime DEFAULT CURRENT_TIMESTAMP,
  `mtime` datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`) USING BTREE
) ENGINE=InnoDB  DEFAULT CHARSET=utf8mb4;

CREATE TABLE `orders_log` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT COMMENT 'id',
  `symbol` varchar(255) NOT NULL COMMENT '交易对',
  `buy_price` varchar(20) NOT NULL DEFAULT '' COMMENT '买入价格',
  `buy_total` varchar(20) NOT NULL DEFAULT '' COMMENT '买入总价',
  `buy_order_id` varchar(60) NOT NULL DEFAULT '' COMMENT '买入订单号',
  `buy_time` datetime DEFAULT NULL COMMENT '买入时间',
  `sale_price` varchar(20) NOT NULL DEFAULT '' COMMENT '卖出价格',
  `sale_total` varchar(20) NOT NULL DEFAULT '' COMMENT '卖出总价',
  `sale_order_id` varchar(60) NOT NULL DEFAULT '' COMMENT '卖出订单号',
  `sale_time` datetime DEFAULT NULL COMMENT '卖出时间',
  `num` varchar(10) NOT NULL DEFAULT '0' COMMENT '购买数量(万分之一个)',
  `status` tinyint(4) unsigned NOT NULL DEFAULT '0' COMMENT '状态0',
  `diff` varchar(20) NOT NULL DEFAULT '' COMMENT '差异差额',
  `ctime` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `mtime` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`) USING BTREE
) ENGINE=InnoDB  DEFAULT CHARSET=utf8mb4;

```

2. 添加对应配置
将config_bak.php 重命名成config.php 添加缺失配置
开发者相关信息去火币网web获取
```
// 数据库账号
const DB_NAME = 'root';
// 数据库密码
const DB_PWD = '';

// 自己账号值
const ACCOUNT_ID = ''; 

// 固定
const HOST = 'api.huobipro.com';

//  开发者SECRET
const SECRET = "";

// 开发者key
const ACCESS_KEY = "";
```

3. 常见命令

3.1 购买
```
php buy.php
b  //b 新建购买任务 e 编辑已经取消的购买任务
btc  // 对应需要买入虚拟币 默认价值为usdt   
// 返回现在市场价格
// 输入买入价
// 输入卖出价
// 输入购买数量
// 输入Y确认
```


3.2 执行定时脚本
方法一: 请用守护进程模式执行(脚本死循环执行,推荐结合supervisor)
```
php cli.php 
```
方法二: 定时任务模式, 每个任务执行耗时100ms,请合理控制频率(脚本只执行一轮)

```
php cron.php 
```
4. 查看收益

```
php earn.php
```


5. tp更新
```
CREATE TABLE `grid_order` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `top_price` varchar(20) NOT NULL DEFAULT '' COMMENT '订单最高金额',
  `grid_rate` varchar(5) NOT NULL COMMENT '每格利率',
  `grid_num` tinyint(4) NOT NULL DEFAULT '0' COMMENT '网格数量',
  `status` tinyint(4) NOT NULL DEFAULT '0' COMMENT '状态,0:初始化,1:已完结',
  `ctime` datetime DEFAULT CURRENT_TIMESTAMP COMMENT '创建时间',
  `mtime` datetime DEFAULT CURRENT_TIMESTAMP COMMENT '更新时间',
  `symbol` varchar(20) NOT NULL DEFAULT '' COMMENT '订单交易对',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='网格原始订单';


alter table orders
	add grid_id int default 0 not null comment '网格策略id';

create index idx_grid_id
	on orders (grid_id);


CREATE TABLE `valuation_log` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `platform` varchar(10) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '',
  `valuation` varchar(10) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '' COMMENT '计价类型',
  `balance` varchar(20) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '' COMMENT '当前价值',
  `timestamp` varchar(20) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '' COMMENT '时间戳毫秒',
  `ctime` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='账户总金额流水'
```


### 最后说明
币市有风险,投资需谨慎.