/*!
 * treeDiffer Namespace for treeDiffer.js
 *
 * Version 1.0.2
 * https://github.com/Tchanders/treeDiffer.js
 *
 * Released under the MIT license
 */

// eslint-disable-next-line no-undef
window.treeDiffer = {};

/*!
 * treeDiffer.TreeNode
 *
 * Released under the MIT license
 */

/**
 * TreeNode
 *
 * Abstract TreeNode class for Trees to be diffed. It should be extended,
 * then a Tree should be built by passing the root node and the name of
 * the new class into the Tree constructor.
 *
 * @class
 * @constructor
 * @param {Object} node Object representing a node to be wrapped
 */
treeDiffer.TreeNode = function ( node ) {
	/**
	 * @property {Object} node Object representing the wrapped node
	 */
	this.node = node;

	/**
	 * @property {treeDiffer.TreeNode[]} children Child nodes
	 */
	this.children = [];

	/**
	 * @property {number} index Index in node list ordered by deepest-first then document order
	 */
	this.index = null;

	/**
	 * @property {number} leftmost Leftmost of this node; see treeDiffer.Tree
	 */
	this.leftmost = null;
};

/**
 * Add a node to the list of this node's children
 *
 * @param {treeDiffer.TreeNode} child
 */
treeDiffer.TreeNode.prototype.addChild = function ( child ) {
	this.children.push( child );
	child.parent = this;
};

/**
 * @method
 * Check if another TreeNode is equal to this node. Conditions for equality
 * will depend on the use case.
 *
 * @param {treeDiffer.TreeNode} other The other TreeNode
 * @return {boolean} True if equal
 */
treeDiffer.TreeNode.prototype.isEqual = null;

/**
 * Get the children of the node that this tree node wraps. How to
 * find and filter children will depend on the use case.
 *
 * @method
 * @return {Object[]} Children of the wrapped node
 */
treeDiffer.TreeNode.prototype.getOriginalNodeChildren = null;

/*!
 * treeDiffer.Tree
 *
 * Released under the MIT license
 */

/**
 * Tree
 *
 * A group of TreeNodes connected by parent-child relationships in a tree
 * structure, along with certain properties that define the exact structure of the
 * tree: the node order, the keyroots, and the leftmost node of each node. (Terms
 * defined in: http://epubs.siam.org/doi/abs/10.1137/0218082?journalCode=smjcat)
 *
 * @class
 * @constructor
 *
 * @param {treeDiffer.TreeNode} node Root node of the tree
 * @param {Function} nodeClass Concrete subclass of treeDiffer.TreeNode
 * @param {Object} config Config options for nodeClass
 */
treeDiffer.Tree = function ( node, nodeClass, config ) {

	this.root = null;
	this.nodeClass = nodeClass;
	this.orderedNodes = [];
	this.keyRoots = [];

	this.findKeyRootsAndOrderedNodes( node, config );

};

/**
 * Find the post-ordering of the tree nodes, the keyroots and the leftmost of each
 * node.
 *
 * @param {Object} node Root node in original tree
 * @param {Object} config Config options for nodeClass
 */
treeDiffer.Tree.prototype.findKeyRootsAndOrderedNodes = function ( node, config ) {
	var leftmost,
		leftmostsToKeyRoots = {},
		tree = this;

	/**
	 * Find the tree nodes in post-order, find the leftmost of each node, and store
	 * the order and leftmost as properties of the nodes.
	 *
	 * @param {treeDiffer.TreeNode} treeNode Node currently being checked
	 * @param {Array} orderedNodes Array to be populated with nodes in order
	 */
	function postOrderNodes( treeNode, orderedNodes ) {
		var i, ilen, childNode,
			children = treeNode.getOriginalNodeChildren();

		for ( i = 0, ilen = children.length; i < ilen; i++ ) {
			// eslint-disable-next-line new-cap
			childNode = new tree.nodeClass( children[ i ], config );
			treeNode.addChild( childNode );
			postOrderNodes( childNode, orderedNodes );
		}

		// Record node order
		orderedNodes.push( treeNode );
		treeNode.index = orderedNodes.length - 1;

		// Record index of leftmost node
		// If this node is a leaf, it is its own leftmost
		treeNode.leftmost = treeNode.children.length === 0 ? treeNode.index : treeNode.children[ 0 ].leftmost;

		// Update the key root corresponding to this leftmost
		// A keyroot is the higest indexed node with each leftmost
		leftmostsToKeyRoots[ treeNode.leftmost ] = treeNode.index;
	}

	// Store the nodes in order
	// eslint-disable-next-line new-cap
	this.root = new tree.nodeClass( node, config );
	this.orderedNodes = [];
	postOrderNodes( this.root, this.orderedNodes );

	// Store the key roots in order of node index
	for ( leftmost in leftmostsToKeyRoots ) {
		this.keyRoots.push( leftmostsToKeyRoots[ leftmost ] );
	}
	this.keyRoots.sort( function ( a, b ) {
		return a - b;
	} );

};

