<?php
require_once './classes/System.class.php';
$system = new System('./config/host.json');

if (! $system->configFileExists ()) {
	header ( 'Location:' . $system->getConfigUrl () );
	exit ();
}

include_once './inc/boot.php';
session_start ();

$system->lookForAuthenticatedUser ();

if (! $system->isUserAuthenticated () && ! $system->isTourRequested()) {
	header ( 'Location:' . $system->getLoginUrl() );
	exit ();
}

if (isset ( $_REQUEST ['b_sort'] )) {
	$_SESSION ['b_sort'] = urldecode ( $_REQUEST ['b_sort'] );
}

if (! isset ( $_SESSION ['b_sort'] )) {
	$_SESSION ['b_sort'] = 'Most frequently hit first';
}

if (! empty ( $_REQUEST ['topic_id'] )) {
	$topic = $system->getTopicById ( $_REQUEST ['topic_id'] );
	if (! $topic instanceof Topic || ($topic->isPrivate () && ! $system->isUserAuthenticated ())) {
		header ( 'Location:' . $system->getProjectUrl () );
		exit ();
	} else {
		$subtopics = $topic->getChildren ();
		$relatedtopics = $topic->getRelatedTopics ();
		switch ($_SESSION ['b_sort']) {
			case 'Last hit first' :
				$bookmarks = $topic->getBookmarksSortByLastHitDate ();
				break;
			case 'Last focused first' :
				$bookmarks = $topic->getBookmarksSortByLastFocusDate ();
				break;
			case 'Last created first' :
				$bookmarks = $topic->getBookmarksSortByCreationDate ();
				break;
			default :
				$bookmarks = $topic->getBookmarks ();
		}
		$bookmarkCreationStats = $topic->countDependentBookmarkCreationYearly ();
	}
	$system->setLastInvolvedTopic ( $topic );
} else {
	header ( 'Location:' . $system->getProjectUrl () );
	exit ();
}
// print_r($_SESSION);>
header ( 'charset=utf-8' );

$meta_description_content = $topic->getDescription () ? $topic->getDescription () : $topic->countDependentBookmarks () . ' ressources web.';

?>
<!doctype html>
<html lang="fr">
<head>
	<title><?php echo $topic->countAncestors() > 1 ? strip_tags( $topic->getHtmlTitle().' ('.$topic->getHtmlPath().')' ) : strip_tags( $topic->getHtmlTitle() ); ?></title>
	<?php echo $system->writeHeadCommonMetaTags(); ?>
	<meta name="author"	content="<?php echo $system->projectCreatorToHtml() ?>" />
	<meta name="description" content="<?php echo ToolBox::toHtml($meta_description_content) ?>" />
	<meta property="og:description" content="<?php echo ToolBox::toHtml($meta_description_content) ?>" />
	<meta property="og:locale" content="fr_FR" />
	<meta property="og:site_name" content="<?php echo ToolBox::toHtml($system->getProjectName()) ?>" />
	<meta property="og:title" content="<?php echo $topic->countAncestors() > 1 ? strip_tags( $topic->getHtmlTitle().' ('.$topic->getHtmlPath().')' ) : strip_tags( $topic->getHtmlTitle() ); ?>" />
	<meta property="og:type" content="website" />
	<meta property="og:url" content="<?php echo $topic->getUrl() ?>" />
	<?php echo $system->writeHeadCommonLinkTags(); ?>
	<link rel="stylesheet" href="<?php echo C3_CSS_URI ?>" type="text/css" />
	<script src="<?php echo D3_URI ?>"></script>
	<script src="<?php echo D3CHART_URI ?>"></script>
	<script src="<?php echo C3_URI ?>"></script>
	<script src="<?php echo JQUERY_URI; ?>"></script>
	<script src="<?php echo MASONRY_URI; ?>"></script>
	<script src="<?php echo BOOTSTRAP_JS_URI; ?>"></script>
