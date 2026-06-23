<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Concerns\ManagesPeopleRecords;
use App\Models\Tenant;

class TenantController extends Controller
{
    use ManagesPeopleRecords;

    protected function modelClass(): string
    {
        return Tenant::class;
    }

    protected function moduleConfig(): array
    {
        return [
            'route' => 'tenants',
            'storage' => 'Tenants',
            'singular' => 'tenant',
            'singularTitle' => 'Tenant',
            'pluralTitle' => 'Tenants',
            'registryTitle' => 'Tenant registry',
            'description' => 'Store tenant identity, contact, documents, blacklist, bank details, and stay-ready information.',
            'role' => 'Tenant',
            'portal' => 'Pattern RMS Tenant Portal',
            'permission' => 'tenants',
            'extra' => 'tenant',
            'rules' => [
                'emergency_contact_name' => ['nullable', 'string', 'max:191'],
                'emergency_contact_mobile' => ['nullable', 'string', 'max:30'],
            ],
        ];
    }
}
