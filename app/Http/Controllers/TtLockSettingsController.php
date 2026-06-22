<?php

namespace App\Http\Controllers;

use App\Models\TtLock;
use App\Models\TtLockEvent;
use App\Models\TtLockSetting;
use App\Support\ActivityLogger;
use App\Support\TtLockApi;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Schema;
use Illuminate\Validation\Rule;

class TtLockSettingsController extends Controller
{
    public function index()
    {
        $events = Schema::hasTable('tt_lock_events')
            ? TtLockEvent::query()->with(['ttLock.unit.building', 'unit.building'])->latest('event_at')->latest()->limit(100)->get()
            : collect();

        return view('tt-lock-settings.index', [
            'settings' => TtLockSetting::query()->withCount('locks')->latest()->get(),
            'locks' => TtLock::query()->with(['setting', 'unit.building'])->orderBy('lock_name')->get(),
            'events' => $events,
            'statuses' => TtLock::STATUSES,
            'callbackUrl' => route('ttlock.callback'),
        ]);
    }

    public function storeSetting(Request $request)
    {
        $setting = TtLockSetting::create($this->validateSetting($request));
        ActivityLogger::log('tt_lock_settings.created', "Created TT Lock setting {$setting->name}.", $setting);

        return back()->with('status', 'TT Lock API setting saved.');
    }

    public function updateSetting(Request $request, TtLockSetting $ttLockSetting)
    {
        $ttLockSetting->update($this->validateSetting($request, $ttLockSetting));
        ActivityLogger::log('tt_lock_settings.updated', "Updated TT Lock setting {$ttLockSetting->name}.", $ttLockSetting);

        return back()->with('status', 'TT Lock API setting updated.');
    }

    public function testConnection(TtLockSetting $ttLockSetting, TtLockApi $api)
    {
        try {
            $api->test($ttLockSetting);
            ActivityLogger::log('tt_lock_settings.tested', "Tested TT Lock setting {$ttLockSetting->name}.", $ttLockSetting);

            return back()->with('status', "TTLock connection successful for {$ttLockSetting->name}.");
        } catch (\Throwable $exception) {
            $ttLockSetting->forceFill([
                'last_tested_at' => now(),
                'last_error' => $exception->getMessage(),
            ])->save();

            return back()->withErrors(['ttlock' => 'TTLock test failed: '.$exception->getMessage()]);
        }
    }

    public function syncLocks(TtLockSetting $ttLockSetting, TtLockApi $api)
    {
        try {
            $result = $api->syncLocks($ttLockSetting);
            ActivityLogger::log('tt_locks.synced', "Synced {$result['synced']} TT Locks from {$ttLockSetting->name}.", $ttLockSetting);

            return back()->with('status', "Synced {$result['synced']} lock(s) from TTLock.");
        } catch (\Throwable $exception) {
            $ttLockSetting->forceFill([
                'last_tested_at' => now(),
                'last_error' => $exception->getMessage(),
            ])->save();

            return back()->withErrors(['ttlock' => 'TTLock sync failed: '.$exception->getMessage()]);
        }
    }

    public function destroySetting(TtLockSetting $ttLockSetting)
    {
        $ttLockSetting->delete();

        return back()->with('status', 'TT Lock API setting deleted.');
    }

    public function storeLock(Request $request)
    {
        $lock = TtLock::create($this->validateLock($request));
        ActivityLogger::log('tt_locks.created', "Created TT Lock {$lock->lock_name}.", $lock);

        return back()->with('status', 'TT Lock saved.');
    }

    public function updateLock(Request $request, TtLock $ttLock)
    {
        $ttLock->update($this->validateLock($request, $ttLock));
        ActivityLogger::log('tt_locks.updated', "Updated TT Lock {$ttLock->lock_name}.", $ttLock);

        return back()->with('status', 'TT Lock updated.');
    }

    public function destroyLock(TtLock $ttLock)
    {
        $ttLock->unit()->update(['tt_lock_id' => null]);
        $ttLock->delete();

        return back()->with('status', 'TT Lock deleted.');
    }

    private function validateSetting(Request $request, ?TtLockSetting $setting = null): array
    {
        $validated = $request->validate([
            'name' => ['nullable', 'string', 'max:191'],
            'client_id' => ['required', 'string', 'max:191'],
            'client_secret' => [$setting ? 'nullable' : 'required', 'string', 'max:500'],
            'username' => ['required', 'string', 'max:191'],
            'password' => [$setting ? 'nullable' : 'required', 'string', 'max:500'],
            'redirect_uri' => ['nullable', 'url', 'max:500'],
            'is_active' => ['nullable', 'boolean'],
        ]);

        $validated['name'] = ($validated['name'] ?? null) ?: 'Default';
        $validated['is_active'] = $request->boolean('is_active');
        $validated['redirect_uri'] = ($validated['redirect_uri'] ?? null) ?: route('ttlock.callback');

        if ($setting && blank($validated['client_secret'] ?? null)) {
            unset($validated['client_secret']);
        }

        if ($setting && blank($validated['password'] ?? null)) {
            unset($validated['password']);
        }

        return $validated;
    }

    private function validateLock(Request $request, ?TtLock $lock = null): array
    {
        return $request->validate([
            'tt_lock_setting_id' => ['nullable', 'exists:tt_lock_settings,id'],
            'lock_name' => ['required', 'string', 'max:191'],
            'lock_id' => ['required', 'string', 'max:191', Rule::unique('tt_locks', 'lock_id')->ignore($lock)],
            'lock_alias' => ['nullable', 'string', 'max:191'],
            'gateway_id' => ['nullable', 'string', 'max:191'],
            'mac_address' => ['nullable', 'string', 'max:191'],
            'battery_level' => ['nullable', 'integer', 'between:0,100'],
            'signal_strength' => ['nullable', 'string', 'max:191'],
            'status' => ['required', Rule::in(TtLock::STATUSES)],
            'last_synced_at' => ['nullable', 'date'],
            'notes' => ['nullable', 'string', 'max:1000'],
        ]);
    }
}
