<?php

/*
	myTinyTodo language pack
	Language: Portuguese (Portugal)
	Original name: Português (Europeu)
	Author: Sérgio Martins
	Author Email: eurospem@live.com.pt
	AppVersion: v1.3.6
	Date: 2010-07-29
*/

class Lang extends DefaultLang
{
	var $js = array
	(
		'confirmDelete' => "Apagar esta tarefa?",
		'actionNoteSave' => "guardar",
		'actionNoteCancel' => "cancelar",
		'error' => "Erro (ver detalhes)",
		'denied' => "Acesso negado",
		'invalidpass' => "Senha errada",
		'tagfilter' => "Título:",
		'addList' => "Criar nova lista",
		'renameList' => "Renomear lista",
		'deleteList' => "Apagar a lista de tarefas toda.\\nTem certeza?",
		'clearCompleted' => "Apagar todas as tarefas completas na lista.\\nTem certeza?",
		'settingsSaved' => "Configurações guardadas. Recarregando...",
	);

	var $inc = array
	(
		'My Tiny Todolist' => "Minha lista de tarefas",
		'htab_newtask' => "Nova tarefa",
		'htab_search' => "Pesquisar",
		'btn_add' => "Adicionar",
		'btn_search' => "Pesquisar",
		'advanced_add' => "Avançado",
		'searching' => "Pesquisar por",
		'tasks' => "Tarefas",
		'taskdate_inline' => "adicionada em %s",
		'taskdate_created' => "Data da criação",
		'taskdate_completed' => "Data da conclusão",
		'edit_task' => "Editar Tarefa",
		'add_task' => "Nova Tarefa",
		'priority' => "Prioridade",
		'task' => "Tarefa",
		'note' => "Nota",
		'save' => "Guardar",
		'cancel' => "Cancelar",
		'password' => "Senha",
		'btn_login' => "Login",
		'a_login' => "Login",
		'a_logout' => "Sair",
		'public_tasks' => "Tarefa Pública",
		'tags' => "Etiquetas",
		'tagfilter_cancel' => "cancelar filtro",
		'sortByHand' => "Ordenar manualmente",
		'sortByPriority' => "Ordenar por prioridade",
		'sortByDueDate' => "Ordenar por data de vencimento",
		'due' => "Prazo",
		'daysago' => "%d dias atrás",
		'indays' => "em %d dias",
		'months_short' => array("Jan","Fev","Mar","Abr","Mai","Jun","Jul","Ago","Set","Out","Nov","Dez"),
		'months_long' => array("Janeiro","Fevereiro","Março","Abril","Maio","Junho","Julho","Agosto","Setembro","Outubro","Novembro","Dezembro"),
		'days_min' => array("Dom","Seg","Ter","Qua","Qui","Sex","Sab"),
		'days_long' => array("Domingo","Segunda","Terça","Quarta","Quinta","Sexta","Sábado"),
		'today' => "hoje",
		'yesterday' => "ontem",
		'tomorrow' => "amanhã",
		'f_past' => "Vencidas",
		'f_today' => "Hoje e Amanhã",
		'f_soon' => "Brevemente",
		'action_edit' => "Editar",
		'action_note' => "Editar Nota",
		'action_delete' => "Apagar",
		'action_priority' => "Prioridade",
		'action_move' => "Mover para",
		'notes' => "Notas:",
		'notes_show' => "Mostrar",
		'notes_hide' => "Esconder",
		'list_new' => "Nova lista",
		'list_rename' => "Renomear lista",
		'list_delete' => "Apagar lista",
		'list_publish' => "Publicar lista",
		'list_showcompleted' => "Mostrar tarefas completas",
		'list_clearcompleted' => "Limpar tarefas completas",
		'alltags' => "Todos os títulos:",
		'alltags_show' => "Mostrar todos",
		'alltags_hide' => "Esconder todas",
		'a_settings' => "Configurações",
		'rss_feed' => "RSS Feed",
		'feed_title' => "%s",
		'feed_description' => "Nova tarefa em %s",

		/* Settings */
		'set_header' => "Configurações",
		'set_title' => "Título",
		'set_title_descr' => "(especifique se deseja alterar o título padrão)",
		'set_language' => "Idíoma",
		'set_protection' => "Proteção por Senha",
		'set_enabled' => "Habilitar",
		'set_disabled' => "Desabilitar",
		'set_newpass' => "Nova senha",
		'set_newpass_descr' => "(deixe em branco se não vai alterar a senha atual)",
		'set_smartsyntax' => "Smart syntax",
		'set_smartsyntax_descr' => "(/prioridade/ tarefa /etiquetas/)",
		'set_autotz' => "Timezone Automática",
		'set_autotz_descr' => "(determinar timezone do utilizador com javascript)",
		'set_autotag' => "Etiquetas Auto",
		'set_autotag_descr' => "(adiciona automáticamente as etiquetas filtradas ás novas tarefas)",
		'set_sessions' => "Mecanismo de manipulação de sessões",
		'set_sessions_php' => "PHP",
		'set_sessions_files' => "Arquivos",
		'set_firstdayofweek' => "Primeiro dia da semana",
		'set_duedate' => "Formato da data do calendário",
		'set_date' => "Formato da data",
		'set_shortdate' => "Formato de data abreviada",
		'set_clock' => "Formato do relógio",
		'set_12hour' => "12-horas",
		'set_24hour' => "24-horas",
		'set_submit' => "Guardar",
		'set_cancel' => "Cancelar",
		'set_showdate' => "Mostrar a data na lista de tarefas",
	);
}

?>