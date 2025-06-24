<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\Account;

class AccountSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $account = new Account();
        $account->name = 'Asset';
        $account->type = 'Asset';
        $account->save();

        $account = new Account();
        $account->name = 'Liability';
        $account->type = 'Liability';
        $account->save();

        $account = new Account();
        $account->name = 'Equity';
        $account->type = 'Equity';
        $account->save();

        $account = new Account();
        $account->name = 'Withdrawal';
        $account->type = 'Equity';
        $account->save();

        $account = new Account();
        $account->name = 'Revenue';
        $account->type = 'Equity';
        $account->save();

        $account = new Account();
        $account->name = 'Expense';
        $account->type = 'Equity';
        $account->save();
    }
}
