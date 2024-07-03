module.exports = {
	simple: function () {
		var done = arguments[ arguments.length - 1 ];

		seleniumUtils.runDiffTest(
			'<h2>Lorem ipsum</h2>' +
			'<p>Lorem ipsum dolor sit <b>amet</b>, consectetur adipiscing elit.</p>',
			'<h2>Lorem ipsum</h2>' +
			'<p>Lorem ipsum dolor sit <i>amet</i>, consectetur adipiscing elit.</p>',
			done
		);
	},
	moveAndChange: function () {
		var done = arguments[ arguments.length - 1 ];

		seleniumUtils.runDiffTest(
			'<h2>Lorem ipsum</h2>' +
			'<p>Lorem ipsum dolor sit amet, consectetur adipiscing elit.</p>' +
			'<p>Maecenas fringilla turpis et nunc bibendum mattis.</p>',
			'<h2>Lorem ipsum</h2>' +
			'<p>Maecenas fringilla turpis et nunc bibendum mattis.</p>' +
			'<p>Lorem ipsum dolor sit amat, consectetur adipiscing elit.</p>',
			done
		);
	},
	linkChange: function () {
		var done = arguments[ arguments.length - 1 ];

		seleniumUtils.runDiffTest(
			'<h2>Lorem ipsum</h2>' +
			'<p><a rel="mw:WikiLink" href="./Lipsum">Lorem ipsum</a> dolor sit amet, consectetur adipiscing elit.</p>',
			'<h2>Lorem ipsum</h2>' +
			'<p><a rel="mw:WikiLink" href="./Lorem ipsum">Lorem ipsum</a> dolor sit amet, consectetur adipiscing elit.</p>',
			done
		);
	},
	listChange: function () {
		var done = arguments[ arguments.length - 1 ];

		seleniumUtils.runDiffTest(
			'<ul><li>Lorem</li><li>ipsum</li><li>dolor</li><li>sit</li><li>amet</li></ul>',
			'<ul><li>Lorem</li><li>ipsum</li><li>sit</li><li>amat</li></ul>',
			done
		);
	}
};
