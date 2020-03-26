<?php

use ProjectSend\Classes\Emails;

require_once '../bootstrap.php';

global $dbh;

header('Content-Type: application/json');

$mail = explode(',', $_GET['email']);
$note = $_GET['note'];
$links = preg_split('/\r\n|\r|\n/', $_GET['links']);
$uploader = $_GET['uploader'];
$link_html = '';

foreach ($links as $link) {
    if (!empty($link)) {
        $link_html .= '<li style="margin-bottom:11px;">';
        $link_html .= '<a class="btn btn-primary" href="' . $link . '">' . $link . '</a>';
        $link_html .= '</li>';
    }
}

$notifier = new Emails;
$email_arguments = array(
    'type' => 'public_links',
    'address' => $mail,
    'note' => $note,
    'links' => $link_html,
    'uploader' => $uploader
);
$notifier->send($email_arguments);

echo json_encode(array('emails_send' => 'true'));
