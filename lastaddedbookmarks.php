<?php
require_once './classes/System.class.php';
$system = new System('./config/host.json');

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
	<title><?php echo ToolBox::toHtml($system->getProjectName().' > '.$doc_title) ?></title>
	<meta charset="UTF-8">
	<meta name="viewport" content="width=device-width, height=device-height, initial-scale=1.0" />
	<meta name="description" content="<?php echo ToolBox::toHtml($doc_description) ?>" />
	<link rel="stylesheet" href="<?php echo $system->getSkinUrl(); ?>/theme.css" type="text/css" />
	<script src="<?php echo JQUERY_URI; ?>"></script>
	<script src="<?php echo MASONRY_URI; ?>"></script>
	<script src="<?php echo BOOTSTRAP_JS_URI; ?>"></script>
	<?php echo $system->writeHtmlHeadTagsForFavicon(); ?>	
</head>
<body id="lastaddedbookmarks">
	<?php include './inc/menu.inc.php'; ?>	
	<div class="container-fluid">
		<header>
			<h1><?php echo ToolBox::toHtml($doc_title) ?></h1>
		</header>
		<?php
		if (count($bookmarks) > 0) {
			$i = $bookmarks->getIterator();
			do {
				if ($b = $i->current()) {
					if (!isset($dateToDisplay) || strcmp($dateToDisplay, $b->getCreationDateFr())!=0) {
						if (isset($dateToDisplay)) {
							echo '</ol></section>';
						}
						$dateToDisplay = $b->getCreationDateFr();
					    echo '<section>';
						echo '<h2>'.$b->getHtmlCreationDateFr().'</h2>';
						echo '<ol class="bl">';
					}
					$cssClasses = array();
					$cssClasses[] = $b->isPrivate() ? 'lockedBookmark' : 'unlockedBookmark';
					echo '<li class="'.implode(' ', $cssClasses).'">';
					echo '<div class="theater">'.$b->getHtmlSnapshotLink().'</div>';
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
						echo '<div class="topic">'.$b->getHtmlLinkToTopic().'</div>';
					}
					echo $b->getHtmlDescription();
					echo '</div>';
					echo '</li>';
				}
			} while ($i->next());
			echo '</ol></section>';
		} else {
			echo '<p>Rien à signaler !</p>';
		}
		?>
	</div>
	<script>
		$(document).ready(function(){
			$('.bl').masonry({
				itemSelector:'li'
			});
		});
	</script>	
</body>
</html>