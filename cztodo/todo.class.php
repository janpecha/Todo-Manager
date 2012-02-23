<?php
	/**
	* Todo Manager
	* 
	* @author		Jan Pecha, janpecha@iunas.cz
	* @link			janpecha.iunas.cz
	* @copyright	(c) 2011 Jan Pecha
	* @version		2.0.0.1a
	* @todo			[DEL]	01.05.2011 - ?? pro datum a cas misto nazvu souboru pouzit jeho ctime ??
	*/
	
	class TodoModel
	{
		private static $dir = './cztodo/todos';
		private static $protocol = '';
		//private static $dateTimeFormat = 'j.n.Y - H:i:s';
		const DATE_TIME_FORMAT = 'YmdHis';
		
		
		
		public static function setDir($directory)
		{
			self::$dir = (string)$directory;
		}
		
		
		
		/*public function setDateTimeFormat($format = 'j.n.Y - H:i:s')
		{
			self::$dateTimeFormat = (string)$format;
		}*/
		
		
		
		public static function setProtocol($protocol = '')
		{
			self::$protocol = (string)$protocol;
		}
		
		
		
		public function addTodo($todoText)
		{
			$fileName = date(self::DATE_TIME_FORMAT).'_u';	// date() => 4+2+2 2+2+2 = 14 + 2 = 16
			file_put_contents(self::$dir . "/$fileName", (string)$todoText);
		}
		
		
		
		public function getAll()
		{
			$todos = array();
			$dir = opendir(self::$dir);
			
			while($t = readdir($dir))
			{
				if($t == '.' || $t == '..'){
					continue;
				}
				
				if(isset($t[0]) && $t[0] === '.')	// .htaccess file, etc.
				{
					continue;
				}
				
				$id = substr($t, 0, -2);
				
				$todos[$id] = array(
					'id'	=> $id,
					'datetime' => DateTime::createFromFormat(self::DATE_TIME_FORMAT, $id),
					'text' 	=> file_get_contents(self::$protocol . self::$dir . "/$t"),
					'state'	=> substr($t, -1)
				);
			}
			
			closedir($dir);
			
			krsort($todos);
			
			return $todos;
		}
		
		
		
		public function changeState($todoId, $state = 'h')
		{
			return rename(self::$dir."/$todoId", self::$dir . '/' . substr($todoId, 0, -1) . $state);
		}
		
		
		
		public function remove($todoId)
		{
			return unlink(self::$protocol . self::$dir . "/$todoId");
		}
	}
	
	
	
	class TodoManager
	{
		private $translate = array();
		private $model;
		private $url;
		private static $dateTimeFormat = 'j.n.Y - H:i:s';
		private static $enabledState = array();
		
		
		
		public function __construct()
		{
			$this->translateCzech();	// default language settings
			
			$this->model = new TodoModel();
			
			$this->url = new dwnParseUri();
			
			self::$enabledState = array(
				'h' => 'done',
				'u' => 'inprogress',
				'd' => 'deferred'
			);
		}
		
		
		
		public function getUrl()
		{
			return $this->url;
		}
		
		
		
		public function run()
		{
			// url: /action/[todoid/][id2/]
			$route = array(
				'cs' => 'actionChangeState',
				'de' => 'actionDelete',
				'at' => 'actionAddTask',
			);
			
			$currentRoute = false;
			$redir = true;
			$redirId = '';
			
			if(isset($_GET['state']) && !isset(self::$enabledState[$_GET['state']]))
			{
				$this->redirect();
			}
			
			$num = $this->url->num();
			
			if(isset($route[$this->url->part(0)]) && ($num > 0 || $num < 4))
			{
				$currentRoute = $this->url->part(0);
				
				$params = array();
				$error = false;
				
				if($num > 1)
				{
					$error = !self::validateId($this->url->part(1));
					
					for($i = 1; $i < $num; $i++)
					{
						$params[$i-1] = $this->url->part($i);
					}
				}
				
				if(!$error)
				{
					$redirId = call_user_func_array(array($this, $route[$currentRoute]), $params);
				}
			}
			elseif($this->url->part(0) === '')	// homepage
			{
				$redir = false;
			}
			
			if($redir)
			{
				$this->redirect($redirId);	// to 303
			}
		}
		
		
		
		private function actionChangeState($todoId, $newState = '')
		{
			if(isset(self::$enabledState[$newState]))
			{
				$this->model->changeState($todoId, $newState);
			}
			
			return $todoId;
		}
		
		
		
		private function actionDelete($todoId)
		{
			$this->model->remove($todoId);
		}
		
		
		
		private function actionAddTask()
		{
			if(isset($_POST['addsmt']) && $_POST['addsmt'] == 'k8ljasf' && isset($_POST['ukol']))
			{
				$this->model->addTodo($_POST['ukol']);
			}
		}
		
		
		
		public function showAll()
		{
			$state = '';
			
			if(isset($_GET['state']) && isset(self::$enabledState[$_GET['state']]))
			{
				$state = $_GET['state'];
			}
			
			$todos = $this->model->getAll();
			$stats = array();
			
			foreach($todos as $id => $todo)
			{
				if($state == '' || $todo['state'] == $state)
				{
					$this->templateShowTodo($todo);
				}
				
				if(isset($stats[$todo['state']]))
				{
					$stats[$todo['state']]++;
				}
				else
				{
					$stats[$todo['state']] = 1;
				}
			}
			
			return $stats;
		}
		
		
		
		public function templateShowTodo(array $todo)
		{
			$dir = self::escape($this->url->dir());
			$querystring = '';
			if($_SERVER['QUERY_STRING'])
			{
				$querystring = '?' . $_SERVER['QUERY_STRING'];
			}
		?>
			<div id="todo-<?php echo self::escape($todo['id']); ?>" class="todoitem<?php if(isset(self::$enabledState[$todo['state']])){ echo ' '.self::$enabledState[$todo['state']]; } ?>">
				<pre><?php echo self::escape($todo['text']); ?></pre>
				
				<div class="panel">
					<span class="datetime"><?php echo self::escape($todo['datetime']->format(self::$dateTimeFormat)); ?></span>
					
<?php
	if($todo['state'] !== 'h')
	{
?>
					<a href="<?php echo $dir ?>/cs/<?php echo self::escape($todo['id'] . '_' . $todo['state']); ?>/h/<?php echo $querystring ?>" rel="nofollow"><?php echo self::escape($this->translate['done']); ?></a>
<?php
		if($todo['state'] !== 'd')
		{
?>
					<a href="<?php echo $dir ?>/cs/<?php echo self::escape($todo['id'] . '_' . $todo['state']); ?>/d/<?php echo $querystring ?>" rel="nofollow"><?php echo self::escape($this->translate['defer']); ?></a>
<?php
		}
		else
		{
?>
					<a href="<?php echo $dir ?>/cs/<?php echo self::escape($todo['id'] . '_' . $todo['state']); ?>/u/<?php echo $querystring ?>" rel="nofollow"><?php echo self::escape($this->translate['restore']); ?></a>
<?php
		}
	}
?>
					
					<a href="<?php echo $dir ?>/de/<?php echo self::escape($todo['id'] . '_' . $todo['state']); ?>/<?php echo $querystring ?>" rel="nofollow"><?php echo self::escape($this->translate['delete']); ?></a>
					
					<div class="more">
						<a href="#" rel="nofollow"><?php echo self::escape($this->translate['print']); ?></a>
					</div>
				</div>
			</div>

<?php
		}
		
		
		
		public static function validateId($todoId)
		{
			if(strlen($todoId) !== 16 && substr($todoId, -2, 1) !== '_')
			{
				return false;
			}
			
			try
			{
				DateTime::createFromFormat(TodoModel::DATE_TIME_FORMAT, substr($todoId, 0, -2));
				return true;
			}
			catch(Exception $e)
			{
				return false;
			}
		}
		
		
		
		public static function escape($s)
		{
			return htmlspecialchars($s, ENT_QUOTES);
		}
		
		
		
		public function redirect($todoId = '')		/*$url, $code = 303*/
		{
			$url = (($this->url->https()) ? 'https':'http').'://'.$this->url->server().$this->url->dir().'/';
			
			if($_SERVER['QUERY_STRING'])
			{
				$url.= '?' . $_SERVER['QUERY_STRING'];
			}
			
			if($todoId)
			{
				$url.= "#todo-" . substr($todoId, 0, -2);
			}
			
			header('Location: '.$url, TRUE, 303);
            die('Pro pokracovani prosim <a href="'.htmlSpecialChars($url).'">kliknete sem</a>.');
		}
		
		
		
		public function getText($s)
		{
			if(isset($this->translate[$s]))
			{
				return $this->translate[$s];
			}
			
			return '';
		}
		
		
		
		public function translateCzech()
		{
			$this->translate = array(
				'delete' => 'Smazat',
				'done'	 => 'Splněno',
				'defer'  => 'Odložit',
				'restore'=> 'Obnovit',
				'print'  => 'Tisk',
				'tasks'  => 'Úkoly',
				'add'	 => 'Přidat'
			);
		}
	}
	
/*
	01.05.2011 - zalozen soubor
	02.10.2011 - odkazy maji rel="nofollow"
*/
