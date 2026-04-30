<?php

namespace App\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\RateLimiter;

class SendWhatsAppMessageJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected $phone;
    protected $message;
    protected $botUrl;
    protected $botPassword;
    protected $isDebug;

    /**
     * The number of times the job may be attempted.
     *
     * @var int
     */
    public $tries = 100;

    public function __construct($phone, $message, $botUrl, $botPassword, $isDebug = false)
    {
        $this->phone = $phone;
        $this->message = $message;
        $this->botUrl = $botUrl;
        $this->botPassword = $botPassword;
        $this->isDebug = $isDebug;
        $this->onQueue('whatsapp');
    }

    public function handle(): void
    {
        $executed = RateLimiter::attempt(
            'whatsapp-messages',
            1, // Max 1 attempt
            function () {
                $finalMessage = $this->message;
                if ($this->isDebug) {
                    $finalMessage .= "\n\n[Debug: " . now()->toDateTimeString() . "]";
                }

                try {
                    $response = Http::withHeaders([
                        'x-api-password' => $this->botPassword
                    ])->post("{$this->botUrl}/api/send-message", [
                        'phone' => $this->phone,
                        'message' => $finalMessage,
                    ]);

                    if ($response->failed()) {
                        throw new \Exception("Bot returned error: " . $response->body());
                    }
                } catch (\Exception $e) {
                    Log::error("WhatsApp Job Failed: " . $e->getMessage());
                    throw $e;
                }
            },
            0 // Decay seconds
        );

        if (! $executed) {
            // Could not obtain lock, release the job back to the queue with a 1 second delay
            $this->release(1);
            return;
        }
    }
}
