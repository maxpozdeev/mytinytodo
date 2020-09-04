<?php

/*
	myTinyTodo language class
*/

class Lang
{
	protected static $instance;
	protected $code = 'en';
	protected $default = 'en';
	protected $langDir = MTTINC. 'lang/';
	protected $strings;
	
	public static function instance()
	{
        if (!isset(self::$instance)) {
			$c = __CLASS__;
			self::$instance = new $c;
        }
		return self::$instance;	
	}
	
	public static function loadLang($code)
	{
		$lang = self::instance();
		
		//check if new format (json)
		if (file_exists("{$lang->langDir}{$code}.json")) {
			$jsonString = file_get_contents("{$lang->langDir}{$code}.json");
			$lang->loadJsonString($code, $jsonString);
		}
		else {
			die("Language file not found ($code.json)");
		}
	}
	
	function loadJsonString($code, $jsonString)
	{
		$this->code = $code;
		$json = json_decode($jsonString, true);
		
		//load default language
		if ( $code != $this->default ) {
			$defStr = file_get_contents("{$this->langDir}{$this->default}.json");
			$default_json = json_decode($defStr, true);
			$this->strings = array_replace($default_json, $json);
		}
		else {
			$this->strings = $json;
		}
	}
	
	function get($key)
	{
		if ( isset($this->strings[$key]) ) {
			return $this->strings[$key];
		}
		return $key;
	}
	
	function rtl()
	{
		if ( isset($this->strings['_rtl']) ) {
			return intval($this->strings['_rtl']);
		}
		return 0;
	}
	
	function makeJS($pretty = 0)
	{
		$a = array();
		$a['daysMin'] = $this->get('days_min');
		$a['daysLong'] = $this->get('days_long');
		$a['monthsLong'] = $this->get('months_long');
		
		$this->fillWithValues($a, [
			'confirmDelete',
			'confirmLeave',
			'actionNoteSave',
			'actionNoteCancel',
			'error',
			'denied',
			'invalidpass',
			'addList',
			'addListDefault',
			'renameList',
			'deleteList',
			'clearCompleted',
			'settingsSaved',
			'tags',
			'tasks',
			'f_past',
			'f_today',
			'f_soon'
		]);
		
		$opts = JSON_UNESCAPED_UNICODE;
		if ($pretty) {
			$opts |= JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES;
		}
		return json_encode($a, $opts);
	}
	
	protected function fillWithValues(array &$a, array $keys)
	{
		foreach ( $keys as $key ) {
			$a[$key] = $this->get($key);
		}
	}
	
	function langDir()
	{
		return $this->langDir;
	}

}

?>