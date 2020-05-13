<?php 
include 'variables.php';
include 'conexion_mysql.php';

mkdir('StoresProcedure',0777);
mkdir('StoresProcedure/AltaBajaModi',0777);
mkdir('StoresProcedure/List',0777);
mkdir('StoresProcedure/Consulta',0777);
$queryTablas=$pdo->query($tablas." ".$db);
while ($array=$queryTablas->fetch()) {
	$nameTabla=$array['Tables_in_'.$db];
    $queryColumns=$pdo->query($columnas." ".$db.".".$array['Tables_in_'.$db]);
	if ($queryColumns->rowCount()>0) {
		#colocar los argumentos
		$varArgs=" ";
		$varCol="";
		$llave=" ";
		$totalFilas=$queryColumns->rowCount();
		$contador=0;
		while ($arr=$queryColumns->fetch()) {
			$contador=$contador+1;
			if ($contador<$totalFilas) {

				$varArgs=$varArgs." IN  "." var_".$arr['Field']." ".$arr['Type'].",";		
				$key=$arr['Key'];
			
				if ($key=='PRI') {
					$llave=$arr['Field'];
				}
			}else{
				$varArgs=$varArgs." IN  "." var_".$arr['Field']." ".$arr['Type'];		
				$key=$arr['Key'];
			
				if ($key=='PRI') {
					$llave=$arr['Field'];
				}
			}
			
		}
		$cantFilas=$queryColumns->rowCount();
		$contador=0;
    	$queryColumns=$pdo->query($columnas." ".$db.".".$array['Tables_in_'.$db]);
		while ($arr=$queryColumns->fetch()) {		
			$contador=$contador+1;
			$key=$arr['Key'];
			if ($contador<$cantFilas) {
				if ($key!='PRI') {
					$varCol=$varCol.$arr['Field'].",";

				}
			}else{
				if ($key!='PRI') {
					$varCol=$varCol.$arr['Field'];
				}
			}
		}
		#valores del insert
    	$queryColumns=$pdo->query($columnas." ".$db.".".$array['Tables_in_'.$db]);
		$valores=" ";
		while ($arr=$queryColumns->fetch()) {		
			$contador=$contador+1;
			$key=$arr['Key'];
			if ($contador<$cantFilas) {
				if ($key!='PRI') {
					$valores=$valores.$arr['Field'].",";

				}
			}else{
				if ($key!='PRI') {
					$valores=$valores.$arr['Field'];
				}
			}
		}
		#todas las columnas valorUpdate
		$queryColumns=$pdo->query($columnas." ".$db.".".$array['Tables_in_'.$db]);
		$valorUpdate=" ";
		$cantFilas=$queryColumns->rowCount();
		$arr=$queryColumns->fetch();
		$nombre_llave=$arr['Field'];
		$contador_update=1;
		while ($arr=$queryColumns->fetch()) {		
			$contador_update=$contador_update+1;
			
			if ($contador_update<$cantFilas) {
				$valorUpdate=$valorUpdate.$arr['Field']."=var_".$arr['Field'].",";
			}if($contador_update==$cantFilas){
				
				$valorUpdate=$valorUpdate.$arr['Field']."=var_".$arr['Field'];
			}
		}
		#armado de listado en tablas relacionadas
		$varRel=" ";
		$queryColumns=$pdo->query($relacionTabla."'".$nameTabla."'");
		if ($queryColumns->rowCount()>0) {
			$count=1;
			$varRel=$varRel." t";
			while ($rel=$queryColumns->fetch()) {
				$count=$count+1;
				$varRel=$varRel." inner join ".$rel['referenced_table_name']." t".strval($count)." on "." t".strval($count).".".$rel['referenced_column_name']."= t.".$rel['column_name'];
			}
		}


		$valorUpdate=$valorUpdate." where ".$nombre_llave."=var_".$nombre_llave;
		$path="StoresProcedure/AltaBajaModi/".$nameTabla."_am_sp.sql";
		$pathL="StoresProcedure/List/".$nameTabla."_l_sp.sql";
		
		crear_archivo($path,$nameTabla,$llave,$varCol,$varArgs,$valores,$valorUpdate,$pdo);
		crear_archivo_l($pathL,$nameTabla,$varCol,$pdo,$varRel);	
	}
}
function crear_archivo($path,$nameTabla,$llave,$varCol,$varArgs,$valores,$valorUpdate,$pdo){
$fh = fopen($path, 'w') or die("Se produjo un error al crear el archivo");
$texto = <<<_END
DELIMITER $$
DROP PROCEDURE IF EXISTS {$nameTabla}_am_sp$$
CREATE PROCEDURE {$nameTabla}_am_sp({$varArgs})
BEGIN
	IF not EXISTS(SELECT 1 FROM {$nameTabla} where {$llave}=var_{$llave}) then
		insert into {$nameTabla}({$varCol}) values({$valores});
	else
		update {$nameTabla} set {$valorUpdate};
	END IF;	 
END$$
DELIMITER ;
_END;
fwrite($fh, $texto) or die("No se pudo escribir en el archivo");
fclose($fh);
#$query=$pdo->query($texto);
}

function crear_archivo_l($path,$nameTabla,$columnas,$pdo, $varRel){
$fh = fopen($path, 'w') or die("Se produjo un error al crear el archivo");
$texto = <<<_END
DELIMITER $$
DROP PROCEDURE IF EXISTS {$nameTabla}_l_sp$$

CREATE PROCEDURE {$nameTabla}_l_sp()
BEGIN
	declare cantidad int;
	set cantidad=(select count(*) from {$nameTabla});
	if cantidad >0 then
		select {$columnas} from {$nameTabla} {$varRel};
	else
		select 0 as resultado;
	end if; 	 
END$$
DELIMITER ;
_END;
#$query=$pdo->query($texto);

fwrite($fh, $texto) or die("No se pudo escribir en el archivo");
fclose($fh);	
}
?>
