<?php
function numberToWords($number): string
{
    $units = array('', 'one', 'two', 'three', 'four', 'five', 'six', 'seven', 'eight', 'nine');
    $teens = array('ten', 'eleven', 'twelve', 'thirteen', 'fourteen', 'fifteen', 'sixteen', 'seventeen', 'eighteen', 'nineteen');
    $tens = array('', '', 'twenty', 'thirty', 'forty', 'fifty', 'sixty', 'seventy', 'eighty', 'ninety');
    $thousands = array('', 'thousand', 'million', 'billion', 'trillion');

    // Special case for zero
    if ($number == 0) {
        return 'zero';
    }

    $words = array();
    $numArray = str_split(strrev((string)$number), 3);

    foreach ($numArray as $key => $value) {
        $chunk = (int)strrev($value);
        if ($chunk != 0) {
            $chunkWords = array();

            // Convert hundreds place
            if ($chunk >= 100) {
                $chunkWords[] = $units[$chunk / 100] . ' hundred';
                $chunk %= 100;
            }

            // Convert tens and ones place
            if ($chunk >= 20) {
                $chunkWords[] = $tens[$chunk / 10];
                $chunk %= 10;
            }

            if ($chunk >= 10) {
                $chunkWords[] = $teens[$chunk - 10];
                $chunk = 0; // We've already handled the last digit
            }

            if ($chunk > 0) {
                $chunkWords[] = $units[$chunk];
            }

            // Add the appropriate thousands unit
            if (!empty($chunkWords)) {
                $chunkWords[] = $thousands[$key];
            }

            // Combine the words in this chunk
            $words = array_merge($chunkWords, $words);
        }
    }

    // Combine all chunks' words and return the result
    return implode(' ', $words);
}

