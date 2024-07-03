/*!
 * VisualEditor MWLanuageVariantNodeContextItem class.
 *
 * @copyright See AUTHORS.txt
 */

/**
 * Context item for a ve.dm.MWLanguageVariantNode.
 *
 * @class
 * @extends ve.ui.LinearContextItem
 *
 * @constructor
 * @param {ve.ui.LinearContext} context Context the item is in
 * @param {ve.dm.MWLanguageVariantNode} model Model the item is related to
 * @param {Object} [config]
 */
ve.ui.MWLanguageVariantNodeContextItem = function VeUiMWLanguageVariantNodeContextItem() {
	// Parent constructor
	ve.ui.MWLanguageVariantNodeContextItem.super.apply( this, arguments );

	// Initialization
	this.$element.addClass( 've-ui-mwLanguageVariantNodeContextItem' );
};

/* Inheritance */

OO.inheritClass( ve.ui.MWLanguageVariantNodeContextItem, ve.ui.LinearContextItem );

/* Static Properties */

ve.ui.MWLanguageVariantNodeContextItem.static.name = 'mwLanguageVariant';

ve.ui.MWLanguageVariantNodeContextItem.static.icon = 'language';

ve.ui.MWLanguageVariantNodeContextItem.static.label = null; // see #setup()

ve.ui.MWLanguageVariantNodeContextItem.static.modelClasses = [
	ve.dm.MWLanguageVariantBlockNode,
	ve.dm.MWLanguageVariantInlineNode,
	ve.dm.MWLanguageVariantHiddenNode
];

ve.ui.MWLanguageVariantNodeContextItem.static.commandName = 'mwLanguageVariant';

/* Methods */

/**
 * @inheritdoc
 */
ve.ui.MWLanguageVariantNodeContextItem.prototype.setup = function () {
	// Set up label
	var messageKey = 'visualeditor-mwlanguagevariantcontextitem-title-' +
		this.model.getRuleType();

	// The following messages are used here:
	// * visualeditor-mwlanguagevariantcontextitem-title-disabled
	// * visualeditor-mwlanguagevariantcontextitem-title-filter
	// * visualeditor-mwlanguagevariantcontextitem-title-name
	// * visualeditor-mwlanguagevariantcontextitem-title-oneway
	// * visualeditor-mwlanguagevariantcontextitem-title-twoway
	// * visualeditor-mwlanguagevariantcontextitem-title-unknown
	this.setLabel( OO.ui.deferMsg( messageKey ) );

	// Invoke superclass method.
	return ve.ui.MWLanguageVariantNodeContextItem.super.prototype.setup.call( this );
};

/**
 * @inheritdoc
 */
