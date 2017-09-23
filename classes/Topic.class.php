<?php

/**
 * @package usocrate.bookmarks
 * @author Florent Chanavat
 */
class Topic implements CollectibleElement
{

    public $id;

    public $title;

    public $description;

    public $creation_date;

    public $privacy;

    /**
     * Le poids de la rubrique (exprimé en nombre de ressources incluses);
     *
     * @var int
     * @since 2008-10-31
     */
    public $weight;

    public $user_id;

    public $image_url;
    // pour RSS : 88 X 33 pixels
    public $bookmarks;
    // BookmarkCollection
    public $children;
    // TopicCollection
    public $ancestors;
    // TopicCollection
    public $relatedtopics;
    // TopicCollection
    public $forgottenbookmarks_percent;

    public function __construct($id = NULL)
    {
        $this->id = $id;
    }

    /**
     * Obtient l'identifiant de la rubrique
     *
     * @return String
     * @since 05/11/2005
     */
    public function getId()
    {
        return isset($this->id) ? $this->id : NULL;
    }

    /**
     *
     * @since 11/01/2011
     * @return bool
     */
    public function hasId()
    {
        return isset($this->id);
    }

    /**
     * Indique si la rubrique est confidentielle
     *
     * @return boolean
     * @since 17/05/2006
     */
    public function getPrivacy()
    {
        return isset($this->privacy) ? $this->privacy : NULL;
    }

    /**
     * Fixe le caractère public ou confidentiel de la rubrique.
     *
     * @since 2006-09-11
     * @version 2008-10-31
     */
    public function setPrivacy($input)
    {
        /**
         * la rubrique sera confidentielle
         */
        if ((int) $input === 1) {
            return $this->setAttribute('privacy', $input);
        } /**
         * la rubrique sera publique à condition qu'elle n'ait aucun ancêtre confidentiel
         */
        else {
            if (! $this->hasPrivateAncestor()) {
                return $this->setAttribute('privacy', $input);
            } else {
                return $this->setAttribute('privacy', 1);
            }
        }
    }

    /**
     * Fixe le poids de la rubrique.
     *
     * @param int $input            
     * @return boolean
     * @since 2008-10-31
     */
    public function setWeight($input)
    {
        return $this->setAttribute('weight', $input);
    }

    /**
     * Fixe le poids de chacune des rubriques-filles, exprimé en nombre de ressources (accessibles) qu'elle englobe.
     *
     * @since 09/08/2013
     */
    public function setChildrenWeight()
    {
        if ($this->hasChild()) {
            return $this->getChildren()->setTopicsWeight();
        }
    }

    /**
     * Indique si la rubrique est confidentielle
     *
     * @return bool
     * @since 04/06/2007
     */
    public function isPrivate()
    {
        switch ($this->getAttribute('privacy')) {
            case 1:
                return true;
            case 0:
                return false;
            default:
                return NULL;
        }
    }

    /**
     * Fixe l'identifiant de la rubrique
     *
     * @since 26/02/2006
     */
    public function setId($input)
    {
        $this->id = $input;
    }

    /**
     * Fixe la date de création de la rubrique
     *
     * @since 19/03/2007
     */
    public function setCreationDate($input)
    {
        return $this->setAttribute('creation_date', $input);
    }

    /**
     * Réinitialise le tableau contenant la liste des rubriques supérieures
     *
     * @since 12/03/2007
     */
    public function unsetAncestors()
    {
        unset($this->ancestors);
    }

    /**
     * Obtient la liste ordonnée des rubriques dont la rubrique est sous-rubrique
     *
     * @return TopicCollection
     * @version 07/06/2014
     */
    public function getAncestors()
    {
        global $system;
        try {
            if (! isset($this->ancestors)) {
                $this->ancestors = new TopicCollection($this->getAncestorCollectionStatement());
            }
            return $this->ancestors;
        } catch (Exception $e) {
            $system->reportException(__METHOD__, $e);
        }
    }

    /**
     * Obtient le nombre de rubriques parentes
     *
     * @return int
     * @since 01/04/2010
     * @version 07/06/2014
     */
    public function countAncestors()
    {
        global $system;
        try {
            return $this->getAncestors()->getSize();
        } catch (Exception $e) {
            $system->reportException(__METHOD__, $e);
        }
    }

    /**
     * Indique si la rubrique est une sous-rubrique
     *
     * @return bool
     * @since 25/04/2010
     */
    public function hasAncestor()
    {
        return $this->countAncestors() > 0;
    }

    /**
     * Indique si au moins une rubrique dont la rubrique est sous-rubrique est confidentielle.
     *
     * @return boolean
     * @since 01/11/2008
     */
    public function hasPrivateAncestor()
    {
        global $system;
        try {
            $i = $this->getAncestors()->getIterator();
            
            while ($i->current()) {
                if ($i->current()->isPrivate()) {
                    return true;
                }
                $i->next();
            }
        } catch (Exception $e) {
            $system->reportException(__METHOD__, $e);
        }
    }

    /**
     * Construit la requête permettant d'obtenir les données des rubriques dont la rubrique est sous-rubrique.
     *
     * @since 01/06/2014
     * @return PDOStatement
     */
    private function getAncestorCollectionStatement()
    {
        global $system;
        if (! is_null($this->getIntervalSize())) {
            $criteria['descendant'] = $this;
            return $system->getTopicCollectionStatement($criteria, NULL, 'topic_interval_size', 'DESC');
        }
    }

    /**
     * Construit la requête permettant d'obtenir les données des sous-rubriques de la rubrique.
     *
     * @since 01/06/2014
     * @return PDOStatement
     */
    private function getDescendantCollectionStatement()
    {
        global $system;
        if (! is_null($this->getIntervalSize())) {
            $criteria['ancestor'] = $this;
            return $system->getTopicCollectionStatement($criteria, NULL, 'topic_interval_lowerlimit', 'ASC');
        }
    }

    /**
     * Construit récursivement la liste des sous-rubriques des sous-rubriques de la rubrique courante
     *
     * @since 05/05/2007
     * @version 07/06/2014
     */
    public function buildDescendantsTree()
    {
        global $system;
        try {
            $this->children = new TopicCollection();
            $statement = $this->getDescendantCollectionStatement();
            $statement->execute();
            foreach ($statement->fetchAll() as $data) {
                $t = new Topic();
                $t->hydrate($data);
                $t->children = new TopicCollection();
                
                // la dernière rubrique ajoutée
                if (isset($stack) && count($stack) > 0) {
                    $lastadded = $stack[count($stack) - 1]->getLastChild();
                } else {
                    $lastadded = $this->getLastChild();
                }
                
                // gestion du contexte
                if (isset($lastadded) && $t->isDescendantOf($lastadded)) {
                    if (! isset($stack)) {
                        $stack = array();
                    }
                    $stack[] = $lastadded;
                } else {
                    while (isset($stack) && count($stack) > 0 && $stack[count($stack) - 1] instanceof Topic && ! $t->isDescendantOf($stack[count($stack) - 1])) {
                        array_pop($stack);
                    }
                }
                
                // ajout de la rubrique courante
                if (isset($stack) && count($stack) > 0) {
                    $stack[count($stack) - 1]->addChild($t);
                } else {
                    $this->addChild($t);
                }
            }
        } catch (Exception $e) {
            $system->reportException(__METHOD__, $e);
        }
    }

