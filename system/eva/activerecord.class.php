<?php
namespace Eva;
abstract class ActiveRecord extends Eva {
    public function __call($f, $a) {
        $this->$f = array($a[0]);
        return $this;
    }
    public function cleanup(){
        $this->__data__ = array();
        return $this;
    }
    public function limit($l, $o = NULL) {
        $this->limit = array($l, PDO::PARAM_INT);
        if($o != NULL) $this->offset = array($o, PDO::PARAM_INT);
        return $this;
    }
    public function offset($o) {
        $this->offset = array($o, PDO::PARAM_INT);
        return $this;
    }
    public function select($what = '*'){

        $statement = 'SELECT '.$what.' FROM '.$this->instance;
        unset($this->instance);

        foreach($this->__data__ as $k => $v) {
            if(!is_array($v[0])) continue;
            if($v[1] == PDO::PARAM_INT) {
                foreach($v[0] as $k2 => $v2)
                    if(!is_numeric($v2)) return NULL;
            };
            $this->where = str_replace(':'.$k, join(',', $v[0]), $this->where[0]);
            unset($this->__data__[$k]);
        };
        if(isset($this->where{7}))
            $statement .= ' WHERE '.$this->where;
        unset($this->where);

        if(isset($this->groupby[0])) $statement .= ' GROUB BY '.$this->groupby[0];
        if(isset($this->orderby[0])) $statement .= ' ORDER BY '.$this->orderby[0];
        unset($this->orderby, $this->groupby);

        if($this->limit) {
            if($this->offset) {
                $this->__data__['limit'][0] = $this->limit[0] * $this->offset[0];
                $statement .= ' LIMIT :limit, :offser';
            } else {
                $statement .= ' LIMIT :limit';
            };
        };
        $sth = \app::$db->prepare($statement);

        foreach($this->__data__ as $k => $v) {
            $sth->bindParam(':'.$k, $v[0], $v[1] ? $v[1] : PDO::PARAM_STR);
        };
        $sth->execute();
        $this->cleanup();
        return $sth->fetchAll(PDO::FETCH_ASSOC);
    }
};

/// 2014 | AeonRUSH |