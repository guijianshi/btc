<?php

namespace app\dal\api;

const TypeBuy = 1;
const TypeSell = 2;

interface IBTCAPI
{
    public function getAvg(string $symbol);
    public function buy(string $symbol, int $type, string $price, string $amount);
    public function query($order_id);
    public function assetValuation($valuation = 'USD');

}