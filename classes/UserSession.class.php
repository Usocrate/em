<?php
/**
 * Session utilisateur dépassant le cadre d'une session navigateur.
 * L'identifiant de la session sera stockée dans un cookie.
 *
 * @author Florent
 * @since 21/08/2009
 *
 */
class UserSession {
	private $id;
	private $user_id;
	private $expiration_date;
	public function __construct($id = NULL) {
		$this->id = $id;
	}
	public function getId() {
		return isset ( $this->id ) ? $this->id : NULL;
	}
	/**
	 * Renvoi le nom de la table (de la base de données) à laquelle est liées cette classe
	 *
	 * @since 21/08/2009
	 */
	public static function getTableName() {
		return defined ( 'DB_TABLE_PREFIX' ) ? DB_TABLE_PREFIX . 'user_session' : 'user_session';
	}
	/**
	 * Supprime les sessions expirée pour un utilisateur donné.
	 *
	 * @param
	 *        	L'identifiant de l'utilisateur $user_id
	 * @return boolean
	 * @since 21/08/2009
	 * @version 29/08/2014
	 */
	public static function deleteUserExpiredSessions($user_id) {
		global $system;
		try {
			$sql = 'DELETE FROM ' . self::getTableName () . ' WHERE user_id=:user_id AND NOW() >= expiration_date';
			$statement = $system->getPdo ()->prepare ( $sql );
			$statement->bindValue ( ':user_id', $user_id );
			return $statement->execute ();
		} catch ( Exception $e ) {
			$system->reportException(__METHOD__, $e );
			return false;
		}
	}
	/**
	 * Obtient un nouvelle session.
	 *
	 * @param	L'identifiant de l'utilisateur pour lequel la session sera créée $user_id
	 * @return UserSession
	 * @version 29/08/2014
	 */
	public static function getNewUserSession($user_id) {
		global $system;
		try {
			$sql = 'INSERT INTO ' . self::getTableName () . ' SET user_id=:user_id, expiration_date=DATE_ADD(NOW(),INTERVAL ' . USER_SESSION_LIFETIME . ' SECOND)';
			$statement = $system->getPdo ()->prepare ( $sql );
			$statement->bindValue ( ':user_id', $user_id );
			$statement->execute ();
			$session_id = $system->getPdo ()->lastInsertId ();
			return new UserSession ( $session_id );
		} catch ( Exception $e ) {
			$system->reportException(__METHOD__, $e );
			return false;
		}
	}
	/**
	 * Indique si la session a expirée.
	 *
	 * @return boolean
	 * @since 21/08/2009
	 */
	public function hasExpired() {
		return strtotime ( $this->getExpirationDate () ) < time ();
	}
	/**
	 * Indique si la session est une session valide pour l'utilisateur dontl'identifiant est passé en paramètre.
	 *
	 * @return boolean
	 * @since 21/08/2009
	 */
	public function isValid($user_id) {
		return $this->isTheRightUser ( $user_id ) && ! $this->hasExpired ();
	}
	/**
	 * Indique si l'identifiant utilisateur passé en paramètre correspond à l'utilisateur de la session.
	 *
	 * @return boolean
	 */
	public function isTheRightUser($user_id) {
		return strcmp ( $this->getUserId (), $user_id ) == 0;
	}
	/**
	 * Obtient l'identifiant de l'utilisateur associé à la session.
	 *
	 * @return int
	 * @version 09/05/2014
	 */
	public function getUserId() {
		global $system;
		try {
			if (! isset ( $this->user_id )) {
				$sql = 'SELECT user_id FROM ' . self::getTableName () . ' WHERE id=' . $this->id;
				$statement = $system->getPdo ()->query ( $sql );
				$this->user_id = $statement->fetchColumn ();
			}
			return $this->user_id;
		} catch ( Exception $e ) {
			$system->reportException(__METHOD__, $e );
		}
	}
	/**
	 * Obtient la date d'expiration de la session.
	 *
	 * @return string
	 * @since 21/08/2009
	 * @version 09/05/2014
	 */
	public function getExpirationDate() {
		global $system;
		try {
			if (! isset ( $this->expiration_date )) {
				$sql = 'SELECT expiration_date FROM ' . self::getTableName () . ' WHERE id=' . $this->id;
				$statement = $system->getPdo ()->query ( $sql );
				$this->expiration_date = $statement->fetchColumn ();
			}
			return $this->expiration_date;
		} catch ( Exception $e ) {
			$system->reportException(__METHOD__, $e );
		}
	}
}