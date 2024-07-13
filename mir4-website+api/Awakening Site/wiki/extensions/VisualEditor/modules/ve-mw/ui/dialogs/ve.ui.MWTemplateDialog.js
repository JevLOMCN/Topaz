/*!
 * VisualEditor user interface MWTemplateDialog class.
 *
 * @copyright See AUTHORS.txt
 * @license The MIT License (MIT); see LICENSE.txt
 */

/**
 * Abstract base class for dialogs that allow to insert and edit MediaWiki transclusions, i.e. a
 * sequence of one or more template invocations that strictly belong to each other (e.g. because
 * they are unbalanced), possibly mixed with raw wikitext snippets. Currently used for:
 * - {@see ve.ui.MWTransclusionDialog} for arbitrary transclusions. Registered via the name
 *   "transclusion".
 * - {@see ve.ui.MWCitationDialog} in the Cite extension for the predefined citation types from
 *   [[MediaWiki:visualeditor-cite-tool-definition.json]]. These are strictly limited to a single
 *   template invocation. Registered via the name "cite".
 *
 * @class
 * @abstract
 * @extends ve.ui.NodeDialog
 *
 * @constructor
 * @param {Object} [config] Configuration options
 * @property {ve.dm.MWTransclusionModel|null} transclusionModel
 * @property {ve.ui.MWTransclusionOutlineWidget} sidebar
 * @property {boolean} [canGoBack=false]
 */
ve.ui.MWTemplateDialog = function VeUiMWTemplateDialog( config ) {
	// Parent constructor
	ve.ui.MWTemplateDialog.super.call( this, config );

	// Properties
	this.transclusionModel = null;
	this.loaded = false;
	this.altered = false;
	this.canGoBack = false;
	this.preventReselection = false;

	this.confirmDialogs = new ve.ui.WindowManager( { factory: ve.ui.windowFactory, isolate: true } );
	$( OO.ui.getTeleportTarget() ).append( this.confirmDialogs.$element );
};

/* Inheritance */

OO.inheritClass( ve.ui.MWTemplateDialog, ve.ui.NodeDialog );

/* Static Properties */

ve.ui.MWTemplateDialog.static.modelClasses = [ ve.dm.MWTransclusionNode ];

/**
 * Configuration for the {@see ve.ui.MWTwoPaneTransclusionDialogLayout} used in this dialog.
 *
 * @static
 * @property {Object}
 * @inheritable
 */
ve.ui.MWTemplateDialog.static.bookletLayoutConfig = {};

/* Methods */

/**
 * @inheritdoc
 */
ve.ui.MWTemplateDialog.prototype.getReadyProcess = function ( data ) {
	return ve.ui.MWTemplateDialog.super.prototype.getReadyProcess.call( this, data )
		.next( function () {
			if ( this.transclusionModel.isEmpty() ) {
				// Focus the template placeholder input field.
				this.bookletLayout.focus();
			}

			this.bookletLayout.getPagesOrdered().forEach( function ( page ) {
				if ( page instanceof ve.ui.MWParameterPage ) {
					page.updateSize();
				}
			} );
		}, this );
};

/**
 * Update dialog actions whenever the content changes.
 *
 * @private
 */
ve.ui.MWTemplateDialog.prototype.touch = function () {
	if ( this.loaded ) {
		this.altered = true;
		this.setApplicableStatus();
	}
};

/**
 * Handle parts being replaced.
 *
 * @protected
 * @param {ve.dm.MWTransclusionPartModel|null} removed Removed part
 * @param {ve.dm.MWTransclusionPartModel|null} added Added part
 */
