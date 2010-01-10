<h4 id="method_node_update">guifi.node.update</h4>
<p><strong>Actualitza un node (o localització) guifi.net a la xarxa.</strong></p>

<h5 id="method_node_update_params">Paràmetres</h5>
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
			<td>ID del node guifi.net a editar.</td>
			<td></td>
		</tr>
		<tr>
			<td>title</td>
			<td>string</td>
			<td>Nom del lloc del node guifi.net.</td>
			<td></td>
		</tr>
		<tr>
			<td>nick</td>
			<td>string</td>
			<td>Nom curt del lloc.</td>
			<td><em>generat automàticament</em></td>
		</tr>
		<tr>
			<td>body</td>
			<td>string</td>
			<td>Descripció del node guifi.net.</td>
			<td><em>generat automàticament</em></td>
		</tr>
		<tr>
			<td>zone_id</td>
			<td>integer</td>
			<td>ID de zona on està ubicat aquest lloc.</td>
			<td></td>
		</tr>
		<tr>
			<td>zone_description</td>
			<td>string</td>
			<td>Descripció de la zona on està localitzat el nou node guifi.net.</td>
			<td></td>
		</tr>
		<tr>
			<td>notification</td>
			<td>string</td>
			<td>Adreça electrònica de notificació de canvis del node.</td>
			<td><em>Adreça electrònica de l'usuari autenticat.</em></td>
		</tr>
		<tr>
			<td>lat</td>
			<td>float</td>
			<td>Latitud, en graus decimals, de la localització del node
			guifi.net.</td>
			<td></td>
		</tr>
		<tr>
			<td>lon</td>
			<td>float</td>
			<td>Longitud, en graus decimals, de la localització del node
			guifi.net.</td>
			<td></td>
		</tr>
		<tr>
			<td>elevation</td>
			<td>integer</td>
			<td>Elevació, en metres, de la localització del nou node guifi.net.</td>
			<td></td>
		</tr>
		<tr>
			<td>stable</td>
			<td>string</td>
			<td>Serveix el node per expandir la xarxa? Possibles valors: <em><strong>Yes</strong></em>
			(Sí), <em><strong>No</strong></em> (No).</td>
			<td><em>Yes</em></td>
		</tr>
		<tr>
			<td>graph_server</td>
			<td>integer</td>
			<td>ID del servidor de gràfiques que recull les dades de
			disponibilitat del dispositiu.</td>
			<td><em>Agafat de la zona pare</em></td>
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
	</tbody>
</table>