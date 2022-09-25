<?php
require_once '../classes/System.class.php';
$system = new System('../config/host.json');

include_once '../inc/boot.php';

session_start();

$publishers = $system->getPublishersByNameClue($_REQUEST['query']);

$items = array();
foreach ($publishers as $p) {
	$items[] = $p->toJson();
}

header('Content-type: text/plain; charset=UTF-8');
$output = '{"publishers":['.implode(',', $items).']}';
echo $output;
?>