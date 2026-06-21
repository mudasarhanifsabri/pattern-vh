<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Concerns\ManagesPeopleRecords;
use App\Models\Agent;

class AgentController extends Controller
{
    use ManagesPeopleRecords;

    protected function modelClass(): string
    {
        return Agent::class;
    }

    protected function moduleConfig(): array
    {
        return [
            'route' => 'agents',
            'storage' => 'Agents',
            'singular' => 'agent',
            'singularTitle' => 'Agent',
            'pluralTitle' => 'Agents',
            'registryTitle' => 'Agent registry',
            'description' => 'Store agent identity, contact, documents, bank details, RERA details, and commission percentage.',
            'role' => 'Agent',
            'portal' => 'Pattern RMS Agent Portal',
            'permission' => 'agents',
            'extra' => 'agent',
            'rules' => [
                'agency_name' => ['nullable', 'string', 'max:191'],
                'rera_no' => ['nullable', 'string', 'max:191'],
                'commission_percent' => ['nullable', 'numeric', 'between:0,100'],
            ],
        ];
    }
}
