<?php

function __autoload($class_name)
{
    $path = './classes/';
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

include_once './inc/boot.php';

session_start();
$system->lookForAuthenticatedUser();

$maintopic = $system->getMainTopic();
$doc_title = 'A propos';

$data = $system->countBookmarkCreationYearly();
$creationYears = array_keys($data);
sort($creationYears);

$data2 = $system->countHitYearlyGroupByBookmarkCreationYear();

header('charset=utf-8');
?>
<!doctype html>
<html lang="fr">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, height=device-height, initial-scale=1.0, minimum-scale=1.0, maximum-scale=1.0, user-scalable=no" />
<meta name="author" content="<?php echo $system->projectCreatorToHtml() ?>" />
<title><?php echo ToolBox::toHtml($doc_title.' ('.$system->getProjectName().')') ?></title>
<link rel="stylesheet" href="<?php echo BOOTSTRAP_CSS_URI ?>" type="text/css" />
<link rel="stylesheet" href="<?php echo FONT_AWESOME_URI ?>" type="text/css" />
<link rel="stylesheet" href="<?php echo $system->getSkinUrl(); ?>/main.css" type="text/css" />
<link rel="icon" type="image/x-icon" href="<?php echo $system->getSkinUrl(); ?>/favicon.ico" />
<link rel="search" type="application/opensearchdescription+xml" href="<?php echo $system->getProjectUrl() ?>/opensearch.xml.php" title="<?php echo $system->projectNameToHtml() ?>" />
<script type="text/javascript" src="<?php echo YUI3_SEEDFILE_URI; ?>"></script>
<script type="text/javascript" src="<?php echo JQUERY_URI; ?>"></script>
<script type="text/javascript" src="<?php echo BOOTSTRAP_JS_URI; ?>"></script>
</head>
<body id="about" class="container">
	<header>
		<div class="brand"><?php echo $system->getHtmlLink() ?></div>
		<h1><?php echo ToolBox::toHtml($doc_title) ?></h1>
	</header>
	<section>
		<h2>Statistiques</h2>
		<div id="creation_stats_div">
			<h3>Découvertes</h3>
			<?php
if (isset($data) && is_array($data)) {
    echo '<div id="chart_container" class="chart_container bonus"></div>';
    echo '<table>';
    echo '<thead>';
    echo '<tr>';
    foreach ($creationYears as $y) {
        echo '<th>' . Year::getHtmlLinkToYearDoc($y) . '</th>';
    }
    echo '</tr>';
    echo '</thead>';
    echo '<tbody>';
    echo '<tr>';
    $max = 0;
    foreach ($data as $k => $v) {
        if ($v > $max)
            $max = $v;
    }
    foreach ($data as $k => $v) {
        echo $v == $max ? '<td class="em">' : '<td>';
        echo $v;
        if (strcmp(date('Y'), $k) == 0) {
            $base = strcmp(date('L'), 1) == 0 ? 366 : 365;
            echo '<br/><small>projection : ' . floor($v * $base / (int) date('z')) . '<small>';
        }
        echo '</td>';
    }
    echo '</tr>';
    echo '<tbody>';
    echo '</table>';
} else {
    echo '<p>Aucune création de ressource !</p>';
}
?>
			</div>
		<div id="hit_stats_div">
			<h3>Consultations selon l'année de découverte</h3>
			<?php
if (isset($data2) && is_array($data2)) {
    echo '<div id="chart2_container" class="chart_container bonus"></div>';
    echo '<table>';
    echo '<thead>';
    echo '<tr>';
    echo '<th rowspan="2" class="empty" />';
    echo '<th colspan="' . count($creationYears) . '">Année de découverte</th>';
    echo '</tr>';
    echo '<tr>';
    foreach ($creationYears as $y) {
        echo '<th>' . Year::getHtmlLinkToYearDoc($y) . '</th>';
    }
    echo '</tr>';
    echo '</thead>';
    echo '<tbody>';
    foreach ($data2 as $k => $v) {
        $max = 0;
        foreach ($v as $n) {
            if ($n > $max)
                $max = $n;
        }
        echo '<tr>';
        echo '<th>' . Year::getHtmlLinkToYearDoc($k) . '</th>';
        foreach ($v as $n) {
            echo $n == $max ? '<td class="em">' . $n . '</td>' : '<td>' . $n . '</td>';
        }
        echo '</tr>';
    }
    echo '<tbody>';
    echo '</table>';
} else {
    echo '<p>Aucune consultation !</p>';
}
?>
			</div>
		<div>
			<a href="<?php echo $system->getProjectUrl() ?>/toppublishers.php">Par éditeur</a>
		</div>
	</section>
	<?php include './inc/footer.inc.php'; ?>
	<script>
	YUI().use("charts", function (Y) {
	
		var chart_data =
		[
		<?php
$pieces = array();
foreach ($data as $year => $count) {
    $pieces[] = '{ year: "' . $year . '", count: ' . $count . ' }';
}
echo implode(',', $pieces);
?> 
		];
	
	    var chart_series =
	    [
	      {xKey:"year", xDisplayName:"Année", yKey:"count", yDisplayName:"Nombre de découvertes"},
	    ];
	
		var chart1 = new Y.Chart({
			dataProvider:chart_data,
			categoryKey:"year",
			type:"column",
			seriesCollection:chart_series,
			render:"#chart_container"
		});
	
		var chart2_data =
		[
		<?php
$pieces = array();
foreach ($data2 as $year => $data) {
    $p = '{';
    $p .= '"hit_year":"' . $year . '"';
    foreach ($creationYears as $y) {
        $p .= isset($data[$y]) ? ',"' . $y . '":' . $data[$y] : ',"' . $y . '":0';
    }
    $p .= '}';
    $pieces[] = $p;
}
echo implode(',', $pieces);
?> 
		];
	
	    var chart2 = new Y.Chart(
	    {
	    	dataProvider:chart2_data,
	    	categoryKey:"hit_year",
	    	type:"area",
	    	stacked:true,
	    	render:"#chart2_container",
	    	interactionType:"planar"
	   });
	});
	</script>
</body>
</html>