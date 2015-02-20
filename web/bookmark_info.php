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

$data = $bookmark->countDayWithHitYearly ();
$data2 = $bookmark->countHitForRecentDays ();

$doc_title = $bookmark->getTitle ();

header ( 'charset=utf-8' );
?>
<!doctype html>
<html lang="fr">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, height=device-height, initial-scale=1.0, minimum-scale=1.0, maximum-scale=1.0, user-scalable=no" />
<title><?php echo $system->projectNameToHtml().' &gt; '.$doc_title; ?></title>
<link rel="stylesheet" href="<?php echo BOOTSTRAP_CSS_URI ?>" type="text/css" />
<link rel="stylesheet" href="<?php echo FONT_AWESOME_URI ?>" type="text/css" />
<link rel="stylesheet" href="<?php echo $system->getSkinUrl(); ?>/main.css" type="text/css" />
<link rel="icon" type="image/x-icon" href="<?php echo $system->getSkinUrl(); ?>/favicon.ico" />
<link rel="search" type="application/opensearchdescription+xml" href="<?php echo $system->getProjectUrl() ?>/opensearch.xml.php" title="<?php echo $system->projectNameToHtml() ?>" />
<script type="text/javascript" src="<?php echo YUI3_SEEDFILE_URI; ?>"></script>
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
						if ($bookmark->isPublisherKnown ()) {
							$dataToDisplay [] = $bookmark->getHtmlLinkToPublisher ();
						}
						$dataToDisplay [] = $bookmark->getHtmlHitFrequency ();
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

		<div class="row bonus">
			<div class="col-md-6">
				<h3>Depuis la création du signet</h3>
					<?php
					if (isset ( $data ) && is_array ( $data )) {
						echo '<div id="chart1_container" class="chart_container"></div>';
					} else {
						echo '<p>Aucune trace d&#39;utilisation du signet.</p>';
					}
					?>
				</div>
			<div class="col-md-6">
				<h3>Au cours des <?php echo ACTIVITY_THRESHOLD1 ?> derniers jours</h3>
					<?php
					if (isset ( $data2 ) && is_array ( $data2 )) {
						echo '<div id="chart2_container" class="chart_container"></div>';
					} else {
						echo '<p>Aucune trace d&#39;utilisation du signet.</p>';
					}
					?>
				</div>
		</div>
	</div>
	<?php include './inc/footer.inc.php'; ?>
<script>
YUI().use("charts","charts-legend",function (Y) {
	var chart1_data =
	[
		<?php
		if (isset ( $data ) && is_array ( $data )) {
			$pieces = array ();
			foreach ( $data as $year => $count ) {
				$pieces [] = $year == date ( 'Y' ) - 1 ? '{year:"' . $year . '", count:' . $count . ', standard:' . $system->countDaysWithHitForPastYearMostHitBookmarks () . '}' : '{year:"' . $year . '", count:' . $count . '}';
			}
			echo implode ( ',', $pieces );
		}
		?> 
	];

	var chart1_series = [
	{
    	type:"column",
        xKey:"year",
        yKey:"count",
        xDisplayName:"Année",
        yDisplayName:"Nombre de jours de consultation",
        styles:{
            fill:{color:"#BDB68F"}
        }
	},
	{
    	type:"combo",
        xKey:"year",
        yKey:"standard",
        xDisplayName:"Année",
        yDisplayName:"Moyenne Top <?php echo MOSTHITBOOKMARKS_POPULATION_SIZE ?>",
        styles:{marker:{
            fill:{color:"#706855"}}
        }
	}
    ];

	var chart1 = new Y.Chart({
		dataProvider:chart1_data,
		legend: {
            position: "bottom",
        },
		categoryKey:"year",
		type:"column",
		render:"#chart1_container",
		seriesCollection:chart1_series,
		interactionType:"planar"
	});

	
	var chart2_data =
	[
		<?php
		if (isset ( $data2 ) && is_array ( $data2 )) {
			$pieces = array ();
			foreach ( $data2 as $index => $count ) {
				$hitday_timestamp = time () - ($index * 86400);
				$pieces [] = '{day:"' . date ( 'm/d/Y', $hitday_timestamp ) . '", count:' . $count . ' }';
			}
			echo implode ( ',', $pieces );
		}
		?> 
	];

	var chart2_axes = {
		hitCountAxis:{
            keys:["count"],
            position:"left",
            type:"numeric",
			styles:{
				majorUnit:{determinant:"count",count:5},
			}
		},
		timelineAxis:{
			keys:["day"],
            position:"bottom",
            type:"time",
            labelFormat:"%d/%m",
			styles:{
				majorUnit:{determinant:"count",count:10},
				majorTicks:{display:"none"}
			}
		}
	};

	var chart2_series = [
		{
	    	type:"column",
	        xAxis:"timelineAxis",
	        yAxis:"hitCountAxis",
	        xKey:"day",
	        yKey:"count",
            xDisplayName:"Jour",
            yDisplayName:"Nombre de consultations",
	        styles:{
	            fill:{color:"#BDB68F"}
	        }
	   }
   ];

	var chart2 = new Y.Chart({
		dataProvider:chart2_data,
		axes:chart2_axes,
		legend: {
            position: "bottom",
        },
		seriesCollection:chart2_series,
		render:"#chart2_container"
	});
});
</script>
</body>
</html>