</head>
<body id="topic">
	<?php include './inc/menu.inc.php'; ?>
	<main>
		<header class="d-lg-flex align-items-center">
			<?php
			$h1 = $topic->getHtmlTitle ();
			if ($topic->countAncestors () > 1) {
				$h1 .= ' <small class="topicPath">(' . $topic->getHtmlPath () . ')</small>';
			}
			?>
			<h1 class="flex-lg-grow-1"><?php echo $h1 ?></h1>
	        
	        <?php if ($system->isUserAuthenticated ()) : ?>
			<div class="btn-group">
				<a class="btn btn-light" href="<?php echo $system->getTopicNewBookmarkEditionUrl($topic) ?>"><i class="fas fa-plus"></i></a>
				<button type="button" class="btn btn-light dropdown-toggle"	data-bs-toggle="dropdown"><i class="fas fa-ellipsis-h"></i></button>
				<ul class="dropdown-menu">
					<li><a class="dropdown-item" href="<?php echo $system->getTopicExportationUrl($topic) ?>" target="_blank"">exportation</a></li>
					<li><a class="dropdown-item" href="<?php echo $system->getTopicNewSubtopicEditionUrl($topic) ?>">ajout d'une sous-rubrique</a></li>
					<li><a class="dropdown-item" href="<?php echo $system->getTopicShortCutEditionUrl($topic) ?>">édition des raccourcis</a></li>
					<li><a class="dropdown-item" href="<?php echo $system->getTopicEditionUrl($topic) ?>">édition des metadonnées</a></li>
					<?php
						if (! $topic->isMainTopic ()) {
							echo '<li><a class="dropdown-item" href="' . $system->getTopicRemovalUrl ( $topic ) . '">suppression...</a></li>';
						}
					?>					
				</ul>
			</div>
			<?php endif; ?>
			
	    </header>
		<?php
		if (count ( $subtopics ) > 0) {
			echo '<section id="subtopics">';
			echo '<ol  class="tl">';
			$subtopics->setTopicsWeight ();
			$i = $subtopics->getIterator ();
			while ( $i->current () ) {
				echo '<li>' . $i->current ()->getHtmlLink () . '</li>';
				$i->next ();
			}
			if ($system->isUserAuthenticated ()) {
				echo '<li class="virtual"><a href="' . $system->getTopicNewSubtopicEditionUrl ( $topic ) . '">+</a></li>';
			}
			echo '</ol>';
			echo '</section>';
		}
		?>
		<section>
			<?php
			if (count ( $bookmarks ) > 0) {
				$levelBreakDown = array (
						'n1' => 3,
						'n2' => 4,
						'n3' => (count ( $bookmarks ) + 1 - 7)
				);
				$levels = array_keys ( $levelBreakDown );

				$i = $bookmarks->getIterator ();

				echo '<ol class="bl">';
				for($l = 0; $l < count ( $levels ); $l ++) {
					$processItems = 0;

					while ( $i->current () && $processItems < $levelBreakDown [$levels [$l]] ) {
						$b = $i->current ();
						$cssClasses = array ();
						$cssClasses [] = $levels [$l];
						$cssClasses [] = $b->isPrivate () ? 'lockedBookmark' : 'unlockedBookmark';
						if ($b->isInactive ()) {
							$cssClasses [] = 'inactive';
						}
						echo '<li class="' . implode ( ' ', $cssClasses ) . '">';
						if (strcmp ( $levels [$l], 'n3' ) != 0) {
							echo '<div class="theater">' . $b->getHtmlSnapshotLink () . '</div>';
						}
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
						$processItems ++;
						$i->next ();
					}
				}
				if ($system->isUserAuthenticated ()) {
					echo '<li class="virtual"><a href="' . $system->getTopicNewBookmarkEditionUrl ( $topic ) . '">+</a></li>';
				}
				echo '</ol>';

				// tri
				$sortBarItems = array ();

				$sortBarItems [] = array (
						'Most frequently hit first',
						'D\'abord les plus utiles'
				);

				$sortBarItems [] = array (
						'Last created first',
						'D\'abord les nouveautés'
				);

				if ($system->isUserAuthenticated ()) {
					$sortBarItems [] = array (
							'Last focused first',
							'D\'abord les dernières utilisées'
					);
				}

				echo '<nav class="secondary">';
				echo '<ul class="nav justify-content-center">';
				foreach ( $sortBarItems as $i ) {
					if (strcasecmp ( $i [0], $_SESSION ['b_sort'] ) == 0) {
						echo '<li class="nav-item"><a class="nav-link active">' . ToolBox::toHtml ( $i [1] ) . '</a></li>';
					} else {
						echo '<li class="nav-item"><a class="nav-link" href="' . $system->getTopicUrl ( $topic ) . '&amp;b_sort=' . urlencode ( $i [0] ) . '">' . ToolBox::toHtml ( $i [1] ) . '</a></li>';
					}
				}
				echo '</ul></nav>';
			} else {
				echo '<p>Aucune ressource enregistrée.';
				if ($system->isUserAuthenticated ()) {
					echo '<br/><a href="' . $system->getTopicNewBookmarkEditionUrl ( $topic ) . '"><small>Nouvelle ressource</small></a>';
				}
				echo '</p>';
			}
			?>
			</section>
			<?php
			if ($relatedtopics instanceof TopicCollection && $relatedtopics->hasElement ()) {
				echo '<section>';
				echo '<h2>Voir aussi</h2>';
				echo '<ol class="tl">';
				$i = $relatedtopics->getIterator ();
				while ( $i->current () ) {
					echo '<li>';
					echo $i->current ()->getHtmlLink ();
					if ($i->current ()->countAncestors () > 1) {
						echo ' <small>(<span class="topicPath">' . $i->current ()->getHtmlPath () . '</span>)</small>';
					}
					echo '</li>';
					$i->next ();
				}
				// echo '<li class="virtual"><a href="'.$system->getTopicShortCutEditionUrl($topic).'">+</a></li>';
				echo '</ol>';
				echo '</section>';
			}
			if (isset ( $bookmarkCreationStats ) && is_array ( $bookmarkCreationStats )) {
				echo '<section class="bonus">';
				echo '<h2>Découvertes</h2>';
				echo '<div id="chart_container" class="chart_container"></div>';
				// echo '<div class="chart_legend">Ci-dessus le nombre de découvertes sur ce thème <strong>' . ToolBox::toHtml ( $topic->getTitle () ) . '</strong>, par année.</div>';
				echo '</section>';
			}
			?>
	</main>

	<script>
		<?php
		$chart_data = array ();
		$year_serie = array (
				'creation_year'
		);
		$count_serie = array (
				'creation_count'
		);
		foreach ( $bookmarkCreationStats as $year => $count ) {
			$year_serie [] = $year . '-12-31';
			$count_serie [] = ( int ) $count;
		}
		array_push ( $chart_data, $year_serie, $count_serie );
		?>

		var chart = c3.generate({
		    bindto: '#chart_container',
		    data: {
		        columns: <?php echo json_encode ( $chart_data ) ?>,
			    x:'creation_year',
				names:{
					year : 'Année de découverte',
					count : 'Découvertes'
				},
				labels: true,
				type:'bar'
		    },
		    legend: {
				show:false
			},
			tooltip: {
				show:false
			},
		    bar: {
		        width: {
		            ratio: 0.3
		        }
		    },
		    axis: {
		        x: {
		            type: 'timeseries',
		            tick:{
		                format:'%Y'
				    }
		        }
		    }
		});
	</script>
</body>
</html>
