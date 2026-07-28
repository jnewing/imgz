<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Enums\ReportReason;
use App\Models\Image;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class ImageReportController extends Controller
{
    /**
     * File an abuse report against an image. Deduplicated per reporter IP.
     */
    public function store(Request $request, Image $image): RedirectResponse
    {
        $validated = $request->validate([
            'reason' => ['required', 'string', Rule::in(ReportReason::values())],
        ]);

        $image->reports()->firstOrCreate(
            ['reporter_ip' => $request->ip()],
            ['reason' => $validated['reason']],
        );

        return back()->with('toast', [
            'type' => 'success',
            'message' => 'Thanks. This image has been reported for review.',
        ]);
    }
}
