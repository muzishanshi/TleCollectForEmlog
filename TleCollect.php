<?php
/*
Plugin Name: TleCollect For Emlog采集插件
Version: 1.0.1
Description: 一个使用QueryList采集工具的Emlog采集插件，包含采集单篇、多篇文章或视频内容而采集结果未知的插件，已验证可采集优酷视频及网站网站，但由于免不了的bug，不保证每一个人使用过程中的问题。
Plugin URL: https://github.com/muzishanshi/TleCollectForEmlog
ForEmlog: 5.3.1
Author: 二呆
Author URL: http://www.tongleer.com
*/
if(!defined('EMLOG_ROOT')){die('err');}
function tle_collect_menu(){
	echo '<div class="sidebarsubmenu"><a href="./plugin.php?plugin=TleCollect">同乐采集</a></div>';
}
addAction('adm_sidebar_ext', 'tle_collect_menu');