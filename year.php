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
	header ( 'Location:'.$system->getConfigUrl() );
	exit ();
}

include_once './inc/boot.php';
session_start ();

$system->lookForAuthenticatedUser ();

if (isset ( $_REQUEST ['year_id'] )) {
	$year = new Year ( $_REQUEST ['year_id'] );
} else {
	header ( 'Location:' . $system->getProjectUrl () );
	exit ();
}
$doc_title = 'L\'année ' . $year->id;

header ( 'charset=utf-8' );
?>
<!doctype html>
<html lang="fr">
<head>
	<meta charset="UTF-8">
	<meta name="viewport" content="width=device-width, height=device-height, initial-scale=1.0, minimum-scale=1.0, maximum-scale=1.0, user-scalable=no" />
	<title><?php echo ToolBox::toHtml($doc_title.' ('.$system->getProjectName().')') ?></title>
	<link rel="stylesheet" href="<?php echo $system->getSkinUrl(); ?>/theme.css" type="text/css" />
	<?php echo $system->writeHtmlHeadTagsForFavicon(); ?>
	<link rel="search" type="application/opensearchdescription+xml" href="<?php echo $system->getProjectUrl() ?>/opensearch.xml.php" title="<?php echo $system->projectNameToHtml() ?>" />
	<script type="text/javascript" src="<?php echo JQUERY_URI; ?>"></script>
	<script type="text/javascript" src="<?php echo BOOTSTRAP_JS_URI; ?>"></script>
</head>
<body id="year">
	<?php include_once 'web/inc/ga_tracker.inc.php'?>
	<div class="container-fluid">
		<header>
			<h1><?php echo ToolBox::toHtml($doc_title); ?></h1>
		</header>
		<?php
		$mostHitBookmarks = $year->getMostHitBookmarkCollection ( 3 );
		if ($mostHitBookmarks->getSize () > 0) {
			echo '<section>';
			echo '<h2>Les plus utiles en ' . $year->getId () . '</h2>';
			echo '<ol class="bl">';
			$i = $mostHitBookmarks->getIterator ();
			do {
				$b = $i->current ();
				echo '<li>';
				echo $b->getHtmlSnapshotLink ();
				echo '<div class="text">';
				echo '<a rel="nofollow" href="' . $b->getHitUrl () . '" target="_blank"';
				$title = $b->getDescription ();
				if (empty ( $title )) {
					$title = $b->getUrl ();
				}
				echo ' title="' . ToolBox::toHtml ( $title ) . '"';
				echo ' class="bookmarkLink hitTrigger">';
				echo ToolBox::toHtml ( ucfirst ( $b->getTitle () ) );
				echo '</a>';
				if ($system->isUserAuthenticated ()) {
					echo $b->getHtmlLinkToInfo ();
				}
				echo '<p>' . $b->getHtmlLinkToTopic () . '</p>';
				echo $b->getHtmlDescription ();
				echo '<p><em>' . $b->countDayWithHit () . '</em> jours d&#39;utilisation.</p>';
				echo '</div>';
				echo '</li>';
			} while ( $i->next () );
			echo '</ol>';
			echo '</section>';
		}
		
		$mostHitBookmarks2 = $year->getMostHitBookmarkCollectionAsCreationYear (3);
		if ($mostHitBookmarks2->getSize () > 0) {
			echo '<section>';
			echo '<h2>Découverts en ' . $year->getId () . '</h2>';
			echo '<ol class="bl">';
			$i2 = $mostHitBookmarks2->getIterator ();
			do {
				$b = $i2->current ();
				echo '<li class="n2">';
				echo $b->getHtmlSnapshotLink ();
				echo '<div class="text">';
				echo '<a rel="nofollow" href="' . $b->getHitUrl () . '" target="_blank"';
				$title = $b->getDescription ();
				if (empty ( $title )) {
					$title = $b->getUrl ();
				}
				echo ' title="' . ToolBox::toHtml ( $title ) . '"';
				echo ' class="bookmarkLink hitTrigger">';
				echo ToolBox::toHtml ( ucfirst ( $b->getTitle () ) );
				echo '</a>';
				if ($system->isUserAuthenticated ()) {
					echo $b->getHtmlLinkToInfo ();
				}
				echo '<p>' . $b->getHtmlLinkToTopic () . '</p>';
				echo $b->getHtmlDescription ();
				echo '<p><em>' . $b->countDayWithHit () . '</em> jours d&#39;utilisation.</p>';
				echo '</div>';
				echo '</li>';
			} while ( $i2->next () );
			echo '</ol>';
			echo '</section>';
		}
		
		echo '<section>';
		echo '<nav class="bar">';
		echo '<span>Voir aussi ...</span>';
		echo '<ol>';
		$data = $system->countBookmarkCreationYearly ();
		foreach ( $data as $y => $count ) {
			echo strcmp ( $y, $year->getId () ) == 0 ? '<li class="inactive">' . $y . '</li>' : '<li>' . Year::getHtmlLinkToYearDoc ( $y ) . '</li>';
		}
		echo '</ol>';
		echo '</nav>';
		echo '</section>';
		?>
	</div>
	<?php include './inc/footer.inc.php'; ?>
</body>
</html>