<?php

namespace App\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Redis;
use Illuminate\Queue\Middleware\RateLimited;

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
    public $tries = 5;

    /**
     * Create a new job instance.
     */
    public function __construct($phone, $message, $botUrl, $botPassword, $isDebug = false)
    {
        $this->phone = $phone;
        $this->message = $message;
        $this->botUrl = $botUrl;
        $this->botPassword = $botPassword;
        $this->isDebug = $isDebug;
    }

    /**
     * Get the middleware the job should pass through.
     *
     * @return array
     */
    public function middleware(): array
    {
        return [new RateLimited('whatsapp-messages')];
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
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

            // Log::info("WhatsApp message sent to {$this->phone}");
        } catch (\Exception $e) {
            Log::error("WhatsApp Job Failed: " . $e->getMessage());
            throw $e;
        }
    }
}
