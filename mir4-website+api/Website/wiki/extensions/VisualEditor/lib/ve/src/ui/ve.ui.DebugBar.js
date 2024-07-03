/*!
 * VisualEditor DebugBar class.
 *
 * @copyright See AUTHORS.txt
 */

/**
 * Debug bar
 *
 * @class
 * @extends OO.ui.Element
 *
 * @constructor
 * @param {ve.ui.Surface} surface Surface to debug
 * @param {Object} [config] Configuration options
 */
ve.ui.DebugBar = function VeUiDebugBar( surface, config ) {
	// Parent constructor
	ve.ui.DebugBar.super.call( this, config );

	this.surface = surface;

	this.$commands = $( '<div>' ).addClass( 've-ui-debugBar-commands' );
	this.$linmodData = $( '<td>' ).addClass( 've-ui-debugBar-dump-linmod-data' );
	this.$viewTree = $( '<td>' ).addClass( 've-ui-debugBar-view-tree' );
	this.$modelTree = $( '<td>' ).addClass( 've-ui-debugBar-model-tree' );

	var closeButton = new OO.ui.ButtonWidget( {
		icon: 'close',
		label: ve.msg( 'visualeditor-debugbar-close' )
	} );

	// Widgets
	this.selectionLabel = new OO.ui.LabelWidget( { classes: [ 've-ui-debugBar-selectionLabel' ] } );

	this.logRangeButton = new OO.ui.ButtonWidget( { label: ve.msg( 'visualeditor-debugbar-logrange' ), disabled: true } );
	this.showModelToggle = new OO.ui.ToggleButtonWidget( { label: ve.msg( 'visualeditor-debugbar-showmodel' ) } );
	this.updateModelToggle = new OO.ui.ToggleButtonWidget( { label: ve.msg( 'visualeditor-debugbar-updatemodel' ) } );
	this.transactionsToggle = new OO.ui.ToggleButtonWidget( { label: ve.msg( 'visualeditor-debugbar-showtransactions' ) } );
	this.testSquasherToggle = new OO.ui.ToggleButtonWidget( { label: ve.msg( 'visualeditor-debugbar-testsquasher' ) } );
	this.inputDebuggingToggle = new OO.ui.ToggleButtonWidget( { label: ve.msg( 'visualeditor-debugbar-inputdebug' ) } ).setValue( ve.inputDebug );
	this.filibusterToggle = new OO.ui.ToggleButtonWidget( { label: ve.msg( 'visualeditor-debugbar-startfilibuster' ) } );

	this.$dump =
		$( '<div>' ).addClass( 've-ui-debugBar-dump' ).append(
			this.updateModelToggle.$element,
			$( '<table>' ).append(
				$( '<thead>' ).append(
					$( '<th>' ).text( 'Linear model data' ),
					$( '<th>' ).text( 'View tree' ),
					$( '<th>' ).text( 'Model tree' )
				),
				$( '<tbody>' ).append(
					$( '<tr>' ).append(
						this.$linmodData, this.$viewTree, this.$modelTree
					)
				)
			)
		).addClass( 'oo-ui-element-hidden' );

	this.$transactions = $( '<div>' ).addClass( 've-ui-debugBar-transactions' );

	this.$filibuster = $( '<div>' ).addClass( [ 've-ui-debugBar-filibuster', 'oo-ui-element-hidden' ] );

	// Events
	this.logRangeButton.on( 'click', this.onLogRangeButtonClick.bind( this ) );
	this.showModelToggle.on( 'change', this.onShowModelToggleChange.bind( this ) );
	this.updateModelToggle.on( 'change', this.onUpdateModelToggleChange.bind( this ) );
	this.inputDebuggingToggle.on( 'change', this.onInputDebuggingToggleChange.bind( this ) );
	this.filibusterToggle.on( 'click', this.onFilibusterToggleClick.bind( this ) );
	this.transactionsToggle.on( 'change', this.onTransactionsToggleChange.bind( this ) );
	this.testSquasherToggle.on( 'change', this.onTestSquasherToggleChange.bind( this ) );
	closeButton.on( 'click', this.$element.remove.bind( this.$element ) );

	this.onHistoryDebounced = ve.debounce( this.onHistory.bind( this ) );

	this.getSurface().getModel().connect( this, {
		select: 'onSurfaceSelect',
		history: 'onHistoryDebounced'
	} );
	this.onSurfaceSelect( this.getSurface().getModel().getSelection() );

	this.$element.addClass( 've-ui-debugBar' );
	this.$element.append(
		this.$commands.append(
			this.selectionLabel.$element,
			this.logRangeButton.$element,
			$( this.constructor.static.dividerTemplate ),
			this.showModelToggle.$element,
			this.inputDebuggingToggle.$element,
			this.filibusterToggle.$element,
			this.transactionsToggle.$element,
			this.testSquasherToggle.$element,
			$( this.constructor.static.dividerTemplate ),
			closeButton.$element
		),
		this.$dump,
		this.$transactions,
		this.$filibuster
	);

	this.target = null;
};