    /**
     * Obtient les sous-rubriques sous forme de balises html <option>
     *
     * @since 05/05/2007
     * @version 28/03/2013
     */
    public function getDescendantsOptionsTags($selection_ids = NULL, $exclusion_ids = NULL, $level = 0)
    {
        if (! isset($this->children)) {
            $this->buildDescendantsTree();
        }
        
        if (isset($selection_ids) && ! is_array($selection_ids)) {
            $selection_ids = array(
                $selection_ids
            );
        }
        if (isset($exclusions_ids) && ! is_array($exclusion_ids)) {
            $exclusion_ids = array(
                $exclusion_ids
            );
        }
        $html = '';
        if (isset($this->children) && $this->countChildren() > 0) {
            $this->children->sortByTitle();
            $i = $this->children->getIterator();
            
            do {
                $t = $i->current();
                if (is_array($exclusion_ids) && in_array($t->getId(), $exclusion_ids)) {
                    continue;
                }
                $html .= '<option value="' . $t->getId() . '" class="niv' . $level . '"';
                if (isset($selection_ids) && in_array($t->getId(), $selection_ids)) {
                    $html .= ' selected="selected"';
                }
                $html .= '>';
                $html .= str_repeat('&#160;&#160;', $level);
                switch ($level) {
                    case 1:
                        $html .= '-&#160;';
                        break;
                    case 2:
                        $html .= '&#160;&#160;';
                        break;
                    default:
                        $html .= '';
                }
                $html .= ToolBox::toHtml($t->getTitle());
                $html .= '</option>';
                $html .= $t->getDescendantsOptionsTags($selection_ids, $exclusion_ids, $level + 1);
            } while ($i->next());
        }
        return $html;
    }

    /**
     * Obtient les identifiants des sous-rubriques de la rubrique courante
     *
     * @since 24/03/2007
     * @version 29/08/2014
     * @return array
     */
    public function getDescendantsIds()
    {
        $ids = array();
        if (! isset($this->children)) {
            $this->buildDescendantsTree();
        }
        if ($this->children->hasElement()) {
            $i = $this->children->getIterator();
            do {
                $t = $i->current();
                $ids[] = $t->getId();
                if ($t->hasChild()) {
                    $ids = array_merge($ids, $t->getDescendantsIds());
                }
            } while ($i->next());
        }
        return $ids;
    }

    /**
     * Obtient la liste ordonnée des identifiants des rubriques dont la rubrique est sous-rubrique.
     *
     * @since 08/02/2014
     * @version 07/06/2014
     */
    public function getAncestorsIds()
    {
        global $system;
        try {
            return $this->getAncestors()->getIds();
        } catch (Exception $e) {
            $system->reportException(__METHOD__, $e);
        }
    }

    /**
     * Indique si la rubrique passée en paramètre est sous-rubrique de la rubrique courante
     *
     * @return bool
     * @since 17/03/2007
     * @version 07/06/2014
     */
    public function isAncestor($topic = NULL)
    {
        global $system;
        try {
            $i = $topic->getAncestors()->getIterator();
            
            while ($i->current()) {
                if (strcmp($i->current()->getId(), $this->id) == 0)
                    return true;
                $i->next();
            }
        } catch (Exception $e) {
            $system->reportException(__METHOD__, $e);
        }
    }

    /**
     * Obtient les données des rubriques-soeurs (ayant la même rubrique-parente que la rubrique courante)
     *
     * @return PDOStatement
     * @since 01/06/2014
     */
    private function getSiblingTopicCollectionStatement()
    {
        global $system;
        try {
            $parenttopic = $this->getParent();
            if ($parenttopic instanceof Topic) {
                $sql = 'SELECT t1.*, COUNT(t2.topic_id) AS relativeDepth';
                $sql .= ' FROM ' . self::getTableName() . ' AS t1';
                $sql .= ' LEFT OUTER JOIN ' . self::getTableName() . ' AS t2';
                $sql .= ' ON (t1.topic_interval_lowerlimit> t2.topic_interval_lowerlimit AND t1.topic_interval_higherlimit < t2.topic_interval_higherlimit )';
                $where = array();
                if (empty($_SESSION['user_id']))
                    $where[] = 't1.topic_private=0';
                $where[] = 't1.topic_interval_lowerlimit > :parent_interval_lowerlimit';
                $where[] = 't1.topic_interval_higherlimit < :parent_interval_higherlimit';
                $where[] = 't1.topic_id <> :id';
                $where[] = 't2.topic_interval_lowerlimit >= :parent_interval_lowerlimit';
                $where[] = 't2.topic_interval_higherlimit<= :parent_interval_higherlimit';
                $sql .= ' WHERE ' . implode(' AND ', $where);
                $sql .= ' GROUP BY t1.topic_id';
                $sql .= ' HAVING relativeDepth=1';
                
                $statement = $system->getPdo()->prepare($sql);
                
                $statement->bindValue(':parent_interval_lowerlimit', (int) $parenttopic->getIntervalLowerLimit(), PDO::PARAM_INT);
                $statement->bindValue(':parent_interval_higherlimit', (int) $parenttopic->getIntervalHigherLimit(), PDO::PARAM_INT);
                $statement->bindValue(':id', (int) $this->getId(), PDO::PARAM_INT);
                
                $statement->setFetchMode(PDO::FETCH_ASSOC);
                
                return $statement;
            }
        } catch (Exception $e) {
            $system->reportException(__METHOD__, $e);
        }
    }

    /**
     * Obtient l'ensemble des rubriques-soeurs (ayant la même rubrique-parente que la rubrique courante)
     *
     * @return TopicCollection
     * @since 16/12/2006
     * @version 07/06/2014
     */
    public function getSiblings()
    {
        global $system;
        try {
            if (! isset($this->siblings)) {
                $this->siblings = new TopicCollection($this->getSiblingTopicCollectionStatement());
            }
            return $this->siblings;
        } catch (Exception $e) {
            $system->reportException(__METHOD__, $e);
        }
    }

    public function pathToHtmlBreadcrumb($target = '_self')
    {
        global $system;
        try {
            if ($this->getAncestors()->getSize() > 0) {
                $html = '<ol class="breadcrumb">';
                $i = $this->getAncestors()->getIterator();
                // on passe délibérement la premier rubrique (la rubrique englobant toutes les autres rubriques)
                while ($i->next()) {
                    $html .= '<li><a href="' . $i->current()->getUrl() . '">' . $i->current()->nameToHtml() . '</a></li>';
                }
                $html .= '</ol>';
                return $html;
            }
        } catch (Exception $e) {
            $system->reportException(__METHOD__, $e);
        }
    }

    public function getHtmlPath($target = '_self')
    {
        global $system;
        try {
            if ($this->hasAncestor()) {
                $items = array();
                $i = $this->getAncestors()->getIterator();
                while ($i->next()) {
                    $items[] = $i->current()->getHtmlLink($target);
                }
                return implode(' / ', $items);
            }
        } catch (Exception $e) {
            $system->reportException(__METHOD__, $e);
        }
    }

    /**
     * Obtient la borne inférieure de l'intervalle correspondant à la rubrique
     *
     * @since 25/02/2007
     * @version 08/05/2007
     * @return int
     */
    public function getIntervalLowerLimit()
    {
        $i = $this->getInterval();
        return $i instanceof Interval ? $i->getLowerLimit() : NULL;
    }

    /**
     * Obtient la borne supérieure de l'intervalle correspondant à la rubrique
     *
     * @since 25/02/2007
     * @version 08/05/2007
     * @return int
     */
    public function getIntervalHigherLimit()
    {
        $i = $this->getInterval();
        return $i instanceof Interval ? $i->getHigherLimit() : NULL;
    }

