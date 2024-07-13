"use strict";
exports.id = 538;
exports.ids = [538];
exports.modules = {

/***/ 8538:
/***/ ((__unused_webpack_module, __webpack_exports__, __webpack_require__) => {

/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   Z: () => (/* binding */ ItemFrame),
/* harmony export */   v: () => (/* binding */ rarityVariantStyles)
/* harmony export */ });
/* harmony import */ var react_jsx_runtime__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(6786);
/* harmony import */ var react_jsx_runtime__WEBPACK_IMPORTED_MODULE_0___default = /*#__PURE__*/__webpack_require__.n(react_jsx_runtime__WEBPACK_IMPORTED_MODULE_0__);
/* harmony import */ var _utils_classNames__WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__(4228);
/* harmony import */ var next_image__WEBPACK_IMPORTED_MODULE_2__ = __webpack_require__(8421);
/* harmony import */ var next_image__WEBPACK_IMPORTED_MODULE_2___default = /*#__PURE__*/__webpack_require__.n(next_image__WEBPACK_IMPORTED_MODULE_2__);



const rarityVariantStyles = {
    Default: "border-[#272043] bg-default-frame",
    Legendary: "border-[#DCC529] bg-legendary-frame",
    Epic: "border-[#761B29] bg-epic-frame",
    Rare: "border-[#2F60A8] bg-rare-frame",
    Uncommon: "border-[#38896B] bg-uncommon-frame",
    Common: "border-[#6D737A] bg-common-frame"
};
function ItemFrame({ item, rarity, children, size = "md", className, tier, quantity = 1, customPath, ...props }) {
    return /*#__PURE__*/ (0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_0__.jsxs)("div", {
        className: (0,_utils_classNames__WEBPACK_IMPORTED_MODULE_1__.cn)("relative flex items-center justify-center rounded-lg border-2", rarityVariantStyles[rarity], {
            "h-10 w-10 sm:h-14 sm:w-14": size === "sm"
        }, {
            "h-14 w-14 sm:h-20 sm:w-20": size === "md"
        }, {
            "h-20 w-20 sm:h-28 sm:w-28": size === "lg"
        }, className),
        ...props,
        children: [
            /*#__PURE__*/ react_jsx_runtime__WEBPACK_IMPORTED_MODULE_0__.jsx((next_image__WEBPACK_IMPORTED_MODULE_2___default()), {
                src: customPath ?? `/items/${item}.webp`,
                alt: "",
                width: sizeToPX[size],
                height: sizeToPX[size],
                className: (0,_utils_classNames__WEBPACK_IMPORTED_MODULE_1__.cn)("object-contain", {
                    "h-6 w-6 sm:h-9 sm:w-9": size === "sm"
                }, {
                    "h-9 w-9 sm:h-14 sm:w-14": size === "md"
                }, {
                    "h-9 w-9 sm:h-14 sm:w-14": size === "lg"
                })
            }),
            tier && /*#__PURE__*/ react_jsx_runtime__WEBPACK_IMPORTED_MODULE_0__.jsx("p", {
                className: "absolute bottom-2 left-2 w-max bg-transparent bg-gradient-to-b from-[#eaecee] to-[#c1c5c7] bg-clip-text text-start font-ptSerif text-base font-bold !leading-none text-transparent drop-shadow-[0_0_1px_#000] sm:text-2xl",
                children: tier === 4 ? "IV" : "I".repeat(tier)
            }),
            quantity > 1 && /*#__PURE__*/ react_jsx_runtime__WEBPACK_IMPORTED_MODULE_0__.jsx("p", {
                className: "absolute bottom-2 right-2 text-end text-sm font-normal leading-none text-neutral-200 drop-shadow-[0_0_2px_#000] sm:text-base",
                children: quantity
            })
        ]
    });
}
const sizeToPX = {
    sm: 36,
    md: 56,
    lg: 56
};


/***/ })

};
;