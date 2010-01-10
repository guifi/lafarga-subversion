<h4 id="method_device_add">guifi.device.add</h4>
<p><strong>Afegeix un nou dispositiu a la xarxa.</strong></p>

<h5 id="method_device_add_params">Paràmetres</h5>
<p>L'ordre bàsica d'ús d'aquest mètode conté els camps bàsics descrits a
la taula de continuació.</p>

<table>
	<colgroup>
		<col class="field_name" />
		<col class="field_type" />
		<col class="field_description" />
		<col class="field_default" />
	</colgroup>
	<thead>
		<tr>
			<th scope="row">Nom</th>
			<th scope="row">Tipus</th>
			<th scope="row">Descripció</th>
			<th scope="row">Per defecte</th>
		</tr>
	</thead>
	<tbody>
		<tr class="required">
			<td>node_id</td>
			<td>integer</td>
			<td>ID del node on el dispositiu s'afegirà.</td>
			<td></td>
		</tr>
		<tr>
			<td>nick</td>
			<td>string</td>
			<td>Nom del nou dispositiu.</td>
			<td><em>generat automàticament</em></td>
		</tr>
		<tr class="required">
			<td>type</td>
			<td>string</td>
			<td>Tipus del nou dispositiu. Possibles valors: <em><strong>radio</strong></em>
			(Trasto sense fils), <em><strong>server</strong></em> (Servidor), <em><strong>nat</strong></em>
			(Tallafocs, xarxa privada darrere NAT), <em><strong>generic</strong></em>
			(Trasto genèric), <em><strong>adsl</strong></em> (Router ADSL o
			trasto proveïdor d'accés a Internet), <em><strong>cam</strong></em>
			(Càmera en xarxa), <em><strong>phone</strong></em> (Telèfon mòbil
			VoIP).</td>
			<td></td>
		</tr>
		<tr>
			<td>notification</td>
			<td>string</td>
			<td>Adreça electrònica de notificació de canvis del dispositiu.</td>
			<td><em>Adreça electrònica de l'usuari autenticat.</em></td>
		</tr>
		<tr class="required">
			<td>mac</td>
			<td>string</td>
			<td>Adreça MAC del dispositiu nou a afegir (del tipus <em>AA:BB:CC:DD:EE:FF</em>).</td>
			<td></td>
		</tr>
		<tr>
			<td>comment</td>
			<td>string</td>
			<td>Comentaris extra del dispositiu a afegir.</td>
			<td></td>
		</tr>
		<tr>
			<td>status</td>
			<td>string</td>
			<td>Estat del dispositiu. Possibles valors: <em><strong>Planned</strong></em>
			(Projectat), <em><strong>Reserved</strong></em> (Reservat), <em><strong>Building</strong></em>
			(En construcció), <em><strong>Testing</strong></em> (En proves), <em><strong>Working</strong></em>
			(Operatiu) i <em><strong>Dropped</strong></em> (Esborrat).</td>
			<td><em>Working</em></td>
		</tr>
		<tr>
			<td>graph_server</td>
			<td>integer</td>
			<td>ID del servidor de gràfiques que recull les dades de
			disponibilitat del dispositiu.</td>
			<td><em>Agafat del node pare</em></td>
		</tr>
	</tbody>
</table>

<p>A més, segons el camp <strong>type</strong> d'aquest mètode hi ha un
seguit de camps extra que complementen la informació sobre el dispositiu
que s'està afegint. Aquests altres camps estan separats a una segona
taula, especificant sobre quin dispositiu s'utilitzen.</p>

<table>
	<colgroup>
		<col class="field_name" />
		<col class="field_type" />
		<col class="field_description" />
		<col class="field_default" />
	</colgroup>
	<thead>
		<tr>
			<th scope="row">Nom</th>
			<th scope="row">Tipus</th>
			<th scope="row">Descripció</th>
			<th scope="row">Per defecte</th>
		</tr>
	</thead>
	<tbody>
		<tr class="group">
			<td colspan="4">type = radio</td>
		</tr>
		<tr class="required">
			<td>model_id</td>
			<td>integer</td>
			<td>ID del <a href="#method_misc_model">model de trasto</a> sense
			fils per afegir</td>
			<td></td>
		</tr>
		<tr class="required">
			<td>firmware</td>
			<td>string</td>
			<td><a href="#method_misc_firmware">Firmware</a> que utilitza el
			trasto sense fils per afegir</td>
			<td></td>
		</tr>
		<tr class="group">
			<td colspan="4">type = adsl</td>
		</tr>
		<tr>
			<td>download</td>
			<td>integer</td>
			<td>Ample de banda de baixada del dispositiu en bytes per segon. Per
			exemple per una velocitat de baixada de 10 Mbps, s'hi ha d'introduir
			<strong><em>10000000</em></strong>.</td>
			<td><em>4000000</em></td>
		</tr>
		<tr>
			<td>upload</td>
			<td>integer</td>
			<td>Ample de banda de pujada del dispositiu en bytes per segon. Per
			exemple per una velocitat de pujada de 512 kbps, s'hi ha d'introduir
			<strong><em>512000</em></strong>.</td>
			<td><em>640000</em></td>
		</tr>
		<tr>
			<td>mrtg_index</td>
			<td>integer</td>
			<td>Interfície SNMP per agafar informació sobre el tràfic d'aquest
			dispositiu.</td>
			<td><em>5</em></td>
		</tr>
		<tr class="group">
			<td colspan="4">type = generic</td>
		</tr>
		<tr>
			<td>mrtg_index</td>
			<td>integer</td>
			<td>Interfície SNMP per agafar informació sobre el tràfic d'aquest
			dispositiu.</td>
			<td><em>5</em></td>
		</tr>
	</tbody>
</table>

<h5 id="method_device_add_return">Retorn</h5>
<p>Els camps que retorna aquest mètode en cas d'èxit són els descrits a
continuació:</p>
<table>
	<colgroup>
		<col class="field_name" />
		<col class="field_type" />
		<col class="field_description" />
	</colgroup>
	<thead>
		<tr>
			<th scope="row">Nom</th>
			<th scope="row">Tipus</th>
			<th scope="row">Descripció</th>
		</tr>
	</thead>
	<tbody>
		<tr>
			<td>device_id</td>
			<td>integer</td>
			<td>ID del dispositiu afegit</td>
		</tr>
	</tbody>
</table>