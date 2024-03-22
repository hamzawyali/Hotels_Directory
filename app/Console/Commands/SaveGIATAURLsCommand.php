<?php

namespace App\Console\Commands;

use App\Services\RateService;
use Illuminate\Console\Command;

class SaveGIATAURLsCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'save:urls {rate_from} {rate_to}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Command description';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $rateFrom = $this->argument('rate_from');
        $rateTo = $this->argument('rate_to');
        $exec = new RateService;
        $exec->getHotelDirectoryURLs($rateFrom, $rateTo);
    }
}
