<?php
/**
 * @package usocrate.bookmarks
 * @author Florent Chanavat
 * @since 04/2007
 */
class Interval {
	private $lowerlimit;
	private $higherlimit;
	public function __construct($lowerlimit = NULL, $higherlimit = NULL) {
		if (isset ( $lowerlimit ))
			$this->setLowerLimit ( $lowerlimit );
		if (isset ( $higherlimit ))
			$this->setHigherLimit ( $higherlimit );
	}
	/**
	 * déplace l'intervalle
	 *
	 * @since 04/2007
	 * @version 10/2014
	 */
	public function shift($shift) {
		global $system;
		try {
			if ($this->areLimitsSet ()) {
				$statement = 'SELECT topic_id FROM ' . $system->getTopicTableName () . ' WHERE topic_interval_lowerlimit>=:lower AND topic_interval_higherlimit<=:higher';
				$statement = $system->getPdo ()->prepare ( $statement );
				$statement->bindValue ( ':lower', ( int ) $this->getLowerLimit (), PDO::PARAM_INT );
				$statement->bindValue ( ':higher', ( int ) $this->getHigherLimit (), PDO::PARAM_INT );
				$statement->execute ();
				$topicIds = $statement->fetchAll ( PDO::FETCH_COLUMN );

				$statement = 'UPDATE ' . $system->getTopicTableName () . ' SET topic_interval_lowerlimit=topic_interval_lowerlimit+(:shift),topic_interval_higherlimit=topic_interval_higherlimit+(:shift) WHERE topic_id=:id';
				$statement = $system->getPdo ()->prepare ( $statement );
				$statement->bindValue ( ':shift', ( int ) $shift, PDO::PARAM_INT );

				$system->getPdo ()->beginTransaction ();
				foreach ( $topicIds as $id ) {
					$statement->bindValue ( ':id', ( int ) $id, PDO::PARAM_INT );
					$statement->execute ();
				}
				$system->getPdo ()->commit ();
				$this->setLowerLimit ( $this->getLowerLimit () + $shift );
				$this->setHigherLimit ( $this->getHigherLimit () + $shift );
				return true;
			}
			throw new Exception ( 'les 2 bornes de l\'intervalle doivent être connues avant de le décaler' );
		} catch ( Exception $e ) {
			$system->reportException ( __METHOD__, $e );
			if ($system->getPdo ()->inTransaction ()) {
				$system->getPdo ()->rollBack ();
			}
			return false;
		}
	}
	/**
	 *
	 * @since 04/2007
	 */
	public function getLowerLimit() {
		return isset ( $this->lowerlimit ) ? $this->lowerlimit : NULL;
	}
	/**
	 * Indique si les bornes de l'intervalle sont fixées
	 *
	 * @return bool
	 * @since 05/2007
	 * @version 05/2015
	 */
	public function areLimitsSet() {
		return isset ( $this->lowerlimit ) && isset ( $this->higherlimit );
	}
	/**
	 * Fixe la borne inférieure de l'intervalle
	 *
	 * @param int $input
	 * @return bool
	 * @since 04/2007
	 * @version 10/2014
	 */
	public function setLowerLimit($input) {
		global $system;
		try {
			if (is_numeric ( $input )) {
				$this->lowerlimit = ( int ) $input;
				return true;
			}
			throw new Exception ( 'la valeur fournie (' . $input . ') n\'est pas un nombre' );
		} catch ( Exception $e ) {
			$system->reportException ( __METHOD__, $e );
			return false;
		}
	}
	/**
	 *
	 * @since 15/04/2007
	 */
	public function getHigherLimit() {
		return isset ( $this->higherlimit ) ? $this->higherlimit : NULL;
	}
	/**
	 * Fixe la borne supérieure de l'intervalle
	 *
	 * @since 15/04/2007
	 * @version 04/10/2014
	 */
	public function setHigherLimit($input) {
		global $system;
		try {
			if (is_numeric ( $input )) {
				$this->higherlimit = ( int ) $input;
				return true;
			}
			throw new Exception ( 'la valeur fournie (' . $input . ') n\'est pas un nombre' );
		} catch ( Exception $e ) {
			$system->reportException ( __METHOD__, $e );
			return false;
		}
	}
	/**
	 * Obtient l'amplitude de l'intervalle (en nombre d'unités).
	 *
	 * @since 04/2007
	 * @version 10/2014
	 * @return int
	 */
	public function getSize() {
		return $this->areLimitsSet () ? ($this->getHigherLimit () - $this->getLowerLimit ()) : NULL;
	}
	/**
	 * Indique si l'intervalle considéré est inclus dans l'intervalle passé en paramètre
	 *
	 * @param Interval $interval
	 * @return bool
	 * @since 05/2007
	 */
	public function isInside($interval) {
		return $this->getLowerLimit () >= $interval->getLowerLimit () && $this->getHigherLimit () <= $interval->getHigherLimit ();
	}
	/**
	 * Obtient le Html permettant d'afficher l'intervalle
	 *
	 * @since 04/2007
	 * @return String
	 */
	public function toHtml() {
		return '<small>[' . $this->getLowerLimit () . '-' . $this->getHigherLimit () . ']</small>';
	}
	/**
	 *
	 * @since 10/2014
	 * @return string
	 */
	public function toText() {
		return '[' . $this->getLowerLimit () . '-' . $this->getHigherLimit () . ']';
	}
	/**
	 * Fixe les valeurs de l'intervalle à partir d'un tableau de valeurs
	 *
	 * @since 05/2007
	 */
	public function hydrate($array = NULL, $prefix = 'interval_') {
		// echo '<p>Interval::hydrate()</p>';
		foreach ( $array as $clé => $valeur ) {
			if (is_null ( $valeur ))
				continue;
			if (isset ( $prefix )) {
				// on ne traite que les clés avec le préfixe spécifié
				if (strcmp ( iconv_substr ( $clé, 0, iconv_strlen ( $prefix ) ), $prefix ) != 0) {
					continue;
				}
				// on retire le préfixe
				$clé = iconv_substr ( $clé, iconv_strlen ( $prefix ) );
			}
			// echo $clé.': '.$valeur.'<br />';
			switch ($clé) {
				case 'lowerlimit' :
					$this->setLowerLimit ( $valeur );
					break;
				case 'higherlimit' :
					$this->setHigherLimit ( $valeur );
					break;
			}
		}
	}
}
?>