/**
 * Get all the descendants of a node
 *
 * @param {treeDiffer.TreeNode} node Node whose descendants to find
 * @return {Array} Descendants of the node
 */
treeDiffer.Tree.prototype.getNodeDescendants = function ( node ) {
	var descendants = [];

	function addDescendants( parentNode ) {
		var i, ilen, childNode;
		for ( i = 0, ilen = parentNode.children.length; i < ilen; i++ ) {
			childNode = parentNode.children[ i ];
			descendants.push( childNode );
			addDescendants( childNode );
		}
	}

	addDescendants( node );

	return descendants;
};

/*!
 * treeDiffer.Differ
 *
 * Released under the MIT license
 */

/* eslint-disable dot-notation */
// We use [ 'null' ] as an index, but for consistencty with
// variable indicies [ i ][ j ] we prefer not to use dot notation

/**
 * Differ
 *
 * Find the minimum transactions to get from the first tree to the second tree. Each
 * transaction is of the form [nodeToRemove, nodeToInsert], where nodeToRemove or
 * nodeToInsert (but not both) can be null. The tree diffing algorithm is presented in:
 * http://epubs.siam.org/doi/abs/10.1137/0218082?journalCode=smjcat
 *
 * @class
 * @constructor
 * @param {treeDiffer.Tree} tree1 First tree
 * @param {treeDiffer.Tree} tree2 Second tree
 * @param {number} [timeout=1000] Timeout after which to stop diffing
 */
treeDiffer.Differ = function ( tree1, tree2, timeout ) {
	var i, ilen, j, jlen, transactions,
		transactionIndex = 0;

	this.endTime = new Date().getTime() + ( timeout || 1000 );

	this.tree1 = tree1;
	this.tree2 = tree2;

	this.insertCost = 1;
	this.removeCost = 1;
	this.changeCost = 1;

	// Temporary, changing store of transactions
	transactions = {
		null: {
			null: []
		}
	};

	// Permanent store of transactions such that transactions[x][y] is the minimum
	// transactions to get from the sub-tree rooted at node x (in tree1) to the sub-tree
	// rooted at node y (in tree2).
	this.transactions = {
		null: {}
	};

	// All possible transactions
	this.indexToTransaction = [];
	this.indexToTransaction.push( [ null, null ] );

	// Indices for each transaction, to avoid high performance cost of creating the
	// transactions multiple times
	this.transactionToIndex = {
		null: {
			null: 0
		}
	};
	transactionIndex += 1;

	// Populate transaction stores
	for ( i = 0, ilen = this.tree1.orderedNodes.length; i < ilen; i++ ) {

		transactions[ i ] = {
			null: []
		};
		this.transactionToIndex[ i ] = {
			null: transactionIndex
		};
		transactionIndex += 1;
		this.indexToTransaction.push( [ i, null ] );

		for ( j = 0, jlen = this.tree2.orderedNodes.length; j < jlen; j++ ) {
			transactions[ null ][ j ] = [];
			transactions[ i ][ j ] = [];

			this.transactionToIndex[ null ][ j ] = transactionIndex;
			transactionIndex += 1;
			this.indexToTransaction.push( [ null, j ] );

			this.transactionToIndex[ i ][ j ] = transactionIndex;
			transactionIndex += 1;
			this.indexToTransaction.push( [ i, j ] );
		}

		this.transactions[ i ] = {};

	}

	this.populateTransactions( transactions );
};

/**
 * Populate this.transactions with minimum transactions between all possible trees
 *
 * @param {Object} transactions Temporary store of transactions between trees
 */
