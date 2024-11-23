<?php
require_once './classes/System.class.php';
$system = new System('./config/host.json');

if (! $system->configFileExists ()) {
	header ( 'Location:'.$system->getConfigUrl() );
	exit ();
}

include_once './inc/boot.php';
session_start ();

$system->lookForAuthenticatedUser ();

if (! $system->isUserAuthenticated () && ! $system->isTourRequested()) {
	header ( 'Location:' . $system->getLoginUrl() );
	exit ();
}

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
	<title><?php echo ToolBox::toHtml($doc_title.' ('.$system->getProjectName().')') ?></title>
	<meta charset="UTF-8">
	<meta name="viewport" content="width=device-width, height=device-height, initial-scale=1.0" />
	<link rel="stylesheet" href="<?php echo $system->getSkinUrl(); ?>/theme.css" type="text/css" />
	<script src="<?php echo BOOTSTRAP_JS_URI; ?>"></script>
	<?php echo $system->writeHtmlHeadTagsForFavicon(); ?>	
</head>
<body id="year">
	<?php include './inc/menu.inc.php'; ?>
	<main>
		<header><h1><?php echo ToolBox::toHtml($doc_title); ?></h1></header>
		<?php
		$mostHitBookmarks = $year->getMostHitBookmarkCollection ( 3 );
		if (count($mostHitBookmarks) > 0) {
			echo '<section>';
			echo '<h2>Les plus utiles en ' . $year->getId () . '</h2>';
			echo '<ol class="bl">';
			$i = $mostHitBookmarks->getIterator ();
			do {
				$b = $i->current ();
				echo '<li>';
				echo '<div class="theater">';
				echo $b->getHtmlSnapshotLink ();
				echo '</div>';
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
					echo ' '.$b->getHtmlLinkToInfo ();
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
		if (count($mostHitBookmarks2) > 0) {
			echo '<section>';
			echo '<h2>Découverts en ' . $year->getId () . '</h2>';
			echo '<ol class="bl">';
			$i2 = $mostHitBookmarks2->getIterator ();
			do {
				$b = $i2->current ();
				echo '<li class="n2">';
				echo '<div class="theater">';
				echo $b->getHtmlSnapshotLink ();
				echo '</div>';
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
					echo ' '.$b->getHtmlLinkToInfo ();
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
	</main>
</body>
</html>