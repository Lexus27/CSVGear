<?php
namespace CSVGear;

require_once dirname(__DIR__) . '/csv/csvgear.php';

$csv = new CSV([
	'index' //main schema field names
],[
	'items' => [

		[
			'src' 		=> 'result.csv',
			'type'		=> 'file',// fopen
			'config'	=> [
				'offset'		=> 0,
				'schema' 		=> [ // item individual schema
					'contributor', 
					'observe', 
					'index', 
					'file' 
				],
				'aliases'		=> [ // aliases [ main_field => item_field  or main_field => [ 'pattern' => "[[~item_field_key~]] + [[~item_field_key~]]"  ]  ]
					'index' => function($key,$row) {
						return implode(' - ', $row);
					}
				]
			]
		]

	]
]);

$csv->flush('csv2.csv',true);
