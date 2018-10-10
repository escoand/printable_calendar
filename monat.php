<!DOCTYPE html>
<html lang="de">
<head>
<meta charset="utf-8" />
<link rel="stylesheet" href="styles.css" />
<link href="https://fonts.googleapis.com/css?family=Noto+Sans" rel="stylesheet" />
<title>Monatsplan &#8211; Luther­kirch­gemeinde</title>
</head>
<body>
<table class="month">
<?php
  include 'functions.php';
  $date = implode('-', array_keys($_GET)) . '-01';
  $thismonth = strtotime($date);
  $nextmonth = strtotime('+1 month', $thismonth);
  cal_import(idate('Y', $nextmonth));
  cal_month(idate('Y', $thismonth), idate('m', $thismonth));
  cal_month(idate('Y', $nextmonth), idate('m', $nextmonth));
?>
</table>
</body>
</html>
