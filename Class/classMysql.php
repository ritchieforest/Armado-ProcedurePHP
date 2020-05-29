<?php 

/**
 * 
 */
class classMysql
{
	var $pdo;
	var $database;
	var $StringColumna;	
	var $StringTabla;
	var $relacionTabla;
	var $lavePrimaria;
	
	function __construct($pdo,$database)
	{
		$this->pdo=$pdo;
		$this->database=$database;
		$this->StringTabla="show full tables from ";
		$this->StringColumna="show COLUMNS from";
		$this->relacionTabla="SELECT table_name, column_name, referenced_table_name, referenced_column_name FROM INFORMATION_SCHEMA.KEY_COLUMN_USAGE WHERE table_schema = '".$database."' AND referenced_table_name IS NOT NULL and table_name ="; 
		$this->listarLlavePrimaria="SELECT COLUMN_NAME,COLUMN_KEY FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA='".$database."' and COLUMN_KEY='PRI' and TABLE_NAME=";
	}
	private function descripcionCamposString($nombre_tabla){
		$listColumn=array();
		$contador=0;
		$query=$this->pdo->query($this->StringColumna.' '.$this->database.'.'.$nombre_tabla);
		if ($query->rowCount()>0) {
			while ($array=$query->fetch()) {
				$tipoDato=explode('(',$array['Type']);
				if ($tipoDato[0]==='varchar' or $tipoDato[0]==='char') {					
					$listColumn[$contador]['nombre_campo']=$array['Field'];
					$contador=$contador+1;
				}
			}
		}
		return $listColumn;
	}
	private function buscadorCampoString(){
		$listColumn=array();
		$contador=0;
		$nombre_tabla=' ';
		$arrayTab=$this->ArrayTabla();
		foreach($arrayTab as $datos){
			$nombre_tabla=$datos['name'];
			$query=$this->pdo->query($this->StringColumna.' '.$this->database.'.'.$nombre_tabla);
			if ($query->rowCount()>0) {
				while ($array=$query->fetch()) {
					$tipoDato=explode('(',$array['Type']);
					if ($tipoDato[0]==='varchar') {					
						$listColumn[$contador]['nombre_campo']=$nombre_tabla.'#'.$array['Field'];
						$contador=$contador+1;
					}
				}
			}
		}
		return $listColumn;
	}
	private function columnasConsulta($tabla){
		$query=$this->pdo->query($this->StringColumna." ".$this->database.".".$tabla);
		$StringColumna=" ";
		$cantidadFilas=$query->rowCount();
		$contador=1;
		if ($query->rowCount()>0) {
			while ($arr=$query->fetch()) {
				if ($contador==$cantidadFilas) {
					$StringColumna=$StringColumna.$arr['Field'];
				}else{
					$StringColumna=$StringColumna.$arr['Field'].",";
				}
				$contador=$contador+1;
			}
		}
		return $StringColumna;

	}
	public function buscadorGeneral($stringConsulta){
		$listDatos=$this->ArrayTabla();
		$listar_consulta=array();
		$nombre_tabla=" ";
		$nombre_campo=" ";
		$where=" ";
		$contadorConsulta=0;
		$contadorWhere=1;
		foreach ($listDatos as $array) {
			$nombre_tabla=$array['name'];
			$campos=$this->descripcionCamposString($nombre_tabla);
			$columnas=$this->columnasConsulta($nombre_tabla);
			$cantidadFilasCampos=count($campos);
			foreach ($campos as $arrayC) {
				if (!empty($arrayC['nombre_campo'])) {
				
					if ($contadorWhere<$cantidadFilasCampos) {
						$where=$where."upper(".$arrayC['nombre_campo'].") like'%".strtoupper($stringConsulta)."%' or ";
						
					}else{
						$where=$where."upper(".$arrayC['nombre_campo'].") like'%".strtoupper($stringConsulta)."%'";
					}
					$contadorWhere=$contadorWhere+1;

				}
			}
			if ($where!=" ") {
				$queryConsulta=$this->pdo->query("select ".$columnas." from ".$nombre_tabla." where ".$where."");
				if ($queryConsulta->rowCount()>0) {
					$queryColumnas=$this->pdo->query($this->StringColumna." ".$this->database.".".$nombre_tabla);
					while ($datosTabla=$queryConsulta->fetch()) {

						while ($listas=$queryColumnas->fetch()) {
							$listar_consulta["'".$nombre_tabla."'"]["'".$listas['Field']."'"]=$datosTabla[$listas['Field']];
							$contadorConsulta=$contadorConsulta+1;	

						}

					}
					
				}
			}
			$contadorWhere=1;
			$where=" ";
			$columnas=" ";
			$nombre_tabla=" ";
			unset($queryConsulta);
			unset($queryColumnas);
			$campos=array();
		}
		return $listar_consulta;
	}
	public function ArrayTabla(){
		$query=$this->pdo->query($this->StringTabla." ".$this->database);
		$totalFilas=$query->rowCount();
		$contador=0;
		$listTabla=array();
		if ($totalFilas>0) {
			while ($arr = $query->fetch()) {
				$listTabla[$contador]['name']=$arr['Tables_in_'.$this->database];
				$contador=$contador+1;
			}
		}
		return $listTabla;
	}
	public function ArrayColumnTabla($tabla){
		$query=$this->pdo->query($this->StringColumna.' '.$this->database.'.'.$tabla);
		$listColumn = array();
		$contador=0;
		if ($query->rowCount()>0) {
			while ($arr=$query->fetch()) {
				$listColumn[$contador]['name']=$arr['Field'];
				$listColumn[$contador]['null']=$arr['Null'];
				$listColumn[$contador]['tipo']=$arr['Type'];
				$listColumn[$contador]['extra']=$arr['Extra'];
				$listColumn[$contador]['defecto']=$arr['Default'];
				$listColumn[$contador]['llave']=$arr['Key'];
				$contador=$contador+1;
			}
		}
		return $listColumn;
	}
	function LlavePrimaria($tabla){
		$query=$this->pdo->query($this->listarLlavePrimaria."'".$tabla."'");
		$llave=' ';
		if ($query->rowCount()>0) {
		while ($arr=$query->fetch()) {
			$llave=$arr['COLUMN_NAME'];
		}}
		return $llave;
	}
	function arrayTablasRelacionadas($tabla){
		$query=$this->pdo->query($this->relacionTabla."'".$tabla."'");
		$listRelacion = array();
		$contador=0;
		if ($query->rowCount()>0) {
			while ($arr=$query->fetch()) {
				$listRelacion[$contador]['name_tabla']=$arr['table_name'];
				$listRelacion[$contador]['name_colum']=$arr['column_name'];		
				$listRelacion[$contador]['ref_table']=$arr['referenced_table_name'];		
				$listRelacion[$contador]['ref_colum']=$arr['referenced_column_name'];
				$contador=$contador+1;		
			}
		}
		return $listRelacion;
	}
	public function crear_am_sql(){
		$listTabla=$this->ArrayTabla();
		$nombre_tabla=" ";
		$argVal=" ";
		$llave=" ";
		$columnas=" ";
		$insert=" ";
		$valorUpdate=" ";
		$contador=1;
		$cantidadFilas=0;
		$index_tabla=0;
		foreach ($listTabla as $array) {
			$nombre_tabla=$array['name'];
			$listColumn=$this->ArrayColumnTabla($nombre_tabla);
			$cantidadFilas=count($listColumn);
			$llave=$this->LlavePrimaria($nombre_tabla);
			foreach ($listColumn as $datosCol) {
				if ($contador<$cantidadFilas) {
					$argVal=$argVal." IN var_".$datosCol['name']." ".$datosCol['tipo'].",";
					if ($datosCol['llave']!='PRI') {
						$columnas=$columnas.$datosCol['name'].",";
						$insert=$insert."var_".$datosCol['name'].",";
						$valorUpdate=$valorUpdate.$datosCol['name']."=var_".$datosCol['name'].",";	
					}

				}else{
					$argVal=$argVal." IN var_".$datosCol['name']." ".$datosCol['tipo'];
					if ($datosCol['llave']!='PRI') {
						$columnas=$columnas.$datosCol['name'];
						$insert=$insert."var_".$datosCol['name'];	
						$valorUpdate=$valorUpdate.$datosCol['name']."=var_".$datosCol['name'];	

					}

				}
				$contador=$contador+1;
			}
		$index_tabla=$index_tabla+1;
		$path='../Resultado/MYSQL/'.$nombre_tabla.'_am_sp.sql';
		$fh = fopen($path, 'w') or die("Se produjo un error al crear el archivo");
		$texto = "
		DELIMITER $$\n
		DROP PROCEDURE IF EXISTS {$nombre_tabla}_am_sp$$\n
		CREATE PROCEDURE {$nombre_tabla}_am_sp({$argVal})\n
		BEGIN\n
			IF not EXISTS(SELECT 1 FROM {$nombre_tabla} where {$llave}=var_{$llave}) then\n
				insert into {$nombre_tabla}({$columnas}) values({$insert});\n
			else\n
				update {$nombre_tabla} set {$valorUpdate} where {$llave}=var_{$llave};\n
			END IF;\n	 
		END$$\n
		DELIMITER ;\n";
		fwrite($fh, $texto) or die("No se pudo escribir en el archivo");
		fclose($fh);
		$argVal=" ";
		$columnas=" ";
		$insert=" ";
		$valorUpdate=" ";
		$llave=" ";
		$nombre_tabla=" ";
		$contador=1;
		$cantidadFilas=0;
		}
	}

