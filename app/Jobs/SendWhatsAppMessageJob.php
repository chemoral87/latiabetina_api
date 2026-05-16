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
use App\Models\WhatsappMessageLog;

class SendWhatsAppMessageJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected $phone;
    protected $message;
    protected $mediaUrl;
    protected $botUrl;
    protected $botPassword;
    protected $isDebug;

    public function __construct($phone, $message, $mediaUrl, $botUrl, $botPassword, $isDebug = false)
    {
        $this->phone = $phone;
        $this->message = $message;
        $this->mediaUrl = $mediaUrl;
        $this->botUrl = $botUrl;
        $this->botPassword = $botPassword;
        $this->isDebug = $isDebug;
        $this->onQueue('whatsapp');
    }

    private function resolveSender(): string
    {
        try {
            $response = Http::withHeaders([
                'x-api-password' => $this->botPassword
            ])->timeout(5)->get("{$this->botUrl}/me");

            if ($response->successful()) {
                return $response->json('number', config('app.name', 'system'));
            }
        } catch (\Exception $e) {
            // Fall back gracefully if the bot is unreachable
        }

        return config('app.name', 'system');
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
                    $endpoint = $this->mediaUrl ? '/api/send-image' : '/api/send-message';
                    $payload = [
                        'phone'   => $this->phone,
                        'message' => $finalMessage,
                    ];
                    if ($this->mediaUrl) {
                        $payload['mediaUrl'] = $this->mediaUrl;
                    }

                    $response = Http::withHeaders([
                        'x-api-password' => $this->botPassword
                    ])->post("{$this->botUrl}{$endpoint}", $payload);

                    if ($response->failed()) {
                        $errorMessage = $response->json('error') ?? $response->body();

                        // Fallback check for technical initialization errors
                        if (str_contains($errorMessage, 'getChat') || str_contains($errorMessage, 'undefined')) {
                            $errorMessage = "WhatsApp Bot not authenticated. Please scan the QR code.";
                        }

                        throw new \Exception("Bot returned error: " . $errorMessage);
                    }

                    WhatsappMessageLog::create([
                        'queue_name'    => 'whatsapp',
                        'sender'        => $this->resolveSender(),
                        'receiver'      => $this->phone,
                        'body'          => $finalMessage,
                        'media_url'     => $this->mediaUrl,
                        'success'       => true,
                        'error_message' => null,
                    ]);
                } catch (\Exception $e) {
                    $errorMessage = $e->getMessage();

                    // Humanize connection errors (e.g., when the server is stopped)
                    if (str_contains($errorMessage, 'cURL error 7') || str_contains($errorMessage, 'Failed to connect')) {
                        $errorMessage = "WhatsApp Bot server is unreachable. Please ensure the bot server is running.";
                    }

                    Log::error("WhatsApp Job Failed: " . $errorMessage);

                    WhatsappMessageLog::create([
                        'queue_name'    => 'whatsapp',
                        'sender'        => $this->resolveSender(),
                        'receiver'      => $this->phone,
                        'body'          => $finalMessage,
                        'media_url'     => $this->mediaUrl,
                        'success'       => false,
                        'error_message' => $errorMessage,
                    ]);

                    // Do NOT re-throw — job should fail silently after one attempt
                }
            },
            1 // Decay seconds
        );

        if (! $executed) {
            // Could not obtain lock, release the job back to the queue with a random delay
            $this->release(rand(8, 15));
            return;
        }

        // Wait a random amount of time before finishing to ensure a gap between jobs on this worker
        sleep(rand(8, 15));
    }
}
