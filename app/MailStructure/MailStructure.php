<?php

namespace App\MailStructure;

use App\Mail\NewAccountMail;
use Exception;
use App\Models\AppSetting;
use App\Models\EmailConfig;
use Illuminate\Support\Facades\Mail;

class MailStructure
{
    /**
     * @throws Exception
     */
    public function EmailConfig(): void
    {
        $emailConfig = EmailConfig::first();
        
        if (!$emailConfig->emailConfigName) {
            throw new Exception("Email config name is not set");
        }

        config([
            'mail.mailers.smtp.host' => $emailConfig->emailHost,
            'mail.mailers.smtp.port' => $emailConfig->emailPort,
            'mail.mailers.smtp.encryption' => $emailConfig->emailEncryption,
            'mail.mailers.smtp.username' => $emailConfig->emailUser,
            'mail.mailers.smtp.password' => $emailConfig->emailPass,
            'mail.mailers.smtp.local_domain' => config('mail.mailers.smtp.local_domain'),
            'mail.from.address' => $emailConfig->emailUser,
            'mail.from.name' => $emailConfig->emailConfigName,
        ]);
    }

    public function newAccount($userMail, $user): void
    {
        $this->EmailConfig();

        
        $email = Mail::to($userMail)
            ->send(new NewAccountMail('emails.NewAccount',
                "Your account has been created!", $user));

        if (!$email) {
            throw new Exception("Email not sent");
        }
    }
}