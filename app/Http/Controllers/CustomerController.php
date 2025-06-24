<?php

namespace App\Http\Controllers;

use Exception;
use App\Models\Role;
use Firebase\JWT\JWT;
use App\Models\Customer;
use App\Models\AppSetting;
use App\Models\EmailConfig;

use App\Models\Transaction;
use Illuminate\Support\Str;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use App\Models\PasswordResetToken;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use App\MailStructure\MailStructure;
use App\Models\ReturnSaleInvoice;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Cookie;

class CustomerController extends Controller
{
    protected MailStructure $MailStructure;

    public function __construct(MailStructure $MailStructure)
    {
        $this->MailStructure = $MailStructure;
    }

    //generate random strings
    public function customerLogin(Request $request): jsonResponse
    {
        try {
            $loggedCustomer = json_decode($request->getContent(), true);

            if (!preg_match('/^[a-zA-Z0-9+_.-]+@[a-zA-Z0-9.-]+$/', $loggedCustomer['email'])) {
                return response()->json(['error' => 'Invalid Email!'], 400);
            }

            $customer = Customer::where('email', $loggedCustomer['email'])->first();
            // check authentication using email and password;
            if (!($customer && Hash::check($loggedCustomer['password'], $customer->password))) {
                return response()->json(['error' => 'username or password is incorrect'], 401);
            }

            $permissions = Role::with('RolePermission.permission')
                ->where('id', $customer['role']['id'])
                ->first();

            $token = array(
                "sub" => $customer->id,
                "roleId" => $customer->roleId,
                "role" => $permissions->name,
                "exp" => time() + 86400
            );

            $jwt = JWT::encode($token, env('JWT_SECRET'), 'HS256');

            unset($customer->password);
            Customer::where('email', $loggedCustomer['email'])->update([
                'isLogin' => 'true',
            ]);

            $customer->token = $jwt;
            $customer->profileImage = $customer->profileImage ? url('/') . '/customer-profileImage/' . $customer->profileImage : null;
            $converted = arrayKeysToCamelCase($customer->toArray());
            return response()->json($converted, 200);
        } catch (Exception $err) {
            return response()->json(['error' => $err->getMessage()], 500);
        }
    }

    public function logout(Request $request): JsonResponse
    {
        try {
            $user = Customer::findOrFail($request->id);
            $user->isLogin = 'false';
            $user->save();
            $cookie = Cookie::forget('refreshToken');

            return response()->json(['message' => 'Logout successfully'], 200)->withCookie($cookie);
        } catch (Exception $error) {
            return response()->json([
                'error' => 'An error occurred during logout. Please try again later.'
            ], 500);
        }
    }

    public function resetPassword(Request $request, $id): JsonResponse
    {
        try {
            $data = $request->attributes->get("data");
            if ($data['sub'] !== (int)$id) {
                return response()->json(['error' => 'You are not authorized to access this data.'], 401);
            }

            $customer = Customer::findOrFail($id);
            $checkingOldPassword = Hash::check($request->input('oldPassword'), $customer->password);

            if ($request->input('oldPassword') === $request->input('password')) {
                return response()->json(['error' => 'Old password and new password should not be same!'], 400);
            }

            if ($checkingOldPassword === false) {
                return response()->json(['error' => 'Old password does not match!'], 400);
            }

            $newHashedPassword = Hash::make($request->input('password'));

            $updatedPassword = Customer::where('id', $id)->update([
                'password' => $newHashedPassword,
            ]);

            if (!$updatedPassword) {
                return response()->json(['error' => 'Password Not Updated!'], 404);
            }
            return response()->json(['message' => 'password reset successfully'], 200);
        } catch (Exception $err) {
            return response()->json(['error' => $err->getMessage()], 500);
        }
    }

