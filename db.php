<?php

$dns = "mysql:host=localhost";
$username = 'root';
$password = '';
$database_name = 'e-shop';


/**
 * where
 */
trait where
{
  public function where(string $key, string $value)
  {
    $this->db_query .= " WHERE `$key` = '$value'";

    return $this;;
  }

  public function orWhere(string $key, string $value)
  {
    $this->db_query .= " OR `$key` = '$value'";

    return $this;;
  }
}

/**
 * runQuery functions
 */
trait runQuery
{
  public function get()
  {
    $statement = $this->conn->prepare($this->db_query);
    $statement->execute();

    return $statement->fetch();
  }

  public function getAll()
  {
    $statement = $this->conn->prepare($this->db_query);
    $statement->execute();

    return $statement->fetchAll();
  }

  public function run()
  {
    return $this->conn->prepare($this->db_query)->execute();
  }
}

/**
 * join
 */
trait join
{

  /** 
   * @param array $table_list [$key, $value]
   */
  public function innerJoin(array $table_list)
  {
    if (count($table_list) !== 2) {
      throw new Exception('table list must be two items');
    }

    $select_tables = "'{$table_list[0]}' = '{$table_list[1]}'";

    $this->db_query .= " INNER JOIN `{$this->table_name}` ON " . $select_tables;
    return $this;
  }

  /** 
   * @param array $table_list [$key, $value]
   */
  public function leftJoin(array $table_list)
  {
    if (count($table_list) !== 2) {
      throw new Exception('table list must be two items');
    }

    $select_tables = "'{$table_list[0]}' = '{$table_list[1]}'";

    $this->db_query .= " LEFT JOIN `{$this->table_name}` ON " . $select_tables;
    return $this;
  }

  /** 
   * @param array $table_list [$key, $value]
   */
  public function outerJoin(array $table_list)
  {
    if (count($table_list) !== 2) {
      throw new Exception('table list must be two items');
    }

    $select_tables = "'{$table_list[0]}' = '{$table_list[1]}'";

    $this->db_query .= " FULL OUTER JOIN `{$this->table_name}` ON " . $select_tables;
    return $this;
  }
}

/**
 * queryBy
 */
trait queryBy
{
  /** order by
   * @param string $column_name 
   * @param string  $order_type  ASC | DESC
   */
  public function orderBy(string $column_name, string $order_type = "ASC")
  {
    $this->db_query .= " ORDER BY '$column_name' $order_type ";

    return $this;
  }

  public function groupBy(string $column_name)
  {
    $this->db_query .= " GROUP BY '$column_name'  ";

    return $this;
  }
}

class Database
{
  use where, runQuery, join, queryBy;

  private $conn;
  private $table_name;
  private $db_query;

  public function __construct()
  {
    global $dns, $username, $password, $database_name;

    $this->conn = new PDO($dns, $username, $password, [
      PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
      PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION
    ]);

    // create database
    $this->conn->exec("CREATE DATABASE IF NOT EXISTS `$database_name`; USE `$database_name`");
  }

  public function createTable(string $table_name, array $table_data)
  {
    // create table
    $table_columns = "";
    foreach ($table_data as $key => $value) {
      $table_columns .= "`$key` $value ,";
    }

    $query = "CREATE TABLE IF NOT EXISTS `$table_name` ( 
          `id` INT NOT NULL AUTO_INCREMENT ,
          $table_columns
          `is_active` INT DEFAULT 1 ,
          PRIMARY KEY (`id`));
    )";

    $this->conn->prepare($query)->execute();

    return $this;
  }

  public function table(string $table_name)
  {
    $this->table_name = $table_name;

    return $this;
  }

  public function select(
    ...$table_columns
  ) {
    // check param
    if (count($table_columns)) {
      $columns_name = "";

      foreach ($table_columns as $column) {
        $columns_name .= "'$column' ,";
      }

      // remove last comma
      $columns_name = substr($columns_name, 0, -1);
    } else {
      $columns_name = "*";
    }


    $query = "SELECT $columns_name FROM `{$this->table_name}` ";

    $this->db_query = $query;

    return $this;
  }

  /** insert
   * @param array $table_data [$key=>$value]
   */
  public function insert(array $table_data)
  {
    $columns = '';
    $values = '';

    foreach ($table_data as $key => $value) {
      $columns .= "`$key` ,";
      $values .= "'$value' ,";
    }

    $columns = substr($columns, 0, -1);
    $values = substr($values, 0, -1);

    $this->db_query = "INSERT INTO `{$this->table_name}` ($columns) VALUES ({$values})";

    return $this;
  }

  /** update
   * @param array $table_data [ [$key=>$value] ]
   */
  public function update(array $table_data)
  {
    $params = [];

    foreach ($table_data as $key => $value) {
      $params[] = "`$key` = '$value'";
    }

    $this->db_query  = "UPDATE `{$this->table_name}` SET " . implode(',', $params);

    return $this;
  }

  public function delete()
  {
    $this->db_query = "DELETE FROM `{$this->table_name}` ";

    return $this;
  }

  public function count(string $table_column)
  {
    $this->db_query = " SELECT COUNT('$table_column') FROM `$this->table_name` ";

    return $this;
  }

  public function limit(int $number)
  {
    $this->db_query .= " LIMIT $number ";

    return $this;
  }

  // for testing
  public function getQuery()
  {
    return $this->db_query;
  }
}

$db = new Database();

$db->createTable("users", [
  "name" => "VARCHAR(255)",
  "age"  => "INT"
]);

// use table
$user = $db->table("users");

print_r(
  $user
    ->select('id', 'name')->orderBy('name')->where('id', 'naif')->orWhere('name', 'ali')->getQuery()
);


// print_r(
//   $user
//     ->select('users', ["name" => "naif"])
//     ->where('id', '1')
//     ->orWhere('name', 'ali')
// );

// print_r(
//   $user
//     ->select('users', ['users.id', 'posts.id'])
//     ->innerJoin("customers", ["users.id", "customers.id"])
// );

// print_r(
//   $user
//     ->select('users', ['users.id', 'posts.id'])
//     ->orderBy("customers", "DESC")
// );

// print_r(
//   $user
//     ->count('users', 'id')
//     ->orderBy("id", "DESC")
// );

// print_r(
//   $user
//     ->select('users', ['id'])
//     ->limit(1)
// );


// you need to use run() if you want to run the query
// use get or getAll if you want to get data;