<!DOCTYPE html>
<html lang="de">
<head>
<meta charset="utf-8" />
<link rel="stylesheet" href="common.css" />
<link rel="stylesheet" href="events.css" />
<link href="https://fonts.googleapis.com/css?family=Noto+Sans" rel="stylesheet" />
<title>Jahresplan &#8211; Lutherkirchgemeinde</title>
</head>
<body class="year">
<table>
<?php
  include 'functions.php';
  $year = implode('', array_keys($_GET));
  if(!is_numeric($year) || $year < 2000 || $year > 2100)
    $year = idate('Y');
  printf('<tr><th class="head" colspan="24">%s</th></tr>', $year);
  cal_import($year);
  cal_year($year);
?>
</table>
<?php
  cal_legend();
?>
</body>
</html>
