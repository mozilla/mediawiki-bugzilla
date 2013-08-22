<?php

# This Source Code Form is subject to the terms of the Mozilla Public
# License, v. 2.0. If a copy of the MPL was not distributed with this
# file, You can obtain one at http://mozilla.org/MPL/2.0/.

function gChartExtendedEncode($arrVals, $maxVal) {
    // Same as simple encoding, but for extended encoding.
    $EXTENDED_MAP=
    'ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz0123456789-.';
    $EXTENDED_MAP_LENGTH = strlen($EXTENDED_MAP);
    $chartData = '';

    for($i = 0, $len = count($arrVals); $i < $len; $i++) {
        // In case the array vals were translated to strings.
        $numericVal = $arrVals[$i];
        // Scale the value to maxVal.
        $scaledVal = floor($EXTENDED_MAP_LENGTH *
        $EXTENDED_MAP_LENGTH * $numericVal / $maxVal);

        if($scaledVal > ($EXTENDED_MAP_LENGTH * $EXTENDED_MAP_LENGTH) - 1) {
            $chartData .= "..";
        }elseif($scaledVal < 0) {
            $chartData .= '__';
        } else {
            // Calculate first and second digits and add them to the output.
            $quotient = floor($scaledVal / $EXTENDED_MAP_LENGTH);
            $remainder = $scaledVal - $EXTENDED_MAP_LENGTH * $quotient;
            $chartData .= $EXTENDED_MAP[$quotient] . $EXTENDED_MAP[$remainder];
        }
    }

    return $chartData;
}
