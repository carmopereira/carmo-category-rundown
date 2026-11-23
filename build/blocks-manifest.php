<?php
// This file is generated. Do not modify it manually.
return array(
	'carmo-category-rundown' => array(
		'$schema' => 'https://schemas.wp.org/trunk/block.json',
		'apiVersion' => 3,
		'name' => 'create-block/carmo-category-rundown',
		'version' => '0.1.5',
		'title' => 'Carmo Category Rundown',
		'category' => 'widgets',
		'icon' => 'screenoptions',
		'description' => 'Carousel of WooCommerce product categories: cards with the category image and name and, on hover, its description and a button to see all.',
		'example' => array(
			
		),
		'attributes' => array(
			'source' => array(
				'type' => 'string',
				'enum' => array(
					'top',
					'children',
					'handpicked'
				),
				'default' => 'top'
			),
			'parentId' => array(
				'type' => 'number',
				'default' => 0
			),
			'categoryIds' => array(
				'type' => 'array',
				'items' => array(
					'type' => 'number'
				),
				'default' => array(
					
				)
			),
			'orderBy' => array(
				'type' => 'string',
				'enum' => array(
					'menu_order',
					'name',
					'count'
				),
				'default' => 'menu_order'
			),
			'categoriesToShow' => array(
				'type' => 'number',
				'default' => 12
			),
			'hideEmpty' => array(
				'type' => 'boolean',
				'default' => true
			),
			'imageFallback' => array(
				'type' => 'boolean',
				'default' => true
			),
			'customTitle' => array(
				'type' => 'string',
				'default' => ''
			),
			'eyebrow' => array(
				'type' => 'string',
				'default' => ''
			),
			'eyebrowClass' => array(
				'type' => 'string',
				'default' => ''
			),
			'titleTag' => array(
				'type' => 'string',
				'default' => 'h2'
			),
			'cardTitleTag' => array(
				'type' => 'string',
				'default' => 'h3'
			),
			'columnsDesktop' => array(
				'type' => 'number',
				'default' => 4
			),
			'columnsMobile' => array(
				'type' => 'number',
				'default' => 2
			),
			'cardRatio' => array(
				'type' => 'string',
				'enum' => array(
					'1/1',
					'3/4',
					'4/3',
					'16/9'
				),
				'default' => '1/1'
			),
			'descriptionLength' => array(
				'type' => 'number',
				'default' => 160
			),
			'buttonText' => array(
				'type' => 'string',
				'default' => ''
			),
			'cardsAlign' => array(
				'type' => 'string',
				'enum' => array(
					'left',
					'center',
					'right'
				),
				'default' => 'left'
			)
		),
		'supports' => array(
			'html' => false,
			'align' => array(
				'wide',
				'full'
			),
			'spacing' => array(
				'margin' => true,
				'padding' => true
			)
		),
		'textdomain' => 'carmo-category-rundown',
		'editorScript' => 'file:./index.js',
		'editorStyle' => 'file:./index.css',
		'style' => 'file:./style-index.css',
		'render' => 'file:./render.php',
		'viewScript' => 'file:./view.js'
	)
);
