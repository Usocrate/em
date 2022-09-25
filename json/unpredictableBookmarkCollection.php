<?php
require_once '../classes/System.class.php';
$system = new System('../config/host.json');

include_once '../inc/boot.php';

session_start();

$system->lookForAuthenticatedUser();

$maxsize = 15;
if (isset($_REQUEST['size']) && $_REQUEST['size']<=$maxsize) {
	$size = $_REQUEST['size'];
} else {
	$size = $maxsize;
}

header('Content-type: text/plain; charset=UTF-8');
echo BookmarkCollection::getUnpredictableOne($size)->toJson();