<?php

use App\Http\Controllers\Admin\ActivityLogController;
use App\Http\Controllers\Admin\PermissionController;
use App\Http\Controllers\Admin\RoleController;
use App\Http\Controllers\Admin\UserController;
use App\Http\Controllers\AccountingController;
use App\Http\Controllers\AgentController;
use App\Http\Controllers\AvailabilityCalendarController;
use App\Http\Controllers\BankReconciliationController;
use App\Http\Controllers\BookingController;
use App\Http\Controllers\BookingConfirmationSigningController;
use App\Http\Controllers\BookingLifecycleController;
use App\Http\Controllers\BuildingController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\CeoDashboardController;
use App\Http\Controllers\DtcmCheckinController;
use App\Http\Controllers\ExpenseController;
use App\Http\Controllers\InventoryController;
use App\Http\Controllers\InvoiceController;
use App\Http\Controllers\IdentityDocumentOcrController;
use App\Http\Controllers\NotificationCenterController;
use App\Http\Controllers\OwnerController;
use App\Http\Controllers\OwnerNoteController;
use App\Http\Controllers\OwnerPayoutController;
use App\Http\Controllers\OwnerUnitContractController;
use App\Http\Controllers\OwnerStatementController;
use App\Http\Controllers\OperationsPlannerController;
use App\Http\Controllers\PaymentController;
use App\Http\Controllers\PaymentCollectionRequestController;
use App\Http\Controllers\OperationsTeamMemberController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\PublicSupportController;
use App\Http\Controllers\PushSubscriptionController;
use App\Http\Controllers\ReportController;
use App\Http\Controllers\SecurityDepositController;
use App\Http\Controllers\SoftwareUpdateController;
use App\Http\Controllers\SystemSettingsController;
use App\Http\Controllers\SupportCenterController;
use App\Http\Controllers\TenantController;
use App\Http\Controllers\TenantPaymentRequestController;
use App\Http\Controllers\TaskManagementController;
use App\Http\Controllers\TtLockCallbackController;
use App\Http\Controllers\TtLockSettingsController;
use App\Http\Controllers\UnitController;
use App\Http\Controllers\UnitDocumentOcrController;
use App\Http\Controllers\UtilityManagementController;
use App\Http\Controllers\VehicleController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return auth()->check()
        ? redirect()->route('dashboard')
        : redirect()->route('login');
});

Route::view('/offline', 'offline')->name('offline');
Route::match(['GET', 'POST'], 'ttlock/callback', TtLockCallbackController::class)->name('ttlock.callback');

Route::get('support/help', [PublicSupportController::class, 'create'])->name('support.public.create');
Route::post('support/help', [PublicSupportController::class, 'store'])->name('support.public.store');
Route::get('support/conversation/{supportTicket}/{token}', [PublicSupportController::class, 'thread'])->name('support.public.thread');
Route::post('support/conversation/{supportTicket}/{token}', [PublicSupportController::class, 'reply'])->name('support.public.reply');
Route::get('support/conversation/{supportTicket}/{token}/messages', [PublicSupportController::class, 'messages'])->name('support.public.messages');
Route::match(['GET', 'POST'], 'support/conversation/{supportTicket}/{token}/typing', [PublicSupportController::class, 'typing'])->name('support.public.typing');
Route::get('support/conversation/{supportTicket}/{token}/attachments/{attachment}', [PublicSupportController::class, 'attachment'])->name('support.public.attachment');

Route::get('booking-confirmations/{booking}/{token}', [BookingConfirmationSigningController::class, 'show'])
    ->name('booking-confirmations.sign');
Route::post('booking-confirmations/{booking}/{token}', [BookingConfirmationSigningController::class, 'sign'])
    ->name('booking-confirmations.sign.store');
Route::get('booking-confirmations/{booking}/{token}/pdf', [BookingConfirmationSigningController::class, 'pdf'])
    ->name('booking-confirmations.pdf');
Route::get('owner-contract-signatures/{ownerContract}/{token}', [OwnerUnitContractController::class, 'signature'])
    ->name('owner-contracts.sign');
Route::post('owner-contract-signatures/{ownerContract}/{token}', [OwnerUnitContractController::class, 'sign'])
    ->name('owner-contracts.sign.store');