    /**
     * Obtient l'intervalle correspondant à la rubrique
     *
     * @return Interval
     * @since 08/05/2007
     * @version 06/05/2015
     */
    public function getInterval()
    {
        global $system;
        try {
            if (! $this->hasId()) {
                return null;
            }
            // on fait le choix de récupérer systématiquement les informations en base de données, par sécurité
            $statement = $system->getPdo()->prepare('SELECT topic_interval_lowerlimit, topic_interval_higherlimit FROM ' . $system->getTopicTableName() . ' WHERE topic_id=:id');
            $statement->bindValue(':id', $this->getId(), PDO::PARAM_INT);
            if ($statement->execute()) {
                $row = $statement->fetch();
                if (isset($row['topic_interval_lowerlimit']) && isset($row['topic_interval_higherlimit'])) {
                    return new Interval($row['topic_interval_lowerlimit'], $row['topic_interval_higherlimit']);
                }
            }
        } catch (Exception $e) {
            $system->reportException(__METHOD__, $e);
        }
    }

    /**
     * Transfère la rubrique dans une autre rubrique
     *
     * @since 25/02/2007
     * @version 03/10/2014
     */
    public function transferTo(Topic $topic)
    {
        global $system;
        try {
            if (empty($_SESSION['user_id'])) {
                throw new Exception('L\'utilisateur n\'est pas identifié');
            }
            if ($topic->getId() == $this->getId()) {
                throw new Exception('La rubrique ne peut être transférée dans elle même !');
            }
            if (is_null($this->getIntervalSize())) {
                throw new Exception('On ne connaît pas l\'intervalle de la rubrique à transférer');
            }
            if ($this->isAncestor($topic)) {
                throw new Exception('On ne peut transférer une rubrique dans une de ses sous-rubriques');
            }
            if ($topic->pushIntervalHigherLimit($this->getIntervalSize()) + 1) { // on a fait de la place dans la rubrique de destination
                $shift = ($topic->getIntervalHigherLimit() - 1) - $this->getIntervalHigherLimit();
                if ($this->shiftInterval($shift)) { // on a décalé l'intervalle de la rubrique à transférer
                    $system->trimTopicInterval();
                    return true;
                } else {
                    throw new Exception('échec du décalage de l\'intervalle de la rubrique à transférer');
                }
            } else {
                throw new Exception('échec de l\'extension de l\'intervalle de la rubrique cible');
            }
        } catch (Exception $e) {
            $system->reportException(__METHOD__, $e);
            if ($system->getPdo()->inTransaction()) {
                $system->getPdo()->rollBack();
            }
            return false;
        }
    }

    /**
     * Obtient l'amplitude de l'intervalle.
     *
     * @since 25/02/2007
     * @version 04/10/2014
     * @return int
     */
    public function getIntervalSize()
    {
        $i = $this->getInterval();
        return ($i instanceof Interval) ? $i->getSize() : null;
    }

    /**
     *
     * @version 04/10/2014
     */
    private function shiftInterval($shift)
    {
        global $system;
        try {
            $this->getInterval()->shift($shift);
            return true;
        } catch (Exception $e) {
            $system->reportException(__METHOD__, $e);
            return false;
        }
    }

    /**
     * Ajoute la rubrique courante à une autre rubrique
     *
     * @since 25/02/2007
     * @version 06/05/2015
     * @return bool
     */
    public function addTo(Topic $topic)
    {
        global $system;
        try {
            if (! $topic->getId()) {
                throw new Exception("L'identifiant de la rubrique de destination est inconnu");
            }
            if (! is_null($this->getIntervalSize())) {
                throw new Exception('L\'intervalle de la rubrique est connu, utiliser plutôt Topic:transferTo()');
            }
            if ($topic->pushIntervalHigherLimit(2)) {
                $l = $topic->getIntervalHigherLimit();
                if (isset($l)) {
                   $result = $this->toDB() && $this->saveInterval(new Interval(($l - 2), ($l - 1)));
                   // une fois la rubrique inscrite dans l'arborescence, on force sa confidentialité si on trouve au moins une catégorie confidentielle danssa hiérarchie.
                   if (!$this->isPrivate() && $this->hasPrivateAncestor()) {
                       $this->setPrivacy(true);
                       $this->toDB();
                   }
                   return $result;
                }
                throw new Exception('On a besoin de connaître la borne supérieure d\'une rubrique pour y ajouter une rubrique');
            }
            throw new Exception('L\'intervalle de la rubrique de destination n\'a pu être agrandi');
        } catch (Exception $e) {
            $system->reportException(__METHOD__, $e);
            return false;
        }
    }

    /**
     * Augmente la taille de l'intervalle associé à la rubrique en agissant sur sa borne supérieure
     *
     * @param int $shift            
     * @since 05/03/2007
     * @version 03/10/2014
     * @return bool
     */
    public function pushIntervalHigherLimit($shift)
    {
        global $system;
        try {
            return $system->pushTopicLimitsBeyondPosition($this->getIntervalHigherLimit() - 1, $shift);
        } catch (Exception $e) {
            $system->reportException(__METHOD__, $e);
            return false;
        }
    }

    /**
     * Obtient la rubrique de niveau supérieur
     *
     * @since 11/09/2006
     * @version 08/05/2007
     * @return Topic
     */
    public function getParent()
    {
        global $system;
        try {
            return $this->getAncestors()->getLastElement();
        } catch (Exception $e) {
            $system->reportException(__METHOD__, $e);
        }
    }

    /**
     *
     * @since 11/03/2015
     */
    public function getParentId()
    {
        return $this->getParent() instanceof Topic ? $this->getParent()->getId() : null;
    }

    /**
     * Obtient les enregistrements des sous-rubriques (premier niveau) de la rubrique.
     *
     * @since 07/06/2014
     */
    private function getChildrenCollectionStatement()
    {
        global $system;
        try {
            // Les sous-rubriques (premier niveau) de la rubrique sont les sous-rubriques ayant un seul parent, la rubrique elle même
            $sql = 'SELECT t1.*, COUNT(*) AS relativeDepth';
            $sql .= ' FROM ' . self::getTableName() . ' AS t1';
            $sql .= ' LEFT OUTER JOIN ' . self::getTableName() . ' AS t2';
            $sql .= ' ON (t1.topic_interval_lowerlimit > t2.topic_interval_lowerlimit AND t1.topic_interval_higherlimit < t2.topic_interval_higherlimit )';
            $where = array();
            if (empty($_SESSION['user_id'])) {
                $where[] = 't1.topic_private=0';
            }
            $where[] = 't1.topic_interval_lowerlimit > :lowerlimit';
            $where[] = 't1.topic_interval_higherlimit < :higherlimit';
            $where[] = 't2.topic_interval_lowerlimit >= :lowerlimit';
            $where[] = 't2.topic_interval_higherlimit <= :higherlimit';
            $sql .= ' WHERE (' . implode(' AND ', $where) . ')';
            $sql .= ' GROUP BY t1.topic_title ASC, t1.topic_id';
            $sql .= ' HAVING COUNT(*)=1'; // relative depth
            $statement = $system->getPdo()->prepare($sql);
            $statement->setFetchMode(PDO::FETCH_ASSOC);
            $statement->bindValue(':lowerlimit', $this->getIntervalLowerLimit(), PDO::PARAM_INT);
            $statement->bindValue(':higherlimit', $this->getIntervalHigherLimit(), PDO::PARAM_INT);
            return $statement;
        } catch (Exception $e) {
            return $system->reportException(__METHOD__, $e);
        }
    }

