<?php

namespace CSVGear;
require_once __DIR__ . '/reader/reader.class.php';
/**
 * Class Manager -
 * @package CSVGear
 */
class Manager extends CSVGearPrototypeAll{

	public static function log($message,$type='error'){
		$f = fopen(__DIR__.'/log.txt','a+');
		$t = date('Y-m-d H:i:s',time());
		$type = strtoupper($type);
		fwrite($f,"[{$t}] CSVGear{$type}:{$message}\r\n");
		fclose($f);
		if($type==='error'){
			throw new CSVGearException($message);
		}else return true;
	}

	public static function newReader($source,$class='',$config=array(),$suffix='Reader'){
		$class_name = (__NAMESPACE__?__NAMESPACE__ . '\\':'') . $class . $suffix;
		if(class_exists($class_name)){
			$reader = new $class_name($source,$class_name,$config);
			return $reader;
		}
		return false;
	}

	public static function newCSV($class,array $schema,$config=array()){
		if(class_exists($class)){
			$csv = new $class($schema,$config);
			return $csv;
		}else{
			return Manager::log('CSV class "'.$class.'" not exists');
		}
	}

}

class CSV extends CSVGearPrototypeAll{

	protected $fp;
	protected $schema = array();
	protected $sources = array();

	public function __construct(array $schema,array $config = array()){
		$this->applyConfig($config);
		$this->applyDefaults(array(
			'row-delimiter' => "\r\n",
			'col-delimiter' => ";",
			'enclosure'		=> '"',
			'escape'		=> "\\",

			'row-max-length'=> 1000,

			'schema'		=> array(),

			'output-file-permission' 	=> '0700',
			'output-dir-permission' 	=> '0700',

			'listeners'	=> array()
		));

		if(empty($schema)){
			Manager::log('CompositeCSV passed schema not valid');
		}
		$this->schema = $schema;

		if(is_array($this->config['items'])){
			foreach($this->config['items'] as & $itm){
				$this->add($itm);
			}
		}

	}

	public function flush($path,$overWrite=false){
		$dir = dirname($path);
		if(file_exists($path) && !$overWrite)return false;
		if(!is_dir($dir))mkdir($dir,decoct($this->getConfig('output-dir-permission','0700',true)),true);
		$this->fp = fopen($path,'a+');
		foreach($this->sources as & $source){
			/** @var $source SourceReader */
			if($source->prepare()){
				while($source->next()!==false){
					$row = $source->row();
					if($this->call('beforeWrite',$this,$row,$source)!==false){
						$this->write($row,$source);
					}
				}
			}else{
				Manager::log($source->getReaderKey().' is not exec prepare , reader detail: '.var_export($source->getInfo()));
			}
		}
		return true;
	}

	/**
	 * @param $source
	 * @param array $field_aliases array('this key' => target key);
	 * @throws CSVGearException
	 */
	public function add($source,array $field_aliases=array()){
		if($source instanceof SourceReader){
			$reader = $source;
		}elseif(is_array($source) && $source['src'] && $source['type']){
			$reader = Manager::newReader($source['src'],$source['type'],(array)$source['config']);
		}else{
			Manager::log('Add to CompositeCSV Stack is not valid source, source can be Reader instance or Array properties to create instance them');
		}
		if(isset($reader)){
			$this->sources[$reader->getReaderKey()] = $reader;
			$this->sources[$reader->getReaderKey()]->_field_aliases = $field_aliases;
		}
	}

	protected function write(array $row,SourceReader & $source){
		$aliases = null;
		$schema = & $this->schema;
		if(is_array($source->_field_aliases))
			$aliases = & $source->_field_aliases;
		$prepare = array_fill_keys($schema,'');
		foreach($prepare as $key=> & $val){
			if($aliases[$key])$key = $aliases[$key];
			if($row[$key]){
				$val = $row[$key];
			}
		}
		$row = $prepare;
		$this->call('onWritePut',$row,$schema,$aliases,$source->getConfig('schema'),$this,$source);
		return fputcsv($this->fp,
			array_values($row),
			$this->getConfig('col-delimiter'),
			$this->getConfig('enclosure')
		);
	}

	public function __destruct(){
		if($this->fp)fclose($this->fp);
	}

	public function call(/** arguments */){
		$arguments = func_get_args();
		$key = array_shift($arguments);
		$listeners = $this->getConfig('listeners');
		$listener = $listeners[$key];
		$scope = null;
		if(is_array($listener)){
			$handler = $listener['fn'];
			$scope = $listener['scope'];
		}else $handler = $listener;
		if(is_callable($handler)){
			if($handler instanceof \Closure && is_object($scope)){
				if(method_exists($handler,'bindTo')){
					$handler->bindTo($scope);
				}
			}
			return call_user_func_array($handler,$arguments);
		}else return true;
	}
}


class CSVGearException extends \Exception{}
class CSVGearPrototypeAll{
	protected $config = array();

	public function setConfig($key,$value){
		$this->config[$key] = $value;
	}

	public function getConfig($key,$default=null,$empty=false){
		return ((!$empty && isset($this->config[$key])) || ($empty && !empty($this->config[$key]))?$this->config[$key]:$default);
	}

	public function applyDefaults(array $config = array()){
		foreach($config as $k=>$v){
			if(!isset($this->config[$k]))$this->config[$k] = $v;
		}
	}

	public function applyConfig(array $config = array()){
		foreach($config as $k=>$v){
			$this->config[$k] = $v;
		}
	}
}
