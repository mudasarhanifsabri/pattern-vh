<?php

namespace Tests\Feature;

use App\Mail\SupportConversationMail;
use App\Models\SupportCategory;
use App\Models\SupportMessage;
use App\Models\SupportTicket;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Storage;
use Spatie\Permission\PermissionRegistrar;
use Tests\TestCase;

class SupportCenterModuleTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }

    public function test_public_customer_can_start_chat_with_attachment_and_auto_reply(): void
    {
        $this->seed();
        Mail::fake();
        Storage::fake(config('filesystems.default'));
        $category = SupportCategory::where('slug', 'security-deposit')->firstOrFail();

        $response = $this->post(route('support.public.store'), [
            'requester_type' => 'existing',
            'requester_role' => 'Tenant',
            'requester_name' => 'Public Tenant',
            'requester_email' => 'public.tenant@example.com',
            'requester_mobile' => '+971500000001',
            'support_category_id' => $category->id,
            'subject' => 'Security deposit question',
            'description' => 'When will my security deposit refund be processed?',
            'priority' => 'medium',
            'attachment' => UploadedFile::fake()->image('deposit.jpg'),
        ]);

        $ticket = SupportTicket::where('requester_email', 'public.tenant@example.com')->firstOrFail();
        $response->assertRedirect(route('support.public.thread', [$ticket, $ticket->public_token]));
        $this->assertDatabaseHas(SupportMessage::class, ['support_ticket_id' => $ticket->id, 'sender_type' => 'bot', 'is_auto_reply' => true]);
        $this->assertCount(1, $ticket->attachments);
        Mail::assertQueued(SupportConversationMail::class);

        $this->get(route('support.public.thread', [$ticket, $ticket->public_token]))
            ->assertOk()
            ->assertSee('Your security deposit is refundable');
    }

    public function test_staff_can_assign_reply_add_note_convert_and_view_reports(): void
    {
        $this->seed();
        Mail::fake();
        $admin = User::where('email', 'admin@example.com')->firstOrFail();
        $ticket = SupportTicket::where('ticket_no', 'SUP-DEMO-0001')->firstOrFail();

        $this->actingAs($admin)->get(route('support.index', ['ticket' => $ticket->id]))
            ->assertOk()->assertSee('Support Center')->assertSee($ticket->ticket_no);

        $this->actingAs($admin)->post(route('support.reply', $ticket), ['body' => 'Finance has started the refund review.'])
            ->assertRedirect();
        $this->actingAs($admin)->post(route('support.reply', $ticket), ['body' => 'Customer disputed one item.', 'is_internal_note' => '1'])
            ->assertRedirect();
        $this->actingAs($admin)->post(route('support.convert', $ticket))->assertRedirect();

        $ticket->refresh();
        $this->assertSame('ticket', $ticket->mode);
        $this->assertNotNull($ticket->first_response_at);
        $this->assertDatabaseHas(SupportMessage::class, ['support_ticket_id' => $ticket->id, 'is_internal_note' => true]);
        Mail::assertQueued(SupportConversationMail::class);

        $this->actingAs($admin)->get(route('support.create'))->assertOk()->assertSee('New support request');
        $this->actingAs($admin)->get(route('support.reports'))->assertOk()->assertSee('Staff performance');
        $this->actingAs($admin)->get(route('support.quick-replies.index'))->assertOk()->assertSee('Saved replies');
        $this->actingAs($admin)->get(route('support.auto-reply-rules.index'))->assertOk()->assertSee('Rules list');
    }

    public function test_customer_only_sees_own_ticket_and_never_private_notes(): void
    {
        $this->seed();
        $ticket = SupportTicket::where('ticket_no', 'SUP-DEMO-0001')->firstOrFail();
        $customer = User::factory()->create(['email' => $ticket->requester_email]);
        $customer->assignRole('Tenant');
        $ticket->update(['requester_user_id' => $customer->id]);
        $ticket->messages()->create(['user_id' => User::where('email', 'admin@example.com')->value('id'), 'sender_type' => 'staff', 'sender_name' => 'Admin', 'body' => 'Private finance note', 'is_internal_note' => true]);

        $response = $this->actingAs($customer)->get(route('support.index', ['ticket' => $ticket->id]));
        $response->assertOk()->assertSee('Support Center');
        $this->assertStringNotContainsString('Private finance note', $response->getContent());

        $other = SupportTicket::create([
            'ticket_no' => 'SUP-OTHER-0001', 'public_token' => str_repeat('a', 48), 'requester_name' => 'Other',
            'requester_email' => 'other@example.com', 'subject' => 'Other ticket', 'priority' => 'low', 'status' => 'open',
        ]);
        $this->actingAs($customer)->get(route('support.index', ['ticket' => $other->id]))->assertForbidden();
    }
}
