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
	const EMPAN = 3;

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
	public function toHtml() {
		global $system;
		$output = '';
		if(count($this->elements)>0) {
			$output.= '<ul id="b_search_history">';
			foreach(array_reverse($this->elements) as $e) {
				$s = implode(' ',$e->getKeywords());
				$output.= '<li><a href="'.$system->getProjectUrl().'/search.php?bookmark_keywords='.urlencode($s).'&amp;bookmark_newsearch=1">'.ToolBox::toHtml($s).'</a>';
				$output.= ' <small>('.$e->countBookmarks().')</small>';
				$output.= ' <button type="button" value="'.ToolBox::toHtml($s).'" class="jsContingent navbar-btn"><i class="fa fa-pencil fa-lg"></i></button></li>';
			}
			$output.= '</ul>';
		}
		return $output;
	}
}