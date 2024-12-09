<?php
require_once '../classes/System.class.php';
$system = new System('../config/host.json');

if (! $system->configFileExists ()) {
	header ( 'Location:'.$system->getConfigUrl() );
	exit ();
}

include_once '../inc/boot.php';
session_start ();

if (! $system->isUserAuthenticated ()) {
	header ( 'Location:' . $system->getLoginUrl() );
	exit ();
}

$messages = array ();
$user = new User ( $system->getAuthenticatedUserId () );
$user->hydrate ();

if (isset ( $_POST ['user_task'] ) && strcmp ( $_POST ['user_task'], 'supprimer' ) == 0) {
	if (strcmp ( $_POST ['user_password'], $_POST ['user_password_bis'] ) == 0) {
		$user->setName ( $_POST ['user_name'] );
		$user->setPassword ( $_POST ['user_password'] );
		$user->setEmail ( $_POST ['user_email'] );
		$messages [] = $user->toDB () ? 'Enregistrement effectif' : 'Echec de l\'enregistrement';
	} else {
		$messages [] = 'Le mot de passe n\'a pas été correctement confirmé, il doit être saisi à nouveau ...';
	}
}
$doc_title = 'Un utilisateur';

header ( 'charset=utf-8' );
?>
<!doctype html>
<html lang="fr">
<head>
	<title><?php echo ToolBox::toHtml($doc_title.' ('.$system->getProjectName().')'); ?></title>
	<?php echo $system->writeHeadCommonMetaTags(); ?>
	<?php echo $system->writeHeadCommonLinkTags(); ?>
	<script src="<?php echo BOOTSTRAP_JS_URI; ?>"></script>
</head>
<body>
	<?php include 'menu.inc.php'; ?>
	<main>
		<header>
			<h1><?php echo ToolBox::toHtml($doc_title) ?></h1>
		</header>
		<div>
			<?php if (count($messages)>0) echo '<div class="alerte">'.implode('<br />', $messages).'</div>'?>
			<form target="_self" action="<?php echo $_SERVER['PHP_SELF'] ?>" method="post">
				<div class="mb-3">
					<label class="form-label" for="user_name_input">Nom de l&rsquo;utilisateur</label> <input id="user_name_input" name="user_name" value="<?php if (isset($_REQUEST['user_name'])) echo $_REQUEST['user_name'] ?>" class="form-control" />
				</div>
				<div class="mb-3">
					<label class="form-label" for="user_password_input">Mot de passe</label> <input id="user_password_input" name="user_password" type="password" class="form-control" />
				</div>
				<div class="mb-3">
					<label class="form-label" for="user_password_bis_input">Confirmation du mot de passe</label> <input id="user_password_bis_input" name="user_password_bis" type="password" class="form-control" />
				</div>
				<div class="mb-3">
					<label class="form-label" for="user_email_input">Mail</label> <input id="user_email_input" name="user_email" type="text" value="<?php if (isset($_REQUEST['user_email'])) echo $_REQUEST['user_email'] ?>" class="form-control" />
				</div>
				<input name="user_id" type="hidden" value="<?php echo $user->getId() ?>" /> <input name="user_task" type="submit" value="enregistrer" class="btn btn-primary" />
			</form>
		</div>
	</main>
</body>
</html>
