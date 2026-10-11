<?php

declare(strict_types=1);

namespace Snawbar\LogViewer\Services;

use Illuminate\Support\Collection;
use Snawbar\LogViewer\DataObjects\LogEntry;

class LogParserService
{
    /**
     * Laravel / Monolog line format: "[%datetime%] %channel%.%level_name%: %message% %context% %extra%".
     */
    protected const HEADER_REGEX = '/^\[(\d{4}-\d{2}-\d{2}[T ]\d{2}:\d{2}:\d{2}(?:\.\d+)?(?:Z|[+-]\d{2}:?\d{2})?)\]\s+(?:(\S+)\.)?([A-Za-z]+):\s?(.*)$/';

    protected const DEFAULT_ENVIRONMENT = 'production';

    /**
     * Scalar context values longer than this are shown as a section instead of a badge.
     */
    protected const BADGE_MAX_LENGTH = 100;

    public function __construct(
        protected readonly LogFileService $logFileService,
    ) {}

    public function parseLogFile(string $filename, ?string $searchTerm = NULL): Collection
    {
        return $this->parseLogContent($this->logFileService->getLogFileContent($filename), $searchTerm);
    }

    public function parseLogContent(string $content, ?string $searchTerm = NULL): Collection
    {
        if (trim($content) === '') {
            return collect();
        }

        $entries = [];
        $header = NULL;
        $lines = [];

        foreach (preg_split('/\r\n|\n|\r/', $content) ?: [] as $line) {
            $jsonRecord = $this->parseJsonLogEntry($line);

            if ($jsonRecord !== NULL) {
                $this->flush($entries, $header, $lines);
                $entries[] = $this->createLogEntry($jsonRecord);

                continue;
            }

            if (preg_match(self::HEADER_REGEX, $line, $matches) === 1) {
                $this->flush($entries, $header, $lines);
                $header = $matches;

                continue;
            }

            if ($header !== NULL) {
                $lines[] = $line;
            }
        }

        $this->flush($entries, $header, $lines);

        return collect($entries)
            ->filter(fn (LogEntry $logEntry) => $logEntry->matches($searchTerm))
            ->reverse()
            ->values();
    }

    protected function flush(array &$entries, ?array &$header, array &$lines): void
    {
        if ($header !== NULL) {
            $entries[] = $this->createLogEntry($this->parseLineEntry($header, $lines));
        }

        $header = NULL;
        $lines = [];
    }

    /**
     * Builds an entry from a header match and its continuation lines.
     */
    protected function parseLineEntry(array $header, array $lines): array
    {
        $body = rtrim(implode("\n", array_merge([$header[4] ?? ''], $lines)));

        [$message, $payloads] = $this->splitMessageAndPayloads($body);

        $stackTrace = '';
        $extra = '';

        if ($payloads === NULL) {
            $payloads = [];
            $firstLine = $header[4] ?? '';
            $cut = strpos($firstLine, ' {"');

            if ($cut !== FALSE || $this->looksLikeStackTrace($lines)) {
                // The JSON could not be recovered (e.g. truncated line): keep the
                // first line as the message and everything after it as raw text.
                $message = $cut === FALSE ? $firstLine : substr($firstLine, 0, $cut);
                $stackTrace = trim(($cut === FALSE ? '' : substr($firstLine, $cut + 1) . "\n") . implode("\n", $lines));
            }
        }

        // Monolog writes the log context first and the extra (shared context
        // from Context::add / processors) second.
        $context = is_array($payloads[0] ?? NULL) ? $payloads[0] : [];
        $shared = is_array($payloads[1] ?? NULL) ? $payloads[1] : [];

        if (array_key_exists('exception', $context)) {
            $stackTrace = $this->formatException($context['exception']) ?: $stackTrace;
            unset($context['exception']);
        }

        [$badges, $longContext] = $this->splitContext($context);
        [$sharedBadges, $sharedLongContext] = $this->splitContext($shared, 'Shared');

        return [
            'timestamp' => $this->normalizeTimestamp($header[1] ?? ''),
            'environment' => ($header[2] ?? '') !== '' ? $header[2] : self::DEFAULT_ENVIRONMENT,
            'level' => strtolower($header[3] ?? 'info'),
            'message' => trim($message),
            'extra' => $extra,
            'context' => $badges + $sharedBadges,
            'longContext' => $longContext + $sharedLongContext,
            'stackTrace' => $stackTrace,
        ];
    }

