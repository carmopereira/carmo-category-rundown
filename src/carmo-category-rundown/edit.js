/**
 * WordPress dependencies
 */
import { __ } from '@wordpress/i18n';
import { InspectorControls, useBlockProps } from '@wordpress/block-editor';
import {
	Disabled,
	FormTokenField,
	Notice,
	PanelBody,
	RangeControl,
	SelectControl,
	Spinner,
	TextControl,
	ToggleControl,
} from '@wordpress/components';
import { decodeEntities } from '@wordpress/html-entities';
import { useSelect } from '@wordpress/data';
import { store as coreStore } from '@wordpress/core-data';
import ServerSideRender from '@wordpress/server-side-render';

/**
 * Internal dependencies
 */
import './editor.scss';

/**
 * Token shown in the category picker: the name plus the ID, so two categories
 * with the same name stay distinguishable and the ID can be read back.
 *
 * @param {Object} category Category record from the REST API.
 * @return {string} Token label.
 */
function getCategoryToken( category ) {
	const name = decodeEntities( category?.name || '' ).trim();

	return `${ name || __( '(sem nome)', 'carmo-category-rundown' ) } (#${ category.id })`;
}

/**
 * Reads the category ID back from a token produced by getCategoryToken().
 *
 * @param {string} token Token label.
 * @return {number} Category ID, or 0 when the token carries none.
 */
