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
	header ( 'Location:' . $system->getConfigUrl () );
	exit ();
}

include_once '../inc/boot.php';
session_start ();

$messages = array ();

if (! $system->isUserAuthenticated ()) {
	header ( 'Location:' . $system->getLoginUrl() );
	exit ();
}

if (! empty ( $_REQUEST ['topic_id'] )) {
	$targettopic = new Topic ( $_REQUEST ['topic_id'] );
}

if (isset ( $_POST ['import_submission'] )) {
	if (strcmp ( $_POST ['topic_type'], 'new' ) == 0 && ! empty ( $_POST ['newtopic_title'] )) {
		// création d'une nouvelle rubrique
		$targettopic = new Topic ();
		$targettopic->setTitle ( $_POST ['newtopic_title'] );
		$targettopic->setDescription ( $_POST ['newtopic_description'] );
		$targettopic->setPrivacy ( $_POST ['newtopic_privacy'] );
		$targettopic->toDB ();
		$targettopic->addTo ( new Topic ( $_POST ['newtopic_parent_id'] ) );
	}
	$messages = $system->importNetscapeBookmarkFile ( $_FILES ['netscapefile'], $targettopic );
	header ( 'Location:' . $system->getHomeUrl () );
	exit ();
}
$doc_title = 'Importez vos bookmarks au format NETSCAPE-Bookmark-file-1';

header ( 'charset=utf-8' );
?>
<!doctype html>
<html lang="fr">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, height=device-height, initial-scale=1.0" />
<title><?php echo ToolBox::toHtml($system->getProjectName().' > '.$doc_title) ?></title>
<link rel="stylesheet" href="<?php echo $system->getSkinUrl(); ?>/theme.css" type="text/css" />
<link rel="apple-touch-icon" sizes="180x180" href="<?php echo $system->getSkinUrl(); ?>/apple-touch-icon.png">
<link rel="icon" type="image/png" href="<?php echo $system->getSkinUrl(); ?>/favicon-32x32.png" sizes="32x32">
<link rel="icon" type="image/png" href="<?php echo $system->getSkinUrl(); ?>/favicon-16x16.png" sizes="16x16">
<link rel="manifest" href="<?php echo $system->getSkinUrl(); ?>/manifest.json">
<link rel="mask-icon" href="<?php echo $system->getSkinUrl(); ?>/safari-pinned-tab.svg" color="#5bbad5">
<link rel="shortcut icon" href="<?php echo $system->getSkinUrl(); ?>/favicon.ico">
<meta name="msapplication-config" content="<?php echo $system->getSkinUrl(); ?>/browserconfig.xml">
<meta name="theme-color" content="#8ea4bc">
<script>
	 	function checkDescriptionLength(textarea)
	 	{
			if (textarea.value.length>255) {
				alert('La description est trop longue ('+textarea.value.length+' caractères).\nLe nombre de caractères autorisé est limité à 255.');
				textarea.focus();
			}		 	
	 	}
	</script>
</head>
<body>
	<?php include 'menu.inc.php'; ?>
	<div class="container-fluid">
		<header>
			<div class="brand"><?php echo $system->getHtmlLink()?></div>
			<h1><?php echo ToolBox::toHtml($doc_title)?></h1>
		</header>
		<?php if (is_array($messages)) echo '<p>'.implode('<br />', $messages).'</p>'; ?>
		<p><strong>Attention à l&rsquo;encodage du fichier d&#39;importation : jeu de caractères UTF-8 obligatoire !</strong></p>
		<form target="_self" action="<?php echo $_SERVER['PHP_SELF'] ?>" method="post" enctype="multipart/form-data">
			<div>
				<h2>Fichier source</h2>
				<label>Localisation</label><input type="file" name="netscapefile" size="75" />
			</div>
			<br />
			<div>
				<h2>Dans quelle rubrique ?</h2>
				<input type="radio" id="topic_type_radio1" name="topic_type" value="existing" checked="checked" onclick="document.getElementById('newtopic_fieldset').style.display='none';document.getElementById('existingtopic_fieldset').style.display='block'" /> <label for="topic_type_radio1">Une rubrique existante</label> <input type="radio" id="topic_type_radio2" name="topic_type" value="new"
					onclick="document.getElementById('newtopic_fieldset').style.display='block';document.getElementById('existingtopic_fieldset').style.display='none'" /> <label for="topic_type_radio2">Une nouvelle rubrique</label>
				<fieldset id="existingtopic_fieldset">
					<legend>Rubrique existante</legend>
					<select name="topic_id">
							<?php $maintopic = $system->getMainTopic()?>
							<option value="<?php echo $maintopic->getId() ?>">- hors rubrique -</option>
							<?php echo empty($_REQUEST['topic_id']) ? $maintopic->getDescendantsOptionsTags() : $maintopic->getDescendantsOptionsTags($_REQUEST['topic_id']); ?>
						</select>
				</fieldset>
				<fieldset id="newtopic_fieldset" style="display: none">
					<legend>Nouvelle rubrique</legend>
					<p>
						<label for="newtopic_title_input">Intitulé</label><br /> <input id="newtopic_title_input" name="newtopic_title" size="20" value="" />
					</p>
					<p>
						<label for="newtopic_parent_select">Sous-rubrique de</label><br /> <select id="newtopic_parent_select" name="newtopic_parent_id">
								<?php $maintopic = $system->getMainTopic()?>
								<option value="<?php echo $maintopic->getId() ?>">- hors rubrique -</option>
								<?php echo empty($_REQUEST['topic_id']) ? $maintopic->getDescendantsOptionsTags() : $maintopic->getDescendantsOptionsTags($_REQUEST['topic_id']); ?>
							</select>
					</p>
					<p>
						<label for="newtopic_description_textarea">Description</label><br />
						<textarea id="newtopic_description_textarea" name="newtopic_description" cols="51" rows="5" onblur="checkDescriptionLength(this)"></textarea>
					</p>
					<p>
						<label>Rubrique confidentielle</label>: <input id="newtopic_privacy_radio1" type="radio" name="newtopic_privacy" value="0" checked="checked" /><label for="newtopic_privacy_radio1">non</label> <input id="newtopic_privacy_radio2" type="radio" name="newtopic_privacy" value="1" /><label for="newtopic_privacy_radio2">oui</label>
					</p>
				</fieldset>
			</div>
			<hr />
			<input name="import_submission" type="submit" value="importer" />
		</form>
	</div>
</body>
</html>