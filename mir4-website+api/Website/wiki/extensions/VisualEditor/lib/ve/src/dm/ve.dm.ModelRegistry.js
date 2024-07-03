/*!
 * VisualEditor ModelRegistry class.
 *
 * @copyright See AUTHORS.txt
 */
( function ( ve ) {

	/**
	 * Registry for models.
	 *
	 * To register a new model type, call #register.
	 *
	 * @extends OO.Registry
	 * @constructor
	 */
	ve.dm.ModelRegistry = function VeDmModelRegistry() {
		// Parent constructor
		OO.Registry.call( this );
		// Map of func presence and tag names to model names
		// [ { tagName: [modelNamesWithoutFunc] }, { tagName: [modelNamesWithFunc] } ]
		this.modelsByTag = [ {}, {} ];
		// Map of func presence and rdfaTypes to model names; only rdfaTypes specified as strings are in here
		// { matchFunctionPresence: { rdfaType: { tagName: [modelNames] } } }
		// [ { rdfaType: { tagName: [modelNamesWithoutFunc] } }, { rdfaType: { tagName: [modelNamesWithFunc] } ]
		this.modelsByTypeAndTag = [];
		// Map of func presence to array of model names with rdfaType regexps
		// [ [modelNamesWithoutFunc], [modelNamesWithFunc] ]
		this.modelsWithTypeRegExps = [ [], [] ];
		// Map tracking registration order
		// { nameA: 0, nameB: 1, … }
		this.registrationOrder = {};
		this.nextNumber = 0;
		this.extSpecificTypes = [];
	};

	/* Inheritance */

	OO.inheritClass( ve.dm.ModelRegistry, OO.Registry );

	/* Private helper functions */

	/**
	 * Helper function for register(). Adds a value to the front of an array in a nested object.
	 * Objects and arrays are created if needed. You can specify one or more keys and a value.
	 *
	 * Specifically:
	 *
	 * - `addType( obj, keyA, value )` does `obj[keyA].unshift( value );`
	 * - `addType( obj, keyA, keyB, value )` does `obj[keyA][keyB].unshift( value )`;
	 * - etc.
	 *
	 * @private
	 * @param {Object} obj Object the array resides in
	 * @param {...string} keys
	 * @param {Mixed} value
	 */
	function addType( obj ) {
		var keys = Array.prototype.slice.call( arguments, 1, -1 ),
			value = arguments[ arguments.length - 1 ],
			o = obj;

		var i, len;
		for ( i = 0, len = keys.length - 1; i < len; i++ ) {
			if ( o[ keys[ i ] ] === undefined ) {
				o[ keys[ i ] ] = {};
			}
			o = o[ keys[ i ] ];
		}
		o[ keys[ i ] ] = o[ keys[ i ] ] || [];
		o[ keys[ i ] ].unshift( value );
	}

	/**
	 * Helper function for unregister().
	 *
	 * Same arguments as addType, except removes the type from the list.
	 *
	 * @private
	 * @param {Object} obj Object the array resides in
	 * @param {...string} keys
	 * @param {Mixed} value to remove
	 */
	function removeType( obj ) {
		var keys = Array.prototype.slice.call( arguments, 1, -1 ),
			value = arguments[ arguments.length - 1 ],
			arr = ve.getProp.apply( obj, [ obj ].concat( keys ) );

		if ( arr ) {
			var index = arr.indexOf( value );
			if ( index !== -1 ) {
				arr.splice( index, 1 );
			}
			// TODO: Prune empty array and empty containing objects
		}
	}

	/* Public methods */

	/**
	 * Register a model type.
	 *
	 * @param {ve.dm.Model} constructor Subclass of ve.dm.Model
	 * @throws Model names must be strings and must not be empty
	 * @throws Models must be subclasses of ve.dm.Model
	 * @throws No factory associated with this ve.dm.Model subclass
	 */
	ve.dm.ModelRegistry.prototype.register = function ( constructor ) {
		var name = constructor.static && constructor.static.name;

		if ( typeof name !== 'string' || name === '' ) {
			throw new Error( 'Model names must be strings and must not be empty' );
		}
		if ( !( constructor.prototype instanceof ve.dm.Model ) ) {
			throw new Error( 'Models must be subclasses of ve.dm.Model' );
		}
		if ( this.lookup( name ) === constructor ) {
			// Don't allow double registration as it would create duplicate
			// entries in various caches.
			return;
		}

		// Register the model with the right factory
		if ( constructor.prototype instanceof ve.dm.Annotation ) {
			ve.dm.annotationFactory.register( constructor );
		} else if ( constructor.prototype instanceof ve.dm.Node ) {
			ve.dm.nodeFactory.register( constructor );
		} else {
			throw new Error( 'No factory associated with this ve.dm.Model subclass' );
		}

		// Parent method
		ve.dm.ModelRegistry.super.prototype.register.call( this, name, constructor );

		var tags = constructor.static.matchTagNames === null ?
			[ '' ] :
			constructor.static.matchTagNames;
		var types = constructor.static.getMatchRdfaTypes() === null ?
			[ '' ] :
			constructor.static.getMatchRdfaTypes();

		var i;
		for ( i = 0; i < tags.length; i++ ) {
			// +!!foo is a shorter equivalent of Number( Boolean( foo ) ) or foo ? 1 : 0
			addType( this.modelsByTag, +!!constructor.static.matchFunction,
				tags[ i ], name
			);
		}
		for ( i = 0; i < types.length; i++ ) {
			if ( types[ i ] instanceof RegExp ) {
				// TODO: Guard against running this again during subsequent
				// iterations of the for loop
				addType( this.modelsWithTypeRegExps, +!!constructor.static.matchFunction, name );
			} else {
				for ( var j = 0; j < tags.length; j++ ) {
					addType( this.modelsByTypeAndTag,
						+!!constructor.static.matchFunction, types[ i ], tags[ j ], name
					);
				}
			}
		}

		this.registrationOrder[ name ] = this.nextNumber++;
	};

	/**
	 * Unregister a model type.
	 *
	 * @param {ve.dm.Model} constructor Subclass of ve.dm.Model
	 * @throws Model names must be strings and must not be empty
	 * @throws Models must be subclasses of ve.dm.Model
	 * @throws No factory associated with this ve.dm.Model subclass
	 */
	ve.dm.ModelRegistry.prototype.unregister = function ( constructor ) {
		var name = constructor.static && constructor.static.name;

		if ( typeof name !== 'string' || name === '' ) {
			throw new Error( 'Model names must be strings and must not be empty' );
		}
		if ( !( constructor.prototype instanceof ve.dm.Model ) ) {
			throw new Error( 'Models must be subclasses of ve.dm.Model' );
		}

		// Unregister the model from the right factory
		if ( constructor.prototype instanceof ve.dm.Annotation ) {
			ve.dm.annotationFactory.unregister( constructor );
		} else if ( constructor.prototype instanceof ve.dm.Node ) {
			ve.dm.nodeFactory.unregister( constructor );
		} else {
			throw new Error( 'No factory associated with this ve.dm.Model subclass' );
		}

		// Parent method
		ve.dm.ModelRegistry.super.prototype.unregister.call( this, name );

		var tags = constructor.static.matchTagNames === null ?
			[ '' ] :
			constructor.static.matchTagNames;
		var types = constructor.static.getMatchRdfaTypes() === null ?
			[ '' ] :
			constructor.static.getMatchRdfaTypes();

		var i;
		for ( i = 0; i < tags.length; i++ ) {
			// +!!foo is a shorter equivalent of Number( Boolean( foo ) ) or foo ? 1 : 0
			removeType( this.modelsByTag, +!!constructor.static.matchFunction,
				tags[ i ], name
			);
		}
		for ( i = 0; i < types.length; i++ ) {
			if ( types[ i ] instanceof RegExp ) {
				// TODO: Guard against running this again during subsequent
				// iterations of the for loop
				removeType( this.modelsWithTypeRegExps, +!!constructor.static.matchFunction, name );
			} else {
				for ( var j = 0; j < tags.length; j++ ) {
					removeType( this.modelsByTypeAndTag,
						+!!constructor.static.matchFunction, types[ i ], tags[ j ], name
					);
				}
			}
		}

		delete this.registrationOrder[ name ];
	};

	/**
	 * Determine which model best matches the given node
	 *
	 * Model matching works as follows:
	 *
	 * Get all models whose tag and rdfaType rules match
	 *
	 * Rank them in order of specificity:
	 *
	 * - tag, rdfaType and func specified
	 * - rdfaType and func specified
	 * - tag and func specified
	 * - func specified
	 * - tag and rdfaType specified
	 * - rdfaType specified
	 * - tag specified
	 * - nothing specified
	 *
	 * If there are multiple candidates with the same specificity, exact matches of strings take precedence over
	 * matches of regular expressions. If there are still multiple candidates, they are ranked in reverse
	 * order of registration (i.e. if A was registered before B, B will rank above A).
	 * The highest-ranking model whose test function does not return false, wins.
	 *
	 * @param {Node} node Node to match (usually an HTMLElement but can also be a Comment node)
	 * @param {boolean} [forceAboutGrouping] If true, only match models with about grouping enabled
	 * @param {string[]} [excludeTypes] Model names to exclude when matching
	 * @return {string|null} Model type, or null if none found
	 */
	ve.dm.ModelRegistry.prototype.matchElement = function ( node, forceAboutGrouping, excludeTypes ) {
		var types,
			nodeName = node.nodeName.toLowerCase(),
			reg = this;

		function byRegistrationOrderDesc( a, b ) {
			return reg.registrationOrder[ b ] - reg.registrationOrder[ a ];
		}

		function matchTypeRegExps( type, tag, withFunc ) {
			var matchedModels = [],
				models = reg.modelsWithTypeRegExps[ +withFunc ];
			for ( var j = 0; j < models.length; j++ ) {
				if ( excludeTypes && excludeTypes.indexOf( models[ j ] ) !== -1 ) {
					continue;
				}
				var matchTypes = reg.registry[ models[ j ] ].static.getMatchRdfaTypes();
				for ( var k = 0; k < matchTypes.length; k++ ) {
					if (
						matchTypes[ k ] instanceof RegExp &&
						matchTypes[ k ].test( type ) &&
						(
							( tag === '' && reg.registry[ models[ j ] ].static.matchTagNames === null ) ||
							( reg.registry[ models[ j ] ].static.matchTagNames || [] ).indexOf( tag ) !== -1
						)
					) {
						matchedModels.push( models[ j ] );
					}
				}
			}
			return matchedModels;
		}

		function allTypesAllowed( model ) {
			var allowedTypes = model.static.getAllowedRdfaTypes();
			var matchTypes = model.static.getMatchRdfaTypes();

			// All types allowed
			if ( allowedTypes === null ) {
				return true;
			}

			if ( matchTypes !== null ) {
				allowedTypes = allowedTypes.concat( matchTypes );
			}

			function checkType( rule, type ) {
				return rule instanceof RegExp ? rule.test( type ) : rule === type;
			}

			for ( var j = 0; j < types.length; j++ ) {
				var typeAllowed = false;
				for ( var k = 0; k < allowedTypes.length; k++ ) {
					if ( checkType( allowedTypes[ k ], types[ j ] ) ) {
						typeAllowed = true;
						break;
					}
				}
				if ( !typeAllowed ) {
					return false;
				}
			}
			return true;
		}

		function matchWithFunc( tag ) {
			var queue = [],
				queue2 = [];
			var j;
			for ( j = 0; j < types.length; j++ ) {
				// Queue string matches and regexp matches separately
				queue = queue.concat( ve.getProp( reg.modelsByTypeAndTag, 1, types[ j ], tag ) || [] );
				if ( excludeTypes ) {
					queue = OO.simpleArrayDifference( queue, excludeTypes );
				}
				queue2 = queue2.concat( matchTypeRegExps( types[ j ], tag, true ) );
			}
			// Filter out matches which contain types which aren't allowed
			queue = queue.filter( function ( name ) {
				return allTypesAllowed( reg.lookup( name ) );
			} );
			queue2 = queue2.filter( function ( name ) {
				return allTypesAllowed( reg.lookup( name ) );
			} );
			if ( forceAboutGrouping ) {
				// Filter out matches that don't support about grouping
				queue = queue.filter( function ( name ) {
					return reg.registry[ name ].static.enableAboutGrouping;
				} );
				queue2 = queue2.filter( function ( name ) {
					return reg.registry[ name ].static.enableAboutGrouping;
				} );
			}
			// Try string matches first, then regexp matches
			queue.sort( byRegistrationOrderDesc );
			queue2.sort( byRegistrationOrderDesc );
			queue = queue.concat( queue2 );
			for ( j = 0; j < queue.length; j++ ) {
				if ( reg.registry[ queue[ j ] ].static.matchFunction( node ) ) {
					return queue[ j ];
				}
			}
			return null;
		}

		function matchWithoutFunc( tag ) {
			var queue = [],
				queue2 = [],
				winningName = null;
			var j;
			for ( j = 0; j < types.length; j++ ) {
				// Queue string and regexp matches separately
				queue = queue.concat( ve.getProp( reg.modelsByTypeAndTag, 0, types[ j ], tag ) || [] );
				if ( excludeTypes ) {
					queue = OO.simpleArrayDifference( queue, excludeTypes );
				}
				queue2 = queue2.concat( matchTypeRegExps( types[ j ], tag, false ) );
			}
			// Filter out matches which contain types which aren't allowed
			queue = queue.filter( function ( name ) {
				return allTypesAllowed( reg.lookup( name ) );
			} );
			queue2 = queue2.filter( function ( name ) {
				return allTypesAllowed( reg.lookup( name ) );
			} );
			if ( forceAboutGrouping ) {
				// Filter out matches that don't support about grouping
				queue = queue.filter( function ( name ) {
					return reg.registry[ name ].static.enableAboutGrouping;
				} );
				queue2 = queue2.filter( function ( name ) {
					return reg.registry[ name ].static.enableAboutGrouping;
				} );
			}
			// Only try regexp matches if there are no string matches
			queue = queue.length > 0 ? queue : queue2;
			// Find most recently registered
			for ( j = 0; j < queue.length; j++ ) {
				if (
					winningName === null ||
					reg.registrationOrder[ winningName ] < reg.registrationOrder[ queue[ j ] ]
				) {
					winningName = queue[ j ];
				}
			}
			return winningName;
		}

		types = [];
		if ( node.getAttribute ) {
			if ( node.getAttribute( 'rel' ) ) {
				types = types.concat( node.getAttribute( 'rel' ).trim().split( /\s+/ ) );
			}
			if ( node.getAttribute( 'typeof' ) ) {
				types = types.concat( node.getAttribute( 'typeof' ).trim().split( /\s+/ ) );
			}
			if ( node.getAttribute( 'property' ) ) {
				types = types.concat( node.getAttribute( 'property' ).trim().split( /\s+/ ) );
			}
		}

		var winner;
		if ( types.length ) {
			// func+tag+type match
			winner = matchWithFunc( nodeName );
			if ( winner !== null ) {
				return winner;
			}

			// func+type match
			// Only look at rules with no tag specified; if a rule does specify a tag, we've
			// either already processed it above, or the tag doesn't match
			winner = matchWithFunc( '' );
			if ( winner !== null ) {
				return winner;
			}
		}

		// func+tag match
		var matches = ve.getProp( this.modelsByTag, 1, nodeName ) || [];
		// No need to sort because individual arrays in modelsByTag are already sorted
		// correctly
		var i;
		var m;
		for ( i = 0; i < matches.length; i++ ) {
			m = this.registry[ matches[ i ] ];
			// Only process this one if it doesn't specify types
			// If it does specify types, then we've either already processed it in the
			// func+tag+type step above, or its type rule doesn't match
			if ( m.static.getMatchRdfaTypes() === null && m.static.matchFunction( node ) && allTypesAllowed( m ) ) {
				return matches[ i ];
			}
		}

		// func only
		// We only need to get the [''][''] array because the other arrays were either
		// already processed during the steps above, or have a type or tag rule that doesn't
		// match this node.
		// No need to sort because individual arrays in modelsByTypeAndTag are already sorted
		// correctly
		matches = ve.getProp( this.modelsByTypeAndTag, 1, '', '' ) || [];
		for ( i = 0; i < matches.length; i++ ) {
			m = this.registry[ matches[ i ] ];
			if ( m.static.matchFunction( node ) && allTypesAllowed( m ) ) {
				return matches[ i ];
			}
		}

		// tag+type
		winner = matchWithoutFunc( nodeName );
		if ( winner !== null ) {
			return winner;
		}

		// type only
		// Only look at rules with no tag specified; if a rule does specify a tag, we've
		// either already processed it above, or the tag doesn't match
		winner = matchWithoutFunc( '' );
		if ( winner !== null ) {
			return winner;
		}

		// tag only
		matches = ve.getProp( this.modelsByTag, 0, nodeName ) || [];
		// No need to track winningName because the individual arrays in modelsByTag are
		// already sorted correctly
		for ( i = 0; i < matches.length; i++ ) {
			m = this.registry[ matches[ i ] ];
			// Only process this one if it doesn't specify types
			// If it does specify types, then we've either already processed it in the
			// tag+type step above, or its type rule doesn't match
			if ( m.static.getMatchRdfaTypes() === null && allTypesAllowed( m ) ) {
				return matches[ i ];
			}
		}

		// Rules with no type or tag specified
		// These are the only rules that can still qualify at this point, the others we've either
		// already processed or have a type or tag rule that disqualifies them
		matches = ve.getProp( this.modelsByTypeAndTag, 0, '', '' ) || [];
		for ( i = 0; i < matches.length; i++ ) {
			m = this.registry[ matches[ i ] ];
			if ( allTypesAllowed( m ) ) {
				return matches[ i ];
			}
		}

		// We didn't find anything, give up
		return null;
	};

	/**
	 * Tests whether a node will be modelled as an annotation
	 *
	 * @param {Node} node The node
	 * @return {boolean} Whether the element will be modelled as an annotation
	 */
	ve.dm.ModelRegistry.prototype.isAnnotation = function ( node ) {
		var modelClass = this.lookup( this.matchElement( node ) );
		return ( modelClass && modelClass.prototype ) instanceof ve.dm.Annotation;
	};

	/* Initialization */

	ve.dm.modelRegistry = new ve.dm.ModelRegistry();

}( ve ) );
