<?php
$regex = '/^\s*([A-Z0-9\-]+)\s+(.+?)\s+(\d{1,2}\/\d{1,2}\/\d{4})\s+([\d,\.]+)\s+([\d,\.]+)(?:\s*[A-Z]{3})?\s+([A-Z_]+)\s+([A-Z0-9]+)\s+([\d,\.]+)(?:\s*[A-Z]{3})?/i';
$line2 = "EXP00075 Wooden Pallet, 800*600 09/12/2025 200 $247.50 PRODUCTION 00114 $49,500.00";
echo "\nLine 2 (With $):\n";
preg_match($regex, $line2, $m2);
print_r($m2);
