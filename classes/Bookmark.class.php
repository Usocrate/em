<?php

/**
 * @package usocrate.bookmarks
 * @author Florent Chanavat
 */
class Bookmark implements CollectibleElement
{

    public $id;

    public $title;

    public $url;

    public $type;

    public $rss_url;

    public $description;

    public $creator;

    public $publisher;

    public $language;

    public $creation_date;

    public $lasthit_date;

    public $hit_frequency;

    public $lastedit_date;

    private $lastedit_user_id;
    
    private $lastfocus_date;

    public $privacy;

    public $heat;

    private $user_id;

    public $snapshot_filename;
    
    public $lastBookmarkUsedAsLocationRef;

    /**
     * un objet de type topic.
     */
    public $topic;

    private $login;

    private $password;

    /**
     *
     * @param string $id            
     * @version 22/06/2014
     */
    public function __construct($id = NULL)
    {
        if (isset($id)) {
            $this->id = $id;
            $this->hydrate();
        }
    }

    /**
     * Fixe la valeur d'une attribut
     *
     * @version 17/10/2007
     */
    public function setAttribute($name, $value)
    {
        $value = trim($value);
        $value = strip_tags($value);
        $value = html_entity_decode($value, ENT_QUOTES, 'UTF-8');
        return $this->{$name} = $value;
    }

    /**
     * Fixe la valeur d'un attribut de type url pour le signet
     *
     * @param string $name            
     * @param string $value            
     * @since 20/11/2007
     */
    public function setUrlAttribute($name, $value)
    {
        if (! empty($value) && ! preg_match("/^[a-zA-Z]+\:/", $value) && preg_match("/^[^\/]+\./", $value)) {
            $this->setAttribute($name, 'http://' . $value);
        } else {
            $this->setAttribute($name, $value);
        }
    }

    /**
     * Obtient la valeur d'un attribut du signet
     *
     * @param string $name            
     * @return mixed
     * @version 20/11/2007
     */
    public function getAttribute($name)
    {
        if (is_null($this->$name)) {
            $this->hydrate();
        }
        return $this->$name;
    }

    /**
     *
     * @since 30/10/2009
     * @version 09/06/2014
     */
    private function getDataFromBase($fields = NULL)
    {
        global $system;
        try {
            if ($this->hasId()) {
                if (! is_array($fields)) {
                    $fields = array(
                        '*'
                    );
                }
                $sql = 'SELECT ' . implode(',', $fields) . ' FROM ' . self::getTableName() . ' WHERE bookmark_id=:id';
                $statement = $system->getPdo()->prepare($sql);
                $statement->bindValue(':id', (int) $this->id, PDO::PARAM_INT);
                $statement->execute();
                return $statement->fetch(PDO::FETCH_ASSOC);
            }
            return false;
        } catch (Exception $e) {
            $system->reportException(__METHOD__, $e);
        }
    }

    /**
     * Renvoie l'identifiant du signet.
     *
     * @return int
     * @since 05/11/2005
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * Demande si le signet est identifié
     *
     * @since 01/10/2010
     * @version 09/05/2014
     * @return bool
     */
    public function hasId()
    {
        return isset($this->id) && ! empty($this->id);
    }

    /**
     * Demande si le signet est connu (supposé enregistré)
     *
     * @since 09/05/2014
     */
    public function isKnown()
    {
        return $this->hasId();
    }

    /**
     * Demande si le signet est nouveau (non identifié, supposé non enregistré)
     *
     * @since 09/05/2014
     */
    public function isNew()
    {
        return ! $this->hasId();
    }

    /**
     * Fixe l'indentifiant du signet.
     *
     * @since 11/09/2006
     */
    public function setId($input)
    {
        return $this->setAttribute('id', $input);
    }

    /**
     * Renvoie l'intitulé de la ressource.
     *
     * @return string
     * @since 25/06/2006
     */
    public function getTitle()
    {
        return $this->getAttribute('title');
    }

    public function getName()
    {
        return $this->getTitle();
    }

    /**
     * Fixe le titre de la ressource.
     *
     * @param string $input            
     * @since 2008-05-14
     */
    public function setTitle($input)
    {
        return $this->setAttribute('title', $input);
    }

    /**
     * Renvoie la date d'enregistrement du signet.
     *
     * @return string NULL
     * @since 26/11/2005
     */
    public function getCreationDate()
    {
        if (! ($this->creation_date instanceof DateTime) && isset($this->id)) {
            $dataset = $this->getDataFromBase(array(
                'bookmark_creation_date'
            ));
            $this->hydrate($dataset);
        }
        return $this->creation_date;
    }

    /**
     *
     * @since 02/10/2010
     */
    public function getCreationUnixTimestamp()
    {
        if ($this->getCreationDate() instanceof DateTime) {
            return strtotime($this->getCreationDate()->format(DateTime::RFC1123));
        }
    }

    /**
     * Renvoie la date d'enregistrement du signet, au format français.
     *
     * @return string NULL
     * @since 10/05/2006
     */
    public function getCreationDateFr()
    {
        return $this->getCreationDate() ? $this->creation_date->format("d/m/Y") : NULL;
    }

    /**
     * Renvoie la date d'enregistrement du signet, au format français, avec lien vers écran de présentation de l'année.
     *
     * @return string NULL
     * @since 26/12/2010
     */
    public function getHtmlCreationDateFr()
    {
        return $this->getCreationDate() ? $this->creation_date->format("d/m/") . $this->getHtmlLinkToCreationYear() : NULL;
    }

    /**
     *
     * @since 17/08/2010
     */
    public function getCreationYear()
    {
        return $this->getCreationDate() ? $this->creation_date->format("Y") : NULL;
    }

    /**
     * Obtient le lien vers écran de présentation de l'année de création du signet.
     *
     * @return string NULL
     * @since 26/12/2010
     */
    public function getHtmlLinkToCreationYear()
    {
        return $this->getCreationDate() ? Year::getHtmlLinkToYearDoc($this->creation_date->format("Y")) : NULL;
    }

    /**
     * Fixe la date de création du signet à partir d'un timestamp unix
     *
     * @param int $input            
     * @since 30/05/2008
     * @version 02/10/2010
     */
    public function setCreationDateFromUnixTime($input)
    {
        $date = new DateTime(date('Y-m-d', $input));
        if ($date instanceof DateTime) {
            $this->creation_date = $date;
            return true;
        }
        return false;
    }

    /**
     * Obtient le nom du créateur de la ressource.
     *
     * @since 20/08/2006
     */
    public function getCreator()
    {
        if (! isset($this->creator) && isset($this->id)) {
            $dataset = $this->getDataFromBase(array(
                'bookmark_creator'
            ));
            $this->hydrate($dataset);
        }
        return $this->creator;
    }

    /**
     * Fixe l'auteur de la ressource.
     *
     * @param string $input            
     * @since 2008-05-14
     */
    public function setCreator($input)
    {
        return $this->setAttribute('creator', $input);
    }

