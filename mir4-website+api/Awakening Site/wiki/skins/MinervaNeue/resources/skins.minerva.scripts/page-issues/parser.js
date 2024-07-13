( function () {
	/**
	 * @typedef PageIssue
	 * @property {string} severity A SEVERITY_LEVEL key.
	 * @property {boolean} grouped True if part of a group of multiple issues, false if singular.
	 * @property {Icon} icon
	 */
	/**
	 * @typedef {Object} IssueSummary
	 * @property {PageIssue} issue
	 * @property {jQuery} $el where the issue was extracted from
	 * @property {string} iconString a string representation of icon.
	 *  This is kept for template compatibility (our views do not yet support composition).
	 * @property {string} text HTML string.
	 */

	// Icons are matching the type selector below use a TYPE_* icon. When unmatched, the icon is
	// chosen by severity. Their color is always determined by severity, too.
	const ICON_NAME = {
		// Generic severity icons.
		SEVERITY: {
			DEFAULT: 'issue-generic',
			LOW: 'issue-severity-low',
			MEDIUM: 'issue-severity-medium',
			HIGH: 'issue-generic'
		},

		// Icons customized by type.
		TYPE: {
			MOVE: 'issue-type-move',
			POINT_OF_VIEW: 'issue-type-point-of-view'
		}
	};
	const ICON_COLOR = {
		DEFAULT: 'defaultColor',
		LOW: 'lowColor',
		MEDIUM: 'mediumColor',
		HIGH: 'highColor'
	};
	// How severities order and compare from least to greatest. For the multiple issues
	// template, severity should be considered the maximum of all its contained issues.
	const SEVERITY_LEVEL = {
		DEFAULT: 0,
		LOW: 1,
		MEDIUM: 2,
		HIGH: 3
	};
	// Match the template's color CSS selector to a severity level concept. Derived via the
	// Ambox templates and sub-templates for the top five wikis and tested on page issues
	// inventory:
	// - https://people.wikimedia.org/~jdrewniak/page_issues_inventory
	// - https://en.wikipedia.org/wiki/Template:Ambox
	// - https://es.wikipedia.org/wiki/Plantilla:Metaplantilla_de_avisos
	// - https://ja.wikipedia.org/wiki/Template:Ambox
	// - https://ru.wikipedia.org/wiki/Шаблон:Ambox
	// - https://it.wikipedia.org/wiki/Template:Avviso
	// Severity is the class associated with the color. The ResourceLoader config mimics the
	// idea by using severity for color variants. Severity is determined independently of icons.
	// These selectors should be migrated to their templates.
	const SEVERITY_REGEX = {
		// recommended (T206177), en, it
		LOW: /mobile-issue-severity-low|ambox-style|avviso-stile/,
		// recommended, en, it
		MEDIUM: /mobile-issue-severity-medium|ambox-content|avviso-contenuto/,
		// recommended, en, en, es / ru, it
		HIGH: /mobile-issue-severity-high|ambox-speedy|ambox-delete|ambox-serious|avviso-importante/
		// ..And everything else that doesn't match should be considered DEFAULT.
	};
	// As above but used to identify specific templates requiring icon customization.
	const TYPE_REGEX = {
		// recommended (opt-in) / en, es / ru, it (long term only recommended should be used)
		MOVE: /mobile-issue-move|ambox-converted|ambox-move|ambox-merge|avviso-struttura/,
		POINT_OF_VIEW: new RegExp( [
			// recommended (opt-in)
			'mobile-issue-pov',
			// FIXME: en classes: plan to remove these provided can get adoption of recommended
			'ambox-Advert',
			'ambox-autobiography',
			'ambox-believerpov',
			'ambox-COI',
			'ambox-coverage',
			'ambox-criticism',
			'ambox-fanpov',
			'ambox-fringe-theories',
			'ambox-geographical-imbalance',
			'ambox-globalize',
			'ambox-npov-language',
			'ambox-POV',
			'ambox-pseudo',
			'ambox-systemic-bias',
			'ambox-unbalanced',
			'ambox-usgovtpov'
		].join( '|' ) )
		// ..And everything else that doesn't match is mapped to a "SEVERITY" type.
	};
	const GROUPED_PARENT_REGEX = /mw-collapsible-content/;
	// Variants supported by specific types. The "severity icon" supports all severities but the
	// type icons only support one each by ResourceLoader.
	const TYPE_SEVERITY = {
		MOVE: 'DEFAULT',
		POINT_OF_VIEW: 'MEDIUM'
	};

	/**
	 * @param {Element} box
	 * @return {string} An SEVERITY_SELECTOR key.
	 */
	function parseSeverity( box ) {
		let severity;
		const identified = Object.keys( SEVERITY_REGEX ).some( function ( key ) {
			const regex = SEVERITY_REGEX[ key ];
			severity = key;
			return regex.test( box.className );
		} );
		return identified ? severity : 'DEFAULT';
	}

	/**
	 * @param {Element} box
	 * @param {string} severity An SEVERITY_LEVEL key.
	 * @return {{name: string, severity: string}} An ICON_NAME.
	 */
	function parseType( box, severity ) {
		let identifiedType;
		const identified = Object.keys( TYPE_REGEX ).some( function ( type ) {
			const regex = TYPE_REGEX[ type ];
			identifiedType = type;
			return regex.test( box.className );
		} );
		return {
			name: identified ? ICON_NAME.TYPE[ identifiedType ] : ICON_NAME.SEVERITY[ severity ],
			severity: identified ? TYPE_SEVERITY[ identifiedType ] : severity
		};
	}

	/**
	 * @param {Element} box
	 * @return {boolean} True if part of a group of multiple issues, false if singular.
	 */
	function parseGroup( box ) {
		return !!box.parentNode && GROUPED_PARENT_REGEX.test( box.parentNode.className );
	}

	/**
	 * @param {Element} box
	 * @param {string} severity An SEVERITY_LEVEL key.
	 * @return {string} A severity or type ISSUE_ICON.
	 */
	function iconName( box, severity ) {
		const nameSeverity = parseType( box, severity );
		// The icon with color variant as expected by ResourceLoader,
		// {iconName}-{severityColorVariant}.
		return nameSeverity.name + '-' + ICON_COLOR[ nameSeverity.severity ];
	}

	/**
	 * @param {string[]} severityLevels an array of SEVERITY_KEY values.
	 * @return {string} The greatest SEVERITY_LEVEL key.
	 */
	function maxSeverity( severityLevels ) {
		return severityLevels.reduce( function ( max, severity ) {
			return SEVERITY_LEVEL[ max ] > SEVERITY_LEVEL[ severity ] ? max : severity;
		}, 'DEFAULT' );
	}

	/**
	 * @param {Element} box
	 * @return {PageIssue}
	 */
	function parse( box ) {
		const severity = parseSeverity( box );
		const iconElement = document.createElement( 'div' );
		iconElement.classList.add( `minerva-icon--${ iconName( box, severity ) }`, 'minerva-ambox-icon' );
		return {
			severity,
			grouped: parseGroup( box ),
			iconElement
		};
	}

	/**
	 * Extract a summary message from a cleanup template generated element that is
	 * friendly for mobile display.
	 *
	 * @param {Object} $box element to extract the message from
	 * @return {IssueSummary}
	 */
	function extract( $box ) {
		const SELECTOR = '.mbox-text, .ambox-text';
		const $container = $( '<div>' );

		$box.find( SELECTOR ).each( function () {
			const $this = $( this );
			// Clean up talk page boxes
			$this.find( 'table, .noprint' ).remove();
			const contents = $this.html();

			if ( contents ) {
				$( '<p>' ).html( contents ).appendTo( $container );
			}
		} );

		const pageIssue = parse( $box.get( 0 ) );

		return {
			issue: pageIssue,
			$el: $box,
			text: $container.html()
		};
	}

	module.exports = {
		extract,
		parse,
		maxSeverity,
		iconName,
		test: {
			parseSeverity,
			parseType,
			parseGroup
		}
	};

}() );
