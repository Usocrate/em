<?php
/**
 * @since 2007-12-27
 * @author Flo
 */
class CollectionIterator implements Iterator
{

    private $var = array();

    /**
     * Constructeur
     *
     * @since 2007-12-27
     * @version 2008-01-02
     */
    public function __construct(&$array)
    {
        if (is_array($array)) {
            $this->var = $array;
            // ToolBox::html_dump($this->var);
        }
    }

    /**
     * Remet le focus sur le premier élément de la collection
     *
     * @since 2007-12-27
     * @author Flo
     */
    public function rewind()
    {
        return reset($this->var);
    }

    /**
     * Obtient l'object sur lequel est le focus
     *
     * @since 2007-12-27
     * @version 2008-01-02
     * @author Flo
     */
    public function current()
    {
        // ToolBox::html_dump(current($this->var));
        return current($this->var);
    }

    public function key()
    {
        return key($this->var);
    }

    /**
     * Obtient l'objet suivant dans la collection et déplace le focus
     *
     * @since 2007-12-27
     * @version 2008-01-02
     */
    public function next()
    {
        return next($this->var);
    }

    public function valid()
    {
        $var = $this->current() !== false;
        return $var;
    }
}
?>