<?php

namespace App\Http\Controllers;

use App\Support\IdentityDocumentOcr;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class IdentityDocumentOcrController extends Controller
{
    public function __invoke(Request $request, IdentityDocumentOcr $ocr): JsonResponse
    {
        abort_unless($request->user()?->canAny([
            'owners.manage',
            'tenants.manage',
            'agents.manage',
            'operations-team.manage',
        ]), 403);

        $validated = $request->validate([
            'document' => ['required', 'file', 'mimes:pdf,jpg,jpeg,png,webp', 'max:5120'],
        ]);

        return response()->json($ocr->extract($validated['document']));
    }
}
