<?php

namespace App\Http\Controllers;

use App\Models\TelegramUser;
use App\Services\TelegramService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use App\Services\OpenAiSqlService;
use App\Services\SqlGuard;
use Illuminate\Support\Facades\DB;

class TelegramWebhookController extends Controller
{
    public function __invoke(Request $request, TelegramService $tg, OpenAiSqlService $sqlAi, SqlGuard $guard)
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

            $data = $sqlAi->makeSql($text);
            $sql  = $guard->validate($data['sql'], 200);

            $rows = DB::connection('report')->select($sql);

            // форматируем коротко под Telegram
            if (($data['result_type'] ?? '') === 'scalar' && isset($rows[0])) {
                $val = array_values((array)$rows[0])[0] ?? null;
                $tg->sendMessage($chatId, "Ответ: {$val}");
            } else {
                $preview = json_encode($rows, JSON_UNESCAPED_UNICODE);
                $tg->sendMessage($chatId, mb_substr($preview, 0, 3500));
            }


//            $tg->sendMessage($chatId, "Принял: {$text}\n(дальше подключим аналитику)");
        }

        return response()->json(['ok' => true]);
    }
}