ve.ui.MWTemplateDialog.prototype.onReplacePart = function ( removed, added ) {
	var removePages = [];

	if ( removed ) {
		// Remove parameter pages of removed templates
		if ( removed instanceof ve.dm.MWTemplateModel ) {
			var params = removed.getParameters();
			for ( var name in params ) {
				removePages.push( params[ name ].getId() );
			}
			removed.disconnect( this );
		}
		removePages.push( removed.getId() );
		this.bookletLayout.removePages( removePages );
	}

	if ( added ) {
		var page = this.getPageFromPart( added );
		if ( page ) {
			var reselect;

			this.bookletLayout.addPages( [ page ], this.transclusionModel.getIndex( added ) );
			if ( removed ) {
				// When we're replacing a part, it can only be a template placeholder
				// becoming an actual template.  Focus this new template.
				reselect = added.getId();
			}

			if ( added instanceof ve.dm.MWTemplateModel ) {
				// Prevent selection changes while parameters are added
				this.preventReselection = true;

				// Add existing params to templates (the template might be being moved)
				var names = added.getOrderedParameterNames();
				for ( var i = 0; i < names.length; i++ ) {
					this.onAddParameter( added.getParameter( names[ i ] ) );
				}
				added.connect( this, { add: 'onAddParameter', remove: 'onRemoveParameter' } );

				this.preventReselection = false;

				if ( this.loaded ) {
					if ( reselect ) {
						this.bookletLayout.focusPart( reselect );
					}
				}

				var documentedParameters = added.getSpec().getDocumentedParameterOrder(),
					undocumentedParameters = added.getSpec().getUndocumentedParameterNames();

				if ( !documentedParameters.length || undocumentedParameters.length ) {
					page.addPlaceholderParameter();
				}
			}
		}
	}

	if ( added || removed ) {
		this.touch();
	}
	this.updateTitle();
};

/**
 * Handle add param events.
 *
 * @private
 * @param {ve.dm.MWParameterModel} param Added param
 */
ve.ui.MWTemplateDialog.prototype.onAddParameter = function ( param ) {
	var page;

	if ( param.getName() ) {
		page = new ve.ui.MWParameterPage( param, {
			$overlay: this.$overlay, readOnly: this.isReadOnly()
		} )
			.connect( this, {
				hasValueChange: 'onHasValueChange'
			} );
	} else {
		// Create parameter placeholder.
		page = new ve.ui.MWAddParameterPage( param, param.getId(), {
			$overlay: this.$overlay
		} )
			.connect( this, {
				templateParameterAdded: this.bookletLayout.focusPart.bind( this.bookletLayout )
			} );
	}
	this.bookletLayout.addPages( [ page ], this.transclusionModel.getIndex( param ) );
	if ( this.loaded ) {
		this.touch();

		if ( page instanceof ve.ui.MWParameterPage ) {
			page.updateSize();
		}
	}
};

/**
 * Handle remove param events.
 *
 * @private
 * @param {ve.dm.MWParameterModel} param Removed param
 */
ve.ui.MWTemplateDialog.prototype.onRemoveParameter = function ( param ) {
	this.bookletLayout.removePages( [ param.getId() ] );

	this.touch();
};

/**
 * Sets transclusion applicable status
 *
 * If the transclusion is empty or only contains a placeholder it will not be insertable.
 * If the transclusion only contains a placeholder it will not be editable.
 *
 * @private
 */
ve.ui.MWTemplateDialog.prototype.setApplicableStatus = function () {
	var canSave = !this.transclusionModel.isEmpty();
	this.actions.setAbilities( { done: canSave && this.altered } );
};

/**
 * @inheritdoc
 */
ve.ui.MWTemplateDialog.prototype.getBodyHeight = function () {
	return 400;
};

/**
 * Get a page for a transclusion part.
 *
 * @protected
 * @param {ve.dm.MWTransclusionModel} part Part to get page for
 * @return {OO.ui.PageLayout|null} Page for part, null if no matching page could be found
 */
ve.ui.MWTemplateDialog.prototype.getPageFromPart = function ( part ) {
	if ( part instanceof ve.dm.MWTemplateModel ) {
		return new ve.ui.MWTemplatePage( part, part.getId(), { $overlay: this.$overlay, isReadOnly: this.isReadOnly() } );
	} else if ( part instanceof ve.dm.MWTemplatePlaceholderModel ) {
		return new ve.ui.MWTemplatePlaceholderPage(
			part,
			part.getId(),
			{ $overlay: this.$overlay }
		);
	}
	return null;
};

/**
 * @inheritdoc
 */
ve.ui.MWTemplateDialog.prototype.getSelectedNode = function ( data ) {
	var selectedNode = ve.ui.MWTemplateDialog.super.prototype.getSelectedNode.call( this );

	// Data initialization
	data = data || {};

	// Require template to match if specified
	if ( selectedNode && data.template && !selectedNode.isSingleTemplate( data.template ) ) {
		return null;
	}

	return selectedNode;
};

/**
 * Update the dialog title.
 *
 * @protected
 */
