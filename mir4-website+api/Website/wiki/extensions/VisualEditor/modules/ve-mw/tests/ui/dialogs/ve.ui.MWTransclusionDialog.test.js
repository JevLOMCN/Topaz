/*!
 * VisualEditor Template Dialog and Transclusion Dialog tests
 *
 * @copyright See AUTHORS.txt
 */

{
	QUnit.module( 've.ui.MWTransclusionDialog', ve.test.utils.newMwEnvironment( {
		config: {
			// Set config variable to activate new sidebar feature
			// TODO: remove this when sidebar feature will be default
			wgVisualEditorConfig: ve.extendObject( {}, mw.config.get( 'wgVisualEditorConfig' ), {
				transclusionDialogNewSidebar: true
			} )
		}
	} ) );

	const createFragmentFromDoc = function ( doc ) {
		// convert doc to something ui magical
		const surface = new ve.dm.Surface( doc );
		// VE block from surface
		const fragment = surface.getLinearFragment( new ve.Range( 1 ) );

		// return fragment as data for the dialog
		return { fragment: fragment };
	};

	QUnit.test.skip( 'onReplacePart', ( assert ) => {
		// don't kill test until this promise is resolved, to allow the async workflow to complete
		const finishTest = assert.async();

		// new wiki page and fragment
		const doc = ve.dm.Document.static.newBlankDocument();
		const fragment = createFragmentFromDoc( doc );

		// new popup window
		const dialog = new ve.ui.MWTransclusionDialog();
		const windowManager = new OO.ui.WindowManager();
		windowManager.addWindows( [ dialog ] );
		const windowInstance = windowManager.openWindow( dialog, fragment );

		windowInstance.opened.done( () => {
			const transclusion = dialog.transclusionModel;
			// mock api call with template data for Test
			const templateData = {
				title: 'Template:Test',
				params: {
					Blub: {
						label: 'Blub',
						description: 'blub',
						type: 'string',
						required: true
					},
					Foo: {
						label: 'Foo',
						description: 'Foo',
						type: 'string',
						required: true
					}
				}
			};
			transclusion.cacheTemplateDataApiResponse( { pages: [ templateData ] } );

			// add a template with an undocumented parameter to the dialog
			const data = {
				target: {
					title: 'Template:Test',
					wt: 'Test',
					href: 'Template:Test'
				},
				params: {
					test: {
					}
				},
				i: 0
			};
			const template = ve.dm.MWTemplateModel.newFromData( transclusion, data );

			// change transclusion model (onReplacePart happens automatically)
			const promise = transclusion.addPart( template );

			promise.done( () => {
				// checking for parameter checkboxes
				// (should be 3 because of 2 predefined and 1 undocumented)
				assert.strictEqual(
					dialog.$element.find( '.ve-ui-mwTransclusionOutlineParameterWidget' ).length, 3
				);
				dialog.close();
			} );

		} ).fail( () => {
			assert.true( false );
			finishTest();
		} );

		windowInstance.closed.then( () => {
			assert.true( true );
			finishTest();
		} );
	} );
}
