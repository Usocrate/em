<?php
class BookmarkSearch {
	public $page_index;
	public $sort;
	public $topic_id;
	public $keywords;
	public $publisher;
	public $heat;
	public $bookmarks_nb;
	public function __construct() {
		$this->page_index = 1;
		$this->sort_key = 'bookmark_title';
		$this->sort_order = 'ASC';
		$this->keywords = array ();
	}
	public function setKeywords($input) {
		$this->keywords = explode ( ' ', $input );
		$validKeywords = array ();
		foreach ( $this->keywords as $k ) {
			if (iconv_strlen ( $k ) > 1)
				$validKeywords [] = $k;
		}
		$this->keywords = $validKeywords;
		$this->keywords = array_slice ( $this->keywords, 0, 4 ); // nombre de mot-clefs maximum = 4
	}
	/**
	 * @param string $sort
	 * @since 11/2013
	 * @version 06/2017
	 */
	private function setSortCriteria($input) {
		$this->sort = $input;
	}
	/**
	 * @param string $order
	 * @since 11/2013
	 * @version 06/2017
	 */
	public function setHitFrequencyAsSortCriteria($order='DESC') {
		$this->setSortCriteria('Most frequently hit first');
	}
	public function hasKeyword() {
		return count ( $this->keywords ) > 0;
	}
	public function getKeywords() {
		return $this->keywords;
	}
	public function getCommaSeparatedKeywords() {
		return implode ( ', ', $this->keywords );
	}
	public function getTopicId() {
		return $this->topic_id;
	}
	public function setTopicId($input) {
		$this->topic_id = $input;
	}
	public function setHeat($input) {
		$this->heat = $input;
	}
	public function setBookmarksNb($input) {
		$this->bookmarks_nb = $input;
	}
	public function isHot() {
		return $this->heat == 1;
	}
	public function setPublisher($input) {
		$this->publisher = $input;
	}
	public function getPublisher() {
		return $this->publisher;
	}
	public function countBookmarks() {
		return $this->bookmarks_nb;
	}
	public function setPageIndex($input) {
		$this->page_index = $input;
	}
	public function getPageIndex() {
		return $this->page_index;
	}
	public function getSort() {
		return $this->sort;
	}
}