    public function requestForgetPassword(Request $request): JsonResponse
    {
        try {
            $customerEmail = $request->input('email');

            //check the email is not fake email using regex
            if (!preg_match('/^[a-zA-Z0-9+_.-]+@[a-zA-Z0-9.-]+$/', $customerEmail)) {
                return response()->json(['error' => 'Invalid Email!'], 400);
            }

            //validate email
            $validEmail = $request->validate([
                'email' => 'required|email',
            ]);

            if (!$validEmail) {
                return response()->json(['error' => 'Invalid Email!'], 400);
            }

            $customer = Customer::where('email', $customerEmail)->first();

            if (!$customer) {
                return response()->json(['error' => 'Customer Not Found!'], 404);
            }

            //company
            $companyName = AppSetting::first();
            $emailConfig = EmailConfig::first();

            //convert the email before @
            $email = explode('@', $customerEmail);

            $token = PasswordResetToken::Create(
                [
                    'userId' => $customer->id,
                    'token' => Str::random(60),
                    'experiresAt' => now()->addHours(2),
                ]
            );


            $forgetPassLink = env('APP_URL') . '/forget-password/' . $token->token;

            $mailData = [
                'title' => 'request forget password',
                'name' => $email[0],
                'resetLink' => $forgetPassLink,
                'expiryHours' => '2',
                'companyName' => $companyName->companyName,
            ];

            $emailSent = sendEmail($emailConfig, $customerEmail, $mailData);

            if ($emailSent === false) {
                return response()->json(['error' => 'Email Not Sent!'], 500);
            }

            return response()->json(['message' => 'Please check your mail']);
        } catch (Exception $err) {
            return response()->json(['error' => $err->getMessage()], 500);
        }
    }

    // reset Customer Password controller method;
    public function forgotPassword(Request $request): jsonResponse
    {
        try {

            $token = $request->input('token');
            $confirmPassword = $request->input('confirmPassword');
            $password = $request->input('password');

            if ($confirmPassword !== $password) {
                return response()->json(['error' => 'Password does not match!'], 400);
            }

            $token = PasswordResetToken::where('token', $token)->first();

            if (!$token) {
                return response()->json(['error' => 'Invalid Token!'], 404);
            }

            if ($token->experiresAt < now()) {
                return response()->json(['error' => 'Token Expired!'], 404);
            }

            $customer = Customer::where('id', $token->userId)->first();

            if (!$customer) {
                return response()->json(['error' => 'Customer Not Found!'], 404);
            }

            $newHashedPassword = Hash::make($password);

            $updatedPassword = Customer::where('id', $token->userId)->update([
                'password' => $newHashedPassword,
            ]);

            if (!$updatedPassword) {
                return response()->json(['error' => 'password not updated!'], 404);
            }

            $token->delete();

            return response()->json(['message' => 'password reset successfully'], 200);
        } catch (Exception $err) {
            return response()->json(['error' => $err->getMessage()], 500);
        }
    }

