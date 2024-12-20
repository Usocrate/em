<?php
require_once './classes/System.class.php';
$system = new System('./config/host.json');

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
		/**
		 * si demande explicite d'authentification (à partir du formulaire d'identification)
		 */
		case 'user_authentication' :
			$user = new User ();
			if ($user->authenticate ( $_POST ['user_name'], $_POST ['user_password'] )) {
				$user->deleteExpiredSessions ();
				$user_session = $user->getNewSession ();
				setcookie ( 'user_id', $user->getId (), strtotime ( $user_session->getExpirationDate () ) );
				setcookie ( 'user_session_id', $user_session->getId (), strtotime ( $user_session->getExpirationDate () ) );
				header ( 'Location:' . $postAuthenticationTargetUrl );
				exit ();
			} else {
				$fb = new UserFeedBack ();
				$fb->addWarningMessage ( 'Les éléments fournis ne permettent pas de vous identifier.' );
			}
			break;
		/**
		 * l'utilisation demande à jeter un oeil sans s'authentifier
		 */
		case 'tour_request':
			$_SESSION['isTourRequested'] = true;
			header ( 'Location:' . $system->getProjectUrl());
			exit ();
	}
}
$doc_title = 'Identification utilisateur';

header ( 'charset=utf-8' );
?>
<!doctype html>
<html lang="fr">
<head>
	<title><?php echo ToolBox::toHtml($doc_title.' ('.$system->getProjectName().')'); ?></title>
	<?php echo $system->writeHeadCommonMetaTags(); ?>
	<?php echo $system->writeHeadCommonLinkTags(); ?>
</head>
<body id="loginDoc">
	<main>
		<h1 class="brand"><a href="<?php echo $system->getProjectUrl() ?>"><?php echo ToolBox::toHtml($system->getProjectName()); ?></a></h1>
		<p class="lead"><?php echo $system->projectDescriptionToHtml() ?></p>		
		<?php
			if (isset ( $fb )) {
				echo '<div>';
				echo $fb->AllMessagesToHtml ();
				echo '</div>';
			}
		?>
		<div class="visu-wrapper deco"><div id="visu"></div></div>
		<form action="<?php echo $system->getLoginUrl() ?>" method="post">
			<input name="task_id" type="hidden" value="user_authentication" /> <input name="postAuthenticationTargetUrl" type="hidden" value="<?php echo $postAuthenticationTargetUrl ?>" />
			<div class="mb-3">
				<label for="name_i" class="form-label">Identifiant</label>
				<input id="name_i" type="text" name="user_name" autocomplete="username" class="form-control" />
			</div>
			<div class="mb-3">
				<label for="password_i" class="form-label">Mot de passe</label>
				<input id="password_i" type="password" name="user_password" autocomplete="current-password" class="form-control" />
			</div>
			<div class="buttonBar">
				<button type="submit" class="btn btn-primary">s&apos;identifier</button>
				<a class="btn btn-link" href="<?php echo $system->getLoginUrl(array('task_id'=>'tour_request')) ?>">Juste jeter un oeil...</a>
			</div>
		</form>
	</main>
<script>
	document.addEventListener("DOMContentLoaded", function() {
		document.getElementById('name_i').focus();
	});
</script>
</body>
</html>
