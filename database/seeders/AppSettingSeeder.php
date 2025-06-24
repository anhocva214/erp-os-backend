<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use \App\Models\AppSetting;

class AppSettingSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $setting = new AppSetting();
        $setting->companyName = 'OS Inventory';
        $setting->dashboardType = 'inventory';
        $setting->tagLine = 'Manage your Inventory, Sales, Purchases etc';
        $setting->address = 'House: 139, Road: 13, Sectorr: 10, Uttara, Dhaka-1230';
        $setting->phone = '+880 18 2021 5555';
        $setting->email = 'solution@omega.ac';
        $setting->website = 'https://solution.omega.ac';
        $setting->footer = 'OS Inventory copyright by Omega Solution LLC';
        $setting->logo = 'os-inventory-logo.png';
        $setting->currencyId = 3;

        $setting->save();
    }
}
