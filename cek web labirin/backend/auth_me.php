<?php
declare(strict_types=1);
require_once __DIR__.'/config.php';

$user = current_user();
if (!$user) {
    json_response(200, ['ok' => true, 'user' => null]);
}
json_response(200, ['ok' => true, 'user' => $user]);
