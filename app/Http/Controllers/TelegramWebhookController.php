<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class TelegramWebhookController extends Controller
{
    public function __invoke(Request $request)
    {
        // Логируем входящее, чтобы убедиться что webhook работает
        Log::info('Telegram update', $request->all());

        // Пока просто отвечать не будем — важно сначала получить апдейты
        return response()->json(['ok' => true]);
    }
}
