<?php
require_once './classes/System.class.php';
$system = new System('./config/host.json');

if (! $system->configFileExists()) {
    header('Location:' . $system->getConfigUrl());
    exit();
}

include_once './inc/boot.php';

session_start();

$system->lookForAuthenticatedUser();

$nbToDisplay = isset($_REQUEST['nb']) ? $_REQUEST['nb'] : 20;
$bookmarks = $system->getLastHitBookmarkCollection($nbToDisplay);
$doc_title = 'Dernières consultations';

header('charset=utf-8');
?>
<!doctype html>
<html lang="fr">
<head>
	<title><?php echo ToolBox::toHtml($doc_title).' ('.$system->projectNameToHtml().')' ?></title>
	<meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, height=device-height, initial-scale=1.0" />
    <link rel="stylesheet" href="<?php echo $system->getSkinUrl(); ?>/theme.css" type="text/css" />
    <script src="<?php echo JQUERY_URI; ?>"></script>
	<script src="<?php echo MASONRY_URI; ?>"></script>    
    <script src="<?php echo BOOTSTRAP_JS_URI; ?>"></script>
    <?php echo $system->writeHtmlHeadTagsForFavicon(); ?>
</head>
<body id="lastHitBookmarks">
	<?php include './inc/menu.inc.php'; ?>
	<div class="container-fluid">
		<header>
			<h1><?php echo ToolBox::toHtml($doc_title) ?></h1>
		</header>
		<section>
			<?php
	        if (count($bookmarks) > 0) {
	            echo '<ol class="bl">';
	            $i = $bookmarks->getIterator();
	            do {
	                $b = $i->current();
	                $cssClasses = array();
	                $cssClasses[] = $b->isPrivate() ? 'lockedBookmark' : 'unlockedBookmark';
	                echo '<li class="' . implode(' ',$cssClasses) . '">';
	                echo '<div class="theater">'.$b->getHtmlSnapshotLink().'</div>';
	                echo '<div class="text">';
	                echo $b->getHtmlLink();
	                if ($system->isUserAuthenticated()) {
	                    echo ' ' . $b->getHtmlLinkToInfo();
	                }
	                // lien vers la rubrique
	                if ($b->isTopicKnown()) {
	                    echo '<div class="topic">'.$b->getHtmlLinkToTopic().'</div>';
	                }
	                echo '</div>';
	                echo '</li>';
	            } while ($i->next());
	            echo '</ol>';
	        } else {
	            echo '<p>Aucune ressource consultée !</p>';
	        }
	    ?>
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