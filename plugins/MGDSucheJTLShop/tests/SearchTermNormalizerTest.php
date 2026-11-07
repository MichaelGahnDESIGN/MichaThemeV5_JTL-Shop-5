<?php

declare(strict_types=1);

require_once dirname(__DIR__) . '/src/Search/SearchTermNormalizer.php';

use Plugin\MGDSucheJTLShop\Search\SearchTermNormalizer;

$normalizer = new SearchTermNormalizer();
$cases = [
    '  Bio   Baumwolle  ' => 'bio baumwolle',
    'Größe &amp; Farbe' => 'größe & farbe',
    "\nMATERIAL\t" => 'material',
];

foreach ($cases as $input => $expected) {
    $actual = $normalizer->normalize($input);
    if ($actual !== $expected) {
        fwrite(STDERR, 'Normalisierung fehlgeschlagen: ' . $input . PHP_EOL);
        exit(1);
    }
}

echo "SearchTermNormalizer: OK\n";
