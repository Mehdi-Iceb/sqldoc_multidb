<?php

namespace App\Mail;

use App\Models\Tenant;
use App\Models\TenantSubscription;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class TenantCreatedMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public Tenant $tenant,
        public TenantSubscription $subscription,
        public string $tenantUrl,
        public string $password
    ) {
        if (!$this->subscription->relationLoaded('subscriptionPlan')) {
        $this->subscription->load('subscriptionPlan');
        }
        
    }

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: '🎉 Welcome to ' . config('app.name') . ' !',
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.tenant-created',
        );
    }

    public function attachments(): array
    {
        return [];
    }
}
