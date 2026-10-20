<?php

declare(strict_types=1);

namespace App\Logging;

use App\Models\LogEntry;
use Monolog\Handler\AbstractProcessingHandler;
use Monolog\LogRecord;

final class DatabaseHandler extends AbstractProcessingHandler
{
    protected function write(LogRecord $record): void
    {
        try {
            LogEntry::create([
                'level'   => $record->level->name,
                'channel' => $record->channel,
                'message' => $record->message,
                'context' => $this->serializeContext($record),
            ]);
        } catch (\Throwable $e) {
            // DB write failed — fall back to file so we never lose the log
            @file_put_contents(
                storage_path('logs/database-handler-fallback.log'),
                json_encode(['message' => $record->message, 'error' => $e->getMessage()]) . PHP_EOL,
                FILE_APPEND
            );
        }
    }

    /**
     * Encode Monolog context/extras so longText JSON casts cleanly back to array.
     */
    private function serializeContext(LogRecord $record): ?string
    {
        $context = array_merge(
            $record->context,
            array_key_exists('extra', $record->extra ?? []) ? $record->extra['extra'] : [],
        );

        if (isset($context['exception']) && $context['exception'] instanceof \Throwable) {
            $exception = $context['exception'];
            $context['exception'] = [
                'class' => $exception::class,
                'message' => $exception->getMessage(),
                'code' => $exception->getCode(),
                'file' => $exception->getFile(),
                'line' => $exception->getLine(),
                'trace' => $exception->getTraceAsString(),
            ];
        }

        if (empty($context)) {
            return null;
        }

        $json = json_encode($context, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
        return $json === false ? null : $json;
    }
}
