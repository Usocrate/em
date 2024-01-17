<?php
require_once './classes/System.class.php';
$system = new System('./config/host.json');

if (! $system->configFileExists()) {
	header ( 'Location:'.$system->getConfigUrl() );
	exit ();
}

include_once './inc/boot.php';
session_start();

$system->lookForAuthenticatedUser();

if (! $system->isUserAuthenticated () && ! $system->isTourRequested()) {
	header ( 'Location:' . $system->getLoginUrl() );
	exit ();
}

$playlists = $system->getPlayLists();
$doc_title = 'Les playlists (webradios)';

header('charset=utf-8');
?>
<!doctype html>
<html lang="fr">
<head>
	<meta charset="UTF-8">
	<meta name="viewport" content="width=device-width, height=device-height, initial-scale=1.0" />
	<meta name="Description" content="Les playlists" />
	<meta name="author" content="<?php echo ToolBox::toHtml($system->getProjectCreator()); ?>" />
	<title><?php echo ToolBox::toHtml($doc_title.' ('.$system->getProjectName().')'); ?></title>
	<link rel="stylesheet" href="<?php echo $system->getSkinUrl(); ?>/theme.css" type="text/css" />
	<script src="<?php echo JQUERY_URI; ?>"></script>
	<script src="<?php echo BOOTSTRAP_JS_URI; ?>"></script>
	<?php echo $system->writeHtmlHeadTagsForFavicon(); ?>
</head>
<body>
	<main class="container-fluid">
		<header>
			<h1 itemprop="name"><?php echo ToolBox::toHtml($doc_title) ?></h1>
		</header>
		<div>
			<?php
			if (count($playlists)>0) {
				//
				// des playlists sont affichables
				//
				echo '<ul>';
				foreach ($playlists as $pl) {
					echo $pl->getHtmlLi();
				}
				echo '</ul>';
			} else {
				echo '<p>Aucune playlist n\'est affichable ...</p>';
			}
			?>
		</div>
	</main>
</body>
</html>
