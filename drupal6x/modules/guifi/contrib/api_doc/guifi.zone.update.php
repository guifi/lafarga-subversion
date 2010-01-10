<h4 id="method_zone_update">guifi.zone.update</h4>
<p><strong>Actualitza una zona a la xarxa.</strong></p>

<h5 id="method_zone_update_params">Paràmetres</h5>
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
			<td>zone_id</td>
			<td>string</td>
			<td>ID de la zona a editar.</td>
			<td></td>
		</tr>
		<tr>
			<td>title</td>
			<td>string</td>
			<td>Nom de la zona</td>
			<td></td>
		</tr>
		<tr>
			<td>nick</td>
			<td>string</td>
			<td>Abreviació de la zona.</td>
			<td><em>generat automàticament</em></td>
		</tr>
		<tr>
			<td>zone_mode</td>
			<td>string</td>
			<td>Mode de la zona. Possibles valors: <em><strong>infrastructure</strong></em>
			(Infraestructura) i <em><strong>ad-hoc</strong></em> (Ad-hoc).</td>
			<td><em>infrastructure</em></td>
		</tr>
		<tr>
			<td>body</td>
			<td>string</td>
			<td>Text explicatiu per mostrar a la zona.</td>
			<td><em>generat automàticament</em></td>
		</tr>
		<tr>
			<td>master</td>
			<td>integer</td>
			<td>ID de la zona pare de la zona a editar.</td>
			<td><em>0</em></td>
		</tr>
		<tr>
			<td>time_zone</td>
			<td>integer</td>
			<td>Fus horari de la zona.</td>
			<td><em>+01 2 2</em></td>
		</tr>
		<tr>
			<td>graph_server</td>
			<td>string</td>
			<td>ID del servidor de gràfiques que recull les dades de
			disponibilitat de la zona.</td>
			<td><em>Agafat de la zona pare</em></td>
		</tr>
		<tr>
			<td>proxy_server</td>
			<td>string</td>
			<td>ID del servidor proxy per defecte de la zona.</td>
			<td><em>Agafat de la zona pare</em></td>
		</tr>
		<tr>
			<td>dns_servers</td>
			<td>string</td>
			<td>Adreces IP dels servidors DNS de la zona, separats per comes (<strong>,</strong>).</td>
			<td><em>agafat de la zona pare</em></td>
		</tr>
		<tr>
			<td>ntp_servers</td>
			<td>string</td>
			<td>Adreces IP dels servidors de temps (NTP) de la zona, separats per
			comes (<strong>,</strong>).</td>
			<td><em>agafat de la zona pare</em></td>
		</tr>
		<tr>
			<td>ospf_zone</td>
			<td>string</td>
			<td>Identificador de zona OSPF de la zona.</td>
			<td></td>
		</tr>
		<tr>
			<td>homepage</td>
			<td>string</td>
			<td>Adreça web relacionada amb la zona.</td>
			<td></td>
		</tr>
		<tr>
			<td>notification</td>
			<td>string</td>
			<td>Adreça electrònica de notificació de canvis de la zona.</td>
			<td><em>Adreça electrònica de l'usuari autenticat.</em></td>
		</tr>
		<tr>
			<td>minx</td>
			<td>float</td>
			<td>Coordenada de longitud, en graus decimals, del límit inferior esquerre (SO) de la
			zona.</td>
			<td></td>
		</tr>
		<tr>
			<td>miny</td>
			<td>float</td>
			<td>Coordenada de latitud, en graus decimals, del límit inferior esquerre (SO) de la
			zona.</td>
			<td></td>
		</tr>
		<tr>
			<td>maxx</td>
			<td>float</td>
			<td>Coordenada de longitud, en graus decimals, del límit superior dret (NE) de la zona.</td>
			<td></td>
		</tr>
		<tr>
			<td>maxy</td>
			<td>float</td>
			<td>Coordenada de latitud, en graus decimals, del límit superior dret (NE) de la zona.</td>
			<td></td>
		</tr>
	</tbody>
</table>