    /**
     * Obtient le nom de l'éditeur de la ressource.
     *
     * @since 20/08/2006
     */
    public function getPublisher()
    {
        if (! is_string($this->publisher) && isset($this->id)) {
            $dataset = $this->getDataFromBase(array(
                'bookmark_publisher'
            ));
            $this->hydrate($dataset);
        }
        return $this->publisher;
    }

    /**
     * Fixe l'éditeur de la ressource.
     *
     * @param string $input            
     * @since 2008-05-14
     */
    public function setPublisher($input)
    {
        return $this->setAttribute('publisher', $input);
    }

    /**
     * Obtient le langage utilisé par la ressource.
     *
     * @since 11/09/2006
     */
    public function getLanguage()
    {
        if (! isset($this->language) && isset($this->id)) {
            $dataset = $this->getDataFromBase(array(
                'bookmark_language'
            ));
            $this->hydrate($dataset);
        }
        return $this->language;
    }

    /**
     * Fixe la langue utilisée par la ressource.
     *
     * @param string $input            
     * @since 2008-05-14
     */
    public function setLanguage($input)
    {
        return $this->setAttribute('language', $input);
    }

    /**
     * Obtient la confidentialité du signet.
     *
     * @return int
     * @since 05/11/2005
     */
    public function getPrivacy()
    {
        if (! isset($this->privacy) && isset($this->id)) {
            $dataset = $this->getDataFromBase(array(
                'bookmark_private'
            ));
            $this->hydrate($dataset);
        }
        return $this->privacy;
    }

    /**
     * Indique si le signet est confidentiel
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
     * Indique si on peut considérer la ressource comme inactive selon l'activité qui y est associée.
     *
     * @return bool
     * @since 28/05/2012
     */
    public function isInactive()
    {
        $ref_unixtimestamp = time() - ACTIVITY_THRESHOLD2 * 3600 * 24;
        return $this->getLastHitUnixTimestamp() === NULL || ($this->getLastHitUnixTimestamp() < $ref_unixtimestamp && $this->getLastEditUnixTimestamp() < $ref_unixtimestamp && $this->getCreationUnixTimestamp() < $ref_unixtimestamp);
    }

    /**
     * Indique si l'éditeur de la ressource est connu
     *
     * @since 12/2010
     * @version 07/2017
     */
    public function isPublisherKnown() {
        return is_string($this->getPublisher()) && !empty($this->publisher);
    }

    /**
     * Indique si la ressource est majeure ou pas.
     *
     * @return boolean
     * @since 06/2012
     * @version 09/2014
     */
    public function isHot() {
        global $system;
        return $this->getHitFrequency() >= $system->getHotBookmarkHitFrequency();
    }

    /**
     * Fournit l'URL de la ressource.
     *
     * @return string
     * @version 12/03/2006
     */
    public function getUrl()
    {
        if (! isset($this->url) && isset($this->id)) {
            $dataset = $this->getDataFromBase(array(
                'bookmark_url'
            ));
            $this->hydrate($dataset);
        }
        return $this->url;
    }

    /**
     * Fournit le type schema.org de la ressource.
     *
     * @return string
     * @version 22/06/2014
     */
    public function getType()
    {
        if (! isset($this->type) && isset($this->id)) {
            $dataset = $this->getDataFromBase(array(
                'bookmark_type'
            ));
            $this->hydrate($dataset);
        }
        return $this->type;
    }

    /**
     * Obtient la liste des types de contenus décrits par Schema.org (http://schema.rdfs.org/all-classes.csv)
     *
     * @since 22/06/2014
     */
    public static function getTypeOptionsFromSchemaRdfsOrg()
    {
        global $system;
        try {
            $filename = $system->getConfigDirectoryPath() . '/schema.rdfs.org/all-classes.csv';
            if (! file_exists($filename)) {
                throw new Exception('Le fichier indiqué est inexistant.');
            }
            if (! is_readable($filename)) {
                throw new Exception('Le fichier indiqué est illisibile.');
            }
            
            $header = NULL;
            $data = array();
            if (($handle = fopen($filename, 'r')) === FALSE) {
                throw new Exception('Le fichier ne peut être ouvert.');
            }
            
            while (($row = fgetcsv($handle)) !== FALSE) {
                if (! $header) {
                    $header = $row;
                } else {
                    $data[] = array_combine($header, $row);
                }
            }
            fclose($handle);
            return $data;
        } catch (Exception $e) {
            $system->reportException(__METHOD__, $e);
            return false;
        }
    }

    /**
     * Indique si l'url de la ressource est connu
     *
     * @since 09/07/2009
     */
    public function hasUrl()
    {
        return $this->getUrl();
    }

    /**
     * Fixe l'url de la ressource.
     *
     * @param string $input            
     * @since 2008-05-14
     */
    public function setUrl($input)
    {
        return $this->setUrlAttribute('url', $input);
    }

    /**
     * Obtient l'identifiant à utiliser pour authentification auprès de la ressource
     *
     * @return String
     * @since 11/09/2006
     */
    public function getLogin()
    {
        if (! isset($this->login) && isset($this->id)) {
            $dataset = $this->getDataFromBase(array(
                'bookmark_login'
            ));
            $this->hydrate($dataset);
        }
        return $this->login;
    }

    /**
     * Obtient le mot de passe à utiliser pour authentification auprès de la ressource
     *
     * @return String
     * @since 11/09/2006
     */
    public function getPassword()
    {
        if (! isset($this->password) && isset($this->id)) {
            $dataset = $this->getDataFromBase(array(
                'bookmark_password'
            ));
            $this->hydrate($dataset);
        }
        return $this->password;
    }

    /**
     * La méthode à utiliser pour supprimer un signet, son historique de consultation, sa vignette.
     *
     * @return boolean
     * @version 05/2009
     */
    public function delete()
    {
        if ($this->removeHitsFromDB() && $this->deleteSnapshot()) {
            return $this->removeFromDB();
        }
        return false;
    }

    public function getHitUrl()
    {
        global $system;
        return $system->getProjectUrl() . '/bookmark_hit.php?bookmark_id=' . $this->id;
    }

    /**
     * Obtient la fréquence de consultation globale du signet (jour de consultation depuis enregistrement).
     *
     * @since 07/2011
     * @version 05/2014
     */
    public function getHitFrequency()
    {
        global $system;
        if (! isset($this->hit_frequency)) {
            try {
                $sql = 'SELECT (COUNT(DISTINCT(DATE(h.hit_date)))/(DATEDIFF(NOW(),b.bookmark_creation_date)+1))';
                $sql .= ' FROM ' . $system->getBookmarkTableName() . ' AS b';
                $sql .= ' LEFT OUTER JOIN ' . $system->getHitTableName() . ' AS h ON h.bookmark_id=b.bookmark_id';
                $sql .= ' GROUP BY b.bookmark_id';
                $statement = $system->getPdo()->query($sql);
                $this->hit_frequency = $statement->fetchColumn();
            } catch (Exception $e) {
                $system->reportException(__METHOD__, $e);
            }
        }
        return $this->hit_frequency;
    }

