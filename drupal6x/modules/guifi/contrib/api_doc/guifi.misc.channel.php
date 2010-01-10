<h4 id="method_misc_channel">guifi.misc.channel</h4>
<p>Aquest mètode serveix per retornar els diversos tipus de channels de
dispositius (o trastos) suportats per guifi.net.</p>

<h5 id="method_misc_channel_params">Paràmetres</h5>
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
			<td>protocol</td>
			<td>string</td>
			<td><a href="#method_misc_protocol">Nom del protocol</a> que suporta els canals retornats.</td>
			<td></td>
		</tr>
	</tbody>
</table>

<h5 id="method_misc_channel_return">Retorna</h5>
<table>
	<colgroup>
		<col class="field_name" />
		<col class="field_type" />
		<col class="field_description" />
		<col class="field_example" />
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
			<td>channels</td>
			<td>array</td>
			<td>Canals suportats de guifi.net retornats</td>
		</tr>
		<tr class="subgroup">
			<td colspan="3">
			<dl>
				<dt class="field_name">title</dt>
				<dd class="field_type">string</dd>
				<dd class="field_description" style="width: 545px">Nom del canal.</dd>
			</dl>
			</td>
		</tr>
		<tr class="subgroup">
			<td colspan="3">
			<dl>
				<dt class="field_name">description</dt>
				<dd class="field_type">string</dd>
				<dd class="field_description" style="width: 545px">Descripció del canal.</dd>
			</dl>
			</td>
		</tr>
	</tbody>
</table>

<h5 id="method_misc_channel_list">Llistat</h5>
<p>Un llistat útil de canals és el següent, ordenats per <a href="#method_misc_protocol">nom de protocol</a>:</p>
<table class="sample">
	<thead>
		<tr>
			<th scope="row">Nom del canal</th>
			<th scope="row">Descripció del canal</th>
		</tr>
	</thead>
	<tbody>
		<tr class="group">
			<td colspan="2">802.11b</td>
		</tr>
		<tr class="group">
			<td colspan="2">802.11g</td>
		</tr>
		<tr class="group">
			<td colspan="2">802.11n</td>
		</tr>
		<tr>
			<td>0</td>
			<td>Auto 2.4GHz</td>
		</tr>
		<tr>
			<td>1</td>
			<td>1.- 2412 MHz</td>
		</tr>
		<tr>
			<td>2</td>
			<td>2-. 2417 MHz</td>
		</tr>
		<tr>
			<td>3</td>
			<td>3.- 2422 MHz</td>
		</tr>
		<tr>
			<td>4</td>
			<td>4.- 2422 MHz</td>
		</tr>
		<tr>
			<td>5</td>
			<td>5.- 2432 MHz</td>
		</tr>
		<tr>
			<td>6</td>
			<td>6.- 2437 MHz</td>
		</tr>
		<tr>
			<td>7</td>
			<td>7.- 2442 MHz</td>
		</tr>
		<tr>
			<td>8</td>
			<td>8.- 2447 MHz</td>
		</tr>
		<tr>
			<td>9</td>
			<td>9.- 2452 MHz</td>
		</tr>
		<tr>
			<td>10</td>
			<td>10.- 2457 MHz</td>
		</tr>
		<tr>
			<td>11</td>
			<td>11.- 2462 MHz</td>
		</tr>
		<tr>
			<td>12</td>
			<td>12.- 2467 MHz</td>
		</tr>
		<tr>
			<td>13</td>
			<td>13.- 2472 MHz</td>
		</tr>
		<tr>
			<td>14</td>
			<td>14.- 2477 MHz</td>
		</tr>
		<tr class="group">
			<td colspan="2">802.11a</td>
		</tr>
		<tr>
			<td>5000</td>
			<td>Auto 5GHz</td>
		</tr>
		<tr>
			<td>5180</td>
			<td>1.- 5180 MHz</td>
		</tr>
		<tr>
			<td>5200</td>
			<td>2-. 5200 MHz</td>
		</tr>
		<tr>
			<td>5220</td>
			<td>3.- 5220 MHz</td>
		</tr>
		<tr>
			<td>5240</td>
			<td>4.- 5240 MHz</td>
		</tr>
		<tr>
			<td>5260</td>
			<td>5.- 5260 MHz</td>
		</tr>
		<tr>
			<td>5280</td>
			<td>6.- 5280 MHz</td>
		</tr>
		<tr>
			<td>5300</td>
			<td>7.- 5300 MHz</td>
		</tr>
		<tr>
			<td>5320</td>
			<td>8.- 5320 MHz</td>
		</tr>
		<tr>
			<td>5500</td>
			<td>9.- 5500 MHz</td>
		</tr>
		<tr>
			<td>5520</td>
			<td>10.- 5520 MHz</td>
		</tr>
		<tr>
			<td>5540</td>
			<td>11.- 5540 MHz</td>
		</tr>
		<tr>
			<td>5560</td>
			<td>12.- 5560 MHz</td>
		</tr>
		<tr>
			<td>5580</td>
			<td>13.- 5580 MHz</td>
		</tr>
		<tr>
			<td>5600</td>
			<td>14.- 5600 MHz</td>
		</tr>
		<tr>
			<td>5620</td>
			<td>15.- 5620 MHz</td>
		</tr>
		<tr>
			<td>5640</td>
			<td>16.- 5640 MHz</td>
		</tr>
		<tr>
			<td>5660</td>
			<td>17.- 5660 MHz</td>
		</tr>
		<tr>
			<td>5680</td>
			<td>18.- 5680 MHz</td>
		</tr>
		<tr>
			<td>5700</td>
			<td>19.- 5700 MHz</td>
		</tr>
		<tr class="group">
			<td colspan="2">WiMAX</td>
		</tr>
		<tr>
			<td>0000</td>
			<td>Auto 2-8Ghz</td>
		</tr>
	</tbody>
</table>