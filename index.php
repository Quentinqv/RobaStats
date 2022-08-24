<!DOCTYPE html>
<html lang="en">

<head>
  <meta charset="UTF-8">
  <meta http-equiv="X-UA-Compatible" content="IE=edge">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-1BmE4kWBq78iYhFldvKuhfTAU6auU8tT94WrHftjDbrCEXSU1oBoqyl2QvZ6jIW3" crossorigin="anonymous">
  <link rel="manifest" href="/manifest.webmanifest">
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

  form {
    margin: 30px 0;
    width: 100%;
    max-width: 1000px;
  }
</style>

<body>
  <img src="logo.png" alt="logo Kertel">
  <form action="main.php" method="post" enctype="multipart/form-data">
    <div class="mb-3">
      <label for="file" class="form-label">Choisir un fichier généré automatiquement (.xls)</label>
      <input class="form-control" type="file" name="file" id="file">
    </div>
    <button type="submit" class="btn btn-success">Stats !</button>
  </form>
  <form action="main_complexe.php" method="post" enctype="multipart/form-data">
    <div class="mb-3">
      <label for="file_complexe" class="form-label">Choisir un fichier généré manuellement par Kertel (.xlsx)</label>
      <input class="form-control" type="file" name="file_complexe" id="file_complexe">
    </div>
    <button type="submit" class="btn btn-success">Stats !</button>
  </form>
  <form action="main_allcentres.php" method="post" enctype="multipart/form-data">
    <div class="mb-3">
      <label for="file_allcentres" class="form-label">Choisir un fichier généré manuellement par Kertel contenant tous les centres (.xlsx)</label>
      <input class="form-control" type="file" name="file_allcentres" id="file_allcentres">
    </div>
    <button type="submit" class="btn btn-success">Stats !</button>
  </form>
  <script>
    if ('serviceWorker' in navigator) {
      // Register a service worker hosted at the root of the
      // site using the default scope.
      navigator.serviceWorker.register('/service-worker.js').then(function(registration) {
        console.log('Service worker registration succeeded:', registration);
      }, /*catch*/ function(error) {
        console.log('Service worker registration failed:', error);
      });
    } else {
      console.log('Service workers are not supported.');
    }
  </script>
</body>
<!-- Copyrights VITOUX Quentin Jan. 2022 -->

</html>