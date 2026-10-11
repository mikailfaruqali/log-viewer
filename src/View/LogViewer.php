<?php

declare(strict_types=1);

namespace Snawbar\LogViewer\View;

use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Date;
use Illuminate\Support\ViewErrorBag;
use Snawbar\LogViewer\DataObjects\LogEntry;

class LogViewer
{
    public const FLASH_SUCCESS = 'snawbar-log-viewer.success';

    public const FLASH_ERROR = 'snawbar-log-viewer.error';

    protected const LEVEL_LABELS = [
        'error' => 'Errors',
        'warning' => 'Warnings',
        'info' => 'Info',
        'debug' => 'Debug',
    ];

    protected readonly Collection $filteredEntries;

    /**
     * @param  array<int, array{name: string, size: int, modified: int}>  $files
     * @param  Collection<int, LogEntry>  $entries  Entries already filtered by the search term
     */
    public function __construct(
        protected readonly array $files,
        protected readonly ?string $currentFile,
        protected readonly Collection $entries,
        protected readonly ?string $search,
        protected readonly ?string $level,
        protected readonly int $page,
        protected readonly int $perPage,
    ) {
        $this->filteredEntries = $this->level === NULL
            ? $this->entries
            : $this->entries->filter(fn (LogEntry $logEntry) => $logEntry->levelGroup() === $this->level)->values();
    }

    public function files(): array
    {
        return array_map(fn (array $file) => [
            'name' => $file['name'],
            'url' => $this->url(['file' => $file['name'], 'search' => NULL, 'level' => NULL, 'page' => NULL]),
            'active' => $file['name'] === $this->currentFile,
            'size' => $this->formatBytes($file['size']),
            'modified' => $this->timeAgo($file['modified'], short: TRUE),
        ], $this->files);
    }

    public function hasFiles(): bool
    {
        return $this->files !== [];
    }

    public function fileCount(): int
    {
        return count($this->files);
    }

    public function currentFile(): ?string
    {
        return $this->currentFile;
    }

    public function hasCurrentFile(): bool
    {
        return $this->currentFile !== NULL;
    }

    public function currentFileMeta(): string
    {
        foreach ($this->files as $file) {
            if ($file['name'] === $this->currentFile) {
                return sprintf(
                    '%s · updated %s',
                    $this->formatBytes($file['size']),
                    $this->timeAgo($file['modified']),
                );
            }
        }

        return '';
    }

    public function search(): ?string
    {
        return $this->search;
    }

    public function level(): ?string
    {
        return $this->level;
    }

    public function isFiltered(): bool
    {
        return $this->search !== NULL || $this->level !== NULL;
    }

    public function stats(): array
    {
        $counts = $this->entries->countBy(fn (LogEntry $logEntry) => $logEntry->levelGroup());

        return collect(self::LEVEL_LABELS)
            ->map(fn (string $label, string $group) => [
                'tone' => $group,
                'label' => $label,
                'value' => (int) ($counts[$group] ?? 0),
                'active' => $this->level === $group,
                'url' => $this->url(['level' => $this->level === $group ? NULL : $group, 'page' => NULL]),
            ])
            ->values()
            ->all();
    }

    public function totalCount(): int
    {
        return $this->entries->count();
    }

    public function filteredCount(): int
    {
        return $this->filteredEntries->count();
    }

    /**
     * @return Collection<int, LogEntry> keyed by a stable index across pages
     */
    public function entries(): Collection
    {
        return $this->filteredEntries->slice(($this->currentPage() - 1) * $this->perPage, $this->perPage);
    }

    public function currentPage(): int
    {
        return min(max(1, $this->page), $this->lastPage());
    }

    public function lastPage(): int
    {
        return max(1, (int) ceil($this->filteredCount() / max(1, $this->perPage)));
    }

    public function hasPages(): bool
    {
        return $this->lastPage() > 1;
    }

