<!-- Un début de <div> existe de par la fonction dol_fiche_head() -->

	[onshow;block=begin;when [view.mode]=='edit']
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
			
			[onshow;block=begin;when [view.mode]!='answer']
				<tr class="status">
					<td width="25%">[langs.transnoentities(Status)]</td>
					<td>[object.getLibStatut(1);strconv=no]</td>
				</tr>
			[onshow;block=end]
		</tbody>
	</table>
	[onshow;block=end]

</div> <!-- Fin div de la fonction dol_fiche_head() -->

[onshow;block=begin;when [view.mode]='edit']
<div class="center">
	
	<!-- '+-' est l'équivalent d'un signe '>' (TBS oblige) -->
	[onshow;block=begin;when [object.id]+-0]
	<input type='hidden' name='id' value='[object.id]' />
	<input type="submit" value="[langs.transnoentities(Save)]" class="button" />
	[onshow;block=end]
	
	[onshow;block=begin;when [object.id]=0]
	<input type="submit" value="[langs.transnoentities(CreateDraft)]" class="button" />
	[onshow;block=end]
	
	<input type="button" onclick="javascript:history.go(-1)" value="[langs.transnoentities(Cancel)]" class="button">
	
</div>
[onshow;block=end]

[onshow;block=begin;when [view.mode]!='answer']
	[onshow;block=begin;when [view.mode]!='edit']
	<div class="tabsAction">
		
		[onshow;block=begin;when [object.fk_statut]=[Questionnaire.STATUS_DRAFT]]
			
			<div class="inline-block divButAction"><a href="[view.urlcard]?id=[object.id]&action=validate" class="butAction">[langs.transnoentities(Validate)]</a></div>
			
		[onshow;block=end]
		
		[onshow;block=begin;when [object.fk_statut]=[Questionnaire.STATUS_VALIDATED]]
			[onshow;block=begin;when [view.at_least_one_invitation]=0]
				<div class="inline-block divButAction"><a href="[view.urlcard]?id=[object.id]&action=modif" class="butAction">[langs.transnoentities(Modify)]</a></div>
			[onshow;block=end]
		[onshow;block=end]
		
		<div class="inline-block divButAction"><a href="[view.urlcard]?id=[object.id]&action=delete" class="butActionDelete">[langs.transnoentities(Delete)]</a></div>
			
	</div>
	[onshow;block=end]
[onshow;block=end]
