<?php
/**
 * Classe permettant de gérer un ensemble d'objets de même type
 *
 * @since 12/2007
 */
class Collection implements Countable, IteratorAggregate {

	/**
	 * un intitulé pour la collection
	 *
	 * @var string
	 */
	protected $name;

	/**
	 * Le type d'objet collectionné.
	 *
	 * @var string
	 */
	protected $element_type;

	/**
	 * Un tableau stockant les objets collectionnés
	 *
	 * @var array
	 */
	protected $elements;

	/**
	 * constructeur
	 *
	 * @param string $element_type
	 * @since 12/2007
	 */
	public function __construct($element_type, $name = NULL) {
		$this->setName ( $name );
		$this->elements = array ();
		$this->element_type = $element_type;
	}

	/**
	 *
	 * {@inheritdoc}
	 * @see Countable::count()
	 * @since 07/2021
	 */
	public function count() {
		return count ( $this->elements );
	}

	/**
	 * Définition requise de l'interface IteratorAggregate
	 *
	 * @return CollectionIterator
	 */
	public function getIterator() {
		return new CollectionIterator ( $this->elements );
	}

	/**
	 * Alias de la fonction self::addElement
	 *
	 * @return boolean
	 * @since 12/2007
	 * @version 03/2007
	 */
	public function add($element) {
		return $this->addElement ( $element );
	}

	/**
	 * Ajoute un élément à la collection.
	 *
	 * @return boolean
	 * @since 12/2007
	 * @version 01/2018
	 */
	public function addElement($element) {
		global $system;
		try {
			if (is_null ( $element )) {
				return true;
			}
			if ($element instanceof $this->element_type) {
				if (! $this->hasElement ( $element )) {
					$this->elements [$element->getId ()] = $element;
					return true;
				}
				return false;
			} else {
				throw new Exception ( 'L\'élément que vous tentez d\'ajouter à la collection "' . get_class ( $this ) . '" n\'est pas du bon type : ' . get_class ( $element ) );
			}
		} catch ( Exception $e ) {
			$system->reportException ( __METHOD__, $e );
			return false;
		}
	}

	/**
	 * supprime le dernier élément de la collection.
	 *
	 * @since 11/2008
	 */
	public function removeLastElement() {
		return array_pop ( $this->elements );
	}

	/**
	 * Obtient la taille de la collection
	 *
	 * @return int
	 */
	public function getSize() {
		return count($this);
	}

	/**
	 * Obtient l'intitulé de la collection
	 *
	 * @return string
	 * @since 05/2008
	 */
	public function getName() {
		return isset ( $this->name ) ? $this->name : NULL;
	}

	/**
	 * Fixe l'intitulé de la collection
	 *
	 * @return string
	 * @since 05/2008
	 */
	public function setName($name) {
		if (! empty ( $name )) {
			$this->name = $name;
		}
	}

	/**
	 * Obtient une représentation de la collection au format json
	 *
	 * @return string
	 * @since 02/2008
	 */
	public function toJson() {
		$i = $this->getIterator ();
		$i->rewind ();
		$jsonPieces = array ();
		while ( $i->current () ) {
			$jsonPieces [] = $i->current ()->toJson ();
			$i->next ();
		}
		return '{"' . __CLASS__ . '":[' . implode ( ',', $jsonPieces ) . ']}';
	}

	/**
	 * Obtient la collection sous forme d'énumération, au format Html
	 *
	 * @param string $url
	 * @param string $id_param_name
	 * @return string
	 * @since 04/2008
	 */
	public function toHtmlEnumeration($url = NULL, $id_param_name = NULL) {
		return $this->toHtml ( 'enumeration', $url, $id_param_name );
	}

