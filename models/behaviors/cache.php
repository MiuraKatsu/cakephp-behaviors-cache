<?php
class CacheBehavior extends ModelBehavior {
	var $enabled = true;
	
	var $config = array();

	var $_cache_log = array();
	
	function setup(&$model, $config = array()) {
		$this->config[$model->alias] = $config;
		if(Configure::Read('Cache.disable')) $this->enabled = false;
	}
	
	//config設定
	function _setConfig(&$model,$config = null){
		if(empty($config)){
			$config = $this->config[$model->alias];
		}

		if(is_array($config)){
			if(!empty($config['config'])){
				$config = $config['config'];
			}else{
				$config = null;
			} 
		}
		return $config;
	}
	
	/**
	 * メソッドキャッシュ
	 */
	function cacheMethod(&$model, $method, $args = array(),$config = null){
		$config = $this->_setConfig($model,$config);
		
		$this->enabled = false;
		// キャッシュキー
		$cachekey = $this->createCacheKey($model, $method, $args ,$config);

		$ret = Cache::read($cachekey,$config);
		$this->logCache('read',$cachekey,$ret,$config,$ret);	
		
		if(!empty($ret)){
			$this->enabled = true;
			return $ret;
		}
		//
		$ret = call_user_func_array(array($model, $method), $args);
		$this->enabled = true;
		$w_ret = Cache::write($cachekey, $ret, $config);
		$this->logCache('write',$cachekey,$ret,$config,$w_ret);	

		// クリア用にモデル毎のキャッシュキーリストを作成
		$cacheListKey = get_class($model) . '_cacheMethodList';
		$list = Cache::read($cacheListKey);
		$this->logCache('read',$cacheListKey,$list,null,$list);	

		$list[$cachekey] = $config;
		$w_ret = Cache::write($cacheListKey, $list);
		$this->logCache('write',$cacheListKey,$list,null,$w_ret);	

		return $ret;
	}
	
	/**
	 * キャッシュキーの生成
	 *
	 */
	function createCacheKey(&$model, $method, $args = array(),$config = null){
		return  get_class($model) . '_' . $method . '_' . $this->_setConfig($model,$config)  . '_' . md5(serialize($args));
	}
	/**
	 * 再帰防止判定用
	 */
	function cacheEnabled(&$model){
		return $this->enabled;
	}
	/**
	 * キャッシュ個別クリア
	 */
	function cacheDelete(&$model, $method, $args = array(),$config = null){
		$config = $this->_setConfig($model,$config);
		$cacheListKey = $this->createCacheKey($model, $method, $args, $config);
		$d_ret = Cache::delete($cacheListKey,$config);
		$this->logCache('delete',$cacheListKey,null,$config,$d_ret);	
	}
	/**
	 * キャッシュ全クリア
	 */
	function cacheDeleteAll(&$model){
		$cacheListKey = get_class($model) . '_cacheMethodList';
		$list = Cache::read($cacheListKey);
		$this->logCache('read',$cacheListKey,$list,$config,$list);	

		if(empty($list)) return;
		foreach($list as $key => $config){
			$d_ret = Cache::delete($key,$config);
			$this->logCache('delete',$key,null,$config,$d_ret);	
		}
		$d_ret = Cache::delete($cacheListKey);
		$this->logCache('read',$cacheListKey,null,$config,$d_ret);	
		return true;
	}
	/**
	 * 追加・変更・削除時にはキャッシュをクリア
	 */
	function afterSave(&$model, $created) {
		$this->cacheDeleteAll($model);
	}
	function afterDelete(&$model) {
		$this->cacheDeleteAll($model);
	}

	function logCache($method,$key,$value = null,$config = null,$result = null){
		$result = var_export($result,true);
		if(!($result === 'false') and !($result === 'NULL')){
			$result = 'true';
		}
		$this->_cache_log[] =  array(
				'method'  =>$method,
				'key'     =>$key,
				'config'  =>$config,
				'value'   =>print_r($value,true),
				'result'  =>$result,
				) ;
	}

	function showCacheLog(){
		$log = $this->_cache_log;

		if(PHP_SAPI !='cli'){
			print ("<table class=\"cake-sql-log\" summary=\"Cake Model Cache Log\" cellspacing=\"0\" border = \"0\">\n<caption>(cache behavior)</caption>\n");
			print ("<thead>\n<tr><th>Nr</th><th>Config</th><th>Method</th><th>Result</th><th>Key</th><th>Value</th></tr>\n</thead>");
			foreach($log as $k => $i){
				print("<tr><td>" . ( $k + 1 ) . "</td>");
				print("<td>" . h($i['config']) ."</td>");
				print("<td>" . h($i['method']) ."</td>");
				print("<td style= \"text-align: left\">" . h($i['result']) ."</td>");
				print("<td style= \"text-align: right\">" . h($i['key']) ."</td>");
				print("<td style= \"text-align: left\">" . h($i['value']) ."</td>");
			}
			print("</tbody></table>");
		}else{

			foreach($log as $k => $i){
				print(($k + 1) . ". {$i['config']} {$i['method']} {$i['key']} {$i['value']} \n");
			}
		}
	}

	function __destruct(){
		if (Configure::read() > 1) {
			$this->showCacheLog();
		}
	}
}
