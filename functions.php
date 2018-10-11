<?php
#ini_set('display_errors', 1);
#ini_set('display_startup_errors', 1);
#error_reporting(E_ALL);

require_once('zapcallib/zapcallib.php');

$urls = array(
  array('http://cloud.ec-hasslau.de/remote.php/dav/public-calendars/fdsHQXEQgZRx2df4?export', false), # feiertage
  array('http://cloud.ec-hasslau.de/remote.php/dav/public-calendars/CSmzMCQMm7eeZaqF?export', true), # 2019
);

$dates = array();
$marks = array();
setlocale(LC_ALL, 'de_DE');

function cal_import($year = null) {
  global $urls, $dates, $marks;
  $max = mktime(0, 0, 0, 1, 1, $year + 1);
  $separator = "\r\n";

  foreach($urls as $url) {
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url[0]);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
    curl_setopt($ch, CURLOPT_FOLLOWLOCATION, 1);

    $data = new ZCiCal(curl_exec($ch));
    curl_close($ch);

    $cancelled = array();
    $event = $data->getFirstEvent();
    while($event !== null) {
      $min = strtotime($event->data['DTSTART']->value[0]);
      foreach(cal_dates($event->data, $min, $max) as $date) {
        $id = $date; #strftime('%Y%m%d', $event->data['DTSTART']->value[0]);
        if($url[1] === true) {
          if($event->data['STATUS']->value[0] === 'CANCELLED') {
            $cancelled[$id][] = $event->data['UID']->value[0];
            continue;
          }
          $tz = $event->data['DTSTART']->getParameters()['tzid'];
          date_default_timezone_set($tz ? $tz : 'Europe/Berlin');
          $dates[$id][$event->data['UID']->value[0]] = array(
             'start' => idate('H', $min) > 0 ? date('H:i', $min) : '',
             'summary' => $event->data['SUMMARY']->value[0],
             'description' => $event->data['DESCRIPTION']->value[0] . ' (' . $event->data['UID']->value[0] . ')',
             'location' => $event->data['LOCATION']->value[0],
          );
          uasort($dates[$id], 'cal_sort');
        } else
          $marks[$id] = true;
      }
      $event = $data->getNextEvent($event);
    }

    # cancelled
    foreach($cancelled as $date => $ids)
      foreach($ids as $id)
        unset($dates[$date][$id]);
  }
}

function cal_dates($event, $min = 0, $max = null) {
  $dates = array();

  if(!array_key_exists('RRULE', $event)) {
    $date = substr($event['DTSTART']->value[0], 0, 8);
    $end = substr($event['DTEND']->value[0], 0, 8);
    do {
      $dates[] = $date++;
     } while($date < $end);
  } else {
    $exdates = array();
    foreach($event['EXDATE']->value as $date)
      $exdates[] = ZDateHelper::fromiCaltoUnixDateTime($date) - 60 * 60;
    $rrule = new ZCRecurringDate(
      $event['RRULE']->value[0],
      $min,
      $exdates
    );
    foreach($rrule->getDates($max) as $date)
      $dates[] = strftime('%Y%m%d', $date);
  }

  return $dates;
}
  
function cal_color($object) {

  # color of date
  if(is_integer($object)) {
    $weekday = idate('w', $object);
    return ($weekday === 6 || $weekday === 0) ? 'weekend' : '';
  }

  # color of item
  elseif(is_array($object) && array_key_exists('description', $object)) {
    preg_match_all('/#[a-zA-Z0-9]+/', $object['description'], $matches);
    return implode(' ', $matches[0]);
  }

  return '';
}

function cal_sort($event1, $event2) {
  return strcmp($event1['start'], $event2['start']);
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
            printf('<div class="event %s" title="%s"><span class="meta">%s %s</span>%s</div>',
              cal_color($item), array_key_exists('description', $item) ? $item['description'] : '', $item['start'], $item['location'], $item['summary']);
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

function cal_month($year, $month) {
  global $dates, $marks;
  $year = intval($year);
  $month = intval($month);
  $today = strftime('%Y%m%d');

  $firstday = mktime(0, 0, 0, $month, 1, $year);
  $offset = 1 - strftime('%u', $firstday);
  print '<tr>';
  printf('<th colspan="7" class="head">%s</th>', utf8_encode(strftime('%B %Y', $firstday)));
  print '</tr><tr>';
  for($day = 1 + $offset; $day <= 31; $day++) {
    $date = mktime(0, 0, 0, $month, $day, $year);
    $id = strftime('%Y%m%d', $date);
    $class = cal_color($date) . ($id == $today ? ' today' : '');

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
            printf('<div class="event %s" title="%s"><span class="meta">%s %s</span>%s</div>',
              cal_color($item), array_key_exists('description', $item) ? $item['description'] : '', $item['start'], $item['location'], $item['summary']);
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

function cal_stream() {
  global $dates, $marks;
  $min = strtotime('-1 week');
  $min = strtotime('-' . (7 - idate('w', $min)) . ' day', $min);
  $max = strtotime('+4 week');
  $max = strtotime('+' . (7 - idate('w', $max)) . ' day', $max);
  $today = strftime('%Y%m%d');
  $date = $min;

  print '<tr>';
  while($date <= $max) {
    $id = strftime('%Y%m%d', $date);
    $class = cal_color($date) . ($id == $today ? ' today' : '');

    # new week
    if($date !== $min && idate('w', $date) === 1)
      print '</tr><tr>';

    # mark day
    $mark = array_key_exists($id, $marks) ? 'mark' : '';
          
    printf('<th class="day %s %s">%s</th>', $mark, $class, substr(strftime('%d %a', $date), 0, 5));
    printf('<td class="events %s" width="100"><div>', $class);
    if(idate('w', $date) === 1)
      print strftime('<span class="week">%V</span>', $date);
    foreach($dates[$id] as $item) {
      printf('<div class="event %s" title="%s"><span class="meta">%s %s</span>%s</div>',
        cal_color($item), array_key_exists('description', $item) ? $item['description'] : '', $item['start'], $item['location'], $item['summary']);
    }
    print "</div></td>";
    $date = strtotime('+1 day', $date);
  }
  print "</tr>";
}

function cal_legend() {
  global $dates;

  $colors = array();
  foreach($dates as $date)
    foreach($date as $event)
      $colors = array_merge($colors, explode(' ', cal_color($event)));
  $colors = array_unique($colors);
  sort($colors);
  if($colors[0] == '')
    array_shift($colors);

  printf('<small><b>Termine der Lutherkirchgemeinde (Stand: %s)</b>; F&auml;rbung anhand Termin-Beschreibung: ',
    strftime('%d.%m.%Y'));
  printf('<span class="event">Standard</span> ');
  foreach($colors as $tag)
    printf('<span class="event %s">%s</span> ', $tag, $tag);
  print '</small>';
}

