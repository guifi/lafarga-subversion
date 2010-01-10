<h4 id="method_interface_remove">guifi.interface.remove</h4>

<p><strong>Esborra una interfície de ràdio de la xarxa.</strong></p>

<div class="alert">
<h6>Vés amb compte</h6>
<p>Aquesta acció no té marxa enrere, i una interfície esborrada deixarà de
mostrar-se a la pàgina web de guifi.net.</p>
</div>

<p>L'API només suporta gestionar interfícies de ràdio sense fils.</p>
<p>No es poden esborrar interfícies del tipus wLan/Lan, així com tampoc
hi pot haver cap ràdio sense interfícies. Les úniques interfícies que es
poden treure són les de tipus <em><strong>wLan</strong></em>, les que
afegeixen rangs d'IP per clients.</p>
<p>Queda per implementar el suport de les connexions per cable.</p>

<h5 id="method_interface_remove_params">Paràmetres</h5>
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
			<td>interface_id</td>
			<td>integer</td>
			<td>ID de la interfície a esborrar.</td>
			<td></td>
		</tr>
	</tbody>
</table>