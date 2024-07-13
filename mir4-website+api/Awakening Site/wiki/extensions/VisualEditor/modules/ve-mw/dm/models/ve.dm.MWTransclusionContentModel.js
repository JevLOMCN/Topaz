/*!
 * VisualEditor DataModel MWTransclusionContentModel class.
 *
 * @copyright See AUTHORS.txt
 * @license The MIT License (MIT); see LICENSE.txt
 */

/**
 * Represents a raw wikitext snippet that is part of an unbalanced sequence of template invocations.
 * Meant to be an item in a {@see ve.dm.MWTransclusionModel}. Holds a back-reference to it's parent.
 *
 * @class
 * @extends ve.dm.MWTransclusionPartModel
 *
 * @constructor
 * @param {ve.dm.MWTransclusionModel} transclusion
 * @param {string} [wikitext='']
 */
ve.dm.MWTransclusionContentModel = function VeDmMWTransclusionContentModel( transclusion, wikitext ) {
	// Parent constructor
	ve.dm.MWTransclusionContentModel.super.call( this, transclusion );

	// Properties
	this.wikitext = wikitext || '';
};

/* Inheritance */

OO.inheritClass( ve.dm.MWTransclusionContentModel, ve.dm.MWTransclusionPartModel );

/* Events */

/**
 * Emitted when the wikitext changed.
 *
 * @event change
 */

/* Methods */

/**
 * @param {string} wikitext
 * @fires change
 */
ve.dm.MWTransclusionContentModel.prototype.setWikitext = function ( wikitext ) {
	if ( this.wikitext !== wikitext ) {
		this.wikitext = wikitext;
		this.emit( 'change' );
	}
};

/**
 * @inheritdoc
 */
ve.dm.MWTransclusionContentModel.prototype.serialize = function () {
	return this.wikitext;
};

/**
 * @inheritdoc
 */
ve.dm.MWTransclusionContentModel.prototype.containsValuableData = function () {
	return this.wikitext !== '';
};
