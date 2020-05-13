<?php 
include 'conexion_mysql.php';
include 'variables.php';
$queryTablas=$pdo->query($tablas." ".$db);
?>

<!DOCTYPE html>
<html>
<head>
	<title></title>
</head>
<body>
	
<form action="crear_store_mysql.php">
	<?php 
	$cantFilas=$queryTablas->rowCount();
	if ($cantFilas>0) {
	while ($arr=$queryTablas->fetch()) {
	?>
	<label><?php echo $arr['Tables_in_'.$db]; ?></label>
	<input type="checkbox" name="<?php echo 'ch_'.$array['Tables_in_'.$db];?>">	
	<?php  
	}}
	?>
	<input type="submit" name="generarConsulta" value="generarConsulta">
</form>	
<script type="text/javascript" src="jquery.js"></script>
<script type="text/javascript">
	$(document).ready(function(){
		alert('Hola');
	})
</script>
</body>
</html>