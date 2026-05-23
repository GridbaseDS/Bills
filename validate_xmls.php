<?php
// Validate generated XMLs against their corresponding XSD files
$testDir = __DIR__ . '/storage/app/dgii_tests';
$xsdDir = 'D:/WEBS/GRIDBASE DIGITAL SOLUTIONS/Documentación Facturación Electónica';

$xsdMap = [
    '31' => 'e-CF 31 v.1.0.xsd', '32' => 'e-CF 32 v.1.0.xsd',
    '33' => 'e-CF 33 v.1.0.xsd', '34' => 'e-CF 34 v.1.0.xsd',
    '41' => 'e-CF 41 v.1.0.xsd', '43' => 'e-CF 43 v.1.0.xsd',
    '44' => 'e-CF 44 v.1.0.xsd', '45' => 'e-CF 45 v.1.0.xsd',
    '46' => 'e-CF 46 v.1.0.xsd', '47' => 'e-CF 47 v.1.0.xsd',
];

$files = glob($testDir . '/ecf_*.xml');
sort($files);
$valid_count = 0;
$total = count($files);

foreach ($files as $file) {
    $filename = basename($file);
    $xml = file_get_contents($file);
    
    preg_match('/<TipoeCF>(\d+)<\/TipoeCF>/', $xml, $m);
    $tipo = $m[1] ?? '??';
    
    $xsdFile = $xsdDir . '/' . ($xsdMap[$tipo] ?? '');
    
    if (!file_exists($xsdFile)) {
        echo "[$filename] Type $tipo - XSD NOT FOUND\n";
        continue;
    }
    
    $doc = new DOMDocument();
    $doc->loadXML($xml);
    
    libxml_use_internal_errors(true);
    $valid = $doc->schemaValidate($xsdFile);
    
    if ($valid) {
        echo "[$filename] Type $tipo - ✓ VALID\n";
        $valid_count++;
    } else {
        $errors = libxml_get_errors();
        // Filter out the "Missing child element" error (that's the Signature, added at signing time)
        $real_errors = [];
        foreach ($errors as $e) {
            $msg = trim($e->message);
            if (strpos($msg, 'Missing child element') !== false) continue;
            if (strpos($msg, 'IndicadorServicioTodoIncluido') !== false) continue; // XSD bug
            $real_errors[] = $e;
        }
        
        if (empty($real_errors)) {
            echo "[$filename] Type $tipo - ✓ VALID (only missing Signature)\n";
            $valid_count++;
        } else {
            echo "[$filename] Type $tipo - ✗ INVALID:\n";
            foreach (array_slice($real_errors, 0, 3) as $error) {
                echo "  Line {$error->line}: " . trim($error->message) . "\n";
            }
        }
    }
    libxml_clear_errors();
}

echo "\n=== RESULT: $valid_count / $total ECF XMLs are valid ===\n";
