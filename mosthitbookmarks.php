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
	<meta name="viewport" content="width=device-width, height=device-height, initial-scale=1.0" />
	<title><?php echo ToolBox::toHtml($doc_title.' ('.$system->getProjectName().')'); ?></title>
	<link rel="stylesheet" href="<?php echo $system->getSkinUrl(); ?>/theme.css" type="text/css" />
	<?php echo $system->writeHtmlHeadTagsForFavicon(); ?>
	<script src="<?php echo JQUERY_URI; ?>"></script>
	<script src="<?php echo MASONRY_URI; ?>"></script>
	<script src="<?php echo BOOTSTRAP_JS_URI; ?>"></script>
</head>
<body id="mosthitbookmarks">
	<?php include_once 'inc/ga_tracker.inc.php'?>
	<?php include './inc/menu.inc.php'; ?>
	<div class="container-fluid">
		<header>
			<h1><?php echo ToolBox::toHtml($doc_title).' <small>(TOP '.$itemsToDisplay.')</small>' ?></h1>
		</header>
		<section>
			<?php
			if (count($bookmarks) > 0) {
				echo '<ol class="bl">';
				$i = $bookmarks->getIterator ();
				do {
					$b = $i->current ();
					echo '<li>';
					echo '<div class="theater">'.$b->getHtmlSnapshotLink().'</div>';
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
					//echo '<p><em>' . $b->countDayWithHit () . '</em> jours d&#39;utilisation.</p>';
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
			if (count($mostHitTopics) > 0) {
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