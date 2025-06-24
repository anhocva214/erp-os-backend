<?php

namespace Database\Seeders;

use App\Models\Currency;
use Illuminate\Database\Seeder;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;

class CurrencySeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {

        $c = new Currency();
        $c->currencyName= "DOLLAR";
        $c->currencySymbol= "&#36;";
        $c->save();

        $c = new Currency();
        $c->currencyName= "EURO";
        $c->currencySymbol= "&#8364;";
        $c->save();

        $c = new Currency();
        $c->currencyName= "BDT";
        $c->currencySymbol= "&#2547;";
        $c->save();

        $c = new Currency();
        $c->currencyName= "POUND";
        $c->currencySymbol= "&#163;";
        $c->save();

        $c = new Currency();
        $c->currencyName= "RUPEE";
        $c->currencySymbol= "&#8377;";
        $c->save();

        $c = new Currency();
        $c->currencyName= "YEN";
        $c->currencySymbol= "&#165;";
        $c->save();

        $c = new Currency();
        $c->currencyName= "WON";
        $c->currencySymbol= "&#8361;";
        $c->save();

        $c = new Currency();
        $c->currencyName= "YUAN";
        $c->currencySymbol= "&#165;";
        $c->save();

        $c = new Currency();
        $c->currencyName= "PESO";
        $c->currencySymbol= "&#8369;";
        $c->save();

        $c = new Currency();
        $c->currencyName= "LIRA";
        $c->currencySymbol= "&#8356;";
        $c->save();

        $c = new Currency();
        $c->currencyName= "FRANC";
        $c->currencySymbol= "&#8355;";
        $c->save();

        $c = new Currency();
        $c->currencyName= "REAL";
        $c->currencySymbol= "&#8369;";
        $c->save();

        $c = new Currency();
        $c->currencyName= "RUBLE";
        $c->currencySymbol= "&#8381;";
        $c->save();

        $c = new Currency();
        $c->currencyName= "RINGGIT";
        $c->currencySymbol= "&#8369;";
        $c->save();




    }
}
