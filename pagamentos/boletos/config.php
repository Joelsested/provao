<?php
require_once __DIR__ . '/../../config/env.php';

define('CONF_ID', env('BOLETO_CLIENT_ID', ''));
define('CONF_SECRETO', env('BOLETO_CLIENT_SECRET', ''));
define('CONF_SANDBOX', filter_var(env('BOLETO_SANDBOX', 'false'), FILTER_VALIDATE_BOOLEAN));

?>