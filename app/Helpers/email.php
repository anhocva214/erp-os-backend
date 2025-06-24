<?php

use App\Mail\Sendmail;
use Illuminate\Support\Facades\Mail;

if (!function_exists('sendEmail')) {
function sendEmail($emailConfig, $customerEmail, $mailData, $cc = null, $bcc = null): bool
{
    //set the config
    config([
        'mail.mailers.smtp.host' => $emailConfig->emailHost,
        'mail.mailers.smtp.port' => $emailConfig->emailPort,
        'mail.mailers.smtp.encryption' => $emailConfig->emailEncryption,
        'mail.mailers.smtp.username' => $emailConfig->emailUser,
        'mail.mailers.smtp.password' => $emailConfig->emailPass,
        'mail.mailers.smtp.local_domain' => env('MAIL_EHLO_DOMAIN'),
        'mail.from.address' => $emailConfig->emailUser,
        'mail.from.name' => $emailConfig->emailConfigName,
    ]);
    $email = explode('@', $customerEmail);

    try {
        if (!$cc && !$bcc) {

            $emailSent = Mail::to($customerEmail)->send(new Sendmail($mailData));
            if (!$emailSent) {
                return false;
            } else {
                return true;
            }

        } else if ($cc && !$bcc) {

            $emailSent = Mail::to($customerEmail)->cc($cc)->send(new Sendmail($mailData));
            if (!$emailSent) {
                return false;
            } else {
                return true;
            }

        } else if (!$cc && $bcc) {

            $emailSent = Mail::to($customerEmail)->bcc($bcc)->send(new Sendmail($mailData));
            if (!$emailSent) {
                return false;
            } else {
                return true;
            }

        } else {

            $emailSent = Mail::to($customerEmail)->cc($cc)->bcc($bcc)->send(new Sendmail($mailData));
            if (!$emailSent) {
                return false;
            } else {
                return true;
            }
        }
    } catch (Exception $e) {
        return false;
    }

}
}
