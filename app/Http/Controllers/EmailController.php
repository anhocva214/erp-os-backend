<?php

namespace App\Http\Controllers;

use Exception;
use App\Models\Cc;
use App\Models\Bcc;
use App\Models\Email;
use App\Mail\Sendmail;
use App\Models\Attachment;
use App\Models\EmailConfig;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Mail;

class EmailController extends Controller
{
    public function sendEmail(Request $request): JsonResponse
    {
        try {
            //get the email config
            $emailConfig = EmailConfig::first();
            $file_path = $request->file_paths;
            //find the attachment storage path

            $attachmentPath = [];
            if ($file_path) {
                foreach ($file_path as $path) {
                    $attachmentPath[] = storage_path('app/uploads/' . $path);
                }
            }
            //check the emailConfigName and req name is the same
            // if ($emailConfig->emailConfigName != request('emailConfigName')) {
            //     return response()->json([
            //         'status' => 'error',
            //         'message' => 'Email config name is not correct.'
            //     ], 400);
            // }


            $cc = null;
            if ($request->cc) {
                if (is_array($request->cc)) {
                    $cc = array_map('trim', $request->cc);
                    $cc = array_map('trim', explode(',', $cc[0]));
                } elseif (strpos($request->cc, ',') !== false) {
                    $cc = array_map('trim', explode(',', $request->cc));
                } else {
                    $cc = trim($request->cc);
                }
            }
            $bcc = null;

            if ($request->bcc) {
                if (is_array($request->bcc)) {
                    $bcc = array_map('trim', $request->bcc);
                    $bcc = array_map('trim', explode(',', $bcc[0]));
                } elseif (strpos($request->bcc, ',') !== false) {
                    $bcc = array_map('trim', explode(',', $request->bcc));
                } else {
                    $bcc = trim($request->bcc);
                }
            }
            //create email
            $createEmail = Email::create([
                'senderEmail' => $emailConfig->emailUser,
                'receiverEmail' => $request->receiverEmail,
                'subject' => $request->subject ?? 'No Subject',
                'body' => $request->body ?? 'No Body',
                'emailStatus' => 'sent',
            ]);

            if ($file_path) {
                foreach ($file_path as $path) {
                    Attachment::create([
                        'emailId' => $createEmail->id,
                        'name' => $path,
                    ]);
                }
            }
            // Handle CC
            if ($cc!==null) {
                if (is_array($cc)) {
                    foreach ($cc as $ccEmail) {
                        if ($ccEmail !== "") {
                            Cc::create([
                                'emailId' => $createEmail->id,
                                'ccEmail' => $ccEmail,
                            ]);
                        }
                    }
                } else {
                    Cc::create([
                        'emailId' => $createEmail->id,
                        'ccEmail' => $cc,
                    ]);
                }
            }

            // Handle BCC
            if ($bcc!==null) {
                if (is_array($bcc)) {
                    foreach ($bcc as $bccEmail) {
                        if ($bccEmail !== "") {
                            Bcc::create([
                                'emailId' => $createEmail->id,
                                'bccEmail' => $bccEmail,
                            ]);
                        }
                    }
                } else {
                    Bcc::create([
                        'emailId' => $createEmail->id,
                        'bccEmail' => $bcc,
                    ]);
                }
            }
            function updateEmailStatus($status, Email $email): void
            {
                $email->update([
                    'emailStatus' => $status,
                ]);
            }

            if (!$cc && !$bcc) {
                //send the email
                $mailData = [
                    'title' => $request->subject,
                    'body' => $request->body,
                    'attachment' => $attachmentPath ?? null,

                ];

                $email = sendEmail($emailConfig, $request->receiverEmail, $mailData);
                

                if ($email != false) {
                    updateEmailStatus('sent', $createEmail);
                    return response()->json([
                        'status' => 'success',
                        'message' => 'Email is sent successfully.'
                    ], 200);
                } else {
                    updateEmailStatus('failed', $createEmail);
                    return response()->json([
                        'status' => 'error',
                        'message' => 'Email is not sent!'
                    ], 500);
                }
                
            } else if ($cc && !$bcc) {
                //send the email
                $mailData = [
                    'title' => $request->subject,
                    'body' => $request->body,
                    'attachment' => $attachmentPath ?? null,

                ];
                $email = sendEmail($emailConfig, $request->receiverEmail, $mailData, $cc);

                if ($email != false) {
                    updateEmailStatus('sent', $createEmail);
                    return response()->json([
                        'status' => 'success',
                        'message' => 'Email is sent successfully.'
                    ], 200);
                } else {
                    updateEmailStatus('failed', $createEmail);
                    return response()->json([
                        'status' => 'error',
                        'message' => 'Email is not sent!'
                    ], 500);
                }
            } else if (!$cc && $bcc) {
                //send the email
                $mailData = [
                    'title' => $request->subject,
                    'body' => $request->body,
                    'attachment' => $attachmentPath ?? null,

                ];
                $email = sendEmail($emailConfig, $request->receiverEmail, $mailData, $bcc);

                if ($email != false) {
                    updateEmailStatus('sent', $createEmail);
                    return response()->json([
                        'status' => 'success',
                        'message' => 'Email is sent successfully.'
                    ], 200);
                } else {
                    updateEmailStatus('failed', $createEmail);
                    return response()->json([
                        'status' => 'error',
                        'message' => 'Email is not sent!'
                    ], 500);
                }
            } else {
                //send the email
                $mailData = [
                    'title' => $request->subject,
                    'body' => $request->body,
                    'attachment' => $attachmentPath ?? null,

                ];
                $email = sendEmail($emailConfig, $request->receiverEmail, $mailData, $cc, $bcc);

                if ($email != false) {
                    updateEmailStatus('sent', $createEmail);
                    return response()->json([
                        'status' => 'success',
                        'message' => 'Email is sent successfully.'
                    ], 200);
                } else {
                    updateEmailStatus('failed', $createEmail);
                    return response()->json([
                        'status' => 'error',
                        'message' => 'Email is not sent!'
                    ], 500);
                }
            }
        } catch (Exception $err) {
            echo $err;
            return response()->json(['error' => 'An error occurred during sending email. Please try again later.'], 500);
        }
    }

    //get all emails
    public function getEmails(): JsonResponse
    {
        try {
            $emails = Email::with('cc', 'bcc', 'attachment')->get();
            return response()->json($emails);
        } catch (Exception $err) {
            return response()->json(['error' => 'An error occurred during get emails. Please try again later.'], 500);
        }
    }

    //getSingleEmail
    public function getSingleEmail(Request $request): JsonResponse
    {
        try {
            $email = Email::with('cc', 'bcc', 'attachment')->find($request->id);
            return response()->json($email);
        } catch (Exception $err) {
            return response()->json(['error' => 'An error occurred during get email. Please try again later.'], 500);
        }
    }

    //deleteEmail
    public function deleteEmail(Request $request): JsonResponse
    {
        try {
            $email = Email::find($request->id);
            $email->delete();
            return response()->json([
                'message' => 'Email is deleted successfully.'
            ], 200);
        } catch (Exception $err) {
            return response()->json(['error' => 'An error occurred during delete email. Please try again later.'], 500);
        }
    }
}