/* Inheritance */

OO.inheritClass( ve.ui.DebugBar, OO.ui.Element );

/**
 * Divider HTML template
 *
 * @property {string}
 */
ve.ui.DebugBar.static.dividerTemplate = '<span class="ve-ui-debugBar-commands-divider">&nbsp;</span>';

/**
 * Get surface the debug bar is attached to
 *
 * @return {ve.ui.Surface|null}
 */
ve.ui.DebugBar.prototype.getSurface = function () {
	return this.surface;
};

/**
 * Handle select events on the attached surface
 *
 * @param {ve.dm.Selection} selection
 */
ve.ui.DebugBar.prototype.onSurfaceSelect = function () {
	// Do not trust the emitted selection: nested emits can invalidate it. See T145938.
	var selection = this.surface.model.getSelection();
	this.selectionLabel.setLabel( selection.getDescription() );
	this.logRangeButton.setDisabled( !(
		( selection instanceof ve.dm.LinearSelection && !selection.isCollapsed() ) ||
		selection instanceof ve.dm.TableSelection
	) );
};

/**
 * Handle history events on the attached surface
 */
ve.ui.DebugBar.prototype.onHistory = function () {
	if ( this.transactionsToggle.getValue() ) {
		this.updateTransactions();
	}
};

/**
 * Handle click events on the log range button
 *
 * @param {jQuery.Event} e
 */
ve.ui.DebugBar.prototype.onLogRangeButtonClick = function () {
	var selection = this.getSurface().getModel().getSelection(),
		documentModel = this.getSurface().getModel().getDocument();
	if ( selection instanceof ve.dm.LinearSelection || selection instanceof ve.dm.TableSelection ) {
		var ranges = selection.getRanges( documentModel );
		for ( var i = 0; i < ranges.length; i++ ) {
			ve.dir( this.getSurface().view.documentView.model.data.slice( ranges[ i ].start, ranges[ i ].end ) );
		}
	}
};

/**
 * Handle change events on the show model toggle
 *
 * @param {boolean} value
 */
ve.ui.DebugBar.prototype.onShowModelToggleChange = function ( value ) {
	if ( value ) {
		this.updateDump();
	} else {
		this.updateModelToggle.setValue( false );
	}
	this.$dump.toggleClass( 'oo-ui-element-hidden', !value );
};

/**
 * Update the model dump
 */
ve.ui.DebugBar.prototype.updateDump = function () {
	var surface = this.getSurface(),
		documentModel = surface.getModel().getDocument(),
		documentView = surface.getView().getDocument();

	// Linear model dump
	var $linmodData = this.generateListFromLinearData( documentModel.data );
	this.$linmodData.empty().append( $linmodData );
	var $modelTree = this.generateListFromNode( documentModel.getDocumentNode() );
	this.$modelTree.empty().append( $modelTree );
	var $viewTree = this.generateListFromNode( documentView.getDocumentNode() );
	this.$viewTree.empty().append( $viewTree );
};

/**
 * Get an ordered list representation of some linear data
 *
 * @param {ve.dm.ElementLinearData} linearData Linear data
 * @return {jQuery} Ordered list
 */
