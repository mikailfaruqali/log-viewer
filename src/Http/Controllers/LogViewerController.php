<?php

declare(strict_types=1);

namespace Snawbar\LogViewer\Http\Controllers;

use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Collection;
use Snawbar\LogViewer\DataObjects\LogEntry;
use Snawbar\LogViewer\Http\Requests\DeleteLogFileRequest;
use Snawbar\LogViewer\Services\LogFileService;
use Snawbar\LogViewer\Services\LogParserService;
use Snawbar\LogViewer\View\LogViewer;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Throwable;

class LogViewerController extends Controller
{
    public function __construct(
        protected readonly LogFileService $logFileService,
        protected readonly LogParserService $logParserService,
    ) {}

    public function index(Request $request): View
    {
        $logFiles = $this->logFileService->getLogFiles();
        $currentFile = $this->getCurrentFile($request, $logFiles);
        $searchTerm = $this->stringQuery($request, 'search');
        $level = $this->stringQuery($request, 'level');

        return view('snawbar-log-viewer::index', [
            'logViewer' => new LogViewer(
                files: $logFiles->map(fn (string $filename) => [
                    'name' => $filename,
                    'size' => $this->logFileService->getSize($filename),
                    'modified' => $this->logFileService->getModificationTime($filename),
                ])->all(),
                currentFile: $currentFile,
                entries: $this->getLogEntries($currentFile, $searchTerm),
                search: $searchTerm,
                level: array_key_exists((string) $level, LogEntry::LEVEL_GROUPS) ? $level : NULL,
                page: max(1, (int) $this->stringQuery($request, 'page')),
                perPage: max(1, (int) config('snawbar-log-viewer.per-page', 50)),
            ),
        ]);
    }

    public function download(Request $request): BinaryFileResponse|RedirectResponse
    {
        $filename = (string) $this->stringQuery($request, 'file');

        try {
            return $this->logFileService->downloadLogFile($filename);
        } catch (Throwable $throwable) {
            return to_route('log-viewer.index')
                ->with(LogViewer::FLASH_ERROR, sprintf('Failed to download log file: %s', $throwable->getMessage()));
        }
    }

    public function delete(DeleteLogFileRequest $deleteLogFileRequest): RedirectResponse
    {
        $filename = $deleteLogFileRequest->validated()['file'];

        try {
            $this->logFileService->deleteLogFile($filename);

            return to_route('log-viewer.index')
                ->with(LogViewer::FLASH_SUCCESS, sprintf("Log file '%s' has been deleted.", $filename));
        } catch (Throwable $throwable) {
            return to_route('log-viewer.index', ['file' => $filename])
                ->with(LogViewer::FLASH_ERROR, sprintf('Failed to delete log file: %s', $throwable->getMessage()));
        }
    }

    protected function getCurrentFile(Request $request, Collection $logFiles): ?string
    {
        $requestedFile = $this->stringQuery($request, 'file');

        if ($requestedFile !== NULL && $logFiles->contains($requestedFile)) {
            return $requestedFile;
        }

        return $logFiles->first();
    }

    protected function getLogEntries(?string $filename, ?string $searchTerm = NULL): Collection
    {
        if ($filename === NULL) {
            return collect();
        }

        try {
            return $this->logParserService->parseLogFile($filename, $searchTerm);
        } catch (Throwable $throwable) {
            logger()->warning('Failed to parse log file', [
                'filename' => $filename,
                'error' => $throwable->getMessage(),
            ]);

            return collect();
        }
    }

    /**
     * Reads a query value as a trimmed string; arrays (e.g. "?search[]=x") are ignored.
     */
    protected function stringQuery(Request $request, string $key): ?string
    {
        $value = $request->query($key);

        if (! is_scalar($value)) {
            return NULL;
        }

        $value = trim((string) $value);

        return $value === '' ? NULL : $value;
    }
}
