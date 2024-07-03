/*!
 * VisualEditor DataModel MWTransclusionPartModel class.
 *
 * @copyright See AUTHORS.txt
 * @license The MIT License (MIT); see LICENSE.txt
 */

/**
 * Abstract base class for items in a {@see ve.dm.MWTransclusionModel}. Holds a back-reference to
 * it's parent. Currently used for:
 * - {@see ve.dm.MWTemplateModel} for a single template invocation.
 * - {@see ve.dm.MWTemplatePlaceholderModel} while searching for a template name to be added.
 * - {@see ve.dm.MWTransclusionContentModel} for a raw wikitext snippet.
 *
 * @abstract
 * @class
 * @mixins OO.EventEmitter
 *
 * @constructor
 * @param {ve.dm.MWTransclusionModel} transclusion
 */
ve.dm.MWTransclusionPartModel = function VeDmMWTransclusionPartModel( transclusion ) {
	// Mixin constructors
	OO.EventEmitter.call( this );

	// Properties
	this.transclusion = transclusion;
	this.id = this.transclusion.nextUniquePartId();
};

/* Inheritance */

OO.mixinClass( ve.dm.MWTransclusionPartModel, OO.EventEmitter );

/* Events */

/**
 * Emitted when anything changed in the content the part represents, e.g. a parameter was added to a
 * template, or a value edited.
 *
 * @event change
 */

/* Methods */

/**
 * Get transclusion part is in.
 *
 * @return {ve.dm.MWTransclusionModel} Transclusion
 */
ve.dm.MWTransclusionPartModel.prototype.getTransclusion = function () {
	return this.transclusion;
};

/**
 * Get a unique part ID within the transclusion.
 *
 * @return {string} Unique ID
 */
ve.dm.MWTransclusionPartModel.prototype.getId = function () {
	return this.id;
};

/**
 * Remove part from transclusion.
 */
ve.dm.MWTransclusionPartModel.prototype.remove = function () {
	this.transclusion.removePart( this );
};

/**
 * Create a serialized representation of this part. Contains all information needed to recreate the
 * original wikitext, including extra whitespace. Used in
 * {@see ve.dm.MWTransclusionModel.getPlainObject}. The corresponding deserializer is in
 * {@see ve.dm.MWTransclusionNode.static.getWikitext}.
 *
 * @return {Object|string|undefined} Serialized representation, raw wikitext, or undefined if empty
 */
ve.dm.MWTransclusionPartModel.prototype.serialize = function () {
	return undefined;
};

/**
 * @return {boolean} True if there is meaningful user input that was not e.g. auto-generated
 */
ve.dm.MWTransclusionPartModel.prototype.containsValuableData = function () {
	return false;
};