    /**
     * Obtient le nombre de raccourcis
     *
     * @return int
     * @since 18/06/2012
     */
    public function countRelatedTopics()
    {
        return $this->getRelatedTopics()->getSize();
    }

    /**
     * Indique si la rubrique est une sous-rubrique
     *
     * @return bool
     * @since 18/06/2012
     */
    public function hasRelatedTopic()
    {
        return $this->countRelatedTopics() > 0;
    }

    /**
     * Obtient les raccourcis vers d'autres rubriques associés à cette rubrique.
     *
     * @since 26/02/2010
     */
    public function getRelatedTopics()
    {
        global $system;
        try {
            if (isset($this->relatedtopics)) {
                return $this->relatedtopics;
            } else {
                $fields = array();
                $fields[] = 't1.topic_id';
                $fields[] = 't1.topic_title';
                $fields[] = 't1.topic_private';
                // pour titres des rubriques parentes, nécessaire échappement des caractères spéciaux CSV
                $fields[] = 'GROUP_CONCAT(IF(LOCATE(\',\',t2.topic_title)=0, t2.topic_title, CONCAT(\'"\',REPLACE(t2.topic_title,\'"\',\'""\'),\'"\')) ORDER BY t2.topic_interval_lowerlimit ASC SEPARATOR \',\') AS ancestors_titles';
                $fields[] = 'GROUP_CONCAT(t2.topic_id ORDER BY t2.topic_interval_lowerlimit ASC SEPARATOR \',\') AS ancestors_ids';
                
                $where = array();
                if (empty($_SESSION['user_id'])) {
                    $where[] = 't1.topic_private=0';
                }
                $where[] = 's.from = :id';
                
                $sql = 'SELECT ' . implode(', ', $fields);
                $sql .= ' FROM ' . $system->getShortCutTableName() . ' AS s';
                $sql .= ' INNER JOIN ' . self::getTableName() . ' AS t1';
                $sql .= ' ON t1.topic_id = s.to';
                $sql .= ' LEFT OUTER JOIN ' . self::getTableName() . ' AS t2';
                $sql .= ' ON (t2.topic_interval_lowerlimit < t1.topic_interval_lowerlimit AND t2.topic_interval_higherlimit > t1.topic_interval_higherlimit)';
                $sql .= ' WHERE ' . implode(' AND ', $where);
                $sql .= ' GROUP BY t2.topic_title ASC, t1.topic_id';

                $statement = $system->getPdo()->prepare($sql);
                
                $statement->bindValue(':id', $this->getId(), PDO::PARAM_INT);
                
                $statement->setFetchMode(PDO::FETCH_ASSOC);
                
                $statement->execute();
                
                // parser les chaînes CSV construites par la requête
                if (! function_exists('str_getcsv')) {
                    // cette fonction arrive en php 5.3
                    function str_getcsv($input, $delimiter = ",", $enclosure = '"', $escape = "\\")
                    {
                        $fiveMBs = 5 * 1024 * 1024;
                        $fp = fopen("php://temp/maxmemory:$fiveMBs", 'r+');
                        fputs($fp, $input);
                        rewind($fp);
                        $data = fgetcsv($fp, 1000, $delimiter, $enclosure); // $escape only got added in 5.3.0
                        fclose($fp);
                        return $data;
                    }
                }
                // construire la collection de topics
                $this->relatedtopics = new TopicCollection();
                foreach ($statement->fetchAll() as $data) {
                    $t = new Topic();
                    $t->hydrate($data);
                    $ancestors_titles = str_getcsv($data['ancestors_titles']);
                    $ancestors_ids = str_getcsv($data['ancestors_ids']);
                    $t->ancestors = new TopicCollection();
                    for ($i = 0; $i < count($ancestors_ids); $i ++) {
                        $a = new Topic($ancestors_ids[$i]);
                        $a->setTitle($ancestors_titles[$i]);
                        $t->ancestors->add(clone $a);
                    }
                    $this->relatedtopics->addElement($t);
                }
                return $this->relatedtopics;
            }
        } catch (Exception $e) {
            $system->reportException(__METHOD__, $e);
        }
    }

    /**
     * Etablit un lien thématique (qui donnera lieu à l'affichage d'un raccourci) entre la rubrique courante et une autre rubrique.
     *
     * @since 27/02/2010
     * @version 05/08/2014
     */
    public function addShortCutTo(Topic $relatedTopic)
    {
        global $system;
        try {
            if (($relatedTopic->getId() != $this->id) && ! ($this->hasShortCutTo($relatedTopic))) {
                $sql = 'INSERT INTO ' . $system->getShortCutTableName() . ' (`from`,`to`) VALUES (:id, :related_id)';
                $statement = $system->getPdo()->prepare($sql);
                $statement->bindValue(':id', (int) $this->id, PDO::PARAM_INT);
                $statement->bindValue(':related_id', (int) $relatedTopic->getId(), PDO::PARAM_INT);
                return $statement->execute();
            }
            return false;
        } catch (Exception $e) {
            $system->reportException(__METHOD__, $e);
        }
    }

    /**
     * Crée une relation mutuelle (raccourcis) avec la rubrique passée en paramètre.
     *
     * @param Topic $topic            
     * @since 24/01/2011
     */
    public function addRelationWith(Topic $topic)
    {
        return $this->addShortCutTo($topic) & $topic->addShortCutTo($this);
    }

    /**
     * Supprime la relation mutuelle avec la rubrique passée en paramètre.
     *
     * @param Topic $topic            
     * @since 24/01/2011
     */
    public function removeRelationWith(Topic $topic)
    {
        return $this->removeShortCutTo($topic) & $topic->removeShortCutTo($this);
    }

    /**
     * Indique si la rubrique possède un raccourci vers la rubrique passée en paramètre.
     *
     * @param Topic $relatedTopic            
     * @return bool
     * @since 24/01/2011
     * @version 05/08/2014
     */
    public function hasShortCutTo(Topic $relatedTopic)
    {
        global $system;
        try {
            $sql = 'SELECT * FROM ' . $system->getShortCutTableName() . ' WHERE `from`=:from AND `to`= :to';
            
            $statement = $system->getPdo()->prepare($sql);
            
            $statement->bindValue(':from', (int) $this->getId(), PDO::PARAM_INT);
            $statement->bindValue(':to', (int) $relatedTopic->getId(), PDO::PARAM_INT);
            
            $statement->execute();
            
            return $statement->rowCount() > 0;
        } catch (Exception $e) {
            $system->reportException(__METHOD__, $e);
        }
    }

    /**
     * Supprime un raccourci associé à la rubrique
     *
     * @param Topic $relatedTopic            
     * @since 20/03/2010
     * @version 07/06/2014
     */
    public function removeShortCutTo(Topic $relatedTopic)
    {
        global $system;
        try {
            $sql = 'DELETE FROM ' . $system->getShortCutTableName() . ' WHERE `from`=:from AND `to`= :to';
            
            $statement = $system->getPdo()->prepare($sql);
            
            $statement->bindValue(':from', (int) $this->getId(), PDO::PARAM_INT);
            $statement->bindValue(':to', (int) $relatedTopic->getId(), PDO::PARAM_INT);
            
            return $statement->execute();
        } catch (Exception $e) {
            $system->reportException(__METHOD__, $e);
        }
    }

