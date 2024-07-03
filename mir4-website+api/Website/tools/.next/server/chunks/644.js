"use strict";
exports.id = 644;
exports.ids = [644];
exports.modules = {

/***/ 3644:
/***/ ((__unused_webpack___webpack_module__, __webpack_exports__, __webpack_require__) => {

/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   $j: () => (/* binding */ $905f4ae918aab1aa$export$848c9b7ead0df967),
/* harmony export */   Dx: () => (/* binding */ $905f4ae918aab1aa$export$f99233281efd08a0),
/* harmony export */   VY: () => (/* binding */ $905f4ae918aab1aa$export$7c6e2c02157bb7d2),
/* harmony export */   aU: () => (/* binding */ $905f4ae918aab1aa$export$e19cd5f9376f8cee),
/* harmony export */   aV: () => (/* binding */ $905f4ae918aab1aa$export$c6fdb837b070b4ff),
/* harmony export */   dk: () => (/* binding */ $905f4ae918aab1aa$export$393edc798c47379d),
/* harmony export */   fC: () => (/* binding */ $905f4ae918aab1aa$export$be92b6f5f03c0fe9),
/* harmony export */   h_: () => (/* binding */ $905f4ae918aab1aa$export$602eac185826482c),
/* harmony export */   xz: () => (/* binding */ $905f4ae918aab1aa$export$41fb9f06171c75f4)
/* harmony export */ });
/* unused harmony exports createAlertDialogScope, AlertDialog, AlertDialogTrigger, AlertDialogPortal, AlertDialogOverlay, AlertDialogContent, AlertDialogAction, AlertDialogCancel, AlertDialogTitle, AlertDialogDescription */
/* harmony import */ var _babel_runtime_helpers_esm_extends__WEBPACK_IMPORTED_MODULE_3__ = __webpack_require__(9885);
/* harmony import */ var react__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(8038);
/* harmony import */ var _radix_ui_react_context__WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__(5866);
/* harmony import */ var _radix_ui_react_compose_refs__WEBPACK_IMPORTED_MODULE_4__ = __webpack_require__(755);
/* harmony import */ var _radix_ui_react_dialog__WEBPACK_IMPORTED_MODULE_2__ = __webpack_require__(5506);
/* harmony import */ var _radix_ui_primitive__WEBPACK_IMPORTED_MODULE_5__ = __webpack_require__(4958);
/* harmony import */ var _radix_ui_react_slot__WEBPACK_IMPORTED_MODULE_6__ = __webpack_require__(9818);
















/* -------------------------------------------------------------------------------------------------
 * AlertDialog
 * -----------------------------------------------------------------------------------------------*/ const $905f4ae918aab1aa$var$ROOT_NAME = 'AlertDialog';
const [$905f4ae918aab1aa$var$createAlertDialogContext, $905f4ae918aab1aa$export$b8891880751c2c5b] = (0,_radix_ui_react_context__WEBPACK_IMPORTED_MODULE_1__/* .createContextScope */ .b)($905f4ae918aab1aa$var$ROOT_NAME, [
    _radix_ui_react_dialog__WEBPACK_IMPORTED_MODULE_2__/* .createDialogScope */ .p8
]);
const $905f4ae918aab1aa$var$useDialogScope = (0,_radix_ui_react_dialog__WEBPACK_IMPORTED_MODULE_2__/* .createDialogScope */ .p8)();
const $905f4ae918aab1aa$export$de466dd8317b0b75 = (props)=>{
    const { __scopeAlertDialog: __scopeAlertDialog , ...alertDialogProps } = props;
    const dialogScope = $905f4ae918aab1aa$var$useDialogScope(__scopeAlertDialog);
    return /*#__PURE__*/ (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)(_radix_ui_react_dialog__WEBPACK_IMPORTED_MODULE_2__/* .Root */ .fC, (0,_babel_runtime_helpers_esm_extends__WEBPACK_IMPORTED_MODULE_3__/* ["default"] */ .Z)({}, dialogScope, alertDialogProps, {
        modal: true
    }));
};
/*#__PURE__*/ Object.assign($905f4ae918aab1aa$export$de466dd8317b0b75, {
    displayName: $905f4ae918aab1aa$var$ROOT_NAME
});
/* -------------------------------------------------------------------------------------------------
 * AlertDialogTrigger
 * -----------------------------------------------------------------------------------------------*/ const $905f4ae918aab1aa$var$TRIGGER_NAME = 'AlertDialogTrigger';
const $905f4ae918aab1aa$export$6edd7a623ef0f40b = /*#__PURE__*/ (0,react__WEBPACK_IMPORTED_MODULE_0__.forwardRef)((props, forwardedRef)=>{
    const { __scopeAlertDialog: __scopeAlertDialog , ...triggerProps } = props;
    const dialogScope = $905f4ae918aab1aa$var$useDialogScope(__scopeAlertDialog);
    return /*#__PURE__*/ (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)(_radix_ui_react_dialog__WEBPACK_IMPORTED_MODULE_2__/* .Trigger */ .xz, (0,_babel_runtime_helpers_esm_extends__WEBPACK_IMPORTED_MODULE_3__/* ["default"] */ .Z)({}, dialogScope, triggerProps, {
        ref: forwardedRef
    }));
});
/*#__PURE__*/ Object.assign($905f4ae918aab1aa$export$6edd7a623ef0f40b, {
    displayName: $905f4ae918aab1aa$var$TRIGGER_NAME
});
/* -------------------------------------------------------------------------------------------------
 * AlertDialogPortal
 * -----------------------------------------------------------------------------------------------*/ const $905f4ae918aab1aa$var$PORTAL_NAME = 'AlertDialogPortal';
const $905f4ae918aab1aa$export$660f2bfdb986706c = (props)=>{
    const { __scopeAlertDialog: __scopeAlertDialog , ...portalProps } = props;
    const dialogScope = $905f4ae918aab1aa$var$useDialogScope(__scopeAlertDialog);
    return /*#__PURE__*/ (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)(_radix_ui_react_dialog__WEBPACK_IMPORTED_MODULE_2__/* .Portal */ .h_, (0,_babel_runtime_helpers_esm_extends__WEBPACK_IMPORTED_MODULE_3__/* ["default"] */ .Z)({}, dialogScope, portalProps));
};
/*#__PURE__*/ Object.assign($905f4ae918aab1aa$export$660f2bfdb986706c, {
    displayName: $905f4ae918aab1aa$var$PORTAL_NAME
});
/* -------------------------------------------------------------------------------------------------
 * AlertDialogOverlay
 * -----------------------------------------------------------------------------------------------*/ const $905f4ae918aab1aa$var$OVERLAY_NAME = 'AlertDialogOverlay';
const $905f4ae918aab1aa$export$a707a4895ce23256 = /*#__PURE__*/ (0,react__WEBPACK_IMPORTED_MODULE_0__.forwardRef)((props, forwardedRef)=>{
    const { __scopeAlertDialog: __scopeAlertDialog , ...overlayProps } = props;
    const dialogScope = $905f4ae918aab1aa$var$useDialogScope(__scopeAlertDialog);
    return /*#__PURE__*/ (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)(_radix_ui_react_dialog__WEBPACK_IMPORTED_MODULE_2__/* .Overlay */ .aV, (0,_babel_runtime_helpers_esm_extends__WEBPACK_IMPORTED_MODULE_3__/* ["default"] */ .Z)({}, dialogScope, overlayProps, {
        ref: forwardedRef
    }));
});
/*#__PURE__*/ Object.assign($905f4ae918aab1aa$export$a707a4895ce23256, {
    displayName: $905f4ae918aab1aa$var$OVERLAY_NAME
});
/* -------------------------------------------------------------------------------------------------
 * AlertDialogContent
 * -----------------------------------------------------------------------------------------------*/ const $905f4ae918aab1aa$var$CONTENT_NAME = 'AlertDialogContent';
const [$905f4ae918aab1aa$var$AlertDialogContentProvider, $905f4ae918aab1aa$var$useAlertDialogContentContext] = $905f4ae918aab1aa$var$createAlertDialogContext($905f4ae918aab1aa$var$CONTENT_NAME);
const $905f4ae918aab1aa$export$94e6af45f0af4efd = /*#__PURE__*/ (0,react__WEBPACK_IMPORTED_MODULE_0__.forwardRef)((props, forwardedRef)=>{
    const { __scopeAlertDialog: __scopeAlertDialog , children: children , ...contentProps } = props;
    const dialogScope = $905f4ae918aab1aa$var$useDialogScope(__scopeAlertDialog);
    const contentRef = (0,react__WEBPACK_IMPORTED_MODULE_0__.useRef)(null);
    const composedRefs = (0,_radix_ui_react_compose_refs__WEBPACK_IMPORTED_MODULE_4__/* .useComposedRefs */ .e)(forwardedRef, contentRef);
    const cancelRef = (0,react__WEBPACK_IMPORTED_MODULE_0__.useRef)(null);
    return /*#__PURE__*/ (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)(_radix_ui_react_dialog__WEBPACK_IMPORTED_MODULE_2__/* .WarningProvider */ .jm, {
        contentName: $905f4ae918aab1aa$var$CONTENT_NAME,
        titleName: $905f4ae918aab1aa$var$TITLE_NAME,
        docsSlug: "alert-dialog"
    }, /*#__PURE__*/ (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)($905f4ae918aab1aa$var$AlertDialogContentProvider, {
        scope: __scopeAlertDialog,
        cancelRef: cancelRef
    }, /*#__PURE__*/ (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)(_radix_ui_react_dialog__WEBPACK_IMPORTED_MODULE_2__/* .Content */ .VY, (0,_babel_runtime_helpers_esm_extends__WEBPACK_IMPORTED_MODULE_3__/* ["default"] */ .Z)({
        role: "alertdialog"
    }, dialogScope, contentProps, {
        ref: composedRefs,
        onOpenAutoFocus: (0,_radix_ui_primitive__WEBPACK_IMPORTED_MODULE_5__/* .composeEventHandlers */ .M)(contentProps.onOpenAutoFocus, (event)=>{
            var _cancelRef$current;
            event.preventDefault();
            (_cancelRef$current = cancelRef.current) === null || _cancelRef$current === void 0 || _cancelRef$current.focus({
                preventScroll: true
            });
        }),
        onPointerDownOutside: (event)=>event.preventDefault()
        ,
        onInteractOutside: (event)=>event.preventDefault()
    }), /*#__PURE__*/ (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)(_radix_ui_react_slot__WEBPACK_IMPORTED_MODULE_6__/* .Slottable */ .A4, null, children), false)));
});
/*#__PURE__*/ Object.assign($905f4ae918aab1aa$export$94e6af45f0af4efd, {
    displayName: $905f4ae918aab1aa$var$CONTENT_NAME
});
/* -------------------------------------------------------------------------------------------------
 * AlertDialogTitle
 * -----------------------------------------------------------------------------------------------*/ const $905f4ae918aab1aa$var$TITLE_NAME = 'AlertDialogTitle';
const $905f4ae918aab1aa$export$225e0da62d314b7 = /*#__PURE__*/ (0,react__WEBPACK_IMPORTED_MODULE_0__.forwardRef)((props, forwardedRef)=>{
    const { __scopeAlertDialog: __scopeAlertDialog , ...titleProps } = props;
    const dialogScope = $905f4ae918aab1aa$var$useDialogScope(__scopeAlertDialog);
    return /*#__PURE__*/ (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)(_radix_ui_react_dialog__WEBPACK_IMPORTED_MODULE_2__/* .Title */ .Dx, (0,_babel_runtime_helpers_esm_extends__WEBPACK_IMPORTED_MODULE_3__/* ["default"] */ .Z)({}, dialogScope, titleProps, {
        ref: forwardedRef
    }));
});
/*#__PURE__*/ Object.assign($905f4ae918aab1aa$export$225e0da62d314b7, {
    displayName: $905f4ae918aab1aa$var$TITLE_NAME
});
/* -------------------------------------------------------------------------------------------------
 * AlertDialogDescription
 * -----------------------------------------------------------------------------------------------*/ const $905f4ae918aab1aa$var$DESCRIPTION_NAME = 'AlertDialogDescription';
const $905f4ae918aab1aa$export$a23b55cde55ad9a5 = /*#__PURE__*/ (0,react__WEBPACK_IMPORTED_MODULE_0__.forwardRef)((props, forwardedRef)=>{
    const { __scopeAlertDialog: __scopeAlertDialog , ...descriptionProps } = props;
    const dialogScope = $905f4ae918aab1aa$var$useDialogScope(__scopeAlertDialog);
    return /*#__PURE__*/ (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)(_radix_ui_react_dialog__WEBPACK_IMPORTED_MODULE_2__/* .Description */ .dk, (0,_babel_runtime_helpers_esm_extends__WEBPACK_IMPORTED_MODULE_3__/* ["default"] */ .Z)({}, dialogScope, descriptionProps, {
        ref: forwardedRef
    }));
});
/*#__PURE__*/ Object.assign($905f4ae918aab1aa$export$a23b55cde55ad9a5, {
    displayName: $905f4ae918aab1aa$var$DESCRIPTION_NAME
});
/* -------------------------------------------------------------------------------------------------
 * AlertDialogAction
 * -----------------------------------------------------------------------------------------------*/ const $905f4ae918aab1aa$var$ACTION_NAME = 'AlertDialogAction';
const $905f4ae918aab1aa$export$b454f818c58ee85d = /*#__PURE__*/ (0,react__WEBPACK_IMPORTED_MODULE_0__.forwardRef)((props, forwardedRef)=>{
    const { __scopeAlertDialog: __scopeAlertDialog , ...actionProps } = props;
    const dialogScope = $905f4ae918aab1aa$var$useDialogScope(__scopeAlertDialog);
    return /*#__PURE__*/ (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)(_radix_ui_react_dialog__WEBPACK_IMPORTED_MODULE_2__/* .Close */ .x8, (0,_babel_runtime_helpers_esm_extends__WEBPACK_IMPORTED_MODULE_3__/* ["default"] */ .Z)({}, dialogScope, actionProps, {
        ref: forwardedRef
    }));
});
/*#__PURE__*/ Object.assign($905f4ae918aab1aa$export$b454f818c58ee85d, {
    displayName: $905f4ae918aab1aa$var$ACTION_NAME
});
/* -------------------------------------------------------------------------------------------------
 * AlertDialogCancel
 * -----------------------------------------------------------------------------------------------*/ const $905f4ae918aab1aa$var$CANCEL_NAME = 'AlertDialogCancel';
const $905f4ae918aab1aa$export$2f67a923571aaea0 = /*#__PURE__*/ (0,react__WEBPACK_IMPORTED_MODULE_0__.forwardRef)((props, forwardedRef)=>{
    const { __scopeAlertDialog: __scopeAlertDialog , ...cancelProps } = props;
    const { cancelRef: cancelRef  } = $905f4ae918aab1aa$var$useAlertDialogContentContext($905f4ae918aab1aa$var$CANCEL_NAME, __scopeAlertDialog);
    const dialogScope = $905f4ae918aab1aa$var$useDialogScope(__scopeAlertDialog);
    const ref = (0,_radix_ui_react_compose_refs__WEBPACK_IMPORTED_MODULE_4__/* .useComposedRefs */ .e)(forwardedRef, cancelRef);
    return /*#__PURE__*/ (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)(_radix_ui_react_dialog__WEBPACK_IMPORTED_MODULE_2__/* .Close */ .x8, (0,_babel_runtime_helpers_esm_extends__WEBPACK_IMPORTED_MODULE_3__/* ["default"] */ .Z)({}, dialogScope, cancelProps, {
        ref: ref
    }));
});
/*#__PURE__*/ Object.assign($905f4ae918aab1aa$export$2f67a923571aaea0, {
    displayName: $905f4ae918aab1aa$var$CANCEL_NAME
});
/* ---------------------------------------------------------------------------------------------- */ const $905f4ae918aab1aa$var$DescriptionWarning = ({ contentRef: contentRef  })=>{
    const MESSAGE = `\`${$905f4ae918aab1aa$var$CONTENT_NAME}\` requires a description for the component to be accessible for screen reader users.

You can add a description to the \`${$905f4ae918aab1aa$var$CONTENT_NAME}\` by passing a \`${$905f4ae918aab1aa$var$DESCRIPTION_NAME}\` component as a child, which also benefits sighted users by adding visible context to the dialog.

Alternatively, you can use your own component as a description by assigning it an \`id\` and passing the same value to the \`aria-describedby\` prop in \`${$905f4ae918aab1aa$var$CONTENT_NAME}\`. If the description is confusing or duplicative for sighted users, you can use the \`@radix-ui/react-visually-hidden\` primitive as a wrapper around your description component.

For more information, see https://radix-ui.com/primitives/docs/components/alert-dialog`;
    $4k4D0$useEffect(()=>{
        var _contentRef$current;
        const hasDescription = document.getElementById((_contentRef$current = contentRef.current) === null || _contentRef$current === void 0 ? void 0 : _contentRef$current.getAttribute('aria-describedby'));
        if (!hasDescription) console.warn(MESSAGE);
    }, [
        MESSAGE,
        contentRef
    ]);
    return null;
};
const $905f4ae918aab1aa$export$be92b6f5f03c0fe9 = $905f4ae918aab1aa$export$de466dd8317b0b75;
const $905f4ae918aab1aa$export$41fb9f06171c75f4 = $905f4ae918aab1aa$export$6edd7a623ef0f40b;
const $905f4ae918aab1aa$export$602eac185826482c = $905f4ae918aab1aa$export$660f2bfdb986706c;
const $905f4ae918aab1aa$export$c6fdb837b070b4ff = $905f4ae918aab1aa$export$a707a4895ce23256;
const $905f4ae918aab1aa$export$7c6e2c02157bb7d2 = $905f4ae918aab1aa$export$94e6af45f0af4efd;
const $905f4ae918aab1aa$export$e19cd5f9376f8cee = $905f4ae918aab1aa$export$b454f818c58ee85d;
const $905f4ae918aab1aa$export$848c9b7ead0df967 = $905f4ae918aab1aa$export$2f67a923571aaea0;
const $905f4ae918aab1aa$export$f99233281efd08a0 = $905f4ae918aab1aa$export$225e0da62d314b7;
const $905f4ae918aab1aa$export$393edc798c47379d = $905f4ae918aab1aa$export$a23b55cde55ad9a5;





//# sourceMappingURL=index.mjs.map


/***/ })

};
;