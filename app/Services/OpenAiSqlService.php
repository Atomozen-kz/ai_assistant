<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use RuntimeException;

class OpenAiSqlService
{
    public function makeSql(string $question): array
    {
        $model = config('services.openai.model');
        $key   = config('services.openai.key');

        $schemaHint = <<<TEXT
Доступные таблицы и поля (используй только их):
- analytics: id, is_plan, plane_month_year, percent_days, orders_sum, new_users_count, opened_app_users_count, units_count, orders_count, created_at, updated_at
- orders: id, user_id, offer_type_id, order_status_id, delivery_type, delivery_price, total_price, created_at, updated_at (и др. поля если встретишь)
- order_statuses: id, name, created_at, updated_at
- actions, client_cards, message_reads, offer_types (если нужно)

Ограничения:
- Только SELECT (или WITH + SELECT)
- Всегда добавляй LIMIT <= 200
- Не используй комментарии, точку с запятой
- Для дат используй created_at / plane_month_year
TEXT;

        $system = "Ты SQL-ассистент для MySQL 8. Возвращай ТОЛЬКО JSON.";
        $user = "Сформируй SQL под вопрос: {$question}\n\n{$schemaHint}\n\nJSON формат:\n{ \"sql\": \"...\", \"result_type\": \"scalar|table\", \"note\": \"коротко\" }";

        $res = Http::withToken($key)
            ->post('https://api.openai.com/v1/responses', [
                'model' => $model,
                'input' => [
                    ['role' => 'system', 'content' => $system],
                    ['role' => 'user', 'content' => $user],
                ],
                'response_format' => ['type' => 'json_object'],
            ])->throw()->json();

        // Responses API возвращает output, вытащим текст JSON
        $text = $res['output'][0]['content'][0]['text'] ?? null;
        if (!$text) throw new RuntimeException('No JSON from model');

        $data = json_decode($text, true);
        if (!is_array($data) || empty($data['sql'])) throw new RuntimeException('Bad JSON');

        return $data;
    }
}