	public function crear_l_sp(){
		$listTabla=$this->ArrayTabla();
		$nombre_tabla=" ";
		$llave=" ";
		$columnas=" ";
		$valorRel=" ";
		$contador=1;
		$cont=1;
		$cantidadFilas=0;
		foreach ($listTabla as $array) {
			$nombre_tabla=$array['name'];
			$listColumn=$this->ArrayColumnTabla($nombre_tabla);
			$cantidadFilas=count($listColumn);
			$llave=$this->LlavePrimaria($nombre_tabla);
			$listTablaRel=$this->arrayTablasRelacionadas($nombre_tabla);
			foreach ($listTablaRel as $rel) {
				$valorRel=$valorRel."t inner join ".$rel['ref_table']." t".strval($cont)." on "." t".strval($cont).".".$rel['ref_colum']."= t.".$rel['name_colum'];
				$cont=$cont+1;

			}
			foreach ($listColumn as $datosCol) {
				if ($contador<$cantidadFilas) {
					if ($datosCol['llave']!='PRI') {
						$columnas=$columnas.$datosCol['name'].",";	
					}

				}else{
					if ($datosCol['llave']!='PRI') {
						$columnas=$columnas.$datosCol['name'];
					}

				}
				$contador=$contador+1;
			}
		$path='../Resultado/MYSQL/'.$nombre_tabla.'_l_sp.sql';
		$fh = fopen($path, 'w') or die("Se produjo un error al crear el archivo");
		$texto = "
		DELIMITER $$\n
		DROP PROCEDURE IF EXISTS {$nombre_tabla}_l_sp$$\n

		CREATE PROCEDURE {$nombre_tabla}_l_sp()\n
		BEGIN
			declare cantidad int;\n
			set cantidad=(select count(*) from {$nombre_tabla});\n
			if cantidad >0 then
				select {$columnas} from {$nombre_tabla} {$valorRel};\n
			else\n
				select 0 as resultado;\n
			end if;\n 	 
		END$$\n
		DELIMITER ;\n";
		fwrite($fh, $texto) or die("No se pudo escribir en el archivo");
		fclose($fh);
		$columnas=" ";
		$valorRel=" ";
		$llave=" ";
		$nombre_tabla=" ";
		$contador=1;
		$cantidadFilas=0;
		}	
	}
	public function crear_c_sp(){
		$listTabla=$this->ArrayTabla();
		$nombre_tabla=" ";
		$llave=" ";
		$columnas=" ";
		$valorRel=" ";
		$contador=1;
		$cont=1;
		$cantidadFilas=0;
		foreach ($listTabla as $array) {
			$nombre_tabla=$array['name'];
			$listColumn=$this->ArrayColumnTabla($nombre_tabla);
			$cantidadFilas=count($listColumn);
			$llave=$this->LlavePrimaria($nombre_tabla);
			$listTablaRel=$this->arrayTablasRelacionadas($nombre_tabla);
			foreach ($listTablaRel as $rel) {
				$valorRel=$valorRel."t inner join ".$rel['ref_table']." t".strval($cont)." on "." t".strval($cont).".".$rel['ref_colum']."= t.".$rel['name_colum'];
				$cont=$cont+1;
				$listColumnT=$this->ArrayColumnTabla($rel['ref_table']);
			foreach ($listColumnT as $datosCol) {
				if ($contador<$cantidadFilas) {
					if ($datosCol['llave']!='PRI') {
						$columnas=$columnas.'t'.strval($cont).".".$datosCol['name'].",";	
					}

				}else{
					if ($datosCol['llave']!='PRI') {
						$columnas=$columnas.'t'.strval($cont).".".$datosCol['name'].',';
					}

				}
				$contador=$contador+1;
			}


			}
			foreach ($listColumn as $datosCol) {
				$contador=1;
				if ($contador<$cantidadFilas) {
					if ($datosCol['llave']!='PRI') {
						$columnas=$columnas.'t.'.$datosCol['name'].",";	
					}

				}else{
					if ($datosCol['llave']!='PRI') {
						$columnas=$columnas.'t.'.$datosCol['name'];
					}

				}
				$contador=$contador+1;
			}
		$path='../Resultado/MYSQL/'.$nombre_tabla.'_c_sp.sql';
		$fh = fopen($path, 'w') or die("Se produjo un error al crear el archivo");
		$texto = "
		DELIMITER $$\n
		DROP PROCEDURE IF EXISTS {$nombre_tabla}_c_sp$$\n

		CREATE PROCEDURE {$nombre_tabla}_c_sp(in var_{$llave} int)\n
		BEGIN
			declare cantidad int;\n
			set cantidad=(select count(*) from {$nombre_tabla});\n
			if cantidad >0 then
				select {$columnas} from {$nombre_tabla} {$valorRel} where t.{$llave}=var_{$llave};\n
			else\n
				select 0 as resultado;\n
			end if;\n 	 
		END$$\n
		DELIMITER ;\n";
		fwrite($fh, $texto) or die("No se pudo escribir en el archivo");
		fclose($fh);
		$columnas=" ";
		$valorRel=" ";
		$llave=" ";
		$nombre_tabla=" ";
		$contador=1;
		$cantidadFilas=0;
	}}
	function crear_archivos_ajax(){
		
	}

