<?php
function __autoload($class_name) {
	$path = './classes/';
	if (is_file ( $path . $class_name . '.class.php' )) {
		include_once $path . $class_name . '.class.php';
	} elseif ($path . $class_name . '.interface.php') {
		include_once $path . $class_name . '.interface.php';
	}
}

$system = new System ( '../config/host.json' );

if (! $system->configFileExists ()) {
	header ( 'Location:' . $system->getConfigUrl () );
	exit ();
}

include_once './inc/boot.php';
session_start ();

$system->lookForAuthenticatedUser ();

if (isset ( $_REQUEST ['b_sort_key'] )) {
	$_SESSION ['b_sort_key'] = $_REQUEST ['b_sort_key'];
}

if (! isset ( $_SESSION ['b_sort_key'] )) {
	$_SESSION ['b_sort_key'] = 'hit_frequency';
}

if (! empty ( $_REQUEST ['topic_id'] )) {
	$topic = $system->getTopicById ( $_REQUEST ['topic_id'] );
	if (! $topic instanceof Topic || ($topic->isPrivate () && ! $system->isUserAuthenticated ())) {
		header ( 'Location:' . $system->getProjectUrl () );
		exit ();
	} else {
		$subtopics = $topic->getChildren ();
		$relatedtopics = $topic->getRelatedTopics ();
		switch ($_SESSION ['b_sort_key']) {
			case 'lasthit_date' :
				$bookmarks = $topic->getBookmarksSortByLastHitDate ();
				break;
			case 'creation_date' :
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

header ( 'charset=utf-8' );
?>
<!doctype html>
<html lang="fr">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, height=device-height, initial-scale=1.0, minimum-scale=1.0, maximum-scale=1.0, user-scalable=no" />
	<?php $meta_description_content =  $topic->getDescription() ? $topic->getDescription() : $topic->countDependentBookmarks().' ressources web.'?>
	<meta name="description" content="<?php echo ToolBox::toHtml($meta_description_content) ?>" />
<meta name="author" content="<?php echo $system->projectCreatorToHtml() ?>" />
<title><?php echo $topic->countAncestors() > 1 ? strip_tags( $topic->getHtmlTitle().' ('.$topic->getHtmlPath().')' ) : strip_tags( $topic->getHtmlTitle() ); ?></title>
<link rel="stylesheet" href="<?php echo BOOTSTRAP_CSS_URI ?>" type="text/css" /><link rel="stylesheet" href="<?php echo BOOTSTRAP_CSS_THEME_URI ?>" type="text/css" />
<link rel="stylesheet" href="<?php echo C3_CSS_URI ?>" type="text/css" />
<link rel="stylesheet" href="<?php echo $system->getSkinUrl(); ?>/main.css" type="text/css" />
<link rel="icon" type="image/x-icon" href="<?php echo $system->getSkinUrl(); ?>/favicon.ico" />
<link rel="search" type="application/opensearchdescription+xml" href="<?php echo $system->getProjectUrl() ?>/opensearch.xml.php" title="<?php echo $system->projectNameToHtml() ?>" />
<link rel="alternate" type="application/rss+xml" title="<?php echo $system->getProjectName() ?> &gt; Canal RSS (les nouveautés de la rubrique)" href="topic_lastaddedbookmarks.rss.php?topic_id=<?php echo $topic->getId(); ?>" />
<script type="text/javascript" src="<?php echo D3_URI ?>"></script>
<script type="text/javascript" src="<?php echo C3_URI ?>"></script>
<script type="text/javascript" src="<?php echo JQUERY_URI; ?>"></script>
<script type="text/javascript" src="<?php echo BOOTSTRAP_JS_URI; ?>"></script>
	<?php include_once './inc/ga_tracker.inc.php'?>
</head>
<body id="topic" class="container">
	<header>
    	<div class="brand"><?php echo $system->getHtmlLink() ?></div>
    	<h1>
    	   <?php 
        	   echo $topic->getHtmlTitle();
        	   if ($topic->countAncestors() > 1) echo ' <small class="topicPath">('.$topic->getHtmlPath().')</small>';
    	   ?>
        </h1>    	   
    </header>
	<?php
	if ($subtopics->getSize () > 0) {
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
		if ($bookmarks->getSize () > 0) {
			$levelBreakDown = array (
					'n1' => 3,
					'n2' => 4,
					'n3' => ($bookmarks->getSize () + 1 - 7) 
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
					
					/*
					 * switch ($_SESSION ['b_sort_key']) {
					 * case 'hit_frequency' :
					 * if ($b->getHitFrequency () > 0) {
					 * echo '<div><span>Taux de consultation : </span><em>' . Round ( $b->getHitFrequency () * 100, 2 ) . '</em>%</div>';
					 * }
					 * break;
					 * case 'lasthit_date' :
					 * if ($b->getLastHitDateFr ()) {
					 * echo '<div><span>Consulté le </span><em>' . $b->getLastHitDateFr () . '</strong></em>';
					 * }
					 * break;
					 * case 'creation_date' :
					 * echo '<div><span>Découvert en </span><em>' . $b->getHtmlLinkToCreationYear () . '</em></div>';
					 * }
					 */
					
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
					'hit_frequency',
					'Les plus utiles' 
			);
			$sortBarItems [] = array (
					'creation_date',
					'Les nouveautés' 
			);
			
			if ($system->isUserAuthenticated ()) {
				$sortBarItems [] = array (
						'lasthit_date',
						'Les dernières consultées' 
				);
			}
			
			echo '<div id="sortBar"><span>D\'abord ...</span>';
			echo '<ul>';
			foreach ( $sortBarItems as $i ) {
				if (strcasecmp ( $i [0], $_SESSION ['b_sort_key'] ) == 0) {
					echo '<li class="emphased">' . ToolBox::toHtml ( $i [1] ) . '</li>';
				} else {
					echo '<li><a href="' . $system->getTopicUrl ( $topic ) . '&amp;b_sort_key=' . $i [0] . '">' . ToolBox::toHtml ( $i [1] ) . '</a></li>';
				}
			}
			echo '</ul>';
			echo '</div>';
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
		<?php if ($system->isUserAuthenticated ()) : ?>
		<div class="btn-group">
			<button type="button" class="btn btn-default dropdown-toggle" data-toggle="dropdown" aria-expanded="false">
				Action <span class="caret"></span>
			</button>
			<ul class="dropdown-menu" role="menu">
				<li><a href="<?php echo $system->getTopicNewBookmarkEditionUrl($topic) ?>">Nouvelle ressource</a></li>
				<li><a href="<?php echo $system->getTopicEditionUrl($topic) ?>">Modification</a></li>
				<li><a href="<?php echo $system->getTopicNewSubtopicEditionUrl($topic) ?>">Nouvelle sous-rubrique</a></li>
				<li><a href="<?php echo $system->getTopicShortCutEditionUrl($topic) ?>">Raccourcis</a></li>
				<?php
				if (! $topic->isMainTopic ()) {
					echo '<li><a href="' . $system->getTopicRemovalUrl ( $topic ) . '">Suppression</a></li>';
				}
				?>
				<li><a href="<?php echo $system->getTopicExportationUrl($topic) ?>" target="_blank"">Exportation</a></li>
			</ul>
		</div>
		<?php endif; ?>
	<?php include './inc/footer.inc.php'; ?>
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
		    $year_serie [] = $year.'-12-31';
		    $count_serie [] = ( int ) $count;
		}
		array_push($chart_data, $year_serie, $count_serie);
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