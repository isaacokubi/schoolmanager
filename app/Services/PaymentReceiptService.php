<?php

namespace App\Services;

use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use Throwable;
/**
 * Defines the payment receipt service component of the school-management application.
 * This file contains service logic or database schema changes used by the application.
 */

class PaymentReceiptService
{
    /**
     * Generate and email a verified payment receipt.
     *
     * A cache lock normally serializes queue workers. If the configured cache
     * backend cannot provide locks, a database row lock is used as a fallback;
     * the receipt audit remains the idempotency guard.
     */
    public function sendForPayment($paymentId)
    {
        $paymentId = (int) $paymentId;
        $lockKey = 'schoolmanager:payment-receipt:' . $paymentId;

        /*
         * Prefer the configured cache lock for queue workers. Some test and
         * small-hosting environments have a misconfigured cache lock store;
         * use the payment row lock as a safe fallback instead of failing every
         * receipt before its PDF/email work can start.
         */
        try {
            $lock = Cache::lock($lockKey, 120);
            $acquired = $lock->get();
        } catch (Throwable $e) {
            report($e);

            return $this->sendWithDatabaseLock($paymentId);
        }

        if (!$acquired) {
            try {
                return $lock->block(10, function () use ($paymentId) {
                    return $this->sendLocked($paymentId);
                });
            } catch (Throwable $e) {
                report($e);

                return [
                    'sent' => false,
                    'retryable' => true,
                    'reason' => 'Receipt delivery is temporarily locked. Please retry.',
                ];
            } finally {
                try {
                    $lock->release();
                } catch (Throwable $e) {
                    // Lock cleanup must not replace the delivery result.
                    report($e);
                }
            }
        }

        try {
            return $this->sendLocked($paymentId);
        } catch (Throwable $e) {
            report($e);

            return [
                'sent' => false,
                'retryable' => true,
                'reason' => 'Receipt generation or delivery failed.',
            ];
        } finally {
            try {
                $lock->release();
            } catch (Throwable $e) {
                // Lock cleanup must not replace the delivery result.
                report($e);
            }
        }
    }

    /**
     * Fallback for environments where the configured cache store cannot lock.
     *
     * Locking the payment row serializes receipt attempts on databases that
     * support row-level locks; the audit check in sendLocked remains the
     * idempotency guard for sequential retries and SQLite-based tests.
     */
    private function sendWithDatabaseLock($paymentId)
    {
        try {
            return DB::transaction(function () use ($paymentId) {
                DB::table('payments')
                    ->where('id', $paymentId)
                    ->lockForUpdate()
                    ->first();

                return $this->sendLocked($paymentId);
            });
        } catch (Throwable $e) {
            report($e);

            return [
                'sent' => false,
                'retryable' => true,
                'reason' => 'Receipt delivery is temporarily unavailable. Please retry.',
            ];
        }
    }

