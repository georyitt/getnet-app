<?php

namespace App\Http\Controllers\Api\Transaction;

use App\Http\Controllers\Controller;
use App\Http\Services\HttpRequestLog;
use App\Http\Utils\GetNetUtil;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Http;

class GetStatusTransactionApiController extends Controller
{
    public function __construct()
    {
    }

    public function __invoke(string $requestId, Request $request): JsonResponse
    {
        $getNetUtil = GetNetUtil::factory();

        $body = [
            'auth' => $getNetUtil->auth(),
        ];

        $verify = App::isProduction();
        $getNetBaseUrl = env('GETNET_BASEURL');
        $response = Http::baseUrl($getNetBaseUrl)
            ->withOptions(["verify" => $verify])
            ->withHeaders([
                'Accept' => 'application/json',
                'Content-Type' => 'application/json',
            ])->post(sprintf("/api/session/%s", $requestId), $body)
            ->json();

        if (!App::isProduction()) {
            HttpRequestLog::factory()->register($body, sprintf("RQ_%s", $requestId), 'status/');
            HttpRequestLog::factory()->register($response, sprintf("RS_%s", $requestId), 'status/');
        }

        return response()->json($body);
    }
}
