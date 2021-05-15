<?php
/**
 * L'historique des recherches de signets
 *
 * @author Florent
 * @package usocrate.bookmarks
 * @since 06/05/2011
 */
class BookmarkSearchHistory {
	private $elements;
	const EMPAN = 1;

	public function __construct() {
		$this->elements = array();
	}

	public function addElement(BookmarkSearch $element) {
		if (!$this->isElement($element)) {
			$this->elements[] = $element;
			if (count($this->elements)>= self::EMPAN) {
				$this->elements = array_slice($this->elements,(self::EMPAN*(-1)));
			}
		}
	}
	
	public function isElement(BookmarkSearch $element) {
		foreach ($this->elements as $e) {
			if (strcmp($e->getCommaSeparatedKeywords(),$element->getCommaSeparatedKeywords())==0) return true;
		}
		return false;
	}
	
	public function init() {
		if (isset($_SESSION['BookmarkSearchHistory'])) {
			foreach ($_SESSION['BookmarkSearchHistory'] as $e){
				$this->addElement(unserialize($e));
			}
		}
	}

	public function save() {
		$_SESSION['BookmarkSearchHistory'] = array();
		foreach ($this->elements as $e){
			//echo serialize($e).'<br/>';
			$_SESSION['BookmarkSearchHistory'][] = serialize($e);
		}
	}

	/**
	 * @return BookmarkSearch
	 */
	public function getLastElement() {
		return count($this->elements)>0 ? $this->elements[count($this->elements)-1] : NULL;
	}

	public function getSize() {
		return count($this->elements);
	}
	/**
	 * @version 05/2021
	 */
	public function toHtml() {
		global $system;
		$output = '';
		if(count($this->elements)>0) {
			$output.= '<ul class="navbar-nav" id="b_search_history">';
			foreach(array_reverse($this->elements) as $e) {
				$s = implode(' ',$e->getKeywords());
				$output.= '<li class="nav-item">';
				if ($e->hasKeyword()) {
					$output.= '<a class="nav-link" href="'.$system->getProjectUrl().'/search.php?bookmark_keywords='.urlencode($s).'&amp;bookmark_newsearch=1">'.ToolBox::toHtml($s).' <small>('.$e->countBookmarks().')</small></a>';
				} else {
					$output.= '<a class="nav-link" href="'.$system->getProjectUrl().'/search.php?bookmark_newsearch=1">Toutes <small>('.$e->countBookmarks().')</small></a>';
				}
				if ($e->hasKeyword()) {
					$output.= ' <button class="btn" type="button" value="'.ToolBox::toHtml($s).'" class="jsContingent"><i class="fas fa-pencil"></i></button>';
				}
				$output.= '</li>';
			}
			$output.= '</ul>';
		}
		return $output;
	}
}