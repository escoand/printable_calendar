<!DOCTYPE html>
<html lang="de">
<head>
<meta charset="utf-8" />
<link rel="stylesheet" href="styles.css">
</head>
<body>
<table>
<?php
  include 'functions.php';
  cal_import();
  cal_year(2019);
?>
</table>
<?php
  cal_legend();
?>
</body>
</html>