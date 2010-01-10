<h3 id="working_codes">Codis de resposta</h3>

<p>Tota crida que es faci a l'API de guifi.net obté un codi de resposta,
tal i com s'explica a <a href="#working_responses">respostes de l'API</a>.</p>

<p>Aquests codi de resposta està conformat pels següents camps:</p>

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
			<td>code</td>
			<td>integer</td>
			<td>Identificador del codi de resposta.</td>
		</tr>
		<tr>
			<td>str</td>
			<td>string</td>
			<td>Cadena de caràcters explicativa del codi de resposta (i unívoca al
			codi d'error).</td>
		</tr>
	</tbody>
</table>

<p>A continuació hi ha un llistat amb els possibles valors que poden
tenir aquests codis.</p>

<table>
	<colgroup>
		<col class="field_name" />
		<col class="field_description" />
	</colgroup>
	<thead>
		<tr>
			<th scope="row">Codi</th>
			<th scope="row">Cadena de caràcters explicativa</th>
		</tr>
	</thead>
	<tbody>
		<tr>
			<td>200</td>
			<td>Request completed successfully</td>
		</tr>
		<tr>
			<td>201</td>
			<td>Request could not be completed, errors found</td>
		</tr>
	</tbody>
</table>