    public function createSingleCustomer(Request $request): jsonResponse
    {
        DB::beginTransaction();
        if ($request->query('query') === 'deletemany') {
            try {
                $ids = json_decode($request->getContent(), true);
                $deletedCustomer = Customer::destroy($ids);
                DB::commit();
                return response()->json($deletedCustomer, 200);
            } catch (Exception $err) {
                DB::rollBack();
                return response()->json(['error' => $err->getMessage()], 500);
            }
        } else if ($request->query('query') === 'createmany') {
            try {
                $customerData = json_decode($request->getContent(), true);

                //check if product already exists
                $customerData = collect($customerData)->map(function ($item) {
                    $customer = Customer::where('email', $item['email'])->first();
                    if ($customer) {
                        return null;
                    }
                    return $item;
                })->filter(function ($item) {
                    return $item !== null;
                })->toArray();

                //if all products already exists
                if (count($customerData) === 0) {
                    return response()->json(['error' => 'All Customer Email already exists.'], 500);
                }
                $createdCustomer = collect($customerData)->map(function ($item) {
                    $randomPassword = $this->makePassword(10);
                    $hashedPass = Hash::make($randomPassword);

                    return Customer::firstOrCreate([
                        'username' => $item['username'],
                        'email' => $item['email'] ?? null,
                        'phone' => $item['phone'],
                        'address' => $item['address'],
                        'password' => $hashedPass,
                    ]);
                });

                $createdCustomer->map(function ($item) {
                    unset($item->password);
                });
                DB::commit();
                return response()->json($createdCustomer, 201);
            } catch (Exception $err) {
                DB::rollBack();
                return response()->json(['error' => $err->getMessage()], 500);
            }
        } else {
            try {

                $randomPassword = $this->makePassword(10);
                $hashedPass = Hash::make($randomPassword);
                $customerData = json_decode($request->getContent(), true);

                if (isset($customerData['email'])) {
                    $customer = Customer::where('email', $customerData['email'])->first();
                    if ($customer) {
                        return response()->json(['error' => 'Customer email already exists.'], 500);
                    }
                    $companyName = AppSetting::first();
                    $emailConfig = EmailConfig::first();
                    //convert the email before @
                    $email = explode('@', $request->email);
                    $createdCustomer = Customer::create([
                        'username' => $request->input('username') ?? $email[0],
                        'email' => $request->input('email'),
                        'phone' => $request->input('phone') ?? null,
                        'address' => $request->input('address') ?? null,
                        'password' => $hashedPass
                    ]);

                    $mailData = [
                        'title' => "New Account",
                        "body" => $request->body,
                        "name" => $request->username,
                        "email" => $request->email,
                        "password" => $randomPassword,
                        "companyName" => $companyName->companyName,
                    ];

                    try {
                        $email = $this->MailStructure->NewAccount($request->email, $mailData);
                    } catch (Exception $err) {
                        DB::rollBack();
                        return response()->json(['error' => 'Email Not Sent!' . $err->getMessage(), ], 404);
                    }
                    unset($createdCustomer->password);
                    DB::commit();
                    return response()->json(['message' => 'Please check your mail', 'data' => $createdCustomer], 201);
                }

                $createdCustomer = Customer::create([
                    'username' => $request->input('username'),
                    'phone' => $request->input('phone'),
                    'address' => $request->input('address'),
                    'password' => $hashedPass,
                ]);
                unset($createdCustomer->password);
                DB::commit();
                return response()->json([$createdCustomer], 201);
            } catch (Exception $err) {
                DB::rollBack();
                return response()->json(['error' => $err->getMessage()], 500);
            }
        }
    }

    private function makePassword($length): string
    {
        $characters = "ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz0123456789";
        $password = "";

        for ($i = 0; $i < $length; $i++) {
            $password .= $characters[random_int(0, strlen($characters) - 1)];
        }
        return $password;
    }

    public function registerCustomer(Request $request): jsonResponse
    {
        try {
            $password = $request->input('password');
            $confirmPassword = $request->input('confirmPassword');
            $email = $request->input('email');

            if (!preg_match('/^[a-zA-Z0-9+_.-]+@[a-zA-Z0-9.-]+$/', $email)) {
                return response()->json(['error' => 'Invalid Email!'], 400);
            }

            if ($confirmPassword !== $password) {
                return response()->json(['error' => 'Password does not match!'], 400);
            }

            if (strlen($password) < 8 || !preg_match('/[A-Z]/', $password) || !preg_match('/[a-z]/', $password) || !preg_match('/[0-9]/', $password) || !preg_match('/[!@#$%^&*(),.?":{}|<>]/', $password)) {
                return response()->json(['error' => 'Password must be at least 8 characters long and include uppercase, lowercase, number, and special character.'], 400);
            }

            //check if email already exists
            $customer = Customer::where('email', $email)->first();
            if ($customer) {
                return response()->json(['error' => 'Email already exists.'], 500);
            }


            $hashedPass = Hash::make($password);
            $roleId = 3;
            $createdCustomer = Customer::create([
                'username' => htmlspecialchars($request->input('username')),
                'email' => $email,
                'password' => $hashedPass,
                'roleId' => $roleId,
            ]);

            unset($createdCustomer->password);
            $converted = arrayKeysToCamelCase($createdCustomer->toArray());
            return response()->json($converted, 201);
        } catch (Exception $err) {
            return response()->json(['error' => $err->getMessage()], 500);
        }
    }