    protected function looksLikeStackTrace(array $lines): bool
    {
        return preg_match('/^\s*(#\d+ |\[stacktrace\]|Stack trace:)/m', implode("\n", $lines)) === 1;
    }

    /**
     * Finds the trailing "%context% %extra%" JSON blobs at the end of the entry
     * body. Returns [message, [context, extra?]] or [body, null] when no valid
     * trailing JSON exists.
     *
     * @return array{0: string, 1: array<int, array<mixed>>|null}
     */
    protected function splitMessageAndPayloads(string $body): array
    {
        $offset = 0;

        while (($position = $this->nextPayloadCandidate($body, $offset)) !== NULL) {
            $payloads = $this->readTrailingPayloads($body, $position);

            if ($payloads !== NULL) {
                return [substr($body, 0, $position), $payloads];
            }

            $offset = $position + 1;
        }

        return [$body, NULL];
    }

    protected function nextPayloadCandidate(string $body, int $offset): ?int
    {
        $candidates = array_filter([
            strpos($body, ' {', $offset),
            strpos($body, ' [', $offset),
        ], fn ($position) => $position !== FALSE);

        return $candidates === [] ? NULL : min($candidates);
    }

    /**
     * @return array<int, array<mixed>>|null
     */
    protected function readTrailingPayloads(string $body, int $position): ?array
    {
        $payloads = [];
        $cursor = $position;
        $length = strlen($body);

        while ($cursor < $length && count($payloads) < 2) {
            while ($cursor < $length && ctype_space($body[$cursor])) {
                $cursor++;
            }

            if ($cursor >= $length) {
                break;
            }

            $end = $this->findJsonEnd($body, $cursor);

            if ($end === NULL) {
                return NULL;
            }

            $decoded = $this->decodeJson(substr($body, $cursor, $end - $cursor));

            if ($decoded === NULL) {
                return NULL;
            }

            $payloads[] = $decoded;
            $cursor = $end;
        }

        return $payloads !== [] && trim(substr($body, $cursor)) === '' ? $payloads : NULL;
    }

    /**
     * Returns the offset just after the balanced JSON object/array starting at $start.
     */
    protected function findJsonEnd(string $text, int $start): ?int
    {
        $open = $text[$start] ?? '';

        if ($open !== '{' && $open !== '[') {
            return NULL;
        }

        $depth = 0;
        $inString = FALSE;
        $length = strlen($text);

        for ($i = $start; $i < $length; $i++) {
            $char = $text[$i];

            if ($inString) {
                if ($char === '\\') {
                    $i++;
                } elseif ($char === '"') {
                    $inString = FALSE;
                }

                continue;
            }

            if ($char === '"') {
                $inString = TRUE;
            } elseif ($char === '{' || $char === '[') {
                $depth++;
            } elseif (($char === '}' || $char === ']') && --$depth === 0) {
                return $i + 1;
            }
        }

        return NULL;
    }

    /**
     * Monolog prints "\n" escapes as real line breaks (allowInlineLineBreaks), so
     * they are turned back into escapes before decoding.
     */
    protected function decodeJson(string $json): ?array
    {
        $decoded = json_decode(str_replace(["\r\n", "\r", "\n"], ['\n', '\r', '\n'], $json), TRUE);

        return is_array($decoded) ? $decoded : NULL;
    }

    /**
     * @return array{0: array<string,string>, 1: array<string,mixed>}
     */
    protected function splitContext(array $context, string $prefix = ''): array
    {
        $badges = [];
        $longContext = [];

        foreach ($context as $key => $value) {
            $key = (string) $key;

            if (is_array($value)) {
                if ($value !== []) {
                    $longContext[$this->sectionLabel($key, $prefix)] = $value;
                }

                continue;
            }

            $string = $this->stringify($value);

            if (mb_strlen($string) > self::BADGE_MAX_LENGTH || str_contains($string, "\n")) {
                $longContext[$this->sectionLabel($key, $prefix)] = $string;
            } else {
                $badges[$key] = $string;
            }
        }

        return [$badges, $longContext];
    }

