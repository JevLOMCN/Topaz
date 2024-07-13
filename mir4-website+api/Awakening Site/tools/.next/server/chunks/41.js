"use strict";
exports.id = 41;
exports.ids = [41,994];
exports.modules = {

/***/ 4885:
/***/ ((__unused_webpack_module, __webpack_exports__, __webpack_require__) => {

/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   Z: () => (/* binding */ Input)
/* harmony export */ });
/* harmony import */ var react_jsx_runtime__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(6786);
/* harmony import */ var react_jsx_runtime__WEBPACK_IMPORTED_MODULE_0___default = /*#__PURE__*/__webpack_require__.n(react_jsx_runtime__WEBPACK_IMPORTED_MODULE_0__);
/* harmony import */ var _utils_classNames__WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__(4228);


function Input({ label, className, suffix, error, isLoading = false, ...props }) {
    return /*#__PURE__*/ (0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_0__.jsxs)("label", {
        className: (0,_utils_classNames__WEBPACK_IMPORTED_MODULE_1__.cn)("flex w-full flex-col items-center gap-1 text-center text-sm font-medium text-primary-100", className),
        children: [
            label,
            /*#__PURE__*/ (0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_0__.jsxs)("div", {
                className: (0,_utils_classNames__WEBPACK_IMPORTED_MODULE_1__.cn)("flex w-full items-center justify-center gap-1.5 rounded-md bg-primary-600 px-1.5 py-1 font-bold text-white transition-colors duration-300 sm:px-3 sm:py-2", {
                    "!border-red-400 bg-red-400/30": error
                }),
                children: [
                    /*#__PURE__*/ react_jsx_runtime__WEBPACK_IMPORTED_MODULE_0__.jsx("input", {
                        className: (0,_utils_classNames__WEBPACK_IMPORTED_MODULE_1__.cn)("flex w-full appearance-none items-center justify-center bg-transparent text-center text-sm font-normal outline-none transition-colors duration-300 selection:bg-primary-800 placeholder:text-neutral-200/70 sm:text-base", {
                            "text-red-200": error
                        }, {
                            "mx-auto w-1/2 animate-pulse rounded-full bg-primary-500/50 text-transparent placeholder:text-transparent": isLoading
                        }),
                        ...props
                    }),
                    suffix && /*#__PURE__*/ react_jsx_runtime__WEBPACK_IMPORTED_MODULE_0__.jsx("div", {
                        className: "shrink-0 text-sm font-bold text-white sm:text-base",
                        children: suffix
                    })
                ]
            })
        ]
    });
}


/***/ }),

/***/ 4994:
/***/ ((__unused_webpack_module, __webpack_exports__, __webpack_require__) => {

__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   $$typeof: () => (/* binding */ $$typeof),
/* harmony export */   __esModule: () => (/* binding */ __esModule),
/* harmony export */   "default": () => (__WEBPACK_DEFAULT_EXPORT__)
/* harmony export */ });
/* harmony import */ var next_dist_build_webpack_loaders_next_flight_loader_module_proxy__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(1313);

const proxy = (0,next_dist_build_webpack_loaders_next_flight_loader_module_proxy__WEBPACK_IMPORTED_MODULE_0__.createProxy)(String.raw`C:\Users\WAGNER\Desktop\Mir4Tools\src\components\global\MainBackground.tsx`)

// Accessing the __esModule property and exporting $$typeof are required here.
// The __esModule getter forces the proxy target to create the default export
// and the $$typeof value is for rendering logic to determine if the module
// is a client boundary.
const { __esModule, $$typeof } = proxy;
const __default__ = proxy.default;


/* harmony default export */ const __WEBPACK_DEFAULT_EXPORT__ = (__default__);

/***/ })

};
;