<?php

namespace App\Http\Controllers;

use App\Support\UnitDocumentOcr;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class UnitDocumentOcrController extends Controller
{
    public function __invoke(Request $request, UnitDocumentOcr $ocr): JsonResponse
    {
        abort_unless($request->user()?->can('units.manage'), 403);

        $validated = $request->validate([
            'document_type' => ['required', Rule::in(['title_deed', 'dtcm_permit'])],
            'document' => ['required', 'file', 'mimes:pdf,jpg,jpeg,png,webp', 'max:10240'],
        ]);

        return response()->json($ocr->extract($validated['document'], $validated['document_type']));
    }
}
