<?php

namespace App\Http\Controllers;

use App\MailStructure\MailStructure;
use App\Models\PurchaseReorderInvoice;
use App\Models\Quote;
use App\Models\Product;
use App\Models\SaleInvoice;
use App\Models\QuoteProduct;
use Exception;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class SendEmailController extends Controller
{

    protected MailStructure $MailStructure;

    public function __construct(MailStructure $MailStructure)
    {
        $this->MailStructure = $MailStructure;
    }

    public function sendEmail(Request $request): JsonResponse
    {
        if ($request->query('type') === 'saleinvoice') {
            try {

                $saleInvoice = SaleInvoice::with('customer', 'saleInvoiceProduct', 'saleInvoiceVat')->find($request->id);
                $receiverEmail = $saleInvoice->customer->email;
                $saleInvoiceProducts = $saleInvoice->saleInvoiceProduct;
                $saleInvoiceVats = $saleInvoice->saleInvoiceVat;


                $saleInvoiceProduct = [];
                foreach ($saleInvoiceProducts as $product) {
                    $saleInvoiceProduct[] = [
                        'productId' => $product->productId,
                        'productQuantity' => $product->productQuantity,
                        'productSalePrice' => $product->productSalePrice,
                    ];

                }
                $saleInvoiceVat = [];
                foreach ($saleInvoiceVats as $product) {
                    $saleInvoiceVat[] =
                        $product->id;

                }
                $converted = arrayKeysToCamelCase($saleInvoice->toArray());


                if ($request->input('receiverEmail')) {

                    MailSend($converted, $saleInvoiceProduct, $saleInvoiceVat, $request->input('receiverEmail'));
                } else {
                    MailSend($converted, $saleInvoiceProduct, $saleInvoiceVat, $receiverEmail);
                }
                return response()->json([
                    'message' => 'Email sent successfully'
                ], 200);
            } catch (Exception $e) {
                return response()->json([
                    'message' => 'Email sent failed '
                ], 500);
            }
        }
        else if ($request->query('type') === 'quote') {
            try {
                $quote = Quote::with('quoteOwner')->find($request->id);
                $receiverEmail = $quote->quoteOwner->email;

                $quoteProduct = QuoteProduct::where('quoteId', $request->id)->get();

                $quoteProducts = [];
                foreach ($quoteProduct as $product) {
                    $quoteProducts[] = [
                        "productId" => $product->productId];
                }


                $converted = arrayKeysToCamelCase($quote->toArray());
                if ($request->input('receiverEmail')) {
                    MailSend($converted, $quoteProducts, null, $request->input('receiverEmail'));
                } else {
                    MailSend($converted, $quoteProducts, null, $receiverEmail);
                }
                return response()->json([
                    'message' => 'Email sent successfully'
                ], 200);
            } catch (Exception $e) {
                return response()->json([
                    'message' => 'Email sent failed'
                ], 500);
            }

        }
        else if ($request->query('type') === 'purchaseReorderInvoice') {
            try {

                $reorderInvoice = PurchaseReorderInvoice::with('product')->where('reorderInvoiceId', $request->id)->get();
                $productIds = $reorderInvoice->pluck('product.id')->toArray();
                $receiverEmail = $request->receiverEmail;

                $converted = arrayKeysToCamelCase($reorderInvoice->toArray());
                MailForReorder($converted, $productIds, $receiverEmail);
                return response()->json([
                    'message' => 'Email sent successfully'
                ], 200);

            } catch (Exception $e) {
                return response()->json([
                    'message' => 'Email sent failed'
                ], 500);
            }

        }
        else {
            return response()->json([
                'error' => 'Invalid query'
            ], 500);
        }
    }

}
