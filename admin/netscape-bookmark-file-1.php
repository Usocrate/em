<?php
function __autoload($class_name) {
	$path = './classes/';
	if (is_file ( $path . $class_name . '.class.php' )) {
		include_once $path . $class_name . '.class.php';
	} elseif ($path . $class_name . '.interface.php') {
		include_once $path . $class_name . '.interface.php';
	}
}

$system = new System ( './config/host.json' );

if (! $system->configFileExists()) {
	header ( 'Location:'.$system->getConfigUrl() );
	exit ();
}

include_once './inc/boot.php';
session_start();

$messages = array();

if (! $system->isUserAuthenticated()) {
    header('Location:' . $system->getLoginUrl());
    exit();
}

if (! empty($_REQUEST['topic_id'])) {
    $topictoexport = new Topic($_REQUEST['topic_id']);
    $topictoexport->hydrate();
}

header('Content-Type:text/html; charset=utf-8');
header('Content-Disposition: attachement; filename="' . ToolBox::formatForFileName($system->getProjectName()) . '_netscape-bookmark-file-1.html"');
$doc_title = 'Exportation de ressources au format netscape-bookmark-file-1 (' . $system->getProjectName() . ')';
?>
<!DOCTYPE NETSCAPE-Bookmark-file-1>
<title><?php echo htmlentities($doc_title) ?></title>
<h1><?php echo htmlentities($doc_title) ?></h1>
<?php
if (isset($topictoexport)) {
    echo $topictoexport->getNetscapeBookmarksFileOutput();
} else {
    foreach ($system->getMainTopics() as $t) {
        echo $t->getNetscapeBookmarksFileOutput();
    }
    $bookmarks = $system->getBookmarksWithoutTopic(); 
    if (count($bookmarks) > 0) {
        $i = $bookmarks->getIterator();
        do {
            echo $i->current()->getNetscapeBookmarksFileOutput();
        } while ($i->next());
    }
}
?>