<?php
require_once './classes/System.class.php';
$system = new System('./config/host.json');

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
	<title><?php echo $system->projectNameToHtml().' &gt; '.$doc_title; ?></title>
	<meta charset="UTF-8">
	<meta name="viewport" content="width=device-width, height=device-height, initial-scale=1.0" />
	<link rel="stylesheet" href="<?php echo $system->getSkinUrl(); ?>/theme.css" type="text/css" />
	<script src="<?php echo D3_URI ?>"></script>
	<script src="<?php echo D3CHART_URI ?>"></script>
	<script src="<?php echo C3_URI ?>"></script>
	<?php echo $system->writeHtmlHeadTagsForFavicon(); ?>
</head>
<body id="bookmark">
	<?php include './inc/menu.inc.php'; ?>
	<main class="container-fluid">
		<header>
			<h1><?php echo ToolBox::toHtml($doc_title) ?></h1>
		</header>
		<section>
			<div class="row">
				<div class="col-md-6"><div class="theater"><?php echo $bookmark->getHtmlSnapshotLink(); ?></div></div>
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
		</section>

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
	</main>
	
    <script>
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