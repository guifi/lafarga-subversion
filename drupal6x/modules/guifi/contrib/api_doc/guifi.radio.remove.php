<h4 id="method_radio_remove">guifi.radio.remove</h4>
<p><strong>Esborra un ràdio d'un dispositiu guifi.net de la xarxa.</strong></p>

<div class="alert">
<h6>Vés amb compte</h6>
<p>Aquesta acció no té marxa enrere, i un radio esborrada deixarà de
mostrar-se a la pàgina web de guifi.net.</p>
<p>Es perdran totes les configuracions IP i els enllaços associats a aquesta ràdio!</p>
</div>

<h5 id="method_radio_remove_params">Paràmetres</h5>
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
			<td>device_id</td>
			<td>integer</td>
			<td><a href="#method_device">ID del dispositiu</a> on està situada la ràdio.</td>
			<td></td>
		</tr>
		<tr class="required">
			<td>radiodev_counter</td>
			<td>integer</td>
			<td>Dins el dispositiu, a quina posició està ubicada la ràdio que es vol esborrar.</td>
			<td></td>
		</tr>
	</tbody>
</table>