    /**
     * Obtient la fréquence de consultation globale du signet, exprimée en pourcentage.
     *
     * @since 15/08/2012
     */
    public function getHitFrequencyAsPercent()
    {
        return round($this->getHitFrequency() * 100, 2) . '%';
    }

    /**
     * Obtient le nombre de jours où le signet a été utilisé depuis son enregistrement.
     *
     * @return boolean
     * @since 25/05/2014
     */
    public function countDayWithHit()
    {
        global $system;
        try {
            if (! isset($this->dayWithHit_count)) {
                $sql = 'SELECT COUNT(DISTINCT(DATE(hit_date))) FROM ' . $system->getHitTableName() . ' WHERE bookmark_id=' . $this->id;
                $this->dayWithHit_count = $system->getPdo()
                    ->query($sql)
                    ->fetchColumn();
            }
            return $this->dayWithHit_count;
        } catch (Exception $e) {
            $system->reportException(__METHOD__, $e);
            return false;
        }
    }

    /**
     * Renvoie, par an le nombre de jours où le signet est consulté.
     *
     * @return array
     * @since 25/01/2013
     * @version 09/05/2014
     */
    public function countDayWithHitYearly()
    {
        global $system;
        try {
            $sql = 'SELECT COUNT(DISTINCT(DATE(hit_date))) AS dayWithHit_nb, YEAR(hit_date) AS year';
            $sql .= ' FROM ' . $system->getHitTableName();
            $sql .= ' WHERE bookmark_id=' . $this->id;
            $sql .= ' GROUP BY YEAR(hit_date) ORDER BY YEAR(hit_date) ASC';
            
            $output = array();
            for ($y = $system->getProjectLaunchYear(); $y <= date('Y'); $y ++) {
                $output[$y] = 0;
            }
            foreach ($system->getPdo()->query($sql) as $row) {
                $output[$row['year']] = $row['dayWithHit_nb'];
            }
            return $output;
        } catch (Exception $e) {
            $system->reportException(__METHOD__, $e);
        }
    }

    /**
     * Renvoie, par trimestre le nombre de jours où le signet est utilisé par un utilisateur authentifié.
     *
     * @return array
     * @since 18/01/2008
     * @version 09/05/2014
     */
    public function countDayWithHitQuaterly($year = NULL)
    {
        global $system;
        try {
            $sql = 'SELECT COUNT(DISTINCT(DATE(hit_date))) AS dayWithHit_nb, YEAR(hit_date) AS year, QUARTER(hit_date) AS quarter';
            $sql .= ' FROM ' . $system->getHitTableName();
            $conditions = array();
            $conditions[] = 'bookmark_id=' . $this->id;
            if (isset($year)) {
                $conditions[] = 'YEAR(hit_date) =:year';
            }
            $sql .= ' WHERE ' . implode(' AND ', $conditions);
            $sql .= ' GROUP BY YEAR(hit_date), QUARTER(hit_date)';
            $sql .= ' ORDER BY YEAR(hit_date) ASC, QUARTER(hit_date) ASC';
            
            $statement = $system->getPdo()->prepare($sql);
            
            $statement->bindValue(':year', $year);
            
            $statement->execute();
            
            $output = array();
            while ($row = $statement->fetch(PDO::FETCH_ASSOC)) {
                /*
                 * on comble les années creuses pour obtenir un continuum depuis la première année de consultation
                 */
                while (isset($year) && $row['year'] - $year > 1) {
                    $year ++;
                    $output[$year] = array_fill(1, 4, 0);
                }
                
                if (! isset($year) || strcmp($row['year'], $year) != 0) {
                    $year = $row['year'];
                    $output[$year] = array_fill(1, 4, 0);
                }
                $output[$year][$row['quarter']] = $row['dayWithHit_nb'];
            }
            /*
             * on comble les années manquantes pour obtenir un continuum jusqu'à l'année courante
             */
            while ($year < date('Y')) {
                $year ++;
                $output[$year] = array_fill(1, 4, 0);
            }
            return $output;
        } catch (Exception $e) {
            $system->reportException(__METHOD__, $e);
        }
    }

    /**
     * Obtient le nombre de consultation pour chacun des N derniers jours
     *
     * @return array
     * @since 01/08/2009
     */
    public function countHitForRecentDays($scope = ACTIVITY_THRESHOLD1)
    {
        global $system;
        try {
            $sql = 'SELECT DATEDIFF(NOW(), hit_date) AS day_index, COUNT(*) AS count';
            $sql .= ' FROM ' . $system->getHitTableName();
            $conditions = array();
            $conditions[] = 'bookmark_id=' . $this->id;
            $conditions[] = 'user_id IS NOT NULL';
            $conditions[] = 'DATEDIFF(NOW(), hit_date) < ' . $scope;
            $sql .= ' WHERE ' . implode(' AND ', $conditions);
            $sql .= ' GROUP BY DATEDIFF(NOW(), hit_date)';
            
            $statement = $system->getPdo()->query($sql);
            
            $output = array();
            for ($i = 0; $i < $scope; $i ++) {
                $output[$i] = 0;
            }
            while ($row = $statement->fetch(PDO::FETCH_ASSOC)) {
                $output[$row['day_index']] = $row['count'];
            }
            return $output;
        } catch (Exception $e) {
            $system->reportException(__METHOD__, $e);
        }
    }

    /**
     * Renvoie la date de la dernière utilisation du signet.
     *
     * @return DateTime
     * @version 05/2021
     */
    public function getLastHitDate()
    {
        global $system;
        try {
            if (! ($this->lasthit_date instanceof DateTime)) {
                $sql = 'SELECT MAX(hit_date) FROM ' . $system->getHitTableName() . ' WHERE bookmark_id=:id GROUP BY bookmark_id';
                
                $statement = $system->getPdo()->prepare($sql);
                $statement->bindValue(':id', $this->id);
                $statement->execute();

                $data = $statement->fetchColumn();
                
                if (! empty($data)) {
                    $this->lasthit_date = new DateTime($data);
                }
            }
            return $this->lasthit_date;
        } catch (Exception $e) {
            $system->reportException(__METHOD__, $e);
        }
    }

    /**
     * Indique si la date de la dernière consultation est connue.
     *
     * @return boolean
     * @since 26/05/2014
     */
    public function isLastHitDateKnown()
    {
        return $this->lasthit_date instanceof DateTime;
    }

    /**
     *
     * @since 02/10/2010
     */
    public function getLastHitUnixTimestamp()
    {
        if ($this->getLastHitDate() instanceof DateTime) {
            return strtotime($this->getLastHitDate()->format(DateTime::RFC1123));
        }
    }

    /**
     * Renvoie la date de la dernière utilisation du signet au format français.
     *
     * @return string NULL
     * @since 26/11/2005
     */
    public function getLastHitDateFr()
    {
        return $this->getLastHitDate() ? $this->lasthit_date->format("d/m/Y") : NULL;
    }

