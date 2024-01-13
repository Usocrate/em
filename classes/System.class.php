<?php
class System {
	private $dir_path;
	private $outsourcing_dir_path;
	private $data_dir_path;
	private $db_host;
	private $db_name;
	private $db_user;
	private $db_password;
	private $project_url;
	private $project_name;
	private $project_description;
	private $project_publisher;
	private $project_creator;
	private $project_launch_year;
	
	// identité graphique
	private $project_theme_color;
	private $project_background_color;
	
	private $host_purpose;
	private $pdo;
	private $bookmark_hit_frequency_avg;
	private $bookmark_hit_frequency_std;
	
	public function __construct($path) {
		$this->config_file_path = $path;
		if ($this->configFileExists ()) {
			$this->parseConfigFile ();
			spl_autoload_register ( array (
					$this,
					'loadClass'
			) );
		}
	}
	public function loadClass($class_name) {
		$path = $this->dir_path . DIRECTORY_SEPARATOR . 'classes' . DIRECTORY_SEPARATOR;
		if (is_file ( $path . $class_name . '.class.php' )) {
			include_once $path . $class_name . '.class.php';
			return true;
		} elseif ($path . '.interface.php') {
			include_once $path . $class_name . '.interface.php';
			return true;
		} else {
			error_log ( $class_name . ' not found.' );
			return false;
		}
	}
	public function configFileExists() {
		return file_exists ( $this->config_file_path );
	}
	public function parseConfigFile() {
		try {
			if (is_readable ( $this->config_file_path )) {
				$data = json_decode ( file_get_contents ( $this->config_file_path ), true );
				foreach ( $data as $key => $value ) {
					switch ($key) {
						case 'db_host' :
							$this->db_host = $value;
							break;
						case 'db_name' :
							$this->db_name = $value;
							break;
						case 'db_user' :
							$this->db_user = $value;
							break;
						case 'db_password' :
							$this->db_password = $value;
							break;
						case 'project_url' :
							$this->project_url = $value;
							break;
						case 'project_name' :
							$this->project_name = $value;
							break;
						case 'project_description' :
							$this->project_description = $value;
							break;
						case 'project_publisher' :
							$this->project_publisher = $value;
							break;
						case 'project_creator' :
							$this->project_creator = $value;
							break;
						case 'project_launch_year' :
							$this->project_launch_year = $value;
							break;
						case 'project_background_color' :
							$this->project_background_color = $value;
							break;
						case 'project_theme_color' :
							$this->project_theme_color = $value;
							break;
						case 'host_purpose' :
							$this->host_purpose = $value;
							break;
						case 'dir_path' :
							$this->dir_path = $value;
							break;
						case 'outsourcing_dir_path' :
							$this->outsourcing_dir_path = $value;
							break;
						case 'data_dir_path' :
							$this->data_dir_path = $value;
							break;
					}
				}
			} else {
				throw new Exception ( 'Le fichier de configuration doit être accessible en lecture.' );
			}
		} catch ( Exception $e ) {
			$this->reportException ( __METHOD__, $e );
			return false;
		}
	}
	public function saveConfigFile() {
		try {
			$a = array (
					'db_host' => $this->db_host,
					'db_name' => $this->db_name,
					'db_user' => $this->db_user,
					'db_password' => $this->db_password,
					'project_url' => $this->project_url,
					'project_name' => $this->project_name,
					'project_description' => $this->project_description,
					'project_publisher' => $this->project_publisher,
					'project_creator' => $this->project_creator,
					'project_launch_year' => $this->project_launch_year,
					'project_theme_color' => $this->project_theme_color,
					'project_background_color' => $this->project_background_color,
					'host_purpose' => $this->host_purpose,
					'dir_path' => $this->dir_path,
					'outsourcing_dir_path' => $this->outsourcing_dir_path,
					'data_dir_path' => $this->data_dir_path
			);
			return file_put_contents ( $this->config_file_path, json_encode ( $a ) );
		} catch ( Exception $e ) {
			$this->reportException ( __METHOD__, $e );
			return false;
		}
	}
	public function setDbHost($input) {
		$this->db_host = $input;
	}
	public function setDbName($input) {
		$this->db_name = $input;
	}
	public function setDbUser($input) {
		$this->db_user = $input;
	}
	public function setDbPassword($input) {
		$this->db_password = $input;
	}
	public function setProjectUrl($input) {
		$this->project_url = $input;
	}
	public function setProjectName($input) {
		$this->project_name = $input;
	}
	public function setProjectDescription($input) {
		$this->project_description = $input;
	}
	public function setProjectPublisher($input) {
		$this->project_publisher = $input;
	}
	public function setProjectCreator($input) {
		$this->project_creator = $input;
	}
	public function setProjectLaunchYear($input) {
		$this->project_launch_year = $input;
	}
	public function setHostPurpose($input) {
		$this->host_purpose = $input;
	}
	public function setDirectoryPath($input) {
		$this->dir_path = $input;
	}
	public function setOutsourcingDirectoryPath($input) {
		$this->outsourcing_dir_path = $input;
	}
	public function setDataDirectoryPath($input) {
		$this->data_dir_path = $input;
	}
	public function getDbHost() {
		return $this->db_host;
	}
	public function getDbName() {
		return $this->db_name;
	}
	public function getDbUser() {
		return $this->db_user;
	}
	public function getDbPassword() {
		return $this->db_password;
	}
	public function getProjectUrl() {
		return $this->project_url;
	}
	public function getHomeUrl() {
		return $this->getProjectUrl ();
	}
	/**
	 *
	 * @version 06/2017
	 */
	public function getConfigUrl() {
		return $this->getProjectUrl () . '/admin/config.php';
	}

	/**
	 * Fournit l'URL à laquelle l'utilisateur peut s'authentifier
	 *
	 * @since 02/2015
	 * @version 06/2017
	 */
	public function getLoginUrl($params = NULL) {
		$url = $this->getProjectUrl () . '/login.php';
		if (is_array ( $params ) && sizeof ( $params ) > 0) {
			$url .= '?';
			do {
				$url .= urlencode ( key ( $params ) ) . '=' . urlencode ( current ( $params ) );
				if (next ( $params )) {
					$url .= '&';
				}
			} while ( current ( $params ) );
		}
		return $url;
	}
	public function getTopicRemovalUrl(Topic $topic, Array $params = array ()) {
		try {
			$params ['topic_id'] = $topic->getId ();

			$url = $this->getProjectUrl () . '/topic_remove.php?';
			do {
				$url .= urlencode ( key ( $params ) ) . '=' . urlencode ( current ( $params ) );
				if (next ( $params )) {
					$url .= '&';
				}
			} while ( current ( $params ) );

			return $url;
		} catch ( Exception $e ) {
			$this->reportException ( __METHOD__, $e );
		}
	}
	public function getTopicExportationUrl(Topic $topic, Array $params = array ()) {
		try {
			$params ['topic_id'] = $topic->getId ();

			$url = $this->getProjectUrl () . '/netscape-bookmark-file-1.php?';
			do {
				$url .= urlencode ( key ( $params ) ) . '=' . urlencode ( current ( $params ) );
				if (next ( $params )) {
					$url .= '&';
				}
			} while ( current ( $params ) );

			return $url;
		} catch ( Exception $e ) {
			$this->reportException ( __METHOD__, $e );
		}
	}
	public function getTopicEditionUrl(Topic $topic, Array $params = array ()) {
		try {
			$params ['topic_id'] = $topic->getId ();

			$url = $this->getProjectUrl () . '/topic_edit.php?';
			do {
				$url .= urlencode ( key ( $params ) ) . '=' . urlencode ( current ( $params ) );
				if (next ( $params )) {
					$url .= '&';
				}
			} while ( current ( $params ) );

			return $url;
		} catch ( Exception $e ) {
			$this->reportException ( __METHOD__, $e );
		}
	}

