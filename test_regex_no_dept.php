<?php
$regex = '/^\s*([A-Z0-9\-]+)\s+(.+?)\s+(\d{1,2}\/\d{1,2}\/\d{4})\s+([\d,\.]+)\s+\$?\s*([\d,\.]+)(?:\s*[A-Z]{3})?\s+([A-Z_]+)\s+([A-Z0-9]+)\s+\$?\s*([\d,\.]+)(?:\s*[A-Z]{3})?/i';
$line = "EXP00075                      ZLMX-PLT-00019, 30020358,    26/05/2026     350       228.0000                       00126      79,800.00 MXP";
echo "Line without department:\n";
preg_match($regex, $line, $m);
print_r($m);