    /**
     * Consigne, en base de données, une utilisation du signet
     *
     * @return boolean
     * @version 18/01/2014
     */
    public function addHit($user_id, $latitude = NULL, $longitude = NULL)
    {
        global $system;
        $sql = 'INSERT INTO ' . $system->getHitTableName() . '(bookmark_id, hit_date, user_id, coords_latitude, coords_longitude) VALUES (:bookmark, NOW(), :user, :latitude, :longitude)';
        $statement = $system->getPdo()->prepare($sql);
        $statement->bindValue(':bookmark', $this->id);
        $statement->bindValue(':user', $user_id);
        $statement->bindValue(':latitude', $latitude);
        $statement->bindValue(':longitude', $longitude);
        return $statement->execute();
    }

    /**
     * Renvoie la date de la dernière édition du signet.
     *
     * @return string
     * @since 10/05/2006
     */
    public function getLastEditDate()
    {
        if (! ($this->lastedit_date instanceof DateTime) && isset($this->id)) {
            $dataset = $this->getDataFromBase(array(
                'bookmark_lastedit_date'
            ));
            $this->hydrate($dataset);
        }
        return $this->lastedit_date;
    }
    /**
     * @since 09/2017
     */
    public function getlastfocusDate() {
      return $this->lastfocus_date;  
    }
    /**
     * @since 09/2017
     */    
    public function getlastfocusDateFr() {
        return $this->getlastfocusDate() ? $this->lastfocus_date->format("d/m/Y") : NULL;
    }    
    /**
     *
     * @since 02/10/2010
     */
    public function getLastEditUnixTimestamp()
    {
        if ($this->getLastEditDate() instanceof DateTime) {
            return strtotime($this->getLastEditDate()->format(DateTime::RFC1123));
        }
    }

    /**
     * Renvoie la date de la dernière édition du signet, au format français.
     *
     * @return string
     * @since 10/05/2006
     */
    public function getLastEditDateFr()
    {
        return $this->getLastEditDate() ? $this->lastedit_date->format("d/m/Y") : NULL;
    }

    /**
     * Fixe la date de dernière édition du signet à partir d'un timestamp unix.
     *
     * @param int $input            
     * @since 30/05/2008
     */
    public function setLastEditDateFromUnixTime($input)
    {
        $date = date('Y-m-d', $input);
        return $this->setAttribute('lastedit_date', $date);
    }

    /**
     * A utiliser pour supprimer de la base de donnée, l'historique des utilisations d'un signet
     *
     * @return boolean
     * @version 09/06/2014
     */
    public function removeHitsFromDB()
    {
        global $system;
        try {
            $sql = 'DELETE FROM ' . $system->getHitTableName() . ' WHERE bookmark_id=:id';
            $statement = $system->getPdo()->prepare($sql);
            $statement->bindValue(':id', $this->id, PDO::PARAM_INT);
            return $statement->execute();
        } catch (Exception $e) {
            $system->reportException(__METHOD__, $e);
            return false;
        }
    }

    /**
     * Fixe le rubrique-mère.
     *
     * @param Topic $topic            
     */
    public function setTopic($topic)
    {
        if ($topic instanceof Topic) {
            $this->topic = $topic;
            return true;
        }
        return false;
    }

    /**
     * Obtient le rubrique-mère
     *
     * @return Topic
     * @since 05/11/2005
     */
    public function getTopic()
    {
        return $this->topic;
    }

    /**
     * Indique si la rubrique dans laquelle est attachée le signet est connue
     *
     * @since 06/08/2012
     */
    public function isTopicKnown()
    {
        return isset($this->topic);
    }

    /**
     * Enregistre en base de données, l'appartenance d'un signet à une rubrique.
     *
     * @return boolean
     * @version 06/2014
     */
    public function updateTopicInDB()
    {
        global $system;
        try {
            if ($this->isKnown()) {
                $sql = 'UPDATE ' . self::getTableName() . ' SET topic_id=:topic_id WHERE bookmark_id=:id';
                $statement = $system->getPdo()->prepare($sql);
                $statement->bindValue(':topic_id', $this->topic->getId(), PDO::PARAM_INT);
                $statement->bindValue(':id', $this->id, PDO::PARAM_INT);
                return $statement->execute();
            }
            return false;
        } catch (Exception $e) {
            $system->reportException(__METHOD__, $e);
            return false;
        }
    }

    /**
     * Obtient l'identifiant de la rubrique-mère.
     *
     * @return int NULL
     * @version 05/11/2005
     */
    public function getTopicId()
    {
        return isset($this->topic) ? $this->topic->getId() : NULL;
    }

    /**
     * Obtient le code Html du lien vers la rubrique à laquelle est attaché le signet.
     *
     * @since 28/05/2012
     * @version 25/05/2014
     */
    public function getHtmlLinkToTopic()
    {
        return isset($this->topic) ? $this->topic->getHtmlLink() : NULL;
    }
    
    /**
     * Indique le dernier signet utilisé comme modèle pour fixer la rubrique du signet courant.
     *
     * @param Bookmark $bookmark
     * @since 13/02/2017
     */
    public function setLastBookmarkUsedAsLocationRef($bookmark)
    {
        if ($bookmark instanceof Bookmark) {
            $this->lastBookmarkUsedAsLocationRef = $bookmark;
            return true;
        }
        return false;
    }    

    /**
     * Obtient les données du signet au format JSON
     *
     * @since 29/06/2007
     * @return string
     * @todo optimisation : à écrire pour que les attributs avec une visibilité 'private' ou 'protected' soient traités
     */
    public function toJson()
    {
        return json_encode($this);
    }

    /**
     * Obtient le lien HTML permettant d'accéder à la ressource.
     *
     * @return String
     * @param $target String
     *            Nom de la frame cible
     * @version 09/12/2010
     */
    public function getHtmlLink($target = '_blank')
    {
        $html = '<a';
        $html .= ' href="' . $this->getHitUrl() . '"';
        $html .= ' rel="nofollow"';
        $html .= ' target="' . $target . '"';
        $html .= ' title="' . ToolBox::toHtml($this->getUrl()) . '"';
        $classes = array(
            'hitTrigger'
        );
        $classes[] = $this->isHot() ? 'hotBookmarkLink' : 'bookmarkLink';
        $html .= ' class="' . implode(' ', $classes) . '"';
        $html .= '>';
        $html .= ToolBox::toHtml(ucfirst($this->getTitle()));
        $html .= '</a>';
        return $html;
    }

    /**
     * Obtient le lien vers les statistiques d'utilisations associées à un signet.
     *
     * @param string $target La fenêtre où afficher les données
     * @since 11/2005
     * @version 03/2008
     */
    public function getHtmlLinkToInfo()
    {
        global $system;
        if ($system->isUserAuthenticated()) {
            $html = '<a href="' . $system->getBookmarkUrl($this) . '">';
            $html .= self::getHtmlInfoIcon();
            $html .= '</a>';
            return $html;
        }
    }