	/**
	 *
	 * @param Topic $topic
	 * @param array $params
	 * @return string
	 */
	public function getTopicShortCutEditionUrl(Topic $topic, Array $params = array ()) {
		try {
			$params ['from_topic_id'] = $topic->getId ();

			$url = $this->getProjectUrl () . '/shortcut_edit.php?';
			do {
				$url .= urlencode ( key ( $params ) ) . '=' . urlencode ( current ( $params ) );
				if (next ( $params )) {
					$url .= '&';
				}
			} while ( current ( $params ) );

			return $url;
		} catch ( Exception $e ) {
			$this->reportException ( __METHOD__, $e );
		}
	}
	/**
	 *
	 * @version 06/2017
	 */
	public function getTopicNewBookmarkEditionUrl(Topic $topic, Array $params = array ()) {
		try {

			$params ['topic_id'] = $topic->getId ();

			$url = $this->getProjectUrl () . '/bookmark_edit.php?';
			do {
				$url .= urlencode ( key ( $params ) ) . '=' . urlencode ( current ( $params ) );
				if (next ( $params )) {
					$url .= '&';
				}
			} while ( current ( $params ) );

			return $url;
		} catch ( Exception $e ) {
			$this->reportException ( __METHOD__, $e );
		}
	}
	/**
	 *
	 * @version 06/2017
	 */
	public function getNewUserEditionUrl() {
		try {
			$url = $this->getProjectUrl () . '/user_edit.php';
			return $url;
		} catch ( Exception $e ) {
			$this->reportException ( __METHOD__, $e );
		}
	}
	public function getTopicNewSubtopicEditionUrl(Topic $topic, Array $params = array ()) {
		try {
			$params ['parent_id'] = $topic->getId ();
			$url = $this->getProjectUrl () . '/topic_edit.php?';
			do {
				$url .= urlencode ( key ( $params ) ) . '=' . urlencode ( current ( $params ) );
				if (next ( $params )) {
					$url .= '&';
				}
			} while ( current ( $params ) );
			return $url;
		} catch ( Exception $e ) {
			$this->reportException ( __METHOD__, $e );
		}
	}
	public function getHtmlHeadTagsForFavicon() {
		$output = array ();
		$output [] = '<link rel="icon" type="image/png" sizes="32x32" href="' . $this->getSkinUrl () . '/images/favicon-32x32.png">';
		$output [] = '<link rel="icon" type="image/png" sizes="16x16" href="' . $this->getSkinUrl () . '/images/favicon-16x16.png">';
		$output [] = '<link rel="manifest" href="' . $this->getSkinUrl () . '/manifest.json">';
		$output [] = '<meta name="application-name" content="' . ToolBox::toHtml ( $this->getProjectName () ) . '">';
		$output [] = '<meta name="theme-color" content="#92a7be">';
		return $output;
	}
	public function writeHtmlHeadTagsForFavicon() {
		foreach ( $this->getHtmlHeadTagsForFavicon () as $tag ) {
			echo $tag;
		}
	}
	public function getProjectName() {
		return $this->project_name;
	}
	public function projectNameToHtml() {
		return ToolBox::toHtml ( $this->project_name );
	}
	public function getProjectDescription() {
		return $this->project_description;
	}
	public function projectDescriptionToHtml() {
		return ToolBox::toHtml ( $this->project_description );
	}
	public function getProjectPublisher() {
		return $this->project_publisher;
	}
	public function projectPublisherToHtml() {
		return ToolBox::toHtml ( $this->project_publisher );
	}
	public function getProjectCreator() {
		return $this->project_creator;
	}
	public function projectCreatorToHtml() {
		return ToolBox::toHtml ( $this->project_creator );
	}
	public function getProjectLaunchYear() {
		return $this->project_launch_year;
	}
	/**
	 *
	 * @since 09/2022
	 */
	public function setProjectThemeColor($input) {
		$this->project_theme_color = $input;
	}
	/**
	 *
	 * @since 09/2022
	 */
	public function getProjectThemeColor() {
		return $this->project_theme_color;
	}
	/**
	 *
	 * @since 09/2022
	 */
	public function setProjectBackgroundColor($input) {
		$this->project_background_color = $input;
	}
	/**
	 *
	 * @since 09/2022
	 */
	public function getProjectBackgroundColor() {
		return $this->project_background_color;
	}
	public function projectLaunchYearToHtml() {
		return ToolBox::toHtml ( $this->project_launch_year );
	}
	public function getProjectLivingYears() {
		$output = array ();
		for($y = $this->getProjectLaunchYear (); $y <= ( int ) date ( 'Y' ); $y ++) {
			$output [] = ( string ) $y;
		}
		return $output;
	}
	public function countProjectLivingYears() {
		return count ( $this->getProjectLivingYears () );
	}
	public function getHostPurpose() {
		return $this->host_purpose;
	}
	public function getDirectoryPath() {
		return $this->dir_path;
	}
	public function getConfigDirectoryPath() {
		return $this->getDirectoryPath () . DIRECTORY_SEPARATOR . 'config';
	}
	public function getOutsourcingDirectoryPath() {
		return $this->outsourcing_dir_path;
	}
	public function getDataDirectoryPath() {
		return $this->data_dir_path;
	}
	public function getSnapshotsDirectoryPath() {
		return $this->getDataDirectoryPath () . DIRECTORY_SEPARATOR . 'snapshots';
	}
	public function getHostPurposeOptions() {
		return array (
				'test',
				'production'
		);
	}
	public function htmlHostPurposeOptions() {
		$html = '';
		foreach ( $this->getHostPurposeOptions () as $o ) {
			$html .= strcmp ( $o, $this->host_purpose ) == 0 ? '<option value="' . ToolBox::toHtml ( $o ) . '" selected>' . ToolBox::toHtml ( $o ) . '</option>' : '<option value="' . ToolBox::toHtml ( $o ) . '">' . ToolBox::toHtml ( $o ) . '</option>';
		}
		return $html;
	}

	/**
	 * Retourne un PHP Data Object permettant de se connecter à la date de données.
	 *
	 * @since 11/2013
	 * @version 02/2014
	 * @return PDO
	 */
	public function getPdo() {
		try {
			if (! isset ( $this->pdo )) {
				$this->pdo = new PDO ( 'mysql:host=' . $this->db_host . ';dbname=' . $this->db_name, $this->db_user, $this->db_password, array (
						PDO::ATTR_PERSISTENT => true
				) );
				$this->pdo->setAttribute ( PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION );
				$this->pdo->exec ( 'SET NAMES utf8' );
			}
			return $this->pdo;
		} catch ( PDOException $e ) {
			$this->reportException ( __METHOD__, $e );
			return false;
		}
	}

	/**
	 * Authentifie l'utilisateur à partir des cookies.
	 *
	 * @return int null
	 * @version 09/2014
	 */
	public function getUserIdFromCookies() {
		try {
			if (isset ( $_COOKIE ['user_id'] ) && isset ( $_COOKIE ['user_session_id'] )) {
				$user = new User ( urldecode ( $_COOKIE ['user_id'] ) );
				$session = new UserSession ( urldecode ( $_COOKIE ['user_session_id'] ) );
				if (! $session->isValid ( $user->getId () )) {
					setcookie ( 'user_id', NULL, time () - 1 );
					setcookie ( 'user_session_id', NULL, time () - 1 );
					throw new Exception ( 'La session enregistrée sous forme de cookie était invalide le cookie a été supprimé.' );
				}
				return $user->getId ();
			}
		} catch ( Exception $e ) {
			$this->reportException ( __METHOD__, $e );
		}
	}

	/**
	 * Indique si un utilisateur authentifié est trouvé (session php + cookie)
	 *
	 * @return bool
	 * @since 04/2010
	 */
	public function isUserAuthenticated() {
		if (empty ( $_SESSION ['user_id'] )) {
			$_SESSION ['user_id'] = $this->getUserIdFromCookies ();
		}
		return ! empty ( $_SESSION ['user_id'] );
	}

	/**
	 * Informe d'une exception.
	 *
	 * @since 08/2012
	 * @version 09/2014
	 */
	public function reportException($context = null, Exception $e) {
		$message = empty ( $context ) ? $e->getMessage () : $context . ' : ' . $e->getMessage ();
		switch ($this->getHostPurpose ()) {
			case 'production' :
				error_log ( $message );
				break;
			default :
				error_log ( $message );
		}
	}
	/**
	 *
	 * @version 06/2017
	 */
	public function getSkinUrl() {
		return $this->getProjectUrl () . '/skin';
	}
	/**
	 *
	 * @version 06/2017
	 */
	public function getImagesUrl() {
		return $this->getSkinUrl () . '/images';
	}
	/**
	 *
	 * @since 09/2022
	 */
	public function getVisuImgUrl($context='home') {
		$images_dir_path = $this->dir_path . DIRECTORY_SEPARATOR . 'skin' . DIRECTORY_SEPARATOR . 'images';
		
		switch ($context) {
			case 'home' :
				if (! is_file ( $images_dir_path . DIRECTORY_SEPARATOR . 'home_reworked.png' )) {
					$this->reworkPhotoFile ( $images_dir_path . DIRECTORY_SEPARATOR . 'home.png', 1472);
				}
				return $this->getImagesUrl () . '/home_reworked.png';
				break;
			case 'login' :
				if (! is_file ( $images_dir_path . DIRECTORY_SEPARATOR . 'login_reworked.png' )) {
					$this->reworkPhotoFile ( $images_dir_path . DIRECTORY_SEPARATOR . 'login.png', 465);
				}
				return $this->getImagesUrl () . '/login_reworked.png';
				break;
		}
	}

	/**
	 *
	 * @since 09/2022
	 */
	public function reworkPhotoFile($file_path, int $targetScale_width=950) {
		try {
			$path_parts = pathinfo ( $file_path );

			$im = new Imagick ( $file_path );

			$im->scaleImage ( $targetScale_width, 0 );

			$im->normalizeimage ();
			$im->orderedPosterizeImage ( "h4x4a", imagick::CHANNEL_BLUE );
			$im->orderedPosterizeImage ( "h4x4a", imagick::CHANNEL_GREEN );
			$im->transformimagecolorspace ( Imagick::COLORSPACE_GRAY );

			$targetPath = $path_parts ['dirname'] . DIRECTORY_SEPARATOR . $path_parts ['filename'] . '_reworked.' . $path_parts ['extension'];
			$handle = fopen ( $targetPath, 'w+' );

			return $im->writeimagefile ( $handle );
		} catch ( Exception $e ) {
			$this->reportException ( __METHOD__, $e );
			return false;
		}
	}

	/**
	 * Détecte un utilisateur authentifié (session php + cookie)
	 *
	 * @return bool
	 * @since 04/2010
	 */
	public function lookForAuthenticatedUser() {
		if (empty ( $_SESSION ['user_id'] )) {
			$_SESSION ['user_id'] = $this->getUserIdFromCookies ();
		}
	}
	/**
	 * @since 01/2024
	 * @return boolean
	 */
	public function isTourRequested() {
		return isset($_SESSION ['isTourRequested']) && $_SESSION ['isTourRequested']===true;
	}
	/**
	 *
	 * @since 01/04/2010
	 */
	public function getAuthenticatedUserId() {
		return $this->isUserAuthenticated () ? $_SESSION ['user_id'] : null;
	}
	/**
	 * Renvoie l'ensemble des utilisateurs accrédités.
	 *
	 * @return array
	 * @since 05/2006
	 * @version 11/2013
	 */
	public function getUsers() {
		try {
			$users = array ();
			$data = $this->getPdo ()->query ( 'SELECT * FROM ' . User::getTableName () . ' AS u' )->fetchAll ();
			foreach ( $data as $d ) {
				$u = new User ();
				$u->hydrate ( $d );
				array_push ( $users, $u );
			}
			return $users;
		} catch ( Exception $e ) {
			$this->reportException ( __METHOD__, $e );
			return false;
		}
	}
	public function getHtmlLink($target = '_top') {
		return '<a target="' . $target . '" href="' . ToolBox::toHtml ( $this->getProjectUrl () ) . '" title="' . ToolBox::toHtml ( $this->getProjectDescription () ) . '">' . ToolBox::toHtml ( $this->getProjectName () ) . '</a>';
	}
	public function getHtmlYearDocLink() {
		return $this->getCreationDate () ? '<a href="' . $this->getProjectUrl () . '/year.php?year_id=' . $this->creation_date->format ( "Y" ) . '">' . $this->creation_date->format ( "Y" ) . '</a>' : NULL;
	}

	/**
	 * Obtient les éditeurs de ressources dont le nom contient la chaîne de caractères passée en paramètre.
	 *
	 * @param string $clue
	 *        	La chaîne de caractères qu'il faut retrouver dans le nom des éditeurs
	 * @since 06/2008
	 * @version 01/2018
	 */
	public function getPublishersByNameClue($clue) {
		return $this->getPublishers ( array (
				'nameClue' => $clue
		) );
	}