    protected function sectionLabel(string $key, string $prefix): string
    {
        $label = strtolower($key) === 'input' ? 'Request Input' : $key;

        return $prefix === '' ? $label : sprintf('%s · %s', $prefix, $label);
    }

    protected function stringify(mixed $value): string
    {
        return match (TRUE) {
            $value === NULL => 'null',
            is_bool($value) => $value ? 'true' : 'false',
            default => (string) $value,
        };
    }

    /**
     * Turns Laravel's normalized exception string ("[object] (Class(code: 0): msg
     * at file:line)\n[stacktrace]\n#0 ...") or a JSON formatter exception array
     * into readable stack trace text.
     */
    protected function formatException(mixed $exception): string
    {
        if (is_array($exception)) {
            return $this->formatJsonException($exception);
        }

        if (! is_string($exception) || trim($exception) === '') {
            return '';
        }

        $text = preg_replace_callback(
            '/(\[previous exception\] )?\[object\] \((.+?) at (\S+?:\d+)\)(?=\n|$)/s',
            fn (array $match) => ($match[1] !== '' ? "\nCaused by: " : '') . $match[2] . "\nat " . $match[3],
            $exception,
        ) ?? $exception;

        $text = str_replace(["[stacktrace]\n", '[stacktrace]'], '', $text);

        return trim(preg_replace("/\n{3,}/", "\n\n", $text) ?? $text);
    }

    protected function formatJsonException(array $exception): string
    {
        $lines = [];

        if (isset($exception['class'])) {
            $lines[] = $exception['class'] . ': ' . ($exception['message'] ?? '');
        }

        if (isset($exception['file'])) {
            $lines[] = 'at ' . $exception['file'];
        }

        foreach ((array) ($exception['trace'] ?? []) as $index => $frame) {
            $lines[] = is_string($frame)
                ? sprintf('#%d %s', $index, $frame)
                : sprintf('#%d %s: %s%s%s()', $index, ($frame['file'] ?? '[internal]') . (isset($frame['line']) ? ':' . $frame['line'] : ''), $frame['class'] ?? '', $frame['type'] ?? '', $frame['function'] ?? '');
        }

        if (isset($exception['previous']) && is_array($exception['previous'])) {
            $lines[] = "\nCaused by: " . $this->formatJsonException($exception['previous']);
        }

        return implode("\n", $lines);
    }

    protected function parseJsonLogEntry(string $line): ?array
    {
        $trimmed = trim($line);

        if ($trimmed === '' || $trimmed[0] !== '{') {
            return NULL;
        }

        $decoded = json_decode($trimmed, TRUE);

        if (! is_array($decoded) || ! isset($decoded['message'], $decoded['level_name'])) {
            return NULL;
        }

        $context = is_array($decoded['context'] ?? NULL) ? $decoded['context'] : [];
        $shared = is_array($decoded['extra'] ?? NULL) ? $decoded['extra'] : [];
        $stackTrace = array_key_exists('exception', $context) ? $this->formatException($context['exception']) : '';
        unset($context['exception']);

        [$badges, $longContext] = $this->splitContext($context);
        [$sharedBadges, $sharedLongContext] = $this->splitContext($shared, 'Shared');

        return [
            'timestamp' => $this->normalizeTimestamp((string) ($decoded['datetime'] ?? '')),
            'environment' => (string) ($decoded['channel'] ?? self::DEFAULT_ENVIRONMENT),
            'level' => strtolower((string) $decoded['level_name']),
            'message' => (string) $decoded['message'],
            'extra' => '',
            'context' => $badges + $sharedBadges,
            'longContext' => $longContext + $sharedLongContext,
            'stackTrace' => $stackTrace,
        ];
    }

    protected function normalizeTimestamp(string $datetime): string
    {
        if ($datetime === '') {
            return '';
        }

        $time = strtotime($datetime);

        return $time === FALSE ? $datetime : date('Y-m-d H:i:s', $time);
    }

    protected function createLogEntry(array $data): LogEntry
    {
        return new LogEntry(
            timestamp: $data['timestamp'],
            environment: $data['environment'],
            level: $data['level'],
            message: $data['message'],
            extra: $data['extra'] ?? '',
            context: $data['context'] ?? [],
            longContext: $data['longContext'] ?? [],
            stackTrace: $data['stackTrace'] ?? '',
        );
    }
}
