<?php
function __autoload($class_name) {
	$path = './classes/';
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

$system->lookForAuthenticatedUser();

if (isset($_REQUEST['topic_id'])) {
	$topic = new Topic($_REQUEST['topic_id']);
	$topic->hydrate();
} else {
	$topic = $system->getMainTopic();
}
$nbToDisplay = isset($_REQUEST['nb']) ? $_REQUEST['nb'] : 15;
$doc_title = 'Les nouveautés';
$doc_description = 'Les '.$nbToDisplay.' dernières ressources ajoutées.';
$bookmarks = $topic->getLastAddedDependentBookmarks($nbToDisplay);

header('charset=utf-8');
?>
<!doctype html>
<html lang="fr">
<head>
	<meta charset="UTF-8">
	<meta name="viewport" content="width=device-width, height=device-height, initial-scale=1.0, minimum-scale=1.0, maximum-scale=1.0, user-scalable=no" />
	<meta name="description" content="<?php echo ToolBox::toHtml($doc_description) ?>" />
	<title><?php echo ToolBox::toHtml($system->getProjectName().' > '.$doc_title) ?></title>
	<link rel="stylesheet" href="<?php echo BOOTSTRAP_CSS_URI ?>" type="text/css" />
	<link rel="stylesheet" href="<?php echo BOOTSTRAP_CSS_THEME_URI ?>" type="text/css" />
	<link rel="stylesheet" href="<?php echo $system->getSkinUrl(); ?>/main.css" type="text/css" />
	<link rel="icon" type="image/x-icon" href="<?php echo $system->getSkinUrl(); ?>/favicon.ico" />
	<link rel="alternate" type="application/rss+xml" title="<?php echo 'Canal RSS '.$system->projectNameToHtml().' : les nouveautés' ?>" href="topic_lastaddedbookmarks.rss.php?topic_id=<?php echo $topic->getId() ?>" />
	<link rel="search" type="application/opensearchdescription+xml" href="<?php echo $system->getProjectUrl() ?>/opensearch.xml.php" title="<?php echo $system->projectNameToHtml() ?>" />
	<script type="text/javascript" src="<?php echo JQUERY_URI; ?>"></script>
	<script type="text/javascript" src="<?php echo BOOTSTRAP_JS_URI; ?>"></script>
	<?php include_once './inc/ga_tracker.inc.php' ?>
</head>
<body id="lastaddedbookmarks" class="container">
	<header>
		<div class="brand"><?php echo $system->getHtmlLink() ?></div>
		<h1><?php echo ToolBox::toHtml($doc_title) ?></h1>
	</header>
	<div>
		<?php
		if ($bookmarks->getSize()>0) {
			$i = $bookmarks->getIterator();
			do {
				if ($b = $i->current()) {
					if (!isset($dateToDisplay) || strcmp($dateToDisplay, $b->getCreationDateFr())!=0) {
						if (isset($dateToDisplay)) {
							echo '</ol>';
						}
						$dateToDisplay = $b->getCreationDateFr();
						echo '<h2>'.$b->getHtmlCreationDateFr().'</h2>';
						echo '<ol class="bl">';
					}
					$class = $b->isPrivate() ? 'lockedBookmark' : 'unlockedBookmark';
					echo '<li class="'.$class.'">';
					echo $b->getHtmlSnapshotLink();
					echo '<div class="text">';
					echo $b->getHtmlLink();
					echo ' '.$b->getHtmlLinkToInfo();
					$dataToDisplay = array();
					if ($b->getCreator()) {
						$dataToDisplay[] = ToolBox::toHtml($b->getCreator());
					}
					if ($b->isPublisherKnown()) {
						$dataToDisplay[] = $b->getHtmlLinkToPublisher();
					}
					if (count($dataToDisplay)) {
						echo '<div class="baseline">'.implode(' - ', $dataToDisplay).'</div>';
					}
					if ($b->getTopic() instanceof Topic) {
						echo '<p>'.$b->getHtmlLinkToTopic().'</p>';
					}
					echo $b->getHtmlDescription();
					echo '</div>';
					echo '</li>';
				}
			} while ($i->next());
			echo '</ol>';
			echo '<hr class="clearer" />';
		} else {
			echo '<p>Rien à signaler !</p>';
		}
		?>
	</div>
	<?php include './inc/footer.inc.php'; ?>
</body>
</html>