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
	exit ();
}

include_once './inc/boot.php';
session_start ();

if (! $system->isUserAuthenticated ()) {
	header ( 'Location:' . $system->getLoginUrl() );
	exit ();
}

$bookmark = $system->getBookmarkById ( $_REQUEST ['bookmark_id'] );
if (! ($bookmark instanceof Bookmark)) {
	header ( 'Location: ' . $system->getProjectUrl () );
	exit ();
}

$doc_title = $bookmark->getTitle ();

header ( 'charset=utf-8' );
?>
<!doctype html>
<html lang="fr">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, height=device-height, initial-scale=1.0, minimum-scale=1.0, maximum-scale=1.0, user-scalable=no" />
<title><?php echo $system->projectNameToHtml().' &gt; '.$doc_title; ?></title>
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
<script type="text/javascript" src="<?php echo D3_URI ?>"></script>
<script type="text/javascript" src="<?php echo D3CHART_URI ?>"></script>
<script type="text/javascript" src="<?php echo C3_URI ?>"></script>
<script type="text/javascript" src="<?php echo JQUERY_URI; ?>"></script>
<script type="text/javascript" src="<?php echo BOOTSTRAP_JS_URI; ?>"></script>
</head>
<body id="bookmark" class="container">
	<header>
		<div class="brand"><?php echo $system->getHtmlLink() ?></div>
		<h1><?php echo ToolBox::toHtml($doc_title) ?></h1>
	</header>
	<div>
		<div class="row">
			<div class="col-md-6"><?php echo $bookmark->getHtmlSnapshotLink(); ?></div>
			<div class="col-md-6">
				<div class="text">
					<p>
						<?php
						$dataToDisplay = array ();
						$dataToDisplay [] = 'Découvert le ' . $bookmark->getHtmlCreationDateFr ();
						if ($bookmark->isPublisherKnown()) {
							$dataToDisplay [] = $bookmark->getHtmlLinkToPublisher ();
						}
						//$dataToDisplay [] = $bookmark->getHtmlHitFrequency ();
						if (count ( $dataToDisplay )) {
							echo '<div>' . implode ( ' - ', $dataToDisplay ) . '</div>';
						}
						?>
						</p>
					<p>
						<?php
							echo $bookmark->getHtmlLinkToTopic ();
							echo $bookmark->getHtmlDescription ();
						?>
					</p>
					<p>
							<?php
							$dataToDisplay = array ();
							if ($bookmark->rss_url) {
								$dataToDisplay [] = $bookmark->getHtmlLinkToRss ();
							}
							if ($system->isUserAuthenticated ()) {
								$dataToDisplay [] = $bookmark->getHtmlLinkToEdition ( NULL, 'mixed' );
								if ($bookmark->getLogin ())
									$dataToDisplay [] = $bookmark->getHtmlLinkToPassword ( NULL, 'mixed' );
							}
							echo implode ( ' | ', $dataToDisplay );
							?>
						</p>
				</div>
			</div>
		</div>

		<?php
		  $chartToDisplay = array();
		  /////////////////////////////////////
		  //  Chart 1
		  /////////////////////////////////////
		  $data1= $bookmark->countDayWithHitYearly ();
		  if (isset ( $data1 ) && is_array ( $data1 ) && array_sum ( $data1 ) > 0) {
		      $chart = array();
		      
		      // données
		      $chart['data'] = array ();
		      $year_serie = array (
		          'hit_year'
		      );
		      $count_serie = array (
		          'hit_count'
		      );
		      $i = 0;
		      foreach ( $data1 as $year => $count ) {
		          $i++;
		          $year_serie [] = $year.'-12-31';
		          $count_serie [] = ( int ) $count;
		      }
		      array_push($chart['data'], $year_serie, $count_serie);
		      
		      // title
		      $chart['title'] = 'Depuis découverte';
		      
		      // container
		      $chart['container_id'] = 'chart1_container';
		      $chartToDisplay['chart1'] = $chart;
		      unset($chart);
		  }
		  /////////////////////////////////////
		  //  Chart 2
		  /////////////////////////////////////
		  $data2 = $bookmark->countHitForRecentDays ();
		  if (isset ( $data2 ) && is_array ( $data2 ) && array_sum ( $data2 ) > 0) {
		      $chart = array();
		      
		      // données
		      $chart['data'] = array ();
		      $day_serie = array (
		          'hit_day'
		      );
		      $count_serie = array (
		          'hit_count'
		      );
		      $i = 0;
		      foreach ( $data2 as $index => $count ) {
		          $i++;
		          $hitday_timestamp = time () - ($index * 86400);
		          $day_serie [] = date ( 'Y-m-d', $hitday_timestamp );
		          $count_serie [] = ( int ) $count;
		      }
		      array_push($chart['data'], $day_serie, $count_serie);

		      // title
		      $chart['title'] = 'Au cours des '.ACTIVITY_THRESHOLD1.' derniers jours';
		      
		      // container
		      $chart['container_id'] = 'chart2_container';
		      $chartToDisplay['chart2'] = $chart;
		      unset($chart);
		  }
    	 
    	 if (count($chartToDisplay)>1) {
    	   echo '<div class="row">';
    	   foreach ($chartToDisplay as $c) {
    	       echo '<div class="col-md-6">';
    	       echo '<h3>'.ToolBox::toHtml($c['title']).'</h3>';
    	       echo '<div id="'.$c['container_id'].'" class="chart_container"></div>';
    	       echo '</div>';
    	   }
    	   echo '</div>';
    	 } elseif (count($chartToDisplay)==1) {
    	     $c = current($chartToDisplay);
    	     echo '<div>';
    	     echo '<h3>'.ToolBox::toHtml($c['title']).'</h3>';
    	     echo '<div id="'.$c['container_id'].'" class="chart_container"></div>';
    	     echo '</div>';    	     
    	 }
    	 //print_r($chartToDisplay);
	     ?>
	</div>
	
	<?php include './inc/footer.inc.php'; ?>
	
    <script type="text/javascript">
        <?php if (isset($chartToDisplay['chart1'])): ?>
        var chart1 = c3.generate({ 
		    bindto: '#<?php echo $chartToDisplay['chart1']['container_id'] ?>',
		    data: {
			    columns: <?php echo json_encode ( $chartToDisplay['chart1']['data'] ) ?>,
			    x:'hit_year',
			    order:null,
				names:{
					hit_count : 'Consultations'
				},
				labels: false,
				type:'bar'
		    },
		    legend: {
				show:true
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
		            tick: {
			            format:'%Y'
				    }
		        }
		    },
		    grid: {
		        y: {
		            lines: [
		                {value: <?php echo $system->countDaysWithHitForPastYearMostHitBookmarks () ?>, text: 'Top <?php echo MOSTHITBOOKMARKS_POPULATION_SIZE ?> <?php echo (int) date('Y')-1 ?> ', position: 'start'}
		            ]
		        }
		    }
		});
    <?php endif; ?>
        
	<?php if (isset($chartToDisplay['chart2'])): ?>
		var chart2 = c3.generate({ 
		    bindto: '#<?php echo $chartToDisplay['chart2']['container_id'] ?>',
		    data: {
			    columns: <?php echo json_encode ( $chartToDisplay['chart2']['data'] ) ?>,
			    x:'hit_day',
			    order:null,
				names:{
					hit_count : 'Consultations'
				},
				labels: false,
				type:'bar'
		    },
		    legend: {
				show:true
			},
			tooltip: {
				show:false
			},
		    bar: {
		        width: {
		            ratio: 1
		        }
		    },
		    axis: {
		    	x: {
		    	    type: 'timeseries',
	    		    tick: {
	        		    format:'%d/%m',
	        		    culling:{max:5},
	    		    }
		        },
		    }
		});
	<?php endif; ?>
    </script>
</body>
</html>