	/**
	 *
	 * @since 01/2018
	 */
	public function getPublisherByName($name) {
		$list = $this->getPublishers ( array (
				'name' => $name
		) );
		return count ( $list ) == 1 ? current ( $list ) : null;
	}

	/**
	 *
	 * @param array $criteria
	 * @return array
	 * @version 01/2018
	 */
	public function getPublishers($criteria = NULL) {
		try {
			/*
			 * Préparation de la requête
			 */
			$sql = 'SELECT bookmark_publisher, COUNT(DISTINCT bookmark_id) AS bookmarks_nb';
			$sql .= ' FROM ' . $this->getBookmarkTableName () . ' AS b';
			$sql .= ' LEFT JOIN ' . $this->getTopicTableName () . ' AS t USING(topic_id)';
			$clauses = array ();
			if (isset ( $criteria ['nameClue'] )) {
				$clauses [] = 'bookmark_publisher LIKE :publisher_name_clue';
			} elseif (isset ( $criteria ['name'] )) {
				$clauses [] = 'bookmark_publisher = :publisher_name';
			} else {
				$clauses [] = 'bookmark_publisher IS NOT NULL';
				$clauses [] = 'bookmark_publisher<>""';
			}
			// limitation aux éditeurs de ressources publiques
			if (empty ( $_SESSION ['user_id'] )) {
				$clauses [] = 'bookmark_private=0';
				$clauses [] = 'topic_private=0';
			}
			// les éditeurs des signets correspondant à des mots-clefs sélectionnés
			if (isset ( $criteria ['bookmarkClues'] )) {
				for($i = 0; $i < count ( $criteria ['bookmarkClues'] ); $i ++) {
					$clauses [] = '(bookmark_title LIKE :bookmarkTitle' . $i . ' OR bookmark_description LIKE :bookmarkDescription' . $i . ')';
				}
			}
			// uniquement les éditeurs représentés dans une rubrique
			if (isset ( $criteria ['topic'] )) {
				$clauses [] = 'topic_interval_lowerlimit>=:topicIntervalLowerLimit';
				$clauses [] = 'topic_interval_higherlimit<=:topicIntervalHigherLimit';
			}
			$sql .= ' WHERE ' . implode ( ' AND ', $clauses );
			$sql .= ' GROUP BY bookmark_publisher';
			$sql .= ' ORDER BY bookmarks_nb DESC';
			// $sql.= ' HAVING bookmarks_nb>1';

			if (isset ( $criteria ['rowCount'] )) {
				$sql .= ' LIMIT :rowCount';
			}
			// echo $sql;
			$statement = $this->getPdo ()->prepare ( $sql );

			/*
			 * Attachement des paramètres
			 */
			if (isset ( $criteria ['nameClue'] )) {
				$statement->bindValue ( ':publisher_name_clue', '%' . $criteria ['nameClue'] . '%', PDO::PARAM_STR );
			} elseif (isset ( $criteria ['name'] )) {
				$statement->bindValue ( ':publisher_name', $criteria ['name'], PDO::PARAM_STR );
			}
			if (isset ( $criteria ['bookmarkClues'] )) {
				for($i = 0; $i < count ( $criteria ['bookmarkClues'] ); $i ++) {
					$statement->bindValue ( ':bookmarkTitle' . $i, '%' . $criteria ['bookmarkClues'] [$i] . '%', PDO::PARAM_STR );
					$statement->bindValue ( ':bookmarkDescription' . $i, '%' . $criteria ['bookmarkClues'] [$i] . '%', PDO::PARAM_STR );
				}
			}
			if (isset ( $criteria ['topic'] )) {
				$statement->bindValue ( ':topicIntervalLowerLimit' . $criteria ['topic']->getIntervalLowerLimit (), PDO::PARAM_INT );
				$statement->bindValue ( ':topicIntervalHigherLimit' . $criteria ['topic']->getIntervalHigherLimit (), PDO::PARAM_INT );
			}
			if (isset ( $criteria ['rowCount'] )) {
				$statement->bindValue ( ':rowCount', $criteria ['rowCount'], PDO::PARAM_INT );
			}
			$statement->execute ();

			/*
			 * Construction de la réponse
			 */
			$output = array ();
			while ( $d = $statement->fetch ( PDO::FETCH_ASSOC ) ) {
				$output [] = new Publisher ( $d ['bookmark_publisher'], $d ['bookmarks_nb'] );
			}
			return $output;
		} catch ( Exception $e ) {
			$this->reportException ( __METHOD__, $e );
			return false;
		}
	}

	/**
	 * Renvoie les éditeurs les plus présents dans le catalogue;
	 *
	 * @return array
	 * @since 05/11/2005
	 * @version 30/11/2013
	 */
	public function getTopPublishers($nb) {
		return $this->getPublishers ( array (
				'rowCount' => $nb
		) );
	}

	/**
	 * Renvoie une ressource choisie aléatoirement
	 *
	 * @return Bookmark
	 * @version 07/05/2014
	 */
	public function getAnyBookmark() {
		try {
			$offset = mt_rand ( 0, $this->countBookmarks () - 1 );
			$statement = $this->getBookmarkCollectionStatement ( NULL, NULL, 1, $offset );
			$statement->execute ();
			$b = new Bookmark ();
			$b->hydrate ( $statement->fetch (), 'bookmark_' );
			return $b;
		} catch ( Exception $e ) {
			$this->reportException ( __METHOD__, $e );
		}
	}

	/**
	 *
	 * @return string
	 * @since 02/2010
	 * @version 06/2014
	 */
	public function getOldestBookmarkCreationYear() {
		try {
			$sql = 'SELECT YEAR(bookmark_creation_date) FROM ' . $this->getBookmarkTableName () . ' ORDER BY bookmark_creation_date LIMIT 1';
			$statement = $this->getPdo ()->prepare ( $sql );
			$statement->execute ();
			return $statement->fetchColumn ();
		} catch ( Exception $e ) {
			$this->reportException ( __METHOD__, $e );
			return false;
		}
	}

