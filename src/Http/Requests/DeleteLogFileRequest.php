<?php

declare(strict_types=1);

namespace Snawbar\LogViewer\Http\Requests;

use Closure;
use Illuminate\Foundation\Http\FormRequest;
use Snawbar\LogViewer\Services\LogFileService;

class DeleteLogFileRequest extends FormRequest
{
    public function authorize(): bool
    {
        return TRUE;
    }

    public function rules(): array
    {
        return [
            'file' => [
                'required',
                'string',
                'max:255',
                function (string $attribute, mixed $value, Closure $fail): void {
                    if (! is_string($value) || ! resolve(LogFileService::class)->logFileExists($value)) {
                        $fail('The selected log file does not exist.');
                    }
                },
            ],
        ];
    }

    public function messages(): array
    {
        return [
            'file.required' => 'Please select a log file to delete.',
        ];
    }

    public function attributes(): array
    {
        return [
            'file' => 'log file',
        ];
    }

    protected function getRedirectUrl(): string
    {
        return route('log-viewer.index');
    }
}