ve.ui.MWTemplateDialog.prototype.updateTitle = function () {
	var title = ve.msg( 'visualeditor-dialog-transclusion-loading' );

	if ( this.transclusionModel.isSingleTemplate() ) {
		var part = this.transclusionModel.getParts()[ 0 ];
		if ( part instanceof ve.dm.MWTemplateModel ) {
			title = ve.msg(
				this.getMode() === 'insert' ?
					'visualeditor-dialog-transclusion-title-insert-known-template' :
					'visualeditor-dialog-transclusion-title-edit-known-template',
				part.getSpec().getLabel()
			);
		} else {
			title = ve.msg( 'visualeditor-dialog-transclusion-title-insert-template' );
		}
	}
	this.title.setLabel( title );
};

/**
 * @inheritdoc
 */
ve.ui.MWTemplateDialog.prototype.initialize = function () {
	// Parent method
	ve.ui.MWTemplateDialog.super.prototype.initialize.call( this );

	// Properties
	this.bookletLayout = new ve.ui.MWTwoPaneTransclusionDialogLayout( this.constructor.static.bookletLayoutConfig );
	// TODO: Remove once all references are gone.
	this.sidebar = this.bookletLayout.sidebar;

	// Initialization
	this.$content.addClass( 've-ui-mwTemplateDialog' );
	// bookletLayout is appended after the form has been built in getSetupProcess for performance
};

/**
 * If the user has left blank required parameters, confirm that they actually want to do this.
 * If no required parameters were left blank, or if they were but the user decided to go ahead
 *  anyway, the returned deferred will be resolved.
 * Otherwise, the returned deferred will be rejected.
 *
 * @private
 * @return {jQuery.Deferred}
 */
ve.ui.MWTemplateDialog.prototype.checkRequiredParameters = function () {
	var blankRequired = [],
		deferred = ve.createDeferred();

	this.bookletLayout.stackLayout.getItems().forEach( function ( page ) {
		if ( !( page instanceof ve.ui.MWParameterPage ) ) {
			return;
		}
		if ( page.parameter.isRequired() && !page.valueInput.getValue() ) {
			blankRequired.push( mw.msg(
				'quotation-marks',
				page.parameter.template.getSpec().getParameterLabel( page.parameter.getName() )
			) );
		}
	} );
	if ( blankRequired.length ) {
		this.confirmDialogs.openWindow( 'requiredparamblankconfirm', {
			message: mw.msg(
				'visualeditor-dialog-transclusion-required-parameter-is-blank',
				mw.language.listToText( blankRequired ),
				blankRequired.length
			),
			title: mw.msg(
				'visualeditor-dialog-transclusion-required-parameter-dialog-title',
				blankRequired.length
			)
		} ).closed.then( function ( data ) {
			if ( data && data.action === 'ok' ) {
				deferred.resolve();
			} else {
				deferred.reject();
			}
		} );
	} else {
		deferred.resolve();
	}
	return deferred.promise();
};

/**
 * @inheritdoc
 */
ve.ui.MWTemplateDialog.prototype.getActionProcess = function ( action ) {
	var dialog = this;

	if ( action === 'done' ) {
		return new OO.ui.Process( function () {
			var deferred = ve.createDeferred();
			dialog.checkRequiredParameters().done( function () {
				var surfaceModel = dialog.getFragment().getSurface(),
					obj = dialog.transclusionModel.getPlainObject(),
					modelPromise = ve.createDeferred().resolve().promise();

				dialog.pushPending();

				if ( dialog.selectedNode instanceof ve.dm.MWTransclusionNode ) {
					dialog.transclusionModel.updateTransclusionNode( surfaceModel, dialog.selectedNode );
					// TODO: updating the node could result in the inline/block state change
				} else if ( obj !== null ) {
					// Collapse returns a new fragment, so update dialog.fragment
					dialog.fragment = dialog.getFragment().collapseToEnd();
					modelPromise = dialog.transclusionModel.insertTransclusionNode( dialog.getFragment() );
				}

				// TODO tracking will only be implemented temporarily to answer questions on
				// template usage for the Technical Wishes topic area see T258917
				var templateEvent = {
					action: 'save',
					// eslint-disable-next-line camelcase
					template_names: []
				};
				var editCountBucket = mw.config.get( 'wgUserEditCountBucket' );
				if ( editCountBucket !== null ) {
					// eslint-disable-next-line camelcase
					templateEvent.user_edit_count_bucket = editCountBucket;
				}
				var parts = dialog.transclusionModel.getParts();
				for ( var i = 0; i < parts.length; i++ ) {
					// Only {@see ve.dm.MWTemplateModel} have a title
					var title = parts[ i ].getTitle && parts[ i ].getTitle();
					if ( title ) {
						templateEvent.template_names.push( title );
					}
				}
				mw.track( 'event.VisualEditorTemplateDialogUse', templateEvent );

				return modelPromise.then( function () {
					dialog.close( { action: action } ).closed.always( dialog.popPending.bind( dialog ) );
				} );
			} ).always( deferred.resolve );

			return deferred;
		} );
	}

	return ve.ui.MWTemplateDialog.super.prototype.getActionProcess.call( this, action );
};

