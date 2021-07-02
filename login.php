<?php
function __autoload($class_name) {
	$path = './classes/';
	if (is_file ( $path . $class_name . '.class.php' )) {
		include_once $path . $class_name . '.class.php';
	} elseif ($path . $class_name . '.interface.php') {
		include_once $path . $class_name . '.interface.php';
	}
}

$system = new System ( './config/host.json' );

if (! $system->configFileExists()) {
	header ( 'Location:'.$system->getConfigUrl() );
	exit ();
}

include_once './inc/boot.php';
session_start ();

/**
 * on détermine le contenu vers lequel l'utilisateur sera redirigé après son authentification.
 */
if (empty ( $_REQUEST ['postAuthenticationTargetUrl'] )) {
	$postAuthenticationTargetUrl = empty ( $_SERVER ['HTTP_REFERER'] ) || strcmp ( $_SERVER ['HTTP_REFERER'], $_SERVER ['PHP_SELF'] ) == 0 ? $system->getProjectUrl() : $_SERVER ['HTTP_REFERER'];
} else {
	$postAuthenticationTargetUrl = $_REQUEST ['postAuthenticationTargetUrl'];
}

//
// c'est ce script qui gère les demandes de connection / déconnection
//
if (isset ( $_REQUEST ['task_id'] )) {
	ToolBox::formatUserPost ( $_REQUEST );
	switch ($_REQUEST ['task_id']) {
		/**
		 * demande explicite d'anonymat (suppression session et cookie)
		 */
		case 'anonymat' :
			session_destroy ();
			setcookie ( 'user_id', NULL, time () - 1 );
			setcookie ( 'user_session_id', NULL, time () - 1 );
			header ( 'Location:' . $postAuthenticationTargetUrl );
			exit ();
			break;
		/**
		 * si demande explicite d'authentification (à partir du formulaire d'identification)
		 */
		case 'user_authentication' :
			$user = new User ();
			if ($user->authenticate ( $_POST ['user_name'], $_POST ['user_password'] )) {
				if (isset ( $_POST ['cookie_option'] )) {
					$user->deleteExpiredSessions ();
					$user_session = $user->getNewSession ();
					setcookie ( 'user_id', $user->getId (), strtotime ( $user_session->getExpirationDate () ) );
					setcookie ( 'user_session_id', $user_session->getId (), strtotime ( $user_session->getExpirationDate () ) );
				}
				header ( 'Location:' . $postAuthenticationTargetUrl );
				exit ();
			} else {
				$fb = new UserFeedBack ();
				$fb->addWarningMessage ( 'Les éléments fournis ne permettent pas de vous identifier.' );
			}
	}
}
$doc_title = 'Identification utilisateur';

header ( 'charset=utf-8' );
?>
<!doctype html>
<html lang="fr">
<head>
	<meta charset="UTF-8">
	<meta name="viewport" content="width=device-width, height=device-height, initial-scale=1.0" />
	<title><?php echo ToolBox::toHtml($doc_title.' ('.$system->getProjectName().')'); ?></title>
	<link rel="stylesheet" href="<?php echo $system->getSkinUrl(); ?>/theme.css" type="text/css" />
	<?php echo $system->writeHtmlHeadTagsForFavicon(); ?>
	<script type="text/javascript" src="<?php echo JQUERY_URI; ?>"></script>
	<script type="text/javascript" src="<?php echo BOOTSTRAP_JS_URI; ?>"></script>
</head>
<body id="loginDoc">
	<div class="container">
		<header>
			<h1 class="brand"><a href="<?php echo $system->getProjectUrl() ?>"><?php echo ToolBox::toHtml($system->getProjectName()); ?></a></h1>
		</header>
		<?php
			if (isset ( $fb )) {
				echo '<div>';
				echo $fb->AllMessagesToHtml ();
				echo '</div>';
			}
		?>
		<form action="<?php echo $system->getLoginUrl() ?>" method="post">
			<input name="task_id" type="hidden" value="user_authentication" /> <input name="postAuthenticationTargetUrl" type="hidden" value="<?php echo $postAuthenticationTargetUrl ?>" />
			<div class="form-group">
				<label for="name_i">Identifiant</label> <input id="name_i" type="text" name="user_name" class="form-control" />
			</div>
			<div class="form-group">
				<label for="password_i">Mot de passe</label> <input id="password_i" type="password" name="user_password" class="form-control" />
			</div>
			<div class="checkbox">
				<label for="cookie_opt_i"> <input id="cookie_opt_i" name="cookie_option" type="checkbox" value="1" /> Mémoriser pour cette machine</label>
			</div>
			<div class="mt-3 mb-2">
				<button type="submit" class="btn btn-primary">s&apos;identifier</button>
				<a class="btn btn-link" href="<?php echo $system->getProjectUrl() ?>">Abandonner</a>
			</div>
		</form>
	</div>
</body>
</html>