ve.ui.DebugBar.prototype.generateListFromLinearData = function ( linearData ) {
	var $ol = $( '<ol>' ).attr( 'start', '0' ),
		data = linearData.data;

	var $chunk, prevType, prevAnnotations, $annotations;
	for ( var i = 0; i < data.length; i++ ) {
		var $label = $( '<span>' );
		var element = data[ i ];
		var annotations = null;
		var text;
		if ( element.type ) {
			$label.addClass( 've-ui-debugBar-dump-element' );
			text = element.type;
			annotations = element.annotations;
		} else if ( Array.isArray( element ) ) {
			$label.addClass( 've-ui-debugBar-dump-achar' );
			text = element[ 0 ];
			annotations = element[ 1 ];
		} else {
			$label.addClass( 've-ui-debugBar-dump-char' );
			text = element;
		}

		$label.text( /\S/.test( text ) ? text : '\u00a0' );

		if ( $chunk && !prevType && !element.type && OO.compare( prevAnnotations, annotations ) ) {
			// This is a run of text with identical annotations. Continue current chunk.
			$chunk.append( $label );
		} else {
			// End current chunk, if any.
			if ( $chunk ) {
				if ( $annotations ) {
					$chunk.append( $annotations );
				}
				$ol.append( $chunk );
				$chunk = null;
				$annotations = null;
			}

			// Begin a new chunk
			$chunk = $( '<li>' ).attr( 'value', i );
			$chunk.append( $label );
			if ( annotations ) {
				$annotations = $( '<span>' ).addClass( 've-ui-debugBar-dump-note' ).text(
					'[' + this.getSurface().getModel().getDocument().getStore().values( annotations ).map( function ( ann ) {
						return JSON.stringify( ann.getComparableObject() );
					} ).join( ', ' ) + ']'
				);
			}
		}

		prevType = element.type;
		prevAnnotations = annotations;
	}

	// End current chunk, if any.
	if ( $chunk ) {
		if ( $annotations ) {
			$chunk.append( $annotations );
		}
		$ol.append( $chunk );
	}

	return $ol;
};

/**
 * Generate an ordered list describing a node
 *
 * @param {ve.Node} node
 * @return {jQuery} Ordered list
 */
ve.ui.DebugBar.prototype.generateListFromNode = function ( node ) {
	var $ol = $( '<ol>' ).attr( 'start', '0' );

	for ( var i = 0; i < node.children.length; i++ ) {
		var $li = $( '<li>' );
		var $label = $( '<span>' ).addClass( 've-ui-debugBar-dump-element' );
		var $note = $( '<span>' ).addClass( 've-ui-debugBar-dump-note' );
		if ( node.children[ i ].length !== undefined ) {
			$li.append(
				$label.text( node.children[ i ].type ),
				$note.text( '(' + node.children[ i ].length + ')' )
			);
		} else {
			$li.append( $label.text( node.children[ i ].type ) );
		}

		if ( node.children[ i ].children ) {
			var $sublist = this.generateListFromNode( node.children[ i ] );
			$li.append( $sublist );
		}

		$ol.append( $li );
	}
	return $ol;
};

/**
 * Handle change events on the update model toggle button
 *
 * @param {boolean} value
 */
ve.ui.DebugBar.prototype.onUpdateModelToggleChange = function ( value ) {
	if ( value ) {
		this.updateDump();
		this.getSurface().model.connect( this, { documentUpdate: 'updateDump' } );
	} else {
		this.getSurface().model.disconnect( this, { documentUpdate: 'updateDump' } );
	}
};

/**
 * Handle click events on the input debugging toggle button
 *
 * @param {boolean} value
 */
ve.ui.DebugBar.prototype.onInputDebuggingToggleChange = function ( value ) {
	var surfaceModel = this.getSurface().getModel(),
		selection = surfaceModel.getSelection();

	ve.inputDebug = value;

	// Clear the cursor before rebuilding, it will be restored later
	surfaceModel.setNullSelection();
	setTimeout( function () {
		surfaceModel.getDocument().rebuildTree();
		surfaceModel.setSelection( selection );
	} );
};

/**
 * Handle click events on the filibuster toggle button
 *
 * @param {jQuery.Event} e
 */
