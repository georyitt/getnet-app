<?php

namespace Api\Transaction;

use Tests\TestCase;

class GetStatusTransactionApiControllerTest extends TestCase
{
    public function test_successful_get_status_transaction()
    {
        $requestId = '59106';
        $request = $this->get(sprintf("/api/transaction/status/%s", $requestId),
            [
                'Accept' => 'application/json',
                'Content-Type' => 'application/json'
            ]
        );
        $request->dd();
    }
}