	/**
	 * Construit la requête permettant d'obtenir les données des signets, avec critères de sélection éventuels.
	 *
	 * @return PDOStatement
	 * @since 05/2014
	 * @version 01/2018
	 */
	public function getBookmarkCollectionStatement($criteria = null, $sort = null, $count = null, $offset = 0) {
		try {
			// SELECT
			$select = array ();
			$select [] = 'b.*';
			$select [] = 't.*';
			$select [] = 'COUNT(DISTINCT(DATE(h.hit_date))) AS bookmark_dayWithHit_count';
			$select [] = 'MAX(hit_date) AS bookmark_lasthit_date';
			$select [] = 'GREATEST(MAX(hit_date), b.bookmark_lastedit_date) AS bookmark_lastfocus_date';
			$select [] = isset ( $criteria ['hit_period_start_date'] ) ? '(COUNT(DISTINCT(DATE(h.hit_date)))/(DATEDIFF(NOW(),:hit_period_start_date)+1)) AS bookmark_hit_frequency' : '(COUNT(DISTINCT(DATE(h.hit_date)))/(DATEDIFF(NOW(),bookmark_creation_date)+1)) AS bookmark_hit_frequency';

			$sql = 'SELECT ' . implode ( ',', $select );
			$sql .= ' FROM ' . $this->getBookmarkTableName () . ' AS b';
			$sql .= ' INNER JOIN ' . $this->getTopicTableName () . ' AS t USING (topic_id)';
			$sql .= ' LEFT JOIN ' . $this->getHitTableName () . ' AS h USING (bookmark_id)';

			// WHERE
			$where = array ();

			if (isset ( $criteria ['bookmark_id'] )) {
				$where [] = 'b.bookmark_id = :id';
			}

			if (isset ( $criteria ['bookmark_url_like_pattern'] )) {
				$where [] = 'bookmark_url LIKE :url_like';
			}

			if (isset ( $criteria ['bookmark_url_regexp_pattern'] )) {
				$where [] = 'bookmark_url REGEXP :url_regexp';
			}

			if (isset ( $criteria ['bookmark_title_like_pattern'] )) {
				$where [] = 'bookmark_title LIKE :title_like';
			}

			if (isset ( $criteria ['bookmark_title'] )) {
				$where [] = 'bookmark_title = :title';
			}

			if (isset ( $criteria ['bookmark_keywords'] )) {
				for($i = 1; $i <= count ( $criteria ['bookmark_keywords'] ); $i ++) {
					$where [] = '(bookmark_title LIKE :keyword' . $i . ' OR bookmark_description LIKE :keyword' . $i . ')';
				}
			}

			if (isset ( $criteria ['bookmark_creation_year'] )) {
				$where [] = 'YEAR(b.bookmark_creation_date) = :bookmark_creation_year';
			}

			if (isset ( $criteria ['bookmark_publisher_like_pattern'] )) {
				$where [] = 'bookmark_publisher LIKE :publisher_like';
			}

			if (isset ( $criteria ['bookmark_publisher'] )) {
				$where [] = 'bookmark_publisher = :publisher';
			}

			if (isset ( $criteria ['topic_id'] )) {
				$where [] = empty ( $criteria ['topic_id'] ) ? 't.topic_id IS NULL' : 't.topic_id = :topic_id';
			}

			if (isset ( $criteria ['topic_interval_lowerlimit'] )) {
				$where [] = 'topic_interval_lowerlimit >= :topic_interval_lowerlimit';
			}

			if (isset ( $criteria ['topic_interval_lowerlimit'] )) {
				$where [] = 'topic_interval_higherlimit <= :topic_interval_higherlimit';
			}

			if (isset ( $criteria ['lastBookmarkUsedAsLocationRef_id'] )) {
				$where [] = 'b.lastBookmarkUsedAsLocationRef_id = :lastBookmarkUsedAsLocationRef_id';
			}

			if (isset ( $criteria ['hit_year'] )) {
				$where [] = 'YEAR(hit_date) = :hit_year';
			}

			if (isset ( $criteria ['hit_period_start_date'] )) {
				$where [] = 'hit_date >= :hit_period_start_date';
			}

			if (empty ( $_SESSION ['user_id'] )) {
				// limitation aux ressources publiques
				$where [] = '(bookmark_private=0 AND topic_private=0)';
			}
			if (count ( $where ) > 0) {
				$sql .= ' WHERE (' . implode ( ' AND ', $where ) . ')';
			}

			// GROUP BY / ORDER BY
			switch ($sort) {
				case 'Last created first' :
					$sql .= ' GROUP BY bookmark_creation_date, b.bookmark_id';
					$sql .= ' ORDER BY bookmark_creation_date DESC';
					break;
				case 'Most anciently created first' :
					$sql .= ' GROUP BY bookmark_creation_date, b.bookmark_id';
					$sql .= ' ORDER BY bookmark_creation_date ASC';
					break;
				case 'Last hit first' :
					$sql .= ' GROUP BY b.bookmark_id';
					$sql .= ' ORDER BY bookmark_lasthit_date DESC';
					break;
				case 'Alphabetical' :
					$sql .= ' GROUP BY b.bookmark_title, b.bookmark_id';
					$sql .= ' ORDER BY b.bookmark_title ASC';
					break;
				case 'Most daily hit first' :
					$sql .= ' GROUP BY b.bookmark_id';
					$sql .= ' ORDER BY bookmark_dayWithHit_count DESC';
					break;
				case 'Last focused first' :
					$sql .= ' GROUP BY b.bookmark_id';
					$sql .= ' ORDER BY bookmark_lastfocus_date DESC';
					break;
				case 'Most anciently focused first' :
					$sql .= ' GROUP BY b.bookmark_id';
					$sql .= ' ORDER BY bookmark_lastfocus_date ASC';
					break;
				default : // Highest hit frequency first
					$sql .= ' GROUP BY b.bookmark_id';
					$sql .= ' ORDER BY bookmark_hit_frequency DESC';
			}

			// LIMIT
			if (isset ( $count )) {
				$sql .= isset ( $offset ) ? ' LIMIT :offset,:count' : ' LIMIT :count';
			}

			$statement = $this->getPdo ()->prepare ( $sql );

			/*
			 * Rattachement des variables
			 */
			if (isset ( $criteria ['bookmark_id'] )) {
				$statement->bindValue ( ':id', ( int ) $criteria ['bookmark_id'], PDO::PARAM_INT );
			}

			if (isset ( $criteria ['bookmark_url_like_pattern'] )) {
				$statement->bindValue ( ':url_like', '%' . $criteria ['bookmark_url_like_pattern'] . '%', PDO::PARAM_STR );
			}

			if (isset ( $criteria ['bookmark_url_regexp_pattern'] )) {
				$statement->bindValue ( ':url_regexp', $criteria ['bookmark_url_regexp_pattern'], PDO::PARAM_STR );
			}

			if (isset ( $criteria ['bookmark_title'] )) {
				$statement->bindValue ( ':title', $criteria ['bookmark_title'], PDO::PARAM_STR );
			}

			if (isset ( $criteria ['bookmark_title_like_pattern'] )) {
				$statement->bindValue ( ':title_like', '%' . $criteria ['bookmark_title_like_pattern'] . '%', PDO::PARAM_STR );
			}

			if (isset ( $criteria ['bookmark_keywords'] )) {
				$i = 1;
				foreach ( $criteria ['bookmark_keywords'] as $k ) {
					// echo '<p>:keyword'.$i.': %' . $k . '%</p>';
					$statement->bindValue ( ':keyword' . $i, '%' . $k . '%', PDO::PARAM_STR );
					$i ++;
				}
			}

			if (isset ( $criteria ['bookmark_publisher_like_pattern'] )) {
				$statement->bindValue ( ':publisher_like', '%' . $criteria ['bookmark_publisher_like_pattern'] . '%', PDO::PARAM_STR );
			}

			if (isset ( $criteria ['bookmark_publisher'] )) {
				$statement->bindValue ( ':publisher', $criteria ['bookmark_publisher'], PDO::PARAM_STR );
			}

			if (isset ( $criteria ['bookmark_creation_year'] )) {
				$statement->bindValue ( ':bookmark_creation_year', $criteria ['bookmark_creation_year'], PDO::PARAM_STR );
			}

			if (isset ( $criteria ['topic_id'] )) {
				if (! empty ( $criteria ['topic_id'] )) {
					$statement->bindValue ( ':topic_id', ( int ) $criteria ['topic_id'], PDO::PARAM_INT );
				}
			}

			if (isset ( $criteria ['topic_interval_lowerlimit'] )) {
				$statement->bindValue ( ':topic_interval_lowerlimit', ( int ) $criteria ['topic_interval_lowerlimit'], PDO::PARAM_INT );
			}

			if (isset ( $criteria ['topic_interval_lowerlimit'] )) {
				$statement->bindValue ( ':topic_interval_higherlimit', ( int ) $criteria ['topic_interval_higherlimit'], PDO::PARAM_INT );
			}

			if (isset ( $criteria ['lastBookmarkUsedAsLocationRef_id'] )) {
				$statement->bindValue ( ':lastBookmarkUsedAsLocationRef_id', ( int ) $criteria ['lastBookmarkUsedAsLocationRef_id'], PDO::PARAM_INT );
			}

			if (isset ( $criteria ['hit_year'] )) {
				$statement->bindValue ( ':hit_year', $criteria ['hit_year'], PDO::PARAM_STR );
			}

			if (isset ( $criteria ['hit_period_start_date'] )) {
				$statement->bindValue ( ':hit_period_start_date', $criteria ['hit_period_start_date'], PDO::PARAM_STR );
			}

			if (isset ( $count )) {
				if (isset ( $offset )) {
					$statement->bindValue ( ':offset', ( int ) $offset, PDO::PARAM_INT );
				}
				$statement->bindValue ( ':count', ( int ) $count, PDO::PARAM_INT );
			}
			$statement->setFetchMode ( PDO::FETCH_ASSOC );
			return $statement;
		} catch ( Exception $e ) {
			$this->reportException ( __METHOD__, $e );
			return false;
		}
	}
	/**
	 * Obtient les données d'un signet.
	 *
	 * @param int $id
	 * @return array
	 * @since 19/11/2007
	 * @version 25/05/2014
	 */
	public function getBookmarkData($id) {
		try {
			$statement = $this->getBookmarkCollectionStatement ( array (
					'bookmark_id' => $id
			) );
			if ($statement->execute () === false) {
				throw new Exception ( $statement->errorInfo () );
			}
			return $statement->fetch ();
		} catch ( Exception $e ) {
			$this->reportException ( __METHOD__, $e );
			return false;
		}
	}

	/**
	 * Obtient la fréquence de consultation moyenne et l'écart-type pour l'ensemble des signets.
	 *
	 * @since 15/08/2012
	 */
	private function initBookmarkHitFrequencyStats() {
		try {
			$sql = 'SELECT AVG(frequency), STD(frequency)';
			$sql .= ' FROM (';
			$sql .= ' SELECT COUNT(DISTINCT(DATE(h.hit_date)))/(DATEDIFF(NOW(),b.bookmark_creation_date)+1) AS frequency';
			$sql .= ' FROM ' . $this->getBookmarkTableName () . ' AS b';
			$sql .= ' LEFT JOIN ' . $this->getHitTableName () . ' AS h ON h.bookmark_id=b.bookmark_id';
			$sql .= ' GROUP BY b.bookmark_id';
			$sql .= ') AS t';

			$statement = $this->getPdo ()->prepare ( $sql );
			if ($statement->execute ()) {
				$data = $statement->fetch ( PDO::FETCH_NUM );
				$this->bookmark_hit_frequency_avg = $data [0];
				$this->bookmark_hit_frequency_std = $data [1];
				$statement->closeCursor ();
			}
		} catch ( Exception $e ) {
			$this->reportException ( __METHOD__, $e );
			return false;
		}
	}
	/**
	 *
	 * @since 29/04/2017
	 */
	public function getMostSeasonnallyHitBookmarksStatement() {
		$sql = 'SELECT AVG(DAYOFYEAR(h.hit_date)), STD(DAYOFYEAR(h.hit_date)), b.*';
		$sql .= ' FROM ' . $this->getHitTableName () . ' AS h';
		$sql .= ' LEFT JOIN ' . $this->getBookmarkTableName () . ' AS b ON b.bookmark_id=h.bookmark_id';
		$sql .= ' GROUP BY h.bookmark_id';
		$sql .= ' HAVING COUNT(DISTINCT(h.hit_date)) > 10 AND COUNT(DISTINCT(YEAR(h.hit_date))) > 2'; // au moins dix consultations et plus de deux ans de consultation pour figurer au classement
		$sql .= ' ORDER BY STD(DAYOFYEAR(h.hit_date)) ASC';
		$sql .= ' LIMIT 0,10';
		return $this->getPdo ()->prepare ( $sql );
	}
	/**
	 *
	 * @since 29/04/2017
	 */
	public function getMostSeasonnallyHitBookmarkCollection() {
		$statement = $this->getMostSeasonnallyHitBookmarksStatement ();
		return new BookmarkCollection ( $statement );
	}
	/**
	 *
	 * @since 02/01/2016
	 * @return array
	 */
	public function getBookmarkBreakdownFromHitFrequency() {
		try {
			$sql = 'SELECT ROUND(frequency,3), COUNT(*)';
			$sql .= ' FROM (';
			$sql .= ' SELECT COUNT(DISTINCT(DATE(h.hit_date)))/(DATEDIFF(NOW(),b.bookmark_creation_date)+1) AS frequency';
			$sql .= ' FROM ' . $this->getBookmarkTableName () . ' AS b';
			$sql .= ' LEFT JOIN ' . $this->getHitTableName () . ' AS h ON h.bookmark_id=b.bookmark_id';
			$sql .= ' GROUP BY b.bookmark_id';
			$sql .= ') AS t';
			$sql .= ' GROUP BY ROUND(frequency,3)';

			$statement = $this->getPdo ()->prepare ( $sql );
			if ($statement->execute ()) {
				$data = $statement->fetchAll ( PDO::FETCH_NUM );
				$output = array ();
				foreach ( $data as $datum ) {
					$output [$datum [0]] = $datum [1];
				}
				return $output;
			}
		} catch ( Exception $e ) {
			$this->reportException ( __METHOD__, $e );
			return false;
		}
	}

	/**
	 * Obtient la fréquence moyenne de consultation des signets.
	 *
	 * @since 15/08/2012
	 */
	public function getBookmarkHitFrequencyAvg() {
		if (! isset ( $this->bookmark_hit_frequency_avg )) {
			$this->initBookmarkHitFrequencyStats ();
		}
		return $this->bookmark_hit_frequency_avg;
	}

