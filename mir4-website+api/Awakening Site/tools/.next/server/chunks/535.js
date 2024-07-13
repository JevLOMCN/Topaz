"use strict";
exports.id = 535;
exports.ids = [535];
exports.modules = {

/***/ 4535:
/***/ ((__unused_webpack___webpack_module__, __webpack_exports__, __webpack_require__) => {

/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   VY: () => (/* binding */ $cb5cc270b50c6fcd$export$7c6e2c02157bb7d2),
/* harmony export */   fC: () => (/* binding */ $cb5cc270b50c6fcd$export$be92b6f5f03c0fe9),
/* harmony export */   h_: () => (/* binding */ $cb5cc270b50c6fcd$export$602eac185826482c),
/* harmony export */   xz: () => (/* binding */ $cb5cc270b50c6fcd$export$41fb9f06171c75f4)
/* harmony export */ });
/* unused harmony exports createPopoverScope, Popover, PopoverAnchor, PopoverTrigger, PopoverPortal, PopoverContent, PopoverClose, PopoverArrow, Anchor, Close, Arrow */
/* harmony import */ var _babel_runtime_helpers_esm_extends__WEBPACK_IMPORTED_MODULE_5__ = __webpack_require__(9885);
/* harmony import */ var react__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(8038);
/* harmony import */ var _radix_ui_primitive__WEBPACK_IMPORTED_MODULE_8__ = __webpack_require__(4958);
/* harmony import */ var _radix_ui_react_compose_refs__WEBPACK_IMPORTED_MODULE_6__ = __webpack_require__(755);
/* harmony import */ var _radix_ui_react_context__WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__(5866);
/* harmony import */ var _radix_ui_react_dismissable_layer__WEBPACK_IMPORTED_MODULE_16__ = __webpack_require__(8221);
/* harmony import */ var _radix_ui_react_focus_guards__WEBPACK_IMPORTED_MODULE_14__ = __webpack_require__(573);
/* harmony import */ var _radix_ui_react_focus_scope__WEBPACK_IMPORTED_MODULE_15__ = __webpack_require__(9586);
/* harmony import */ var _radix_ui_react_id__WEBPACK_IMPORTED_MODULE_4__ = __webpack_require__(1042);
/* harmony import */ var _radix_ui_react_popper__WEBPACK_IMPORTED_MODULE_2__ = __webpack_require__(8242);
/* harmony import */ var _radix_ui_react_portal__WEBPACK_IMPORTED_MODULE_10__ = __webpack_require__(3214);
/* harmony import */ var _radix_ui_react_presence__WEBPACK_IMPORTED_MODULE_9__ = __webpack_require__(4261);
/* harmony import */ var _radix_ui_react_primitive__WEBPACK_IMPORTED_MODULE_7__ = __webpack_require__(5585);
/* harmony import */ var _radix_ui_react_slot__WEBPACK_IMPORTED_MODULE_13__ = __webpack_require__(9818);
/* harmony import */ var _radix_ui_react_use_controllable_state__WEBPACK_IMPORTED_MODULE_3__ = __webpack_require__(4352);
/* harmony import */ var aria_hidden__WEBPACK_IMPORTED_MODULE_11__ = __webpack_require__(6860);
/* harmony import */ var react_remove_scroll__WEBPACK_IMPORTED_MODULE_12__ = __webpack_require__(8162);




































/* -------------------------------------------------------------------------------------------------
 * Popover
 * -----------------------------------------------------------------------------------------------*/ const $cb5cc270b50c6fcd$var$POPOVER_NAME = 'Popover';
const [$cb5cc270b50c6fcd$var$createPopoverContext, $cb5cc270b50c6fcd$export$c8393c9e73286932] = (0,_radix_ui_react_context__WEBPACK_IMPORTED_MODULE_1__/* .createContextScope */ .b)($cb5cc270b50c6fcd$var$POPOVER_NAME, [
    _radix_ui_react_popper__WEBPACK_IMPORTED_MODULE_2__/* .createPopperScope */ .D7
]);
const $cb5cc270b50c6fcd$var$usePopperScope = (0,_radix_ui_react_popper__WEBPACK_IMPORTED_MODULE_2__/* .createPopperScope */ .D7)();
const [$cb5cc270b50c6fcd$var$PopoverProvider, $cb5cc270b50c6fcd$var$usePopoverContext] = $cb5cc270b50c6fcd$var$createPopoverContext($cb5cc270b50c6fcd$var$POPOVER_NAME);
const $cb5cc270b50c6fcd$export$5b6b19405a83ff9d = (props)=>{
    const { __scopePopover: __scopePopover , children: children , open: openProp , defaultOpen: defaultOpen , onOpenChange: onOpenChange , modal: modal = false  } = props;
    const popperScope = $cb5cc270b50c6fcd$var$usePopperScope(__scopePopover);
    const triggerRef = (0,react__WEBPACK_IMPORTED_MODULE_0__.useRef)(null);
    const [hasCustomAnchor, setHasCustomAnchor] = (0,react__WEBPACK_IMPORTED_MODULE_0__.useState)(false);
    const [open = false, setOpen] = (0,_radix_ui_react_use_controllable_state__WEBPACK_IMPORTED_MODULE_3__/* .useControllableState */ .T)({
        prop: openProp,
        defaultProp: defaultOpen,
        onChange: onOpenChange
    });
    return /*#__PURE__*/ (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)(_radix_ui_react_popper__WEBPACK_IMPORTED_MODULE_2__/* .Root */ .fC, popperScope, /*#__PURE__*/ (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)($cb5cc270b50c6fcd$var$PopoverProvider, {
        scope: __scopePopover,
        contentId: (0,_radix_ui_react_id__WEBPACK_IMPORTED_MODULE_4__/* .useId */ .M)(),
        triggerRef: triggerRef,
        open: open,
        onOpenChange: setOpen,
        onOpenToggle: (0,react__WEBPACK_IMPORTED_MODULE_0__.useCallback)(()=>setOpen((prevOpen)=>!prevOpen
            )
        , [
            setOpen
        ]),
        hasCustomAnchor: hasCustomAnchor,
        onCustomAnchorAdd: (0,react__WEBPACK_IMPORTED_MODULE_0__.useCallback)(()=>setHasCustomAnchor(true)
        , []),
        onCustomAnchorRemove: (0,react__WEBPACK_IMPORTED_MODULE_0__.useCallback)(()=>setHasCustomAnchor(false)
        , []),
        modal: modal
    }, children));
};
/*#__PURE__*/ Object.assign($cb5cc270b50c6fcd$export$5b6b19405a83ff9d, {
    displayName: $cb5cc270b50c6fcd$var$POPOVER_NAME
});
/* -------------------------------------------------------------------------------------------------
 * PopoverAnchor
 * -----------------------------------------------------------------------------------------------*/ const $cb5cc270b50c6fcd$var$ANCHOR_NAME = 'PopoverAnchor';
const $cb5cc270b50c6fcd$export$96e5381f42521a79 = /*#__PURE__*/ (0,react__WEBPACK_IMPORTED_MODULE_0__.forwardRef)((props, forwardedRef)=>{
    const { __scopePopover: __scopePopover , ...anchorProps } = props;
    const context = $cb5cc270b50c6fcd$var$usePopoverContext($cb5cc270b50c6fcd$var$ANCHOR_NAME, __scopePopover);
    const popperScope = $cb5cc270b50c6fcd$var$usePopperScope(__scopePopover);
    const { onCustomAnchorAdd: onCustomAnchorAdd , onCustomAnchorRemove: onCustomAnchorRemove  } = context;
    (0,react__WEBPACK_IMPORTED_MODULE_0__.useEffect)(()=>{
        onCustomAnchorAdd();
        return ()=>onCustomAnchorRemove()
        ;
    }, [
        onCustomAnchorAdd,
        onCustomAnchorRemove
    ]);
    return /*#__PURE__*/ (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)(_radix_ui_react_popper__WEBPACK_IMPORTED_MODULE_2__/* .Anchor */ .ee, (0,_babel_runtime_helpers_esm_extends__WEBPACK_IMPORTED_MODULE_5__/* ["default"] */ .Z)({}, popperScope, anchorProps, {
        ref: forwardedRef
    }));
});
/*#__PURE__*/ Object.assign($cb5cc270b50c6fcd$export$96e5381f42521a79, {
    displayName: $cb5cc270b50c6fcd$var$ANCHOR_NAME
});
/* -------------------------------------------------------------------------------------------------
 * PopoverTrigger
 * -----------------------------------------------------------------------------------------------*/ const $cb5cc270b50c6fcd$var$TRIGGER_NAME = 'PopoverTrigger';
const $cb5cc270b50c6fcd$export$7dacb05d26466c3 = /*#__PURE__*/ (0,react__WEBPACK_IMPORTED_MODULE_0__.forwardRef)((props, forwardedRef)=>{
    const { __scopePopover: __scopePopover , ...triggerProps } = props;
    const context = $cb5cc270b50c6fcd$var$usePopoverContext($cb5cc270b50c6fcd$var$TRIGGER_NAME, __scopePopover);
    const popperScope = $cb5cc270b50c6fcd$var$usePopperScope(__scopePopover);
    const composedTriggerRef = (0,_radix_ui_react_compose_refs__WEBPACK_IMPORTED_MODULE_6__/* .useComposedRefs */ .e)(forwardedRef, context.triggerRef);
    const trigger = /*#__PURE__*/ (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)(_radix_ui_react_primitive__WEBPACK_IMPORTED_MODULE_7__/* .Primitive */ .WV.button, (0,_babel_runtime_helpers_esm_extends__WEBPACK_IMPORTED_MODULE_5__/* ["default"] */ .Z)({
        type: "button",
        "aria-haspopup": "dialog",
        "aria-expanded": context.open,
        "aria-controls": context.contentId,
        "data-state": $cb5cc270b50c6fcd$var$getState(context.open)
    }, triggerProps, {
        ref: composedTriggerRef,
        onClick: (0,_radix_ui_primitive__WEBPACK_IMPORTED_MODULE_8__/* .composeEventHandlers */ .M)(props.onClick, context.onOpenToggle)
    }));
    return context.hasCustomAnchor ? trigger : /*#__PURE__*/ (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)(_radix_ui_react_popper__WEBPACK_IMPORTED_MODULE_2__/* .Anchor */ .ee, (0,_babel_runtime_helpers_esm_extends__WEBPACK_IMPORTED_MODULE_5__/* ["default"] */ .Z)({
        asChild: true
    }, popperScope), trigger);
});
/*#__PURE__*/ Object.assign($cb5cc270b50c6fcd$export$7dacb05d26466c3, {
    displayName: $cb5cc270b50c6fcd$var$TRIGGER_NAME
});
/* -------------------------------------------------------------------------------------------------
 * PopoverPortal
 * -----------------------------------------------------------------------------------------------*/ const $cb5cc270b50c6fcd$var$PORTAL_NAME = 'PopoverPortal';
const [$cb5cc270b50c6fcd$var$PortalProvider, $cb5cc270b50c6fcd$var$usePortalContext] = $cb5cc270b50c6fcd$var$createPopoverContext($cb5cc270b50c6fcd$var$PORTAL_NAME, {
    forceMount: undefined
});
const $cb5cc270b50c6fcd$export$dd679ffb4362d2d4 = (props)=>{
    const { __scopePopover: __scopePopover , forceMount: forceMount , children: children , container: container  } = props;
    const context = $cb5cc270b50c6fcd$var$usePopoverContext($cb5cc270b50c6fcd$var$PORTAL_NAME, __scopePopover);
    return /*#__PURE__*/ (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)($cb5cc270b50c6fcd$var$PortalProvider, {
        scope: __scopePopover,
        forceMount: forceMount
    }, /*#__PURE__*/ (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)(_radix_ui_react_presence__WEBPACK_IMPORTED_MODULE_9__/* .Presence */ .z, {
        present: forceMount || context.open
    }, /*#__PURE__*/ (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)(_radix_ui_react_portal__WEBPACK_IMPORTED_MODULE_10__/* .Portal */ .h, {
        asChild: true,
        container: container
    }, children)));
};
/*#__PURE__*/ Object.assign($cb5cc270b50c6fcd$export$dd679ffb4362d2d4, {
    displayName: $cb5cc270b50c6fcd$var$PORTAL_NAME
});
/* -------------------------------------------------------------------------------------------------
 * PopoverContent
 * -----------------------------------------------------------------------------------------------*/ const $cb5cc270b50c6fcd$var$CONTENT_NAME = 'PopoverContent';
const $cb5cc270b50c6fcd$export$d7e1f420b25549ff = /*#__PURE__*/ (0,react__WEBPACK_IMPORTED_MODULE_0__.forwardRef)((props, forwardedRef)=>{
    const portalContext = $cb5cc270b50c6fcd$var$usePortalContext($cb5cc270b50c6fcd$var$CONTENT_NAME, props.__scopePopover);
    const { forceMount: forceMount = portalContext.forceMount , ...contentProps } = props;
    const context = $cb5cc270b50c6fcd$var$usePopoverContext($cb5cc270b50c6fcd$var$CONTENT_NAME, props.__scopePopover);
    return /*#__PURE__*/ (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)(_radix_ui_react_presence__WEBPACK_IMPORTED_MODULE_9__/* .Presence */ .z, {
        present: forceMount || context.open
    }, context.modal ? /*#__PURE__*/ (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)($cb5cc270b50c6fcd$var$PopoverContentModal, (0,_babel_runtime_helpers_esm_extends__WEBPACK_IMPORTED_MODULE_5__/* ["default"] */ .Z)({}, contentProps, {
        ref: forwardedRef
    })) : /*#__PURE__*/ (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)($cb5cc270b50c6fcd$var$PopoverContentNonModal, (0,_babel_runtime_helpers_esm_extends__WEBPACK_IMPORTED_MODULE_5__/* ["default"] */ .Z)({}, contentProps, {
        ref: forwardedRef
    })));
});
/*#__PURE__*/ Object.assign($cb5cc270b50c6fcd$export$d7e1f420b25549ff, {
    displayName: $cb5cc270b50c6fcd$var$CONTENT_NAME
});
/* -----------------------------------------------------------------------------------------------*/ const $cb5cc270b50c6fcd$var$PopoverContentModal = /*#__PURE__*/ (0,react__WEBPACK_IMPORTED_MODULE_0__.forwardRef)((props, forwardedRef)=>{
    const context = $cb5cc270b50c6fcd$var$usePopoverContext($cb5cc270b50c6fcd$var$CONTENT_NAME, props.__scopePopover);
    const contentRef = (0,react__WEBPACK_IMPORTED_MODULE_0__.useRef)(null);
    const composedRefs = (0,_radix_ui_react_compose_refs__WEBPACK_IMPORTED_MODULE_6__/* .useComposedRefs */ .e)(forwardedRef, contentRef);
    const isRightClickOutsideRef = (0,react__WEBPACK_IMPORTED_MODULE_0__.useRef)(false); // aria-hide everything except the content (better supported equivalent to setting aria-modal)
    (0,react__WEBPACK_IMPORTED_MODULE_0__.useEffect)(()=>{
        const content = contentRef.current;
        if (content) return (0,aria_hidden__WEBPACK_IMPORTED_MODULE_11__/* .hideOthers */ .Ry)(content);
    }, []);
    return /*#__PURE__*/ (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)(react_remove_scroll__WEBPACK_IMPORTED_MODULE_12__/* .RemoveScroll */ .f, {
        as: _radix_ui_react_slot__WEBPACK_IMPORTED_MODULE_13__/* .Slot */ .g7,
        allowPinchZoom: true
    }, /*#__PURE__*/ (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)($cb5cc270b50c6fcd$var$PopoverContentImpl, (0,_babel_runtime_helpers_esm_extends__WEBPACK_IMPORTED_MODULE_5__/* ["default"] */ .Z)({}, props, {
        ref: composedRefs // we make sure we're not trapping once it's been closed
        ,
        trapFocus: context.open,
        disableOutsidePointerEvents: true,
        onCloseAutoFocus: (0,_radix_ui_primitive__WEBPACK_IMPORTED_MODULE_8__/* .composeEventHandlers */ .M)(props.onCloseAutoFocus, (event)=>{
            var _context$triggerRef$c;
            event.preventDefault();
            if (!isRightClickOutsideRef.current) (_context$triggerRef$c = context.triggerRef.current) === null || _context$triggerRef$c === void 0 || _context$triggerRef$c.focus();
        }),
        onPointerDownOutside: (0,_radix_ui_primitive__WEBPACK_IMPORTED_MODULE_8__/* .composeEventHandlers */ .M)(props.onPointerDownOutside, (event)=>{
            const originalEvent = event.detail.originalEvent;
            const ctrlLeftClick = originalEvent.button === 0 && originalEvent.ctrlKey === true;
            const isRightClick = originalEvent.button === 2 || ctrlLeftClick;
            isRightClickOutsideRef.current = isRightClick;
        }, {
            checkForDefaultPrevented: false
        }) // When focus is trapped, a `focusout` event may still happen.
        ,
        onFocusOutside: (0,_radix_ui_primitive__WEBPACK_IMPORTED_MODULE_8__/* .composeEventHandlers */ .M)(props.onFocusOutside, (event)=>event.preventDefault()
        , {
            checkForDefaultPrevented: false
        })
    })));
});
const $cb5cc270b50c6fcd$var$PopoverContentNonModal = /*#__PURE__*/ (0,react__WEBPACK_IMPORTED_MODULE_0__.forwardRef)((props, forwardedRef)=>{
    const context = $cb5cc270b50c6fcd$var$usePopoverContext($cb5cc270b50c6fcd$var$CONTENT_NAME, props.__scopePopover);
    const hasInteractedOutsideRef = (0,react__WEBPACK_IMPORTED_MODULE_0__.useRef)(false);
    const hasPointerDownOutsideRef = (0,react__WEBPACK_IMPORTED_MODULE_0__.useRef)(false);
    return /*#__PURE__*/ (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)($cb5cc270b50c6fcd$var$PopoverContentImpl, (0,_babel_runtime_helpers_esm_extends__WEBPACK_IMPORTED_MODULE_5__/* ["default"] */ .Z)({}, props, {
        ref: forwardedRef,
        trapFocus: false,
        disableOutsidePointerEvents: false,
        onCloseAutoFocus: (event)=>{
            var _props$onCloseAutoFoc;
            (_props$onCloseAutoFoc = props.onCloseAutoFocus) === null || _props$onCloseAutoFoc === void 0 || _props$onCloseAutoFoc.call(props, event);
            if (!event.defaultPrevented) {
                var _context$triggerRef$c2;
                if (!hasInteractedOutsideRef.current) (_context$triggerRef$c2 = context.triggerRef.current) === null || _context$triggerRef$c2 === void 0 || _context$triggerRef$c2.focus(); // Always prevent auto focus because we either focus manually or want user agent focus
                event.preventDefault();
            }
            hasInteractedOutsideRef.current = false;
            hasPointerDownOutsideRef.current = false;
        },
        onInteractOutside: (event)=>{
            var _props$onInteractOuts, _context$triggerRef$c3;
            (_props$onInteractOuts = props.onInteractOutside) === null || _props$onInteractOuts === void 0 || _props$onInteractOuts.call(props, event);
            if (!event.defaultPrevented) {
                hasInteractedOutsideRef.current = true;
                if (event.detail.originalEvent.type === 'pointerdown') hasPointerDownOutsideRef.current = true;
            } // Prevent dismissing when clicking the trigger.
            // As the trigger is already setup to close, without doing so would
            // cause it to close and immediately open.
            const target = event.target;
            const targetIsTrigger = (_context$triggerRef$c3 = context.triggerRef.current) === null || _context$triggerRef$c3 === void 0 ? void 0 : _context$triggerRef$c3.contains(target);
            if (targetIsTrigger) event.preventDefault(); // On Safari if the trigger is inside a container with tabIndex={0}, when clicked
            // we will get the pointer down outside event on the trigger, but then a subsequent
            // focus outside event on the container, we ignore any focus outside event when we've
            // already had a pointer down outside event.
            if (event.detail.originalEvent.type === 'focusin' && hasPointerDownOutsideRef.current) event.preventDefault();
        }
    }));
});
/* -----------------------------------------------------------------------------------------------*/ const $cb5cc270b50c6fcd$var$PopoverContentImpl = /*#__PURE__*/ (0,react__WEBPACK_IMPORTED_MODULE_0__.forwardRef)((props, forwardedRef)=>{
    const { __scopePopover: __scopePopover , trapFocus: trapFocus , onOpenAutoFocus: onOpenAutoFocus , onCloseAutoFocus: onCloseAutoFocus , disableOutsidePointerEvents: disableOutsidePointerEvents , onEscapeKeyDown: onEscapeKeyDown , onPointerDownOutside: onPointerDownOutside , onFocusOutside: onFocusOutside , onInteractOutside: onInteractOutside , ...contentProps } = props;
    const context = $cb5cc270b50c6fcd$var$usePopoverContext($cb5cc270b50c6fcd$var$CONTENT_NAME, __scopePopover);
    const popperScope = $cb5cc270b50c6fcd$var$usePopperScope(__scopePopover); // Make sure the whole tree has focus guards as our `Popover` may be
    // the last element in the DOM (beacuse of the `Portal`)
    (0,_radix_ui_react_focus_guards__WEBPACK_IMPORTED_MODULE_14__/* .useFocusGuards */ .EW)();
    return /*#__PURE__*/ (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)(_radix_ui_react_focus_scope__WEBPACK_IMPORTED_MODULE_15__/* .FocusScope */ .M, {
        asChild: true,
        loop: true,
        trapped: trapFocus,
        onMountAutoFocus: onOpenAutoFocus,
        onUnmountAutoFocus: onCloseAutoFocus
    }, /*#__PURE__*/ (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)(_radix_ui_react_dismissable_layer__WEBPACK_IMPORTED_MODULE_16__/* .DismissableLayer */ .XB, {
        asChild: true,
        disableOutsidePointerEvents: disableOutsidePointerEvents,
        onInteractOutside: onInteractOutside,
        onEscapeKeyDown: onEscapeKeyDown,
        onPointerDownOutside: onPointerDownOutside,
        onFocusOutside: onFocusOutside,
        onDismiss: ()=>context.onOpenChange(false)
    }, /*#__PURE__*/ (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)(_radix_ui_react_popper__WEBPACK_IMPORTED_MODULE_2__/* .Content */ .VY, (0,_babel_runtime_helpers_esm_extends__WEBPACK_IMPORTED_MODULE_5__/* ["default"] */ .Z)({
        "data-state": $cb5cc270b50c6fcd$var$getState(context.open),
        role: "dialog",
        id: context.contentId
    }, popperScope, contentProps, {
        ref: forwardedRef,
        style: {
            ...contentProps.style,
            '--radix-popover-content-transform-origin': 'var(--radix-popper-transform-origin)',
            '--radix-popover-content-available-width': 'var(--radix-popper-available-width)',
            '--radix-popover-content-available-height': 'var(--radix-popper-available-height)',
            '--radix-popover-trigger-width': 'var(--radix-popper-anchor-width)',
            '--radix-popover-trigger-height': 'var(--radix-popper-anchor-height)'
        }
    }))));
});
/* -------------------------------------------------------------------------------------------------
 * PopoverClose
 * -----------------------------------------------------------------------------------------------*/ const $cb5cc270b50c6fcd$var$CLOSE_NAME = 'PopoverClose';
const $cb5cc270b50c6fcd$export$d6ac43ebaa40d53e = /*#__PURE__*/ (0,react__WEBPACK_IMPORTED_MODULE_0__.forwardRef)((props, forwardedRef)=>{
    const { __scopePopover: __scopePopover , ...closeProps } = props;
    const context = $cb5cc270b50c6fcd$var$usePopoverContext($cb5cc270b50c6fcd$var$CLOSE_NAME, __scopePopover);
    return /*#__PURE__*/ (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)(_radix_ui_react_primitive__WEBPACK_IMPORTED_MODULE_7__/* .Primitive */ .WV.button, (0,_babel_runtime_helpers_esm_extends__WEBPACK_IMPORTED_MODULE_5__/* ["default"] */ .Z)({
        type: "button"
    }, closeProps, {
        ref: forwardedRef,
        onClick: (0,_radix_ui_primitive__WEBPACK_IMPORTED_MODULE_8__/* .composeEventHandlers */ .M)(props.onClick, ()=>context.onOpenChange(false)
        )
    }));
});
/*#__PURE__*/ Object.assign($cb5cc270b50c6fcd$export$d6ac43ebaa40d53e, {
    displayName: $cb5cc270b50c6fcd$var$CLOSE_NAME
});
/* -------------------------------------------------------------------------------------------------
 * PopoverArrow
 * -----------------------------------------------------------------------------------------------*/ const $cb5cc270b50c6fcd$var$ARROW_NAME = 'PopoverArrow';
const $cb5cc270b50c6fcd$export$3152841115e061b2 = /*#__PURE__*/ (0,react__WEBPACK_IMPORTED_MODULE_0__.forwardRef)((props, forwardedRef)=>{
    const { __scopePopover: __scopePopover , ...arrowProps } = props;
    const popperScope = $cb5cc270b50c6fcd$var$usePopperScope(__scopePopover);
    return /*#__PURE__*/ (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)(_radix_ui_react_popper__WEBPACK_IMPORTED_MODULE_2__/* .Arrow */ .Eh, (0,_babel_runtime_helpers_esm_extends__WEBPACK_IMPORTED_MODULE_5__/* ["default"] */ .Z)({}, popperScope, arrowProps, {
        ref: forwardedRef
    }));
});
/*#__PURE__*/ Object.assign($cb5cc270b50c6fcd$export$3152841115e061b2, {
    displayName: $cb5cc270b50c6fcd$var$ARROW_NAME
});
/* -----------------------------------------------------------------------------------------------*/ function $cb5cc270b50c6fcd$var$getState(open) {
    return open ? 'open' : 'closed';
}
const $cb5cc270b50c6fcd$export$be92b6f5f03c0fe9 = $cb5cc270b50c6fcd$export$5b6b19405a83ff9d;
const $cb5cc270b50c6fcd$export$b688253958b8dfe7 = (/* unused pure expression or super */ null && ($cb5cc270b50c6fcd$export$96e5381f42521a79));
const $cb5cc270b50c6fcd$export$41fb9f06171c75f4 = $cb5cc270b50c6fcd$export$7dacb05d26466c3;
const $cb5cc270b50c6fcd$export$602eac185826482c = $cb5cc270b50c6fcd$export$dd679ffb4362d2d4;
const $cb5cc270b50c6fcd$export$7c6e2c02157bb7d2 = $cb5cc270b50c6fcd$export$d7e1f420b25549ff;
const $cb5cc270b50c6fcd$export$f39c2d165cd861fe = (/* unused pure expression or super */ null && ($cb5cc270b50c6fcd$export$d6ac43ebaa40d53e));
const $cb5cc270b50c6fcd$export$21b07c8f274aebd5 = (/* unused pure expression or super */ null && ($cb5cc270b50c6fcd$export$3152841115e061b2));





//# sourceMappingURL=index.mjs.map


/***/ })

};
;