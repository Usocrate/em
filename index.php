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

if (! $system->configFileExists ()) {
	header ( 'Location:' . $system->getConfigUrl () );
	exit ();
}

include_once './inc/boot.php';
session_start ();

$system->lookForAuthenticatedUser ();

$maintopic = $system->getMainTopic ();

header ( 'charset=utf-8' );
?>
<!doctype html>
<html lang="fr">
<head>
<meta charset="UTF-8">
	<meta name="viewport" content="width=device-width, height=device-height, initial-scale=1.0" />
	<meta name="description" content="<?php echo $system->projectDescriptionToHtml() ?>" />
	<meta name="author" content="<?php echo $system->projectCreatorToHtml() ?>" />
	<title><?php echo $system->projectNameToHtml().' : '.$system->projectDescriptionToHtml() ?></title>
	<link rel="stylesheet" href="<?php echo $system->getSkinUrl(); ?>/theme.css" type="text/css" />
	<?php echo $system->writeHtmlHeadTagsForFavicon(); ?>
	<script src="<?php echo JQUERY_URI; ?>"></script>
	<script src="<?php echo BOOTSTRAP_JS_URI; ?>"></script>
</head>
<body id="home">
	<?php include_once 'inc/ga_tracker.inc.php'?>
	<?php include './inc/menu.inc.php'; ?>
	<div class="container-fluid">
		<h1><?php echo $system->projectDescriptionToHtml() ?></h1>
		<img id="visu" src="<?php echo $system->getImagesUrl(); ?>/home_w920_bw.png" class="deco" alt="">
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
					echo '<ol class="tl">';
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
	</div>
</body>
</html>