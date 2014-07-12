<!DOCTYPE html>
<html lang="en">
  <head>
    <meta charset="utf-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Logging</title>

    <!-- Bootstrap -->
    <link href="css/bootstrap.min.css" rel="stylesheet">
    <link href="css/font-awesome.min.css" rel="stylesheet">
  </head>
  <body>
<table class="table table-striped table-condensed">
	<tr><td>
<?php

require 'config.inc.php';   // include configuration file

if($config['debug']) {
	error_reporting(E_ALL);
	ini_set('display_errors', 1);
}

$dir = 'img';

// find all node folders using note_pattern
$handle = opendir($dir);
while($file = readdir($handle)) {
  if(preg_match($config['node_pattern'], $file)) {
    $nodes[] = $file;
  }
}
// and finally sort the nodes
sort($nodes);
?>

<h2>Head node</h2>
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

<h2>Compute nodes</h2>
<table class="table-striped table-condensed">
<?php if(isset($nodes)): ?>
<?php foreach($nodes as $node): ?>
<tr><td>
  <b><?php echo strtoupper($node); ?></b>
</td><td>
  <?php foreach($config['graphs'] as $graph): ?>
  <?php if(file_exists($dir.'/'.$node.'/'.$graph.'.png')): ?>
	   <img src="<?php echo $dir.'/'.$node.'/'.$graph.'.png'; ?>" />
  <?php endif; ?>
  <?php endforeach; ?>

</td></tr>
<?php endforeach; ?>
<?php endif; ?>

</table>
</body>
</html>
