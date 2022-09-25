<?php
require_once './classes/System.class.php';
$system = new System('./config/host.json');

if (! $system->configFileExists ()) {
	header ( 'Location:'.$system->getConfigUrl() );
	exit ();
}

include_once './inc/boot.php';
session_start ();
//$system->lookForAuthenticatedUser();

$nbToDisplay = isset ( $_REQUEST ['nb'] ) ? $_REQUEST ['nb'] : 20;
$periodToCheck = isset ( $_REQUEST ['period'] ) ? $_REQUEST ['period'] : ACTIVITY_THRESHOLD1;
$date = date ( "Y-m-d", strtotime ( '-' . $periodToCheck . ' day' ) );

$publishers = $system->getTopPublishers ( $nbToDisplay );

$doc_title = 'Les éditeurs les plus représentés (TOP ' . $nbToDisplay . ')';

header ( 'charset=utf-8' );
?>
<!doctype html>
<html lang="fr">
<head>
	<meta charset="UTF-8">
	<meta name="viewport" content="width=device-width, height=device-height, initial-scale=1.0" />
	<title><?php echo ToolBox::toHtml($doc_title.' ('.$system->getProjectName().')') ?></title>
	<link rel="stylesheet" href="<?php echo $system->getSkinUrl(); ?>/theme.css" type="text/css" />
	<?php echo $system->writeHtmlHeadTagsForFavicon(); ?>
	<script src="<?php echo JQUERY_URI; ?>"></script>
	<script src="<?php echo BOOTSTRAP_JS_URI; ?>"></script>
</head>
<body>
	<?php include './inc/menu.inc.php'; ?>
	<div class="container-fluid">
		<header><h1><?php echo ToolBox::toHtml($doc_title); ?></h1></header>

		<?php
		echo '<ol>';
		foreach ( $publishers as $p ) {
			echo '<li>';
			echo $p->getHtmlName ();
			echo ' <strong>';
			echo $p->countBookmarks () > 0 ? $p->getHtmlLinkTo ( $p->countBookmarks () ) : $p->countBookmarks ();
			echo ' </strong>';
			echo '</li>';
		}
		echo '</ol>';
		?>

	</div>
</body>
</html>