    /**
     * Obtient, au format HTML, l'icône utilisée pour construite un lien vers la page d'information sur le signet
     *
     * @return string
     * @since 11/2010
     * @version 05/2021
     */
    public static function getHtmlInfoIcon()
    {
        return '<i class="fas fa-info-circle"></i>';
    }

    /**
     * Obtient le lien HTML permettant d'éditer le signet.
     *
     * @return String
     * @version 05/2021
     */
    public function getHtmlLinkToEdition($target = '_self', $type = 'icon')
    {
        $params = array(
            'bookmark_id' => $this->getId()
        );
        switch ($type) {
            case 'icon':
                return '<a href="' . self::getEditionUrl($params) . '" target="' . $target . '"><i class="fas fa-edit"></i></a>';
            case 'text':
                return '<a href="' . self::getEditionUrl($params) . '" target="' . $target . '" >éditer</a>';
            case 'mixed':
                return '<a href="' . self::getEditionUrl($params) . '" target="' . $target . '"><i class="fas fa-edit"></i></span> éditer</a>';
        }
    }
    /**
     * @version 06/2017
     */
    public static function getEditionUrl($params = NULL)
    {
        global $system;
        $output = $system->getProjectUrl() . '/bookmark_edit.php';
        if (is_array($params) && sizeof($params) > 0) {
            $output .= '?';
            do {
                $output .= urlencode(key($params)) . '=' . urlencode(current($params));
                if (next($params)) {
                    $output .= '&';
                }
            } while (current($params));
        }
        return $output;
    }

    /**
     * Obtient le lien HTML permettant d'afficher les signets de l'éditeur de la ressources.
     *
     * @return String
     * @since 07/2009
     * @version 05/2012
     */
    public function getHtmlLinkToPublisher()
    {
        if (isset($this->publisher)) {
            $p = new Publisher($this->publisher);
            return $p->getHtmlLinkTo();
        }
    }

    /**
     * Obtient le lien HTML permettant d'afficher les éventuels mots de passe permettant d'accéder à la ressource
     *
     * @return String
     * @param String $target Nom de la frame cible
     * @version 05/2021
     */
    public function getHtmlLinkToPassword($target = '_self', $type = 'icon')
    {
        if ($this->login) {
            switch ($type) {
                case 'icon':
                    return '<a href="' . self::getPasswordUrl() . '" target="' . $target . '" ><i class="fas fa-lock"></i></a>';
                case 'text':
                    return '<a href="' . self::getPasswordUrl() . '" target="' . $target . '" >identifiants</a>';
                case 'mixed':
                    return '<a href="' . self::getPasswordUrl() . '" target="' . $target . '" ><i class="fas fa-lock"></i> identifiants</a>';
            }
        }
    }

    /**
     * Obtient l'url de l'écran permettant d'afficher en clair les identifiants et mot de passe à utiliser avec la ressource
     *
     * @since 11/2012
     * @version 06/2017
     */
    public function getPasswordUrl()
    {
        global $system;
        $output = $system->getProjectUrl() . '/bookmark_account.php';
        $output .= '?bookmark_id=' . $this->getId();
        return $output;
    }

    /**
     * Obtient le lien HTML permettant d'accéder à l'éventuel fil d'info RSS associé à la ressource.
     *
     * @return String
     * @param String $target
     *            Nom de la frame cible
     * @version 16/09/2006
     */
    public function getHtmlLinkToRss($target = '_blank')
    {
        if ($this->rss_url) {
            return '<a href="' . ToolBox::toHtml($this->rss_url) . '" target="' . $target . '" class="rssLink">RSS</a>';
        }
    }

    /**
     * Obtient l'Url du flux RSS éventuellement associé à la ressource.
     *
     * @since 20/08/2006
     */
    public function getRssUrl()
    {
        return isset($this->rss_url) ? $this->rss_url : NULL;
    }

    /**
     * Fixe l'url du fil d'info RSS associé à la ressource.
     *
     * @param string $input            
     * @since 2008-05-14
     */
    public function setRssUrl($input)
    {
        return $this->setUrlAttribute('rss_url', $input);
    }

    /**
     * Obtient la description de la ressource.
     *
     * @since 11/09/2006
     */
    public function getDescription()
    {
        return $this->getAttribute('description');
    }

    /**
     *
     * @since 08/06/2012
     */
    public function hasDescription()
    {
        return is_string($this->getDescription());
    }

    /**
     * Fixe la description de la ressource.
     *
     * @param string $input            
     * @since 2008-05-14
     */
    public function setDescription($input)
    {
        return $this->setAttribute('description', $input);
    }

    /**
     * Indique si la miniature de l'interface web a déjà été enregistrée.
     *
     * @return bool
     * @since 2009-05-22
     */
    public function hasSnapshot()
    {
        global $system;
        return $this->getSnapshotFileName() && is_file($system->getSnapshotsDirectoryPath() . DIRECTORY_SEPARATOR . $this->getSnapshotFileName());
    }

    /**
     * Obtient l'âge de la miniature en jour(s).
     *
     * @return int
     * @since 13/07/2009
     */
    public function getSnapshotAge()
    {
        global $system;
        $snapshot_file_path = $system->getSnapshotsDirectoryPath() . DIRECTORY_SEPARATOR . $this->getSnapshotFileName();
        if (is_file($snapshot_file_path)) {
            $mtime = filemtime($snapshot_file_path);
            if ($mtime !== false) {
                return floor((mktime() - $mtime) / 86400);
            }
        }
    }

    /**
     * Obtient le nom du fichier image représentant l'aperçu de l'interface web de la ressource.
     *
     * @return string
     * @since 22/05/2009
     */
    public function getSnapshotFileName()
    {
        if (! isset($this->snapshot_filename) && isset($this->id)) {
            $dataset = $this->getDataFromBase(array(
                'bookmark_thumbnail_filename'
            ));
            $this->hydrate($dataset);
        }
        return $this->snapshot_filename;
    }

    /**
     *
     * @since 30/10/2009
     */
    public function setSnapshotFileName($input)
    {
        $this->snapshot_filename = $input;
    }

    /**
     *
     * @return string
     * @since 2009-10-30
     */
    private function buildSnapshotFileName($extension = 'jpg')
    {
        return isset($this->id) ? $this->id . '.' . $extension : NULL;
    }

