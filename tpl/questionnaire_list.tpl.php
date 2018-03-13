<table id="[liste.id]" class="noborder" width="100%">
	<thead>
	<tr class="liste_titre">
		<th class="liste_titre">[entete.libelle;block=th;strconv=no] 
			<span>[onshow;block=span; when [entete.order]==1]<a href="javascript:TListTBS_OrderDown('[liste.id]','[entete.$;strconv=js]')">[liste.order_down;strconv=no]</a><a href="javascript:TListTBS_OrderUp('[liste.id]', '[entete.$;strconv=js]')">[liste.order_up;strconv=no]</a></span>
		</th>
	</tr>
	</thead>
	<tbody>
	<tr class="liste_titre">[onshow;block=tr;when [liste.nbSearch]+-0]
		<td class="liste_titre">[recherche.val;block=td;strconv=no]</td>
	</tr>
	<tr class="impair">
		<!-- [champs.$;block=tr;sub1] -->
		<td>[champs_sub1.val;block=td; strconv=no]</td>
	</tr>
	<tr class="pair">
		<!-- [champs.$;block=tr;sub1] -->
		<td>[champs_sub1.val;block=td; strconv=no]</td>
	</tr>
	</tbody>
</table>
<p align="center">
	[liste.messageNothing] [onshow; block=p; strconv=no; when [liste.totalNB]==0]
</p>