<?php
require_once '../../classes/System.class.php';
$system = new System('../../config/host.json');

include_once '../../inc/boot.php';

session_start();

header('Content-type: text/plain; charset=UTF-8');

switch ($_SERVER["REQUEST_METHOD"]) {
	case 'GET' :
		$data = isset($_GET['query']) ? Bookmark::getTypeOptionsFromSchemaRdfsOrg($_GET['query']) : Bookmark::getTypeOptionsFromSchemaRdfsOrg();
		echo json_encode($data);
		exit;
}
?>