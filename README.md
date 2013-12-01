sql-join-checker
================

Checks SQL queries to make sure joins are done on foreign keys and not typoed.  This will output something like:

````
Checking query: select * from sf_guard_user u LEFT JOIN sf_guard_user_profile p ON  ON u.id=p.id
                                                                                   
FK not found for u.id (sf_guard_user.id) join on p.id (sf_guard_user_profile.id)! Available Mappings:
                                                                                   
     -sf_guard_user_group.group_id=>sf_guard_group.id                              
     -sf_guard_user_group.user_id=>sf_guard_user.id                                
     -sf_guard_user_permission.permission_id=>sf_guard_permission.id               
     -sf_guard_user_permission.user_id=>sf_guard_user.id                           
     -sf_guard_user_profile.user_id=>sf_guard_user.id                              
     -sf_guard_user_profile.participant_source_id=>participant_source.id  
     
Completed check. 
````

To use this edit the runSetfiveChecker.php file and at the top put the query, your db user, password, and name.  

The class can be easily modified to run through a list of SQL queries so you don't need to edit it each time.
