<?php
namespace CSVGear;

require_once dirname(__DIR__) . '/csv/csvgear.php';

$csv = new CSV([
	'index'
],[
	'items' => [

		[
			'src' 		=> 'result.csv',
			'type'		=> 'file',
			'config'	=> [
				'charset'		=> 'Windows1251',
				'offset'		=> 0,
				'schema' 		=> [ 'contributor', 'observe', 'index', 'file' ],
				'aliases'		=> [
					'index' => function($key,$row) {
						return implode(' - ', $row);

					}
				]
			]
		]

	]
]);

$csv->flush('csv2.csv',true);
