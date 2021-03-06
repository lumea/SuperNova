<?php

include('common.' . substr(strrchr(__FILE__, '.'), 1));

define('SN_IN_ALLY', true);

// Main admin page save themes
lng_include('alliance');

$mode = sys_get_param_str('mode');

if($mode == 'ainfo')
{
  include('includes/alliance/ali_info.inc');
};

if(!$user['ally_id'])
{
  $user_request = doquery("SELECT * FROM {{alliance_requests}} WHERE `id_user` ='{$user['id']}' LIMIT 1;", '', true);
  if($user_request['id_user'])
  {
    require('includes/alliance/ali_external_request.inc');
  }
  else
  {
    switch($mode)
    {
      case 'search':
        require('includes/alliance/ali_external_search.inc');
      break;

      case 'apply':
        require('includes/alliance/ali_external_request.inc');
      break;

      case 'make':
        require('includes/alliance/ali_external_create_ally.inc');
      break;

      default:
        display(parsetemplate(gettemplate('ali_external', true)), $lang['alliance']);
      break;
    }
  }
}

sn_ali_fill_user_ally($user);
//$ally = doquery("SELECT * FROM {{alliance}} WHERE `id` ='{$user['ally_id']}'", '', true);
if(!isset($user['ally']))
{
  doquery("UPDATE {{users}} SET `ally_id` = null, `ally_name` = null, `ally_register_time` = 0, `ally_rank_id` = 0  WHERE `id`='{$user['id']}' LIMIT 1;");
  message($lang['ali_sys_notFound'], $lang['your_alliance'], 'alliance.php');
}
$ally = &$user['ally'];

$rights = array(
  0 => 'name',
  1 => 'mail',
  2 => 'online',
  3 => 'invite',
  4 => 'kick',
  5 => 'admin',
  6 => 'forum',
  7 => 'diplomacy'
);
$rights_old = array(
  0 => 'name',
  1 => 'mails',
  2 => 'onlinestatus',
  3 => 'bewerbungenbearbeiten',
  4 => 'kick',
  5 => 'rechtehand'
);

// This piece converting old ally data to new one
//  unset($ally['ranklist']);
if(!$ally['ranklist'] && $ally['ally_ranks'])
{
  $ally_ranks = unserialize($ally['ally_ranks']);
  $i = 0;
  foreach($ally_ranks as $rank_id => $rank)
  {
    foreach($rights as $key => $value)
    {
      $ranks[$i][$value] = $rank[$rights_old[$key]];
    }
    doquery("UPDATE {{users}} SET `ally_rank_id` = {$i} WHERE `ally_id` ='{$user['ally_id']}' AND `ally_rank_id`={$rank_id};", '', true);
    $i++;
  }

  if(!empty($ranks))
  {
    ali_rank_list_save($ranks);
  }
}

if($ally['ranklist'])
{
  $str_ranks = explode(';', $ally['ranklist']);
  foreach($str_ranks as $str_rank)
  {
    if(!$str_rank)
    {
      continue;
    }

    $tmp = explode(',', $str_rank);
    $rank_id = count($ranks);
    foreach($rights as $key => $value)
    {
      $ranks[$rank_id][$value] = $tmp[$key];
    }
  }
}

$isAllyOwner = $ally['ally_owner'] == $user['id'];
$user_can_send_mails = $ranks[$user['ally_rank_id']]['mail'] || $isAllyOwner;
$userCanPostForum = $ranks[$user['ally_rank_id']]['forum'] || $isAllyOwner;
$user_onlinestatus = $ranks[$user['ally_rank_id']]['online'] || $isAllyOwner;
$user_admin_applications = $ranks[$user['ally_rank_id']]['invite'] || $isAllyOwner;
$user_can_kick = $ranks[$user['ally_rank_id']]['kick'] || $isAllyOwner;
$user_can_negotiate = $ranks[$user['ally_rank_id']]['diplomacy'] || $isAllyOwner;
$user_can_edit_rights = $user_admin = $ranks[$user['ally_rank_id']]['admin'] || $isAllyOwner;

$edit = sys_get_param_str('edit');
ally_pre_call();
switch($mode)
{
  case 'admin':
    if(!array_key_exists($edit , $sn_ali_admin_internal))
    {
      $edit = 'default';
    }
    if($sn_ali_admin_internal[$edit]['include'])
    {
      require("includes/{$sn_ali_admin_internal[$edit]['include']}");
    }
    if(isset($sn_ali_admin_internal[$edit]['function']) && is_callable($sn_ali_admin_internal[$edit]['function']))
    {
      call_user_func($sn_ali_admin_internal[$edit]['function']);
    }
  break;

  case 'memberslist':  require('includes/alliance/ali_internal_members.inc'); break;
  case 'circular':     require('includes/alliance/ali_internal_admin_mail.inc'); break;
  default:             require('includes/alliance/ali_info.inc'); break;
}

function ally_pre_call(){$func_args = func_get_args();return sn_function_call('ally_pre_call', $func_args);}

?>
