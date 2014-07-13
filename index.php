<?php
/**
 * Index page
 *
 *  This file shows all the graphs collected by the run.php script
 *
 * PHP 5
 *
 * PHPCollectd
 * Copyright (c), Ivo Filot
 *
 * Licensed under The MIT License
 * For full copyright and license information, please see the file LICENSE
 * Redistributions of files must retain the above copyright notice.
 *
 * @copyright     Copyright (c) Ivo Filot
 * @link          https://github.com/ifilot/phpcollectd
 * @license       MIT License (http://www.opensource.org/licenses/mit-license.php)
 */
?>

<!DOCTYPE html>
<html lang="en">
  <head>
    <meta charset="utf-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Logging</title>

    <!-- Bootstrap -->
    <link href="css/bootstrap.min.css" rel="stylesheet">
  </head>
  <body>
<table class="table table-striped table-condensed">
	<tr><td>
<?php

// import the configuration file
if (!file_exists('config.inc.php'))
  throw new Exception ('config.inc.php does not exist, please create one');
else {
  require_once('config.inc.php'); 
}

if($config['debug']) {
	error_reporting(E_ALL);
	ini_set('display_errors', 1);
}

$dir = 'img';
?>

<table class="table-striped table-condensed"><tr><td>
  <b><?php echo gethostname(); ?></b>
</td><td>
<?php
foreach($config['graphs'] as $graph): ?>
  <?php if(file_exists($dir.'/'.$graph.'.png')): ?>
    <img src="<?php echo $dir.'/'.$graph.'.png'; ?>" />
  <?php endif; ?>

<?php endforeach; ?>
</td></tr>
</table>

</body>
</html>