    /**
     * Obtient l'intervalle représentant les sous-rubriques de la rubrique
     *
     * @return Interval
     * @since 22/04/2007
     * @version 07/06/2014
     */
    private function getChildrenInterval()
    {
        global $system;
        try {
            $fields = array();
            $fields[] = 'MIN(topic_interval_lowerlimit) AS lowerlimit';
            $fields[] = 'MAX(topic_interval_higherlimit) AS higherlimit';
            
            $where[] = 'topic_interval_lowerlimit > :lowerlimit';
            $where[] = 'topic_interval_higherlimit < :higherlimit';
            if (empty($_SESSION['user_id'])) {
                $where[] = 'topic_private=0';
            }
            
            $sql = 'SELECT ' . implode(',', $fields) . ' FROM ' . self::getTableName() . ' WHERE ' . implode(' AND ', $where);
            
            $statement = $system->getPdo()->prepare($sql);
            
            $statement->bindValue(':lowerlimit', (int) $this->getIntervalLowerLimit(), PDO::PARAM_INT);
            $statement->bindValue(':higherlimit', (int) $this->getIntervalHigherLimit(), PDO::PARAM_INT);
            
            $statement->setFetchMode(PDO::FETCH_ASSOC);
            $statement->execute();
            
            $data = $statement->fetch();
            return new Interval($data['lowerlimit'], $data['higherlimit']);
        } catch (Exception $e) {
            $system->reportException(__METHOD__, $e);
        }
    }

    /**
     * Obtient la liste des sous-rubriques (premier niveau) sous forme de collection.
     *
     * @return TopicCollection
     * @since 31/10/2008
     * @version 07/06/2014
     */
    public function getChildren()
    {
        if (! isset($this->children)) {
            $this->children = new TopicCollection($this->getChildrenCollectionStatement());
        }
        return $this->children;
    }

    /**
     * Obtient le dernier enfant
     *
     * @return Topic
     * @since 04/06/2007
     * @version 07/06/2014
     */
    public function getLastChild()
    {
        if (isset($this->children) && $this->children->getSize() > 0) {
            return $this->children->getLastElement();
        }
    }

    /**
     * Indique si la rubrique précède la rubrique passée en paramètre dans l'ordre alphabétique.
     *
     * @param Topic $topic            
     * @since 04/06/2007
     * @version 11/12/2007
     * @todo débogage
     */
    public function comeAlphabeticallyFirst(Topic $topic)
    {
        for ($i = 1; $i < iconv_strlen($topic->getTitle()) || $i < iconv_strlen($this->getTitle()); $i ++) {
            $s1 = iconv_substr($topic->getTitle(), 0, $i);
            $s2 = iconv_substr($this->getTitle(), 0, $i);
            // echo $s1.' vs '.$s2.' : '.strcasecmp($s1, $s2).'<br/>';
            if (strcasecmp($s1, $s2) != 0) {
                return strcasecmp($s1, $s2) > 0;
            }
        }
        return NULL;
    }

    /**
     * Ajoute une sous-rubriques à la liste des sous-rubriques directement inférieures
     *
     * @since 27/05/2007
     * @version 07/06/2014
     */
    public function addChild(Topic $t)
    {
        global $system;
        try {
            if (! isset($this->children)) {
                $this->children = new TopicCollection();
            }
            $this->children->add($t);
        } catch (Exception $e) {
            $system->reportException(__METHOD__, $e);
        }
    }

    /**
     * Indique si la rubrique est sous-rubrique directe de la rubrique passée en paramètre
     *
     * @since 27/05/2007
     * @version 07/06/2014
     */
    public function isChildOf($t)
    {
        global $system;
        try {
            return $t->getChildren()->hasElement($this);
        } catch (Exception $e) {
            $system->reportException(__METHOD__, $e);
        }
    }

    /**
     * Obtient les identifiants des sous-rubriques (premier niveau) de la rubrique
     *
     * @return array;
     * @since 19/03/2007
     * @version 07/06/2014
     */
    private function getChildrenIds()
    {
        global $system;
        try {
            return $this->getChildren()->getIds();
        } catch (Exception $e) {
            $system->reportException(__METHOD__, $e);
        }
    }

    /**
     * Obtient le nombre de sous-rubriques directes de la rubrique
     *
     * @return int
     * @since 05/05/2007
     * @version 07/06/2014
     */
    public function countChildren()
    {
        global $system;
        try {
            return $this->getChildren()->getSize();
        } catch (Exception $e) {
            $system->reportException(__METHOD__, $e);
        }
    }

    /**
     * Indique si la rubrique possède des sous-rubrique
     *
     * @return bool
     * @since 27/05/2007
     * @version 07/06/2014
     */
    public function hasChild()
    {
        if (! isset($this->children)) {
            $this->getChildren();
        }
        return $this->countChildren() > 0;
    }

    /**
     * Indique si la rubrique est sous-rubrique d'une autre rubrique
     *
     * @since 01/05/2007
     * @return bool
     */
    public function isDescendantOf($topic)
    {
        return $topic instanceof Topic ? $this->getIntervalLowerLimit() > $topic->getIntervalLowerLimit() && $this->getIntervalHigherLimit() < $topic->getIntervalHigherLimit() : NULL;
    }

    /**
     * Indique si la rubrique est la rubrique principale
     *
     * @since 08/04/2007
     * @version 03/01/2014
     * @return bool
     */
    public function isMainTopic()
    {
        global $system;
        return $this->getId() == $system->getMainTopicId();
    }

    /**
     * Répercute l'état de confidentialité de la rubrique à l'ensemble de ses sous-rubriques
     *
     * @version 07/06/2014
     */
    public function spreadPrivacyToDescendants()
    {
        global $system;
        try {
            if (is_null($this->getIntervalSize())) {
                throw Exception('Impossible de connaître les bornes de la rubrique' . $this->getId());
            }
            $where = array();
            $where[] = 'topic_interval_lowerlimit > :lowerlimit';
            $where[] = 'topic_interval_higherlimit < :higherlimit';
            $sql = 'UPDATE ' . self::getTableName() . ' SET topic_private = :privacy';
            $sql .= ' WHERE ' . implode(' AND ', $where);
            
            $statement = $system->getPdo()->prepare($sql);
            
            $statement->bindValue(':lowerlimit', (int) $this->getIntervalLowerLimit(), PDO::PARAM_INT);
            $statement->bindValue(':higherlimit', (int) $this->getIntervalHigherLimit(), PDO::PARAM_INT);
            $statement->bindValue(':privacy', (int) $this->getPrivacy(), PDO::PARAM_INT);
            
            return $statement->execute();
        } catch (Exception $e) {
            $system->reportException(__METHOD__, $e);
            return false;
        }
    }

    /**
     * Obtient les données des signets rattachés à la rubrique (niveau 1)
     *
     * @return Resource
     * @since 06/2014
     * @version 06/2017
     */
    private function getBookmarkCollectionStatement($criteria = null, $sort = null)
    {
        global $system;
        if (is_array($criteria)) {
            $criteria['topic_id'] = $this->getId();
        } else {
            $criteria = array(
                'topic_id' => $this->getId()
            );
        }
        return $system->getBookmarkCollectionStatement($criteria, $sort);
    }

    /**
     * Obtient la liste des signets rattachés à la rubrique
     *
     * @return BookmarkCollection
     * @since 04/10/2014
     */
    public function getBookmarks()
    {
        global $system;
        try {
            if (! isset($this->bookmarks)) {
                $this->bookmarks = new BookmarkCollection($this->getBookmarkCollectionStatement());
            }
            return $this->bookmarks;
        } catch (Exception $e) {
            $system->reportException(__METHOD__, $e);
        }
    }

