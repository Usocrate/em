<?php
iconv_set_encoding ( 'internal_encoding', 'UTF-8' );
iconv_set_encoding ( 'input_encoding', 'UTF-8' );
iconv_set_encoding ( 'output_encoding', 'UTF-8' );

define ( 'USER_SESSION_LIFETIME', 60 * 60 * 24 * 7 ); // 7 jours;
define ( 'ACTIVITY_THRESHOLD1', 100 ); // le nombre de jours précédant l'instant t durant lesquels l'activité sur le site est considérée comme récente
define ( 'ACTIVITY_THRESHOLD2', 500 ); // la période (en jours) au-delà de laquelle les dates de dernière consultation, de dernière modification et de création d'une ressource sont considérées comme anciennes
define ( 'MOSTHITBOOKMARKS_POPULATION_SIZE', 12 ); // le nombre définissant la population de signets les plus consultés
                                                
// Angular
//define ( 'ANGULAR_URI', 'https://ajax.googleapis.com/ajax/libs/angularjs/1.3.13/angular.min.js' );

// Bootstrap
define ( 'BOOTSTRAP_CSS_URI', $system->getSkinUrl().'/bootstrap/css/bootstrap.min.css');
define ( 'BOOTSTRAP_CSS_THEME_URI', $system->getSkinUrl().'/bootstrap/css/bootstrap-theme.min.css');
define ( 'BOOTSTRAP_JS_URI', $system->getSkinUrl().'/bootstrap/js/bootstrap.min.js');

// JQuery
define ( 'JQUERY_URI', 'https://ajax.googleapis.com/ajax/libs/jquery/1.11.2/jquery.min.js' );
define ( 'JQUERY_UI_URI', 'https://code.jquery.com/ui/1.11.4/jquery-ui.min.js' );

// D3
define ( 'D3_URI', '/outsourcing/d3/d3.min.js' );

// C3
define ( 'C3_URI', '/outsourcing/c3/c3.min.js' );
define ( 'C3_CSS_URI', '/outsourcing/c3/c3.min.css' );
?>