ve.ui.DebugBar.prototype.onFilibusterToggleClick = function () {
	var value = this.filibusterToggle.getValue();
	if ( value ) {
		this.filibusterToggle.setLabel( ve.msg( 'visualeditor-debugbar-stopfilibuster' ) );
		this.$filibuster.off( 'click' );
		this.$filibuster.empty();
		ve.initFilibuster();
		ve.filibuster.start();
	} else {
		ve.filibuster.stop();
		// eslint-disable-next-line no-jquery/no-html
		this.$filibuster.html( ve.filibuster.getObservationsHtml() );
		this.$filibuster.on( 'click', function ( e ) {
			var $li = $( e.target ).closest( '.ve-filibuster-frame' );

			// eslint-disable-next-line no-jquery/no-class-state
			if ( $li.hasClass( 've-filibuster-frame-expandable' ) ) {
				$li.removeClass( 've-filibuster-frame-expandable' );
				var path = $li.data( 've-filibuster-frame' );
				if ( !path ) {
					return;
				}
				$li.children( 'span' ).replaceWith(
					$( ve.filibuster.getObservationsHtml( path ) )
				);
				// eslint-disable-next-line no-jquery/no-class-state
				$li.toggleClass( 've-filibuster-frame-expanded' );
			} else if ( $li.children( 'ul' ).length ) {
				// eslint-disable-next-line no-jquery/no-class-state
				$li.toggleClass( 've-filibuster-frame-collapsed' );
				// eslint-disable-next-line no-jquery/no-class-state
				$li.toggleClass( 've-filibuster-frame-expanded' );
			}
		} );
		this.filibusterToggle.setLabel( ve.msg( 'visualeditor-debugbar-startfilibuster' ) );
	}
	this.$filibuster.toggleClass( 'oo-ui-element-hidden', !!value );
};

/**
 * Handle click events on the filibuster toggle button
 *
 * @param {boolean} value
 */
ve.ui.DebugBar.prototype.onTransactionsToggleChange = function ( value ) {
	if ( value ) {
		this.updateTransactions();
	}
	this.$transactions.toggleClass( 'oo-ui-element-hidden', !value );
};

/**
 * Handle click events on the test squasher toggle button
 *
 * @param {boolean} value
 */
ve.ui.DebugBar.prototype.onTestSquasherToggleChange = function ( value ) {
	var doc = this.getSurface().getModel().getDocument();
	if ( value ) {
		doc.connect( this, { transact: 'testSquasher' } );
		this.testSquasher();
	} else {
		doc.disconnect( this, { transact: 'testSquasher' } );
	}
};

/**
 * Update the transaction dump
 */
ve.ui.DebugBar.prototype.updateTransactions = function () {
	var surface = this.getSurface(),
		$transactionsList = $( '<ol>' );

	surface.getModel().getHistory().forEach( function ( item ) {
		var $state = $( '<ol>' ).appendTo( $( '<li>' ).appendTo( $transactionsList ) );
		item.transactions.forEach( function ( tx ) {
			$state.append( $( '<li>' ).text( ve.summarizeTransaction( tx ) ) );
		} );
	} );

	this.$transactions.empty().append( $transactionsList );
};

ve.ui.DebugBar.prototype.testSquasher = function () {
	function squashTransactions( txs ) {
		return new ve.dm.Change(
			0,
			txs.map( function ( tx ) {
				return tx.clone();
			} ),
			txs.map( function () {
				return new ve.dm.HashValueStore();
			} ),
			{}
		).squash().txs;
	}

	var transactions = this.getSurface().getModel().getDocument().completeHistory.transactions;
	if ( transactions.length < 3 ) {
		// Nothing interesting here
		return;
	}

	var squashed = squashTransactions( transactions );
	for ( var i = 1, iLen = transactions.length - 1; i < iLen; i++ ) {
		var squashedBefore = squashTransactions( transactions.slice( 0, i ) );
		var squashedAfter = squashTransactions( transactions.slice( i ) );
		var doubleSquashed = squashTransactions( [].concat(
			squashedBefore,
			squashedAfter
		) );
		var dump = JSON.stringify( squashed );
		var doubleDump = JSON.stringify( doubleSquashed );
		if ( dump !== doubleDump ) {
			throw new Error( 'Discrepancy splitting at i=' + i );
		}
	}
};

/**
 * Destroy the debug bar
 */
ve.ui.DebugBar.prototype.destroy = function () {
	this.getSurface().getModel().disconnect();
	this.$element.remove();
};