treeDiffer.Differ.prototype.populateTransactions = function ( transactions ) {
	var i, ilen, j, jlen, iNulls, jNulls, ii, jj, keyRoot1, keyRoot2,
		differ = this;

	function getTransactionFromIndex( index ) {
		return differ.indexToTransaction[ index ];
	}

	for ( i = 0, ilen = this.tree1.keyRoots.length; i < ilen; i++ ) {

		// Make transactions for tree -> null
		keyRoot1 = this.tree1.orderedNodes[ this.tree1.keyRoots[ i ] ];
		iNulls = [];
		for ( ii = keyRoot1.leftmost; ii < keyRoot1.index + 1; ii++ ) {
			iNulls.push( this.transactionToIndex[ ii ][ null ] );
			transactions[ ii ][ null ] = iNulls.slice();
		}

		for ( j = 0, jlen = this.tree2.keyRoots.length; j < jlen; j++ ) {

			// Make transactions of null -> tree
			keyRoot2 = this.tree2.orderedNodes[ this.tree2.keyRoots[ j ] ];
			jNulls = [];
			for ( jj = keyRoot2.leftmost; jj < keyRoot2.index + 1; jj++ ) {
				jNulls.push( this.transactionToIndex[ null ][ jj ] );
				transactions[ null ][ jj ] = jNulls.slice();
			}

			// Get the diff
			this.findMinimumTransactions( keyRoot1, keyRoot2, transactions );

			if ( new Date().getTime() > this.endTime ) {
				this.transactions = null;
				return;
			}
		}
	}

	for ( i = 0, ilen = this.tree1.orderedNodes.length; i < ilen; i++ ) {
		for ( j = 0, jlen = this.tree2.orderedNodes.length; j < jlen; j++ ) {
			if ( this.transactions[ i ][ j ] && this.transactions[ i ][ j ].length > 0 ) {
				this.transactions[ i ][ j ] = this.transactions[ i ][ j ].map( getTransactionFromIndex );
			}
		}
	}

};

/**
 * Get the cost of removing a node from the first tree, inserting a node into the second
 * tree, or relabelling a node from the first tree to a node from the second tree.
 *
 * @param {treeDiffer.TreeNode} node1 Node from the first tree
 * @param {treeDiffer.TreeNode} node2 Node from the second tree]
 * @return {number} Cost of the transaction
 */
treeDiffer.Differ.prototype.getNodeDistance = function ( node1, node2 ) {
	if ( node1 === null && node2 === null ) {
		return 0;
	}
	if ( node1 === null ) {
		return this.insertCost;
	}
	if ( node2 === null ) {
		return this.removeCost;
	}
	if ( node1.isEqual( node2 ) ) {
		return 0;
	}
	return this.changeCost;
};

/**
 * Find the minimum transactions to get from the first tree to the second tree. This
 * method is the heart of the tree differ.
 *
 * @param {treeDiffer.TreeNode} keyRoot1 A keyroot from the first tree
 * @param {treeDiffer.TreeNode} keyRoot2 A keyroot from the second tree
 * @param {Object} transactions Temporary store of transactions between trees
 */
