<h4 id="method_radio_update">guifi.radio.update</h4>
<p><strong>Afegeix una nova ràdio a un dispositiu de la xarxa.</strong></p>

<h5 id="method_radio_update_params">Paràmetres</h5>
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
			<td>device_id</td>
			<td>integer</td>
			<td>Dispositiu on està situada aquesta ràdio.</td>
			<td></td>
		</tr>
		<tr class="required">
			<td>radiodev_counter</td>
			<td>string</td>
			<td>Posició de la ràdio per actualitzar en relació a altres ràdios
			del mateix dispositiu.</td>
			<td></td>
		</tr>
		<tr>
			<td>antenna_angle</td>
			<td>integer</td>
			<td>Angle d'obertura de l'antena. Possibles valors: <em><strong>0</strong></em>
			(original/integrada), <em><strong>6</strong></em> (yagi/directiva), <em><strong>60</strong></em>
			(patch 60 graus), <em><strong>90</strong></em> (patch 90 graus), <em><strong>120</strong></em>
			(sector 120 graus), <em><strong>360</strong></em> (omnidirectiva).</td>
			<td></td>
		</tr>
		<tr>
			<td>antenna_gain</td>
			<td>integer</td>
			<td>Guany de l'antena. Possibles valors: <em><strong>2</strong></em>
			(2 dB), <em><strong>8</strong></em> (8 dB), <em><strong>12</strong></em>
			(12 dB), <em><strong>14</strong></em> (14 dB), <em><strong>18</strong></em>
			(18 dB), <em><strong>21</strong></em> (21 dB), <em><strong>24</strong></em>
			(24 dB), <em><strong>more</strong></em> (més de 24 dB).</td>
			<td></td>
		</tr>
		<tr>
			<td>antenna_azimuth</td>
			<td>integer</td>
			<td>Azimuth de l'antena d'aquesta ràdio, en graus. Rang de valors: 0
			- 360.</td>
			<td></td>
		</tr>
		<tr>
			<td>antenna_mode</td>
			<td>string</td>
			<td>Connector de la ràdio on està connectada l'antena. El significat
			dels valors depèn del model de dispositiu. Possibles valors: <em><strong>Main</strong></em>
			(Principal, Dret, Intern), <em><strong>Aux</strong></em> (Auxiliar,
			Esquerra, Extern).</td>
			<td></td>
		</tr>
	</tbody>
</table>

<p>A més, segons el camp <strong>mode</strong> que tingui aquesta hi ha
un seguit de camps extra que complementen la informació sobre la ràdio
que s'està editant. Aquests altres camps estan separats a una segona
taula, especificant sobre quin tipus de ràdio s'utilitzen.</p>

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
			<td colspan="4">mode = ap</td>
		</tr>
		<tr>
			<td>ssid</td>
			<td>string</td>
			<td><abbr title="Service Set Identifier">SSID</abbr> de la ràdio.</td>
			<td><em>Generat a partir del nom del dispositiu</em></td>
		</tr>
		<tr>
			<td>protocol</td>
			<td>string</td>
			<td><a href="#guifi_misc_protocol">Protocol</a> que utilitza aquesta
			ràdio.</td>
			<td><em>802.11b</em></td>
		</tr>
		<tr>
			<td>channel</td>
			<td>string</td>
			<td><a href="#guifi_misc_channel">Canal de radiofreqüència</a> que
			utilitza aquesta ràdio. Aquest valor depèn del protocol de la ràdio.</td>
			<td><em>Automàtic</em></td>
		</tr>
		<tr>
			<td>clients_accepted</td>
			<td>string</td>
			<td>Si la ràdio accepta clients o no. Possibles valors: <em><strong>Yes</strong></em>
			(Sí), <em><strong>No</strong></em> (No).</td>
			<td><em>Yes</em></td>
		</tr>
		<tr class="group">
			<td colspan="4">mode = ad-hoc</td>
		</tr>
		<tr>
			<td>ssid</td>
			<td>string</td>
			<td><abbr title="Service Set Identifier">SSID</abbr> de la ràdio.</td>
			<td><em>Generat a partir del nom del dispositiu</em></td>
		</tr>
		<tr>
			<td>protocol</td>
			<td>string</td>
			<td><a href="#guifi_misc_protocol">Nom del protocol</a> que utilitza
			aquesta ràdio.</td>
			<td><em>802.11b</em></td>
		</tr>
		<tr>
			<td>channel</td>
			<td>string</td>
			<td><a href="#guifi_misc_channel">Canal de radiofreqüència</a> que
			utilitza aquesta ràdio. Aquest valor depèn del protocol de la ràdio.</td>
			<td><em>Automàtic</em></td>
		</tr>
	</tbody>
</table>