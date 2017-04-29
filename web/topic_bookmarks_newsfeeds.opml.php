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

if (! $system->configFileExists()) {
	header ( 'Location:'.$system->getConfigUrl() );
	exit ();
}

include_once './inc/boot.php';
session_start();


if (!$system->isUserAuthenticated()) {
	header('Location:'.$system->getLoginUrl());
	exit;
}

if (isset($_REQUEST['topic_id'])) {
	$topic = new Topic($_REQUEST['topic_id']);
	$topic->hydrate();
} else {
	$topic = $system->getMainTopic();
}
header('Content-Type:text/xml; charset=utf-8');
//header('Content-Disposition: attachement; filename="'.ToolBox::formatForFileName($system->getProjectName()).'_newsfeeds.opml"');

echo '<?xml version="1.0" encoding="utf-8" ?>';
echo '<?xml-stylesheet type="text/xsl" href="'.$system->getProjectUrl().'/xsl/opml.xsl.php"?>';
echo '<opml version="'.ToolBox::xmlEntities($opml_version).'">';
echo '<head>';
echo '<title><![CDATA['.$system->getProjectName().' : Liste des flux RSS]]></title>';
echo '</head>';
echo '<body class="container">';
$bookmarks = $topic->getDependentBookmarksWithNewsFeed();
foreach ($bookmarks as $b) {
	echo '<outline text="'.ToolBox::xmlEntities($b->getTitle()).'" type="rss" xmlUrl="'.ToolBox::xmlEntities($b->getRssUrl()).'" title="'.ToolBox::xmlEntities($b->getTitle()).'"/>';
}
echo '</body>';
echo '</opml>';
?>