    // get all the customer controller method;

    public function getAllCustomer(Request $request): jsonResponse
    {
        if ($request->query('query') === 'all') {
            try {
                // Optimized query with caching
                $cacheKey = 'customers_all_optimized';
                $allCustomer = Cache::remember($cacheKey, 600, function () { // 10 minutes cache
                    return Customer::orderBy('id', 'desc')
                        ->where('status', 'true')
                        ->select('id', 'username', 'email', 'phone', 'address', 'status')
                        ->get();
                });

                $converted = arrayKeysToCamelCase($allCustomer->toArray());
                return response()->json($converted, 200);
            } catch (Exception $err) {
                return response()->json(['error' => $err->getMessage()], 500);
            }
        } else if ($request->query('query') === 'info') {

            try {
                //aggregate query
                $customerInfo = Customer::where('status', 'true')
                    ->selectRaw('COUNT(id) as countedId')
                    ->first();

                $result = [
                    '_count' => [
                        'id' => $customerInfo->countedId,
                    ],
                ];
                return response()->json($result, 200);
            } catch (Exception $err) {
                return response()->json(['error' => $err->getMessage()], 500);
            }
        } else if ($request->query('query') === 'search') {
            try {
                $pagination = getPagination($request->query());

                $key = trim($request->query('key'));

                $getAllCustomer = Customer::where(function ($query) use ($key) {
                    return $query->orWhere('username', 'LIKE', '%' . $key . '%')
                        ->orWhere('phone', 'LIKE', '%' . $key . '%')
                        ->orWhere('email', 'LIKE', '%' . $key . '%')
                        ->orWhere('address', 'LIKE', '%' . $key . '%');
                })
                    ->with(['saleInvoice' => function ($query) {
                        $query->orderBy('id', 'desc');
                    }])
                    ->orderBy('id', 'desc')
                    ->skip($pagination['skip'])
                    ->take($pagination['limit'])
                    ->get();

                $customerCount = Customer::where(function ($query) use ($key) {
                    return $query->orWhere('username', 'LIKE', '%' . $key . '%')
                        ->orWhere('phone', 'LIKE', '%' . $key . '%')
                        ->orWhere('email', 'LIKE', '%' . $key . '%')
                        ->orWhere('address', 'LIKE', '%' . $key . '%');
                })
                    ->count();

                // secure data removing password form customer data;
                collect($getAllCustomer)->map(function ($item) {
                    unset($item->password);
                });

                $converted = arrayKeysToCamelCase($getAllCustomer->toArray());
                $finalResult = [
                    'getAllCustomer' => $converted,
                    'totalCustomer' => $customerCount,
                ];
                return response()->json($finalResult, 200);
            } catch (Exception $err) {
                return response()->json(['error' => $err->getMessage()], 500);
            }
        } else if ($request->query('query') === 'report') {
            try {
                $allCustomer = Customer::where('status', 'true')
                    ->with(['saleInvoice' => function ($query) {
                        $query->orderBy('created_at', 'desc');
                    },])
                    ->orderBy('created_at', 'desc')
                    ->get();

                //with total sale amount, total paid amount, total due amount
                $allCustomer = $allCustomer->map(function ($item) {

                    $allSaleInvoiceId = $item->saleInvoice->map(function ($item) {
                        return $item->id;
                    });

                    $totalAmount = Transaction::where('type', 'sale')
                        ->whereIn('relatedId', $allSaleInvoiceId)
                        ->where(function ($query) {
                            $query->where('debitId', 4);
                        })
                        ->get();

                    // transaction of the paidAmount
                    $totalPaidAmount = Transaction::where('type', 'sale')
                        ->whereIn('relatedId', $allSaleInvoiceId)
                        ->where(function ($query) {
                            $query->orWhere('creditId', 4);
                        })
                        ->get();

                    // transaction of the total amount
                    $totalAmountOfReturn = Transaction::where('type', 'sale_return')
                        ->whereIn('relatedId', $allSaleInvoiceId)
                        ->where(function ($query) {
                            $query->where('creditId', 4);
                        })
                        ->get();

                    // transaction of the total instant return
                    $totalInstantReturnAmount = Transaction::where('type', 'sale_return')
                        ->whereIn('relatedId', $allSaleInvoiceId)
                        ->where(function ($query) {
                            $query->where('debitId', 4);
                        })
                        ->get();

                    // calculate grand total due amount
                    $totalDueAmount = (($totalAmount->sum('amount') - $totalAmountOfReturn->sum('amount')) - $totalPaidAmount->sum('amount')) + $totalInstantReturnAmount->sum('amount');

                    $totalAmount = $totalAmount->sum('amount');
                    $totalPaidAmount = $totalPaidAmount->sum('amount');
                    $totalReturnAmount = $totalAmountOfReturn->sum('amount');
                    $instantPaidReturnAmount = $totalInstantReturnAmount->sum('amount');
                    $dueAmount = $totalDueAmount;

                    // include dueAmount in singleSupplier
                    $item->totalAmount = takeUptoThreeDecimal((float)$totalAmount) ?? 0;
                    $item->totalPaidAmount = takeUptoThreeDecimal((float)$totalPaidAmount) ?? 0;
                    $item->totalReturnAmount = takeUptoThreeDecimal((float)$totalReturnAmount) ?? 0;
                    $item->instantPaidReturnAmount = takeUptoThreeDecimal((float)$instantPaidReturnAmount) ?? 0;
                    $item->dueAmount = takeUptoThreeDecimal((float)$dueAmount) ?? 0;

                    return $item;
                });

                $grandData = [
                    'grandTotalAmount' => $allCustomer->sum('totalAmount'),
                    'grandTotalPaidAmount' => $allCustomer->sum('totalPaidAmount'),
                    'grandTotalReturnAmount' => $allCustomer->sum('totalReturnAmount'),
                    'grandInstantPaidReturnAmount' => $allCustomer->sum('instantPaidReturnAmount'),
                    'grandDueAmount' => $allCustomer->sum('dueAmount'),
                ];

                $converted = arrayKeysToCamelCase($allCustomer->toArray());

                $finalResult = [
                    'grandData' => $grandData,
                    'allCustomer' => $converted,
                ];

                return response()->json($finalResult, 200);
            } catch (Exception $err) {
                return response()->json(['error' => $err->getMessage()], 500);
            }
        } else if ($request->query()) {
            try {
                $pagination = getPagination($request->query());

                $getAllCustomer = Customer::with(['saleInvoice' => function ($query) {
                    $query->orderBy('id', 'desc');
                }])
                    ->when($request->query('status'), function ($query) use ($request) {
                        return $query->whereIn('status', explode(',', $request->query('status')));
                    })
                    ->orderBy('id', 'desc')
                    ->skip($pagination['skip'])
                    ->take($pagination['limit'])
                    ->get();

                $customerCount = Customer::when($request->query('status'), function ($query) use ($request) {
                    return $query->whereIn('status', explode(',', $request->query('status')));
                })
                    ->count();

                // secure data removing password form customer data;
                collect($getAllCustomer)->map(function ($item) {
                    unset($item->password);
                });

                $converted = arrayKeysToCamelCase($getAllCustomer->toArray());
                $finalResult = [
                    'getAllCustomer' => $converted,
                    'totalCustomer' => $customerCount,
                ];
                return response()->json($finalResult, 200);
            } catch (Exception $err) {
                return response()->json(['error' => $err->getMessage()], 500);
            }
        } else {
            return response()->json(['error' => 'Invalid query!'], 400);
        }
    }


