<?php

declare(strict_types=1);

namespace Snawbar\LogViewer\View;

class Highlighter
{
    public static function stackTrace(string $trace): string
    {
        $html = [];

        foreach (explode("\n", $trace) as $index => $line) {
            $escaped = e($line);
            $trimmed = ltrim($line);

            $class = match (TRUE) {
                str_starts_with($trimmed, 'Caused by:') => 'trace-caused',
                $index === 0 || preg_match('/^[\w\\\\]+(?:\(code: [^)]*\))?: /', $trimmed) === 1 => 'trace-exception',
                str_starts_with($trimmed, 'at ') => 'trace-at',
                preg_match('/^#\d+ /', $trimmed) === 1 => preg_match('#[\\\\/]vendor[\\\\/]#', $line) === 1 ? 'trace-frame trace-vendor' : 'trace-frame',
                default => NULL,
            };

            $html[] = $class === NULL ? $escaped : sprintf('<span class="%s">%s</span>', $class, $escaped);
        }

        return implode("\n", $html);
    }

    public static function json(mixed $value, int $depth = 0): string
    {
        $indent = str_repeat('  ', $depth);
        $childIndent = str_repeat('  ', $depth + 1);

        if (is_array($value)) {
            $isList = array_is_list($value);

            if ($value === []) {
                return self::punct($isList ? '[]' : '{}');
            }

            $items = [];

            foreach ($value as $key => $item) {
                $keyHtml = $isList ? '' : '<span class="json-key">' . e(self::encode((string) $key)) . '</span>' . self::punct(': ');
                $items[] = $childIndent . $keyHtml . self::json($item, $depth + 1);
            }

            return self::punct($isList ? '[' : '{') . "\n"
                . implode(self::punct(',') . "\n", $items) . "\n"
                . $indent . self::punct($isList ? ']' : '}');
        }

        if (is_string($value)) {
            $trimmed = trim($value);

            if ($trimmed !== '' && ($trimmed[0] === '{' || $trimmed[0] === '[')) {
                $inner = json_decode($trimmed, TRUE);

                if (is_array($inner)) {
                    return self::json($inner, $depth);
                }
            }

            return '<span class="json-str">' . e(self::encode($value)) . '</span>';
        }

        return match (TRUE) {
            is_int($value), is_float($value) => '<span class="json-num">' . e((string) $value) . '</span>',
            is_bool($value) => '<span class="json-bool">' . ($value ? 'true' : 'false') . '</span>',
            $value === NULL => '<span class="json-null">null</span>',
            default => '<span class="json-str">' . e(self::encode((string) json_encode($value))) . '</span>',
        };
    }

    protected static function encode(string $value): string
    {
        return (string) json_encode($value, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_INVALID_UTF8_SUBSTITUTE);
    }

    protected static function punct(string $value): string
    {
        return '<span class="json-punct">' . e($value) . '</span>';
    }
}
