<?php

namespace App\Jobs;

use App\Services\PaymentReceiptService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\DB;
use RuntimeException;
use Throwable;
/**
 * Defines the send payment receipt job component of the school-management application.
 *
 * This file contains application logic, framework configuration, or schema
 * changes. The method comments describe each operation's purpose and lifecycle.
 */

class SendPaymentReceiptJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $tries = 3;

    public $timeout = 120;

    /**
     * Payment ID whose verified receipt should be delivered.
     *
     * @var int
     */
    public $paymentId;

    /**
     * Helper method for SendPaymentReceiptJob.php; it performs the __construct step and returns or updates the data needed by its caller.
     */
    public function __construct($paymentId)
    {
        $this->paymentId = (int) $paymentId;
    }

    /**
     * Helper method for SendPaymentReceiptJob.php; it performs the backoff step and returns or updates the data needed by its caller.
     */
    public function backoff()
    {
        return [30, 120, 300];
    }

    /**
     * Execute the job or request processing logic and coordinate the operations described in this method.
     */
    public function handle(PaymentReceiptService $receiptService)
    {
        $result = $receiptService->sendForPayment($this->paymentId);

        if (($result['sent'] ?? false) === true) {
            return;
        }

        /*
         * Missing recipients or missing payment/student data are permanent
         * application conditions and should not consume queue retries.
         */
        if (($result['retryable'] ?? false) !== true) {
            return;
        }

        throw new RuntimeException(
            $result['reason'] ?? 'Payment receipt delivery failed.'
        );
    }

    /**
     * Helper method for SendPaymentReceiptJob.php; it performs the failed step and returns or updates the data needed by its caller.
     */
    public function failed(Throwable $exception)
    {
        DB::table('payment_audits')->insert([
            'payment_id' => $this->paymentId,
            'event' => 'receipt_job_failed',
            'details' => json_encode([
                'error' => $exception->getMessage(),
                'attempts' => $this->attempts(),
            ]),
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }
}
