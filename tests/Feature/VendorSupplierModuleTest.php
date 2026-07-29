<?php

namespace Tests\Feature;

use App\Models\OperationsTeamMember;
use App\Models\User;
use App\Models\Vendor;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class VendorSupplierModuleTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_register_vendor_with_multiple_documents(): void
    {
        $this->seed();
        Storage::fake(config('filesystems.default'));

        $admin = User::where('email', 'admin@example.com')->firstOrFail();

        $this->actingAs($admin)
            ->get(route('vendors.index'))
            ->assertOk()
            ->assertSee('Vendors & suppliers');

        $this->actingAs($admin)
            ->post(route('vendors.store'), [
                'company_name' => 'Bright Clean Services LLC',
                'legal_name' => 'Bright Clean Services L.L.C.',
                'contact_person' => 'Maha Noor',
                'mobile_no' => '+971501234567',
                'email' => 'accounts@brightclean.test',
                'category' => 'cleaning',
                'trade_license_no' => 'TL-12345',
                'trade_license_expiry_date' => now()->addYear()->toDateString(),
                'tax_registration_no' => '100123456700003',
                'status' => 'active',
                'documents' => [
                    [
                        'document_type' => 'trade_license',
                        'title' => 'Trade licence',
                        'document_number' => 'TL-12345',
                        'expiry_date' => now()->addYear()->toDateString(),
                        'file' => UploadedFile::fake()->create('trade-licence.pdf', 80, 'application/pdf'),
                    ],
                    [
                        'document_type' => 'tax_registration_certificate',
                        'title' => 'TRN certificate',
                        'document_number' => '100123456700003',
                        'file' => UploadedFile::fake()->create('trn-certificate.pdf', 80, 'application/pdf'),
                    ],
                ],
            ])
            ->assertRedirect();

        $vendor = Vendor::with('documents')->where('company_name', 'Bright Clean Services LLC')->firstOrFail();

        $this->assertNotEmpty($vendor->supplier_no);
        $this->assertCount(2, $vendor->documents);
        Storage::disk(config('filesystems.default'))->assertExists($vendor->documents->first()->path);

        $this->actingAs($admin)
            ->get(route('vendors.show', $vendor))
            ->assertOk()
            ->assertSee('Trade licence')
            ->assertSee('TRN certificate');

        $this->actingAs($admin)
            ->get(route('vendors.documents.show', [$vendor, $vendor->documents->first()]))
            ->assertRedirect();
    }

    public function test_deleting_vendor_removes_its_documents(): void
    {
        $this->seed();
        Storage::fake(config('filesystems.default'));

        $admin = User::where('email', 'admin@example.com')->firstOrFail();
        $vendor = Vendor::create([
            'supplier_no' => 'VEN-DELETE-1',
            'company_name' => 'Delete Me Supplier',
            'category' => 'other',
            'status' => 'active',
            'created_by' => $admin->id,
            'updated_by' => $admin->id,
        ]);
        $path = 'Vendors/VEN-DELETE-1/documents/trade-licence.pdf';
        Storage::disk(config('filesystems.default'))->put($path, 'document');
        $document = $vendor->documents()->create([
            'document_type' => 'trade_license',
            'title' => 'Trade licence',
            'disk' => config('filesystems.default'),
            'path' => $path,
            'original_name' => 'trade-licence.pdf',
            'created_by' => $admin->id,
        ]);

        $this->actingAs($admin)
            ->delete(route('vendors.destroy', $vendor))
            ->assertRedirect(route('vendors.index'));

        $this->assertSoftDeleted('vendors', ['id' => $vendor->id]);
        $this->assertDatabaseMissing('vendor_documents', ['id' => $document->id]);
        Storage::disk(config('filesystems.default'))->assertMissing($path);
    }

    public function test_operations_users_cannot_access_the_admin_supplier_registry(): void
    {
        $this->seed();

        $operationsUser = User::factory()->create();
        $operationsUser->assignRole('Operations Team');
        OperationsTeamMember::create([
            'full_name' => 'Nora Operations',
            'user_id' => $operationsUser->id,
            'email' => $operationsUser->email,
            'mobile_no' => '+971501234567',
            'team_role' => 'operations',
        ]);

        $this->actingAs($operationsUser)
            ->get(route('vendors.index'))
            ->assertForbidden();
    }
}
