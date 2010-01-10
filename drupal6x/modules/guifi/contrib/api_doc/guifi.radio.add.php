<h4 id="method_radio_add">guifi.radio.add</h4>
<p><strong>Afegeix una nova ràdio a un dispositiu de la xarxa.</strong></p>

<h5 id="method_radio_add_params">Paràmetres</h5>
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
			<td>mode</td>
			<td>string</td>
			<td>Mode de funcionament de la ràdio. Possibles valors: <strong>Mode
			infrastructura</strong>: <em><strong>ap</strong></em> (AP o AP amb
			WDS), <em><strong>client</strong></em> (Client sense fils), <em><strong>routedclient</strong></em>
			(Client enrutat); <strong>Mode ad-hoc</strong>: <em><strong>ad-hoc</strong></em>
			(Ad-hoc)</td>
			<td></td>
		</tr>
		<tr class="required">
			<td>device_id</td>
			<td>integer</td>
			<td>Dispositiu on anirà situada aquesta ràdio.</td>
			<td></td>
		</tr>
		<tr class="required">
			<td>mac</td>
			<td>string</td>
			<td>Adreça MAC de la primera interfície de la ràdio.</td>
			<td><em>A la primera ràdio es genera a partir de l'adreça MAC del
			dispositiu. Les altres són obligatòries.</em></td>
		</tr>
		<tr>
			<td>antenna_angle</td>
			<td>integer</td>
			<td>Angle d'obertura de l'antena. Possibles valors: <em><strong>0</strong></em>
			(original/integrada), <em><strong>6</strong></em> (yagi/directiva), <em><strong>60</strong></em>
			(patch 60 graus), <em><strong>90</strong></em> (patch 90 graus), <em><strong>120</strong></em>
			(sector 120 graus), <em><strong>360</strong></em> (omnidirectiva).</td>
			<td><em>Depèn del mode. ap: 120; client: 30; routedclient: 30;
			ad-hoc: 360</em></td>
		</tr>
		<tr>
			<td>antenna_gain</td>
			<td>integer</td>
			<td>Guany de l'antena. Possibles valors: <em><strong>2</strong></em>
			(2 dB), <em><strong>8</strong></em> (8 dB), <em><strong>12</strong></em>
			(12 dB), <em><strong>14</strong></em> (14 dB), <em><strong>18</strong></em>
			(18 dB), <em><strong>21</strong></em> (21 dB), <em><strong>24</strong></em>
			(24 dB), <em><strong>more</strong></em> (més de 24 dB).</td>
			<td><em>21</em></td>
		</tr>
		<tr>
			<td>antenna_azimuth</td>
			<td>integer</td>
			<td>Azimuth de l'antena d'aquesta ràdio, en graus. Rang de valors: 0
			- 360.</td>
			<td><em>0</em></td>
		</tr>
		<tr>
			<td>antenna_mode</td>
			<td>string</td>
			<td>Connector de la ràdio on està connectada l'antena. El significat
			dels valors depèn del model de dispositiu. Possibles valors: <em><strong>Main</strong></em>
			(Principal, Dret, Intern), <em><strong>Aux</strong></em> (Auxiliar,
			Esquerra, Extern).</td>
			<td><em>0</em></td>
		</tr>
	</tbody>
</table>

<p>A més, segons el camp <strong>mode</strong> d'aquest mètode hi ha un
seguit de camps extra que complementen la informació sobre la ràdio que
s'està afegint. Aquests altres camps estan separats a una segona taula,
especificant sobre quin tipus de ràdio s'utilitzen.</p>

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
			<td><a href="#method_misc_protocol">Protocol</a> que utilitza aquesta
			ràdio.</td>
			<td><em>802.11b</em></td>
		</tr>
		<tr>
			<td>channel</td>
			<td>string</td>
			<td><a href="#method_misc_channel">Canal de radiofreqüència</a> que
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
			<td><a href="#method_misc_protocol">Nom del protocol</a> que utilitza
			aquesta ràdio.</td>
			<td><em>802.11b</em></td>
		</tr>
		<tr>
			<td>channel</td>
			<td>string</td>
			<td><a href="#method_misc_channel">Canal de radiofreqüència</a> que
			utilitza aquesta ràdio. Aquest valor depèn del protocol de la ràdio.</td>
			<td><em>Automàtic</em></td>
		</tr>
	</tbody>
</table>

<h5 id="method_radio_add_return">Retorn</h5>
<p>A l'afegir una ràdio a un dispositiu de guifi.net, automàticament es
creen noves <a href="#method_interface">interfícies</a> per aquesta
ràdio.</p>
<p>El tipus i número d'interfícies dependrà del mode de funcionament de
la pròpia ràdio. En qualsevol cas, aquestes interfícies afegides
automàticament es poden tractar mitjançant el grup de mètodes d'<a
	href="#method_interface">interfície</a>.</p>

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
			<td>radiodev_counter</td>
			<td>integer</td>
			<td>Posició de la ràdio afegida en relació a altres ràdios del mateix
			dispositiu</td>
		</tr>
		<tr class="group returngroup">
			<td>interfaces</td>
			<td>array</td>
			<td>Interfícies afegides automàticament a aquesta ràdio.</td>
		</tr>
		<tr class="subgroup">
			<td colspan="3">
			<dl>
				<dt class="field_name">interface_type</dt>
				<dd class="field_type">string</dd>
				<dd class="field_description" style="width: 545px">Tipus d'<a
					href="#method_interface">interfície</a> afegida</dd>
			</dl>
			</td>
		</tr>
		<tr class="subgroup">
			<td colspan="3">
			<dl class="group">
				<dt class="field_name">ipv4</dt>
				<dd class="field_type">array</dd>
				<dd class="field_description" style="width: 545px">Informació sobre
				les <strong>N</strong> xarxes IPv4 que s'hagin pogut afegir.</dd>
			</dl>
			</td>
		</tr>
		<tr class="subgroup sublevel2">
			<td colspan="3">
			<dl>
				<dt class="field_name">ipv4_type</dt>
				<dd class="field_type">string</dd>
				<dd class="field_description" style="width: 495px">Tipus
				d'adreçament IPv4. Possibles valors: <em><strong>1</strong></em>
				(Adreces públiques), <em><strong>2</strong></em> (Adreces troncals).</dd>
			</dl>
			</td>
		</tr>
		<tr class="subgroup sublevel2">
			<td colspan="3">
			<dl>
				<dt class="field_name">ipv4</dt>
				<dd class="field_type">string</dd>
				<dd class="field_description" style="width: 495px">Adreça IPv4.</dd>
			</dl>
			</td>
		</tr>
		<tr class="subgroup sublevel2">
			<td colspan="3">
			<dl>
				<dt class="field_name">netmask</dt>
				<dd class="field_type">string</dd>
				<dd class="field_description" style="width: 495px">Màscara de
				l'adreça IPv4.</dd>
			</dl>
			</td>
		</tr>
	</tbody>
</table>

<h6>Exemple de retorn</h6>
<p>Per clarificar els conceptes, a continuació hi ha un exemple típic
d'un possible retorn agafat a l'atzar a l'hora d'afegir una ràdio nova.</p>

<blockquote><pre>
array(
   "radiodev_counter" = 0,
   "interfaces" = array(
      0 = array(
         "interface_type" = "wds/p2p"
         ),
      1 = array(
         "interface_type" = "wLan/Lan"
         "ipv4" = array(
            0 = array(
               "ipv4_type" = 1
               "ipv4" = "10.145.9.33"
               "netmask" = "255.255.255.224"
               )
            )
         )
      )
   );
</pre></blockquote>
