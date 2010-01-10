<h4 id="method_link_update">guifi.link.update</h4>
<p><strong>Actualitza un nou enllaç de la xarxa.</strong></p>

<h5 id="method_link_update_params">Paràmetres</h5>
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
			<td>link_id</td>
			<td>integer</td>
			<td>ID de l'enllaç a editar.</td>
			<td></td>
		</tr>
		<tr>
			<td>ipv4</td>
			<td>string</td>
			<td>Adreça IP que ha de tenir el dispositiu de l'enllaç amb identificador <em><strong>from_device_id</strong></em>. Aquesta adreça es verificarà, i si no és correcta no s'afegirà l'enllaç.</td>
			<td><em>Generat automàticament</em></td>
		</tr>
		<tr>
			<td>status</td>
			<td>string</td>
			<td>Estat de l'enllaç. Possibles valors: <em><strong>Planned</strong></em>
			(Projectat), <em><strong>Reserved</strong></em> (Reservat), <em><strong>Building</strong></em>
			(En construcció), <em><strong>Testing</strong></em> (En proves), <em><strong>Working</strong></em>
			(Operatiu) i <em><strong>Dropped</strong></em> (Esborrat).</td>
			<td><em>Working</em></td>
		</tr>
	</tbody>
</table>

<p>A més, segons el <strong>mode</strong> de l'enllaç a editar hi ha un
seguit de camps extra que complementen la informació sobre l'enllaç que
s'està editant. Aquests altres camps estan separats a una segona taula,
especificant sobre quin tipus d'enllaç s'utilitzen.</p>

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
			<td colspan="4">mode = link2ap</td>
		</tr>
		
		<tr class="group">
			<td colspan="4">mode = wds</td>
		</tr>
		<tr>
			<td>routing</td>
			<td>string</td>
			<td>Tipus d'enrutament aplicat a aquest enllaç. Possibles valors: <em><strong>OSPF</strong></em>
			(<abbr title="Open Shortest Path First">OSPF</abbr>), <em><strong>BGP</strong></em>
			(<abbr title="Border Gateway Protocol">BGP</abbr>), <em><strong>Static</strong></em>
			(estàtic).</td>
			<td><em>BGP</em></td>
		</tr>
	</tbody>
</table>