	/**
	 * Obtient la collection au format html
	 *
	 * @return string
	 * @since 01/2007
	 * @todo système d'ajout de lien à paufiner
	 */
	public function toHtml($type = 'debug', $url = NULL, $id_param_name = NULL) {
		if (count($this) > 0) {
			$i = $this->getIterator ();
			$i->rewind ();
			$pieces = array ();
			while ( $i->current () ) {
				$html = '';
				if (! empty ( $url )) {
					if (isset ( $id_param_name )) {
						$href = $url . '?' . $id_param_name . '=' . $i->current ()->getId ();
					}
					$html = '<a href="' . htmlentities ( $href ) . '">';
				}
				$html .= htmlentities ( $i->current ()->getName () );
				if (! empty ( $url )) {
					$html .= '</a>';
				}
				// $html.= ' <small>('.$i->current()->getId().')</small>';
				$pieces [] = $html;
				$i->next ();
			}
			switch ($type) {
				case 'enumeration' :
					if (count ( $pieces ) > 1) {
						$last = array_pop ( $pieces );
						$html = implode ( ', ', $pieces );
						$html .= ' et ' . $last . '.';
					} else {
						$html = $pieces [0];
					}
					break;
				case 'unordered_list' :
					$html = '<ul>';
					foreach ( $pieces as $p ) {
						$html .= '<li>' . $p . '</li>';
					}
					$html .= '</ul>';
					break;
				case 'debug' :
					$html = '<div>';
					$html .= '<h3>' . htmlentities ( $this->getName () ) . '</h3>';
					$html .= '<p>' . $this->toHtml ( 'enumeration' ) . '</p>';
					$html .= '</div>';
					break;
			}
			return $html;
		}
	}

	/**
	 * Revoie les éléments de la collection sous forme de balises Html <option>.
	 *
	 * @param string $value_to_select
	 * @since 01/2008
	 */
	public function toHtmlOptionTags($value_to_select = NULL) {
		if ($this->getSize () > 0) {
			$i = $this->getIterator ();
			$i->rewind ();
			while ( $i->current () ) {
				$html .= '<option value="' . $i->current ()->getId () . '"';
				if (strcmp ( $i->current ()->getId (), $value_to_select ) == 0) {
					$html .= ' selected="selected"';
				}
				$html .= '>';
				$html .= htmlentities ( $i->current ()->getName () );
				$html .= '</option>';
				$i->next ();
			}
			return $html;
		}
	}

	/**
	 * Retourne le premier élément de la collection.
	 *
	 * @since 01/2008
	 * @version 05/2008
	 */
	public function getFirstElement() {
		$ids = $this->getIds ();
		if (count ( $ids ) > 0) {
			return $this->getElementById ( $ids [0] );
		} else {
			return NULL;
		}
	}

	/**
	 * Retourne le dernier élément de la collection.
	 *
	 * @version 06/2014
	 */
	public function getLastElement() {
		global $system;
		try {
			return end ( $this->elements );
		} catch ( Exception $e ) {
			$system->reportException ( __METHOD__, $e );
		}
	}

	/**
	 * Obtient la liste des identifiants des objets de la collection
	 *
	 * @return array
	 * @since 01/2008
	 * @version 03/2008
	 */
	public function getIds() {
		return array_keys ( $this->elements );
	}

	/**
	 * Obtient les identifiants des descendants directs (sous-chapitres) des chapitres de la collection.
	 *
	 * @return array
	 * @since 07/2008
	 */
	public function getChildIds() {
		$output = array ();
		$i = $this->getIterator ();
		$i->rewind ();
		while ( $i->current () ) {
			$output = array_merge ( $output, $i->current ()->getChildIds () );
			$i->next ();
		}
		return $output;
	}

	/**
	 * Obtient les noms des éléments de la collection
	 *
	 * @return array
	 * @since 01/2008
	 */
	public function getNames() {
		$names = array ();
		if (count($this) > 0) {
			$i = $this->getIterator ();
			$i->rewind ();
			while ( $i->current () ) {
				$names [] = $i->current ()->getName ();
				$i->next ();
			}
		}
		return $names;
	}

