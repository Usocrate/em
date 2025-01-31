<?php
require_once '../../classes/System.class.php';
$system = new System('../../config/host.json');

include_once '../../inc/boot.php';

session_start();

header('Content-type: text/plain; charset=UTF-8');

switch ($_SERVER["REQUEST_METHOD"]) {
	case 'GET' :
		$publishers = $system->getPublishersByNameClue($_GET['query']);
		$output = array();
		foreach ($publishers as $p) {
			$output[] = $p->getName();
		}
		echo json_encode($output);
		exit;
}
?>