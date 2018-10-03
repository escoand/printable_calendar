<?php
#ini_set('display_errors', 1);
#ini_set('display_startup_errors', 1);
#error_reporting(E_ALL);

$urls = array(
  array('https://calendar.google.com/calendar/ical/cj17aoakb3pl0o2g53n0haoau0%40group.calendar.google.com/public/basic.ics', false), # feiertage
  array('https://calendar.google.com/calendar/ical/11tbjm5vddo9a7h4330t2hhqic%40group.calendar.google.com/public/basic.ics', true), # 2019
);
$default_color = 'bg-green';
$colors = array(
  '#allianz' => 'bg-orange',
  '#sonder' => 'bg-blue',
  '#kinder' => 'bg-red',
  '#advent' => 'bg-violet',
  'www.schulferien.org' => 'bg-gray',
);

$dates = array();
$marks = array();
setlocale(LC_ALL, 'de_DE');

function cal_import() {
  global $urls, $dates, $marks;
  $separator = "\r\n";

  foreach($urls as $url) {
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url[0]);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
    curl_setopt($ch, CURLOPT_FOLLOWLOCATION, 1);

    $content = curl_exec($ch);
    $line = strtok($content, $separator);
    while($line !== false) {

      if($line === 'BEGIN:VEVENT')
        $item = array();
      elseif(substr($line, 0, 8) === 'DTSTART:')
        $item['start'] = substr($line, 8);
      elseif(substr($line, 0, 19) === 'DTSTART;VALUE=DATE:')
        $item['start'] = substr($line, 19);
      elseif(substr($line, 0, 6) === 'DTEND:')
        $item['end'] = substr($line, 6);
      elseif(substr($line, 0, 17) === 'DTEND;VALUE=DATE:')
        $item['end'] = substr($line, 17);
      elseif(substr($line, 0, 6) === 'RRULE:') {
        foreach(explode(';', substr($line, 6)) as $elem) {
          if(substr($elem, 0, 5) === 'FREQ=')
            $item['freq'] = substr($elem, 5);
          elseif(substr($elem, 0, 6) === 'COUNT=')
            $item['count'] = substr($elem, 6);
          elseif(substr($elem, 0, 9) === 'INTERVAL=')
            $item['interval'] = substr($elem, 9);
          elseif(substr($elem, 0, 8) === 'BYMONTH=')
            $item['bymonth'] = substr($elem, 8);
          elseif(substr($elem, 0, 6) === 'BYDAY=')
            $item['byday'] = substr($elem, 6);
        }
      } elseif(substr($line, 0, 8) === 'SUMMARY:')
        $item['summary'] = substr($line, 8);
      elseif(substr($line, 0, 12) === 'DESCRIPTION:' && strlen($line) > 12)
        $item['description'] = substr($line, 12);
      elseif($line === 'END:VEVENT' && array_key_exists('start', $item) && array_key_exists('end', $item)) {

        # oneday
        if(substr($item['start'], 0, 8) === substr($item['end'], 0, 8)) {
          $date = substr($item['start'], 0, 8);
          if(!array_key_exists($date, $dates))
            $dates[$date] = array();
          if(strlen($item['start']) > 8)
            $item['start'] = substr($item['start'], 9, 2) . ':' . substr($item['start'], 11, 2);
          else
            $item['start'] = '';
          if($url[1])
            $dates[$date][] = $item;
          else
            $marks[$i] = true;
        }

        # range
        elseif(substr($item['start'], 0, 8) < substr($item['end'], 0, 8)) {
          for($i = substr($item['start'], 0, 8); $i < substr($item['end'], 0, 8); $i++) {
            $item['start'] = '';
            if($url[1])
              $dates[$i][] = $item;
            else
              $marks[$i] = true;
          }
        }
      }

      $line = strtok($separator);
    }
    curl_close($ch);
  }
}

