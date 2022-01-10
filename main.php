<?php
require_once __DIR__ . '/SimpleXLSX.php';

$file = $_FILES['file']['tmp_name'];
$xlsx = SimpleXLSX::parse($file);

$plages = [
  [
    'label' => '9h00 - 9h30',
    'start' => '09:00:00',
    'end'   => '09:30:00',
    'uniqueData'  => [],
    'data'  => [],
    'stats' => [],
  ],
  [
    'label' => '9h30 - 10h00',
    'start' => '09:30:00',
    'end'   => '10:00:00',
    'uniqueData'  => [],
    'data'  => [],
    'stats' => [],
  ],
  [
    'label' => '10h00 - 10h30',
    'start' => '10:00:00',
    'end'   => '09:30:00',
    'uniqueData'  => [],
    'data'  => [],
    'stats' => [],
  ],
  [
    'label' => '10h30 - 11h00',
    'start' => '10:30:00',
    'end'   => '11:00:00',
    'uniqueData'  => [],
    'data'  => [],
    'stats' => [],
  ],
  [
    'label' => '11h00 - 11h30',
    'start' => '11:00:00',
    'end'   => '11:30:00',
    'uniqueData'  => [],
    'data'  => [],
    'stats' => [],
  ]
];

$rows = $xlsx->rows();
unset($rows[0]);

/**
 * Parse rows from .xlsx file to PHP array
 * 
 * @param rows {Array} Array containing all of the rows to parse
 * @return data {Array}
 */
function parsedRows($rows)
{
  $data = [];
  foreach ($rows as $r) {
    // Explode to separate date and hour
    $temp = explode(' ', $r['0']);

    // Parse the row to a new format
    $row = [
      'date' => $temp[0],
      'hour' => $temp[1],
      'time' => $r[2],
      'state' => $r[3] == 'Connecté' ? true : false,
      'num'   => $r[1],
    ];

    // Save this row to $data
    $data[] = $row;
  }

  return $data;
}

function avgTime($data)
{
  $temp = $data;
  $totalTime = 0;
  foreach ($temp as $key => $value) {
    $explode = explode('min', $temp[$key]['time']);
    $explode[1] = str_replace('s', '', $explode[1]);
    $time = $explode[0] * 60 + $explode[1];

    $totalTime += $time;
  }

  $t = round($totalTime / sizeof($temp));
  $avgTime = sprintf('%02dh%02dmin%02ds', ($t / 3600), ($t / 60 % 60), $t % 60);
  $avgTime = substr($avgTime, 3);

  return $avgTime;
}

/**
 * Sort rows depending on hours passed
 * 
 * @param rows {Array} All rows to sort
 * @param palges {Array} Hours to sort on
 */
function getStats($rows, $plages)
{
  foreach ($rows as $keyRow => $row) {
    foreach ($plages as $key => $value) {
      if ($value['start'] <= $row['hour'] && $value['end'] > $row['hour']) {
        $plages[$key]['data'][] = $row;

        $isExist = array_filter($plages[$key]['uniqueData'], function ($e) use ($row) {
          return $e['num'] == $row['num'];
        });
        if ($isExist == []) {
          $plages[$key]['uniqueData'][] = $row;
        }
      }
    }
  }

  // Total numbers of call on that file
  $nbTotal = sizeof($rows);
  $nbTotalSuccess = sizeof(array_filter($rows, function ($e) {
    return $e['state'] == true;
  }));
  $nbTotalUnique = 0;

  foreach ($plages as $key => $value) {
    $plages[$key]['stats']['nb'] = sizeof($plages[$key]['data']);
    $plages[$key]['stats']['nbUnique'] = sizeof($plages[$key]['uniqueData']);
    $nbTotalUnique+=$plages[$key]['stats']['nbUnique'];
    $plages[$key]['stats']['nbSuccess'] = sizeof(array_filter($plages[$key]['data'], function ($e) {
      return $e['state'] == true;
    }));
    $plages[$key]['stats']['percentage'] = $plages[$key]['stats']['nb'] == 0 ? 0 : round(($plages[$key]['stats']['nbSuccess'] / $plages[$key]['stats']['nb']) * 100);

    $plages[$key]['stats']['avgTime'] = avgTime($plages[$key]['data']);
  }

  $nbTotalPercentage = $nbTotal == 0 ? 0 : round(($nbTotalSuccess / $nbTotal) * 100);

  return [
    'plages'  => $plages,
    'stats'   => [
      'nbTotal'         => $nbTotal,
      'nbTotalSuccess'  => $nbTotalSuccess,
      'nbTotalUnique'  => $nbTotalUnique,
      'nbTotalPercentage'  => $nbTotalPercentage,
    ],
  ];
}

(array)$parsedRows = (array)parsedRows($rows);
$dayToDayRows = [];
foreach ($parsedRows as $key => $value) {
  if (!isset($dayToDayRows[$value['date']])) {
    $dayToDayRows[$value['date']] = $value['date'];
  }
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
  <meta charset="UTF-8">
  <meta http-equiv="X-UA-Compatible" content="IE=edge">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-1BmE4kWBq78iYhFldvKuhfTAU6auU8tT94WrHftjDbrCEXSU1oBoqyl2QvZ6jIW3" crossorigin="anonymous">
  <title>RobaStats</title>
</head>
<style>
  body {
    height: 100vh;
    display: flex;
    justify-content: center;
    align-items: center;
    flex-direction: column;
  }

  h2 {
    margin: 20px 0 0 0;
  }
</style>

<body>
  <h1>Stats Kertel</h1>
  <?php
  foreach ($dayToDayRows as $key => $value) {
    $dayRows = array_filter($parsedRows, function ($e) use ($value) {
      return $e['date'] == $value;
    });
    $stats = getStats($dayRows, $plages);
    $avgTime = avgTime($dayRows);
  ?>
    <h2>Statistiques du <?php echo ($value); ?></h2>
    <table class="table table-striped table-hover">
      <thead>
        <th>Horaires</th>
        <th>Nb d'appel</th>
        <th>Nb d'appel unique</th>
        <th>% de réponse</th>
        <th>Temps moyen d'appel</th>
      </thead>
      <tbody>
        <?php
        foreach ($stats['plages'] as $key => $value) {
          echo ("<tr><td>" . $value['label'] . "</td><td>" . $value['stats']['nb'] . "</td><td>" . $value['stats']['nbUnique'] . "</td><td>" . $value['stats']['percentage'] . " %</td><td>" . $value['stats']['avgTime'] . "</td></tr>");
        }
        ?>
      </tbody>
    </table>
    <table class="table table-striped table-hover">
      <thead>
        <th>Horaires</th>
        <th>Nb d'appel</th>
        <th>Nb d'appel unique</th>
        <th>% de réponse</th>
        <th>Temps moyen d'appel</th>
      </thead>
      <tbody>
        <?php
          echo ("<tr><td>Total</td><td>" . $stats['stats']['nbTotal'] . "</td><td>" . $stats['stats']['nbTotalUnique'] . "</td><td>" . $stats['stats']['nbTotalPercentage'] . " %</td><td>" . $avgTime . "</td></tr>");
        ?>
      </tbody>
    </table>
  <?php
  }
  ?>

</body>

</html>