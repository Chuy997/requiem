<?php
$regex = '/^\s*([A-Z0-9\-]+)\s+(.+?)\s+(\d{1,2}\/\d{1,2}\/\d{4})\s+([\d,\.]+)\s+\$?\s*([\d,\.]+)(?:\s*[A-Z]{3})?\s+(?:([A-Z_]+)\s+)?([A-Z0-9]+)\s+\$?\s*([\d,\.]+)(?:\s*[A-Z]{3})?/i';
$line1 = "EXP00075 Wooden Pallet, 800*600 09/12/2025 200 247.50 PRODUCTION 00114 49,500.00";
$line2 = "EXP00075                      ZLMX-PLT-00019, 30020358,    26/05/2026     350       228.0000                       00126      79,800.00 MXP";

echo "Line 1 (With dept):\n";
preg_match($regex, $line1, $m1);
print_r($m1);

echo "\nLine 2 (No dept):\n";
preg_match($regex, $line2, $m2);
print_r($m2);
