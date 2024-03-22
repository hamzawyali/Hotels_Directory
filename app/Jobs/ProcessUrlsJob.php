<?php

namespace App\Jobs;

use App\Models\Hotel;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Cache;

class ProcessUrlsJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * Create a new job instance.
     */
    public function __construct(public array $data)
    {
        //
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        $cacheKey = $this->data['GIATA_id'];
        Cache::put($cacheKey, ['name' => $this->data['name'], 'GIATA_id' => $this->data['GIATA_id'], 'rate' => $this->data['rate']], 3600);
        ReceiveUrlsJob::dispatch($this->data['GIATA_id']);
    }
}
