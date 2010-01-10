<h4 id="method_misc_firmware">guifi.misc.firmware</h4>
<p>Aquest mètode serveix per retornar els diversos tipus de firmwares de
dispositius (o trastos) suportats per guifi.net.</p>

<h5 id="method_misc_firmware_params">Paràmetres</h5>
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
		<tr>
			<td>model_id</td>
			<td>integer</td>
			<td><a href="#method_misc_model">ID de model</a> que suporta el firmware retornat.</td>
			<td></td>
		</tr>
	</tbody>
</table>

<h5 id="method_misc_firmware_return">Retorna</h5>
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
		<tr class="group">
			<td>firmwares</td>
			<td>array</td>
			<td>Firmwares suportats de guifi.net retornats</td>
		</tr>
		<tr class="subgroup">
			<td colspan="3">
			<dl>
				<dt class="field_name">title</dt>
				<dd class="field_type">string</dd>
				<dd class="field_description" style="width: 545px">Nom del firmware.</dd>
			</dl>
			</td>
		</tr>
		<tr class="subgroup">
			<td colspan="3">
			<dl>
				<dt class="field_name">description</dt>
				<dd class="field_type">string</dd>
				<dd class="field_description" style="width: 545px">Descripció del firmware.</dd>
			</dl>
			</td>
		</tr>
	</tbody>
</table>

<h5 id="method_misc_firmware_list">Llistat</h5>
<p>Un llistat útil de firmwares de dispositius és el següent:</p>
<table class="sample">
	<colgroup>
		<col class="field_name" />
		<col class="field_type" />
	</colgroup>
	<thead>
		<tr>
			<th scope="row">Nom del firmware</th>
			<th scope="row">Descripció del firmware</th>
		</tr>
	</thead>
	<tbody>
		<tr>
			<td>Alchemy</td>
			<td>Alchemy from sveasoft</td>
		</tr>
		<tr>
			<td>Talisman</td>
			<td>Talisman from sveasoft</td>
		</tr>
		<tr>
			<td>DD-WRT</td>
			<td>DD-WRT from BrainSlayer</td>
		</tr>
		<tr>
			<td>DD-guifi</td>
			<td>DD-guifi from Miquel Martos</td>
		</tr>
		<tr>
			<td>RouterOSv2.9</td>
			<td>RouterOS 2.9 from Mikrotik</td>
		</tr>
		<tr>
			<td>whiterussian</td>
			<td>OpenWRT-whiterussian</td>
		</tr>
		<tr>
			<td>kamikaze</td>
			<td>OpenWRT kamikaze</td>
		</tr>
		<tr>
			<td>Freifunk-BATMAN</td>
			<td>OpenWRT-Freifunk-v1.6.16 with B.A.T.M.A.N</td>
		</tr>
		<tr>
			<td>RouterOSv3.x</td>
			<td>RouterOS 3.x from Mikrotik</td>
		</tr>
		<tr>
			<td>AirOsv221</td>
			<td>Ubiquti AirOs 2.2.1</td>
		</tr>
		<tr>
			<td>Freifunk-OLSR</td>
			<td>OpenWRT-Freifunk-v1.6.16 with OLSR</td>
		</tr>
		<tr>
			<td>AirOsv30</td>
			<td>Ubiquti AirOs 3.0</td>
		</tr>
		<tr>
			<td>RouterOSv4.x</td>
			<td>RouterOS 4.x from Mikrotik</td>
		</tr>
	</tbody>
</table>