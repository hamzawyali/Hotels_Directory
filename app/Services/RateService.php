<?php

namespace App\Services;

use App\Jobs\ProcessUrlsJob;
use App\Models\Hotel;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Http\Client\Pool;
use Illuminate\Support\Facades\Cache;

class RateService
{
    /**
     * @param int $rateFrom
     * @param int $rateTo
     * @return void
     * @throws \Exception
     */
    public function getHotelDirectoryURLs(int $rateFrom, int $rateTo): void
    {
        $latestRevision = Cache::get(LATEST_REVISION) ?? 0;
        $response = $this->callGiataAPICURL(env('HOTEL_DIRECTORY_URL') . '?after=' . $latestRevision);
        $data = json_decode($response, true);
        Cache::forever(LATEST_REVISION, $data['latestRevision']);
        $this->checkValidGiataId($data['urls'], $rateFrom, $rateTo);
    }

    /**
     * @param array $urls
     * @param $rateFrom
     * @param $rateTo
     * @return void
     */
    public function checkValidGiataId(array $urls, $rateFrom, $rateTo): void
    {
        $ratingArr = range($rateFrom + 1, $rateTo - 1);
        $chunkUrls = array_chunk($urls, 100);
        foreach ($chunkUrls as $chunkUrl) {
            Http::pool(function (Pool $pool) use ($urls, $chunkUrl, $ratingArr, &$responses) {
                collect($chunkUrl)->each(function ($url, $key) use ($pool, $chunkUrl, $ratingArr, &$responses) {
                    preg_match('/\/(\d+)\//', $url, $matches);
                    $giataId = (int)$matches[1];
                    for ($x = 0; $x < count($ratingArr); $x++) {
                        if ($giataId % $ratingArr[$x] != 0) {
                            continue;
                        }
                        $pool->get($url)->then(function ($response) use ($url, $key, &$responses) {
                            $response = $response->json();
                            if ($response['source'] == SOURCE) {
                                $hotelName = $response['names'][0]['value'] ?? null;
                                $giataId = $response['giataId'] ?? null;
                                $rate = str_replace(',', '.', $response['ratings'][0]['value']) ?? null;
                                if (fmod($giataId, $rate) == 0) {
                                    log::info('giataId: ' . $giataId);
                                    log::info('rate: ' . $rate);
                                    ProcessUrlsJob::dispatch(['name' => $hotelName, 'GIATA_id' => $giataId, 'rate' => $rate, 'batchKey' => $key]);
                                }
                            }
                        });
                        break;
                    }
                });
            });
        }
//        echo 'URLS dispatched successfully';
    }

    /**
     * @param $url
     * @return bool|string
     */
    public function callGiataAPICURL($url): bool|string
    {
        try {
            $curl = curl_init();
            curl_setopt_array($curl, array(
                CURLOPT_URL => $url,
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_ENCODING => "",
                CURLOPT_MAXREDIRS => 10,
                CURLOPT_TIMEOUT => 30000,
                CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
                CURLOPT_CUSTOMREQUEST => "GET",
                CURLOPT_HTTPHEADER => array(
                    "accept: */*",
                    "accept-language: en-US,en;q=0.8",
                    "content-type: application/json",
                ),
            ));

            $response = curl_exec($curl);
            curl_close($curl);
            return $response;
        } catch (\Exception $exception) {
            Log::error($exception->getMessage());
            throw $exception;
        }
    }

    /**
     * @return string
     * @throws \Exception
     */
    public function countUrlsWithoutReminder($request): string
    {
        $check = $this->callGiataAPICURL(env('HOTEL_DIRECTORY_URL') . '?after=' . Cache::get(LATEST_REVISION));
        $data = json_decode($check, true);
        if ($data['urls']) {
            $this->checkValidGiataId($data['urls'], $request['rate_from'], $request['rate_to']);
        }
        if ($data['deletedUrls']) {
            foreach ($data['deletedUrls'] as $deletedUrl) {
                preg_match('/\/(\d+)\//', $deletedUrl, $matches);
                $deletedGiataId[] = (int)$matches[1];
            }
            Hotel::whereIn('GIATA_id', $deletedGiataId)->delete();
        }
        if ($data['latestRevision'] != Cache::get(LATEST_REVISION)) {
            Cache::forget(LATEST_REVISION);
            Cache::put(LATEST_REVISION, $data['latestRevision']);
        }
        return Hotel::count();
    }
}
