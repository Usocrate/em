<?php
require_once '../classes/System.class.php';
$system = new System('../config/host.json');

if ($system->configFileExists()) {
    $system->parseConfigFile();
} else {
    $system->setDbName('em');
    $system->setDbUser('root');
    $system->setDbHost('localhost');

    //
    // initialisation répertoire
    //
    $path = '../';
    if (! is_dir($path)) {
        mkdir($path, 0770);
    }
    $system->setDirectoryPath(realpath($path));
    $system->setOutsourcingDirectoryPath(realpath($path) . DIRECTORY_SEPARATOR . 'outsourcing');

    $system->setProjectUrl($_SERVER['REQUEST_SCHEME'] . '://' . $_SERVER['HTTP_HOST'] . $_SERVER['CONTEXT_PREFIX']);

    $system->setProjectName('Exomemory');
    // $system->setProjectLaunchYear ( date ( 'Y' ) );
    $system->setProjectLaunchYear('2004');
    $system->setProjectDescription('Collection de ressources pour concepteurs web, depuis ' . $system->getProjectLaunchYear() . ' ...');
    $system->setProjectCreator($_SERVER['SERVER_ADMIN']);
    $system->setProjectPublisher($_SERVER['SERVER_NAME']);
}

if (isset($_POST['task_id'])) {
    $fb = new UserFeedBack();
    ToolBox::formatUserPost($_POST);
    switch ($_POST['task_id']) {
        case 'save':
            if (isset($_POST['db_host'])) {
                $system->setDbHost($_POST['db_host']);
            }
            if (isset($_POST['db_name'])) {
                $system->setDbName($_POST['db_name']);
            }
            if (isset($_POST['db_user'])) {
                $system->setDbUser($_POST['db_user']);
            }
            if (isset($_POST['db_password'])) {
                $system->setDbPassword($_POST['db_password']);
            }
            if (isset($_POST['project_url'])) {
                $system->setProjectUrl($_POST['project_url']);
            }
            if (isset($_POST['project_name'])) {
                $system->setProjectName($_POST['project_name']);
            }
            if (isset($_POST['project_description'])) {
                $system->setProjectDescription($_POST['project_description']);
            }
            if (isset($_POST['project_publisher'])) {
                $system->setProjectPublisher($_POST['project_publisher']);
            }
            if (isset($_POST['project_creator'])) {
                $system->setProjectCreator($_POST['project_creator']);
            }
            if (isset($_POST['project_theme_color'])) {
            	$system->setProjectThemeColor($_POST['project_theme_color']);
            }
            if (isset($_POST['project_background_color'])) {
            	$system->setProjectBackgroundColor($_POST['project_background_color']);
            }
            if (isset($_POST['project_launch_year'])) {
                $system->setProjectLaunchYear($_POST['project_launch_year']);
            }
            if (isset($_POST['host_purpose'])) {
                $system->setHostPurpose($_POST['host_purpose']);
            }
            if (isset($_POST['dir_path'])) {
                $system->setDirectoryPath($_POST['dir_path']);
            }
            if (isset($_POST['outsourcing_dir_path'])) {
                $system->setOutsourcingDirectoryPath($_POST['outsourcing_dir_path']);
            }
            if (isset($_POST['data_dir_path'])) {
                $system->setDataDirectoryPath($_POST['data_dir_path']);
            }
            if (isset($_POST['ga_key'])) {
                $system->setGoogleAnalyticsKey($_POST['ga_key']);
            }
            if ($system->saveConfigFile()) {
              $fb->addSuccessMessage('Configuration enregistrée.');
              try {
              	//
              	// écriture du fichier .htpasswd à disposition pour protéger certains répertoires
              	// on reprend les identifiants d'accès à la base de données
              	// NB : configuration Apache2 à faire en complément
              	//
              	$htpasswdFilePath = '../config/.htpasswd';
                file_put_contents($htpasswdFilePath, $system->getDbUser().':'.password_hash ( $system->getDbPassword(), PASSWORD_BCRYPT ).'\n');
                if (file_exists($htpasswdFilePath)) {
                  $fb->addSuccessMessage('Un fichier est aussi à disposition pour protéger certains répertoires ('.realpath($htpasswdFilePath).').');
                }
               
                // upload d'un fichier image pour l'écran d'accueil
                if ( ! empty ($_FILES['home_screen_img_src_file']) && $_FILES['home_screen_img_src_file']['size']>0) {
                	$system->reworkPhotoFile($_FILES['home_screen_img_src_file']);
                }
                
              } catch ( Exception $e ) {
                $system->reportException ( __METHOD__, $e );
              }
            } else {
                $fb->addDangerMessage('Echec de l\'enregistrement de la configuration.');
            }
            break;
    }
}
include_once '../inc/boot.php';
header('charset=utf-8');
?>
<!doctype html>
<html lang="fr">
<head>
	<title><?php echo $system->projectNameToHtml().' : '.$system->projectDescriptionToHtml() ?></title>
	<meta charset="UTF-8">
	<meta name="viewport" content="width=device-width, height=device-height, initial-scale=1.0" />
	<meta name="description" content="<?php echo $system->projectDescriptionToHtml() ?>" />
	<meta name="author" content="<?php echo $system->projectCreatorToHtml() ?>" />
	<link rel="stylesheet" href="<?php echo $system->getSkinUrl(); ?>/theme.css" type="text/css" />
	<script src="<?php echo JQUERY_URI; ?>"></script>
	<script src="<?php echo BOOTSTRAP_JS_URI; ?>"></script>
	<?php echo $system->writeHtmlHeadTagsForFavicon(); ?>
