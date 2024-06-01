<?php

namespace Api\Transaction;

use Tests\TestCase;

class InitTransactionApiControllerTest extends TestCase
{
    public function test_successful_init_transaction()
    {
        $request = $this->json('POST', '/api/transaction/init',
            [
                'buy_order' => '203011',
                'session_id' => '43fds123123dfs',
                'amount' => 10000,
                'return_url' => 'http://localhost:3000/return?reference=203011',
            ],
            [
                'Accept' => 'application/json',
                'Content-Type' => 'application/json'
            ]
        );
        $request->dd();
    }
}
