<?php

class Year
{

    public $id;

    public function __construct($id)
    {
        $this->id = $id;
    }

    public function getId()
    {
        return $this->id;
    }

    /**
     * Obtient les signets les plus utilisés au cours de l'année.
     *
     * @param number $count            
     * @return BookmarkCollection
     * @since 25/05/2014
     */
    public function getMostHitBookmarkCollection($count = 3)
    {
        global $system;
    	return $system->getYearMostHitBookmarkCollection($this->id, $count);
    }

    /**
     * Obtient les signets créés cette année là les plus consultés.
     *
     * @param number $count            
     * @return BookmarkCollection
     * @since 25/05/2014
     */
    public function getMostHitBookmarkCollectionAsCreationYear($count = 3)
    {
        global $system;
        return $system->getCreationYearMostHitBookmarkCollection($this->id, $count);
    }
    public static function getHtmlLinkToYearDoc($year)
    {
        global $system;
    	return '<a href="'.$system->getProjectUrl().'/year.php?year_id=' . $year . '">' . $year . '</a>';
    }
}