	/**
	 * Obtient l'écart-type à la fréquence moyenne de consultation des signets.
	 *
	 * @since 15/08/2012
	 */
	public function getBookmarkHitFrequencyStd() {
		if (! isset ( $this->bookmark_hit_frequency_std )) {
			$this->initBookmarkHitFrequencyStats ();
		}
		return $this->bookmark_hit_frequency_std;
	}

	/**
	 * Indique la fréquence de consultation au-delà de laquelle un signet est considéré comme très utilisé
	 *
	 * @since 08/2012
	 */
	public function getHotBookmarkHitFrequency() {
		return $this->getBookmarkHitFrequencyAvg () + $this->getBookmarkHitFrequencyStd ();
	}

	/**
	 *
	 * @since 11/2009
	 */
	public function getBookmarkById($id) {
		if (is_numeric ( $id )) {
			$data = $this->getBookmarkData ( $id );
			if (is_array ( $data )) {
				$b = new Bookmark ();
				$b->hydrate ( $data );
				return $b;
			}
		}
	}

	/**
	 *
	 * @return Bookmark
	 * @since 08/2014
	 */
	public function getBookmarkByTitle($title) {
		try {
			$s = $this->getBookmarkCollectionStatement ( array (
					'bookmark_title' => $title
			) );
			$c = new BookmarkCollection ( $s );
			if (count ( $c ) == 1) {
				$i = $c->getIterator ();
				return $i->current ();
			}
		} catch ( Exception $e ) {
			$this->reportException ( __METHOD__, $e );
		}
	}

	/**
	 * Retourne le nombre de signets correspondant aux critères transmis.
	 *
	 * @return int
	 * @version 05/2014
	 */
	public function countBookmarks($criteria = NULL) {
		try {
			$sql = 'SELECT COUNT(*) FROM ' . $this->getBookmarkTableName () . ' AS b INNER JOIN ' . $this->getTopicTableName () . ' AS t ON b.topic_id=t.topic_id';

			// //////////////////////////////////
			// WHERE
			// //////////////////////////////////
			$conditions = array ();

			if (isset ( $criteria ['bookmark_id'] )) {
				$conditions [] = 'b.bookmark_id LIKE :id';
			}

			if (isset ( $criteria ['bookmark_url_like_pattern'] )) {
				$conditions [] = 'bookmark_url LIKE :url_like';
			}

			if (isset ( $criteria ['bookmark_url_regexp_pattern'] )) {
				$conditions [] = 'bookmark_url REGEXP :url_regexp';
			}

			if (isset ( $criteria ['bookmark_keywords'] )) {
				for($i = 1; $i <= count ( $criteria ['bookmark_keywords'] ); $i ++) {
					$conditions [] = '(bookmark_title LIKE :keyword' . $i . ' OR bookmark_description LIKE :keyword' . $i . ')';
				}
			}

			if (isset ( $criteria ['bookmark_creation_year'] )) {
				$conditions [] = 'YEAR(b.bookmark_creation_date) = :bookmark_creation_year';
			}

			if (isset ( $criteria ['bookmark_publisher_like_pattern'] )) {
				$conditions [] = 'bookmark_publisher LIKE :publisher_like';
			}

			if (isset ( $criteria ['bookmark_publisher'] )) {
				$conditions [] = 'bookmark_publisher = :publisher';
			}

			if (isset ( $criteria ['topic_id'] )) {
				$conditions [] = empty ( $criteria ['topic_id'] ) ? 't.topic_id IS NULL' : 't.topic_id = :topic_id';
			}

			if (isset ( $criteria ['topic_interval_lowerlimit'] )) {
				$conditions [] = 't.topic_interval_lowerlimit >= :topic_interval_lowerlimit';
			}

			if (isset ( $criteria ['topic_interval_lowerlimit'] )) {
				$conditions [] = 't.topic_interval_higherlimit <= :topic_interval_higherlimit';
			}

			if (empty ( $_SESSION ['user_id'] )) {
				// limitation aux ressources publiques
				$conditions [] = '(bookmark_private=0 AND topic_private=0)';
			}
			if (count ( $conditions ) > 0) {
				$sql .= ' WHERE (' . implode ( ' AND ', $conditions ) . ')';
			}

			$statement = $this->getPdo ()->prepare ( $sql );

			// //////////////////////////////////
			// Rattachement des variables
			// //////////////////////////////////
			if (isset ( $criteria ['bookmark_id'] )) {
				$statement->bindValue ( ':id', ( int ) $criteria ['bookmark_id'], PDO::PARAM_INT );
			}

			if (isset ( $criteria ['bookmark_url_like_pattern'] )) {
				$statement->bindValue ( ':url_like', '%' . $criteria ['bookmark_url_like_pattern'] . '%', PDO::PARAM_STR );
			}

			if (isset ( $criteria ['bookmark_url_regexp_pattern'] )) {
				$statement->bindValue ( ':url_regexp', $criteria ['bookmark_url_like_pattern'], PDO::PARAM_STR );
			}

			if (isset ( $criteria ['bookmark_keywords'] )) {
				$i = 1;
				foreach ( $criteria ['bookmark_keywords'] as $k ) {
					$statement->bindValue ( ':keyword' . $i, '%' . $k . '%', PDO::PARAM_STR );
					$i ++;
				}
			}

			if (isset ( $criteria ['bookmark_publisher_like_pattern'] )) {
				$statement->bindValue ( ':publisher_like', '%' . $criteria ['bookmark_publisher_like_pattern'] . '%', PDO::PARAM_STR );
			}

			if (isset ( $criteria ['bookmark_publisher'] )) {
				$statement->bindValue ( ':publisher', $criteria ['bookmark_publisher'], PDO::PARAM_STR );
			}

			if (isset ( $criteria ['bookmark_creation_year'] )) {
				$statement->bindValue ( ':bookmark_creation_year', $criteria ['bookmark_creation_year'], PDO::PARAM_STR );
			}

			if (isset ( $criteria ['topic_id'] )) {
				if (! empty ( $criteria ['topic_id'] )) {
					$statement->bindValue ( ':topic_id', ( int ) $criteria ['topic_id'], PDO::PARAM_INT );
				}
			}

			if (isset ( $criteria ['topic_interval_lowerlimit'] )) {
				$statement->bindValue ( ':topic_interval_lowerlimit', ( int ) $criteria ['topic_interval_lowerlimit'], PDO::PARAM_INT );
			}

			if (isset ( $criteria ['topic_interval_lowerlimit'] )) {
				$statement->bindValue ( ':topic_interval_higherlimit', ( int ) $criteria ['topic_interval_higherlimit'], PDO::PARAM_INT );
			}

			$statement->execute ();
			return $statement->fetchColumn ();
		} catch ( Exception $e ) {
			$this->reportException ( __METHOD__, $e );
			return false;
		}
	}

	/**
	 * Renvoie le nombre de ressources du catalogue selon leur année de création
	 *
	 * @return array
	 * @since 01/2007
	 */
	public function countBookmarkCreationYearly() {
		$sql = 'SELECT YEAR(b.bookmark_creation_date) AS year, COUNT(*) AS nb';
		$sql .= ' FROM ' . $this->getBookmarkTableName () . ' AS b';
		$sql .= ' LEFT JOIN ' . $this->getTopicTableName () . ' AS t ON b.topic_id=t.topic_id';
		if (empty ( $_SESSION ['user_id'] )) {
			// limitation aux ressources publiques
			$criterias = array ();
			$criterias [] = 'bookmark_private=0';
			$criterias [] = 'topic_private=0';
		}
		if (isset ( $criterias )) {
			$sql .= ' WHERE ' . implode ( ' AND ', $criterias );
		}
		$sql .= ' GROUP BY year ORDER BY year ASC';

		$statement = $this->getPdo ()->prepare ( $sql );
		$statement->execute ();
		$output = array ();
		while ( $data = $statement->fetch ( PDO::FETCH_ASSOC ) ) {
			$output [$data ['year']] = ( int ) $data ['nb'];
		}
		$statement->closeCursor ();
		return (count ( $output ) > 0) ? $output : NULL;
	}

	/*
	 * @since 23/07/2015
	 */
	public function countBookmarksByType() {
		$sql = 'SELECT b.bookmark_type AS type, COUNT(*) AS nb FROM ' . $this->getBookmarkTableName () . ' AS b';
		$sql .= ' LEFT JOIN ' . $this->getTopicTableName () . ' AS t ON b.topic_id=t.topic_id';
		if (empty ( $_SESSION ['user_id'] )) {
			// limitation aux ressources publiques
			$criteria = array ();
			$criteria [] = 'bookmark_private=0';
			$criteria [] = 'topic_private=0';
		}
		if (isset ( $criteria )) {
			$sql .= ' WHERE ' . implode ( ' AND ', $criteria );
		}
		$sql .= ' GROUP BY type ORDER BY nb DESC';
		$statement = $this->getPdo ()->prepare ( $sql );
		$statement->execute ();
		$output = array ();
		while ( $data = $statement->fetch ( PDO::FETCH_ASSOC ) ) {
			// if (empty($data ['type'])) continue;
			$output [$data ['type']] = ( int ) $data ['nb'];
		}
		$statement->closeCursor ();
		return (count ( $output ) > 0) ? $output : NULL;
	}
	/**
	 *
	 * @since 02/2022
	 * @param Bookmark $b
	 */
	public function deleteBookmark(Bookmark $b) {
		try {
			if ($b->hasId ()) {

				// suppression de la miniature
				if ($b->hasSnapshot ()) {
					unlink ( $this->getSnapshotsDirectoryPath () . DIRECTORY_SEPARATOR . $b->getSnapshotFileName () );
				}

				// suppression de la ressource
				$sql = 'DELETE FROM ' . $this->getBookmarkTableName () . ' WHERE bookmark_id=:id';
				$statement = $this->getPdo ()->prepare ( $sql );
				$statement->bindValue ( ':id', $b->getId (), PDO::PARAM_INT );
				return $statement->execute ();
			}
			throw new Exception ( 'La ressource à supprimer n\'est pas identifiée' );
		} catch ( Exception $e ) {
			$this->reportException ( __METHOD__, $e );
			return false;
		}
	}

