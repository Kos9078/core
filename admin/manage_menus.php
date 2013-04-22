<?php
/*
* Project:		EQdkp-Plus
* License:		Creative Commons - Attribution-Noncommercial-Share Alike 3.0 Unported
* Link:			http://creativecommons.org/licenses/by-nc-sa/3.0/
* -----------------------------------------------------------------------
* Began:		2009
* Date:			$Date$
* -----------------------------------------------------------------------
* @author		$Author$
* @copyright	2006-2011 EQdkp-Plus Developer Team
* @link			http://eqdkp-plus.com
* @package		eqdkpplus
* @version		$Rev$
*
* $Id$
*/

//tbody not allowed withoud thead, 

define('EQDKP_INC', true);
define('IN_ADMIN', true);
$eqdkp_root_path = './../';
include_once($eqdkp_root_path . 'common.php');

class Manage_Menus extends page_generic {
	public static function __shortcuts() {
		$shortcuts = array('user', 'tpl', 'in', 'pdh', 'jquery', 'core', 'config', 'html', 'admin_index'=>'admin_index');
		return array_merge(parent::$shortcuts, $shortcuts);
	}

	public function __construct(){
		$this->user->check_auth('a_config_man');
		$handler = array(
			'save' => array('process' => 'save','csrf'=>true),
			'mode' => array('process' => 'delete_plink', 'csrf'=>true)
		);
		parent::__construct(false, $handler);
		$this->process();
	}

	// ---------------------------------------------------------
	// Process Save
	// ---------------------------------------------------------
	public function save() {
	
		$json = $this->in->get('serialized');
		$arrItems = $this->in->getArray('mainmenu', 'string');
		
		$decoded = json_decode($json, true);
		$arrSorted = array();
		if ($decoded){
			$intFirstLevel = -1;
			$intSecondLevel = -1;
			foreach($decoded as $item){
				if ((int)$item['item_id']){
					$hash = $arrItems[$item['item_id']]['id'];
					if ($arrItems[$item['item_id']]['type'] == 'pluslink'){
						//New plus links
						if ($hash == 'new'){
							$data = $arrItems[$item['item_id']];
							$pid = $this->pdh->put('links', 'add', array($data['name'], $data['url'],$data['window'],$data['visibility'],$data['windowsize']));
							if (!$pid) continue;
							$link = $this->core->handle_link($data['url'],$data['name'], $data['window'], 'pluslink'.$pid);
							$hash = $this->core->build_link_hash($link);
						} else {
							//Update existing plus link
							$data = $arrItems[$item['item_id']];
							$pid = $this->pdh->put('links', 'update', array($data['specialid'], $data['name'], $data['url'],$data['window'],$data['visibility'],$data['windowsize']));
							if (!$pid) continue;
							$link = $this->core->handle_link($data['url'],$data['name'], $data['window'], 'pluslink'.$data['specialid']);
							$hash = $this->core->build_link_hash($link);
						}
					}
					
					$hidden = $arrItems[$item['item_id']]['hidden'];
					switch((int)$item['depth']){
						case 1: 	$intFirstLevel++;									
									$arrSorted[$intFirstLevel]['item'] = array('hash' => $hash, 'hidden' => $hidden);
						break;
						case 2:		$intSecondLevel++;
									$arrSorted[$intFirstLevel]['_childs'][$intSecondLevel]['item'] = array('hash' => $hash, 'hidden' => $hidden);
						break;
						case 3:		$arrSorted[$intFirstLevel]['_childs'][$intSecondLevel]['_childs'][] = array('hash' => $hash, 'hidden' => $hidden);
						break;
					}
					
				}
			}

			$this->config->set('mainmenu', serialize($arrSorted));
			$this->pdh->process_hook_queue();			
		}
				
		//Admin Favs
		$favs = ($this->in->getArray('fav', 'string'));
		$this->config->set('admin_favs', serialize($favs));
		
		redirect('admin/manage_menus.php'.$this->SID.'&status=saved');
	}

	public function delete_plink() {
		if ($this->in->get('id', 0) > 0){
			$this->pdh->put('links', 'delete_link', $this->in->get('id', 0));
			$this->pdh->process_hook_queue();
		}
		redirect('admin/manage_menus.php'.$this->SID);
	}

