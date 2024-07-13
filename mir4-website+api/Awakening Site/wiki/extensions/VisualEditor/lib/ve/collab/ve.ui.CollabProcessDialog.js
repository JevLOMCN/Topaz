/**
 * CollabProcessDialog - Dialog for hosting or joining a collab session
 *
 * @param {Object} [config] Configuration options
 */
ve.ui.CollabProcessDialog = function VeUiCollabProcessDialog( config ) {
	ve.ui.CollabProcessDialog.super.call( this, config );
};

OO.inheritClass( ve.ui.CollabProcessDialog, OO.ui.ProcessDialog );

ve.ui.CollabProcessDialog.static.name = null;

ve.ui.CollabProcessDialog.static.title = OO.ui.deferMsg( 'visualeditor-collab-dialog-title' );

ve.ui.CollabProcessDialog.static.imageUri = 'data:image/svg+xml;charset=utf-8;base64,PHN2ZyB4bWxucz0iaHR0cDovL3d3dy53My5vcmcvMjAwMC9zdmciIHdpZHRoPSIyNTAiIGhlaWdodD0iMTUwIiBzdHlsZT0ic3Ryb2tlOiNjY2NjY2M7c3Ryb2tlLXdpZHRoOjY7c3Ryb2tlLWxpbmVjYXA6cm91bmQiPg0KIDxyZWN0IHN0eWxlPSJmaWxsOndoaXRlO3N0cm9rZTp3aGl0ZSIgd2lkdGg9IjI1MCIgaGVpZ2h0PSIxNTAiIC8+DQogPHBhdGggZD0iTSAzMCwzMEggNzciIC8+DQogPHBhdGggZD0iTSA5MCwzMEggMTM0IiAvPg0KIDxwYXRoIGQ9Ik0gMTQ3LDMwSCAyMjAiIC8+DQogPHBhdGggZD0iTSAzMCw1MEggMTY4IiBzdHlsZT0ic3Ryb2tlOiNmZmNiMzMiIC8+DQogPHBhdGggZD0iTSAxODAsNTBIIDIyMCIgLz4NCiA8cGF0aCBkPSJNIDMwLDcwSCAxMTUiIHN0eWxlPSJzdHJva2U6I2U4NTdjOCIgLz4NCiA8cGF0aCBkPSJNIDEyOCw3MEggMjIwIiAvPg0KIDxwYXRoIGQ9Ik0gMzAsOTBIIDQ3IiAvPg0KIDxwYXRoIGQ9Ik0gNjAsOTBIIDEzOCIgLz4NCiA8cGF0aCBkPSJNIDE1MSw5MEggMjIwIiBzdHlsZT0ic3Ryb2tlOiMwMGFmODkiIC8+DQogPHBhdGggZD0iTSAzMCwxMTBIIDIyMCIgLz4NCiA8cGF0aCBkPSJNIDMwLDEzMEggOTgiIC8+DQogPHBhdGggZD0iTSAxMTEsMTMwSCAyMjAiIC8+DQo8L3N2Zz4=';

ve.ui.CollabProcessDialog.static.actions = [
	{
		label: OO.ui.deferMsg( 'visualeditor-dialog-action-done' ),
		flags: [ 'safe', 'close' ]
	}
];
ve.ui.CollabProcessDialog.prototype.initialize = function () {
	ve.ui.CollabProcessDialog.super.prototype.initialize.apply( this, arguments );

	this.stack = new OO.ui.StackLayout( {
		expanded: false
	} );

	this.initPanel = new OO.ui.PanelLayout( {
		padded: true,
		expanded: false
	} );
	this.initButton = new OO.ui.ButtonWidget( {
		flags: [ 'primary', 'progressive' ]
	} );
	this.initButton.$element[ 0 ].style.display = 'block';
	this.initButton.$element[ 0 ].firstElementChild.style.minWidth = '100%';

	this.$summary = $( '<p>' );

	this.initPanel.$element.append(
		$( '<img>' ).prop( 'src', ve.ui.CollabProcessDialog.static.imageUri )
			.css( { display: 'block', margin: '2em auto' } ),
		this.$summary.css( { 'font-weight': 'bold' } ),
		$( '<p>' ).text( ve.msg( 'visualeditor-collab-dialog-sharing' ) ),
		$( '<p>' ).text( ve.msg( 'visualeditor-collab-dialog-sessionend' ) ),
		$( '<p>' ).text( ve.msg( 'visualeditor-collab-dialog-privacy' ) ),
		$( '<div>' ).append( this.initButton.$element )
	);

	this.stack.addItems( [
		this.initPanel
	] );

	this.$body.append( this.stack.$element );
	this.initButton.connect( this, { click: 'onButtonClick' } );
};

