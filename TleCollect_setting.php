<?php if(!defined('EMLOG_ROOT')){die('err');}?>
<?php
require 'collect/phpQuery/phpQuery.php';
require 'collect/QueryList/QueryList.php';
require 'collect/QueryList/Ext/AQuery.php';
require 'collect/QueryList/Ext/Request.php';
require 'collect/QueryList/Ext/Http.php';
require 'collect/QueryList/Ext/Multi.php';
require 'collect/QueryList/Ext/CurlMulti.php';

use QL\QueryList;

$Log_Model = new Log_Model();
$Tag_Model = new Tag_Model();

$action = isset($_POST['action']) ? trim($_POST['action']) : '';
$client_id = isset($_POST['client_id']) ? trim($_POST['client_id']) : '';
if ($action=='collect') {
	$postDate = isset($_POST['postdate']) ? trim($_POST['postdate']) : '';
	$date = isset($_POST['date']) ? addslashes($_POST['date']) : '';//修改前的文章时间
	$pid = isset($_POST['pid']) ? $_POST['pid'] : '';
	$address = isset($_POST['address']) ? $_POST['address'] : '';
	$character = isset($_POST['character']) ? $_POST['character'] : 'utf-8';
	$coverimg = isset($_POST['coverimg']) ? $_POST['coverimg'] : '';
	$coverimgattr = isset($_POST['coverimgattr']) ? $_POST['coverimgattr'] : '';
	$title = isset($_POST['title']) ? $_POST['title'] : '';
	$titleattr = isset($_POST['titleattr']) ? $_POST['titleattr'] : '';
	$titleprefix = isset($_POST['titleprefix']) ? $_POST['titleprefix'] : '';
	$content = isset($_POST['content']) ? $_POST['content'] : '';
	$filterContent = isset($_POST['filterContent']) ? $_POST['filterContent'] : '';
	$postTime = $Log_Model->postDate(Option::get('timezone'), $postDate, $date);
	
	LoginAuth::checkToken();
	
	if(!isset($address)||!isset($title)||!isset($content)){
		emDirect("plugin.php?plugin=TleCollect&active_error=1");
	}
	if(strpos($address,'youku.com')){
		$html='value';$iframe='';
	}else{
		$html='html';$iframe='iframe';
	}
	//采集某页面所有的图片
	$data = QueryList::Query($address,array(
		//采集规则库
		//'规则名' => array('jQuery选择器','要采集的属性'),
		'coverimg' => array($coverimg,$coverimgattr),
		'title' => array($title,$titleattr),
		'content' => array($content,$html,$filterContent),
		'embed' => array('embed','src'),
		'iframe' => array($iframe,'src')
		),'','utf-8',$character)->data;
	
	$content=str_replace('\'', '"', $data[0]["content"]);
	if(strpos($content,'client_id: "')){
		$temp=substr($content,strpos($content,'client_id: "')+12);
		$client_id=substr($temp,0,strpos($temp,'"'));
		$content=str_replace($client_id, $client_id, $content);
		if(strpos($content,'onPlayEnd')){
			$content=preg_replace("/onPlayEnd:\sfunction\(\)\{[\w\W]*;[^\}]*\}{1}/", 'onPlayEnd: function(){}', $content);
		}
		if(strpos($content,'youkuplayer')){
			$content=preg_replace("/\<div\sid=\"youkuplayer\"[\w\W]*\>{1}(\<\/div\>){1}/", '<div id="youkuplayer" style="width:50%;height:300px;margin:0 auto;"></div>', $content);
		}
	}else if(strpos($content,'<embed')!== false){
		$temp=str_replace('/v.swf', '', $data[0]["embed"]);
		$client_id=substr($temp,strrpos($temp,'/')+1);
		$content=str_replace($client_id, $client_id, $content);
	}else if(strpos($content,'<iframe')!== false){
		if(strpos($address,'youku.com')){
			$temp=str_replace('<iframe height=498 width=510 src="http://player.youku.com/embed/', '', $content);
			$video_id=substr($temp,0,strpos($temp,'"'));
			$content='<iframe height=498 width=100% src="http://player.youku.com/embed/'.$video_id.'?client_id='.$client_id.'" frameborder=0 allowfullscreen></iframe>';
		}else{
			$temp=strpos($data[0]["iframe"],'client_id=')+10;
			$client_id=substr($data[0]["iframe"],$temp);
			$content=str_replace($client_id, $client_id, $content);
		}
	}
	if(strpos($address,'youku.com')===false){
		$titleSql=$titleprefix.$data[0]["title"];
	}else{
		$titleSql=$data[0]["title"];
	}
	if($data[0]['coverimg']!=''){
		$content='<img src="'.$data[0]['coverimg'].'.jpg" alt="'.$titleSql.'" style="display:none;" />'.$content;
	}
	$content.='';
	$logData = array(
		'title' => $titleSql,
		'alias' => '',
		'content' => $content,
		'excerpt' => $titleSql,
		'author' => 1,
		'sortid' => $pid,
		'date' => $postTime,
		'top '=> 'n',
		'sortop '=> 'n',
		'allow_remark' => 'y',
		'hide' => 'n',
		'checked' => $user_cache[UID]['ischeck'] == 'y' ? 'n' : 'y',
		'password' => ''
	);
	if (!$Log_Model->isRepeatPost($titleSql, $postTime)) {
		$blogid=$Log_Model->addlog($logData);
		$Tag_Model->addTag($titleprefix, $blogid);
	}
	
	$CACHE->updateCache();
	
	emDirect("plugin.php?plugin=TleCollect&active_start=1");
}else if($action=='collectmul'){
	$postDate = isset($_POST['postdate']) ? trim($_POST['postdate']) : '';
	$date = isset($_POST['date']) ? addslashes($_POST['date']) : '';//修改前的文章时间
	$pid = isset($_POST['pid']) ? $_POST['pid'] : '';
	$address = isset($_POST['address']) ? $_POST['address'] : '';
	$coverimg = isset($_POST['coverimg']) ? $_POST['coverimg'] : '';
	$coverimgattr = isset($_POST['coverimgattr']) ? $_POST['coverimgattr'] : '';
	$character = isset($_POST['character']) ? $_POST['character'] : 'utf-8';
	$container = isset($_POST['container']) ? $_POST['container'] : '';
	$filter = isset($_POST['filter']) ? $_POST['filter'] : '';
	$filterContent = isset($_POST['filterContent']) ? $_POST['filterContent'] : '';
	$title = isset($_POST['title']) ? $_POST['title'] : '';
	$titleattr = isset($_POST['titleattr']) ? $_POST['titleattr'] : '';
	$titleprefix = isset($_POST['titleprefix']) ? $_POST['titleprefix'] : '';
	$filterTitle = isset($_POST['filterTitle']) ? $_POST['filterTitle'] : '';
	$content = isset($_POST['content']) ? $_POST['content'] : '';
	
	$postTime = $Log_Model->postDate(Option::get('timezone'), $postDate, $date);
	
	LoginAuth::checkToken();
	
	if(!isset($pid)||!isset($address)||!isset($container)||!isset($title)||!isset($content)){
		emDirect("plugin.php?plugin=TleCollect&page=mul&active_error=1");
	}
	//采集某页面所有的图片
	$domainStart=strpos($address,'http://')+7;
	$domainEnd=strpos($address,'/',$domainStart);
	$domain=substr($address,0,$domainEnd);
	$params=array('var1' => 'testvalue', 'var2' => 'somevalue');
	$urls = QueryList::run('Request',array(
			'target' => $address,
			'referrer'=>$domain,
			'method' => 'GET',
			'params' => $params,
			'user_agent'=>'Mozilla/5.0 (Macintosh; Intel Mac OS X 10.8; rv:21.0) Gecko/20100101 Firefox/21.0',
			'cookiePath' => './cookie.txt',
			'timeout' =>'60'
		))->setQuery(array('link' => array($container,'href',$filter,function($body){
		//利用回调函数补全相对链接
		global $domain;
		if(strpos($body,'http')===false){
			$link=$domain.$body;
		}else{
			$link=$body;
		}
		return $link;
	})),'','utf-8',$character)->getData(function($item){
		return $item['link'];
	});
	if(strpos($address,'youku.com')){
		$urls_youku=array();
		for($i=0;$i<count($urls);$i++){
			if($i%2==0){
				$urls_youku[$i]="http:".substr($urls[$i],21);
			}
		}
		$urls=$urls_youku;
	}
	//多线程扩展
	QueryList::run('Multi',array(
		'list' => $urls,
		'curl' => array(
			'opt' => array(
						CURLOPT_SSL_VERIFYPEER => false,
						CURLOPT_SSL_VERIFYHOST => false,
						CURLOPT_FOLLOWLOCATION => true,
						CURLOPT_AUTOREFERER => true,
					),
			//设置线程数
			'maxThread' => 100,
			//设置最大尝试数
			'maxTry' => 3
		),
		'success' => function($a){
			global $address,$title,$titleprefix,$content,$pid,$postTime,$Log_Model,$Tag_Model,$filterContent,$filterTitle,$coverimg,$coverimgattr;
			if(strpos($address,'youku.com')){
				$html='value';$iframe='';
			}else{
				$html='html';$iframe='iframe';
			}
			//采集规则
			$reg = array(
				'coverimg' => array($coverimg,$coverimgattr),
				//采集文章标题
				'title' => array($title,$titleattr),
				//采集文章正文内容,利用过滤功能去掉文章中的超链接，但保留超链接的文字，并去掉版权、JS代码等无用信息
				'content' => array($content,$html,$filterContent),
				'embed' => array('embed','src'),
				'iframe' => array($iframe,'src')
				);
			$ql = QueryList::Query($a['content'],$reg,'');
			$data = $ql->getData();
			//处理内容
			if($data[0]["title"]==''||$data[0]["content"]==''){
				return;
			}
			$filterTitleArr=explode(' ',$filterTitle);
			foreach($filterTitleArr as $key => $value){
				if(strpos($data[0]["title"],$value)){
					return;
				}
			}
			$log=str_replace('\'', '"', $data[0]["content"]);
			if(strpos($log,'client_id: "')){
				$temp=substr($log,strpos($log,'client_id: "')+12);
				$client_id=substr($temp,0,strpos($temp,'"'));
				$log=str_replace($client_id, $client_id, $log);
				if(strpos($log,'onPlayEnd')){
					$log=preg_replace("/onPlayEnd:\sfunction\(\)\{[\w\W]*;[^\}]*\}{1}/", 'onPlayEnd: function(){}', $log);
				}
				if(strpos($log,'youkuplayer')){
					$log=preg_replace("/\<div\sid=\"youkuplayer\"[\w\W]*\>{1}(\<\/div\>){1}/", '<div id="youkuplayer" style="width:300px;height:300px;"></div>', $log);
				}
			}else if(strpos($log,'<embed')!== false){
				$temp=str_replace('/v.swf', '', $data[0]["embed"]);
				$client_id=substr($temp,strrpos($temp,'/')+1);
				$log=str_replace($client_id, $client_id, $log);
			}else if(strpos($log,'<iframe')!== false){
				if(strpos($address,'youku.com')){
					$temp=str_replace('<iframe height=498 width=510 src="http://player.youku.com/embed/', '', $log);
					$video_id=substr($temp,0,strpos($temp,'"'));
					$log='<iframe height=498 width=100% src="http://player.youku.com/embed/'.$video_id.'?client_id='.$client_id.'" frameborder=0 allowfullscreen></iframe>';
				}else{
					$temp=strpos($data[0]["iframe"],'client_id=')+10;
					$client_id=substr($data[0]["iframe"],$temp);
					$log=str_replace($client_id, $client_id, $log);
				}
			}
			if(strpos($address,'youku.com')===false){
				$titleSql=$titleprefix.$data[0]["title"];
			}else{
				$titleSql=$data[0]["title"];
			}
			if($data[0]['coverimg']!=''){
				$log='<img src="'.$data[0]['coverimg'].'.jpg" alt="'.$titleSql.'" style="display:none;" />'.$log;
			}
			$log.='<p style="text-align:center;"><span style="line-height:1.5;color:red;">如果觉得此视频非常给力，请随意打赏几毛。您的支持将鼓励作者继续发布！</span></p>';
			//打印结果，实际操作中这里应该做入数据库操作
			$logData = array(
				'title' => $titleSql,
				'alias' => '',
				'content' => $log,
				'excerpt' => $titleSql,
				'author' => 1,
				'sortid' => $pid,
				'date' => $postTime,
				'top '=> 'n',
				'sortop '=> 'n',
				'allow_remark' => 'y',
				'hide' => 'n',
				'checked' => $user_cache[UID]['ischeck'] == 'y' ? 'n' : 'y',
				'password' => ''
			);
			if (!$Log_Model->isRepeatPost($titleSql, $postTime)) {
				$blogid=$Log_Model->addlog($logData);
				$Tag_Model->addTag($titleprefix, $blogid);
			}
		}
	));
	
	$CACHE->updateCache();
	
	emDirect("plugin.php?plugin=TleCollect&page=mul&active_start=1");
}
?>
<?php
function plugin_setting_view(){
	$version=file_get_contents('http://api.tongleer.com/interface/TleCollect.php?action=update&version=1');
	echo $version;
	
	$Sort_Model = new Sort_Model();
	$sorts = $Sort_Model->getSorts();
	?>
	<p>
	<?php if(isset($_GET['active_start'])):?><span>采集结束</span><?php endif;?>
	<?php if(isset($_GET['active_error'])):?><span>采集参数出错</span><?php endif;?>
	<br />
	<a href="plugin.php?plugin=TleCollect">采集单篇</a>
	<a href="plugin.php?plugin=TleCollect&page=mul">采集多篇</a>
	<br />
	优酷client_id(已过时)：<input maxlength="255" value="<?php echo $client_id; ?>" name="client_id" placeholder="可选" />
	</p>
	<?php if($_GET['page']==''){?>
	<form action="" method="post">
	<div>
		<li>
		采集到<select name="pid" id="pid">
			<option value="-1">无</option>
			<?php
				foreach($sorts as $key=>$value):
					if($value['pid'] == 0) {
						continue;
					}
			?>
					<option value="<?php echo $value['sid']; ?>"><?php echo $value['sortname']; ?></option>
			<?php endforeach; ?>
		</select>分类
		</li>
		<li>
			采集网址<input maxlength="255" value="<?php echo $address; ?>" name="address" />
			字符集<input maxlength="255" value="<?php if($character!=''){echo $character;}else{echo 'utf-8';} ?>" name="character" />
		</li>
		<li>
			缩略图选择器<input maxlength="255" value="<?php echo $coverimg; ?>" name="coverimg" />
			缩略图选择器属性<input maxlength="255" value="<?php echo $coverimgattr?$coverimgattr:"src"; ?>" name="coverimgattr" />
		</li>
		<li>
			标题选择器<input name="title" value="<?php echo $title; ?>" maxlength="200" />
			标题属性<input name="titleattr" value="<?php echo $titleattr; ?>" maxlength="200" />
			标题前缀<input name="titleprefix" value="<?php echo $titleprefix; ?>" maxlength="200" />
		</li>
		<li>
			内容选择器<input name="content" value="<?php echo $content; ?>" maxlength="200" />
			内容过滤器(选填)<input name="filterContent" value="<?php echo $filterContent; ?>" maxlength="200" />
		</li>
		<li>
			<input name="action" value="collect" type="hidden" />
			<input name="token" id="token" value="<?php echo LoginAuth::genToken(); ?>" type="hidden" />
			<input type="submit" value="开始采集" />
			<input maxlength="200" type="hidden" name="postdate" id="postdate" value="<?php echo $postDate; ?>"/>
			<input name="date" id="date" type="hidden" value="" >
		</li>
		<li>
			<p>
			搞笑视频网：标题：.picdl img标题属性：alt内容：.pck<br />
			优酷：标题：#subtitle标题属性：title内容：#link4缩略图选择器：.item-cover.current .cover img
			</p>
		</li>
	</div>
	</form>
	<?php }else if($_GET['page']=='mul'){?>
	<form action="" method="post">
	<div>
		<li>
		采集到<select name="pid" id="pid">
			<option value="-1">无</option>
			<?php
				foreach($sorts as $key=>$value):
					if($value['pid'] == 0) {
						continue;
					}
			?>
					<option value="<?php echo $value['sid']; ?>"><?php echo $value['sortname']; ?></option>
			<?php endforeach; ?>
		</select>分类
		</li>
		<li>
			采集网址<input maxlength="255" value="<?php echo $address; ?>" name="address" />
			字符集<input maxlength="255" value="<?php if($character!=''){echo $character;}else{echo 'utf-8';} ?>" name="character" />
		</li>
		<li>
			缩略图选择器<input maxlength="255" value="<?php if($coverimg!=''){echo $coverimg;}else{echo '.item-cover.current .cover img';} ?>" name="coverimg" />
			缩略图选择器属性<input maxlength="255" value="<?php if($coverimgattr!=''){echo $coverimgattr;}else{echo 'src';} ?>" name="coverimgattr" />
		</li>
		<li>
			列表容器选择器<input name="container" value="<?php if($container!=''){echo $container;}else{echo '.box-video a';} ?>" maxlength="200" />
			列表容器过滤器(选填)<input name="filter" value="<?php if($filter!=''){echo $filter;}else{echo '.info-list';} ?>" maxlength="200" />
		</li>
		<li>
			标题选择器<input name="title" value="<?php if($title!=''){echo $title;}else{echo '#subtitle';} ?>" maxlength="200" />
			标题属性<input name="titleattr" value="<?php echo $titleattr; ?>" maxlength="200" />
			标题前缀<input name="titleprefix" value="<?php echo $titleprefix; ?>" maxlength="200" />
			标题过滤用空格分隔<input name="filterTitle" value="<?php if($filterTitle!=''){echo $filterTitle;}else{echo '综艺 电影 剧集 动漫';} ?>" maxlength="200" />
		</li>
		<li>
			内容选择器<input name="content" value="<?php if($content!=''){echo $content;}else{echo '#link4';} ?>" maxlength="200" />
			内容过滤器(选填)<input name="filterContent" value="<?php echo $filterContent; ?>" maxlength="200" />
		</li>
		<li>
			<input name="action" value="collectmul" type="hidden" />
			<input name="token" id="token" value="<?php echo LoginAuth::genToken(); ?>" type="hidden" />
			<input type="submit" value="开始采集" />
			<input maxlength="200" type="hidden" name="postdate" id="postdate" value="<?php echo $postDate; ?>"/>
			<input name="date" id="date" type="hidden" value="" >
		</li>
		<li>
			<p>
			搞笑视频网：标题：h1:last内容：.pck列表：.ptb10 a列表过滤：-.pt10缩略图选择器：.bodyn:first img:first-child<br />
			优酷：<br />
			标题：#subtitle标题属性：title内容：#link4列表：.box-video a 列表容器过滤器(选填)：.info-list缩略图选择器：.item-cover.current .cover img标题过滤：综艺 电影 剧集 动漫
			</p>
		</li>
	</div>
	</form>
	<?php }?>
	<p>
	以上示例仅供参考，如果对应网站有所更新，需要适当修改<br />
	特别注意：<br />
	1、采集优酷视频频率不能过快，否则会被优酷限制采集，不过过一会即可重新采集。<br />
	2、采集文章和视频同理，不过依然要求自己到网页源代码寻找选择器，进行采集。<br />
	3、此采集功能需少量技术，而且不太人性化，如果不能满足需求，可选择其他采集插件，谢谢使用。
	</p>
	<?php
}
?>