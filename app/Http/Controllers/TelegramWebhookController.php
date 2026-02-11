<?php

namespace App\Http\Controllers;

use App\Models\TelegramUser;
use App\Services\TelegramService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class TelegramWebhookController extends Controller
{
    public function __invoke(Request $request, TelegramService $tg)
    {
        Log::info('Telegram update', $request->all());

        $message = $request->input('message');
        if (!$message) {
            return response()->json(['ok' => true]);
        }

        $from = $message['from'] ?? [];
        $chat = $message['chat'] ?? [];

        $telegramId = (int)($from['id'] ?? 0);
        $chatId     = (int)($chat['id'] ?? 0);

        if ($telegramId && $chatId) {
            TelegramUser::updateOrCreate(
                ['telegram_id' => $telegramId],
                [
                    'chat_id' => $chatId,
                    'username' => $from['username'] ?? null,
                    'first_name' => $from['first_name'] ?? null,
                    'last_name' => $from['last_name'] ?? null,
                ]
            );
        }

        $text = trim((string)($message['text'] ?? ''));

        if ($text === '/start') {
            $tg->sendMessage($chatId, "Привет! Я бот аналитики.\nСпроси: «Сколько заказов сегодня?»");
        } else {
            $tg->sendMessage($chatId, "Принял: {$text}\n(дальше подключим аналитику)");
        }

        return response()->json(['ok' => true]);
    }
}
