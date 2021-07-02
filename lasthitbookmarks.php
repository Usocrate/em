<?php

function __autoload($class_name)
{
    $path = './classes/';
    if (is_file($path . $class_name . '.class.php')) {
        include_once $path . $class_name . '.class.php';
    } elseif ($path . $class_name . '.interface.php') {
        include_once $path . $class_name . '.interface.php';
    }
}

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
<meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, height=device-height, initial-scale=1.0, minimum-scale=1.0, maximum-scale=1.0, user-scalable=no" />
    <title><?php echo ToolBox::toHtml($doc_title).' ('.$system->projectNameToHtml().')' ?></title>
    <link rel="stylesheet" href="<?php echo $system->getSkinUrl(); ?>/theme.css" type="text/css" />
    <?php echo $system->writeHtmlHeadTagsForFavicon(); ?>
    <script type="text/javascript" src="<?php echo JQUERY_URI; ?>"></script>
    <script type="text/javascript" src="<?php echo BOOTSTRAP_JS_URI; ?>"></script>
</head>
<body id="lastHitBookmarks">
	<?php include './inc/menu.inc.php'; ?>
	<div class="container-fluid">
		<header>
			<h1><?php echo ToolBox::toHtml($doc_title) ?></h1>
		</header>
		<section>
			<?php
	        if ($bookmarks->getSize() > 0) {
	            echo '<ol class="bl">';
	            $i = $bookmarks->getIterator();
	            do {
	                $b = $i->current();
	                $cssClasses = array();
	                $cssClasses[] = $b->isPrivate() ? 'lockedBookmark' : 'unlockedBookmark';
	                echo '<li class="' . implode(' ',$cssClasses) . '">';
	                echo $b->getHtmlSnapshotLink();
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
</body>
</html>