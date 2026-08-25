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
    protected $originalLogId;

    public function __construct($phone, $message, $mediaUrl, $botUrl, $botPassword, $isDebug = false, $originalLogId = null)
    {
        $this->phone = $phone;
        $this->message = $message;
        $this->mediaUrl = $mediaUrl;
        $this->botUrl = $botUrl;
        $this->botPassword = $botPassword;
        $this->isDebug = $isDebug;
        $this->originalLogId = $originalLogId;
        $this->onQueue('whatsapp');
    }

    private function normalizePhone(string $phone): string
    {
        $digits = preg_replace('/\D/', '', $phone);
        // Si son 10 dígitos (ej. 8120441172) -> anteponer 52 para MX: 528120441172
        if (strlen($digits) === 10) {
            $digits = '52' . $digits;
        }
        // 521... (formato antiguo móvil MX) -> 52...
        if (strlen($digits) === 13 && str_starts_with($digits, '521')) {
            $digits = '52' . substr($digits, 3);
        }
        return $digits;
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

                // Non-prod safeguard: redirect all sends to test number (default 8120221172)
                // (per request: if environment is not prod, sent to WHATSAPP_TEST_PHONE instead)
                $effectivePhone = $this->phone;
                $originalPhone  = $this->phone;
                if (!app()->environment('production')) {
                    $effectivePhone = config('services.whatsapp.test_phone', '8120221172');
                    if ($originalPhone !== $effectivePhone) {
                        Log::info("WhatsApp Job [non-prod] redirecting {$originalPhone} → {$effectivePhone}", [
                            'env' => app()->environment(),
                        ]);
                        // Annotate body so /whatsapp logs show original intent
                        $finalMessage .= "\n\n[Redirigido en " . app()->environment() . " de {$originalPhone} a {$effectivePhone}]";
                    }
                }

                // Normalizar a 12 dígitos MX con 52 al inicio si vienen 10 dígitos (ej. 8120441172 → 528120441172)
                $effectivePhone = $this->normalizePhone($effectivePhone);
                $originalPhone  = $this->normalizePhone($originalPhone);

                try {
                    $endpoint = $this->mediaUrl ? '/api/send-image' : '/api/send-message';
                    $payload = [
                        'phone'   => $effectivePhone,
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
                        $code = $response->json('code');

                        // getChat/undefined after send is often post-send ack — message WAS delivered
                        if (str_contains($errorMessage, 'getChat') || str_contains($errorMessage, 'undefined')) {
                            Log::warning("WhatsApp Job: getChat warning but message likely delivered to {$effectivePhone} — " . $errorMessage);
                            // Don't throw — treat as success with warning (user receives it)
                            WhatsappMessageLog::create([
                                'queue_name'      => 'whatsapp',
                                'sender'          => $this->resolveSender(),
                                'receiver'        => $effectivePhone,
                                'body'            => $finalMessage,
                                'media_url'       => $this->mediaUrl,
                                'success'         => true,
                                'error_message'   => "Advertencia post-envío: " . $errorMessage,
                                'original_log_id' => $this->originalLogId,
                            ]);
                            return;
                        }

                        // Humanize other bot errors
                        if (str_contains($errorMessage, 'No LID') || str_contains($errorMessage, 'LID for user') || $code === 'INVALID_LID' || $code === 'NOT_REGISTERED' || str_contains($errorMessage, 'Evaluation failed')) {
                            $errorMessage = "El número {$effectivePhone} no está registrado en WhatsApp o es inválido.";
                        } elseif ($response->status() === 503) {
                            $errorMessage = "WhatsApp Bot no está listo ({$errorMessage}).";
                        }

                        throw new \Exception("Bot returned error: " . $errorMessage);
                    }

                    WhatsappMessageLog::create([
                        'queue_name'      => 'whatsapp',
                        'sender'          => $this->resolveSender(),
                        'receiver'        => $effectivePhone,
                        'body'            => $finalMessage,
                        'media_url'       => $this->mediaUrl,
                        'success'         => true,
                        'error_message'   => null,
                        'original_log_id' => $this->originalLogId,
                    ]);
                } catch (\Exception $e) {
                    $errorMessage = $e->getMessage();

                    // Humanize connection errors (e.g., when the server is stopped)
                    if (str_contains($errorMessage, 'cURL error 7') || str_contains($errorMessage, 'Failed to connect')) {
                        $errorMessage = "WhatsApp Bot server is unreachable. Please ensure the bot server is running.";
                    }

                    // getChat post-send warning — message was likely delivered (user confirms receipt)
                    if (str_contains($errorMessage, 'getChat') || str_contains($errorMessage, 'undefined')) {
                        Log::warning("WhatsApp Job: getChat warning but likely delivered to {$effectivePhone} (orig {$originalPhone}) — " . $errorMessage);
                        WhatsappMessageLog::create([
                            'queue_name'      => 'whatsapp',
                            'sender'          => $this->resolveSender(),
                            'receiver'        => $effectivePhone,
                            'body'            => $finalMessage,
                            'media_url'       => $this->mediaUrl,
                            'success'         => true,
                            'error_message'   => "Advertencia post-envío: " . $errorMessage,
                            'original_log_id' => $this->originalLogId,
                        ]);
                        return;
                    }

                    // No LID / not registered is an expected user error, not a system error — log as warning
                    if (str_contains($errorMessage, 'no está registrado en WhatsApp') || str_contains($errorMessage, 'No LID') || str_contains($errorMessage, 'INVALID_LID')) {
                        Log::warning("WhatsApp Job: invalid number {$effectivePhone} (orig {$originalPhone}) — " . $errorMessage);
                    } else {
                        Log::error("WhatsApp Job Failed: " . $errorMessage);
                    }

                    WhatsappMessageLog::create([
                        'queue_name'      => 'whatsapp',
                        'sender'          => $this->resolveSender(),
                        'receiver'        => $effectivePhone,
                        'body'            => $finalMessage,
                        'media_url'       => $this->mediaUrl,
                        'success'         => false,
                        'error_message'   => $errorMessage,
                        'original_log_id' => $this->originalLogId,
                    ]);

                    // Do NOT re-throw — job should fail silently after one attempt
                }
            },
            1 // Decay seconds
        );

        if (! $executed) {
            // Could not obtain lock, release the job back to the queue with a random delay
            $this->release(rand(10, 20));
            return;
        }

        // Wait a random amount of time before finishing to ensure a gap between jobs on this worker
        sleep(rand(10, 20));
    }
}