treeDiffer.Differ.prototype.findMinimumTransactions = function ( keyRoot1, keyRoot2, transactions ) {
	var i, j, iMinus1, jMinus1, nodeDistance, transaction, remove, insert, change,
		orderedNode1, orderedNode2;

	function getLowestCost( removeCost, insertCost, changeCost ) {
		// This used to be written as:
		//  transaction = costs.indexOf( Math.min.apply( null, costs ) )
		// but expanding into two simple comparisons makes it much faster.
		var minCost = removeCost,
			index = 0;
		if ( insertCost < minCost ) {
			index = 1;
			minCost = insertCost;
		}
		if ( changeCost < minCost ) {
			index = 2;
		}
		return index;
	}

	for ( i = keyRoot1.leftmost; i < keyRoot1.index + 1; i++ ) {
		iMinus1 = i === keyRoot1.leftmost ? null : i - 1;
		orderedNode1 = this.tree1.orderedNodes[ i ];

		for ( j = keyRoot2.leftmost; j < keyRoot2.index + 1; j++ ) {
			jMinus1 = j === keyRoot2.leftmost ? null : j - 1;
			orderedNode2 = this.tree2.orderedNodes[ j ];

			if ( orderedNode1.leftmost === keyRoot1.leftmost && orderedNode2.leftmost === keyRoot2.leftmost ) {

				// Previous transactions, leading up to a remove, insert or change
				remove = transactions[ iMinus1 ][ j ];
				insert = transactions[ i ][ jMinus1 ];
				change = transactions[ iMinus1 ][ jMinus1 ];

				nodeDistance = this.getNodeDistance( orderedNode1, orderedNode2 );

				// Cost of each transaction
				transaction = getLowestCost(
					remove.length + this.removeCost,
					insert.length + this.insertCost,
					change.length + nodeDistance
				);

				if ( transaction === 0 ) {
					// Record a remove
					( transactions[ i ][ j ] = remove.slice() ).push(
						this.transactionToIndex[ i ][ null ]
					);
				} else if ( transaction === 1 ) {
					// Record an insert
					( transactions[ i ][ j ] = insert.slice() ).push(
						this.transactionToIndex[ null ][ j ]
					);
				} else {
					transactions[ i ][ j ] = change.slice();
					// If nodes i and j are different, record a change,
					// otherwise there is no transaction
					if ( nodeDistance === 1 ) {
						transactions[ i ][ j ].push( this.transactionToIndex[ i ][ j ] );
					}
				}

				this.transactions[ i ][ j ] = transactions[ i ][ j ].slice();
			} else {

				// Previous transactions, leading up to a remove, insert or change
				remove = transactions[ iMinus1 ][ j ];
				insert = transactions[ i ][ jMinus1 ];
				change = transactions[
					orderedNode1.leftmost - 1 < keyRoot1.leftmost ? null : orderedNode1.leftmost - 1
				][
					orderedNode2.leftmost - 1 < keyRoot2.leftmost ? null : orderedNode2.leftmost - 1
				];

				transaction = getLowestCost(
					remove.length + this.removeCost,
					insert.length + this.insertCost,
					change.length + this.transactions[ i ][ j ].length
				);
				if ( transaction === 0 ) {
					// Record a remove
					( transactions[ i ][ j ] = remove.slice() ).push(
						this.transactionToIndex[ i ][ null ]
					);
				} else if ( transaction === 1 ) {
					// Record an insert
					( transactions[ i ][ j ] = insert.slice() ).push(
						this.transactionToIndex[ null ][ j ]
					);
				} else {
					// Record a change
					transactions[ i ][ j ] = change.concat( this.transactions[ i ][ j ] );
				}

			}

		}

	}
};

/**
 * Given a set of transactions and the lengths of two trees, find the nodes that
 * correspond.
 *
 * @param {Array} transactions Minimum transactions to get from the first tree to the
 * second tree
 * @param {number} oldTreeLength Number of nodes in the first tree
 * @param {number} newTreeLength Number of nodes in the second tree
 * @return {Object} Corresponding nodes
 */
treeDiffer.Differ.prototype.getCorrespondingNodes = function ( transactions, oldTreeLength, newTreeLength ) {
	var i, j, rem, ins,
		oldToNew = {},
		newToOld = {},
		remove = [],
		insert = [],
		change = {},
		ilen = Math.max( oldTreeLength, newTreeLength ),
		jlen = ilen;

	for ( i = 0; i < transactions.length; i++ ) {
		if ( transactions[ i ][ 0 ] === null ) {
			insert.push( transactions[ i ][ 1 ] );
		} else if ( transactions[ i ][ 1 ] === null ) {
			remove.push( transactions[ i ][ 0 ] );
		} else {
			oldToNew[ transactions[ i ][ 0 ] ] = transactions[ i ][ 1 ];
			newToOld[ transactions[ i ][ 1 ] ] = transactions[ i ][ 0 ];
			change[ transactions[ i ][ 0 ] ] = transactions[ i ][ 1 ];
		}
	}

	rem = remove.slice();
	ins = insert.slice();

	remove.sort( function ( a, b ) {
		return a - b;
	} );
	insert.sort( function ( a, b ) {
		return a - b;
	} );

	for ( i = 0, j = 0; i < ilen && j < jlen; i++, j++ ) {
		if ( i === remove[ 0 ] ) {
			// Old node is a remove
			remove.shift();
			j--;
		} else if ( j === insert[ 0 ] ) {
			// New node is an insert
			insert.shift();
			i--;
		} else if ( !( i in oldToNew ) && !( j in newToOld ) ) {
			// Neither is changed, so they must correspond
			// NB Moves don't exist to the tree differ
			oldToNew[ i ] = j;
			newToOld[ j ] = i;
		} else if ( !( i in oldToNew ) ) {
			// Old node is unchanged, new node is changed
			i--;
		} else if ( !( j in newToOld ) ) {
			// New node is unchanged, old node is changed
			j--;
		}
	}

	return {
		oldToNew: oldToNew,
		newToOld: newToOld,
		remove: rem,
		insert: ins,
		change: change
	};
};
