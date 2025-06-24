<?php

use Carbon\Carbon;
use App\Models\Users;
use App\Mail\Sendmail;
use App\Models\Product;
use App\Models\Customer;
use App\Models\AppSetting;
use App\Models\ProductVat;
use App\Models\EmailConfig;
use Illuminate\Support\Facades\Mail;

/**
 * @throws Exception
 */
if (!function_exists('MailSend')) {
    function MailSend($saleData, $product = null, $vatId = null, $receiverEmail = null): void
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
                'mail.mailers.smtp.local_domain' => env('MAIL_EHLO_DOMAIN'),
                'mail.from.address' => $emailConfig->emailUser,
                'mail.from.name' => $emailConfig->emailConfigName,
            ]);

            if (isset($saleData['customerId'])) {

                $customer = Customer::find($saleData['customerId']);

                if (!$customer) {
                    throw new Exception("Customer not found");
                }
                if ($receiverEmail == null) {
                    $receiverEmail = $customer->email;
                }
            }

            if (isset($saleData["userId"])) {
                $user = Users::find($saleData['userId']);
            }
           
            $productIds = array_column($product, 'productId');

           
            $productInfo = Product::whereIn('id', $productIds)->get();
            if (isset($vatId)) {
                
                $subTotal = [];
                foreach ($productInfo as $index => $productItem) {
                    $productQuantity = $productInfo[$index]['productQuantity'] ?? 0; // Use the correct key here
                    $subTotal[] = $productQuantity * $productItem->productSalePrice * (1 + $productItem->productVat / 100);
                }
                
                

            } else {

                $total = 0;
                $productQuantities = [];
                foreach ($productInfo as $index => $productItem) {

                    $productQuantity = $productInfo[$index]['productQuantity'] ?? 0;
                    $productQuantities[] = $productQuantity;
                    $total += $productItem->productSalePrice * $productQuantity;
                }
            }
            
            if (isset($vatId)) {
                $data = [
                    'title' => 'Invoice',
                    'invoiceId' => $saleData['id'],
                    'customerName' => $customer->username,
                    'customerEmail' => $customer->email,
                    'customerAddress' => $customer->address,
                    'productNames' => $productInfo->pluck('name')->toArray(),
                    'productQuantities' => array_column($product, 'productQuantity'),
                    'productPrices' => $productInfo->pluck('productSalePrice')->toArray(),
                    'productVats' => $productInfo->pluck('productVat')->toArray(),
                    'subtotal' => $subTotal,
                    'govTax' => ProductVat::find($vatId)->toArray(),
                    'totalAmount' => $saleData['totalAmount'],
                    'company' => AppSetting::first(),
                    'discountAmount' => $saleData['discount'],
                    'note' => $saleData['note'],
                    'invoiceDate' => $saleData['date'],
                    'dueAmount' => $saleData['dueAmount'],
                    'paidAmount' => $saleData['paidAmount'],
                    'salePerson' => $user->username,
                    'body' => '',
                    'name' => '',
                    'email' => '',
                    'password' => '',
                    'companyName' => ''
                ];
                
            } else {
                $data = [
                    'title' => 'quote',
                    'quoteId' => $saleData['id'],
                    'quoteName' => $saleData['quoteName'],
                    'quoteDate' => $saleData['quoteDate'],
                    'quoteOwner' => $saleData['quoteOwner']['username'],
                    'expirationDate' => $saleData['expirationDate'],
                    'productQuantities' => $productQuantities,
                    'productPrices' => $productInfo->pluck('productSalePrice')->toArray(),
                    'productNames' => $productInfo->pluck('name')->toArray(),
                    'totalAmount' => $total,
                    'company' => AppSetting::first(),
                    'termsAndConditions' => $saleData['termsAndConditions'],
                    'body' => '',
                    'name' => '',
                    'email' => '',
                    'password' => '',
                    'companyName' => ''
                ];
                
            }
           
            //send pdf file to email
            $email = Mail::to($receiverEmail)->send(new Sendmail($data));
            if (!$email) {
                throw new Exception("Email not sent");
            }

    }
}

/**
 * @throws Exception
 */
if (!function_exists('MailForReorder')) {
    function MailForReorder($reorderData, $productIds, $receiverEmail): void
    {
        $emailConfig = EmailConfig::first();
        $date = Carbon::now()->format('Y-m-d');

        if (!$emailConfig->emailConfigName) {
            throw new Exception("Email config name is not set");
        }

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

        $productInfo = Product::whereIn('id', $productIds)->get();

        $productquantities = [];
        foreach($reorderData as $index => $productItem){
            $productQuantity = $reorderData[$index]['productQuantity'] ?? 0;
            $productquantities[] = $productQuantity;
        }


        $data = [
            'title' => 'Purchase Reorder Invoice',
            'reorderId' => $reorderData[0]['reorderInvoiceId'],
            'date' => $date,
            'productQuantities' => $productquantities,
            'productNames' => $productInfo->pluck('name')->toArray(),
            'company' => AppSetting::first(),
            'body' => '',
            'name' => '',
            'email' => '',
            'password' => '',
            'companyName' => ''
        ];
        $email = Mail::to($receiverEmail)->send(new Sendmail($data));
            if (!$email) {
                throw new Exception("Email not sent");
            }

    }
}