	/**
	 *
	 * @since 10/2010
	 */
	public function setLastInvolvedTopic(Topic $topic) {
		$_SESSION ['lastInvolvedTopic'] = serialize ( $topic );
	}

	/**
	 *
	 * @return Topic
	 * @since 01/10/2010
	 */
	public function getLastInvolvedTopic() {
		return isset ( $_SESSION ['lastInvolvedTopic'] ) ? unserialize ( $_SESSION ['lastInvolvedTopic'] ) : NULL;
	}

	/**
	 * Obtient le nombre de consultations annuel moyen pour les signets les plus utilisés en se basant sur l'année précédente.
	 *
	 * @since 09/2011
	 * @version 06/2017
	 */
	public function countDaysWithHitForPastYearMostHitBookmarks() {
		try {
			$sql = 'SELECT COUNT(DISTINCT(DAYOFYEAR(h.hit_date))) AS dayWithHit_count';
			$sql .= ' FROM ' . $this->getHitTableName () . ' h';
			$sql .= ' INNER JOIN ' . $this->getBookmarkTableName () . ' b ON (b.bookmark_id = h.bookmark_id)';
			$sql .= ' LEFT OUTER JOIN ' . $this->getTopicTableName () . ' t ON (b.topic_id = t.topic_id)';
			$where = array ();
			$where [] = 'YEAR(h.hit_date)=' . (( int ) date ( 'Y' ) - 1);
			if (empty ( $_SESSION ['user_id'] )) {
				$where [] = '(bookmark_private=0 AND topic_private=0)';
			}
			$sql .= ' WHERE ' . implode ( ' AND ', $where );
			$sql .= ' GROUP BY h.bookmark_id';
			$sql .= ' ORDER BY COUNT(DISTINCT(DAYOFYEAR(h.hit_date))) DESC';
			$sql .= ' LIMIT 0,' . MOSTHITBOOKMARKS_POPULATION_SIZE;

			$statement = $this->getPdo ()->query ( $sql );
			$sum = 0;
			$n = 0;
			while ( $data = $statement->fetchColumn () ) {
				$sum += $data;
				$n ++;
			}
			return $n > 0 ? round ( $sum / $n ) : NULL;
		} catch ( Exception $e ) {
			$this->reportException ( __METHOD__, $e );
		}
	}

	/**
	 * Obtient le nombre de consultations annuelles en fonction de l'année de création des signets.
	 *
	 * @since 07/05/2012
	 * @version 09/04/2015
	 */
	public function countHitYearlyGroupByBookmarkCreationYear() {
		try {
			$sql = 'SELECT YEAR(b.bookmark_creation_date) AS creation_year, YEAR(h.hit_date) AS hit_year, COUNT(*) AS hit_count';
			$sql .= ' FROM ' . $this->getBookmarkTableName () . ' b';
			$sql .= ' INNER JOIN ' . $this->getHitTableName () . ' h ON (b.bookmark_id = h.bookmark_id)';
			$sql .= ' LEFT OUTER JOIN ' . $this->getTopicTableName () . ' t ON (b.topic_id = t.topic_id)';
			if (empty ( $_SESSION ['user_id'] )) {
				$sql .= ' WHERE (b.bookmark_private=0 AND t.topic_private=0)';
			}
			$sql .= ' GROUP BY YEAR(b.bookmark_creation_date), YEAR(h.hit_date)';
			$sql .= ' ORDER BY YEAR(h.hit_date) ASC, YEAR(b.bookmark_creation_date) ASC';

			$statement = $this->getPdo ()->query ( $sql );
			$statement->setFetchMode ( PDO::FETCH_ASSOC );
			$statement->execute ();

			$output = array ();
			foreach ( $statement->fetchAll () as $data ) {
				$output [$data ['creation_year']] [$data ['hit_year']] = $data ['hit_count'];
			}
			return $output;
		} catch ( Exception $e ) {
			$this->reportException ( __METHOD__, $e );
		}
	}

	/**
	 * Obtient les signets les plus consultés à partir d'une date donnée.
	 *
	 * @return BookmarkCollection
	 * @since 26/05/2014
	 */
	public function getMostHitBookmarkCollectionSinceDate($date = '1974-01-29', $count = null) {
		$criteria = array (
				'hit_period_start_date' => $date
		);
		$statement = $this->getBookmarkCollectionStatement ( $criteria, 'Most daily hit first', $count );
		return new BookmarkCollection ( $statement );
	}

	/**
	 * Obtient les signets les plus consultées parmi ceux créés telle année.
	 *
	 * @param string $count
	 * @return BookmarkCollection
	 * @since 25/05/2014
	 */
	public function getCreationYearMostHitBookmarkCollection($year, $count = NULL) {
		$criteria = array (
				'bookmark_creation_year' => $year
		);
		$statement = $this->getBookmarkCollectionStatement ( $criteria, 'Most daily hit first', $count );
		return new BookmarkCollection ( $statement );
	}

	/**
	 * Obtient les signets les plus utilisés au cours d'une année donnée.
	 *
	 * @return BookmarkCollection
	 * @since 05/2014
	 * @version 07/2017
	 */
	public function getYearMostHitBookmarkCollection($year, $count = 3) {
		$criteria = array (
				'hit_year' => $year
		);
		$statement = $this->getBookmarkCollectionStatement ( $criteria, 'Most daily hit first', $count );
		return new BookmarkCollection ( $statement );
	}

	/**
	 * Obtient les derniers signets utilisés.
	 *
	 * @return BookmarkCollection
	 * @since 05/2014
	 */
	public function getLastHitBookmarkCollection($count) {
		$statement = $this->getBookmarkCollectionStatement ( null, 'Last hit first', $count );
		return new BookmarkCollection ( $statement );
	}
	/**
	 *
	 * @since 09/2017
	 */
	public function getLastFocusedBookmarkCollection($count) {
		$statement = $this->getBookmarkCollectionStatement ( null, 'Last focused first', $count );
		return new BookmarkCollection ( $statement );
	}

	/**
	 * Obtient les signets considérés comme oubliés c'est à dire sans consultation ou modification dans une période récente.
	 *
	 * @return BookmarkCollection
	 * @since 05/2014
	 * @version 09/2017
	 */
	public function getForgottenBookmarkCollection($count = 7) {
		$criteria = array (
				'recentfocus' => false
		);
		$statement = $this->getBookmarkCollectionStatement ( $criteria, 'Most anciently focused first', $count );
		return new BookmarkCollection ( $statement );
	}

	/**
	 * Renvoie les signets hors rubrique.
	 *
	 * @return BookmarkCollection
	 * @version 25/05/2014
	 */
	public function getBookmarksWithoutTopic() {
		$criteria = array (
				'topic_id' => null
		);
		$statement = $this->getBookmarkCollectionStatement ( $criteria );
		return new BookmarkCollection ( $statement );
	}
	/**
	 *
	 * @since 01/2018
	 */
	public function getBookmarksWithTheSameExpectedLocation(Bookmark $b) {
		$criteria = array (
				'lastBookmarkUsedAsLocationRef_id' => $b->getId ()
		);
		$statement = $this->getBookmarkCollectionStatement ( $criteria );
		return new BookmarkCollection ( $statement );
	}

	/**
	 * Obtient l'historique des recherches de signets
	 *
	 * @return BookmarkSearchHistory
	 * @since 05/2011
	 */
	public function getBookmarkSearchHistory() {
		$output = new BookmarkSearchHistory ();
		$output->init ();
		return $output;
	}

	/**
	 * Obtient les resources de type playlist.
	 *
	 * @since 03/2006
	 * @version 05/2014
	 * @return BookmarkCollection
	 */
	public function getPlaylistCollection() {
		$formats = array (
				'pls',
				'm3u',
				'asx'
		);
		$criteria = array (
				'bookmark_url_regexp_pattern' => "^.*(' . implode('|', $formats) . ')$"
		);
		$statement = $this->getBookmarkCollectionStatement ( $criteria );
		return new BookmarkCollection ( $statement );
	}

	/**
	 * Obtient la rubrique-mère, racine de l'arborescence du catalogue.
	 *
	 * @since 02/2007
	 * @version 01/2014
	 * @return Topic
	 */
	public function getMainTopic() {
		try {
			$where = array ();
			$where [] = '(topic_interval_higherlimit-topic_interval_lowerlimit) = (SELECT MAX(topic_interval_higherlimit-topic_interval_lowerlimit) FROM ' . $this->getTopicTableName () . ')';
			if (empty ( $_SESSION ['user_id'] )) {
				$where [] = 'topic_private=0';
			}
			$sql = 'SELECT * FROM ' . $this->getTopicTableName () . ' WHERE ' . implode ( ' AND ', $where );
			$statement = $this->getPdo ()->prepare ( $sql );
			$statement->execute ();
			$data = $statement->fetch ( PDO::FETCH_ASSOC );
			if ($data) {
				$t = new Topic ();
				$t->hydrate ( $data );
				return $t;
			} else
				return NULL;
		} catch ( Exception $e ) {
			$this->reportException ( __METHOD__, $e );
			return false;
		}
	}

	/**
	 * Obtient l'identifiant de la rubrique-racine
	 *
	 * @since 05/2007
	 * @version 01/2014
	 */
	public function getMainTopicId() {
		$root = $this->getMainTopic ();
		return $root instanceof Topic ? $root->getId () : NULL;
	}

