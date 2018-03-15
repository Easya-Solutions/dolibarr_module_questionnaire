<!-- Un début de <div> existe de par la fonction dol_fiche_head() -->
	<input type="hidden" name="action" value="[view.action]" />
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

[onshow;block=begin;when [view.mode]='edit']

<table class="border" width="100%">
	<tr>
		<td>Groupes Utilisateurs</td>
		<td>[form.select_usergroups;strconv=no]</td>
	</tr>
	<tr>
		<td>Utilisateurs</td>
		<td>[form.select_users;strconv=no]</td>
	</tr>
	<tr>
		<td>Date limite de réponse</td>
		<td>[form.date_limite;strconv=no]</td>
	</tr>
</table>

<br />

<div class="center">
	
	<!-- '+-' est l'équivalent d'un signe '>' (TBS oblige) -->
	[onshow;block=begin;when [object.id]+-0]
	<input type='hidden' name='id' value='[object.id]' />
	<input type='hidden' name='fk_invitation' value='[form.fk_invitation]' />
	<input type="submit" value="[langs.transnoentities(Save)]" class="button" />
	[onshow;block=end]
	
	<input type="button" onclick="javascript:history.go(-1)" value="[langs.transnoentities(Cancel)]" class="button">
	
</div>
[onshow;block=end]

[onshow;block=begin;when [view.mode]!='edit']
<div class="tabsAction">
	<a href="[view.urlinvitation]?id=[object.id]&action=create" class="butAction">Créer invitation</a>
</div>
[view.list_invitations;strconv=no]
[onshow;block=end]
