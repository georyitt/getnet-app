<?php

namespace App\Http\Controllers\Api\Transaction;

use App\Http\Controllers\Controller;
use App\Http\Services\HttpRequestLog;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response as HttpResponse;

class NotificationTransactionApiController extends Controller
{

    public function __invoke(Request $request): JsonResponse
    {
        $data = $request->all();
        if ($request->get('reference')) {
            return response()->json((object)['message' => "reference not found"], HttpResponse::HTTP_INTERNAL_SERVER_ERROR);
        }
        HttpRequestLog::factory()->register($data, sprintf("notification_%s", $request->get('reference')), 'notification/');
        return response()->json((object)['message' => "success",], HttpResponse::HTTP_OK);
    }
}
