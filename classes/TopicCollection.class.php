<?php
/**
 * Classe permettant de gérer un ensemble de rubriques.
 *
 * @package usocrate.bookmarks
 * @since 01/06/2008
 */
class TopicCollection extends Collection
{

    /**
     * Constructeur
     * 
     * @version 01/06/2014
     */
    public function __construct($input = NULL)
    {
        parent::__construct('Topic');
        if ($input instanceof PDOStatement) {
            $input->execute();
            foreach ($input->fetchAll() as $data) {
                $t = new Topic();
                $t->hydrate($data, 'topic_');
                $this->add($t);
            }
        }
        elseif (is_array($input)) {
            foreach ($input as $data) {
                if ($data instanceof Topic) {
                    // la case du tableau considérée contient un objet du type attendu
                    $this->add($data);
                } elseif (is_array($data)) {
                    // la case du tableau considérée contient un tableau de données
                    $item = new Topic();
                    $item->hydrate($data);
                    $this->add($item);
                }
            }
        }
    }

    /**
     * Calcule le poids de chacune des rubriques de la collection exprimé en nombre de ressources (accessibles) qu'elle englobe.
     *
     * @return boolean
     * @since 31/10/2008
     * @version 09/05/2014
     */
    public function setTopicsWeight()
    {
        global $system;
        try {
            $sql = 'SELECT t1.topic_id, COUNT(DISTINCT(t3.bookmark_id)) AS topic_weight';
            $sql .= ' FROM ' . Topic::getTableName() . ' AS t1';
            $sql .= ' LEFT OUTER JOIN ' . Topic::getTableName() . ' AS t2 ON(t2.topic_interval_lowerlimit >= t1.topic_interval_lowerlimit AND t2.topic_interval_higherlimit <= t1.topic_interval_higherlimit)';
            $sql .= ' LEFT OUTER JOIN ' . Bookmark::getTableName() . ' AS t3 ON(t3.topic_id = t2.topic_id)';
            $criterias = array();
            $criterias[] = 't1.topic_id IN(' . $this->getCommaSeparatedIds() . ')';
            $criterias[] = 't3.bookmark_id IS NOT NULL';
            if (empty($_SESSION['user_id'])) {
                $criterias[] = 't2.topic_private = 0';
                $criterias[] = 't3.bookmark_private = 0';
            }
            $sql .= ' WHERE ' . implode(' AND ', $criterias);
            $sql .= ' GROUP BY t1.topic_id';
            
            $statement = $system->getPdo()->query($sql);
            
            while ($row = $statement->fetch(PDO::FETCH_ASSOC)) {
                $this->getElementById($row['topic_id'])->setWeight($row['topic_weight']);
            }
            return true;
        } catch (Exception $e) {
            $system->reportException(__METHOD__, $e );
            return false;
        }
    }

    /**
     * Trie les sous-rubriques en fonction du nombre de signets que chacune contient
     *
     * @since 09/08/2013
     */
    public function sortByWeight()
    {
        return uasort($this->elements, 'self::compareWeight');
    }

    /**
     * Trie les sous-rubriques par ordre alphabétique du titre.
     *
     * @since 07/06/2014
     */
    public function sortByTitle()
    {
        return uasort($this->elements, 'self::compareTitle');
    }

    /**
     *
     * @since 09/08/2013
     */
    public static function compareWeight(Topic $t1, Topic $t2)
    {
        if ($t1->getWeight() == $t2->getWeight()) {
            return 0;
        }
        return $t1->getWeight() > $t2->getWeight() ? - 1 : 1;
    }
    /**
     *
     * @since 07/06/2014
     */
    public static function compareTitle(Topic $t1, Topic $t2)
    {
        if (strcmp($t1->getTitle(),$t2->getTitle())<0) return 1;
        if (strcmp($t1->getTitle(),$t2->getTitle())>0) return -1;
        if (strcmp($t1->getTitle(),$t2->getTitle())==0) return 0;
    }
}
?>