    /**
     * @since 11/06/2016
     */
    public function getSnapshotFromPhantomJS()
    {
        global $system;
        try {
            $phantom_script_name = 'snapshot.js';
            $phantom_script_path = $system->getDirectoryPath() . DIRECTORY_SEPARATOR . 'phantom' . DIRECTORY_SEPARATOR . $phantom_script_name;
            
            if (! is_file($phantom_script_path)) {
                throw new Exception('Script Phantom non trouvé');
            }
            
            $filename = $this->buildSnapshotFilename();
            $file_path = $system->getSnapshotsDirectoryPath() . DIRECTORY_SEPARATOR . $filename;
            $cmd = 'phantomjs ' . $phantom_script_path . ' ' . $this->getUrl() . ' ' . $file_path;
            exec($cmd, $output);
            if (is_file($file_path)) {
                /*
                 * commande imagemagick (redimensionnement)
                 */
                $cmd2 = 'convert ' . $file_path . ' -resize 320X240 ' . $file_path;
                exec($cmd2, $output);
                $this->setSnapshotFileName($filename);
                $this->toDB();
            } else {
                throw new Exception('Echec de l\'enregistrement de l\'aperçu de la ressource');
            }
            return $output;
        } catch (Exception $e) {
            print_r($e->getMessage());
            $system->reportException(__METHOD__, $e);
            return false;
        }
    }

    /**
     * Efface le fichier image représentant l'interface de la ressource.
     *
     * @return bool
     * @since 22/05/2009
     */
    public function deleteSnapshot()
    {
        global $system;
        if ($this->hasSnapshot()) {
            unlink($system->getSnapshotsDirectoryPath() . DIRECTORY_SEPARATOR . $this->getSnapshotFileName());
        }
    }

    /**
     * Obtient un aperçu de l'interface web de la ressource sous forme de balise Html <img>
     *
     * @param string $size            
     * @return string
     */
    public function getSnapshotImgTag()
    {
        if ($this->hasSnapshot() === false) {
            // $this->getSnapshotFromBluga();
            //$this->getSnapshotFromPhantomJS();
        } else {
            return '<img src="' . ToolBox::toHtml($this->getSnapshotUrl()) . '" alt="' . ToolBox::toHtml($this->getTitle()) . '" />';
        }
    }

    /**
     * Obtient un lien vers la ressource sous forme de vignette
     *
     * @return string
     * @version 08/2017
     */
    public function getHtmlSnapshotLink($mode = 'normal')
    {
        global $system;
        $html = strcmp($mode, 'bonus') == 0 ? '<div class="snapshot bonus">' : '<div class="snapshot">';
        $html .= '<a';
        $html .= ' href="' . $this->getHitUrl() . '"';
        $html .= ' rel="nofollow"';
        $html .= ' target="_blank"';
        $html .= ' title="' . ToolBox::toHtml($this->getUrl()) . '"';
        $html .= ' class="hitTrigger"';
        $html .= '>';
        if ($this->hasSnapshot() === true) {
            $html .= $this->getSnapshotImgTag();
        } else {
            $html .= '<img src="' . $system->getImagesUrl() . '/missingSnapshot.svg" alt="" />';
        }
        $html .= '</a>';
        $html .= '</div>';
        return $html;
    }

    public function getSnapshotUrl()
    {
        return $this->getSnapshotAbsoluteUrl();
    }

    /**
     * @version 06/2017
     */
    public function getSnapshotAbsoluteUrl()
    {
        global $system;
        return $system->getProjectUrl() . '/data/snapshots/' . $this->id . '.jpg';
    }

    /**
     * Renvoie une description de la ressource au format HTML
     *
     * @return string
     * @version 09/11/2007
     */
    public function getHtmlDescription()
    {
        return $this->description ? '<p>' . ucfirst(nl2br(ToolBox::toHtml($this->description))) . '</p>' : NULL;
    }

    /**
     * Obtient la resource sous forme de balise HTML LI
     *
     * @return string
     * @since 26/11/2005
     * @version 28/05/2012
     */
    public function getHtmlLi($niv = 'n2')
    {
        $cssClasses = array();
        $cssClasses[] = $this->isPrivate() ? 'lockedBookmark' : 'unlockedBookmark';
        $cssClasses[] = $niv;
        if ($this->isInactive()) {
            $cssClasses[] = 'inactive';
        }
        $html = '<li class="' . implode(' ', $cssClasses) . '">';
        $html .= '<div class="text">';
        $html .= $this->getHtmlLink();
        $html .= ' ' . $this->getHtmlLinkToInfo();
        $dataToDisplay = array();
        if ($this->creator) {
            $dataToDisplay[] = ToolBox::toHtml($this->creator);
        }
        if ($this->isPublisherKnown()) {
            $dataToDisplay[] = $this->getHtmlLinkToPublisher();
        }
        if ($this->getCreationYear()) {
            $dataToDisplay[] = Year::getHtmlLinkToYearDoc($this->getCreationYear());
        }
        /*
         * if ($this->isHot()) { $dataToDisplay[] = $this->getHtmlHitFrequency(); }
         */
        if (count($dataToDisplay)) {
            $html .= '<div class="baseline">';
            $html .= implode(' - ', $dataToDisplay);
            $html .= '</div>';
        }
        $html .= $this->getHtmlDescription();
        $html .= '</div>';
        $html .= '</li>';
        return $html;
    }

    /**
     *
     * @since 15/08/2012
     */
    public function getHtmlHitFrequency()
    {
        $classes = array(
            'kpi'
        );
        if ($this->isHot()) {
            $classes[] = 'hot';
        }
        return '<span title="taux de consultation" class="' . join(' ', $classes) . '">' . $this->getHitFrequencyAsPercent() . '</span>';
    }

    /**
     * Renvoie le signet au format NETSCAPE-bookmark-file-1.
     *
     * @return string
     * @version 02/10/2010
     */
    public function getNetscapeBookmarksFileOutput()
    {
        $output = '<dt>';
        $output .= '<a href="' . $this->url . '"';
        // last_visit
        if ($this->lasthit_date instanceof DateTime) {
            $output .= ' last_visit="';
            list ($day, $month, $year) = explode('/', $this->getLastHitDateFr());
            $output .= mktime(0, 0, 0, $month, $day, $year);
            $output .= '"';
        }
        // add_date
        if ($this->creation_date instanceof DateTime) {
            $output .= ' add_date="';
            $output .= mktime(0, 0, 0, $this->creation_date->format('m'), $this->creation_date->format('d'), $this->creation_date->format('Y'));
            $output .= '"';
        }
        $output .= '>';
        $output .= ucfirst($this->title);
        $output .= '</a>';
        $output .= "\n";
        if (! empty($this->description)) {
            $output .= '<dd>' . $this->description . "\n";
        }
        return $output;
    }

    /**
     * Renvoi le nom de la table (de la base de données) à laquelle est liées cette classe
     *
     * @return string
     * @since 25/03/2007
     * @version 08/05/2007
     */
    public static function getTableName()
    {
        global $system;
        return $system->getBookmarkTableName();
    }

