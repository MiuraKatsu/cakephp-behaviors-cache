<?php
/**
 * Application model for Cake.
 *
 * This is a placeholder class.
 * Create the same file in app/app_model.php
 * Add your application-wide methods to the class, your models will inherit them.
 *
 * @package       cake
 * @subpackage    cake.cake.libs.model
 */
class AppModel extends Model {
	var $actsAs = array('Cache'=>array('config'=>'_cake_find_'));


/**
 * Find override.
 * モデルキャッシュを利用するために
 * 
 * @link http://www.exgear.jp/blog/2008/11/method_cache_behavior/
 * @link http://d.hatena.ne.jp/lifegood/20090604/p1
 */
	function find($conditions = null, $fields = array(), $order = null, $recursive = null) {
	     // Call cache method
			$args = func_get_args();
	        if ($this->Behaviors->attached('Cache')) {
	               if($this->cacheEnabled()) {
	                    return $this->cacheMethod(__FUNCTION__, $args );
	               }
	          }
	     // Case normal find. The model does not have cache behavior.
			return parent::find($conditions, $fields, $order, $recursive);
	}
	
}
?>
