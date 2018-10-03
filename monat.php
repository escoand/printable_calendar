<!DOCTYPE html>
<html lang="de">
<head>
<meta charset="utf-8" />
<link rel="stylesheet" href="styles.css">
<style>
  td { min-width: 100px; }
  .head { padding-top: 20px; text-align: left; }
</style>
</head>
<body>
<table>
<?php
  include 'functions.php';
  $nextmonth = strtotime('+1 month');
  foreach($urls as &$url)
    $url[1] = true;
  cal_import();
  cal_month(strftime('%Y'), strftime('%m'));
  cal_month(strftime('%Y', $nextmonth), strftime('%m', $nextmonth));
?>
</table>
</body>
</html>