    /**
     * Enregistre les attributs du signet en base de données
     *
     * @version 22/06/2014
     */
    public function toDB()
    {
        global $system;
        
        $settings = array();
        $t = $this->getTopic();
        if ($t instanceof Topic && $t->getId()) {
            $settings[] = 'topic_id=:topic_id';
        }
        if (isset($this->title)) {
            $settings[] = 'bookmark_title=:title';
        }
        if (isset($this->url)) {
            $settings[] = 'bookmark_url=:url';
        }
        if (isset($this->type)) {
            $settings[] = 'bookmark_type=:type';
        }
        if (isset($this->rss_url)) {
            $settings[] = 'bookmark_rss_url=:rss_url';
        }
        if (isset($this->description)) {
            $settings[] = 'bookmark_description=:description';
        }
        if (isset($this->creator)) {
            $settings[] = 'bookmark_creator=:creator';
        }
        if (isset($this->publisher)) {
            $settings[] = 'bookmark_publisher=:publisher';
        }
        if (isset($this->language)) {
            $settings[] = 'bookmark_language=:language';
        }
        if (isset($this->privacy)) {
            $settings[] = 'bookmark_private=:privacy';
        }
        if (isset($this->login)) {
            $settings[] = 'bookmark_login=:login';
        }
        if (isset($this->password)) {
            $settings[] = 'bookmark_password=:password';
        }
        if (isset($this->snapshot_filename)) {
            $settings[] = 'bookmark_thumbnail_filename=:snapshot_filename';
        }
        if (isset($this->lastBookmarkUsedAsLocationRef)) {
            $settings[] = 'lastBookmarkUsedAsLocationRef_id=:lastBookmarkUsedAsLocationRef_id';
        }        
        // si la ressource ne possède pas d'id elle est considérée comme nouvelle (inconnue du système)
        if ($this->isNew()) {
            // même si le signet est nouveau, on peut vouloir forcer les dates de création, mise à jour
            // c'est le cas lors d'une importation à partir d'un document NETSCAPE-Bookmark-file-1
            $settings[] = $this->creation_date instanceof DateTime ? 'bookmark_creation_date=:creation_date' : 'bookmark_creation_date=NOW()';
            $settings[] = $this->lastedit_date instanceof DateTime ? 'bookmark_lastedit_date=:lastedit_date' : 'bookmark_lastedit_date=bookmark_creation_date';
            $settings[] = 'user_id=:user_id';
        } else {
            $settings[] = 'bookmark_lastedit_date=NOW()';
            $settings[] = 'bookmark_lastedit_user_id=:lastedit_user_id';
        }
        $sql = ($this->id) ? 'UPDATE' : 'INSERT INTO';
        $sql .= ' ' . self::getTableName() . ' SET ' . implode(', ', $settings);
        if ($this->hasId())
            $sql .= ' WHERE bookmark_id=:id';
        
        $statement = $system->getPdo()->prepare($sql);
        
        if ($t instanceof Topic && $t->getId()) {
            $statement->bindValue(':topic_id', $t->getId(), PDO::PARAM_INT);
        }
        if (isset($this->title)) {
            $statement->bindValue(':title', $this->title, PDO::PARAM_STR);
        }
        if (isset($this->url)) {
            $statement->bindValue(':url', $this->url, PDO::PARAM_STR);
        }
        if (isset($this->type)) {
            $statement->bindValue(':type', $this->type, PDO::PARAM_STR);
        }
        if (isset($this->rss_url)) {
            $statement->bindValue(':rss_url', $this->rss_url, PDO::PARAM_STR);
        }
        if (isset($this->description)) {
            $statement->bindValue(':description', $this->description, PDO::PARAM_STR);
        }
        if (isset($this->creator)) {
            $statement->bindValue(':creator', $this->creator, PDO::PARAM_STR);
        }
        if (isset($this->publisher)) {
            $statement->bindValue(':publisher', $this->publisher, PDO::PARAM_STR);
        }
        if (isset($this->language)) {
            $statement->bindValue(':language', $this->language, PDO::PARAM_STR);
        }
        if (isset($this->privacy)) {
            $statement->bindValue(':privacy', $this->privacy, PDO::PARAM_INT);
        }
        if (isset($this->login)) {
            $statement->bindValue(':login', $this->login, PDO::PARAM_STR);
        }
        if (isset($this->password)) {
            $statement->bindValue(':password', $this->password, PDO::PARAM_STR);
        }
        if (isset($this->snapshot_filename)) {
            $statement->bindValue(':snapshot_filename', $this->snapshot_filename, PDO::PARAM_STR);
        }
        if (isset($this->lastBookmarkUsedAsLocationRef)) {
            $statement->bindValue(':lastBookmarkUsedAsLocationRef_id', $this->lastBookmarkUsedAsLocationRef->getId(), PDO::PARAM_STR);
        }
        if ($this->isNew()) {
            if ($this->creation_date instanceof DateTime) {
                $statement->bindValue(':creation_date', $this->creation_date->format('Y-m-d H:i:s'), PDO::PARAM_STR);
            }
            if ($this->lastedit_date instanceof DateTime) {
                $statement->bindValue(':lastedit_date', $this->lastedit_date->format('Y-m-d H:i:s'), PDO::PARAM_STR);
            }
            $statement->bindValue(':user_id', $system->getAuthenticatedUserId(), PDO::PARAM_INT);
        } else {
            $statement->bindValue(':lastedit_user_id', $system->getAuthenticatedUserId(), PDO::PARAM_INT);
        }
        if ($this->hasId()) {
            $statement->bindValue(':id', $this->id, PDO::PARAM_INT);
        }
        
        $result = $statement->execute();
        
        if ($result && ! isset($this->id)) {
            $this->id = $system->getPdo()->lastInsertId();
        }
        return $result;
    }

    /**
     * Supprime en base de données, l'enregistrement du signet
     *
     * @return boolean
     * @version 13/01/2014
     */
    public function removeFromDB()
    {
        global $system;
        return ($this->hasId()) ? $system->deleteBookmarkRow($this->id) : false;
    }

