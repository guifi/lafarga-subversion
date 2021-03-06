<h4 id="method_zone_add"><?php _e("guifi.zone.add")?></h4>
<p><strong><?php _e("Afegeix una nova zona a la xarxa.")?></strong></p>

<h5 id="method_zone_add_params"><?php _e("Paràmetres")?></h5>

<p><?php _e("L'ordre bàsica d'ús d'aquest mètode conté els camps bàsics descrits a la taula de continuació.")?></p>
<table>
	<colgroup>
		<col class="field_name" />
		<col class="field_type" />
		<col class="field_description" />
		<col class="field_default" />
	</colgroup>
	<thead>
		<tr>
			<th scope="row"><?php _e("Nom")?></th>
			<th scope="row"><?php _e("Tipus")?></th>
			<th scope="row"><?php _e("Descripció")?></th>
			<th scope="row"><?php _e("Per defecte")?></th>
		</tr>
	</thead>
	<tbody>
		<tr class="required">
			<td>title</td>
			<td>string</td>
			<td><?php _e("Nom de la zona a crear.")?></td>
			<td></td>
		</tr>
		<tr>
			<td>nick</td>
			<td>string</td>
			<td><?php _e("Abreviació de la zona a crear.")?></td>
			<td><em><?php _e("generat automàticament")?></em></td>
		</tr>
		<tr>
			<td>zone_mode</td>
			<td>string</td>
			<td><?php _e("Mode de la zona. Possibles valors: <em><strong>%s</strong></em>
			(Infraestructura) i <em><strong>%s</strong></em> (Ad-hoc).", 'infrastructure', 'ad-hoc')?></td>
			<td><em>infrastructure</em>"</td>
		</tr>
		<tr>
			<td>body</td>
			<td>string</td>
			<td><?php _e("Text explicatiu per mostrar a la zona.")?></td>
			<td><em><?php _e("generat automàticament")?></em></td>
		</tr>
		<tr class="required">
			<td>master</td>
			<td>integer</td>
			<td><?php _e("ID de la zona pare de la nova zona a crear.")?></td>
			<td></td>
		</tr>
		<tr>
			<td>time_zone</td>
			<td>integer</td>
			<td><?php _e("Fus horari de la zona a crear.")?></td>
			<td><em>+01 2 2</em></td>
		</tr>
		<tr>
			<td>graph_server</td>
			<td>string</td>
			<td><?php _e("ID del servidor de gràfiques que recull les dades de disponibilitat de la zona.")?></td>
			<td><em><?php _e("Agafat de la zona pare")?></em></td>
		</tr>
		<tr>
			<td>proxy_server</td>
			<td>string</td>
			<td><?php _e("ID del servidor proxy per defecte de la zona.")?></td>
			<td><em><?php _e("Agafat de la zona pare")?></em></td>
		</tr>
		<tr>
			<td>dns_servers</td>
			<td>string</td>
			<td><?php _e("Adreces IP dels servidors DNS de la zona, separats per comes (<strong>,</strong>).")?></td>
			<td><em><?php _e("Agafat de la zona pare")?></em></td>
		</tr>
		<tr>
			<td>ntp_servers</td>
			<td>string</td>
			<td><?php _e("Adreces IP dels servidors de temps (NTP) de la zona, separats per comes (<strong>,</strong>).")?></td>
			<td><em><?php _e("Agafat de la zona pare")?></em></td>
		</tr>
		<tr>
			<td>ospf_zone</td>
			<td>string</td>
			<td><?php _e("Identificador de zona OSPF de la zona a crear.")?></td>
			<td></td>
		</tr>
		<tr>
			<td>homepage</td>
			<td>string</td>
			<td><?php _e("Adreça web relacionada amb la zona a crear.")?></td>
			<td></td>
		</tr>
		<tr>
			<td>notification</td>
			<td>string</td>
			<td><?php _e("Adreça electrònica de notificació de canvis de la zona.")?></td>
			<td><em><?php _e("Adreça electrònica de l'usuari autenticat.")?></em></td>
		</tr>
		<tr class="required">
			<td>minx</td>
			<td>float</td>
			<td><?php _e("Coordenada de longitud, en graus decimals, del límit inferior esquerre (SO) de la zona.")?></td>
			<td></td>
		</tr>
		<tr class="required">
			<td>miny</td>
			<td>float</td>
			<td><?php _e("Coordenada de latitud, en graus decimals, del límit inferior esquerre (SO) de la zona.")?></td>
			<td></td>
		</tr>
		<tr class="required">
			<td>maxx</td>
			<td>float</td>
			<td><?php _e("Coordenada de longitud, en graus decimals, del límit superior dret (NE) de la zona.")?></td>
			<td></td>
		</tr>
		<tr class="required">
			<td>maxy</td>
			<td>float</td>
			<td><?php _e("Coordenada de latitud, en graus decimals, del límit superior dret (NE) de la zona.")?></td>
			<td></td>
		</tr>
	</tbody>
</table>

<h5 id="method_zone_add_return"><?php _e("Retorn")?></h5>

<p><?php _e("Els camps que retorna aquest mètode en cas d'èxit són els descrits a continuació:")?></p>
<table>
	<colgroup>
		<col class="field_name" />
		<col class="field_type" />
		<col class="field_description" />
	</colgroup>
	<thead>
		<tr>
			<th scope="row"><?php _e("Nom")?></th>
			<th scope="row"><?php _e("Tipus")?></th>
			<th scope="row"><?php _e("Descripció")?></th>
		</tr>
	</thead>
	<tbody>
		<tr>
			<td>zone_id</td>
			<td>integer</td>
			<td><?php _e("ID de la zona afegida.")?></td>
		</tr>
	</tbody>
</table>