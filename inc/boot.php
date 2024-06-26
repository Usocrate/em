<?php
iconv_set_encoding ( 'internal_encoding', 'UTF-8' );
iconv_set_encoding ( 'input_encoding', 'UTF-8' );
iconv_set_encoding ( 'output_encoding', 'UTF-8' );

define ( 'USER_SESSION_LIFETIME', 60 * 60 * 24 * 7 ); // 7 jours;
define ( 'ACTIVITY_THRESHOLD1', 100 ); // le nombre de jours précédant l'instant t durant lesquels l'activité sur le site est considérée comme récente
define ( 'ACTIVITY_THRESHOLD2', 500 ); // la période (en jours) au-delà de laquelle les dates de dernière consultation, de dernière modification et de création d'une ressource sont considérées comme anciennes
define ( 'MOSTHITBOOKMARKS_POPULATION_SIZE', 24 ); // le nombre définissant la population de signets les plus consultés

// Bootstrap
define ( 'BOOTSTRAP_JS_URI', $system->getProjectUrl().'/vendor/twbs/bootstrap/dist/js/bootstrap.bundle.min.js');

// JQuery
define ( 'JQUERY_URI', 'https://ajax.googleapis.com/ajax/libs/jquery/1.11.2/jquery.min.js' );
define ( 'JQUERY_UI_URI', 'https://code.jquery.com/ui/1.11.4/jquery-ui.min.js' );

// Masonry
define ('MASONRY_URI', 'https://unpkg.com/masonry-layout@4.2.0/dist/masonry.pkgd.min.js');

// D3
define ( 'D3_URI', $system->getProjectUrl().'/outsourcing/d3.v3.min.js' );

// D3 Chart
define ( 'D3CHART_URI', $system->getProjectUrl().'/outsourcing/d3.chart.min.js' );

// C3
define ( 'C3_URI', $system->getProjectUrl().'/outsourcing/c3.min.js' );
define ( 'C3_CSS_URI', $system->getProjectUrl().'/outsourcing/c3.min.css' );
?>
