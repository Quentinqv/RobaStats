<?php
$isXls = substr($_FILES['file_allcentres']['name'], -3) == "xls" ? true : false;
if ($isXls) {
  require_once __DIR__ . '/SimpleXLS.php';
  $file = $_FILES['file_allcentres']['tmp_name'];
  $xlsx = SimpleXLS::parse($file);
} else {
  require_once __DIR__ . '/SimpleXLSX.php';
  $file = $_FILES['file_allcentres']['tmp_name'];
  $xlsx = SimpleXLSX::parse($file);
}

$plages = [
  [
    'label' => '8h30 - 10h00',
    'start' => '08:30:00',
    'end'   => '10:00:59',
    'uniqueData'  => [],
    'data'  => [],
    'stats' => [],
  ],
  [
    'label' => '10h01 - 11h30',
    'start' => '10:01:00',
    'end'   => '11:30:59',
    'uniqueData'  => [],
    'data'  => [],
    'stats' => [],
  ],
  [
    'label' => '11h31 - 13h00',
    'start' => '11:31:00',
    'end'   => '13:00:59',
    'uniqueData'  => [],
    'data'  => [],
    'stats' => [],
  ],
  [
    'label' => '13h01 - 14h00',
    'start' => '13:01:00',
    'end'   => '14:00:59',
    'uniqueData'  => [],
    'data'  => [],
    'stats' => [],
  ],
  [
    'label' => '14h01 - 15h30',
    'start' => '14:01:00',
    'end'   => '15:30:59',
    'uniqueData'  => [],
    'data'  => [],
    'stats' => [],
  ],
  [
    'label' => '15h31 - 17h00',
    'start' => '15:31:00',
    'end'   => '17:00:59',
    'uniqueData'  => [],
    'data'  => [],
    'stats' => [],
  ],
  [
    'label' => '17h01 - 18h30',
    'start' => '17:01:00',
    'end'   => '18:30:59',
    'uniqueData'  => [],
    'data'  => [],
    'stats' => [],
  ],
  [
    'label' => '18h31 - 19h00',
    'start' => '18:31:00',
    'end'   => '19:00:59',
    'uniqueData'  => [],
    'data'  => [],
    'stats' => [],
  ]
];


$rows = $xlsx->rows();
unset($rows[0]);
unset($rows[1]);
unset($rows[2]);
unset($rows[3]);

/**
 * Parse rows from .xlsx file to PHP array
 * 
 * @param rows {Array} Array containing all of the rows to parse
 * @return data {Array}
 */
function parsedRows($rows)
{
  $data = [];
  $allLieux = [];
  foreach ($rows as $r) {
    $tempDate = explode(' ', $r[2]);
    $tempHeure = explode(' ', $r[3]);

    // Parse the row to a new format
    $row = [
      'date' => $tempDate[0],
      'hour' => $tempHeure[1],
      'time' => $r[5],
      'state' => $r[12] == 'Répondu' ? true : false,
      'num'   => $r[6],
      'lieu' => $r[1],
    ];

    // echo(gettype(array_search($r[1], $allLieux)).'<br>');
    if (gettype(array_search($r[1], $allLieux)) == 'boolean') {
      array_push($allLieux, $r[1]);
    }

    // Save this row to $data
    $data[] = $row;
  }

  return [$data, $allLieux];
}

function avgTime($data)
{
  $temp = $data;
  $totalTime = 0;
  foreach ($temp as $key => $value) {
    $time = $value['time'] == '' ? 0 : $value['time'];
    $totalTime += $time;
  }

  if (sizeof($temp) == 0) {
    $t = 0;
  } else {
    $t = round($totalTime / sizeof($temp));
  }
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
      if ($value['start'] <= $row['hour'] && $value['end'] >= $row['hour']) {
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
  $nbTotalDansPlage = 0;

  foreach ($plages as $key => $value) {
    $plages[$key]['stats']['nb'] = sizeof($plages[$key]['data']);
    $nbTotalDansPlage += $plages[$key]['stats']['nb'];
    $plages[$key]['stats']['nbUnique'] = sizeof($plages[$key]['uniqueData']);
    $nbTotalUnique += $plages[$key]['stats']['nbUnique'];
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
      'nbTotalDansPlages'   => $nbTotalDansPlage,
    ],
  ];
}

(array)$parsedRows = (array)parsedRows($rows)[0];
(array)$allLieux = (array)parsedRows($rows)[1];

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
    min-height: 100vh;
    display: flex;
    justify-content: center;
    align-items: center;
    flex-direction: column;
  }

  h2 {
    border-bottom: solid 2px green;
  }

  h2,
  h3 {
    margin: 20px 0 0 0;
    cursor: pointer;
  }

  .hide {
    display: none;
  }
</style>

<body>
  <h1>Stats Kertel</h1>
  <?php
  foreach ($allLieux as $key2 => $lieu) {
    $currentLieu = array_filter($parsedRows, function ($e) use ($lieu) {
      return $e['lieu'] == $lieu;
    });

    echo ('<div class="eachLieu">');
    echo ("<h2>$lieu</h2>");
    echo ('<div class="container__table hide">');

    $dayToDayRows = [];
    foreach ($currentLieu as $key3 => $value4) {
      if (!isset($dayToDayRows[$value4['date']])) {
        $dayToDayRows[$value4['date']] = $value4['date'];
      }
    }

    foreach ($dayToDayRows as $key => $value) {
      $dayRows = array_filter($parsedRows, function ($e) use ($value, $lieu) {
        return ($e['date'] == $value) && $e['lieu'] == $lieu;
      });
      $stats = getStats($dayRows, $plages);
      $avgTime = avgTime($dayRows);
  ?>
      <h3 data-jour="<?= $value.$key3 ?>">Statistiques du <?php echo ($value . ' / ' . $lieu); ?></h3>
      <div class="hide" data-jour="<?= $value.$key3 ?>">
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
            <th>Global</th>
            <th>Nb d'appel</th>
            <th>Nb dans plages</th>
            <th>Nb d'appel unique</th>
            <th>% de réponse</th>
            <th>Temps moyen d'appel</th>
          </thead>
          <tbody>
            <?php
            echo ("<tr><td>Total</td><td>" . $stats['stats']['nbTotal'] . "</td><td>" . $stats['stats']['nbTotalDansPlages'] . "</td><td>" . $stats['stats']['nbTotalUnique'] . "</td><td>" . $stats['stats']['nbTotalPercentage'] . " %</td><td>" . $avgTime . "</td></tr>");
            ?>
          </tbody>
        </table>
      </div>
  <?php
    }
    echo ('</div>');
    echo ('</div>');
  }
  ?>

  <script>
    const titreLieux = document.querySelectorAll("h2")
    const titreJour = document.querySelectorAll("h3")

    for (const key in titreLieux) {
      if (Object.hasOwnProperty.call(titreLieux, key)) {
        const element = titreLieux[key];
        element.addEventListener('click', function() {
          const divLieu = element.parentElement.querySelector(".container__table")
          divLieu.classList.toggle('hide')
        })
      }
    }

    for (const key in titreJour) {
      if (Object.hasOwnProperty.call(titreJour, key)) {
        const element = titreJour[key];
        element.addEventListener('click', function() {
          const tableJour = document.querySelector('div[data-jour="' + element.dataset.jour + '"]')
          tableJour.classList.toggle('hide')
        })
      }
    }
  </script>
</body>
<!-- Copyrights VITOUX Quentin Jan. 2022 -->

</html>