    public function getProfile(Request $request): jsonResponse
    {
        try {
            $data = $request->attributes->get("data");

            if ($data['role'] === 'customer') {
                $customer = Customer::where('id', $data['sub'])->first();

                if (!$customer) {
                    return response()->json(['error' => 'Customer Not Found!'], 404);
                }

                unset($customer->password);
                if ($customer->googleId) {
                    if (str_contains($customer->profileImage, 'googleusercontent')) {
                        $customer->profileImage = $customer->profileImage;
                    } else {
                        $customer->profileImage = $customer->profileImage ? url('/') . '/customer-profileImage/' . $customer->profileImage : null;
                    }
                } else {
                    $customer->profileImage = $customer->profileImage ? url('/') . '/customer-profileImage/' . $customer->profileImage : null;
                }
                $converted = arrayKeysToCamelCase($customer->toArray());
                return response()->json($converted, 200);
            }
            return response()->json(['error' => 'You are not authorized to access this data.'], 401);
        } catch (Exception $err) {
            return response()->json(['error' => $err->getMessage()], 500);
        }
    }


    public function profileUpdate(Request $request): jsonResponse
    {
        try {
            $data = $request->attributes->get("data");
            $customerData = json_decode($request->getContent(), true);

            if ($data['role'] === 'customer') {
                $customer = Customer::where('id', $data['sub'])->first();
                $updatedCustomer = Customer::where('id', $data['sub'])->update([
                    'username' => $customerData['username'] ?? $customer->username,
                    'phone' => $customerData['phone'] ?? $customer->phone,
                    'address' => $customerData['address'] ?? $customer->address,
                ]);

                if (!$updatedCustomer) {
                    return response()->json(['error' => 'Customer Not Updated!'], 404);
                }

                return response()->json(['message' => 'Customer Updated SuccessFully'], 200);
            }
            return response()->json(['error' => 'You are not authorized to access this data.'], 401);
        } catch (Exception $err) {
            return response()->json(['error' => $err->getMessage()], 500);
        }
    }

