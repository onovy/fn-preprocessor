<?php

$dir = opendir('format');
while ($format = readdir($dir)) {
    $key = file_get_contents('format/' . $format);
    if (isset($formats[$key])) {
	$formats[$key]++;
    } else {
	$formats[$key] = 1;
    }
}
closedir($dir);

arsort($formats);
$show = 10;
foreach ($formats as $format=>$count) {
    if (!$show--) {
	break;
    }
    print '===== ' . $count . ' ====' . PHP_EOL;
    print $format;
    print PHP_EOL.PHP_EOL;
}

?>