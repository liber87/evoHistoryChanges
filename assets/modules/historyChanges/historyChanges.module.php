<?php
	function formatSize($bytes) {
		if ($bytes >= 1073741824) {
			$bytes = number_format($bytes / 1073741824, 2) . ' GB';
		}
		
		elseif ($bytes >= 1048576) {
			$bytes = number_format($bytes / 1048576, 2) . ' MB';
		}
		
		elseif ($bytes >= 1024) {
			$bytes = number_format($bytes / 1024, 2) . ' KB';
		}
		
		elseif ($bytes > 1) {
			$bytes = $bytes . ' байты';
		}
		
		elseif ($bytes == 1) {
			$bytes = $bytes . ' байт';
		}
		
		else {
			$bytes = '0 байтов';
		}
		
		return $bytes;
	}
	if (!function_exists('GetListFiles')){
		function GetListFiles($folder,&$all_files){
			global $cfiles;
			$fp=opendir($folder);
			while($cv_file=readdir($fp)){
				if(is_file($folder."/".$cv_file)){
					$info = stat($folder."/".$cv_file);
					$all_files[]=array('size'=>$info['size'],'atime'=>$info['atime'],'file'=>$folder."/".$cv_file);
				}
				elseif($cv_file!="."&&$cv_file!=".."&&is_dir($folder."/".$cv_file)){
					GetListFiles($folder."/".$cv_file,$all_files);
				}
			}
			closedir($fp);
		}
	}
	function compare ($v1, $v2) {						
		if ($v1["atime"] == $v2["atime"]) return 0;
		return ($v1["atime"] > $v2["atime"])? -1: 1;
	}
	
	function compare2 ($v1, $v2) {						
		if ($v1["last"] == $v2["last"]) return 0;
		return ($v1["last"] > $v2["last"])? -1: 1;
	}
	
	if(!isset($_SESSION['mgrValidated']) || !$_SESSION['mgrRole']) {
		die($_lang['login_as_admin']);
	}
	
	$module_path = str_replace('\\','/', dirname(__FILE__)) .'/';
	include $module_path . 'lang/english.inc.php';	
	if ($language != 'english') {
		$lang_file = $module_path . 'lang/' . $modx->config['manager_language'] . '.inc.php';
		if (file_exists($lang_file)) {
			include $lang_file;
		}
	}
	require_once($_SERVER['DOCUMENT_ROOT'].'/assets/cache/siteManager.php');
	$days = (int) $modx->event->params['days'];
	if ($days) {
		$difference = time()-$days*86400;		
		$where= ' where timestamp>='.$difference;		
	}
	
	$elements = ['templates'=>[],'chuncks'=>[],'plugins'=>[],'snippets'=>[],'modules'=>[]];
	$e = ['16'=>'templates','102'=>'plugins','22'=>'snippets','78'=>'chuncks','108'=>'modules','301'=>'tmplvars'];
	$res = $modx->db->query('Select * from '.$modx->getFullTableName('manager_log').' '.$where.' order by id desc');
	while($row = $modx->db->getRow($res)){		
		if (!$elements[$e[$row['action']]][$row['itemid']]['name']){
			$elements[$e[$row['action']]][$row['itemid']] = array('name' => $row['itemname'],'last'=>$row['timestamp'],'user'=>$row['username']);							
		}
		$elements[$e[$row['action']]][$row['itemid']]['changes'][] = $row['timestamp'];
	}	
	
	///foreach($e as $name) usort($elements[$name], "compare2");	
	
