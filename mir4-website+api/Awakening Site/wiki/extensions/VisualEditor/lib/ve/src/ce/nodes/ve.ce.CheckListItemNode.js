/*!
 * VisualEditor ContentEditable CheckListItemNode class.
 *
 * @copyright See AUTHORS.txt
 */

/**
 * ContentEditable list item node.
 *
 * @class
 * @extends ve.ce.BranchNode
 * @constructor
 * @param {ve.dm.CheckListItemNode} model Model to observe
 * @param {Object} [config] Configuration options
 */
ve.ce.CheckListItemNode = function VeCeCheckListItemNode() {
	// Parent constructor
	ve.ce.CheckListItemNode.super.apply( this, arguments );

	this.$element.addClass( 've-ce-checkListItemNode' );

	// Events
	this.model.connect( this, { attributeChange: 'onAttributeChange' } );
	this.$element.on( 'click', this.onClick.bind( this ) );

	this.updateChecked();
};

/* Inheritance */

OO.inheritClass( ve.ce.CheckListItemNode, ve.ce.BranchNode );

/* Static Properties */

ve.ce.CheckListItemNode.static.name = 'checkListItem';

ve.ce.CheckListItemNode.static.tagName = 'li';

ve.ce.CheckListItemNode.static.splitOnEnter = true;

/* Methods */

/**
 * Handle click events on the checkbox
 *
 * @param {jQuery.Event} e Click event
 */
ve.ce.CheckListItemNode.prototype.onClick = function ( e ) {
	if ( e.target === this.$element[ 0 ] ) {
		// TODO: This should probably live in ui.Actions.
		var fragment = this.getRoot().getSurface().getModel().getLinearFragment( this.getOuterRange(), true );
		fragment.changeAttributes( { checked: !this.getModel().getAttribute( 'checked' ) } );
	}
};

/**
 * @param {string} key Attribute key
 * @param {string} from Old value
 * @param {string} to New value
 */
ve.ce.CheckListItemNode.prototype.onAttributeChange = function ( key ) {
	if ( key === 'checked' ) {
		this.updateChecked();
	}
};

ve.ce.CheckListItemNode.prototype.updateChecked = function () {
	this.$element.toggleClass( 've-ce-checkListItemNode-checked', !!this.getModel().getAttribute( 'checked' ) );
};

/* Registration */

ve.ce.nodeFactory.register( ve.ce.CheckListItemNode );
