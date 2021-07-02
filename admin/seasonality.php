<?php

function __autoload($class_name)
{
    $path = '../classes/';
    if (is_file($path . $class_name . '.class.php')) {
        include_once $path . $class_name . '.class.php';
    } elseif ($path . $class_name . '.interface.php') {
        include_once $path . $class_name . '.interface.php';
    }
}

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
	<meta charset="UTF-8">
	<meta name="viewport" content="width=device-width, height=device-height, initial-scale=1.0" />
	<meta name="author" content="<?php echo $system->projectCreatorToHtml() ?>" />
	<meta name="msapplication-config" content="<?php echo $system->getSkinUrl(); ?>/browserconfig.xml">
	<meta name="theme-color" content="#8ea4bc">
	<title><?php echo ToolBox::toHtml($doc_title.' ('.$system->getProjectName().')') ?></title>
	<link rel="stylesheet" href="<?php echo C3_CSS_URI ?>" type="text/css" />
	<link rel="stylesheet" href="<?php echo $system->getSkinUrl(); ?>/theme.css" type="text/css" />
	<link rel="apple-touch-icon" sizes="180x180" href="<?php echo $system->getSkinUrl(); ?>/apple-touch-icon.png">
	<link rel="icon" type="image/png" href="<?php echo $system->getSkinUrl(); ?>/favicon-32x32.png" sizes="32x32">
	<link rel="icon" type="image/png" href="<?php echo $system->getSkinUrl(); ?>/favicon-16x16.png" sizes="16x16">
	<link rel="manifest" href="<?php echo $system->getSkinUrl(); ?>/manifest.json">
	<link rel="mask-icon" href="<?php echo $system->getSkinUrl(); ?>/safari-pinned-tab.svg" color="#5bbad5">
	<link rel="shortcut icon" href="<?php echo $system->getSkinUrl(); ?>/favicon.ico">
	<script src="<?php echo JQUERY_URI; ?>"></script>
	<script src="<?php echo BOOTSTRAP_JS_URI; ?>"></script>
	<script src="<?php echo MASONRY_URI; ?>"></script>
</head>
<body>
	<?php include 'menu.inc.php'; ?>
	<div class="container-fluid">
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
	</div>
	<script>
	  $(document).ready(function(){
	    $('.bl').masonry({
	      itemSelector: 'li'
	    });
	  });
	</script>
</body>
</html>
