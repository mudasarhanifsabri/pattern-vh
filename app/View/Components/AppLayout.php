<?php

namespace App\View\Components;

use Illuminate\View\Component;
use Illuminate\View\View;

class AppLayout extends Component
{
    /**
     * Get the view / contents that represents the component.
     */
    public function render(): View
    {
        $user = auth()->user();
        $ownerPortal = $user?->can('portal.owner')
            && ! $user?->can('accounting.view')
            && ! $user?->can('accounting.manage')
            && ! $user?->can('users.manage');

        if ($ownerPortal) {
            return view('layouts.owner.app');
        }

        return view('layouts.app');
    }
}
