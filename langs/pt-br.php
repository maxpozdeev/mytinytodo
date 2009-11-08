<?php

/*
	myTinyTodo language pack
	Language: Brazilian Portuguese
	Author: Raphael Guimarães
	E-Mail:
	Url:
	Version: v1.3b3
	Date: 2009-11-04
*/

class Lang extends DefaultLang
{
	var $js = array
	(
		'actionNote' => "nota",
		'actionEdit' => "modificar",
		'actionDelete' => "deletar",
		'taskDate' => array("function(date) { return 'added at '+date; }"),
		'confirmDelete' => "você tem certeza?",
		'actionNoteSave' => "salvar",
		'actionNoteCancel' => "cancelar",
		'error' => "Ocorreu algum erro (clique para detalhes)",
		'denied' => "Acesso negado",
		'invalidpass' => "Password errado",
		'readonly' => "Somente leitura",
		'tagfilter' => "Título:",
		'addList' => "Criar nova list",
		'renameList' => "Renomear lista",
		'deleteList' => "Isso deletará essa lista com todas as tarefas nela.\\nVocê tem certeza?",
		'settingsSaved' => "Preferencias salvas. Recarregando...",
	);

	var $inc = array
	(
		'My Tiny Todolist' => "Minha lista de tarefas",
		'htab_newtask' => "Nova tarefa",
		'htab_search' => "Procurar",
		'btn_add' => "Adicionar",
		'btn_search' => "Procurar",
		'advanced_add' => "Avaçado",
		'searching' => "Procurar por",
		'tasks' => "Tarefas",
		'edit_task' => "Editar Tarefa",
		'add_task' => "Nova Tarefa",
		'priority' => "Prioridade",
		'task' => "Tarefa",
		'note' => "Nota",
		'save' => "Salvar",
		'cancel' => "Cancelar",
		'password' => "Senha",
		'btn_login' => "Login",
		'a_login' => "Login",
		'a_logout' => "Sair",
		'tags' => "Títulos",
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
		'today' => "hoje",
		'yesterday' => "ontem",
		'tomorrow' => "amanhã",
		'f_past' => "Vencido",
		'f_today' => "Hoje e amanhã",
		'f_soon' => "Breve",
		'tasks_and_compl' => "Tarefas + completas",
		'notes' => "Notas:",
		'notes_show' => "Mostrar",
		'notes_hide' => "Esconder",
		'list_new' => "Nova lista",
		'list_rename' => "Renomear",
		'list_delete' => "Deletar",
		'alltags' => "Todos os títulos:",
		'alltags_show' => "Mostrar todos",
		'alltags_hide' => "Esconder todos",
		'a_settings' => "Preferências",
		'rss_feed' => "RSS Feed",
	);
}

?>