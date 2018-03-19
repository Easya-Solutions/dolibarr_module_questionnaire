</div> <!-- Fin div de la fonction dol_fiche_head() -->

[onshow;block=begin;when [view.mode]!='edit']
	[view.list_answers;strconv=no]
[onshow;block=end]
<br />
[onshow;block=begin;when [view.action]=='view_answer']
	[view.user_answers;strconv=no]
[onshow;block=end]