function cal_color($object) {
  global $default_color, $colors;

  # color of date
  if(is_integer($object)) {
    $weekday = idate('w', $object);
    return ($weekday === 6 || $weekday === 0) ? 'weekend' : '';
  }

  # color of item
  elseif(is_array($object) && array_key_exists('description', $object)) {
    foreach($colors as $key => $value) {
      if(strpos($object['description'], $key) !== false) {
        return $value;
      }
    }
  }

  return $default_color;
}

function cal_month($year, $month) {
  global $dates, $marks;
  $year = intval($year);
  $month = intval($month);

  $firstday = mktime(0, 0, 0, $month, 1, $year);
  $offset = 1 - strftime('%u', $firstday);
  print '<tr>';
  printf('<th colspan="7" class="head">%s</th>', strftime('%B %Y', $firstday));
  print '</tr><tr>';
  for($day = 1 + $offset; $day <= 31; $day++) {
    $date = mktime(0, 0, 0, $month, $day, $year);
    $id = strftime('%Y%m%d', $date);
    $class = cal_color($date);

    # day exists
    if(idate('m', $date) === $month) {

      # new week
      if(idate('w', $date) === 1 && idate('d', $date) !== 1)
        print '</tr><tr>';

      # mark day
      $mark = array_key_exists($id, $marks) ? 'mark' : '';
          
      printf('<th class="day %s %s">%s</th>', $mark, $class, substr(strftime('%d %a', $date), 0, 5));
      printf('<td class="events %s" width="100"><div>', $class);
      if(idate('w', $date) === 1)
        print strftime('<span class="week">%V</span>', $date);
      if(array_key_exists($id, $dates)) {
        foreach($dates[$id] as $item) {

          # event
          printf('<div class="event %s" title="%s"><i>%s</i> %s</div>',
            cal_color($item), array_key_exists('description', $item) ? $item['description'] : '', $item['start'], $item['summary']);
        }
      }
      print "</div></td>";
    }

    # not exists
    else {
      print '<td colspan="2"></td>';
    }

  }
  print "</tr>";
}

function cal_year($year) {
  global $dates, $marks;

  for($day = 0; $day <= 31 + 6; $day++) {
    print '<tr>';

    for($month = 1; $month <= 12; $month++) {
      $firstday = mktime(0, 0, 0, $month, 1, $year);
      $offset = 1 - strftime('%u', $firstday);
      $date = mktime(0, 0, 0, $month, $day + $offset, $year);
      $id = strftime('%Y%m%d', $date);
      $class = cal_color($date);

      # header
      if($day === 0) {
        print utf8_encode(strftime('<th colspan="2" class="head">%B</th>', $firstday));
      }

      # day exists
      elseif(idate('m', $date) === $month) {

        # mark day
        $mark = array_key_exists($id, $marks) ? 'mark' : '';
            
        printf('<th class="day %s %s">%s</th>', $mark, $class, substr(strftime('%d %a', $date), 0, 5));
        printf('<td class="events %s"><div>', $class);
        if(idate('w', $date) === 1)
          print strftime('<span class="week">%V</span>', $date);
        if(array_key_exists($id, $dates)) {
          foreach($dates[$id] as $item) {
            printf('<div class="event %s" title="%s"><i>%s</i> %s</div>',
              cal_color($item), array_key_exists('description', $item) ? $item['description'] : '', $item['start'], $item['summary']);
          }
        }
        print "</div></td>";
      }

      # not exists
      else {
        print '<td colspan="2"></td>';
      }

    }
    print "</tr>";
  }
}

function cal_legend() {
  global $colors, $default_color;

  printf('<small><b>Termine der Lutherkirchgemeinde (Stand: %s)</b>; F&auml;rbung anhand Termin-Beschreibung:',
    strftime('%d.%m.%Y'));
  printf(' <span class="%s">&nbsp;Standard&nbsp;</span>', $default_color);
  foreach($colors as $string => $color)
    printf(' <span class="%s">&nbsp;%s&nbsp;</span>', $color, $string);
  print '</small>';
}