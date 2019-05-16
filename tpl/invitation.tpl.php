</div> <!-- Fin div de la fonction dol_fiche_head() -->

[onshow;block=begin;when [view.mode]='edit']

<table class="border" width="100%">
	[onshow;block=begin;when [view.act]='create']
	<tr>
		<td>Groupes Utilisateurs</td>
		<td>[form.select_usergroups;strconv=no]</td>
	</tr>
	
	<tr>
		<td>Utilisateurs</td>
		<td>[form.select_users;strconv=no]</td>
	</tr>
	[onshow;block=end]
	[onshow;block=begin;when [view.fk_user]=0]
	<tr>
		<td>Adresse Mail</td>
		<td><input type='text' name='emails' value='[form.emails]' /></td>
	</tr>
	[onshow;block=end]
	[onshow;block=begin;when [view.act]='create']
	<tr>
		<td>Adresses Mails (séparées par une virgule)</td>
		<td><input type='text' name='emails' value='[form.emails]' /></td>
	</tr>
	[onshow;block=end]
	<tr>
		<td>Date limite de réponse</td>
		<td>[form.date_limite;strconv=no]</td>
	</tr>
	
	
</table>

<br />

<div class="center">
	
	<!-- '+-' est l'équivalent d'un signe '>' (TBS oblige) -->
	[onshow;block=begin;when [object.id]+-0]
	<input type="hidden" name="action" value="[view.action]" />
	<input type='hidden' name='id' value='[object.id]' />
	<input type='hidden' name='fk_invitation' value='[form.fk_invitation]' />
	
	<input type="submit" value="[langs.transnoentities(Save)]" class="button" />
	[onshow;block=end]
	
	<input type="button" onclick="javascript:history.go(-1)" value="[langs.transnoentities(Cancel)]" class="button">
	
</div>
[onshow;block=end]

[onshow;block=begin;when [view.mode]!='edit']
<div class="tabsAction">
	<a href="[view.urladvselecttarget]?id=[object.id]" class="butAction">Inviter des tiers/contacts</a>
	<a href="[view.urlinvitation]?id=[object.id]&action=create" class="butAction">Inviter des utilisateurs/emails</a>
</div>

[view.list_invitations;strconv=no]
[onshow;block=end]
