( function () {
	'use strict';
	const extensionAssetsPath = mw.config.get( 'wgExtensionAssetsPath' );
	window.MathJax = {
		loader: {
			// see https://docs.mathjax.org/en/latest/input/mathml.html
			load: [ '[mml]/mml3' ],
			// see https://docs.mathjax.org/en/latest/options/startup/loader.html
			paths: {
				mathjax: extensionAssetsPath + '/Math/modules/mathjax/es5'
			}
		}
	};
}() );