    /**
     * Obtient la liste des dernières signets ajoutés.
     *
     * @param int $row_count            
     * @return BookmarkCollection
     * @since 05/2007
     * @version 06/2017
     */
    public function getLastAddedDependentBookmarks($count = 15)
    {
        global $system;
        try {
            $criteria = array(
                'topic_interval_lowerlimit' => $this->getIntervalLowerLimit(),
                'topic_interval_higherlimit' => $this->getIntervalHigherLimit()
            );
            $statement = $system->getBookmarkCollectionStatement($criteria, 'Last created first', $count);
            return new BookmarkCollection($statement);
        } catch (Exception $e) {
            $system->reportException(__METHOD__, $e);
        }
    }

    /**
     * Obtient les signets rattachés à la rubrique triés par date de dernière consultation.
     *
     * @return BookmarkCollection
     * @version 06/2017
     */
    public function getBookmarksSortByLastHitDate() {
        global $system;
        try {
            $statement = $this->getBookmarkCollectionStatement(null, 'Last hit first');
            return new BookmarkCollection($statement);
        } catch (Exception $e) {
            $system->reportException(__METHOD__, $e);
        }
    }
    /**
     * @since 09/2017
     */
    public function getBookmarksSortByLastFocusDate() {
        global $system;
        try {
            $statement = $this->getBookmarkCollectionStatement(null, 'Last focused first');
            return new BookmarkCollection($statement);
        } catch (Exception $e) {
            $system->reportException(__METHOD__, $e);
        }
    }    

    /**
     * Obtient les signets rattachés à la rubrique triés par date d'enregistrement.
     *
     * @return BookmarkCollection
     * @version 06/2017
     */
    public function getBookmarksSortByCreationDate()
    {
        global $system;
        try {
            $statement = $this->getBookmarkCollectionStatement(null, 'Last created first');
            return new BookmarkCollection($statement);
        } catch (Exception $e) {
            $system->reportException(__METHOD__, $e);
        }
    }

    /**
     * supprime en base de données les ressources de la rubrique
     *
     * @since 25/03/2007
     * @version 07/06/2014
     */
    public function deleteDependentBookmarks()
    {
        global $system;
        try {
            if (! $this->hasId()) {
                throw new Exception('L\'identifiant de la rubrique est inconnu');
            }
            return $this->getDependentBookmarks()->delete();
        } catch (Exception $e) {
            $system->reportException(__METHOD__, $e);
            return false;
        }
    }

    /**
     * Obtient les ressources associées à la rubrique ou à l'une de ses sous-rubriques
     *
     * @since 05/03/2007
     * @version 07/06/2014
     */
    public function getDependentBookmarks()
    {
        global $system;
        try {
            return new BookmarkCollection($this->getDependentBookmarkCollectionStatement());
        } catch (Exception $e) {
            $system->reportException(__METHOD__, $e);
        }
    }

    /**
     *
     * @return Ambigous <PDOStatement, boolean, unknown>
     * @since 07/06/2014
     * @version 06/05/2015
     */
    public function getDependentBookmarkCollectionStatement()
    {
        global $system;
        try {
            $i = $this->getInterval();
            if ($i->areLimitsSet()) {
                $criteria = array(
                    'topic_interval_lowerlimit' => $i->getLowerLimit(),
                    'topic_interval_higherlimit' => $i->getHigherLimit()
                );
                return $system->getBookmarkCollectionStatement($criteria);
            } else {
                throw new Exception('On ne peut récupérer les signets de la rubrique ' . $this->getName() . ' sans en connaître les bornes.');
            }
        } catch (Exception $e) {
            $system->reportException(__METHOD__, $e);
            return false;
        }
    }

    /**
     * Décompte du nombre de signets enregistrés, pour chaque année d'existence de la collection.
     *
     * @return array
     * @since 06/02/2010
     * @version 07/06/2014
     */
    public function countDependentBookmarkCreationYearly()
    {
        global $system;
        try {
            $sql = 'SELECT YEAR(b.bookmark_creation_date) AS year, COUNT(*) AS count';
            $sql .= ' FROM ' . $system->getBookmarkTableName() . ' AS b LEFT JOIN ' . self::getTableName() . ' AS t ON(t.topic_id=b.topic_id)';
            $where = array();
            if (empty($_SESSION['user_id'])) {
                // limitation aux ressources publiques
                $where[] = '(bookmark_private=0 AND topic_private=0)';
            }
            $where[] = '(topic_interval_lowerlimit >= :lowerlimit AND topic_interval_higherlimit <= :higherlimit)';
            $sql .= ' WHERE ' . implode(' AND ', $where);
            $sql .= ' GROUP BY year ASC';
            
            // print_r($sql);
            
            $statement = $system->getPdo()->prepare($sql);
            
            $statement->bindValue(':lowerlimit', (int) $this->getIntervalLowerLimit(), PDO::PARAM_INT);
            $statement->bindValue(':higherlimit', (int) $this->getIntervalHigherLimit(), PDO::PARAM_INT);
            
            $statement->setFetchMode(PDO::FETCH_ASSOC);
            
            $statement->execute();
            
            $output = array();
            
            for ($y = (int) $system->getOldestBookmarkCreationYear(); $y <= (int) date('Y'); $y ++) {
                $output[(string) $y] = 0;
            }
            
            foreach ($statement->fetchAll() as $data) {
                $output[$data['year']] = $data['count'];
            }
            
            return $output;
        } catch (Exception $e) {
            $system->reportException(__METHOD__, $e);
        }
    }

    /**
     * Obtient le nombre de signets de la rubrique, sous-rubriques comprises.
     *
     * @return int
     * @since 19/09/2013
     * @version 07/06/2014
     */
    public function countDependentBookmarks()
    {
        global $system;
        try {
            return $this->getDependentBookmarks()->getSize();
        } catch (Exception $e) {
            $system->reportException(__METHOD__, $e);
        }
    }

    /**
     * Obtient les ressources associées à la rubrique ou à l'une de ses sous-rubriques et pour lesquelles un fil d'info RSS a été renseigné
     *
     * @since 16/06/2007
     * @version 07/06/2014
     */
    public function getDependentBookmarksWithNewsFeed()
    {
        global $system;
        try {
            if (isset($criteria)) {
                $criteria['rss'] = true;
            } else {
                $criteria = array(
                    'rss' => true
                );
            }
            return new BookmarkCollection($this->getDependentBookmarkCollectionStatement($criteria));
        } catch (Exception $e) {
            $system->reportException(__METHOD__, $e);
        }
    }

    /**
     * Obtient le nombre de ressources situées dans la rubrique
     *
     * @version 07/06/2014
     */
    public function countBookmarks()
    {
        global $system;
        try {
            return $this->getBookmarks()->getSize();
        } catch (Exception $e) {
            $system->reportException(__METHOD__, $e);
        }
    }

    /**
     * Supprime la rubrique et son contenu.
     *
     * @return bool
     * @version 28/09/2014
     */
    public function delete()
    {
        global $system;
        try {
            if (! $this->getId()) {
                throw new Exception('La rubrique à retirer n\'a pas d\'identifiant');
            }
            if ($this->deleteDependentBookmarks()) {
                // on supprime les sous-rubriques
                $system->getPdo()->exec('SET SQL_SAFE_UPDATES=0');
                $sql = 'DELETE FROM ' . $system->getTopicTableName() . ' WHERE topic_interval_lowerlimit>=:lower AND topic_interval_higherlimit<=:higher';
                $statement = $system->getPdo()->prepare($sql);
                $i = $this->getInterval();
                $statement->bindValue(':lower', $i->getLowerLimit(), PDO::PARAM_INT);
                $statement->bindValue(':higher', $i->getHigherLimit(), PDO::PARAM_INT);
                if ($statement->execute()) {
                    $system->trimTopicInterval();
                    return true;
                }
                return false;
            }
            throw new Exception('échec de la suppression des ressources associées');
        } catch (Exception $e) {
            $system->reportException(__METHOD__, $e);
            return false;
        }
    }