	/**
	 * Si un élément est passé en paramètre, indique si celui-ci est déjà dans la collection, sinon indique si la collection possède des éléments.
	 *
	 * @param object $element
	 *        	| NULL
	 * @return boolean
	 * @since 01/2008
	 * @version 03/2008
	 */
	public function hasElement($element = NULL) {
		try {
			/**
			 * si pas d'élément passé en paramètre on indique simplement si la collection possède des éléments
			 */
			if (is_null ( $element )) {
				return count($this) > 0;
			}
			/**
			 * sinon on indique si l'élément fait partie de la collection
			 */
			if ($element instanceof $this->element_type) {
				return in_array ( $element->getId (), $this->getIds () );
			} else {
				return false;
			}
		} catch ( Exception $e ) {
			echo '<p>' . __METHOD__ . ' : ' . htmlentities ( $e->getMessage () ) . '</p>';
			exit ();
		}
	}

	/**
	 * Renvoie un des éléments de la collection, retrouvé par son identifiant.
	 *
	 * @param string $id
	 * @return object
	 * @since 01/2008
	 * @version 02/2008
	 */
	public function getElementById($id) {
		return isset ( $this->elements [$id] ) ? $this->elements [$id] : NULL;
	}

	/**
	 * Renvoie les éléments de la collection portant le nom passé en paramètre
	 *
	 * @param string $name
	 * @return Collection
	 * @since 05/2008
	 */
	public function getElementsByName($name) {
		$class = get_class ( $this );
		$selection = new $class ( 'les éléments portant le nom ' . $name . ' parmi la collection ' . $this->getName () );
		$names = $this->getNames ();
		$ids = $this->getIds ();
		/**
		 * recherche des éléments portant le nom passé en paramètre
		 */
		while ( current ( $names ) ) {
			// echo '<p>'.current($names).' : '.key($names).'</p>';
			if (strcmp ( $name, current ( $names ) ) == 0) {
				$id = $ids [key ( $names )];
				$selection->addElement ( $this->getElementById ( $id ) );
			}
			next ( $names );
		}
		return $selection;
	}

	/**
	 * Renvoie le premier élément de la collection portant le nom passé en paramètre.
	 *
	 * @param string $name
	 * @return Object
	 * @since 05/2008
	 */
	public function getElementByName($name) {
		$selection = $this->getElementsByName ( $name );
		return count($selection) > 0 ? $selection->getFirstElement () : NULL;
	}

	/**
	 * Renvoie le nom d'un élément de la collection retrouvé par son identifiant.
	 *
	 * @param string $id
	 * @return string
	 * @since 01/2008
	 * @version 02/2008
	 */
	public function getElementName($id) {
		return isset ( $this->elements [$id] ) ? $this->elements [$id]->getName () : NULL;
	}

	/**
	 * Obtient un sous-ensemble d'élements de la collection, retrouvés par leur identifiant.
	 *
	 * @param array $ids
	 * @return Collection
	 * @since 01/2008
	 */
	public function getSelectionByIds(Array $ids) {
		$class = get_class ( $this );
		$selection = new $class ( 'Sélection d\'éléments parmi la collection ' . $this->getName () );
		if (count($this) > 0) {
			$i = $this->getIterator ();
			$i->rewind ();
			while ( $i->current () ) {
				if (in_array ( $i->current ()->getId (), $ids )) {
					$selection->addElement ( $i->current () );
				}
				$i->next ();
			}
		}
		return $selection;
	}

	/**
	 * Obtient la liste des identifiants des éléments de la collection au format CSV
	 *
	 * @return string
	 * @since 01/2008
	 * @version 01/2008
	 */
	public function getCommaSeparatedIds() {
		return isset ( $this->elements ) ? implode ( ',', $this->getIds () ) : NULL;
	}

	/**
	 * Obtient les noms des éléments de la collection au format CSV
	 *
	 * @return string
	 * @since 01/2008
	 */
	public function getCommaSeparatedNames() {
		return is_array ( $this->elements ) ? implode ( ',', $this->getNames () ) : NULL;
	}