</head>
<body>
	<?php include 'menu.inc.php'; ?>
	<div class="container-fluid">
		<header><h1>Configuration</h1></header>
		<?php
		if (isset($fb)) {
		    echo '<div>';
		    echo $fb->AllMessagesToHtml();
		    echo '</div>';
		}
		?>
		<form action="<?php $_SERVER['PHP_SELF'] ?>" method="post">
			<div class="row">
				<div class="col-md-6">					
					<fieldset>
						<legend>Projet</legend>
						<div class="form-group">
							<label for="project_url_i">Url</label><input id="project_url_i" id="project_url_i" type="url" name="project_url" class="form-control" value="<?php echo ToolBox::toHtml($system->getProjectUrl()); ?>" />
						</div>
						<div class="form-group">
							<label for="project_name_i">Nom</label><input id="project_name_i" type="text" name="project_name" class="form-control" value="<?php echo ToolBox::toHtml($system->getProjectName()); ?>" />
						</div>
						<div class="form-group">
							<label for="project_description_i">Description</label><input id="project_description_i" type="text" name="project_description" class="form-control" value="<?php echo ToolBox::toHtml($system->getProjectDescription()); ?>" />
						</div>
						<div class="form-group">
							<label for="project_creator_i">Auteur</label><input id="project_creator_i" type="text" name="project_creator" class="form-control" value="<?php echo ToolBox::toHtml($system->getProjectCreator()); ?>" />
						</div>
						<div class="form-group">
							<label for="project_publisher_i">Editeur</label><input id="project_publisher_i" type="text" name="project_publisher" class="form-control" value="<?php echo ToolBox::toHtml($system->getProjectPublisher()); ?>" />
						</div>
						<div class="form-group">
							<label for="project_launch_year_i">Année de création</label><input id="project_launch_year_i" type="text" name="project_launch_year" class="form-control" value="<?php echo ToolBox::toHtml($system->getProjectLaunchYear()); ?>" />
						</div>
					</fieldset>
					<fieldset>
						<legend>Base de données</legend>
						<div class="alert alert-info">NB : Les mêmes identifiants seront demandés pour accéder aux zones sécurisées de l'application.</div>
						<div class="form-group">
							<label for="db_name_i">Nom</label><input id="db_name_i" type="text" name="db_name" class="form-control" value="<?php echo ToolBox::toHtml($system->getDbName()); ?>" />
						</div>
						<div class="form-group">
							<label for="db_user_i">Utilisateur</label><input id="db_user_i" type="text" name="db_user" class="form-control" value="<?php echo ToolBox::toHtml($system->getDbUser()); ?>" />
						</div>
						<div class="form-group">
							<label for="db_password_i">Mot de passe</label><input id="db_password_i" type="password" name="db_password" class="form-control" value="<?php echo ToolBox::toHtml($system->getDbPassword()); ?>" />
						</div>
						<div class="form-group">
							<label for="db_host_i">Hôte</label><input id="db_host_i" type="text" name="db_host" class="form-control" value="<?php echo ToolBox::toHtml($system->getDbHost()); ?>" />
						</div>
					</fieldset>
				</div>
				<div class="col-md-6">					
					<fieldset>
						<legend>Chemin d'accès aux fichiers</legend>
						<div class="form-group">
							<label for="dir_path_i">Répertoire où l'application est installée</label><input id="dir_path_i" type="text" name="dir_path" class="form-control" value="<?php echo ToolBox::toHtml($system->getDirectoryPath()); ?>" />
						</div>
						<div class="form-group">
							<label for="outsourcing_dir_path_i">Répertoire où les librairies tiers sont installées</label><input id="outsourcing_dir_path_i" type="text" name="outsourcing_dir_path" class="form-control" value="<?php echo ToolBox::toHtml($system->getOutsourcingDirectoryPath()); ?>" />
						</div>
						<div class="form-group">
							<label for="data_dir_path_i">Répertoire où les données propres à l'instance sont enregistrées</label><input id="data_dir_path_i" type="text" name="data_dir_path" class="form-control" value="<?php echo ToolBox::toHtml($system->getDataDirectoryPath()); ?>" />
						</div>
					</fieldset>
						<legend>Identité graphique</legend>
						<div class="form-group">
							<label for="project_theme_color_i">Couleur principale</label><input id="project_theme_color_i" type="text" name="project_theme_color" class="form-control" value="<?php echo $system->getProjectThemeColor(); ?>" />
						</div>
						<div class="form-group">
							<label for="project_background_color_i">Couleur complémentaire</label><input id="project_background_color_i" type="text" name="project_background_color" class="form-control" value="<?php echo $system->getProjectBackgroundColor(); ?>" />
						</div>
						<div class="form-group">
							<label for="home_screen_img_src_file_i">Image à utiliser sur l'écran d'accueil</label><input id="home_screen_img_src_file_i" type="file" name="home_screen_img_src_file" class="form-control-file" />
						</div>
					</fieldset>					
					<fieldset>
						<legend>Google analytics</legend>
						<div class="form-group">
							<label for="ga_key_i">Clé</label><input id="ga_key_i" type="text" name="ga_key" class="form-control" value="<?php echo ToolBox::toHtml($system->getGoogleAnalyticsKey()); ?>" />
						</div>
						<p class="help-block"><a href="https://www.google.com/analytics/web/">google.com/analytics/web</a></p>
					</fieldset>
					<fieldset>
						<legend>Divers</legend>
						<div class="form-group">
							<label for="host_purpose_i">Objectif de l'instance</label><select name="host_purpose" class="form-control"><?php echo $system->htmlHostPurposeOptions(); ?></select>
						</div>
					</fieldset>
				</div>
			</div>
			<div class="buttonBar">
				<a class="btn btn-link" href="<?php echo $system->getHomeUrl(); ?>">quitter</a>
				<button name="task_id" type="submit" value="save" class="btn btn-primary">enregistrer</button>
			</div>			
		</form>
	</div>
</body>
</html>
