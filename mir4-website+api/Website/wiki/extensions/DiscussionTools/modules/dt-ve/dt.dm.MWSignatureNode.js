function DtDmMWSignatureNode() {
	// Parent constructor
	DtDmMWSignatureNode.super.apply( this, arguments );
}

OO.inheritClass( DtDmMWSignatureNode, ve.dm.MWSignatureNode );

DtDmMWSignatureNode.static.name = 'dtMwSignature';

// Match the special marker we use when switching from source to visual mode
DtDmMWSignatureNode.static.matchTagNames = [ 'span' ];

DtDmMWSignatureNode.static.matchRdfaTypes = null;

DtDmMWSignatureNode.static.matchFunction = function ( domElement ) {
	return domElement.getAttribute( 'data-dtsignatureforswitching' ) !== null;
};

DtDmMWSignatureNode.static.toDataElement = function () {
	return { type: 'dtMwSignature' };
};

ve.dm.modelRegistry.register( DtDmMWSignatureNode );

module.exports = DtDmMWSignatureNode;
