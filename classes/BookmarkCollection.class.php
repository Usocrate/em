<?php

/**
 * Classe permettant de gérer un ensemble de signets
 *
 * @since 01/06/2008
 */
class BookmarkCollection extends Collection
{

    /**
     *
     * @version 29/05/2014
     */
    public function __construct($input = NULL)
    {
        global $system;
        try {
            parent::__construct('Bookmark');
            if ($input instanceof PDOStatement) {
                $input->execute();
                $data = $input->fetchAll();
                foreach ($data as $datum) {
                    $b = new Bookmark();
                    $b->hydrate($datum, 'bookmark_');
                    $this->add($b);
                }
            } elseif (is_array($input)) {
                foreach ($input as $datum) {
                    if ($datum instanceof Bookmark) {
                        $this->add($datum);
                    } else {
                        $b = new Bookmark();
                        $b->hydrate($datum, 'bookmark_');
                        $this->add($b);
                    }
                }
            }
        } catch (Exception $e) {
            $system->reportException(__METHOD__, $e);
        }
    }

    /**
     * Supprime les signets inclus dans la collection.
     *
     * @return bool
     * @since 22/05/2009
     * @version 09/06/2014
     */
    public function delete()
    {
        global $system;
        try {
            if (count($this) > 0) {
                $this->deleteSnapshots();
                $sql = 'DELETE FROM ' . Bookmark::getTableName() . ' WHERE bookmark_id IN(' . $this->getCommaSeparatedIds() . ')';
                return $system->getPdo()->exec($sql);
            }
            return true;
        } catch (Exception $e) {
            $system->reportException(__METHOD__, $e);
            return false;
        }
    }

    /**
     * Suppression des vignettes associées aux signets de la collection
     *
     * @since 22/05/2009
     */
    public function deleteSnapshots()
    {
        $i = $this->getIterator();
        while ($i->current()) {
            $i->current()->deleteSnapshot();
            $i->next();
        }
    }

    /**
     * Tente d'indentifier une collection de signets à partir d'une url.
     *
     * @return BookmarkCollection
     * @since 26/03/2013
     * @version 07/05/2014
     */
    public static function getFromUrl($url)
    {
        global $system;
        try {
            $parsed = parse_url(urldecode($url));
            if (is_array($parsed)) {
                $pattern = '';
                if (isset($parsed['host']))
                    $pattern .= $parsed['host'];
                if (isset($parsed['path']))
                    $pattern .= $parsed['path'];
                if (! empty($pattern)) {
                    $criteria = array();
                    $criteria['bookmark_url_like_pattern'] = $pattern;
                    $statement = $system->getBookmarkCollectionStatement($criteria, 'Alphabetical', 7);
                    return new BookmarkCollection($statement);
                }
            }
        } catch (Exception $e) {
            $system->reportException(__METHOD__, $e);
        }
    }

    /**
     * Tente d'indentifier une collection des signets à partir d'une chaîne de caractère à retrouver dans le titre du signet.
     *
     * @param
     *            $pattern
     * @return BookmarkCollection
     * @since 29/08/2014
     */
    public static function getFromTitle($pattern)
    {
        global $system;
        try {
            $criteria = array();
            $criteria['bookmark_title_like_pattern'] = $pattern;
            $statement = $system->getBookmarkCollectionStatement($criteria, 'Most frequently hit first', 7);
            return new BookmarkCollection($statement);
        } catch (Exception $e) {
            $system->reportException(__METHOD__, $e);
        }
    }
}
?>