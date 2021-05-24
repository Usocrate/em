<?php

function __autoload($class_name) {
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
    exit;
}

include_once './inc/boot.php';
session_start();

if (! $system->isUserAuthenticated()) {
    header('Location:' . $system->getLoginUrl());
    exit;
}

if (empty($_REQUEST['bookmark_id'])) {
    header('Location:' . $system->getProjectUrl());
    exit;
}

$bookmark = $system->getBookmarkById($_REQUEST['bookmark_id']);

if (! ($bookmark instanceof Bookmark)) {
    header('Location:' . $system->getProjectUrl());
    exit;
}

$withTheSameExpectedLocation = $system->getBookmarksWithTheSameExpectedLocation($bookmark);

if (isset($_POST['task_id'])) {
	print_r($_POST);
    ToolBox::formatUserPost($_POST);
    switch ($_POST['task_id']) {
        case 'b_withTheSameExpectedLocation_transfer':
        	$i = $withTheSameExpectedLocation->getIterator();
        	while($i->current()) {
        		$b = $i->current();
        		if ( strcmp($b->getTopicId(),$bookmark->getTopicId()) != 0 ) {
        			$b->setTopic($bookmark->getTopic());
        			$b->updateTopicInDB();
        		}	
        		$i->next();
        	}
        	header('Location:' . $system->getTopicUrl($bookmark->getTopic()));
            exit;
    }
}

$doc_title = 'Les resssources liées';

header('charset=utf-8');
?>
<!doctype html>
<html lang="fr">
<head>
	<meta charset="UTF-8">
	<meta name="viewport" content="width=device-width, height=device-height, initial-scale=1.0, minimum-scale=1.0, maximum-scale=1.0, user-scalable=no" />
	<title><?php echo $system->projectNameToHtml().' &gt; '.$doc_title; ?></title>
	<link rel="stylesheet" href="<?php echo $system->getSkinUrl(); ?>/theme.css" type="text/css" />
	<?php echo $system->writeHtmlHeadTagsForFavicon(); ?>
	<link rel="search" type="application/opensearchdescription+xml" href="<?php echo $system->getProjectUrl() ?>/opensearch.xml.php" title="<?php echo $system->projectNameToHtml() ?>" />
	<script type="text/javascript" src="<?php echo JQUERY_URI; ?>"></script>
	<script type="text/javascript" src="<?php echo JQUERY_UI_URI; ?>"></script>
	<script type="text/javascript" src="<?php echo BOOTSTRAP_JS_URI; ?>"></script>
</head>
<body id="bookmarkEdit">
	<div class="container-fluid">
		<header>
			<h1><?php echo ToolBox::toHtml($doc_title) ?></h1>
		</header>
		<div>
			<form method="post" action="<?php echo $_SERVER['PHP_SELF'] ?>">
			<input type="hidden" name="bookmark_id" value="<?php echo $bookmark->getId() ?>">
			<input type="hidden" name="task_id" value="b_withTheSameExpectedLocation_transfer">
			<?php
				echo '<div>';
				echo '<p>'.Toolbox::toHtml($bookmark->getTitle()).' se trouve dans la rubrique '.Toolbox::toHtml($bookmark->getTopic()->getTitle()).'.</p>';
				echo '<p>Est-ce que ces ressources doivent être dans la même rubrique ?</p>';
				echo '</div>';
				
				$i = $withTheSameExpectedLocation->getIterator();
				echo '<ul class="bl">';
				while ( $i->current() ) {
					$b = $i->current();
					$cssClasses = array ();
					$cssClasses [] = $levels [$l];
					$cssClasses [] = $b->isPrivate () ? 'lockedBookmark' : 'unlockedBookmark';
					if ($b->isInactive ()) {
						$cssClasses[] = 'inactive';
					}
					echo '<li class="' . implode ( ' ', $cssClasses ) . '">';
					echo strcmp ( $levels [$l], 'n1' ) == 0 ? $b->getHtmlSnapshotLink () : $b->getHtmlSnapshotLink ( 'bonus' );
					echo '<div class="text">';
					echo $b->getHtmlLink ();
					if ($system->isUserAuthenticated ()) {
						echo ' ' . $b->getHtmlLinkToInfo ();
					}
					if ($b->isPublisherKnown ()) {
						echo '<div class="baseline">' . $b->getHtmlLinkToPublisher () . '</div>';
					}
					echo $b->getHtmlDescription ();
					echo '</div>';
					echo '</li>';
					$i->next ();
				}
				echo '</ul>';
			?>
			<div><button type="submit" class="btn btn-primary">Oui</button> <a href="<?php echo $system->getTopicUrl( $b->getTopic() ) ?>">Non</a></div>
			</form>
		</div>
	</div>
	<script type="text/javascript">
		$(document).ready(function(){
			
		});
	</script>
</body>
</html>