    // get a single customer data controller method;
    public function getSingleCustomer(Request $request, $customerId): jsonResponse
    {
        try {

            $data = $request->attributes->get('data');

            if ($data['role'] === "customer" && $data['sub'] != $customerId) {
                return response()->json([
                    "error" => "You are not authorized to access this route",
                ], 403);
            }

            $singleCustomer = Customer::where('id', $customerId)
                ->with(['saleInvoice.saleInvoiceProduct', 'saleInvoice' => function ($query) {
                    $query->orderBy('created_at', 'desc');
                }])
                ->first();

            if (!$singleCustomer) {
                return response()->json(['error' => 'No customer found!'], 404);
            }
            // to secure data removing password form customer data;
            unset($singleCustomer->password);

            //get returnSaleInvoice nested by customerId from customer table
            $customersAllInvoice = Customer::where('id', (int)$customerId)
                ->with(['saleInvoice' => function ($query) {
                    $query->orderBy('created_at', 'desc');
                }])
                ->first();

            // get all saleInvoice of a customer
            $allSaleInvoiceId = $customersAllInvoice->saleInvoice->map(function ($item) {
                return $item->id;
            });

            // get all returnSaleInvoice of a customer
            $allReturnSaleInvoiceId = $customersAllInvoice->saleInvoice->flatMap(function ($item) {
                return !empty($item->returnSaleInvoice) ? $item->returnSaleInvoice : [];
            });


            $totalAmount = Transaction::where('type', 'sale')
                ->whereIn('relatedId', $allSaleInvoiceId)
                ->where(function ($query) {
                    $query->where('debitId', 4);
                })
                ->get();

            // transaction of the paidAmount
            $totalPaidAmount = Transaction::where('type', 'sale')
                ->whereIn('relatedId', $allSaleInvoiceId)
                ->where(function ($query) {
                    $query->orWhere('creditId', 4);
                })
                ->get();

            // transaction of the total amount
            $totalAmountOfReturn = Transaction::where('type', 'sale_return')
                ->whereIn('relatedId', $allSaleInvoiceId)
                ->where(function ($query) {
                    $query->where('creditId', 4);
                })
                ->get();

            // transaction of the total instant return
            $totalInstantReturnAmount = Transaction::where('type', 'sale_return')
                ->whereIn('relatedId', $allSaleInvoiceId)
                ->where(function ($query) {
                    $query->where('debitId', 4);
                })
                ->get();

            //get all transactions related to purchaseInvoiceId
            $allTransaction = Transaction::whereIn('type', ["sale", "sale_return"])
                ->whereIn('relatedId', $allSaleInvoiceId)
                ->with('debit:id,name', 'credit:id,name')
                ->orderBy('id', 'desc')
                ->get();

            //get all return sale invoice
            $allReturnSaleInvoice = ReturnSaleInvoice::whereIn('saleInvoiceId', $allSaleInvoiceId)
                ->orderBy('created_at', 'desc')
                ->with('returnSaleInvoiceProduct')
                ->get();

            // calculate grand total due amount
            $totalDueAmount = (($totalAmount->sum('amount') - $totalAmountOfReturn->sum('amount')) - $totalPaidAmount->sum('amount')) + $totalInstantReturnAmount->sum('amount');

            // include dynamic transaction data in singleSupplier
            $singleCustomer->totalAmount = takeUptoThreeDecimal((float)$totalAmount->sum('amount')) ?? 0;
            $singleCustomer->totalPaidAmount = takeUptoThreeDecimal((float)$totalPaidAmount->sum('amount')) ?? 0;
            $singleCustomer->totalReturnAmount = takeUptoThreeDecimal((float)$totalAmountOfReturn->sum('amount')) ?? 0;
            $singleCustomer->instantPaidReturnAmount = takeUptoThreeDecimal((float)$totalInstantReturnAmount->sum('amount')) ?? 0;
            $singleCustomer->dueAmount = takeUptoThreeDecimal((float)$totalDueAmount) ?? 0;
            $singleCustomer->totalSaleInvoice = $allSaleInvoiceId->count() ?? 0;
            $singleCustomer->totalReturnSaleInvoice = $allReturnSaleInvoiceId->count() ?? 0;
            $singleCustomer->allTransaction = arrayKeysToCamelCase($allTransaction->toArray());
            $singleCustomer->returnSaleInvoice = arrayKeysToCamelCase($allReturnSaleInvoice->toArray());


            // ===  modify each sale invoice of customer with dynamic transaction data === //
            $singleCustomer->saleInvoice->map(function ($item) use ($totalAmount, $totalPaidAmount, $totalAmountOfReturn, $totalInstantReturnAmount) {

                $totalAmount = $totalAmount->filter(function ($trans) use ($item) {
                    return ($trans->relatedId === $item->id && $trans->type === 'sale' && $trans->debitId === 4);
                })->reduce(function ($acc, $current) {
                    return $acc + $current->amount;
                }, 0);

                $totalPaid = $totalPaidAmount->filter(function ($trans) use ($item) {
                    return ($trans->relatedId === $item->id && $trans->type === 'sale' && $trans->creditId === 4);
                })->reduce(function ($acc, $current) {
                    return $acc + $current->amount;
                }, 0);

                $totalReturnAmount = $totalAmountOfReturn->filter(function ($trans) use ($item) {
                    return ($trans->relatedId === $item->id && $trans->type === 'sale_return' && $trans->creditId === 4);
                })->reduce(function ($acc, $current) {
                    return $acc + $current->amount;
                }, 0);

                $instantPaidReturnAmount = $totalInstantReturnAmount->filter(function ($trans) use ($item) {
                    return ($trans->relatedId === $item->id && $trans->type === 'sale_return' && $trans->debitId === 4);
                })->reduce(function ($acc, $current) {
                    return $acc + $current->amount;
                }, 0);

                $totalDueAmount = (($totalAmount - $totalReturnAmount) - $totalPaid) + $instantPaidReturnAmount;

                $item->paidAmount = takeUptoThreeDecimal($totalPaid);
                $item->instantPaidReturnAmount = takeUptoThreeDecimal($instantPaidReturnAmount);
                $item->dueAmount = takeUptoThreeDecimal($totalDueAmount);
                $item->returnAmount = takeUptoThreeDecimal($totalReturnAmount);

                return $item;
            });
            //profit takeUpToThreeDecimal
            $singleCustomer->saleInvoice->map(function ($item) {
                $item->profit = takeUptoThreeDecimal($item->profit);
                return $item;
            });

            $converted = arrayKeysToCamelCase($singleCustomer->toArray());
            return response()->json($converted, 200);
        } catch (Exception $err) {
            return response()->json(['error' => $err->getMessage()], 500);
        }
    }

