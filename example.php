<?php
require_once('php-sql-parser.php');
require_once('SetfiveJoinChecker.php');
// QUERY HERE
$sql = 'select * from sf_guard_user u LEFT JOIN sf_guard_user_profile p ON u.id=p.id';

// DB username, password, and database name here
$checker = new SetfiveJoinChecker('myuser','mypassword','mydatabase');
$checker->checkQuery($sql);


// Example of multiple queries being checked.
$queries = array('select * from sf_guard_user u LEFT JOIN sf_guard_user_profile p ON u.id=p.id',
                 'select * from sf_guard_user u LEFT JOIN sf_guard_user_permission p ON u.id=p.user_id');

foreach($queries as $sql)
{
  $checker->checkQuery($sql);
}


