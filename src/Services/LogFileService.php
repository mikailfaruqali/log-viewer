<?php

declare(strict_types=1);

namespace Snawbar\LogViewer\Services;

use Illuminate\Support\Collection;
use Illuminate\Support\Facades\File;
use InvalidArgumentException;
use RuntimeException;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class LogFileService
{
    protected readonly string $logPath;

    public function __construct()
    {
        $this->logPath = storage_path('logs');
    }

    public function getLogFiles(): Collection
    {
        return collect(File::glob(sprintf('%s/*.log', $this->logPath)) ?: [])
            ->map(fn (string $file) => basename($file))
            ->filter(fn (string $filename) => $this->isValidLogFile($filename))
            ->sortByDesc(fn (string $filename) => $this->getModificationTime($filename))
            ->values();
    }

    public function logFileExists(string $filename): bool
    {
        return $this->isValidLogFile($filename);
    }

    public function getLogFileContent(string $filename): string
    {
        $this->ensureLogFileExists($filename);

        return File::get($this->getLogFilePath($filename));
    }

    public function getSize(string $filename): int
    {
        return (int) File::size($this->getLogFilePath($filename));
    }

    public function getModificationTime(string $filename): int
    {
        return (int) File::lastModified($this->getLogFilePath($filename));
    }

    public function downloadLogFile(string $filename): BinaryFileResponse
    {
        $this->ensureLogFileExists($filename);

        return response()->download($this->getLogFilePath($filename), $filename);
    }

    public function deleteLogFile(string $filename): bool
    {
        $this->ensureLogFileExists($filename);

        if (! File::delete($this->getLogFilePath($filename))) {
            throw new RuntimeException(sprintf("Failed to delete log file '%s'.", $filename));
        }

        return TRUE;
    }

    protected function ensureLogFileExists(string $filename): void
    {
        throw_if($filename === '', InvalidArgumentException::class, 'Filename cannot be empty.');

        if (! $this->isValidLogFile($filename)) {
            throw new InvalidArgumentException(sprintf("Log file '%s' does not exist.", $filename));
        }
    }

    protected function getLogFilePath(string $filename): string
    {
        return sprintf('%s/%s', $this->logPath, basename($filename));
    }

    /**
     * Only plain *.log files directly inside storage/logs are accepted, so a
     * crafted name can never reach other files (e.g. "../.env" or ".gitignore").
     */
    protected function isValidLogFile(string $filename): bool
    {
        return $filename !== ''
            && basename($filename) === $filename
            && str_ends_with($filename, '.log')
            && File::isFile($this->getLogFilePath($filename))
            && File::isReadable($this->getLogFilePath($filename));
    }
}
