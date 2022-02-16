<?php
function __autoload($class_name) {
	$path = '../../classes/';
	if (is_file ( $path . $class_name . '.class.php' )) {
		include_once $path . $class_name . '.class.php';
	} elseif ($path . $class_name . '.interface.php') {
		include_once $path . $class_name . '.interface.php';
	}
}

$system = new System ( '../../config/host.json' );

include_once '../../inc/boot.php';
session_start ();

if (! $system->isUserAuthenticated ()) {
	header ( 'Location:' . $system->getLoginUrl() );
	exit;
}

header("Content-type: text/plain");

switch ($_SERVER["REQUEST_METHOD"]) {
	case 'GET' :
		exit;
		
	case 'POST' :
		ToolBox::formatUserPost($_POST);
		$fb = new Feedback();
		
		switch($_POST['task']) {
			case 'deletion':
				if (isset($_POST['id'])) {
					$b = new Bookmark($_POST['id']);
					$b->hydrate();
					
					$t = $b->getTopic();
					
					if ($system->deleteBookmark($b)) {
						$fb->setMessage('C\'est oublié.');
						$fb->setType('success');
						$fb->addDatum('location', $system->getTopicUrl($t));
					} else {
						$fb->setMessage('Mince, problème !');
						$fb->setType('error');
					}
				}
				break;
		}
		echo $fb->toJson();
		exit;
		
	case 'DELETE' :
		exit;
}
?>