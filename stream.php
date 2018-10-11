<!DOCTYPE html>
<html lang="de">
<head>
<meta charset="utf-8" />
<link rel="stylesheet" href="common.css" />
<link rel="stylesheet" href="events.css" />
<link href="https://fonts.googleapis.com/css?family=Noto+Sans" rel="stylesheet" />
<title>Terminplan &#8211; Lutherkirchgemeinde</title>
</head>
<body class="month">
<table>
<?php
  include 'functions.php';
  cal_import(idate('Y'));
  cal_stream();
?>
</table>
</body>
</html>