	function crear_archivo_php(){
		$listTabla=$this->ArrayTabla();
		$nombre_tabla=" ";
		$variables=" ";
		$var_empty=" ";
		$var_datos=" ";
		$contador=1;
		$cantidadFilas=0;
		foreach ($listTabla as $array) {
			$nombre_tabla=$array['name'];
			$listColumn=$this->ArrayColumnTabla($nombre_tabla);
			$cantidadFilas=count($listColumn);
			foreach ($listColumn as $datosCol) {
				$variables=$variables."\$".$datosCol['name']."=\$_POST['var_".$datosCol['name']."'];\n";
				$tipoDato=explode('(', $datosCol['tipo']);
				if ($contador<$cantidadFilas) {
					$var_empty=$var_empty."!var_empty(\$".$datosCol['name'].") && ";
					if ($tipoDato[0]=='int') {
						$var_datos=$var_datos."{\$".$datosCol['name']."},";	
					}else if($tipoDato[0]=='date'){
						$var_datos=$var_datos."'{\$".$datosCol['name']."}',";	
					}else if($tipoDato[0]=='varchar'){
						$var_datos=$var_datos."'{\$".$datosCol['name']."}',";	
					}else if ($tipoDato[0]=='double') {
						$var_datos=$var_datos."{\$".$datosCol['name']."},";	
					}
				}else{
					$var_empty=$var_empty."!var_empty(\$".$datosCol['name'].")";
					if ($tipoDato[0]=='int') {
						$var_datos=$var_datos."{\$".$datosCol['name']."}";	
					}else if($tipoDato[0]=='date'){
						$var_datos=$var_datos."'{\$".$datosCol['name']."}'";	
					}else if($tipoDato[0]=='varchar'){
						$var_datos=$var_datos."'{\$".$datosCol['name']."}'";	
					}else if ($tipoDato[0]=='double') {
						$var_datos=$var_datos."{\$".$datosCol['name']."}";	
					}			
				}

				$contador=$contador+1;
			}

			$path='../Resultado/PHP/'.$nombre_tabla.'_am.php';
			$fh = fopen($path, 'w') or die("Se produjo un error al crear el archivo");
			$texto = "
				<?php
					if(isset(\$_POST['enviar'])){
						{$variables}
						if({$var_empty}){
							\$query=\$pdo->query(\"call {$nombre_tabla}_am_sp({$var_datos})\");
							if(\$query->rowCount()>0){
								echo 'Alta Correcta';
							}
						}

					}	
				?>
			";
			fwrite($fh, $texto) or die("No se pudo escribir en el archivo");
			fclose($fh);
			$nombre_tabla=" ";
			$variables=" ";
			$var_empty=" ";
			$var_datos=" ";
			$contador=1;
			$cantidadFilas=0;
		}
		
		

	}

}


include '../conexion_mysql.php';
$mysql= new classMysql($pdo,'veterinary');
/*$array=$mysql->ArrayTabla();
$list=$mysql->arrayTablasRelacionadas('paciente');
var_dump($list);
$raios=0;
foreach ($array as $arr) {
	echo $arr['name']."\n";

}*/

$array=$mysql->buscadorGeneral('leo');
var_dump($array);
echo "<br><br><br>";

foreach($array as $producto => $detalles)
{
	echo "<h1> $producto </h1>";

    foreach($detalles as $indice => $valor)
	{
		echo "<p> $indice:$valor </p>";
	}
}
#$mysql->crear_am_sql();
#$mysql->crear_l_sp();
#$mysql->crear_c_sp();













 ?>