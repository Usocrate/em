<?php
function __autoload($class_name) {
	$path = '../classes/';
	if (is_file ( $path . $class_name . '.class.php' )) {
		include_once $path . $class_name . '.class.php';
	} elseif ($path . $class_name . '.interface.php') {
		include_once $path . $class_name . '.interface.php';
	}
}

$system = new System ( '../config/host.json' );

include_once './inc/boot.php';
session_start ();

if (! $system->isUserAuthenticated ()) {
	header ( 'Location:' . $system->getLoginUrl() );
	exit;
}

$bookmark = new Bookmark ( $_REQUEST ['id'] );
$bookmark->hydrate ();

$output = array();

if (isset($_REQUEST['cmd'])) {
    switch ($_REQUEST['cmd']) {
        case 'getAccountData' :
            $output['login'] = $bookmark->getLogin();
            $output['password'] = $bookmark->getPassword();
            break;
    }
}
header('Content-type: text/plain; charset=UTF-8');
echo json_encode($output);
?>