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

    public function __construct($paymentId)
    {
        $this->paymentId = (int) $paymentId;
    }

    public function backoff()
    {
        return [30, 120, 300];
    }

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
