<?php
require_once './classes/System.class.php';
$system = new System('./config/host.json');

if (! $system->configFileExists ()) {
	header ( 'Location:' . $system->getConfigUrl () );
	exit ();
}

include_once './inc/boot.php';
session_start ();

$system->lookForAuthenticatedUser ();

if (! $system->isUserAuthenticated () && ! $system->isTourRequested()) {
	header ( 'Location:' . $system->getLoginUrl() );
	exit ();
}

$maintopic = $system->getMainTopic ();

header ( 'charset=utf-8' );
?>
<!doctype html>
<html lang="fr">
<head>
	<title><?php echo $system->projectNameToHtml().' : '.$system->projectDescriptionToHtml() ?></title>
	<?php echo $system->writeHeadCommonMetaTags(); ?>
	<?php echo $system->writeHeadCommonLinkTags(); ?>
	<meta name="description" content="<?php echo $system->projectDescriptionToHtml() ?>" />
	<script src="<?php echo BOOTSTRAP_JS_URI; ?>"></script>
</head>
<body id="indexDoc">
	<?php include './inc/menu.inc.php'; ?>
	<main>
		<h1><?php echo $system->projectDescriptionToHtml() ?></h1>
		<div class="visu-wrapper deco"><div id="visu"></div></div>
		<section id="subtopics">
			<?php
			if ($maintopic->hasChild ()) {
				// rubriques avec le plus de ressources
				echo '<ol class="tl">';
				$topics = $maintopic->getChildren();
				$topics->setTopicsWeight();
				$topics->sortByWeight();
				$i = $topics->getIterator();
				$topicsToHighlight = 3;
				$highlightedTopics = 0;
				while ( $i->current() && $highlightedTopics < $topicsToHighlight ) {
					// $class = $i->current()->isPrivate() ? 'lockedtopic' : 'unlockedtopic';
					echo '<li class="emphased">' . $i->current ()->getHtmlLink () . '</li>';
					$highlightedTopics ++;
					$i->next ();
				}
				echo '</ol>';
				// liste complémentaire
				if (count($topics) > $highlightedTopics) {
					echo '<div class="tl">';
					echo '<span>... et aussi : </span>';
					echo '<ol>';
					while ( $i->current() ) {
						$class = $i->current ()->isPrivate () ? 'lockedtopic' : 'unlockedtopic';
						echo '<li class="' . $class . '">' . $i->current ()->getHtmlLink () . '</li>';
						$i->next ();
					}
					if ($system->isUserAuthenticated ()) {
						echo '<li class="virtual"><a href="' . $system->getTopicNewSubtopicEditionUrl ( $maintopic ) . '">+</a></li>';
					}
					echo '</ol>';
					echo '</div>';
				}
			}
			?>
		</section>
	</main>
</body>
</html>