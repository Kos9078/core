<?php
/*	Project:	EQdkp-Plus
 *	Package:	EQdkp-plus
 *	Link:		http://eqdkp-plus.eu
 *
 *	Copyright (C) 2006-2015 EQdkp-Plus Developer Team
 *
 *	This program is free software: you can redistribute it and/or modify
 *	it under the terms of the GNU Affero General Public License as published
 *	by the Free Software Foundation, either version 3 of the License, or
 *	(at your option) any later version.
 *
 *	This program is distributed in the hope that it will be useful,
 *	but WITHOUT ANY WARRANTY; without even the implied warranty of
 *	MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 *	GNU Affero General Public License for more details.
 *
 *	You should have received a copy of the GNU Affero General Public License
 *	along with this program.  If not, see <http://www.gnu.org/licenses/>.
 */

if(!defined('EQDKP_INC'))
{
	die('Do not access this file directly.');
}

if(!class_exists('pdh_r_adjustment')){
	class pdh_r_adjustment extends pdh_r_generic{
		public static $shortcuts = array('apa' => 'auto_point_adjustments');

		public $default_lang = 'english';
		public $adjustments;

		public $hooks = array(
			'adjustment_update',
			'event_update',
			'item_update',
			'member_update',
			'raid_update'
		);

		public $presets = array(
			'adj_date'			=> array('date', array('%adjustment_id%'), array()),
			'adj_reason'		=> array('reason', array('%adjustment_id%'), array()),
			'adj_value'			=> array('value', array('%adjustment_id%'), array()),
			'adj_reason_link'	=> array('link', array('%adjustment_id%', '%link_url%', '%link_url_suffix%'), array()),
			'adj_event'			=> array('event_name', array('%adjustment_id%'), array()),
			'adj_member'		=> array('member_name', array('%adjustment_id%'), array()),
			'adj_members'		=> array('m4agk4a', array('%adjustment_id%'), array()),
			'adj_raid'			=> array('raid_id', array('%adjustment_id%', '%raid_link_url%', '%raid_link_url_suffix%'), array()),
			'adjedit'			=> array('editicon', array('%adjustment_id%', '%link_url%', '%link_url_suffix%'), array()),
		);

		private $decayed = array();

		public function reset($affected_ids=array()) {
			//tell apas which ids to delete
			if(empty($affected_ids) && !empty($this->adjustments)) $affected_ids = array_keys($this->adjustments);
			$this->apa->enqueue_update('adjustment', $affected_ids);
			$this->pdc->del('pdh_adjustment_table');
			$this->adjustments = NULL;
		}

		public function init(){
			//get cached data
			$this->adjustments = $this->pdc->get('pdh_adjustment_table');
			if($this->adjustments !== NULL){
				return true;
			}
			
			$objQuery = $this->db->query("SELECT * FROM __adjustments ORDER BY adjustment_date DESC;");
			if($objQuery){
				while($row = $objQuery->fetchAssoc()){
					$this->adjustments[$row['adjustment_id']]['value'] = $row['adjustment_value'];
					$this->adjustments[$row['adjustment_id']]['date'] = $row['adjustment_date'];
					$this->adjustments[$row['adjustment_id']]['member'] = $row['member_id'];
					$this->adjustments[$row['adjustment_id']]['reason'] = $row['adjustment_reason'];
					$this->adjustments[$row['adjustment_id']]['event'] = $row['event_id'];
					$this->adjustments[$row['adjustment_id']]['added_by'] = $row['adjustment_added_by'];
					$this->adjustments[$row['adjustment_id']]['updated_by'] = $row['adjustment_updated_by'];
					$this->adjustments[$row['adjustment_id']]['group_key'] = $row['adjustment_group_key'];
					$this->adjustments[$row['adjustment_id']]['raid_id'] = $row['raid_id'];
				}
				
				$this->pdc->put('pdh_adjustment_table', $this->adjustments, null);
			}
	
		}

		public function get_id_list(){
			return (is_array($this->adjustments)) ? array_keys($this->adjustments) : array();
		}

		public function get_value($adj_id, $dkp_id=0, $date=0) {
			if($dkp_id) {
				if(!isset($this->decayed[$dkp_id])) $this->decayed[$dkp_id] = $this->apa->is_decay('adjustment', $dkp_id);
				if($this->decayed[$dkp_id]) {
					$data = array('id' => $adj_id, 'value' => $this->adjustments[$adj_id]['value'], 'date' => $this->adjustments[$adj_id]['date']);
					$val = $this->apa->get_decay_val('adjustment', $dkp_id, $date, $data);
				}
			}
			return (isset($val)) ? $val : $this->adjustments[$adj_id]['value'];
		}

		public function get_html_value($adj_id, $dkp_id=0) {
			return '<span class="' . color_item($this->get_value($adj_id, $dkp_id)) . '">'.runden($this->get_value($adj_id, $dkp_id)).'</span>';
		}

		public function get_caption_value($dkp_id=0) {
			$caption = '';
			if($dkp_id && $this->apa->is_decay('adjustment', $dkp_id)) $caption = $this->apa->get_caption('adjustment', $dkp_id);
			return ($caption) ? $caption : $this->pdh->get_lang('adjustment', 'value');
		}

		public function get_date($adj_id){
			return $this->adjustments[$adj_id]['date'];
		}

		public function get_html_date($adj_id) {
			return $this->time->user_date($this->get_date($adj_id));
		}

		public function get_member($adj_id){
			return $this->adjustments[$adj_id]['member'];
		}

		public function get_member_name($adj_id) {
			return $this->pdh->get('member', 'name', array($this->get_member($adj_id)));
		}

		public function get_html_member_name($adj_id) {
			return $this->pdh->geth('member', 'name', array($this->get_member($adj_id)));
		}

		public function get_event($adj_id) {
			return $this->adjustments[$adj_id]['event'];
		}

		public function get_event_name($adj_id) {
			return $this->pdh->get('event', 'name', array($this->adjustments[$adj_id]['event']));
		}

		public function get_reason($adj_id){
			return isset($this->adjustments[$adj_id]) ? $this->adjustments[$adj_id]['reason'] : '';
		}

		public function get_raid_id($adj_id){
			return $this->adjustments[$adj_id]['raid_id'];
		}
		
		public function get_html_raid_id($adj_id, $base_url, $url_suffix = ''){
			return '<a href="'.$this->pdh->get('raid', 'raidlink', array($this->get_raid_id($adj_id), $base_url, $url_suffix)).'">'.$this->pdh->get('raid', 'event_name', array($this->get_raid_id($adj_id))).'</a>';
		}
		

		public function get_adjsofmember($member_id){
			$adjustment_ids = array();
			if (is_array($this->adjustments)){
				foreach($this->adjustments as $id => $details){
					if($details['member'] == $member_id){
						$adjustment_ids[] = $id;
					}
				}
			}
			return $adjustment_ids;
		}
		
		public function get_adjsofmembers($arrMemberIDs){
			$adj4member = array();
			foreach($arrMemberIDs as $member_id){
				$arrAdj = $this->get_adjsofmember($member_id);
				if (is_array($arrAdj)) $adj4member = array_merge($adj4member, $arrAdj);
			}
			return array_unique($adj4member);
		}

		
		public function get_adjsofuser($user_id){
			$arrMemberList = $this->pdh->get('member', 'connection_id', array($user_id));
			$adjustment_ids = array();
			if (is_array($this->adjustments)){
				foreach($this->adjustments as $id => $details){
					if(in_array($details['member'],$arrMemberList)){
						$adjustment_ids[] = $id;
					}
				}
			}
			return $adjustment_ids;
		}

		public function get_adjsofraid($raid_id){
			$adjustment_ids = array();
			if(is_array($this->adjustments)){
				foreach($this->adjustments as $id => $adj){
					if($raid_id == $adj['raid_id']){
						$adjustment_ids[] = $id;
					}
				}
			}
			return $adjustment_ids;
		}

		public function get_adjsofeventid($event_id) {
			$adjs_ids = array();
			if(is_array($this->adjustments)) {
				foreach($this->adjustments as $id => $adj) {
					if($event_id == $adj['event']) $adjs_ids[] = $id;
				}
			}
			return $adjs_ids;
		}

		public function get_group_key($adj_id){
			return $this->adjustments[$adj_id]['group_key'];
		}

		public function get_ids_of_group_key($group_key){
			$ids = array();
			foreach($this->adjustments as $id => $det){
				if($det['group_key'] == $group_key){
					$ids[] = $id;
				}
			}
			return $ids;
		}

		public function get_link($adj_id, $baseurl, $url_suffix=''){
			return $baseurl.$this->SID.'&amp;a='.$adj_id.$url_suffix;
		}

		public function get_html_link($adj_id, $baseurl, $url_suffix='', $type='reason') {
			$allowed_types = array('reason', 'member');
			$type = (in_array($type, $allowed_types)) ? $type : 'reason';
			return "<a href='".$this->get_link($adj_id, $baseurl, $url_suffix)."'>".call_user_func_array(array($this, 'get_'.$type), array($adj_id))."</a>";
		}
		
		public function comp_link($params1, $params2){
			$method = (isset($params1[3]) && $params1[3] == 'member') ? 'get_member' : 'get_reason';
			return ($this->$method($params1[0]) < $this->$method($params2[0])) ? -1  : 1 ;
			
		}

		public function get_editicon($adj_id, $baseurl, $url_suffix='') {
			$out = "<a href='".$this->get_link($adj_id, $baseurl, $url_suffix)."'>
						<i class='fa fa-pencil fa-lg' title='".$this->user->lang('edit')."'></i>
					</a>";
			
			$out .= '&nbsp;&nbsp;&nbsp;<a href="'.$this->get_link($adj_id, $baseurl, '&copy=true').'">
				<i class="fa fa-copy fa-lg" title="'.$this->user->lang('copy').'"></i>
			</a>';
				
			return $out;
		}

		public function get_m4agk4a($adj_id) {
			return $this->pdh->aget('adjustment', 'member_name', 0, array($this->get_ids_of_group_key($this->get_group_key($adj_id))));
		}

		public function get_html_m4agk4a($adj_id) {
			return implode(', ', $this->pdh->aget('adjustment', 'html_member_name', 0, array($this->get_ids_of_group_key($this->get_group_key($adj_id)))));
		}
		
		public function comp_m4agk4a($params1, $params2){
			$members1 = implode(', ', $this->pdh->aget('adjustment', 'member_name', 0, array($this->get_ids_of_group_key($this->get_group_key($params1[0])))));
			$members2 = implode(', ', $this->pdh->aget('adjustment', 'member_name', 0, array($this->get_ids_of_group_key($this->get_group_key($params2[0])))));
			return ($members1 < $members2) ? -1  : 1 ;
		}
	}
}
?>