	/**
	 * Construit la requête permettant d'obtenir les données de rubriques.
	 *
	 * @param string $criteria
	 * @param string $columns
	 * @param string $sort_key
	 * @param string $sort_order
	 * @return PDOStatement
	 */
	public function getTopicCollectionStatement($criteria = NULL, $columns = NULL, $sort_key = 'topic_title', $sort_order = 'ASC') {
		try {
			if (! isset ( $column ) || ! is_array ( $columns )) {
				$columns = array ();
				$columns [] = '*';
				$columns [] = '(topic_interval_higherlimit-topic_interval_lowerlimit)+1 AS topic_interval_size';
			}
			$sql = 'SELECT ' . implode ( ', ', $columns ) . ' FROM ' . $this->getTopicTableName ();

			$where = array ();

			if (isset ( $criteria ['id'] )) {
				$where [] = 'topic_id = :id';
			}

			if (isset ( $criteria ['ancestor'] )) {
				$where [] = 'topic_interval_lowerlimit > :ancestor_interval_lowerlimit';
				$where [] = 'topic_interval_higherlimit < :ancestor_interval_higherlimit';
			}

			if (isset ( $criteria ['descendant'] )) {
				$where [] = 'topic_interval_lowerlimit < :descendant_interval_lowerlimit';
				$where [] = 'topic_interval_higherlimit > :descendant_interval_higherlimit';
			}

			if (empty ( $_SESSION ['user_id'] )) {
				$where [] = 'topic_private=0';
			}

			if (count ( $where ) > 0) {
				$sql .= ' WHERE ' . implode ( ' AND ', $where );
			}
			$sql .= ' ORDER BY ' . $sort_key . ' ' . $sort_order;
			// echo $sql;

			$statement = $this->getPdo ()->prepare ( $sql );
			$statement->setFetchMode ( PDO::FETCH_ASSOC );

			if (isset ( $criteria ['id'] )) {
				$statement->bindValue ( ':id', ( int ) $criteria ['id'], PDO::PARAM_INT );
			}

			if (isset ( $criteria ['ancestor'] )) {
				$statement->bindValue ( ':ancestor_interval_lowerlimit', ( int ) $criteria ['ancestor']->getIntervalLowerLimit (), PDO::PARAM_INT );
				$statement->bindValue ( ':ancestor_interval_higherlimit', ( int ) $criteria ['ancestor']->getIntervalHigherLimit (), PDO::PARAM_INT );
			}

			if (isset ( $criteria ['descendant'] )) {
				$statement->bindValue ( ':descendant_interval_lowerlimit', ( int ) $criteria ['descendant']->getIntervalLowerLimit (), PDO::PARAM_INT );
				$statement->bindValue ( ':descendant_interval_higherlimit', ( int ) $criteria ['descendant']->getIntervalHigherLimit (), PDO::PARAM_INT );
			}

			return $statement;
		} catch ( Exception $e ) {
			$this->reportException ( __METHOD__, $e );
		}
	}

	/**
	 * Otient la collection des rubriques avec le plus fort taux de consultation des leurs signets.
	 *
	 * @return TopicCollection
	 * @since 01/2013
	 * @version 06/2014
	 */
	public function getMostHitTopics($count = 7) {
		try {
			$where = array ();
			if (empty ( $_SESSION ['user_id'] )) {
				$where [] = '(b.bookmark_private=0 AND t.topic_private=0)';
			}
			$where [] = 'DATEDIFF(DATE(h.hit_date), :hit_period_start_date)>=0';
			$sql = 'SELECT t.*';
			$sql .= ' ,COUNT(DISTINCT(DATE(h.hit_date))) AS topic_daysWithHitCount';
			$sql .= ' FROM ' . $this->getTopicTableName () . ' AS t';
			$sql .= ' INNER JOIN ' . $this->getBookmarkTableName () . ' AS b USING (topic_id)';
			$sql .= ' LEFT OUTER JOIN ' . $this->getHitTableName () . ' AS h USING (bookmark_id)';
			$sql .= ' WHERE ' . implode ( ' AND ', $where );
			$sql .= ' GROUP BY t.topic_id';
			$sql .= ' ORDER BY COUNT(DISTINCT(DATE(h.hit_date))) DESC';
			$sql .= ' LIMIT 0, :count';

			$statement = $this->getPdo ()->prepare ( $sql );
			$statement->setFetchMode ( PDO::FETCH_ASSOC );

			$statement->bindValue ( ':hit_period_start_date', date ( "Y-m-d", strtotime ( '-' . ACTIVITY_THRESHOLD1 . ' day' ) ), PDO::PARAM_STR );
			$statement->bindValue ( ':count', ( int ) $count, PDO::PARAM_INT );

			return new TopicCollection ( $statement );
		} catch ( Exception $e ) {
			$this->reportException ( __METHOD__, $e );
			return false;
		}
	}

	/**
	 * Obtient une rubrique identifiée.
	 *
	 * @since 11/2009
	 * @version 06/2014
	 * @return Topic
	 */
	public function getTopicById($id) {
		try {
			$criteria = array (
					'id' => $id
			);
			$statement = $this->getTopicCollectionStatement ( $criteria );
			$statement->execute ();
			$data = $statement->fetch ();
			if (is_array ( $data )) {
				$t = new Topic ();
				$t->hydrate ( $data );
				return $t;
			}
		} catch ( Exception $e ) {
			$this->reportException ( __METHOD__, $e );
		}
	}
	public function getTopicUrl(Topic $topic) {
		return $this->getProjectUrl () . '/topic.php?topic_id=' . $topic->getId ();
	}
	public function getBookmarkUrl(Bookmark $bookmark) {
		return $this->getProjectUrl () . '/bookmark_info.php?bookmark_id=' . $bookmark->getId ();
	}

	/**
	 * Renvoie les rubriques principales (avec rubrique-mère comme unique ancêtre)
	 *
	 * @return array Tableau d'objets Topic
	 * @version 11/2007
	 */
	public function getMainTopics() {
		try {
			$mainTopics = array ();

			$sql = 'SELECT t1.*, COUNT(t2.topic_id) AS ancestors_nb';
			$sql .= ' FROM ' . $this->getTopicTableName () . ' AS t1';
			$sql .= ' LEFT OUTER JOIN ' . $this->getTopicTableName () . ' AS t2';
			$sql .= ' ON (t1.topic_interval_lowerlimit > t2.topic_interval_lowerlimit AND t1.topic_interval_higherlimit < t2.topic_interval_higherlimit )';
			$criterias = array ();
			if (empty ( $_SESSION ['user_id'] )) {
				$criterias [] = 't1.topic_private=0';
			}
			if (count ( $criterias ) > 0) {
				$sql .= ' WHERE ' . implode ( ' AND ', $criterias );
			}
			$sql .= ' GROUP BY t1.topic_id';
			$sql .= ' HAVING ancestors_nb=1';

			$statement = $this->getPdo ()->query ( $sql );
			$statement->setFetchMode ( PDO::FETCH_ASSOC );
			$statement->execute ();

			foreach ( $statement->fetchAll () as $data ) {
				$t = new Topic ();
				$t->hydrate ( $data );
				$mainTopics [] = $t;
			}
			return $mainTopics;
		} catch ( Exception $e ) {
			$this->reportException ( __METHOD__, $e );
		}
	}

	/**
	 * Obtient une liste de rubriques sous forme de tags HTML <option>
	 *
	 * @since 05/2007
	 */
	public function getOptionTagsFromArray($topics, $selection_ids = NULL, $exclusion_ids = NULL) {
		if (isset ( $selection_ids ) && ! is_array ( $selection_ids )) {
			$selection_ids = array (
					$selection_ids
			);
		}
		if (isset ( $exclusion_ids ) && ! is_array ( $exclusion_ids )) {
			$exclusion_ids = array (
					$exclusion_ids
			);
		}
		$html = '';
		foreach ( $topics as $t ) {
			if (isset ( $exclusion_ids ) && in_array ( $t->getId (), $exclusion_ids )) {
				continue;
			}
			$string .= ' ' . ToolBox::toHtml ( $t->getTitle () );
			$html .= '<option value="' . ToolBox::toHtml ( $t->getId () ) . '"';
			if (in_array ( $t->getId (), $selection_ids )) {
				$html .= ' selected="selected"';
			}
			$html .= '>';
			$html .= $string;
			$html .= '</option>';
		}
		return $html;
	}

	/**
	 * Importation d'un fichier de type NETSCAPE-Bookmark-file-1
	 *
	 * @version 05/2008
	 */
	public function importNetscapeBookmarkFile($uploadedfile, $targettopic = NULL) {
		$messages = array ();
		// ouverture du fichier source
		// doit être encodé en UTF-8
		try {
			if (is_file ( $uploadedfile ['tmp_name'] )) {
				$fp = @fopen ( $uploadedfile ['tmp_name'], "r-" );
				if (@$fp) {
					$firstLine = fgets ( $fp );
					// est-ce un document de type NETSCAPE-Bookmark-file-1 ?
					if (! preg_match ( '/<!DOCTYPE NETSCAPE-Bookmark-file-1>/i', $firstLine )) {
						array_push ( $messages, 'Le fichier source doit être un document de type NETSCAPE-Bookmark-file-1 !' );
						return $messages;
					}
					$addedtopics_nb = 0;
					$addedbookmarks_nb = 0;

					// pour enregistrer la position actuelle au sein de l'arborescence
					// soit la liste ordonnée des Topics successivement ouverts
					$parents_ids = array ();

					if ($targettopic instanceof Topic) {
						// une rubrique cible est sélectionnée
						array_push ( $parents_ids, $targettopic->getId () );
					} else {
						// par défaut importation dans la rubrique principale
						array_push ( $parents_ids, $this->getMainTopic ()->getId () );
					}

					// lecture lignes suivantes
					while ( ! @feof ( $fp ) ) {
						$parent = new Topic ( end ( $parents_ids ) );
						$line = fgets ( $fp );
						if (preg_match ( '/<dt><h3[^>]*>(.*)<\/h3>/i', $line, $match )) {
							// une rubrique est trouvée
							$t = new Topic ();
							if (isset ( $match [1] )) {
								$t->setTitle ( $match [1] );
							}
							// extraction add_date
							preg_match ( "(/add_date=\"([^\"]*/i))", $line, $match );
							if (isset ( $match [1] )) {
								$t->setCreationDate ( $match [1] );
							}
							// traitement d'un éventuel commentaire (issu d'ikeepbookmarks.com)
							preg_match ( '/<i>(.*)<\/i>/i', $line, $match );
							if (isset ( $match [1] )) {
								$t->setDescription ( $match [1] );
							}
							if ($t->addTo ( $parent )) {
								$descriptibleObject = $t;
								$addedtopics_nb ++;
							}
						} elseif (preg_match ( '/<dl>/i', $line )) {
							// le dernier topic créé est considéré comme la rubrique parente
							// des prochaines Ressources et Rubriques extraites du fichier
							array_push ( $parents_ids, $t->getId () );
						} elseif (preg_match ( '/<dt><a href="([^"]*)[^>]*>(.*)<\/a>/i', $line, $match )) {
							// une ressource est trouvée
							$b = new Bookmark ();
							if (isset ( $match [1] )) {
								$b->setUrl ( urldecode ( $match [1] ) );
							}
							if (isset ( $match [2] )) {
								$b->setTitle ( $match [2] );
							}
							// extraction des dates
							preg_match ( "/add_date=\"([^\"]*)/i", $line, $match );
							if (isset ( $match [1] )) {
								$b->setCreationDateFromUnixTime ( $match [1] );
							}
							preg_match ( "/last_modified=\"([^\"]*)/i", $line, $match );
							if (isset ( $match [1] )) {
								$b->setLastEditDateFromUnixTime ( $match [1] );
							}
							// preg_match("/last_visit=\"([^\"]*)/i", $line, $match);
							// if (isset($match[1])) {
							// $b->setAttribute('last_hit_date', $match[1]);
							// }

							// traitement d'un éventuel commentaire (issu d'ikeepbookmarks.com)
							preg_match ( '/<i>(.*)<\/i>/i', $line, $match );
							if (isset ( $match [1] )) {
								$b->setDescription ( $match [1] );
							}
							$b->setTopic ( $parent );
							// $b->toHtml();
							if ($b->toDB ()) {
								$descriptibleObject = $b;
								$addedbookmarks_nb ++;
							}
						} elseif (preg_match ( '/<dd>(.*)/i', $line, $match )) {
							if (isset ( $match [1] )) {
								$descriptibleObject->setDescription ( $match [1] );
								$descriptibleObject->toDB ();
							}
						} elseif (preg_match ( '/<\/dl>/i', $line )) {
							// on remonte d'un niveau dans l'arborescence
							array_pop ( $parents_ids );
						}
					}
					fclose ( $fp );
					return true;
				}
			}
			throw new Exception ( 'Le fichier source indiqué(' . $uploadedfile ['name'] . ') n\'est pas un fichier !' );
		} catch ( Exception $e ) {
			$this->reportException ( __METHOD__, $e );
			return false;
		}
	}

