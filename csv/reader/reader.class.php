<?php
namespace CSVGear;



interface ReaderInterface{

	function prepare();
	function getType();

	function rewind();
	function next();
	function key();
	function current();

	function destroy();

}
class SourceReader extends CSVGearPrototypeAll implements ReaderInterface{

	protected $_src = array();
	protected $src;
	protected $type;

	protected $current;
	protected $index;

	protected $prepared = false;
	protected $readerUniqId;

	public function __construct($src,$type,array $config = array()){
		$this->src 		= $src;
		$this->type 	= $type;
		$this->applyConfig($config);
		$this->applyDefaults(array(
			'row-delimiter' => "\r\n",
			'col-delimiter' => ";",
			'enclosure'		=> '"',
			'escape'		=> "\\",

			'row-max-length'=> 1000,

			'schema'		=> array(),

			'charset'		=> 'UTF-8',

			'offset'		=> 0,
		));

		if(!$this->getConfig('schema',false,true))Manager::log('Fields schema is empty');
		$this->readerUniqId = uniqid($this->type.'_',true);
	}

	public function getType(){
		return $this->type;
	}

	/**
	 * @return bool
	 * @throws CSVGearException
	 */
	public function prepare(){
		if($this->validateSource()){
			$this->prepared = $this->applySource();
			if($this->prepared)return true;
		}
		return false;
	}

	protected function validateSource(){
		if(!is_array($this->src)){
			Manager::log('source is not defined!!!');
			return false;
		}
		return true;
	}

	/**
	 * @return bool
	 * @throws CSVGearException
	 */
	protected function applySource(){}
	protected function applyRows(){}
	protected function csvToRow($csv){}

	public function next(){
		$newIndex = $this->index+1;
		if($this->src[$newIndex]){
			$this->index = $newIndex;
			$this->current = $this->filterRow($this->src[$this->index]);
			return true;
		}else{
			return false;
		}
	}

	public function & current(){
		return $this->current;
	}

	public function row(){
		$row = & $this->current();
		$schema = $this->getConfig('schema');
		$schema = array_fill_keys($schema,null);
		$i = 0;
		foreach($schema as $k=>& $v){
			$v = $row[$i];
			$i++;
		}
		return $schema;
	}

	public function rewind(){
		$this->index = -1;
		$this->current = false;
		$offset = $this->getConfig('offset',0); $i=0;
		while($i < $offset && $this->next()){}
	}

	public function key(){
		return $this->index;
	}



	public function opt($key,$default=null,$empty=false){
		return ((!$empty && isset($this->_src[$key])) || ($empty && !empty($this->_src[$key]))?$this->_src[$key]:$default);
	}

	public function setOpt($key,$value=null){
		$this->_src[$key] = $value;
	}

	public function getReaderKey(){
		return $this->readerUniqId;
	}

	public function destroy(){

	}

	public function getInfo(){
		return $this->_src;
	}

	public function __destruct(){
		$this->destroy();
	}

	public function filterRow($array){
		return $array;
	}
}



require_once __DIR__ . '/inlinereader.class.php';
require_once __DIR__ . '/filereader.class.php';
require_once __DIR__ . '/ftpreader.class.php';

