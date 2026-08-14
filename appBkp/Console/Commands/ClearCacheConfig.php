<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Log;

class ClearCacheConfig extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'clear:cache-config';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'This command will clear cache and config';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        Artisan::call('optimize:clear');
        Artisan::call('config:cache');
        Artisan::call('config:clear');
        Artisan::call('cache:clear');
        //Log::info('Cache clear cron ended');
    }
}