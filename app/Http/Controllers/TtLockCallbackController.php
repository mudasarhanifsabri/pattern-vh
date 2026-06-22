<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\File;

class TtLockCallbackController extends Controller
{
    public function __invoke(Request $request)
    {
        File::ensureDirectoryExists(storage_path('logs/ttlock'));
        File::append(
            storage_path('logs/ttlock/callback.log'),
            now()->toDateTimeString().' '.$request->ip().' '.json_encode($request->all()).PHP_EOL,
        );

        return response()->json(['success' => true]);
    }
}
