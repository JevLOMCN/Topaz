/*!
 * VisualEditor UserInterface CommentInspector class.
 *
 * @copyright See AUTHORS.txt
 */

/**
 * Comment inspector.
 *
 * @class
 * @extends ve.ui.NodeInspector
 *
 * @constructor
 * @param {Object} [config] Configuration options
 */
ve.ui.CommentInspector = function VeUiCommentInspector() {
	// Parent constructor
	ve.ui.CommentInspector.super.apply( this, arguments );
};

/* Inheritance */

OO.inheritClass( ve.ui.CommentInspector, ve.ui.NodeInspector );

/* Static properties */

ve.ui.CommentInspector.static.name = 'comment';

ve.ui.CommentInspector.static.title =
	OO.ui.deferMsg( 'visualeditor-commentinspector-title' );

ve.ui.CommentInspector.static.modelClasses = [ ve.dm.CommentNode ];

ve.ui.CommentInspector.static.size = 'large';

ve.ui.CommentInspector.static.actions = [
	{
		action: 'remove',
		label: OO.ui.deferMsg( 'visualeditor-inspector-remove-tooltip' ),
		flags: 'destructive',
		modes: 'edit'
	}
].concat( ve.ui.CommentInspector.super.static.actions );

/* Methods */

/**
 * Handle frame ready events.
 */
ve.ui.CommentInspector.prototype.initialize = function () {
	// Parent method
	ve.ui.CommentInspector.super.prototype.initialize.call( this );

	this.textWidget = new ve.ui.WhitespacePreservingTextInputWidget( {
		autosize: true
	} );
	this.textWidget.connect( this, { resize: 'updateSize' } );
	this.textWidget.$input.attr( 'aria-label', OO.ui.deferMsg( 'visualeditor-commentinspector-title' ) );

	this.$content.addClass( 've-ui-commentInspector-content' );
	this.form.$element.append( this.textWidget.$element );
};

/**
 * @inheritdoc
 */
ve.ui.CommentInspector.prototype.getActionProcess = function ( action ) {
	if ( action === 'remove' || action === 'insert' ) {
		return new OO.ui.Process( function () {
			this.close( { action: action } );
		}, this );
	}
	return ve.ui.CommentInspector.super.prototype.getActionProcess.call( this, action );
};

/**
 * Handle the inspector being setup.
 *
 * @param {Object} [data] Inspector opening data
 * @return {OO.ui.Process}
 */
ve.ui.CommentInspector.prototype.getSetupProcess = function ( data ) {
	return ve.ui.CommentInspector.super.prototype.getSetupProcess.call( this, data )
		.next( function () {
			this.getFragment().getSurface().pushStaging();

			this.commentNode = this.getSelectedNode();
			if ( this.commentNode ) {
				this.textWidget.setValueAndWhitespace( this.commentNode.getAttribute( 'text' ) || '' );
			} else {
				this.textWidget.setWhitespace( [ ' ', ' ' ] );
				this.getFragment().insertContent( [
					{
						type: 'comment',
						attributes: { text: '' }
					},
					{ type: '/comment' }
				] ).select();
				this.commentNode = this.getSelectedNode();
			}
			this.textWidget.setReadOnly( this.isReadOnly() );
		}, this );
};

/**
 * @inheritdoc
 */
ve.ui.CommentInspector.prototype.getReadyProcess = function ( data ) {
	return ve.ui.CommentInspector.super.prototype.getReadyProcess.call( this, data )
		.next( function () {
			this.textWidget.focus();
		}, this );
};

/**
 * @inheritdoc
 */
ve.ui.CommentInspector.prototype.getTeardownProcess = function ( data ) {
	data = data || {};
	return ve.ui.CommentInspector.super.prototype.getTeardownProcess.call( this, data )
		.first( function () {
			var surfaceModel = this.getFragment().getSurface();

			// data.action can be 'done', 'remove' or undefined (cancel)
			if ( data.action === 'done' && this.textWidget.getValue() !== '' ) {
				// Edit comment node
				this.getFragment().changeAttributes( { text: this.textWidget.getValueAndWhitespace() } );
				surfaceModel.applyStaging();
			} else {
				surfaceModel.popStaging();
				if ( data.action === 'remove' || data.action === 'done' ) {
					this.getFragment().removeContent();
				}
			}

			// Reset inspector
			this.textWidget.setValueAndWhitespace( '' );
		}, this );
};

/* Registration */

ve.ui.windowFactory.register( ve.ui.CommentInspector );
