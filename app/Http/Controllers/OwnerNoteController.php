<?php

namespace App\Http\Controllers;

use App\Models\Owner;
use App\Support\ActivityLogger;
use Illuminate\Http\Request;

class OwnerNoteController extends Controller
{
    public function store(Request $request, Owner $owner)
    {
        $validated = $request->validate([
            'note' => ['required', 'string', 'max:4000'],
        ]);

        $owner->notes()->create([
            'user_id' => auth()->id(),
            'note' => $validated['note'],
        ]);

        ActivityLogger::log('owners.notes.created', "Added note for owner {$owner->full_name}.", $owner);

        return redirect()->route('owners.show', $owner)->with('status', 'Note added successfully.');
    }
}
