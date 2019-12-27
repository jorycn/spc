<?php
require_once __DIR__.'/init.php';
require_once SPC_PATH.'/SpcChineseCalendar.php';

$calendar = new SpcChineseCalendar();

// $result = $calendar->solar(2017, 5, 5); // 阳历
// $result = $calendar->lunar(2017, 4, 10); // 阴历
$result = $calendar->solar(2019, 12, 27, 14); // 阳历，带 $hour 参数

print_r($result);
