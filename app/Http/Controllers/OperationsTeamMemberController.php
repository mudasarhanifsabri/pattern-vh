<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Concerns\ManagesPeopleRecords;
use App\Models\OperationsTeamMember;
use Illuminate\Validation\Rule;

class OperationsTeamMemberController extends Controller
{
    use ManagesPeopleRecords;

    protected function modelClass(): string
    {
        return OperationsTeamMember::class;
    }

    protected function moduleConfig(): array
    {
        return [
            'route' => 'operations-team',
            'storage' => 'Operations Team',
            'singular' => 'team member',
            'singularTitle' => 'Operations team member',
            'pluralTitle' => 'Operations Team',
            'registryTitle' => 'Operations team registry',
            'description' => 'Store cleaners, technicians, and operations staff for future task management and auto-assignment.',
            'role' => 'Operations Team',
            'portal' => 'Pattern RMS Operations Portal',
            'permission' => 'operations-team',
            'extra' => 'operations',
            'rules' => [
                'team_role' => ['required', Rule::in(OperationsTeamMember::TEAM_ROLES)],
                'specialty' => ['nullable', 'string', 'max:191'],
                'service_area' => ['nullable', 'string', 'max:191'],
                'availability_status' => ['nullable', 'string', 'max:191'],
                'auto_assign_checkout_cleaning' => ['nullable', 'boolean'],
                'auto_assign_checkout_inspection' => ['nullable', 'boolean'],
                'auto_assign_stay_tasks' => ['nullable', 'boolean'],
            ],
        ];
    }
}
