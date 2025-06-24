<?php

use Illuminate\Support\Str;

function takeUptoThreeDecimal($number): float
{
    return floatval(round((float) $number, 3));
}
