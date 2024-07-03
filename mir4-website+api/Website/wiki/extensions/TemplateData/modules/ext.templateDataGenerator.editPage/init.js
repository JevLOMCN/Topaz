/*!
 * TemplateData Generator edit page init
 *
 * @author Moriel Schottlender
 * @author Ed Sanders
 */
// We're on an edit page, but we don't know what namespace yet (e.g. when loaded by VisualEditorPluginModules)
if (
	mw.config.get( 'wgCanonicalNamespace' ) === 'Template' &&
	mw.config.get( 'wgPageContentModel' ) === 'wikitext'
) {
	mw.loader.using( 'ext.templateDataGenerator.editTemplatePage' );
}
