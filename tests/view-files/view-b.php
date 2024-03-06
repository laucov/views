<?php
/** @var \Laucov\Views\View $this */
$variables = array_keys(get_defined_vars());
$locals = array_diff($variables, [
    '_GET',
    '_POST',
    '_COOKIE',
    '_FILES',
    'argv',
    'argc',
    '_ENV',
    '_REQUEST',
    '_SERVER',
]);
?>
<p>Data keys: [<?=implode(', ', $locals)?>]</p>