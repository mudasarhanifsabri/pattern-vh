<?php

namespace Database\Seeders;

use App\Models\Building;
use App\Models\Expense;
use App\Models\Agent;
use App\Models\Booking;
use App\Models\InventoryItem;
use App\Models\InventoryMovement;
use App\Models\Invoice;
use App\Models\Payment;
use App\Models\OperationsTeamMember;
use App\Models\Owner;
use App\Models\OwnerUnitContract;
use App\Models\Tenant;
use App\Models\TtLock;
use App\Models\TtLockSetting;
use App\Models\Unit;
use App\Models\User;
use App\Models\UtilityAccount;
use App\Models\UtilityBill;
use App\Models\Vehicle;
use App\Models\VehicleHandover;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use App\Support\BookingWorkflow;
use App\Support\InvoicePaymentWorkflow;
use App\Support\TaxCalculator;

class DemoPortfolioSeeder extends Seeder
{
    public function run(): void
    {
        $marinaVista = Building::updateOrCreate(
            ['name' => 'Marina Vista'],
            [
                'code' => 'MV',
                'area' => 'Dubai Marina',
                'address' => 'Marina Vista, Dubai Marina, Dubai',
                'latitude' => 25.0807000,
                'longitude' => 55.1403000,
                'security_emails' => ['security@example.com', 'management@example.com'],
                'amenities' => ['Pool', 'Gym', 'Lobby security', 'Covered parking'],
                'notes' => 'Security requires tenant passport/Emirates ID, unit permit, and title deed for access card requests.',
            ],
        );

        $palmResidence = Building::updateOrCreate(
            ['name' => 'Palm Residence'],
            [
                'code' => 'PR',
                'area' => 'Palm Jumeirah',
                'address' => 'Palm Residence, Palm Jumeirah, Dubai',
                'latitude' => 25.1122000,
                'longitude' => 55.1389000,
                'security_emails' => ['palm.security@example.com'],
                'amenities' => ['Beach access', 'Pool', 'Concierge', 'Valet parking'],
            ],
        );

        $mariam = Owner::updateOrCreate(
            ['email' => 'mariam.owner@example.com'],
            [
                'full_name' => 'Mariam Al Farsi',
                'mobile_no' => '+971501112233',
                'mobile_has_whatsapp' => true,
                'identity_type' => 'emirates_id',
                'identity_no' => '784-1987-1234567-1',
            ],
        );

        $layla = Owner::updateOrCreate(
            ['email' => 'layla.owner@example.com'],
            [
                'full_name' => 'Layla Hassan',
                'mobile_no' => '+971555551088',
                'mobile_has_whatsapp' => true,
                'identity_type' => 'passport',
                'identity_no' => 'P1234567',
            ],
        );

        $mariamUser = User::updateOrCreate(
            ['email' => 'demo.owner@example.com'],
            ['name' => $mariam->full_name, 'password' => Hash::make('ChangeMe123!'), 'email_verified_at' => now()],
        );
        $mariamUser->assignRole('Owner');
        $mariam->update(['user_id' => $mariamUser->id]);

        $ttLockSetting = TtLockSetting::updateOrCreate(
            ['client_id' => 'demo-client-id'],
            [
                'name' => 'Pattern TT Lock',
                'client_secret' => 'demo-client-secret',
                'username' => 'ttlock-demo@pattern.ae',
                'password' => 'demo-password',
                'redirect_uri' => url('/tt-lock/callback'),
                'is_active' => true,
            ],
        );

        $mainDoorLock = TtLock::updateOrCreate(
            ['lock_id' => 'TT-1402-MAIN'],
            [
                'tt_lock_setting_id' => $ttLockSetting->id,
                'lock_name' => 'Marina Vista 1402 Main Door',
                'lock_alias' => 'Main Door',
                'gateway_id' => 'GW-1402',
                'mac_address' => 'AA:BB:14:02:00:01',
                'battery_level' => 84,
                'signal_strength' => 'Good',
                'status' => 'active',
                'last_synced_at' => now()->subMinutes(20),
                'notes' => 'Primary guest access lock.',
            ],
        );

        $villaGateLock = TtLock::updateOrCreate(
            ['lock_id' => 'TT-V08-GATE'],
            [
                'tt_lock_setting_id' => $ttLockSetting->id,
                'lock_name' => 'Palm Villa 08 Main Gate',
                'lock_alias' => 'Villa Main Gate',
                'gateway_id' => 'GW-V08',
                'mac_address' => 'AA:BB:00:08:00:01',
                'battery_level' => 67,
                'signal_strength' => 'Fair',
                'status' => 'owner_managed',
                'last_synced_at' => now()->subHours(2),
                'notes' => 'Gate lock attached to villa entrance.',
            ],
        );

        $unit = Unit::updateOrCreate(
            ['building_id' => $marinaVista->id, 'unit_no' => '1402'],
            [
                'unit_type' => '1 BHK',
                'availability_status' => 'available',
                'floor' => '14',
                'bedrooms' => 1,
                'bathrooms' => 2,
                'size_sqft' => 820,
                'view' => 'Marina view',
                'parking_no' => 'P2-118',
                'wifi_name' => 'Pattern-1402',
                'wifi_password' => 'Pattern@1402',
                'management_fee_percent' => 18,
                'rent_period' => 'monthly',
                'rent_amount' => 14500,
                'amenities' => ['WiFi', 'Pool', 'Gym', 'Balcony', 'Parking'],
                'internet_provider' => 'du',
                'internet_account_no' => 'DU-1402',
                'electricity_company' => 'DEWA',
                'electricity_paid_by_us' => true,
                'electricity_username' => 'dewa-1402',
                'electricity_password' => 'demo-password',
                'gas_company' => 'Lootah Gas',
                'gas_details' => 'Central gas meter in utility room.',
                'hvac_details' => 'Annual AC service due every May.',
                'title_deed_no' => 'TD-MV-1402',
                'title_deed_expiry_date' => now()->addYears(2),
                'dtcm_permit_no' => 'DTCM-MV-1402',
                'dtcm_permit_expiry_date' => now()->addYear(),
                'ttlock_settings' => ['Auto-lock enabled', 'Guest passcodes expire after checkout'],
                'ttlock_locks' => [
                    ['name' => 'Main Door', 'lock_id' => 'TT-1402-MAIN', 'gateway_id' => 'GW-1402', 'passcode' => 'Managed in TT Lock', 'status' => 'Active', 'notes' => 'Primary guest access lock.'],
                ],
                'tt_lock_id' => $mainDoorLock->id,
            ],
        );

        $unit->owners()->sync([
            $mariam->id => ['share_percent' => 70],
            $layla->id => ['share_percent' => 30],
        ]);

        Unit::updateOrCreate(
            ['building_id' => $palmResidence->id, 'unit_no' => 'Villa 08'],
            [
                'unit_type' => 'Villa',
                'availability_status' => 'occupied',
                'bedrooms' => 4,
                'bathrooms' => 5,
                'size_sqft' => 3600,
                'parking_no' => 'V08',
                'management_fee_percent' => 20,
                'rent_period' => 'seasonal',
                'rent_amount' => 48000,
                'amenities' => ['Private pool', 'Garden', 'Beach access', 'Maid room'],
                'internet_provider' => 'etisalat',
                'internet_account_no' => 'ET-V08',
                'electricity_company' => 'DEWA',
                'electricity_paid_by_us' => false,
                'ttlock_locks' => [
                    ['name' => 'Villa Main Gate', 'lock_id' => 'TT-V08-GATE', 'gateway_id' => 'GW-V08', 'passcode' => 'Owner managed', 'status' => 'Active', 'notes' => 'Gate lock attached to villa entrance.'],
                ],
                'tt_lock_id' => $villaGateLock->id,
            ],
        );

        $tenant = Tenant::updateOrCreate(
            ['email' => 'nora.tenant@example.com'],
            [
                'full_name' => 'Nora Al Mansoori',
                'mobile_no' => '+971522220101',
                'mobile_has_whatsapp' => true,
                'identity_type' => 'emirates_id',
                'identity_no' => '784-1992-7654321-0',
                'identity_expiry_date' => now()->addYears(3),
                'date_of_birth' => now()->subYears(32),
                'nationality' => 'UAE',
                'emergency_contact_name' => 'Hamad Al Mansoori',
                'emergency_contact_mobile' => '+971522220102',
                'bank_name' => 'Emirates NBD',
                'bank_account_name' => 'Nora Al Mansoori',
                'iban' => 'AE070331234567890123456',
            ],
        );
        $tenantUser = User::updateOrCreate(
            ['email' => 'demo.tenant@example.com'],
            ['name' => $tenant->full_name, 'password' => Hash::make('ChangeMe123!'), 'email_verified_at' => now()],
        );
        $tenantUser->assignRole('Tenant');
        $tenant->update(['user_id' => $tenantUser->id]);
        $tenant->notes()->firstOrCreate(['note' => 'Demo tenant profile. Ready for future booking and check-in modules.']);

        $agent = Agent::updateOrCreate(
            ['email' => 'amin.agent@example.com'],
            [
                'full_name' => 'Amin Siddiqui',
                'mobile_no' => '+971555550303',
                'mobile_has_whatsapp' => true,
                'identity_type' => 'passport',
                'identity_no' => 'A9988776',
                'agency_name' => 'Dubai Stay Brokers',
                'rera_no' => 'RERA-42719',
                'commission_percent' => 5,
                'bank_name' => 'ADCB',
                'bank_account_name' => 'Amin Siddiqui',
                'iban' => 'AE460030123456789012345',
            ],
        );
        $agentUser = User::updateOrCreate(
            ['email' => 'demo.agent@example.com'],
            ['name' => $agent->full_name, 'password' => Hash::make('ChangeMe123!'), 'email_verified_at' => now()],
        );
        $agentUser->assignRole('Agent');
        $agent->update(['user_id' => $agentUser->id]);
        $agent->notes()->firstOrCreate(['note' => 'Demo agent profile with 5% commission.']);

        $cleaner = OperationsTeamMember::updateOrCreate(
            ['email' => 'sara.cleaner@example.com'],
            [
                'full_name' => 'Sara Khan',
                'mobile_no' => '+971555550404',
                'mobile_has_whatsapp' => true,
                'identity_type' => 'emirates_id',
                'identity_no' => '784-1995-4444444-4',
                'team_role' => 'cleaner',
                'specialty' => 'Checkout cleaning',
                'service_area' => 'Dubai Marina',
                'availability_status' => 'available',
                'auto_assign_checkout_cleaning' => true,
                'auto_assign_checkout_inspection' => false,
                'auto_assign_stay_tasks' => true,
            ],
        );
        $cleanerUser = User::updateOrCreate(
            ['email' => 'demo.cleaner@example.com'],
            ['name' => $cleaner->full_name, 'password' => Hash::make('ChangeMe123!'), 'email_verified_at' => now()],
        );
        $cleanerUser->assignRole('Cleaner');
        $cleaner->update(['user_id' => $cleanerUser->id]);
        $cleaner->notes()->firstOrCreate(['note' => 'Demo cleaner. Future checkout automation can assign cleaning tasks here.']);

        $technician = OperationsTeamMember::updateOrCreate(
            ['email' => 'omar.technician@example.com'],
            [
                'full_name' => 'Omar Farooq',
                'mobile_no' => '+971555550505',
                'mobile_has_whatsapp' => true,
                'identity_type' => 'passport',
                'identity_no' => 'T4455667',
                'team_role' => 'technician',
                'specialty' => 'AC, plumbing, smart locks',
                'service_area' => 'Palm Jumeirah',
                'availability_status' => 'available',
                'auto_assign_checkout_cleaning' => false,
                'auto_assign_checkout_inspection' => true,
                'auto_assign_stay_tasks' => true,
            ],
        );
        $technicianUser = User::updateOrCreate(
            ['email' => 'demo.technician@example.com'],
            ['name' => $technician->full_name, 'password' => Hash::make('ChangeMe123!'), 'email_verified_at' => now()],
        );
        $technicianUser->assignRole('Technician');
        $technician->update(['user_id' => $technicianUser->id]);
        $technician->notes()->firstOrCreate(['note' => 'Demo technician. Future checkout automation can assign inspection tasks here.']);

        $bookingRent = 8500;
        $bookingVat = TaxCalculator::rentVat($bookingRent);
        $bookingTotal = $bookingRent + $bookingVat + 1500 + 75 + 250 + 425;

        $booking = Booking::updateOrCreate(
            ['booking_no' => 'BK-DEMO-0001'],
            [
                'booking_type' => 'holiday_home',
                'unit_id' => $unit->id,
                'tenant_id' => $tenant->id,
                'agent_id' => $agent->id,
                'check_in_date' => now()->addDays(3)->toDateString(),
                'check_out_date' => now()->addDays(8)->toDateString(),
                'check_in_time' => '15:00',
                'check_out_time' => '11:00',
                'guest_count' => 2,
                'rent_amount' => $bookingRent,
                'deposit_amount' => 1500,
                'dtcm_fee' => 75,
                'cleaning_fee' => 250,
                'agency_fee' => 425,
                'vat_amount' => $bookingVat,
                'total_amount' => $bookingTotal,
                'booking_status' => 'confirmed',
                'source' => 'Direct / agent',
                'notes' => 'Demo confirmed holiday home booking.',
            ],
        );

        $booking->forceFill([
            'confirmation_token' => $booking->confirmation_token ?: Str::random(48),
            'confirmation_delivery_channels' => ['email', 'whatsapp', 'portal'],
            'confirmation_link_sent_at' => now(),
        ])->save();

        app(BookingWorkflow::class)->afterSaved($booking->fresh(['unit', 'tenant']));

        $invoice = Invoice::updateOrCreate(
            ['invoice_no' => 'INV-DEMO-0001'],
            [
                'booking_id' => $booking->id,
                'tenant_id' => $booking->tenant_id,
                'unit_id' => $booking->unit_id,
                'invoice_date' => now()->toDateString(),
                'due_date' => now()->addDays(2)->toDateString(),
                'rent_amount' => $booking->rent_amount,
                'deposit_amount' => $booking->deposit_amount,
                'dtcm_fee' => $booking->dtcm_fee,
                'cleaning_fee' => $booking->cleaning_fee,
                'agency_fee' => $booking->agency_fee,
                'vat_amount' => $booking->vat_amount,
                'total_amount' => $booking->total_amount,
                'paid_amount' => 0,
                'balance_amount' => $booking->total_amount,
                'status' => 'sent',
                'notes' => 'Demo invoice generated from booking.',
            ],
        );

        $payment = Payment::updateOrCreate(
            ['payment_no' => 'PAY-DEMO-0001'],
            [
                'invoice_id' => $invoice->id,
                'booking_id' => $booking->id,
                'method' => 'card_machine',
                'status' => 'approved',
                'amount' => $booking->total_amount,
                'paid_at' => now(),
                'reference_no' => 'CARD-DEMO-APPROVAL',
                'notes' => 'Demo full payment by debit card machine.',
                'approved_at' => now(),
                'verification_notes' => 'Demo payment verified and approved.',
            ],
        );

        app(InvoicePaymentWorkflow::class)->afterPayment($payment);

        Expense::updateOrCreate(
            ['expense_no' => 'EXP-DEMO-0001'],
            [
                'name' => 'AC filter replacement',
                'type' => 'maintenance',
                'expense_to_role' => 'owner',
                'expense_to_id' => $mariam->id,
                'owner_id' => $mariam->id,
                'unit_id' => $unit->id,
                'association' => 'owner_account',
                'incurred_on' => now()->subDays(3)->toDateString(),
                'amount' => 320,
                'notes' => 'Demo owner expense for account statement.',
            ],
        );

        OwnerUnitContract::updateOrCreate(
            ['contract_no' => 'PMC-DEMO-0001'],
            [
                'owner_id' => $mariam->id,
                'unit_id' => $unit->id,
                'status' => 'active',
                'contract_start_date' => now()->startOfMonth(),
                'contract_end_date' => now()->startOfMonth()->addYear(),
                'effective_date' => now()->startOfMonth(),
                'company_name' => 'Pattern Vacation Homes Rental',
                'company_registration_no' => '1123804',
                'company_contact_no' => '+971 4 329 9693',
                'company_email' => 'customerservice@pattern.ae',
                'company_address' => 'Office 413, Al Attar Business Centre, Al Barsha, Dubai, UAE',
                'owner_name' => $mariam->full_name,
                'owner_nationality' => 'Emirati',
                'owner_passport_no' => $mariam->identity_no,
                'owner_contact_no' => $mariam->mobile_no,
                'owner_email' => $mariam->email,
                'property_name' => $marinaVista->name,
                'floor_no' => '14',
                'community' => 'Dubai Marina',
                'property_no' => '1402',
                'property_type' => '1 BHK',
                'dewa_account_no' => 'DEWA-MV-1402',
                'management_fee_percent' => 10,
                'startup_fee' => 3000,
                'furniture_fee' => 23800,
                'vat_amount' => 1190,
                'grand_total' => 27990,
                'bank_account_holder' => $mariam->full_name,
                'bank_currency' => 'AED',
                'bank_name' => 'Emirates NBD',
                'iban' => 'AE070331234567890123456',
                'special_terms' => 'Initial term 12 months from unit permit date. Owner personal use up to 30 calendar days per year during off-season only.',
                'company_signed_at' => now(),
                'owner_signed_at' => now(),
            ],
        );

        $dewaAccount = UtilityAccount::updateOrCreate(
            ['unit_id' => $unit->id, 'provider_type' => 'dewa'],
            [
                'provider_name' => 'DEWA',
                'account_no' => 'DEWA-MV-1402',
                'username' => 'dewa-1402',
                'password' => 'demo-password',
                'paid_by_company' => true,
                'billing_day' => 15,
                'next_due_date' => now()->startOfWeek()->addDays(2)->toDateString(),
                'estimated_amount' => 650,
                'status' => 'active',
                'notes' => 'Company-paid DEWA account for demo unit.',
            ],
        );

        $internetAccount = UtilityAccount::updateOrCreate(
            ['unit_id' => $unit->id, 'provider_type' => 'internet'],
            [
                'provider_name' => 'du Home Internet',
                'account_no' => 'DU-1402',
                'paid_by_company' => true,
                'billing_day' => 19,
                'next_due_date' => now()->startOfWeek()->addDays(4)->toDateString(),
                'estimated_amount' => 399,
                'status' => 'active',
            ],
        );

        UtilityBill::updateOrCreate(
            ['utility_account_id' => $dewaAccount->id, 'due_date' => $dewaAccount->next_due_date],
            ['amount' => 642.50, 'status' => 'pending', 'notes' => 'Demo DEWA bill.'],
        );

        UtilityBill::updateOrCreate(
            ['utility_account_id' => $internetAccount->id, 'due_date' => $internetAccount->next_due_date],
            ['amount' => 399, 'status' => 'pending', 'notes' => 'Demo internet bill.'],
        );

        $vehicle = Vehicle::updateOrCreate(
            ['plate_no' => 'D 45231'],
            [
                'name' => 'Operations Van 01',
                'vehicle_type' => 'Van',
                'make_model' => 'Toyota Hiace',
                'status' => 'checked_out',
                'odometer' => 42850,
                'registration_expiry_date' => now()->addMonths(8),
                'insurance_expiry_date' => now()->addMonths(7),
                'notes' => 'Demo vehicle for cleaning and maintenance operations.',
            ],
        );

        VehicleHandover::updateOrCreate(
            ['vehicle_id' => $vehicle->id, 'handover_type' => 'check_out', 'handover_at' => now()->subHours(3)],
            [
                'team_member_id' => $cleaner->id,
                'odometer' => 42850,
                'fuel_level' => '3/4',
                'photos' => [],
                'remarks' => 'Vehicle handed over for Marina checkout cleaning.',
            ],
        );

        $linen = InventoryItem::updateOrCreate(
            ['sku' => 'LINEN-KING-SET'],
            [
                'name' => 'King bed linen set',
                'category' => 'linen',
                'storage_location' => 'Main store - Shelf A',
                'quantity' => 18,
                'reorder_level' => 10,
                'unit_cost' => 85,
                'status' => 'available',
            ],
        );

        $batteries = InventoryItem::updateOrCreate(
            ['sku' => 'LOCK-BATTERY-AA'],
            [
                'name' => 'Smart lock AA batteries',
                'category' => 'maintenance',
                'storage_location' => 'Maintenance box',
                'quantity' => 8,
                'reorder_level' => 12,
                'unit_cost' => 6,
                'status' => 'low_stock',
            ],
        );

        InventoryMovement::firstOrCreate(
            ['inventory_item_id' => $linen->id, 'movement_type' => 'stock_in', 'reference' => 'DEMO-STOCK'],
            ['quantity' => 18, 'notes' => 'Opening demo stock.'],
        );

        InventoryMovement::firstOrCreate(
            ['inventory_item_id' => $batteries->id, 'movement_type' => 'assigned_to_unit', 'reference' => 'Unit 1402'],
            ['quantity' => 4, 'notes' => 'Used for TT Lock battery replacement.'],
        );
    }
}
