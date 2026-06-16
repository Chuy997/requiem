<?php
$regex = '/^\s*([A-Z0-9\-]+)\s+(.+?)\s+(\d{1,2}\/\d{1,2}\/\d{4})\s+([\d,\.]+)\s+\$?\s*([\d,\.]+)(?:\s*[A-Z]{3})?\s+([A-Z_]+)\s+([A-Z0-9]+)\s+\$?\s*([\d,\.]+)(?:\s*[A-Z]{3})?/i';
$line3 = "EXP00075 Wooden Pallet, 800*600 09/12/2025 200 $ 247.50 PRODUCTION 00114 $ 49,500.00";
echo "\nLine 3 (With $ and space):\n";
preg_match($regex, $line3, $m3);
print_r($m3);