    /**
     * Actual receipt delivery. Caller must hold the payment receipt lock.
     */
    private function sendLocked($paymentId)
    {
        $payment = DB::table('payments')
            ->where('id', $paymentId)
            ->first();

        if (!$payment || $payment->status !== 'completed') {
            return [
                'sent' => false,
                'retryable' => false,
                'reason' => 'Payment is not completed.',
            ];
        }

        /*
         * Idempotency guard inside the lock.
         */
        $alreadySent = DB::table('payment_audits')
            ->where('payment_id', $payment->id)
            ->where('event', 'receipt_sent')
            ->exists();

        if ($alreadySent) {
            return [
                'sent' => true,
                'already_sent' => true,
                'receipt_number' => $payment->mpesa_receipt ?: ('PAY-' . $payment->id),
            ];
        }

        $student = $payment->student_id
            ? DB::table('students')->where('id', $payment->student_id)->first()
            : null;

        if (!$student) {
            return [
                'sent' => false,
                'retryable' => false,
                'reason' => 'Payment has no linked student.',
            ];
        }

        $parent = null;

        if (!empty($student->parent_id)) {
            $parent = DB::table('parents')
                ->where('id', $student->parent_id)
                ->first();
        }

        /*
         * Parent/sponsor recipients are the actual receipt recipients.
         * Administrators are never used as the primary recipient.
         */
        $recipients = [];

        if (
            $parent &&
            !empty($parent->email) &&
            filter_var($parent->email, FILTER_VALIDATE_EMAIL)
        ) {
            $recipients[] = strtolower(trim($parent->email));
        }

        try {
            $portalRows = DB::table('portal_profiles')
                ->join('users', 'users.id', '=', 'portal_profiles.user_id')
                ->where('portal_profiles.active', true)
                ->whereIn('portal_profiles.portal_type', ['parent', 'sponsor'])
                ->where('portal_profiles.admission_number', $student->admission_number)
                ->whereNotNull('users.email')
                ->select('users.email')
                ->get();

            foreach ($portalRows as $portalRow) {
                if (
                    !empty($portalRow->email) &&
                    filter_var($portalRow->email, FILTER_VALIDATE_EMAIL)
                ) {
                    $recipients[] = strtolower(trim($portalRow->email));
                }
            }
        } catch (Throwable $e) {
            report($e);

            return [
                'sent' => false,
                'retryable' => true,
                'reason' => 'Portal recipient lookup failed.',
            ];
        }

        $recipients = array_values(
            array_unique(array_filter($recipients))
        );

        /*
         * Resolve administrative BCC recipients.
         */
        $adminRecipients = [];

        try {
            $admins = DB::table('users')
                ->whereIn('role', ['admin', 'administrator', 'superadmin'])
                ->whereNotNull('email')
                ->pluck('email');

            foreach ($admins as $email) {
                if (
                    !empty($email) &&
                    filter_var($email, FILTER_VALIDATE_EMAIL)
                ) {
                    $adminRecipients[] = strtolower(trim($email));
                }
            }
        } catch (Throwable $e) {
            report($e);

            return [
                'sent' => false,
                'retryable' => true,
                'reason' => 'Administrator recipient lookup failed.',
            ];
        }

        try {
            foreach (['school_email', 'email'] as $settingKey) {
                $setting = DB::table('settings')
                    ->where('key', $settingKey)
                    ->value('value');

                if (
                    !empty($setting) &&
                    filter_var($setting, FILTER_VALIDATE_EMAIL)
                ) {
                    $adminRecipients[] = strtolower(trim($setting));
                }
            }
        } catch (Throwable $e) {
            report($e);

            return [
                'sent' => false,
                'retryable' => true,
                'reason' => 'School email configuration lookup failed.',
            ];
        }

        $adminRecipients = array_values(
            array_unique(array_filter($adminRecipients))
        );

        /*
         * Never expose an administrator address as the parent recipient.
         */
        $adminRecipients = array_values(
            array_diff($adminRecipients, $recipients)
        );

        /*
         * A receipt is NOT considered delivered merely because an
         * administrator address exists. A parent/sponsor recipient is
         * required for successful receipt delivery.
         */
        if (empty($recipients)) {
            DB::table('payment_audits')->insert([
                'payment_id' => $payment->id,
                'event' => 'receipt_delivery_failed',
                'details' => json_encode([
                    'reason' => 'No valid parent or sponsor email address was available.',
                    'mpesa_receipt' => $payment->mpesa_receipt,
                    'admin_copies_available' => count($adminRecipients),
                ]),
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            return [
                'sent' => false,
                'retryable' => false,
                'reason' => 'No parent or sponsor receipt recipient was found.',
            ];
        }

        $schoolName = DB::table('settings')
            ->where('key', 'school_name')
            ->value('value');

        $schoolName = $schoolName ?: 'SchoolManager';

        $receiptNumber = $payment->mpesa_receipt
            ?: ('PAY-' . $payment->id);

        $viewData = [
            'schoolName' => $schoolName,
            'student' => $student,
            'parent' => $parent,
            'payment' => $payment,
            'receiptNumber' => $receiptNumber,
        ];

        try {
            $pdf = Pdf::loadView('receipts.payment', $viewData)
                ->setPaper('a4', 'portrait');

            $pdfContent = $pdf->output();

            $subject = $schoolName .
                ' - School Fees Payment Receipt - ' .
                $receiptNumber;

            Mail::raw(
                'Dear Parent/Sponsor,' . PHP_EOL . PHP_EOL .
                'Your school fees payment has been successfully verified.' .
                PHP_EOL . PHP_EOL .
                'Learner: ' . $student->name . PHP_EOL .
                'Amount: KES ' .
                    number_format((float) $payment->amount, 2) . PHP_EOL .
                'M-Pesa Receipt: ' . $receiptNumber . PHP_EOL .
                'Payment Date: ' .
                    ($payment->paid_at ?: now()) . PHP_EOL . PHP_EOL .
                'Please find the official payment receipt attached to this email.' .
                PHP_EOL . PHP_EOL .
                'Regards,' . PHP_EOL .
                $schoolName,
                function (\Illuminate\Mail\Message $message) use (
                    $recipients,
                    $adminRecipients,
                    $pdfContent,
                    $subject,
                    $receiptNumber
                ) {
                    foreach ($recipients as $recipient) {
                        $message->to($recipient);
                    }

                    foreach ($adminRecipients as $admin) {
                        $message->bcc($admin);
                    }

                    $message->subject($subject);

                    $message->attachData(
                        $pdfContent,
                        'payment-receipt-' . $receiptNumber . '.pdf',
                        [
                            'mime' => 'application/pdf',
                        ]
                    );
                }
            );

            DB::table('payment_audits')->insert([
                'payment_id' => $payment->id,
                'event' => 'receipt_sent',
                'details' => json_encode([
                    'recipients' => $recipients,
                    'admin_copies' => $adminRecipients,
                    'receipt_number' => $receiptNumber,
                ]),
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            return [
                'sent' => true,
                'retryable' => false,
                'recipients' => $recipients,
                'admin_copies' => $adminRecipients,
                'receipt_number' => $receiptNumber,
            ];
        } catch (Throwable $e) {
            report($e);

            /*
             * Do not store SMTP/PDF internals or potentially sensitive
             * infrastructure details in the application audit table.
             */
            DB::table('payment_audits')->insert([
                'payment_id' => $payment->id,
                'event' => 'receipt_delivery_failed',
                'details' => json_encode([
                    'reason' => 'Email/PDF generation failed.',
                    'recipients' => $recipients,
                    'admin_copies' => $adminRecipients,
                    'receipt_number' => $receiptNumber,
                ]),
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            return [
                'sent' => false,
                'retryable' => true,
                'reason' => 'Receipt generation or delivery failed.',
            ];
        }
    }
}
