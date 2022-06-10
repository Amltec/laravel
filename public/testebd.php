<?php
$pdo = DB::connection()->getPdo();

if($pdo)
   {
     echo "Connected successfully to database ".DB::connection()->getDatabaseName();
   } else {
     echo "You are not connected to database";
   }
?>
