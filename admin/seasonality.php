<?php
require_once '../classes/System.class.php';
$system = new System('../config/host.json');

if (! $system->configFileExists()) {
    header ( 'Location:'.$system->getConfigUrl() );
    exit();
}

include_once '../inc/boot.php';

session_start();

$system->lookForAuthenticatedUser();

if (! $system->isUserAuthenticated()) {
	header('Location:' . $system->getLoginUrl());
	exit();
}

$maintopic = $system->getMainTopic();
$project_years = $system->getProjectLivingYears();

$doc_title = 'Les consultations saisonnières';
header('charset=utf-8');
?>
<!doctype html>
<html lang="fr">
<head>
	<title><?php echo ToolBox::toHtml($doc_title.' ('.$system->getProjectName().')') ?></title>
	<?php echo $system->writeHeadCommonMetaTags(); ?>
	<?php echo $system->writeHeadCommonLinkTags(); ?>
	<link rel="stylesheet" href="<?php echo C3_CSS_URI ?>" type="text/css" />
	<script src="<?php echo JQUERY_URI; ?>"></script>
	<script src="<?php echo BOOTSTRAP_JS_URI; ?>"></script>
	<script src="<?php echo MASONRY_URI; ?>"></script>
</head>
<body>
	<?php include 'menu.inc.php'; ?>
	<main>
		<header>
			<h1><?php echo ToolBox::toHtml($doc_title) ?></h1>
		</header>
		<div>
			<ol class="bl">
				<?php
				$c= $system->getMostSeasonnallyHitBookmarkCollection();
				$i = $c->getIterator ();
				do {
					$b = $i->current ();
					$cssClasses = array ();
					$cssClasses [] = $b->isPrivate () ? 'lockedBookmark' : 'unlockedBookmark';
					if ($b->isInactive ()) {
						$cssClasses [] = 'inactive';
					}
					echo '<li class="' . implode ( ' ', $cssClasses ) . '">';
					echo $b->getHtmlSnapshotLink ();
					echo '<div class="text">';
					echo isset ( $highlighter ) ? $highlighter->getString ( $b->getHtmlLink () ) : $b->getHtmlLink ();
					if ($system->isUserAuthenticated ()) {
						echo ' ';
						echo $b->getHtmlLinkToInfo ();
					}
					$dataToDisplay = array ();
					if ($b->getCreator ())
						$dataToDisplay [] = ToolBox::toHtml ( $b->getCreator () );
					if ($b->isPublisherKnown ()) {
						$dataToDisplay [] = $b->getHtmlLinkToPublisher ();
					}
					if (count ( $dataToDisplay )) {
						echo '<div class="baseline">' . implode ( ' - ', $dataToDisplay ) . '</div>';
					}
					$t = $b->getTopic ();
					if ($t instanceof Topic && (! isset ( $topic ) || $t->getId () != $topic->getId ())) {
						if ($t->getHtmlLink ()) {
							echo '<div class="topic">' . $t->getHtmlLink () . '</div>';
						}
					}
					echo isset ( $highlighter ) ? $highlighter->getString ( $b->getHtmlDescription () ) : $b->getHtmlDescription ();
					echo '</div>';
					echo '</li>';
				} while ( $i->next () );
				?>
			</ol>
		</div>
	</main>
	<script>
	  $(document).ready(function(){
	    $('.bl').masonry({
	      itemSelector: 'li'
	    });
	  });
	</script>
</body>
</html>