?>
<html>
	<head>
		<title><?=$_lang['edit'];?></title>
		<meta http-equiv="Content-Type" content="text/html; charset=UTF-8" />
		<script src="./../../../../<?=MGR_DIR;?>/media/script/jquery/jquery.min.js" type="text/javascript"></script>
		<script type="text/javascript" src="./../../../../<?=MGR_DIR;?>/media/script/tabpane.js"></script>		
		<meta name="viewport" content="initial-scale=1.0,user-scalable=no,maximum-scale=1,width=device-width" />
		<meta http-equiv="Content-Type" content="text/html; charset=<?=$modx->config['modx_charset'];?>" />
		<link rel="stylesheet" type="text/css" href="./../../../../<?=MGR_DIR;?>/media/style/default/css/styles.min.css" />
		<style>
			.text-primary,td{font-size: 0.8125rem !important; cursor:ponter;}
			.table.data td{padding: 0.75rem 0.5rem 0.3rem 0.5rem;}
			.table.data td.actions{padding-top: 0.4rem;}
			.err .elements_buttonbar li{display:none;}
			.err{    text-decoration: line-through;    font-style: italic; color:#b68282;}
		</style>
	</head>
	<body class="sectionBody">
		<h1><i class="fa fa-undo"></i><?=$_lang['module_title'];?></h1>
		<form method="post" action="">
			<div class="tab-pane " id="docManagerPane">
				<script type="text/javascript">
					tpResources = new WebFXTabPane(document.getElementById('docManagerPane'));
				</script>				
				<div class="tab-page" id="tabTemplates">
					<h2 class="tab"><i class="fa fa-newspaper-o"></i> <?=$_lang['templates'];?></h2>
					<script type="text/javascript">tpResources.addTabPage(document.getElementById('tabTemplates'));</script>
					<div class="panel-group">
						<div class=" resourceTable">							
							<table class="table data">
								<thead>
									<tr>
										<th><?=$_lang['name_temlate'];?></th>
										<th style="width: 1%;"><?=$_lang['edit'];?></th>
										<th style="width: 1%;"><?=$_lang['whom'];?></th>
										<th style="width: 1%;" class="text-nowrap"></th>
									</tr>
								</thead>
								<tbody>
									<?php
										if (count($elements['templates'])){
											foreach($elements['templates'] as $id => $item){
												$err = '';
												if (!$modx->db->getValue('Select count(*) from '.$modx->getFullTableName('site_templates').' where id='.$id)) $err = ' class="err"';												
												
												echo '
												<tr '.$err.'>
												<td><i class="fa fa-newspaper-o"></i> '.$item['name'].'</td>
												<td class="text-nowrap">'.date("d-m-Y H:i:s",$item['last']).'</td>
												<td class="text-right">'.$item['user'].'</td>
												<td class="actions text-right">
												<ul class="elements_buttonbar">
												<li>
												<a title="'.$_lng['edit'].'" href="/../../../manager/index.php?a=16&id='.$id.'" target="main">
												<i class="fa fa-edit fa-fw"></i>
												</a>
												</li>
												<li>
												<a onclick="return confirm(\''.$_lang['confirm_copy'].'\')" title="Сделать копию" href="/../../../manager/index.php?a=96&id='.$id.'" target="main"><i class="fa fa-clone fa-fw"></i></a>
												</li>
												<li>
												<a onclick="return confirm(\''.$_lang['confirm_del'].'\')" title="Удалить" href="/../../../manager/index.php?a=21&id='.$id.'"><i class="fa fa-trash fa-fw" target="main"></i>
												</a>
												</li>
												</ul>
												</td>
												</tr>';
											}			
										}
									?>
								</tbody>
							</table>
						</div>					
					</div>
				</div>
				<div class="tab-page" id="tabTemplateVariables">
					<h2 class="tab"><i class="fa fa-list-alt"></i> <?=$_lang['tmplvars'];?></h2>
					<script type="text/javascript">tpResources.addTabPage(document.getElementById('tabTemplateVariables'));</script>
					<div class="panel-group">
						<div class=" resourceTable">
							<table class="table data">
								<thead>
									<tr>
										<th><?=$_lang['tv'];?></th>
										<th style="width: 1%;"><?=$_lang['edit'];?></th>
										<th style="width: 1%;"><?=$_lang['whom'];?></th>
										<th style="width: 1%;" class="text-nowrap"></th>
									</tr>
								</thead>
								<tbody>
								<?php
								if (count($elements['tmplvars'])){
									foreach($elements['tmplvars'] as $id => $item){
										$err = '';
										if (!$modx->db->getValue('Select count(*) from '.$modx->getFullTableName('site_tmplvars').' where id='.$id)) $err = ' class="err"';												
										
										echo '
										<tr '.$err.'>
										<td><i class="fa fa-newspaper-o"></i> '.$item['name'].'</td>
										<td class="text-nowrap">'.date("d-m-Y H:i:s",$item['last']).'</td>
										<td class="text-right">'.$item['user'].'</td>
										<td class="actions text-right">
										<ul class="elements_buttonbar">
										<li>
										<a title="'.$_lng['edit'].'" href="/../../../manager/index.php?a=301&id='.$id.'" target="main">
										<i class="fa fa-edit fa-fw"></i>
										</a>
										</li>
										<li>
										<a onclick="return confirm(\''.$_lang['confirm_del'].'\')" title="Сделать копию" href="/../../../manager/index.php?a=304&id='.$id.'" target="main"><i class="fa fa-clone fa-fw"></i></a>
										</li>
										<li>
										<a onclick="return confirm(\''.$_lang['confirm_del'].'\')" title="Удалить" href="/../../../manager/index.php?a=303&id='.$id.'"><i class="fa fa-trash fa-fw" target="main"></i>
										</a>
										</li>
										</ul>
										</td>
										</tr>';
									}		
								}
								?>
								</tbody>
							</table>
						</div>					
					</div>
				</div>			
				<div class="tab-page" id="tabChunks">
					<h2 class="tab"><i class="fa fa-th-large"></i> <?=$_lang['htmlsnippets'];?></h2>
					<script type="text/javascript">tpResources.addTabPage(document.getElementById('tabChunks'));</script>
					<div class="panel-group">
						<div class=" resourceTable">
							<table class="table data">
								<thead>
									<tr>
										<th><?=$_lang['chunks'];?></th>
										<th style="width: 1%;"><?=$_lang['edit'];?></th>
										<th style="width: 1%;"><?=$_lang['whom'];?></th>
										<th style="width: 1%;" class="text-nowrap"></th>
									</tr>
								</thead>
								<tbody>
								<?php
									foreach($elements['chuncks'] as $id => $item){
										$err = '';
										if (!$modx->db->getValue('Select count(*) from '.$modx->getFullTableName('site_htmlsnippets').' where id='.$id)) $err = ' class="err"';												
										
										echo '
										<tr '.$err.'>
										<td><i class="fa fa-newspaper-o"></i> '.$item['name'].'</td>
										<td class="text-nowrap">'.date("d-m-Y H:i:s",$item['last']).'</td>
										<td class="text-right">'.$item['user'].'</td>
										<td class="actions text-right">
										<ul class="elements_buttonbar">
										<li>
										<a title="'.$_lng['edit'].'" href="/../../../manager/index.php?a=78&id='.$id.'" target="main">
										<i class="fa fa-edit fa-fw"></i>
										</a>
										</li>
										<li>
										<a onclick="return confirm(\''.$_lang['confirm_del'].'\')" title="Сделать копию" href="/../../../manager/index.php?a=97&id='.$id.'" target="main"><i class="fa fa-clone fa-fw"></i></a>
										</li>
										<li>
										<a onclick="return confirm(\''.$_lang['confirm_del'].'\')" title="Удалить" href="/../../../manager/index.php?a=80&id='.$id.'"><i class="fa fa-trash fa-fw" target="main"></i>
										</a>
										</li>
										</ul>
										</td>
										</tr>';
									}		
								?>
								</tbody>
							</table>
						</div>					
					</div>
				</div>	
				<div class="tab-page" id="tabSnippets">
					<h2 class="tab"><i class="fa fa-code"></i> <?=$_lang['snippets'];?></h2>
					<script type="text/javascript">tpResources.addTabPage(document.getElementById('tabSnippets'));</script>
					<div class="panel-group">
						<div class=" resourceTable">
							<table class="table data">
								<thead>
									<tr>
										<th><?=$_lang['snippets'];?></th>
										<th style="width: 1%;"><?=$_lang['edit'];?></th>
										<th style="width: 1%;"><?=$_lang['whom'];?></th>
										<th style="width: 1%;" class="text-nowrap"></th>
									</tr>
								</thead>
								<tbody>
								<?php
								if (count($elements['snippets'])){
									foreach($elements['snippets'] as $id => $item){
										$err = '';
										if (!$modx->db->getValue('Select count(*) from '.$modx->getFullTableName('site_snippets').' where id='.$id)) $err = ' class="err"';												
										
										echo '
										<tr '.$err.'>
										<td><i class="fa fa-newspaper-o"></i> '.$item['name'].'</td>
										<td class="text-nowrap">'.date("d-m-Y H:i:s",$item['last']).'</td>
										<td class="text-right">'.$item['user'].'</td>
										<td class="actions text-right">
										<ul class="elements_buttonbar">
										<li>
										<a title="'.$_lng['edit'].'" href="/../../../manager/index.php?a=22&id='.$id.'" target="main">
										<i class="fa fa-edit fa-fw"></i>
										</a>
										</li>
										<li>
										<a onclick="return confirm(\''.$_lang['confirm_del'].'\')" title="Сделать копию" href="/../../../manager/index.php?a=98&id='.$id.'" target="main"><i class="fa fa-clone fa-fw"></i></a>
										</li>
										<li>
										<a onclick="return confirm(\''.$_lang['confirm_del'].'\')" title="Удалить" href="/../../../manager/index.php?a=25&id='.$id.'"><i class="fa fa-trash fa-fw" target="main"></i>
										</a>
										</li>
										</ul>
										</td>
										</tr>';
									}		
								}
								?>
								</tbody>
							</table>
						</div>					
					</div>
				</div>	
				<div class="tab-page" id="tabPlugins">
					<h2 class="tab"><i class="fa fa-plug"></i> <?=$_lang['plugins'];?></h2>
					<script type="text/javascript">tpResources.addTabPage(document.getElementById('tabPlugins'));</script>
					<div class="panel-group">
						<div class=" resourceTable">
							<table class="table data">
								<thead>
									<tr>
										<th><?=$_lang['plugins'];?></th>
										<th style="width: 1%;"><?=$_lang['edit'];?></th>
										<th style="width: 1%;"><?=$_lang['whom'];?></th>
										<th style="width: 1%;" class="text-nowrap"></th>
									</tr>
								</thead>
								<tbody>
								<?php
								if (count($elements['plugins'])){
									foreach($elements['plugins'] as $id => $item){
										$err = '';
										if (!$modx->db->getValue('Select count(*) from '.$modx->getFullTableName('site_plugins').' where id='.$id)) $err = ' class="err"';												
										
										echo '
										<tr '.$err.'>
										<td><i class="fa fa-newspaper-o"></i> '.$item['name'].'</td>
										<td class="text-nowrap">'.date("d-m-Y H:i:s",$item['last']).'</td>
										<td class="text-right">'.$item['user'].'</td>
										<td class="actions text-right">
										<ul class="elements_buttonbar">
										<li>
										<a title="'.$_lng['edit'].'" href="/../../../manager/index.php?a=102&id='.$id.'" target="main">
										<i class="fa fa-edit fa-fw"></i>
										</a>
										</li>
										<li>
										<a onclick="return confirm(\''.$_lang['confirm_del'].'\')" title="Сделать копию" href="/../../../manager/index.php?a=105&id='.$id.'" target="main"><i class="fa fa-clone fa-fw"></i></a>
										</li>
										<li>
										<a onclick="return confirm(\''.$_lang['confirm_del'].'\')" title="Удалить" href="/../../../manager/index.php?a=104&id='.$id.'"><i class="fa fa-trash fa-fw" target="main"></i>
										</a>
										</li>
										</ul>
										</td>
										</tr>';
									}		
								}
								?>
								</tbody>
							</table>
						</div>					
					</div>
				</div>	
				<div class="tab-page" id="tabModules">
					<h2 class="tab"><i class="fa fa-cubes"></i>  <?=$_lang['modules'];?></h2>
					<script type="text/javascript">tpResources.addTabPage(document.getElementById('tabModules'));</script>
					<div class="panel-group">
						<div class=" resourceTable">
							<table class="table data">
								<thead>
									<tr>
										<th><?=$_lang['modules'];?></th>
										<th style="width: 1%;"><?=$_lang['edit'];?></th>
										<th style="width: 1%;"><?=$_lang['whom'];?></th>
										<th style="width: 1%;" class="text-nowrap"></th>
									</tr>
								</thead>
								<tbody>
								<?php
								if (count($elements['modules'])){
									foreach($elements['modules'] as $id => $item){
										$err = '';
										if (!$modx->db->getValue('Select count(*) from '.$modx->getFullTableName('site_modules').' where id='.$id)) $err = ' class="err"';												
										
										echo '
										<tr '.$err.'>
										<td><i class="fa fa-newspaper-o"></i> '.$item['name'].'</td>
										<td class="text-nowrap">'.date("d-m-Y H:i:s",$item['last']).'</td>
										<td class="text-right">'.$item['user'].'</td>
										<td class="actions text-right">
										<ul class="elements_buttonbar">
										<li>
										<a title="'.$_lng['edit'].'" href="/../../../manager/index.php?a=112&id='.$id.'" target="main">
										<i class="fa fa-edit fa-fw"></i>
										</a>
										</li>										
										</ul>
										</td>
										</tr>';
									}		
								}
								?>
								</tbody>
							</table>
						</div>					
					</div>
				</div>	
				<div class="tab-page" id="tabFiles">
					<h2 class="tab"><i class="fa fa-file"></i> <?=$_lang['files'];?></h2>
					<script type="text/javascript">tpResources.addTabPage(document.getElementById('tabFiles'));</script>
					<div class="tab-body">
						<?php		
							
							$all_files=array();
							GetListFiles(MODX_BASE_PATH.$modx->event->params['assets'],$all_files);
							usort($all_files, "compare");
							echo '
							<table class="table data">
							<thead>
							<tr>
							<th>Имя файла</th>
							<th style="width: 1%;">'.$_lang['edit'].'</th>
							<th style="width: 1%;">Размер файла</th>
							<th style="width: 1%;" class="text-nowrap">Параметры</th>
							</tr>
							</thead>
							<tbody>
							';
							$i = 0;							
							foreach($all_files as $file){
								$name = str_replace(MODX_BASE_PATH,'',$file['file']);
								echo '
								<tr>
								<td><i class="fa fa-file-o FilesPage"></i> '.$name.'</td>
								<td class="text-nowrap">'.date("d-m-Y H:i:s",$file['atime']).'</td>
								<td class="text-right">'.formatSize($file['size']).'</td>
								<td class="actions text-right">
								<ul class="elements_buttonbar">
									<li>
										<a title="'.$_lng['edit'].'" href="/../../../manager/index.php?a=31&amp;mode=view&amp;path='.urldecode($file['file']).'" target="main">
										<i class="fa fa-eye"></i>
										</a>
									</li>
									<li>
										<a title="'.$_lng['edit'].'" href="/../../../manager/index.php?a=31&amp;mode=edit&amp;path='.urldecode($file['file']).'" target="main">
										<i class="fa fa-edit fa-fw"></i>
										</a>
									</li>										
								</ul>
								</td>
								</tr>								
								';
								$i++;
								if ($i>30) break;
							}
							
							
							echo '</tbody></table>';
							
						?>	
					</div>
				</div>
				
			</div>
		</form>
		<style>
			.opened > a{font-weight:700;}
			.sub_catalog{margin-left:25px;}
			#el b{padding-right:30px;}
			.resourceTable ul.elements{margin:0 !important;}
			.resourceTable .panel-title>a{    padding: 5px 2.25rem !important;}
			#tabFiles .subchecked > label > input { background-color: rgba(0,0,0,0.15); border-color: #ccc; }
			.sectionBody fieldset.package-options { margin: 0 0 1rem; padding-top: 0.375rem !important; }
			.package-options legend { box-shadow: none; border: none; width: auto; background: none; margin: 0; }
			.package-options label { margin: 0; }
			.package-options > :last-child { margin-bottom: 0; }
		</style>		
	</body>
</html>		