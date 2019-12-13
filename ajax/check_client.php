<?php
require_once '../bootstrap.php';

header('Content-Type: application/json');

$owner = getClientOwner($_GET['user_email']);

if (isNotUniqueEmail($_GET['user_email']) && $owner['id'] != CURRENT_USER_ID) {
    echo json_encode(array('exists' => 'true', 'owner' => $owner['name'] . " (" . $owner['email'] . ")"));
} else {
    echo json_encode(array('exists' => 'false'));
}
