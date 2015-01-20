<?php
namespace CSVGear;

class FileReader extends SourceReader{

	protected function validateSource(){
		if(!file_exists($this->src) || !is_readable($this->src)){
			Manager::log("Source file '{$this->src}' not found or not readable");
			return false;
		}
		return true;
	}

	protected function applySource(){
		$this->_src['path'] = $this->src;
		$this->src = fopen($this->src,'r');
		$this->rewind();
		return true;
	}

	public function next(){
		if(!$this->prepared)return false;
		$row = fgetcsv($this->src,
			$this->getConfig('row-max-length'),
			$this->getConfig('col-delimiter',";",true),
			$this->getConfig('enclosure','"',true),
			$this->getConfig('escape',"\\",true)
		);
		if($row!==false){
			$this->index++;
			$this->current = $row;
			return true;
		}else{
			return $row;
		}
	}

	public function rewind(){
		fseek($this->src,0);
		$this->index = -1;
		for($i=0,$offset = $this->getConfig('offset',0);$i<$offset;$i++){
			$this->next();
			Manager::log($this->current(),'log');
		}

		$this->current = false;
	}

	public function destroy(){
		if(is_resource($this->src))
		fclose($this->src);
	}
}
