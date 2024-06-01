<?php

namespace App\Http\Controllers\Api\Transaction;

use App\Http\Controllers\Controller;
use App\Http\Services\HttpRequestLog;
use App\Http\Utils\GetNetUtil;
use App\Requests\TransactionInitRequest;
use Http;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\App;
use Symfony\Component\HttpFoundation\Response as HttpResponse;

class InitTransactionApiController extends Controller
{
    public function __construct()
    {
    }

    public function __invoke(TransactionInitRequest $request): JsonResponse
    {
        $getNetUtil = GetNetUtil::factory();

        $body = [
            'auth' => $getNetUtil->auth(),
            'locale' => 'es_CL',
            'payment' => [
                'reference' => $request->get('buy_order'),
                'description' => $request->get('session_id'),
                'amount' => [
                    'currency' => 'CLP',
                    'total' => $request->get('amount')
                ],
            ],
            'expiration' => $getNetUtil->getExpiredDate(),
            'ipAddress' => $request->getClientIp(),
            'returnUrl' => $request->get('return_url'),
            'userAgent' => !empty($request->userAgent()) ? $request->userAgent() : 'Mozilla/5.0, AppleWebKit/537.36, Chrome/104.0.0.0 Safari/537.3',

        ];

        $verify = App::isProduction();
        $getNetBaseUrl = env('GETNET_BASEURL');
        $response = Http::baseUrl($getNetBaseUrl)
            ->withOptions(["verify" => $verify])
            ->withHeaders([
                'Accept' => 'application/json',
                'Content-Type' => 'application/json',
            ])->post('/api/session', $body)
            ->json();


        if (!App::isProduction()) {
            HttpRequestLog::factory()->register($body, sprintf("RQ_%s", $request->get('buy_order')), 'init/');
            HttpRequestLog::factory()->register($response, sprintf("RS_%s", $request->get('buy_order')), 'init/');
        }

        if ($response["status"]["status"] === 'FAILED' && $response["status"]["reason"] === 401) {
            return response()->json((object)['message' => $response["status"]["message"]], HttpResponse::HTTP_UNAUTHORIZED);
        } else if ($response["status"]["status"] === 'OK') {
            return response()->json((object)[
                'url' => $response['processUrl'],
                'token' => $response['requestId'],
            ], HttpResponse::HTTP_OK);
        }

        return response()->json((object)[
            'message' => $response["status"]["message"],
        ], HttpResponse::HTTP_INTERNAL_SERVER_ERROR);
    }
}
