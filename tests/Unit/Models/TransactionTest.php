<?php

namespace Tests\Unit\Models;

use App\Models\Transaction;
use Tests\TestCase;

class TransactionTest extends TestCase
{
    public function test_transaction_date_serializes_as_the_same_calendar_date_in_utc_plus_7(): void
    {
        $original = date_default_timezone_get();
        date_default_timezone_set('Asia/Ho_Chi_Minh');

        try {
            $transaction = new Transaction(['transaction_date' => '2026-09-19']);

            $this->assertSame('2026-09-19', $transaction->toArray()['transaction_date']);
        } finally {
            date_default_timezone_set($original);
        }
    }
}
