/*!
 * VisualEditor DataModel MWParameterModel class.
 *
 * @copyright See AUTHORS.txt
 * @license The MIT License (MIT); see LICENSE.txt
 */

/**
 * Represents a parameter that's part of a template invocation, centered around the parameter's
 * value. Holds a reference to the specification of the template and the parameter as it is
 * documented via TemplateData. Meant to be a sub-element of a {@see ve.dm.MWTemplateModel}, which
 * is a sub-element of a {@see ve.dm.MWTransclusionModel}.
 *
 * @class
 * @mixins OO.EventEmitter
 *
 * @constructor
 * @param {ve.dm.MWTemplateModel} template Reference back to the template that contains the
 *  parameter, as well as to the specification
 * @param {string} [name] Can be missing or empty when meant to be used as a placeholder. Parameters
 *  without a name won't be serialized to wikitext, {@see ve.dm.MWTemplateModel.serialize}.
 * @param {string} [value='']
 */
ve.dm.MWParameterModel = function VeDmMWParameterModel( template, name, value ) {
	// Mixin constructors
	OO.EventEmitter.call( this );

	// Properties
	this.template = template;
	this.originalName = name;
	this.name = typeof name === 'string' ? name.trim() : '';
	this.value = value || '';
	this.id = this.template.getId() + '/' + this.name;
};

/* Inheritance */

OO.mixinClass( ve.dm.MWParameterModel, OO.EventEmitter );

/* Events */

/**
 * Emitted when the parameter's value changed.
 *
 * @event change
 */

/* Methods */

/**
 * @return {boolean}
 */
ve.dm.MWParameterModel.prototype.isRequired = function () {
	return this.template.getSpec().isParameterRequired( this.name );
};

/**
 * @return {boolean}
 */
ve.dm.MWParameterModel.prototype.isSuggested = function () {
	return this.template.getSpec().isParameterSuggested( this.name );
};

/**
 * @return {boolean}
 */
ve.dm.MWParameterModel.prototype.isDeprecated = function () {
	return this.template.getSpec().isParameterDeprecated( this.name );
};

/**
 * @return {boolean}
 */
ve.dm.MWParameterModel.prototype.isDocumented = function () {
	return this.template.getSpec().isParameterDocumented( this.name );
};

/**
 * Get template of which this parameter is part.
 *
 * @return {ve.dm.MWTemplateModel}
 */
ve.dm.MWParameterModel.prototype.getTemplate = function () {
	return this.template;
};

/**
 * Get unique parameter ID within the transclusion.
 *
 * @return {string} Unique ID
 */
ve.dm.MWParameterModel.prototype.getId = function () {
	return this.id;
};

/**
 * @return {string} Trimmed parameter name, or an empty string if no name was provided
 */
ve.dm.MWParameterModel.prototype.getName = function () {
	return this.name;
};

/**
 * Original parameter name. Will be used in {@see ve.dm.MWTransclusionPartModel.serialize}.
 *
 * @return {string|undefined} Untrimmed parameter name as provided on construction time
 */
ve.dm.MWParameterModel.prototype.getOriginalName = function () {
	return this.originalName;
};

/**
 * @return {string} Parameter value, or automatic value if there is none stored.
 *  Otherwise an empty string.
 */
ve.dm.MWParameterModel.prototype.getValue = function () {
	return this.value || this.getAutoValue() || '';
};

/**
 * @return {string[]}
 */
ve.dm.MWParameterModel.prototype.getSuggestedValues = function () {
	return this.template.getSpec().getParameterSuggestedValues( this.name );
};

/**
 * @return {string}
 */
ve.dm.MWParameterModel.prototype.getDefaultValue = function () {
	return this.template.getSpec().getParameterDefaultValue( this.name );
};

/**
 * @return {string|null}
 */
ve.dm.MWParameterModel.prototype.getExampleValue = function () {
	return this.template.getSpec().getParameterExampleValue( this.name );
};

/**
 * @return {string}
 */
ve.dm.MWParameterModel.prototype.getAutoValue = function () {
	return this.template.getSpec().getParameterAutoValue( this.name );
};

/**
 * @return {string} Parameter type, e.g. "string"
 */
ve.dm.MWParameterModel.prototype.getType = function () {
	return this.template.getSpec().getParameterType( this.name );
};

/**
 * @param {string} value
 * @fires change
 */
ve.dm.MWParameterModel.prototype.setValue = function ( value ) {
	if ( this.value !== value ) {
		this.value = value;
		this.emit( 'change' );
	}
};

/**
 * Remove parameter from template.
 */
ve.dm.MWParameterModel.prototype.remove = function () {
	this.template.removeParameter( this );
};
