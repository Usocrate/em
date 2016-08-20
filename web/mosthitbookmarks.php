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

if (! $system->configFileExists ()) {
	header ( 'Location:'.$system->getConfigUrl() );
	exit ();
}

include_once './inc/boot.php';

session_start ();

$system->lookForAuthenticatedUser ();

$periodToCheck = isset ( $_REQUEST ['period'] ) ? $_REQUEST ['period'] : ACTIVITY_THRESHOLD1;
$date = date ( "Y-m-d", strtotime ( '-' . $periodToCheck . ' day' ) );

$itemsToDisplay = MOSTHITBOOKMARKS_POPULATION_SIZE;
$bookmarks = $system->getMostHitBookmarkCollectionSinceDate ( $date, $itemsToDisplay );
$doc_title = 'Utiles depuis ' . $periodToCheck . ' jours';

header ( 'charset=utf-8' );
?>
<!doctype html>
<html lang="fr">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, height=device-height, initial-scale=1.0, minimum-scale=1.0, maximum-scale=1.0, user-scalable=no" />
<title><?php echo ToolBox::toHtml($doc_title.' ('.$system->getProjectName().')'); ?></title>
<link rel="stylesheet" href="<?php echo BOOTSTRAP_CSS_URI ?>" type="text/css" /><link rel="stylesheet" href="<?php echo BOOTSTRAP_CSS_THEME_URI ?>" type="text/css" />
<link rel="stylesheet" href="<?php echo $system->getSkinUrl(); ?>/main.css" type="text/css" />
<link rel="apple-touch-icon" sizes="180x180" href="<?php echo $system->getSkinUrl(); ?>/apple-touch-icon.png">
<link rel="icon" type="image/png" href="<?php echo $system->getSkinUrl(); ?>/favicon-32x32.png" sizes="32x32">
<link rel="icon" type="image/png" href="<?php echo $system->getSkinUrl(); ?>/favicon-16x16.png" sizes="16x16">
<link rel="manifest" href="<?php echo $system->getSkinUrl(); ?>/manifest.json">
<link rel="mask-icon" href="<?php echo $system->getSkinUrl(); ?>/safari-pinned-tab.svg" color="#5bbad5">
<link rel="shortcut icon" href="<?php echo $system->getSkinUrl(); ?>/favicon.ico">
<meta name="msapplication-config" content="<?php echo $system->getSkinUrl(); ?>/browserconfig.xml">
<meta name="theme-color" content="#8ea4bc">
<link rel="search" type="application/opensearchdescription+xml" href="<?php echo $system->getProjectUrl() ?>/opensearch.xml.php" title="<?php echo $system->projectNameToHtml() ?>" />
<script type="text/javascript" src="<?php echo JQUERY_URI; ?>"></script>
<script type="text/javascript" src="<?php echo BOOTSTRAP_JS_URI; ?>"></script>
	<?php include_once './inc/ga_tracker.inc.php'?>
</head>
<body id="mosthitbookmarks" class="container">
	<header>
		<div class="brand"><?php echo $system->getHtmlLink() ?></div>
		<h1><?php echo ToolBox::toHtml($doc_title).' <small>(TOP '.$itemsToDisplay.')</small>' ?></h1>
	</header>
	<section>
		<?php
		if ($bookmarks->getSize () > 0) {
			echo '<ol class="bl">';
			$i = $bookmarks->getIterator ();
			do {
				$b = $i->current ();
				echo '<li class="card">';
				echo $b->getHtmlSnapshotLink ();
				echo '<div class="text">';
				$title = $b->hasDescription () ? $b->getDescription () : $b->getUrl ();
				$classes = array (
						'hitTrigger' 
				);
				$classes [] = $b->isHot () ? 'hotBookmarkLink' : 'bookmarkLink';
				echo '<a href="' . $b->getHitUrl () . '" rel="nofollow" target="_blank" class="' . implode ( ' ', $classes ) . '">';
				echo ToolBox::toHtml ( ucfirst ( $b->getTitle () ) );
				echo '</a>';
				if ($system->isUserAuthenticated ()) {
					echo ' ' . $b->getHtmlLinkToInfo ();
				}
				echo '<div class="topic">'.$b->getHtmlLinkToTopic().'</div>';
				echo '<div>';
				if ($b->hasDescription ()) {
					echo '<p>' . ucfirst ( nl2br ( ToolBox::toHtml ( $b->getDescription () ) ) ) . '</p>';
				}
				echo '<p><em>' . $b->countDayWithHit () . '</em> jours d&#39;utilisation.</p>';
				echo '</div>';
				echo '</div>';
				echo '</li>';
			} while ( $i->next () );
			echo '</ol>';
		} else {
			echo '<div><p>Aucune ressource !</p></div>';
		}
		?>
	</section>
	<section>
		<nav id="hotTopicsNav" class="tl bonus">
		<?php
		$mostHitTopics = $system->getMostHitTopics ( 7 );
		if ($mostHitTopics->getSize () > 0) {
			echo '<span>Zones chaudes :</span>';
			echo '<ol class="tl">';
			$i = $mostHitTopics->getIterator ();
			while ( $i->current () ) {
				echo '<li>';
				echo $i->current ()->getHtmlLink ();
				echo '</li>';
				$i->next ();
			}
			echo '</ol>';
		}
		?>
		</nav>
	</section>
	<?php include './inc/footer.inc.php'; ?>
</body>
</html>