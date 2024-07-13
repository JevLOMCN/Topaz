/*!
 * VisualEditor DataModel HeadingNode class.
 *
 * @copyright See AUTHORS.txt
 */

/**
 * DataModel heading node.
 *
 * @class
 * @extends ve.dm.ContentBranchNode
 *
 * @constructor
 * @param {Object} [element] Reference to element in linear model
 * @param {ve.dm.Node[]} [children]
 */
ve.dm.HeadingNode = function VeDmHeadingNode() {
	// Parent constructor
	ve.dm.HeadingNode.super.apply( this, arguments );
};

/* Inheritance */

OO.inheritClass( ve.dm.HeadingNode, ve.dm.ContentBranchNode );

/* Methods */

ve.dm.HeadingNode.prototype.compareForMerging = function ( otherNode ) {
	return ve.dm.HeadingNode.super.prototype.compareForMerging.apply( this, arguments ) &&
		this.getAttribute( 'level' ) === otherNode.getAttribute( 'level' );
};

/* Static Properties */

ve.dm.HeadingNode.static.name = 'heading';

ve.dm.HeadingNode.static.defaultAttributes = {
	level: 1
};

ve.dm.HeadingNode.static.matchTagNames = [ 'h1', 'h2', 'h3', 'h4', 'h5', 'h6' ];

ve.dm.HeadingNode.static.toDataElement = function ( domElements ) {
	var levels = {
			h1: 1,
			h2: 2,
			h3: 3,
			h4: 4,
			h5: 5,
			h6: 6
		},
		level = levels[ domElements[ 0 ].nodeName.toLowerCase() ];
	return { type: this.name, attributes: { level: level } };
};

ve.dm.HeadingNode.static.toDomElements = function ( dataElement, doc ) {
	var level = dataElement.attributes && dataElement.attributes.level || 1;
	return [ doc.createElement( 'h' + level ) ];
};

ve.dm.HeadingNode.static.describeChange = function ( key, change ) {
	if ( key === 'level' ) {
		return ve.htmlMsg( 'visualeditor-changedesc-no-key',
			// The following messages are used here:
			// * visualeditor-formatdropdown-format-heading1
			// * visualeditor-formatdropdown-format-heading2
			// * visualeditor-formatdropdown-format-heading3
			// * visualeditor-formatdropdown-format-heading4
			// * visualeditor-formatdropdown-format-heading5
			// * visualeditor-formatdropdown-format-heading6
			this.wrapText( 'del', ve.msg( 'visualeditor-formatdropdown-format-heading' + change.from ) ),
			this.wrapText( 'ins', ve.msg( 'visualeditor-formatdropdown-format-heading' + change.to ) )
		);
	}
	// Parent method
	return ve.dm.HeadingNode.super.static.describeChange.apply( this, arguments );
};

/* Registration */

ve.dm.modelRegistry.register( ve.dm.HeadingNode );
