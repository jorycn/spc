<?php
$config = array(
  'db_host' => 'localhost',
  'db_name' => 'hanzi',
  'db_username' => 'root',
  'db_passwd' => 'root'
);

class SpcDB {

  private $_db;

  public function __construct()
  {
    global $config;
    if(!$this->_db){
      $_opts_values = array(PDO::ATTR_PERSISTENT=>true,PDO::ATTR_ERRMODE=>2,PDO::MYSQL_ATTR_INIT_COMMAND=>'SET NAMES utf8');
      $dsn = 'mysql:dbname='.$config['db_name'].';host='.$config['db_host'];
      $this->_db = new PDO($dsn, $config['db_username'], $config['db_passwd'], $_opts_values);
    }
  }

  public function pdo_fetch($sql, $params){
    $sth = $this->_db->prepare($sql);
    $sth->execute($params);     
    return $sth->fetch();
  }
  public function pdo_fetchAll($sql, $params){
    $sth = $this->_db->prepare($sql);
    $sth->execute($params);
    return $sth->fetchAll();
  }
  public function pdo_execute($sql, $params = []){
    $sth = $this->_db->prepare($sql);
    if(!empty($params)){
      $sth->execute($params);
    }
    return $sth->execute();
  }
  public function pdo_get($tablename, $params = array(), $fields = array()) {
    $select = '*';
    if (!empty($fields)){
      if (is_array($fields)) {
        $select = '`'.implode('`,`', $fields).'`';
      } else {
        $select = $fields;
      }
    }
    $condition = $this->pdo_implode($params, 'AND');
    $sql = "SELECT {$select} FROM " . $tablename . (!empty($condition['fields']) ? " WHERE {$condition['fields']}" : '') . " LIMIT 1";
    return $this->pdo_fetch($sql, $condition['params']);
  }
  public function pdo_insert($table, $data = array(), $replace = FALSE) {
    $cmd = $replace ? 'REPLACE INTO' : 'INSERT INTO';
    $condition = $this->pdo_implode($data, ',');
    return $this->pdo_execute("$cmd " . $table . " SET {$condition['fields']}", $condition['params']);
  }
  public function pdo_delete($table, $params = array(), $glue = 'AND') {
    $condition = $this->pdo_implode($params, $glue);
    $sql = "DELETE FROM " . $table;
    $sql .= $condition['fields'] ? ' WHERE '.$condition['fields'] : '';
    return $this->pdo_execute($sql, $condition['params']);
  }
  public function pdo_update($table, $data = array(), $params = array(), $glue = 'AND') {
    $fields = $this->pdo_implode($data, ',');
    $condition = $this->pdo_implode($params, $glue);
    $params = array_merge($fields['params'], $condition['params']);
    $sql = "UPDATE " . $table . " SET {$fields['fields']}";
    $sql .= $condition['fields'] ? ' WHERE '.$condition['fields'] : '';
    return $this->pdo_execute($sql, $params);
  }

  public function pdo_implode($params, $glue = ',') {
    $result = array('fields' => ' 1 ', 'params' => array());
    $split = '';
    $suffix = '';
    $allow_operator = array('>', '<', '<>', '!=', '>=', '<=', '+=', '-=', 'LIKE', 'like');
    if (in_array(strtolower($glue), array('and', 'or'))) {
      $suffix = '__';
    }
    if (!is_array($params)) {
      $result['fields'] = $params;
      return $result;
    }
    if (is_array($params)) {
      $result['fields'] = '';
      foreach ($params as $fields => $value) {
        $operator = '';
        if (strpos($fields, ' ') !== FALSE) {
          list($fields, $operator) = explode(' ', $fields, 2);
          if (!in_array($operator, $allow_operator)) {
            $operator = '';
          }
        }
        if (empty($operator)) {
          $fields = trim($fields);
          if (is_array($value)) {
            $operator = 'IN';
          } else {
            $operator = '=';
          }
        } elseif ($operator == '+=') {
          $operator = " = `$fields` + ";
        } elseif ($operator == '-=') {
          $operator = " = `$fields` - ";
        }
        if (is_array($value)) {
          $insql = array();
          foreach ($value as $k => $v) {
            $insql[] = ":{$suffix}{$fields}_{$k}";
            $result['params'][":{$suffix}{$fields}_{$k}"] = is_null($v) ? '' : $v;
          }
          $result['fields'] .= $split . "`$fields` {$operator} (".implode(",", $insql).")";
          $split = ' ' . $glue . ' ';
        } else {
          $result['fields'] .= $split . "`$fields` {$operator}  :{$suffix}$fields";
          $split = ' ' . $glue . ' ';
          $result['params'][":{$suffix}$fields"] = is_null($value) ? '' : $value;
        }
      }
    }
    return $result;
  }

}