	/**
	 * Met en commun deux collections (union)
	 *
	 * @param Collection $c
	 * @return Collection
	 * @since 01/2008
	 * @version 02/2008
	 */
	public function mergeWith(Collection $c) {
		try {
			if (strcmp ( get_class ( $this ), get_class ( $c ) ) != 0) {
				throw new Exception ( 'Deux collections doivent être de même type pour pouvoir être fusionnées. Ici la collection "' . $this->getName () . '" est de type "' . get_class ( $this ) . '" alors que la collection "' . $c->getName () . '" est de type ' . get_class ( $c ) . ')' );
			}
			$class = get_class ( $this );
			/**
			 * les 2 collections comportent au moins un élément
			 */
			if (count($this) > 0 && count($c) > 0) {
				// echo '<p>les 2 collections comportent au moins un élément</p>';
				$ids = array_merge ( $this->getIds (), $c->getIds () ); // les identifiants des éléments présents dans au moins une des deux collections
				$output = new $class ( 'Conglomérat de collections de type ' . $class );
				foreach ( $ids as $id ) {
					$element_to_add = in_array ( $id, $this->getIds () ) ? $this->getElementById ( $id ) : $c->getElementById ( $id );
					$output->addElement ( $element_to_add );
				}
				return $output;
			} /**
			 * seule la collection passée en paramètre comporte un élément au moins
			 */
			elseif (count($this) == 0) {
				// echo '<p>seule la collection passée en paramètre <em>'.$c->getName().'</em> comporte un élément au moins</p>';
				return $c;
			} /**
			 * seule la collection courante comporte un élément au moins
			 */
			elseif (count($c) == 0) {
				// echo '<p>seule la collection courante <em>'.$this->getName().'</em> comporte un élément au moins</p>';
				return $this;
			} /**
			 * aucune des 2 collections à fusionner ne comporte d'éléments
			 */
			else {
				// echo '<p>aucune des 2 collections à fusionner ne comporte d\'éléments</p>';
				return new $class ();
			}
		} catch ( Exception $e ) {
			echo '<p>' . __METHOD__ . ' : ' . htmlentities ( $e->getMessage () ) . '</p>';
		}
	}

	/**
	 * Renvoie les éléments communs que possède la collection avec la collection passée en paramètre.
	 *
	 * @param Collection $c
	 * @return Collection
	 * @since 01/2008
	 * @version 03/2008
	 */
	public function getIntersectionWith(Collection $c) {
		try {
			if (strcmp ( get_class ( $this ), get_class ( $c ) ) != 0) {
				throw new Exception ( 'Deux collections doivent être de même type pour pouvoir en extraire les éléments en commun' );
			}
			$ids = array_intersect ( $this->getIds (), $c->getIds () ); // les identifiants des éléments en commun
			$class = get_class ( $this );
			$output = new $class ();
			foreach ( $ids as $id ) {
				$element_to_add = in_array ( $id, $this->getIds () ) ? $this->getElementById ( $id ) : $c->getElementById ( $id );
				$output->addElement ( $element_to_add );
			}
			return $output;
		} catch ( Exception $e ) {
			echo '<p>' . __METHOD__ . ' : ' . htmlentities ( $e->getMessage () ) . '</p>';
		}
	}

	/**
	 * Retourne les éléments de la collection amputés des éléments de la collection passée en paramètre.
	 *
	 * @param Collection $c
	 * @return Collection
	 * @since 03/2003
	 */
	public function getDifference(Collection $c) {
		try {
			if (strcmp ( get_class ( $this ), get_class ( $c ) ) != 0) {
				throw new Exception ( 'Deux collections doivent être de même type pour pouvoir en faire la différence' );
			}
			$ids = array_diff ( $this->getIds (), $c->getIds () ); // les identifiants des éléments en commun
			$class = get_class ( $this );
			$output = new $class ();
			foreach ( $ids as $id ) {
				$output->addElement ( $this->getElementById ( $id ) );
			}
			return $output;
		} catch ( Exception $e ) {
			echo '<p>' . __METHOD__ . ' : ' . htmlentities ( $e->getMessage () ) . '</p>';
		}
	}
}
?>