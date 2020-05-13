<?php 
include 'conexion_mysql.php';
$columnas="show COLUMNS from ";
$tablas="show full tables from ";
$relacionTabla="SELECT table_name, column_name, referenced_table_name, referenced_column_name FROM INFORMATION_SCHEMA.KEY_COLUMN_USAGE WHERE table_schema = '{$db}' AND referenced_table_name IS NOT NULL and table_name =";






 ?>