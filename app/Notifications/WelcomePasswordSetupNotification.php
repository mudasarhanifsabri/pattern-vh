<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use Illuminate\Support\Facades\URL;

class WelcomePasswordSetupNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(
        public string $token,
        public string $portalName = 'Pattern RMS',
    ) {}

    /**
     * Get the notification's delivery channels.
     *
     * @return array<int, string>
     */
    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    /**
     * Get the mail representation of the notification.
     */
    public function toMail(object $notifiable): MailMessage
    {
        $setupUrl = URL::route('password.reset', [
            'token' => $this->token,
            'email' => $notifiable->getEmailForPasswordReset(),
        ]);

        $portalType = match (true) {
            str_contains(strtolower($this->portalName), 'tenant') => 'tenant',
            str_contains(strtolower($this->portalName), 'agent') => 'agent',
            str_contains(strtolower($this->portalName), 'operations'), str_contains(strtolower($this->portalName), 'maintainer') => 'maintainer',
            default => 'owner',
        };
        $subjects = [
            'owner' => 'Welcome to Pattern - Your Owner Portal is Ready',
            'tenant' => 'Welcome to Pattern - Your Guest Portal is Ready',
            'agent' => 'Welcome to Pattern - Your Agent Portal is Ready',
            'maintainer' => 'Welcome to Pattern - Your Team Portal is Ready',
        ];

        return (new MailMessage)
            ->subject($subjects[$portalType])
            ->view('emails.welcome.'.$portalType, [
                'ownerName' => $notifiable->name,
                'ownerEmail' => $notifiable->getEmailForPasswordReset(),
                'portalName' => $this->portalName,
                'loginUrl' => URL::route('login'),
                'setupUrl' => $setupUrl,
                'portalType' => $portalType,
            ]);
    }

    /**
     * Get the array representation of the notification.
     *
     * @return array<string, mixed>
     */
    public function toArray(object $notifiable): array
    {
        return [
            //
        ];
    }
}
