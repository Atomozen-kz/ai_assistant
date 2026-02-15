<?php

namespace App\Services;

use RuntimeException;

class SqlGuard
{
    public function validate(string $sql, int $maxLimit = 200): string
    {
        $s = trim($sql);
        $lower = mb_strtolower($s);

        // запрет мульти-запросов
        if (str_contains($s, ';')) throw new RuntimeException('";" запрещён');

        // только SELECT / WITH
        if (!(str_starts_with($lower, 'select') || str_starts_with($lower, 'with'))) {
            throw new RuntimeException('Разрешены только SELECT запросы');
        }

        // запрет комментариев
        if (str_contains($lower, '--') || str_contains($lower, '/*') || str_contains($lower, '*/')) {
            throw new RuntimeException('Комментарии запрещены');
        }

        // запрет опасных слов
        $blocked = ['insert','update','delete','drop','alter','truncate','create','grant','revoke','set','call','outfile','dumpfile','load_file'];
        foreach ($blocked as $w) {
            if (preg_match('/\b'.preg_quote($w,'/').'\b/i', $s)) {
                throw new RuntimeException("Запрещено: {$w}");
            }
        }

        // обязателен LIMIT
        if (!preg_match('/\blimit\s+(\d+)\b/i', $s, $m)) {
            throw new RuntimeException('LIMIT обязателен');
        }
        $limit = (int) $m[1];
        if ($limit < 1 || $limit > $maxLimit) {
            throw new RuntimeException("LIMIT должен быть 1..{$maxLimit}");
        }

        return $s;
    }
}
