<?php

declare(strict_types=1);

namespace Snawbar\LogViewer\DataObjects;

readonly class LogEntry
{
    public const LEVEL_GROUPS = [
        'error' => ['emergency', 'alert', 'critical', 'error'],
        'warning' => ['warning'],
        'info' => ['notice', 'info'],
        'debug' => ['debug'],
    ];

    /**
     * @param  array<string,string>  $context  Short scalar values (context + shared context) shown as badges
     * @param  array<string,mixed>  $longContext  Long/complex values shown as sections (e.g. request input, nested arrays)
     * @param  string  $stackTrace  Exception and stack trace text
     */
    public function __construct(
        public string $timestamp,
        public string $environment,
        public string $level,
        public string $message,
        public string $extra = '',
        public array $context = [],
        public array $longContext = [],
        public string $stackTrace = '',
    ) {}

    public function hasStackTrace(): bool
    {
        return trim($this->stackTrace) !== '';
    }

    public function hasExtra(): bool
    {
        return trim($this->extra) !== '';
    }

    public function hasContext(): bool
    {
        return $this->context !== [];
    }

    public function hasLongContext(): bool
    {
        return $this->longContext !== [];
    }

    public function getContext(): array
    {
        return $this->context;
    }

    public function getLongContext(): array
    {
        return $this->longContext;
    }

    public function levelGroup(): string
    {
        foreach (self::LEVEL_GROUPS as $group => $levels) {
            if (in_array($this->level, $levels, TRUE)) {
                return $group;
            }
        }

        return 'debug';
    }

    public function isError(): bool
    {
        return $this->levelGroup() === 'error';
    }

    public function getFormattedTimestamp(): string
    {
        $time = strtotime($this->timestamp);

        return $time === FALSE ? $this->timestamp : date('M j, Y H:i:s', $time);
    }

    public function matches(?string $searchTerm): bool
    {
        $term = mb_strtolower(trim((string) $searchTerm));

        if ($term === '') {
            return TRUE;
        }

        $haystack = implode("\n", [
            $this->timestamp,
            $this->environment,
            $this->level,
            $this->message,
            $this->extra,
            $this->stackTrace,
            json_encode($this->context, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_INVALID_UTF8_SUBSTITUTE) ?: '',
            json_encode($this->longContext, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_INVALID_UTF8_SUBSTITUTE) ?: '',
        ]);

        return str_contains(mb_strtolower($haystack), $term);
    }

    public function highlightSearchTerm(string $text, ?string $searchTerm): string
    {
        $term = trim((string) $searchTerm);

        if ($term === '') {
            return e($text);
        }

        // Split on the raw text first and escape each piece afterwards, so a
        // term like "amp" can never land inside an escaped "&amp;" entity.
        $parts = preg_split('/(' . preg_quote($term, '/') . ')/iu', $text, -1, PREG_SPLIT_DELIM_CAPTURE);

        if ($parts === FALSE) {
            return e($text);
        }

        $html = '';

        foreach ($parts as $index => $part) {
            $html .= $index % 2 === 1 ? '<mark>' . e($part) . '</mark>' : e($part);
        }

        return $html;
    }
}
