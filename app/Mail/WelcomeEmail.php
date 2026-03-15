<?php

namespace App\Mail;

use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class WelcomeEmail extends Mailable
{
    use Queueable;
    use SerializesModels;

    public function __construct(
        public User $user,
        public string $plainPassword,
    ) {}

    public function build()
    {
        $brand = config('flocksense.brand_name', 'FlockSense');

        return $this->subject("Welcome to {$brand}")
            ->view('emails.welcome', [
                'subject' => "Welcome to {$brand}",
                'preheader' => "Your {$brand} account has been created.",
                'user' => $this->user,
                'plainPassword' => $this->plainPassword,
                'loginUrl' => url('/login'),
            ]);
    }
}
