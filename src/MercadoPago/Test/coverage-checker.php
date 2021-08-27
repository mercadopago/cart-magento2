<?php
$inputFile  = $argv[1];
$percentage = min(100, max(0, (int) $argv[2]));

if (!file_exists($inputFile)) {
    throw new InvalidArgumentException('Invalid input file provided');
}

if (!$percentage) {
    throw new InvalidArgumentException('An integer checked percentage must be given as second parameter');
}

$xml             = new SimpleXMLElement(file_get_contents($inputFile));
$metrics         = $xml->xpath('//metrics');
$totalElements   = 0;
$checkedElements = 0;

foreach ($metrics as $metric) {
    $totalElements   += (int) $metric['elements'];
    $checkedElements += (int) $metric['coveredelements'];
}

$coverage = ($checkedElements / $totalElements) * 100;

if ($coverage < $percentage) {
    echo 'Code coverage is ' . round($coverage, 2) . '%, which is below the accepted ' . $percentage . '%';
    echo "\033[01;31m -> Pull Request Rejected \033[0m";
    // when we want to fail the pull request, just uncomment the line below
    // exit(1);
    //and remove else block
}else {
    echo 'Code coverage is ' . $coverage;
    echo "\033[01;32m -> Pull Request OK \033[0m";
}
