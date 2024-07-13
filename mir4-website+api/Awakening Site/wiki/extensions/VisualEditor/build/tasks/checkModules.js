'use strict';

const fs = require( 'fs' );
const path = require( 'path' );

const extensionJsonPath = '../../extension.json';
const modulesJsonPath = '../../lib/ve/build/modules.json';

const ignored = [
	'node_modules/',
	'lib/',
	'dist/',
	'src/ve.track.js', // Re-implemented in MW
	'src/ce/ve.ce.debug.js',
	'tests/ve.qunit.local.js',
	// Standalone / Demos
	'demos/',
	'src/init/sa/',
	'tests/init/ve.init.sa.Platform.test.js',
	'src/ui/dialogs/ve.ui.DiffDialog.js',
	// Rebaser
	'rebaser/',
	'collab/',
	'src/ve.FakePeer.js',
	// TODO: Put these is a folder
	'tests/ve.FakePeer.test.js',
	'tests/dm/ve.dm.TransactionSquasher.test.js',
	'tests/dm/ve.dm.RebaseServer.test.js',
	'tests/dm/ve.dm.FakeMongo.js',
	'tests/dm/ve.dm.FakeSocket.js',
	'tests/dm/ve.dm.DocumentStore.test.js',
	'tests/dm/ve.dm.TransportServer.test.js'

];

function readJson( filePath ) {
	try {
		// eslint-disable-next-line security/detect-non-literal-fs-filename
		const rawData = fs.readFileSync( filePath );
		return JSON.parse( rawData );
	} catch ( error ) {
		console.error( `Error reading file ${ filePath }:`, error );
		return null;
	}
}

function addFilesToSet( files, set, basePath = '' ) {
	if ( Array.isArray( files ) ) {
		files.forEach( ( file ) => set.add( path.join( basePath, file.file || file ) ) );
	} else if ( typeof files === 'string' ) {
		set.add( path.join( basePath, files ) );
	}
}

function isIgnored( filePath ) {
	return ignored.some( ( ignorePath ) => {
		const fullIgnorePath = path.join( 'lib/ve', ignorePath );
		return filePath === fullIgnorePath || filePath.startsWith( fullIgnorePath );
	} );
}

function addModulesToSet( modules, set, basePath = '' ) {
	Object.values( modules ).forEach( ( module ) => {
		[
			'scripts',
			'debugScripts',
			'styles'
		].forEach( ( property ) => {
			if ( module[ property ] ) {
				addFilesToSet( module[ property ], set, basePath );
			}
		} );
		if ( module.skinStyles ) {
			Object.values( module.skinStyles ).forEach( ( files ) => {
				addFilesToSet( files, set, basePath );
			} );
		}
	} );
}

function checkFiles() {
	const extensionJson = readJson( extensionJsonPath );
	const modulesJson = readJson( modulesJsonPath );

	if ( !extensionJson || !modulesJson ) {
		return;
	}

	const extensionFiles = new Set();
	addModulesToSet( extensionJson.ResourceModules, extensionFiles );
	addModulesToSet( { QUnitTestModule: extensionJson.QUnitTestModule }, extensionFiles );

	const modulesFiles = new Set();
	addModulesToSet( modulesJson, modulesFiles, 'lib/ve' );

	const missingFiles = Array.from( modulesFiles ).filter( ( file ) => {
		return !extensionFiles.has( file ) && !isIgnored( file );
	} );

	if ( missingFiles.length ) {
		console.log( missingFiles.length + ' missing file(s):' );
		console.log( missingFiles.join( '\n' ) );
	} else {
		console.log( 'No missing files.' );
	}
}

checkFiles();
