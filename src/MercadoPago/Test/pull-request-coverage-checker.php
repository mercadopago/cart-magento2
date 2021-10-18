<?php

$cloverFile       = $argv[1];
$percentage       = min(100, max(0, (int) $argv[2]));
$pullRequestFiles = [];

for ($i = 3; $i < count($argv); $i++) {
    $filename = str_replace('src/', '', $argv[$i]);
    $filename = str_replace('/', '\\', $filename);
    $filename = str_replace('.php', '', $filename);

    $pullRequestFiles[] = $filename;
}

if (!file_exists($cloverFile)) {
    throw new InvalidArgumentException('Invalid clover file provided');
}

if (!$percentage) {
    throw new InvalidArgumentException('An integer checked percentage must be given as second parameter');
}

if (count($pullRequestFiles) == 0) {
    print_r('Pull Request does not contain any php file to check code coverage');
    return;
}

$xml             = new SimpleXMLElement(file_get_contents($cloverFile));
$classes         = $xml->xpath('//class');
$totalElements   = 0;
$checkedElements = 0;

foreach ($classes as $class) {
    if (in_array($class['name'], $pullRequestFiles)) {
        $totalElements   += (int) $class->metrics['elements'];
        $checkedElements += (int) $class->metrics['coveredelements'];
    }
}

if ($totalElements == 0 || $checkedElements == 0) {
    throw new Exception('Pull Request does not contain tested php files to check code coverage');
}

$coverage = ($checkedElements / $totalElements) * 100;

if ($coverage >= $percentage) {
    print_r('Code coverage is ' . $coverage);
    print_r(' -> Pull Request OK');
    return;
}

print_r('Code coverage is ' . round($coverage, 2) . '%, which is below the accepted ' . $percentage . '%');
print_r(' -> Pull Request Rejected');

