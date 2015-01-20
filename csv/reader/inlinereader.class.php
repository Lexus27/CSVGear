<?php
namespace CSVGear;
require_once 'reader.class.php';

class InlineReader extends SourceReader implements ReaderInterface{

	protected function validateSource(){
		if(!$this->src){
			Manager::log('source is not defined!!!');
			return false;
		}
		return true;
	}

	/**
	 * @return bool
	 * @throws CSVGearException
	 */
	protected function applySource(){
		$this->setOpt('string',$this->src);
		/** @var Array  */
		$this->src = str_getcsv($this->src,
			$this->getConfig('row-delimiter',"\r\n",true),
			$this->getConfig('enclosure','"',true),
			$this->getConfig('escape',"\\",true)
		);

		if(is_array($this->src)){
			$this->applyRows();
			$this->rewind();
		}else{
			Manager::log('Invalid SRC');
		}
	}

	protected function applyRows(){
		$this->src = array_map(array($this,'csvToRow'),$this->src);
	}

	protected function csvToRow($csv){
		return str_getcsv($csv,
			$this->getConfig('col-delimiter',";",true),
			$this->getConfig('enclosure','"',true),
			$this->getConfig('escape',"\\",true)
		);
	}

	public function next(){
		$newIndex = $this->index+1;
		if($this->src[$newIndex]){
			$this->index = $newIndex;
			$this->current = $this->src[$this->index];
			return true;
		}else{
			return false;
		}
	}


}
