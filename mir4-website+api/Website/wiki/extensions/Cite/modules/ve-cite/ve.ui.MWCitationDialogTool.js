'use strict';

/*!
 * VisualEditor MediaWiki UserInterface citation dialog tool class.
 *
 * @copyright 2011-2018 VisualEditor Team's Cite sub-team and others; see AUTHORS.txt
 * @license MIT
 */

/**
 * MediaWiki UserInterface citation dialog tool.
 *
 * @abstract
 * @constructor
 * @extends ve.ui.MWReferenceDialogTool
 * @param {OO.ui.Toolbar} toolbar
 * @param {Object} [config] Configuration options
 */
ve.ui.MWCitationDialogTool = function VeUiMWCitationDialogTool( toolbar, config ) {
	// Parent method
	ve.ui.MWCitationDialogTool.super.call( this, toolbar, config );
};

/* Inheritance */

OO.inheritClass( ve.ui.MWCitationDialogTool, ve.ui.MWReferenceDialogTool );

/* Static Properties */
ve.ui.MWCitationDialogTool.static.group = 'cite';

/**
 * Only display tool for single-template transclusions of these templates.
 *
 * @property {string|string[]|null}
 * @static
 * @inheritable
 */
ve.ui.MWCitationDialogTool.static.template = null;

/* Static Methods */

/**
 * @override
 */
ve.ui.MWCitationDialogTool.static.isCompatibleWith = function ( model ) {
	const compatible = ve.ui.MWCitationDialogTool.super.static.isCompatibleWith.call( this, model );

	if ( compatible && this.template ) {
		// Check if content of the reference node contains only a template with the same name as
		// this.template
		const internalItem = model.getInternalItem();
		const branches = internalItem.getChildren();
		if ( branches.length === 1 && branches[ 0 ].canContainContent() ) {
			const leaves = branches[ 0 ].getChildren();
			if ( leaves.length === 1 && leaves[ 0 ] instanceof ve.dm.MWTransclusionNode ) {
				return leaves[ 0 ].isSingleTemplate( this.template );
			}
		}
		return false;
	}

	return compatible;
};
