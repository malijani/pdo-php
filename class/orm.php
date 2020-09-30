<?php
namespace db;

include "db.php";

class ORM
{
    // @obj database object
    private $db;

    // @string primary key column
    private $pk;

    // @string tbl name
    private $tbl;

    // @array variable scope param and sql helper
    public $vars;

    /*
     * @void
     *
     * Default Constructor
     *
     * Creates connection to db
     *
     * @param array $data
     * */
    public function __construct($data=[])
    {
        $this->db = new DB();
        $this->vars = $data;
    }


    /*
     * @void
     *
     * Default Set
     *
     * @param string $name
     * @param string $value
     * */
    public function __set($name, $value)
    {
        if (strtolower($name) === $this->pk) {
            $this->vars[$this->pk] = $value;
        } else {
            $this->vars[$name] = $value;
        }
    }

    /*
     * Default Get
     *
     * @return string
     * */
    public function __get($name)
    {
        if (is_array($this->vars)) {
            if (array_key_exists($name, $this->vars)) {
                return $this->vars[$name];
            }
        }
        return null;
    }



    /*TODO: Document all the methods of orm class*/
    public function save($id = "0")
    {
        $this->vars[$this->pk] = (empty($this->vars[$this->pk])) ? $id : $this->vars[$this->pk];

        $fieldsVals = '';
        $columns=array_keys($this->vars);

        foreach ($columns as $column) {
            if ($column !== $this->pk) {
                $fieldsVals .= $column." = :".$column.",";
            }
            $fieldsVals = substr_replace($fieldsVals, '', -1);
        }

        if (count($columns) > 1) {
            $sql = "UPDATE".$this->tbl." SET ".$fieldsVals." WHERE ".$this->pk."= :".$this->pk;
            if ($id === "0" && $this->vars[$this->pk] === "0") {
                unset($this->vars[$this->pk]);
                $sql="UPDATE ".$this->tbl." SET ".$fieldsVals;
            }
            return $this->exec($sql);
        }
        return null;
    }


    /**/
    public function create()
    {
        $bindings = $this->vars;

        if (!empty($bindings)) {
            $fields = array_keys($bindings);
            $fieldsVals = array(implode(",",$fields),":".implode(",:",$fields));
            $sql="INSERT INTO ".$this->tbl." (".$fieldsVals[0].") VALUES (".$fieldsVals[1].")";
        } else {
            $sql="INSERT INTO ".$this->tbl." () VALUES ()";
        }
        return $this->exec($sql);
    }


    /**/
    public function delete($id="")
    {
        $id=(empty($this->vars[$this->pk])) ? $id : $this->vars[$this->pk];

        if (!empty($id)) {
            $sql="DELETE FROM ".$this->tbl." WHERE ".$this->pk."= :".$this->pk." LIMIT 1";
        }

        return $this->exec($sql, array($this->pk=>$id));
    }

    /**/
    public function find($id="")
    {
        $id= (empty($this->vars[$this->pk])) ? $id : $this->vars[$this->pk];

        if (!empty($id)) {
            $sql = "SELECT * FROM ".$this->tbl." WHERE ".$this->pk."= :".$this->pk." LIMIT 1";
            $res = $this->db->row($sql, array($this->pk=>$id));
            $this->vars = ($res != false) ? $res : null;
        }
    }


    /**/
    public function search($fields=array(), $sort=array())
    {
        $bindings=empty($fields) ? $this->vars : $fields;

        $sql="SELECT * FROM ".$this->tbl;

        if (!empty($bindings)) {
            $fieldsVals=[];
            $columns=array_keys($bindings);
            foreach ($columns as $column) {
                $fieldsVals[]=$column." = :".$column;
            }
            $sql.="WHERE ".implode(" AND ", $fieldsVals);
        }

        if (!empty($sort)) {
            $sortVals=[];
            foreach ($sort as $key => $value) {
                $sortVals[]=$key." ".$value;
            }
            $sql.=" ORDER BY ".implode(", ",$sortVals);
        }
        return $this->exec($sql);
    }


    /**/
    public function all()
    {
        return $this->db->query("SELECT * FROM ".$this->tbl);
    }


    /**/
    public function min($field)
    {
        if ($field) {
            return $this->db->single("SELECT min(".$field.") FROM ".$this->tbl);
        }
    }


    /**/
    public function max($field)
    {
        if ($field){
            return $this->db->single("SELECT max(".$field.") FROM ".$this->tbl);
        }
    }

    /**/
    public function avg($field)
    {
        if ($field){
            return $this->db->single("SELECT avg(".$field.") FROM ".$this->tbl);
        }
    }

    /**/
    public function sum($field)
    {
        if ($field){
            return $this->db->single("SELECT sum(".$field.") FROM ".$this->tbl);
        }
    }


    /**/
    public function count($field)
    {
        if ($field){
            return $this->db->single("SELECT count(".$field.") FROM ".$this->tbl);
        }
    }


    /**/
    private function exec($sql, $array = null)
    {
        if ($array !== null) {
            // Get result with $array as parameters
            $res = $this->db->query($sql, $array);
        } else {
            // Get result with $this->vars as parameters
            $res = $this->db->query($sql, $this->vars);
        }

        //  Empty bindings
        $this->vars = [];

        return $res;
    }
}