    /**
     * Transfère le contenu de la rubrique vers un autre emplacement (autre rubrique ou hors rubrique)
     *
     * @param Topic $target            
     * @return bool
     * @version 04/10/2014
     */
    public function sendContentTo(Topic $target)
    {
        $cond1 = $this->countBookmarks() > 0 ? $this->transferBookmarksTo($target) : true;
        $cond2 = $this->hasChild() ? $this->transferChildrenTo($target) : true;
        return $cond1 && $cond2;
    }

    /**
     * Transfère les sous-rubriques de la rubrique dans une autre rubrique
     *
     * @param Topic $topic            
     * @return bool
     * @version 04/10/2014
     */
    public function transferChildrenTo(Topic $topic)
    {
        global $system;
        try {
            if (empty($_SESSION['user_id'])) {
                throw new Exception('L\'utilisateur n\'est pas identifié');
            }
            if (empty($this->id)) {
                throw new Exception('L\'identifiant de la rubrique est inconnu');
            }
            if (! $topic->getId()) {
                throw new Exception('La rubrique de destination n\'est pas éligible');
            }
            if ($this->countChildren() == 0) {
                return true;
            }
            $children_i = $this->getChildrenInterval();
            $i = $topic->getInterval();
            if ($i->isInside($children_i)) {
                throw new Exception('Les sous-rubriques de ' . $this->getName() . ' ne peuvent être déplacées dans ' . $topic->getName() . ' car cette rubrique se trouve dans l\'une des sous-rubriques.');
            } else {
                $i = $this->getChildren()->getIterator();
                do {
                    $i->current()->transferTo($topic);
                } while ($i->next());
                return true;
            }
        } catch (Exception $e) {
            $system->reportException(__METHOD__, $e);
            return false;
        }
    }

    /**
     * Transfère les ressources de la rubrique (premier niveau) dans une autre rubrique
     *
     * @param Topic $target            
     * @return bool
     * @since 25/03/2007
     * @version 04/10/2014
     */
    public function transferBookmarksTo(Topic $target)
    {
        global $system;
        try {
            if (empty($_SESSION['user_id'])) {
                throw new Exception('L\'utilisateur n\'est pas identifié');
            }
            if (empty($this->id)) {
                throw new Exception('L\'identifiant de la rubrique d\'origine est inconnu');
            }
            if (! $target->getId()) {
                throw new Exception('La rubrique de destination n\'est pas éligible');
            }
            if ($this->countBookmarks() == 0) {
                return true;
            }
            $sql = 'UPDATE ' . Bookmark::getTableName() . ' SET topic_id = :target_id';
            $sql .= ' WHERE topic_id = :id';
            
            $statement = $system->getPdo()->prepare($sql);
            
            $statement->bindValue(':target_id', (int) $target->getId(), PDO::PARAM_INT);
            $statement->bindValue(':id', (int) $this->getId(), PDO::PARAM_INT);
            
            if ($statement->execute()) {
                unset($this->bookmarks);
                return true;
            }
            return false;
        } catch (Exception $e) {
            $system->reportException(__METHOD__, $e);
            return false;
        }
    }

    /**
     * Fixe la valeur d'un attribut
     *
     * @version 04/03/2006
     */
    public function setAttribute($name, $value)
    {
        // echo __METHOD__.' : '.$name.'->'.$value.'<br/>';
        $value = trim($value);
        $value = html_entity_decode($value, ENT_QUOTES, 'UTF-8');
        return $this->{$name} = $value;
    }

    public function getAttribute($name)
    {
        if (is_null($this->$name)) {
            $this->hydrate();
        }
        return $this->$name;
    }

    /**
     * Obtient l'intitulé de la rubrique.
     *
     * @return string
     */
    public function getTitle()
    {
        if (! isset($this->title)) {
            $this->title = $this->getDataFromBase(array(
                'topic_title'
            ));
        }
        return ucfirst($this->title);
    }

    /**
     * Alias de la fonction getTitle()
     *
     * @return string
     * @since 27/02/2010
     */
    public function getName()
    {
        return $this->getTitle();
    }

    public function nameToHtml()
    {
        return ToolBox::toHtml($this->getName());
    }

    /**
     * Fixe le titre de la rubrique
     *
     * @since 11/09/2006
     */
    public function setTitle($input)
    {
        return $this->setAttribute('title', $input);
    }

    public function getUrl()
    {
        global $system;
        return $system->getTopicUrl($this);
    }

    /**
     * Obtient le nombre de ressources regroupées dans la rubrique.
     *
     * @return int
     * @since 2009-01-29
     * @version 07/06/2014
     */
    public function getWeight()
    {
        global $system;
        try {
            if (! isset($this->weight) && $this->hasId()) {
                $sql = 'SELECT t1.topic_id, COUNT(IF(t2.bookmark_id IS NULL,0,1)) AS weight';
                $sql .= ' FROM ' . Topic::getTableName() . ' AS t1';
                $sql .= ' LEFT OUTER JOIN ' . Bookmark::getTableName() . ' AS t2 USING(topic_id)';
                $where = array();
                $where[] = 't1.topic_interval_lowerlimit >= :lowerlimit';
                $where[] = 't1.topic_interval_higherlimit <= :higherlimit';
                if (empty($_SESSION['user_id'])) {
                    $where[] = 't1.topic_private = 0';
                    $where[] = 't2.bookmark_private = 0';
                }
                $sql .= ' WHERE ' . implode(' AND ', $where);
                $sql .= ' GROUP BY t1.topic_id';
                
                $statement = $system->getPdo()->prepare($sql);
                
                $statement->bindValue(':lowerlimit', (int) $this->getIntervalLowerLimit(), PDO::PARAM_INT);
                $statement->bindValue(':higherlimit', (int) $this->getIntervalHigherLimit(), PDO::PARAM_INT);
                
                $statement->execute();
                
                $this->setWeight($statement->fetchColumn());
            }
            return $this->weight;
        } catch (ErrorException $e) {
            $system->reportException(__METHOD__, $e);
        }
    }

    /**
     * Obtient le lien HTML permettant d'accéder à la rubrique
     */
    public function getHtmlLink($target = '_self')
    {
        if (empty($this->id)) {
            return false;
        } else {
            $cssClass = $this->isPrivate() ? 'lockedTopic' : 'unlockedTopic';
            $html = '<a target="' . $target . '" href="' . ToolBox::toHtml($this->getUrl()) . '" class="' . $cssClass . '">';
            $html .= $this->getHtmlTitle();
            $html .= '</a>';
            /*
             * if (isset($this->weight)) { $html.= ' <small>('.$this->weight.')</small>'; }
             */
            return $html;
        }
    }

    /**
     * Obtient le Html permettant d'afficher l'intervalle
     *
     * @since 05/03/2007
     */
    public function intervalToHtml()
    {
        return '<small>(' . $this->getIntervalLowerLimit() . '-' . $this->getIntervalHigherLimit() . ')</small>';
    }

    /**
     * Obtient la description de la rubrique
     *
     * @return String
     * @since 04/03/2006
     */
    public function getDescription()
    {
        return isset($this->description) ? $this->description : NULL;
    }

