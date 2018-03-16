<!-- Un début de <div> existe de par la fonction dol_fiche_head() -->
	<table width="100%" class="border">
		<tbody>
			<tr class="ref">
				<td width="25%">[langs.transnoentities(Ref)]</td>
				<td>[view.showRef;strconv=no]</td>
			</tr>

			<tr class="title">
				<td width="25%">[langs.transnoentities(Title)]</td>
				<td>[view.showTitle;strconv=no]</td>
			</tr>

			<tr class="status">
				<td width="25%">[langs.transnoentities(Status)]</td>
				<td>[object.getLibStatut(1);strconv=no]</td>
			</tr>
		</tbody>
	</table>

</div> <!-- Fin div de la fonction dol_fiche_head() -->

[onshow;block=begin;when [view.mode]!='edit']
	[view.list_answers;strconv=no]
[onshow;block=end]
<br />
[onshow;block=begin;when [view.action]=='view_answer']
	[view.user_answers;strconv=no]
[onshow;block=end]