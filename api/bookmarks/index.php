<?php
require_once '../../classes/System.class.php';
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