function getTokenCategoryId( token ) {
	const match = /\(#(\d+)\)$/.exec( String( token ).trim() );

	return match ? Number( match[ 1 ] ) : 0;
}

/**
 * Flattens the category list into tree order, with the depth of each one,
 * so the parent dropdown can indent subcategories under their parent.
 *
 * @param {Array} categories Category records.
 * @return {Array} [ { category, depth } ] in tree order.
 */
function getCategoryTree( categories ) {
	const byParent = {};

	categories.forEach( ( category ) => {
		( byParent[ category.parent ] ||= [] ).push( category );
	} );

	const ids = new Set( categories.map( ( category ) => category.id ) );
	const result = [];
	const walk = ( parentId, depth ) => {
		( byParent[ parentId ] || [] ).forEach( ( category ) => {
			result.push( { category, depth } );
			walk( category.id, depth + 1 );
		} );
	};

	walk( 0, 0 );

	// Categories whose parent is missing from the list still get listed.
	categories
		.filter(
			( category ) => category.parent && ! ids.has( category.parent )
		)
		.forEach( ( category ) => {
			result.push( { category, depth: 0 } );
			walk( category.id, 1 );
		} );

	return result;
}

export default function Edit( { attributes, setAttributes } ) {
	const {
		source,
		parentId,
		categoryIds,
		orderBy,
		categoriesToShow,
		hideEmpty,
		imageFallback,
		customTitle,
		eyebrow,
		eyebrowClass,
		titleTag,
		cardTitleTag,
		columnsDesktop,
		columnsMobile,
		cardRatio,
		descriptionLength,
		buttonText,
		cardsAlign,
	} = attributes;

	const blockProps = useBlockProps();
	const isHandPicked = source === 'handpicked';
	const isChildren = source === 'children';

	const { categories, hasResolvedCategories } = useSelect( ( select ) => {
		const query = {
			per_page: -1,
			hide_empty: false,
			_fields: 'id,name,parent',
		};

		return {
			categories: select( coreStore ).getEntityRecords(
				'taxonomy',
				'product_cat',
				query
			),
			hasResolvedCategories: select( coreStore ).hasFinishedResolution(
				'getEntityRecords',
				[ 'taxonomy', 'product_cat', query ]
			),
		};
	}, [] );

	const tree = getCategoryTree( categories || [] );

	const parentOptions = [
		{
			label: __( '— Escolha uma categoria —', 'carmo-category-rundown' ),
			value: 0,
		},
		...tree.map( ( { category, depth } ) => ( {
			label: `${ '— '.repeat( depth ) }${ decodeEntities(
				category.name
			) }`,
			value: category.id,
		} ) ),
	];

	const categoryById = Object.fromEntries(
		( categories || [] ).map( ( category ) => [ category.id, category ] )
	);
	const categoryTokens = categoryIds.map( ( id ) =>
		categoryById[ id ]
			? getCategoryToken( categoryById[ id ] )
			: `(#${ id })`
	);
	const categorySuggestions = tree
		.map( ( { category } ) => category )
		.filter( ( category ) => ! categoryIds.includes( category.id ) )
		.map( getCategoryToken );

	const headingHelp = isChildren
		? __(
				'Vazio: mostra o nome da categoria-mãe.',
				'carmo-category-rundown'
			)
		: __(
				'Vazio: sem título por cima do carousel.',
				'carmo-category-rundown'
			);

	return (
		<>
			<InspectorControls>
				<PanelBody
					title={ __( 'Categorias', 'carmo-category-rundown' ) }
				>
					<SelectControl
						label={ __(
							'Que categorias mostrar',
							'carmo-category-rundown'
						) }
						value={ source }
						options={ [
							{
								label: __(
									'Categorias principais (sem mãe)',
									'carmo-category-rundown'
								),
								value: 'top',
							},
							{
								label: __(
									'Subcategorias de uma categoria',
									'carmo-category-rundown'
								),
								value: 'children',
							},
							{
								label: __(
									'Categorias escolhidas',
									'carmo-category-rundown'
								),
								value: 'handpicked',
							},
						] }
						onChange={ ( value ) =>
							setAttributes( { source: value } )
						}
						__next40pxDefaultSize
						__nextHasNoMarginBottom
					/>

					{ ! hasResolvedCategories && <Spinner /> }

					{ hasResolvedCategories && ! categories?.length && (
						<Notice status="warning" isDismissible={ false }>
							{ __(
								'Não foram encontradas categorias de produto. Confirme que o WooCommerce está ativo.',
								'carmo-category-rundown'
							) }
						</Notice>
					) }

					{ hasResolvedCategories &&
						categories?.length > 0 &&
						isChildren && (
							<SelectControl
								label={ __(
									'Categoria-mãe',
									'carmo-category-rundown'
								) }
								value={ parentId }
								options={ parentOptions }
								onChange={ ( value ) =>
									setAttributes( {
										parentId: Number( value ),
									} )
								}
								__next40pxDefaultSize
								__nextHasNoMarginBottom
							/>
						) }

					{ hasResolvedCategories &&
						categories?.length > 0 &&
						isHandPicked && (
							<>
								<FormTokenField
									label={ __(
										'Categorias',
										'carmo-category-rundown'
									) }
									value={ categoryTokens }
									suggestions={ categorySuggestions }
									onChange={ ( tokens ) =>
										setAttributes( {
											categoryIds: [
												...new Set(
													tokens
														.map(
															getTokenCategoryId
														)
														.filter( Boolean )
												),
											],
										} )
									}
									__experimentalValidateInput={ ( token ) =>
										getTokenCategoryId( token ) > 0
									}
									__experimentalExpandOnFocus
									__experimentalShowHowTo={ false }
									__next40pxDefaultSize
									__nextHasNoMarginBottom
								/>
								<p className="carmo-rundown-editor__help">
									{ __(
										'Escreva para procurar. Aparecem no carousel pela ordem em que as escolher.',
										'carmo-category-rundown'
									) }
								</p>
							</>
						) }

					{ ! isHandPicked && (
						<>
							<SelectControl
								label={ __(
									'Ordenar por',
									'carmo-category-rundown'
								) }
								value={ orderBy }
								options={ [
									{
										label: __(
											'Ordem personalizada (WooCommerce)',
											'carmo-category-rundown'
										),
										value: 'menu_order',
									},
									{
										label: __(
											'Nome',
											'carmo-category-rundown'
										),
										value: 'name',
									},
									{
										label: __(
											'Número de produtos',
											'carmo-category-rundown'
										),
										value: 'count',
									},
								] }
								onChange={ ( value ) =>
									setAttributes( { orderBy: value } )
								}
								__next40pxDefaultSize
								__nextHasNoMarginBottom
							/>
							<RangeControl
								label={ __(
									'Categorias a mostrar',
									'carmo-category-rundown'
								) }
								value={ categoriesToShow }
								onChange={ ( value ) =>
									setAttributes( { categoriesToShow: value } )
								}
								min={ 1 }
								max={ 30 }
								__next40pxDefaultSize
								__nextHasNoMarginBottom
							/>
						</>
					) }

					<ToggleControl
						label={ __(
							'Esconder categorias vazias',
							'carmo-category-rundown'
						) }
						checked={ hideEmpty }
						onChange={ ( value ) =>
							setAttributes( { hideEmpty: value } )
						}
						__nextHasNoMarginBottom
					/>

					<ToggleControl
						label={ __(
							'Sem miniatura: usar imagem de um produto',
							'carmo-category-rundown'
						) }
						help={ __(
							'Quando a categoria não tem miniatura, mostra a imagem do primeiro produto dela (ou das subcategorias). Fica em cache até um produto ou categoria mudar.',
							'carmo-category-rundown'
						) }
						checked={ imageFallback }
						onChange={ ( value ) =>
							setAttributes( { imageFallback: value } )
						}
						__nextHasNoMarginBottom
					/>
				</PanelBody>

				<PanelBody title={ __( 'Título', 'carmo-category-rundown' ) }>
					<TextControl
						label={ __(
							'Título (opcional)',
							'carmo-category-rundown'
						) }
						help={ headingHelp }
						value={ customTitle }
						onChange={ ( value ) =>
							setAttributes( { customTitle: value } )
						}
						__next40pxDefaultSize
						__nextHasNoMarginBottom
					/>
					<SelectControl
						label={ __(
							'Elemento do título',
							'carmo-category-rundown'
						) }
						value={ titleTag }
						options={ [
							{ label: 'H1', value: 'h1' },
							{ label: 'H2', value: 'h2' },
							{ label: 'H3', value: 'h3' },
							{
								label: __(
									'Span (sem heading)',
									'carmo-category-rundown'
								),
								value: 'span',
							},
						] }
						onChange={ ( value ) =>
							setAttributes( { titleTag: value } )
						}
						__next40pxDefaultSize
						__nextHasNoMarginBottom
					/>
					<TextControl
						label={ __(
							'Subtítulo (opcional)',
							'carmo-category-rundown'
						) }
						help={ __(
							'Texto curto por cima do título, por exemplo «Categorias».',
							'carmo-category-rundown'
						) }
						value={ eyebrow }
						onChange={ ( value ) =>
							setAttributes( { eyebrow: value } )
						}
						__next40pxDefaultSize
						__nextHasNoMarginBottom
					/>
					{ !! eyebrow && (
						<TextControl
							label={ __(
								'Classe CSS do subtítulo',
								'carmo-category-rundown'
							) }
							help={ __(
								'Opcional. Uma ou mais classes do tema, separadas por espaços.',
								'carmo-category-rundown'
							) }
							value={ eyebrowClass }
							onChange={ ( value ) =>
								setAttributes( { eyebrowClass: value } )
							}
							__next40pxDefaultSize
							__nextHasNoMarginBottom
						/>
					) }
				</PanelBody>

				<PanelBody title={ __( 'Cards', 'carmo-category-rundown' ) }>
					<RangeControl
						label={ __(
							'Colunas (desktop)',
							'carmo-category-rundown'
						) }
						value={ columnsDesktop }
						onChange={ ( value ) =>
							setAttributes( { columnsDesktop: value } )
						}
						min={ 1 }
						max={ 6 }
						__next40pxDefaultSize
						__nextHasNoMarginBottom
					/>
					<RangeControl
						label={ __(
							'Colunas (mobile)',
							'carmo-category-rundown'
						) }
						value={ columnsMobile }
						onChange={ ( value ) =>
							setAttributes( { columnsMobile: value } )
						}
						min={ 1 }
						max={ 3 }
						__next40pxDefaultSize
						__nextHasNoMarginBottom
					/>
					<SelectControl
						label={ __(
							'Proporção do card',
							'carmo-category-rundown'
						) }
						value={ cardRatio }
						options={ [
							{
								label: __(
									'Quadrado (1:1)',
									'carmo-category-rundown'
								),
								value: '1/1',
							},
							{
								label: __(
									'Vertical (3:4)',
									'carmo-category-rundown'
								),
								value: '3/4',
							},
							{
								label: __(
									'Horizontal (4:3)',
									'carmo-category-rundown'
								),
								value: '4/3',
							},
							{
								label: __(
									'Panorâmico (16:9)',
									'carmo-category-rundown'
								),
								value: '16/9',
							},
						] }
						onChange={ ( value ) =>
							setAttributes( { cardRatio: value } )
						}
						__next40pxDefaultSize
						__nextHasNoMarginBottom
					/>
					<SelectControl
						label={ __(
							'Elemento do nome da categoria',
							'carmo-category-rundown'
						) }
						value={ cardTitleTag }
						options={ [
							{ label: 'H2', value: 'h2' },
							{ label: 'H3', value: 'h3' },
							{ label: 'H4', value: 'h4' },
							{
								label: __(
									'Span (sem heading)',
									'carmo-category-rundown'
								),
								value: 'span',
							},
						] }
						onChange={ ( value ) =>
							setAttributes( { cardTitleTag: value } )
						}
						__next40pxDefaultSize
						__nextHasNoMarginBottom
					/>
					<RangeControl
						label={ __(
							'Limite de caracteres da descrição',
							'carmo-category-rundown'
						) }
						help={ __(
							'Usa a descrição da categoria. 0 para não mostrar.',
							'carmo-category-rundown'
						) }
						value={ descriptionLength }
						onChange={ ( value ) =>
							setAttributes( { descriptionLength: value } )
						}
						min={ 0 }
						max={ 400 }
						step={ 10 }
						__next40pxDefaultSize
						__nextHasNoMarginBottom
					/>
					<TextControl
						label={ __(
							'Texto do botão',
							'carmo-category-rundown'
						) }
						help={ __(
							'Vazio: «Ver todos». O card inteiro é um link para a categoria.',
							'carmo-category-rundown'
						) }
						value={ buttonText }
						onChange={ ( value ) =>
							setAttributes( { buttonText: value } )
						}
						__next40pxDefaultSize
						__nextHasNoMarginBottom
					/>
					<SelectControl
						label={ __(
							'Alinhamento dos cards',
							'carmo-category-rundown'
						) }
						help={ __(
							'Onde ficam os cards quando não chegam para encher a linha.',
							'carmo-category-rundown'
						) }
						value={ cardsAlign }
						options={ [
							{
								label: __(
									'Esquerda',
									'carmo-category-rundown'
								),
								value: 'left',
							},
							{
								label: __( 'Centro', 'carmo-category-rundown' ),
								value: 'center',
							},
							{
								label: __(
									'Direita',
									'carmo-category-rundown'
								),
								value: 'right',
							},
						] }
						onChange={ ( value ) =>
							setAttributes( { cardsAlign: value } )
						}
						__next40pxDefaultSize
						__nextHasNoMarginBottom
					/>
				</PanelBody>
			</InspectorControls>
			<div { ...blockProps }>
				{ /*
				 * The preview is the real front-end markup, full of category
				 * links. Left clickable, a click would navigate the editor
				 * canvas away, so the preview is made inert.
				 */ }
				<Disabled>
					<ServerSideRender
						block="create-block/carmo-category-rundown"
						attributes={ attributes }
					/>
				</Disabled>
			</div>
		</>
	);
}