ve.ui.MWLanguageVariantNodeContextItem.prototype.renderBody = function () {
	var $body = this.$body,
		$table = $( '<table>' ),
		$header = $( '<tr>' ),
		variantInfo = this.model.getVariantInfo(),
		type = this.model.getRuleType(),
		isHidden = this.model.isHidden();

	$table.addClass(
		've-ui-mwLanguageVariantNodeContextItem-rule-table'
	);
	$table.append( $header );

	switch ( type ) {
		case 'filter':
		case 'name':
			$header
				.append( $( '<th>' ).text(
					ve.msg( 'visualeditor-mwlanguagevariantcontextitem-rule-name-label' )
				) )
				.append( $( '<th>' ).text(
					ve.msg( 'visualeditor-mwlanguagevariantcontextitem-rule-code-label' )
				) );

			var languageCodes = ( type === 'filter' ) ?
				variantInfo.filter.l : [ variantInfo.name.t ];
			languageCodes.forEach( function ( code ) {
				var name = ve.init.platform.getLanguageName( code.toLowerCase() );
				if ( !name ) {
					name = ve.msg( 'visualeditor-mwlanguagevariantcontextitem-rule-invalid-language-label' );
				}
				$table
					.append( $( '<tr>' )
						.append( $( '<td>' ).text( name ).attr( 'lang', code ) )
						.append( $( '<td>' ).text( code ) )
					);
			} );
			break;

		case 'oneway':
			$header
				.append( $( '<th>' ).text(
					ve.msg( 'visualeditor-mwlanguagevariantcontextitem-rule-name-label' )
				) )
				.append( $( '<th>' ).text(
					ve.msg( 'visualeditor-mwlanguagevariantcontextitem-rule-code-label' )
				) )
				.append( $( '<th>' ).text(
					ve.msg( 'visualeditor-mwlanguagevariantcontextitem-rule-text-from-label' )
				) )
				.append( $( '<th>' ).text(
					ve.msg( 'visualeditor-mwlanguagevariantcontextitem-rule-text-to-label' )
				) );

			variantInfo.oneway.forEach( function ( item ) {
				// Safe HTML from the parser
				// eslint-disable-next-line no-jquery/no-html
				var $fromText = $( '<td>' ).html( item.f ),
					// eslint-disable-next-line no-jquery/no-html
					$toText = $( '<td>' ).html( item.t ),
					code = item.l,
					name = ve.init.platform.getLanguageName( code.toLowerCase() );
				$table
					.append( $( '<tr>' )
						.append( $( '<td>' ).text( name ).attr( 'lang', code ) )
						.append( $( '<td>' ).text( code ) )
						.append( $fromText )
						.append( $toText )
					);
			} );
			break;

		case 'twoway':
			$header
				.append( $( '<th>' ).text(
					ve.msg( 'visualeditor-mwlanguagevariantcontextitem-rule-name-label' )
				) )
				.append( $( '<th>' ).text(
					ve.msg( 'visualeditor-mwlanguagevariantcontextitem-rule-code-label' )
				) )
				.append( $( '<th>' ).text(
					ve.msg( 'visualeditor-mwlanguagevariantcontextitem-rule-text-twoway-label' )
				) );

			variantInfo.twoway.forEach( function ( item ) {
				var code = item.l,
					name = ve.init.platform.getLanguageName( code.toLowerCase() ),
					// eslint-disable-next-line no-jquery/no-html
					$text = $( '<td>' ).html( item.t );
				ve.dm.MWLanguageVariantNode.static.processVariants(
					$text[ 0 ], { showHidden: true }
				);
				$table
					.append( $( '<tr>' )
						.append( $( '<td>' ).text( name ).attr( 'lang', code ) )
						.append( $( '<td>' ).text( code ) )
						.append( $text )
					);
			} );
			break;

		default:
			break;
	}
	if ( $table.find( 'tr' ).length > 1 ) {
		// Don't put $table in $body if the table is empty; this allows
		// CSS :empty rules to have their proper effect.
		ve.dm.MWLanguageVariantNode.static.processVariants(
			$table[ 0 ], { showHidden: true }
		);
		$body.append( $table );
	}
	// Show "extra" properties, like describe, title, etc.
	[ 'describe', 'title', 'hidden' ].forEach( function ( flag ) {
		var f = ( flag === 'hidden' ) ? isHidden : variantInfo[ flag ];
		if ( f ) {
			// The following messages can be used here:
			// * visualeditor-mwlanguagevariantcontextitem-flag-describe
			// * visualeditor-mwlanguagevariantcontextitem-flag-hidden
			// * visualeditor-mwlanguagevariantcontextitem-flag-title
			$body.append( $( '<p>' ).text( OO.ui.msg(
				'visualeditor-mwlanguagevariantcontextitem-flag-' + flag
			) ) );
		}
	} );
};

/**
 * @inheritdoc
 */
ve.ui.MWLanguageVariantNodeContextItem.prototype.getCommand = function () {
	var type = this.model.getRuleType(),
		cmdName = this.constructor.static.commandName + '-' + type;
	return this.context.getSurface().commandRegistry.lookup( cmdName );
};

/* Registration */

ve.ui.contextItemFactory.register( ve.ui.MWLanguageVariantNodeContextItem );

[ 'disabled', 'filter', 'name', 'twoway', 'oneway' ].forEach( function ( type ) {
	ve.ui.commandRegistry.register(
		new ve.ui.Command(
			'mwLanguageVariant-' + type, 'window', 'open',
			{
				args: [ 'mwLanguageVariant-' + type ],
				supportedSelections: [ 'linear' ]
			}
		)
	);
} );
