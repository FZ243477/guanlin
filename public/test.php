<?php
$path = __DIR__.'/../pull.bat';

exec($path, $output);

echo "<pre>";
print_r($output);
?>