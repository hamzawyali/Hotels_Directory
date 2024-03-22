<?php

namespace App\Http\Controllers;

use App\Http\Requests\CountUrlValidationRequest;
use App\Services\RateService;
use Illuminate\Http\Request;

class RateController extends Controller
{
    /**
     * @return string
     * @throws \Exception
     */
    public function countUrls(CountUrlValidationRequest $request): string
    {
        $service = new RateService;
        return $service->countUrlsWithoutReminder($request->validated());
    }
}
