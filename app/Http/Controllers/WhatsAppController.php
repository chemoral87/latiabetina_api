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
        $this->botUrl = env('WHATSAPP_BOT_URL', 'http://localhost:3007');
        $this->botPassword = env('WHATSAPP_BOT_PASSWORD');
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
        ]);

        try {
            \App\Jobs\SendWhatsAppMessageJob::dispatch(
                $request->phone,
                $request->message,
                $this->botUrl,
                $this->botPassword,
                $request->boolean('isDebug') || env('WHATSAPP_DEBUG', false)
            );

            return response()->json(['status' => 'queued', 'message' => 'Message has been queued for sending.']);
        } catch (\Exception $e) {
            Log::error("WhatsApp Bot Send Error: " . $e->getMessage());
            return response()->json(['error' => 'Failed to send message: ' . $e->getMessage()], 500);
        }
    }
}
