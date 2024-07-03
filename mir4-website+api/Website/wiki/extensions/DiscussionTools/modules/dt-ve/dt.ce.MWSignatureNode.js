function DtCeMWSignatureNode() {
	// Parent constructor
	DtCeMWSignatureNode.super.apply( this, arguments );
}

OO.inheritClass( DtCeMWSignatureNode, ve.ce.MWSignatureNode );

DtCeMWSignatureNode.static.name = 'dtMwSignature';

ve.ce.nodeFactory.register( DtCeMWSignatureNode );

module.exports = DtCeMWSignatureNode;
