<?php

/* 
	myTinyTodo Brazilian Portuguese language pack 
*/
#############################################################
# Patrick José Salles Streitenberger - patrickjp4@gmail.com #
#############################################################

class Lang extends DefaultLang
{
	var $js = array
	(
		'actionNote' => "notas",
		'actionEdit' => "modificar",
		'actionDelete' => "apagar",
		'taskDate' => array("function(date) { return 'added at '+date; }"),
		'confirmDelete' => "Você tem certeza?",
		'actionNoteSave' => "salvar",
		'actionNoteCancel' => "cancelar",
		'error' => "Ocorreu um erro (clique para detalhes)",
		'denied' => "Acesso Negado",
		'invalidpass' => "Senha incorreta",
		'readonly' => "somente leitura",
		'tagfilter' => "Tag:",
	);

	var $inc = array
	(
		'My Tiny Todolist' => "My Tiny Todolist",
		'tab_newtask' => "nova tarefa",
		'tab_search' => "procurar",
		'btn_add' => "Adicionar",
		'btn_search' => "Procurar",
		'searching' => "Procurar por",
		'tasks' => "Tarefas",
		'edit_task' => "Editar tarefa",
		'priority' => "Prioridade",
		'task' => "Tarefa",
		'note' => "Notas",
		'save' => "Salvar",
		'cancel' => "Cancelar",
		'password' => "Senha",
		'btn_login' => "Login",
		'a_login' => "Login",
		'a_logout' => "Sair",
		'tags' => "Tags",
		'tagfilter_cancel' => "cancelar filtro",
		'sortByHand' => "Ordenar manualmente",
		'sortByPriority' => "Ordenar por prioridade",
		'sortByDueDate' => "Ordenar por data de vencimento",
		'due' => "Vencimento",
		'daysago' => "%d dias atrás",
		'indays' => "em %d dias",
		'months_short' => array("Jan","Fev","Mar","Abr","Mai","Jun","Jul","Ago","Set","Out","Nov","Dez"),
		'days_min' => array("Dom","Seg","Ter","Qua","Qui","Sex","Sab"),
		'date_md' => "%1\$s %2\$d",
		'date_ymd' => "%2\$s %3\$d, %1\$d",
		'today' => "hoje",
		'yesterday' => "ontem",
		'tomorrow' => "amanhã",
		'f_past' => "Vencido",
		'f_today' => "Hoje e amanhã",
		'f_soon' => "Breve",
		'tasks_and_compl' => "Tarefas completadas",
	);
}

?>