    // update a single customer data controller method;
    public function updateSingleCustomer(Request $request, $id): jsonResponse
    {
        try {
            $file_paths = $request->file_paths;
            if (isset($request['password'])) {
                unset($request['password']);
                return response()->json(['message' => 'password cannot be updated!'], 400);
            }

            unset($request['resetPassword']);

            $customer = Customer::where('id', $id)->first();
            $updatedCustomer = Customer::where('id', $id)->update([
                'profileImage' => $file_paths[0] ?? $customer->profileImage,
                'username' => $request->username ?? $customer->username,
                'email' => $request->email ?? $customer->email,
                'firstName' => $request->firstName ?? $customer->firstName,
                'lastName' => $request->lastName ?? $customer->lastName,
                'phone' => $request->phone ?? $customer->phone,
                'address' => $request->address ?? $customer->address,
            ]);


            if (!$updatedCustomer) {
                return response()->json(['error' => 'Customer Not Updated!'], 404);
            }

            return response()->json(['message' => 'Customer Updated SuccessFully'], 200);
        } catch (Exception $err) {
            return response()->json(['error' => $err->getMessage()], 500);
        }
    }

    // delete a single customer data controller method
    public function deleteSingleCustomer(Request $request, $id): jsonResponse
    {
        try {

            $deleted = Customer::where('id', (int)$id)->first();

            if (!$deleted) {
                return response()->json(['error' => 'Customer Not Found!'], 404);
            }

            $deleted->status = $request->status;
            $deleted->save();

            return response()->json(["message" => "Customer Deleted SuccessFull"], 200);
        } catch (Exception $err) {
            return response()->json(['error' => $err->getMessage()], 500);
        }
    }

    /**
     * Fast API for customers with minimal data and aggressive caching
     */
    public function getAllCustomerFast(Request $request): JsonResponse
    {
        try {
            if ($request->query('query') === 'all') {
                $cacheKey = 'customers_fast_all';
                $customers = Cache::remember($cacheKey, 3600, function () { // 1 hour cache
                    return Customer::select('id', 'username', 'email', 'phone', 'status')
                        ->where('status', 'true')
                        ->orderBy('id', 'desc')
                        ->get();
                });

                return response()->json([
                    'customers' => $customers,
                    'count' => $customers->count(),
                    'cached' => true,
                    'fast' => true
                ], 200);
            }

            if ($request->query('query') === 'info') {
                $cacheKey = 'customers_info_fast';
                $result = Cache::remember($cacheKey, 1800, function () { // 30 minutes cache
                    $count = Customer::where('status', 'true')->count();
                    return [
                        '_count' => ['id' => $count],
                        'cached' => true,
                        'fast' => true
                    ];
                });

                return response()->json($result, 200);
            }

            return response()->json(['error' => 'Invalid query parameter'], 400);
        } catch (Exception $err) {
            return response()->json(['error' => 'An error occurred: ' . $err->getMessage()], 500);
        }
    }
}
