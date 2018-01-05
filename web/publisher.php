<?php
function __autoload($class_name) {
	$path = '../classes/';
	if (is_file ( $path . $class_name . '.class.php' )) {
		include_once $path . $class_name . '.class.php';
	} elseif ($path . $class_name . '.interface.php') {
		include_once $path . $class_name . '.interface.php';
	}
}

$system = new System ( '../config/host.json' );

if (! $system->configFileExists ()) {
	header ( 'Location:'.$system->getConfigUrl() );
	exit;
}

include_once './inc/boot.php';
session_start();
//$system->lookForAuthenticatedUser();

if (isset ( $_REQUEST ['b_sort'] )) {
	$_SESSION ['b_sort'] = $_REQUEST ['b_sort'];
}

if (! isset ( $_SESSION ['b_sort'] )) {
	$_SESSION ['b_sort'] = 'Most frequently hit first';
}

// identification de l'éditeur
if (! empty ( $_REQUEST ['publisher_name'] )) {
	$publisher = $system->getPublisherByName($_REQUEST ['publisher_name']);
}

// en cas d'échec
if (empty($publisher) || ! ($publisher instanceof Publisher)) {
	header ( 'Location:./toppublishers.php');
	exit;
}

// sinon récupération des signets
switch ($_SESSION ['b_sort']) {
	case 'Last hit first' :
		$bookmarks = $publisher->getBookmarkCollectionSortedByLastHitDate();
		break;
	case 'Last created first' :
		$bookmarks = $publisher->getBookmarkCollectionSortedByCreationDate();
		break;
	default :
		$bookmarks = $publisher->getBookmarkCollectionSortedByHitFrequency();
}

$doc_title = $publisher->getName();

header ( 'charset=utf-8' );
?>
<!doctype html>
<html lang="fr">
<head>
	<meta charset="UTF-8">
	<meta name="viewport" content="width=device-width, height=device-height, initial-scale=1.0, minimum-scale=1.0, maximum-scale=1.0, user-scalable=no" />
	<title><?php echo ToolBox::toHtml($doc_title).' ('.$system->projectNameToHtml().')' ?></title>
	<link rel="stylesheet" href="<?php echo BOOTSTRAP_CSS_URI ?>" type="text/css" /><link rel="stylesheet" href="<?php echo BOOTSTRAP_CSS_THEME_URI ?>" type="text/css" />
	<link rel="stylesheet" href="<?php echo $system->getSkinUrl(); ?>/main.css" type="text/css" />
	<link rel="apple-touch-icon" sizes="180x180" href="<?php echo $system->getSkinUrl(); ?>/apple-touch-icon.png">
	<link rel="icon" type="image/png" href="<?php echo $system->getSkinUrl(); ?>/favicon-32x32.png" sizes="32x32">
	<link rel="icon" type="image/png" href="<?php echo $system->getSkinUrl(); ?>/favicon-16x16.png" sizes="16x16">
	<link rel="manifest" href="<?php echo $system->getSkinUrl(); ?>/manifest.json">
	<link rel="mask-icon" href="<?php echo $system->getSkinUrl(); ?>/safari-pinned-tab.svg" color="#5bbad5">
	<link rel="shortcut icon" href="<?php echo $system->getSkinUrl(); ?>/favicon.ico">
	<meta name="msapplication-config" content="<?php echo $system->getSkinUrl(); ?>/browserconfig.xml">
	<meta name="theme-color" content="#8ea4bc">
	<link rel="search" type="application/opensearchdescription+xml" href="<?php echo $system->getProjectUrl() ?>/opensearch.xml.php" title="<?php echo $system->projectNameToHtml() ?>" />
	<script type="text/javascript" src="<?php echo JQUERY_URI; ?>"></script>
	<script type="text/javascript" src="<?php echo MASONRY_URI; ?>"></script>
	<script type="text/javascript" src="<?php echo BOOTSTRAP_JS_URI; ?>"></script>
</head>
<body id="publisher" class="container">
	<header>
		<div class="brand"><?php echo $system->getHtmlLink() ?></div>
		<h1><?php echo ToolBox::toHtml($doc_title) ?></h1>
	</header>
	<div>
		<div>
			<?php
			if ($bookmarks->getSize () > 0) {
				$i = $bookmarks->getIterator ();
				echo '<ul class="bl">';
				do {
					$cssClasses = array ();
					$cssClasses [] = $i->current ()->isPrivate () ? 'lockedBookmark' : 'unlockedBookmark';
					
					if ($i->current ()->isInactive ()) {
						$cssClasses [] = 'inactive';
					}
					
					$html = '<li class="' . implode ( ' ', $cssClasses ) . '">';
					$html .= $i->current ()->getHtmlSnapshotLink();
					$html .= '<div class="text">';
					$html .= $i->current ()->getHtmlLink ();
					$html .= ' ' . $i->current ()->getHtmlLinkToInfo ();
					$dataToDisplay = array ();
					if ($i->current ()->getCreator ()) {
						$dataToDisplay [] = ToolBox::toHtml ( $i->current ()->getCreator () );
					}
					if ($i->current ()->getCreationYear ()) {
						$dataToDisplay [] = Year::getHtmlLinkToYearDoc ( $i->current ()->getCreationYear () );
					}
					if (count ( $dataToDisplay )) {
						$html .= '<div class="baseline">' . implode ( ' - ', $dataToDisplay ) . '</div>';
					}
					if ($i->current ()->getTopic ()) {
						$html .= '<div class="topic">' . $i->current ()->getHtmlLinkToTopic () . '</div>';
					}
					$html .= $i->current()->getHtmlDescription();
					$html .= '</div>';
					$html .= '</li>';
					echo $html;
				} while ( $i->next () );
				echo '</ul>';
			}
			?>
		</div>
		<div id="sortBar">
		<?php
			$sortBarItems = array ();

			$sortBarItems [] = array (
					'Most frequently hit first',
					'Les plus utiles'
			);

			$sortBarItems [] = array (
					'Last created first',
					'Les nouveautés'
			);

			echo '<span>D\'abord ...</span>';
			echo '<ul>';
			foreach ( $sortBarItems as $i ) {
				if (strcmp ( $i[0], $_SESSION ['b_sort'] ) == 0) {
					echo '<li class="emphased">' . ToolBox::toHtml ( $i [1] ) . '</li>';
				} else {
					$href = './publisher.php?publisher_name='.ToolBox::toHtml( $publisher->getName()).'&amp;b_sort='.$i[0];
					echo '<li><a href="'.$href.'">'.ToolBox::toHtml($i[1]).'</a></li>';
				}
			}
			echo '</ul>';
		?>
		</div>
	</div>
	<?php include './inc/footer.inc.php'; ?>
	<script type="text/javascript">
		$(document).ready(function(){
			$('.bl').masonry({
				itemSelector:'li'
			});
		});
	</script>
</body>
</html>
