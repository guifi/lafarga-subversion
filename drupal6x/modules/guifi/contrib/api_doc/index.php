<!DOCTYPE html PUBLIC "-//W3C//DTD HTML 4.01 Transitional//EN" "http://www.w3.org/TR/html4/loose.dtd">
<html>
<head>
<meta http-equiv="Content-Type" content="text/html; charset=UTF-8">
<title>guifi.net API documentation</title>

<link rel="stylesheet" type="text/css" href="style.css" />

<script src="http://jqueryjs.googlecode.com/files/jquery-1.3.2.min.js"></script>
<script src="http://samaxesjs.googlecode.com/files/jquery.toc-1.0.2.min.js"></script>

<script type="text/javascript">
$(document).ready(function() {
    $('#tocdiv').toc({exclude: 'h1, h6'});
});
</script>

</head>
<body>
	<div id="container">
		<div id="content">
			<h1>Documentació de l'API de guifi.net</h1>
			
			<h2 id="toc">Taula de continguts</h2>
			<div id="tocdiv"></div>
			
			<h2 id="overview">Descripció general</h2>
			
			<p>
				L'API de guifi.net consisteix en un grup de mètodes per poder crear, editar o esborrar els diversos
				objectes que conformen la xarxa lliure, oberta i neutral.
			</p>
			<p>
				Els diversos mètodes estan classificats en 8 grans <a href="#methods">grups de mètodes</a>, cadascun 
				referent a la seva àrea.</p>
			<p>
				Aquests grups són:
			</p>
			
			<ul>
				<li><a href="#method_auth">Auth</a>: per autenticar-se com a usuari de guifi.net i poder utilitzar els permisos d'aquest usuari</li>
				<li><a href="#method_zone">Zone</a>: per poder gestionar les zones dins de guifi.net</li>
				<li><a href="#method_node">Node</a>: per poder gestionar els nodes dins de guifi.net</li>
				<li><a href="#method_device">Device</a>: per poder gestionar els dispositius (o trastos) dins de guifi.net</li>
				<li><a href="#method_radio">Radio</a>: per poder gestionar les ràdios dins de guifi.net</li>
				<li><a href="#method_interface">Interface</a>: per poder gestionar les interfícies de les ràdios dins de guifi.net</li>
				<li><a href="#method_link">Link</a>: per poder gestionar els enllaços entre nodes dins de guifi.net</li>
				<li><a href="#method_misc">Misc</a>: per poder agafar informació sobre diversos tipus de dades dins de guifi.net</li>
			</ul>
			<br />
			<p>
				Tots els mètodes descrits en aquests grups tenen fins a dos subapartats, els paràmetres i el retorn.
			</p>
			<p>
				Els <strong>paràmetres</strong> és la informació bàsica que servirà per operar amb l'aplicació de guifi.net,
				mentre que el <strong>retorn</strong> és la informació que ens retornarà l'aplicació un cop executada l'operació.
			</p>
			<p>
				Els paràmetres que estiguin marcats en vermell i amb un asterisc al davant (<span class="required"><strong>*</strong></span>) 
				són obligatoris, mentre que els altres són opcionals.
			</p>
			
			<h2 id="working">Funcionament</h2>
			
			<?php require('guifi.working.calls.php'); ?>
			
            <?php require('guifi.working.responses.php'); ?>			
            
            <?php require('guifi.working.codes.php'); ?>
            
            <?php require('guifi.working.errors.php'); ?>
			
			<h2 id="methods">Mètodes</h2>
			<p>
				A continuació hi ha detallats els diferents grups de mètodes existents a l'API de guifi.net.
			</p>
						
			<h3 id="method_auth">guifi.auth</h3>
			<p>
				Aquest grup de mètodes serveix per controlar l'<strong>autenticació contra guifi.net</strong>, i així poder gestionar correctament
				els permisos a utilitzar per altres mètodes.
			</p>
			<p>
				Totes les crides a l'API de guifi.net han de començar amb un d'aquests mètodes, que permetran després
				executar els altres mètodes correctament.
			</p>
			
			<?php require('guifi.auth.login.php'); ?>
			
			<h3 id="method_zone">guifi.zone</h3>
			<p>
				Aquest grup de mètodes serveix per controlar les zones dins de guifi.net.
			</p>
			
			<?php require('guifi.zone.add.php'); ?>
			
			<?php require('guifi.zone.update.php'); ?>
			
			<?php require('guifi.zone.remove.php'); ?>
			
			<?php require('guifi.zone.nearest.php'); ?>
			
			
			<h3 id="method_node">guifi.node</h3>
			
			<?php require('guifi.node.add.php'); ?>
			
			<?php require('guifi.node.update.php'); ?>
			
			<?php require('guifi.node.remove.php'); ?>
			
			
			<h3 id="method_device">guifi.device</h3>
			
			<p>
				Aquest grup de mètodes serveix per controlar els dispositius (o trastos) que hi ha dins de guifi.net.
			</p>
			<p>
				Cada dispositiu està associat a un <a href="#method_node">node</a>, i hi ha diversos tipus de dispositius.
			</p>
			
			<?php require('guifi.device.add.php'); ?>
			
			<?php require('guifi.device.update.php'); ?>
			
			<?php require('guifi.device.remove.php'); ?>
			
			
			<h3 id="method_radio">guifi.radio</h3>
			
			<?php require('guifi.radio.add.php'); ?>
			
			<?php require('guifi.radio.update.php'); ?>
			
			<?php require('guifi.radio.remove.php'); ?>
			
			<?php require('guifi.radio.nearest.php'); ?>
			
			
			<h3 id="method_interface">guifi.interface</h3>
			
			<?php require('guifi.interface.add.php'); ?>
			
			<?php require('guifi.interface.remove.php'); ?>
			
			
			<h3 id="method_link">guifi.link</h3>
			
			<?php require('guifi.link.add.php'); ?>
			
			<?php require('guifi.link.update.php'); ?>
			
			<?php require('guifi.link.remove.php'); ?>
			
			
			<h3 id="method_misc">guifi.misc</h3>
			
			<?php require('guifi.misc.model.php') ?>
			
			<?php require('guifi.misc.manufacturer.php') ?>
			
			<?php require('guifi.misc.firmware.php') ?>
			
			<?php require('guifi.misc.protocol.php') ?>
			
			<?php require('guifi.misc.channel.php') ?>
			
			
			<h2 id="auth">Autenticació</h2>
			
			<?php require('guifi.auth.php') ?>
			
			
			<h2 id="libraries">Llibreries</h2>
			<p>El codi font de l'API de guifi.net és lliure, i es pot trobar al <a href="http://trac.guifi.net">Trac de guifi.net</a></p>
			<p>A més a més, s'ofereixen als programadors que vulguin fer servir l'API de guifi.net un seguit de llibreries per
			poder-hi treballar més còmodament des de bon principi.</p>
			<p>Els llenguatges de programació disponibles per aquestes llibreries són els següents:</p>
			<ul>
				<li><a href="http://ontanem.net/projectes/guifi/api_client/">PHP</a></li>
			</ul>
			<br />
			
			<p>T'animem a programar les teves pròpies llibreries en el llenguatge de programació que prefereixis, i a alliberar-ne 
			el codi font perquè tothom en pugui fer ús!</p>
		</div>
	</div>

</body>
</html>