    /**
     * Fixe la description de la rubrique
     *
     * @since 11/09/2006
     */
    public function setDescription($input)
    {
        return $this->setAttribute('description', $input);
    }

    /**
     * Obtient la description de la rubrique au format Html
     *
     * @return String
     * @version 15/09/2006
     */
    public function getHtmlDescription()
    {
        return ! empty($this->description) ? '<div class="description">' . ucfirst(nl2br(ToolBox::toHtml($this->description))) . '</div>' : NULL;
    }

    /**
     *
     * @since 10/08/2013
     */
    public function getHtmlTitle()
    {
        // $i = $this->getInterval ();
        // return ToolBox::toHtml ( ucfirst ( $this->title ) ) . ' (' . $i->getLowerLimit() . ' - ' . $i->getHigherLimit() . ')';
        return ToolBox::toHtml($this->title);
    }

    /**
     * Obtient le contenu de la rubrique au format NETSCAPE-bookmark-file-1
     *
     * @return string
     * @version 04/10/2014
     */
    public function getNetscapeBookmarksFileOutput()
    {
        $output = '<dt><h3 folded>' . ToolBox::toHtml(ucfirst($this->getAttribute('title'))) . '</h3>' . "\n";
        if (isset($this->description)) {
            $output .= '<dd>' . ToolBox::toHtml($this->description) . "\n";
        }
        if (! isset($this->children)) {
            $this->buildDescendantsTree();
        }
        $this->getBookmarks();
        
        $output .= '<dl><p>' . "\n";
        if ($this->countChildren() > 0) {
            $i = $this->getChildren()->getIterator();
            do {
                $output .= $i->current()->getNetscapeBookmarksFileOutput();
            } while ($i->next());
        }
        
        if ($this->countBookmarks() > 0) {
            $i = $this->getBookmarks()->getIterator();
            do {
                $output .= $i->current()->getNetscapeBookmarksFileOutput();
            } while ($i->next());
        }
        
        $output .= '</dl><p>' . "\n";
        
        return $output;
    }

    /**
     * Accès à la représentation de la rubrique en base de données
     *
     * @version 04/06/2007
     */
    public function toDB()
    {
        global $system;
        try {
            $settings = array();
            if ($this->title) {
                $settings[] = 'topic_title = :title';
            }
            
            $settings[] = $this->description ? 'topic_description = :description' : 'topic_description = NULL';
            
            if ($this->image_url) {
                $settings[] = 'topic_image_url = :image_url';
            }
            if (isset($this->privacy)) {
                $settings[] = 'topic_private = :privacy';
            }
            if (! $this->id) {
                $settings[] = 'topic_creation_date=NOW()';
                $settings[] = 'user_id = :user_id';
            }
            $sql = ! empty($this->id) ? 'UPDATE ' : 'INSERT INTO ';
            $sql .= self::getTableName() . ' SET ';
            $sql .= implode(',', $settings);
            
            if ($this->id) {
                $sql .= ' WHERE topic_id = :id';
            }
            
            $statement = $system->getPdo()->prepare($sql);
            
            if ($this->title) {
                $statement->bindValue(':title', $this->title, PDO::PARAM_STR);
            }
            
            if ($this->description) {
                $statement->bindValue(':description', $this->description, PDO::PARAM_STR);
            }
            
            if ($this->image_url) {
                $statement->bindValue(':image_url', $this->image_url, PDO::PARAM_STR);
            }
            
            if (isset($this->privacy)) {
                // si la rubrique est définie comme publique,
                // on vérifie que les rubrique de niveau supérieur soit publique également
                // sinon modification
                if (! $this->isPrivate()) {
                    if ($this->hasPrivateAncestor()) {
                        $this->privacy = 1;
                    }
                }
                $statement->bindValue(':privacy', (int) $this->privacy, PDO::PARAM_INT);
            }
            if (! $this->id) {
                $statement->bindValue(':user_id', (int) $_SESSION['user_id'], PDO::PARAM_INT);
            } else {
                $statement->bindValue(':id', (int) $this->id, PDO::PARAM_INT);
            }
            
            $result = $statement->execute();
            
            if ($result && ! isset($this->id)) {
                $this->id = $system->getPdo()->lastInsertId();
            }
            return $result;
        } catch (Exception $e) {
            $system->reportException(__METHOD__, $e);
            return false;
        }
    }

    /**
     *
     * @since 04/10/2014
     */
    public function saveInterval(Interval $interval)
    {
        global $system;
        try {
            if (! $this->hasId()) {
                throw new Exception('Rubrique non identifiée');
            }
            if (! $interval->areLimitsSet()) {
                throw new Exception('On ne peut sauvegarder un intervalle dont une des bornes n\'est pas fixée');
            }
            $sql = 'UPDATE ' . $system->getTopicTableName() . ' SET topic_interval_lowerlimit=' . $interval->getLowerLimit() . ',topic_interval_higherlimit=' . $interval->getHigherLimit() . ' WHERE topic_id=' . $this->id;
            return $system->getPdo()->query($sql);
        } catch (Exception $e) {
            $system->reportException(__METHOD__, $e);
            return false;
        }
    }

    public function toJsObject()
    {
        return '{title:' . ToolBox::stringToJson($this->getTitle()) . ',weight:' . $this->getWeight() . '}';
    }

    /**
     * Fixe les attributs de la rubrique à partir d'un tableau dont les clés sont normalisées
     *
     * @version 04/10/2014
     */
    public function hydrate($array = NULL, $prefix = 'topic_')
    {
        global $system;
        try {
            if (isset($array) && is_array($array)) {
                $intervalIsProcessed = false;
                foreach ($array as $clé => $valeur) {
                    if (is_null($valeur))
                        continue;
                    if (isset($prefix)) {
                        // on ne traite que les clés avec le préfixe spécifié
                        if (strcmp(iconv_substr($clé, 0, iconv_strlen($prefix)), $prefix) != 0) {
                            continue;
                        }
                        // on retire le préfixe
                        $clé = iconv_substr($clé, iconv_strlen($prefix));
                    }
                    //
                    // Gestion des cas particuliers et règle générale.
                    //
                    if (strcmp($clé, 'private') == 0) {
                        $this->privacy = $valeur;
                    } else {
                        $this->setAttribute($clé, $valeur);
                    }
                }
                return true;
            } elseif ($this->hasId()) {
                return $this->getDataFromBase();
            }
        } catch (Exception $e) {
            $system->reportException(__METHOD__, $e);
            return false;
        }
    }

    /**
     * Renvoi le nom de la table (de la base de données) à laquelle est liées cette classe
     *
     * @since 25/03/2007
     */
    public static function getTableName()
    {
        global $system;
        return $system->getTopicTableName();
    }

    /**
     * Fixe les attributs de la rubrique à partir de son enregistrement en base de données
     *
     * @version 07/06/2014
     */
    public function getDataFromBase($fields = NULL)
    {
        global $system;
        try {
            if ($this->getId()) {
                if (! is_array($fields)) {
                    $fields = array(
                        '*'
                    );
                }
                $sql = 'SELECT ' . implode(',', $fields) . ' FROM ' . self::getTableName() . ' WHERE topic_id=:id';
                $statement = $system->getPdo()->prepare($sql);
                $statement->bindValue(':id', $this->id, PDO::PARAM_INT);
                $statement->setFetchMode(PDO::FETCH_ASSOC);
                $statement->execute();
                return $this->hydrate($statement->fetch());
            }
            return false;
        } catch (Exception $e) {
            $system->reportException(__METHOD__, $e);
        }
    }
}
?>