/**
 * @inheritdoc
 */
ve.ui.MWTemplateDialog.prototype.getSetupProcess = function ( data ) {
	data = data || {};
	return ve.ui.MWTemplateDialog.super.prototype.getSetupProcess.call( this, data )
		.next( function () {
			var promise,
				dialog = this;

			// Properties
			this.loaded = false;
			this.altered = false;
			this.transclusionModel = new ve.dm.MWTransclusionModel( this.getFragment().getDocument() );

			// Events
			this.transclusionModel.connect( this, {
				replace: 'onReplacePart',
				change: 'touch'
			} );

			// Detach the form while building for performance
			this.bookletLayout.$element.detach();

			this.transclusionModel.connect( this.bookletLayout, { replace: 'onReplacePart' } );

			// Initialization
			if ( !this.selectedNode ) {
				if ( data.template ) {
					// The template name is from MediaWiki:Visualeditor-cite-tool-definition.json,
					// passed via a ve.ui.Command, which triggers a ve.ui.MWCitationAction, which
					// executes ve.ui.WindowAction.open(), which opens this dialog.
					var template = ve.dm.MWTemplateModel.newFromName(
						this.transclusionModel, data.template
					);
					promise = this.transclusionModel.addPart( template );
				} else {
					// Open the dialog to add a new template, always starting with a placeholder
					var placeholderPage = new ve.dm.MWTemplatePlaceholderModel( this.transclusionModel );
					promise = this.transclusionModel.addPart( placeholderPage );
					promise.then( function () {
						dialog.bookletLayout.setPage( placeholderPage.getId() );
					} );
					this.canGoBack = true;
				}
			} else {
				// Open the dialog to edit an existing template

				// TODO tracking will only be implemented temporarily to answer questions on
				// template usage for the Technical Wishes topic area see T258917
				var templateEvent = {
					action: 'edit',
					// eslint-disable-next-line camelcase
					template_names: []
				};
				var editCountBucket = mw.config.get( 'wgUserEditCountBucket' );
				if ( editCountBucket !== null ) {
					// eslint-disable-next-line camelcase
					templateEvent.user_edit_count_bucket = editCountBucket;
				}
				for ( var i = 0; i < this.selectedNode.partsList.length; i++ ) {
					if ( this.selectedNode.partsList[ i ].templatePage ) {
						templateEvent.template_names.push( this.selectedNode.partsList[ i ].templatePage );
					}
				}
				mw.track( 'event.VisualEditorTemplateDialogUse', templateEvent );

				promise = this.transclusionModel
					.load( ve.copy( this.selectedNode.getAttribute( 'mw' ) ) )
					.then( this.initializeTemplateParameters.bind( this ) );
			}
			this.actions.setAbilities( { done: false } );

			return promise.then( function () {
				// Add missing required and suggested parameters to each transclusion.
				dialog.transclusionModel.addPromptedParameters();

				dialog.$body.append( dialog.bookletLayout.$element );
				dialog.$element.addClass( 've-ui-mwTemplateDialog-ready' );
				dialog.loaded = true;
			} );
		}, this );
};

/**
 * Intentionally empty. This is provided for Wikia extensibility.
 */
ve.ui.MWTemplateDialog.prototype.initializeTemplateParameters = function () {};

/**
 * @private
 * @param {string} pageName
 * @param {boolean} hasValue
 */
ve.ui.MWTemplateDialog.prototype.onHasValueChange = function ( pageName, hasValue ) {
	this.sidebar.toggleHasValueByPageName( pageName, hasValue );
};

/**
 * @inheritdoc
 */
ve.ui.MWTemplateDialog.prototype.getTeardownProcess = function ( data ) {
	return ve.ui.MWTemplateDialog.super.prototype.getTeardownProcess.call( this, data )
		.first( function () {
			// Cleanup
			this.$element.removeClass( 've-ui-mwTemplateDialog-ready' );
			this.transclusionModel.disconnect( this );
			this.transclusionModel.abortAllApiRequests();
			this.transclusionModel = null;
			this.bookletLayout.clearPages();
			this.content = null;
		}, this );
};
