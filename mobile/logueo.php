<html>
<?php include "cabecera.php"; ?>
<body>
<div class="row-fluid">
	<div id="titulo-login" class="span4 offset4">
		<h3 id="titulologin" class="text-center">Acceso a DomoWeb</h3>
	</div>
</div>
<div class="row-fluid">
	<div class="span4 offset4">
		<form class="form-horizontal" action="acceder.php" method="post">
			<div class="control-group">
				<label class="control-label" for="inputUser">Usuario</label>
				<div class="controls">
					<input type="text" id="inputUser" name="nombre" placeholder="Usuario">
				</div>
			</div>
			<div class="control-group">
				<label class="control-label" for="inputPassword">Contrase&ntilde;a</label>
				<div class="controls">
					<input type="password" id="inputPassword" name="pass" placeholder="Contrase&ntilde;a">
				</div>
			</div>
			<div class="control-group">
				<div class="controls">
					<button type="submit" class="btn">Acceder</button>
				</div>
			</div>
		</form>
	</div>
</div>
</body>
</html>
