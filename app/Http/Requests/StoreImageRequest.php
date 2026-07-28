<?php

declare(strict_types=1);

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Auth;

class StoreImageRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     *
     * Authenticated users may always upload. Guests may upload only when the
     * instance permits anonymous uploads and does not require an account.
     */
    public function authorize(): bool
    {
        if (Auth::check()) {
            return true;
        }

        return (bool) config('imgz.features.guest_uploads')
            && ! config('imgz.features.require_account');
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        /** @var string[] $allowedMimes */
        $allowedMimes = config('imgz.uploads.allowed_mimes', []);

        /** @var int[] $retentionOptions */
        $retentionOptions = config('imgz.retention.options_minutes', []);

        return [
            'images' => ['required', 'array', 'max:'.config('imgz.uploads.max_files')],
            'images.*' => [
                'required',
                'file',
                'mimetypes:'.implode(',', $allowedMimes),
                'max:'.config('imgz.uploads.max_size_kb'),
            ],
            'title' => ['nullable', 'string', 'max:255'],
            'expires_in' => ['nullable', 'integer', 'in:'.implode(',', $retentionOptions)],
        ];
    }
}
