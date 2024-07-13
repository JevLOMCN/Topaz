/*
 * This file is part of the MediaWiki extension MultimediaViewer.
 *
 * MultimediaViewer is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 2 of the License, or
 * (at your option) any later version.
 *
 * MultimediaViewer is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with MultimediaViewer.  If not, see <http://www.gnu.org/licenses/>.
 */

const Canvas = require( './mmv.ui.canvas.js' );
const CanvasButtons = require( './mmv.ui.canvasButtons.js' );
const Description = require( './mmv.ui.description.js' );
const Dialog = require( './mmv.ui.dialog.js' );
const DownloadDialog = require( './mmv.ui.download.dialog.js' );
const UiElement = require( './mmv.ui.js' );
const MetadataPanel = require( './mmv.ui.metadataPanel.js' );
const MetadataPanelScroller = require( './mmv.ui.metadataPanelScroller.js' );
const Permission = require( './mmv.ui.permission.js' );
const ProgressBar = require( './mmv.ui.progressBar.js' );
const ReuseDialog = require( './mmv.ui.reuse.dialog.js' );
const StripeButtons = require( './mmv.ui.stripeButtons.js' );
const TruncatableTextField = require( './mmv.ui.truncatableTextField.js' );
const OptionsDialog = require( './mmv.ui.viewingOptions.js' );

module.exports = {
	Canvas,
	CanvasButtons,
	Description,
	Dialog,
	DownloadDialog,
	UiElement,
	MetadataPanel,
	MetadataPanelScroller,
	Permission,
	ProgressBar,
	ReuseDialog,
	StripeButtons,
	TruncatableTextField,
	OptionsDialog
};
