<?php
require_once('php-sql-parser.php');
// QUERY HERE
$sql = 'select * from sf_guard_user u LEFT JOIN sf_guard_user_profile p ON u.id=p.id';

// DB username, password, and database name here
$checker = new Checker('myuser','mypassword','mydatabase');
$checker->checkQuery($sql);



class Checker
{
  private $fks=array(),$dbUser,$dbPassword,$dbName,$dbHost;
  public function __construct($db_user,$db_password,$db_name,$db_host = 'localhost')
  {
    $this->dbUser = $db_user;
    $this->dbPassword = $db_password;
    $this->dbName = $db_name;
    $this->dbHost = $db_host;
    $this->loadForeignKeysFromDb();
  }

  private function loadForeignKeysFromDb()
  {
    $conn = new PDO('mysql:host='.$this->dbHost.';dbname='.$this->dbName,$this->dbUser,$this->dbPassword);

    $tables=$conn->query('SHOW TABLES');
    // format: table.local column=foreign table.foreign column
    $fkTablesMapping = array();
    foreach($tables as $tableInfo)
    {
      $table = $tableInfo[0];
      $structures = $conn->query('SHOW CREATE TABLE '.$table);
      $createSql = $structures->fetch();
      $createSql = $createSql['Create Table'];
      if(preg_match_all('/.*CONSTRAINT\s`.*FOREIGN KEY \(`([a-zA-Z_]*)`\) REFERENCES `([a-zA-Z_]*)` \(`([a-zA-Z_]*)`.*/',$createSql,$matches,PREG_SET_ORDER))
      {
        foreach($matches as $match)
        {
          $localColumn = $match[1];
          $localColumn = $match[1];
          $this->fks[$table.'.'.$match[1]] = $match[2].'.'.$match[3];
        }
      }
    }
  }

  public function checkQuery($sql)
  {
    echo "Checking query: $sql\n\n";
    $parser = new PHPSQLParser($sql);
    $results = $parser->parsed;
    $this->checkResults($results['FROM']);
    echo "Completed check.\n";
    
  }

  private function checkResults($from)
  {
    $tables = array();
    foreach($from as $r)
    {
      if($r['expr_type'] === 'subquery')
      {
        $this->checkResults($r['sub_tree']['FROM']);
        continue;
      }

      $alias = $r['alias'] ? $r['alias']['name'] : $r['table'];
      $tables[$alias] = $r['table'];
      if($r['ref_type']=='ON')
      {
        $leftSide = $r['ref_clause'][0]['base_expr'];
        $leftSide = explode('.',$leftSide);
        $leftAlias = $leftSide[0];
        $leftColumn = $leftSide[1];

        $rightSide = $r['ref_clause'][2]['base_expr'];
        $rightSide = explode('.',$rightSide);
        $rightAlias = $rightSide[0];
        $rightColumn = $rightSide[1];    
        
        if(!isset($tables[$leftAlias]))
        {
          echo "Left alias not found: $leftAlias.  Available aliases:\n";
          var_dump($tables);
          return;
        }

        if(!isset($tables[$rightAlias]))
        {
          echo "Right alias not found: $rightAlias.  Available aliases:\n";
          var_dump($tables);
          return;
        }

        $join= "$leftAlias.$leftColumn (".$tables[$leftAlias].".$leftColumn) join on $rightAlias.$rightColumn (".$tables[$rightAlias].".$rightColumn)";

        // Did the aliases flip?
        if(!isset($this->fks[$tables[$leftAlias].'.'.$leftColumn])&&isset($this->fks[$tables[$rightAlias].'.'.$rightColumn]))
        {
          $tempAlias = $leftAlias;
          $tempColumn = $leftColumn;

          $leftAlias = $rightAlias;
          $leftColumn = $rightColumn;

          $rightColumn = $tempColumn;
          $rightAlias = $tempAlias;
        }
        
        if(!isset($this->fks[$tables[$leftAlias].'.'.$leftColumn]))
        {
          echo "FK not found for $join! Avialable mappings:\n\n";
          foreach($this->fks as $table=>$fk)
          {
            if(strpos($table,$tables[$leftAlias])!==false)
              echo "     -".$table.'=>'.$fk."\n";
          }
          return;
        }

        if($this->fks[$tables[$leftAlias].'.'.$leftColumn]!=$tables[$rightAlias].'.'.$rightColumn)
        {
          echo "FK does not match for $join.  We have the mapping as ".$tables[$leftAlias].'.'.$leftColumn.'=>'.$this->fks[$tables[$leftAlias].'.'.$leftColumn]."\n";
          return;
        }
      }
    }
  }
}

