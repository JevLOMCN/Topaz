"use strict";
exports.id = 769;
exports.ids = [769];
exports.modules = {

/***/ 4769:
/***/ ((module, exports, __webpack_require__) => {


Object.defineProperty(exports, "__esModule", ({
    value: true
}));
Object.defineProperty(exports, "default", ({
    enumerable: true,
    get: function() {
        return dynamic;
    }
}));
const _interop_require_default = __webpack_require__(1550);
const _react = /*#__PURE__*/ _interop_require_default._(__webpack_require__(7887));
const _loadable = /*#__PURE__*/ _interop_require_default._(__webpack_require__(7934));
// Normalize loader to return the module as form { default: Component } for `React.lazy`.
// Also for backward compatible since next/dynamic allows to resolve a component directly with loader
// Client component reference proxy need to be converted to a module.
function convertModule(mod) {
    return {
        default: (mod == null ? void 0 : mod.default) || mod
    };
}
function dynamic(dynamicOptions, options) {
    const loadableFn = _loadable.default;
    const loadableOptions = {
        // A loading component is not required, so we default it
        loading: (param)=>{
            let { error, isLoading, pastDelay } = param;
            if (!pastDelay) return null;
            if (false) {}
            return null;
        }
    };
    if (typeof dynamicOptions === "function") {
        loadableOptions.loader = dynamicOptions;
    }
    Object.assign(loadableOptions, options);
    const loaderFn = loadableOptions.loader;
    const loader = ()=>loaderFn != null ? loaderFn().then(convertModule) : Promise.resolve(convertModule(()=>null));
    return loadableFn({
        ...loadableOptions,
        loader: loader
    });
}
if ((typeof exports.default === "function" || typeof exports.default === "object" && exports.default !== null) && typeof exports.default.__esModule === "undefined") {
    Object.defineProperty(exports.default, "__esModule", {
        value: true
    });
    Object.assign(exports.default, exports);
    module.exports = exports.default;
} //# sourceMappingURL=app-dynamic.js.map


/***/ }),

/***/ 1154:
/***/ ((module, __unused_webpack_exports, __webpack_require__) => {

/* __next_internal_client_entry_do_not_use__  cjs */ 
const { createProxy } = __webpack_require__(1313);
module.exports = createProxy("C:\\Users\\WAGNER\\Desktop\\Mir4Tools\\node_modules\\next\\dist\\shared\\lib\\lazy-dynamic\\dynamic-no-ssr.js");
 //# sourceMappingURL=dynamic-no-ssr.js.map


/***/ }),

/***/ 7934:
/***/ ((__unused_webpack_module, exports, __webpack_require__) => {


Object.defineProperty(exports, "__esModule", ({
    value: true
}));
Object.defineProperty(exports, "default", ({
    enumerable: true,
    get: function() {
        return _default;
    }
}));
const _interop_require_default = __webpack_require__(1550);
const _react = /*#__PURE__*/ _interop_require_default._(__webpack_require__(7887));
const _dynamicnossr = __webpack_require__(1154);
function Loadable(options) {
    const opts = Object.assign({
        loader: null,
        loading: null,
        ssr: true
    }, options);
    opts.lazy = /*#__PURE__*/ _react.default.lazy(opts.loader);
    function LoadableComponent(props) {
        const Loading = opts.loading;
        const fallbackElement = /*#__PURE__*/ _react.default.createElement(Loading, {
            isLoading: true,
            pastDelay: true,
            error: null
        });
        const Wrap = opts.ssr ? _react.default.Fragment : _dynamicnossr.NoSSR;
        const Lazy = opts.lazy;
        return /*#__PURE__*/ _react.default.createElement(_react.default.Suspense, {
            fallback: fallbackElement
        }, /*#__PURE__*/ _react.default.createElement(Wrap, null, /*#__PURE__*/ _react.default.createElement(Lazy, props)));
    }
    LoadableComponent.displayName = "LoadableComponent";
    return LoadableComponent;
}
const _default = Loadable; //# sourceMappingURL=loadable.js.map


/***/ }),

/***/ 1550:
/***/ ((__unused_webpack_module, exports) => {


exports._ = exports._interop_require_default = _interop_require_default;
function _interop_require_default(obj) {
    return obj && obj.__esModule ? obj : {
        default: obj
    };
}


/***/ })

};
;