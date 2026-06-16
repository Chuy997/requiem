<?php
$lines = [
    "EXP00075                     ZLMX-PKG-00004, 30035310,   26/05/2026     700        49.7500                                34,825.00 MXP",
    "EXP00075                       ZLMX-LBL-00030, SPU and     29/05/2026      75       19.9500 USD   PRODUCTION       00126      1,496.25 USD",
    "EXP00075              ZLMX-PLT-00019, 30020358,         9/12/2025     200       247.5000      PRODUCTION         00114       49,500.00 MXP"
];

$regex = '/^\s*([A-Z0-9\-]+)\s+(.+?)\s+(\d{1,2}\/\d{1,2}\/\d{4})\s+([\d,\.]+)\s+\$?\s*([\d,\.]+)(?:\s*[A-Z]{3})?\s+(?:([A-Z_]+)\s+)?(?:([A-Z0-9]+)\s+)?\$?\s*([\d,\.]+)(?:\s*[A-Z]{3})?/i';

foreach ($lines as $line) {
    if (preg_match($regex, $line, $matches)) {
        print_r($matches);
    } else {
        echo "No match for: $line\n";
    }
}