    public function pageSummary(): string
    {
        $from = ($this->currentPage() - 1) * $this->perPage + 1;
        $to = min($this->filteredCount(), $this->currentPage() * $this->perPage);

        return sprintf('%s–%s of %s', number_format($from), number_format($to), number_format($this->filteredCount()));
    }

    public function previousPageUrl(): ?string
    {
        return $this->currentPage() > 1 ? $this->url(['page' => $this->currentPage() - 1]) : NULL;
    }

    public function nextPageUrl(): ?string
    {
        return $this->currentPage() < $this->lastPage() ? $this->url(['page' => $this->currentPage() + 1]) : NULL;
    }

    public function listTitle(): string
    {
        return $this->level === NULL ? 'Entries' : self::LEVEL_LABELS[$this->level];
    }

    public function resultText(): string
    {
        $count = $this->filteredCount();
        $text = sprintf('%s %s', number_format($count), $count === 1 ? 'entry' : 'entries');

        if ($this->search !== NULL) {
            $text .= sprintf(' matching “%s”', $this->search);
        }

        return $text;
    }

    public function emptyState(): array
    {
        return match (TRUE) {
            ! $this->hasFiles() => ['title' => 'No log files yet', 'text' => 'Log files written to storage/logs will show up here.'],
            ! $this->hasCurrentFile() => ['title' => 'Select a log file', 'text' => 'Pick a file from the sidebar to start viewing entries.'],
            $this->isFiltered() => ['title' => 'No matching entries', 'text' => 'Try different keywords or clear the filters.'],
            default => ['title' => 'This log file is empty', 'text' => 'There are no entries to display.'],
        };
    }

    public function flash(): ?array
    {
        $session = session();

        if ($session->has(self::FLASH_SUCCESS)) {
            return ['tone' => 'success', 'message' => (string) $session->get(self::FLASH_SUCCESS)];
        }

        if ($session->has(self::FLASH_ERROR)) {
            return ['tone' => 'error', 'message' => (string) $session->get(self::FLASH_ERROR)];
        }

        $errors = $session->get('errors');

        if ($errors instanceof ViewErrorBag && $errors->any()) {
            return ['tone' => 'error', 'message' => (string) $errors->first()];
        }

        return NULL;
    }

    public function indexUrl(): string
    {
        return route('log-viewer.index');
    }

    public function url(array $overrides = []): string
    {
        $query = array_merge([
            'file' => $this->currentFile,
            'search' => $this->search,
            'level' => $this->level,
            'page' => $this->currentPage() > 1 ? $this->currentPage() : NULL,
        ], $overrides);

        return route('log-viewer.index', array_filter($query, fn ($value) => $value !== NULL && $value !== ''));
    }

    public function clearSearchUrl(): string
    {
        return $this->url(['search' => NULL, 'page' => NULL]);
    }

    public function clearFiltersUrl(): string
    {
        return $this->url(['search' => NULL, 'level' => NULL, 'page' => NULL]);
    }

    public function downloadUrl(): string
    {
        return route('log-viewer.download', ['file' => $this->currentFile]);
    }

    public function deleteUrl(): string
    {
        return route('log-viewer.delete');
    }

    public function stackTrace(string $trace): string
    {
        return Highlighter::stackTrace($trace);
    }

    public function json(mixed $value): string
    {
        return Highlighter::json($value);
    }

    /**
     * Always English, whatever locale the host app uses.
     */
    protected function timeAgo(int $timestamp, bool $short = FALSE): string
    {
        return Date::createFromTimestamp($timestamp)
            ->locale('en')
            ->diffForHumans(['short' => $short]);
    }

    protected function formatBytes(int $bytes): string
    {
        $units = ['B', 'KB', 'MB', 'GB'];
        $power = $bytes > 0 ? min((int) floor(log($bytes, 1024)), count($units) - 1) : 0;

        return sprintf($power === 0 ? '%d %s' : '%.1f %s', $bytes / (1024 ** $power), $units[$power]);
    }
}
