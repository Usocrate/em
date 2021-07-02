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

$doc_title = 'Consommation';

$data = $system->countBookmarkCreationYearly();
$data2 = $system->countHitYearlyGroupByBookmarkCreationYear();

header('charset=utf-8');
?>
<!doctype html>
<html lang="fr">
<head>
	<meta charset="UTF-8">
	<meta name="viewport" content="width=device-width, height=device-height, initial-scale=1.0" />
	<meta name="author" content="<?php echo $system->projectCreatorToHtml() ?>" />
	<title><?php echo ToolBox::toHtml($doc_title.' ('.$system->getProjectName().')') ?></title>
	<link rel="stylesheet" href="<?php echo C3_CSS_URI ?>" type="text/css" />
	<link rel="stylesheet" href="<?php echo $system->getSkinUrl(); ?>/theme.css" type="text/css" />
	<?php echo $system->writeHtmlHeadTagsForFavicon(); ?>
	<script type="text/javascript" src="<?php echo D3_URI ?>"></script>
	<script type="text/javascript" src="<?php echo D3CHART_URI ?>"></script>
	<script type="text/javascript" src="<?php echo C3_URI ?>"></script>
	<script src="https://code.jquery.com/jquery-3.5.1.slim.min.js" integrity="sha384-DfXdz2htPH0lsSSs5nCTpuj/zy4C+OGpamoFVy38MVBnE+IbbVYUew+OrCXaRkfj" crossorigin="anonymous"></script>	
	<script type="text/javascript" src="../../vendor/twbs/bootstrap/dist/js/bootstrap.bundle.min.js"></script>
	<script type="text/javascript" src="<?php echo JQUERY_URI; ?>"></script>
	<script type="text/javascript" src="<?php echo JQUERY_UI_URI; ?>"></script>
	<script type="text/javascript" src="<?php echo BOOTSTRAP_JS_URI; ?>"></script>
</head>
<body id="about">
	<?php include 'menu.inc.php'; ?>
	<div id="creation_stats_div" class="container-fluid">
		<header>
			<h1><?php echo ToolBox::toHtml($doc_title) ?></h1>
		</header>
		<?php
		if (isset($data) && is_array($data)) {
		    $base = strcmp(date('L'), 1) == 0 ? 366 : 365;
		    // en début d'année, pas de projection si pas encore de nouvelle découverte
		    if (isset($data[date('Y')])) {
		        $projection = floor($data[date('Y')] * $base / (int) date('z'));
		    }
		}
		?>
		<h2>Découvertes
		<?php
		if (isset($projection)) {
		  echo ' <small>( projection '.date('Y').' : '.$projection.')</small>';
		}
		?>
		</h2>
		<div id="chart_container" class="chart_container"></div>

		<div id="hit_stats_div">
			<h2>Consultations</h2>
			<?php
			if (isset($data2) && is_array($data2)) {
			    echo '<div id="chart2_container" class="chart_container"></div>';
			} else {
			    echo '<p>Aucune consultation !</p>';
			}
			?>
			</div>
		
		<nav class="bar">
			<ol>
			<?php
				$publishers = $system->getTopPublishers(7);
				foreach ( $publishers as $p ) {
					echo '<li>'.$p->getHtmlLinkTo().' <small>('.$p->countBookmarks().')</small></li>';
				}			
			?>
			</ol>
		</nav>
		<nav class="bar">
			<ol>
			<?php
				$data = $system->countBookmarkCreationYearly ();
				foreach ( $data as $y => $count ) {
					echo '<li>' . Year::getHtmlLinkToYearDoc ( $y ) . '</li>';
				}
			?>
			</ol>
		</nav>
	</div>	

	<?php
	   $chart_data = array ();
    	$year_serie = array (
    	    'creation_year'
    	);
		$count_serie = array (
				'creation_count' 
		);
		$projection_serie = array(
				'creation_projection'
		);
		$i = 0;
		foreach ( $data as $year => $count ) {
			$i++;
			$year_serie [] = $year.'-12-31';
			$count_serie [] = ( int ) $count;
			if (isset($projection)) {
			    $projection_serie [] = $i == count($data) ? $projection - $count : '';
			}
		}
		array_push($chart_data, $year_serie, $count_serie, $projection_serie);
	?>
	<script type="text/javascript">
	var chart = c3.generate({ 
	    bindto: '#chart_container',
	    data: {
		    columns: <?php echo json_encode ( $chart_data ) ?>,
		    x:'creation_year',
		    order:null,
			names:{
				creation_count : 'Découvertes'
			},
			labels: true,
			type:'bar',
			groups: [
				['creation_projection', 'creation_count']
			]
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
	            tick: {
		            format:'%Y'
			    }
	        }
	    }
	});
	</script>

	<?php
		$chart2_data = array();
		
		$count_time_serie = array('count_time');
		foreach($project_years as $y) {
		    $count_time_serie[] = strcmp(date('Y'), $y) != 0 ? $y.'-12-31' : date('Y-m-d');
		}
		array_push($chart2_data, $count_time_serie);
		
		foreach ( $data2 as $creation_year => $year_hit_count) {
			$count_serie = array( (string) $creation_year );
			foreach($project_years as $y) {
				$count_serie[] = isset($year_hit_count[$y]) ? (int) $year_hit_count[$y] : 0;
			}
			array_push($chart2_data, $count_serie);
		}
	?>
	<script type="text/javascript">
	var chart2 = c3.generate({ 
	    bindto: '#chart2_container',
	    data: {
		    columns: <?php echo json_encode ( $chart2_data ) ?>,
			x:'count_time',
			names: {
				<?php
				$keys = array_keys($data2);
				// légende plus riche pour la première série)
				echo json_encode($keys[0]).':'.json_encode('découvertes '.$keys[0]);
				?>
			},
			order:null,
			labels: false,
			type:'area',
			groups: [
				<?php echo json_encode ( $project_years ) ?>
			]
	    },
	    point: {
	        show: false
	    },
	    legend: {
			show:true
		},
		tooltip: {
			show:false
		},
	    axis: {
	        x: {
	            type: 'timeseries',
	            tick:{
	                format:'%Y'
			    }
	        },       
        	y: {
	            label: 'Consultations'
	        }
	    }
	});
	</script>
</body>
</html>