Route::get('owner-contract-signatures/{ownerContract}/{token}/pdf', [OwnerUnitContractController::class, 'signaturePdf'])
    ->name('owner-contracts.sign.pdf');
Route::get('owner-contract-signatures/{ownerContract}/{token}/document', [OwnerUnitContractController::class, 'signatureDocument'])
    ->name('owner-contracts.sign.document');

Route::get('/dashboard', DashboardController::class)->middleware(['auth', 'verified'])->name('dashboard');

Route::middleware('auth')->group(function () {
    Route::get('ceo', CeoDashboardController::class)->middleware('permission:ceo.dashboard')->name('ceo.dashboard');
    Route::get('support', [SupportCenterController::class, 'index'])->middleware('permission:support.view|support.manage')->name('support.index');
    Route::get('support/create', [SupportCenterController::class, 'create'])->middleware('permission:support.view|support.manage')->name('support.create');
    Route::post('support', [SupportCenterController::class, 'store'])->middleware('permission:support.view|support.manage')->name('support.store');
    Route::patch('support/{supportTicket}', [SupportCenterController::class, 'update'])->middleware('permission:support.manage')->name('support.update');
    Route::delete('support/{supportTicket}', [SupportCenterController::class, 'destroy'])->middleware('permission:support.manage')->name('support.destroy');
    Route::post('support/{supportTicket}/reply', [SupportCenterController::class, 'reply'])->middleware('permission:support.view|support.manage')->name('support.reply');
    Route::post('support/{supportTicket}/convert', [SupportCenterController::class, 'convert'])->middleware('permission:support.manage')->name('support.convert');
    Route::match(['GET', 'POST'], 'support/{supportTicket}/typing', [SupportCenterController::class, 'typing'])->middleware('permission:support.view|support.manage')->name('support.typing');
    Route::get('support/{supportTicket}/messages', [SupportCenterController::class, 'messages'])->middleware('permission:support.view|support.manage')->name('support.messages');
    Route::get('support/attachments/{attachment}', [SupportCenterController::class, 'attachment'])->middleware('permission:support.view|support.manage')->name('support.attachments');
    Route::post('support/presence/ping', [SupportCenterController::class, 'ping'])->middleware('permission:support.view|support.manage')->name('support.presence.ping');
    Route::post('push-subscriptions', [PushSubscriptionController::class, 'store'])->name('push-subscriptions.store');
    Route::delete('push-subscriptions', [PushSubscriptionController::class, 'destroy'])->name('push-subscriptions.destroy');
    Route::get('notifications/feed', [NotificationCenterController::class, 'feed'])->name('notifications.feed');
    Route::post('notifications/test-push', [NotificationCenterController::class, 'testPush'])->name('notifications.test-push');
    Route::post('notifications/{notificationLog}/read', [NotificationCenterController::class, 'read'])->name('notifications.read');
    Route::post('notifications/read-all', [NotificationCenterController::class, 'readAll'])->name('notifications.read-all');
    Route::post('identity-documents/ocr', IdentityDocumentOcrController::class)->name('identity-documents.ocr');
    Route::post('unit-documents/ocr', UnitDocumentOcrController::class)->middleware('permission:units.manage')->name('unit-documents.ocr');
    Route::get('support-reports', [SupportCenterController::class, 'reports'])->middleware('permission:support.reports')->name('support.reports');
    Route::get('support-quick-replies', [SupportCenterController::class, 'quickReplies'])->middleware('permission:support.manage')->name('support.quick-replies.index');
    Route::post('support-reports/quick-replies', [SupportCenterController::class, 'storeQuickReply'])->middleware('permission:support.manage')->name('support.quick-replies.store');
    Route::get('support-auto-reply-rules', [SupportCenterController::class, 'autoReplyRules'])->middleware('permission:support.manage')->name('support.auto-reply-rules.index');
    Route::post('support-reports/auto-reply-rules', [SupportCenterController::class, 'storeRule'])->middleware('permission:support.manage')->name('support.auto-reply-rules.store');

    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');

    Route::resource('owners', OwnerController::class)->except(['index', 'show'])->middleware('permission:owners.manage');
    Route::resource('owners', OwnerController::class)->only(['index', 'show'])->middleware('permission:owners.view|owners.manage');
    Route::get('owners/{owner}/document', [OwnerController::class, 'document'])
        ->middleware('permission:owners.view|owners.manage')
        ->name('owners.document');
    Route::post('owners/{owner}/send-invite', [OwnerController::class, 'sendInvite'])
        ->middleware('permission:owners.manage')
        ->name('owners.send-invite');
    Route::post('owners/{owner}/notes', [OwnerNoteController::class, 'store'])->middleware('permission:owners.manage')->name('owners.notes.store');

    foreach ([
        'tenants' => TenantController::class,
        'agents' => AgentController::class,
        'operations-team' => OperationsTeamMemberController::class,
    ] as $prefix => $controller) {
        Route::resource($prefix, $controller)->except(['index', 'show'])->middleware("permission:{$prefix}.manage");
        Route::resource($prefix, $controller)->only(['index', 'show'])->middleware("permission:{$prefix}.view|{$prefix}.manage");
        Route::get("{$prefix}/{record}/document", [$controller, 'document'])
            ->middleware("permission:{$prefix}.view|{$prefix}.manage")
            ->name("{$prefix}.document");
        Route::post("{$prefix}/{record}/send-invite", [$controller, 'sendInvite'])
            ->middleware("permission:{$prefix}.manage")
            ->name("{$prefix}.send-invite");
        Route::post("{$prefix}/{record}/notes", [$controller, 'storeNote'])
            ->middleware("permission:{$prefix}.manage")
            ->name("{$prefix}.notes.store");
    }

    Route::resource('buildings', BuildingController::class)->except(['index', 'show'])->middleware('permission:buildings.manage');
    Route::resource('buildings', BuildingController::class)->only(['index', 'show'])->middleware('permission:buildings.view|buildings.manage');

    Route::resource('units', UnitController::class)->except(['index', 'show'])->middleware('permission:units.manage');
    Route::resource('units', UnitController::class)->only(['index', 'show'])->middleware('permission:units.view|units.manage');
    Route::get('units/{unit}/documents/{type}', [UnitController::class, 'document'])
        ->middleware('permission:units.view|units.manage')
        ->name('units.document');
    Route::get('units/{unit}/pictures/{index}', [UnitController::class, 'picture'])
        ->whereNumber('index')
        ->middleware('permission:units.view|units.manage')
        ->name('units.picture');
    Route::post('units/{unit}/access-card-request', [UnitController::class, 'sendAccessCardRequest'])
        ->middleware('permission:units.manage')
        ->name('units.access-card-request');

    Route::resource('bookings', BookingController::class)->except(['index', 'show'])->middleware('permission:bookings.manage');
    Route::resource('bookings', BookingController::class)->only(['index', 'show'])->middleware('permission:bookings.view|bookings.manage');
    Route::get('availability-calendar', [AvailabilityCalendarController::class, 'index'])
        ->middleware('permission:availability-calendar.view|bookings.view|bookings.manage')
        ->name('availability-calendar.index');
    Route::get('planning-sheet', [OperationsPlannerController::class, 'index'])
        ->middleware('permission:bookings.view|bookings.manage|booking-tasks.view|booking-tasks.manage')
        ->name('planning-sheet.index');
    Route::get('planning-sheet/pdf', [OperationsPlannerController::class, 'pdf'])
        ->middleware('permission:bookings.view|bookings.manage|booking-tasks.view|booking-tasks.manage')
        ->name('planning-sheet.pdf');
    Route::get('bookings/{booking}/confirmation-pdf', [BookingController::class, 'confirmationPdf'])
        ->middleware('permission:bookings.view|bookings.manage')
        ->name('bookings.confirmation-pdf');
    Route::get('bookings/{booking}/inspection', [TaskManagementController::class, 'bookingInspection'])
        ->middleware('permission:portal.tenant|bookings.view|bookings.manage|booking-tasks.view|booking-tasks.manage')
        ->name('bookings.inspection');
    Route::post('bookings/{booking}/inspection', [TaskManagementController::class, 'submitBookingInspection'])
        ->middleware('permission:bookings.manage|booking-tasks.manage')
        ->name('bookings.inspection.store');
    Route::post('bookings/{booking}/send-confirmation-link', [BookingConfirmationSigningController::class, 'send'])
        ->middleware('permission:bookings.manage')
        ->name('bookings.send-confirmation-link');
    Route::post('bookings/{booking}/smart-lock-access', [BookingController::class, 'updateSmartLockAccess'])
        ->middleware('permission:bookings.manage')
        ->name('bookings.smart-lock-access.update');
    Route::post('bookings/{booking}/request-extension', [BookingLifecycleController::class, 'requestExtension'])
        ->middleware('permission:portal.tenant')
        ->name('bookings.request-extension');
    Route::post('bookings/{booking}/request-checkout', [BookingLifecycleController::class, 'requestCheckout'])
        ->middleware('permission:portal.tenant')
        ->name('bookings.request-checkout');
    Route::post('bookings/{booking}/complete-checkout', [BookingLifecycleController::class, 'completeCheckout'])
        ->middleware('permission:bookings.manage')
        ->name('bookings.complete-checkout');
    Route::post('booking-extension-requests/{extensionRequest}/approve', [BookingLifecycleController::class, 'approveExtension'])
        ->middleware('permission:bookings.manage')
        ->name('booking-extension-requests.approve');
    Route::post('booking-extension-requests/{extensionRequest}/reject', [BookingLifecycleController::class, 'rejectExtension'])
        ->middleware('permission:bookings.manage')
        ->name('booking-extension-requests.reject');
    Route::post('booking-deposit-refunds/{depositRefund}/complete-inspection', [BookingLifecycleController::class, 'completeInspection'])
        ->middleware('permission:bookings.manage')
        ->name('booking-deposit-refunds.complete-inspection');
    Route::post('booking-deposit-refunds/{depositRefund}/accept', [BookingLifecycleController::class, 'acceptDepositReport'])
        ->middleware('permission:portal.tenant')
        ->name('booking-deposit-refunds.accept');
    Route::post('booking-deposit-refunds/{depositRefund}/process', [BookingLifecycleController::class, 'processRefund'])
        ->middleware('permission:bookings.manage')
        ->name('booking-deposit-refunds.process');

    Route::get('tasks', [TaskManagementController::class, 'index'])
        ->middleware('permission:booking-tasks.view|booking-tasks.manage')
        ->name('tasks.index');
    Route::patch('tasks/{bookingTask}', [TaskManagementController::class, 'update'])
        ->middleware('permission:booking-tasks.manage')
        ->name('tasks.update');
    Route::post('tasks/{bookingTask}/checkout-inspection', [TaskManagementController::class, 'submitCheckoutInspection'])
        ->middleware('permission:booking-tasks.manage')
        ->name('tasks.checkout-inspection');
    Route::post('bookings/{booking}/tenant-check-in-report', [TaskManagementController::class, 'submitTenantCheckIn'])
        ->middleware('permission:portal.tenant')
        ->name('bookings.tenant-check-in-report');

    Route::get('utilities', [UtilityManagementController::class, 'index'])
        ->middleware('permission:utilities.view|utilities.manage')
        ->name('utilities.index');
    Route::post('utilities/accounts', [UtilityManagementController::class, 'storeAccount'])
        ->middleware('permission:utilities.manage')
        ->name('utilities.accounts.store');
    Route::post('utilities/bills', [UtilityManagementController::class, 'storeBill'])
        ->middleware('permission:utilities.manage')
        ->name('utilities.bills.store');
    Route::post('utilities/bills/{utilityBill}/mark-paid', [UtilityManagementController::class, 'markBillPaid'])
        ->middleware('permission:utilities.manage')
        ->name('utilities.bills.mark-paid');

    Route::get('vehicles', [VehicleController::class, 'index'])
        ->middleware('permission:vehicles.view|vehicles.manage')
        ->name('vehicles.index');
    Route::post('vehicles', [VehicleController::class, 'store'])
        ->middleware('permission:vehicles.manage')
        ->name('vehicles.store');
    Route::post('vehicles/{vehicle}/handover', [VehicleController::class, 'handover'])
        ->middleware('permission:vehicles.manage')
        ->name('vehicles.handover');

    Route::get('inventory', [InventoryController::class, 'index'])
        ->middleware('permission:inventory.view|inventory.manage')
        ->name('inventory.index');
    Route::post('inventory', [InventoryController::class, 'store'])
        ->middleware('permission:inventory.manage')
        ->name('inventory.store');
    Route::post('inventory/{inventoryItem}/movement', [InventoryController::class, 'movement'])
        ->middleware('permission:inventory.manage')
        ->name('inventory.movement');

    Route::get('accounting', AccountingController::class)
        ->middleware('permission:accounting.view|accounting.manage')
        ->name('accounting.index');
    Route::get('bank-reconciliation', [BankReconciliationController::class, 'index'])
        ->middleware('permission:bank-reconciliation.view|bank-reconciliation.manage')
        ->name('bank-reconciliation.index');
    Route::post('bank-reconciliation/accounts', [BankReconciliationController::class, 'storeAccount'])
        ->middleware('permission:bank-reconciliation.manage')
        ->name('bank-reconciliation.accounts.store');
    Route::post('bank-reconciliation/import', [BankReconciliationController::class, 'import'])
        ->middleware('permission:bank-reconciliation.manage')
        ->name('bank-reconciliation.import');
    Route::post('bank-reconciliation/transactions/{bankTransaction}/confirm', [BankReconciliationController::class, 'confirm'])
        ->middleware('permission:bank-reconciliation.manage')
        ->name('bank-reconciliation.confirm');
    Route::post('bank-reconciliation/transactions/{bankTransaction}/manual-match', [BankReconciliationController::class, 'manualMatch'])
        ->middleware('permission:bank-reconciliation.manage')
        ->name('bank-reconciliation.manual-match');
    Route::post('bank-reconciliation/matches/{match}/reject', [BankReconciliationController::class, 'reject'])
        ->middleware('permission:bank-reconciliation.manage')
        ->name('bank-reconciliation.reject');
    Route::post('bank-reconciliation/transactions/{bankTransaction}/ignore', [BankReconciliationController::class, 'ignore'])
        ->middleware('permission:bank-reconciliation.manage')
        ->name('bank-reconciliation.ignore');
    Route::resource('expenses', ExpenseController::class)->except(['index', 'show'])->middleware('permission:expenses.manage');
    Route::resource('expenses', ExpenseController::class)->only(['index', 'show'])->middleware('permission:expenses.view|expenses.manage');
    Route::get('expenses/{expense}/receipt', [ExpenseController::class, 'receipt'])
        ->middleware('permission:expenses.view|expenses.manage')
        ->name('expenses.receipt');
    Route::get('owner-statements', [OwnerStatementController::class, 'index'])
        ->middleware('permission:owner-statements.view|owner-statements.manage|portal.owner')
        ->name('owner-statements.index');
    Route::get('owner-statements/pdf', [OwnerStatementController::class, 'pdf'])
        ->middleware('permission:owner-statements.view|owner-statements.manage|portal.owner')
        ->name('owner-statements.pdf');
    Route::get('owner-payouts', [OwnerPayoutController::class, 'index'])
        ->middleware('permission:owner-payouts.view|owner-payouts.manage|portal.owner')
        ->name('owner-payouts.index');
    Route::post('owner-payouts/transfers', [OwnerPayoutController::class, 'storeTransfer'])
        ->middleware('permission:owner-payouts.manage')
        ->name('owner-payouts.transfers.store');
    Route::get('reports', [ReportController::class, 'index'])
        ->middleware('permission:reports.view|reports.export|accounting.view|accounting.manage|users.manage|portal.owner')
        ->name('reports.index');
    Route::get('reports/export', [ReportController::class, 'export'])
        ->middleware('permission:reports.export|accounting.manage|users.manage|portal.owner')
        ->name('reports.export');

    Route::get('settings', [SystemSettingsController::class, 'index'])
        ->middleware('permission:users.manage|roles.manage')
        ->name('settings.index');
    Route::patch('settings', [SystemSettingsController::class, 'update'])
        ->middleware('permission:users.manage|roles.manage')
        ->name('settings.update');
    Route::post('settings/test', [SystemSettingsController::class, 'test'])
        ->middleware('permission:users.manage|roles.manage')
        ->name('settings.test');
    Route::get('software-updates', [SoftwareUpdateController::class, 'index'])
        ->middleware('permission:software-updates.manage|users.manage')
        ->name('software-updates.index');
    Route::post('software-updates/run', [SoftwareUpdateController::class, 'run'])
        ->middleware('permission:software-updates.manage|users.manage')
        ->name('software-updates.run');
    Route::get('software-updates/logs/{type}/download', [SoftwareUpdateController::class, 'downloadLog'])
        ->middleware('permission:software-updates.manage|users.manage')
        ->name('software-updates.logs.download');
    Route::post('software-updates/logs/{type}/copy', [SoftwareUpdateController::class, 'copyLog'])
        ->middleware('permission:software-updates.manage|users.manage')
        ->name('software-updates.logs.copy');

    Route::get('tt-lock-settings', [TtLockSettingsController::class, 'index'])
        ->middleware('permission:users.manage|roles.manage')
        ->name('tt-lock-settings.index');
    Route::post('tt-lock-settings/groups', [TtLockSettingsController::class, 'storeSetting'])
        ->middleware('permission:users.manage|roles.manage')
        ->name('tt-lock-settings.groups.store');
    Route::patch('tt-lock-settings/groups/{ttLockSetting}', [TtLockSettingsController::class, 'updateSetting'])
        ->middleware('permission:users.manage|roles.manage')
        ->name('tt-lock-settings.groups.update');
    Route::delete('tt-lock-settings/groups/{ttLockSetting}', [TtLockSettingsController::class, 'destroySetting'])
        ->middleware('permission:users.manage|roles.manage')
        ->name('tt-lock-settings.groups.destroy');
    Route::post('tt-lock-settings/groups/{ttLockSetting}/test', [TtLockSettingsController::class, 'testConnection'])
        ->middleware('permission:users.manage|roles.manage')
        ->name('tt-lock-settings.groups.test');
    Route::post('tt-lock-settings/groups/{ttLockSetting}/sync-locks', [TtLockSettingsController::class, 'syncLocks'])
        ->middleware('permission:users.manage|roles.manage')
        ->name('tt-lock-settings.groups.sync-locks');
    Route::post('tt-lock-settings/groups/{ttLockSetting}/sync-history', [TtLockSettingsController::class, 'syncHistory'])
        ->middleware('permission:users.manage|roles.manage')
        ->name('tt-lock-settings.groups.sync-history');
    Route::post('tt-lock-settings/locks', [TtLockSettingsController::class, 'storeLock'])
        ->middleware('permission:users.manage|roles.manage')
        ->name('tt-lock-settings.locks.store');
    Route::patch('tt-lock-settings/locks/{ttLock}', [TtLockSettingsController::class, 'updateLock'])
        ->middleware('permission:users.manage|roles.manage')
        ->name('tt-lock-settings.locks.update');
    Route::delete('tt-lock-settings/locks/{ttLock}', [TtLockSettingsController::class, 'destroyLock'])
        ->middleware('permission:users.manage|roles.manage')
        ->name('tt-lock-settings.locks.destroy');

    Route::resource('owner-contracts', OwnerUnitContractController::class)->except(['index', 'show'])
        ->middleware('permission:owner-contracts.manage');
    Route::resource('owner-contracts', OwnerUnitContractController::class)->only(['index', 'show'])
        ->middleware('permission:owner-contracts.view|owner-contracts.manage');
    Route::get('owner-contracts/{ownerContract}/document', [OwnerUnitContractController::class, 'document'])
        ->middleware('permission:owner-contracts.view|owner-contracts.manage')
        ->name('owner-contracts.document');
    Route::get('owner-contracts/{ownerContract}/prepared-document', [OwnerUnitContractController::class, 'preparedDocument'])
        ->middleware('permission:owner-contracts.view|owner-contracts.manage')
        ->name('owner-contracts.prepared-document');
    Route::get('owner-contracts/{ownerContract}/pdf', [OwnerUnitContractController::class, 'pdf'])
        ->middleware('permission:owner-contracts.view|owner-contracts.manage')
        ->name('owner-contracts.pdf');
    Route::post('owner-contracts/{ownerContract}/signature-link', [OwnerUnitContractController::class, 'signatureLink'])
        ->middleware('permission:owner-contracts.manage')
        ->name('owner-contracts.signature-link');
    Route::post('owner-contracts/{ownerContract}/company-signature', [OwnerUnitContractController::class, 'companySign'])
        ->middleware('permission:owner-contracts.manage')
        ->name('owner-contracts.company-signature');

    Route::resource('invoices', InvoiceController::class)->except(['index', 'show'])->middleware('permission:invoices.manage');
    Route::resource('invoices', InvoiceController::class)->only(['index', 'show'])->middleware('permission:invoices.view|invoices.manage');
    Route::get('invoices/{invoice}/pdf', [InvoiceController::class, 'pdf'])
        ->middleware('permission:invoices.view|invoices.manage')
        ->name('invoices.pdf');
    Route::get('payments', [PaymentController::class, 'index'])
        ->middleware('permission:payments.view|payments.manage')
        ->name('payments.index');
    Route::post('invoices/{invoice}/payments', [PaymentController::class, 'store'])
        ->middleware('permission:payments.manage')
        ->name('invoices.payments.store');
    Route::get('payments/{payment}/proof', [PaymentController::class, 'proof'])
        ->middleware('permission:payments.view|payments.manage')
        ->name('payments.proof');
    Route::post('payments/{payment}/approve', [PaymentController::class, 'approve'])
        ->middleware('permission:payments.manage')
        ->name('payments.approve');
    Route::post('payments/{payment}/reject', [PaymentController::class, 'reject'])
        ->middleware('permission:payments.manage')
        ->name('payments.reject');
    Route::get('receipts/{receipt}/pdf', [PaymentController::class, 'receiptPdf'])
        ->middleware('permission:receipts.view|receipts.manage')
        ->name('receipts.pdf');
    Route::get('security-deposits', [SecurityDepositController::class, 'index'])
        ->middleware('permission:security-deposits.view|security-deposits.manage')
        ->name('security-deposits.index');

    Route::get('payment-collection-requests', [PaymentCollectionRequestController::class, 'index'])
        ->middleware('permission:payment-collection-requests.view|payment-collection-requests.manage')
        ->name('payment-collection-requests.index');
    Route::post('payment-collection-requests/{paymentCollectionRequest}/schedule', [PaymentCollectionRequestController::class, 'schedule'])
        ->middleware('permission:payment-collection-requests.manage')
        ->name('payment-collection-requests.schedule');
    Route::post('payment-collection-requests/{paymentCollectionRequest}/collect', [PaymentCollectionRequestController::class, 'collect'])
        ->middleware('permission:payment-collection-requests.manage')
        ->name('payment-collection-requests.collect');
    Route::post('payment-collection-requests/{paymentCollectionRequest}/cancel', [PaymentCollectionRequestController::class, 'cancel'])
        ->middleware('permission:payment-collection-requests.manage')
        ->name('payment-collection-requests.cancel');

    Route::get('booking-dtcm-checkins', [DtcmCheckinController::class, 'index'])
        ->middleware('permission:dtcm-checkins.view|dtcm-checkins.manage')
        ->name('dtcm-checkins.index');
    Route::post('booking-dtcm-checkins/{dtcmCheckin}/complete', [DtcmCheckinController::class, 'complete'])
        ->middleware('permission:dtcm-checkins.manage')
        ->name('dtcm-checkins.complete');

    Route::get('tenant/payment-requests', [TenantPaymentRequestController::class, 'index'])
        ->middleware('permission:portal.tenant')
        ->name('tenant.payment-requests.index');
    Route::post('tenant/payment-requests', [TenantPaymentRequestController::class, 'store'])
        ->middleware('permission:portal.tenant')
        ->name('tenant.payment-requests.store');

    Route::prefix('admin')->name('admin.')->middleware('permission:users.view|roles.view|permissions.view|activity.view')->group(function () {
        Route::resource('users', UserController::class)->middleware('permission:users.manage');
        Route::resource('roles', RoleController::class)->middleware('permission:roles.manage');
        Route::resource('permissions', PermissionController::class)->middleware('permission:permissions.manage');
        Route::get('activity-logs', [ActivityLogController::class, 'index'])
            ->middleware('permission:activity.view')
            ->name('activity-logs.index');
    });
});

require __DIR__.'/auth.php';
