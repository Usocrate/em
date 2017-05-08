<?php

/**
 * @package usocrate.bookmarks
 * @author Florent Chanavat
 */
class User
{
    private $id;
    private $name;
    private $password;
    private $email;

    public function __construct($id = NULL)
    {
        $this->id = $id;
    }

    /**
     * Renvoi le nom de la table (de la base de données) à laquelle est liées cette classe
     *
     * @since 08/05/2007
     */
    public static function getTableName()
    {
        global $system;
    	return $system->getUserTableName();
    }

    /**
     * Fixe les attributs de l'utilisateur à partir d'un tableau associatif dont les clefs sont normalisées.
     *
     * @return boolean
     * @version 09/05/2014
     */
    public function hydrate($row = NULL)
    {
        global $system;
        if (is_array($row)) {
            if (isset($row['user_id']))
                $this->id = $row['user_id'];
            if (isset($row['username']))
                $this->name = $row['username'];
            if (isset($row['password']))
                $this->password = $row['password'];
            if (isset($row['email']))
                $this->email = $row['email'];
            return true;
        } elseif ($this->id) {
            $statement = $system->getPdo()->query('SELECT * FROM ' . self::getTableName() . ' WHERE user_id=' . $this->id);
            $row = $statement->fetch(PDO::FETCH_ASSOC);
            if (! $row)
                return false;
            return $this->hydrate($row);
        }
        return false;
    }

    /**
     * Demande si l'utilisateur est identifié (supposé enregistré)
     *
     * @since 09/05/2014
     */
    public function isKnown()
    {
        return isset($this->id) && ! empty($this->id);
    }

    /**
     * Demande si l'utilisateur est nouveau (non identifié, supposé non enregistré)
     *
     * @since 09/05/2014
     */
    public function isNew()
    {
        return ! $this->isKnown();
    }

    /**
     * Enregistre les attributs de l'utilisateur en base de données
     *
     * @version 09/05/2014
     */
    public function toDB()
    {
        global $system;
        
        $settings = array();
        if (isset($this->name)) {
            $settings[] = 'username=:name"';
        }
        if (isset($this->password)) {
            $settings[] = 'password=:password';
        }
        if (isset($this->email)) {
            $settings[] = 'email=:email';
        }
        $sql = $this->isKnown() ? 'UPDATE' : 'INSERT INTO';
        $sql .= self::getTableName();
        $sql .= ' SET ' . implode(', ', $settings);
        if ($this->isKnown()) {
            $sql .= ' WHERE user_id=:id';
        }
        
        $statement = $system->getPdo()->prepare($sql);
        
        if (isset($this->name)) {
            $statement->bindValue(':name', $this->name, PDO::PARAM_STR);
        }
        if (isset($this->password)) {
            $statement->bindValue(':password', $this->password, PDO::PARAM_STR);
        }
        if (isset($this->email)) {
            $statement->bindValue(':email', $this->email, PDO::PARAM_STR);
        }
        if ($this->isKnown()) {
            $statement->bindValue(':id', (int) $this->id, PDO::PARAM_INT);
        }
        
        $result = $statement->execute();
        
        if ($result && ! isset($this->id)) {
            $this->id = $system->getPdo()->lastInsertId();
        }
        
        return $result;
    }

    /**
     * Identifie l'utilisateur à partir d'un nom et d'un mot de passe.
     *
     * @version 29/08/2014
     */
    public function authenticate($name, $password)
    {
        global $system;
        try {
            $sql = 'SELECT * FROM ' . self::getTableName() . ' WHERE username=:name AND password=:password';
            $statement = $system->getPdo()->prepare($sql);
            $statement->bindValue(':name', $name, PDO::PARAM_STR);
            $statement->bindValue(':password', $password, PDO::PARAM_STR);
            $statement->execute();
            $row = $statement->fetch(PDO::FETCH_ASSOC);
            print_r($row);
            if ($row) {
                $this->hydrate($row);
                $_SESSION['user_id'] = $this->getId();
                return true;
            }
            return false;
        } catch (Exception $e) {
            $system->reportException(__METHOD__, $e);
            return false;
        }
    }

    /**
     * Obtient une nouvelle session.
     *
     * @return UserSession
     * @since 21/08/2009
     */
    public function getNewSession()
    {
        return UserSession::getNewUserSession($this->id);
    }

    /**
     * Supprime les sessions expirées.
     *
     * @return boolean
     * @since 21/08/2009
     */
    public function deleteExpiredSessions()
    {
        global $system;
        try {
            if (empty($this->id)) {
                throw new Exception('Impossible de supprimer les sessions expirées d\'un utilisateur non identifié.');
            } else {
                return UserSession::deleteUserExpiredSessions($this->id);
            }
        } catch (Exception $e) {
            $system->reportException(__METHOD__, $e);
            return false;
        }
    }

    /**
     * Obtient l'identifiant de l'utilisateur.
     *
     * @return string NULL
     * @since 20/11/2005
     */
    public function getId()
    {
        return isset($this->id) ? $this->id : NULL;
    }

    /**
     * Fixe l'identifiant de l'utilisateur.
     *
     * @since 20/11/2005
     */
    public function setId($input)
    {
        return $this->id = $input;
    }

    /**
     * Obtient le nom de l'utilisateur.
     *
     * @return string NULL
     * @since 20/11/2005
     */
    public function getName()
    {
        return isset($this->name) ? $this->name : NULL;
    }

    /**
     * Fixe le nom de l'utilisateur.
     *
     * @since 20/11/2005
     */
    public function setName($input)
    {
        return $this->name = $input;
    }

    /**
     * Obtient le mot de passe de l'utilisateur.
     *
     * @return string NULL
     * @since 20/11/2005
     */
    public function getPassword()
    {
        return isset($this->password) ? $this->password : NULL;
    }

    /**
     * Fixe le mot de passe de l'utilisateur.
     *
     * @since 20/11/2005
     */
    public function setPassword($input)
    {
        return $this->password = $input;
    }

    /**
     * Obtient l'email de l'utilisateur
     *
     * @return string NULL
     * @since 20/11/2005
     */
    public function getEmail()
    {
        return empty($this->email) ? NULL : $this->email;
    }

    /**
     * Fixe l'email de l'utilisateur
     *
     * @since 20/11/2005
     */
    public function setEmail($input)
    {
        $input = trim( $input );
        $input = strtolower( $input );
        return $this->email = $input;
    }
    
    /**
     * @since 24/11/2016
     */
    public function getHashForGravatar() {
        return md5( $this->email );
    }
}
?>