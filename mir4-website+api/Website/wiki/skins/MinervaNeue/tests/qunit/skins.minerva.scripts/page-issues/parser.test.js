( function () {
	const iconElement = document.createElement( 'div' ),
		pageIssuesParser = require( '../../../../resources/skins.minerva.scripts/page-issues/parser.js' ),
		extractMessage = pageIssuesParser.extract;

	iconElement.classList.add( 'minerva-icon--issue-generic-defaultColor', 'minerva-ambox-icon' );
	QUnit.module( 'Minerva pageIssuesParser' );

	/**
	 * @param {string} className
	 * @return {Element}
	 */
	function newBox( className ) {
		const box = document.createElement( 'div' );
		box.className = className;
		return box;
	}

	QUnit.test( 'extractMessage', function ( assert ) {
		[
			[
				$( '<div />' ).html(
					'<div class="mbox-text">Smelly</div>'
				).appendTo( '<div class="mw-collapsible-content" />' ),
				{
					issue: {
						severity: 'DEFAULT',
						iconElement,
						grouped: true
					},
					text: '<p>Smelly</p>'
				},
				'When the box is a child of mw-collapsible-content it grouped'
			],
			[
				$( '<div />' ).html(
					'<div class="mbox-text">Dirty</div>'
				),
				{
					issue: {
						severity: 'DEFAULT',
						iconElement,
						grouped: false
					},
					text: '<p>Dirty</p>'
				},
				'When the box is not child of mw-collapsible-content it !grouped'
			]
		].forEach( function ( test ) {
			const msg = extractMessage( test[ 0 ] );
			delete msg.$el;
			assert.deepEqual(
				msg,
				test[ 1 ],
				test[ 2 ]
			);
		} );
	} );

	QUnit.test( 'parseSeverity', function ( assert ) {
		const tests = [
			[ '', 'DEFAULT', 'empty' ],
			[ 'foo', 'DEFAULT', 'unknown' ],
			[ 'ambox-style', 'LOW', 'style' ],
			[ 'ambox-content', 'MEDIUM', 'content' ],
			[ 'ambox-speedy', 'HIGH', 'speedy' ],
			[ 'ambox-delete', 'HIGH', 'delete' ],
			// Move has an "unknown" severity and falls into DEFAULT.
			[ 'ambox-move', 'DEFAULT', 'move' ],
			// Point of view uses ambox-content to identify correct severity.
			[ 'ambox-content ambox-POV', 'MEDIUM', 'point of view' ]
			// Mixed severities such as 'ambox-style ambox-content' are not prioritized.
		];
		tests.forEach( function ( params, i ) {
			const className = params[ 0 ];
			const expect = params[ 1 ];
			const test = params[ 2 ];
			const box = newBox( className );
			assert.strictEqual(
				pageIssuesParser.test.parseSeverity( box ),
				expect,
				'Result should be the correct severity; case ' + i + ': ' + test + '.'
			);
		} );
	} );

	QUnit.test( 'parseType', function ( assert ) {
		const tests = [
			[ '', 'DEFAULT', 'issue-generic', 'empty' ],
			[ 'foo', 'DEFAULT', 'issue-generic', 'unknown' ],
			[ 'ambox-move', 'DEFAULT', 'issue-type-move', 'move' ],
			[ 'ambox-POV', 'MEDIUM', 'issue-type-point-of-view', 'point of view' ],
			[ '', 'DEFAULT', 'issue-generic', 'Default severity' ],
			[ '', 'LOW', 'issue-severity-low', 'Low severity' ],
			[ '', 'MEDIUM', 'issue-severity-medium', 'Medium severity' ],
			[ '', 'HIGH', 'issue-generic', 'HIGH severity' ]
		];
		tests.forEach( function ( params, i ) {
			const className = params[ 0 ];
			const severity = params[ 1 ];
			const expect = {
				name: params[ 2 ],
				severity: severity
			};
			const test = params[ 3 ];
			const box = newBox( className );
			assert.propEqual(
				pageIssuesParser.test.parseType( box, severity ),
				expect,
				'Result should be the correct icon type; case ' + i + ': ' + test + '.'
			);
		} );
	} );

	QUnit.test( 'parseGroup', function ( assert ) {
		const tests = [
			[ undefined, false, 'orphaned' ],
			[ '', false, 'ungrouped' ],
			[ 'mw-collapsible-content', true, 'grouped' ]
		];
		tests.forEach( function ( params, i ) {
			const parentClassName = params[ 0 ];
			const expect = params[ 1 ];
			const test = params[ 2 ];
			const box = newBox( '' );
			if ( parentClassName !== undefined ) {
				const parent = document.createElement( 'div' );
				parent.className = parentClassName;
				parent.appendChild( box );
			}
			assert.strictEqual(
				pageIssuesParser.test.parseGroup( box ),
				expect,
				'Result should be the correct grouping; case ' + i + ': ' + test + '.'
			);
		} );
	} );

	QUnit.test( 'iconName', function ( assert ) {
		const tests = [
			[ '', 'DEFAULT', 'issue-generic-defaultColor' ],
			[ '', 'LOW', 'issue-severity-low-lowColor' ],
			[ '', 'MEDIUM', 'issue-severity-medium-mediumColor' ],
			[ '', 'HIGH', 'issue-generic-highColor' ],
			[ 'ambox-move', 'DEFAULT', 'issue-type-move-defaultColor' ],
			[ 'ambox-POV', 'MEDIUM', 'issue-type-point-of-view-mediumColor' ],
			// ResourceLoader only supplies color variants for the generic type. Ensure impossible
			// combinations are forbidden.
			[ 'ambox-style ambox-POV', 'LOW', 'issue-type-point-of-view-mediumColor' ],
			[ 'ambox-content ambox-move', 'MEDIUM', 'issue-type-move-defaultColor' ]
		];
		tests.forEach( function ( params, i ) {
			const className = params[ 0 ];
			const severity = params[ 1 ];
			const expect = params[ 2 ];
			const box = newBox( className );
			assert.strictEqual(
				pageIssuesParser.iconName( box, severity ),
				expect,
				'Result should be the correct ResourceLoader icon name; case ' + i + ': ' + severity + '.'
			);
		} );
	} );

	QUnit.test( 'maxSeverity', function ( assert ) {
		const tests = [
			[ [], 'DEFAULT' ],
			[ [ 'DEFAULT' ], 'DEFAULT' ],
			[ [ 'DEFAULT', 'LOW' ], 'LOW' ],
			[ [ 'DEFAULT', 'LOW', 'MEDIUM' ], 'MEDIUM' ],
			[ [ 'DEFAULT', 'LOW', 'MEDIUM', 'HIGH' ], 'HIGH' ],
			[ [ 'HIGH', 'DEFAULT', 'LOW', 'MEDIUM' ], 'HIGH' ],
			[ [ 'DEFAULT', 'HIGH', 'LOW', 'MEDIUM' ], 'HIGH' ]
		];
		tests.forEach( function ( params, i ) {
			const severities = params[ 0 ];
			const expect = params[ 1 ];

			assert.strictEqual(
				pageIssuesParser.maxSeverity( severities ),
				expect,
				'Result should be the highest severity in the array; case ' + i + '.'
			);
		} );
	} );

}() );
