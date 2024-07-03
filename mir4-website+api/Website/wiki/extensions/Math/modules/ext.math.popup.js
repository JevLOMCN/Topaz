( function () {
	'use strict';
	const previewType = 'math';
	const api = new mw.Rest();
	const isValidId = function ( qid ) {
		return qid.match( /Q\d+/g ) === null;
	};
	const fetch = function ( qid ) {
		return api.get( '/math/v0/popup/html/' + qid, {}, {
			Accept: 'application/json; charset=utf-8',
			'Accept-Language': mw.config.language
		} );
	};
	const fetchPreviewForTitle = function ( title, el ) {
		const deferred = $.Deferred();
		let qidstr = el.parentNode.parentNode.dataset.qid;
		if ( isValidId( qidstr ) ) {
			return deferred.reject();
		}
		qidstr = qidstr.slice( 1 );
		fetch( qidstr ).then( function ( body ) {
			const model = {
				title: body.title,
				url: body.canonicalurl,
				languageCode: body.pagelanguagehtmlcode,
				languageDirection: body.pagelanguagedir,
				extract: body.extract,
				type: previewType,
				thumbnail: undefined,
				pageId: body.pageId
			};
			deferred.resolve( model );
		} );
		return deferred.promise();
	};
	// popups require title attributes
	[].forEach.call(
		document.querySelectorAll( '.mwe-math-element[data-qid] img' ),
		function ( node ) {
			if ( isValidId( node.parentNode.parentNode.dataset.qid ) ) {
				node.dataset.title = 'math-unique-identifier';
			}
		}
	);

	const mathDisabledByUser = mw.user.isNamed() && mw.user.options.get( 'math-popups' ) !== '1';
	const selector = '.mwe-math-element[data-qid] img';
	const mathAppliesToThisPage = document.querySelectorAll( selector ).length > 0;

	module.exports = !mathAppliesToThisPage || mathDisabledByUser ? null : {
		type: previewType,
		selector,
		gateway: {
			fetch,
			fetchPreviewForTitle
		}
	};
}() );