	// ---------------------------------------------------------
	// Display form
	// ---------------------------------------------------------
	public function display($messages=false){
		if($messages){
			$this->pdh->process_hook_queue();
			$this->core->messages($messages);
		}
		
		if ($this->in->get('status') == 'saved'){
			$this->core->message( $this->user->lang('pk_succ_saved'), $this->user->lang('save_suc'), 'green');
		}

		// Menus
		$arrOl = $this->build_menu_ol();
		$strMenuOl = $arrOl[0];
		$intMaxID = $arrOl[1];

		// The JavaScript Magic...
		$this->tpl->add_js('
			// Return a helper with preserved width of cells
			var fixHelper = function(e, ui) {
				ui.children().each(function() {
					$(this).width($(this).width());
				});
				return ui;
			};

			$("#sortable9, #sortable10 div div ul").sortable({
				connectWith: \'.connectedSortable5\',
				helper: fixHelper,
				cursor: \'pointer\',
				cancel: \'.not-sortable, tbody .not-sortable,.not-sortable tbody, .th_add, .td_add\',
				receive: function(event, ui){
					var classI = $(ui.item).attr("class");
					classI = classI.toString();
					var pos = $(ui.item).parents().attr("class");
						if (pos.indexOf("receiver") != -1){
							var content = $(ui.item).html();
							var Oclass = $(ui.item).attr(\'class\');
							var Oid = $(ui.item).attr(\'id\');
							var Oicon = document.getElementById("icon_"+Oid).innerHTML;
							$(ui.item).html(\'<img src="\'+Oicon+\'" alt="" /> \' +content + \'   <img src="../images/global/delete.png" onclick="removeThis(this.parentNode.id); 	$(this).parent().remove();" class="not-sortable" height="16" width="16" alt="" />\');
							document.getElementById("cb_"+Oid).checked = true;
						}

					}
			}).disableSelection();
			
			$(".equalHeights").equalHeights();
			
		', 'docready');

		$this->tpl->add_js('
			function removeThis(test){
				document.getElementById("cb_"+test).checked = false;
				var name = document.getElementById(test).innerHTML;
				var clas = document.getElementById(test).className;
				regex = new RegExp(\'(<img.*?>)\',\'gi\');
				do {
					found = false;
					if (regex.exec(name)) {
						found = true;
						name = name.replace(RegExp.$1, \'\');
					}
				} while (found != false)
				$("#t"+clas+" ul").append(\'<li class="\'+clas+\'" id="\'+test+\'">\'+name+\'</li>\');
				document.getElementById("cb_"+test).checked = false;
			}
		');
		
		$a_linkMode= array(
			'0'				=> $this->user->lang('pk_set_link_type_self'),
			'1'				=> $this->user->lang('pk_set_link_type_link'),
			'2'				=> $this->user->lang('pk_set_link_type_iframe'),
			'4'				=> $this->user->lang('pk_set_link_type_D_iframe_womenues'),
		);
					
		$a_linkVis= array(
			'0'				=> $this->user->lang('info_opt_vis_0'),
			'1'				=> $this->user->lang('info_opt_vis_1'),
			'2'				=> $this->user->lang('info_opt_vis_2'),
			'3'				=> $this->user->lang('info_opt_vis_3'),
		);
	

			$image_path = '../images/admin/';
			$admin_menu = $this->admin_index->adminmenu(false);
			unset($admin_menu['favorits']);
			$compare_array = array();
			$favs_array = array();
			if ($this->config->get('admin_favs')){
				$favs_array = unserialize(stripslashes($this->config->get('admin_favs')));
				$no_favs = true;
					if (is_array($favs_array)){
						foreach ($favs_array as $fav_key => $fav){
							$items = explode('|', $fav);
							$adm = $admin_menu;
							foreach ($items as $item){
								$latest = $adm;
								$adm = $adm[$item];
							}

							$link = preg_replace('#\?s\=([0-9A-Za-z]{1,32})?#', '', $adm['link']);

							$compare_array[] = $link;
							if ($adm['link']){
								$this->tpl->assign_block_vars('fav_row', array(
									'NAME' => $adm['text'],
									'ID'	=> 'fav_'.$fav_key,
									'ICON' => $image_path.$adm['icon'],
									'DATA'	=> $fav,
									'IDENT'	=> 'i'.md5($latest['name']),
									'GROUP'	=> $latest['name'],	
								));
								$this->tpl->add_js('document.getElementById("cb_fav_'.$fav_key.'").checked = true;', 'docready');
								$no_favs = false;
							}
								
						}
					}
			}

			// Header row
			if(is_array($admin_menu)){
				foreach($admin_menu as $k => $v){
					//Der große Block rundherum, einfach immer abfeuern
					$this->tpl->assign_block_vars('group_row', array(
						'ALPHA' => '', //irreleveat
					));

					// Restart next loop if the element isn't an array we can use
					if ( !is_array($v) ){continue;}
					
					$ident = 'i'.md5($v['name']);
					$this->jquery->Collapse('#container_'.$ident);
					$this->tpl->assign_block_vars('group_row.menu_row', array(
						'NAME' => '<img src="'.((isset($v['icon'])) ? $image_path.$v['icon'] : $image_path.'plugin.png').'" alt="" /> '.$v['name'],
						'GROUP'	=> $v['name'],
						'IDENT'	=> $ident,
					));

					// Generate the Menues
					if(is_array($v)){
						foreach ( $v as $k2 => $row ){

							$admnsubmenu = ((isset($row['link']) && isset($row['text'])) ? false : true);
							// Ignore the first element (header)
							if ( ($k2 == 'name' || $k2 == 'icon')){
								continue;
							}
							if($admnsubmenu){
								$this->tpl->assign_block_vars('group_row', array(
									'ALPHA' => '', //irreleveat
								));
								$ident = 'i'.md5($row['name']);
								$this->jquery->Collapse('#container_'.$ident);
								$this->tpl->assign_block_vars('group_row.menu_row', array(
									'NAME' => '<img src="'.((isset($row['icon'])) ? $image_path.$row['icon'] : $image_path.'plugin.png').'" alt="" /> '.$row['name'],
									'GROUP'	=> $row['name'],
									'IDENT'	=> $ident,
								));
								// Submenu
								if(!isset($row['link']) && !isset($row['text'])){
									if(is_array($row)){
										foreach($row as $k3 => $row2){
											if ($k3 == 'name' || $k3 =='icon'){
												continue;
											}

											$link = preg_replace('#\?s\=([0-9A-Za-z]{1,32})?#', '', $row2['link']);
											if (!in_array($link, $compare_array)){
												$this->tpl->assign_block_vars('group_row.menu_row.item_row', array(
													'NAME' => $row2['text'],
													'ID'	=> 'l'.md5($link),
													'ICON' => $image_path.$row2['icon'],
													'DATA'	=> $k.'|'.$k2.'|'.$k3,
												));
											}
										}
									}
								}
							}else{

								$link = preg_replace('#\?s\=([0-9A-Za-z]{1,32})?#', '', $row['link']);
								if (!in_array($link, $compare_array)){
									$this->tpl->assign_block_vars('group_row.menu_row.item_row', array(
										'NAME' => $row['text'],
										'ID'	=> 'l'.md5($link),
										'ICON' => $image_path.$row['icon'],
										'DATA'	=> $k.'|'.$k2,
									));
								}

							}
						}
					}
				}
		}
		

		$this->jquery->Tab_header('menu_tabs', true);
		if ($this->in->exists('tab')){
			$this->jquery->Tab_Select('menu_tabs', $this->in->get('tab',0));
		}
		$this->tpl->assign_vars(array(				
			'CSRF_MODE_TOKEN'		=> $this->CSRFGetToken('mode'),
			'S_NO_FAVS'				=> (count($favs_array) > 0) ? false : true,
			'DD_LINK_WINDOW'		=> $this->html->DropDown('editlink-window', $a_linkMode , '', '', '', 'editlink-window'),
			'DD_LINK_VISIBILITY'	=> $this->html->DropDown('editlink-visibility', $a_linkVis , '', '', '', 'editlink-visibility'),
			'MENU_OL'				=> $strMenuOl,
			'NEW_ID'				=> ++$intMaxID,
			'DD_ARTICLES'			=> $this->html->DropDown('editlink-article', $this->build_article_dropdown(), '', '', '', 'editlink-article'),
		));
		
		
				
		$this->core->set_vars(array(
			'page_title'		=> $this->user->lang('manage_menus'),
			'template_file'		=> 'admin/manage_menus.html',
			'display'			=> true)
		);
	}
	
	private function build_article_dropdown(){
		$arrItems = $this->core->build_menu_array(true);
		$arrOut[''] = "";
		foreach($arrItems as $k => $v){
			if ( !is_array($v) )continue;
			$id++;
			
			if (!isset($v['childs'])){
				if (!$this->check_for_hidden_article($v, $id)) {
					if (isset($v['category'])){
						$arrOut[$v['_hash']] = $this->pdh->get('article_categories', 'name_prefix', array($v['id'])).$this->pdh->get('article_categories', 'name', array($v['id']));
					} else {
						$catid = $this->pdh->get('articles', 'category', array($v['id']));
						$arrOut[$v['_hash']] = $this->pdh->get('article_categories', 'name_prefix', array($catid)).' -> '.$this->pdh->get('articles', 'title', array($v['id']));
					}
				}
			}
		}
		return $arrOut;
	}
	
	private function build_menu_ol(){
		$arrItems = $this->core->build_menu_array(true);
			
		$html  = '<ol class="sortable">';
		$id = 0;
		foreach($arrItems as $k => $v){
			if ( !is_array($v) )continue;
			$id++;
			
			if (!isset($v['childs'])){
				if (!$this->check_for_hidden_article($v, $id)) continue;
				$html .= '<li id="list_'.$id.'">'.$this->create_li($v, $id).'</li>';
				
			} else {
				$html .= '<li id="list_'.$id.'">'.$this->create_li($v, $id).'<ol>';
				
				foreach($v['childs'] as $k2 => $v2){
					$id++;
					if (!isset($v2['childs'])){
						$html .= '<li id="list_'.$id.'">'.$this->create_li($v2, $id).'</li>';
					} else {
						$html .= '<li id="list_'.$id.'">'.$this->create_li($v2, $id).'<ol>';
						
						foreach($v2['childs'] as $k3 => $v3){
							$id++;
							$html .= '<li id="list_'.$id.'">'.$this->create_li($v3, $id).'</li>';
							
						}
						
						$html .= '</ol></li>';
					}
					
				}
				
				$html .= '</ol></li>';
			}
		}

		$html .= '</ol>';
		
		return array($html, $id);
	}
	
	private function check_for_hidden_article($arrLink){
		if ((int)$arrLink['hidden'] && (isset($arrLink['article']) || isset($arrLink['category']))) return false;
		return true;
	}
	
	private function create_li($arrLink, $id){
		$hash = $arrLink['_hash'];
		$blnPluslink = (isset($arrLink['id']) && strpos($arrLink['id'], "pluslink") === 0);

		$html = '
			<div data-linkid="'.$id.'">
			<span class="ui-icon ui-icon-arrowthick-2-n-s" title="{L_dragndrop}" style="display:inline-block;"></span>
			<span class="link-hide '.(((int)$arrLink['hidden']) ? 'eye-gray' : 'eye').'"></span>';
			if ($blnPluslink){
				$plinkid = intval(str_replace("pluslink", "", $arrLink['id']));
				$arrPluslinkData = $this->pdh->get('links', 'data', array($plinkid));
				$html .= '<i class="icon-cog"></i><a href="javascript:void(0);" class="edit-menulink-trigger">'.$arrLink['text'].' ('.$arrLink['link'].')</a>
					<img src="'.$this->root_path.'images/global/delete.png" onclick="delete_plink('.$plinkid.', this)" alt="" title="'.$this->user->lang("delete").'" style="cursor:pointer;"/>
					<input type="hidden" value="'.$arrPluslinkData['url'].'"  name="mainmenu['.$id.'][url]" class="link-url">
					<input type="hidden" value="'.$arrPluslinkData['name'].'"  name="mainmenu['.$id.'][name]" class="link-name">
					<input type="hidden" value="'.$arrPluslinkData['window'].'"  name="mainmenu['.$id.'][window]" class="link-window">
					<input type="hidden" value="'.$arrPluslinkData['height'].'"  name="mainmenu['.$id.'][windowsize]" class="link-windowsize">
					<input type="hidden" value="'.$arrPluslinkData['visibility'].'"  name="mainmenu['.$id.'][visibility]" class="link-visibility">
					<input type="hidden" value="'.$plinkid.'"  name="mainmenu['.$id.'][specialid]" class="link-specialid">
				';
			} else {
				$html .= ''.$arrLink['text'].' ('.$this->user->removeSIDfromString($arrLink['link']).')';
			}	
			$html .= '
			<input type="hidden" value="'.(($blnPluslink) ? 'pluslink' : 'normal').'"  name="mainmenu['.$id.'][type]" class="link-type">			
			<input type="hidden" value="'.(((int)$arrLink['hidden']) ? 1 : 0).'"  name="mainmenu['.$id.'][hidden]" class="link-hidden">
			<input type="hidden" value="'.$hash.'"  name="mainmenu['.$id.'][id]" class="link-id">
			</div>
		';
		return $html;
	}
}
registry::register('Manage_Menus');
?>