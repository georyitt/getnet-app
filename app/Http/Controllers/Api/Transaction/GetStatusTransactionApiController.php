<?php

namespace App\Http\Controllers\Api\Transaction;

use App\Http\Controllers\Controller;
use App\Http\Services\HttpRequestLog;
use App\Http\Utils\GetNetUtil;
use DateTime;
use DateTimeZone;
use Exception;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;

class GetStatusTransactionApiController extends Controller
{
    public function __construct()
    {
    }

    /**
     * @throws Exception
     */
    public function __invoke(string $requestId, Request $request): JsonResponse
    {

        $cacheData = Cache::get('buy_order_' . $requestId);
        if ($cacheData) {
            $requestId = $cacheData;
        }
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

        if (App::isProduction()) {
            HttpRequestLog::factory()->register($body, sprintf("RQ_%s", $requestId), 'status/');
            HttpRequestLog::factory()->register($response, sprintf("RS_%s", $requestId), 'status/');
        }

        if ($response['status']['status'] === 'FAILED') {
            return response()->json(['error_message' => $response['status']['message']], 422);
        }

        if ($response['status']['status'] === 'OK' || $response['status']['status'] === 'PENDING' || $response['status']['status'] === 'REJECTED') {
            $originalDate = $response['status']['date'];
            $date = new DateTime($originalDate, new DateTimeZone('UTC'));

            $data = [
                'amount' => $response['request']['payment']['amount']['total'],
                'status' => $response['status']['status'] === 'REJECTED' ? 'NULLIFIED' : 'INITIALIZED',
                'buy_order' => $response['request']['payment']['reference'],
                'session_id' => $response['request']['payment']['description'],
                'accounting_date' => $date->format('md'),
                'transaction_date' => $date->format('Y-m-d\TH:i:s.v\Z'),
                'installments_number' => 0,
            ];
        } else {
            $originalDate = $response['payment'][0]['status']['date'];
            $date = new DateTime($originalDate, new DateTimeZone('UTC'));

            $processorFields = $response['payment'][0]['processorFields'];
            $data = [
                'vci' => $this->getVci($response['status']['status']),
                'amount' => $response['request']['payment']['amount']['total'],
                'status' => $this->getStatus($response['status']['status']),
                'buy_order' => $response['request']['payment']['reference'],
                'session_id' => $response['request']['payment']['description'],
                'card_detail' => [
                    'card_number' => $this->findValueKeyWord($processorFields, 'lastDigits'),
                ],
                'accounting_date' => $date->format('md'),
                'transaction_date' => $date->format('Y-m-d\TH:i:s.v\Z'),
                'authorization_code' => $response['payment'][0]['authorization'],
                'payment_type_code' => $this->getPaymentTypeCode($this->findValueKeyWord($processorFields, 'installments') ?? 0, $response['payment'][0]['paymentMethod']),
                'response_code' => $this->getResponseCode($response['status']['status']),
                'installments_number' => $this->findValueKeyWord($processorFields, 'installments') ?? 0,
            ];
        }
        return response()->json($data);
    }

    private function getVci(string $status): string
    {
        switch ($status) {
            case 'APPROVED':
                return 'TSY';
            case 'FAILED':
                return 'TSN';
        }
        return 'TSY';
    }

    private function getPaymentTypeCode(int $installmentsNumber, string $paymentMethod): string
    {
        if ($paymentMethod === 'Mastercard' && $installmentsNumber === 1) {
            return 'VD';
        }

        switch ($installmentsNumber) {
            case 1:
                return 'VN';
            case 2:
                return 'SI';
            case 3:
                return 'S2';
            case $installmentsNumber < 7:
                return 'NC';
            case $installmentsNumber >= 7:
                return 'VC';
        }
        return 'VC';
    }

    private function findValueKeyWord(array $processorFields, string $searchField)
    {
        $value = null;

        foreach ($processorFields as $field) {
            if ($field['keyword'] === $searchField) {
                $value = $field['value'];
                break;
            }
        }
        return $value;
    }

    private function getResponseCode(string $status): int
    {
        switch ($status) {
            case 'APPROVED':
                return 0;
            case 'FAILED':
                return -3;
            case 'REVERSED':
                return -4;
        }
        return -3;
    }

    private function getStatus(string $status): string
    {
        switch ($status) {
            case 'APPROVED':
                return 'AUTHORIZED';
            case 'OK':
                return 'INITIALIZED';
            case 'PENDING':
                return 'INITIALIZED';
            case 'FAILED':
                return 'FAILED';
            case 'REVERSED':
                return 'FAILED';
            default:
                return 'FAILED';
        }
    }
}