	/**
	 * Obtient le nom de la table où sont enregistrés les utilisateurs
	 *
	 * @return String
	 * @since 05/2007
	 */
	public function getUserTableName() {
		return defined ( 'DB_TABLE_PREFIX' ) ? DB_TABLE_PREFIX . 'user' : 'user';
	}

	/**
	 * Obtient le nom de la table où sont enregistrés les ressources
	 *
	 * @return String
	 * @since 05/2007
	 */
	public function getBookmarkTableName() {
		return defined ( 'DB_TABLE_PREFIX' ) ? DB_TABLE_PREFIX . 'bookmark' : 'bookmark';
	}

	/**
	 * Obtient le nom de la table où sont enregistrés les rubriques
	 *
	 * @return String
	 * @since 05/2007
	 */
	public function getTopicTableName() {
		return defined ( 'DB_TABLE_PREFIX' ) ? DB_TABLE_PREFIX . 'topic' : 'topic';
	}

	/**
	 * Obtient le nom de la table où sont enregistrées les consultations
	 *
	 * @return String
	 * @since 05/2007
	 */
	public function getHitTableName() {
		return defined ( 'DB_TABLE_PREFIX' ) ? DB_TABLE_PREFIX . 'hit' : 'hit';
	}

	/**
	 * Obtient le nom de la table où sont enregistrés les raccourcis entre rubriques
	 *
	 * @return string
	 * @since 02/2010
	 */
	public function getShortCutTableName() {
		return defined ( 'DB_TABLE_PREFIX' ) ? DB_TABLE_PREFIX . 'shortcut' : 'shortcut';
	}

	/**
	 * Obtient la première lacune dans le continuum des intervalles associés aux rubriques
	 *
	 * @since 09/2014
	 * @version 01/2015
	 * @return Interval | null
	 */
	private function getNextTopicIntervalGap($position = 1) {
		try {
			$sql = '(SELECT topic_interval_lowerlimit AS interval_limit FROM ' . $this->getTopicTableName () . ' WHERE topic_interval_lowerlimit>=:position) UNION (SELECT topic_interval_higherlimit FROM ' . $this->getTopicTableName () . ' WHERE topic_interval_lowerlimit>=:position) ORDER BY interval_limit ASC';
			$statement = $this->getPdo ()->prepare ( $sql );
			$statement->bindValue ( ':position', ( int ) $position, PDO::PARAM_INT );
			$statement->execute ();
			$last = null;
			foreach ( $statement->fetchAll () as $row ) {
				if (isset ( $last )) {
					if ($row ['interval_limit'] - $last > 1) {
						return new Interval ( $last, $row ['interval_limit'] );
					}
				}
				$last = $row ['interval_limit'];
			}
		} catch ( Exception $e ) {
			$this->reportException ( __METHOD__, $e );
			return false;
		}
	}

	/**
	 * Supprime de manière récursive les lacunes constatées dans le continuum des intervalles associés aux rubriques.
	 *
	 * @return boolean
	 * @since 09/2014
	 * @version 10/2014
	 */
	public function trimTopicInterval() {
		try {
			$i = $this->getNextTopicIntervalGap ();
			while ( $i instanceof Interval ) {
				$shift = $i->getSize () - 1;
				$position = $i->getLowerLimit ();
				if (! $this->pullTopicLimitsBeyondPosition ( $position, $shift )) {
					throw new Exception ( 'L\'intervalle ' . $i->toText () . ' n\'a pu être réduit' );
				}
				$i = $this->getNextTopicIntervalGap ();
			}
			return true;
		} catch ( Exception $e ) {
			$this->reportException ( __METHOD__, $e );
			return false;
		}
	}

	/**
	 * Obtient la liste des identifiants des rubriques dont l'intervalle s'ouvre au-delà de la position passée en paramètre.
	 *
	 * @param int $position
	 * @return multitype:array boolean
	 * @since 11/2014
	 */
	private function getTopicStartingBeyondPositionIds($position) {
		try {
			$statement = $this->getPdo ()->prepare ( 'SELECT topic_id FROM ' . $this->getTopicTableName () . ' WHERE topic_interval_lowerlimit>:position' );
			$statement->bindValue ( ':position', ( int ) $position, PDO::PARAM_INT );
			$statement->execute ();
			return $statement->fetchAll ( PDO::FETCH_COLUMN );
		} catch ( Exception $e ) {
			$this->reportException ( __METHOD__, $e );
			return false;
		}
	}

	/**
	 * Obtient la liste des identifiants des rubriques dont l'intervalle se ferme au-delà de la position passée en paramètre.
	 *
	 * @param int $position
	 * @return multitype:array boolean
	 * @since 01/11/2014
	 */
	private function getTopicClosingBeyondPositionIds($position) {
		try {
			$statement = $this->getPdo ()->prepare ( 'SELECT topic_id FROM ' . $this->getTopicTableName () . ' WHERE topic_interval_higherlimit>:position' );
			$statement->bindValue ( ':position', ( int ) $position, PDO::PARAM_INT );
			$statement->execute ();
			return $statement->fetchAll ( PDO::FETCH_COLUMN );
		} catch ( Exception $e ) {
			$this->reportException ( __METHOD__, $e );
			return false;
		}
	}

	/**
	 *
	 * @since 04/10/2014
	 * @version 12/01/2015
	 */
	public function pullTopicLimitsBeyondPosition($position, $shift) {
		try {
			$topicStartingBeyond = $this->getTopicStartingBeyondPositionIds ( $position );
			$topicClosingBeyond = $this->getTopicClosingBeyondPositionIds ( $position );
			$toProcess = array_unique ( array_merge ( $topicStartingBeyond, $topicClosingBeyond ) );
			asort ( $toProcess );

			$statement = array ();
			$statement ['all'] = $this->getPdo ()->prepare ( 'UPDATE ' . $this->getTopicTableName () . ' SET topic_interval_lowerlimit=topic_interval_lowerlimit-:shift, topic_interval_higherlimit=topic_interval_higherlimit-:shift WHERE topic_id=:id' );
			$statement ['higherOnly'] = $this->getPdo ()->prepare ( 'UPDATE ' . $this->getTopicTableName () . ' SET topic_interval_higherlimit=topic_interval_higherlimit-:shift WHERE topic_id=:id' );

			$this->getPdo ()->beginTransaction ();
			foreach ( $toProcess as $id ) {
				$mode = in_array ( $id, $topicStartingBeyond ) ? 'all' : 'higherOnly';
				$statement [$mode]->bindValue ( ':shift', ( int ) $shift, PDO::PARAM_INT );
				$statement [$mode]->bindValue ( ':id', ( int ) $id, PDO::PARAM_INT );
				$statement [$mode]->execute ();
			}
			return $this->getPdo ()->commit ();
		} catch ( Exception $e ) {
			$this->reportException ( __METHOD__, $e );
			if ($this->getPdo ()->inTransaction ()) {
				$this->getPdo ()->rollBack ();
			}
			return false;
		}
	}

	/**
	 *
	 * @since 10/2014
	 */
	public function pushTopicLimitsBeyondPosition($position, $shift) {
		try {
			$topicStartingBeyond = $this->getTopicStartingBeyondPositionIds ( $position );
			$topicClosingBeyond = $this->getTopicClosingBeyondPositionIds ( $position );
			$toProcess = array_unique ( array_merge ( $topicStartingBeyond, $topicClosingBeyond ) );
			asort ( $toProcess );

			$statement = array ();
			$statement ['all'] = $this->getPdo ()->prepare ( 'UPDATE ' . $this->getTopicTableName () . ' SET topic_interval_lowerlimit=topic_interval_lowerlimit+:shift, topic_interval_higherlimit=topic_interval_higherlimit+:shift WHERE topic_id=:id' );
			$statement ['higherOnly'] = $this->getPdo ()->prepare ( 'UPDATE ' . $this->getTopicTableName () . ' SET topic_interval_higherlimit=topic_interval_higherlimit+:shift WHERE topic_id=:id' );

			$this->getPdo ()->beginTransaction ();
			foreach ( $toProcess as $id ) {
				$mode = in_array ( $id, $topicStartingBeyond ) ? 'all' : 'higherOnly';
				$statement [$mode]->bindValue ( ':shift', ( int ) $shift, PDO::PARAM_INT );
				$statement [$mode]->bindValue ( ':id', ( int ) $id, PDO::PARAM_INT );
				$statement [$mode]->execute ();
			}
			return $this->getPdo ()->commit ();
		} catch ( Exception $e ) {
			$this->reportException ( __METHOD__, $e );
			if ($this->getPdo ()->inTransaction ()) {
				$this->getPdo ()->rollBack ();
			}
			return false;
		}
	}
}
?>