<!DOCTYPE html>
<html lang="de">
<head>
<meta charset="utf-8" />
<link rel="stylesheet" href="common.css" />
<link rel="stylesheet" href="events.css" />
<link href="https://fonts.googleapis.com/css?family=Noto+Sans" rel="stylesheet" />
<title>Monatsplan &#8211; Lutherkirchgemeinde</title>
</head>
<body class="month">
<table>
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
