<?php
/**
 * @package usocrate.bookmarks
 * @author Florent Chanavat
 */
class Highlighter {
	private $keywords;

	public function __construct($keywords)
	{
		$this->keywords = $keywords;
	}

	/**
	 * Obtient la chaîne avec les occurences de mot-clefs mis en exergue.
	 * @param String $input Le texte source
	 * @version 30/09/2006
	 */
	public function getString($input)
	{
		foreach ($this->keywords as $k) {
			$pattern = '@(\>[^<]*)('.ToolBox::toHtml($k).')@i';
			$input = preg_replace($pattern, '$1<span class="stabylo">$2</span>', $input);
		}
		return $input;
	}
}
?>