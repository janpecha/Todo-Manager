<?php
	/*
		Pouzity nasledujici cizi kody:
			(c) David Grudl
				* http://latrine.dgx.cz/presmerovani-pod-http
				* SafeStream
			(c) Jan Pecha
				* dwnParseUri v1.0
	*/

	define('TODO_NAME', 'Todo - interní todo list');
	
	define('LIB_DIR', __DIR__ . '/cztodo');

	$loadLib = (include LIB_DIR . '/SafeStream.php');
	$loadLib&= (include LIB_DIR . '/todo.class.php');
	$loadLib&= (include LIB_DIR . '/parseuri.class.l.php');

	if($loadLib)
	{
		SafeStream::register();
		$manager = new TodoManager();
		TodoModel::setProtocol('safe://');	// ne moc hezke hazet to sem
		TodoModel::setDir(LIB_DIR . '/todos');
	}
	else
	{
		echo "<h1>Initialization error</h1>";
		exit;
	}

	$manager->run();

?><!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Strict//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-strict.dtd">
<html xmlns="http://www.w3.org/1999/xhtml" xml:lang="cs" lang="cs">
  <head>
	  <meta http-equiv="Content-Type" content="text/html;charset=UTF-8" />
	  <meta name="description" content="Todo List v2.0" />
	  <meta name="author" content="Jan Pecha (c) 2010-2011" />
	  <link rel="stylesheet" type="text/css" media="screen" href="./css/screen.css" />
	  <link rel="stylesheet" type="text/css" media="print" href="./css/print.css" />
	  <title><?php echo TodoManager::escape(TODO_NAME); ?></title>
  </head>
  <body>
	<div id="telo">
		<h1><?php echo TodoManager::escape(TODO_NAME); ?></h1>
		<form action="<?php echo $manager->getUrl()->dir(); ?>/at/<?php if($manager->getUrl()->querystring()){ echo '?' . $manager->getUrl()->querystring(); } ?>" method="post" id="addform">
			<p><input type="hidden" name="addsmt" value="k8ljasf" />
			<textarea rows="4" cols="50" name="ukol" class="addtext"></textarea>
			<input type="submit" name="odeslano" value="<?php echo TodoManager::escape($manager->getText('add')); ?>" class="addsubmit" /></p>
		</form>
		
		<div id="topbar">
			<h2><?php echo TodoManager::escape($manager->getText('tasks')); ?></h2>
		
			<div id="topmenu">
				<a href="<?php echo $manager->getUrl()->dir(); ?>" class="showAll" title="Všechny"><span>Všechny</span></a>
				<a href="?state=u" class="onlyInProgress" title="Pouze čekající"><span>Pouze čekající</span></a>
				<a href="?state=h" class="onlyDone" title="Pouze splněné"><span>Pouze splněné</span></a>
				<a href="?state=d" class="onlyDeferred" title="Pouze odložené"><span>Pouze odložené</span></a>
<?php
	ob_start();
	$stats = $manager->showAll();
	$content = ob_get_contents();
	ob_end_clean();
?>
				<div id="stat">
				<?php
					if(isset($stats['h']))
					{
						echo $stats['h'];
					}
					else
					{
						echo "0";
					}
					
					echo ' splněno, ';
					
					
					if(isset($stats['u']))
					{
						echo $stats['u'];
					}
					else
					{
						echo "0";
					}
					echo ' nesplněno, ';
					
					if(isset($stats['d']))
					{
						echo $stats['d'];
					}
					else
					{
						echo "0";
					}
					echo ' odloženo';
				?>
				
				</div>
			</div>
		</div>
		
		<?php echo $content; ?>
		
	</div>
  </body>
</html>