ve.ui.CollabProcessDialog.prototype.getBodyHeight = function () {
	return this.stack.$element.outerHeight( true );
};

ve.ui.CollabProcessDialog.prototype.onButtonClick = function () {
	this.close( 'accept' );
};

ve.ui.CollabProcessDialog.prototype.getReadyProcess = function ( data ) {
	return ve.ui.CollabProcessDialog.super.prototype.getReadyProcess.call( this, data )
		.next( function () {
			switch ( this.stack.getCurrentItem() ) {
				case this.initPanel:
					this.initButton.focus();
					break;
				case this.copyPanel:
					this.copyTextLayout.button.focus();
					break;
			}
		}, this );
};

/**
 * HostCollabProcessDialog - Dialog for hosting a new collab session
 *
 * @param {Object} [config] Configuration options
 */
ve.ui.HostCollabProcessDialog = function VeUiHostCollabProcessDialog( config ) {
	ve.ui.HostCollabProcessDialog.super.call( this, config );
};

OO.inheritClass( ve.ui.HostCollabProcessDialog, ve.ui.CollabProcessDialog );

ve.ui.HostCollabProcessDialog.static.name = 'hostCollabDialog';

ve.ui.HostCollabProcessDialog.prototype.initialize = function () {
	ve.ui.HostCollabProcessDialog.super.prototype.initialize.apply( this, arguments );

	this.initButton.setLabel( ve.msg( 'visualeditor-collab-hostbutton-label' ) );
	this.initButton.setIcon( 'userAdd' );
	this.$summary.text( ve.msg( 'visualeditor-collab-dialog-summary-host' ) );

	this.copyPanel = new OO.ui.PanelLayout( {
		padded: true,
		expanded: false
	} );
	this.afterCopyButton = new OO.ui.ButtonWidget( {
		flags: [ 'primary', 'progressive' ],
		label: ve.msg( 'visualeditor-dialog-action-done' )
	} );
	this.copyTextLayout = new OO.ui.CopyTextLayout();
	this.copyPanel.$element.append(
		new OO.ui.FieldsetLayout( {
			label: ve.msg( 'visualeditor-collab-copy-title' ),
			items: [
				this.copyTextLayout,
				new OO.ui.FieldLayout( this.afterCopyButton )
			]
		} ).$element
	);
	this.stack.addItems( [ this.copyPanel ] );

	this.afterCopyButton.on( 'click', this.close.bind( this ) );
};

ve.ui.HostCollabProcessDialog.prototype.onButtonClick = function () {
	this.initButton.setDisabled( true );
	this.pushPending();

	var dialog = this;
	ve.collab.initPeerServer();
	var collabUrl = new URL( location.href );
	ve.collab.peerServer.peer.on( 'open', function ( newId ) {
		collabUrl.searchParams.set( 'collabSession', newId );
		dialog.copyTextLayout.textInput.setValue( collabUrl );
		dialog.stack.setItem( dialog.copyPanel );
		dialog.updateSize();
		dialog.copyTextLayout.button.focus();
		dialog.popPending();
	} );
};

ve.ui.windowFactory.register( ve.ui.HostCollabProcessDialog );

/**
 * JoinCollabProcessDialog - Dialog for joining an existing collab session
 *
 * @param {Object} [config] Configuration options
 */
ve.ui.JoinCollabProcessDialog = function VeUiJoinCollabProcessDialog( config ) {
	ve.ui.JoinCollabProcessDialog.super.call( this, config );
};

OO.inheritClass( ve.ui.JoinCollabProcessDialog, ve.ui.CollabProcessDialog );

ve.ui.JoinCollabProcessDialog.static.name = 'joinCollabDialog';

ve.ui.JoinCollabProcessDialog.prototype.initialize = function () {
	ve.ui.JoinCollabProcessDialog.super.prototype.initialize.apply( this, arguments );

	this.initButton.setLabel( ve.msg( 'visualeditor-collab-joinbutton-label' ) );
	this.initButton.setIcon( 'userGroup' );
	this.$summary.text( ve.msg( 'visualeditor-collab-dialog-summary-join' ) );
};

ve.ui.windowFactory.register( ve.ui.JoinCollabProcessDialog );
