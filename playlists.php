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

if (! $system->configFileExists()) {
	header ( 'Location:'.$system->getConfigUrl() );
	exit ();
}

include_once './inc/boot.php';
session_start();

$system->lookForAuthenticatedUser();

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
	<?php echo $system->writeHtmlHeadTagsForFavicon(); ?>
	<script type="text/javascript" src="<?php echo JQUERY_URI; ?>"></script>
<script type="text/javascript" src="<?php echo BOOTSTRAP_JS_URI; ?>"></script>
</head>
<body>
	<div class="container-fluid">
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
	</div>
</body>
</html>