    /**
     * Fixe les propriétés du signet à partir d'un tableau de valeurs
     * ou de l'enregistrement du signet en base de données si aucun tableau de valeur n'est fourni
     *
     * @since 20/11/2007
     * @return boolean
     */
    public function hydrate($array = NULL, $prefix = 'bookmark_')
    {
        global $system;
        try {
            if (isset($array) && is_array($array)) {
                foreach ($array as $key => $value) {
                    //echo $key.': '.$value.'<br/>';
                    if (is_null($value))
                        continue;
                    /**
                     * temporaire
                     */
                    if (strcmp($key, 'topic_id') == 0) {
                        $this->topic = new Topic();
                        $this->topic->hydrate($array, 'topic_');
                    }
                    
                    if (isset($prefix)) {
                        // on ne traite que les clés avec le préfixe spécifié
                        if (strcmp(iconv_substr($key, 0, iconv_strlen($prefix)), $prefix) != 0) {
                            continue;
                        }
                        // on retire le préfixe
                        $key = iconv_substr($key, iconv_strlen($prefix));
                    }
                    switch ($key) {
                        // on gère les cas particuliers puis on définit la règle générale
                        case 'lasthit_date':
                            $this->lasthit_date = new DateTime($value);
                            break;
                        case 'hit_frequency':
                            $this->hit_frequency = $value;
                            break;
                        case 'lastedit_date':
                            $this->lastedit_date = new DateTime($value);
                            break;
                        case 'lastfocus_date':
                            $this->lastfocus_date = new DateTime($value);
                            break;                   
                        case 'creation_date':
                            $this->creation_date = new DateTime($value);
                            break;
                        case 'private':
                            $this->privacy = $value;
                            break;
                        case 'rss_url':
                            $this->setUrlAttribute('rss_url', $value);
                            break;
                        case 'topic_id':
                            $this->topic = new Topic();
                            $this->topic->hydrate($array, $prefix . 'topic_');
                            break;
                        case 'url':
                            $this->setUrlAttribute('url', $value);
                            break;
                        case 'thumbnail_filename':
                            $this->snapshot_filename = $value;
                            break;
                        default:
                            $this->setAttribute($key, $value);
                    }
                }
                return true;
            } elseif ($this->id) {
                // on ne transmet pas les données de l'initialisation
                // mais on connaît l'identifiant de la ressource
                $data = $system->getBookmarkData($this->id);
                if (! $data) {
                    throw new Exception('Echec de la récupération des données du signet');
                }
                return $this->hydrate($data, 'bookmark_');
            }
        } catch (Exception $e) {
            $system->reportException(__METHOD__, $e);
            return false;
        }
    }

    /**
     * Renseigne les données du signet à partir du document référencé.
     *
     * @since 28/03/2013
     * @version 22/03/2014
     */
    public function hydrateFromUrl($url = NULL)
    {
        /**
         * tentative de récupération du DOM
         */
        $dom = new DOMDocument();
        error_reporting(E_ERROR); // impossible de garantir que la syntaxe du document cible soit parfaite
        if (isset($url)) {
            $this->setUrl($url);
        }
        $dom->loadHTMLFile($this->getUrl());
        /**
         * traitement balise 'html'
         */
        $tags = $dom->getElementsByTagName('html');
        $t = $tags->item(0);
        if ($t instanceof DOMNode) {
            $lang = $t->attributes->getNamedItem('lang');
            $this->setLanguage($lang);
        }
        /**
         * traitement balise 'title'
         */
        $tags = $dom->getElementsByTagName('title');
        $t = $tags->item(0);
        if ($t instanceof DOMNode) {
            if (strcasecmp($dom->encoding, 'iso-8859-1') == 0) {
                $this->setTitle($t->nodeValue);
            } else {
                $this->setTitle(utf8_decode($t->nodeValue));
            }
        }
        /**
         * traitement balises 'meta'
         */
        $tags = $dom->getElementsByTagName('meta');
        for ($i = 0; $i < $tags->length; $i ++) {
            $t = $tags->item($i);
            $name = $t->attributes->getNamedItem('name');
            $http_equiv = $t->attributes->getNamedItem('http-equiv');
            $content = $t->attributes->getNamedItem('content');
            if ($name instanceof DOMNode && $content instanceof DOMNode) {
                switch (strtolower($name->nodeValue)) {
                    /**
                     * Dublin Core prioritaire
                     */
                    case 'dc_creator':
                        if (strcasecmp($dom->encoding, 'iso-8859-1') == 0) {
                            $this->setCreator($content->nodeValue);
                        } else {
                            $this->setCreator(utf8_decode($content->nodeValue));
                        }
                        break;
                    case 'dc_description':
                        if (strcasecmp($dom->encoding, 'iso-8859-1') == 0) {
                            $this->setDescription($content->nodeValue);
                        } else {
                            $this->setDescription(utf8_decode($content->nodeValue));
                        }
                        break;
                    case 'dc_language':
                        if (strcasecmp($dom->encoding, 'iso-8859-1') == 0) {
                            $this->setLanguage($content->nodeValue);
                        } else {
                            $this->setLanguage(utf8_decode($content->nodeValue));
                        }
                        break;
                    case 'dc_rights':
                        if (strcasecmp($dom->encoding, 'iso-8859-1') == 0) {
                            $this->setPublisher($content->nodeValue);
                        } else {
                            $this->setPublisher(utf8_decode($content->nodeValue));
                        }
                        break;
                    case 'author':
                        if (strcasecmp($dom->encoding, 'iso-8859-1') == 0) {
                            $this->setCreator($content->nodeValue);
                        } else {
                            $this->setCreator(utf8_decode($content->nodeValue));
                        }
                        break;
                    case 'copyright':
                        if (strcasecmp($dom->encoding, 'iso-8859-1') == 0) {
                            $this->setPublisher($content->nodeValue);
                        } else {
                            $this->setPublisher(utf8_decode($content->nodeValue));
                        }
                        break;
                    case 'description':
                        if (strcasecmp($dom->encoding, 'iso-8859-1') == 0) {
                            $this->setDescription($content->nodeValue);
                        } else {
                            $this->setDescription(utf8_decode($content->nodeValue));
                        }
                        break;
                    case 'language':
                        if (strcasecmp($dom->encoding, 'iso-8859-1') == 0) {
                            $this->setLanguage($content->nodeValue);
                        } else {
                            $this->setLanguage(utf8_decode($content->nodeValue));
                        }
                        break;
                }
            }
        }
        /**
         * traitement balises 'link'
         */
        $tags = $dom->getElementsByTagName('link');
        for ($i = 0; $i < $tags->length; $i ++) {
            $t = $tags->item($i);
            $rel = $t->attributes->getNamedItem('rel');
            if ($rel instanceof DOMNode && strcmp($rel->nodeValue, 'alternate') == 0) {
                $type = $t->attributes->getNamedItem('type');
                if ($type instanceof DOMNode && strcmp($type->nodeValue, 'application/rss+xml') == 0) {
                    $href = $t->attributes->getNamedItem('href');
                    if ($href instanceof DOMNode) {
                        if (strcasecmp($dom->encoding, 'iso-8859-1') == 0) {
                            $this->setRssUrl($href->nodeValue);
                        } else {
                            $this->setRssUrl(utf8_decode($href->nodeValue));
                        }
                    }
                }
            }
        }
    }

    /**
     *
     * @since 17/08/2010
     * @version 02/10/2010
     */
    public function moreRecentFirst(Bookmark $b1, Bookmark $b2)
    {
        if ($b1->getCreationUnixTimestamp() == $b2->getCreationUnixTimestamp()) {
            return 0;
        } else {
            return $b1->getCreationUnixTimestamp() > $b2->getCreationUnixTimestamp() ? - 1 : 1;
        }
    }

    /**
     *
     * @since 14/07/2011
     */
    public function higherHitFrequencyFirst(Bookmark $b1, Bookmark $b2)
    {
        if ($b1->getHitFrequency() == $b2->getHitFrequency()) {
            return 0;
        } else {
            return $b1->getHitFrequency() > $b2->getHitFrequency() ? - 1 : 1;
        }
    }
}
?>