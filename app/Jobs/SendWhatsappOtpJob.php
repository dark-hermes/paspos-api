<?php

namespace App\Jobs;

use App\Services\WhatsappBotClient;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;

class SendWhatsappOtpJob implements ShouldQueue
{
    use Queueable;

    /**
     * Create a new job instance.
     */
    public function __construct(
        public string $phone,
        public string $otp,
        public string $purpose,
    ) {}

    /**
     * Execute the job.
     */
    public function handle(WhatsappBotClient $whatsappBotClient): void
    {
        $message = match ($this->purpose) {
            'registration' => 'Kode OTP verifikasi akun Anda: ' . $this->otp,
            'password_reset' => 'Kode OTP reset password Anda: ' . $this->otp,
            default => 'Kode OTP Anda: ' . $this->otp,
        };

        $whatsappBotClient->sendMessage($this->phone, $message);
    }
}
