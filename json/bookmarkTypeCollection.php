<?php
require_once '../classes/System.class.php';
$system = new System('../config/host.json');

include_once '../inc/boot.php';

session_start();

$system->lookForAuthenticatedUser();

if (isset($_REQUEST['pattern'])) {
	$pattern = $_REQUEST['pattern'];
}

header('Content-type: text/plain; charset=UTF-8');
//header('Content-type: text/javascript; charset=UTF-8');

echo json_encode($system->countBookmarksByType());