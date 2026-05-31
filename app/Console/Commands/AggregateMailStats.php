<?php

namespace App\Console\Commands;

use App\Services\StatsAggregator;
use Illuminate\Console\Command;

class AggregateMailStats extends Command
{
    protected $signature = 'mailadmin:aggregate';
    protected $description = '聚合每日和每月邮件统计';

    public function handle(StatsAggregator $aggregator): int
    {
        $aggregator->aggregate();
        $this->info('邮件统计已聚合。');
        return self::SUCCESS;
    }
}
