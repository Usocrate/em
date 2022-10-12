<?php
require_once '../classes/System.class.php';
$system = new System('../config/host.json');

if (! $system->configFileExists ()) {
	header ( 'Location:'.$system->getConfigUrl() );
	exit ();
}

include_once '../inc/boot.php';
session_start ();

$system->lookForAuthenticatedUser ();

if (! $system->isUserAuthenticated()) {
	header('Location:' . $system->getLoginUrl());
	exit();
}

// le titre du document
$doc_title = 'Maintenance';

// paramètres de publication
$count = isset ( $_REQUEST ['nb'] ) ? $_REQUEST ['nb'] : 20;

$bookmarks = $system->getForgottenBookmarkCollection($count);

header ( 'charset=utf-8' );
?>
<!doctype html>
<html lang="fr">
<head>
	<title><?php echo $system->projectNameToHtml().' &gt; '.$doc_title ?></title>
	<meta charset="UTF-8">
	<meta name="viewport" content="width=device-width, height=device-height, initial-scale=1.0" />
	<link rel="stylesheet" href="<?php echo $system->getSkinUrl(); ?>/theme.css" type="text/css" />
	<script src="<?php echo JQUERY_URI; ?>"></script>
	<script src="<?php echo BOOTSTRAP_JS_URI; ?>"></script>
	<?php echo $system->writeHtmlHeadTagsForFavicon(); ?>
</head>
<body>
	<?php include 'menu.inc.php'; ?>
	<div class="container-fluid">
		<header><h1><?php echo ToolBox::toHtml($doc_title) ?></h1></header>
		<section>
		<h2>Les ressources oubliées</h2>
		<?php
		if (count($bookmarks) > 0) {
			echo '<ul>';
			$i = $bookmarks->getIterator ();
			do {
				$b = $i->current ();
				$class = $b->isPrivate () ? 'lockedBookmark' : 'unlockedBookmark';
				echo '<li class="' . $class . '">';
				echo $b->getHtmlLink ();
				echo ' ';
				echo $b->getHtmlLinkToInfo();
				
				// lien vers la rubrique
				echo '<p>' . $b->getHtmlLinkToTopic () . '</p>';
				//echo '<p>Dernière activité : '.$b->getlastfocusDateFr().'</p>';
				echo '<p>'.$b->getHtmlDescription ().'</p>';
				echo '</li>';
			} while ( $i->next () );
			echo '</ul>';
		} else {
			echo '<p>Aucune ressource !</p>';
		}
		?>
		</section>
	</div>
</body>
</html>