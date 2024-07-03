/*!
 * VisualEditor rebaser demo
 *
 * @copyright See AUTHORS.txt
 */

( function () {
	function RebaserTarget() {
		RebaserTarget.super.apply( this, arguments );

		// HACK: Disable redo command until supported (T185706)
		ve.ui.commandRegistry.unregister( 'redo' );
	}

	OO.inheritClass( RebaserTarget, ve.init.sa.Target );

	var linkIndex = RebaserTarget.static.toolbarGroups.findIndex( function ( group ) {
		return group.name === 'link';
	} );
	RebaserTarget.static.toolbarGroups = ve.copy( RebaserTarget.static.toolbarGroups );
	RebaserTarget.static.toolbarGroups.splice( linkIndex + 1, 0, {
		name: 'commentAnnotation',
		include: [ 'commentAnnotation' ]
	} );
	RebaserTarget.static.toolbarGroups.push(
		{
			name: 'authorList',
			align: 'after',
			include: [ 'authorList' ]
		}
	);

	new ve.init.sa.Platform( ve.messagePaths ).initialize().done( function () {
		var progressDeferred = ve.createDeferred(),
			panel = new OO.ui.PanelLayout( {
				// eslint-disable-next-line no-jquery/no-global-selector
				$element: $( '.ve-demo-editor' ),
				expanded: false,
				framed: true
			} ),
			target = new RebaserTarget();

		panel.$element.append( target.$element );

		// Add a dummy surface while the doc is loading
		var dummySurface = target.addSurface( ve.dm.converter.getModelFromDom( ve.createDocumentFromHtml( '' ) ) );
		dummySurface.setReadOnly( true );

		// TODO: Create the correct model surface type (ve.ui.Surface#createModel)
		var surfaceModel = new ve.dm.Surface( ve.dm.converter.getModelFromDom( ve.createDocumentFromHtml( '' ) ) );
		surfaceModel.createSynchronizer(
			ve.docName,
			{ server: this.rebaserUrl }
		);

		dummySurface.createProgress( progressDeferred.promise(), ve.msg( 'visualeditor-rebase-client-connecting' ), true );

		surfaceModel.synchronizer.once( 'initDoc', function ( error ) {
			progressDeferred.resolve();
			target.clearSurfaces();
			if ( error ) {
				OO.ui.alert(
					$( '<p>' ).append(
						ve.htmlMsg( 'visualeditor-rebase-corrupted-document-error', $( '<pre>' ).text( error.stack ) )
					),
					{ title: ve.msg( 'visualeditor-rebase-corrupted-document-title' ), size: 'large' }
				).then( function () {
					// TODO: Go back to landing page?
				} );
				return;
			}
			// Don't add the surface until the history has been applied
			target.addSurface( surfaceModel );
			target.getSurface().getView().focus();
		} );

	} );
}() );
