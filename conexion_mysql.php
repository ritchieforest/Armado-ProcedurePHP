
		<?php
			// Conexion en PDO
				$db = "veterinary";
				$suser ='root';
				$spass = '';
				$msg='';

				$_SESSION["bd"]="programacion4";

				try {
					$pdo = new PDO("mysql:dbname=veterinary;host=localhost",'root','',array(PDO::ATTR_PERSISTENT => true,
																		PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES utf8"));
					$pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
					$msg='conexion_ok';  
				} catch (PDOException $e) {
					$msg='conexion_cancel: '.$e->getMessage();
					//echo "Error al conectar a la base de datos. ".$e->getMessage();	
				}
				//
		?>