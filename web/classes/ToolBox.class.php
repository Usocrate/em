<?php
/**
 * Boîte à outils.
 * @author Florent
 * @since 27/12/2010
 */
class ToolBox {
	/**
	 * @version 18/04/2005
	 */
	public static function getHtmlPagesNav($page_index=1, $pages_nb, $param, $page_index_param_name='page_index')
	{
		//	construction de l'url de base des liens
		$url_param = is_array($param) ? self::arrayToUrlParam($param) : $param;
		if (iconv_strlen($url_param)>0) $url_param.='&';
		$url_base = $_SERVER['PHP_SELF'].'?'.$url_param;

		$empan = 3;
		$nav_items = array();

		//	première page
		if ($page_index>2) $nav_items[] = '<a href="'.$url_base.$page_index_param_name.'=1">&lt;&lt;</a>';
		else $nav_items[] = '<small>&lt;&lt;</small>';
		//	page précédente
		if ($page_index>1) $nav_items[] = '<a href="'.$url_base.$page_index_param_name.'='.($page_index-1).'">&lt;</a>';
		else $nav_items[] = '<small>&lt;</small>';
		//	autres pages
		for ($i=($page_index-$empan); $i<=($page_index+$empan); $i++){
			if ($i<1 || $i>$pages_nb) continue;
			if ($i==$page_index){
				$nav_items[] = '<strong>'.$i.'</strong>';
			} else {
				$nav_items[] = '<a href="'.$url_base.$page_index_param_name.'='.$i.'">'.$i.'</a>';
			}
		}
		//	page suivante
		if ($page_index<$pages_nb) $nav_items[] = '<a href="'.$url_base.$page_index_param_name.'='.($page_index+1).'">&gt;</a>';
		else $nav_items[] = '<small>&gt;</small>';
		//	dernière page
		if ($page_index<($pages_nb-1)) $nav_items[] = '<a href="'.$url_base.$page_index_param_name.'='.$pages_nb.'">&gt;&gt;</a>';
		else $nav_items[] = '<small>&gt;&gt;</small>';
		return implode('<small> | </small>', $nav_items);
	}
	/**
	 * Transforme un tableau en chaîne de paramètres à intégrer dans une url.
	 *
	 * @param $array
	 * @return string
	 * @version 2009-04-17
	 */
	public static function arrayToUrlParam($array)
	{
		if (is_array($array)){
			$params = array();
			foreach($array as $clé=>$valeur){
				if (isset($valeur)) $params[] = $clé.'='.urlencode($valeur);
			}
			return implode('&', $params);
		}
		return false;
	}
	/**
	 * Convertit tous les caractères de balisage d'une chaîne en entités Xml ("&amp;", "&lt;", "&gt;", "&apos;" et "&quot;")
	 *
	 * @since 16/09/2006
	 */
	public static function xmlEntities($input)
	{
		$search = array('&', '<', '>', '\'', '"');
		$replace = array('&amp;', '&lt;', '&gt;', '&apos;', '&quot;');
		return str_replace($search, $replace, $input);
	}
	/**
	 * Convertit une chaîne de caractère au format json.
	 *
	 * @param string $input
	 * @since 2008-09-06
	 */
	public static function stringToJson($input)
	{
		static $jsonReplaces = array(array("\\", "/", "\n", "\t", "\r", "\b", "\f", '"'), array('\\\\', '\\/', '\\n', '\\t', '\\r', '\\b', '\\f', '\"'));
		return '"' . str_replace($jsonReplaces[0], $jsonReplaces[1], $input) . '"';
	}
	/**
	 * @since 16/06/2012
	 */	
	public static function toHtml($input)
	{
		return htmlentities($input, ENT_QUOTES, 'UTF-8');
		
		/**
		* A partir de php 5.4
		*/
		//return htmlentities($input, ENT_HTML5);
	}	
	public static function sans_accent($chaine) {
		$accent  ="ÀÁÂÃÄÅÆÇÈÉÊËÌÍÎÏÐÑÒÓÔÕÖØÙÚÛÜÝÞßàáâãäåæçèéêëìíîïðñòóôõöøùúûýýþÿ";
		$noaccent="aaaaaaaceeeeiiiidnoooooouuuuybsaaaaaaaceeeeiiiidnoooooouuuyyby";
		return strtr(trim($chaine), $accent, $noaccent);
	}
	/**
	 * Elimine les caractères indésirables pour qu'une chaîne de caractère devienne utilisable comme nom de fichier.
	 *
	 * @return string
	 * @since 16/09/2005
	 */
	public static function formatForFileName($input)
	{
		$input = self::sans_accent($input);
		$input = strtolower($input);
		$input = str_replace(' ', '-', $input);
		return $input;
	}
	/**
	 * Formatte les données postées via formulaire pour les enregistrer en base.
	 *
	 * @version 20/01/2011
	 */
	public static function formatUserPost($data)
	{
		if (is_array($data)) {
			array_walk($data, 'ToolBox::formatUserPost');
		} else {
			$data = strip_tags($data);
			$data = html_entity_decode($data, ENT_QUOTES, 'UTF-8');
			$data = trim($data);
		}
	}
	/**
	 * Ajoute un répertoire dans la liste des répertoires utilisés dans la recherche de fichiers à inclure.
	 * @since 10/02/2007
	 */
	public static function addIncludePath($input)
	{
		return ini_set('include_path', $input.PATH_SEPARATOR.ini_get('include_path'));
	}
}