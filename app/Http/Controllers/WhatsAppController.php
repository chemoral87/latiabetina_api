<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class WhatsAppController extends Controller
{
    protected $botUrl;
    protected $botPassword;

    public function __construct()
    {
        $this->botUrl = config('services.whatsapp.bot_url');
        $this->botPassword = config('services.whatsapp.password');
    }

    public function status()
    {
        try {
            $response = Http::withHeaders([
                'x-api-password' => $this->botPassword
            ])->get("{$this->botUrl}/status");

            return response()->json($response->json(), $response->status());
        } catch (\Exception $e) {
            Log::error("WhatsApp Bot Status Error: " . $e->getMessage());
            return response()->json(['status' => 'OFFLINE', 'error' => 'Could not connect to bot'], 503);
        }
    }

    public function sendMessage(Request $request)
    {
        $request->validate([
            'phone' => 'required|string',
            'message' => 'required|string',
            'mediaUrl' => 'nullable|url',
        ]);

        try {
            \App\Jobs\SendWhatsAppMessageJob::dispatch(
                $request->phone,
                $request->message,
                $request->mediaUrl,
                $this->botUrl,
                $this->botPassword,
                config('services.whatsapp.debug', false)
            );

            return response()->json(['status' => 'queued', 'message' => 'Message has been queued for sending.']);
        } catch (\Exception $e) {
            Log::error("WhatsApp Bot Send Error: " . $e->getMessage());
            return response()->json(['error' => 'Failed to send message: ' . $e->getMessage()], 500);
        }
    }
}
