<?php
class Publisher {
	private $name;
	private $bookmarks_nb;
	
	/**
	 *
	 * @param string $name        	
	 * @param int $bookmarks_nb        	
	 * @version 30/11/2013
	 */
	public function __construct($name, $bookmarks_nb = NULL) {
		$this->name = $name;
		$this->bookmarks_nb = $bookmarks_nb;
	}
	public function getName() {
		return $this->name;
	}
	
	/**
	 *
	 * @return int
	 * @since 30/11/2013
	 */
	public function countBookmarks() {
		return $this->bookmarks_nb;
	}
	
	/**
	 * Obtient la liste des ressources liées à l'éditeur.
	 *
	 * @return BookmarkCollection
	 * @version 01/2018
	 */
	public function getBookmarkCollection($criteria = NULL, $sort = NULL) {
		global $system;
		try {
			if (is_null ( $criteria )) {
				$criteria = array ();
			}
			$criteria ['bookmark_publisher'] = $this->name;
			return new BookmarkCollection ( $system->getBookmarkCollectionStatement ( $criteria, $sort ) );
		} catch ( Exception $e ) {
			$system->reportException ( __METHOD__, $e );
		}
	}
	
	/**
	 * @return BookmarkCollection
	 * @since 02/2012
	 * @version 06/2017
	 */
	public function getBookmarkCollectionSortedByLastHitDate() {
		return $this->getBookmarkCollection ( NULL, 'Last hit first' );
	}
	
	/**
	 * @return BookmarkCollection
	 * @since 02/2012
	 * @version 06/2017
	 */
	public function getBookmarkCollectionSortedByHitFrequency() {
		return $this->getBookmarkCollection ( NULL, 'Most frequently hit first' );
	}
	
	/**
	 * @return BookmarkCollection
	 * @since 02/2012
	 * @version 06/2017
	 */
	public function getBookmarkCollectionSortedByCreationDate() {
		return $this->getBookmarkCollection ( NULL, 'Last created first' );
	}
	
	/**
	 * @return string
	 * @since 05/2012
	 */
	public function getHtmlLinkTo($label = NULL) {
		global $system;
		$label = isset ( $label ) ? $label : $this->name;
		return '<a href="'.$system->getProjectUrl().'/publisher.php?publisher_name=' . urlencode ( $this->name ) . '">' . ToolBox::toHtml ( $label ) . '</a>';
	}
	
	/**
	 *
	 * @return string
	 * @since 24/11/2013
	 */
	public function getHtmlName() {
		return ToolBox::toHtml ( $this->name );
	}
	
	/**
	 *
	 * @return string
	 * @since 30/11/2013
	 */
	public function toJson() {
		return '{"name":' . ToolBox::stringToJson ( $this->getName () ) . ',"bookmarks_nb":' . $this->countBookmarks () . '}';
	}
}