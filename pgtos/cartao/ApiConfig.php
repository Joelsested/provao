<?php 
require_once __DIR__ . '/../../config/env.php';

$mp_enabled = filter_var(env('MP_ENABLED', 'false'), FILTER_VALIDATE_BOOLEAN);
$access_token = $mp_enabled ? env('MP_ACCESS_TOKEN', '') : '';
$public_key = $mp_enabled ? env('MP_PUBLIC_KEY', '') : '';
?>
