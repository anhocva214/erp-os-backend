<?php

namespace Database\Seeders;

use App\Models\PaymentMethod;
use Illuminate\Database\Seeder;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;

class PaymentMethodSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        
        $paymentMethod = new PaymentMethod();
        $paymentMethod->subAccountId = 1;
        $paymentMethod->methodName = 'Demo Payment Method';
        $paymentMethod->logo = Null;
        $paymentMethod->ownerAccount = 'Demo Owner Account';
        $paymentMethod->instruction = 'Demo Instruction';
        $paymentMethod->save();

        
    }
}
