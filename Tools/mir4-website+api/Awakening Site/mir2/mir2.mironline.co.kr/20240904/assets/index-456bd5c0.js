function B_(e, t) {
    for (var n = 0; n < t.length; n++) {
        const r = t[n];
        if (typeof r != "string" && !Array.isArray(r)) {
            for (const i in r)
                if (i !== "default" && !(i in e)) {
                    const s = Object.getOwnPropertyDescriptor(r, i);
                    s && Object.defineProperty(e, i, s.get ? s : {
                        enumerable: !0,
                        get: () => r[i]
                    })
                }
        }
    }
    return Object.freeze(Object.defineProperty(e, Symbol.toStringTag, {
        value: "Module"
    }))
}
(function() {
    const t = document.createElement("link").relList;
    if (t && t.supports && t.supports("modulepreload"))
        return;
    for (const i of document.querySelectorAll('link[rel="modulepreload"]'))
        r(i);
    new MutationObserver(i => {
        for (const s of i)
            if (s.type === "childList")
                for (const o of s.addedNodes)
                    o.tagName === "LINK" && o.rel === "modulepreload" && r(o)
    }
    ).observe(document, {
        childList: !0,
        subtree: !0
    });
    function n(i) {
        const s = {};
        return i.integrity && (s.integrity = i.integrity),
        i.referrerPolicy && (s.referrerPolicy = i.referrerPolicy),
        i.crossOrigin === "use-credentials" ? s.credentials = "include" : i.crossOrigin === "anonymous" ? s.credentials = "omit" : s.credentials = "same-origin",
        s
    }
    function r(i) {
        if (i.ep)
            return;
        i.ep = !0;
        const s = n(i);
        fetch(i.href, s)
    }
}
)();
function Yo(e) {
    return e && e.__esModule && Object.prototype.hasOwnProperty.call(e, "default") ? e.default : e
}
var U_ = {
    exports: {}
}
  , Jl = {}
  , H_ = {
    exports: {}
}
  , pe = {};
/**
 * @license React
 * react.production.min.js
 *
 * Copyright (c) Facebook, Inc. and its affiliates.
 *
 * This source code is licensed under the MIT license found in the
 * LICENSE file in the root directory of this source tree.
 */
var Ko = Symbol.for("react.element")
  , Kx = Symbol.for("react.portal")
  , Xx = Symbol.for("react.fragment")
  , Qx = Symbol.for("react.strict_mode")
  , Jx = Symbol.for("react.profiler")
  , Zx = Symbol.for("react.provider")
  , e1 = Symbol.for("react.context")
  , t1 = Symbol.for("react.forward_ref")
  , n1 = Symbol.for("react.suspense")
  , r1 = Symbol.for("react.memo")
  , i1 = Symbol.for("react.lazy")
  , xm = Symbol.iterator;
function s1(e) {
    return e === null || typeof e != "object" ? null : (e = xm && e[xm] || e["@@iterator"],
    typeof e == "function" ? e : null)
}
var G_ = {
    isMounted: function() {
        return !1
    },
    enqueueForceUpdate: function() {},
    enqueueReplaceState: function() {},
    enqueueSetState: function() {}
}
  , W_ = Object.assign
  , q_ = {};
function vs(e, t, n) {
    this.props = e,
    this.context = t,
    this.refs = q_,
    this.updater = n || G_
}
vs.prototype.isReactComponent = {};
vs.prototype.setState = function(e, t) {
    if (typeof e != "object" && typeof e != "function" && e != null)
        throw Error("setState(...): takes an object of state variables to update or a function which returns an object of state variables.");
    this.updater.enqueueSetState(this, e, t, "setState")
}
;
vs.prototype.forceUpdate = function(e) {
    this.updater.enqueueForceUpdate(this, e, "forceUpdate")
}
;
function Y_() {}
Y_.prototype = vs.prototype;
function jf(e, t, n) {
    this.props = e,
    this.context = t,
    this.refs = q_,
    this.updater = n || G_
}
var Nf = jf.prototype = new Y_;
Nf.constructor = jf;
W_(Nf, vs.prototype);
Nf.isPureReactComponent = !0;
var bm = Array.isArray
  , K_ = Object.prototype.hasOwnProperty
  , Rf = {
    current: null
}
  , X_ = {
    key: !0,
    ref: !0,
    __self: !0,
    __source: !0
};
function Q_(e, t, n) {
    var r, i = {}, s = null, o = null;
    if (t != null)
        for (r in t.ref !== void 0 && (o = t.ref),
        t.key !== void 0 && (s = "" + t.key),
        t)
            K_.call(t, r) && !X_.hasOwnProperty(r) && (i[r] = t[r]);
    var l = arguments.length - 2;
    if (l === 1)
        i.children = n;
    else if (1 < l) {
        for (var a = Array(l), c = 0; c < l; c++)
            a[c] = arguments[c + 2];
        i.children = a
    }
    if (e && e.defaultProps)
        for (r in l = e.defaultProps,
        l)
            i[r] === void 0 && (i[r] = l[r]);
    return {
        $$typeof: Ko,
        type: e,
        key: s,
        ref: o,
        props: i,
        _owner: Rf.current
    }
}
function o1(e, t) {
    return {
        $$typeof: Ko,
        type: e.type,
        key: t,
        ref: e.ref,
        props: e.props,
        _owner: e._owner
    }
}
function If(e) {
    return typeof e == "object" && e !== null && e.$$typeof === Ko
}
function a1(e) {
    var t = {
        "=": "=0",
        ":": "=2"
    };
    return "$" + e.replace(/[=:]/g, function(n) {
        return t[n]
    })
}
var Sm = /\/+/g;
function zc(e, t) {
    return typeof e == "object" && e !== null && e.key != null ? a1("" + e.key) : t.toString(36)
}
function qa(e, t, n, r, i) {
    var s = typeof e;
    (s === "undefined" || s === "boolean") && (e = null);
    var o = !1;
    if (e === null)
        o = !0;
    else
        switch (s) {
        case "string":
        case "number":
            o = !0;
            break;
        case "object":
            switch (e.$$typeof) {
            case Ko:
            case Kx:
                o = !0
            }
        }
    if (o)
        return o = e,
        i = i(o),
        e = r === "" ? "." + zc(o, 0) : r,
        bm(i) ? (n = "",
        e != null && (n = e.replace(Sm, "$&/") + "/"),
        qa(i, t, n, "", function(c) {
            return c
        })) : i != null && (If(i) && (i = o1(i, n + (!i.key || o && o.key === i.key ? "" : ("" + i.key).replace(Sm, "$&/") + "/") + e)),
        t.push(i)),
        1;
    if (o = 0,
    r = r === "" ? "." : r + ":",
    bm(e))
        for (var l = 0; l < e.length; l++) {
            s = e[l];
            var a = r + zc(s, l);
            o += qa(s, t, n, a, i)
        }
    else if (a = s1(e),
    typeof a == "function")
        for (e = a.call(e),
        l = 0; !(s = e.next()).done; )
            s = s.value,
            a = r + zc(s, l++),
            o += qa(s, t, n, a, i);
    else if (s === "object")
        throw t = String(e),
        Error("Objects are not valid as a React child (found: " + (t === "[object Object]" ? "object with keys {" + Object.keys(e).join(", ") + "}" : t) + "). If you meant to render a collection of children, use an array instead.");
    return o
}
function ga(e, t, n) {
    if (e == null)
        return e;
    var r = []
      , i = 0;
    return qa(e, r, "", "", function(s) {
        return t.call(n, s, i++)
    }),
    r
}
function l1(e) {
    if (e._status === -1) {
        var t = e._result;
        t = t(),
        t.then(function(n) {
            (e._status === 0 || e._status === -1) && (e._status = 1,
            e._result = n)
        }, function(n) {
            (e._status === 0 || e._status === -1) && (e._status = 2,
            e._result = n)
        }),
        e._status === -1 && (e._status = 0,
        e._result = t)
    }
    if (e._status === 1)
        return e._result.default;
    throw e._result
}
var kt = {
    current: null
}
  , Ya = {
    transition: null
}
  , c1 = {
    ReactCurrentDispatcher: kt,
    ReactCurrentBatchConfig: Ya,
    ReactCurrentOwner: Rf
};
function J_() {
    throw Error("act(...) is not supported in production builds of React.")
}
pe.Children = {
    map: ga,
    forEach: function(e, t, n) {
        ga(e, function() {
            t.apply(this, arguments)
        }, n)
    },
    count: function(e) {
        var t = 0;
        return ga(e, function() {
            t++
        }),
        t
    },
    toArray: function(e) {
        return ga(e, function(t) {
            return t
        }) || []
    },
    only: function(e) {
        if (!If(e))
            throw Error("React.Children.only expected to receive a single React element child.");
        return e
    }
};
pe.Component = vs;
pe.Fragment = Xx;
pe.Profiler = Jx;
pe.PureComponent = jf;
pe.StrictMode = Qx;
pe.Suspense = n1;
pe.__SECRET_INTERNALS_DO_NOT_USE_OR_YOU_WILL_BE_FIRED = c1;
pe.act = J_;
pe.cloneElement = function(e, t, n) {
    if (e == null)
        throw Error("React.cloneElement(...): The argument must be a React element, but you passed " + e + ".");
    var r = W_({}, e.props)
      , i = e.key
      , s = e.ref
      , o = e._owner;
    if (t != null) {
        if (t.ref !== void 0 && (s = t.ref,
        o = Rf.current),
        t.key !== void 0 && (i = "" + t.key),
        e.type && e.type.defaultProps)
            var l = e.type.defaultProps;
        for (a in t)
            K_.call(t, a) && !X_.hasOwnProperty(a) && (r[a] = t[a] === void 0 && l !== void 0 ? l[a] : t[a])
    }
    var a = arguments.length - 2;
    if (a === 1)
        r.children = n;
    else if (1 < a) {
        l = Array(a);
        for (var c = 0; c < a; c++)
            l[c] = arguments[c + 2];
        r.children = l
    }
    return {
        $$typeof: Ko,
        type: e.type,
        key: i,
        ref: s,
        props: r,
        _owner: o
    }
}
;
pe.createContext = function(e) {
    return e = {
        $$typeof: e1,
        _currentValue: e,
        _currentValue2: e,
        _threadCount: 0,
        Provider: null,
        Consumer: null,
        _defaultValue: null,
        _globalName: null
    },
    e.Provider = {
        $$typeof: Zx,
        _context: e
    },
    e.Consumer = e
}
;
pe.createElement = Q_;
pe.createFactory = function(e) {
    var t = Q_.bind(null, e);
    return t.type = e,
    t
}
;
pe.createRef = function() {
    return {
        current: null
    }
}
;
pe.forwardRef = function(e) {
    return {
        $$typeof: t1,
        render: e
    }
}
;
pe.isValidElement = If;
pe.lazy = function(e) {
    return {
        $$typeof: i1,
        _payload: {
            _status: -1,
            _result: e
        },
        _init: l1
    }
}
;
pe.memo = function(e, t) {
    return {
        $$typeof: r1,
        type: e,
        compare: t === void 0 ? null : t
    }
}
;
pe.startTransition = function(e) {
    var t = Ya.transition;
    Ya.transition = {};
    try {
        e()
    } finally {
        Ya.transition = t
    }
}
;
pe.unstable_act = J_;
pe.useCallback = function(e, t) {
    return kt.current.useCallback(e, t)
}
;
pe.useContext = function(e) {
    return kt.current.useContext(e)
}
;
pe.useDebugValue = function() {}
;
pe.useDeferredValue = function(e) {
    return kt.current.useDeferredValue(e)
}
;
pe.useEffect = function(e, t) {
    return kt.current.useEffect(e, t)
}
;
pe.useId = function() {
    return kt.current.useId()
}
;
pe.useImperativeHandle = function(e, t, n) {
    return kt.current.useImperativeHandle(e, t, n)
}
;
pe.useInsertionEffect = function(e, t) {
    return kt.current.useInsertionEffect(e, t)
}
;
pe.useLayoutEffect = function(e, t) {
    return kt.current.useLayoutEffect(e, t)
}
;
pe.useMemo = function(e, t) {
    return kt.current.useMemo(e, t)
}
;
pe.useReducer = function(e, t, n) {
    return kt.current.useReducer(e, t, n)
}
;
pe.useRef = function(e) {
    return kt.current.useRef(e)
}
;
pe.useState = function(e) {
    return kt.current.useState(e)
}
;
pe.useSyncExternalStore = function(e, t, n) {
    return kt.current.useSyncExternalStore(e, t, n)
}
;
pe.useTransition = function() {
    return kt.current.useTransition()
}
;
pe.version = "18.3.1";
H_.exports = pe;
var g = H_.exports;
const Ce = Yo(g)
  , u1 = B_({
    __proto__: null,
    default: Ce
}, [g]);
/**
 * @license React
 * react-jsx-runtime.production.min.js
 *
 * Copyright (c) Facebook, Inc. and its affiliates.
 *
 * This source code is licensed under the MIT license found in the
 * LICENSE file in the root directory of this source tree.
 */
var d1 = g
  , f1 = Symbol.for("react.element")
  , p1 = Symbol.for("react.fragment")
  , m1 = Object.prototype.hasOwnProperty
  , h1 = d1.__SECRET_INTERNALS_DO_NOT_USE_OR_YOU_WILL_BE_FIRED.ReactCurrentOwner
  , g1 = {
    key: !0,
    ref: !0,
    __self: !0,
    __source: !0
};
function Z_(e, t, n) {
    var r, i = {}, s = null, o = null;
    n !== void 0 && (s = "" + n),
    t.key !== void 0 && (s = "" + t.key),
    t.ref !== void 0 && (o = t.ref);
    for (r in t)
        m1.call(t, r) && !g1.hasOwnProperty(r) && (i[r] = t[r]);
    if (e && e.defaultProps)
        for (r in t = e.defaultProps,
        t)
            i[r] === void 0 && (i[r] = t[r]);
    return {
        $$typeof: f1,
        type: e,
        key: s,
        ref: o,
        props: i,
        _owner: h1.current
    }
}
Jl.Fragment = p1;
Jl.jsx = Z_;
Jl.jsxs = Z_;
U_.exports = Jl;
var u = U_.exports
  , Xu = {}
  , ev = {
    exports: {}
}
  , Yt = {}
  , tv = {
    exports: {}
}
  , nv = {};
/**
 * @license React
 * scheduler.production.min.js
 *
 * Copyright (c) Facebook, Inc. and its affiliates.
 *
 * This source code is licensed under the MIT license found in the
 * LICENSE file in the root directory of this source tree.
 */
(function(e) {
    function t(N, $) {
        var O = N.length;
        N.push($);
        e: for (; 0 < O; ) {
            var B = O - 1 >>> 1
              , H = N[B];
            if (0 < i(H, $))
                N[B] = $,
                N[O] = H,
                O = B;
            else
                break e
        }
    }
    function n(N) {
        return N.length === 0 ? null : N[0]
    }
    function r(N) {
        if (N.length === 0)
            return null;
        var $ = N[0]
          , O = N.pop();
        if (O !== $) {
            N[0] = O;
            e: for (var B = 0, H = N.length, ue = H >>> 1; B < ue; ) {
                var de = 2 * (B + 1) - 1
                  , xe = N[de]
                  , ge = de + 1
                  , wt = N[ge];
                if (0 > i(xe, O))
                    ge < H && 0 > i(wt, xe) ? (N[B] = wt,
                    N[ge] = O,
                    B = ge) : (N[B] = xe,
                    N[de] = O,
                    B = de);
                else if (ge < H && 0 > i(wt, O))
                    N[B] = wt,
                    N[ge] = O,
                    B = ge;
                else
                    break e
            }
        }
        return $
    }
    function i(N, $) {
        var O = N.sortIndex - $.sortIndex;
        return O !== 0 ? O : N.id - $.id
    }
    if (typeof performance == "object" && typeof performance.now == "function") {
        var s = performance;
        e.unstable_now = function() {
            return s.now()
        }
    } else {
        var o = Date
          , l = o.now();
        e.unstable_now = function() {
            return o.now() - l
        }
    }
    var a = []
      , c = []
      , d = 1
      , f = null
      , p = 3
      , h = !1
      , m = !1
      , _ = !1
      , x = typeof setTimeout == "function" ? setTimeout : null
      , v = typeof clearTimeout == "function" ? clearTimeout : null
      , w = typeof setImmediate < "u" ? setImmediate : null;
    typeof navigator < "u" && navigator.scheduling !== void 0 && navigator.scheduling.isInputPending !== void 0 && navigator.scheduling.isInputPending.bind(navigator.scheduling);
    function y(N) {
        for (var $ = n(c); $ !== null; ) {
            if ($.callback === null)
                r(c);
            else if ($.startTime <= N)
                r(c),
                $.sortIndex = $.expirationTime,
                t(a, $);
            else
                break;
            $ = n(c)
        }
    }
    function b(N) {
        if (_ = !1,
        y(N),
        !m)
            if (n(a) !== null)
                m = !0,
                Z(C);
            else {
                var $ = n(c);
                $ !== null && L(b, $.startTime - N)
            }
    }
    function C(N, $) {
        m = !1,
        _ && (_ = !1,
        v(k),
        k = -1),
        h = !0;
        var O = p;
        try {
            for (y($),
            f = n(a); f !== null && (!(f.expirationTime > $) || N && !A()); ) {
                var B = f.callback;
                if (typeof B == "function") {
                    f.callback = null,
                    p = f.priorityLevel;
                    var H = B(f.expirationTime <= $);
                    $ = e.unstable_now(),
                    typeof H == "function" ? f.callback = H : f === n(a) && r(a),
                    y($)
                } else
                    r(a);
                f = n(a)
            }
            if (f !== null)
                var ue = !0;
            else {
                var de = n(c);
                de !== null && L(b, de.startTime - $),
                ue = !1
            }
            return ue
        } finally {
            f = null,
            p = O,
            h = !1
        }
    }
    var S = !1
      , T = null
      , k = -1
      , P = 5
      , R = -1;
    function A() {
        return !(e.unstable_now() - R < P)
    }
    function V() {
        if (T !== null) {
            var N = e.unstable_now();
            R = N;
            var $ = !0;
            try {
                $ = T(!0, N)
            } finally {
                $ ? F() : (S = !1,
                T = null)
            }
        } else
            S = !1
    }
    var F;
    if (typeof w == "function")
        F = function() {
            w(V)
        }
        ;
    else if (typeof MessageChannel < "u") {
        var U = new MessageChannel
          , Q = U.port2;
        U.port1.onmessage = V,
        F = function() {
            Q.postMessage(null)
        }
    } else
        F = function() {
            x(V, 0)
        }
        ;
    function Z(N) {
        T = N,
        S || (S = !0,
        F())
    }
    function L(N, $) {
        k = x(function() {
            N(e.unstable_now())
        }, $)
    }
    e.unstable_IdlePriority = 5,
    e.unstable_ImmediatePriority = 1,
    e.unstable_LowPriority = 4,
    e.unstable_NormalPriority = 3,
    e.unstable_Profiling = null,
    e.unstable_UserBlockingPriority = 2,
    e.unstable_cancelCallback = function(N) {
        N.callback = null
    }
    ,
    e.unstable_continueExecution = function() {
        m || h || (m = !0,
        Z(C))
    }
    ,
    e.unstable_forceFrameRate = function(N) {
        0 > N || 125 < N ? console.error("forceFrameRate takes a positive int between 0 and 125, forcing frame rates higher than 125 fps is not supported") : P = 0 < N ? Math.floor(1e3 / N) : 5
    }
    ,
    e.unstable_getCurrentPriorityLevel = function() {
        return p
    }
    ,
    e.unstable_getFirstCallbackNode = function() {
        return n(a)
    }
    ,
    e.unstable_next = function(N) {
        switch (p) {
        case 1:
        case 2:
        case 3:
            var $ = 3;
            break;
        default:
            $ = p
        }
        var O = p;
        p = $;
        try {
            return N()
        } finally {
            p = O
        }
    }
    ,
    e.unstable_pauseExecution = function() {}
    ,
    e.unstable_requestPaint = function() {}
    ,
    e.unstable_runWithPriority = function(N, $) {
        switch (N) {
        case 1:
        case 2:
        case 3:
        case 4:
        case 5:
            break;
        default:
            N = 3
        }
        var O = p;
        p = N;
        try {
            return $()
        } finally {
            p = O
        }
    }
    ,
    e.unstable_scheduleCallback = function(N, $, O) {
        var B = e.unstable_now();
        switch (typeof O == "object" && O !== null ? (O = O.delay,
        O = typeof O == "number" && 0 < O ? B + O : B) : O = B,
        N) {
        case 1:
            var H = -1;
            break;
        case 2:
            H = 250;
            break;
        case 5:
            H = 1073741823;
            break;
        case 4:
            H = 1e4;
            break;
        default:
            H = 5e3
        }
        return H = O + H,
        N = {
            id: d++,
            callback: $,
            priorityLevel: N,
            startTime: O,
            expirationTime: H,
            sortIndex: -1
        },
        O > B ? (N.sortIndex = O,
        t(c, N),
        n(a) === null && N === n(c) && (_ ? (v(k),
        k = -1) : _ = !0,
        L(b, O - B))) : (N.sortIndex = H,
        t(a, N),
        m || h || (m = !0,
        Z(C))),
        N
    }
    ,
    e.unstable_shouldYield = A,
    e.unstable_wrapCallback = function(N) {
        var $ = p;
        return function() {
            var O = p;
            p = $;
            try {
                return N.apply(this, arguments)
            } finally {
                p = O
            }
        }
    }
}
)(nv);
tv.exports = nv;
var _1 = tv.exports;
/**
 * @license React
 * react-dom.production.min.js
 *
 * Copyright (c) Facebook, Inc. and its affiliates.
 *
 * This source code is licensed under the MIT license found in the
 * LICENSE file in the root directory of this source tree.
 */
var v1 = g
  , Wt = _1;
function z(e) {
    for (var t = "https://reactjs.org/docs/error-decoder.html?invariant=" + e, n = 1; n < arguments.length; n++)
        t += "&args[]=" + encodeURIComponent(arguments[n]);
    return "Minified React error #" + e + "; visit " + t + " for the full message or use the non-minified dev environment for full errors and additional helpful warnings."
}
var rv = new Set
  , _o = {};
function yi(e, t) {
    os(e, t),
    os(e + "Capture", t)
}
function os(e, t) {
    for (_o[e] = t,
    e = 0; e < t.length; e++)
        rv.add(t[e])
}
var Qn = !(typeof window > "u" || typeof window.document > "u" || typeof window.document.createElement > "u")
  , Qu = Object.prototype.hasOwnProperty
  , y1 = /^[:A-Z_a-z\u00C0-\u00D6\u00D8-\u00F6\u00F8-\u02FF\u0370-\u037D\u037F-\u1FFF\u200C-\u200D\u2070-\u218F\u2C00-\u2FEF\u3001-\uD7FF\uF900-\uFDCF\uFDF0-\uFFFD][:A-Z_a-z\u00C0-\u00D6\u00D8-\u00F6\u00F8-\u02FF\u0370-\u037D\u037F-\u1FFF\u200C-\u200D\u2070-\u218F\u2C00-\u2FEF\u3001-\uD7FF\uF900-\uFDCF\uFDF0-\uFFFD\-.0-9\u00B7\u0300-\u036F\u203F-\u2040]*$/
  , Cm = {}
  , km = {};
function w1(e) {
    return Qu.call(km, e) ? !0 : Qu.call(Cm, e) ? !1 : y1.test(e) ? km[e] = !0 : (Cm[e] = !0,
    !1)
}
function x1(e, t, n, r) {
    if (n !== null && n.type === 0)
        return !1;
    switch (typeof t) {
    case "function":
    case "symbol":
        return !0;
    case "boolean":
        return r ? !1 : n !== null ? !n.acceptsBooleans : (e = e.toLowerCase().slice(0, 5),
        e !== "data-" && e !== "aria-");
    default:
        return !1
    }
}
function b1(e, t, n, r) {
    if (t === null || typeof t > "u" || x1(e, t, n, r))
        return !0;
    if (r)
        return !1;
    if (n !== null)
        switch (n.type) {
        case 3:
            return !t;
        case 4:
            return t === !1;
        case 5:
            return isNaN(t);
        case 6:
            return isNaN(t) || 1 > t
        }
    return !1
}
function Et(e, t, n, r, i, s, o) {
    this.acceptsBooleans = t === 2 || t === 3 || t === 4,
    this.attributeName = r,
    this.attributeNamespace = i,
    this.mustUseProperty = n,
    this.propertyName = e,
    this.type = t,
    this.sanitizeURL = s,
    this.removeEmptyString = o
}
var dt = {};
"children dangerouslySetInnerHTML defaultValue defaultChecked innerHTML suppressContentEditableWarning suppressHydrationWarning style".split(" ").forEach(function(e) {
    dt[e] = new Et(e,0,!1,e,null,!1,!1)
});
[["acceptCharset", "accept-charset"], ["className", "class"], ["htmlFor", "for"], ["httpEquiv", "http-equiv"]].forEach(function(e) {
    var t = e[0];
    dt[t] = new Et(t,1,!1,e[1],null,!1,!1)
});
["contentEditable", "draggable", "spellCheck", "value"].forEach(function(e) {
    dt[e] = new Et(e,2,!1,e.toLowerCase(),null,!1,!1)
});
["autoReverse", "externalResourcesRequired", "focusable", "preserveAlpha"].forEach(function(e) {
    dt[e] = new Et(e,2,!1,e,null,!1,!1)
});
"allowFullScreen async autoFocus autoPlay controls default defer disabled disablePictureInPicture disableRemotePlayback formNoValidate hidden loop noModule noValidate open playsInline readOnly required reversed scoped seamless itemScope".split(" ").forEach(function(e) {
    dt[e] = new Et(e,3,!1,e.toLowerCase(),null,!1,!1)
});
["checked", "multiple", "muted", "selected"].forEach(function(e) {
    dt[e] = new Et(e,3,!0,e,null,!1,!1)
});
["capture", "download"].forEach(function(e) {
    dt[e] = new Et(e,4,!1,e,null,!1,!1)
});
["cols", "rows", "size", "span"].forEach(function(e) {
    dt[e] = new Et(e,6,!1,e,null,!1,!1)
});
["rowSpan", "start"].forEach(function(e) {
    dt[e] = new Et(e,5,!1,e.toLowerCase(),null,!1,!1)
});
var Lf = /[\-:]([a-z])/g;
function Mf(e) {
    return e[1].toUpperCase()
}
"accent-height alignment-baseline arabic-form baseline-shift cap-height clip-path clip-rule color-interpolation color-interpolation-filters color-profile color-rendering dominant-baseline enable-background fill-opacity fill-rule flood-color flood-opacity font-family font-size font-size-adjust font-stretch font-style font-variant font-weight glyph-name glyph-orientation-horizontal glyph-orientation-vertical horiz-adv-x horiz-origin-x image-rendering letter-spacing lighting-color marker-end marker-mid marker-start overline-position overline-thickness paint-order panose-1 pointer-events rendering-intent shape-rendering stop-color stop-opacity strikethrough-position strikethrough-thickness stroke-dasharray stroke-dashoffset stroke-linecap stroke-linejoin stroke-miterlimit stroke-opacity stroke-width text-anchor text-decoration text-rendering underline-position underline-thickness unicode-bidi unicode-range units-per-em v-alphabetic v-hanging v-ideographic v-mathematical vector-effect vert-adv-y vert-origin-x vert-origin-y word-spacing writing-mode xmlns:xlink x-height".split(" ").forEach(function(e) {
    var t = e.replace(Lf, Mf);
    dt[t] = new Et(t,1,!1,e,null,!1,!1)
});
"xlink:actuate xlink:arcrole xlink:role xlink:show xlink:title xlink:type".split(" ").forEach(function(e) {
    var t = e.replace(Lf, Mf);
    dt[t] = new Et(t,1,!1,e,"http://www.w3.org/1999/xlink",!1,!1)
});
["xml:base", "xml:lang", "xml:space"].forEach(function(e) {
    var t = e.replace(Lf, Mf);
    dt[t] = new Et(t,1,!1,e,"http://www.w3.org/XML/1998/namespace",!1,!1)
});
["tabIndex", "crossOrigin"].forEach(function(e) {
    dt[e] = new Et(e,1,!1,e.toLowerCase(),null,!1,!1)
});
dt.xlinkHref = new Et("xlinkHref",1,!1,"xlink:href","http://www.w3.org/1999/xlink",!0,!1);
["src", "href", "action", "formAction"].forEach(function(e) {
    dt[e] = new Et(e,1,!1,e.toLowerCase(),null,!0,!0)
});
function Of(e, t, n, r) {
    var i = dt.hasOwnProperty(t) ? dt[t] : null;
    (i !== null ? i.type !== 0 : r || !(2 < t.length) || t[0] !== "o" && t[0] !== "O" || t[1] !== "n" && t[1] !== "N") && (b1(t, n, i, r) && (n = null),
    r || i === null ? w1(t) && (n === null ? e.removeAttribute(t) : e.setAttribute(t, "" + n)) : i.mustUseProperty ? e[i.propertyName] = n === null ? i.type === 3 ? !1 : "" : n : (t = i.attributeName,
    r = i.attributeNamespace,
    n === null ? e.removeAttribute(t) : (i = i.type,
    n = i === 3 || i === 4 && n === !0 ? "" : "" + n,
    r ? e.setAttributeNS(r, t, n) : e.setAttribute(t, n))))
}
var nr = v1.__SECRET_INTERNALS_DO_NOT_USE_OR_YOU_WILL_BE_FIRED
  , _a = Symbol.for("react.element")
  , Di = Symbol.for("react.portal")
  , $i = Symbol.for("react.fragment")
  , Af = Symbol.for("react.strict_mode")
  , Ju = Symbol.for("react.profiler")
  , iv = Symbol.for("react.provider")
  , sv = Symbol.for("react.context")
  , Df = Symbol.for("react.forward_ref")
  , Zu = Symbol.for("react.suspense")
  , ed = Symbol.for("react.suspense_list")
  , $f = Symbol.for("react.memo")
  , hr = Symbol.for("react.lazy")
  , ov = Symbol.for("react.offscreen")
  , Em = Symbol.iterator;
function Ps(e) {
    return e === null || typeof e != "object" ? null : (e = Em && e[Em] || e["@@iterator"],
    typeof e == "function" ? e : null)
}
var ze = Object.assign, Bc;
function qs(e) {
    if (Bc === void 0)
        try {
            throw Error()
        } catch (n) {
            var t = n.stack.trim().match(/\n( *(at )?)/);
            Bc = t && t[1] || ""
        }
    return `
` + Bc + e
}
var Uc = !1;
function Hc(e, t) {
    if (!e || Uc)
        return "";
    Uc = !0;
    var n = Error.prepareStackTrace;
    Error.prepareStackTrace = void 0;
    try {
        if (t)
            if (t = function() {
                throw Error()
            }
            ,
            Object.defineProperty(t.prototype, "props", {
                set: function() {
                    throw Error()
                }
            }),
            typeof Reflect == "object" && Reflect.construct) {
                try {
                    Reflect.construct(t, [])
                } catch (c) {
                    var r = c
                }
                Reflect.construct(e, [], t)
            } else {
                try {
                    t.call()
                } catch (c) {
                    r = c
                }
                e.call(t.prototype)
            }
        else {
            try {
                throw Error()
            } catch (c) {
                r = c
            }
            e()
        }
    } catch (c) {
        if (c && r && typeof c.stack == "string") {
            for (var i = c.stack.split(`
`), s = r.stack.split(`
`), o = i.length - 1, l = s.length - 1; 1 <= o && 0 <= l && i[o] !== s[l]; )
                l--;
            for (; 1 <= o && 0 <= l; o--,
            l--)
                if (i[o] !== s[l]) {
                    if (o !== 1 || l !== 1)
                        do
                            if (o--,
                            l--,
                            0 > l || i[o] !== s[l]) {
                                var a = `
` + i[o].replace(" at new ", " at ");
                                return e.displayName && a.includes("<anonymous>") && (a = a.replace("<anonymous>", e.displayName)),
                                a
                            }
                        while (1 <= o && 0 <= l);
                    break
                }
        }
    } finally {
        Uc = !1,
        Error.prepareStackTrace = n
    }
    return (e = e ? e.displayName || e.name : "") ? qs(e) : ""
}
function S1(e) {
    switch (e.tag) {
    case 5:
        return qs(e.type);
    case 16:
        return qs("Lazy");
    case 13:
        return qs("Suspense");
    case 19:
        return qs("SuspenseList");
    case 0:
    case 2:
    case 15:
        return e = Hc(e.type, !1),
        e;
    case 11:
        return e = Hc(e.type.render, !1),
        e;
    case 1:
        return e = Hc(e.type, !0),
        e;
    default:
        return ""
    }
}
function td(e) {
    if (e == null)
        return null;
    if (typeof e == "function")
        return e.displayName || e.name || null;
    if (typeof e == "string")
        return e;
    switch (e) {
    case $i:
        return "Fragment";
    case Di:
        return "Portal";
    case Ju:
        return "Profiler";
    case Af:
        return "StrictMode";
    case Zu:
        return "Suspense";
    case ed:
        return "SuspenseList"
    }
    if (typeof e == "object")
        switch (e.$$typeof) {
        case sv:
            return (e.displayName || "Context") + ".Consumer";
        case iv:
            return (e._context.displayName || "Context") + ".Provider";
        case Df:
            var t = e.render;
            return e = e.displayName,
            e || (e = t.displayName || t.name || "",
            e = e !== "" ? "ForwardRef(" + e + ")" : "ForwardRef"),
            e;
        case $f:
            return t = e.displayName || null,
            t !== null ? t : td(e.type) || "Memo";
        case hr:
            t = e._payload,
            e = e._init;
            try {
                return td(e(t))
            } catch {}
        }
    return null
}
function C1(e) {
    var t = e.type;
    switch (e.tag) {
    case 24:
        return "Cache";
    case 9:
        return (t.displayName || "Context") + ".Consumer";
    case 10:
        return (t._context.displayName || "Context") + ".Provider";
    case 18:
        return "DehydratedFragment";
    case 11:
        return e = t.render,
        e = e.displayName || e.name || "",
        t.displayName || (e !== "" ? "ForwardRef(" + e + ")" : "ForwardRef");
    case 7:
        return "Fragment";
    case 5:
        return t;
    case 4:
        return "Portal";
    case 3:
        return "Root";
    case 6:
        return "Text";
    case 16:
        return td(t);
    case 8:
        return t === Af ? "StrictMode" : "Mode";
    case 22:
        return "Offscreen";
    case 12:
        return "Profiler";
    case 21:
        return "Scope";
    case 13:
        return "Suspense";
    case 19:
        return "SuspenseList";
    case 25:
        return "TracingMarker";
    case 1:
    case 0:
    case 17:
    case 2:
    case 14:
    case 15:
        if (typeof t == "function")
            return t.displayName || t.name || null;
        if (typeof t == "string")
            return t
    }
    return null
}
function Or(e) {
    switch (typeof e) {
    case "boolean":
    case "number":
    case "string":
    case "undefined":
        return e;
    case "object":
        return e;
    default:
        return ""
    }
}
function av(e) {
    var t = e.type;
    return (e = e.nodeName) && e.toLowerCase() === "input" && (t === "checkbox" || t === "radio")
}
function k1(e) {
    var t = av(e) ? "checked" : "value"
      , n = Object.getOwnPropertyDescriptor(e.constructor.prototype, t)
      , r = "" + e[t];
    if (!e.hasOwnProperty(t) && typeof n < "u" && typeof n.get == "function" && typeof n.set == "function") {
        var i = n.get
          , s = n.set;
        return Object.defineProperty(e, t, {
            configurable: !0,
            get: function() {
                return i.call(this)
            },
            set: function(o) {
                r = "" + o,
                s.call(this, o)
            }
        }),
        Object.defineProperty(e, t, {
            enumerable: n.enumerable
        }),
        {
            getValue: function() {
                return r
            },
            setValue: function(o) {
                r = "" + o
            },
            stopTracking: function() {
                e._valueTracker = null,
                delete e[t]
            }
        }
    }
}
function va(e) {
    e._valueTracker || (e._valueTracker = k1(e))
}
function lv(e) {
    if (!e)
        return !1;
    var t = e._valueTracker;
    if (!t)
        return !0;
    var n = t.getValue()
      , r = "";
    return e && (r = av(e) ? e.checked ? "true" : "false" : e.value),
    e = r,
    e !== n ? (t.setValue(e),
    !0) : !1
}
function pl(e) {
    if (e = e || (typeof document < "u" ? document : void 0),
    typeof e > "u")
        return null;
    try {
        return e.activeElement || e.body
    } catch {
        return e.body
    }
}
function nd(e, t) {
    var n = t.checked;
    return ze({}, t, {
        defaultChecked: void 0,
        defaultValue: void 0,
        value: void 0,
        checked: n ?? e._wrapperState.initialChecked
    })
}
function Tm(e, t) {
    var n = t.defaultValue == null ? "" : t.defaultValue
      , r = t.checked != null ? t.checked : t.defaultChecked;
    n = Or(t.value != null ? t.value : n),
    e._wrapperState = {
        initialChecked: r,
        initialValue: n,
        controlled: t.type === "checkbox" || t.type === "radio" ? t.checked != null : t.value != null
    }
}
function cv(e, t) {
    t = t.checked,
    t != null && Of(e, "checked", t, !1)
}
function rd(e, t) {
    cv(e, t);
    var n = Or(t.value)
      , r = t.type;
    if (n != null)
        r === "number" ? (n === 0 && e.value === "" || e.value != n) && (e.value = "" + n) : e.value !== "" + n && (e.value = "" + n);
    else if (r === "submit" || r === "reset") {
        e.removeAttribute("value");
        return
    }
    t.hasOwnProperty("value") ? id(e, t.type, n) : t.hasOwnProperty("defaultValue") && id(e, t.type, Or(t.defaultValue)),
    t.checked == null && t.defaultChecked != null && (e.defaultChecked = !!t.defaultChecked)
}
function Pm(e, t, n) {
    if (t.hasOwnProperty("value") || t.hasOwnProperty("defaultValue")) {
        var r = t.type;
        if (!(r !== "submit" && r !== "reset" || t.value !== void 0 && t.value !== null))
            return;
        t = "" + e._wrapperState.initialValue,
        n || t === e.value || (e.value = t),
        e.defaultValue = t
    }
    n = e.name,
    n !== "" && (e.name = ""),
    e.defaultChecked = !!e._wrapperState.initialChecked,
    n !== "" && (e.name = n)
}
function id(e, t, n) {
    (t !== "number" || pl(e.ownerDocument) !== e) && (n == null ? e.defaultValue = "" + e._wrapperState.initialValue : e.defaultValue !== "" + n && (e.defaultValue = "" + n))
}
var Ys = Array.isArray;
function Zi(e, t, n, r) {
    if (e = e.options,
    t) {
        t = {};
        for (var i = 0; i < n.length; i++)
            t["$" + n[i]] = !0;
        for (n = 0; n < e.length; n++)
            i = t.hasOwnProperty("$" + e[n].value),
            e[n].selected !== i && (e[n].selected = i),
            i && r && (e[n].defaultSelected = !0)
    } else {
        for (n = "" + Or(n),
        t = null,
        i = 0; i < e.length; i++) {
            if (e[i].value === n) {
                e[i].selected = !0,
                r && (e[i].defaultSelected = !0);
                return
            }
            t !== null || e[i].disabled || (t = e[i])
        }
        t !== null && (t.selected = !0)
    }
}
function sd(e, t) {
    if (t.dangerouslySetInnerHTML != null)
        throw Error(z(91));
    return ze({}, t, {
        value: void 0,
        defaultValue: void 0,
        children: "" + e._wrapperState.initialValue
    })
}
function jm(e, t) {
    var n = t.value;
    if (n == null) {
        if (n = t.children,
        t = t.defaultValue,
        n != null) {
            if (t != null)
                throw Error(z(92));
            if (Ys(n)) {
                if (1 < n.length)
                    throw Error(z(93));
                n = n[0]
            }
            t = n
        }
        t == null && (t = ""),
        n = t
    }
    e._wrapperState = {
        initialValue: Or(n)
    }
}
function uv(e, t) {
    var n = Or(t.value)
      , r = Or(t.defaultValue);
    n != null && (n = "" + n,
    n !== e.value && (e.value = n),
    t.defaultValue == null && e.defaultValue !== n && (e.defaultValue = n)),
    r != null && (e.defaultValue = "" + r)
}
function Nm(e) {
    var t = e.textContent;
    t === e._wrapperState.initialValue && t !== "" && t !== null && (e.value = t)
}
function dv(e) {
    switch (e) {
    case "svg":
        return "http://www.w3.org/2000/svg";
    case "math":
        return "http://www.w3.org/1998/Math/MathML";
    default:
        return "http://www.w3.org/1999/xhtml"
    }
}
function od(e, t) {
    return e == null || e === "http://www.w3.org/1999/xhtml" ? dv(t) : e === "http://www.w3.org/2000/svg" && t === "foreignObject" ? "http://www.w3.org/1999/xhtml" : e
}
var ya, fv = function(e) {
    return typeof MSApp < "u" && MSApp.execUnsafeLocalFunction ? function(t, n, r, i) {
        MSApp.execUnsafeLocalFunction(function() {
            return e(t, n, r, i)
        })
    }
    : e
}(function(e, t) {
    if (e.namespaceURI !== "http://www.w3.org/2000/svg" || "innerHTML"in e)
        e.innerHTML = t;
    else {
        for (ya = ya || document.createElement("div"),
        ya.innerHTML = "<svg>" + t.valueOf().toString() + "</svg>",
        t = ya.firstChild; e.firstChild; )
            e.removeChild(e.firstChild);
        for (; t.firstChild; )
            e.appendChild(t.firstChild)
    }
});
function vo(e, t) {
    if (t) {
        var n = e.firstChild;
        if (n && n === e.lastChild && n.nodeType === 3) {
            n.nodeValue = t;
            return
        }
    }
    e.textContent = t
}
var Zs = {
    animationIterationCount: !0,
    aspectRatio: !0,
    borderImageOutset: !0,
    borderImageSlice: !0,
    borderImageWidth: !0,
    boxFlex: !0,
    boxFlexGroup: !0,
    boxOrdinalGroup: !0,
    columnCount: !0,
    columns: !0,
    flex: !0,
    flexGrow: !0,
    flexPositive: !0,
    flexShrink: !0,
    flexNegative: !0,
    flexOrder: !0,
    gridArea: !0,
    gridRow: !0,
    gridRowEnd: !0,
    gridRowSpan: !0,
    gridRowStart: !0,
    gridColumn: !0,
    gridColumnEnd: !0,
    gridColumnSpan: !0,
    gridColumnStart: !0,
    fontWeight: !0,
    lineClamp: !0,
    lineHeight: !0,
    opacity: !0,
    order: !0,
    orphans: !0,
    tabSize: !0,
    widows: !0,
    zIndex: !0,
    zoom: !0,
    fillOpacity: !0,
    floodOpacity: !0,
    stopOpacity: !0,
    strokeDasharray: !0,
    strokeDashoffset: !0,
    strokeMiterlimit: !0,
    strokeOpacity: !0,
    strokeWidth: !0
}
  , E1 = ["Webkit", "ms", "Moz", "O"];
Object.keys(Zs).forEach(function(e) {
    E1.forEach(function(t) {
        t = t + e.charAt(0).toUpperCase() + e.substring(1),
        Zs[t] = Zs[e]
    })
});
function pv(e, t, n) {
    return t == null || typeof t == "boolean" || t === "" ? "" : n || typeof t != "number" || t === 0 || Zs.hasOwnProperty(e) && Zs[e] ? ("" + t).trim() : t + "px"
}
function mv(e, t) {
    e = e.style;
    for (var n in t)
        if (t.hasOwnProperty(n)) {
            var r = n.indexOf("--") === 0
              , i = pv(n, t[n], r);
            n === "float" && (n = "cssFloat"),
            r ? e.setProperty(n, i) : e[n] = i
        }
}
var T1 = ze({
    menuitem: !0
}, {
    area: !0,
    base: !0,
    br: !0,
    col: !0,
    embed: !0,
    hr: !0,
    img: !0,
    input: !0,
    keygen: !0,
    link: !0,
    meta: !0,
    param: !0,
    source: !0,
    track: !0,
    wbr: !0
});
function ad(e, t) {
    if (t) {
        if (T1[e] && (t.children != null || t.dangerouslySetInnerHTML != null))
            throw Error(z(137, e));
        if (t.dangerouslySetInnerHTML != null) {
            if (t.children != null)
                throw Error(z(60));
            if (typeof t.dangerouslySetInnerHTML != "object" || !("__html"in t.dangerouslySetInnerHTML))
                throw Error(z(61))
        }
        if (t.style != null && typeof t.style != "object")
            throw Error(z(62))
    }
}
function ld(e, t) {
    if (e.indexOf("-") === -1)
        return typeof t.is == "string";
    switch (e) {
    case "annotation-xml":
    case "color-profile":
    case "font-face":
    case "font-face-src":
    case "font-face-uri":
    case "font-face-format":
    case "font-face-name":
    case "missing-glyph":
        return !1;
    default:
        return !0
    }
}
var cd = null;
function Ff(e) {
    return e = e.target || e.srcElement || window,
    e.correspondingUseElement && (e = e.correspondingUseElement),
    e.nodeType === 3 ? e.parentNode : e
}
var ud = null
  , es = null
  , ts = null;
function Rm(e) {
    if (e = Jo(e)) {
        if (typeof ud != "function")
            throw Error(z(280));
        var t = e.stateNode;
        t && (t = rc(t),
        ud(e.stateNode, e.type, t))
    }
}
function hv(e) {
    es ? ts ? ts.push(e) : ts = [e] : es = e
}
function gv() {
    if (es) {
        var e = es
          , t = ts;
        if (ts = es = null,
        Rm(e),
        t)
            for (e = 0; e < t.length; e++)
                Rm(t[e])
    }
}
function _v(e, t) {
    return e(t)
}
function vv() {}
var Gc = !1;
function yv(e, t, n) {
    if (Gc)
        return e(t, n);
    Gc = !0;
    try {
        return _v(e, t, n)
    } finally {
        Gc = !1,
        (es !== null || ts !== null) && (vv(),
        gv())
    }
}
function yo(e, t) {
    var n = e.stateNode;
    if (n === null)
        return null;
    var r = rc(n);
    if (r === null)
        return null;
    n = r[t];
    e: switch (t) {
    case "onClick":
    case "onClickCapture":
    case "onDoubleClick":
    case "onDoubleClickCapture":
    case "onMouseDown":
    case "onMouseDownCapture":
    case "onMouseMove":
    case "onMouseMoveCapture":
    case "onMouseUp":
    case "onMouseUpCapture":
    case "onMouseEnter":
        (r = !r.disabled) || (e = e.type,
        r = !(e === "button" || e === "input" || e === "select" || e === "textarea")),
        e = !r;
        break e;
    default:
        e = !1
    }
    if (e)
        return null;
    if (n && typeof n != "function")
        throw Error(z(231, t, typeof n));
    return n
}
var dd = !1;
if (Qn)
    try {
        var js = {};
        Object.defineProperty(js, "passive", {
            get: function() {
                dd = !0
            }
        }),
        window.addEventListener("test", js, js),
        window.removeEventListener("test", js, js)
    } catch {
        dd = !1
    }
function P1(e, t, n, r, i, s, o, l, a) {
    var c = Array.prototype.slice.call(arguments, 3);
    try {
        t.apply(n, c)
    } catch (d) {
        this.onError(d)
    }
}
var eo = !1
  , ml = null
  , hl = !1
  , fd = null
  , j1 = {
    onError: function(e) {
        eo = !0,
        ml = e
    }
};
function N1(e, t, n, r, i, s, o, l, a) {
    eo = !1,
    ml = null,
    P1.apply(j1, arguments)
}
function R1(e, t, n, r, i, s, o, l, a) {
    if (N1.apply(this, arguments),
    eo) {
        if (eo) {
            var c = ml;
            eo = !1,
            ml = null
        } else
            throw Error(z(198));
        hl || (hl = !0,
        fd = c)
    }
}
function wi(e) {
    var t = e
      , n = e;
    if (e.alternate)
        for (; t.return; )
            t = t.return;
    else {
        e = t;
        do
            t = e,
            t.flags & 4098 && (n = t.return),
            e = t.return;
        while (e)
    }
    return t.tag === 3 ? n : null
}
function wv(e) {
    if (e.tag === 13) {
        var t = e.memoizedState;
        if (t === null && (e = e.alternate,
        e !== null && (t = e.memoizedState)),
        t !== null)
            return t.dehydrated
    }
    return null
}
function Im(e) {
    if (wi(e) !== e)
        throw Error(z(188))
}
function I1(e) {
    var t = e.alternate;
    if (!t) {
        if (t = wi(e),
        t === null)
            throw Error(z(188));
        return t !== e ? null : e
    }
    for (var n = e, r = t; ; ) {
        var i = n.return;
        if (i === null)
            break;
        var s = i.alternate;
        if (s === null) {
            if (r = i.return,
            r !== null) {
                n = r;
                continue
            }
            break
        }
        if (i.child === s.child) {
            for (s = i.child; s; ) {
                if (s === n)
                    return Im(i),
                    e;
                if (s === r)
                    return Im(i),
                    t;
                s = s.sibling
            }
            throw Error(z(188))
        }
        if (n.return !== r.return)
            n = i,
            r = s;
        else {
            for (var o = !1, l = i.child; l; ) {
                if (l === n) {
                    o = !0,
                    n = i,
                    r = s;
                    break
                }
                if (l === r) {
                    o = !0,
                    r = i,
                    n = s;
                    break
                }
                l = l.sibling
            }
            if (!o) {
                for (l = s.child; l; ) {
                    if (l === n) {
                        o = !0,
                        n = s,
                        r = i;
                        break
                    }
                    if (l === r) {
                        o = !0,
                        r = s,
                        n = i;
                        break
                    }
                    l = l.sibling
                }
                if (!o)
                    throw Error(z(189))
            }
        }
        if (n.alternate !== r)
            throw Error(z(190))
    }
    if (n.tag !== 3)
        throw Error(z(188));
    return n.stateNode.current === n ? e : t
}
function xv(e) {
    return e = I1(e),
    e !== null ? bv(e) : null
}
function bv(e) {
    if (e.tag === 5 || e.tag === 6)
        return e;
    for (e = e.child; e !== null; ) {
        var t = bv(e);
        if (t !== null)
            return t;
        e = e.sibling
    }
    return null
}
var Sv = Wt.unstable_scheduleCallback
  , Lm = Wt.unstable_cancelCallback
  , L1 = Wt.unstable_shouldYield
  , M1 = Wt.unstable_requestPaint
  , Ye = Wt.unstable_now
  , O1 = Wt.unstable_getCurrentPriorityLevel
  , Vf = Wt.unstable_ImmediatePriority
  , Cv = Wt.unstable_UserBlockingPriority
  , gl = Wt.unstable_NormalPriority
  , A1 = Wt.unstable_LowPriority
  , kv = Wt.unstable_IdlePriority
  , Zl = null
  , Ln = null;
function D1(e) {
    if (Ln && typeof Ln.onCommitFiberRoot == "function")
        try {
            Ln.onCommitFiberRoot(Zl, e, void 0, (e.current.flags & 128) === 128)
        } catch {}
}
var vn = Math.clz32 ? Math.clz32 : V1
  , $1 = Math.log
  , F1 = Math.LN2;
function V1(e) {
    return e >>>= 0,
    e === 0 ? 32 : 31 - ($1(e) / F1 | 0) | 0
}
var wa = 64
  , xa = 4194304;
function Ks(e) {
    switch (e & -e) {
    case 1:
        return 1;
    case 2:
        return 2;
    case 4:
        return 4;
    case 8:
        return 8;
    case 16:
        return 16;
    case 32:
        return 32;
    case 64:
    case 128:
    case 256:
    case 512:
    case 1024:
    case 2048:
    case 4096:
    case 8192:
    case 16384:
    case 32768:
    case 65536:
    case 131072:
    case 262144:
    case 524288:
    case 1048576:
    case 2097152:
        return e & 4194240;
    case 4194304:
    case 8388608:
    case 16777216:
    case 33554432:
    case 67108864:
        return e & 130023424;
    case 134217728:
        return 134217728;
    case 268435456:
        return 268435456;
    case 536870912:
        return 536870912;
    case 1073741824:
        return 1073741824;
    default:
        return e
    }
}
function _l(e, t) {
    var n = e.pendingLanes;
    if (n === 0)
        return 0;
    var r = 0
      , i = e.suspendedLanes
      , s = e.pingedLanes
      , o = n & 268435455;
    if (o !== 0) {
        var l = o & ~i;
        l !== 0 ? r = Ks(l) : (s &= o,
        s !== 0 && (r = Ks(s)))
    } else
        o = n & ~i,
        o !== 0 ? r = Ks(o) : s !== 0 && (r = Ks(s));
    if (r === 0)
        return 0;
    if (t !== 0 && t !== r && !(t & i) && (i = r & -r,
    s = t & -t,
    i >= s || i === 16 && (s & 4194240) !== 0))
        return t;
    if (r & 4 && (r |= n & 16),
    t = e.entangledLanes,
    t !== 0)
        for (e = e.entanglements,
        t &= r; 0 < t; )
            n = 31 - vn(t),
            i = 1 << n,
            r |= e[n],
            t &= ~i;
    return r
}
function z1(e, t) {
    switch (e) {
    case 1:
    case 2:
    case 4:
        return t + 250;
    case 8:
    case 16:
    case 32:
    case 64:
    case 128:
    case 256:
    case 512:
    case 1024:
    case 2048:
    case 4096:
    case 8192:
    case 16384:
    case 32768:
    case 65536:
    case 131072:
    case 262144:
    case 524288:
    case 1048576:
    case 2097152:
        return t + 5e3;
    case 4194304:
    case 8388608:
    case 16777216:
    case 33554432:
    case 67108864:
        return -1;
    case 134217728:
    case 268435456:
    case 536870912:
    case 1073741824:
        return -1;
    default:
        return -1
    }
}
function B1(e, t) {
    for (var n = e.suspendedLanes, r = e.pingedLanes, i = e.expirationTimes, s = e.pendingLanes; 0 < s; ) {
        var o = 31 - vn(s)
          , l = 1 << o
          , a = i[o];
        a === -1 ? (!(l & n) || l & r) && (i[o] = z1(l, t)) : a <= t && (e.expiredLanes |= l),
        s &= ~l
    }
}
function pd(e) {
    return e = e.pendingLanes & -1073741825,
    e !== 0 ? e : e & 1073741824 ? 1073741824 : 0
}
function Ev() {
    var e = wa;
    return wa <<= 1,
    !(wa & 4194240) && (wa = 64),
    e
}
function Wc(e) {
    for (var t = [], n = 0; 31 > n; n++)
        t.push(e);
    return t
}
function Xo(e, t, n) {
    e.pendingLanes |= t,
    t !== 536870912 && (e.suspendedLanes = 0,
    e.pingedLanes = 0),
    e = e.eventTimes,
    t = 31 - vn(t),
    e[t] = n
}
function U1(e, t) {
    var n = e.pendingLanes & ~t;
    e.pendingLanes = t,
    e.suspendedLanes = 0,
    e.pingedLanes = 0,
    e.expiredLanes &= t,
    e.mutableReadLanes &= t,
    e.entangledLanes &= t,
    t = e.entanglements;
    var r = e.eventTimes;
    for (e = e.expirationTimes; 0 < n; ) {
        var i = 31 - vn(n)
          , s = 1 << i;
        t[i] = 0,
        r[i] = -1,
        e[i] = -1,
        n &= ~s
    }
}
function zf(e, t) {
    var n = e.entangledLanes |= t;
    for (e = e.entanglements; n; ) {
        var r = 31 - vn(n)
          , i = 1 << r;
        i & t | e[r] & t && (e[r] |= t),
        n &= ~i
    }
}
var be = 0;
function Tv(e) {
    return e &= -e,
    1 < e ? 4 < e ? e & 268435455 ? 16 : 536870912 : 4 : 1
}
var Pv, Bf, jv, Nv, Rv, md = !1, ba = [], Cr = null, kr = null, Er = null, wo = new Map, xo = new Map, vr = [], H1 = "mousedown mouseup touchcancel touchend touchstart auxclick dblclick pointercancel pointerdown pointerup dragend dragstart drop compositionend compositionstart keydown keypress keyup input textInput copy cut paste click change contextmenu reset submit".split(" ");
function Mm(e, t) {
    switch (e) {
    case "focusin":
    case "focusout":
        Cr = null;
        break;
    case "dragenter":
    case "dragleave":
        kr = null;
        break;
    case "mouseover":
    case "mouseout":
        Er = null;
        break;
    case "pointerover":
    case "pointerout":
        wo.delete(t.pointerId);
        break;
    case "gotpointercapture":
    case "lostpointercapture":
        xo.delete(t.pointerId)
    }
}
function Ns(e, t, n, r, i, s) {
    return e === null || e.nativeEvent !== s ? (e = {
        blockedOn: t,
        domEventName: n,
        eventSystemFlags: r,
        nativeEvent: s,
        targetContainers: [i]
    },
    t !== null && (t = Jo(t),
    t !== null && Bf(t)),
    e) : (e.eventSystemFlags |= r,
    t = e.targetContainers,
    i !== null && t.indexOf(i) === -1 && t.push(i),
    e)
}
function G1(e, t, n, r, i) {
    switch (t) {
    case "focusin":
        return Cr = Ns(Cr, e, t, n, r, i),
        !0;
    case "dragenter":
        return kr = Ns(kr, e, t, n, r, i),
        !0;
    case "mouseover":
        return Er = Ns(Er, e, t, n, r, i),
        !0;
    case "pointerover":
        var s = i.pointerId;
        return wo.set(s, Ns(wo.get(s) || null, e, t, n, r, i)),
        !0;
    case "gotpointercapture":
        return s = i.pointerId,
        xo.set(s, Ns(xo.get(s) || null, e, t, n, r, i)),
        !0
    }
    return !1
}
function Iv(e) {
    var t = ti(e.target);
    if (t !== null) {
        var n = wi(t);
        if (n !== null) {
            if (t = n.tag,
            t === 13) {
                if (t = wv(n),
                t !== null) {
                    e.blockedOn = t,
                    Rv(e.priority, function() {
                        jv(n)
                    });
                    return
                }
            } else if (t === 3 && n.stateNode.current.memoizedState.isDehydrated) {
                e.blockedOn = n.tag === 3 ? n.stateNode.containerInfo : null;
                return
            }
        }
    }
    e.blockedOn = null
}
function Ka(e) {
    if (e.blockedOn !== null)
        return !1;
    for (var t = e.targetContainers; 0 < t.length; ) {
        var n = hd(e.domEventName, e.eventSystemFlags, t[0], e.nativeEvent);
        if (n === null) {
            n = e.nativeEvent;
            var r = new n.constructor(n.type,n);
            cd = r,
            n.target.dispatchEvent(r),
            cd = null
        } else
            return t = Jo(n),
            t !== null && Bf(t),
            e.blockedOn = n,
            !1;
        t.shift()
    }
    return !0
}
function Om(e, t, n) {
    Ka(e) && n.delete(t)
}
function W1() {
    md = !1,
    Cr !== null && Ka(Cr) && (Cr = null),
    kr !== null && Ka(kr) && (kr = null),
    Er !== null && Ka(Er) && (Er = null),
    wo.forEach(Om),
    xo.forEach(Om)
}
function Rs(e, t) {
    e.blockedOn === t && (e.blockedOn = null,
    md || (md = !0,
    Wt.unstable_scheduleCallback(Wt.unstable_NormalPriority, W1)))
}
function bo(e) {
    function t(i) {
        return Rs(i, e)
    }
    if (0 < ba.length) {
        Rs(ba[0], e);
        for (var n = 1; n < ba.length; n++) {
            var r = ba[n];
            r.blockedOn === e && (r.blockedOn = null)
        }
    }
    for (Cr !== null && Rs(Cr, e),
    kr !== null && Rs(kr, e),
    Er !== null && Rs(Er, e),
    wo.forEach(t),
    xo.forEach(t),
    n = 0; n < vr.length; n++)
        r = vr[n],
        r.blockedOn === e && (r.blockedOn = null);
    for (; 0 < vr.length && (n = vr[0],
    n.blockedOn === null); )
        Iv(n),
        n.blockedOn === null && vr.shift()
}
var ns = nr.ReactCurrentBatchConfig
  , vl = !0;
function q1(e, t, n, r) {
    var i = be
      , s = ns.transition;
    ns.transition = null;
    try {
        be = 1,
        Uf(e, t, n, r)
    } finally {
        be = i,
        ns.transition = s
    }
}
function Y1(e, t, n, r) {
    var i = be
      , s = ns.transition;
    ns.transition = null;
    try {
        be = 4,
        Uf(e, t, n, r)
    } finally {
        be = i,
        ns.transition = s
    }
}
function Uf(e, t, n, r) {
    if (vl) {
        var i = hd(e, t, n, r);
        if (i === null)
            nu(e, t, r, yl, n),
            Mm(e, r);
        else if (G1(i, e, t, n, r))
            r.stopPropagation();
        else if (Mm(e, r),
        t & 4 && -1 < H1.indexOf(e)) {
            for (; i !== null; ) {
                var s = Jo(i);
                if (s !== null && Pv(s),
                s = hd(e, t, n, r),
                s === null && nu(e, t, r, yl, n),
                s === i)
                    break;
                i = s
            }
            i !== null && r.stopPropagation()
        } else
            nu(e, t, r, null, n)
    }
}
var yl = null;
function hd(e, t, n, r) {
    if (yl = null,
    e = Ff(r),
    e = ti(e),
    e !== null)
        if (t = wi(e),
        t === null)
            e = null;
        else if (n = t.tag,
        n === 13) {
            if (e = wv(t),
            e !== null)
                return e;
            e = null
        } else if (n === 3) {
            if (t.stateNode.current.memoizedState.isDehydrated)
                return t.tag === 3 ? t.stateNode.containerInfo : null;
            e = null
        } else
            t !== e && (e = null);
    return yl = e,
    null
}
function Lv(e) {
    switch (e) {
    case "cancel":
    case "click":
    case "close":
    case "contextmenu":
    case "copy":
    case "cut":
    case "auxclick":
    case "dblclick":
    case "dragend":
    case "dragstart":
    case "drop":
    case "focusin":
    case "focusout":
    case "input":
    case "invalid":
    case "keydown":
    case "keypress":
    case "keyup":
    case "mousedown":
    case "mouseup":
    case "paste":
    case "pause":
    case "play":
    case "pointercancel":
    case "pointerdown":
    case "pointerup":
    case "ratechange":
    case "reset":
    case "resize":
    case "seeked":
    case "submit":
    case "touchcancel":
    case "touchend":
    case "touchstart":
    case "volumechange":
    case "change":
    case "selectionchange":
    case "textInput":
    case "compositionstart":
    case "compositionend":
    case "compositionupdate":
    case "beforeblur":
    case "afterblur":
    case "beforeinput":
    case "blur":
    case "fullscreenchange":
    case "focus":
    case "hashchange":
    case "popstate":
    case "select":
    case "selectstart":
        return 1;
    case "drag":
    case "dragenter":
    case "dragexit":
    case "dragleave":
    case "dragover":
    case "mousemove":
    case "mouseout":
    case "mouseover":
    case "pointermove":
    case "pointerout":
    case "pointerover":
    case "scroll":
    case "toggle":
    case "touchmove":
    case "wheel":
    case "mouseenter":
    case "mouseleave":
    case "pointerenter":
    case "pointerleave":
        return 4;
    case "message":
        switch (O1()) {
        case Vf:
            return 1;
        case Cv:
            return 4;
        case gl:
        case A1:
            return 16;
        case kv:
            return 536870912;
        default:
            return 16
        }
    default:
        return 16
    }
}
var wr = null
  , Hf = null
  , Xa = null;
function Mv() {
    if (Xa)
        return Xa;
    var e, t = Hf, n = t.length, r, i = "value"in wr ? wr.value : wr.textContent, s = i.length;
    for (e = 0; e < n && t[e] === i[e]; e++)
        ;
    var o = n - e;
    for (r = 1; r <= o && t[n - r] === i[s - r]; r++)
        ;
    return Xa = i.slice(e, 1 < r ? 1 - r : void 0)
}
function Qa(e) {
    var t = e.keyCode;
    return "charCode"in e ? (e = e.charCode,
    e === 0 && t === 13 && (e = 13)) : e = t,
    e === 10 && (e = 13),
    32 <= e || e === 13 ? e : 0
}
function Sa() {
    return !0
}
function Am() {
    return !1
}
function Kt(e) {
    function t(n, r, i, s, o) {
        this._reactName = n,
        this._targetInst = i,
        this.type = r,
        this.nativeEvent = s,
        this.target = o,
        this.currentTarget = null;
        for (var l in e)
            e.hasOwnProperty(l) && (n = e[l],
            this[l] = n ? n(s) : s[l]);
        return this.isDefaultPrevented = (s.defaultPrevented != null ? s.defaultPrevented : s.returnValue === !1) ? Sa : Am,
        this.isPropagationStopped = Am,
        this
    }
    return ze(t.prototype, {
        preventDefault: function() {
            this.defaultPrevented = !0;
            var n = this.nativeEvent;
            n && (n.preventDefault ? n.preventDefault() : typeof n.returnValue != "unknown" && (n.returnValue = !1),
            this.isDefaultPrevented = Sa)
        },
        stopPropagation: function() {
            var n = this.nativeEvent;
            n && (n.stopPropagation ? n.stopPropagation() : typeof n.cancelBubble != "unknown" && (n.cancelBubble = !0),
            this.isPropagationStopped = Sa)
        },
        persist: function() {},
        isPersistent: Sa
    }),
    t
}
var ys = {
    eventPhase: 0,
    bubbles: 0,
    cancelable: 0,
    timeStamp: function(e) {
        return e.timeStamp || Date.now()
    },
    defaultPrevented: 0,
    isTrusted: 0
}, Gf = Kt(ys), Qo = ze({}, ys, {
    view: 0,
    detail: 0
}), K1 = Kt(Qo), qc, Yc, Is, ec = ze({}, Qo, {
    screenX: 0,
    screenY: 0,
    clientX: 0,
    clientY: 0,
    pageX: 0,
    pageY: 0,
    ctrlKey: 0,
    shiftKey: 0,
    altKey: 0,
    metaKey: 0,
    getModifierState: Wf,
    button: 0,
    buttons: 0,
    relatedTarget: function(e) {
        return e.relatedTarget === void 0 ? e.fromElement === e.srcElement ? e.toElement : e.fromElement : e.relatedTarget
    },
    movementX: function(e) {
        return "movementX"in e ? e.movementX : (e !== Is && (Is && e.type === "mousemove" ? (qc = e.screenX - Is.screenX,
        Yc = e.screenY - Is.screenY) : Yc = qc = 0,
        Is = e),
        qc)
    },
    movementY: function(e) {
        return "movementY"in e ? e.movementY : Yc
    }
}), Dm = Kt(ec), X1 = ze({}, ec, {
    dataTransfer: 0
}), Q1 = Kt(X1), J1 = ze({}, Qo, {
    relatedTarget: 0
}), Kc = Kt(J1), Z1 = ze({}, ys, {
    animationName: 0,
    elapsedTime: 0,
    pseudoElement: 0
}), eb = Kt(Z1), tb = ze({}, ys, {
    clipboardData: function(e) {
        return "clipboardData"in e ? e.clipboardData : window.clipboardData
    }
}), nb = Kt(tb), rb = ze({}, ys, {
    data: 0
}), $m = Kt(rb), ib = {
    Esc: "Escape",
    Spacebar: " ",
    Left: "ArrowLeft",
    Up: "ArrowUp",
    Right: "ArrowRight",
    Down: "ArrowDown",
    Del: "Delete",
    Win: "OS",
    Menu: "ContextMenu",
    Apps: "ContextMenu",
    Scroll: "ScrollLock",
    MozPrintableKey: "Unidentified"
}, sb = {
    8: "Backspace",
    9: "Tab",
    12: "Clear",
    13: "Enter",
    16: "Shift",
    17: "Control",
    18: "Alt",
    19: "Pause",
    20: "CapsLock",
    27: "Escape",
    32: " ",
    33: "PageUp",
    34: "PageDown",
    35: "End",
    36: "Home",
    37: "ArrowLeft",
    38: "ArrowUp",
    39: "ArrowRight",
    40: "ArrowDown",
    45: "Insert",
    46: "Delete",
    112: "F1",
    113: "F2",
    114: "F3",
    115: "F4",
    116: "F5",
    117: "F6",
    118: "F7",
    119: "F8",
    120: "F9",
    121: "F10",
    122: "F11",
    123: "F12",
    144: "NumLock",
    145: "ScrollLock",
    224: "Meta"
}, ob = {
    Alt: "altKey",
    Control: "ctrlKey",
    Meta: "metaKey",
    Shift: "shiftKey"
};
function ab(e) {
    var t = this.nativeEvent;
    return t.getModifierState ? t.getModifierState(e) : (e = ob[e]) ? !!t[e] : !1
}
function Wf() {
    return ab
}
var lb = ze({}, Qo, {
    key: function(e) {
        if (e.key) {
            var t = ib[e.key] || e.key;
            if (t !== "Unidentified")
                return t
        }
        return e.type === "keypress" ? (e = Qa(e),
        e === 13 ? "Enter" : String.fromCharCode(e)) : e.type === "keydown" || e.type === "keyup" ? sb[e.keyCode] || "Unidentified" : ""
    },
    code: 0,
    location: 0,
    ctrlKey: 0,
    shiftKey: 0,
    altKey: 0,
    metaKey: 0,
    repeat: 0,
    locale: 0,
    getModifierState: Wf,
    charCode: function(e) {
        return e.type === "keypress" ? Qa(e) : 0
    },
    keyCode: function(e) {
        return e.type === "keydown" || e.type === "keyup" ? e.keyCode : 0
    },
    which: function(e) {
        return e.type === "keypress" ? Qa(e) : e.type === "keydown" || e.type === "keyup" ? e.keyCode : 0
    }
})
  , cb = Kt(lb)
  , ub = ze({}, ec, {
    pointerId: 0,
    width: 0,
    height: 0,
    pressure: 0,
    tangentialPressure: 0,
    tiltX: 0,
    tiltY: 0,
    twist: 0,
    pointerType: 0,
    isPrimary: 0
})
  , Fm = Kt(ub)
  , db = ze({}, Qo, {
    touches: 0,
    targetTouches: 0,
    changedTouches: 0,
    altKey: 0,
    metaKey: 0,
    ctrlKey: 0,
    shiftKey: 0,
    getModifierState: Wf
})
  , fb = Kt(db)
  , pb = ze({}, ys, {
    propertyName: 0,
    elapsedTime: 0,
    pseudoElement: 0
})
  , mb = Kt(pb)
  , hb = ze({}, ec, {
    deltaX: function(e) {
        return "deltaX"in e ? e.deltaX : "wheelDeltaX"in e ? -e.wheelDeltaX : 0
    },
    deltaY: function(e) {
        return "deltaY"in e ? e.deltaY : "wheelDeltaY"in e ? -e.wheelDeltaY : "wheelDelta"in e ? -e.wheelDelta : 0
    },
    deltaZ: 0,
    deltaMode: 0
})
  , gb = Kt(hb)
  , _b = [9, 13, 27, 32]
  , qf = Qn && "CompositionEvent"in window
  , to = null;
Qn && "documentMode"in document && (to = document.documentMode);
var vb = Qn && "TextEvent"in window && !to
  , Ov = Qn && (!qf || to && 8 < to && 11 >= to)
  , Vm = String.fromCharCode(32)
  , zm = !1;
function Av(e, t) {
    switch (e) {
    case "keyup":
        return _b.indexOf(t.keyCode) !== -1;
    case "keydown":
        return t.keyCode !== 229;
    case "keypress":
    case "mousedown":
    case "focusout":
        return !0;
    default:
        return !1
    }
}
function Dv(e) {
    return e = e.detail,
    typeof e == "object" && "data"in e ? e.data : null
}
var Fi = !1;
function yb(e, t) {
    switch (e) {
    case "compositionend":
        return Dv(t);
    case "keypress":
        return t.which !== 32 ? null : (zm = !0,
        Vm);
    case "textInput":
        return e = t.data,
        e === Vm && zm ? null : e;
    default:
        return null
    }
}
function wb(e, t) {
    if (Fi)
        return e === "compositionend" || !qf && Av(e, t) ? (e = Mv(),
        Xa = Hf = wr = null,
        Fi = !1,
        e) : null;
    switch (e) {
    case "paste":
        return null;
    case "keypress":
        if (!(t.ctrlKey || t.altKey || t.metaKey) || t.ctrlKey && t.altKey) {
            if (t.char && 1 < t.char.length)
                return t.char;
            if (t.which)
                return String.fromCharCode(t.which)
        }
        return null;
    case "compositionend":
        return Ov && t.locale !== "ko" ? null : t.data;
    default:
        return null
    }
}
var xb = {
    color: !0,
    date: !0,
    datetime: !0,
    "datetime-local": !0,
    email: !0,
    month: !0,
    number: !0,
    password: !0,
    range: !0,
    search: !0,
    tel: !0,
    text: !0,
    time: !0,
    url: !0,
    week: !0
};
function Bm(e) {
    var t = e && e.nodeName && e.nodeName.toLowerCase();
    return t === "input" ? !!xb[e.type] : t === "textarea"
}
function $v(e, t, n, r) {
    hv(r),
    t = wl(t, "onChange"),
    0 < t.length && (n = new Gf("onChange","change",null,n,r),
    e.push({
        event: n,
        listeners: t
    }))
}
var no = null
  , So = null;
function bb(e) {
    Kv(e, 0)
}
function tc(e) {
    var t = Bi(e);
    if (lv(t))
        return e
}
function Sb(e, t) {
    if (e === "change")
        return t
}
var Fv = !1;
if (Qn) {
    var Xc;
    if (Qn) {
        var Qc = "oninput"in document;
        if (!Qc) {
            var Um = document.createElement("div");
            Um.setAttribute("oninput", "return;"),
            Qc = typeof Um.oninput == "function"
        }
        Xc = Qc
    } else
        Xc = !1;
    Fv = Xc && (!document.documentMode || 9 < document.documentMode)
}
function Hm() {
    no && (no.detachEvent("onpropertychange", Vv),
    So = no = null)
}
function Vv(e) {
    if (e.propertyName === "value" && tc(So)) {
        var t = [];
        $v(t, So, e, Ff(e)),
        yv(bb, t)
    }
}
function Cb(e, t, n) {
    e === "focusin" ? (Hm(),
    no = t,
    So = n,
    no.attachEvent("onpropertychange", Vv)) : e === "focusout" && Hm()
}
function kb(e) {
    if (e === "selectionchange" || e === "keyup" || e === "keydown")
        return tc(So)
}
function Eb(e, t) {
    if (e === "click")
        return tc(t)
}
function Tb(e, t) {
    if (e === "input" || e === "change")
        return tc(t)
}
function Pb(e, t) {
    return e === t && (e !== 0 || 1 / e === 1 / t) || e !== e && t !== t
}
var bn = typeof Object.is == "function" ? Object.is : Pb;
function Co(e, t) {
    if (bn(e, t))
        return !0;
    if (typeof e != "object" || e === null || typeof t != "object" || t === null)
        return !1;
    var n = Object.keys(e)
      , r = Object.keys(t);
    if (n.length !== r.length)
        return !1;
    for (r = 0; r < n.length; r++) {
        var i = n[r];
        if (!Qu.call(t, i) || !bn(e[i], t[i]))
            return !1
    }
    return !0
}
function Gm(e) {
    for (; e && e.firstChild; )
        e = e.firstChild;
    return e
}
function Wm(e, t) {
    var n = Gm(e);
    e = 0;
    for (var r; n; ) {
        if (n.nodeType === 3) {
            if (r = e + n.textContent.length,
            e <= t && r >= t)
                return {
                    node: n,
                    offset: t - e
                };
            e = r
        }
        e: {
            for (; n; ) {
                if (n.nextSibling) {
                    n = n.nextSibling;
                    break e
                }
                n = n.parentNode
            }
            n = void 0
        }
        n = Gm(n)
    }
}
function zv(e, t) {
    return e && t ? e === t ? !0 : e && e.nodeType === 3 ? !1 : t && t.nodeType === 3 ? zv(e, t.parentNode) : "contains"in e ? e.contains(t) : e.compareDocumentPosition ? !!(e.compareDocumentPosition(t) & 16) : !1 : !1
}
function Bv() {
    for (var e = window, t = pl(); t instanceof e.HTMLIFrameElement; ) {
        try {
            var n = typeof t.contentWindow.location.href == "string"
        } catch {
            n = !1
        }
        if (n)
            e = t.contentWindow;
        else
            break;
        t = pl(e.document)
    }
    return t
}
function Yf(e) {
    var t = e && e.nodeName && e.nodeName.toLowerCase();
    return t && (t === "input" && (e.type === "text" || e.type === "search" || e.type === "tel" || e.type === "url" || e.type === "password") || t === "textarea" || e.contentEditable === "true")
}
function jb(e) {
    var t = Bv()
      , n = e.focusedElem
      , r = e.selectionRange;
    if (t !== n && n && n.ownerDocument && zv(n.ownerDocument.documentElement, n)) {
        if (r !== null && Yf(n)) {
            if (t = r.start,
            e = r.end,
            e === void 0 && (e = t),
            "selectionStart"in n)
                n.selectionStart = t,
                n.selectionEnd = Math.min(e, n.value.length);
            else if (e = (t = n.ownerDocument || document) && t.defaultView || window,
            e.getSelection) {
                e = e.getSelection();
                var i = n.textContent.length
                  , s = Math.min(r.start, i);
                r = r.end === void 0 ? s : Math.min(r.end, i),
                !e.extend && s > r && (i = r,
                r = s,
                s = i),
                i = Wm(n, s);
                var o = Wm(n, r);
                i && o && (e.rangeCount !== 1 || e.anchorNode !== i.node || e.anchorOffset !== i.offset || e.focusNode !== o.node || e.focusOffset !== o.offset) && (t = t.createRange(),
                t.setStart(i.node, i.offset),
                e.removeAllRanges(),
                s > r ? (e.addRange(t),
                e.extend(o.node, o.offset)) : (t.setEnd(o.node, o.offset),
                e.addRange(t)))
            }
        }
        for (t = [],
        e = n; e = e.parentNode; )
            e.nodeType === 1 && t.push({
                element: e,
                left: e.scrollLeft,
                top: e.scrollTop
            });
        for (typeof n.focus == "function" && n.focus(),
        n = 0; n < t.length; n++)
            e = t[n],
            e.element.scrollLeft = e.left,
            e.element.scrollTop = e.top
    }
}
var Nb = Qn && "documentMode"in document && 11 >= document.documentMode
  , Vi = null
  , gd = null
  , ro = null
  , _d = !1;
function qm(e, t, n) {
    var r = n.window === n ? n.document : n.nodeType === 9 ? n : n.ownerDocument;
    _d || Vi == null || Vi !== pl(r) || (r = Vi,
    "selectionStart"in r && Yf(r) ? r = {
        start: r.selectionStart,
        end: r.selectionEnd
    } : (r = (r.ownerDocument && r.ownerDocument.defaultView || window).getSelection(),
    r = {
        anchorNode: r.anchorNode,
        anchorOffset: r.anchorOffset,
        focusNode: r.focusNode,
        focusOffset: r.focusOffset
    }),
    ro && Co(ro, r) || (ro = r,
    r = wl(gd, "onSelect"),
    0 < r.length && (t = new Gf("onSelect","select",null,t,n),
    e.push({
        event: t,
        listeners: r
    }),
    t.target = Vi)))
}
function Ca(e, t) {
    var n = {};
    return n[e.toLowerCase()] = t.toLowerCase(),
    n["Webkit" + e] = "webkit" + t,
    n["Moz" + e] = "moz" + t,
    n
}
var zi = {
    animationend: Ca("Animation", "AnimationEnd"),
    animationiteration: Ca("Animation", "AnimationIteration"),
    animationstart: Ca("Animation", "AnimationStart"),
    transitionend: Ca("Transition", "TransitionEnd")
}
  , Jc = {}
  , Uv = {};
Qn && (Uv = document.createElement("div").style,
"AnimationEvent"in window || (delete zi.animationend.animation,
delete zi.animationiteration.animation,
delete zi.animationstart.animation),
"TransitionEvent"in window || delete zi.transitionend.transition);
function nc(e) {
    if (Jc[e])
        return Jc[e];
    if (!zi[e])
        return e;
    var t = zi[e], n;
    for (n in t)
        if (t.hasOwnProperty(n) && n in Uv)
            return Jc[e] = t[n];
    return e
}
var Hv = nc("animationend")
  , Gv = nc("animationiteration")
  , Wv = nc("animationstart")
  , qv = nc("transitionend")
  , Yv = new Map
  , Ym = "abort auxClick cancel canPlay canPlayThrough click close contextMenu copy cut drag dragEnd dragEnter dragExit dragLeave dragOver dragStart drop durationChange emptied encrypted ended error gotPointerCapture input invalid keyDown keyPress keyUp load loadedData loadedMetadata loadStart lostPointerCapture mouseDown mouseMove mouseOut mouseOver mouseUp paste pause play playing pointerCancel pointerDown pointerMove pointerOut pointerOver pointerUp progress rateChange reset resize seeked seeking stalled submit suspend timeUpdate touchCancel touchEnd touchStart volumeChange scroll toggle touchMove waiting wheel".split(" ");
function Vr(e, t) {
    Yv.set(e, t),
    yi(t, [e])
}
for (var Zc = 0; Zc < Ym.length; Zc++) {
    var eu = Ym[Zc]
      , Rb = eu.toLowerCase()
      , Ib = eu[0].toUpperCase() + eu.slice(1);
    Vr(Rb, "on" + Ib)
}
Vr(Hv, "onAnimationEnd");
Vr(Gv, "onAnimationIteration");
Vr(Wv, "onAnimationStart");
Vr("dblclick", "onDoubleClick");
Vr("focusin", "onFocus");
Vr("focusout", "onBlur");
Vr(qv, "onTransitionEnd");
os("onMouseEnter", ["mouseout", "mouseover"]);
os("onMouseLeave", ["mouseout", "mouseover"]);
os("onPointerEnter", ["pointerout", "pointerover"]);
os("onPointerLeave", ["pointerout", "pointerover"]);
yi("onChange", "change click focusin focusout input keydown keyup selectionchange".split(" "));
yi("onSelect", "focusout contextmenu dragend focusin keydown keyup mousedown mouseup selectionchange".split(" "));
yi("onBeforeInput", ["compositionend", "keypress", "textInput", "paste"]);
yi("onCompositionEnd", "compositionend focusout keydown keypress keyup mousedown".split(" "));
yi("onCompositionStart", "compositionstart focusout keydown keypress keyup mousedown".split(" "));
yi("onCompositionUpdate", "compositionupdate focusout keydown keypress keyup mousedown".split(" "));
var Xs = "abort canplay canplaythrough durationchange emptied encrypted ended error loadeddata loadedmetadata loadstart pause play playing progress ratechange resize seeked seeking stalled suspend timeupdate volumechange waiting".split(" ")
  , Lb = new Set("cancel close invalid load scroll toggle".split(" ").concat(Xs));
function Km(e, t, n) {
    var r = e.type || "unknown-event";
    e.currentTarget = n,
    R1(r, t, void 0, e),
    e.currentTarget = null
}
function Kv(e, t) {
    t = (t & 4) !== 0;
    for (var n = 0; n < e.length; n++) {
        var r = e[n]
          , i = r.event;
        r = r.listeners;
        e: {
            var s = void 0;
            if (t)
                for (var o = r.length - 1; 0 <= o; o--) {
                    var l = r[o]
                      , a = l.instance
                      , c = l.currentTarget;
                    if (l = l.listener,
                    a !== s && i.isPropagationStopped())
                        break e;
                    Km(i, l, c),
                    s = a
                }
            else
                for (o = 0; o < r.length; o++) {
                    if (l = r[o],
                    a = l.instance,
                    c = l.currentTarget,
                    l = l.listener,
                    a !== s && i.isPropagationStopped())
                        break e;
                    Km(i, l, c),
                    s = a
                }
        }
    }
    if (hl)
        throw e = fd,
        hl = !1,
        fd = null,
        e
}
function Re(e, t) {
    var n = t[bd];
    n === void 0 && (n = t[bd] = new Set);
    var r = e + "__bubble";
    n.has(r) || (Xv(t, e, 2, !1),
    n.add(r))
}
function tu(e, t, n) {
    var r = 0;
    t && (r |= 4),
    Xv(n, e, r, t)
}
var ka = "_reactListening" + Math.random().toString(36).slice(2);
function ko(e) {
    if (!e[ka]) {
        e[ka] = !0,
        rv.forEach(function(n) {
            n !== "selectionchange" && (Lb.has(n) || tu(n, !1, e),
            tu(n, !0, e))
        });
        var t = e.nodeType === 9 ? e : e.ownerDocument;
        t === null || t[ka] || (t[ka] = !0,
        tu("selectionchange", !1, t))
    }
}
function Xv(e, t, n, r) {
    switch (Lv(t)) {
    case 1:
        var i = q1;
        break;
    case 4:
        i = Y1;
        break;
    default:
        i = Uf
    }
    n = i.bind(null, t, n, e),
    i = void 0,
    !dd || t !== "touchstart" && t !== "touchmove" && t !== "wheel" || (i = !0),
    r ? i !== void 0 ? e.addEventListener(t, n, {
        capture: !0,
        passive: i
    }) : e.addEventListener(t, n, !0) : i !== void 0 ? e.addEventListener(t, n, {
        passive: i
    }) : e.addEventListener(t, n, !1)
}
function nu(e, t, n, r, i) {
    var s = r;
    if (!(t & 1) && !(t & 2) && r !== null)
        e: for (; ; ) {
            if (r === null)
                return;
            var o = r.tag;
            if (o === 3 || o === 4) {
                var l = r.stateNode.containerInfo;
                if (l === i || l.nodeType === 8 && l.parentNode === i)
                    break;
                if (o === 4)
                    for (o = r.return; o !== null; ) {
                        var a = o.tag;
                        if ((a === 3 || a === 4) && (a = o.stateNode.containerInfo,
                        a === i || a.nodeType === 8 && a.parentNode === i))
                            return;
                        o = o.return
                    }
                for (; l !== null; ) {
                    if (o = ti(l),
                    o === null)
                        return;
                    if (a = o.tag,
                    a === 5 || a === 6) {
                        r = s = o;
                        continue e
                    }
                    l = l.parentNode
                }
            }
            r = r.return
        }
    yv(function() {
        var c = s
          , d = Ff(n)
          , f = [];
        e: {
            var p = Yv.get(e);
            if (p !== void 0) {
                var h = Gf
                  , m = e;
                switch (e) {
                case "keypress":
                    if (Qa(n) === 0)
                        break e;
                case "keydown":
                case "keyup":
                    h = cb;
                    break;
                case "focusin":
                    m = "focus",
                    h = Kc;
                    break;
                case "focusout":
                    m = "blur",
                    h = Kc;
                    break;
                case "beforeblur":
                case "afterblur":
                    h = Kc;
                    break;
                case "click":
                    if (n.button === 2)
                        break e;
                case "auxclick":
                case "dblclick":
                case "mousedown":
                case "mousemove":
                case "mouseup":
                case "mouseout":
                case "mouseover":
                case "contextmenu":
                    h = Dm;
                    break;
                case "drag":
                case "dragend":
                case "dragenter":
                case "dragexit":
                case "dragleave":
                case "dragover":
                case "dragstart":
                case "drop":
                    h = Q1;
                    break;
                case "touchcancel":
                case "touchend":
                case "touchmove":
                case "touchstart":
                    h = fb;
                    break;
                case Hv:
                case Gv:
                case Wv:
                    h = eb;
                    break;
                case qv:
                    h = mb;
                    break;
                case "scroll":
                    h = K1;
                    break;
                case "wheel":
                    h = gb;
                    break;
                case "copy":
                case "cut":
                case "paste":
                    h = nb;
                    break;
                case "gotpointercapture":
                case "lostpointercapture":
                case "pointercancel":
                case "pointerdown":
                case "pointermove":
                case "pointerout":
                case "pointerover":
                case "pointerup":
                    h = Fm
                }
                var _ = (t & 4) !== 0
                  , x = !_ && e === "scroll"
                  , v = _ ? p !== null ? p + "Capture" : null : p;
                _ = [];
                for (var w = c, y; w !== null; ) {
                    y = w;
                    var b = y.stateNode;
                    if (y.tag === 5 && b !== null && (y = b,
                    v !== null && (b = yo(w, v),
                    b != null && _.push(Eo(w, b, y)))),
                    x)
                        break;
                    w = w.return
                }
                0 < _.length && (p = new h(p,m,null,n,d),
                f.push({
                    event: p,
                    listeners: _
                }))
            }
        }
        if (!(t & 7)) {
            e: {
                if (p = e === "mouseover" || e === "pointerover",
                h = e === "mouseout" || e === "pointerout",
                p && n !== cd && (m = n.relatedTarget || n.fromElement) && (ti(m) || m[Jn]))
                    break e;
                if ((h || p) && (p = d.window === d ? d : (p = d.ownerDocument) ? p.defaultView || p.parentWindow : window,
                h ? (m = n.relatedTarget || n.toElement,
                h = c,
                m = m ? ti(m) : null,
                m !== null && (x = wi(m),
                m !== x || m.tag !== 5 && m.tag !== 6) && (m = null)) : (h = null,
                m = c),
                h !== m)) {
                    if (_ = Dm,
                    b = "onMouseLeave",
                    v = "onMouseEnter",
                    w = "mouse",
                    (e === "pointerout" || e === "pointerover") && (_ = Fm,
                    b = "onPointerLeave",
                    v = "onPointerEnter",
                    w = "pointer"),
                    x = h == null ? p : Bi(h),
                    y = m == null ? p : Bi(m),
                    p = new _(b,w + "leave",h,n,d),
                    p.target = x,
                    p.relatedTarget = y,
                    b = null,
                    ti(d) === c && (_ = new _(v,w + "enter",m,n,d),
                    _.target = y,
                    _.relatedTarget = x,
                    b = _),
                    x = b,
                    h && m)
                        t: {
                            for (_ = h,
                            v = m,
                            w = 0,
                            y = _; y; y = Li(y))
                                w++;
                            for (y = 0,
                            b = v; b; b = Li(b))
                                y++;
                            for (; 0 < w - y; )
                                _ = Li(_),
                                w--;
                            for (; 0 < y - w; )
                                v = Li(v),
                                y--;
                            for (; w--; ) {
                                if (_ === v || v !== null && _ === v.alternate)
                                    break t;
                                _ = Li(_),
                                v = Li(v)
                            }
                            _ = null
                        }
                    else
                        _ = null;
                    h !== null && Xm(f, p, h, _, !1),
                    m !== null && x !== null && Xm(f, x, m, _, !0)
                }
            }
            e: {
                if (p = c ? Bi(c) : window,
                h = p.nodeName && p.nodeName.toLowerCase(),
                h === "select" || h === "input" && p.type === "file")
                    var C = Sb;
                else if (Bm(p))
                    if (Fv)
                        C = Tb;
                    else {
                        C = kb;
                        var S = Cb
                    }
                else
                    (h = p.nodeName) && h.toLowerCase() === "input" && (p.type === "checkbox" || p.type === "radio") && (C = Eb);
                if (C && (C = C(e, c))) {
                    $v(f, C, n, d);
                    break e
                }
                S && S(e, p, c),
                e === "focusout" && (S = p._wrapperState) && S.controlled && p.type === "number" && id(p, "number", p.value)
            }
            switch (S = c ? Bi(c) : window,
            e) {
            case "focusin":
                (Bm(S) || S.contentEditable === "true") && (Vi = S,
                gd = c,
                ro = null);
                break;
            case "focusout":
                ro = gd = Vi = null;
                break;
            case "mousedown":
                _d = !0;
                break;
            case "contextmenu":
            case "mouseup":
            case "dragend":
                _d = !1,
                qm(f, n, d);
                break;
            case "selectionchange":
                if (Nb)
                    break;
            case "keydown":
            case "keyup":
                qm(f, n, d)
            }
            var T;
            if (qf)
                e: {
                    switch (e) {
                    case "compositionstart":
                        var k = "onCompositionStart";
                        break e;
                    case "compositionend":
                        k = "onCompositionEnd";
                        break e;
                    case "compositionupdate":
                        k = "onCompositionUpdate";
                        break e
                    }
                    k = void 0
                }
            else
                Fi ? Av(e, n) && (k = "onCompositionEnd") : e === "keydown" && n.keyCode === 229 && (k = "onCompositionStart");
            k && (Ov && n.locale !== "ko" && (Fi || k !== "onCompositionStart" ? k === "onCompositionEnd" && Fi && (T = Mv()) : (wr = d,
            Hf = "value"in wr ? wr.value : wr.textContent,
            Fi = !0)),
            S = wl(c, k),
            0 < S.length && (k = new $m(k,e,null,n,d),
            f.push({
                event: k,
                listeners: S
            }),
            T ? k.data = T : (T = Dv(n),
            T !== null && (k.data = T)))),
            (T = vb ? yb(e, n) : wb(e, n)) && (c = wl(c, "onBeforeInput"),
            0 < c.length && (d = new $m("onBeforeInput","beforeinput",null,n,d),
            f.push({
                event: d,
                listeners: c
            }),
            d.data = T))
        }
        Kv(f, t)
    })
}
function Eo(e, t, n) {
    return {
        instance: e,
        listener: t,
        currentTarget: n
    }
}
function wl(e, t) {
    for (var n = t + "Capture", r = []; e !== null; ) {
        var i = e
          , s = i.stateNode;
        i.tag === 5 && s !== null && (i = s,
        s = yo(e, n),
        s != null && r.unshift(Eo(e, s, i)),
        s = yo(e, t),
        s != null && r.push(Eo(e, s, i))),
        e = e.return
    }
    return r
}
function Li(e) {
    if (e === null)
        return null;
    do
        e = e.return;
    while (e && e.tag !== 5);
    return e || null
}
function Xm(e, t, n, r, i) {
    for (var s = t._reactName, o = []; n !== null && n !== r; ) {
        var l = n
          , a = l.alternate
          , c = l.stateNode;
        if (a !== null && a === r)
            break;
        l.tag === 5 && c !== null && (l = c,
        i ? (a = yo(n, s),
        a != null && o.unshift(Eo(n, a, l))) : i || (a = yo(n, s),
        a != null && o.push(Eo(n, a, l)))),
        n = n.return
    }
    o.length !== 0 && e.push({
        event: t,
        listeners: o
    })
}
var Mb = /\r\n?/g
  , Ob = /\u0000|\uFFFD/g;
function Qm(e) {
    return (typeof e == "string" ? e : "" + e).replace(Mb, `
`).replace(Ob, "")
}
function Ea(e, t, n) {
    if (t = Qm(t),
    Qm(e) !== t && n)
        throw Error(z(425))
}
function xl() {}
var vd = null
  , yd = null;
function wd(e, t) {
    return e === "textarea" || e === "noscript" || typeof t.children == "string" || typeof t.children == "number" || typeof t.dangerouslySetInnerHTML == "object" && t.dangerouslySetInnerHTML !== null && t.dangerouslySetInnerHTML.__html != null
}
var xd = typeof setTimeout == "function" ? setTimeout : void 0
  , Ab = typeof clearTimeout == "function" ? clearTimeout : void 0
  , Jm = typeof Promise == "function" ? Promise : void 0
  , Db = typeof queueMicrotask == "function" ? queueMicrotask : typeof Jm < "u" ? function(e) {
    return Jm.resolve(null).then(e).catch($b)
}
: xd;
function $b(e) {
    setTimeout(function() {
        throw e
    })
}
function ru(e, t) {
    var n = t
      , r = 0;
    do {
        var i = n.nextSibling;
        if (e.removeChild(n),
        i && i.nodeType === 8)
            if (n = i.data,
            n === "/$") {
                if (r === 0) {
                    e.removeChild(i),
                    bo(t);
                    return
                }
                r--
            } else
                n !== "$" && n !== "$?" && n !== "$!" || r++;
        n = i
    } while (n);
    bo(t)
}
function Tr(e) {
    for (; e != null; e = e.nextSibling) {
        var t = e.nodeType;
        if (t === 1 || t === 3)
            break;
        if (t === 8) {
            if (t = e.data,
            t === "$" || t === "$!" || t === "$?")
                break;
            if (t === "/$")
                return null
        }
    }
    return e
}
function Zm(e) {
    e = e.previousSibling;
    for (var t = 0; e; ) {
        if (e.nodeType === 8) {
            var n = e.data;
            if (n === "$" || n === "$!" || n === "$?") {
                if (t === 0)
                    return e;
                t--
            } else
                n === "/$" && t++
        }
        e = e.previousSibling
    }
    return null
}
var ws = Math.random().toString(36).slice(2)
  , Rn = "__reactFiber$" + ws
  , To = "__reactProps$" + ws
  , Jn = "__reactContainer$" + ws
  , bd = "__reactEvents$" + ws
  , Fb = "__reactListeners$" + ws
  , Vb = "__reactHandles$" + ws;
function ti(e) {
    var t = e[Rn];
    if (t)
        return t;
    for (var n = e.parentNode; n; ) {
        if (t = n[Jn] || n[Rn]) {
            if (n = t.alternate,
            t.child !== null || n !== null && n.child !== null)
                for (e = Zm(e); e !== null; ) {
                    if (n = e[Rn])
                        return n;
                    e = Zm(e)
                }
            return t
        }
        e = n,
        n = e.parentNode
    }
    return null
}
function Jo(e) {
    return e = e[Rn] || e[Jn],
    !e || e.tag !== 5 && e.tag !== 6 && e.tag !== 13 && e.tag !== 3 ? null : e
}
function Bi(e) {
    if (e.tag === 5 || e.tag === 6)
        return e.stateNode;
    throw Error(z(33))
}
function rc(e) {
    return e[To] || null
}
var Sd = []
  , Ui = -1;
function zr(e) {
    return {
        current: e
    }
}
function Le(e) {
    0 > Ui || (e.current = Sd[Ui],
    Sd[Ui] = null,
    Ui--)
}
function je(e, t) {
    Ui++,
    Sd[Ui] = e.current,
    e.current = t
}
var Ar = {}
  , yt = zr(Ar)
  , Nt = zr(!1)
  , ui = Ar;
function as(e, t) {
    var n = e.type.contextTypes;
    if (!n)
        return Ar;
    var r = e.stateNode;
    if (r && r.__reactInternalMemoizedUnmaskedChildContext === t)
        return r.__reactInternalMemoizedMaskedChildContext;
    var i = {}, s;
    for (s in n)
        i[s] = t[s];
    return r && (e = e.stateNode,
    e.__reactInternalMemoizedUnmaskedChildContext = t,
    e.__reactInternalMemoizedMaskedChildContext = i),
    i
}
function Rt(e) {
    return e = e.childContextTypes,
    e != null
}
function bl() {
    Le(Nt),
    Le(yt)
}
function eh(e, t, n) {
    if (yt.current !== Ar)
        throw Error(z(168));
    je(yt, t),
    je(Nt, n)
}
function Qv(e, t, n) {
    var r = e.stateNode;
    if (t = t.childContextTypes,
    typeof r.getChildContext != "function")
        return n;
    r = r.getChildContext();
    for (var i in r)
        if (!(i in t))
            throw Error(z(108, C1(e) || "Unknown", i));
    return ze({}, n, r)
}
function Sl(e) {
    return e = (e = e.stateNode) && e.__reactInternalMemoizedMergedChildContext || Ar,
    ui = yt.current,
    je(yt, e),
    je(Nt, Nt.current),
    !0
}
function th(e, t, n) {
    var r = e.stateNode;
    if (!r)
        throw Error(z(169));
    n ? (e = Qv(e, t, ui),
    r.__reactInternalMemoizedMergedChildContext = e,
    Le(Nt),
    Le(yt),
    je(yt, e)) : Le(Nt),
    je(Nt, n)
}
var Un = null
  , ic = !1
  , iu = !1;
function Jv(e) {
    Un === null ? Un = [e] : Un.push(e)
}
function zb(e) {
    ic = !0,
    Jv(e)
}
function Br() {
    if (!iu && Un !== null) {
        iu = !0;
        var e = 0
          , t = be;
        try {
            var n = Un;
            for (be = 1; e < n.length; e++) {
                var r = n[e];
                do
                    r = r(!0);
                while (r !== null)
            }
            Un = null,
            ic = !1
        } catch (i) {
            throw Un !== null && (Un = Un.slice(e + 1)),
            Sv(Vf, Br),
            i
        } finally {
            be = t,
            iu = !1
        }
    }
    return null
}
var Hi = []
  , Gi = 0
  , Cl = null
  , kl = 0
  , tn = []
  , nn = 0
  , di = null
  , Hn = 1
  , Gn = "";
function Kr(e, t) {
    Hi[Gi++] = kl,
    Hi[Gi++] = Cl,
    Cl = e,
    kl = t
}
function Zv(e, t, n) {
    tn[nn++] = Hn,
    tn[nn++] = Gn,
    tn[nn++] = di,
    di = e;
    var r = Hn;
    e = Gn;
    var i = 32 - vn(r) - 1;
    r &= ~(1 << i),
    n += 1;
    var s = 32 - vn(t) + i;
    if (30 < s) {
        var o = i - i % 5;
        s = (r & (1 << o) - 1).toString(32),
        r >>= o,
        i -= o,
        Hn = 1 << 32 - vn(t) + i | n << i | r,
        Gn = s + e
    } else
        Hn = 1 << s | n << i | r,
        Gn = e
}
function Kf(e) {
    e.return !== null && (Kr(e, 1),
    Zv(e, 1, 0))
}
function Xf(e) {
    for (; e === Cl; )
        Cl = Hi[--Gi],
        Hi[Gi] = null,
        kl = Hi[--Gi],
        Hi[Gi] = null;
    for (; e === di; )
        di = tn[--nn],
        tn[nn] = null,
        Gn = tn[--nn],
        tn[nn] = null,
        Hn = tn[--nn],
        tn[nn] = null
}
var Ht = null
  , Ut = null
  , Ae = !1
  , _n = null;
function e0(e, t) {
    var n = rn(5, null, null, 0);
    n.elementType = "DELETED",
    n.stateNode = t,
    n.return = e,
    t = e.deletions,
    t === null ? (e.deletions = [n],
    e.flags |= 16) : t.push(n)
}
function nh(e, t) {
    switch (e.tag) {
    case 5:
        var n = e.type;
        return t = t.nodeType !== 1 || n.toLowerCase() !== t.nodeName.toLowerCase() ? null : t,
        t !== null ? (e.stateNode = t,
        Ht = e,
        Ut = Tr(t.firstChild),
        !0) : !1;
    case 6:
        return t = e.pendingProps === "" || t.nodeType !== 3 ? null : t,
        t !== null ? (e.stateNode = t,
        Ht = e,
        Ut = null,
        !0) : !1;
    case 13:
        return t = t.nodeType !== 8 ? null : t,
        t !== null ? (n = di !== null ? {
            id: Hn,
            overflow: Gn
        } : null,
        e.memoizedState = {
            dehydrated: t,
            treeContext: n,
            retryLane: 1073741824
        },
        n = rn(18, null, null, 0),
        n.stateNode = t,
        n.return = e,
        e.child = n,
        Ht = e,
        Ut = null,
        !0) : !1;
    default:
        return !1
    }
}
function Cd(e) {
    return (e.mode & 1) !== 0 && (e.flags & 128) === 0
}
function kd(e) {
    if (Ae) {
        var t = Ut;
        if (t) {
            var n = t;
            if (!nh(e, t)) {
                if (Cd(e))
                    throw Error(z(418));
                t = Tr(n.nextSibling);
                var r = Ht;
                t && nh(e, t) ? e0(r, n) : (e.flags = e.flags & -4097 | 2,
                Ae = !1,
                Ht = e)
            }
        } else {
            if (Cd(e))
                throw Error(z(418));
            e.flags = e.flags & -4097 | 2,
            Ae = !1,
            Ht = e
        }
    }
}
function rh(e) {
    for (e = e.return; e !== null && e.tag !== 5 && e.tag !== 3 && e.tag !== 13; )
        e = e.return;
    Ht = e
}
function Ta(e) {
    if (e !== Ht)
        return !1;
    if (!Ae)
        return rh(e),
        Ae = !0,
        !1;
    var t;
    if ((t = e.tag !== 3) && !(t = e.tag !== 5) && (t = e.type,
    t = t !== "head" && t !== "body" && !wd(e.type, e.memoizedProps)),
    t && (t = Ut)) {
        if (Cd(e))
            throw t0(),
            Error(z(418));
        for (; t; )
            e0(e, t),
            t = Tr(t.nextSibling)
    }
    if (rh(e),
    e.tag === 13) {
        if (e = e.memoizedState,
        e = e !== null ? e.dehydrated : null,
        !e)
            throw Error(z(317));
        e: {
            for (e = e.nextSibling,
            t = 0; e; ) {
                if (e.nodeType === 8) {
                    var n = e.data;
                    if (n === "/$") {
                        if (t === 0) {
                            Ut = Tr(e.nextSibling);
                            break e
                        }
                        t--
                    } else
                        n !== "$" && n !== "$!" && n !== "$?" || t++
                }
                e = e.nextSibling
            }
            Ut = null
        }
    } else
        Ut = Ht ? Tr(e.stateNode.nextSibling) : null;
    return !0
}
function t0() {
    for (var e = Ut; e; )
        e = Tr(e.nextSibling)
}
function ls() {
    Ut = Ht = null,
    Ae = !1
}
function Qf(e) {
    _n === null ? _n = [e] : _n.push(e)
}
var Bb = nr.ReactCurrentBatchConfig;
function Ls(e, t, n) {
    if (e = n.ref,
    e !== null && typeof e != "function" && typeof e != "object") {
        if (n._owner) {
            if (n = n._owner,
            n) {
                if (n.tag !== 1)
                    throw Error(z(309));
                var r = n.stateNode
            }
            if (!r)
                throw Error(z(147, e));
            var i = r
              , s = "" + e;
            return t !== null && t.ref !== null && typeof t.ref == "function" && t.ref._stringRef === s ? t.ref : (t = function(o) {
                var l = i.refs;
                o === null ? delete l[s] : l[s] = o
            }
            ,
            t._stringRef = s,
            t)
        }
        if (typeof e != "string")
            throw Error(z(284));
        if (!n._owner)
            throw Error(z(290, e))
    }
    return e
}
function Pa(e, t) {
    throw e = Object.prototype.toString.call(t),
    Error(z(31, e === "[object Object]" ? "object with keys {" + Object.keys(t).join(", ") + "}" : e))
}
function ih(e) {
    var t = e._init;
    return t(e._payload)
}
function n0(e) {
    function t(v, w) {
        if (e) {
            var y = v.deletions;
            y === null ? (v.deletions = [w],
            v.flags |= 16) : y.push(w)
        }
    }
    function n(v, w) {
        if (!e)
            return null;
        for (; w !== null; )
            t(v, w),
            w = w.sibling;
        return null
    }
    function r(v, w) {
        for (v = new Map; w !== null; )
            w.key !== null ? v.set(w.key, w) : v.set(w.index, w),
            w = w.sibling;
        return v
    }
    function i(v, w) {
        return v = Rr(v, w),
        v.index = 0,
        v.sibling = null,
        v
    }
    function s(v, w, y) {
        return v.index = y,
        e ? (y = v.alternate,
        y !== null ? (y = y.index,
        y < w ? (v.flags |= 2,
        w) : y) : (v.flags |= 2,
        w)) : (v.flags |= 1048576,
        w)
    }
    function o(v) {
        return e && v.alternate === null && (v.flags |= 2),
        v
    }
    function l(v, w, y, b) {
        return w === null || w.tag !== 6 ? (w = du(y, v.mode, b),
        w.return = v,
        w) : (w = i(w, y),
        w.return = v,
        w)
    }
    function a(v, w, y, b) {
        var C = y.type;
        return C === $i ? d(v, w, y.props.children, b, y.key) : w !== null && (w.elementType === C || typeof C == "object" && C !== null && C.$$typeof === hr && ih(C) === w.type) ? (b = i(w, y.props),
        b.ref = Ls(v, w, y),
        b.return = v,
        b) : (b = il(y.type, y.key, y.props, null, v.mode, b),
        b.ref = Ls(v, w, y),
        b.return = v,
        b)
    }
    function c(v, w, y, b) {
        return w === null || w.tag !== 4 || w.stateNode.containerInfo !== y.containerInfo || w.stateNode.implementation !== y.implementation ? (w = fu(y, v.mode, b),
        w.return = v,
        w) : (w = i(w, y.children || []),
        w.return = v,
        w)
    }
    function d(v, w, y, b, C) {
        return w === null || w.tag !== 7 ? (w = ci(y, v.mode, b, C),
        w.return = v,
        w) : (w = i(w, y),
        w.return = v,
        w)
    }
    function f(v, w, y) {
        if (typeof w == "string" && w !== "" || typeof w == "number")
            return w = du("" + w, v.mode, y),
            w.return = v,
            w;
        if (typeof w == "object" && w !== null) {
            switch (w.$$typeof) {
            case _a:
                return y = il(w.type, w.key, w.props, null, v.mode, y),
                y.ref = Ls(v, null, w),
                y.return = v,
                y;
            case Di:
                return w = fu(w, v.mode, y),
                w.return = v,
                w;
            case hr:
                var b = w._init;
                return f(v, b(w._payload), y)
            }
            if (Ys(w) || Ps(w))
                return w = ci(w, v.mode, y, null),
                w.return = v,
                w;
            Pa(v, w)
        }
        return null
    }
    function p(v, w, y, b) {
        var C = w !== null ? w.key : null;
        if (typeof y == "string" && y !== "" || typeof y == "number")
            return C !== null ? null : l(v, w, "" + y, b);
        if (typeof y == "object" && y !== null) {
            switch (y.$$typeof) {
            case _a:
                return y.key === C ? a(v, w, y, b) : null;
            case Di:
                return y.key === C ? c(v, w, y, b) : null;
            case hr:
                return C = y._init,
                p(v, w, C(y._payload), b)
            }
            if (Ys(y) || Ps(y))
                return C !== null ? null : d(v, w, y, b, null);
            Pa(v, y)
        }
        return null
    }
    function h(v, w, y, b, C) {
        if (typeof b == "string" && b !== "" || typeof b == "number")
            return v = v.get(y) || null,
            l(w, v, "" + b, C);
        if (typeof b == "object" && b !== null) {
            switch (b.$$typeof) {
            case _a:
                return v = v.get(b.key === null ? y : b.key) || null,
                a(w, v, b, C);
            case Di:
                return v = v.get(b.key === null ? y : b.key) || null,
                c(w, v, b, C);
            case hr:
                var S = b._init;
                return h(v, w, y, S(b._payload), C)
            }
            if (Ys(b) || Ps(b))
                return v = v.get(y) || null,
                d(w, v, b, C, null);
            Pa(w, b)
        }
        return null
    }
    function m(v, w, y, b) {
        for (var C = null, S = null, T = w, k = w = 0, P = null; T !== null && k < y.length; k++) {
            T.index > k ? (P = T,
            T = null) : P = T.sibling;
            var R = p(v, T, y[k], b);
            if (R === null) {
                T === null && (T = P);
                break
            }
            e && T && R.alternate === null && t(v, T),
            w = s(R, w, k),
            S === null ? C = R : S.sibling = R,
            S = R,
            T = P
        }
        if (k === y.length)
            return n(v, T),
            Ae && Kr(v, k),
            C;
        if (T === null) {
            for (; k < y.length; k++)
                T = f(v, y[k], b),
                T !== null && (w = s(T, w, k),
                S === null ? C = T : S.sibling = T,
                S = T);
            return Ae && Kr(v, k),
            C
        }
        for (T = r(v, T); k < y.length; k++)
            P = h(T, v, k, y[k], b),
            P !== null && (e && P.alternate !== null && T.delete(P.key === null ? k : P.key),
            w = s(P, w, k),
            S === null ? C = P : S.sibling = P,
            S = P);
        return e && T.forEach(function(A) {
            return t(v, A)
        }),
        Ae && Kr(v, k),
        C
    }
    function _(v, w, y, b) {
        var C = Ps(y);
        if (typeof C != "function")
            throw Error(z(150));
        if (y = C.call(y),
        y == null)
            throw Error(z(151));
        for (var S = C = null, T = w, k = w = 0, P = null, R = y.next(); T !== null && !R.done; k++,
        R = y.next()) {
            T.index > k ? (P = T,
            T = null) : P = T.sibling;
            var A = p(v, T, R.value, b);
            if (A === null) {
                T === null && (T = P);
                break
            }
            e && T && A.alternate === null && t(v, T),
            w = s(A, w, k),
            S === null ? C = A : S.sibling = A,
            S = A,
            T = P
        }
        if (R.done)
            return n(v, T),
            Ae && Kr(v, k),
            C;
        if (T === null) {
            for (; !R.done; k++,
            R = y.next())
                R = f(v, R.value, b),
                R !== null && (w = s(R, w, k),
                S === null ? C = R : S.sibling = R,
                S = R);
            return Ae && Kr(v, k),
            C
        }
        for (T = r(v, T); !R.done; k++,
        R = y.next())
            R = h(T, v, k, R.value, b),
            R !== null && (e && R.alternate !== null && T.delete(R.key === null ? k : R.key),
            w = s(R, w, k),
            S === null ? C = R : S.sibling = R,
            S = R);
        return e && T.forEach(function(V) {
            return t(v, V)
        }),
        Ae && Kr(v, k),
        C
    }
    function x(v, w, y, b) {
        if (typeof y == "object" && y !== null && y.type === $i && y.key === null && (y = y.props.children),
        typeof y == "object" && y !== null) {
            switch (y.$$typeof) {
            case _a:
                e: {
                    for (var C = y.key, S = w; S !== null; ) {
                        if (S.key === C) {
                            if (C = y.type,
                            C === $i) {
                                if (S.tag === 7) {
                                    n(v, S.sibling),
                                    w = i(S, y.props.children),
                                    w.return = v,
                                    v = w;
                                    break e
                                }
                            } else if (S.elementType === C || typeof C == "object" && C !== null && C.$$typeof === hr && ih(C) === S.type) {
                                n(v, S.sibling),
                                w = i(S, y.props),
                                w.ref = Ls(v, S, y),
                                w.return = v,
                                v = w;
                                break e
                            }
                            n(v, S);
                            break
                        } else
                            t(v, S);
                        S = S.sibling
                    }
                    y.type === $i ? (w = ci(y.props.children, v.mode, b, y.key),
                    w.return = v,
                    v = w) : (b = il(y.type, y.key, y.props, null, v.mode, b),
                    b.ref = Ls(v, w, y),
                    b.return = v,
                    v = b)
                }
                return o(v);
            case Di:
                e: {
                    for (S = y.key; w !== null; ) {
                        if (w.key === S)
                            if (w.tag === 4 && w.stateNode.containerInfo === y.containerInfo && w.stateNode.implementation === y.implementation) {
                                n(v, w.sibling),
                                w = i(w, y.children || []),
                                w.return = v,
                                v = w;
                                break e
                            } else {
                                n(v, w);
                                break
                            }
                        else
                            t(v, w);
                        w = w.sibling
                    }
                    w = fu(y, v.mode, b),
                    w.return = v,
                    v = w
                }
                return o(v);
            case hr:
                return S = y._init,
                x(v, w, S(y._payload), b)
            }
            if (Ys(y))
                return m(v, w, y, b);
            if (Ps(y))
                return _(v, w, y, b);
            Pa(v, y)
        }
        return typeof y == "string" && y !== "" || typeof y == "number" ? (y = "" + y,
        w !== null && w.tag === 6 ? (n(v, w.sibling),
        w = i(w, y),
        w.return = v,
        v = w) : (n(v, w),
        w = du(y, v.mode, b),
        w.return = v,
        v = w),
        o(v)) : n(v, w)
    }
    return x
}
var cs = n0(!0)
  , r0 = n0(!1)
  , El = zr(null)
  , Tl = null
  , Wi = null
  , Jf = null;
function Zf() {
    Jf = Wi = Tl = null
}
function ep(e) {
    var t = El.current;
    Le(El),
    e._currentValue = t
}
function Ed(e, t, n) {
    for (; e !== null; ) {
        var r = e.alternate;
        if ((e.childLanes & t) !== t ? (e.childLanes |= t,
        r !== null && (r.childLanes |= t)) : r !== null && (r.childLanes & t) !== t && (r.childLanes |= t),
        e === n)
            break;
        e = e.return
    }
}
function rs(e, t) {
    Tl = e,
    Jf = Wi = null,
    e = e.dependencies,
    e !== null && e.firstContext !== null && (e.lanes & t && (jt = !0),
    e.firstContext = null)
}
function an(e) {
    var t = e._currentValue;
    if (Jf !== e)
        if (e = {
            context: e,
            memoizedValue: t,
            next: null
        },
        Wi === null) {
            if (Tl === null)
                throw Error(z(308));
            Wi = e,
            Tl.dependencies = {
                lanes: 0,
                firstContext: e
            }
        } else
            Wi = Wi.next = e;
    return t
}
var ni = null;
function tp(e) {
    ni === null ? ni = [e] : ni.push(e)
}
function i0(e, t, n, r) {
    var i = t.interleaved;
    return i === null ? (n.next = n,
    tp(t)) : (n.next = i.next,
    i.next = n),
    t.interleaved = n,
    Zn(e, r)
}
function Zn(e, t) {
    e.lanes |= t;
    var n = e.alternate;
    for (n !== null && (n.lanes |= t),
    n = e,
    e = e.return; e !== null; )
        e.childLanes |= t,
        n = e.alternate,
        n !== null && (n.childLanes |= t),
        n = e,
        e = e.return;
    return n.tag === 3 ? n.stateNode : null
}
var gr = !1;
function np(e) {
    e.updateQueue = {
        baseState: e.memoizedState,
        firstBaseUpdate: null,
        lastBaseUpdate: null,
        shared: {
            pending: null,
            interleaved: null,
            lanes: 0
        },
        effects: null
    }
}
function s0(e, t) {
    e = e.updateQueue,
    t.updateQueue === e && (t.updateQueue = {
        baseState: e.baseState,
        firstBaseUpdate: e.firstBaseUpdate,
        lastBaseUpdate: e.lastBaseUpdate,
        shared: e.shared,
        effects: e.effects
    })
}
function qn(e, t) {
    return {
        eventTime: e,
        lane: t,
        tag: 0,
        payload: null,
        callback: null,
        next: null
    }
}
function Pr(e, t, n) {
    var r = e.updateQueue;
    if (r === null)
        return null;
    if (r = r.shared,
    ve & 2) {
        var i = r.pending;
        return i === null ? t.next = t : (t.next = i.next,
        i.next = t),
        r.pending = t,
        Zn(e, n)
    }
    return i = r.interleaved,
    i === null ? (t.next = t,
    tp(r)) : (t.next = i.next,
    i.next = t),
    r.interleaved = t,
    Zn(e, n)
}
function Ja(e, t, n) {
    if (t = t.updateQueue,
    t !== null && (t = t.shared,
    (n & 4194240) !== 0)) {
        var r = t.lanes;
        r &= e.pendingLanes,
        n |= r,
        t.lanes = n,
        zf(e, n)
    }
}
function sh(e, t) {
    var n = e.updateQueue
      , r = e.alternate;
    if (r !== null && (r = r.updateQueue,
    n === r)) {
        var i = null
          , s = null;
        if (n = n.firstBaseUpdate,
        n !== null) {
            do {
                var o = {
                    eventTime: n.eventTime,
                    lane: n.lane,
                    tag: n.tag,
                    payload: n.payload,
                    callback: n.callback,
                    next: null
                };
                s === null ? i = s = o : s = s.next = o,
                n = n.next
            } while (n !== null);
            s === null ? i = s = t : s = s.next = t
        } else
            i = s = t;
        n = {
            baseState: r.baseState,
            firstBaseUpdate: i,
            lastBaseUpdate: s,
            shared: r.shared,
            effects: r.effects
        },
        e.updateQueue = n;
        return
    }
    e = n.lastBaseUpdate,
    e === null ? n.firstBaseUpdate = t : e.next = t,
    n.lastBaseUpdate = t
}
function Pl(e, t, n, r) {
    var i = e.updateQueue;
    gr = !1;
    var s = i.firstBaseUpdate
      , o = i.lastBaseUpdate
      , l = i.shared.pending;
    if (l !== null) {
        i.shared.pending = null;
        var a = l
          , c = a.next;
        a.next = null,
        o === null ? s = c : o.next = c,
        o = a;
        var d = e.alternate;
        d !== null && (d = d.updateQueue,
        l = d.lastBaseUpdate,
        l !== o && (l === null ? d.firstBaseUpdate = c : l.next = c,
        d.lastBaseUpdate = a))
    }
    if (s !== null) {
        var f = i.baseState;
        o = 0,
        d = c = a = null,
        l = s;
        do {
            var p = l.lane
              , h = l.eventTime;
            if ((r & p) === p) {
                d !== null && (d = d.next = {
                    eventTime: h,
                    lane: 0,
                    tag: l.tag,
                    payload: l.payload,
                    callback: l.callback,
                    next: null
                });
                e: {
                    var m = e
                      , _ = l;
                    switch (p = t,
                    h = n,
                    _.tag) {
                    case 1:
                        if (m = _.payload,
                        typeof m == "function") {
                            f = m.call(h, f, p);
                            break e
                        }
                        f = m;
                        break e;
                    case 3:
                        m.flags = m.flags & -65537 | 128;
                    case 0:
                        if (m = _.payload,
                        p = typeof m == "function" ? m.call(h, f, p) : m,
                        p == null)
                            break e;
                        f = ze({}, f, p);
                        break e;
                    case 2:
                        gr = !0
                    }
                }
                l.callback !== null && l.lane !== 0 && (e.flags |= 64,
                p = i.effects,
                p === null ? i.effects = [l] : p.push(l))
            } else
                h = {
                    eventTime: h,
                    lane: p,
                    tag: l.tag,
                    payload: l.payload,
                    callback: l.callback,
                    next: null
                },
                d === null ? (c = d = h,
                a = f) : d = d.next = h,
                o |= p;
            if (l = l.next,
            l === null) {
                if (l = i.shared.pending,
                l === null)
                    break;
                p = l,
                l = p.next,
                p.next = null,
                i.lastBaseUpdate = p,
                i.shared.pending = null
            }
        } while (1);
        if (d === null && (a = f),
        i.baseState = a,
        i.firstBaseUpdate = c,
        i.lastBaseUpdate = d,
        t = i.shared.interleaved,
        t !== null) {
            i = t;
            do
                o |= i.lane,
                i = i.next;
            while (i !== t)
        } else
            s === null && (i.shared.lanes = 0);
        pi |= o,
        e.lanes = o,
        e.memoizedState = f
    }
}
function oh(e, t, n) {
    if (e = t.effects,
    t.effects = null,
    e !== null)
        for (t = 0; t < e.length; t++) {
            var r = e[t]
              , i = r.callback;
            if (i !== null) {
                if (r.callback = null,
                r = n,
                typeof i != "function")
                    throw Error(z(191, i));
                i.call(r)
            }
        }
}
var Zo = {}
  , Mn = zr(Zo)
  , Po = zr(Zo)
  , jo = zr(Zo);
function ri(e) {
    if (e === Zo)
        throw Error(z(174));
    return e
}
function rp(e, t) {
    switch (je(jo, t),
    je(Po, e),
    je(Mn, Zo),
    e = t.nodeType,
    e) {
    case 9:
    case 11:
        t = (t = t.documentElement) ? t.namespaceURI : od(null, "");
        break;
    default:
        e = e === 8 ? t.parentNode : t,
        t = e.namespaceURI || null,
        e = e.tagName,
        t = od(t, e)
    }
    Le(Mn),
    je(Mn, t)
}
function us() {
    Le(Mn),
    Le(Po),
    Le(jo)
}
function o0(e) {
    ri(jo.current);
    var t = ri(Mn.current)
      , n = od(t, e.type);
    t !== n && (je(Po, e),
    je(Mn, n))
}
function ip(e) {
    Po.current === e && (Le(Mn),
    Le(Po))
}
var $e = zr(0);
function jl(e) {
    for (var t = e; t !== null; ) {
        if (t.tag === 13) {
            var n = t.memoizedState;
            if (n !== null && (n = n.dehydrated,
            n === null || n.data === "$?" || n.data === "$!"))
                return t
        } else if (t.tag === 19 && t.memoizedProps.revealOrder !== void 0) {
            if (t.flags & 128)
                return t
        } else if (t.child !== null) {
            t.child.return = t,
            t = t.child;
            continue
        }
        if (t === e)
            break;
        for (; t.sibling === null; ) {
            if (t.return === null || t.return === e)
                return null;
            t = t.return
        }
        t.sibling.return = t.return,
        t = t.sibling
    }
    return null
}
var su = [];
function sp() {
    for (var e = 0; e < su.length; e++)
        su[e]._workInProgressVersionPrimary = null;
    su.length = 0
}
var Za = nr.ReactCurrentDispatcher
  , ou = nr.ReactCurrentBatchConfig
  , fi = 0
  , Ve = null
  , nt = null
  , it = null
  , Nl = !1
  , io = !1
  , No = 0
  , Ub = 0;
function mt() {
    throw Error(z(321))
}
function op(e, t) {
    if (t === null)
        return !1;
    for (var n = 0; n < t.length && n < e.length; n++)
        if (!bn(e[n], t[n]))
            return !1;
    return !0
}
function ap(e, t, n, r, i, s) {
    if (fi = s,
    Ve = t,
    t.memoizedState = null,
    t.updateQueue = null,
    t.lanes = 0,
    Za.current = e === null || e.memoizedState === null ? qb : Yb,
    e = n(r, i),
    io) {
        s = 0;
        do {
            if (io = !1,
            No = 0,
            25 <= s)
                throw Error(z(301));
            s += 1,
            it = nt = null,
            t.updateQueue = null,
            Za.current = Kb,
            e = n(r, i)
        } while (io)
    }
    if (Za.current = Rl,
    t = nt !== null && nt.next !== null,
    fi = 0,
    it = nt = Ve = null,
    Nl = !1,
    t)
        throw Error(z(300));
    return e
}
function lp() {
    var e = No !== 0;
    return No = 0,
    e
}
function Nn() {
    var e = {
        memoizedState: null,
        baseState: null,
        baseQueue: null,
        queue: null,
        next: null
    };
    return it === null ? Ve.memoizedState = it = e : it = it.next = e,
    it
}
function ln() {
    if (nt === null) {
        var e = Ve.alternate;
        e = e !== null ? e.memoizedState : null
    } else
        e = nt.next;
    var t = it === null ? Ve.memoizedState : it.next;
    if (t !== null)
        it = t,
        nt = e;
    else {
        if (e === null)
            throw Error(z(310));
        nt = e,
        e = {
            memoizedState: nt.memoizedState,
            baseState: nt.baseState,
            baseQueue: nt.baseQueue,
            queue: nt.queue,
            next: null
        },
        it === null ? Ve.memoizedState = it = e : it = it.next = e
    }
    return it
}
function Ro(e, t) {
    return typeof t == "function" ? t(e) : t
}
function au(e) {
    var t = ln()
      , n = t.queue;
    if (n === null)
        throw Error(z(311));
    n.lastRenderedReducer = e;
    var r = nt
      , i = r.baseQueue
      , s = n.pending;
    if (s !== null) {
        if (i !== null) {
            var o = i.next;
            i.next = s.next,
            s.next = o
        }
        r.baseQueue = i = s,
        n.pending = null
    }
    if (i !== null) {
        s = i.next,
        r = r.baseState;
        var l = o = null
          , a = null
          , c = s;
        do {
            var d = c.lane;
            if ((fi & d) === d)
                a !== null && (a = a.next = {
                    lane: 0,
                    action: c.action,
                    hasEagerState: c.hasEagerState,
                    eagerState: c.eagerState,
                    next: null
                }),
                r = c.hasEagerState ? c.eagerState : e(r, c.action);
            else {
                var f = {
                    lane: d,
                    action: c.action,
                    hasEagerState: c.hasEagerState,
                    eagerState: c.eagerState,
                    next: null
                };
                a === null ? (l = a = f,
                o = r) : a = a.next = f,
                Ve.lanes |= d,
                pi |= d
            }
            c = c.next
        } while (c !== null && c !== s);
        a === null ? o = r : a.next = l,
        bn(r, t.memoizedState) || (jt = !0),
        t.memoizedState = r,
        t.baseState = o,
        t.baseQueue = a,
        n.lastRenderedState = r
    }
    if (e = n.interleaved,
    e !== null) {
        i = e;
        do
            s = i.lane,
            Ve.lanes |= s,
            pi |= s,
            i = i.next;
        while (i !== e)
    } else
        i === null && (n.lanes = 0);
    return [t.memoizedState, n.dispatch]
}
function lu(e) {
    var t = ln()
      , n = t.queue;
    if (n === null)
        throw Error(z(311));
    n.lastRenderedReducer = e;
    var r = n.dispatch
      , i = n.pending
      , s = t.memoizedState;
    if (i !== null) {
        n.pending = null;
        var o = i = i.next;
        do
            s = e(s, o.action),
            o = o.next;
        while (o !== i);
        bn(s, t.memoizedState) || (jt = !0),
        t.memoizedState = s,
        t.baseQueue === null && (t.baseState = s),
        n.lastRenderedState = s
    }
    return [s, r]
}
function a0() {}
function l0(e, t) {
    var n = Ve
      , r = ln()
      , i = t()
      , s = !bn(r.memoizedState, i);
    if (s && (r.memoizedState = i,
    jt = !0),
    r = r.queue,
    cp(d0.bind(null, n, r, e), [e]),
    r.getSnapshot !== t || s || it !== null && it.memoizedState.tag & 1) {
        if (n.flags |= 2048,
        Io(9, u0.bind(null, n, r, i, t), void 0, null),
        st === null)
            throw Error(z(349));
        fi & 30 || c0(n, t, i)
    }
    return i
}
function c0(e, t, n) {
    e.flags |= 16384,
    e = {
        getSnapshot: t,
        value: n
    },
    t = Ve.updateQueue,
    t === null ? (t = {
        lastEffect: null,
        stores: null
    },
    Ve.updateQueue = t,
    t.stores = [e]) : (n = t.stores,
    n === null ? t.stores = [e] : n.push(e))
}
function u0(e, t, n, r) {
    t.value = n,
    t.getSnapshot = r,
    f0(t) && p0(e)
}
function d0(e, t, n) {
    return n(function() {
        f0(t) && p0(e)
    })
}
function f0(e) {
    var t = e.getSnapshot;
    e = e.value;
    try {
        var n = t();
        return !bn(e, n)
    } catch {
        return !0
    }
}
function p0(e) {
    var t = Zn(e, 1);
    t !== null && yn(t, e, 1, -1)
}
function ah(e) {
    var t = Nn();
    return typeof e == "function" && (e = e()),
    t.memoizedState = t.baseState = e,
    e = {
        pending: null,
        interleaved: null,
        lanes: 0,
        dispatch: null,
        lastRenderedReducer: Ro,
        lastRenderedState: e
    },
    t.queue = e,
    e = e.dispatch = Wb.bind(null, Ve, e),
    [t.memoizedState, e]
}
function Io(e, t, n, r) {
    return e = {
        tag: e,
        create: t,
        destroy: n,
        deps: r,
        next: null
    },
    t = Ve.updateQueue,
    t === null ? (t = {
        lastEffect: null,
        stores: null
    },
    Ve.updateQueue = t,
    t.lastEffect = e.next = e) : (n = t.lastEffect,
    n === null ? t.lastEffect = e.next = e : (r = n.next,
    n.next = e,
    e.next = r,
    t.lastEffect = e)),
    e
}
function m0() {
    return ln().memoizedState
}
function el(e, t, n, r) {
    var i = Nn();
    Ve.flags |= e,
    i.memoizedState = Io(1 | t, n, void 0, r === void 0 ? null : r)
}
function sc(e, t, n, r) {
    var i = ln();
    r = r === void 0 ? null : r;
    var s = void 0;
    if (nt !== null) {
        var o = nt.memoizedState;
        if (s = o.destroy,
        r !== null && op(r, o.deps)) {
            i.memoizedState = Io(t, n, s, r);
            return
        }
    }
    Ve.flags |= e,
    i.memoizedState = Io(1 | t, n, s, r)
}
function lh(e, t) {
    return el(8390656, 8, e, t)
}
function cp(e, t) {
    return sc(2048, 8, e, t)
}
function h0(e, t) {
    return sc(4, 2, e, t)
}
function g0(e, t) {
    return sc(4, 4, e, t)
}
function _0(e, t) {
    if (typeof t == "function")
        return e = e(),
        t(e),
        function() {
            t(null)
        }
        ;
    if (t != null)
        return e = e(),
        t.current = e,
        function() {
            t.current = null
        }
}
function v0(e, t, n) {
    return n = n != null ? n.concat([e]) : null,
    sc(4, 4, _0.bind(null, t, e), n)
}
function up() {}
function y0(e, t) {
    var n = ln();
    t = t === void 0 ? null : t;
    var r = n.memoizedState;
    return r !== null && t !== null && op(t, r[1]) ? r[0] : (n.memoizedState = [e, t],
    e)
}
function w0(e, t) {
    var n = ln();
    t = t === void 0 ? null : t;
    var r = n.memoizedState;
    return r !== null && t !== null && op(t, r[1]) ? r[0] : (e = e(),
    n.memoizedState = [e, t],
    e)
}
function x0(e, t, n) {
    return fi & 21 ? (bn(n, t) || (n = Ev(),
    Ve.lanes |= n,
    pi |= n,
    e.baseState = !0),
    t) : (e.baseState && (e.baseState = !1,
    jt = !0),
    e.memoizedState = n)
}
function Hb(e, t) {
    var n = be;
    be = n !== 0 && 4 > n ? n : 4,
    e(!0);
    var r = ou.transition;
    ou.transition = {};
    try {
        e(!1),
        t()
    } finally {
        be = n,
        ou.transition = r
    }
}
function b0() {
    return ln().memoizedState
}
function Gb(e, t, n) {
    var r = Nr(e);
    if (n = {
        lane: r,
        action: n,
        hasEagerState: !1,
        eagerState: null,
        next: null
    },
    S0(e))
        C0(t, n);
    else if (n = i0(e, t, n, r),
    n !== null) {
        var i = Ct();
        yn(n, e, r, i),
        k0(n, t, r)
    }
}
function Wb(e, t, n) {
    var r = Nr(e)
      , i = {
        lane: r,
        action: n,
        hasEagerState: !1,
        eagerState: null,
        next: null
    };
    if (S0(e))
        C0(t, i);
    else {
        var s = e.alternate;
        if (e.lanes === 0 && (s === null || s.lanes === 0) && (s = t.lastRenderedReducer,
        s !== null))
            try {
                var o = t.lastRenderedState
                  , l = s(o, n);
                if (i.hasEagerState = !0,
                i.eagerState = l,
                bn(l, o)) {
                    var a = t.interleaved;
                    a === null ? (i.next = i,
                    tp(t)) : (i.next = a.next,
                    a.next = i),
                    t.interleaved = i;
                    return
                }
            } catch {} finally {}
        n = i0(e, t, i, r),
        n !== null && (i = Ct(),
        yn(n, e, r, i),
        k0(n, t, r))
    }
}
function S0(e) {
    var t = e.alternate;
    return e === Ve || t !== null && t === Ve
}
function C0(e, t) {
    io = Nl = !0;
    var n = e.pending;
    n === null ? t.next = t : (t.next = n.next,
    n.next = t),
    e.pending = t
}
function k0(e, t, n) {
    if (n & 4194240) {
        var r = t.lanes;
        r &= e.pendingLanes,
        n |= r,
        t.lanes = n,
        zf(e, n)
    }
}
var Rl = {
    readContext: an,
    useCallback: mt,
    useContext: mt,
    useEffect: mt,
    useImperativeHandle: mt,
    useInsertionEffect: mt,
    useLayoutEffect: mt,
    useMemo: mt,
    useReducer: mt,
    useRef: mt,
    useState: mt,
    useDebugValue: mt,
    useDeferredValue: mt,
    useTransition: mt,
    useMutableSource: mt,
    useSyncExternalStore: mt,
    useId: mt,
    unstable_isNewReconciler: !1
}
  , qb = {
    readContext: an,
    useCallback: function(e, t) {
        return Nn().memoizedState = [e, t === void 0 ? null : t],
        e
    },
    useContext: an,
    useEffect: lh,
    useImperativeHandle: function(e, t, n) {
        return n = n != null ? n.concat([e]) : null,
        el(4194308, 4, _0.bind(null, t, e), n)
    },
    useLayoutEffect: function(e, t) {
        return el(4194308, 4, e, t)
    },
    useInsertionEffect: function(e, t) {
        return el(4, 2, e, t)
    },
    useMemo: function(e, t) {
        var n = Nn();
        return t = t === void 0 ? null : t,
        e = e(),
        n.memoizedState = [e, t],
        e
    },
    useReducer: function(e, t, n) {
        var r = Nn();
        return t = n !== void 0 ? n(t) : t,
        r.memoizedState = r.baseState = t,
        e = {
            pending: null,
            interleaved: null,
            lanes: 0,
            dispatch: null,
            lastRenderedReducer: e,
            lastRenderedState: t
        },
        r.queue = e,
        e = e.dispatch = Gb.bind(null, Ve, e),
        [r.memoizedState, e]
    },
    useRef: function(e) {
        var t = Nn();
        return e = {
            current: e
        },
        t.memoizedState = e
    },
    useState: ah,
    useDebugValue: up,
    useDeferredValue: function(e) {
        return Nn().memoizedState = e
    },
    useTransition: function() {
        var e = ah(!1)
          , t = e[0];
        return e = Hb.bind(null, e[1]),
        Nn().memoizedState = e,
        [t, e]
    },
    useMutableSource: function() {},
    useSyncExternalStore: function(e, t, n) {
        var r = Ve
          , i = Nn();
        if (Ae) {
            if (n === void 0)
                throw Error(z(407));
            n = n()
        } else {
            if (n = t(),
            st === null)
                throw Error(z(349));
            fi & 30 || c0(r, t, n)
        }
        i.memoizedState = n;
        var s = {
            value: n,
            getSnapshot: t
        };
        return i.queue = s,
        lh(d0.bind(null, r, s, e), [e]),
        r.flags |= 2048,
        Io(9, u0.bind(null, r, s, n, t), void 0, null),
        n
    },
    useId: function() {
        var e = Nn()
          , t = st.identifierPrefix;
        if (Ae) {
            var n = Gn
              , r = Hn;
            n = (r & ~(1 << 32 - vn(r) - 1)).toString(32) + n,
            t = ":" + t + "R" + n,
            n = No++,
            0 < n && (t += "H" + n.toString(32)),
            t += ":"
        } else
            n = Ub++,
            t = ":" + t + "r" + n.toString(32) + ":";
        return e.memoizedState = t
    },
    unstable_isNewReconciler: !1
}
  , Yb = {
    readContext: an,
    useCallback: y0,
    useContext: an,
    useEffect: cp,
    useImperativeHandle: v0,
    useInsertionEffect: h0,
    useLayoutEffect: g0,
    useMemo: w0,
    useReducer: au,
    useRef: m0,
    useState: function() {
        return au(Ro)
    },
    useDebugValue: up,
    useDeferredValue: function(e) {
        var t = ln();
        return x0(t, nt.memoizedState, e)
    },
    useTransition: function() {
        var e = au(Ro)[0]
          , t = ln().memoizedState;
        return [e, t]
    },
    useMutableSource: a0,
    useSyncExternalStore: l0,
    useId: b0,
    unstable_isNewReconciler: !1
}
  , Kb = {
    readContext: an,
    useCallback: y0,
    useContext: an,
    useEffect: cp,
    useImperativeHandle: v0,
    useInsertionEffect: h0,
    useLayoutEffect: g0,
    useMemo: w0,
    useReducer: lu,
    useRef: m0,
    useState: function() {
        return lu(Ro)
    },
    useDebugValue: up,
    useDeferredValue: function(e) {
        var t = ln();
        return nt === null ? t.memoizedState = e : x0(t, nt.memoizedState, e)
    },
    useTransition: function() {
        var e = lu(Ro)[0]
          , t = ln().memoizedState;
        return [e, t]
    },
    useMutableSource: a0,
    useSyncExternalStore: l0,
    useId: b0,
    unstable_isNewReconciler: !1
};
function mn(e, t) {
    if (e && e.defaultProps) {
        t = ze({}, t),
        e = e.defaultProps;
        for (var n in e)
            t[n] === void 0 && (t[n] = e[n]);
        return t
    }
    return t
}
function Td(e, t, n, r) {
    t = e.memoizedState,
    n = n(r, t),
    n = n == null ? t : ze({}, t, n),
    e.memoizedState = n,
    e.lanes === 0 && (e.updateQueue.baseState = n)
}
var oc = {
    isMounted: function(e) {
        return (e = e._reactInternals) ? wi(e) === e : !1
    },
    enqueueSetState: function(e, t, n) {
        e = e._reactInternals;
        var r = Ct()
          , i = Nr(e)
          , s = qn(r, i);
        s.payload = t,
        n != null && (s.callback = n),
        t = Pr(e, s, i),
        t !== null && (yn(t, e, i, r),
        Ja(t, e, i))
    },
    enqueueReplaceState: function(e, t, n) {
        e = e._reactInternals;
        var r = Ct()
          , i = Nr(e)
          , s = qn(r, i);
        s.tag = 1,
        s.payload = t,
        n != null && (s.callback = n),
        t = Pr(e, s, i),
        t !== null && (yn(t, e, i, r),
        Ja(t, e, i))
    },
    enqueueForceUpdate: function(e, t) {
        e = e._reactInternals;
        var n = Ct()
          , r = Nr(e)
          , i = qn(n, r);
        i.tag = 2,
        t != null && (i.callback = t),
        t = Pr(e, i, r),
        t !== null && (yn(t, e, r, n),
        Ja(t, e, r))
    }
};
function ch(e, t, n, r, i, s, o) {
    return e = e.stateNode,
    typeof e.shouldComponentUpdate == "function" ? e.shouldComponentUpdate(r, s, o) : t.prototype && t.prototype.isPureReactComponent ? !Co(n, r) || !Co(i, s) : !0
}
function E0(e, t, n) {
    var r = !1
      , i = Ar
      , s = t.contextType;
    return typeof s == "object" && s !== null ? s = an(s) : (i = Rt(t) ? ui : yt.current,
    r = t.contextTypes,
    s = (r = r != null) ? as(e, i) : Ar),
    t = new t(n,s),
    e.memoizedState = t.state !== null && t.state !== void 0 ? t.state : null,
    t.updater = oc,
    e.stateNode = t,
    t._reactInternals = e,
    r && (e = e.stateNode,
    e.__reactInternalMemoizedUnmaskedChildContext = i,
    e.__reactInternalMemoizedMaskedChildContext = s),
    t
}
function uh(e, t, n, r) {
    e = t.state,
    typeof t.componentWillReceiveProps == "function" && t.componentWillReceiveProps(n, r),
    typeof t.UNSAFE_componentWillReceiveProps == "function" && t.UNSAFE_componentWillReceiveProps(n, r),
    t.state !== e && oc.enqueueReplaceState(t, t.state, null)
}
function Pd(e, t, n, r) {
    var i = e.stateNode;
    i.props = n,
    i.state = e.memoizedState,
    i.refs = {},
    np(e);
    var s = t.contextType;
    typeof s == "object" && s !== null ? i.context = an(s) : (s = Rt(t) ? ui : yt.current,
    i.context = as(e, s)),
    i.state = e.memoizedState,
    s = t.getDerivedStateFromProps,
    typeof s == "function" && (Td(e, t, s, n),
    i.state = e.memoizedState),
    typeof t.getDerivedStateFromProps == "function" || typeof i.getSnapshotBeforeUpdate == "function" || typeof i.UNSAFE_componentWillMount != "function" && typeof i.componentWillMount != "function" || (t = i.state,
    typeof i.componentWillMount == "function" && i.componentWillMount(),
    typeof i.UNSAFE_componentWillMount == "function" && i.UNSAFE_componentWillMount(),
    t !== i.state && oc.enqueueReplaceState(i, i.state, null),
    Pl(e, n, i, r),
    i.state = e.memoizedState),
    typeof i.componentDidMount == "function" && (e.flags |= 4194308)
}
function ds(e, t) {
    try {
        var n = ""
          , r = t;
        do
            n += S1(r),
            r = r.return;
        while (r);
        var i = n
    } catch (s) {
        i = `
Error generating stack: ` + s.message + `
` + s.stack
    }
    return {
        value: e,
        source: t,
        stack: i,
        digest: null
    }
}
function cu(e, t, n) {
    return {
        value: e,
        source: null,
        stack: n ?? null,
        digest: t ?? null
    }
}
function jd(e, t) {
    try {
        console.error(t.value)
    } catch (n) {
        setTimeout(function() {
            throw n
        })
    }
}
var Xb = typeof WeakMap == "function" ? WeakMap : Map;
function T0(e, t, n) {
    n = qn(-1, n),
    n.tag = 3,
    n.payload = {
        element: null
    };
    var r = t.value;
    return n.callback = function() {
        Ll || (Ll = !0,
        Fd = r),
        jd(e, t)
    }
    ,
    n
}
function P0(e, t, n) {
    n = qn(-1, n),
    n.tag = 3;
    var r = e.type.getDerivedStateFromError;
    if (typeof r == "function") {
        var i = t.value;
        n.payload = function() {
            return r(i)
        }
        ,
        n.callback = function() {
            jd(e, t)
        }
    }
    var s = e.stateNode;
    return s !== null && typeof s.componentDidCatch == "function" && (n.callback = function() {
        jd(e, t),
        typeof r != "function" && (jr === null ? jr = new Set([this]) : jr.add(this));
        var o = t.stack;
        this.componentDidCatch(t.value, {
            componentStack: o !== null ? o : ""
        })
    }
    ),
    n
}
function dh(e, t, n) {
    var r = e.pingCache;
    if (r === null) {
        r = e.pingCache = new Xb;
        var i = new Set;
        r.set(t, i)
    } else
        i = r.get(t),
        i === void 0 && (i = new Set,
        r.set(t, i));
    i.has(n) || (i.add(n),
    e = uS.bind(null, e, t, n),
    t.then(e, e))
}
function fh(e) {
    do {
        var t;
        if ((t = e.tag === 13) && (t = e.memoizedState,
        t = t !== null ? t.dehydrated !== null : !0),
        t)
            return e;
        e = e.return
    } while (e !== null);
    return null
}
function ph(e, t, n, r, i) {
    return e.mode & 1 ? (e.flags |= 65536,
    e.lanes = i,
    e) : (e === t ? e.flags |= 65536 : (e.flags |= 128,
    n.flags |= 131072,
    n.flags &= -52805,
    n.tag === 1 && (n.alternate === null ? n.tag = 17 : (t = qn(-1, 1),
    t.tag = 2,
    Pr(n, t, 1))),
    n.lanes |= 1),
    e)
}
var Qb = nr.ReactCurrentOwner
  , jt = !1;
function St(e, t, n, r) {
    t.child = e === null ? r0(t, null, n, r) : cs(t, e.child, n, r)
}
function mh(e, t, n, r, i) {
    n = n.render;
    var s = t.ref;
    return rs(t, i),
    r = ap(e, t, n, r, s, i),
    n = lp(),
    e !== null && !jt ? (t.updateQueue = e.updateQueue,
    t.flags &= -2053,
    e.lanes &= ~i,
    er(e, t, i)) : (Ae && n && Kf(t),
    t.flags |= 1,
    St(e, t, r, i),
    t.child)
}
function hh(e, t, n, r, i) {
    if (e === null) {
        var s = n.type;
        return typeof s == "function" && !vp(s) && s.defaultProps === void 0 && n.compare === null && n.defaultProps === void 0 ? (t.tag = 15,
        t.type = s,
        j0(e, t, s, r, i)) : (e = il(n.type, null, r, t, t.mode, i),
        e.ref = t.ref,
        e.return = t,
        t.child = e)
    }
    if (s = e.child,
    !(e.lanes & i)) {
        var o = s.memoizedProps;
        if (n = n.compare,
        n = n !== null ? n : Co,
        n(o, r) && e.ref === t.ref)
            return er(e, t, i)
    }
    return t.flags |= 1,
    e = Rr(s, r),
    e.ref = t.ref,
    e.return = t,
    t.child = e
}
function j0(e, t, n, r, i) {
    if (e !== null) {
        var s = e.memoizedProps;
        if (Co(s, r) && e.ref === t.ref)
            if (jt = !1,
            t.pendingProps = r = s,
            (e.lanes & i) !== 0)
                e.flags & 131072 && (jt = !0);
            else
                return t.lanes = e.lanes,
                er(e, t, i)
    }
    return Nd(e, t, n, r, i)
}
function N0(e, t, n) {
    var r = t.pendingProps
      , i = r.children
      , s = e !== null ? e.memoizedState : null;
    if (r.mode === "hidden")
        if (!(t.mode & 1))
            t.memoizedState = {
                baseLanes: 0,
                cachePool: null,
                transitions: null
            },
            je(Yi, Vt),
            Vt |= n;
        else {
            if (!(n & 1073741824))
                return e = s !== null ? s.baseLanes | n : n,
                t.lanes = t.childLanes = 1073741824,
                t.memoizedState = {
                    baseLanes: e,
                    cachePool: null,
                    transitions: null
                },
                t.updateQueue = null,
                je(Yi, Vt),
                Vt |= e,
                null;
            t.memoizedState = {
                baseLanes: 0,
                cachePool: null,
                transitions: null
            },
            r = s !== null ? s.baseLanes : n,
            je(Yi, Vt),
            Vt |= r
        }
    else
        s !== null ? (r = s.baseLanes | n,
        t.memoizedState = null) : r = n,
        je(Yi, Vt),
        Vt |= r;
    return St(e, t, i, n),
    t.child
}
function R0(e, t) {
    var n = t.ref;
    (e === null && n !== null || e !== null && e.ref !== n) && (t.flags |= 512,
    t.flags |= 2097152)
}
function Nd(e, t, n, r, i) {
    var s = Rt(n) ? ui : yt.current;
    return s = as(t, s),
    rs(t, i),
    n = ap(e, t, n, r, s, i),
    r = lp(),
    e !== null && !jt ? (t.updateQueue = e.updateQueue,
    t.flags &= -2053,
    e.lanes &= ~i,
    er(e, t, i)) : (Ae && r && Kf(t),
    t.flags |= 1,
    St(e, t, n, i),
    t.child)
}
function gh(e, t, n, r, i) {
    if (Rt(n)) {
        var s = !0;
        Sl(t)
    } else
        s = !1;
    if (rs(t, i),
    t.stateNode === null)
        tl(e, t),
        E0(t, n, r),
        Pd(t, n, r, i),
        r = !0;
    else if (e === null) {
        var o = t.stateNode
          , l = t.memoizedProps;
        o.props = l;
        var a = o.context
          , c = n.contextType;
        typeof c == "object" && c !== null ? c = an(c) : (c = Rt(n) ? ui : yt.current,
        c = as(t, c));
        var d = n.getDerivedStateFromProps
          , f = typeof d == "function" || typeof o.getSnapshotBeforeUpdate == "function";
        f || typeof o.UNSAFE_componentWillReceiveProps != "function" && typeof o.componentWillReceiveProps != "function" || (l !== r || a !== c) && uh(t, o, r, c),
        gr = !1;
        var p = t.memoizedState;
        o.state = p,
        Pl(t, r, o, i),
        a = t.memoizedState,
        l !== r || p !== a || Nt.current || gr ? (typeof d == "function" && (Td(t, n, d, r),
        a = t.memoizedState),
        (l = gr || ch(t, n, l, r, p, a, c)) ? (f || typeof o.UNSAFE_componentWillMount != "function" && typeof o.componentWillMount != "function" || (typeof o.componentWillMount == "function" && o.componentWillMount(),
        typeof o.UNSAFE_componentWillMount == "function" && o.UNSAFE_componentWillMount()),
        typeof o.componentDidMount == "function" && (t.flags |= 4194308)) : (typeof o.componentDidMount == "function" && (t.flags |= 4194308),
        t.memoizedProps = r,
        t.memoizedState = a),
        o.props = r,
        o.state = a,
        o.context = c,
        r = l) : (typeof o.componentDidMount == "function" && (t.flags |= 4194308),
        r = !1)
    } else {
        o = t.stateNode,
        s0(e, t),
        l = t.memoizedProps,
        c = t.type === t.elementType ? l : mn(t.type, l),
        o.props = c,
        f = t.pendingProps,
        p = o.context,
        a = n.contextType,
        typeof a == "object" && a !== null ? a = an(a) : (a = Rt(n) ? ui : yt.current,
        a = as(t, a));
        var h = n.getDerivedStateFromProps;
        (d = typeof h == "function" || typeof o.getSnapshotBeforeUpdate == "function") || typeof o.UNSAFE_componentWillReceiveProps != "function" && typeof o.componentWillReceiveProps != "function" || (l !== f || p !== a) && uh(t, o, r, a),
        gr = !1,
        p = t.memoizedState,
        o.state = p,
        Pl(t, r, o, i);
        var m = t.memoizedState;
        l !== f || p !== m || Nt.current || gr ? (typeof h == "function" && (Td(t, n, h, r),
        m = t.memoizedState),
        (c = gr || ch(t, n, c, r, p, m, a) || !1) ? (d || typeof o.UNSAFE_componentWillUpdate != "function" && typeof o.componentWillUpdate != "function" || (typeof o.componentWillUpdate == "function" && o.componentWillUpdate(r, m, a),
        typeof o.UNSAFE_componentWillUpdate == "function" && o.UNSAFE_componentWillUpdate(r, m, a)),
        typeof o.componentDidUpdate == "function" && (t.flags |= 4),
        typeof o.getSnapshotBeforeUpdate == "function" && (t.flags |= 1024)) : (typeof o.componentDidUpdate != "function" || l === e.memoizedProps && p === e.memoizedState || (t.flags |= 4),
        typeof o.getSnapshotBeforeUpdate != "function" || l === e.memoizedProps && p === e.memoizedState || (t.flags |= 1024),
        t.memoizedProps = r,
        t.memoizedState = m),
        o.props = r,
        o.state = m,
        o.context = a,
        r = c) : (typeof o.componentDidUpdate != "function" || l === e.memoizedProps && p === e.memoizedState || (t.flags |= 4),
        typeof o.getSnapshotBeforeUpdate != "function" || l === e.memoizedProps && p === e.memoizedState || (t.flags |= 1024),
        r = !1)
    }
    return Rd(e, t, n, r, s, i)
}
function Rd(e, t, n, r, i, s) {
    R0(e, t);
    var o = (t.flags & 128) !== 0;
    if (!r && !o)
        return i && th(t, n, !1),
        er(e, t, s);
    r = t.stateNode,
    Qb.current = t;
    var l = o && typeof n.getDerivedStateFromError != "function" ? null : r.render();
    return t.flags |= 1,
    e !== null && o ? (t.child = cs(t, e.child, null, s),
    t.child = cs(t, null, l, s)) : St(e, t, l, s),
    t.memoizedState = r.state,
    i && th(t, n, !0),
    t.child
}
function I0(e) {
    var t = e.stateNode;
    t.pendingContext ? eh(e, t.pendingContext, t.pendingContext !== t.context) : t.context && eh(e, t.context, !1),
    rp(e, t.containerInfo)
}
function _h(e, t, n, r, i) {
    return ls(),
    Qf(i),
    t.flags |= 256,
    St(e, t, n, r),
    t.child
}
var Id = {
    dehydrated: null,
    treeContext: null,
    retryLane: 0
};
function Ld(e) {
    return {
        baseLanes: e,
        cachePool: null,
        transitions: null
    }
}
function L0(e, t, n) {
    var r = t.pendingProps, i = $e.current, s = !1, o = (t.flags & 128) !== 0, l;
    if ((l = o) || (l = e !== null && e.memoizedState === null ? !1 : (i & 2) !== 0),
    l ? (s = !0,
    t.flags &= -129) : (e === null || e.memoizedState !== null) && (i |= 1),
    je($e, i & 1),
    e === null)
        return kd(t),
        e = t.memoizedState,
        e !== null && (e = e.dehydrated,
        e !== null) ? (t.mode & 1 ? e.data === "$!" ? t.lanes = 8 : t.lanes = 1073741824 : t.lanes = 1,
        null) : (o = r.children,
        e = r.fallback,
        s ? (r = t.mode,
        s = t.child,
        o = {
            mode: "hidden",
            children: o
        },
        !(r & 1) && s !== null ? (s.childLanes = 0,
        s.pendingProps = o) : s = cc(o, r, 0, null),
        e = ci(e, r, n, null),
        s.return = t,
        e.return = t,
        s.sibling = e,
        t.child = s,
        t.child.memoizedState = Ld(n),
        t.memoizedState = Id,
        e) : dp(t, o));
    if (i = e.memoizedState,
    i !== null && (l = i.dehydrated,
    l !== null))
        return Jb(e, t, o, r, l, i, n);
    if (s) {
        s = r.fallback,
        o = t.mode,
        i = e.child,
        l = i.sibling;
        var a = {
            mode: "hidden",
            children: r.children
        };
        return !(o & 1) && t.child !== i ? (r = t.child,
        r.childLanes = 0,
        r.pendingProps = a,
        t.deletions = null) : (r = Rr(i, a),
        r.subtreeFlags = i.subtreeFlags & 14680064),
        l !== null ? s = Rr(l, s) : (s = ci(s, o, n, null),
        s.flags |= 2),
        s.return = t,
        r.return = t,
        r.sibling = s,
        t.child = r,
        r = s,
        s = t.child,
        o = e.child.memoizedState,
        o = o === null ? Ld(n) : {
            baseLanes: o.baseLanes | n,
            cachePool: null,
            transitions: o.transitions
        },
        s.memoizedState = o,
        s.childLanes = e.childLanes & ~n,
        t.memoizedState = Id,
        r
    }
    return s = e.child,
    e = s.sibling,
    r = Rr(s, {
        mode: "visible",
        children: r.children
    }),
    !(t.mode & 1) && (r.lanes = n),
    r.return = t,
    r.sibling = null,
    e !== null && (n = t.deletions,
    n === null ? (t.deletions = [e],
    t.flags |= 16) : n.push(e)),
    t.child = r,
    t.memoizedState = null,
    r
}
function dp(e, t) {
    return t = cc({
        mode: "visible",
        children: t
    }, e.mode, 0, null),
    t.return = e,
    e.child = t
}
function ja(e, t, n, r) {
    return r !== null && Qf(r),
    cs(t, e.child, null, n),
    e = dp(t, t.pendingProps.children),
    e.flags |= 2,
    t.memoizedState = null,
    e
}
function Jb(e, t, n, r, i, s, o) {
    if (n)
        return t.flags & 256 ? (t.flags &= -257,
        r = cu(Error(z(422))),
        ja(e, t, o, r)) : t.memoizedState !== null ? (t.child = e.child,
        t.flags |= 128,
        null) : (s = r.fallback,
        i = t.mode,
        r = cc({
            mode: "visible",
            children: r.children
        }, i, 0, null),
        s = ci(s, i, o, null),
        s.flags |= 2,
        r.return = t,
        s.return = t,
        r.sibling = s,
        t.child = r,
        t.mode & 1 && cs(t, e.child, null, o),
        t.child.memoizedState = Ld(o),
        t.memoizedState = Id,
        s);
    if (!(t.mode & 1))
        return ja(e, t, o, null);
    if (i.data === "$!") {
        if (r = i.nextSibling && i.nextSibling.dataset,
        r)
            var l = r.dgst;
        return r = l,
        s = Error(z(419)),
        r = cu(s, r, void 0),
        ja(e, t, o, r)
    }
    if (l = (o & e.childLanes) !== 0,
    jt || l) {
        if (r = st,
        r !== null) {
            switch (o & -o) {
            case 4:
                i = 2;
                break;
            case 16:
                i = 8;
                break;
            case 64:
            case 128:
            case 256:
            case 512:
            case 1024:
            case 2048:
            case 4096:
            case 8192:
            case 16384:
            case 32768:
            case 65536:
            case 131072:
            case 262144:
            case 524288:
            case 1048576:
            case 2097152:
            case 4194304:
            case 8388608:
            case 16777216:
            case 33554432:
            case 67108864:
                i = 32;
                break;
            case 536870912:
                i = 268435456;
                break;
            default:
                i = 0
            }
            i = i & (r.suspendedLanes | o) ? 0 : i,
            i !== 0 && i !== s.retryLane && (s.retryLane = i,
            Zn(e, i),
            yn(r, e, i, -1))
        }
        return _p(),
        r = cu(Error(z(421))),
        ja(e, t, o, r)
    }
    return i.data === "$?" ? (t.flags |= 128,
    t.child = e.child,
    t = dS.bind(null, e),
    i._reactRetry = t,
    null) : (e = s.treeContext,
    Ut = Tr(i.nextSibling),
    Ht = t,
    Ae = !0,
    _n = null,
    e !== null && (tn[nn++] = Hn,
    tn[nn++] = Gn,
    tn[nn++] = di,
    Hn = e.id,
    Gn = e.overflow,
    di = t),
    t = dp(t, r.children),
    t.flags |= 4096,
    t)
}
function vh(e, t, n) {
    e.lanes |= t;
    var r = e.alternate;
    r !== null && (r.lanes |= t),
    Ed(e.return, t, n)
}
function uu(e, t, n, r, i) {
    var s = e.memoizedState;
    s === null ? e.memoizedState = {
        isBackwards: t,
        rendering: null,
        renderingStartTime: 0,
        last: r,
        tail: n,
        tailMode: i
    } : (s.isBackwards = t,
    s.rendering = null,
    s.renderingStartTime = 0,
    s.last = r,
    s.tail = n,
    s.tailMode = i)
}
function M0(e, t, n) {
    var r = t.pendingProps
      , i = r.revealOrder
      , s = r.tail;
    if (St(e, t, r.children, n),
    r = $e.current,
    r & 2)
        r = r & 1 | 2,
        t.flags |= 128;
    else {
        if (e !== null && e.flags & 128)
            e: for (e = t.child; e !== null; ) {
                if (e.tag === 13)
                    e.memoizedState !== null && vh(e, n, t);
                else if (e.tag === 19)
                    vh(e, n, t);
                else if (e.child !== null) {
                    e.child.return = e,
                    e = e.child;
                    continue
                }
                if (e === t)
                    break e;
                for (; e.sibling === null; ) {
                    if (e.return === null || e.return === t)
                        break e;
                    e = e.return
                }
                e.sibling.return = e.return,
                e = e.sibling
            }
        r &= 1
    }
    if (je($e, r),
    !(t.mode & 1))
        t.memoizedState = null;
    else
        switch (i) {
        case "forwards":
            for (n = t.child,
            i = null; n !== null; )
                e = n.alternate,
                e !== null && jl(e) === null && (i = n),
                n = n.sibling;
            n = i,
            n === null ? (i = t.child,
            t.child = null) : (i = n.sibling,
            n.sibling = null),
            uu(t, !1, i, n, s);
            break;
        case "backwards":
            for (n = null,
            i = t.child,
            t.child = null; i !== null; ) {
                if (e = i.alternate,
                e !== null && jl(e) === null) {
                    t.child = i;
                    break
                }
                e = i.sibling,
                i.sibling = n,
                n = i,
                i = e
            }
            uu(t, !0, n, null, s);
            break;
        case "together":
            uu(t, !1, null, null, void 0);
            break;
        default:
            t.memoizedState = null
        }
    return t.child
}
function tl(e, t) {
    !(t.mode & 1) && e !== null && (e.alternate = null,
    t.alternate = null,
    t.flags |= 2)
}
function er(e, t, n) {
    if (e !== null && (t.dependencies = e.dependencies),
    pi |= t.lanes,
    !(n & t.childLanes))
        return null;
    if (e !== null && t.child !== e.child)
        throw Error(z(153));
    if (t.child !== null) {
        for (e = t.child,
        n = Rr(e, e.pendingProps),
        t.child = n,
        n.return = t; e.sibling !== null; )
            e = e.sibling,
            n = n.sibling = Rr(e, e.pendingProps),
            n.return = t;
        n.sibling = null
    }
    return t.child
}
function Zb(e, t, n) {
    switch (t.tag) {
    case 3:
        I0(t),
        ls();
        break;
    case 5:
        o0(t);
        break;
    case 1:
        Rt(t.type) && Sl(t);
        break;
    case 4:
        rp(t, t.stateNode.containerInfo);
        break;
    case 10:
        var r = t.type._context
          , i = t.memoizedProps.value;
        je(El, r._currentValue),
        r._currentValue = i;
        break;
    case 13:
        if (r = t.memoizedState,
        r !== null)
            return r.dehydrated !== null ? (je($e, $e.current & 1),
            t.flags |= 128,
            null) : n & t.child.childLanes ? L0(e, t, n) : (je($e, $e.current & 1),
            e = er(e, t, n),
            e !== null ? e.sibling : null);
        je($e, $e.current & 1);
        break;
    case 19:
        if (r = (n & t.childLanes) !== 0,
        e.flags & 128) {
            if (r)
                return M0(e, t, n);
            t.flags |= 128
        }
        if (i = t.memoizedState,
        i !== null && (i.rendering = null,
        i.tail = null,
        i.lastEffect = null),
        je($e, $e.current),
        r)
            break;
        return null;
    case 22:
    case 23:
        return t.lanes = 0,
        N0(e, t, n)
    }
    return er(e, t, n)
}
var O0, Md, A0, D0;
O0 = function(e, t) {
    for (var n = t.child; n !== null; ) {
        if (n.tag === 5 || n.tag === 6)
            e.appendChild(n.stateNode);
        else if (n.tag !== 4 && n.child !== null) {
            n.child.return = n,
            n = n.child;
            continue
        }
        if (n === t)
            break;
        for (; n.sibling === null; ) {
            if (n.return === null || n.return === t)
                return;
            n = n.return
        }
        n.sibling.return = n.return,
        n = n.sibling
    }
}
;
Md = function() {}
;
A0 = function(e, t, n, r) {
    var i = e.memoizedProps;
    if (i !== r) {
        e = t.stateNode,
        ri(Mn.current);
        var s = null;
        switch (n) {
        case "input":
            i = nd(e, i),
            r = nd(e, r),
            s = [];
            break;
        case "select":
            i = ze({}, i, {
                value: void 0
            }),
            r = ze({}, r, {
                value: void 0
            }),
            s = [];
            break;
        case "textarea":
            i = sd(e, i),
            r = sd(e, r),
            s = [];
            break;
        default:
            typeof i.onClick != "function" && typeof r.onClick == "function" && (e.onclick = xl)
        }
        ad(n, r);
        var o;
        n = null;
        for (c in i)
            if (!r.hasOwnProperty(c) && i.hasOwnProperty(c) && i[c] != null)
                if (c === "style") {
                    var l = i[c];
                    for (o in l)
                        l.hasOwnProperty(o) && (n || (n = {}),
                        n[o] = "")
                } else
                    c !== "dangerouslySetInnerHTML" && c !== "children" && c !== "suppressContentEditableWarning" && c !== "suppressHydrationWarning" && c !== "autoFocus" && (_o.hasOwnProperty(c) ? s || (s = []) : (s = s || []).push(c, null));
        for (c in r) {
            var a = r[c];
            if (l = i != null ? i[c] : void 0,
            r.hasOwnProperty(c) && a !== l && (a != null || l != null))
                if (c === "style")
                    if (l) {
                        for (o in l)
                            !l.hasOwnProperty(o) || a && a.hasOwnProperty(o) || (n || (n = {}),
                            n[o] = "");
                        for (o in a)
                            a.hasOwnProperty(o) && l[o] !== a[o] && (n || (n = {}),
                            n[o] = a[o])
                    } else
                        n || (s || (s = []),
                        s.push(c, n)),
                        n = a;
                else
                    c === "dangerouslySetInnerHTML" ? (a = a ? a.__html : void 0,
                    l = l ? l.__html : void 0,
                    a != null && l !== a && (s = s || []).push(c, a)) : c === "children" ? typeof a != "string" && typeof a != "number" || (s = s || []).push(c, "" + a) : c !== "suppressContentEditableWarning" && c !== "suppressHydrationWarning" && (_o.hasOwnProperty(c) ? (a != null && c === "onScroll" && Re("scroll", e),
                    s || l === a || (s = [])) : (s = s || []).push(c, a))
        }
        n && (s = s || []).push("style", n);
        var c = s;
        (t.updateQueue = c) && (t.flags |= 4)
    }
}
;
D0 = function(e, t, n, r) {
    n !== r && (t.flags |= 4)
}
;
function Ms(e, t) {
    if (!Ae)
        switch (e.tailMode) {
        case "hidden":
            t = e.tail;
            for (var n = null; t !== null; )
                t.alternate !== null && (n = t),
                t = t.sibling;
            n === null ? e.tail = null : n.sibling = null;
            break;
        case "collapsed":
            n = e.tail;
            for (var r = null; n !== null; )
                n.alternate !== null && (r = n),
                n = n.sibling;
            r === null ? t || e.tail === null ? e.tail = null : e.tail.sibling = null : r.sibling = null
        }
}
function ht(e) {
    var t = e.alternate !== null && e.alternate.child === e.child
      , n = 0
      , r = 0;
    if (t)
        for (var i = e.child; i !== null; )
            n |= i.lanes | i.childLanes,
            r |= i.subtreeFlags & 14680064,
            r |= i.flags & 14680064,
            i.return = e,
            i = i.sibling;
    else
        for (i = e.child; i !== null; )
            n |= i.lanes | i.childLanes,
            r |= i.subtreeFlags,
            r |= i.flags,
            i.return = e,
            i = i.sibling;
    return e.subtreeFlags |= r,
    e.childLanes = n,
    t
}
function eS(e, t, n) {
    var r = t.pendingProps;
    switch (Xf(t),
    t.tag) {
    case 2:
    case 16:
    case 15:
    case 0:
    case 11:
    case 7:
    case 8:
    case 12:
    case 9:
    case 14:
        return ht(t),
        null;
    case 1:
        return Rt(t.type) && bl(),
        ht(t),
        null;
    case 3:
        return r = t.stateNode,
        us(),
        Le(Nt),
        Le(yt),
        sp(),
        r.pendingContext && (r.context = r.pendingContext,
        r.pendingContext = null),
        (e === null || e.child === null) && (Ta(t) ? t.flags |= 4 : e === null || e.memoizedState.isDehydrated && !(t.flags & 256) || (t.flags |= 1024,
        _n !== null && (Bd(_n),
        _n = null))),
        Md(e, t),
        ht(t),
        null;
    case 5:
        ip(t);
        var i = ri(jo.current);
        if (n = t.type,
        e !== null && t.stateNode != null)
            A0(e, t, n, r, i),
            e.ref !== t.ref && (t.flags |= 512,
            t.flags |= 2097152);
        else {
            if (!r) {
                if (t.stateNode === null)
                    throw Error(z(166));
                return ht(t),
                null
            }
            if (e = ri(Mn.current),
            Ta(t)) {
                r = t.stateNode,
                n = t.type;
                var s = t.memoizedProps;
                switch (r[Rn] = t,
                r[To] = s,
                e = (t.mode & 1) !== 0,
                n) {
                case "dialog":
                    Re("cancel", r),
                    Re("close", r);
                    break;
                case "iframe":
                case "object":
                case "embed":
                    Re("load", r);
                    break;
                case "video":
                case "audio":
                    for (i = 0; i < Xs.length; i++)
                        Re(Xs[i], r);
                    break;
                case "source":
                    Re("error", r);
                    break;
                case "img":
                case "image":
                case "link":
                    Re("error", r),
                    Re("load", r);
                    break;
                case "details":
                    Re("toggle", r);
                    break;
                case "input":
                    Tm(r, s),
                    Re("invalid", r);
                    break;
                case "select":
                    r._wrapperState = {
                        wasMultiple: !!s.multiple
                    },
                    Re("invalid", r);
                    break;
                case "textarea":
                    jm(r, s),
                    Re("invalid", r)
                }
                ad(n, s),
                i = null;
                for (var o in s)
                    if (s.hasOwnProperty(o)) {
                        var l = s[o];
                        o === "children" ? typeof l == "string" ? r.textContent !== l && (s.suppressHydrationWarning !== !0 && Ea(r.textContent, l, e),
                        i = ["children", l]) : typeof l == "number" && r.textContent !== "" + l && (s.suppressHydrationWarning !== !0 && Ea(r.textContent, l, e),
                        i = ["children", "" + l]) : _o.hasOwnProperty(o) && l != null && o === "onScroll" && Re("scroll", r)
                    }
                switch (n) {
                case "input":
                    va(r),
                    Pm(r, s, !0);
                    break;
                case "textarea":
                    va(r),
                    Nm(r);
                    break;
                case "select":
                case "option":
                    break;
                default:
                    typeof s.onClick == "function" && (r.onclick = xl)
                }
                r = i,
                t.updateQueue = r,
                r !== null && (t.flags |= 4)
            } else {
                o = i.nodeType === 9 ? i : i.ownerDocument,
                e === "http://www.w3.org/1999/xhtml" && (e = dv(n)),
                e === "http://www.w3.org/1999/xhtml" ? n === "script" ? (e = o.createElement("div"),
                e.innerHTML = "<script><\/script>",
                e = e.removeChild(e.firstChild)) : typeof r.is == "string" ? e = o.createElement(n, {
                    is: r.is
                }) : (e = o.createElement(n),
                n === "select" && (o = e,
                r.multiple ? o.multiple = !0 : r.size && (o.size = r.size))) : e = o.createElementNS(e, n),
                e[Rn] = t,
                e[To] = r,
                O0(e, t, !1, !1),
                t.stateNode = e;
                e: {
                    switch (o = ld(n, r),
                    n) {
                    case "dialog":
                        Re("cancel", e),
                        Re("close", e),
                        i = r;
                        break;
                    case "iframe":
                    case "object":
                    case "embed":
                        Re("load", e),
                        i = r;
                        break;
                    case "video":
                    case "audio":
                        for (i = 0; i < Xs.length; i++)
                            Re(Xs[i], e);
                        i = r;
                        break;
                    case "source":
                        Re("error", e),
                        i = r;
                        break;
                    case "img":
                    case "image":
                    case "link":
                        Re("error", e),
                        Re("load", e),
                        i = r;
                        break;
                    case "details":
                        Re("toggle", e),
                        i = r;
                        break;
                    case "input":
                        Tm(e, r),
                        i = nd(e, r),
                        Re("invalid", e);
                        break;
                    case "option":
                        i = r;
                        break;
                    case "select":
                        e._wrapperState = {
                            wasMultiple: !!r.multiple
                        },
                        i = ze({}, r, {
                            value: void 0
                        }),
                        Re("invalid", e);
                        break;
                    case "textarea":
                        jm(e, r),
                        i = sd(e, r),
                        Re("invalid", e);
                        break;
                    default:
                        i = r
                    }
                    ad(n, i),
                    l = i;
                    for (s in l)
                        if (l.hasOwnProperty(s)) {
                            var a = l[s];
                            s === "style" ? mv(e, a) : s === "dangerouslySetInnerHTML" ? (a = a ? a.__html : void 0,
                            a != null && fv(e, a)) : s === "children" ? typeof a == "string" ? (n !== "textarea" || a !== "") && vo(e, a) : typeof a == "number" && vo(e, "" + a) : s !== "suppressContentEditableWarning" && s !== "suppressHydrationWarning" && s !== "autoFocus" && (_o.hasOwnProperty(s) ? a != null && s === "onScroll" && Re("scroll", e) : a != null && Of(e, s, a, o))
                        }
                    switch (n) {
                    case "input":
                        va(e),
                        Pm(e, r, !1);
                        break;
                    case "textarea":
                        va(e),
                        Nm(e);
                        break;
                    case "option":
                        r.value != null && e.setAttribute("value", "" + Or(r.value));
                        break;
                    case "select":
                        e.multiple = !!r.multiple,
                        s = r.value,
                        s != null ? Zi(e, !!r.multiple, s, !1) : r.defaultValue != null && Zi(e, !!r.multiple, r.defaultValue, !0);
                        break;
                    default:
                        typeof i.onClick == "function" && (e.onclick = xl)
                    }
                    switch (n) {
                    case "button":
                    case "input":
                    case "select":
                    case "textarea":
                        r = !!r.autoFocus;
                        break e;
                    case "img":
                        r = !0;
                        break e;
                    default:
                        r = !1
                    }
                }
                r && (t.flags |= 4)
            }
            t.ref !== null && (t.flags |= 512,
            t.flags |= 2097152)
        }
        return ht(t),
        null;
    case 6:
        if (e && t.stateNode != null)
            D0(e, t, e.memoizedProps, r);
        else {
            if (typeof r != "string" && t.stateNode === null)
                throw Error(z(166));
            if (n = ri(jo.current),
            ri(Mn.current),
            Ta(t)) {
                if (r = t.stateNode,
                n = t.memoizedProps,
                r[Rn] = t,
                (s = r.nodeValue !== n) && (e = Ht,
                e !== null))
                    switch (e.tag) {
                    case 3:
                        Ea(r.nodeValue, n, (e.mode & 1) !== 0);
                        break;
                    case 5:
                        e.memoizedProps.suppressHydrationWarning !== !0 && Ea(r.nodeValue, n, (e.mode & 1) !== 0)
                    }
                s && (t.flags |= 4)
            } else
                r = (n.nodeType === 9 ? n : n.ownerDocument).createTextNode(r),
                r[Rn] = t,
                t.stateNode = r
        }
        return ht(t),
        null;
    case 13:
        if (Le($e),
        r = t.memoizedState,
        e === null || e.memoizedState !== null && e.memoizedState.dehydrated !== null) {
            if (Ae && Ut !== null && t.mode & 1 && !(t.flags & 128))
                t0(),
                ls(),
                t.flags |= 98560,
                s = !1;
            else if (s = Ta(t),
            r !== null && r.dehydrated !== null) {
                if (e === null) {
                    if (!s)
                        throw Error(z(318));
                    if (s = t.memoizedState,
                    s = s !== null ? s.dehydrated : null,
                    !s)
                        throw Error(z(317));
                    s[Rn] = t
                } else
                    ls(),
                    !(t.flags & 128) && (t.memoizedState = null),
                    t.flags |= 4;
                ht(t),
                s = !1
            } else
                _n !== null && (Bd(_n),
                _n = null),
                s = !0;
            if (!s)
                return t.flags & 65536 ? t : null
        }
        return t.flags & 128 ? (t.lanes = n,
        t) : (r = r !== null,
        r !== (e !== null && e.memoizedState !== null) && r && (t.child.flags |= 8192,
        t.mode & 1 && (e === null || $e.current & 1 ? rt === 0 && (rt = 3) : _p())),
        t.updateQueue !== null && (t.flags |= 4),
        ht(t),
        null);
    case 4:
        return us(),
        Md(e, t),
        e === null && ko(t.stateNode.containerInfo),
        ht(t),
        null;
    case 10:
        return ep(t.type._context),
        ht(t),
        null;
    case 17:
        return Rt(t.type) && bl(),
        ht(t),
        null;
    case 19:
        if (Le($e),
        s = t.memoizedState,
        s === null)
            return ht(t),
            null;
        if (r = (t.flags & 128) !== 0,
        o = s.rendering,
        o === null)
            if (r)
                Ms(s, !1);
            else {
                if (rt !== 0 || e !== null && e.flags & 128)
                    for (e = t.child; e !== null; ) {
                        if (o = jl(e),
                        o !== null) {
                            for (t.flags |= 128,
                            Ms(s, !1),
                            r = o.updateQueue,
                            r !== null && (t.updateQueue = r,
                            t.flags |= 4),
                            t.subtreeFlags = 0,
                            r = n,
                            n = t.child; n !== null; )
                                s = n,
                                e = r,
                                s.flags &= 14680066,
                                o = s.alternate,
                                o === null ? (s.childLanes = 0,
                                s.lanes = e,
                                s.child = null,
                                s.subtreeFlags = 0,
                                s.memoizedProps = null,
                                s.memoizedState = null,
                                s.updateQueue = null,
                                s.dependencies = null,
                                s.stateNode = null) : (s.childLanes = o.childLanes,
                                s.lanes = o.lanes,
                                s.child = o.child,
                                s.subtreeFlags = 0,
                                s.deletions = null,
                                s.memoizedProps = o.memoizedProps,
                                s.memoizedState = o.memoizedState,
                                s.updateQueue = o.updateQueue,
                                s.type = o.type,
                                e = o.dependencies,
                                s.dependencies = e === null ? null : {
                                    lanes: e.lanes,
                                    firstContext: e.firstContext
                                }),
                                n = n.sibling;
                            return je($e, $e.current & 1 | 2),
                            t.child
                        }
                        e = e.sibling
                    }
                s.tail !== null && Ye() > fs && (t.flags |= 128,
                r = !0,
                Ms(s, !1),
                t.lanes = 4194304)
            }
        else {
            if (!r)
                if (e = jl(o),
                e !== null) {
                    if (t.flags |= 128,
                    r = !0,
                    n = e.updateQueue,
                    n !== null && (t.updateQueue = n,
                    t.flags |= 4),
                    Ms(s, !0),
                    s.tail === null && s.tailMode === "hidden" && !o.alternate && !Ae)
                        return ht(t),
                        null
                } else
                    2 * Ye() - s.renderingStartTime > fs && n !== 1073741824 && (t.flags |= 128,
                    r = !0,
                    Ms(s, !1),
                    t.lanes = 4194304);
            s.isBackwards ? (o.sibling = t.child,
            t.child = o) : (n = s.last,
            n !== null ? n.sibling = o : t.child = o,
            s.last = o)
        }
        return s.tail !== null ? (t = s.tail,
        s.rendering = t,
        s.tail = t.sibling,
        s.renderingStartTime = Ye(),
        t.sibling = null,
        n = $e.current,
        je($e, r ? n & 1 | 2 : n & 1),
        t) : (ht(t),
        null);
    case 22:
    case 23:
        return gp(),
        r = t.memoizedState !== null,
        e !== null && e.memoizedState !== null !== r && (t.flags |= 8192),
        r && t.mode & 1 ? Vt & 1073741824 && (ht(t),
        t.subtreeFlags & 6 && (t.flags |= 8192)) : ht(t),
        null;
    case 24:
        return null;
    case 25:
        return null
    }
    throw Error(z(156, t.tag))
}
function tS(e, t) {
    switch (Xf(t),
    t.tag) {
    case 1:
        return Rt(t.type) && bl(),
        e = t.flags,
        e & 65536 ? (t.flags = e & -65537 | 128,
        t) : null;
    case 3:
        return us(),
        Le(Nt),
        Le(yt),
        sp(),
        e = t.flags,
        e & 65536 && !(e & 128) ? (t.flags = e & -65537 | 128,
        t) : null;
    case 5:
        return ip(t),
        null;
    case 13:
        if (Le($e),
        e = t.memoizedState,
        e !== null && e.dehydrated !== null) {
            if (t.alternate === null)
                throw Error(z(340));
            ls()
        }
        return e = t.flags,
        e & 65536 ? (t.flags = e & -65537 | 128,
        t) : null;
    case 19:
        return Le($e),
        null;
    case 4:
        return us(),
        null;
    case 10:
        return ep(t.type._context),
        null;
    case 22:
    case 23:
        return gp(),
        null;
    case 24:
        return null;
    default:
        return null
    }
}
var Na = !1
  , _t = !1
  , nS = typeof WeakSet == "function" ? WeakSet : Set
  , Y = null;
function qi(e, t) {
    var n = e.ref;
    if (n !== null)
        if (typeof n == "function")
            try {
                n(null)
            } catch (r) {
                Ge(e, t, r)
            }
        else
            n.current = null
}
function Od(e, t, n) {
    try {
        n()
    } catch (r) {
        Ge(e, t, r)
    }
}
var yh = !1;
function rS(e, t) {
    if (vd = vl,
    e = Bv(),
    Yf(e)) {
        if ("selectionStart"in e)
            var n = {
                start: e.selectionStart,
                end: e.selectionEnd
            };
        else
            e: {
                n = (n = e.ownerDocument) && n.defaultView || window;
                var r = n.getSelection && n.getSelection();
                if (r && r.rangeCount !== 0) {
                    n = r.anchorNode;
                    var i = r.anchorOffset
                      , s = r.focusNode;
                    r = r.focusOffset;
                    try {
                        n.nodeType,
                        s.nodeType
                    } catch {
                        n = null;
                        break e
                    }
                    var o = 0
                      , l = -1
                      , a = -1
                      , c = 0
                      , d = 0
                      , f = e
                      , p = null;
                    t: for (; ; ) {
                        for (var h; f !== n || i !== 0 && f.nodeType !== 3 || (l = o + i),
                        f !== s || r !== 0 && f.nodeType !== 3 || (a = o + r),
                        f.nodeType === 3 && (o += f.nodeValue.length),
                        (h = f.firstChild) !== null; )
                            p = f,
                            f = h;
                        for (; ; ) {
                            if (f === e)
                                break t;
                            if (p === n && ++c === i && (l = o),
                            p === s && ++d === r && (a = o),
                            (h = f.nextSibling) !== null)
                                break;
                            f = p,
                            p = f.parentNode
                        }
                        f = h
                    }
                    n = l === -1 || a === -1 ? null : {
                        start: l,
                        end: a
                    }
                } else
                    n = null
            }
        n = n || {
            start: 0,
            end: 0
        }
    } else
        n = null;
    for (yd = {
        focusedElem: e,
        selectionRange: n
    },
    vl = !1,
    Y = t; Y !== null; )
        if (t = Y,
        e = t.child,
        (t.subtreeFlags & 1028) !== 0 && e !== null)
            e.return = t,
            Y = e;
        else
            for (; Y !== null; ) {
                t = Y;
                try {
                    var m = t.alternate;
                    if (t.flags & 1024)
                        switch (t.tag) {
                        case 0:
                        case 11:
                        case 15:
                            break;
                        case 1:
                            if (m !== null) {
                                var _ = m.memoizedProps
                                  , x = m.memoizedState
                                  , v = t.stateNode
                                  , w = v.getSnapshotBeforeUpdate(t.elementType === t.type ? _ : mn(t.type, _), x);
                                v.__reactInternalSnapshotBeforeUpdate = w
                            }
                            break;
                        case 3:
                            var y = t.stateNode.containerInfo;
                            y.nodeType === 1 ? y.textContent = "" : y.nodeType === 9 && y.documentElement && y.removeChild(y.documentElement);
                            break;
                        case 5:
                        case 6:
                        case 4:
                        case 17:
                            break;
                        default:
                            throw Error(z(163))
                        }
                } catch (b) {
                    Ge(t, t.return, b)
                }
                if (e = t.sibling,
                e !== null) {
                    e.return = t.return,
                    Y = e;
                    break
                }
                Y = t.return
            }
    return m = yh,
    yh = !1,
    m
}
function so(e, t, n) {
    var r = t.updateQueue;
    if (r = r !== null ? r.lastEffect : null,
    r !== null) {
        var i = r = r.next;
        do {
            if ((i.tag & e) === e) {
                var s = i.destroy;
                i.destroy = void 0,
                s !== void 0 && Od(t, n, s)
            }
            i = i.next
        } while (i !== r)
    }
}
function ac(e, t) {
    if (t = t.updateQueue,
    t = t !== null ? t.lastEffect : null,
    t !== null) {
        var n = t = t.next;
        do {
            if ((n.tag & e) === e) {
                var r = n.create;
                n.destroy = r()
            }
            n = n.next
        } while (n !== t)
    }
}
function Ad(e) {
    var t = e.ref;
    if (t !== null) {
        var n = e.stateNode;
        switch (e.tag) {
        case 5:
            e = n;
            break;
        default:
            e = n
        }
        typeof t == "function" ? t(e) : t.current = e
    }
}
function $0(e) {
    var t = e.alternate;
    t !== null && (e.alternate = null,
    $0(t)),
    e.child = null,
    e.deletions = null,
    e.sibling = null,
    e.tag === 5 && (t = e.stateNode,
    t !== null && (delete t[Rn],
    delete t[To],
    delete t[bd],
    delete t[Fb],
    delete t[Vb])),
    e.stateNode = null,
    e.return = null,
    e.dependencies = null,
    e.memoizedProps = null,
    e.memoizedState = null,
    e.pendingProps = null,
    e.stateNode = null,
    e.updateQueue = null
}
function F0(e) {
    return e.tag === 5 || e.tag === 3 || e.tag === 4
}
function wh(e) {
    e: for (; ; ) {
        for (; e.sibling === null; ) {
            if (e.return === null || F0(e.return))
                return null;
            e = e.return
        }
        for (e.sibling.return = e.return,
        e = e.sibling; e.tag !== 5 && e.tag !== 6 && e.tag !== 18; ) {
            if (e.flags & 2 || e.child === null || e.tag === 4)
                continue e;
            e.child.return = e,
            e = e.child
        }
        if (!(e.flags & 2))
            return e.stateNode
    }
}
function Dd(e, t, n) {
    var r = e.tag;
    if (r === 5 || r === 6)
        e = e.stateNode,
        t ? n.nodeType === 8 ? n.parentNode.insertBefore(e, t) : n.insertBefore(e, t) : (n.nodeType === 8 ? (t = n.parentNode,
        t.insertBefore(e, n)) : (t = n,
        t.appendChild(e)),
        n = n._reactRootContainer,
        n != null || t.onclick !== null || (t.onclick = xl));
    else if (r !== 4 && (e = e.child,
    e !== null))
        for (Dd(e, t, n),
        e = e.sibling; e !== null; )
            Dd(e, t, n),
            e = e.sibling
}
function $d(e, t, n) {
    var r = e.tag;
    if (r === 5 || r === 6)
        e = e.stateNode,
        t ? n.insertBefore(e, t) : n.appendChild(e);
    else if (r !== 4 && (e = e.child,
    e !== null))
        for ($d(e, t, n),
        e = e.sibling; e !== null; )
            $d(e, t, n),
            e = e.sibling
}
var ct = null
  , hn = !1;
function sr(e, t, n) {
    for (n = n.child; n !== null; )
        V0(e, t, n),
        n = n.sibling
}
function V0(e, t, n) {
    if (Ln && typeof Ln.onCommitFiberUnmount == "function")
        try {
            Ln.onCommitFiberUnmount(Zl, n)
        } catch {}
    switch (n.tag) {
    case 5:
        _t || qi(n, t);
    case 6:
        var r = ct
          , i = hn;
        ct = null,
        sr(e, t, n),
        ct = r,
        hn = i,
        ct !== null && (hn ? (e = ct,
        n = n.stateNode,
        e.nodeType === 8 ? e.parentNode.removeChild(n) : e.removeChild(n)) : ct.removeChild(n.stateNode));
        break;
    case 18:
        ct !== null && (hn ? (e = ct,
        n = n.stateNode,
        e.nodeType === 8 ? ru(e.parentNode, n) : e.nodeType === 1 && ru(e, n),
        bo(e)) : ru(ct, n.stateNode));
        break;
    case 4:
        r = ct,
        i = hn,
        ct = n.stateNode.containerInfo,
        hn = !0,
        sr(e, t, n),
        ct = r,
        hn = i;
        break;
    case 0:
    case 11:
    case 14:
    case 15:
        if (!_t && (r = n.updateQueue,
        r !== null && (r = r.lastEffect,
        r !== null))) {
            i = r = r.next;
            do {
                var s = i
                  , o = s.destroy;
                s = s.tag,
                o !== void 0 && (s & 2 || s & 4) && Od(n, t, o),
                i = i.next
            } while (i !== r)
        }
        sr(e, t, n);
        break;
    case 1:
        if (!_t && (qi(n, t),
        r = n.stateNode,
        typeof r.componentWillUnmount == "function"))
            try {
                r.props = n.memoizedProps,
                r.state = n.memoizedState,
                r.componentWillUnmount()
            } catch (l) {
                Ge(n, t, l)
            }
        sr(e, t, n);
        break;
    case 21:
        sr(e, t, n);
        break;
    case 22:
        n.mode & 1 ? (_t = (r = _t) || n.memoizedState !== null,
        sr(e, t, n),
        _t = r) : sr(e, t, n);
        break;
    default:
        sr(e, t, n)
    }
}
function xh(e) {
    var t = e.updateQueue;
    if (t !== null) {
        e.updateQueue = null;
        var n = e.stateNode;
        n === null && (n = e.stateNode = new nS),
        t.forEach(function(r) {
            var i = fS.bind(null, e, r);
            n.has(r) || (n.add(r),
            r.then(i, i))
        })
    }
}
function fn(e, t) {
    var n = t.deletions;
    if (n !== null)
        for (var r = 0; r < n.length; r++) {
            var i = n[r];
            try {
                var s = e
                  , o = t
                  , l = o;
                e: for (; l !== null; ) {
                    switch (l.tag) {
                    case 5:
                        ct = l.stateNode,
                        hn = !1;
                        break e;
                    case 3:
                        ct = l.stateNode.containerInfo,
                        hn = !0;
                        break e;
                    case 4:
                        ct = l.stateNode.containerInfo,
                        hn = !0;
                        break e
                    }
                    l = l.return
                }
                if (ct === null)
                    throw Error(z(160));
                V0(s, o, i),
                ct = null,
                hn = !1;
                var a = i.alternate;
                a !== null && (a.return = null),
                i.return = null
            } catch (c) {
                Ge(i, t, c)
            }
        }
    if (t.subtreeFlags & 12854)
        for (t = t.child; t !== null; )
            z0(t, e),
            t = t.sibling
}
function z0(e, t) {
    var n = e.alternate
      , r = e.flags;
    switch (e.tag) {
    case 0:
    case 11:
    case 14:
    case 15:
        if (fn(t, e),
        Pn(e),
        r & 4) {
            try {
                so(3, e, e.return),
                ac(3, e)
            } catch (_) {
                Ge(e, e.return, _)
            }
            try {
                so(5, e, e.return)
            } catch (_) {
                Ge(e, e.return, _)
            }
        }
        break;
    case 1:
        fn(t, e),
        Pn(e),
        r & 512 && n !== null && qi(n, n.return);
        break;
    case 5:
        if (fn(t, e),
        Pn(e),
        r & 512 && n !== null && qi(n, n.return),
        e.flags & 32) {
            var i = e.stateNode;
            try {
                vo(i, "")
            } catch (_) {
                Ge(e, e.return, _)
            }
        }
        if (r & 4 && (i = e.stateNode,
        i != null)) {
            var s = e.memoizedProps
              , o = n !== null ? n.memoizedProps : s
              , l = e.type
              , a = e.updateQueue;
            if (e.updateQueue = null,
            a !== null)
                try {
                    l === "input" && s.type === "radio" && s.name != null && cv(i, s),
                    ld(l, o);
                    var c = ld(l, s);
                    for (o = 0; o < a.length; o += 2) {
                        var d = a[o]
                          , f = a[o + 1];
                        d === "style" ? mv(i, f) : d === "dangerouslySetInnerHTML" ? fv(i, f) : d === "children" ? vo(i, f) : Of(i, d, f, c)
                    }
                    switch (l) {
                    case "input":
                        rd(i, s);
                        break;
                    case "textarea":
                        uv(i, s);
                        break;
                    case "select":
                        var p = i._wrapperState.wasMultiple;
                        i._wrapperState.wasMultiple = !!s.multiple;
                        var h = s.value;
                        h != null ? Zi(i, !!s.multiple, h, !1) : p !== !!s.multiple && (s.defaultValue != null ? Zi(i, !!s.multiple, s.defaultValue, !0) : Zi(i, !!s.multiple, s.multiple ? [] : "", !1))
                    }
                    i[To] = s
                } catch (_) {
                    Ge(e, e.return, _)
                }
        }
        break;
    case 6:
        if (fn(t, e),
        Pn(e),
        r & 4) {
            if (e.stateNode === null)
                throw Error(z(162));
            i = e.stateNode,
            s = e.memoizedProps;
            try {
                i.nodeValue = s
            } catch (_) {
                Ge(e, e.return, _)
            }
        }
        break;
    case 3:
        if (fn(t, e),
        Pn(e),
        r & 4 && n !== null && n.memoizedState.isDehydrated)
            try {
                bo(t.containerInfo)
            } catch (_) {
                Ge(e, e.return, _)
            }
        break;
    case 4:
        fn(t, e),
        Pn(e);
        break;
    case 13:
        fn(t, e),
        Pn(e),
        i = e.child,
        i.flags & 8192 && (s = i.memoizedState !== null,
        i.stateNode.isHidden = s,
        !s || i.alternate !== null && i.alternate.memoizedState !== null || (mp = Ye())),
        r & 4 && xh(e);
        break;
    case 22:
        if (d = n !== null && n.memoizedState !== null,
        e.mode & 1 ? (_t = (c = _t) || d,
        fn(t, e),
        _t = c) : fn(t, e),
        Pn(e),
        r & 8192) {
            if (c = e.memoizedState !== null,
            (e.stateNode.isHidden = c) && !d && e.mode & 1)
                for (Y = e,
                d = e.child; d !== null; ) {
                    for (f = Y = d; Y !== null; ) {
                        switch (p = Y,
                        h = p.child,
                        p.tag) {
                        case 0:
                        case 11:
                        case 14:
                        case 15:
                            so(4, p, p.return);
                            break;
                        case 1:
                            qi(p, p.return);
                            var m = p.stateNode;
                            if (typeof m.componentWillUnmount == "function") {
                                r = p,
                                n = p.return;
                                try {
                                    t = r,
                                    m.props = t.memoizedProps,
                                    m.state = t.memoizedState,
                                    m.componentWillUnmount()
                                } catch (_) {
                                    Ge(r, n, _)
                                }
                            }
                            break;
                        case 5:
                            qi(p, p.return);
                            break;
                        case 22:
                            if (p.memoizedState !== null) {
                                Sh(f);
                                continue
                            }
                        }
                        h !== null ? (h.return = p,
                        Y = h) : Sh(f)
                    }
                    d = d.sibling
                }
            e: for (d = null,
            f = e; ; ) {
                if (f.tag === 5) {
                    if (d === null) {
                        d = f;
                        try {
                            i = f.stateNode,
                            c ? (s = i.style,
                            typeof s.setProperty == "function" ? s.setProperty("display", "none", "important") : s.display = "none") : (l = f.stateNode,
                            a = f.memoizedProps.style,
                            o = a != null && a.hasOwnProperty("display") ? a.display : null,
                            l.style.display = pv("display", o))
                        } catch (_) {
                            Ge(e, e.return, _)
                        }
                    }
                } else if (f.tag === 6) {
                    if (d === null)
                        try {
                            f.stateNode.nodeValue = c ? "" : f.memoizedProps
                        } catch (_) {
                            Ge(e, e.return, _)
                        }
                } else if ((f.tag !== 22 && f.tag !== 23 || f.memoizedState === null || f === e) && f.child !== null) {
                    f.child.return = f,
                    f = f.child;
                    continue
                }
                if (f === e)
                    break e;
                for (; f.sibling === null; ) {
                    if (f.return === null || f.return === e)
                        break e;
                    d === f && (d = null),
                    f = f.return
                }
                d === f && (d = null),
                f.sibling.return = f.return,
                f = f.sibling
            }
        }
        break;
    case 19:
        fn(t, e),
        Pn(e),
        r & 4 && xh(e);
        break;
    case 21:
        break;
    default:
        fn(t, e),
        Pn(e)
    }
}
function Pn(e) {
    var t = e.flags;
    if (t & 2) {
        try {
            e: {
                for (var n = e.return; n !== null; ) {
                    if (F0(n)) {
                        var r = n;
                        break e
                    }
                    n = n.return
                }
                throw Error(z(160))
            }
            switch (r.tag) {
            case 5:
                var i = r.stateNode;
                r.flags & 32 && (vo(i, ""),
                r.flags &= -33);
                var s = wh(e);
                $d(e, s, i);
                break;
            case 3:
            case 4:
                var o = r.stateNode.containerInfo
                  , l = wh(e);
                Dd(e, l, o);
                break;
            default:
                throw Error(z(161))
            }
        } catch (a) {
            Ge(e, e.return, a)
        }
        e.flags &= -3
    }
    t & 4096 && (e.flags &= -4097)
}
function iS(e, t, n) {
    Y = e,
    B0(e)
}
function B0(e, t, n) {
    for (var r = (e.mode & 1) !== 0; Y !== null; ) {
        var i = Y
          , s = i.child;
        if (i.tag === 22 && r) {
            var o = i.memoizedState !== null || Na;
            if (!o) {
                var l = i.alternate
                  , a = l !== null && l.memoizedState !== null || _t;
                l = Na;
                var c = _t;
                if (Na = o,
                (_t = a) && !c)
                    for (Y = i; Y !== null; )
                        o = Y,
                        a = o.child,
                        o.tag === 22 && o.memoizedState !== null ? Ch(i) : a !== null ? (a.return = o,
                        Y = a) : Ch(i);
                for (; s !== null; )
                    Y = s,
                    B0(s),
                    s = s.sibling;
                Y = i,
                Na = l,
                _t = c
            }
            bh(e)
        } else
            i.subtreeFlags & 8772 && s !== null ? (s.return = i,
            Y = s) : bh(e)
    }
}
function bh(e) {
    for (; Y !== null; ) {
        var t = Y;
        if (t.flags & 8772) {
            var n = t.alternate;
            try {
                if (t.flags & 8772)
                    switch (t.tag) {
                    case 0:
                    case 11:
                    case 15:
                        _t || ac(5, t);
                        break;
                    case 1:
                        var r = t.stateNode;
                        if (t.flags & 4 && !_t)
                            if (n === null)
                                r.componentDidMount();
                            else {
                                var i = t.elementType === t.type ? n.memoizedProps : mn(t.type, n.memoizedProps);
                                r.componentDidUpdate(i, n.memoizedState, r.__reactInternalSnapshotBeforeUpdate)
                            }
                        var s = t.updateQueue;
                        s !== null && oh(t, s, r);
                        break;
                    case 3:
                        var o = t.updateQueue;
                        if (o !== null) {
                            if (n = null,
                            t.child !== null)
                                switch (t.child.tag) {
                                case 5:
                                    n = t.child.stateNode;
                                    break;
                                case 1:
                                    n = t.child.stateNode
                                }
                            oh(t, o, n)
                        }
                        break;
                    case 5:
                        var l = t.stateNode;
                        if (n === null && t.flags & 4) {
                            n = l;
                            var a = t.memoizedProps;
                            switch (t.type) {
                            case "button":
                            case "input":
                            case "select":
                            case "textarea":
                                a.autoFocus && n.focus();
                                break;
                            case "img":
                                a.src && (n.src = a.src)
                            }
                        }
                        break;
                    case 6:
                        break;
                    case 4:
                        break;
                    case 12:
                        break;
                    case 13:
                        if (t.memoizedState === null) {
                            var c = t.alternate;
                            if (c !== null) {
                                var d = c.memoizedState;
                                if (d !== null) {
                                    var f = d.dehydrated;
                                    f !== null && bo(f)
                                }
                            }
                        }
                        break;
                    case 19:
                    case 17:
                    case 21:
                    case 22:
                    case 23:
                    case 25:
                        break;
                    default:
                        throw Error(z(163))
                    }
                _t || t.flags & 512 && Ad(t)
            } catch (p) {
                Ge(t, t.return, p)
            }
        }
        if (t === e) {
            Y = null;
            break
        }
        if (n = t.sibling,
        n !== null) {
            n.return = t.return,
            Y = n;
            break
        }
        Y = t.return
    }
}
function Sh(e) {
    for (; Y !== null; ) {
        var t = Y;
        if (t === e) {
            Y = null;
            break
        }
        var n = t.sibling;
        if (n !== null) {
            n.return = t.return,
            Y = n;
            break
        }
        Y = t.return
    }
}
function Ch(e) {
    for (; Y !== null; ) {
        var t = Y;
        try {
            switch (t.tag) {
            case 0:
            case 11:
            case 15:
                var n = t.return;
                try {
                    ac(4, t)
                } catch (a) {
                    Ge(t, n, a)
                }
                break;
            case 1:
                var r = t.stateNode;
                if (typeof r.componentDidMount == "function") {
                    var i = t.return;
                    try {
                        r.componentDidMount()
                    } catch (a) {
                        Ge(t, i, a)
                    }
                }
                var s = t.return;
                try {
                    Ad(t)
                } catch (a) {
                    Ge(t, s, a)
                }
                break;
            case 5:
                var o = t.return;
                try {
                    Ad(t)
                } catch (a) {
                    Ge(t, o, a)
                }
            }
        } catch (a) {
            Ge(t, t.return, a)
        }
        if (t === e) {
            Y = null;
            break
        }
        var l = t.sibling;
        if (l !== null) {
            l.return = t.return,
            Y = l;
            break
        }
        Y = t.return
    }
}
var sS = Math.ceil
  , Il = nr.ReactCurrentDispatcher
  , fp = nr.ReactCurrentOwner
  , sn = nr.ReactCurrentBatchConfig
  , ve = 0
  , st = null
  , et = null
  , ut = 0
  , Vt = 0
  , Yi = zr(0)
  , rt = 0
  , Lo = null
  , pi = 0
  , lc = 0
  , pp = 0
  , oo = null
  , Pt = null
  , mp = 0
  , fs = 1 / 0
  , Bn = null
  , Ll = !1
  , Fd = null
  , jr = null
  , Ra = !1
  , xr = null
  , Ml = 0
  , ao = 0
  , Vd = null
  , nl = -1
  , rl = 0;
function Ct() {
    return ve & 6 ? Ye() : nl !== -1 ? nl : nl = Ye()
}
function Nr(e) {
    return e.mode & 1 ? ve & 2 && ut !== 0 ? ut & -ut : Bb.transition !== null ? (rl === 0 && (rl = Ev()),
    rl) : (e = be,
    e !== 0 || (e = window.event,
    e = e === void 0 ? 16 : Lv(e.type)),
    e) : 1
}
function yn(e, t, n, r) {
    if (50 < ao)
        throw ao = 0,
        Vd = null,
        Error(z(185));
    Xo(e, n, r),
    (!(ve & 2) || e !== st) && (e === st && (!(ve & 2) && (lc |= n),
    rt === 4 && yr(e, ut)),
    It(e, r),
    n === 1 && ve === 0 && !(t.mode & 1) && (fs = Ye() + 500,
    ic && Br()))
}
function It(e, t) {
    var n = e.callbackNode;
    B1(e, t);
    var r = _l(e, e === st ? ut : 0);
    if (r === 0)
        n !== null && Lm(n),
        e.callbackNode = null,
        e.callbackPriority = 0;
    else if (t = r & -r,
    e.callbackPriority !== t) {
        if (n != null && Lm(n),
        t === 1)
            e.tag === 0 ? zb(kh.bind(null, e)) : Jv(kh.bind(null, e)),
            Db(function() {
                !(ve & 6) && Br()
            }),
            n = null;
        else {
            switch (Tv(r)) {
            case 1:
                n = Vf;
                break;
            case 4:
                n = Cv;
                break;
            case 16:
                n = gl;
                break;
            case 536870912:
                n = kv;
                break;
            default:
                n = gl
            }
            n = X0(n, U0.bind(null, e))
        }
        e.callbackPriority = t,
        e.callbackNode = n
    }
}
function U0(e, t) {
    if (nl = -1,
    rl = 0,
    ve & 6)
        throw Error(z(327));
    var n = e.callbackNode;
    if (is() && e.callbackNode !== n)
        return null;
    var r = _l(e, e === st ? ut : 0);
    if (r === 0)
        return null;
    if (r & 30 || r & e.expiredLanes || t)
        t = Ol(e, r);
    else {
        t = r;
        var i = ve;
        ve |= 2;
        var s = G0();
        (st !== e || ut !== t) && (Bn = null,
        fs = Ye() + 500,
        li(e, t));
        do
            try {
                lS();
                break
            } catch (l) {
                H0(e, l)
            }
        while (1);
        Zf(),
        Il.current = s,
        ve = i,
        et !== null ? t = 0 : (st = null,
        ut = 0,
        t = rt)
    }
    if (t !== 0) {
        if (t === 2 && (i = pd(e),
        i !== 0 && (r = i,
        t = zd(e, i))),
        t === 1)
            throw n = Lo,
            li(e, 0),
            yr(e, r),
            It(e, Ye()),
            n;
        if (t === 6)
            yr(e, r);
        else {
            if (i = e.current.alternate,
            !(r & 30) && !oS(i) && (t = Ol(e, r),
            t === 2 && (s = pd(e),
            s !== 0 && (r = s,
            t = zd(e, s))),
            t === 1))
                throw n = Lo,
                li(e, 0),
                yr(e, r),
                It(e, Ye()),
                n;
            switch (e.finishedWork = i,
            e.finishedLanes = r,
            t) {
            case 0:
            case 1:
                throw Error(z(345));
            case 2:
                Xr(e, Pt, Bn);
                break;
            case 3:
                if (yr(e, r),
                (r & 130023424) === r && (t = mp + 500 - Ye(),
                10 < t)) {
                    if (_l(e, 0) !== 0)
                        break;
                    if (i = e.suspendedLanes,
                    (i & r) !== r) {
                        Ct(),
                        e.pingedLanes |= e.suspendedLanes & i;
                        break
                    }
                    e.timeoutHandle = xd(Xr.bind(null, e, Pt, Bn), t);
                    break
                }
                Xr(e, Pt, Bn);
                break;
            case 4:
                if (yr(e, r),
                (r & 4194240) === r)
                    break;
                for (t = e.eventTimes,
                i = -1; 0 < r; ) {
                    var o = 31 - vn(r);
                    s = 1 << o,
                    o = t[o],
                    o > i && (i = o),
                    r &= ~s
                }
                if (r = i,
                r = Ye() - r,
                r = (120 > r ? 120 : 480 > r ? 480 : 1080 > r ? 1080 : 1920 > r ? 1920 : 3e3 > r ? 3e3 : 4320 > r ? 4320 : 1960 * sS(r / 1960)) - r,
                10 < r) {
                    e.timeoutHandle = xd(Xr.bind(null, e, Pt, Bn), r);
                    break
                }
                Xr(e, Pt, Bn);
                break;
            case 5:
                Xr(e, Pt, Bn);
                break;
            default:
                throw Error(z(329))
            }
        }
    }
    return It(e, Ye()),
    e.callbackNode === n ? U0.bind(null, e) : null
}
function zd(e, t) {
    var n = oo;
    return e.current.memoizedState.isDehydrated && (li(e, t).flags |= 256),
    e = Ol(e, t),
    e !== 2 && (t = Pt,
    Pt = n,
    t !== null && Bd(t)),
    e
}
function Bd(e) {
    Pt === null ? Pt = e : Pt.push.apply(Pt, e)
}
function oS(e) {
    for (var t = e; ; ) {
        if (t.flags & 16384) {
            var n = t.updateQueue;
            if (n !== null && (n = n.stores,
            n !== null))
                for (var r = 0; r < n.length; r++) {
                    var i = n[r]
                      , s = i.getSnapshot;
                    i = i.value;
                    try {
                        if (!bn(s(), i))
                            return !1
                    } catch {
                        return !1
                    }
                }
        }
        if (n = t.child,
        t.subtreeFlags & 16384 && n !== null)
            n.return = t,
            t = n;
        else {
            if (t === e)
                break;
            for (; t.sibling === null; ) {
                if (t.return === null || t.return === e)
                    return !0;
                t = t.return
            }
            t.sibling.return = t.return,
            t = t.sibling
        }
    }
    return !0
}
function yr(e, t) {
    for (t &= ~pp,
    t &= ~lc,
    e.suspendedLanes |= t,
    e.pingedLanes &= ~t,
    e = e.expirationTimes; 0 < t; ) {
        var n = 31 - vn(t)
          , r = 1 << n;
        e[n] = -1,
        t &= ~r
    }
}
function kh(e) {
    if (ve & 6)
        throw Error(z(327));
    is();
    var t = _l(e, 0);
    if (!(t & 1))
        return It(e, Ye()),
        null;
    var n = Ol(e, t);
    if (e.tag !== 0 && n === 2) {
        var r = pd(e);
        r !== 0 && (t = r,
        n = zd(e, r))
    }
    if (n === 1)
        throw n = Lo,
        li(e, 0),
        yr(e, t),
        It(e, Ye()),
        n;
    if (n === 6)
        throw Error(z(345));
    return e.finishedWork = e.current.alternate,
    e.finishedLanes = t,
    Xr(e, Pt, Bn),
    It(e, Ye()),
    null
}
function hp(e, t) {
    var n = ve;
    ve |= 1;
    try {
        return e(t)
    } finally {
        ve = n,
        ve === 0 && (fs = Ye() + 500,
        ic && Br())
    }
}
function mi(e) {
    xr !== null && xr.tag === 0 && !(ve & 6) && is();
    var t = ve;
    ve |= 1;
    var n = sn.transition
      , r = be;
    try {
        if (sn.transition = null,
        be = 1,
        e)
            return e()
    } finally {
        be = r,
        sn.transition = n,
        ve = t,
        !(ve & 6) && Br()
    }
}
function gp() {
    Vt = Yi.current,
    Le(Yi)
}
function li(e, t) {
    e.finishedWork = null,
    e.finishedLanes = 0;
    var n = e.timeoutHandle;
    if (n !== -1 && (e.timeoutHandle = -1,
    Ab(n)),
    et !== null)
        for (n = et.return; n !== null; ) {
            var r = n;
            switch (Xf(r),
            r.tag) {
            case 1:
                r = r.type.childContextTypes,
                r != null && bl();
                break;
            case 3:
                us(),
                Le(Nt),
                Le(yt),
                sp();
                break;
            case 5:
                ip(r);
                break;
            case 4:
                us();
                break;
            case 13:
                Le($e);
                break;
            case 19:
                Le($e);
                break;
            case 10:
                ep(r.type._context);
                break;
            case 22:
            case 23:
                gp()
            }
            n = n.return
        }
    if (st = e,
    et = e = Rr(e.current, null),
    ut = Vt = t,
    rt = 0,
    Lo = null,
    pp = lc = pi = 0,
    Pt = oo = null,
    ni !== null) {
        for (t = 0; t < ni.length; t++)
            if (n = ni[t],
            r = n.interleaved,
            r !== null) {
                n.interleaved = null;
                var i = r.next
                  , s = n.pending;
                if (s !== null) {
                    var o = s.next;
                    s.next = i,
                    r.next = o
                }
                n.pending = r
            }
        ni = null
    }
    return e
}
function H0(e, t) {
    do {
        var n = et;
        try {
            if (Zf(),
            Za.current = Rl,
            Nl) {
                for (var r = Ve.memoizedState; r !== null; ) {
                    var i = r.queue;
                    i !== null && (i.pending = null),
                    r = r.next
                }
                Nl = !1
            }
            if (fi = 0,
            it = nt = Ve = null,
            io = !1,
            No = 0,
            fp.current = null,
            n === null || n.return === null) {
                rt = 1,
                Lo = t,
                et = null;
                break
            }
            e: {
                var s = e
                  , o = n.return
                  , l = n
                  , a = t;
                if (t = ut,
                l.flags |= 32768,
                a !== null && typeof a == "object" && typeof a.then == "function") {
                    var c = a
                      , d = l
                      , f = d.tag;
                    if (!(d.mode & 1) && (f === 0 || f === 11 || f === 15)) {
                        var p = d.alternate;
                        p ? (d.updateQueue = p.updateQueue,
                        d.memoizedState = p.memoizedState,
                        d.lanes = p.lanes) : (d.updateQueue = null,
                        d.memoizedState = null)
                    }
                    var h = fh(o);
                    if (h !== null) {
                        h.flags &= -257,
                        ph(h, o, l, s, t),
                        h.mode & 1 && dh(s, c, t),
                        t = h,
                        a = c;
                        var m = t.updateQueue;
                        if (m === null) {
                            var _ = new Set;
                            _.add(a),
                            t.updateQueue = _
                        } else
                            m.add(a);
                        break e
                    } else {
                        if (!(t & 1)) {
                            dh(s, c, t),
                            _p();
                            break e
                        }
                        a = Error(z(426))
                    }
                } else if (Ae && l.mode & 1) {
                    var x = fh(o);
                    if (x !== null) {
                        !(x.flags & 65536) && (x.flags |= 256),
                        ph(x, o, l, s, t),
                        Qf(ds(a, l));
                        break e
                    }
                }
                s = a = ds(a, l),
                rt !== 4 && (rt = 2),
                oo === null ? oo = [s] : oo.push(s),
                s = o;
                do {
                    switch (s.tag) {
                    case 3:
                        s.flags |= 65536,
                        t &= -t,
                        s.lanes |= t;
                        var v = T0(s, a, t);
                        sh(s, v);
                        break e;
                    case 1:
                        l = a;
                        var w = s.type
                          , y = s.stateNode;
                        if (!(s.flags & 128) && (typeof w.getDerivedStateFromError == "function" || y !== null && typeof y.componentDidCatch == "function" && (jr === null || !jr.has(y)))) {
                            s.flags |= 65536,
                            t &= -t,
                            s.lanes |= t;
                            var b = P0(s, l, t);
                            sh(s, b);
                            break e
                        }
                    }
                    s = s.return
                } while (s !== null)
            }
            q0(n)
        } catch (C) {
            t = C,
            et === n && n !== null && (et = n = n.return);
            continue
        }
        break
    } while (1)
}
function G0() {
    var e = Il.current;
    return Il.current = Rl,
    e === null ? Rl : e
}
function _p() {
    (rt === 0 || rt === 3 || rt === 2) && (rt = 4),
    st === null || !(pi & 268435455) && !(lc & 268435455) || yr(st, ut)
}
function Ol(e, t) {
    var n = ve;
    ve |= 2;
    var r = G0();
    (st !== e || ut !== t) && (Bn = null,
    li(e, t));
    do
        try {
            aS();
            break
        } catch (i) {
            H0(e, i)
        }
    while (1);
    if (Zf(),
    ve = n,
    Il.current = r,
    et !== null)
        throw Error(z(261));
    return st = null,
    ut = 0,
    rt
}
function aS() {
    for (; et !== null; )
        W0(et)
}
function lS() {
    for (; et !== null && !L1(); )
        W0(et)
}
function W0(e) {
    var t = K0(e.alternate, e, Vt);
    e.memoizedProps = e.pendingProps,
    t === null ? q0(e) : et = t,
    fp.current = null
}
function q0(e) {
    var t = e;
    do {
        var n = t.alternate;
        if (e = t.return,
        t.flags & 32768) {
            if (n = tS(n, t),
            n !== null) {
                n.flags &= 32767,
                et = n;
                return
            }
            if (e !== null)
                e.flags |= 32768,
                e.subtreeFlags = 0,
                e.deletions = null;
            else {
                rt = 6,
                et = null;
                return
            }
        } else if (n = eS(n, t, Vt),
        n !== null) {
            et = n;
            return
        }
        if (t = t.sibling,
        t !== null) {
            et = t;
            return
        }
        et = t = e
    } while (t !== null);
    rt === 0 && (rt = 5)
}
function Xr(e, t, n) {
    var r = be
      , i = sn.transition;
    try {
        sn.transition = null,
        be = 1,
        cS(e, t, n, r)
    } finally {
        sn.transition = i,
        be = r
    }
    return null
}
function cS(e, t, n, r) {
    do
        is();
    while (xr !== null);
    if (ve & 6)
        throw Error(z(327));
    n = e.finishedWork;
    var i = e.finishedLanes;
    if (n === null)
        return null;
    if (e.finishedWork = null,
    e.finishedLanes = 0,
    n === e.current)
        throw Error(z(177));
    e.callbackNode = null,
    e.callbackPriority = 0;
    var s = n.lanes | n.childLanes;
    if (U1(e, s),
    e === st && (et = st = null,
    ut = 0),
    !(n.subtreeFlags & 2064) && !(n.flags & 2064) || Ra || (Ra = !0,
    X0(gl, function() {
        return is(),
        null
    })),
    s = (n.flags & 15990) !== 0,
    n.subtreeFlags & 15990 || s) {
        s = sn.transition,
        sn.transition = null;
        var o = be;
        be = 1;
        var l = ve;
        ve |= 4,
        fp.current = null,
        rS(e, n),
        z0(n, e),
        jb(yd),
        vl = !!vd,
        yd = vd = null,
        e.current = n,
        iS(n),
        M1(),
        ve = l,
        be = o,
        sn.transition = s
    } else
        e.current = n;
    if (Ra && (Ra = !1,
    xr = e,
    Ml = i),
    s = e.pendingLanes,
    s === 0 && (jr = null),
    D1(n.stateNode),
    It(e, Ye()),
    t !== null)
        for (r = e.onRecoverableError,
        n = 0; n < t.length; n++)
            i = t[n],
            r(i.value, {
                componentStack: i.stack,
                digest: i.digest
            });
    if (Ll)
        throw Ll = !1,
        e = Fd,
        Fd = null,
        e;
    return Ml & 1 && e.tag !== 0 && is(),
    s = e.pendingLanes,
    s & 1 ? e === Vd ? ao++ : (ao = 0,
    Vd = e) : ao = 0,
    Br(),
    null
}
function is() {
    if (xr !== null) {
        var e = Tv(Ml)
          , t = sn.transition
          , n = be;
        try {
            if (sn.transition = null,
            be = 16 > e ? 16 : e,
            xr === null)
                var r = !1;
            else {
                if (e = xr,
                xr = null,
                Ml = 0,
                ve & 6)
                    throw Error(z(331));
                var i = ve;
                for (ve |= 4,
                Y = e.current; Y !== null; ) {
                    var s = Y
                      , o = s.child;
                    if (Y.flags & 16) {
                        var l = s.deletions;
                        if (l !== null) {
                            for (var a = 0; a < l.length; a++) {
                                var c = l[a];
                                for (Y = c; Y !== null; ) {
                                    var d = Y;
                                    switch (d.tag) {
                                    case 0:
                                    case 11:
                                    case 15:
                                        so(8, d, s)
                                    }
                                    var f = d.child;
                                    if (f !== null)
                                        f.return = d,
                                        Y = f;
                                    else
                                        for (; Y !== null; ) {
                                            d = Y;
                                            var p = d.sibling
                                              , h = d.return;
                                            if ($0(d),
                                            d === c) {
                                                Y = null;
                                                break
                                            }
                                            if (p !== null) {
                                                p.return = h,
                                                Y = p;
                                                break
                                            }
                                            Y = h
                                        }
                                }
                            }
                            var m = s.alternate;
                            if (m !== null) {
                                var _ = m.child;
                                if (_ !== null) {
                                    m.child = null;
                                    do {
                                        var x = _.sibling;
                                        _.sibling = null,
                                        _ = x
                                    } while (_ !== null)
                                }
                            }
                            Y = s
                        }
                    }
                    if (s.subtreeFlags & 2064 && o !== null)
                        o.return = s,
                        Y = o;
                    else
                        e: for (; Y !== null; ) {
                            if (s = Y,
                            s.flags & 2048)
                                switch (s.tag) {
                                case 0:
                                case 11:
                                case 15:
                                    so(9, s, s.return)
                                }
                            var v = s.sibling;
                            if (v !== null) {
                                v.return = s.return,
                                Y = v;
                                break e
                            }
                            Y = s.return
                        }
                }
                var w = e.current;
                for (Y = w; Y !== null; ) {
                    o = Y;
                    var y = o.child;
                    if (o.subtreeFlags & 2064 && y !== null)
                        y.return = o,
                        Y = y;
                    else
                        e: for (o = w; Y !== null; ) {
                            if (l = Y,
                            l.flags & 2048)
                                try {
                                    switch (l.tag) {
                                    case 0:
                                    case 11:
                                    case 15:
                                        ac(9, l)
                                    }
                                } catch (C) {
                                    Ge(l, l.return, C)
                                }
                            if (l === o) {
                                Y = null;
                                break e
                            }
                            var b = l.sibling;
                            if (b !== null) {
                                b.return = l.return,
                                Y = b;
                                break e
                            }
                            Y = l.return
                        }
                }
                if (ve = i,
                Br(),
                Ln && typeof Ln.onPostCommitFiberRoot == "function")
                    try {
                        Ln.onPostCommitFiberRoot(Zl, e)
                    } catch {}
                r = !0
            }
            return r
        } finally {
            be = n,
            sn.transition = t
        }
    }
    return !1
}
function Eh(e, t, n) {
    t = ds(n, t),
    t = T0(e, t, 1),
    e = Pr(e, t, 1),
    t = Ct(),
    e !== null && (Xo(e, 1, t),
    It(e, t))
}
function Ge(e, t, n) {
    if (e.tag === 3)
        Eh(e, e, n);
    else
        for (; t !== null; ) {
            if (t.tag === 3) {
                Eh(t, e, n);
                break
            } else if (t.tag === 1) {
                var r = t.stateNode;
                if (typeof t.type.getDerivedStateFromError == "function" || typeof r.componentDidCatch == "function" && (jr === null || !jr.has(r))) {
                    e = ds(n, e),
                    e = P0(t, e, 1),
                    t = Pr(t, e, 1),
                    e = Ct(),
                    t !== null && (Xo(t, 1, e),
                    It(t, e));
                    break
                }
            }
            t = t.return
        }
}
function uS(e, t, n) {
    var r = e.pingCache;
    r !== null && r.delete(t),
    t = Ct(),
    e.pingedLanes |= e.suspendedLanes & n,
    st === e && (ut & n) === n && (rt === 4 || rt === 3 && (ut & 130023424) === ut && 500 > Ye() - mp ? li(e, 0) : pp |= n),
    It(e, t)
}
function Y0(e, t) {
    t === 0 && (e.mode & 1 ? (t = xa,
    xa <<= 1,
    !(xa & 130023424) && (xa = 4194304)) : t = 1);
    var n = Ct();
    e = Zn(e, t),
    e !== null && (Xo(e, t, n),
    It(e, n))
}
function dS(e) {
    var t = e.memoizedState
      , n = 0;
    t !== null && (n = t.retryLane),
    Y0(e, n)
}
function fS(e, t) {
    var n = 0;
    switch (e.tag) {
    case 13:
        var r = e.stateNode
          , i = e.memoizedState;
        i !== null && (n = i.retryLane);
        break;
    case 19:
        r = e.stateNode;
        break;
    default:
        throw Error(z(314))
    }
    r !== null && r.delete(t),
    Y0(e, n)
}
var K0;
K0 = function(e, t, n) {
    if (e !== null)
        if (e.memoizedProps !== t.pendingProps || Nt.current)
            jt = !0;
        else {
            if (!(e.lanes & n) && !(t.flags & 128))
                return jt = !1,
                Zb(e, t, n);
            jt = !!(e.flags & 131072)
        }
    else
        jt = !1,
        Ae && t.flags & 1048576 && Zv(t, kl, t.index);
    switch (t.lanes = 0,
    t.tag) {
    case 2:
        var r = t.type;
        tl(e, t),
        e = t.pendingProps;
        var i = as(t, yt.current);
        rs(t, n),
        i = ap(null, t, r, e, i, n);
        var s = lp();
        return t.flags |= 1,
        typeof i == "object" && i !== null && typeof i.render == "function" && i.$$typeof === void 0 ? (t.tag = 1,
        t.memoizedState = null,
        t.updateQueue = null,
        Rt(r) ? (s = !0,
        Sl(t)) : s = !1,
        t.memoizedState = i.state !== null && i.state !== void 0 ? i.state : null,
        np(t),
        i.updater = oc,
        t.stateNode = i,
        i._reactInternals = t,
        Pd(t, r, e, n),
        t = Rd(null, t, r, !0, s, n)) : (t.tag = 0,
        Ae && s && Kf(t),
        St(null, t, i, n),
        t = t.child),
        t;
    case 16:
        r = t.elementType;
        e: {
            switch (tl(e, t),
            e = t.pendingProps,
            i = r._init,
            r = i(r._payload),
            t.type = r,
            i = t.tag = mS(r),
            e = mn(r, e),
            i) {
            case 0:
                t = Nd(null, t, r, e, n);
                break e;
            case 1:
                t = gh(null, t, r, e, n);
                break e;
            case 11:
                t = mh(null, t, r, e, n);
                break e;
            case 14:
                t = hh(null, t, r, mn(r.type, e), n);
                break e
            }
            throw Error(z(306, r, ""))
        }
        return t;
    case 0:
        return r = t.type,
        i = t.pendingProps,
        i = t.elementType === r ? i : mn(r, i),
        Nd(e, t, r, i, n);
    case 1:
        return r = t.type,
        i = t.pendingProps,
        i = t.elementType === r ? i : mn(r, i),
        gh(e, t, r, i, n);
    case 3:
        e: {
            if (I0(t),
            e === null)
                throw Error(z(387));
            r = t.pendingProps,
            s = t.memoizedState,
            i = s.element,
            s0(e, t),
            Pl(t, r, null, n);
            var o = t.memoizedState;
            if (r = o.element,
            s.isDehydrated)
                if (s = {
                    element: r,
                    isDehydrated: !1,
                    cache: o.cache,
                    pendingSuspenseBoundaries: o.pendingSuspenseBoundaries,
                    transitions: o.transitions
                },
                t.updateQueue.baseState = s,
                t.memoizedState = s,
                t.flags & 256) {
                    i = ds(Error(z(423)), t),
                    t = _h(e, t, r, n, i);
                    break e
                } else if (r !== i) {
                    i = ds(Error(z(424)), t),
                    t = _h(e, t, r, n, i);
                    break e
                } else
                    for (Ut = Tr(t.stateNode.containerInfo.firstChild),
                    Ht = t,
                    Ae = !0,
                    _n = null,
                    n = r0(t, null, r, n),
                    t.child = n; n; )
                        n.flags = n.flags & -3 | 4096,
                        n = n.sibling;
            else {
                if (ls(),
                r === i) {
                    t = er(e, t, n);
                    break e
                }
                St(e, t, r, n)
            }
            t = t.child
        }
        return t;
    case 5:
        return o0(t),
        e === null && kd(t),
        r = t.type,
        i = t.pendingProps,
        s = e !== null ? e.memoizedProps : null,
        o = i.children,
        wd(r, i) ? o = null : s !== null && wd(r, s) && (t.flags |= 32),
        R0(e, t),
        St(e, t, o, n),
        t.child;
    case 6:
        return e === null && kd(t),
        null;
    case 13:
        return L0(e, t, n);
    case 4:
        return rp(t, t.stateNode.containerInfo),
        r = t.pendingProps,
        e === null ? t.child = cs(t, null, r, n) : St(e, t, r, n),
        t.child;
    case 11:
        return r = t.type,
        i = t.pendingProps,
        i = t.elementType === r ? i : mn(r, i),
        mh(e, t, r, i, n);
    case 7:
        return St(e, t, t.pendingProps, n),
        t.child;
    case 8:
        return St(e, t, t.pendingProps.children, n),
        t.child;
    case 12:
        return St(e, t, t.pendingProps.children, n),
        t.child;
    case 10:
        e: {
            if (r = t.type._context,
            i = t.pendingProps,
            s = t.memoizedProps,
            o = i.value,
            je(El, r._currentValue),
            r._currentValue = o,
            s !== null)
                if (bn(s.value, o)) {
                    if (s.children === i.children && !Nt.current) {
                        t = er(e, t, n);
                        break e
                    }
                } else
                    for (s = t.child,
                    s !== null && (s.return = t); s !== null; ) {
                        var l = s.dependencies;
                        if (l !== null) {
                            o = s.child;
                            for (var a = l.firstContext; a !== null; ) {
                                if (a.context === r) {
                                    if (s.tag === 1) {
                                        a = qn(-1, n & -n),
                                        a.tag = 2;
                                        var c = s.updateQueue;
                                        if (c !== null) {
                                            c = c.shared;
                                            var d = c.pending;
                                            d === null ? a.next = a : (a.next = d.next,
                                            d.next = a),
                                            c.pending = a
                                        }
                                    }
                                    s.lanes |= n,
                                    a = s.alternate,
                                    a !== null && (a.lanes |= n),
                                    Ed(s.return, n, t),
                                    l.lanes |= n;
                                    break
                                }
                                a = a.next
                            }
                        } else if (s.tag === 10)
                            o = s.type === t.type ? null : s.child;
                        else if (s.tag === 18) {
                            if (o = s.return,
                            o === null)
                                throw Error(z(341));
                            o.lanes |= n,
                            l = o.alternate,
                            l !== null && (l.lanes |= n),
                            Ed(o, n, t),
                            o = s.sibling
                        } else
                            o = s.child;
                        if (o !== null)
                            o.return = s;
                        else
                            for (o = s; o !== null; ) {
                                if (o === t) {
                                    o = null;
                                    break
                                }
                                if (s = o.sibling,
                                s !== null) {
                                    s.return = o.return,
                                    o = s;
                                    break
                                }
                                o = o.return
                            }
                        s = o
                    }
            St(e, t, i.children, n),
            t = t.child
        }
        return t;
    case 9:
        return i = t.type,
        r = t.pendingProps.children,
        rs(t, n),
        i = an(i),
        r = r(i),
        t.flags |= 1,
        St(e, t, r, n),
        t.child;
    case 14:
        return r = t.type,
        i = mn(r, t.pendingProps),
        i = mn(r.type, i),
        hh(e, t, r, i, n);
    case 15:
        return j0(e, t, t.type, t.pendingProps, n);
    case 17:
        return r = t.type,
        i = t.pendingProps,
        i = t.elementType === r ? i : mn(r, i),
        tl(e, t),
        t.tag = 1,
        Rt(r) ? (e = !0,
        Sl(t)) : e = !1,
        rs(t, n),
        E0(t, r, i),
        Pd(t, r, i, n),
        Rd(null, t, r, !0, e, n);
    case 19:
        return M0(e, t, n);
    case 22:
        return N0(e, t, n)
    }
    throw Error(z(156, t.tag))
}
;
function X0(e, t) {
    return Sv(e, t)
}
function pS(e, t, n, r) {
    this.tag = e,
    this.key = n,
    this.sibling = this.child = this.return = this.stateNode = this.type = this.elementType = null,
    this.index = 0,
    this.ref = null,
    this.pendingProps = t,
    this.dependencies = this.memoizedState = this.updateQueue = this.memoizedProps = null,
    this.mode = r,
    this.subtreeFlags = this.flags = 0,
    this.deletions = null,
    this.childLanes = this.lanes = 0,
    this.alternate = null
}
function rn(e, t, n, r) {
    return new pS(e,t,n,r)
}
function vp(e) {
    return e = e.prototype,
    !(!e || !e.isReactComponent)
}
function mS(e) {
    if (typeof e == "function")
        return vp(e) ? 1 : 0;
    if (e != null) {
        if (e = e.$$typeof,
        e === Df)
            return 11;
        if (e === $f)
            return 14
    }
    return 2
}
function Rr(e, t) {
    var n = e.alternate;
    return n === null ? (n = rn(e.tag, t, e.key, e.mode),
    n.elementType = e.elementType,
    n.type = e.type,
    n.stateNode = e.stateNode,
    n.alternate = e,
    e.alternate = n) : (n.pendingProps = t,
    n.type = e.type,
    n.flags = 0,
    n.subtreeFlags = 0,
    n.deletions = null),
    n.flags = e.flags & 14680064,
    n.childLanes = e.childLanes,
    n.lanes = e.lanes,
    n.child = e.child,
    n.memoizedProps = e.memoizedProps,
    n.memoizedState = e.memoizedState,
    n.updateQueue = e.updateQueue,
    t = e.dependencies,
    n.dependencies = t === null ? null : {
        lanes: t.lanes,
        firstContext: t.firstContext
    },
    n.sibling = e.sibling,
    n.index = e.index,
    n.ref = e.ref,
    n
}
function il(e, t, n, r, i, s) {
    var o = 2;
    if (r = e,
    typeof e == "function")
        vp(e) && (o = 1);
    else if (typeof e == "string")
        o = 5;
    else
        e: switch (e) {
        case $i:
            return ci(n.children, i, s, t);
        case Af:
            o = 8,
            i |= 8;
            break;
        case Ju:
            return e = rn(12, n, t, i | 2),
            e.elementType = Ju,
            e.lanes = s,
            e;
        case Zu:
            return e = rn(13, n, t, i),
            e.elementType = Zu,
            e.lanes = s,
            e;
        case ed:
            return e = rn(19, n, t, i),
            e.elementType = ed,
            e.lanes = s,
            e;
        case ov:
            return cc(n, i, s, t);
        default:
            if (typeof e == "object" && e !== null)
                switch (e.$$typeof) {
                case iv:
                    o = 10;
                    break e;
                case sv:
                    o = 9;
                    break e;
                case Df:
                    o = 11;
                    break e;
                case $f:
                    o = 14;
                    break e;
                case hr:
                    o = 16,
                    r = null;
                    break e
                }
            throw Error(z(130, e == null ? e : typeof e, ""))
        }
    return t = rn(o, n, t, i),
    t.elementType = e,
    t.type = r,
    t.lanes = s,
    t
}
function ci(e, t, n, r) {
    return e = rn(7, e, r, t),
    e.lanes = n,
    e
}
function cc(e, t, n, r) {
    return e = rn(22, e, r, t),
    e.elementType = ov,
    e.lanes = n,
    e.stateNode = {
        isHidden: !1
    },
    e
}
function du(e, t, n) {
    return e = rn(6, e, null, t),
    e.lanes = n,
    e
}
function fu(e, t, n) {
    return t = rn(4, e.children !== null ? e.children : [], e.key, t),
    t.lanes = n,
    t.stateNode = {
        containerInfo: e.containerInfo,
        pendingChildren: null,
        implementation: e.implementation
    },
    t
}
function hS(e, t, n, r, i) {
    this.tag = t,
    this.containerInfo = e,
    this.finishedWork = this.pingCache = this.current = this.pendingChildren = null,
    this.timeoutHandle = -1,
    this.callbackNode = this.pendingContext = this.context = null,
    this.callbackPriority = 0,
    this.eventTimes = Wc(0),
    this.expirationTimes = Wc(-1),
    this.entangledLanes = this.finishedLanes = this.mutableReadLanes = this.expiredLanes = this.pingedLanes = this.suspendedLanes = this.pendingLanes = 0,
    this.entanglements = Wc(0),
    this.identifierPrefix = r,
    this.onRecoverableError = i,
    this.mutableSourceEagerHydrationData = null
}
function yp(e, t, n, r, i, s, o, l, a) {
    return e = new hS(e,t,n,l,a),
    t === 1 ? (t = 1,
    s === !0 && (t |= 8)) : t = 0,
    s = rn(3, null, null, t),
    e.current = s,
    s.stateNode = e,
    s.memoizedState = {
        element: r,
        isDehydrated: n,
        cache: null,
        transitions: null,
        pendingSuspenseBoundaries: null
    },
    np(s),
    e
}
function gS(e, t, n) {
    var r = 3 < arguments.length && arguments[3] !== void 0 ? arguments[3] : null;
    return {
        $$typeof: Di,
        key: r == null ? null : "" + r,
        children: e,
        containerInfo: t,
        implementation: n
    }
}
function Q0(e) {
    if (!e)
        return Ar;
    e = e._reactInternals;
    e: {
        if (wi(e) !== e || e.tag !== 1)
            throw Error(z(170));
        var t = e;
        do {
            switch (t.tag) {
            case 3:
                t = t.stateNode.context;
                break e;
            case 1:
                if (Rt(t.type)) {
                    t = t.stateNode.__reactInternalMemoizedMergedChildContext;
                    break e
                }
            }
            t = t.return
        } while (t !== null);
        throw Error(z(171))
    }
    if (e.tag === 1) {
        var n = e.type;
        if (Rt(n))
            return Qv(e, n, t)
    }
    return t
}
function J0(e, t, n, r, i, s, o, l, a) {
    return e = yp(n, r, !0, e, i, s, o, l, a),
    e.context = Q0(null),
    n = e.current,
    r = Ct(),
    i = Nr(n),
    s = qn(r, i),
    s.callback = t ?? null,
    Pr(n, s, i),
    e.current.lanes = i,
    Xo(e, i, r),
    It(e, r),
    e
}
function uc(e, t, n, r) {
    var i = t.current
      , s = Ct()
      , o = Nr(i);
    return n = Q0(n),
    t.context === null ? t.context = n : t.pendingContext = n,
    t = qn(s, o),
    t.payload = {
        element: e
    },
    r = r === void 0 ? null : r,
    r !== null && (t.callback = r),
    e = Pr(i, t, o),
    e !== null && (yn(e, i, o, s),
    Ja(e, i, o)),
    o
}
function Al(e) {
    if (e = e.current,
    !e.child)
        return null;
    switch (e.child.tag) {
    case 5:
        return e.child.stateNode;
    default:
        return e.child.stateNode
    }
}
function Th(e, t) {
    if (e = e.memoizedState,
    e !== null && e.dehydrated !== null) {
        var n = e.retryLane;
        e.retryLane = n !== 0 && n < t ? n : t
    }
}
function wp(e, t) {
    Th(e, t),
    (e = e.alternate) && Th(e, t)
}
function _S() {
    return null
}
var Z0 = typeof reportError == "function" ? reportError : function(e) {
    console.error(e)
}
;
function xp(e) {
    this._internalRoot = e
}
dc.prototype.render = xp.prototype.render = function(e) {
    var t = this._internalRoot;
    if (t === null)
        throw Error(z(409));
    uc(e, t, null, null)
}
;
dc.prototype.unmount = xp.prototype.unmount = function() {
    var e = this._internalRoot;
    if (e !== null) {
        this._internalRoot = null;
        var t = e.containerInfo;
        mi(function() {
            uc(null, e, null, null)
        }),
        t[Jn] = null
    }
}
;
function dc(e) {
    this._internalRoot = e
}
dc.prototype.unstable_scheduleHydration = function(e) {
    if (e) {
        var t = Nv();
        e = {
            blockedOn: null,
            target: e,
            priority: t
        };
        for (var n = 0; n < vr.length && t !== 0 && t < vr[n].priority; n++)
            ;
        vr.splice(n, 0, e),
        n === 0 && Iv(e)
    }
}
;
function bp(e) {
    return !(!e || e.nodeType !== 1 && e.nodeType !== 9 && e.nodeType !== 11)
}
function fc(e) {
    return !(!e || e.nodeType !== 1 && e.nodeType !== 9 && e.nodeType !== 11 && (e.nodeType !== 8 || e.nodeValue !== " react-mount-point-unstable "))
}
function Ph() {}
function vS(e, t, n, r, i) {
    if (i) {
        if (typeof r == "function") {
            var s = r;
            r = function() {
                var c = Al(o);
                s.call(c)
            }
        }
        var o = J0(t, r, e, 0, null, !1, !1, "", Ph);
        return e._reactRootContainer = o,
        e[Jn] = o.current,
        ko(e.nodeType === 8 ? e.parentNode : e),
        mi(),
        o
    }
    for (; i = e.lastChild; )
        e.removeChild(i);
    if (typeof r == "function") {
        var l = r;
        r = function() {
            var c = Al(a);
            l.call(c)
        }
    }
    var a = yp(e, 0, !1, null, null, !1, !1, "", Ph);
    return e._reactRootContainer = a,
    e[Jn] = a.current,
    ko(e.nodeType === 8 ? e.parentNode : e),
    mi(function() {
        uc(t, a, n, r)
    }),
    a
}
function pc(e, t, n, r, i) {
    var s = n._reactRootContainer;
    if (s) {
        var o = s;
        if (typeof i == "function") {
            var l = i;
            i = function() {
                var a = Al(o);
                l.call(a)
            }
        }
        uc(t, o, e, i)
    } else
        o = vS(n, t, e, i, r);
    return Al(o)
}
Pv = function(e) {
    switch (e.tag) {
    case 3:
        var t = e.stateNode;
        if (t.current.memoizedState.isDehydrated) {
            var n = Ks(t.pendingLanes);
            n !== 0 && (zf(t, n | 1),
            It(t, Ye()),
            !(ve & 6) && (fs = Ye() + 500,
            Br()))
        }
        break;
    case 13:
        mi(function() {
            var r = Zn(e, 1);
            if (r !== null) {
                var i = Ct();
                yn(r, e, 1, i)
            }
        }),
        wp(e, 1)
    }
}
;
Bf = function(e) {
    if (e.tag === 13) {
        var t = Zn(e, 134217728);
        if (t !== null) {
            var n = Ct();
            yn(t, e, 134217728, n)
        }
        wp(e, 134217728)
    }
}
;
jv = function(e) {
    if (e.tag === 13) {
        var t = Nr(e)
          , n = Zn(e, t);
        if (n !== null) {
            var r = Ct();
            yn(n, e, t, r)
        }
        wp(e, t)
    }
}
;
Nv = function() {
    return be
}
;
Rv = function(e, t) {
    var n = be;
    try {
        return be = e,
        t()
    } finally {
        be = n
    }
}
;
ud = function(e, t, n) {
    switch (t) {
    case "input":
        if (rd(e, n),
        t = n.name,
        n.type === "radio" && t != null) {
            for (n = e; n.parentNode; )
                n = n.parentNode;
            for (n = n.querySelectorAll("input[name=" + JSON.stringify("" + t) + '][type="radio"]'),
            t = 0; t < n.length; t++) {
                var r = n[t];
                if (r !== e && r.form === e.form) {
                    var i = rc(r);
                    if (!i)
                        throw Error(z(90));
                    lv(r),
                    rd(r, i)
                }
            }
        }
        break;
    case "textarea":
        uv(e, n);
        break;
    case "select":
        t = n.value,
        t != null && Zi(e, !!n.multiple, t, !1)
    }
}
;
_v = hp;
vv = mi;
var yS = {
    usingClientEntryPoint: !1,
    Events: [Jo, Bi, rc, hv, gv, hp]
}
  , Os = {
    findFiberByHostInstance: ti,
    bundleType: 0,
    version: "18.3.1",
    rendererPackageName: "react-dom"
}
  , wS = {
    bundleType: Os.bundleType,
    version: Os.version,
    rendererPackageName: Os.rendererPackageName,
    rendererConfig: Os.rendererConfig,
    overrideHookState: null,
    overrideHookStateDeletePath: null,
    overrideHookStateRenamePath: null,
    overrideProps: null,
    overridePropsDeletePath: null,
    overridePropsRenamePath: null,
    setErrorHandler: null,
    setSuspenseHandler: null,
    scheduleUpdate: null,
    currentDispatcherRef: nr.ReactCurrentDispatcher,
    findHostInstanceByFiber: function(e) {
        return e = xv(e),
        e === null ? null : e.stateNode
    },
    findFiberByHostInstance: Os.findFiberByHostInstance || _S,
    findHostInstancesForRefresh: null,
    scheduleRefresh: null,
    scheduleRoot: null,
    setRefreshHandler: null,
    getCurrentFiber: null,
    reconcilerVersion: "18.3.1-next-f1338f8080-20240426"
};
if (typeof __REACT_DEVTOOLS_GLOBAL_HOOK__ < "u") {
    var Ia = __REACT_DEVTOOLS_GLOBAL_HOOK__;
    if (!Ia.isDisabled && Ia.supportsFiber)
        try {
            Zl = Ia.inject(wS),
            Ln = Ia
        } catch {}
}
Yt.__SECRET_INTERNALS_DO_NOT_USE_OR_YOU_WILL_BE_FIRED = yS;
Yt.createPortal = function(e, t) {
    var n = 2 < arguments.length && arguments[2] !== void 0 ? arguments[2] : null;
    if (!bp(t))
        throw Error(z(200));
    return gS(e, t, null, n)
}
;
Yt.createRoot = function(e, t) {
    if (!bp(e))
        throw Error(z(299));
    var n = !1
      , r = ""
      , i = Z0;
    return t != null && (t.unstable_strictMode === !0 && (n = !0),
    t.identifierPrefix !== void 0 && (r = t.identifierPrefix),
    t.onRecoverableError !== void 0 && (i = t.onRecoverableError)),
    t = yp(e, 1, !1, null, null, n, !1, r, i),
    e[Jn] = t.current,
    ko(e.nodeType === 8 ? e.parentNode : e),
    new xp(t)
}
;
Yt.findDOMNode = function(e) {
    if (e == null)
        return null;
    if (e.nodeType === 1)
        return e;
    var t = e._reactInternals;
    if (t === void 0)
        throw typeof e.render == "function" ? Error(z(188)) : (e = Object.keys(e).join(","),
        Error(z(268, e)));
    return e = xv(t),
    e = e === null ? null : e.stateNode,
    e
}
;
Yt.flushSync = function(e) {
    return mi(e)
}
;
Yt.hydrate = function(e, t, n) {
    if (!fc(t))
        throw Error(z(200));
    return pc(null, e, t, !0, n)
}
;
Yt.hydrateRoot = function(e, t, n) {
    if (!bp(e))
        throw Error(z(405));
    var r = n != null && n.hydratedSources || null
      , i = !1
      , s = ""
      , o = Z0;
    if (n != null && (n.unstable_strictMode === !0 && (i = !0),
    n.identifierPrefix !== void 0 && (s = n.identifierPrefix),
    n.onRecoverableError !== void 0 && (o = n.onRecoverableError)),
    t = J0(t, null, e, 1, n ?? null, i, !1, s, o),
    e[Jn] = t.current,
    ko(e),
    r)
        for (e = 0; e < r.length; e++)
            n = r[e],
            i = n._getVersion,
            i = i(n._source),
            t.mutableSourceEagerHydrationData == null ? t.mutableSourceEagerHydrationData = [n, i] : t.mutableSourceEagerHydrationData.push(n, i);
    return new dc(t)
}
;
Yt.render = function(e, t, n) {
    if (!fc(t))
        throw Error(z(200));
    return pc(null, e, t, !1, n)
}
;
Yt.unmountComponentAtNode = function(e) {
    if (!fc(e))
        throw Error(z(40));
    return e._reactRootContainer ? (mi(function() {
        pc(null, null, e, !1, function() {
            e._reactRootContainer = null,
            e[Jn] = null
        })
    }),
    !0) : !1
}
;
Yt.unstable_batchedUpdates = hp;
Yt.unstable_renderSubtreeIntoContainer = function(e, t, n, r) {
    if (!fc(n))
        throw Error(z(200));
    if (e == null || e._reactInternals === void 0)
        throw Error(z(38));
    return pc(e, t, n, !1, r)
}
;
Yt.version = "18.3.1-next-f1338f8080-20240426";
function ey() {
    if (!(typeof __REACT_DEVTOOLS_GLOBAL_HOOK__ > "u" || typeof __REACT_DEVTOOLS_GLOBAL_HOOK__.checkDCE != "function"))
        try {
            __REACT_DEVTOOLS_GLOBAL_HOOK__.checkDCE(ey)
        } catch (e) {
            console.error(e)
        }
}
ey(),
ev.exports = Yt;
var mc = ev.exports;
const xS = Yo(mc)
  , bS = B_({
    __proto__: null,
    default: xS
}, [mc]);
var jh = mc;
Xu.createRoot = jh.createRoot,
Xu.hydrateRoot = jh.hydrateRoot;
/**
 * @remix-run/router v1.17.0
 *
 * Copyright (c) Remix Software Inc.
 *
 * This source code is licensed under the MIT license found in the
 * LICENSE.md file in the root directory of this source tree.
 *
 * @license MIT
 */
function De() {
    return De = Object.assign ? Object.assign.bind() : function(e) {
        for (var t = 1; t < arguments.length; t++) {
            var n = arguments[t];
            for (var r in n)
                Object.prototype.hasOwnProperty.call(n, r) && (e[r] = n[r])
        }
        return e
    }
    ,
    De.apply(this, arguments)
}
var qe;
(function(e) {
    e.Pop = "POP",
    e.Push = "PUSH",
    e.Replace = "REPLACE"
}
)(qe || (qe = {}));
const Nh = "popstate";
function SS(e) {
    e === void 0 && (e = {});
    function t(r, i) {
        let {pathname: s, search: o, hash: l} = r.location;
        return Mo("", {
            pathname: s,
            search: o,
            hash: l
        }, i.state && i.state.usr || null, i.state && i.state.key || "default")
    }
    function n(r, i) {
        return typeof i == "string" ? i : hi(i)
    }
    return kS(t, n, null, e)
}
function ce(e, t) {
    if (e === !1 || e === null || typeof e > "u")
        throw new Error(t)
}
function ps(e, t) {
    if (!e) {
        typeof console < "u" && console.warn(t);
        try {
            throw new Error(t)
        } catch {}
    }
}
function CS() {
    return Math.random().toString(36).substr(2, 8)
}
function Rh(e, t) {
    return {
        usr: e.state,
        key: e.key,
        idx: t
    }
}
function Mo(e, t, n, r) {
    return n === void 0 && (n = null),
    De({
        pathname: typeof e == "string" ? e : e.pathname,
        search: "",
        hash: ""
    }, typeof t == "string" ? rr(t) : t, {
        state: n,
        key: t && t.key || r || CS()
    })
}
function hi(e) {
    let {pathname: t="/", search: n="", hash: r=""} = e;
    return n && n !== "?" && (t += n.charAt(0) === "?" ? n : "?" + n),
    r && r !== "#" && (t += r.charAt(0) === "#" ? r : "#" + r),
    t
}
function rr(e) {
    let t = {};
    if (e) {
        let n = e.indexOf("#");
        n >= 0 && (t.hash = e.substr(n),
        e = e.substr(0, n));
        let r = e.indexOf("?");
        r >= 0 && (t.search = e.substr(r),
        e = e.substr(0, r)),
        e && (t.pathname = e)
    }
    return t
}
function kS(e, t, n, r) {
    r === void 0 && (r = {});
    let {window: i=document.defaultView, v5Compat: s=!1} = r
      , o = i.history
      , l = qe.Pop
      , a = null
      , c = d();
    c == null && (c = 0,
    o.replaceState(De({}, o.state, {
        idx: c
    }), ""));
    function d() {
        return (o.state || {
            idx: null
        }).idx
    }
    function f() {
        l = qe.Pop;
        let x = d()
          , v = x == null ? null : x - c;
        c = x,
        a && a({
            action: l,
            location: _.location,
            delta: v
        })
    }
    function p(x, v) {
        l = qe.Push;
        let w = Mo(_.location, x, v);
        n && n(w, x),
        c = d() + 1;
        let y = Rh(w, c)
          , b = _.createHref(w);
        try {
            o.pushState(y, "", b)
        } catch (C) {
            if (C instanceof DOMException && C.name === "DataCloneError")
                throw C;
            i.location.assign(b)
        }
        s && a && a({
            action: l,
            location: _.location,
            delta: 1
        })
    }
    function h(x, v) {
        l = qe.Replace;
        let w = Mo(_.location, x, v);
        n && n(w, x),
        c = d();
        let y = Rh(w, c)
          , b = _.createHref(w);
        o.replaceState(y, "", b),
        s && a && a({
            action: l,
            location: _.location,
            delta: 0
        })
    }
    function m(x) {
        let v = i.location.origin !== "null" ? i.location.origin : i.location.href
          , w = typeof x == "string" ? x : hi(x);
        return w = w.replace(/ $/, "%20"),
        ce(v, "No window.location.(origin|href) available to create URL for href: " + w),
        new URL(w,v)
    }
    let _ = {
        get action() {
            return l
        },
        get location() {
            return e(i, o)
        },
        listen(x) {
            if (a)
                throw new Error("A history only accepts one active listener");
            return i.addEventListener(Nh, f),
            a = x,
            () => {
                i.removeEventListener(Nh, f),
                a = null
            }
        },
        createHref(x) {
            return t(i, x)
        },
        createURL: m,
        encodeLocation(x) {
            let v = m(x);
            return {
                pathname: v.pathname,
                search: v.search,
                hash: v.hash
            }
        },
        push: p,
        replace: h,
        go(x) {
            return o.go(x)
        }
    };
    return _
}
var Pe;
(function(e) {
    e.data = "data",
    e.deferred = "deferred",
    e.redirect = "redirect",
    e.error = "error"
}
)(Pe || (Pe = {}));
const ES = new Set(["lazy", "caseSensitive", "path", "id", "index", "children"]);
function TS(e) {
    return e.index === !0
}
function Oo(e, t, n, r) {
    return n === void 0 && (n = []),
    r === void 0 && (r = {}),
    e.map( (i, s) => {
        let o = [...n, String(s)]
          , l = typeof i.id == "string" ? i.id : o.join("-");
        if (ce(i.index !== !0 || !i.children, "Cannot specify children on an index route"),
        ce(!r[l], 'Found a route id collision on id "' + l + `".  Route id's must be globally unique within Data Router usages`),
        TS(i)) {
            let a = De({}, i, t(i), {
                id: l
            });
            return r[l] = a,
            a
        } else {
            let a = De({}, i, t(i), {
                id: l,
                children: void 0
            });
            return r[l] = a,
            i.children && (a.children = Oo(i.children, t, o, r)),
            a
        }
    }
    )
}
function ei(e, t, n) {
    return n === void 0 && (n = "/"),
    sl(e, t, n, !1)
}
function sl(e, t, n, r) {
    let i = typeof t == "string" ? rr(t) : t
      , s = xs(i.pathname || "/", n);
    if (s == null)
        return null;
    let o = ty(e);
    jS(o);
    let l = null;
    for (let a = 0; l == null && a < o.length; ++a) {
        let c = VS(s);
        l = $S(o[a], c, r)
    }
    return l
}
function PS(e, t) {
    let {route: n, pathname: r, params: i} = e;
    return {
        id: n.id,
        pathname: r,
        params: i,
        data: t[n.id],
        handle: n.handle
    }
}
function ty(e, t, n, r) {
    t === void 0 && (t = []),
    n === void 0 && (n = []),
    r === void 0 && (r = "");
    let i = (s, o, l) => {
        let a = {
            relativePath: l === void 0 ? s.path || "" : l,
            caseSensitive: s.caseSensitive === !0,
            childrenIndex: o,
            route: s
        };
        a.relativePath.startsWith("/") && (ce(a.relativePath.startsWith(r), 'Absolute route path "' + a.relativePath + '" nested under path ' + ('"' + r + '" is not valid. An absolute child route path ') + "must start with the combined path of all its parent routes."),
        a.relativePath = a.relativePath.slice(r.length));
        let c = Yn([r, a.relativePath])
          , d = n.concat(a);
        s.children && s.children.length > 0 && (ce(s.index !== !0, "Index routes must not have child routes. Please remove " + ('all child routes from route path "' + c + '".')),
        ty(s.children, t, d, c)),
        !(s.path == null && !s.index) && t.push({
            path: c,
            score: AS(c, s.index),
            routesMeta: d
        })
    }
    ;
    return e.forEach( (s, o) => {
        var l;
        if (s.path === "" || !((l = s.path) != null && l.includes("?")))
            i(s, o);
        else
            for (let a of ny(s.path))
                i(s, o, a)
    }
    ),
    t
}
function ny(e) {
    let t = e.split("/");
    if (t.length === 0)
        return [];
    let[n,...r] = t
      , i = n.endsWith("?")
      , s = n.replace(/\?$/, "");
    if (r.length === 0)
        return i ? [s, ""] : [s];
    let o = ny(r.join("/"))
      , l = [];
    return l.push(...o.map(a => a === "" ? s : [s, a].join("/"))),
    i && l.push(...o),
    l.map(a => e.startsWith("/") && a === "" ? "/" : a)
}
function jS(e) {
    e.sort( (t, n) => t.score !== n.score ? n.score - t.score : DS(t.routesMeta.map(r => r.childrenIndex), n.routesMeta.map(r => r.childrenIndex)))
}
const NS = /^:[\w-]+$/
  , RS = 3
  , IS = 2
  , LS = 1
  , MS = 10
  , OS = -2
  , Ih = e => e === "*";
function AS(e, t) {
    let n = e.split("/")
      , r = n.length;
    return n.some(Ih) && (r += OS),
    t && (r += IS),
    n.filter(i => !Ih(i)).reduce( (i, s) => i + (NS.test(s) ? RS : s === "" ? LS : MS), r)
}
function DS(e, t) {
    return e.length === t.length && e.slice(0, -1).every( (r, i) => r === t[i]) ? e[e.length - 1] - t[t.length - 1] : 0
}
function $S(e, t, n) {
    n === void 0 && (n = !1);
    let {routesMeta: r} = e
      , i = {}
      , s = "/"
      , o = [];
    for (let l = 0; l < r.length; ++l) {
        let a = r[l]
          , c = l === r.length - 1
          , d = s === "/" ? t : t.slice(s.length) || "/"
          , f = Lh({
            path: a.relativePath,
            caseSensitive: a.caseSensitive,
            end: c
        }, d)
          , p = a.route;
        if (!f && c && n && !r[r.length - 1].route.index && (f = Lh({
            path: a.relativePath,
            caseSensitive: a.caseSensitive,
            end: !1
        }, d)),
        !f)
            return null;
        Object.assign(i, f.params),
        o.push({
            params: i,
            pathname: Yn([s, f.pathname]),
            pathnameBase: US(Yn([s, f.pathnameBase])),
            route: p
        }),
        f.pathnameBase !== "/" && (s = Yn([s, f.pathnameBase]))
    }
    return o
}
function Lh(e, t) {
    typeof e == "string" && (e = {
        path: e,
        caseSensitive: !1,
        end: !0
    });
    let[n,r] = FS(e.path, e.caseSensitive, e.end)
      , i = t.match(n);
    if (!i)
        return null;
    let s = i[0]
      , o = s.replace(/(.)\/+$/, "$1")
      , l = i.slice(1);
    return {
        params: r.reduce( (c, d, f) => {
            let {paramName: p, isOptional: h} = d;
            if (p === "*") {
                let _ = l[f] || "";
                o = s.slice(0, s.length - _.length).replace(/(.)\/+$/, "$1")
            }
            const m = l[f];
            return h && !m ? c[p] = void 0 : c[p] = (m || "").replace(/%2F/g, "/"),
            c
        }
        , {}),
        pathname: s,
        pathnameBase: o,
        pattern: e
    }
}
function FS(e, t, n) {
    t === void 0 && (t = !1),
    n === void 0 && (n = !0),
    ps(e === "*" || !e.endsWith("*") || e.endsWith("/*"), 'Route path "' + e + '" will be treated as if it were ' + ('"' + e.replace(/\*$/, "/*") + '" because the `*` character must ') + "always follow a `/` in the pattern. To get rid of this warning, " + ('please change the route path to "' + e.replace(/\*$/, "/*") + '".'));
    let r = []
      , i = "^" + e.replace(/\/*\*?$/, "").replace(/^\/*/, "/").replace(/[\\.*+^${}|()[\]]/g, "\\$&").replace(/\/:([\w-]+)(\?)?/g, (o, l, a) => (r.push({
        paramName: l,
        isOptional: a != null
    }),
    a ? "/?([^\\/]+)?" : "/([^\\/]+)"));
    return e.endsWith("*") ? (r.push({
        paramName: "*"
    }),
    i += e === "*" || e === "/*" ? "(.*)$" : "(?:\\/(.+)|\\/*)$") : n ? i += "\\/*$" : e !== "" && e !== "/" && (i += "(?:(?=\\/|$))"),
    [new RegExp(i,t ? void 0 : "i"), r]
}
function VS(e) {
    try {
        return e.split("/").map(t => decodeURIComponent(t).replace(/\//g, "%2F")).join("/")
    } catch (t) {
        return ps(!1, 'The URL path "' + e + '" could not be decoded because it is is a malformed URL segment. This is probably due to a bad percent ' + ("encoding (" + t + ").")),
        e
    }
}
function xs(e, t) {
    if (t === "/")
        return e;
    if (!e.toLowerCase().startsWith(t.toLowerCase()))
        return null;
    let n = t.endsWith("/") ? t.length - 1 : t.length
      , r = e.charAt(n);
    return r && r !== "/" ? null : e.slice(n) || "/"
}
function zS(e, t) {
    t === void 0 && (t = "/");
    let {pathname: n, search: r="", hash: i=""} = typeof e == "string" ? rr(e) : e;
    return {
        pathname: n ? n.startsWith("/") ? n : BS(n, t) : t,
        search: HS(r),
        hash: GS(i)
    }
}
function BS(e, t) {
    let n = t.replace(/\/+$/, "").split("/");
    return e.split("/").forEach(i => {
        i === ".." ? n.length > 1 && n.pop() : i !== "." && n.push(i)
    }
    ),
    n.length > 1 ? n.join("/") : "/"
}
function pu(e, t, n, r) {
    return "Cannot include a '" + e + "' character in a manually specified " + ("`to." + t + "` field [" + JSON.stringify(r) + "].  Please separate it out to the ") + ("`to." + n + "` field. Alternatively you may provide the full path as ") + 'a string in <Link to="..."> and the router will parse it for you.'
}
function ry(e) {
    return e.filter( (t, n) => n === 0 || t.route.path && t.route.path.length > 0)
}
function Sp(e, t) {
    let n = ry(e);
    return t ? n.map( (r, i) => i === e.length - 1 ? r.pathname : r.pathnameBase) : n.map(r => r.pathnameBase)
}
function Cp(e, t, n, r) {
    r === void 0 && (r = !1);
    let i;
    typeof e == "string" ? i = rr(e) : (i = De({}, e),
    ce(!i.pathname || !i.pathname.includes("?"), pu("?", "pathname", "search", i)),
    ce(!i.pathname || !i.pathname.includes("#"), pu("#", "pathname", "hash", i)),
    ce(!i.search || !i.search.includes("#"), pu("#", "search", "hash", i)));
    let s = e === "" || i.pathname === "", o = s ? "/" : i.pathname, l;
    if (o == null)
        l = n;
    else {
        let f = t.length - 1;
        if (!r && o.startsWith("..")) {
            let p = o.split("/");
            for (; p[0] === ".."; )
                p.shift(),
                f -= 1;
            i.pathname = p.join("/")
        }
        l = f >= 0 ? t[f] : "/"
    }
    let a = zS(i, l)
      , c = o && o !== "/" && o.endsWith("/")
      , d = (s || o === ".") && n.endsWith("/");
    return !a.pathname.endsWith("/") && (c || d) && (a.pathname += "/"),
    a
}
const Yn = e => e.join("/").replace(/\/\/+/g, "/")
  , US = e => e.replace(/\/+$/, "").replace(/^\/*/, "/")
  , HS = e => !e || e === "?" ? "" : e.startsWith("?") ? e : "?" + e
  , GS = e => !e || e === "#" ? "" : e.startsWith("#") ? e : "#" + e;
class kp {
    constructor(t, n, r, i) {
        i === void 0 && (i = !1),
        this.status = t,
        this.statusText = n || "",
        this.internal = i,
        r instanceof Error ? (this.data = r.toString(),
        this.error = r) : this.data = r
    }
}
function hc(e) {
    return e != null && typeof e.status == "number" && typeof e.statusText == "string" && typeof e.internal == "boolean" && "data"in e
}
const iy = ["post", "put", "patch", "delete"]
  , WS = new Set(iy)
  , qS = ["get", ...iy]
  , YS = new Set(qS)
  , KS = new Set([301, 302, 303, 307, 308])
  , XS = new Set([307, 308])
  , mu = {
    state: "idle",
    location: void 0,
    formMethod: void 0,
    formAction: void 0,
    formEncType: void 0,
    formData: void 0,
    json: void 0,
    text: void 0
}
  , QS = {
    state: "idle",
    data: void 0,
    formMethod: void 0,
    formAction: void 0,
    formEncType: void 0,
    formData: void 0,
    json: void 0,
    text: void 0
}
  , As = {
    state: "unblocked",
    proceed: void 0,
    reset: void 0,
    location: void 0
}
  , Ep = /^(?:[a-z][a-z0-9+.-]*:|\/\/)/i
  , JS = e => ({
    hasErrorBoundary: !!e.hasErrorBoundary
})
  , sy = "remix-router-transitions";
function ZS(e) {
    const t = e.window ? e.window : typeof window < "u" ? window : void 0
      , n = typeof t < "u" && typeof t.document < "u" && typeof t.document.createElement < "u"
      , r = !n;
    ce(e.routes.length > 0, "You must provide a non-empty routes array to createRouter");
    let i;
    if (e.mapRouteProperties)
        i = e.mapRouteProperties;
    else if (e.detectErrorBoundary) {
        let E = e.detectErrorBoundary;
        i = j => ({
            hasErrorBoundary: E(j)
        })
    } else
        i = JS;
    let s = {}, o = Oo(e.routes, i, void 0, s), l, a = e.basename || "/", c = e.unstable_dataStrategy || i2, d = e.unstable_patchRoutesOnMiss, f = De({
        v7_fetcherPersist: !1,
        v7_normalizeFormMethod: !1,
        v7_partialHydration: !1,
        v7_prependBasename: !1,
        v7_relativeSplatPath: !1,
        unstable_skipActionErrorRevalidation: !1
    }, e.future), p = null, h = new Set, m = null, _ = null, x = null, v = e.hydrationData != null, w = ei(o, e.history.location, a), y = null;
    if (w == null && !d) {
        let E = xt(404, {
            pathname: e.history.location.pathname
        })
          , {matches: j, route: I} = Uh(o);
        w = j,
        y = {
            [I.id]: E
        }
    }
    let b;
    if (!w)
        b = !1,
        w = [];
    else if (w.some(E => E.route.lazy))
        b = !1;
    else if (!w.some(E => E.route.loader))
        b = !0;
    else if (f.v7_partialHydration) {
        let E = e.hydrationData ? e.hydrationData.loaderData : null
          , j = e.hydrationData ? e.hydrationData.errors : null
          , I = D => D.route.loader ? typeof D.route.loader == "function" && D.route.loader.hydrate === !0 ? !1 : E && E[D.route.id] !== void 0 || j && j[D.route.id] !== void 0 : !0;
        if (j) {
            let D = w.findIndex(W => j[W.route.id] !== void 0);
            b = w.slice(0, D + 1).every(I)
        } else
            b = w.every(I)
    } else
        b = e.hydrationData != null;
    let C, S = {
        historyAction: e.history.action,
        location: e.history.location,
        matches: w,
        initialized: b,
        navigation: mu,
        restoreScrollPosition: e.hydrationData != null ? !1 : null,
        preventScrollReset: !1,
        revalidation: "idle",
        loaderData: e.hydrationData && e.hydrationData.loaderData || {},
        actionData: e.hydrationData && e.hydrationData.actionData || null,
        errors: e.hydrationData && e.hydrationData.errors || y,
        fetchers: new Map,
        blockers: new Map
    }, T = qe.Pop, k = !1, P, R = !1, A = new Map, V = null, F = !1, U = !1, Q = [], Z = [], L = new Map, N = 0, $ = -1, O = new Map, B = new Set, H = new Map, ue = new Map, de = new Set, xe = new Map, ge = new Map, wt = new Map, un = !1;
    function Mt() {
        if (p = e.history.listen(E => {
            let {action: j, location: I, delta: D} = E;
            if (un) {
                un = !1;
                return
            }
            ps(ge.size === 0 || D != null, "You are trying to use a blocker on a POP navigation to a location that was not created by @remix-run/router. This will fail silently in production. This can happen if you are navigating outside the router via `window.history.pushState`/`window.location.hash` instead of using router navigation APIs.  This can also happen if you are using createHashRouter and the user manually changes the URL.");
            let W = gm({
                currentLocation: S.location,
                nextLocation: I,
                historyAction: j
            });
            if (W && D != null) {
                un = !0,
                e.history.go(D * -1),
                ua(W, {
                    state: "blocked",
                    location: I,
                    proceed() {
                        ua(W, {
                            state: "proceeding",
                            proceed: void 0,
                            reset: void 0,
                            location: I
                        }),
                        e.history.go(D)
                    },
                    reset() {
                        let J = new Map(S.blockers);
                        J.set(W, As),
                        Be({
                            blockers: J
                        })
                    }
                });
                return
            }
            return dn(j, I)
        }
        ),
        n) {
            _2(t, A);
            let E = () => v2(t, A);
            t.addEventListener("pagehide", E),
            V = () => t.removeEventListener("pagehide", E)
        }
        return S.initialized || dn(qe.Pop, S.location, {
            initialHydration: !0
        }),
        C
    }
    function ot() {
        p && p(),
        V && V(),
        h.clear(),
        P && P.abort(),
        S.fetchers.forEach( (E, j) => ji(j)),
        S.blockers.forEach( (E, j) => hm(j))
    }
    function ft(E) {
        return h.add(E),
        () => h.delete(E)
    }
    function Be(E, j) {
        j === void 0 && (j = {}),
        S = De({}, S, E);
        let I = []
          , D = [];
        f.v7_fetcherPersist && S.fetchers.forEach( (W, J) => {
            W.state === "idle" && (de.has(J) ? D.push(J) : I.push(J))
        }
        ),
        [...h].forEach(W => W(S, {
            deletedFetchers: D,
            unstable_viewTransitionOpts: j.viewTransitionOpts,
            unstable_flushSync: j.flushSync === !0
        })),
        f.v7_fetcherPersist && (I.forEach(W => S.fetchers.delete(W)),
        D.forEach(W => ji(W)))
    }
    function Ot(E, j, I) {
        var D, W;
        let {flushSync: J} = I === void 0 ? {} : I, ee = S.actionData != null && S.navigation.formMethod != null && gn(S.navigation.formMethod) && S.navigation.state === "loading" && ((D = E.state) == null ? void 0 : D._isRedirect) !== !0, G;
        j.actionData ? Object.keys(j.actionData).length > 0 ? G = j.actionData : G = null : ee ? G = S.actionData : G = null;
        let ie = j.loaderData ? zh(S.loaderData, j.loaderData, j.matches || [], j.errors) : S.loaderData
          , K = S.blockers;
        K.size > 0 && (K = new Map(K),
        K.forEach( (ye, ke) => K.set(ke, As)));
        let oe = k === !0 || S.navigation.formMethod != null && gn(S.navigation.formMethod) && ((W = E.state) == null ? void 0 : W._isRedirect) !== !0;
        l && (o = l,
        l = void 0),
        F || T === qe.Pop || (T === qe.Push ? e.history.push(E, E.state) : T === qe.Replace && e.history.replace(E, E.state));
        let Se;
        if (T === qe.Pop) {
            let ye = A.get(S.location.pathname);
            ye && ye.has(E.pathname) ? Se = {
                currentLocation: S.location,
                nextLocation: E
            } : A.has(E.pathname) && (Se = {
                currentLocation: E,
                nextLocation: S.location
            })
        } else if (R) {
            let ye = A.get(S.location.pathname);
            ye ? ye.add(E.pathname) : (ye = new Set([E.pathname]),
            A.set(S.location.pathname, ye)),
            Se = {
                currentLocation: S.location,
                nextLocation: E
            }
        }
        Be(De({}, j, {
            actionData: G,
            loaderData: ie,
            historyAction: T,
            location: E,
            initialized: !0,
            navigation: mu,
            revalidation: "idle",
            restoreScrollPosition: vm(E, j.matches || S.matches),
            preventScrollReset: oe,
            blockers: K
        }), {
            viewTransitionOpts: Se,
            flushSync: J === !0
        }),
        T = qe.Pop,
        k = !1,
        R = !1,
        F = !1,
        U = !1,
        Q = [],
        Z = []
    }
    async function Gr(E, j) {
        if (typeof E == "number") {
            e.history.go(E);
            return
        }
        let I = Ud(S.location, S.matches, a, f.v7_prependBasename, E, f.v7_relativeSplatPath, j == null ? void 0 : j.fromRouteId, j == null ? void 0 : j.relative)
          , {path: D, submission: W, error: J} = Mh(f.v7_normalizeFormMethod, !1, I, j)
          , ee = S.location
          , G = Mo(S.location, D, j && j.state);
        G = De({}, G, e.history.encodeLocation(G));
        let ie = j && j.replace != null ? j.replace : void 0
          , K = qe.Push;
        ie === !0 ? K = qe.Replace : ie === !1 || W != null && gn(W.formMethod) && W.formAction === S.location.pathname + S.location.search && (K = qe.Replace);
        let oe = j && "preventScrollReset"in j ? j.preventScrollReset === !0 : void 0
          , Se = (j && j.unstable_flushSync) === !0
          , ye = gm({
            currentLocation: ee,
            nextLocation: G,
            historyAction: K
        });
        if (ye) {
            ua(ye, {
                state: "blocked",
                location: G,
                proceed() {
                    ua(ye, {
                        state: "proceeding",
                        proceed: void 0,
                        reset: void 0,
                        location: G
                    }),
                    Gr(E, j)
                },
                reset() {
                    let ke = new Map(S.blockers);
                    ke.set(ye, As),
                    Be({
                        blockers: ke
                    })
                }
            });
            return
        }
        return await dn(K, G, {
            submission: W,
            pendingError: J,
            preventScrollReset: oe,
            replace: j && j.replace,
            enableViewTransition: j && j.unstable_viewTransition,
            flushSync: Se
        })
    }
    function Pi() {
        if (Dn(),
        Be({
            revalidation: "loading"
        }),
        S.navigation.state !== "submitting") {
            if (S.navigation.state === "idle") {
                dn(S.historyAction, S.location, {
                    startUninterruptedRevalidation: !0
                });
                return
            }
            dn(T || S.historyAction, S.navigation.location, {
                overrideNavigation: S.navigation
            })
        }
    }
    async function dn(E, j, I) {
        P && P.abort(),
        P = null,
        T = E,
        F = (I && I.startUninterruptedRevalidation) === !0,
        Wx(S.location, S.matches),
        k = (I && I.preventScrollReset) === !0,
        R = (I && I.enableViewTransition) === !0;
        let D = l || o
          , W = I && I.overrideNavigation
          , J = ei(D, j, a)
          , ee = (I && I.flushSync) === !0
          , G = ym(J, D, j.pathname);
        if (G.active && G.matches && (J = G.matches),
        !J) {
            let {error: we, notFoundMatches: at, route: Xe} = Fc(j.pathname);
            Ot(j, {
                matches: at,
                loaderData: {},
                errors: {
                    [Xe.id]: we
                }
            }, {
                flushSync: ee
            });
            return
        }
        if (S.initialized && !U && u2(S.location, j) && !(I && I.submission && gn(I.submission.formMethod))) {
            Ot(j, {
                matches: J
            }, {
                flushSync: ee
            });
            return
        }
        P = new AbortController;
        let ie = Mi(e.history, j, P.signal, I && I.submission), K;
        if (I && I.pendingError)
            K = [lo(J).route.id, {
                type: Pe.error,
                error: I.pendingError
            }];
        else if (I && I.submission && gn(I.submission.formMethod)) {
            let we = await ks(ie, j, I.submission, J, G.active, {
                replace: I.replace,
                flushSync: ee
            });
            if (we.shortCircuited)
                return;
            if (we.pendingActionResult) {
                let[at,Xe] = we.pendingActionResult;
                if (zt(Xe) && hc(Xe.error) && Xe.error.status === 404) {
                    P = null,
                    Ot(j, {
                        matches: we.matches,
                        loaderData: {},
                        errors: {
                            [at]: Xe.error
                        }
                    });
                    return
                }
            }
            J = we.matches || J,
            K = we.pendingActionResult,
            W = hu(j, I.submission),
            ee = !1,
            G.active = !1,
            ie = Mi(e.history, ie.url, ie.signal)
        }
        let {shortCircuited: oe, matches: Se, loaderData: ye, errors: ke} = await Es(ie, j, J, G.active, W, I && I.submission, I && I.fetcherSubmission, I && I.replace, I && I.initialHydration === !0, ee, K);
        oe || (P = null,
        Ot(j, De({
            matches: Se || J
        }, Bh(K), {
            loaderData: ye,
            errors: ke
        })))
    }
    async function ks(E, j, I, D, W, J) {
        J === void 0 && (J = {}),
        Dn();
        let ee = h2(j, I);
        if (Be({
            navigation: ee
        }, {
            flushSync: J.flushSync === !0
        }),
        W) {
            let K = await fa(D, j.pathname, E.signal);
            if (K.type === "aborted")
                return {
                    shortCircuited: !0
                };
            if (K.type === "error") {
                let {error: oe, notFoundMatches: Se, route: ye} = da(j.pathname, K);
                return {
                    matches: Se,
                    pendingActionResult: [ye.id, {
                        type: Pe.error,
                        error: oe
                    }]
                }
            } else if (K.matches)
                D = K.matches;
            else {
                let {notFoundMatches: oe, error: Se, route: ye} = Fc(j.pathname);
                return {
                    matches: oe,
                    pendingActionResult: [ye.id, {
                        type: Pe.error,
                        error: Se
                    }]
                }
            }
        }
        let G, ie = Qs(D, j);
        if (!ie.route.action && !ie.route.lazy)
            G = {
                type: Pe.error,
                error: xt(405, {
                    method: E.method,
                    pathname: j.pathname,
                    routeId: ie.route.id
                })
            };
        else if (G = (await pt("action", E, [ie], D))[0],
        E.signal.aborted)
            return {
                shortCircuited: !0
            };
        if (si(G)) {
            let K;
            return J && J.replace != null ? K = J.replace : K = $h(G.response.headers.get("Location"), new URL(E.url), a) === S.location.pathname + S.location.search,
            await Ue(E, G, {
                submission: I,
                replace: K
            }),
            {
                shortCircuited: !0
            }
        }
        if (ii(G))
            throw xt(400, {
                type: "defer-action"
            });
        if (zt(G)) {
            let K = lo(D, ie.route.id);
            return (J && J.replace) !== !0 && (T = qe.Push),
            {
                matches: D,
                pendingActionResult: [K.route.id, G]
            }
        }
        return {
            matches: D,
            pendingActionResult: [ie.route.id, G]
        }
    }
    async function Es(E, j, I, D, W, J, ee, G, ie, K, oe) {
        let Se = W || hu(j, J)
          , ye = J || ee || Wh(Se)
          , ke = !F && (!f.v7_partialHydration || !ie);
        if (D) {
            if (ke) {
                let He = ae(oe);
                Be(De({
                    navigation: Se
                }, He !== void 0 ? {
                    actionData: He
                } : {}), {
                    flushSync: K
                })
            }
            let le = await fa(I, j.pathname, E.signal);
            if (le.type === "aborted")
                return {
                    shortCircuited: !0
                };
            if (le.type === "error") {
                let {error: He, notFoundMatches: Dt, route: Te} = da(j.pathname, le);
                return {
                    matches: Dt,
                    loaderData: {},
                    errors: {
                        [Te.id]: He
                    }
                }
            } else if (le.matches)
                I = le.matches;
            else {
                let {error: He, notFoundMatches: Dt, route: Te} = Fc(j.pathname);
                return {
                    matches: Dt,
                    loaderData: {},
                    errors: {
                        [Te.id]: He
                    }
                }
            }
        }
        let we = l || o
          , [at,Xe] = Oh(e.history, S, I, ye, j, f.v7_partialHydration && ie === !0, f.unstable_skipActionErrorRevalidation, U, Q, Z, de, H, B, we, a, oe);
        if (Vc(le => !(I && I.some(He => He.route.id === le)) || at && at.some(He => He.route.id === le)),
        $ = ++N,
        at.length === 0 && Xe.length === 0) {
            let le = pm();
            return Ot(j, De({
                matches: I,
                loaderData: {},
                errors: oe && zt(oe[1]) ? {
                    [oe[0]]: oe[1].error
                } : null
            }, Bh(oe), le ? {
                fetchers: new Map(S.fetchers)
            } : {}), {
                flushSync: K
            }),
            {
                shortCircuited: !0
            }
        }
        if (ke) {
            let le = {};
            if (!D) {
                le.navigation = Se;
                let He = ae(oe);
                He !== void 0 && (le.actionData = He)
            }
            Xe.length > 0 && (le.fetchers = Me(Xe)),
            Be(le, {
                flushSync: K
            })
        }
        Xe.forEach(le => {
            L.has(le.key) && En(le.key),
            le.controller && L.set(le.key, le.controller)
        }
        );
        let Ts = () => Xe.forEach(le => En(le.key));
        P && P.signal.addEventListener("abort", Ts);
        let {loaderResults: ir, fetcherResults: Ni} = await kn(S.matches, I, at, Xe, E);
        if (E.signal.aborted)
            return {
                shortCircuited: !0
            };
        P && P.signal.removeEventListener("abort", Ts),
        Xe.forEach(le => L.delete(le.key));
        let Ri = Hh([...ir, ...Ni]);
        if (Ri) {
            if (Ri.idx >= at.length) {
                let le = Xe[Ri.idx - at.length].key;
                B.add(le)
            }
            return await Ue(E, Ri.result, {
                replace: G
            }),
            {
                shortCircuited: !0
            }
        }
        let {loaderData: Ii, errors: Tn} = Vh(S, I, at, ir, oe, Xe, Ni, xe);
        xe.forEach( (le, He) => {
            le.subscribe(Dt => {
                (Dt || le.done) && xe.delete(He)
            }
            )
        }
        ),
        f.v7_partialHydration && ie && S.errors && Object.entries(S.errors).filter(le => {
            let[He] = le;
            return !at.some(Dt => Dt.route.id === He)
        }
        ).forEach(le => {
            let[He,Dt] = le;
            Tn = Object.assign(Tn || {}, {
                [He]: Dt
            })
        }
        );
        let pa = pm()
          , ma = mm($)
          , ha = pa || ma || Xe.length > 0;
        return De({
            matches: I,
            loaderData: Ii,
            errors: Tn
        }, ha ? {
            fetchers: new Map(S.fetchers)
        } : {})
    }
    function ae(E) {
        if (E && !zt(E[1]))
            return {
                [E[0]]: E[1].data
            };
        if (S.actionData)
            return Object.keys(S.actionData).length === 0 ? null : S.actionData
    }
    function Me(E) {
        return E.forEach(j => {
            let I = S.fetchers.get(j.key)
              , D = Ds(void 0, I ? I.data : void 0);
            S.fetchers.set(j.key, D)
        }
        ),
        new Map(S.fetchers)
    }
    function q(E, j, I, D) {
        if (r)
            throw new Error("router.fetch() was called during the server render, but it shouldn't be. You are likely calling a useFetcher() method in the body of your component. Try moving it to a useEffect or a callback.");
        L.has(E) && En(E);
        let W = (D && D.unstable_flushSync) === !0
          , J = l || o
          , ee = Ud(S.location, S.matches, a, f.v7_prependBasename, I, f.v7_relativeSplatPath, j, D == null ? void 0 : D.relative)
          , G = ei(J, ee, a)
          , ie = ym(G, J, ee);
        if (ie.active && ie.matches && (G = ie.matches),
        !G) {
            At(E, j, xt(404, {
                pathname: ee
            }), {
                flushSync: W
            });
            return
        }
        let {path: K, submission: oe, error: Se} = Mh(f.v7_normalizeFormMethod, !0, ee, D);
        if (Se) {
            At(E, j, Se, {
                flushSync: W
            });
            return
        }
        let ye = Qs(G, K);
        if (k = (D && D.preventScrollReset) === !0,
        oe && gn(oe.formMethod)) {
            re(E, j, K, ye, G, ie.active, W, oe);
            return
        }
        H.set(E, {
            routeId: j,
            path: K
        }),
        Oe(E, j, K, ye, G, ie.active, W, oe)
    }
    async function re(E, j, I, D, W, J, ee, G) {
        Dn(),
        H.delete(E);
        function ie(Te) {
            if (!Te.route.action && !Te.route.lazy) {
                let $n = xt(405, {
                    method: G.formMethod,
                    pathname: I,
                    routeId: j
                });
                return At(E, j, $n, {
                    flushSync: ee
                }),
                !0
            }
            return !1
        }
        if (!J && ie(D))
            return;
        let K = S.fetchers.get(E);
        Qt(E, g2(G, K), {
            flushSync: ee
        });
        let oe = new AbortController
          , Se = Mi(e.history, I, oe.signal, G);
        if (J) {
            let Te = await fa(W, I, Se.signal);
            if (Te.type === "aborted")
                return;
            if (Te.type === "error") {
                let {error: $n} = da(I, Te);
                At(E, j, $n, {
                    flushSync: ee
                });
                return
            } else if (Te.matches) {
                if (W = Te.matches,
                D = Qs(W, I),
                ie(D))
                    return
            } else {
                At(E, j, xt(404, {
                    pathname: I
                }), {
                    flushSync: ee
                });
                return
            }
        }
        L.set(E, oe);
        let ye = N
          , we = (await pt("action", Se, [D], W))[0];
        if (Se.signal.aborted) {
            L.get(E) === oe && L.delete(E);
            return
        }
        if (f.v7_fetcherPersist && de.has(E)) {
            if (si(we) || zt(we)) {
                Qt(E, pr(void 0));
                return
            }
        } else {
            if (si(we))
                if (L.delete(E),
                $ > ye) {
                    Qt(E, pr(void 0));
                    return
                } else
                    return B.add(E),
                    Qt(E, Ds(G)),
                    Ue(Se, we, {
                        fetcherSubmission: G
                    });
            if (zt(we)) {
                At(E, j, we.error);
                return
            }
        }
        if (ii(we))
            throw xt(400, {
                type: "defer-action"
            });
        let at = S.navigation.location || S.location
          , Xe = Mi(e.history, at, oe.signal)
          , Ts = l || o
          , ir = S.navigation.state !== "idle" ? ei(Ts, S.navigation.location, a) : S.matches;
        ce(ir, "Didn't find any matches after fetcher action");
        let Ni = ++N;
        O.set(E, Ni);
        let Ri = Ds(G, we.data);
        S.fetchers.set(E, Ri);
        let[Ii,Tn] = Oh(e.history, S, ir, G, at, !1, f.unstable_skipActionErrorRevalidation, U, Q, Z, de, H, B, Ts, a, [D.route.id, we]);
        Tn.filter(Te => Te.key !== E).forEach(Te => {
            let $n = Te.key
              , wm = S.fetchers.get($n)
              , Yx = Ds(void 0, wm ? wm.data : void 0);
            S.fetchers.set($n, Yx),
            L.has($n) && En($n),
            Te.controller && L.set($n, Te.controller)
        }
        ),
        Be({
            fetchers: new Map(S.fetchers)
        });
        let pa = () => Tn.forEach(Te => En(Te.key));
        oe.signal.addEventListener("abort", pa);
        let {loaderResults: ma, fetcherResults: ha} = await kn(S.matches, ir, Ii, Tn, Xe);
        if (oe.signal.aborted)
            return;
        oe.signal.removeEventListener("abort", pa),
        O.delete(E),
        L.delete(E),
        Tn.forEach(Te => L.delete(Te.key));
        let le = Hh([...ma, ...ha]);
        if (le) {
            if (le.idx >= Ii.length) {
                let Te = Tn[le.idx - Ii.length].key;
                B.add(Te)
            }
            return Ue(Xe, le.result)
        }
        let {loaderData: He, errors: Dt} = Vh(S, S.matches, Ii, ma, void 0, Tn, ha, xe);
        if (S.fetchers.has(E)) {
            let Te = pr(we.data);
            S.fetchers.set(E, Te)
        }
        mm(Ni),
        S.navigation.state === "loading" && Ni > $ ? (ce(T, "Expected pending action"),
        P && P.abort(),
        Ot(S.navigation.location, {
            matches: ir,
            loaderData: He,
            errors: Dt,
            fetchers: new Map(S.fetchers)
        })) : (Be({
            errors: Dt,
            loaderData: zh(S.loaderData, He, ir, Dt),
            fetchers: new Map(S.fetchers)
        }),
        U = !1)
    }
    async function Oe(E, j, I, D, W, J, ee, G) {
        let ie = S.fetchers.get(E);
        Qt(E, Ds(G, ie ? ie.data : void 0), {
            flushSync: ee
        });
        let K = new AbortController
          , oe = Mi(e.history, I, K.signal);
        if (J) {
            let we = await fa(W, I, oe.signal);
            if (we.type === "aborted")
                return;
            if (we.type === "error") {
                let {error: at} = da(I, we);
                At(E, j, at, {
                    flushSync: ee
                });
                return
            } else if (we.matches)
                W = we.matches,
                D = Qs(W, I);
            else {
                At(E, j, xt(404, {
                    pathname: I
                }), {
                    flushSync: ee
                });
                return
            }
        }
        L.set(E, K);
        let Se = N
          , ke = (await pt("loader", oe, [D], W))[0];
        if (ii(ke) && (ke = await uy(ke, oe.signal, !0) || ke),
        L.get(E) === K && L.delete(E),
        !oe.signal.aborted) {
            if (de.has(E)) {
                Qt(E, pr(void 0));
                return
            }
            if (si(ke))
                if ($ > Se) {
                    Qt(E, pr(void 0));
                    return
                } else {
                    B.add(E),
                    await Ue(oe, ke);
                    return
                }
            if (zt(ke)) {
                At(E, j, ke.error);
                return
            }
            ce(!ii(ke), "Unhandled fetcher deferred data"),
            Qt(E, pr(ke.data))
        }
    }
    async function Ue(E, j, I) {
        let {submission: D, fetcherSubmission: W, replace: J} = I === void 0 ? {} : I;
        j.response.headers.has("X-Remix-Revalidate") && (U = !0);
        let ee = j.response.headers.get("Location");
        ce(ee, "Expected a Location header on the redirect Response"),
        ee = $h(ee, new URL(E.url), a);
        let G = Mo(S.location, ee, {
            _isRedirect: !0
        });
        if (n) {
            let ke = !1;
            if (j.response.headers.has("X-Remix-Reload-Document"))
                ke = !0;
            else if (Ep.test(ee)) {
                const we = e.history.createURL(ee);
                ke = we.origin !== t.location.origin || xs(we.pathname, a) == null
            }
            if (ke) {
                J ? t.location.replace(ee) : t.location.assign(ee);
                return
            }
        }
        P = null;
        let ie = J === !0 ? qe.Replace : qe.Push
          , {formMethod: K, formAction: oe, formEncType: Se} = S.navigation;
        !D && !W && K && oe && Se && (D = Wh(S.navigation));
        let ye = D || W;
        if (XS.has(j.response.status) && ye && gn(ye.formMethod))
            await dn(ie, G, {
                submission: De({}, ye, {
                    formAction: ee
                }),
                preventScrollReset: k
            });
        else {
            let ke = hu(G, D);
            await dn(ie, G, {
                overrideNavigation: ke,
                fetcherSubmission: W,
                preventScrollReset: k
            })
        }
    }
    async function pt(E, j, I, D) {
        try {
            let W = await s2(c, E, j, I, D, s, i);
            return await Promise.all(W.map( (J, ee) => {
                if (f2(J)) {
                    let G = J.result;
                    return {
                        type: Pe.redirect,
                        response: l2(G, j, I[ee].route.id, D, a, f.v7_relativeSplatPath)
                    }
                }
                return a2(J)
            }
            ))
        } catch (W) {
            return I.map( () => ({
                type: Pe.error,
                error: W
            }))
        }
    }
    async function kn(E, j, I, D, W) {
        let[J,...ee] = await Promise.all([I.length ? pt("loader", W, I, j) : [], ...D.map(G => {
            if (G.matches && G.match && G.controller) {
                let ie = Mi(e.history, G.path, G.controller.signal);
                return pt("loader", ie, [G.match], G.matches).then(K => K[0])
            } else
                return Promise.resolve({
                    type: Pe.error,
                    error: xt(404, {
                        pathname: G.path
                    })
                })
        }
        )]);
        return await Promise.all([Gh(E, I, J, J.map( () => W.signal), !1, S.loaderData), Gh(E, D.map(G => G.match), ee, D.map(G => G.controller ? G.controller.signal : null), !0)]),
        {
            loaderResults: J,
            fetcherResults: ee
        }
    }
    function Dn() {
        U = !0,
        Q.push(...Vc()),
        H.forEach( (E, j) => {
            L.has(j) && (Z.push(j),
            En(j))
        }
        )
    }
    function Qt(E, j, I) {
        I === void 0 && (I = {}),
        S.fetchers.set(E, j),
        Be({
            fetchers: new Map(S.fetchers)
        }, {
            flushSync: (I && I.flushSync) === !0
        })
    }
    function At(E, j, I, D) {
        D === void 0 && (D = {});
        let W = lo(S.matches, j);
        ji(E),
        Be({
            errors: {
                [W.route.id]: I
            },
            fetchers: new Map(S.fetchers)
        }, {
            flushSync: (D && D.flushSync) === !0
        })
    }
    function la(E) {
        return f.v7_fetcherPersist && (ue.set(E, (ue.get(E) || 0) + 1),
        de.has(E) && de.delete(E)),
        S.fetchers.get(E) || QS
    }
    function ji(E) {
        let j = S.fetchers.get(E);
        L.has(E) && !(j && j.state === "loading" && O.has(E)) && En(E),
        H.delete(E),
        O.delete(E),
        B.delete(E),
        de.delete(E),
        S.fetchers.delete(E)
    }
    function $c(E) {
        if (f.v7_fetcherPersist) {
            let j = (ue.get(E) || 0) - 1;
            j <= 0 ? (ue.delete(E),
            de.add(E)) : ue.set(E, j)
        } else
            ji(E);
        Be({
            fetchers: new Map(S.fetchers)
        })
    }
    function En(E) {
        let j = L.get(E);
        ce(j, "Expected fetch controller: " + E),
        j.abort(),
        L.delete(E)
    }
    function ca(E) {
        for (let j of E) {
            let I = la(j)
              , D = pr(I.data);
            S.fetchers.set(j, D)
        }
    }
    function pm() {
        let E = []
          , j = !1;
        for (let I of B) {
            let D = S.fetchers.get(I);
            ce(D, "Expected fetcher: " + I),
            D.state === "loading" && (B.delete(I),
            E.push(I),
            j = !0)
        }
        return ca(E),
        j
    }
    function mm(E) {
        let j = [];
        for (let[I,D] of O)
            if (D < E) {
                let W = S.fetchers.get(I);
                ce(W, "Expected fetcher: " + I),
                W.state === "loading" && (En(I),
                O.delete(I),
                j.push(I))
            }
        return ca(j),
        j.length > 0
    }
    function Hx(E, j) {
        let I = S.blockers.get(E) || As;
        return ge.get(E) !== j && ge.set(E, j),
        I
    }
    function hm(E) {
        S.blockers.delete(E),
        ge.delete(E)
    }
    function ua(E, j) {
        let I = S.blockers.get(E) || As;
        ce(I.state === "unblocked" && j.state === "blocked" || I.state === "blocked" && j.state === "blocked" || I.state === "blocked" && j.state === "proceeding" || I.state === "blocked" && j.state === "unblocked" || I.state === "proceeding" && j.state === "unblocked", "Invalid blocker state transition: " + I.state + " -> " + j.state);
        let D = new Map(S.blockers);
        D.set(E, j),
        Be({
            blockers: D
        })
    }
    function gm(E) {
        let {currentLocation: j, nextLocation: I, historyAction: D} = E;
        if (ge.size === 0)
            return;
        ge.size > 1 && ps(!1, "A router only supports one blocker at a time");
        let W = Array.from(ge.entries())
          , [J,ee] = W[W.length - 1]
          , G = S.blockers.get(J);
        if (!(G && G.state === "proceeding") && ee({
            currentLocation: j,
            nextLocation: I,
            historyAction: D
        }))
            return J
    }
    function Fc(E) {
        let j = xt(404, {
            pathname: E
        })
          , I = l || o
          , {matches: D, route: W} = Uh(I);
        return Vc(),
        {
            notFoundMatches: D,
            route: W,
            error: j
        }
    }
    function da(E, j) {
        let I = j.partialMatches
          , D = I[I.length - 1].route
          , W = xt(400, {
            type: "route-discovery",
            routeId: D.id,
            pathname: E,
            message: j.error != null && "message"in j.error ? j.error : String(j.error)
        });
        return {
            notFoundMatches: I,
            route: D,
            error: W
        }
    }
    function Vc(E) {
        let j = [];
        return xe.forEach( (I, D) => {
            (!E || E(D)) && (I.cancel(),
            j.push(D),
            xe.delete(D))
        }
        ),
        j
    }
    function Gx(E, j, I) {
        if (m = E,
        x = j,
        _ = I || null,
        !v && S.navigation === mu) {
            v = !0;
            let D = vm(S.location, S.matches);
            D != null && Be({
                restoreScrollPosition: D
            })
        }
        return () => {
            m = null,
            x = null,
            _ = null
        }
    }
    function _m(E, j) {
        return _ && _(E, j.map(D => PS(D, S.loaderData))) || E.key
    }
    function Wx(E, j) {
        if (m && x) {
            let I = _m(E, j);
            m[I] = x()
        }
    }
    function vm(E, j) {
        if (m) {
            let I = _m(E, j)
              , D = m[I];
            if (typeof D == "number")
                return D
        }
        return null
    }
    function ym(E, j, I) {
        if (d)
            if (E) {
                if (E[E.length - 1].route.path === "*")
                    return {
                        active: !0,
                        matches: sl(j, I, a, !0)
                    }
            } else
                return {
                    active: !0,
                    matches: sl(j, I, a, !0) || []
                };
        return {
            active: !1,
            matches: null
        }
    }
    async function fa(E, j, I) {
        let D = E
          , W = D.length > 0 ? D[D.length - 1].route : null;
        for (; ; ) {
            try {
                await r2(d, j, D, o || l, s, i, wt, I)
            } catch (K) {
                return {
                    type: "error",
                    error: K,
                    partialMatches: D
                }
            }
            if (I.aborted)
                return {
                    type: "aborted"
                };
            let J = l || o
              , ee = ei(J, j, a)
              , G = !1;
            if (ee) {
                let K = ee[ee.length - 1].route;
                if (K.index)
                    return {
                        type: "success",
                        matches: ee
                    };
                if (K.path && K.path.length > 0)
                    if (K.path === "*")
                        G = !0;
                    else
                        return {
                            type: "success",
                            matches: ee
                        }
            }
            let ie = sl(J, j, a, !0);
            if (!ie || D.map(K => K.route.id).join("-") === ie.map(K => K.route.id).join("-"))
                return {
                    type: "success",
                    matches: G ? ee : null
                };
            if (D = ie,
            W = D[D.length - 1].route,
            W.path === "*")
                return {
                    type: "success",
                    matches: D
                }
        }
    }
    function qx(E) {
        s = {},
        l = Oo(E, i, void 0, s)
    }
    return C = {
        get basename() {
            return a
        },
        get future() {
            return f
        },
        get state() {
            return S
        },
        get routes() {
            return o
        },
        get window() {
            return t
        },
        initialize: Mt,
        subscribe: ft,
        enableScrollRestoration: Gx,
        navigate: Gr,
        fetch: q,
        revalidate: Pi,
        createHref: E => e.history.createHref(E),
        encodeLocation: E => e.history.encodeLocation(E),
        getFetcher: la,
        deleteFetcher: $c,
        dispose: ot,
        getBlocker: Hx,
        deleteBlocker: hm,
        patchRoutes(E, j) {
            return ay(E, j, o || l, s, i)
        },
        _internalFetchControllers: L,
        _internalActiveDeferreds: xe,
        _internalSetRoutes: qx
    },
    C
}
function e2(e) {
    return e != null && ("formData"in e && e.formData != null || "body"in e && e.body !== void 0)
}
function Ud(e, t, n, r, i, s, o, l) {
    let a, c;
    if (o) {
        a = [];
        for (let f of t)
            if (a.push(f),
            f.route.id === o) {
                c = f;
                break
            }
    } else
        a = t,
        c = t[t.length - 1];
    let d = Cp(i || ".", Sp(a, s), xs(e.pathname, n) || e.pathname, l === "path");
    return i == null && (d.search = e.search,
    d.hash = e.hash),
    (i == null || i === "" || i === ".") && c && c.route.index && !Tp(d.search) && (d.search = d.search ? d.search.replace(/^\?/, "?index&") : "?index"),
    r && n !== "/" && (d.pathname = d.pathname === "/" ? n : Yn([n, d.pathname])),
    hi(d)
}
function Mh(e, t, n, r) {
    if (!r || !e2(r))
        return {
            path: n
        };
    if (r.formMethod && !m2(r.formMethod))
        return {
            path: n,
            error: xt(405, {
                method: r.formMethod
            })
        };
    let i = () => ({
        path: n,
        error: xt(400, {
            type: "invalid-body"
        })
    })
      , s = r.formMethod || "get"
      , o = e ? s.toUpperCase() : s.toLowerCase()
      , l = ly(n);
    if (r.body !== void 0) {
        if (r.formEncType === "text/plain") {
            if (!gn(o))
                return i();
            let p = typeof r.body == "string" ? r.body : r.body instanceof FormData || r.body instanceof URLSearchParams ? Array.from(r.body.entries()).reduce( (h, m) => {
                let[_,x] = m;
                return "" + h + _ + "=" + x + `
`
            }
            , "") : String(r.body);
            return {
                path: n,
                submission: {
                    formMethod: o,
                    formAction: l,
                    formEncType: r.formEncType,
                    formData: void 0,
                    json: void 0,
                    text: p
                }
            }
        } else if (r.formEncType === "application/json") {
            if (!gn(o))
                return i();
            try {
                let p = typeof r.body == "string" ? JSON.parse(r.body) : r.body;
                return {
                    path: n,
                    submission: {
                        formMethod: o,
                        formAction: l,
                        formEncType: r.formEncType,
                        formData: void 0,
                        json: p,
                        text: void 0
                    }
                }
            } catch {
                return i()
            }
        }
    }
    ce(typeof FormData == "function", "FormData is not available in this environment");
    let a, c;
    if (r.formData)
        a = Hd(r.formData),
        c = r.formData;
    else if (r.body instanceof FormData)
        a = Hd(r.body),
        c = r.body;
    else if (r.body instanceof URLSearchParams)
        a = r.body,
        c = Fh(a);
    else if (r.body == null)
        a = new URLSearchParams,
        c = new FormData;
    else
        try {
            a = new URLSearchParams(r.body),
            c = Fh(a)
        } catch {
            return i()
        }
    let d = {
        formMethod: o,
        formAction: l,
        formEncType: r && r.formEncType || "application/x-www-form-urlencoded",
        formData: c,
        json: void 0,
        text: void 0
    };
    if (gn(d.formMethod))
        return {
            path: n,
            submission: d
        };
    let f = rr(n);
    return t && f.search && Tp(f.search) && a.append("index", ""),
    f.search = "?" + a,
    {
        path: hi(f),
        submission: d
    }
}
function t2(e, t) {
    let n = e;
    if (t) {
        let r = e.findIndex(i => i.route.id === t);
        r >= 0 && (n = e.slice(0, r))
    }
    return n
}
function Oh(e, t, n, r, i, s, o, l, a, c, d, f, p, h, m, _) {
    let x = _ ? zt(_[1]) ? _[1].error : _[1].data : void 0
      , v = e.createURL(t.location)
      , w = e.createURL(i)
      , y = _ && zt(_[1]) ? _[0] : void 0
      , b = y ? t2(n, y) : n
      , C = _ ? _[1].statusCode : void 0
      , S = o && C && C >= 400
      , T = b.filter( (P, R) => {
        let {route: A} = P;
        if (A.lazy)
            return !0;
        if (A.loader == null)
            return !1;
        if (s)
            return typeof A.loader != "function" || A.loader.hydrate ? !0 : t.loaderData[A.id] === void 0 && (!t.errors || t.errors[A.id] === void 0);
        if (n2(t.loaderData, t.matches[R], P) || a.some(U => U === P.route.id))
            return !0;
        let V = t.matches[R]
          , F = P;
        return Ah(P, De({
            currentUrl: v,
            currentParams: V.params,
            nextUrl: w,
            nextParams: F.params
        }, r, {
            actionResult: x,
            unstable_actionStatus: C,
            defaultShouldRevalidate: S ? !1 : l || v.pathname + v.search === w.pathname + w.search || v.search !== w.search || oy(V, F)
        }))
    }
    )
      , k = [];
    return f.forEach( (P, R) => {
        if (s || !n.some(Q => Q.route.id === P.routeId) || d.has(R))
            return;
        let A = ei(h, P.path, m);
        if (!A) {
            k.push({
                key: R,
                routeId: P.routeId,
                path: P.path,
                matches: null,
                match: null,
                controller: null
            });
            return
        }
        let V = t.fetchers.get(R)
          , F = Qs(A, P.path)
          , U = !1;
        p.has(R) ? U = !1 : c.includes(R) ? U = !0 : V && V.state !== "idle" && V.data === void 0 ? U = l : U = Ah(F, De({
            currentUrl: v,
            currentParams: t.matches[t.matches.length - 1].params,
            nextUrl: w,
            nextParams: n[n.length - 1].params
        }, r, {
            actionResult: x,
            unstable_actionStatus: C,
            defaultShouldRevalidate: S ? !1 : l
        })),
        U && k.push({
            key: R,
            routeId: P.routeId,
            path: P.path,
            matches: A,
            match: F,
            controller: new AbortController
        })
    }
    ),
    [T, k]
}
function n2(e, t, n) {
    let r = !t || n.route.id !== t.route.id
      , i = e[n.route.id] === void 0;
    return r || i
}
function oy(e, t) {
    let n = e.route.path;
    return e.pathname !== t.pathname || n != null && n.endsWith("*") && e.params["*"] !== t.params["*"]
}
function Ah(e, t) {
    if (e.route.shouldRevalidate) {
        let n = e.route.shouldRevalidate(t);
        if (typeof n == "boolean")
            return n
    }
    return t.defaultShouldRevalidate
}
async function r2(e, t, n, r, i, s, o, l) {
    let a = [t, ...n.map(c => c.route.id)].join("-");
    try {
        let c = o.get(a);
        c || (c = e({
            path: t,
            matches: n,
            patch: (d, f) => {
                l.aborted || ay(d, f, r, i, s)
            }
        }),
        o.set(a, c)),
        c && d2(c) && await c
    } finally {
        o.delete(a)
    }
}
function ay(e, t, n, r, i) {
    if (e) {
        var s;
        let o = r[e];
        ce(o, "No route found to patch children into: routeId = " + e);
        let l = Oo(t, i, [e, "patch", String(((s = o.children) == null ? void 0 : s.length) || "0")], r);
        o.children ? o.children.push(...l) : o.children = l
    } else {
        let o = Oo(t, i, ["patch", String(n.length || "0")], r);
        n.push(...o)
    }
}
async function Dh(e, t, n) {
    if (!e.lazy)
        return;
    let r = await e.lazy();
    if (!e.lazy)
        return;
    let i = n[e.id];
    ce(i, "No route found in manifest");
    let s = {};
    for (let o in r) {
        let a = i[o] !== void 0 && o !== "hasErrorBoundary";
        ps(!a, 'Route "' + i.id + '" has a static property "' + o + '" defined but its lazy function is also returning a value for this property. ' + ('The lazy route property "' + o + '" will be ignored.')),
        !a && !ES.has(o) && (s[o] = r[o])
    }
    Object.assign(i, s),
    Object.assign(i, De({}, t(i), {
        lazy: void 0
    }))
}
function i2(e) {
    return Promise.all(e.matches.map(t => t.resolve()))
}
async function s2(e, t, n, r, i, s, o, l) {
    let a = r.reduce( (f, p) => f.add(p.route.id), new Set)
      , c = new Set
      , d = await e({
        matches: i.map(f => {
            let p = a.has(f.route.id);
            return De({}, f, {
                shouldLoad: p,
                resolve: m => (c.add(f.route.id),
                p ? o2(t, n, f, s, o, m, l) : Promise.resolve({
                    type: Pe.data,
                    result: void 0
                }))
            })
        }
        ),
        request: n,
        params: i[0].params,
        context: l
    });
    return i.forEach(f => ce(c.has(f.route.id), '`match.resolve()` was not called for route id "' + f.route.id + '". You must call `match.resolve()` on every match passed to `dataStrategy` to ensure all routes are properly loaded.')),
    d.filter( (f, p) => a.has(i[p].route.id))
}
async function o2(e, t, n, r, i, s, o) {
    let l, a, c = d => {
        let f, p = new Promise( (_, x) => f = x);
        a = () => f(),
        t.signal.addEventListener("abort", a);
        let h = _ => typeof d != "function" ? Promise.reject(new Error("You cannot call the handler for a route which defines a boolean " + ('"' + e + '" [routeId: ' + n.route.id + "]"))) : d({
            request: t,
            params: n.params,
            context: o
        }, ..._ !== void 0 ? [_] : []), m;
        return s ? m = s(_ => h(_)) : m = (async () => {
            try {
                return {
                    type: "data",
                    result: await h()
                }
            } catch (_) {
                return {
                    type: "error",
                    result: _
                }
            }
        }
        )(),
        Promise.race([m, p])
    }
    ;
    try {
        let d = n.route[e];
        if (n.route.lazy)
            if (d) {
                let f, [p] = await Promise.all([c(d).catch(h => {
                    f = h
                }
                ), Dh(n.route, i, r)]);
                if (f !== void 0)
                    throw f;
                l = p
            } else if (await Dh(n.route, i, r),
            d = n.route[e],
            d)
                l = await c(d);
            else if (e === "action") {
                let f = new URL(t.url)
                  , p = f.pathname + f.search;
                throw xt(405, {
                    method: t.method,
                    pathname: p,
                    routeId: n.route.id
                })
            } else
                return {
                    type: Pe.data,
                    result: void 0
                };
        else if (d)
            l = await c(d);
        else {
            let f = new URL(t.url)
              , p = f.pathname + f.search;
            throw xt(404, {
                pathname: p
            })
        }
        ce(l.result !== void 0, "You defined " + (e === "action" ? "an action" : "a loader") + " for route " + ('"' + n.route.id + "\" but didn't return anything from your `" + e + "` ") + "function. Please return a value or `null`.")
    } catch (d) {
        return {
            type: Pe.error,
            result: d
        }
    } finally {
        a && t.signal.removeEventListener("abort", a)
    }
    return l
}
async function a2(e) {
    let {result: t, type: n, status: r} = e;
    if (cy(t)) {
        let o;
        try {
            let l = t.headers.get("Content-Type");
            l && /\bapplication\/json\b/.test(l) ? t.body == null ? o = null : o = await t.json() : o = await t.text()
        } catch (l) {
            return {
                type: Pe.error,
                error: l
            }
        }
        return n === Pe.error ? {
            type: Pe.error,
            error: new kp(t.status,t.statusText,o),
            statusCode: t.status,
            headers: t.headers
        } : {
            type: Pe.data,
            data: o,
            statusCode: t.status,
            headers: t.headers
        }
    }
    if (n === Pe.error)
        return {
            type: Pe.error,
            error: t,
            statusCode: hc(t) ? t.status : r
        };
    if (p2(t)) {
        var i, s;
        return {
            type: Pe.deferred,
            deferredData: t,
            statusCode: (i = t.init) == null ? void 0 : i.status,
            headers: ((s = t.init) == null ? void 0 : s.headers) && new Headers(t.init.headers)
        }
    }
    return {
        type: Pe.data,
        data: t,
        statusCode: r
    }
}
function l2(e, t, n, r, i, s) {
    let o = e.headers.get("Location");
    if (ce(o, "Redirects returned/thrown from loaders/actions must have a Location header"),
    !Ep.test(o)) {
        let l = r.slice(0, r.findIndex(a => a.route.id === n) + 1);
        o = Ud(new URL(t.url), l, i, !0, o, s),
        e.headers.set("Location", o)
    }
    return e
}
function $h(e, t, n) {
    if (Ep.test(e)) {
        let r = e
          , i = r.startsWith("//") ? new URL(t.protocol + r) : new URL(r)
          , s = xs(i.pathname, n) != null;
        if (i.origin === t.origin && s)
            return i.pathname + i.search + i.hash
    }
    return e
}
function Mi(e, t, n, r) {
    let i = e.createURL(ly(t)).toString()
      , s = {
        signal: n
    };
    if (r && gn(r.formMethod)) {
        let {formMethod: o, formEncType: l} = r;
        s.method = o.toUpperCase(),
        l === "application/json" ? (s.headers = new Headers({
            "Content-Type": l
        }),
        s.body = JSON.stringify(r.json)) : l === "text/plain" ? s.body = r.text : l === "application/x-www-form-urlencoded" && r.formData ? s.body = Hd(r.formData) : s.body = r.formData
    }
    return new Request(i,s)
}
function Hd(e) {
    let t = new URLSearchParams;
    for (let[n,r] of e.entries())
        t.append(n, typeof r == "string" ? r : r.name);
    return t
}
function Fh(e) {
    let t = new FormData;
    for (let[n,r] of e.entries())
        t.append(n, r);
    return t
}
function c2(e, t, n, r, i, s) {
    let o = {}, l = null, a, c = !1, d = {}, f = r && zt(r[1]) ? r[1].error : void 0;
    return n.forEach( (p, h) => {
        let m = t[h].route.id;
        if (ce(!si(p), "Cannot handle redirect results in processLoaderData"),
        zt(p)) {
            let _ = p.error;
            if (f !== void 0 && (_ = f,
            f = void 0),
            l = l || {},
            s)
                l[m] = _;
            else {
                let x = lo(e, m);
                l[x.route.id] == null && (l[x.route.id] = _)
            }
            o[m] = void 0,
            c || (c = !0,
            a = hc(p.error) ? p.error.status : 500),
            p.headers && (d[m] = p.headers)
        } else
            ii(p) ? (i.set(m, p.deferredData),
            o[m] = p.deferredData.data,
            p.statusCode != null && p.statusCode !== 200 && !c && (a = p.statusCode),
            p.headers && (d[m] = p.headers)) : (o[m] = p.data,
            p.statusCode && p.statusCode !== 200 && !c && (a = p.statusCode),
            p.headers && (d[m] = p.headers))
    }
    ),
    f !== void 0 && r && (l = {
        [r[0]]: f
    },
    o[r[0]] = void 0),
    {
        loaderData: o,
        errors: l,
        statusCode: a || 200,
        loaderHeaders: d
    }
}
function Vh(e, t, n, r, i, s, o, l) {
    let {loaderData: a, errors: c} = c2(t, n, r, i, l, !1);
    for (let d = 0; d < s.length; d++) {
        let {key: f, match: p, controller: h} = s[d];
        ce(o !== void 0 && o[d] !== void 0, "Did not find corresponding fetcher result");
        let m = o[d];
        if (!(h && h.signal.aborted))
            if (zt(m)) {
                let _ = lo(e.matches, p == null ? void 0 : p.route.id);
                c && c[_.route.id] || (c = De({}, c, {
                    [_.route.id]: m.error
                })),
                e.fetchers.delete(f)
            } else if (si(m))
                ce(!1, "Unhandled fetcher revalidation redirect");
            else if (ii(m))
                ce(!1, "Unhandled fetcher deferred data");
            else {
                let _ = pr(m.data);
                e.fetchers.set(f, _)
            }
    }
    return {
        loaderData: a,
        errors: c
    }
}
function zh(e, t, n, r) {
    let i = De({}, t);
    for (let s of n) {
        let o = s.route.id;
        if (t.hasOwnProperty(o) ? t[o] !== void 0 && (i[o] = t[o]) : e[o] !== void 0 && s.route.loader && (i[o] = e[o]),
        r && r.hasOwnProperty(o))
            break
    }
    return i
}
function Bh(e) {
    return e ? zt(e[1]) ? {
        actionData: {}
    } : {
        actionData: {
            [e[0]]: e[1].data
        }
    } : {}
}
function lo(e, t) {
    return (t ? e.slice(0, e.findIndex(r => r.route.id === t) + 1) : [...e]).reverse().find(r => r.route.hasErrorBoundary === !0) || e[0]
}
function Uh(e) {
    let t = e.length === 1 ? e[0] : e.find(n => n.index || !n.path || n.path === "/") || {
        id: "__shim-error-route__"
    };
    return {
        matches: [{
            params: {},
            pathname: "",
            pathnameBase: "",
            route: t
        }],
        route: t
    }
}
function xt(e, t) {
    let {pathname: n, routeId: r, method: i, type: s, message: o} = t === void 0 ? {} : t
      , l = "Unknown Server Error"
      , a = "Unknown @remix-run/router error";
    return e === 400 ? (l = "Bad Request",
    s === "route-discovery" ? a = 'Unable to match URL "' + n + '" - the `children()` function for ' + ("route `" + r + "` threw the following error:\n" + o) : i && n && r ? a = "You made a " + i + ' request to "' + n + '" but ' + ('did not provide a `loader` for route "' + r + '", ') + "so there is no way to handle the request." : s === "defer-action" ? a = "defer() is not supported in actions" : s === "invalid-body" && (a = "Unable to encode submission body")) : e === 403 ? (l = "Forbidden",
    a = 'Route "' + r + '" does not match URL "' + n + '"') : e === 404 ? (l = "Not Found",
    a = 'No route matches URL "' + n + '"') : e === 405 && (l = "Method Not Allowed",
    i && n && r ? a = "You made a " + i.toUpperCase() + ' request to "' + n + '" but ' + ('did not provide an `action` for route "' + r + '", ') + "so there is no way to handle the request." : i && (a = 'Invalid request method "' + i.toUpperCase() + '"')),
    new kp(e || 500,l,new Error(a),!0)
}
function Hh(e) {
    for (let t = e.length - 1; t >= 0; t--) {
        let n = e[t];
        if (si(n))
            return {
                result: n,
                idx: t
            }
    }
}
function ly(e) {
    let t = typeof e == "string" ? rr(e) : e;
    return hi(De({}, t, {
        hash: ""
    }))
}
function u2(e, t) {
    return e.pathname !== t.pathname || e.search !== t.search ? !1 : e.hash === "" ? t.hash !== "" : e.hash === t.hash ? !0 : t.hash !== ""
}
function d2(e) {
    return typeof e == "object" && e != null && "then"in e
}
function f2(e) {
    return cy(e.result) && KS.has(e.result.status)
}
function ii(e) {
    return e.type === Pe.deferred
}
function zt(e) {
    return e.type === Pe.error
}
function si(e) {
    return (e && e.type) === Pe.redirect
}
function p2(e) {
    let t = e;
    return t && typeof t == "object" && typeof t.data == "object" && typeof t.subscribe == "function" && typeof t.cancel == "function" && typeof t.resolveData == "function"
}
function cy(e) {
    return e != null && typeof e.status == "number" && typeof e.statusText == "string" && typeof e.headers == "object" && typeof e.body < "u"
}
function m2(e) {
    return YS.has(e.toLowerCase())
}
function gn(e) {
    return WS.has(e.toLowerCase())
}
async function Gh(e, t, n, r, i, s) {
    for (let o = 0; o < n.length; o++) {
        let l = n[o]
          , a = t[o];
        if (!a)
            continue;
        let c = e.find(f => f.route.id === a.route.id)
          , d = c != null && !oy(c, a) && (s && s[a.route.id]) !== void 0;
        if (ii(l) && (i || d)) {
            let f = r[o];
            ce(f, "Expected an AbortSignal for revalidating fetcher deferred result"),
            await uy(l, f, i).then(p => {
                p && (n[o] = p || n[o])
            }
            )
        }
    }
}
async function uy(e, t, n) {
    if (n === void 0 && (n = !1),
    !await e.deferredData.resolveData(t)) {
        if (n)
            try {
                return {
                    type: Pe.data,
                    data: e.deferredData.unwrappedData
                }
            } catch (i) {
                return {
                    type: Pe.error,
                    error: i
                }
            }
        return {
            type: Pe.data,
            data: e.deferredData.data
        }
    }
}
function Tp(e) {
    return new URLSearchParams(e).getAll("index").some(t => t === "")
}
function Qs(e, t) {
    let n = typeof t == "string" ? rr(t).search : t.search;
    if (e[e.length - 1].route.index && Tp(n || ""))
        return e[e.length - 1];
    let r = ry(e);
    return r[r.length - 1]
}
function Wh(e) {
    let {formMethod: t, formAction: n, formEncType: r, text: i, formData: s, json: o} = e;
    if (!(!t || !n || !r)) {
        if (i != null)
            return {
                formMethod: t,
                formAction: n,
                formEncType: r,
                formData: void 0,
                json: void 0,
                text: i
            };
        if (s != null)
            return {
                formMethod: t,
                formAction: n,
                formEncType: r,
                formData: s,
                json: void 0,
                text: void 0
            };
        if (o !== void 0)
            return {
                formMethod: t,
                formAction: n,
                formEncType: r,
                formData: void 0,
                json: o,
                text: void 0
            }
    }
}
function hu(e, t) {
    return t ? {
        state: "loading",
        location: e,
        formMethod: t.formMethod,
        formAction: t.formAction,
        formEncType: t.formEncType,
        formData: t.formData,
        json: t.json,
        text: t.text
    } : {
        state: "loading",
        location: e,
        formMethod: void 0,
        formAction: void 0,
        formEncType: void 0,
        formData: void 0,
        json: void 0,
        text: void 0
    }
}
function h2(e, t) {
    return {
        state: "submitting",
        location: e,
        formMethod: t.formMethod,
        formAction: t.formAction,
        formEncType: t.formEncType,
        formData: t.formData,
        json: t.json,
        text: t.text
    }
}
function Ds(e, t) {
    return e ? {
        state: "loading",
        formMethod: e.formMethod,
        formAction: e.formAction,
        formEncType: e.formEncType,
        formData: e.formData,
        json: e.json,
        text: e.text,
        data: t
    } : {
        state: "loading",
        formMethod: void 0,
        formAction: void 0,
        formEncType: void 0,
        formData: void 0,
        json: void 0,
        text: void 0,
        data: t
    }
}
function g2(e, t) {
    return {
        state: "submitting",
        formMethod: e.formMethod,
        formAction: e.formAction,
        formEncType: e.formEncType,
        formData: e.formData,
        json: e.json,
        text: e.text,
        data: t ? t.data : void 0
    }
}
function pr(e) {
    return {
        state: "idle",
        formMethod: void 0,
        formAction: void 0,
        formEncType: void 0,
        formData: void 0,
        json: void 0,
        text: void 0,
        data: e
    }
}
function _2(e, t) {
    try {
        let n = e.sessionStorage.getItem(sy);
        if (n) {
            let r = JSON.parse(n);
            for (let[i,s] of Object.entries(r || {}))
                s && Array.isArray(s) && t.set(i, new Set(s || []))
        }
    } catch {}
}
function v2(e, t) {
    if (t.size > 0) {
        let n = {};
        for (let[r,i] of t)
            n[r] = [...i];
        try {
            e.sessionStorage.setItem(sy, JSON.stringify(n))
        } catch (r) {
            ps(!1, "Failed to save applied view transitions in sessionStorage (" + r + ").")
        }
    }
}
/**
 * React Router v6.24.0
 *
 * Copyright (c) Remix Software Inc.
 *
 * This source code is licensed under the MIT license found in the
 * LICENSE.md file in the root directory of this source tree.
 *
 * @license MIT
 */
function Ao() {
    return Ao = Object.assign ? Object.assign.bind() : function(e) {
        for (var t = 1; t < arguments.length; t++) {
            var n = arguments[t];
            for (var r in n)
                Object.prototype.hasOwnProperty.call(n, r) && (e[r] = n[r])
        }
        return e
    }
    ,
    Ao.apply(this, arguments)
}
const gc = g.createContext(null)
  , dy = g.createContext(null)
  , xi = g.createContext(null)
  , _c = g.createContext(null)
  , Ur = g.createContext({
    outlet: null,
    matches: [],
    isDataRoute: !1
})
  , fy = g.createContext(null);
function y2(e, t) {
    let {relative: n} = t === void 0 ? {} : t;
    ea() || ce(!1);
    let {basename: r, navigator: i} = g.useContext(xi)
      , {hash: s, pathname: o, search: l} = gy(e, {
        relative: n
    })
      , a = o;
    return r !== "/" && (a = o === "/" ? r : Yn([r, o])),
    i.createHref({
        pathname: a,
        search: l,
        hash: s
    })
}
function ea() {
    return g.useContext(_c) != null
}
function bi() {
    return ea() || ce(!1),
    g.useContext(_c).location
}
function py(e) {
    g.useContext(xi).static || g.useLayoutEffect(e)
}
function my() {
    let {isDataRoute: e} = g.useContext(Ur);
    return e ? I2() : w2()
}
function w2() {
    ea() || ce(!1);
    let e = g.useContext(gc)
      , {basename: t, future: n, navigator: r} = g.useContext(xi)
      , {matches: i} = g.useContext(Ur)
      , {pathname: s} = bi()
      , o = JSON.stringify(Sp(i, n.v7_relativeSplatPath))
      , l = g.useRef(!1);
    return py( () => {
        l.current = !0
    }
    ),
    g.useCallback(function(c, d) {
        if (d === void 0 && (d = {}),
        !l.current)
            return;
        if (typeof c == "number") {
            r.go(c);
            return
        }
        let f = Cp(c, JSON.parse(o), s, d.relative === "path");
        e == null && t !== "/" && (f.pathname = f.pathname === "/" ? t : Yn([t, f.pathname])),
        (d.replace ? r.replace : r.push)(f, d.state, d)
    }, [t, r, o, s, e])
}
const hy = g.createContext(null);
function vc() {
    return g.useContext(hy)
}
function x2(e) {
    let t = g.useContext(Ur).outlet;
    return t && g.createElement(hy.Provider, {
        value: e
    }, t)
}
function gy(e, t) {
    let {relative: n} = t === void 0 ? {} : t
      , {future: r} = g.useContext(xi)
      , {matches: i} = g.useContext(Ur)
      , {pathname: s} = bi()
      , o = JSON.stringify(Sp(i, r.v7_relativeSplatPath));
    return g.useMemo( () => Cp(e, JSON.parse(o), s, n === "path"), [e, o, s, n])
}
function b2(e, t, n, r) {
    ea() || ce(!1);
    let {navigator: i} = g.useContext(xi)
      , {matches: s} = g.useContext(Ur)
      , o = s[s.length - 1]
      , l = o ? o.params : {};
    o && o.pathname;
    let a = o ? o.pathnameBase : "/";
    o && o.route;
    let c = bi(), d;
    if (t) {
        var f;
        let x = typeof t == "string" ? rr(t) : t;
        a === "/" || (f = x.pathname) != null && f.startsWith(a) || ce(!1),
        d = x
    } else
        d = c;
    let p = d.pathname || "/"
      , h = p;
    if (a !== "/") {
        let x = a.replace(/^\//, "").split("/");
        h = "/" + p.replace(/^\//, "").split("/").slice(x.length).join("/")
    }
    let m = ei(e, {
        pathname: h
    })
      , _ = T2(m && m.map(x => Object.assign({}, x, {
        params: Object.assign({}, l, x.params),
        pathname: Yn([a, i.encodeLocation ? i.encodeLocation(x.pathname).pathname : x.pathname]),
        pathnameBase: x.pathnameBase === "/" ? a : Yn([a, i.encodeLocation ? i.encodeLocation(x.pathnameBase).pathname : x.pathnameBase])
    })), s, n, r);
    return t && _ ? g.createElement(_c.Provider, {
        value: {
            location: Ao({
                pathname: "/",
                search: "",
                hash: "",
                state: null,
                key: "default"
            }, d),
            navigationType: qe.Pop
        }
    }, _) : _
}
function S2() {
    let e = R2()
      , t = hc(e) ? e.status + " " + e.statusText : e instanceof Error ? e.message : JSON.stringify(e)
      , n = e instanceof Error ? e.stack : null
      , i = {
        padding: "0.5rem",
        backgroundColor: "rgba(200,200,200, 0.5)"
    }
      , s = null;
    return g.createElement(g.Fragment, null, g.createElement("h2", null, "Unexpected Application Error!"), g.createElement("h3", {
        style: {
            fontStyle: "italic"
        }
    }, t), n ? g.createElement("pre", {
        style: i
    }, n) : null, s)
}
const C2 = g.createElement(S2, null);
class k2 extends g.Component {
    constructor(t) {
        super(t),
        this.state = {
            location: t.location,
            revalidation: t.revalidation,
            error: t.error
        }
    }
    static getDerivedStateFromError(t) {
        return {
            error: t
        }
    }
    static getDerivedStateFromProps(t, n) {
        return n.location !== t.location || n.revalidation !== "idle" && t.revalidation === "idle" ? {
            error: t.error,
            location: t.location,
            revalidation: t.revalidation
        } : {
            error: t.error !== void 0 ? t.error : n.error,
            location: n.location,
            revalidation: t.revalidation || n.revalidation
        }
    }
    componentDidCatch(t, n) {
        console.error("React Router caught the following error during render", t, n)
    }
    render() {
        return this.state.error !== void 0 ? g.createElement(Ur.Provider, {
            value: this.props.routeContext
        }, g.createElement(fy.Provider, {
            value: this.state.error,
            children: this.props.component
        })) : this.props.children
    }
}
function E2(e) {
    let {routeContext: t, match: n, children: r} = e
      , i = g.useContext(gc);
    return i && i.static && i.staticContext && (n.route.errorElement || n.route.ErrorBoundary) && (i.staticContext._deepestRenderedBoundaryId = n.route.id),
    g.createElement(Ur.Provider, {
        value: t
    }, r)
}
function T2(e, t, n, r) {
    var i;
    if (t === void 0 && (t = []),
    n === void 0 && (n = null),
    r === void 0 && (r = null),
    e == null) {
        var s;
        if ((s = n) != null && s.errors)
            e = n.matches;
        else
            return null
    }
    let o = e
      , l = (i = n) == null ? void 0 : i.errors;
    if (l != null) {
        let d = o.findIndex(f => f.route.id && (l == null ? void 0 : l[f.route.id]) !== void 0);
        d >= 0 || ce(!1),
        o = o.slice(0, Math.min(o.length, d + 1))
    }
    let a = !1
      , c = -1;
    if (n && r && r.v7_partialHydration)
        for (let d = 0; d < o.length; d++) {
            let f = o[d];
            if ((f.route.HydrateFallback || f.route.hydrateFallbackElement) && (c = d),
            f.route.id) {
                let {loaderData: p, errors: h} = n
                  , m = f.route.loader && p[f.route.id] === void 0 && (!h || h[f.route.id] === void 0);
                if (f.route.lazy || m) {
                    a = !0,
                    c >= 0 ? o = o.slice(0, c + 1) : o = [o[0]];
                    break
                }
            }
        }
    return o.reduceRight( (d, f, p) => {
        let h, m = !1, _ = null, x = null;
        n && (h = l && f.route.id ? l[f.route.id] : void 0,
        _ = f.route.errorElement || C2,
        a && (c < 0 && p === 0 ? (L2("route-fallback", !1),
        m = !0,
        x = null) : c === p && (m = !0,
        x = f.route.hydrateFallbackElement || null)));
        let v = t.concat(o.slice(0, p + 1))
          , w = () => {
            let y;
            return h ? y = _ : m ? y = x : f.route.Component ? y = g.createElement(f.route.Component, null) : f.route.element ? y = f.route.element : y = d,
            g.createElement(E2, {
                match: f,
                routeContext: {
                    outlet: d,
                    matches: v,
                    isDataRoute: n != null
                },
                children: y
            })
        }
        ;
        return n && (f.route.ErrorBoundary || f.route.errorElement || p === 0) ? g.createElement(k2, {
            location: n.location,
            revalidation: n.revalidation,
            component: _,
            error: h,
            children: w(),
            routeContext: {
                outlet: null,
                matches: v,
                isDataRoute: !0
            }
        }) : w()
    }
    , null)
}
var _y = function(e) {
    return e.UseBlocker = "useBlocker",
    e.UseRevalidator = "useRevalidator",
    e.UseNavigateStable = "useNavigate",
    e
}(_y || {})
  , Dl = function(e) {
    return e.UseBlocker = "useBlocker",
    e.UseLoaderData = "useLoaderData",
    e.UseActionData = "useActionData",
    e.UseRouteError = "useRouteError",
    e.UseNavigation = "useNavigation",
    e.UseRouteLoaderData = "useRouteLoaderData",
    e.UseMatches = "useMatches",
    e.UseRevalidator = "useRevalidator",
    e.UseNavigateStable = "useNavigate",
    e.UseRouteId = "useRouteId",
    e
}(Dl || {});
function P2(e) {
    let t = g.useContext(gc);
    return t || ce(!1),
    t
}
function j2(e) {
    let t = g.useContext(dy);
    return t || ce(!1),
    t
}
function N2(e) {
    let t = g.useContext(Ur);
    return t || ce(!1),
    t
}
function vy(e) {
    let t = N2()
      , n = t.matches[t.matches.length - 1];
    return n.route.id || ce(!1),
    n.route.id
}
function R2() {
    var e;
    let t = g.useContext(fy)
      , n = j2(Dl.UseRouteError)
      , r = vy(Dl.UseRouteError);
    return t !== void 0 ? t : (e = n.errors) == null ? void 0 : e[r]
}
function I2() {
    let {router: e} = P2(_y.UseNavigateStable)
      , t = vy(Dl.UseNavigateStable)
      , n = g.useRef(!1);
    return py( () => {
        n.current = !0
    }
    ),
    g.useCallback(function(i, s) {
        s === void 0 && (s = {}),
        n.current && (typeof i == "number" ? e.navigate(i) : e.navigate(i, Ao({
            fromRouteId: t
        }, s)))
    }, [e, t])
}
const qh = {};
function L2(e, t, n) {
    !t && !qh[e] && (qh[e] = !0)
}
function Si(e) {
    return x2(e.context)
}
function M2(e) {
    let {basename: t="/", children: n=null, location: r, navigationType: i=qe.Pop, navigator: s, static: o=!1, future: l} = e;
    ea() && ce(!1);
    let a = t.replace(/^\/*/, "/")
      , c = g.useMemo( () => ({
        basename: a,
        navigator: s,
        static: o,
        future: Ao({
            v7_relativeSplatPath: !1
        }, l)
    }), [a, l, s, o]);
    typeof r == "string" && (r = rr(r));
    let {pathname: d="/", search: f="", hash: p="", state: h=null, key: m="default"} = r
      , _ = g.useMemo( () => {
        let x = xs(d, a);
        return x == null ? null : {
            location: {
                pathname: x,
                search: f,
                hash: p,
                state: h,
                key: m
            },
            navigationType: i
        }
    }
    , [a, d, f, p, h, m, i]);
    return _ == null ? null : g.createElement(xi.Provider, {
        value: c
    }, g.createElement(_c.Provider, {
        children: n,
        value: _
    }))
}
new Promise( () => {}
);
function O2(e) {
    let t = {
        hasErrorBoundary: e.ErrorBoundary != null || e.errorElement != null
    };
    return e.Component && Object.assign(t, {
        element: g.createElement(e.Component),
        Component: void 0
    }),
    e.HydrateFallback && Object.assign(t, {
        hydrateFallbackElement: g.createElement(e.HydrateFallback),
        HydrateFallback: void 0
    }),
    e.ErrorBoundary && Object.assign(t, {
        errorElement: g.createElement(e.ErrorBoundary),
        ErrorBoundary: void 0
    }),
    t
}
/**
 * React Router DOM v6.24.0
 *
 * Copyright (c) Remix Software Inc.
 *
 * This source code is licensed under the MIT license found in the
 * LICENSE.md file in the root directory of this source tree.
 *
 * @license MIT
 */
function Do() {
    return Do = Object.assign ? Object.assign.bind() : function(e) {
        for (var t = 1; t < arguments.length; t++) {
            var n = arguments[t];
            for (var r in n)
                Object.prototype.hasOwnProperty.call(n, r) && (e[r] = n[r])
        }
        return e
    }
    ,
    Do.apply(this, arguments)
}
function A2(e, t) {
    if (e == null)
        return {};
    var n = {}, r = Object.keys(e), i, s;
    for (s = 0; s < r.length; s++)
        i = r[s],
        !(t.indexOf(i) >= 0) && (n[i] = e[i]);
    return n
}
function D2(e) {
    return !!(e.metaKey || e.altKey || e.ctrlKey || e.shiftKey)
}
function $2(e, t) {
    return e.button === 0 && (!t || t === "_self") && !D2(e)
}
const F2 = ["onClick", "relative", "reloadDocument", "replace", "state", "target", "to", "preventScrollReset", "unstable_viewTransition"]
  , V2 = "6";
try {
    window.__reactRouterVersion = V2
} catch {}
function z2(e, t) {
    return ZS({
        basename: t == null ? void 0 : t.basename,
        future: Do({}, t == null ? void 0 : t.future, {
            v7_prependBasename: !0
        }),
        history: SS({
            window: t == null ? void 0 : t.window
        }),
        hydrationData: (t == null ? void 0 : t.hydrationData) || B2(),
        routes: e,
        mapRouteProperties: O2,
        unstable_dataStrategy: t == null ? void 0 : t.unstable_dataStrategy,
        unstable_patchRoutesOnMiss: t == null ? void 0 : t.unstable_patchRoutesOnMiss,
        window: t == null ? void 0 : t.window
    }).initialize()
}
function B2() {
    var e;
    let t = (e = window) == null ? void 0 : e.__staticRouterHydrationData;
    return t && t.errors && (t = Do({}, t, {
        errors: U2(t.errors)
    })),
    t
}
function U2(e) {
    if (!e)
        return null;
    let t = Object.entries(e)
      , n = {};
    for (let[r,i] of t)
        if (i && i.__type === "RouteErrorResponse")
            n[r] = new kp(i.status,i.statusText,i.data,i.internal === !0);
        else if (i && i.__type === "Error") {
            if (i.__subType) {
                let s = window[i.__subType];
                if (typeof s == "function")
                    try {
                        let o = new s(i.message);
                        o.stack = "",
                        n[r] = o
                    } catch {}
            }
            if (n[r] == null) {
                let s = new Error(i.message);
                s.stack = "",
                n[r] = s
            }
        } else
            n[r] = i;
    return n
}
const H2 = g.createContext({
    isTransitioning: !1
})
  , G2 = g.createContext(new Map)
  , W2 = "startTransition"
  , Yh = u1[W2]
  , q2 = "flushSync"
  , Kh = bS[q2];
function Y2(e) {
    Yh ? Yh(e) : e()
}
function $s(e) {
    Kh ? Kh(e) : e()
}
class K2 {
    constructor() {
        this.status = "pending",
        this.promise = new Promise( (t, n) => {
            this.resolve = r => {
                this.status === "pending" && (this.status = "resolved",
                t(r))
            }
            ,
            this.reject = r => {
                this.status === "pending" && (this.status = "rejected",
                n(r))
            }
        }
        )
    }
}
function X2(e) {
    let {fallbackElement: t, router: n, future: r} = e
      , [i,s] = g.useState(n.state)
      , [o,l] = g.useState()
      , [a,c] = g.useState({
        isTransitioning: !1
    })
      , [d,f] = g.useState()
      , [p,h] = g.useState()
      , [m,_] = g.useState()
      , x = g.useRef(new Map)
      , {v7_startTransition: v} = r || {}
      , w = g.useCallback(T => {
        v ? Y2(T) : T()
    }
    , [v])
      , y = g.useCallback( (T, k) => {
        let {deletedFetchers: P, unstable_flushSync: R, unstable_viewTransitionOpts: A} = k;
        P.forEach(F => x.current.delete(F)),
        T.fetchers.forEach( (F, U) => {
            F.data !== void 0 && x.current.set(U, F.data)
        }
        );
        let V = n.window == null || n.window.document == null || typeof n.window.document.startViewTransition != "function";
        if (!A || V) {
            R ? $s( () => s(T)) : w( () => s(T));
            return
        }
        if (R) {
            $s( () => {
                p && (d && d.resolve(),
                p.skipTransition()),
                c({
                    isTransitioning: !0,
                    flushSync: !0,
                    currentLocation: A.currentLocation,
                    nextLocation: A.nextLocation
                })
            }
            );
            let F = n.window.document.startViewTransition( () => {
                $s( () => s(T))
            }
            );
            F.finished.finally( () => {
                $s( () => {
                    f(void 0),
                    h(void 0),
                    l(void 0),
                    c({
                        isTransitioning: !1
                    })
                }
                )
            }
            ),
            $s( () => h(F));
            return
        }
        p ? (d && d.resolve(),
        p.skipTransition(),
        _({
            state: T,
            currentLocation: A.currentLocation,
            nextLocation: A.nextLocation
        })) : (l(T),
        c({
            isTransitioning: !0,
            flushSync: !1,
            currentLocation: A.currentLocation,
            nextLocation: A.nextLocation
        }))
    }
    , [n.window, p, d, x, w]);
    g.useLayoutEffect( () => n.subscribe(y), [n, y]),
    g.useEffect( () => {
        a.isTransitioning && !a.flushSync && f(new K2)
    }
    , [a]),
    g.useEffect( () => {
        if (d && o && n.window) {
            let T = o
              , k = d.promise
              , P = n.window.document.startViewTransition(async () => {
                w( () => s(T)),
                await k
            }
            );
            P.finished.finally( () => {
                f(void 0),
                h(void 0),
                l(void 0),
                c({
                    isTransitioning: !1
                })
            }
            ),
            h(P)
        }
    }
    , [w, o, d, n.window]),
    g.useEffect( () => {
        d && o && i.location.key === o.location.key && d.resolve()
    }
    , [d, p, i.location, o]),
    g.useEffect( () => {
        !a.isTransitioning && m && (l(m.state),
        c({
            isTransitioning: !0,
            flushSync: !1,
            currentLocation: m.currentLocation,
            nextLocation: m.nextLocation
        }),
        _(void 0))
    }
    , [a.isTransitioning, m]),
    g.useEffect( () => {}
    , []);
    let b = g.useMemo( () => ({
        createHref: n.createHref,
        encodeLocation: n.encodeLocation,
        go: T => n.navigate(T),
        push: (T, k, P) => n.navigate(T, {
            state: k,
            preventScrollReset: P == null ? void 0 : P.preventScrollReset
        }),
        replace: (T, k, P) => n.navigate(T, {
            replace: !0,
            state: k,
            preventScrollReset: P == null ? void 0 : P.preventScrollReset
        })
    }), [n])
      , C = n.basename || "/"
      , S = g.useMemo( () => ({
        router: n,
        navigator: b,
        static: !1,
        basename: C
    }), [n, b, C]);
    return g.createElement(g.Fragment, null, g.createElement(gc.Provider, {
        value: S
    }, g.createElement(dy.Provider, {
        value: i
    }, g.createElement(G2.Provider, {
        value: x.current
    }, g.createElement(H2.Provider, {
        value: a
    }, g.createElement(M2, {
        basename: C,
        location: i.location,
        navigationType: i.historyAction,
        navigator: b,
        future: {
            v7_relativeSplatPath: n.future.v7_relativeSplatPath
        }
    }, i.initialized || n.future.v7_partialHydration ? g.createElement(Q2, {
        routes: n.routes,
        future: n.future,
        state: i
    }) : t))))), null)
}
function Q2(e) {
    let {routes: t, future: n, state: r} = e;
    return b2(t, void 0, r, n)
}
const J2 = typeof window < "u" && typeof window.document < "u" && typeof window.document.createElement < "u"
  , Z2 = /^(?:[a-z][a-z0-9+.-]*:|\/\/)/i
  , eC = g.forwardRef(function(t, n) {
    let {onClick: r, relative: i, reloadDocument: s, replace: o, state: l, target: a, to: c, preventScrollReset: d, unstable_viewTransition: f} = t, p = A2(t, F2), {basename: h} = g.useContext(xi), m, _ = !1;
    if (typeof c == "string" && Z2.test(c) && (m = c,
    J2))
        try {
            let y = new URL(window.location.href)
              , b = c.startsWith("//") ? new URL(y.protocol + c) : new URL(c)
              , C = xs(b.pathname, h);
            b.origin === y.origin && C != null ? c = C + b.search + b.hash : _ = !0
        } catch {}
    let x = y2(c, {
        relative: i
    })
      , v = tC(c, {
        replace: o,
        state: l,
        target: a,
        preventScrollReset: d,
        relative: i,
        unstable_viewTransition: f
    });
    function w(y) {
        r && r(y),
        y.defaultPrevented || v(y)
    }
    return g.createElement("a", Do({}, p, {
        href: m || x,
        onClick: _ || s ? r : w,
        ref: n,
        target: a
    }))
});
var Xh;
(function(e) {
    e.UseScrollRestoration = "useScrollRestoration",
    e.UseSubmit = "useSubmit",
    e.UseSubmitFetcher = "useSubmitFetcher",
    e.UseFetcher = "useFetcher",
    e.useViewTransitionState = "useViewTransitionState"
}
)(Xh || (Xh = {}));
var Qh;
(function(e) {
    e.UseFetcher = "useFetcher",
    e.UseFetchers = "useFetchers",
    e.UseScrollRestoration = "useScrollRestoration"
}
)(Qh || (Qh = {}));
function tC(e, t) {
    let {target: n, replace: r, state: i, preventScrollReset: s, relative: o, unstable_viewTransition: l} = t === void 0 ? {} : t
      , a = my()
      , c = bi()
      , d = gy(e, {
        relative: o
    });
    return g.useCallback(f => {
        if ($2(f, n)) {
            f.preventDefault();
            let p = r !== void 0 ? r : hi(c) === hi(d);
            a(e, {
                replace: p,
                state: i,
                preventScrollReset: s,
                relative: o,
                unstable_viewTransition: l
            })
        }
    }
    , [c, a, d, r, i, n, e, s, o, l])
}
const nC = "_container_1gh44_238"
  , rC = "_intro_video_1gh44_248"
  , iC = "_transition_video_1gh44_254"
  , sC = "_intro_hero_1gh44_260"
  , oC = "_accept_button_1gh44_278"
  , aC = "_flare_1gh44_283"
  , lC = "_blink_1gh44_1"
  , cC = "_accept_button_clicked_1gh44_292"
  , uC = "_fade_out_1gh44_296"
  , dC = "_fadeOut_1gh44_1"
  , fC = "_video_fade_out_1gh44_300"
  , pC = "_fadeIn_1gh44_1"
  , pn = {
    "a11y-hidden": "_a11y-hidden_1gh44_192",
    container: nC,
    intro_video: rC,
    transition_video: iC,
    intro_hero: sC,
    accept_button: oC,
    flare: aC,
    blink: lC,
    accept_button_clicked: cC,
    fade_out: uC,
    fadeOut: dC,
    video_fade_out: fC,
    fadeIn: pC
};
function Pp() {
    const e = my();
    return g.useMemo( () => ({
        goBack() {
            e(-1)
        },
        push(t, n) {
            e(t, n)
        },
        replace(t) {
            e(t, {
                replace: !0
            })
        }
    }), [e])
}
const yy = ({start: e, end: t, ref: n}) => {
    const r = async () => {
        const s = n.current;
        if (!s)
            return;
        const o = e
          , l = t;
        s.currentTime >= l && (s.currentTime = o,
        await s.play())
    }
      , i = () => {
        const s = n.current;
        s && s.addEventListener("timeupdate", r)
    }
    ;
    return g.useEffect( () => (n.current && n.current.addEventListener("loadedmetadata", function() {
        i()
    }),
    () => {
        n.current && n.current.removeEventListener("loadedmetadata", function() {
            i()
        })
    }
    ), [n, e, t]),
    {
        initializeVideo: r,
        mainVideoHandler: i
    }
}
;
function ms(e, t) {
    const n = g.useRef(null);
    return g.useEffect( () => () => {
        n.current && clearTimeout(n.current)
    }
    , []),
    (...r) => {
        n.current && clearTimeout(n.current),
        n.current = setTimeout( () => {
            e(...r)
        }
        , t)
    }
}
const Gd = {
    MAIN: "https://web-cdn.mironline.co.kr/mir2/2023_event/1108/videos/main.mp4",
    INTRO: "https://web-cdn.mironline.co.kr/mir2/2023_event/1108/videos/intro.mp4",
    TRANSITION: "https://web-cdn.mironline.co.kr/mir2/2023_event/1108/videos/transition.mp4"
}
  , mC = {
    GRA: "https://web-cdn.mironline.co.kr/mir2/2023_event/1108/images/1920_gra.png",
    LOADING: "https://web-cdn.mironline.co.kr/mir2/2023_event/1108/images/loading.gif"
}
  , gu = {
    HOME: "https://web-cdn.mironline.co.kr/mir2/2023_event/1108/images/header-home.png",
    LOGO: "https://web-cdn.mironline.co.kr/mir2/2023_event/1108/images/header-logo.png",
    X: "https://web-cdn.mironline.co.kr/mir2/2023_event/1108/images/header-x.png"
}
  , _u = {
    ACCEPT_BUTTON: "https://web-cdn.mironline.co.kr/mir2/2023_event/1108/images/intro-accept-btn.png",
    ACCEPT_BUTTON_FLARE: "https://web-cdn.mironline.co.kr/mir2/2023_event/1108/images/intro-accept-btn-flare.png",
    HERO: "https://web-cdn.mironline.co.kr/mir2/2023_event/1108/images/intro-hero.png"
}
  , Fn = {
    BG: "https://web-cdn.mironline.co.kr/mir2/2023_event/1108/images/main_bg.png",
    HERO: "https://web-cdn.mironline.co.kr/mir2/2023_event/1108/images/main-hero.png",
    ILLUSION_BENEFIT: "https://web-cdn.mironline.co.kr/mir2/2023_event/1108/images/main-illusion-benefit.png",
    ILLUSION_INFO: "https://web-cdn.mironline.co.kr/mir2/2023_event/1108/images/main-illusion-info.png",
    LIVE_BENEFIT: "https://web-cdn.mironline.co.kr/mir2/2023_event/1108/images/main-live-benefit.png",
    NEW_CONTENT: "https://web-cdn.mironline.co.kr/mir2/2023_event/1108/images/main-newcontent.png",
    ROUTER_ICON: "https://web-cdn.mironline.co.kr/mir2/2023_event/1108/images/main-router-icon.png",
    ROUTER_ICON_HOVER: "https://web-cdn.mironline.co.kr/mir2/2023_event/1108/images/main-router-icon-hover.png",
    ROUTER_ICON_NOT_HOVER: "https://web-cdn.mironline.co.kr/mir2/2023_event/1108/images/main-router-icon-not-hover.png",
    CLOUD1: "https://web-cdn.mironline.co.kr/mir2/2023_event/1108/images/main-cloud-01.png",
    CLOUD2: "https://web-cdn.mironline.co.kr/mir2/2023_event/1108/images/main-cloud-02.png",
    CLOUD3: "https://web-cdn.mironline.co.kr/mir2/2023_event/1108/images/main-cloud-03.png",
    CLOUD4: "https://web-cdn.mironline.co.kr/mir2/2023_event/1108/images/main-cloud-04.png"
}
  , hC = {
    CLOSE: "https://web-cdn.mironline.co.kr/mir2/2023_event/1108/images/modal-close.png"
}
  , on = {
    HEADER_BACK: "https://web-cdn.mironline.co.kr/mir2/2023_event/1108/images/tab-header-back.png",
    ILLUSION_BENEFIT_HERO: "https://web-cdn.mironline.co.kr/mir2/2023_event/1108/images/tab-illusion-benefit-hero.png",
    ILLUSION_BENEFIT_LEVEL: "https://web-cdn.mironline.co.kr/mir2/2023_event/1108/images/tab-illusion-benefit-level.png",
    ILLUSION_INFO_BAK_BUTTON: "https://web-cdn.mironline.co.kr/mir2/2023_event/1108/images/tab-illusion-info-bak-btn.png",
    ILLUSION_INFO_HERO: "https://web-cdn.mironline.co.kr/mir2/2023_event/1108/images/tab-illusion-info-hero.png",
    ILLUSION_INFO_TOP: "https://web-cdn.mironline.co.kr/mir2/2023_event/1108/images/tab-illusion-info-top.png",
    LIVE_BENEFIT_HERO: "https://web-cdn.mironline.co.kr/mir2/2023_event/1108/images/tab-live-benefit-hero.png",
    LIVE_NEWCONTENT: "https://web-cdn.mironline.co.kr/mir2/2023_event/1108/images/tab-live-newcontent.png"
}
  , gC = () => {
    const e = Pp()
      , [t,n] = g.useState(!1)
      , [r,i] = g.useState(!1)
      , s = g.useRef(null)
      , o = g.useRef(null)
      , l = g.useRef(null)
      , a = g.useRef(null)
      , c = g.useRef(null);
    yy({
        start: 0,
        end: 5,
        ref: s
    });
    const f = ms( () => {
        var m, _, x;
        const p = s.current
          , h = o.current;
        p && (i(!0),
        h == null || h.play(),
        p.classList.add(pn.video_fade_out),
        (m = a.current) == null || m.classList.add(pn.fade_out),
        (_ = l.current) == null || _.classList.add(pn.fade_out),
        (x = c.current) == null || x.classList.add(pn.off),
        n(!1),
        h == null || h.addEventListener("ended", () => {
            e.push("/231108/main")
        }
        ))
    }
    , 200);
    return u.jsxs("section", {
        className: pn.container,
        children: [u.jsx("video", {
            muted: !0,
            autoPlay: !0,
            ref: s,
            className: pn.intro_video,
            children: u.jsx("source", {
                src: Gd.INTRO,
                type: "video/mp4"
            })
        }), u.jsx("video", {
            muted: !0,
            className: pn.transition_video,
            ref: o,
            children: u.jsx("source", {
                src: Gd.TRANSITION,
                type: "video/mp4"
            })
        }), u.jsx("img", {
            className: pn.intro_hero,
            src: _u.HERO,
            ref: l
        }), u.jsxs("div", {
            onClick: f,
            onMouseEnter: () => n(!0),
            onMouseLeave: () => n(!1),
            className: `${r ? pn.accept_button_clicked : void 0}`,
            children: [u.jsx("img", {
                className: pn.accept_button,
                src: _u.ACCEPT_BUTTON,
                alt: "intro-accept-button",
                ref: a
            }), t && u.jsx("img", {
                className: pn.flare,
                src: _u.ACCEPT_BUTTON_FLARE,
                alt: "intro-accept-button-flare",
                ref: c
            })]
        })]
    })
}
  , _C = "_container_vsn6y_238"
  , vC = "_cloud_wrap_vsn6y_246"
  , yC = "_right_cloud1_vsn6y_259"
  , wC = "_slideRight_vsn6y_1"
  , xC = "_left_cloud1_vsn6y_265"
  , bC = "_slideLeft_vsn6y_1"
  , SC = "_router_icon_illusion_info_vsn6y_273"
  , CC = "_fadeIn_vsn6y_1"
  , kC = "_router_icon_illusion_benefit_vsn6y_303"
  , EC = "_router_icon_live_benefit_vsn6y_333"
  , TC = "_router_icon_newcontent_vsn6y_363"
  , PC = "_rotate_icon_vsn6y_393"
  , jC = "_spin_vsn6y_1"
  , NC = "_fade_in_vsn6y_397"
  , Vn = {
    "a11y-hidden": "_a11y-hidden_vsn6y_192",
    container: _C,
    cloud_wrap: vC,
    right_cloud1: yC,
    slideRight: wC,
    left_cloud1: xC,
    slideLeft: bC,
    router_icon_illusion_info: SC,
    fadeIn: CC,
    router_icon_illusion_benefit: kC,
    router_icon_live_benefit: EC,
    router_icon_newcontent: TC,
    rotate_icon: PC,
    spin: jC,
    fade_in: NC
}
  , wy = g.createContext({
    transformPagePoint: e => e,
    isStatic: !1,
    reducedMotion: "never"
})
  , yc = g.createContext({})
  , wc = g.createContext(null)
  , xc = typeof document < "u"
  , jp = xc ? g.useLayoutEffect : g.useEffect
  , xy = g.createContext({
    strict: !1
})
  , Np = e => e.replace(/([a-z])([A-Z])/g, "$1-$2").toLowerCase()
  , RC = "framerAppearId"
  , by = "data-" + Np(RC);
function IC(e, t, n, r) {
    const {visualElement: i} = g.useContext(yc)
      , s = g.useContext(xy)
      , o = g.useContext(wc)
      , l = g.useContext(wy).reducedMotion
      , a = g.useRef();
    r = r || s.renderer,
    !a.current && r && (a.current = r(e, {
        visualState: t,
        parent: i,
        props: n,
        presenceContext: o,
        blockInitialAnimation: o ? o.initial === !1 : !1,
        reducedMotionConfig: l
    }));
    const c = a.current;
    g.useInsertionEffect( () => {
        c && c.update(n, o)
    }
    );
    const d = g.useRef(!!(n[by] && !window.HandoffComplete));
    return jp( () => {
        c && (c.render(),
        d.current && c.animationState && c.animationState.animateChanges())
    }
    ),
    g.useEffect( () => {
        c && (c.updateFeatures(),
        !d.current && c.animationState && c.animationState.animateChanges(),
        d.current && (d.current = !1,
        window.HandoffComplete = !0))
    }
    ),
    c
}
function Ki(e) {
    return e && typeof e == "object" && Object.prototype.hasOwnProperty.call(e, "current")
}
function LC(e, t, n) {
    return g.useCallback(r => {
        r && e.mount && e.mount(r),
        t && (r ? t.mount(r) : t.unmount()),
        n && (typeof n == "function" ? n(r) : Ki(n) && (n.current = r))
    }
    , [t])
}
function $o(e) {
    return typeof e == "string" || Array.isArray(e)
}
function bc(e) {
    return e !== null && typeof e == "object" && typeof e.start == "function"
}
const Rp = ["animate", "whileInView", "whileFocus", "whileHover", "whileTap", "whileDrag", "exit"]
  , Ip = ["initial", ...Rp];
function Sc(e) {
    return bc(e.animate) || Ip.some(t => $o(e[t]))
}
function Sy(e) {
    return !!(Sc(e) || e.variants)
}
function MC(e, t) {
    if (Sc(e)) {
        const {initial: n, animate: r} = e;
        return {
            initial: n === !1 || $o(n) ? n : void 0,
            animate: $o(r) ? r : void 0
        }
    }
    return e.inherit !== !1 ? t : {}
}
function OC(e) {
    const {initial: t, animate: n} = MC(e, g.useContext(yc));
    return g.useMemo( () => ({
        initial: t,
        animate: n
    }), [Jh(t), Jh(n)])
}
function Jh(e) {
    return Array.isArray(e) ? e.join(" ") : e
}
const Zh = {
    animation: ["animate", "variants", "whileHover", "whileTap", "exit", "whileInView", "whileFocus", "whileDrag"],
    exit: ["exit"],
    drag: ["drag", "dragControls"],
    focus: ["whileFocus"],
    hover: ["whileHover", "onHoverStart", "onHoverEnd"],
    tap: ["whileTap", "onTap", "onTapStart", "onTapCancel"],
    pan: ["onPan", "onPanStart", "onPanSessionStart", "onPanEnd"],
    inView: ["whileInView", "onViewportEnter", "onViewportLeave"],
    layout: ["layout", "layoutId"]
}
  , Fo = {};
for (const e in Zh)
    Fo[e] = {
        isEnabled: t => Zh[e].some(n => !!t[n])
    };
function AC(e) {
    for (const t in e)
        Fo[t] = {
            ...Fo[t],
            ...e[t]
        }
}
const Lp = g.createContext({})
  , Cy = g.createContext({})
  , DC = Symbol.for("motionComponentSymbol");
function $C({preloadedFeatures: e, createVisualElement: t, useRender: n, useVisualState: r, Component: i}) {
    e && AC(e);
    function s(l, a) {
        let c;
        const d = {
            ...g.useContext(wy),
            ...l,
            layoutId: FC(l)
        }
          , {isStatic: f} = d
          , p = OC(l)
          , h = r(l, f);
        if (!f && xc) {
            p.visualElement = IC(i, h, d, t);
            const m = g.useContext(Cy)
              , _ = g.useContext(xy).strict;
            p.visualElement && (c = p.visualElement.loadFeatures(d, _, e, m))
        }
        return g.createElement(yc.Provider, {
            value: p
        }, c && p.visualElement ? g.createElement(c, {
            visualElement: p.visualElement,
            ...d
        }) : null, n(i, l, LC(h, p.visualElement, a), h, f, p.visualElement))
    }
    const o = g.forwardRef(s);
    return o[DC] = i,
    o
}
function FC({layoutId: e}) {
    const t = g.useContext(Lp).id;
    return t && e !== void 0 ? t + "-" + e : e
}
function VC(e) {
    function t(r, i={}) {
        return $C(e(r, i))
    }
    if (typeof Proxy > "u")
        return t;
    const n = new Map;
    return new Proxy(t,{
        get: (r, i) => (n.has(i) || n.set(i, t(i)),
        n.get(i))
    })
}
const zC = ["animate", "circle", "defs", "desc", "ellipse", "g", "image", "line", "filter", "marker", "mask", "metadata", "path", "pattern", "polygon", "polyline", "rect", "stop", "switch", "symbol", "svg", "text", "tspan", "use", "view"];
function Mp(e) {
    return typeof e != "string" || e.includes("-") ? !1 : !!(zC.indexOf(e) > -1 || /[A-Z]/.test(e))
}
const $l = {};
function BC(e) {
    Object.assign($l, e)
}
const ta = ["transformPerspective", "x", "y", "z", "translateX", "translateY", "translateZ", "scale", "scaleX", "scaleY", "rotate", "rotateX", "rotateY", "rotateZ", "skew", "skewX", "skewY"]
  , Ci = new Set(ta);
function ky(e, {layout: t, layoutId: n}) {
    return Ci.has(e) || e.startsWith("origin") || (t || n !== void 0) && (!!$l[e] || e === "opacity")
}
const Lt = e => !!(e && e.getVelocity)
  , UC = {
    x: "translateX",
    y: "translateY",
    z: "translateZ",
    transformPerspective: "perspective"
}
  , HC = ta.length;
function GC(e, {enableHardwareAcceleration: t=!0, allowTransformNone: n=!0}, r, i) {
    let s = "";
    for (let o = 0; o < HC; o++) {
        const l = ta[o];
        if (e[l] !== void 0) {
            const a = UC[l] || l;
            s += `${a}(${e[l]}) `
        }
    }
    return t && !e.z && (s += "translateZ(0)"),
    s = s.trim(),
    i ? s = i(e, r ? "" : s) : n && r && (s = "none"),
    s
}
const Ey = e => t => typeof t == "string" && t.startsWith(e)
  , Ty = Ey("--")
  , Wd = Ey("var(--")
  , WC = /var\s*\(\s*--[\w-]+(\s*,\s*(?:(?:[^)(]|\((?:[^)(]+|\([^)(]*\))*\))*)+)?\s*\)/g
  , qC = (e, t) => t && typeof e == "number" ? t.transform(e) : e
  , Dr = (e, t, n) => Math.min(Math.max(n, e), t)
  , ki = {
    test: e => typeof e == "number",
    parse: parseFloat,
    transform: e => e
}
  , co = {
    ...ki,
    transform: e => Dr(0, 1, e)
}
  , La = {
    ...ki,
    default: 1
}
  , uo = e => Math.round(e * 1e5) / 1e5
  , Cc = /(-)?([\d]*\.?[\d])+/g
  , Py = /(#[0-9a-f]{3,8}|(rgb|hsl)a?\((-?[\d\.]+%?[,\s]+){2}(-?[\d\.]+%?)\s*[\,\/]?\s*[\d\.]*%?\))/gi
  , YC = /^(#[0-9a-f]{3,8}|(rgb|hsl)a?\((-?[\d\.]+%?[,\s]+){2}(-?[\d\.]+%?)\s*[\,\/]?\s*[\d\.]*%?\))$/i;
function na(e) {
    return typeof e == "string"
}
const ra = e => ({
    test: t => na(t) && t.endsWith(e) && t.split(" ").length === 1,
    parse: parseFloat,
    transform: t => `${t}${e}`
})
  , mr = ra("deg")
  , On = ra("%")
  , te = ra("px")
  , KC = ra("vh")
  , XC = ra("vw")
  , eg = {
    ...On,
    parse: e => On.parse(e) / 100,
    transform: e => On.transform(e * 100)
}
  , tg = {
    ...ki,
    transform: Math.round
}
  , jy = {
    borderWidth: te,
    borderTopWidth: te,
    borderRightWidth: te,
    borderBottomWidth: te,
    borderLeftWidth: te,
    borderRadius: te,
    radius: te,
    borderTopLeftRadius: te,
    borderTopRightRadius: te,
    borderBottomRightRadius: te,
    borderBottomLeftRadius: te,
    width: te,
    maxWidth: te,
    height: te,
    maxHeight: te,
    size: te,
    top: te,
    right: te,
    bottom: te,
    left: te,
    padding: te,
    paddingTop: te,
    paddingRight: te,
    paddingBottom: te,
    paddingLeft: te,
    margin: te,
    marginTop: te,
    marginRight: te,
    marginBottom: te,
    marginLeft: te,
    rotate: mr,
    rotateX: mr,
    rotateY: mr,
    rotateZ: mr,
    scale: La,
    scaleX: La,
    scaleY: La,
    scaleZ: La,
    skew: mr,
    skewX: mr,
    skewY: mr,
    distance: te,
    translateX: te,
    translateY: te,
    translateZ: te,
    x: te,
    y: te,
    z: te,
    perspective: te,
    transformPerspective: te,
    opacity: co,
    originX: eg,
    originY: eg,
    originZ: te,
    zIndex: tg,
    fillOpacity: co,
    strokeOpacity: co,
    numOctaves: tg
};
function Op(e, t, n, r) {
    const {style: i, vars: s, transform: o, transformOrigin: l} = e;
    let a = !1
      , c = !1
      , d = !0;
    for (const f in t) {
        const p = t[f];
        if (Ty(f)) {
            s[f] = p;
            continue
        }
        const h = jy[f]
          , m = qC(p, h);
        if (Ci.has(f)) {
            if (a = !0,
            o[f] = m,
            !d)
                continue;
            p !== (h.default || 0) && (d = !1)
        } else
            f.startsWith("origin") ? (c = !0,
            l[f] = m) : i[f] = m
    }
    if (t.transform || (a || r ? i.transform = GC(e.transform, n, d, r) : i.transform && (i.transform = "none")),
    c) {
        const {originX: f="50%", originY: p="50%", originZ: h=0} = l;
        i.transformOrigin = `${f} ${p} ${h}`
    }
}
const Ap = () => ({
    style: {},
    transform: {},
    transformOrigin: {},
    vars: {}
});
function Ny(e, t, n) {
    for (const r in t)
        !Lt(t[r]) && !ky(r, n) && (e[r] = t[r])
}
function QC({transformTemplate: e}, t, n) {
    return g.useMemo( () => {
        const r = Ap();
        return Op(r, t, {
            enableHardwareAcceleration: !n
        }, e),
        Object.assign({}, r.vars, r.style)
    }
    , [t])
}
function JC(e, t, n) {
    const r = e.style || {}
      , i = {};
    return Ny(i, r, e),
    Object.assign(i, QC(e, t, n)),
    e.transformValues ? e.transformValues(i) : i
}
function ZC(e, t, n) {
    const r = {}
      , i = JC(e, t, n);
    return e.drag && e.dragListener !== !1 && (r.draggable = !1,
    i.userSelect = i.WebkitUserSelect = i.WebkitTouchCallout = "none",
    i.touchAction = e.drag === !0 ? "none" : `pan-${e.drag === "x" ? "y" : "x"}`),
    e.tabIndex === void 0 && (e.onTap || e.onTapStart || e.whileTap) && (r.tabIndex = 0),
    r.style = i,
    r
}
const ek = new Set(["animate", "exit", "variants", "initial", "style", "values", "variants", "transition", "transformTemplate", "transformValues", "custom", "inherit", "onBeforeLayoutMeasure", "onAnimationStart", "onAnimationComplete", "onUpdate", "onDragStart", "onDrag", "onDragEnd", "onMeasureDragConstraints", "onDirectionLock", "onDragTransitionEnd", "_dragX", "_dragY", "onHoverStart", "onHoverEnd", "onViewportEnter", "onViewportLeave", "globalTapTarget", "ignoreStrict", "viewport"]);
function Fl(e) {
    return e.startsWith("while") || e.startsWith("drag") && e !== "draggable" || e.startsWith("layout") || e.startsWith("onTap") || e.startsWith("onPan") || e.startsWith("onLayout") || ek.has(e)
}
let Ry = e => !Fl(e);
function tk(e) {
    e && (Ry = t => t.startsWith("on") ? !Fl(t) : e(t))
}
try {
    tk(require("@emotion/is-prop-valid").default)
} catch {}
function nk(e, t, n) {
    const r = {};
    for (const i in e)
        i === "values" && typeof e.values == "object" || (Ry(i) || n === !0 && Fl(i) || !t && !Fl(i) || e.draggable && i.startsWith("onDrag")) && (r[i] = e[i]);
    return r
}
function ng(e, t, n) {
    return typeof e == "string" ? e : te.transform(t + n * e)
}
function rk(e, t, n) {
    const r = ng(t, e.x, e.width)
      , i = ng(n, e.y, e.height);
    return `${r} ${i}`
}
const ik = {
    offset: "stroke-dashoffset",
    array: "stroke-dasharray"
}
  , sk = {
    offset: "strokeDashoffset",
    array: "strokeDasharray"
};
function ok(e, t, n=1, r=0, i=!0) {
    e.pathLength = 1;
    const s = i ? ik : sk;
    e[s.offset] = te.transform(-r);
    const o = te.transform(t)
      , l = te.transform(n);
    e[s.array] = `${o} ${l}`
}
function Dp(e, {attrX: t, attrY: n, attrScale: r, originX: i, originY: s, pathLength: o, pathSpacing: l=1, pathOffset: a=0, ...c}, d, f, p) {
    if (Op(e, c, d, p),
    f) {
        e.style.viewBox && (e.attrs.viewBox = e.style.viewBox);
        return
    }
    e.attrs = e.style,
    e.style = {};
    const {attrs: h, style: m, dimensions: _} = e;
    h.transform && (_ && (m.transform = h.transform),
    delete h.transform),
    _ && (i !== void 0 || s !== void 0 || m.transform) && (m.transformOrigin = rk(_, i !== void 0 ? i : .5, s !== void 0 ? s : .5)),
    t !== void 0 && (h.x = t),
    n !== void 0 && (h.y = n),
    r !== void 0 && (h.scale = r),
    o !== void 0 && ok(h, o, l, a, !1)
}
const Iy = () => ({
    ...Ap(),
    attrs: {}
})
  , $p = e => typeof e == "string" && e.toLowerCase() === "svg";
function ak(e, t, n, r) {
    const i = g.useMemo( () => {
        const s = Iy();
        return Dp(s, t, {
            enableHardwareAcceleration: !1
        }, $p(r), e.transformTemplate),
        {
            ...s.attrs,
            style: {
                ...s.style
            }
        }
    }
    , [t]);
    if (e.style) {
        const s = {};
        Ny(s, e.style, e),
        i.style = {
            ...s,
            ...i.style
        }
    }
    return i
}
function lk(e=!1) {
    return (n, r, i, {latestValues: s}, o) => {
        const a = (Mp(n) ? ak : ZC)(r, s, o, n)
          , d = {
            ...nk(r, typeof n == "string", e),
            ...a,
            ref: i
        }
          , {children: f} = r
          , p = g.useMemo( () => Lt(f) ? f.get() : f, [f]);
        return g.createElement(n, {
            ...d,
            children: p
        })
    }
}
function Ly(e, {style: t, vars: n}, r, i) {
    Object.assign(e.style, t, i && i.getProjectionStyles(r));
    for (const s in n)
        e.style.setProperty(s, n[s])
}
const My = new Set(["baseFrequency", "diffuseConstant", "kernelMatrix", "kernelUnitLength", "keySplines", "keyTimes", "limitingConeAngle", "markerHeight", "markerWidth", "numOctaves", "targetX", "targetY", "surfaceScale", "specularConstant", "specularExponent", "stdDeviation", "tableValues", "viewBox", "gradientTransform", "pathLength", "startOffset", "textLength", "lengthAdjust"]);
function Oy(e, t, n, r) {
    Ly(e, t, void 0, r);
    for (const i in t.attrs)
        e.setAttribute(My.has(i) ? i : Np(i), t.attrs[i])
}
function Fp(e, t) {
    const {style: n} = e
      , r = {};
    for (const i in n)
        (Lt(n[i]) || t.style && Lt(t.style[i]) || ky(i, e)) && (r[i] = n[i]);
    return r
}
function Ay(e, t) {
    const n = Fp(e, t);
    for (const r in e)
        if (Lt(e[r]) || Lt(t[r])) {
            const i = ta.indexOf(r) !== -1 ? "attr" + r.charAt(0).toUpperCase() + r.substring(1) : r;
            n[i] = e[r]
        }
    return n
}
function Vp(e, t, n, r={}, i={}) {
    return typeof t == "function" && (t = t(n !== void 0 ? n : e.custom, r, i)),
    typeof t == "string" && (t = e.variants && e.variants[t]),
    typeof t == "function" && (t = t(n !== void 0 ? n : e.custom, r, i)),
    t
}
function Dy(e) {
    const t = g.useRef(null);
    return t.current === null && (t.current = e()),
    t.current
}
const Vl = e => Array.isArray(e)
  , ck = e => !!(e && typeof e == "object" && e.mix && e.toValue)
  , uk = e => Vl(e) ? e[e.length - 1] || 0 : e;
function ol(e) {
    const t = Lt(e) ? e.get() : e;
    return ck(t) ? t.toValue() : t
}
function dk({scrapeMotionValuesFromProps: e, createRenderState: t, onMount: n}, r, i, s) {
    const o = {
        latestValues: fk(r, i, s, e),
        renderState: t()
    };
    return n && (o.mount = l => n(r, l, o)),
    o
}
const $y = e => (t, n) => {
    const r = g.useContext(yc)
      , i = g.useContext(wc)
      , s = () => dk(e, t, r, i);
    return n ? s() : Dy(s)
}
;
function fk(e, t, n, r) {
    const i = {}
      , s = r(e, {});
    for (const p in s)
        i[p] = ol(s[p]);
    let {initial: o, animate: l} = e;
    const a = Sc(e)
      , c = Sy(e);
    t && c && !a && e.inherit !== !1 && (o === void 0 && (o = t.initial),
    l === void 0 && (l = t.animate));
    let d = n ? n.initial === !1 : !1;
    d = d || o === !1;
    const f = d ? l : o;
    return f && typeof f != "boolean" && !bc(f) && (Array.isArray(f) ? f : [f]).forEach(h => {
        const m = Vp(e, h);
        if (!m)
            return;
        const {transitionEnd: _, transition: x, ...v} = m;
        for (const w in v) {
            let y = v[w];
            if (Array.isArray(y)) {
                const b = d ? y.length - 1 : 0;
                y = y[b]
            }
            y !== null && (i[w] = y)
        }
        for (const w in _)
            i[w] = _[w]
    }
    ),
    i
}
const We = e => e;
class rg {
    constructor() {
        this.order = [],
        this.scheduled = new Set
    }
    add(t) {
        if (!this.scheduled.has(t))
            return this.scheduled.add(t),
            this.order.push(t),
            !0
    }
    remove(t) {
        const n = this.order.indexOf(t);
        n !== -1 && (this.order.splice(n, 1),
        this.scheduled.delete(t))
    }
    clear() {
        this.order.length = 0,
        this.scheduled.clear()
    }
}
function pk(e) {
    let t = new rg
      , n = new rg
      , r = 0
      , i = !1
      , s = !1;
    const o = new WeakSet
      , l = {
        schedule: (a, c=!1, d=!1) => {
            const f = d && i
              , p = f ? t : n;
            return c && o.add(a),
            p.add(a) && f && i && (r = t.order.length),
            a
        }
        ,
        cancel: a => {
            n.remove(a),
            o.delete(a)
        }
        ,
        process: a => {
            if (i) {
                s = !0;
                return
            }
            if (i = !0,
            [t,n] = [n, t],
            n.clear(),
            r = t.order.length,
            r)
                for (let c = 0; c < r; c++) {
                    const d = t.order[c];
                    d(a),
                    o.has(d) && (l.schedule(d),
                    e())
                }
            i = !1,
            s && (s = !1,
            l.process(a))
        }
    };
    return l
}
const Ma = ["prepare", "read", "update", "preRender", "render", "postRender"]
  , mk = 40;
function hk(e, t) {
    let n = !1
      , r = !0;
    const i = {
        delta: 0,
        timestamp: 0,
        isProcessing: !1
    }
      , s = Ma.reduce( (f, p) => (f[p] = pk( () => n = !0),
    f), {})
      , o = f => s[f].process(i)
      , l = () => {
        const f = performance.now();
        n = !1,
        i.delta = r ? 1e3 / 60 : Math.max(Math.min(f - i.timestamp, mk), 1),
        i.timestamp = f,
        i.isProcessing = !0,
        Ma.forEach(o),
        i.isProcessing = !1,
        n && t && (r = !1,
        e(l))
    }
      , a = () => {
        n = !0,
        r = !0,
        i.isProcessing || e(l)
    }
    ;
    return {
        schedule: Ma.reduce( (f, p) => {
            const h = s[p];
            return f[p] = (m, _=!1, x=!1) => (n || a(),
            h.schedule(m, _, x)),
            f
        }
        , {}),
        cancel: f => Ma.forEach(p => s[p].cancel(f)),
        state: i,
        steps: s
    }
}
const {schedule: Ne, cancel: tr, state: gt, steps: vu} = hk(typeof requestAnimationFrame < "u" ? requestAnimationFrame : We, !0)
  , gk = {
    useVisualState: $y({
        scrapeMotionValuesFromProps: Ay,
        createRenderState: Iy,
        onMount: (e, t, {renderState: n, latestValues: r}) => {
            Ne.read( () => {
                try {
                    n.dimensions = typeof t.getBBox == "function" ? t.getBBox() : t.getBoundingClientRect()
                } catch {
                    n.dimensions = {
                        x: 0,
                        y: 0,
                        width: 0,
                        height: 0
                    }
                }
            }
            ),
            Ne.render( () => {
                Dp(n, r, {
                    enableHardwareAcceleration: !1
                }, $p(t.tagName), e.transformTemplate),
                Oy(t, n)
            }
            )
        }
    })
}
  , _k = {
    useVisualState: $y({
        scrapeMotionValuesFromProps: Fp,
        createRenderState: Ap
    })
};
function vk(e, {forwardMotionProps: t=!1}, n, r) {
    return {
        ...Mp(e) ? gk : _k,
        preloadedFeatures: n,
        useRender: lk(t),
        createVisualElement: r,
        Component: e
    }
}
function Wn(e, t, n, r={
    passive: !0
}) {
    return e.addEventListener(t, n, r),
    () => e.removeEventListener(t, n)
}
const Fy = e => e.pointerType === "mouse" ? typeof e.button != "number" || e.button <= 0 : e.isPrimary !== !1;
function kc(e, t="page") {
    return {
        point: {
            x: e[t + "X"],
            y: e[t + "Y"]
        }
    }
}
const yk = e => t => Fy(t) && e(t, kc(t));
function Kn(e, t, n, r) {
    return Wn(e, t, yk(n), r)
}
const wk = (e, t) => n => t(e(n))
  , Ir = (...e) => e.reduce(wk);
function Vy(e) {
    let t = null;
    return () => {
        const n = () => {
            t = null
        }
        ;
        return t === null ? (t = e,
        n) : !1
    }
}
const ig = Vy("dragHorizontal")
  , sg = Vy("dragVertical");
function zy(e) {
    let t = !1;
    if (e === "y")
        t = sg();
    else if (e === "x")
        t = ig();
    else {
        const n = ig()
          , r = sg();
        n && r ? t = () => {
            n(),
            r()
        }
        : (n && n(),
        r && r())
    }
    return t
}
function By() {
    const e = zy(!0);
    return e ? (e(),
    !1) : !0
}
class Hr {
    constructor(t) {
        this.isMounted = !1,
        this.node = t
    }
    update() {}
}
function og(e, t) {
    const n = "pointer" + (t ? "enter" : "leave")
      , r = "onHover" + (t ? "Start" : "End")
      , i = (s, o) => {
        if (s.pointerType === "touch" || By())
            return;
        const l = e.getProps();
        e.animationState && l.whileHover && e.animationState.setActive("whileHover", t),
        l[r] && Ne.update( () => l[r](s, o))
    }
    ;
    return Kn(e.current, n, i, {
        passive: !e.getProps()[r]
    })
}
class xk extends Hr {
    mount() {
        this.unmount = Ir(og(this.node, !0), og(this.node, !1))
    }
    unmount() {}
}
class bk extends Hr {
    constructor() {
        super(...arguments),
        this.isActive = !1
    }
    onFocus() {
        let t = !1;
        try {
            t = this.node.current.matches(":focus-visible")
        } catch {
            t = !0
        }
        !t || !this.node.animationState || (this.node.animationState.setActive("whileFocus", !0),
        this.isActive = !0)
    }
    onBlur() {
        !this.isActive || !this.node.animationState || (this.node.animationState.setActive("whileFocus", !1),
        this.isActive = !1)
    }
    mount() {
        this.unmount = Ir(Wn(this.node.current, "focus", () => this.onFocus()), Wn(this.node.current, "blur", () => this.onBlur()))
    }
    unmount() {}
}
const Uy = (e, t) => t ? e === t ? !0 : Uy(e, t.parentElement) : !1;
function yu(e, t) {
    if (!t)
        return;
    const n = new PointerEvent("pointer" + e);
    t(n, kc(n))
}
class Sk extends Hr {
    constructor() {
        super(...arguments),
        this.removeStartListeners = We,
        this.removeEndListeners = We,
        this.removeAccessibleListeners = We,
        this.startPointerPress = (t, n) => {
            if (this.isPressing)
                return;
            this.removeEndListeners();
            const r = this.node.getProps()
              , s = Kn(window, "pointerup", (l, a) => {
                if (!this.checkPressEnd())
                    return;
                const {onTap: c, onTapCancel: d, globalTapTarget: f} = this.node.getProps();
                Ne.update( () => {
                    !f && !Uy(this.node.current, l.target) ? d && d(l, a) : c && c(l, a)
                }
                )
            }
            , {
                passive: !(r.onTap || r.onPointerUp)
            })
              , o = Kn(window, "pointercancel", (l, a) => this.cancelPress(l, a), {
                passive: !(r.onTapCancel || r.onPointerCancel)
            });
            this.removeEndListeners = Ir(s, o),
            this.startPress(t, n)
        }
        ,
        this.startAccessiblePress = () => {
            const t = s => {
                if (s.key !== "Enter" || this.isPressing)
                    return;
                const o = l => {
                    l.key !== "Enter" || !this.checkPressEnd() || yu("up", (a, c) => {
                        const {onTap: d} = this.node.getProps();
                        d && Ne.update( () => d(a, c))
                    }
                    )
                }
                ;
                this.removeEndListeners(),
                this.removeEndListeners = Wn(this.node.current, "keyup", o),
                yu("down", (l, a) => {
                    this.startPress(l, a)
                }
                )
            }
              , n = Wn(this.node.current, "keydown", t)
              , r = () => {
                this.isPressing && yu("cancel", (s, o) => this.cancelPress(s, o))
            }
              , i = Wn(this.node.current, "blur", r);
            this.removeAccessibleListeners = Ir(n, i)
        }
    }
    startPress(t, n) {
        this.isPressing = !0;
        const {onTapStart: r, whileTap: i} = this.node.getProps();
        i && this.node.animationState && this.node.animationState.setActive("whileTap", !0),
        r && Ne.update( () => r(t, n))
    }
    checkPressEnd() {
        return this.removeEndListeners(),
        this.isPressing = !1,
        this.node.getProps().whileTap && this.node.animationState && this.node.animationState.setActive("whileTap", !1),
        !By()
    }
    cancelPress(t, n) {
        if (!this.checkPressEnd())
            return;
        const {onTapCancel: r} = this.node.getProps();
        r && Ne.update( () => r(t, n))
    }
    mount() {
        const t = this.node.getProps()
          , n = Kn(t.globalTapTarget ? window : this.node.current, "pointerdown", this.startPointerPress, {
            passive: !(t.onTapStart || t.onPointerStart)
        })
          , r = Wn(this.node.current, "focus", this.startAccessiblePress);
        this.removeStartListeners = Ir(n, r)
    }
    unmount() {
        this.removeStartListeners(),
        this.removeEndListeners(),
        this.removeAccessibleListeners()
    }
}
const qd = new WeakMap
  , wu = new WeakMap
  , Ck = e => {
    const t = qd.get(e.target);
    t && t(e)
}
  , kk = e => {
    e.forEach(Ck)
}
;
function Ek({root: e, ...t}) {
    const n = e || document;
    wu.has(n) || wu.set(n, {});
    const r = wu.get(n)
      , i = JSON.stringify(t);
    return r[i] || (r[i] = new IntersectionObserver(kk,{
        root: e,
        ...t
    })),
    r[i]
}
function Tk(e, t, n) {
    const r = Ek(t);
    return qd.set(e, n),
    r.observe(e),
    () => {
        qd.delete(e),
        r.unobserve(e)
    }
}
const Pk = {
    some: 0,
    all: 1
};
class jk extends Hr {
    constructor() {
        super(...arguments),
        this.hasEnteredView = !1,
        this.isInView = !1
    }
    startObserver() {
        this.unmount();
        const {viewport: t={}} = this.node.getProps()
          , {root: n, margin: r, amount: i="some", once: s} = t
          , o = {
            root: n ? n.current : void 0,
            rootMargin: r,
            threshold: typeof i == "number" ? i : Pk[i]
        }
          , l = a => {
            const {isIntersecting: c} = a;
            if (this.isInView === c || (this.isInView = c,
            s && !c && this.hasEnteredView))
                return;
            c && (this.hasEnteredView = !0),
            this.node.animationState && this.node.animationState.setActive("whileInView", c);
            const {onViewportEnter: d, onViewportLeave: f} = this.node.getProps()
              , p = c ? d : f;
            p && p(a)
        }
        ;
        return Tk(this.node.current, o, l)
    }
    mount() {
        this.startObserver()
    }
    update() {
        if (typeof IntersectionObserver > "u")
            return;
        const {props: t, prevProps: n} = this.node;
        ["amount", "margin", "root"].some(Nk(t, n)) && this.startObserver()
    }
    unmount() {}
}
function Nk({viewport: e={}}, {viewport: t={}}={}) {
    return n => e[n] !== t[n]
}
const Rk = {
    inView: {
        Feature: jk
    },
    tap: {
        Feature: Sk
    },
    focus: {
        Feature: bk
    },
    hover: {
        Feature: xk
    }
};
function Hy(e, t) {
    if (!Array.isArray(t))
        return !1;
    const n = t.length;
    if (n !== e.length)
        return !1;
    for (let r = 0; r < n; r++)
        if (t[r] !== e[r])
            return !1;
    return !0
}
function Ik(e) {
    const t = {};
    return e.values.forEach( (n, r) => t[r] = n.get()),
    t
}
function Lk(e) {
    const t = {};
    return e.values.forEach( (n, r) => t[r] = n.getVelocity()),
    t
}
function Ec(e, t, n) {
    const r = e.getProps();
    return Vp(r, t, n !== void 0 ? n : r.custom, Ik(e), Lk(e))
}
let Mk = We
  , zp = We;
const Lr = e => e * 1e3
  , Xn = e => e / 1e3
  , Ok = {
    current: !1
}
  , Gy = e => Array.isArray(e) && typeof e[0] == "number";
function Wy(e) {
    return !!(!e || typeof e == "string" && qy[e] || Gy(e) || Array.isArray(e) && e.every(Wy))
}
const Js = ([e,t,n,r]) => `cubic-bezier(${e}, ${t}, ${n}, ${r})`
  , qy = {
    linear: "linear",
    ease: "ease",
    easeIn: "ease-in",
    easeOut: "ease-out",
    easeInOut: "ease-in-out",
    circIn: Js([0, .65, .55, 1]),
    circOut: Js([.55, 0, 1, .45]),
    backIn: Js([.31, .01, .66, -.59]),
    backOut: Js([.33, 1.53, .69, .99])
};
function Yy(e) {
    if (e)
        return Gy(e) ? Js(e) : Array.isArray(e) ? e.map(Yy) : qy[e]
}
function Ak(e, t, n, {delay: r=0, duration: i, repeat: s=0, repeatType: o="loop", ease: l, times: a}={}) {
    const c = {
        [t]: n
    };
    a && (c.offset = a);
    const d = Yy(l);
    return Array.isArray(d) && (c.easing = d),
    e.animate(c, {
        delay: r,
        duration: i,
        easing: Array.isArray(d) ? "linear" : d,
        fill: "both",
        iterations: s + 1,
        direction: o === "reverse" ? "alternate" : "normal"
    })
}
function Dk(e, {repeat: t, repeatType: n="loop"}) {
    const r = t && n !== "loop" && t % 2 === 1 ? 0 : e.length - 1;
    return e[r]
}
const Ky = (e, t, n) => (((1 - 3 * n + 3 * t) * e + (3 * n - 6 * t)) * e + 3 * t) * e
  , $k = 1e-7
  , Fk = 12;
function Vk(e, t, n, r, i) {
    let s, o, l = 0;
    do
        o = t + (n - t) / 2,
        s = Ky(o, r, i) - e,
        s > 0 ? n = o : t = o;
    while (Math.abs(s) > $k && ++l < Fk);
    return o
}
function ia(e, t, n, r) {
    if (e === t && n === r)
        return We;
    const i = s => Vk(s, 0, 1, e, n);
    return s => s === 0 || s === 1 ? s : Ky(i(s), t, r)
}
const zk = ia(.42, 0, 1, 1)
  , Bk = ia(0, 0, .58, 1)
  , Xy = ia(.42, 0, .58, 1)
  , Uk = e => Array.isArray(e) && typeof e[0] != "number"
  , Qy = e => t => t <= .5 ? e(2 * t) / 2 : (2 - e(2 * (1 - t))) / 2
  , Jy = e => t => 1 - e(1 - t)
  , Bp = e => 1 - Math.sin(Math.acos(e))
  , Zy = Jy(Bp)
  , Hk = Qy(Bp)
  , ew = ia(.33, 1.53, .69, .99)
  , Up = Jy(ew)
  , Gk = Qy(Up)
  , Wk = e => (e *= 2) < 1 ? .5 * Up(e) : .5 * (2 - Math.pow(2, -10 * (e - 1)))
  , qk = {
    linear: We,
    easeIn: zk,
    easeInOut: Xy,
    easeOut: Bk,
    circIn: Bp,
    circInOut: Hk,
    circOut: Zy,
    backIn: Up,
    backInOut: Gk,
    backOut: ew,
    anticipate: Wk
}
  , ag = e => {
    if (Array.isArray(e)) {
        zp(e.length === 4);
        const [t,n,r,i] = e;
        return ia(t, n, r, i)
    } else if (typeof e == "string")
        return qk[e];
    return e
}
  , Hp = (e, t) => n => !!(na(n) && YC.test(n) && n.startsWith(e) || t && Object.prototype.hasOwnProperty.call(n, t))
  , tw = (e, t, n) => r => {
    if (!na(r))
        return r;
    const [i,s,o,l] = r.match(Cc);
    return {
        [e]: parseFloat(i),
        [t]: parseFloat(s),
        [n]: parseFloat(o),
        alpha: l !== void 0 ? parseFloat(l) : 1
    }
}
  , Yk = e => Dr(0, 255, e)
  , xu = {
    ...ki,
    transform: e => Math.round(Yk(e))
}
  , oi = {
    test: Hp("rgb", "red"),
    parse: tw("red", "green", "blue"),
    transform: ({red: e, green: t, blue: n, alpha: r=1}) => "rgba(" + xu.transform(e) + ", " + xu.transform(t) + ", " + xu.transform(n) + ", " + uo(co.transform(r)) + ")"
};
function Kk(e) {
    let t = ""
      , n = ""
      , r = ""
      , i = "";
    return e.length > 5 ? (t = e.substring(1, 3),
    n = e.substring(3, 5),
    r = e.substring(5, 7),
    i = e.substring(7, 9)) : (t = e.substring(1, 2),
    n = e.substring(2, 3),
    r = e.substring(3, 4),
    i = e.substring(4, 5),
    t += t,
    n += n,
    r += r,
    i += i),
    {
        red: parseInt(t, 16),
        green: parseInt(n, 16),
        blue: parseInt(r, 16),
        alpha: i ? parseInt(i, 16) / 255 : 1
    }
}
const Yd = {
    test: Hp("#"),
    parse: Kk,
    transform: oi.transform
}
  , Xi = {
    test: Hp("hsl", "hue"),
    parse: tw("hue", "saturation", "lightness"),
    transform: ({hue: e, saturation: t, lightness: n, alpha: r=1}) => "hsla(" + Math.round(e) + ", " + On.transform(uo(t)) + ", " + On.transform(uo(n)) + ", " + uo(co.transform(r)) + ")"
}
  , bt = {
    test: e => oi.test(e) || Yd.test(e) || Xi.test(e),
    parse: e => oi.test(e) ? oi.parse(e) : Xi.test(e) ? Xi.parse(e) : Yd.parse(e),
    transform: e => na(e) ? e : e.hasOwnProperty("red") ? oi.transform(e) : Xi.transform(e)
}
  , Fe = (e, t, n) => -n * e + n * t + e;
function bu(e, t, n) {
    return n < 0 && (n += 1),
    n > 1 && (n -= 1),
    n < 1 / 6 ? e + (t - e) * 6 * n : n < 1 / 2 ? t : n < 2 / 3 ? e + (t - e) * (2 / 3 - n) * 6 : e
}
function Xk({hue: e, saturation: t, lightness: n, alpha: r}) {
    e /= 360,
    t /= 100,
    n /= 100;
    let i = 0
      , s = 0
      , o = 0;
    if (!t)
        i = s = o = n;
    else {
        const l = n < .5 ? n * (1 + t) : n + t - n * t
          , a = 2 * n - l;
        i = bu(a, l, e + 1 / 3),
        s = bu(a, l, e),
        o = bu(a, l, e - 1 / 3)
    }
    return {
        red: Math.round(i * 255),
        green: Math.round(s * 255),
        blue: Math.round(o * 255),
        alpha: r
    }
}
const Su = (e, t, n) => {
    const r = e * e;
    return Math.sqrt(Math.max(0, n * (t * t - r) + r))
}
  , Qk = [Yd, oi, Xi]
  , Jk = e => Qk.find(t => t.test(e));
function lg(e) {
    const t = Jk(e);
    let n = t.parse(e);
    return t === Xi && (n = Xk(n)),
    n
}
const nw = (e, t) => {
    const n = lg(e)
      , r = lg(t)
      , i = {
        ...n
    };
    return s => (i.red = Su(n.red, r.red, s),
    i.green = Su(n.green, r.green, s),
    i.blue = Su(n.blue, r.blue, s),
    i.alpha = Fe(n.alpha, r.alpha, s),
    oi.transform(i))
}
;
function Zk(e) {
    var t, n;
    return isNaN(e) && na(e) && (((t = e.match(Cc)) === null || t === void 0 ? void 0 : t.length) || 0) + (((n = e.match(Py)) === null || n === void 0 ? void 0 : n.length) || 0) > 0
}
const rw = {
    regex: WC,
    countKey: "Vars",
    token: "${v}",
    parse: We
}
  , iw = {
    regex: Py,
    countKey: "Colors",
    token: "${c}",
    parse: bt.parse
}
  , sw = {
    regex: Cc,
    countKey: "Numbers",
    token: "${n}",
    parse: ki.parse
};
function Cu(e, {regex: t, countKey: n, token: r, parse: i}) {
    const s = e.tokenised.match(t);
    s && (e["num" + n] = s.length,
    e.tokenised = e.tokenised.replace(t, r),
    e.values.push(...s.map(i)))
}
function zl(e) {
    const t = e.toString()
      , n = {
        value: t,
        tokenised: t,
        values: [],
        numVars: 0,
        numColors: 0,
        numNumbers: 0
    };
    return n.value.includes("var(--") && Cu(n, rw),
    Cu(n, iw),
    Cu(n, sw),
    n
}
function ow(e) {
    return zl(e).values
}
function aw(e) {
    const {values: t, numColors: n, numVars: r, tokenised: i} = zl(e)
      , s = t.length;
    return o => {
        let l = i;
        for (let a = 0; a < s; a++)
            a < r ? l = l.replace(rw.token, o[a]) : a < r + n ? l = l.replace(iw.token, bt.transform(o[a])) : l = l.replace(sw.token, uo(o[a]));
        return l
    }
}
const eE = e => typeof e == "number" ? 0 : e;
function tE(e) {
    const t = ow(e);
    return aw(e)(t.map(eE))
}
const $r = {
    test: Zk,
    parse: ow,
    createTransformer: aw,
    getAnimatableNone: tE
}
  , lw = (e, t) => n => `${n > 0 ? t : e}`;
function cw(e, t) {
    return typeof e == "number" ? n => Fe(e, t, n) : bt.test(e) ? nw(e, t) : e.startsWith("var(") ? lw(e, t) : dw(e, t)
}
const uw = (e, t) => {
    const n = [...e]
      , r = n.length
      , i = e.map( (s, o) => cw(s, t[o]));
    return s => {
        for (let o = 0; o < r; o++)
            n[o] = i[o](s);
        return n
    }
}
  , nE = (e, t) => {
    const n = {
        ...e,
        ...t
    }
      , r = {};
    for (const i in n)
        e[i] !== void 0 && t[i] !== void 0 && (r[i] = cw(e[i], t[i]));
    return i => {
        for (const s in r)
            n[s] = r[s](i);
        return n
    }
}
  , dw = (e, t) => {
    const n = $r.createTransformer(t)
      , r = zl(e)
      , i = zl(t);
    return r.numVars === i.numVars && r.numColors === i.numColors && r.numNumbers >= i.numNumbers ? Ir(uw(r.values, i.values), n) : lw(e, t)
}
  , Vo = (e, t, n) => {
    const r = t - e;
    return r === 0 ? 1 : (n - e) / r
}
  , cg = (e, t) => n => Fe(e, t, n);
function rE(e) {
    return typeof e == "number" ? cg : typeof e == "string" ? bt.test(e) ? nw : dw : Array.isArray(e) ? uw : typeof e == "object" ? nE : cg
}
function iE(e, t, n) {
    const r = []
      , i = n || rE(e[0])
      , s = e.length - 1;
    for (let o = 0; o < s; o++) {
        let l = i(e[o], e[o + 1]);
        if (t) {
            const a = Array.isArray(t) ? t[o] || We : t;
            l = Ir(a, l)
        }
        r.push(l)
    }
    return r
}
function fw(e, t, {clamp: n=!0, ease: r, mixer: i}={}) {
    const s = e.length;
    if (zp(s === t.length),
    s === 1)
        return () => t[0];
    e[0] > e[s - 1] && (e = [...e].reverse(),
    t = [...t].reverse());
    const o = iE(t, r, i)
      , l = o.length
      , a = c => {
        let d = 0;
        if (l > 1)
            for (; d < e.length - 2 && !(c < e[d + 1]); d++)
                ;
        const f = Vo(e[d], e[d + 1], c);
        return o[d](f)
    }
    ;
    return n ? c => a(Dr(e[0], e[s - 1], c)) : a
}
function sE(e, t) {
    const n = e[e.length - 1];
    for (let r = 1; r <= t; r++) {
        const i = Vo(0, t, r);
        e.push(Fe(n, 1, i))
    }
}
function oE(e) {
    const t = [0];
    return sE(t, e.length - 1),
    t
}
function aE(e, t) {
    return e.map(n => n * t)
}
function lE(e, t) {
    return e.map( () => t || Xy).splice(0, e.length - 1)
}
function Bl({duration: e=300, keyframes: t, times: n, ease: r="easeInOut"}) {
    const i = Uk(r) ? r.map(ag) : ag(r)
      , s = {
        done: !1,
        value: t[0]
    }
      , o = aE(n && n.length === t.length ? n : oE(t), e)
      , l = fw(o, t, {
        ease: Array.isArray(i) ? i : lE(t, i)
    });
    return {
        calculatedDuration: e,
        next: a => (s.value = l(a),
        s.done = a >= e,
        s)
    }
}
function pw(e, t) {
    return t ? e * (1e3 / t) : 0
}
const cE = 5;
function mw(e, t, n) {
    const r = Math.max(t - cE, 0);
    return pw(n - e(r), t - r)
}
const ku = .001
  , uE = .01
  , ug = 10
  , dE = .05
  , fE = 1;
function pE({duration: e=800, bounce: t=.25, velocity: n=0, mass: r=1}) {
    let i, s;
    Mk(e <= Lr(ug));
    let o = 1 - t;
    o = Dr(dE, fE, o),
    e = Dr(uE, ug, Xn(e)),
    o < 1 ? (i = c => {
        const d = c * o
          , f = d * e
          , p = d - n
          , h = Kd(c, o)
          , m = Math.exp(-f);
        return ku - p / h * m
    }
    ,
    s = c => {
        const f = c * o * e
          , p = f * n + n
          , h = Math.pow(o, 2) * Math.pow(c, 2) * e
          , m = Math.exp(-f)
          , _ = Kd(Math.pow(c, 2), o);
        return (-i(c) + ku > 0 ? -1 : 1) * ((p - h) * m) / _
    }
    ) : (i = c => {
        const d = Math.exp(-c * e)
          , f = (c - n) * e + 1;
        return -ku + d * f
    }
    ,
    s = c => {
        const d = Math.exp(-c * e)
          , f = (n - c) * (e * e);
        return d * f
    }
    );
    const l = 5 / e
      , a = hE(i, s, l);
    if (e = Lr(e),
    isNaN(a))
        return {
            stiffness: 100,
            damping: 10,
            duration: e
        };
    {
        const c = Math.pow(a, 2) * r;
        return {
            stiffness: c,
            damping: o * 2 * Math.sqrt(r * c),
            duration: e
        }
    }
}
const mE = 12;
function hE(e, t, n) {
    let r = n;
    for (let i = 1; i < mE; i++)
        r = r - e(r) / t(r);
    return r
}
function Kd(e, t) {
    return e * Math.sqrt(1 - t * t)
}
const gE = ["duration", "bounce"]
  , _E = ["stiffness", "damping", "mass"];
function dg(e, t) {
    return t.some(n => e[n] !== void 0)
}
function vE(e) {
    let t = {
        velocity: 0,
        stiffness: 100,
        damping: 10,
        mass: 1,
        isResolvedFromDuration: !1,
        ...e
    };
    if (!dg(e, _E) && dg(e, gE)) {
        const n = pE(e);
        t = {
            ...t,
            ...n,
            mass: 1
        },
        t.isResolvedFromDuration = !0
    }
    return t
}
function hw({keyframes: e, restDelta: t, restSpeed: n, ...r}) {
    const i = e[0]
      , s = e[e.length - 1]
      , o = {
        done: !1,
        value: i
    }
      , {stiffness: l, damping: a, mass: c, duration: d, velocity: f, isResolvedFromDuration: p} = vE({
        ...r,
        velocity: -Xn(r.velocity || 0)
    })
      , h = f || 0
      , m = a / (2 * Math.sqrt(l * c))
      , _ = s - i
      , x = Xn(Math.sqrt(l / c))
      , v = Math.abs(_) < 5;
    n || (n = v ? .01 : 2),
    t || (t = v ? .005 : .5);
    let w;
    if (m < 1) {
        const y = Kd(x, m);
        w = b => {
            const C = Math.exp(-m * x * b);
            return s - C * ((h + m * x * _) / y * Math.sin(y * b) + _ * Math.cos(y * b))
        }
    } else if (m === 1)
        w = y => s - Math.exp(-x * y) * (_ + (h + x * _) * y);
    else {
        const y = x * Math.sqrt(m * m - 1);
        w = b => {
            const C = Math.exp(-m * x * b)
              , S = Math.min(y * b, 300);
            return s - C * ((h + m * x * _) * Math.sinh(S) + y * _ * Math.cosh(S)) / y
        }
    }
    return {
        calculatedDuration: p && d || null,
        next: y => {
            const b = w(y);
            if (p)
                o.done = y >= d;
            else {
                let C = h;
                y !== 0 && (m < 1 ? C = mw(w, y, b) : C = 0);
                const S = Math.abs(C) <= n
                  , T = Math.abs(s - b) <= t;
                o.done = S && T
            }
            return o.value = o.done ? s : b,
            o
        }
    }
}
function fg({keyframes: e, velocity: t=0, power: n=.8, timeConstant: r=325, bounceDamping: i=10, bounceStiffness: s=500, modifyTarget: o, min: l, max: a, restDelta: c=.5, restSpeed: d}) {
    const f = e[0]
      , p = {
        done: !1,
        value: f
    }
      , h = k => l !== void 0 && k < l || a !== void 0 && k > a
      , m = k => l === void 0 ? a : a === void 0 || Math.abs(l - k) < Math.abs(a - k) ? l : a;
    let _ = n * t;
    const x = f + _
      , v = o === void 0 ? x : o(x);
    v !== x && (_ = v - f);
    const w = k => -_ * Math.exp(-k / r)
      , y = k => v + w(k)
      , b = k => {
        const P = w(k)
          , R = y(k);
        p.done = Math.abs(P) <= c,
        p.value = p.done ? v : R
    }
    ;
    let C, S;
    const T = k => {
        h(p.value) && (C = k,
        S = hw({
            keyframes: [p.value, m(p.value)],
            velocity: mw(y, k, p.value),
            damping: i,
            stiffness: s,
            restDelta: c,
            restSpeed: d
        }))
    }
    ;
    return T(0),
    {
        calculatedDuration: null,
        next: k => {
            let P = !1;
            return !S && C === void 0 && (P = !0,
            b(k),
            T(k)),
            C !== void 0 && k > C ? S.next(k - C) : (!P && b(k),
            p)
        }
    }
}
const yE = e => {
    const t = ({timestamp: n}) => e(n);
    return {
        start: () => Ne.update(t, !0),
        stop: () => tr(t),
        now: () => gt.isProcessing ? gt.timestamp : performance.now()
    }
}
  , pg = 2e4;
function mg(e) {
    let t = 0;
    const n = 50;
    let r = e.next(t);
    for (; !r.done && t < pg; )
        t += n,
        r = e.next(t);
    return t >= pg ? 1 / 0 : t
}
const wE = {
    decay: fg,
    inertia: fg,
    tween: Bl,
    keyframes: Bl,
    spring: hw
};
function Ul({autoplay: e=!0, delay: t=0, driver: n=yE, keyframes: r, type: i="keyframes", repeat: s=0, repeatDelay: o=0, repeatType: l="loop", onPlay: a, onStop: c, onComplete: d, onUpdate: f, ...p}) {
    let h = 1, m = !1, _, x;
    const v = () => {
        x = new Promise(B => {
            _ = B
        }
        )
    }
    ;
    v();
    let w;
    const y = wE[i] || Bl;
    let b;
    y !== Bl && typeof r[0] != "number" && (b = fw([0, 100], r, {
        clamp: !1
    }),
    r = [0, 100]);
    const C = y({
        ...p,
        keyframes: r
    });
    let S;
    l === "mirror" && (S = y({
        ...p,
        keyframes: [...r].reverse(),
        velocity: -(p.velocity || 0)
    }));
    let T = "idle"
      , k = null
      , P = null
      , R = null;
    C.calculatedDuration === null && s && (C.calculatedDuration = mg(C));
    const {calculatedDuration: A} = C;
    let V = 1 / 0
      , F = 1 / 0;
    A !== null && (V = A + o,
    F = V * (s + 1) - o);
    let U = 0;
    const Q = B => {
        if (P === null)
            return;
        h > 0 && (P = Math.min(P, B)),
        h < 0 && (P = Math.min(B - F / h, P)),
        k !== null ? U = k : U = Math.round(B - P) * h;
        const H = U - t * (h >= 0 ? 1 : -1)
          , ue = h >= 0 ? H < 0 : H > F;
        U = Math.max(H, 0),
        T === "finished" && k === null && (U = F);
        let de = U
          , xe = C;
        if (s) {
            const Mt = Math.min(U, F) / V;
            let ot = Math.floor(Mt)
              , ft = Mt % 1;
            !ft && Mt >= 1 && (ft = 1),
            ft === 1 && ot--,
            ot = Math.min(ot, s + 1),
            !!(ot % 2) && (l === "reverse" ? (ft = 1 - ft,
            o && (ft -= o / V)) : l === "mirror" && (xe = S)),
            de = Dr(0, 1, ft) * V
        }
        const ge = ue ? {
            done: !1,
            value: r[0]
        } : xe.next(de);
        b && (ge.value = b(ge.value));
        let {done: wt} = ge;
        !ue && A !== null && (wt = h >= 0 ? U >= F : U <= 0);
        const un = k === null && (T === "finished" || T === "running" && wt);
        return f && f(ge.value),
        un && N(),
        ge
    }
      , Z = () => {
        w && w.stop(),
        w = void 0
    }
      , L = () => {
        T = "idle",
        Z(),
        _(),
        v(),
        P = R = null
    }
      , N = () => {
        T = "finished",
        d && d(),
        Z(),
        _()
    }
      , $ = () => {
        if (m)
            return;
        w || (w = n(Q));
        const B = w.now();
        a && a(),
        k !== null ? P = B - k : (!P || T === "finished") && (P = B),
        T === "finished" && v(),
        R = P,
        k = null,
        T = "running",
        w.start()
    }
    ;
    e && $();
    const O = {
        then(B, H) {
            return x.then(B, H)
        },
        get time() {
            return Xn(U)
        },
        set time(B) {
            B = Lr(B),
            U = B,
            k !== null || !w || h === 0 ? k = B : P = w.now() - B / h
        },
        get duration() {
            const B = C.calculatedDuration === null ? mg(C) : C.calculatedDuration;
            return Xn(B)
        },
        get speed() {
            return h
        },
        set speed(B) {
            B === h || !w || (h = B,
            O.time = Xn(U))
        },
        get state() {
            return T
        },
        play: $,
        pause: () => {
            T = "paused",
            k = U
        }
        ,
        stop: () => {
            m = !0,
            T !== "idle" && (T = "idle",
            c && c(),
            L())
        }
        ,
        cancel: () => {
            R !== null && Q(R),
            L()
        }
        ,
        complete: () => {
            T = "finished"
        }
        ,
        sample: B => (P = 0,
        Q(B))
    };
    return O
}
function xE(e) {
    let t;
    return () => (t === void 0 && (t = e()),
    t)
}
const bE = xE( () => Object.hasOwnProperty.call(Element.prototype, "animate"))
  , SE = new Set(["opacity", "clipPath", "filter", "transform", "backgroundColor"])
  , Oa = 10
  , CE = 2e4
  , kE = (e, t) => t.type === "spring" || e === "backgroundColor" || !Wy(t.ease);
function EE(e, t, {onUpdate: n, onComplete: r, ...i}) {
    if (!(bE() && SE.has(t) && !i.repeatDelay && i.repeatType !== "mirror" && i.damping !== 0 && i.type !== "inertia"))
        return !1;
    let o = !1, l, a, c = !1;
    const d = () => {
        a = new Promise(y => {
            l = y
        }
        )
    }
    ;
    d();
    let {keyframes: f, duration: p=300, ease: h, times: m} = i;
    if (kE(t, i)) {
        const y = Ul({
            ...i,
            repeat: 0,
            delay: 0
        });
        let b = {
            done: !1,
            value: f[0]
        };
        const C = [];
        let S = 0;
        for (; !b.done && S < CE; )
            b = y.sample(S),
            C.push(b.value),
            S += Oa;
        m = void 0,
        f = C,
        p = S - Oa,
        h = "linear"
    }
    const _ = Ak(e.owner.current, t, f, {
        ...i,
        duration: p,
        ease: h,
        times: m
    })
      , x = () => {
        c = !1,
        _.cancel()
    }
      , v = () => {
        c = !0,
        Ne.update(x),
        l(),
        d()
    }
    ;
    return _.onfinish = () => {
        c || (e.set(Dk(f, i)),
        r && r(),
        v())
    }
    ,
    {
        then(y, b) {
            return a.then(y, b)
        },
        attachTimeline(y) {
            return _.timeline = y,
            _.onfinish = null,
            We
        },
        get time() {
            return Xn(_.currentTime || 0)
        },
        set time(y) {
            _.currentTime = Lr(y)
        },
        get speed() {
            return _.playbackRate
        },
        set speed(y) {
            _.playbackRate = y
        },
        get duration() {
            return Xn(p)
        },
        play: () => {
            o || (_.play(),
            tr(x))
        }
        ,
        pause: () => _.pause(),
        stop: () => {
            if (o = !0,
            _.playState === "idle")
                return;
            const {currentTime: y} = _;
            if (y) {
                const b = Ul({
                    ...i,
                    autoplay: !1
                });
                e.setWithVelocity(b.sample(y - Oa).value, b.sample(y).value, Oa)
            }
            v()
        }
        ,
        complete: () => {
            c || _.finish()
        }
        ,
        cancel: v
    }
}
function TE({keyframes: e, delay: t, onUpdate: n, onComplete: r}) {
    const i = () => (n && n(e[e.length - 1]),
    r && r(),
    {
        time: 0,
        speed: 1,
        duration: 0,
        play: We,
        pause: We,
        stop: We,
        then: s => (s(),
        Promise.resolve()),
        cancel: We,
        complete: We
    });
    return t ? Ul({
        keyframes: [0, 1],
        duration: 0,
        delay: t,
        onComplete: i
    }) : i()
}
const PE = {
    type: "spring",
    stiffness: 500,
    damping: 25,
    restSpeed: 10
}
  , jE = e => ({
    type: "spring",
    stiffness: 550,
    damping: e === 0 ? 2 * Math.sqrt(550) : 30,
    restSpeed: 10
})
  , NE = {
    type: "keyframes",
    duration: .8
}
  , RE = {
    type: "keyframes",
    ease: [.25, .1, .35, 1],
    duration: .3
}
  , IE = (e, {keyframes: t}) => t.length > 2 ? NE : Ci.has(e) ? e.startsWith("scale") ? jE(t[1]) : PE : RE
  , Xd = (e, t) => e === "zIndex" ? !1 : !!(typeof t == "number" || Array.isArray(t) || typeof t == "string" && ($r.test(t) || t === "0") && !t.startsWith("url("))
  , LE = new Set(["brightness", "contrast", "saturate", "opacity"]);
function ME(e) {
    const [t,n] = e.slice(0, -1).split("(");
    if (t === "drop-shadow")
        return e;
    const [r] = n.match(Cc) || [];
    if (!r)
        return e;
    const i = n.replace(r, "");
    let s = LE.has(t) ? 1 : 0;
    return r !== n && (s *= 100),
    t + "(" + s + i + ")"
}
const OE = /([a-z-]*)\(.*?\)/g
  , Qd = {
    ...$r,
    getAnimatableNone: e => {
        const t = e.match(OE);
        return t ? t.map(ME).join(" ") : e
    }
}
  , AE = {
    ...jy,
    color: bt,
    backgroundColor: bt,
    outlineColor: bt,
    fill: bt,
    stroke: bt,
    borderColor: bt,
    borderTopColor: bt,
    borderRightColor: bt,
    borderBottomColor: bt,
    borderLeftColor: bt,
    filter: Qd,
    WebkitFilter: Qd
}
  , Gp = e => AE[e];
function gw(e, t) {
    let n = Gp(e);
    return n !== Qd && (n = $r),
    n.getAnimatableNone ? n.getAnimatableNone(t) : void 0
}
const _w = e => /^0[^.\s]+$/.test(e);
function DE(e) {
    if (typeof e == "number")
        return e === 0;
    if (e !== null)
        return e === "none" || e === "0" || _w(e)
}
function $E(e, t, n, r) {
    const i = Xd(t, n);
    let s;
    Array.isArray(n) ? s = [...n] : s = [null, n];
    const o = r.from !== void 0 ? r.from : e.get();
    let l;
    const a = [];
    for (let c = 0; c < s.length; c++)
        s[c] === null && (s[c] = c === 0 ? o : s[c - 1]),
        DE(s[c]) && a.push(c),
        typeof s[c] == "string" && s[c] !== "none" && s[c] !== "0" && (l = s[c]);
    if (i && a.length && l)
        for (let c = 0; c < a.length; c++) {
            const d = a[c];
            s[d] = gw(t, l)
        }
    return s
}
function FE({when: e, delay: t, delayChildren: n, staggerChildren: r, staggerDirection: i, repeat: s, repeatType: o, repeatDelay: l, from: a, elapsed: c, ...d}) {
    return !!Object.keys(d).length
}
function Wp(e, t) {
    return e[t] || e.default || e
}
const VE = {
    skipAnimations: !1
}
  , qp = (e, t, n, r={}) => i => {
    const s = Wp(r, e) || {}
      , o = s.delay || r.delay || 0;
    let {elapsed: l=0} = r;
    l = l - Lr(o);
    const a = $E(t, e, n, s)
      , c = a[0]
      , d = a[a.length - 1]
      , f = Xd(e, c)
      , p = Xd(e, d);
    let h = {
        keyframes: a,
        velocity: t.getVelocity(),
        ease: "easeOut",
        ...s,
        delay: -l,
        onUpdate: m => {
            t.set(m),
            s.onUpdate && s.onUpdate(m)
        }
        ,
        onComplete: () => {
            i(),
            s.onComplete && s.onComplete()
        }
    };
    if (FE(s) || (h = {
        ...h,
        ...IE(e, h)
    }),
    h.duration && (h.duration = Lr(h.duration)),
    h.repeatDelay && (h.repeatDelay = Lr(h.repeatDelay)),
    !f || !p || Ok.current || s.type === !1 || VE.skipAnimations)
        return TE(h);
    if (!r.isHandoff && t.owner && t.owner.current instanceof HTMLElement && !t.owner.getProps().onUpdate) {
        const m = EE(t, e, h);
        if (m)
            return m
    }
    return Ul(h)
}
;
function Hl(e) {
    return !!(Lt(e) && e.add)
}
const vw = e => /^\-?\d*\.?\d+$/.test(e);
function Yp(e, t) {
    e.indexOf(t) === -1 && e.push(t)
}
function Kp(e, t) {
    const n = e.indexOf(t);
    n > -1 && e.splice(n, 1)
}
class Xp {
    constructor() {
        this.subscriptions = []
    }
    add(t) {
        return Yp(this.subscriptions, t),
        () => Kp(this.subscriptions, t)
    }
    notify(t, n, r) {
        const i = this.subscriptions.length;
        if (i)
            if (i === 1)
                this.subscriptions[0](t, n, r);
            else
                for (let s = 0; s < i; s++) {
                    const o = this.subscriptions[s];
                    o && o(t, n, r)
                }
    }
    getSize() {
        return this.subscriptions.length
    }
    clear() {
        this.subscriptions.length = 0
    }
}
const zE = e => !isNaN(parseFloat(e));
class BE {
    constructor(t, n={}) {
        this.version = "10.18.0",
        this.timeDelta = 0,
        this.lastUpdated = 0,
        this.canTrackVelocity = !1,
        this.events = {},
        this.updateAndNotify = (r, i=!0) => {
            this.prev = this.current,
            this.current = r;
            const {delta: s, timestamp: o} = gt;
            this.lastUpdated !== o && (this.timeDelta = s,
            this.lastUpdated = o,
            Ne.postRender(this.scheduleVelocityCheck)),
            this.prev !== this.current && this.events.change && this.events.change.notify(this.current),
            this.events.velocityChange && this.events.velocityChange.notify(this.getVelocity()),
            i && this.events.renderRequest && this.events.renderRequest.notify(this.current)
        }
        ,
        this.scheduleVelocityCheck = () => Ne.postRender(this.velocityCheck),
        this.velocityCheck = ({timestamp: r}) => {
            r !== this.lastUpdated && (this.prev = this.current,
            this.events.velocityChange && this.events.velocityChange.notify(this.getVelocity()))
        }
        ,
        this.hasAnimated = !1,
        this.prev = this.current = t,
        this.canTrackVelocity = zE(this.current),
        this.owner = n.owner
    }
    onChange(t) {
        return this.on("change", t)
    }
    on(t, n) {
        this.events[t] || (this.events[t] = new Xp);
        const r = this.events[t].add(n);
        return t === "change" ? () => {
            r(),
            Ne.read( () => {
                this.events.change.getSize() || this.stop()
            }
            )
        }
        : r
    }
    clearListeners() {
        for (const t in this.events)
            this.events[t].clear()
    }
    attach(t, n) {
        this.passiveEffect = t,
        this.stopPassiveEffect = n
    }
    set(t, n=!0) {
        !n || !this.passiveEffect ? this.updateAndNotify(t, n) : this.passiveEffect(t, this.updateAndNotify)
    }
    setWithVelocity(t, n, r) {
        this.set(n),
        this.prev = t,
        this.timeDelta = r
    }
    jump(t) {
        this.updateAndNotify(t),
        this.prev = t,
        this.stop(),
        this.stopPassiveEffect && this.stopPassiveEffect()
    }
    get() {
        return this.current
    }
    getPrevious() {
        return this.prev
    }
    getVelocity() {
        return this.canTrackVelocity ? pw(parseFloat(this.current) - parseFloat(this.prev), this.timeDelta) : 0
    }
    start(t) {
        return this.stop(),
        new Promise(n => {
            this.hasAnimated = !0,
            this.animation = t(n),
            this.events.animationStart && this.events.animationStart.notify()
        }
        ).then( () => {
            this.events.animationComplete && this.events.animationComplete.notify(),
            this.clearAnimation()
        }
        )
    }
    stop() {
        this.animation && (this.animation.stop(),
        this.events.animationCancel && this.events.animationCancel.notify()),
        this.clearAnimation()
    }
    isAnimating() {
        return !!this.animation
    }
    clearAnimation() {
        delete this.animation
    }
    destroy() {
        this.clearListeners(),
        this.stop(),
        this.stopPassiveEffect && this.stopPassiveEffect()
    }
}
function hs(e, t) {
    return new BE(e,t)
}
const yw = e => t => t.test(e)
  , UE = {
    test: e => e === "auto",
    parse: e => e
}
  , ww = [ki, te, On, mr, XC, KC, UE]
  , Fs = e => ww.find(yw(e))
  , HE = [...ww, bt, $r]
  , GE = e => HE.find(yw(e));
function WE(e, t, n) {
    e.hasValue(t) ? e.getValue(t).set(n) : e.addValue(t, hs(n))
}
function qE(e, t) {
    const n = Ec(e, t);
    let {transitionEnd: r={}, transition: i={}, ...s} = n ? e.makeTargetAnimatable(n, !1) : {};
    s = {
        ...s,
        ...r
    };
    for (const o in s) {
        const l = uk(s[o]);
        WE(e, o, l)
    }
}
function YE(e, t, n) {
    var r, i;
    const s = Object.keys(t).filter(l => !e.hasValue(l))
      , o = s.length;
    if (o)
        for (let l = 0; l < o; l++) {
            const a = s[l]
              , c = t[a];
            let d = null;
            Array.isArray(c) && (d = c[0]),
            d === null && (d = (i = (r = n[a]) !== null && r !== void 0 ? r : e.readValue(a)) !== null && i !== void 0 ? i : t[a]),
            d != null && (typeof d == "string" && (vw(d) || _w(d)) ? d = parseFloat(d) : !GE(d) && $r.test(c) && (d = gw(a, c)),
            e.addValue(a, hs(d, {
                owner: e
            })),
            n[a] === void 0 && (n[a] = d),
            d !== null && e.setBaseTarget(a, d))
        }
}
function KE(e, t) {
    return t ? (t[e] || t.default || t).from : void 0
}
function XE(e, t, n) {
    const r = {};
    for (const i in e) {
        const s = KE(i, t);
        if (s !== void 0)
            r[i] = s;
        else {
            const o = n.getValue(i);
            o && (r[i] = o.get())
        }
    }
    return r
}
function QE({protectedKeys: e, needsAnimating: t}, n) {
    const r = e.hasOwnProperty(n) && t[n] !== !0;
    return t[n] = !1,
    r
}
function JE(e, t) {
    const n = e.get();
    if (Array.isArray(t)) {
        for (let r = 0; r < t.length; r++)
            if (t[r] !== n)
                return !0
    } else
        return n !== t
}
function xw(e, t, {delay: n=0, transitionOverride: r, type: i}={}) {
    let {transition: s=e.getDefaultTransition(), transitionEnd: o, ...l} = e.makeTargetAnimatable(t);
    const a = e.getValue("willChange");
    r && (s = r);
    const c = []
      , d = i && e.animationState && e.animationState.getState()[i];
    for (const f in l) {
        const p = e.getValue(f)
          , h = l[f];
        if (!p || h === void 0 || d && QE(d, f))
            continue;
        const m = {
            delay: n,
            elapsed: 0,
            ...Wp(s || {}, f)
        };
        if (window.HandoffAppearAnimations) {
            const v = e.getProps()[by];
            if (v) {
                const w = window.HandoffAppearAnimations(v, f, p, Ne);
                w !== null && (m.elapsed = w,
                m.isHandoff = !0)
            }
        }
        let _ = !m.isHandoff && !JE(p, h);
        if (m.type === "spring" && (p.getVelocity() || m.velocity) && (_ = !1),
        p.animation && (_ = !1),
        _)
            continue;
        p.start(qp(f, p, h, e.shouldReduceMotion && Ci.has(f) ? {
            type: !1
        } : m));
        const x = p.animation;
        Hl(a) && (a.add(f),
        x.then( () => a.remove(f))),
        c.push(x)
    }
    return o && Promise.all(c).then( () => {
        o && qE(e, o)
    }
    ),
    c
}
function Jd(e, t, n={}) {
    const r = Ec(e, t, n.custom);
    let {transition: i=e.getDefaultTransition() || {}} = r || {};
    n.transitionOverride && (i = n.transitionOverride);
    const s = r ? () => Promise.all(xw(e, r, n)) : () => Promise.resolve()
      , o = e.variantChildren && e.variantChildren.size ? (a=0) => {
        const {delayChildren: c=0, staggerChildren: d, staggerDirection: f} = i;
        return ZE(e, t, c + a, d, f, n)
    }
    : () => Promise.resolve()
      , {when: l} = i;
    if (l) {
        const [a,c] = l === "beforeChildren" ? [s, o] : [o, s];
        return a().then( () => c())
    } else
        return Promise.all([s(), o(n.delay)])
}
function ZE(e, t, n=0, r=0, i=1, s) {
    const o = []
      , l = (e.variantChildren.size - 1) * r
      , a = i === 1 ? (c=0) => c * r : (c=0) => l - c * r;
    return Array.from(e.variantChildren).sort(eT).forEach( (c, d) => {
        c.notify("AnimationStart", t),
        o.push(Jd(c, t, {
            ...s,
            delay: n + a(d)
        }).then( () => c.notify("AnimationComplete", t)))
    }
    ),
    Promise.all(o)
}
function eT(e, t) {
    return e.sortNodePosition(t)
}
function tT(e, t, n={}) {
    e.notify("AnimationStart", t);
    let r;
    if (Array.isArray(t)) {
        const i = t.map(s => Jd(e, s, n));
        r = Promise.all(i)
    } else if (typeof t == "string")
        r = Jd(e, t, n);
    else {
        const i = typeof t == "function" ? Ec(e, t, n.custom) : t;
        r = Promise.all(xw(e, i, n))
    }
    return r.then( () => e.notify("AnimationComplete", t))
}
const nT = [...Rp].reverse()
  , rT = Rp.length;
function iT(e) {
    return t => Promise.all(t.map( ({animation: n, options: r}) => tT(e, n, r)))
}
function sT(e) {
    let t = iT(e);
    const n = aT();
    let r = !0;
    const i = (a, c) => {
        const d = Ec(e, c);
        if (d) {
            const {transition: f, transitionEnd: p, ...h} = d;
            a = {
                ...a,
                ...h,
                ...p
            }
        }
        return a
    }
    ;
    function s(a) {
        t = a(e)
    }
    function o(a, c) {
        const d = e.getProps()
          , f = e.getVariantContext(!0) || {}
          , p = []
          , h = new Set;
        let m = {}
          , _ = 1 / 0;
        for (let v = 0; v < rT; v++) {
            const w = nT[v]
              , y = n[w]
              , b = d[w] !== void 0 ? d[w] : f[w]
              , C = $o(b)
              , S = w === c ? y.isActive : null;
            S === !1 && (_ = v);
            let T = b === f[w] && b !== d[w] && C;
            if (T && r && e.manuallyAnimateOnMount && (T = !1),
            y.protectedKeys = {
                ...m
            },
            !y.isActive && S === null || !b && !y.prevProp || bc(b) || typeof b == "boolean")
                continue;
            let P = oT(y.prevProp, b) || w === c && y.isActive && !T && C || v > _ && C
              , R = !1;
            const A = Array.isArray(b) ? b : [b];
            let V = A.reduce(i, {});
            S === !1 && (V = {});
            const {prevResolvedValues: F={}} = y
              , U = {
                ...F,
                ...V
            }
              , Q = Z => {
                P = !0,
                h.has(Z) && (R = !0,
                h.delete(Z)),
                y.needsAnimating[Z] = !0
            }
            ;
            for (const Z in U) {
                const L = V[Z]
                  , N = F[Z];
                if (m.hasOwnProperty(Z))
                    continue;
                let $ = !1;
                Vl(L) && Vl(N) ? $ = !Hy(L, N) : $ = L !== N,
                $ ? L !== void 0 ? Q(Z) : h.add(Z) : L !== void 0 && h.has(Z) ? Q(Z) : y.protectedKeys[Z] = !0
            }
            y.prevProp = b,
            y.prevResolvedValues = V,
            y.isActive && (m = {
                ...m,
                ...V
            }),
            r && e.blockInitialAnimation && (P = !1),
            P && (!T || R) && p.push(...A.map(Z => ({
                animation: Z,
                options: {
                    type: w,
                    ...a
                }
            })))
        }
        if (h.size) {
            const v = {};
            h.forEach(w => {
                const y = e.getBaseTarget(w);
                y !== void 0 && (v[w] = y)
            }
            ),
            p.push({
                animation: v
            })
        }
        let x = !!p.length;
        return r && (d.initial === !1 || d.initial === d.animate) && !e.manuallyAnimateOnMount && (x = !1),
        r = !1,
        x ? t(p) : Promise.resolve()
    }
    function l(a, c, d) {
        var f;
        if (n[a].isActive === c)
            return Promise.resolve();
        (f = e.variantChildren) === null || f === void 0 || f.forEach(h => {
            var m;
            return (m = h.animationState) === null || m === void 0 ? void 0 : m.setActive(a, c)
        }
        ),
        n[a].isActive = c;
        const p = o(d, a);
        for (const h in n)
            n[h].protectedKeys = {};
        return p
    }
    return {
        animateChanges: o,
        setActive: l,
        setAnimateFunction: s,
        getState: () => n
    }
}
function oT(e, t) {
    return typeof t == "string" ? t !== e : Array.isArray(t) ? !Hy(t, e) : !1
}
function Wr(e=!1) {
    return {
        isActive: e,
        protectedKeys: {},
        needsAnimating: {},
        prevResolvedValues: {}
    }
}
function aT() {
    return {
        animate: Wr(!0),
        whileInView: Wr(),
        whileHover: Wr(),
        whileTap: Wr(),
        whileDrag: Wr(),
        whileFocus: Wr(),
        exit: Wr()
    }
}
class lT extends Hr {
    constructor(t) {
        super(t),
        t.animationState || (t.animationState = sT(t))
    }
    updateAnimationControlsSubscription() {
        const {animate: t} = this.node.getProps();
        this.unmount(),
        bc(t) && (this.unmount = t.subscribe(this.node))
    }
    mount() {
        this.updateAnimationControlsSubscription()
    }
    update() {
        const {animate: t} = this.node.getProps()
          , {animate: n} = this.node.prevProps || {};
        t !== n && this.updateAnimationControlsSubscription()
    }
    unmount() {}
}
let cT = 0;
class uT extends Hr {
    constructor() {
        super(...arguments),
        this.id = cT++
    }
    update() {
        if (!this.node.presenceContext)
            return;
        const {isPresent: t, onExitComplete: n, custom: r} = this.node.presenceContext
          , {isPresent: i} = this.node.prevPresenceContext || {};
        if (!this.node.animationState || t === i)
            return;
        const s = this.node.animationState.setActive("exit", !t, {
            custom: r ?? this.node.getProps().custom
        });
        n && !t && s.then( () => n(this.id))
    }
    mount() {
        const {register: t} = this.node.presenceContext || {};
        t && (this.unmount = t(this.id))
    }
    unmount() {}
}
const dT = {
    animation: {
        Feature: lT
    },
    exit: {
        Feature: uT
    }
}
  , hg = (e, t) => Math.abs(e - t);
function fT(e, t) {
    const n = hg(e.x, t.x)
      , r = hg(e.y, t.y);
    return Math.sqrt(n ** 2 + r ** 2)
}
class bw {
    constructor(t, n, {transformPagePoint: r, contextWindow: i, dragSnapToOrigin: s=!1}={}) {
        if (this.startEvent = null,
        this.lastMoveEvent = null,
        this.lastMoveEventInfo = null,
        this.handlers = {},
        this.contextWindow = window,
        this.updatePoint = () => {
            if (!(this.lastMoveEvent && this.lastMoveEventInfo))
                return;
            const f = Tu(this.lastMoveEventInfo, this.history)
              , p = this.startEvent !== null
              , h = fT(f.offset, {
                x: 0,
                y: 0
            }) >= 3;
            if (!p && !h)
                return;
            const {point: m} = f
              , {timestamp: _} = gt;
            this.history.push({
                ...m,
                timestamp: _
            });
            const {onStart: x, onMove: v} = this.handlers;
            p || (x && x(this.lastMoveEvent, f),
            this.startEvent = this.lastMoveEvent),
            v && v(this.lastMoveEvent, f)
        }
        ,
        this.handlePointerMove = (f, p) => {
            this.lastMoveEvent = f,
            this.lastMoveEventInfo = Eu(p, this.transformPagePoint),
            Ne.update(this.updatePoint, !0)
        }
        ,
        this.handlePointerUp = (f, p) => {
            this.end();
            const {onEnd: h, onSessionEnd: m, resumeAnimation: _} = this.handlers;
            if (this.dragSnapToOrigin && _ && _(),
            !(this.lastMoveEvent && this.lastMoveEventInfo))
                return;
            const x = Tu(f.type === "pointercancel" ? this.lastMoveEventInfo : Eu(p, this.transformPagePoint), this.history);
            this.startEvent && h && h(f, x),
            m && m(f, x)
        }
        ,
        !Fy(t))
            return;
        this.dragSnapToOrigin = s,
        this.handlers = n,
        this.transformPagePoint = r,
        this.contextWindow = i || window;
        const o = kc(t)
          , l = Eu(o, this.transformPagePoint)
          , {point: a} = l
          , {timestamp: c} = gt;
        this.history = [{
            ...a,
            timestamp: c
        }];
        const {onSessionStart: d} = n;
        d && d(t, Tu(l, this.history)),
        this.removeListeners = Ir(Kn(this.contextWindow, "pointermove", this.handlePointerMove), Kn(this.contextWindow, "pointerup", this.handlePointerUp), Kn(this.contextWindow, "pointercancel", this.handlePointerUp))
    }
    updateHandlers(t) {
        this.handlers = t
    }
    end() {
        this.removeListeners && this.removeListeners(),
        tr(this.updatePoint)
    }
}
function Eu(e, t) {
    return t ? {
        point: t(e.point)
    } : e
}
function gg(e, t) {
    return {
        x: e.x - t.x,
        y: e.y - t.y
    }
}
function Tu({point: e}, t) {
    return {
        point: e,
        delta: gg(e, Sw(t)),
        offset: gg(e, pT(t)),
        velocity: mT(t, .1)
    }
}
function pT(e) {
    return e[0]
}
function Sw(e) {
    return e[e.length - 1]
}
function mT(e, t) {
    if (e.length < 2)
        return {
            x: 0,
            y: 0
        };
    let n = e.length - 1
      , r = null;
    const i = Sw(e);
    for (; n >= 0 && (r = e[n],
    !(i.timestamp - r.timestamp > Lr(t))); )
        n--;
    if (!r)
        return {
            x: 0,
            y: 0
        };
    const s = Xn(i.timestamp - r.timestamp);
    if (s === 0)
        return {
            x: 0,
            y: 0
        };
    const o = {
        x: (i.x - r.x) / s,
        y: (i.y - r.y) / s
    };
    return o.x === 1 / 0 && (o.x = 0),
    o.y === 1 / 0 && (o.y = 0),
    o
}
function qt(e) {
    return e.max - e.min
}
function Zd(e, t=0, n=.01) {
    return Math.abs(e - t) <= n
}
function _g(e, t, n, r=.5) {
    e.origin = r,
    e.originPoint = Fe(t.min, t.max, e.origin),
    e.scale = qt(n) / qt(t),
    (Zd(e.scale, 1, 1e-4) || isNaN(e.scale)) && (e.scale = 1),
    e.translate = Fe(n.min, n.max, e.origin) - e.originPoint,
    (Zd(e.translate) || isNaN(e.translate)) && (e.translate = 0)
}
function fo(e, t, n, r) {
    _g(e.x, t.x, n.x, r ? r.originX : void 0),
    _g(e.y, t.y, n.y, r ? r.originY : void 0)
}
function vg(e, t, n) {
    e.min = n.min + t.min,
    e.max = e.min + qt(t)
}
function hT(e, t, n) {
    vg(e.x, t.x, n.x),
    vg(e.y, t.y, n.y)
}
function yg(e, t, n) {
    e.min = t.min - n.min,
    e.max = e.min + qt(t)
}
function po(e, t, n) {
    yg(e.x, t.x, n.x),
    yg(e.y, t.y, n.y)
}
function gT(e, {min: t, max: n}, r) {
    return t !== void 0 && e < t ? e = r ? Fe(t, e, r.min) : Math.max(e, t) : n !== void 0 && e > n && (e = r ? Fe(n, e, r.max) : Math.min(e, n)),
    e
}
function wg(e, t, n) {
    return {
        min: t !== void 0 ? e.min + t : void 0,
        max: n !== void 0 ? e.max + n - (e.max - e.min) : void 0
    }
}
function _T(e, {top: t, left: n, bottom: r, right: i}) {
    return {
        x: wg(e.x, n, i),
        y: wg(e.y, t, r)
    }
}
function xg(e, t) {
    let n = t.min - e.min
      , r = t.max - e.max;
    return t.max - t.min < e.max - e.min && ([n,r] = [r, n]),
    {
        min: n,
        max: r
    }
}
function vT(e, t) {
    return {
        x: xg(e.x, t.x),
        y: xg(e.y, t.y)
    }
}
function yT(e, t) {
    let n = .5;
    const r = qt(e)
      , i = qt(t);
    return i > r ? n = Vo(t.min, t.max - r, e.min) : r > i && (n = Vo(e.min, e.max - i, t.min)),
    Dr(0, 1, n)
}
function wT(e, t) {
    const n = {};
    return t.min !== void 0 && (n.min = t.min - e.min),
    t.max !== void 0 && (n.max = t.max - e.min),
    n
}
const ef = .35;
function xT(e=ef) {
    return e === !1 ? e = 0 : e === !0 && (e = ef),
    {
        x: bg(e, "left", "right"),
        y: bg(e, "top", "bottom")
    }
}
function bg(e, t, n) {
    return {
        min: Sg(e, t),
        max: Sg(e, n)
    }
}
function Sg(e, t) {
    return typeof e == "number" ? e : e[t] || 0
}
const Cg = () => ({
    translate: 0,
    scale: 1,
    origin: 0,
    originPoint: 0
})
  , Qi = () => ({
    x: Cg(),
    y: Cg()
})
  , kg = () => ({
    min: 0,
    max: 0
})
  , Ze = () => ({
    x: kg(),
    y: kg()
});
function en(e) {
    return [e("x"), e("y")]
}
function Cw({top: e, left: t, right: n, bottom: r}) {
    return {
        x: {
            min: t,
            max: n
        },
        y: {
            min: e,
            max: r
        }
    }
}
function bT({x: e, y: t}) {
    return {
        top: t.min,
        right: e.max,
        bottom: t.max,
        left: e.min
    }
}
function ST(e, t) {
    if (!t)
        return e;
    const n = t({
        x: e.left,
        y: e.top
    })
      , r = t({
        x: e.right,
        y: e.bottom
    });
    return {
        top: n.y,
        left: n.x,
        bottom: r.y,
        right: r.x
    }
}
function Pu(e) {
    return e === void 0 || e === 1
}
function tf({scale: e, scaleX: t, scaleY: n}) {
    return !Pu(e) || !Pu(t) || !Pu(n)
}
function Qr(e) {
    return tf(e) || kw(e) || e.z || e.rotate || e.rotateX || e.rotateY
}
function kw(e) {
    return Eg(e.x) || Eg(e.y)
}
function Eg(e) {
    return e && e !== "0%"
}
function Gl(e, t, n) {
    const r = e - n
      , i = t * r;
    return n + i
}
function Tg(e, t, n, r, i) {
    return i !== void 0 && (e = Gl(e, i, r)),
    Gl(e, n, r) + t
}
function nf(e, t=0, n=1, r, i) {
    e.min = Tg(e.min, t, n, r, i),
    e.max = Tg(e.max, t, n, r, i)
}
function Ew(e, {x: t, y: n}) {
    nf(e.x, t.translate, t.scale, t.originPoint),
    nf(e.y, n.translate, n.scale, n.originPoint)
}
function CT(e, t, n, r=!1) {
    const i = n.length;
    if (!i)
        return;
    t.x = t.y = 1;
    let s, o;
    for (let l = 0; l < i; l++) {
        s = n[l],
        o = s.projectionDelta;
        const a = s.instance;
        a && a.style && a.style.display === "contents" || (r && s.options.layoutScroll && s.scroll && s !== s.root && Ji(e, {
            x: -s.scroll.offset.x,
            y: -s.scroll.offset.y
        }),
        o && (t.x *= o.x.scale,
        t.y *= o.y.scale,
        Ew(e, o)),
        r && Qr(s.latestValues) && Ji(e, s.latestValues))
    }
    t.x = Pg(t.x),
    t.y = Pg(t.y)
}
function Pg(e) {
    return Number.isInteger(e) || e > 1.0000000000001 || e < .999999999999 ? e : 1
}
function _r(e, t) {
    e.min = e.min + t,
    e.max = e.max + t
}
function jg(e, t, [n,r,i]) {
    const s = t[i] !== void 0 ? t[i] : .5
      , o = Fe(e.min, e.max, s);
    nf(e, t[n], t[r], o, t.scale)
}
const kT = ["x", "scaleX", "originX"]
  , ET = ["y", "scaleY", "originY"];
function Ji(e, t) {
    jg(e.x, t, kT),
    jg(e.y, t, ET)
}
function Tw(e, t) {
    return Cw(ST(e.getBoundingClientRect(), t))
}
function TT(e, t, n) {
    const r = Tw(e, n)
      , {scroll: i} = t;
    return i && (_r(r.x, i.offset.x),
    _r(r.y, i.offset.y)),
    r
}
const Pw = ({current: e}) => e ? e.ownerDocument.defaultView : null
  , PT = new WeakMap;
class jT {
    constructor(t) {
        this.openGlobalLock = null,
        this.isDragging = !1,
        this.currentDirection = null,
        this.originPoint = {
            x: 0,
            y: 0
        },
        this.constraints = !1,
        this.hasMutatedConstraints = !1,
        this.elastic = Ze(),
        this.visualElement = t
    }
    start(t, {snapToCursor: n=!1}={}) {
        const {presenceContext: r} = this.visualElement;
        if (r && r.isPresent === !1)
            return;
        const i = d => {
            const {dragSnapToOrigin: f} = this.getProps();
            f ? this.pauseAnimation() : this.stopAnimation(),
            n && this.snapToCursor(kc(d, "page").point)
        }
          , s = (d, f) => {
            const {drag: p, dragPropagation: h, onDragStart: m} = this.getProps();
            if (p && !h && (this.openGlobalLock && this.openGlobalLock(),
            this.openGlobalLock = zy(p),
            !this.openGlobalLock))
                return;
            this.isDragging = !0,
            this.currentDirection = null,
            this.resolveConstraints(),
            this.visualElement.projection && (this.visualElement.projection.isAnimationBlocked = !0,
            this.visualElement.projection.target = void 0),
            en(x => {
                let v = this.getAxisMotionValue(x).get() || 0;
                if (On.test(v)) {
                    const {projection: w} = this.visualElement;
                    if (w && w.layout) {
                        const y = w.layout.layoutBox[x];
                        y && (v = qt(y) * (parseFloat(v) / 100))
                    }
                }
                this.originPoint[x] = v
            }
            ),
            m && Ne.update( () => m(d, f), !1, !0);
            const {animationState: _} = this.visualElement;
            _ && _.setActive("whileDrag", !0)
        }
          , o = (d, f) => {
            const {dragPropagation: p, dragDirectionLock: h, onDirectionLock: m, onDrag: _} = this.getProps();
            if (!p && !this.openGlobalLock)
                return;
            const {offset: x} = f;
            if (h && this.currentDirection === null) {
                this.currentDirection = NT(x),
                this.currentDirection !== null && m && m(this.currentDirection);
                return
            }
            this.updateAxis("x", f.point, x),
            this.updateAxis("y", f.point, x),
            this.visualElement.render(),
            _ && _(d, f)
        }
          , l = (d, f) => this.stop(d, f)
          , a = () => en(d => {
            var f;
            return this.getAnimationState(d) === "paused" && ((f = this.getAxisMotionValue(d).animation) === null || f === void 0 ? void 0 : f.play())
        }
        )
          , {dragSnapToOrigin: c} = this.getProps();
        this.panSession = new bw(t,{
            onSessionStart: i,
            onStart: s,
            onMove: o,
            onSessionEnd: l,
            resumeAnimation: a
        },{
            transformPagePoint: this.visualElement.getTransformPagePoint(),
            dragSnapToOrigin: c,
            contextWindow: Pw(this.visualElement)
        })
    }
    stop(t, n) {
        const r = this.isDragging;
        if (this.cancel(),
        !r)
            return;
        const {velocity: i} = n;
        this.startAnimation(i);
        const {onDragEnd: s} = this.getProps();
        s && Ne.update( () => s(t, n))
    }
    cancel() {
        this.isDragging = !1;
        const {projection: t, animationState: n} = this.visualElement;
        t && (t.isAnimationBlocked = !1),
        this.panSession && this.panSession.end(),
        this.panSession = void 0;
        const {dragPropagation: r} = this.getProps();
        !r && this.openGlobalLock && (this.openGlobalLock(),
        this.openGlobalLock = null),
        n && n.setActive("whileDrag", !1)
    }
    updateAxis(t, n, r) {
        const {drag: i} = this.getProps();
        if (!r || !Aa(t, i, this.currentDirection))
            return;
        const s = this.getAxisMotionValue(t);
        let o = this.originPoint[t] + r[t];
        this.constraints && this.constraints[t] && (o = gT(o, this.constraints[t], this.elastic[t])),
        s.set(o)
    }
    resolveConstraints() {
        var t;
        const {dragConstraints: n, dragElastic: r} = this.getProps()
          , i = this.visualElement.projection && !this.visualElement.projection.layout ? this.visualElement.projection.measure(!1) : (t = this.visualElement.projection) === null || t === void 0 ? void 0 : t.layout
          , s = this.constraints;
        n && Ki(n) ? this.constraints || (this.constraints = this.resolveRefConstraints()) : n && i ? this.constraints = _T(i.layoutBox, n) : this.constraints = !1,
        this.elastic = xT(r),
        s !== this.constraints && i && this.constraints && !this.hasMutatedConstraints && en(o => {
            this.getAxisMotionValue(o) && (this.constraints[o] = wT(i.layoutBox[o], this.constraints[o]))
        }
        )
    }
    resolveRefConstraints() {
        const {dragConstraints: t, onMeasureDragConstraints: n} = this.getProps();
        if (!t || !Ki(t))
            return !1;
        const r = t.current
          , {projection: i} = this.visualElement;
        if (!i || !i.layout)
            return !1;
        const s = TT(r, i.root, this.visualElement.getTransformPagePoint());
        let o = vT(i.layout.layoutBox, s);
        if (n) {
            const l = n(bT(o));
            this.hasMutatedConstraints = !!l,
            l && (o = Cw(l))
        }
        return o
    }
    startAnimation(t) {
        const {drag: n, dragMomentum: r, dragElastic: i, dragTransition: s, dragSnapToOrigin: o, onDragTransitionEnd: l} = this.getProps()
          , a = this.constraints || {}
          , c = en(d => {
            if (!Aa(d, n, this.currentDirection))
                return;
            let f = a && a[d] || {};
            o && (f = {
                min: 0,
                max: 0
            });
            const p = i ? 200 : 1e6
              , h = i ? 40 : 1e7
              , m = {
                type: "inertia",
                velocity: r ? t[d] : 0,
                bounceStiffness: p,
                bounceDamping: h,
                timeConstant: 750,
                restDelta: 1,
                restSpeed: 10,
                ...s,
                ...f
            };
            return this.startAxisValueAnimation(d, m)
        }
        );
        return Promise.all(c).then(l)
    }
    startAxisValueAnimation(t, n) {
        const r = this.getAxisMotionValue(t);
        return r.start(qp(t, r, 0, n))
    }
    stopAnimation() {
        en(t => this.getAxisMotionValue(t).stop())
    }
    pauseAnimation() {
        en(t => {
            var n;
            return (n = this.getAxisMotionValue(t).animation) === null || n === void 0 ? void 0 : n.pause()
        }
        )
    }
    getAnimationState(t) {
        var n;
        return (n = this.getAxisMotionValue(t).animation) === null || n === void 0 ? void 0 : n.state
    }
    getAxisMotionValue(t) {
        const n = "_drag" + t.toUpperCase()
          , r = this.visualElement.getProps()
          , i = r[n];
        return i || this.visualElement.getValue(t, (r.initial ? r.initial[t] : void 0) || 0)
    }
    snapToCursor(t) {
        en(n => {
            const {drag: r} = this.getProps();
            if (!Aa(n, r, this.currentDirection))
                return;
            const {projection: i} = this.visualElement
              , s = this.getAxisMotionValue(n);
            if (i && i.layout) {
                const {min: o, max: l} = i.layout.layoutBox[n];
                s.set(t[n] - Fe(o, l, .5))
            }
        }
        )
    }
    scalePositionWithinConstraints() {
        if (!this.visualElement.current)
            return;
        const {drag: t, dragConstraints: n} = this.getProps()
          , {projection: r} = this.visualElement;
        if (!Ki(n) || !r || !this.constraints)
            return;
        this.stopAnimation();
        const i = {
            x: 0,
            y: 0
        };
        en(o => {
            const l = this.getAxisMotionValue(o);
            if (l) {
                const a = l.get();
                i[o] = yT({
                    min: a,
                    max: a
                }, this.constraints[o])
            }
        }
        );
        const {transformTemplate: s} = this.visualElement.getProps();
        this.visualElement.current.style.transform = s ? s({}, "") : "none",
        r.root && r.root.updateScroll(),
        r.updateLayout(),
        this.resolveConstraints(),
        en(o => {
            if (!Aa(o, t, null))
                return;
            const l = this.getAxisMotionValue(o)
              , {min: a, max: c} = this.constraints[o];
            l.set(Fe(a, c, i[o]))
        }
        )
    }
    addListeners() {
        if (!this.visualElement.current)
            return;
        PT.set(this.visualElement, this);
        const t = this.visualElement.current
          , n = Kn(t, "pointerdown", a => {
            const {drag: c, dragListener: d=!0} = this.getProps();
            c && d && this.start(a)
        }
        )
          , r = () => {
            const {dragConstraints: a} = this.getProps();
            Ki(a) && (this.constraints = this.resolveRefConstraints())
        }
          , {projection: i} = this.visualElement
          , s = i.addEventListener("measure", r);
        i && !i.layout && (i.root && i.root.updateScroll(),
        i.updateLayout()),
        r();
        const o = Wn(window, "resize", () => this.scalePositionWithinConstraints())
          , l = i.addEventListener("didUpdate", ({delta: a, hasLayoutChanged: c}) => {
            this.isDragging && c && (en(d => {
                const f = this.getAxisMotionValue(d);
                f && (this.originPoint[d] += a[d].translate,
                f.set(f.get() + a[d].translate))
            }
            ),
            this.visualElement.render())
        }
        );
        return () => {
            o(),
            n(),
            s(),
            l && l()
        }
    }
    getProps() {
        const t = this.visualElement.getProps()
          , {drag: n=!1, dragDirectionLock: r=!1, dragPropagation: i=!1, dragConstraints: s=!1, dragElastic: o=ef, dragMomentum: l=!0} = t;
        return {
            ...t,
            drag: n,
            dragDirectionLock: r,
            dragPropagation: i,
            dragConstraints: s,
            dragElastic: o,
            dragMomentum: l
        }
    }
}
function Aa(e, t, n) {
    return (t === !0 || t === e) && (n === null || n === e)
}
function NT(e, t=10) {
    let n = null;
    return Math.abs(e.y) > t ? n = "y" : Math.abs(e.x) > t && (n = "x"),
    n
}
class RT extends Hr {
    constructor(t) {
        super(t),
        this.removeGroupControls = We,
        this.removeListeners = We,
        this.controls = new jT(t)
    }
    mount() {
        const {dragControls: t} = this.node.getProps();
        t && (this.removeGroupControls = t.subscribe(this.controls)),
        this.removeListeners = this.controls.addListeners() || We
    }
    unmount() {
        this.removeGroupControls(),
        this.removeListeners()
    }
}
const Ng = e => (t, n) => {
    e && Ne.update( () => e(t, n))
}
;
class IT extends Hr {
    constructor() {
        super(...arguments),
        this.removePointerDownListener = We
    }
    onPointerDown(t) {
        this.session = new bw(t,this.createPanHandlers(),{
            transformPagePoint: this.node.getTransformPagePoint(),
            contextWindow: Pw(this.node)
        })
    }
    createPanHandlers() {
        const {onPanSessionStart: t, onPanStart: n, onPan: r, onPanEnd: i} = this.node.getProps();
        return {
            onSessionStart: Ng(t),
            onStart: Ng(n),
            onMove: r,
            onEnd: (s, o) => {
                delete this.session,
                i && Ne.update( () => i(s, o))
            }
        }
    }
    mount() {
        this.removePointerDownListener = Kn(this.node.current, "pointerdown", t => this.onPointerDown(t))
    }
    update() {
        this.session && this.session.updateHandlers(this.createPanHandlers())
    }
    unmount() {
        this.removePointerDownListener(),
        this.session && this.session.end()
    }
}
function LT() {
    const e = g.useContext(wc);
    if (e === null)
        return [!0, null];
    const {isPresent: t, onExitComplete: n, register: r} = e
      , i = g.useId();
    return g.useEffect( () => r(i), []),
    !t && n ? [!1, () => n && n(i)] : [!0]
}
const al = {
    hasAnimatedSinceResize: !0,
    hasEverUpdated: !1
};
function Rg(e, t) {
    return t.max === t.min ? 0 : e / (t.max - t.min) * 100
}
const Vs = {
    correct: (e, t) => {
        if (!t.target)
            return e;
        if (typeof e == "string")
            if (te.test(e))
                e = parseFloat(e);
            else
                return e;
        const n = Rg(e, t.target.x)
          , r = Rg(e, t.target.y);
        return `${n}% ${r}%`
    }
}
  , MT = {
    correct: (e, {treeScale: t, projectionDelta: n}) => {
        const r = e
          , i = $r.parse(e);
        if (i.length > 5)
            return r;
        const s = $r.createTransformer(e)
          , o = typeof i[0] != "number" ? 1 : 0
          , l = n.x.scale * t.x
          , a = n.y.scale * t.y;
        i[0 + o] /= l,
        i[1 + o] /= a;
        const c = Fe(l, a, .5);
        return typeof i[2 + o] == "number" && (i[2 + o] /= c),
        typeof i[3 + o] == "number" && (i[3 + o] /= c),
        s(i)
    }
};
class OT extends Ce.Component {
    componentDidMount() {
        const {visualElement: t, layoutGroup: n, switchLayoutGroup: r, layoutId: i} = this.props
          , {projection: s} = t;
        BC(AT),
        s && (n.group && n.group.add(s),
        r && r.register && i && r.register(s),
        s.root.didUpdate(),
        s.addEventListener("animationComplete", () => {
            this.safeToRemove()
        }
        ),
        s.setOptions({
            ...s.options,
            onExitComplete: () => this.safeToRemove()
        })),
        al.hasEverUpdated = !0
    }
    getSnapshotBeforeUpdate(t) {
        const {layoutDependency: n, visualElement: r, drag: i, isPresent: s} = this.props
          , o = r.projection;
        return o && (o.isPresent = s,
        i || t.layoutDependency !== n || n === void 0 ? o.willUpdate() : this.safeToRemove(),
        t.isPresent !== s && (s ? o.promote() : o.relegate() || Ne.postRender( () => {
            const l = o.getStack();
            (!l || !l.members.length) && this.safeToRemove()
        }
        ))),
        null
    }
    componentDidUpdate() {
        const {projection: t} = this.props.visualElement;
        t && (t.root.didUpdate(),
        queueMicrotask( () => {
            !t.currentAnimation && t.isLead() && this.safeToRemove()
        }
        ))
    }
    componentWillUnmount() {
        const {visualElement: t, layoutGroup: n, switchLayoutGroup: r} = this.props
          , {projection: i} = t;
        i && (i.scheduleCheckAfterUnmount(),
        n && n.group && n.group.remove(i),
        r && r.deregister && r.deregister(i))
    }
    safeToRemove() {
        const {safeToRemove: t} = this.props;
        t && t()
    }
    render() {
        return null
    }
}
function jw(e) {
    const [t,n] = LT()
      , r = g.useContext(Lp);
    return Ce.createElement(OT, {
        ...e,
        layoutGroup: r,
        switchLayoutGroup: g.useContext(Cy),
        isPresent: t,
        safeToRemove: n
    })
}
const AT = {
    borderRadius: {
        ...Vs,
        applyTo: ["borderTopLeftRadius", "borderTopRightRadius", "borderBottomLeftRadius", "borderBottomRightRadius"]
    },
    borderTopLeftRadius: Vs,
    borderTopRightRadius: Vs,
    borderBottomLeftRadius: Vs,
    borderBottomRightRadius: Vs,
    boxShadow: MT
}
  , Nw = ["TopLeft", "TopRight", "BottomLeft", "BottomRight"]
  , DT = Nw.length
  , Ig = e => typeof e == "string" ? parseFloat(e) : e
  , Lg = e => typeof e == "number" || te.test(e);
function $T(e, t, n, r, i, s) {
    i ? (e.opacity = Fe(0, n.opacity !== void 0 ? n.opacity : 1, FT(r)),
    e.opacityExit = Fe(t.opacity !== void 0 ? t.opacity : 1, 0, VT(r))) : s && (e.opacity = Fe(t.opacity !== void 0 ? t.opacity : 1, n.opacity !== void 0 ? n.opacity : 1, r));
    for (let o = 0; o < DT; o++) {
        const l = `border${Nw[o]}Radius`;
        let a = Mg(t, l)
          , c = Mg(n, l);
        if (a === void 0 && c === void 0)
            continue;
        a || (a = 0),
        c || (c = 0),
        a === 0 || c === 0 || Lg(a) === Lg(c) ? (e[l] = Math.max(Fe(Ig(a), Ig(c), r), 0),
        (On.test(c) || On.test(a)) && (e[l] += "%")) : e[l] = c
    }
    (t.rotate || n.rotate) && (e.rotate = Fe(t.rotate || 0, n.rotate || 0, r))
}
function Mg(e, t) {
    return e[t] !== void 0 ? e[t] : e.borderRadius
}
const FT = Rw(0, .5, Zy)
  , VT = Rw(.5, .95, We);
function Rw(e, t, n) {
    return r => r < e ? 0 : r > t ? 1 : n(Vo(e, t, r))
}
function Og(e, t) {
    e.min = t.min,
    e.max = t.max
}
function Jt(e, t) {
    Og(e.x, t.x),
    Og(e.y, t.y)
}
function Ag(e, t, n, r, i) {
    return e -= t,
    e = Gl(e, 1 / n, r),
    i !== void 0 && (e = Gl(e, 1 / i, r)),
    e
}
function zT(e, t=0, n=1, r=.5, i, s=e, o=e) {
    if (On.test(t) && (t = parseFloat(t),
    t = Fe(o.min, o.max, t / 100) - o.min),
    typeof t != "number")
        return;
    let l = Fe(s.min, s.max, r);
    e === s && (l -= t),
    e.min = Ag(e.min, t, n, l, i),
    e.max = Ag(e.max, t, n, l, i)
}
function Dg(e, t, [n,r,i], s, o) {
    zT(e, t[n], t[r], t[i], t.scale, s, o)
}
const BT = ["x", "scaleX", "originX"]
  , UT = ["y", "scaleY", "originY"];
function $g(e, t, n, r) {
    Dg(e.x, t, BT, n ? n.x : void 0, r ? r.x : void 0),
    Dg(e.y, t, UT, n ? n.y : void 0, r ? r.y : void 0)
}
function Fg(e) {
    return e.translate === 0 && e.scale === 1
}
function Iw(e) {
    return Fg(e.x) && Fg(e.y)
}
function HT(e, t) {
    return e.x.min === t.x.min && e.x.max === t.x.max && e.y.min === t.y.min && e.y.max === t.y.max
}
function Lw(e, t) {
    return Math.round(e.x.min) === Math.round(t.x.min) && Math.round(e.x.max) === Math.round(t.x.max) && Math.round(e.y.min) === Math.round(t.y.min) && Math.round(e.y.max) === Math.round(t.y.max)
}
function Vg(e) {
    return qt(e.x) / qt(e.y)
}
class GT {
    constructor() {
        this.members = []
    }
    add(t) {
        Yp(this.members, t),
        t.scheduleRender()
    }
    remove(t) {
        if (Kp(this.members, t),
        t === this.prevLead && (this.prevLead = void 0),
        t === this.lead) {
            const n = this.members[this.members.length - 1];
            n && this.promote(n)
        }
    }
    relegate(t) {
        const n = this.members.findIndex(i => t === i);
        if (n === 0)
            return !1;
        let r;
        for (let i = n; i >= 0; i--) {
            const s = this.members[i];
            if (s.isPresent !== !1) {
                r = s;
                break
            }
        }
        return r ? (this.promote(r),
        !0) : !1
    }
    promote(t, n) {
        const r = this.lead;
        if (t !== r && (this.prevLead = r,
        this.lead = t,
        t.show(),
        r)) {
            r.instance && r.scheduleRender(),
            t.scheduleRender(),
            t.resumeFrom = r,
            n && (t.resumeFrom.preserveOpacity = !0),
            r.snapshot && (t.snapshot = r.snapshot,
            t.snapshot.latestValues = r.animationValues || r.latestValues),
            t.root && t.root.isUpdating && (t.isLayoutDirty = !0);
            const {crossfade: i} = t.options;
            i === !1 && r.hide()
        }
    }
    exitAnimationComplete() {
        this.members.forEach(t => {
            const {options: n, resumingFrom: r} = t;
            n.onExitComplete && n.onExitComplete(),
            r && r.options.onExitComplete && r.options.onExitComplete()
        }
        )
    }
    scheduleRender() {
        this.members.forEach(t => {
            t.instance && t.scheduleRender(!1)
        }
        )
    }
    removeLeadSnapshot() {
        this.lead && this.lead.snapshot && (this.lead.snapshot = void 0)
    }
}
function zg(e, t, n) {
    let r = "";
    const i = e.x.translate / t.x
      , s = e.y.translate / t.y;
    if ((i || s) && (r = `translate3d(${i}px, ${s}px, 0) `),
    (t.x !== 1 || t.y !== 1) && (r += `scale(${1 / t.x}, ${1 / t.y}) `),
    n) {
        const {rotate: a, rotateX: c, rotateY: d} = n;
        a && (r += `rotate(${a}deg) `),
        c && (r += `rotateX(${c}deg) `),
        d && (r += `rotateY(${d}deg) `)
    }
    const o = e.x.scale * t.x
      , l = e.y.scale * t.y;
    return (o !== 1 || l !== 1) && (r += `scale(${o}, ${l})`),
    r || "none"
}
const WT = (e, t) => e.depth - t.depth;
class qT {
    constructor() {
        this.children = [],
        this.isDirty = !1
    }
    add(t) {
        Yp(this.children, t),
        this.isDirty = !0
    }
    remove(t) {
        Kp(this.children, t),
        this.isDirty = !0
    }
    forEach(t) {
        this.isDirty && this.children.sort(WT),
        this.isDirty = !1,
        this.children.forEach(t)
    }
}
function YT(e, t) {
    const n = performance.now()
      , r = ({timestamp: i}) => {
        const s = i - n;
        s >= t && (tr(r),
        e(s - t))
    }
    ;
    return Ne.read(r, !0),
    () => tr(r)
}
function KT(e) {
    window.MotionDebug && window.MotionDebug.record(e)
}
function XT(e) {
    return e instanceof SVGElement && e.tagName !== "svg"
}
function QT(e, t, n) {
    const r = Lt(e) ? e : hs(e);
    return r.start(qp("", r, t, n)),
    r.animation
}
const Bg = ["", "X", "Y", "Z"]
  , JT = {
    visibility: "hidden"
}
  , Ug = 1e3;
let ZT = 0;
const Jr = {
    type: "projectionFrame",
    totalNodes: 0,
    resolvedTargetDeltas: 0,
    recalculatedProjection: 0
};
function Mw({attachResizeListener: e, defaultParent: t, measureScroll: n, checkIsScrollRoot: r, resetTransform: i}) {
    return class {
        constructor(o={}, l=t == null ? void 0 : t()) {
            this.id = ZT++,
            this.animationId = 0,
            this.children = new Set,
            this.options = {},
            this.isTreeAnimating = !1,
            this.isAnimationBlocked = !1,
            this.isLayoutDirty = !1,
            this.isProjectionDirty = !1,
            this.isSharedProjectionDirty = !1,
            this.isTransformDirty = !1,
            this.updateManuallyBlocked = !1,
            this.updateBlockedByResize = !1,
            this.isUpdating = !1,
            this.isSVG = !1,
            this.needsReset = !1,
            this.shouldResetTransform = !1,
            this.treeScale = {
                x: 1,
                y: 1
            },
            this.eventHandlers = new Map,
            this.hasTreeAnimated = !1,
            this.updateScheduled = !1,
            this.projectionUpdateScheduled = !1,
            this.checkUpdateFailed = () => {
                this.isUpdating && (this.isUpdating = !1,
                this.clearAllSnapshots())
            }
            ,
            this.updateProjection = () => {
                this.projectionUpdateScheduled = !1,
                Jr.totalNodes = Jr.resolvedTargetDeltas = Jr.recalculatedProjection = 0,
                this.nodes.forEach(nP),
                this.nodes.forEach(aP),
                this.nodes.forEach(lP),
                this.nodes.forEach(rP),
                KT(Jr)
            }
            ,
            this.hasProjected = !1,
            this.isVisible = !0,
            this.animationProgress = 0,
            this.sharedNodes = new Map,
            this.latestValues = o,
            this.root = l ? l.root || l : this,
            this.path = l ? [...l.path, l] : [],
            this.parent = l,
            this.depth = l ? l.depth + 1 : 0;
            for (let a = 0; a < this.path.length; a++)
                this.path[a].shouldResetTransform = !0;
            this.root === this && (this.nodes = new qT)
        }
        addEventListener(o, l) {
            return this.eventHandlers.has(o) || this.eventHandlers.set(o, new Xp),
            this.eventHandlers.get(o).add(l)
        }
        notifyListeners(o, ...l) {
            const a = this.eventHandlers.get(o);
            a && a.notify(...l)
        }
        hasListeners(o) {
            return this.eventHandlers.has(o)
        }
        mount(o, l=this.root.hasTreeAnimated) {
            if (this.instance)
                return;
            this.isSVG = XT(o),
            this.instance = o;
            const {layoutId: a, layout: c, visualElement: d} = this.options;
            if (d && !d.current && d.mount(o),
            this.root.nodes.add(this),
            this.parent && this.parent.children.add(this),
            l && (c || a) && (this.isLayoutDirty = !0),
            e) {
                let f;
                const p = () => this.root.updateBlockedByResize = !1;
                e(o, () => {
                    this.root.updateBlockedByResize = !0,
                    f && f(),
                    f = YT(p, 250),
                    al.hasAnimatedSinceResize && (al.hasAnimatedSinceResize = !1,
                    this.nodes.forEach(Gg))
                }
                )
            }
            a && this.root.registerSharedNode(a, this),
            this.options.animate !== !1 && d && (a || c) && this.addEventListener("didUpdate", ({delta: f, hasLayoutChanged: p, hasRelativeTargetChanged: h, layout: m}) => {
                if (this.isTreeAnimationBlocked()) {
                    this.target = void 0,
                    this.relativeTarget = void 0;
                    return
                }
                const _ = this.options.transition || d.getDefaultTransition() || pP
                  , {onLayoutAnimationStart: x, onLayoutAnimationComplete: v} = d.getProps()
                  , w = !this.targetLayout || !Lw(this.targetLayout, m) || h
                  , y = !p && h;
                if (this.options.layoutRoot || this.resumeFrom && this.resumeFrom.instance || y || p && (w || !this.currentAnimation)) {
                    this.resumeFrom && (this.resumingFrom = this.resumeFrom,
                    this.resumingFrom.resumingFrom = void 0),
                    this.setAnimationOrigin(f, y);
                    const b = {
                        ...Wp(_, "layout"),
                        onPlay: x,
                        onComplete: v
                    };
                    (d.shouldReduceMotion || this.options.layoutRoot) && (b.delay = 0,
                    b.type = !1),
                    this.startAnimation(b)
                } else
                    p || Gg(this),
                    this.isLead() && this.options.onExitComplete && this.options.onExitComplete();
                this.targetLayout = m
            }
            )
        }
        unmount() {
            this.options.layoutId && this.willUpdate(),
            this.root.nodes.remove(this);
            const o = this.getStack();
            o && o.remove(this),
            this.parent && this.parent.children.delete(this),
            this.instance = void 0,
            tr(this.updateProjection)
        }
        blockUpdate() {
            this.updateManuallyBlocked = !0
        }
        unblockUpdate() {
            this.updateManuallyBlocked = !1
        }
        isUpdateBlocked() {
            return this.updateManuallyBlocked || this.updateBlockedByResize
        }
        isTreeAnimationBlocked() {
            return this.isAnimationBlocked || this.parent && this.parent.isTreeAnimationBlocked() || !1
        }
        startUpdate() {
            this.isUpdateBlocked() || (this.isUpdating = !0,
            this.nodes && this.nodes.forEach(cP),
            this.animationId++)
        }
        getTransformTemplate() {
            const {visualElement: o} = this.options;
            return o && o.getProps().transformTemplate
        }
        willUpdate(o=!0) {
            if (this.root.hasTreeAnimated = !0,
            this.root.isUpdateBlocked()) {
                this.options.onExitComplete && this.options.onExitComplete();
                return
            }
            if (!this.root.isUpdating && this.root.startUpdate(),
            this.isLayoutDirty)
                return;
            this.isLayoutDirty = !0;
            for (let d = 0; d < this.path.length; d++) {
                const f = this.path[d];
                f.shouldResetTransform = !0,
                f.updateScroll("snapshot"),
                f.options.layoutRoot && f.willUpdate(!1)
            }
            const {layoutId: l, layout: a} = this.options;
            if (l === void 0 && !a)
                return;
            const c = this.getTransformTemplate();
            this.prevTransformTemplateValue = c ? c(this.latestValues, "") : void 0,
            this.updateSnapshot(),
            o && this.notifyListeners("willUpdate")
        }
        update() {
            if (this.updateScheduled = !1,
            this.isUpdateBlocked()) {
                this.unblockUpdate(),
                this.clearAllSnapshots(),
                this.nodes.forEach(Hg);
                return
            }
            this.isUpdating || this.nodes.forEach(sP),
            this.isUpdating = !1,
            this.nodes.forEach(oP),
            this.nodes.forEach(eP),
            this.nodes.forEach(tP),
            this.clearAllSnapshots();
            const l = performance.now();
            gt.delta = Dr(0, 1e3 / 60, l - gt.timestamp),
            gt.timestamp = l,
            gt.isProcessing = !0,
            vu.update.process(gt),
            vu.preRender.process(gt),
            vu.render.process(gt),
            gt.isProcessing = !1
        }
        didUpdate() {
            this.updateScheduled || (this.updateScheduled = !0,
            queueMicrotask( () => this.update()))
        }
        clearAllSnapshots() {
            this.nodes.forEach(iP),
            this.sharedNodes.forEach(uP)
        }
        scheduleUpdateProjection() {
            this.projectionUpdateScheduled || (this.projectionUpdateScheduled = !0,
            Ne.preRender(this.updateProjection, !1, !0))
        }
        scheduleCheckAfterUnmount() {
            Ne.postRender( () => {
                this.isLayoutDirty ? this.root.didUpdate() : this.root.checkUpdateFailed()
            }
            )
        }
        updateSnapshot() {
            this.snapshot || !this.instance || (this.snapshot = this.measure())
        }
        updateLayout() {
            if (!this.instance || (this.updateScroll(),
            !(this.options.alwaysMeasureLayout && this.isLead()) && !this.isLayoutDirty))
                return;
            if (this.resumeFrom && !this.resumeFrom.instance)
                for (let a = 0; a < this.path.length; a++)
                    this.path[a].updateScroll();
            const o = this.layout;
            this.layout = this.measure(!1),
            this.layoutCorrected = Ze(),
            this.isLayoutDirty = !1,
            this.projectionDelta = void 0,
            this.notifyListeners("measure", this.layout.layoutBox);
            const {visualElement: l} = this.options;
            l && l.notify("LayoutMeasure", this.layout.layoutBox, o ? o.layoutBox : void 0)
        }
        updateScroll(o="measure") {
            let l = !!(this.options.layoutScroll && this.instance);
            this.scroll && this.scroll.animationId === this.root.animationId && this.scroll.phase === o && (l = !1),
            l && (this.scroll = {
                animationId: this.root.animationId,
                phase: o,
                isRoot: r(this.instance),
                offset: n(this.instance)
            })
        }
        resetTransform() {
            if (!i)
                return;
            const o = this.isLayoutDirty || this.shouldResetTransform
              , l = this.projectionDelta && !Iw(this.projectionDelta)
              , a = this.getTransformTemplate()
              , c = a ? a(this.latestValues, "") : void 0
              , d = c !== this.prevTransformTemplateValue;
            o && (l || Qr(this.latestValues) || d) && (i(this.instance, c),
            this.shouldResetTransform = !1,
            this.scheduleRender())
        }
        measure(o=!0) {
            const l = this.measurePageBox();
            let a = this.removeElementScroll(l);
            return o && (a = this.removeTransform(a)),
            mP(a),
            {
                animationId: this.root.animationId,
                measuredBox: l,
                layoutBox: a,
                latestValues: {},
                source: this.id
            }
        }
        measurePageBox() {
            const {visualElement: o} = this.options;
            if (!o)
                return Ze();
            const l = o.measureViewportBox()
              , {scroll: a} = this.root;
            return a && (_r(l.x, a.offset.x),
            _r(l.y, a.offset.y)),
            l
        }
        removeElementScroll(o) {
            const l = Ze();
            Jt(l, o);
            for (let a = 0; a < this.path.length; a++) {
                const c = this.path[a]
                  , {scroll: d, options: f} = c;
                if (c !== this.root && d && f.layoutScroll) {
                    if (d.isRoot) {
                        Jt(l, o);
                        const {scroll: p} = this.root;
                        p && (_r(l.x, -p.offset.x),
                        _r(l.y, -p.offset.y))
                    }
                    _r(l.x, d.offset.x),
                    _r(l.y, d.offset.y)
                }
            }
            return l
        }
        applyTransform(o, l=!1) {
            const a = Ze();
            Jt(a, o);
            for (let c = 0; c < this.path.length; c++) {
                const d = this.path[c];
                !l && d.options.layoutScroll && d.scroll && d !== d.root && Ji(a, {
                    x: -d.scroll.offset.x,
                    y: -d.scroll.offset.y
                }),
                Qr(d.latestValues) && Ji(a, d.latestValues)
            }
            return Qr(this.latestValues) && Ji(a, this.latestValues),
            a
        }
        removeTransform(o) {
            const l = Ze();
            Jt(l, o);
            for (let a = 0; a < this.path.length; a++) {
                const c = this.path[a];
                if (!c.instance || !Qr(c.latestValues))
                    continue;
                tf(c.latestValues) && c.updateSnapshot();
                const d = Ze()
                  , f = c.measurePageBox();
                Jt(d, f),
                $g(l, c.latestValues, c.snapshot ? c.snapshot.layoutBox : void 0, d)
            }
            return Qr(this.latestValues) && $g(l, this.latestValues),
            l
        }
        setTargetDelta(o) {
            this.targetDelta = o,
            this.root.scheduleUpdateProjection(),
            this.isProjectionDirty = !0
        }
        setOptions(o) {
            this.options = {
                ...this.options,
                ...o,
                crossfade: o.crossfade !== void 0 ? o.crossfade : !0
            }
        }
        clearMeasurements() {
            this.scroll = void 0,
            this.layout = void 0,
            this.snapshot = void 0,
            this.prevTransformTemplateValue = void 0,
            this.targetDelta = void 0,
            this.target = void 0,
            this.isLayoutDirty = !1
        }
        forceRelativeParentToResolveTarget() {
            this.relativeParent && this.relativeParent.resolvedRelativeTargetAt !== gt.timestamp && this.relativeParent.resolveTargetDelta(!0)
        }
        resolveTargetDelta(o=!1) {
            var l;
            const a = this.getLead();
            this.isProjectionDirty || (this.isProjectionDirty = a.isProjectionDirty),
            this.isTransformDirty || (this.isTransformDirty = a.isTransformDirty),
            this.isSharedProjectionDirty || (this.isSharedProjectionDirty = a.isSharedProjectionDirty);
            const c = !!this.resumingFrom || this !== a;
            if (!(o || c && this.isSharedProjectionDirty || this.isProjectionDirty || !((l = this.parent) === null || l === void 0) && l.isProjectionDirty || this.attemptToResolveRelativeTarget))
                return;
            const {layout: f, layoutId: p} = this.options;
            if (!(!this.layout || !(f || p))) {
                if (this.resolvedRelativeTargetAt = gt.timestamp,
                !this.targetDelta && !this.relativeTarget) {
                    const h = this.getClosestProjectingParent();
                    h && h.layout && this.animationProgress !== 1 ? (this.relativeParent = h,
                    this.forceRelativeParentToResolveTarget(),
                    this.relativeTarget = Ze(),
                    this.relativeTargetOrigin = Ze(),
                    po(this.relativeTargetOrigin, this.layout.layoutBox, h.layout.layoutBox),
                    Jt(this.relativeTarget, this.relativeTargetOrigin)) : this.relativeParent = this.relativeTarget = void 0
                }
                if (!(!this.relativeTarget && !this.targetDelta)) {
                    if (this.target || (this.target = Ze(),
                    this.targetWithTransforms = Ze()),
                    this.relativeTarget && this.relativeTargetOrigin && this.relativeParent && this.relativeParent.target ? (this.forceRelativeParentToResolveTarget(),
                    hT(this.target, this.relativeTarget, this.relativeParent.target)) : this.targetDelta ? (this.resumingFrom ? this.target = this.applyTransform(this.layout.layoutBox) : Jt(this.target, this.layout.layoutBox),
                    Ew(this.target, this.targetDelta)) : Jt(this.target, this.layout.layoutBox),
                    this.attemptToResolveRelativeTarget) {
                        this.attemptToResolveRelativeTarget = !1;
                        const h = this.getClosestProjectingParent();
                        h && !!h.resumingFrom == !!this.resumingFrom && !h.options.layoutScroll && h.target && this.animationProgress !== 1 ? (this.relativeParent = h,
                        this.forceRelativeParentToResolveTarget(),
                        this.relativeTarget = Ze(),
                        this.relativeTargetOrigin = Ze(),
                        po(this.relativeTargetOrigin, this.target, h.target),
                        Jt(this.relativeTarget, this.relativeTargetOrigin)) : this.relativeParent = this.relativeTarget = void 0
                    }
                    Jr.resolvedTargetDeltas++
                }
            }
        }
        getClosestProjectingParent() {
            if (!(!this.parent || tf(this.parent.latestValues) || kw(this.parent.latestValues)))
                return this.parent.isProjecting() ? this.parent : this.parent.getClosestProjectingParent()
        }
        isProjecting() {
            return !!((this.relativeTarget || this.targetDelta || this.options.layoutRoot) && this.layout)
        }
        calcProjection() {
            var o;
            const l = this.getLead()
              , a = !!this.resumingFrom || this !== l;
            let c = !0;
            if ((this.isProjectionDirty || !((o = this.parent) === null || o === void 0) && o.isProjectionDirty) && (c = !1),
            a && (this.isSharedProjectionDirty || this.isTransformDirty) && (c = !1),
            this.resolvedRelativeTargetAt === gt.timestamp && (c = !1),
            c)
                return;
            const {layout: d, layoutId: f} = this.options;
            if (this.isTreeAnimating = !!(this.parent && this.parent.isTreeAnimating || this.currentAnimation || this.pendingAnimation),
            this.isTreeAnimating || (this.targetDelta = this.relativeTarget = void 0),
            !this.layout || !(d || f))
                return;
            Jt(this.layoutCorrected, this.layout.layoutBox);
            const p = this.treeScale.x
              , h = this.treeScale.y;
            CT(this.layoutCorrected, this.treeScale, this.path, a),
            l.layout && !l.target && (this.treeScale.x !== 1 || this.treeScale.y !== 1) && (l.target = l.layout.layoutBox);
            const {target: m} = l;
            if (!m) {
                this.projectionTransform && (this.projectionDelta = Qi(),
                this.projectionTransform = "none",
                this.scheduleRender());
                return
            }
            this.projectionDelta || (this.projectionDelta = Qi(),
            this.projectionDeltaWithTransform = Qi());
            const _ = this.projectionTransform;
            fo(this.projectionDelta, this.layoutCorrected, m, this.latestValues),
            this.projectionTransform = zg(this.projectionDelta, this.treeScale),
            (this.projectionTransform !== _ || this.treeScale.x !== p || this.treeScale.y !== h) && (this.hasProjected = !0,
            this.scheduleRender(),
            this.notifyListeners("projectionUpdate", m)),
            Jr.recalculatedProjection++
        }
        hide() {
            this.isVisible = !1
        }
        show() {
            this.isVisible = !0
        }
        scheduleRender(o=!0) {
            if (this.options.scheduleRender && this.options.scheduleRender(),
            o) {
                const l = this.getStack();
                l && l.scheduleRender()
            }
            this.resumingFrom && !this.resumingFrom.instance && (this.resumingFrom = void 0)
        }
        setAnimationOrigin(o, l=!1) {
            const a = this.snapshot
              , c = a ? a.latestValues : {}
              , d = {
                ...this.latestValues
            }
              , f = Qi();
            (!this.relativeParent || !this.relativeParent.options.layoutRoot) && (this.relativeTarget = this.relativeTargetOrigin = void 0),
            this.attemptToResolveRelativeTarget = !l;
            const p = Ze()
              , h = a ? a.source : void 0
              , m = this.layout ? this.layout.source : void 0
              , _ = h !== m
              , x = this.getStack()
              , v = !x || x.members.length <= 1
              , w = !!(_ && !v && this.options.crossfade === !0 && !this.path.some(fP));
            this.animationProgress = 0;
            let y;
            this.mixTargetDelta = b => {
                const C = b / 1e3;
                Wg(f.x, o.x, C),
                Wg(f.y, o.y, C),
                this.setTargetDelta(f),
                this.relativeTarget && this.relativeTargetOrigin && this.layout && this.relativeParent && this.relativeParent.layout && (po(p, this.layout.layoutBox, this.relativeParent.layout.layoutBox),
                dP(this.relativeTarget, this.relativeTargetOrigin, p, C),
                y && HT(this.relativeTarget, y) && (this.isProjectionDirty = !1),
                y || (y = Ze()),
                Jt(y, this.relativeTarget)),
                _ && (this.animationValues = d,
                $T(d, c, this.latestValues, C, w, v)),
                this.root.scheduleUpdateProjection(),
                this.scheduleRender(),
                this.animationProgress = C
            }
            ,
            this.mixTargetDelta(this.options.layoutRoot ? 1e3 : 0)
        }
        startAnimation(o) {
            this.notifyListeners("animationStart"),
            this.currentAnimation && this.currentAnimation.stop(),
            this.resumingFrom && this.resumingFrom.currentAnimation && this.resumingFrom.currentAnimation.stop(),
            this.pendingAnimation && (tr(this.pendingAnimation),
            this.pendingAnimation = void 0),
            this.pendingAnimation = Ne.update( () => {
                al.hasAnimatedSinceResize = !0,
                this.currentAnimation = QT(0, Ug, {
                    ...o,
                    onUpdate: l => {
                        this.mixTargetDelta(l),
                        o.onUpdate && o.onUpdate(l)
                    }
                    ,
                    onComplete: () => {
                        o.onComplete && o.onComplete(),
                        this.completeAnimation()
                    }
                }),
                this.resumingFrom && (this.resumingFrom.currentAnimation = this.currentAnimation),
                this.pendingAnimation = void 0
            }
            )
        }
        completeAnimation() {
            this.resumingFrom && (this.resumingFrom.currentAnimation = void 0,
            this.resumingFrom.preserveOpacity = void 0);
            const o = this.getStack();
            o && o.exitAnimationComplete(),
            this.resumingFrom = this.currentAnimation = this.animationValues = void 0,
            this.notifyListeners("animationComplete")
        }
        finishAnimation() {
            this.currentAnimation && (this.mixTargetDelta && this.mixTargetDelta(Ug),
            this.currentAnimation.stop()),
            this.completeAnimation()
        }
        applyTransformsToTarget() {
            const o = this.getLead();
            let {targetWithTransforms: l, target: a, layout: c, latestValues: d} = o;
            if (!(!l || !a || !c)) {
                if (this !== o && this.layout && c && Ow(this.options.animationType, this.layout.layoutBox, c.layoutBox)) {
                    a = this.target || Ze();
                    const f = qt(this.layout.layoutBox.x);
                    a.x.min = o.target.x.min,
                    a.x.max = a.x.min + f;
                    const p = qt(this.layout.layoutBox.y);
                    a.y.min = o.target.y.min,
                    a.y.max = a.y.min + p
                }
                Jt(l, a),
                Ji(l, d),
                fo(this.projectionDeltaWithTransform, this.layoutCorrected, l, d)
            }
        }
        registerSharedNode(o, l) {
            this.sharedNodes.has(o) || this.sharedNodes.set(o, new GT),
            this.sharedNodes.get(o).add(l);
            const c = l.options.initialPromotionConfig;
            l.promote({
                transition: c ? c.transition : void 0,
                preserveFollowOpacity: c && c.shouldPreserveFollowOpacity ? c.shouldPreserveFollowOpacity(l) : void 0
            })
        }
        isLead() {
            const o = this.getStack();
            return o ? o.lead === this : !0
        }
        getLead() {
            var o;
            const {layoutId: l} = this.options;
            return l ? ((o = this.getStack()) === null || o === void 0 ? void 0 : o.lead) || this : this
        }
        getPrevLead() {
            var o;
            const {layoutId: l} = this.options;
            return l ? (o = this.getStack()) === null || o === void 0 ? void 0 : o.prevLead : void 0
        }
        getStack() {
            const {layoutId: o} = this.options;
            if (o)
                return this.root.sharedNodes.get(o)
        }
        promote({needsReset: o, transition: l, preserveFollowOpacity: a}={}) {
            const c = this.getStack();
            c && c.promote(this, a),
            o && (this.projectionDelta = void 0,
            this.needsReset = !0),
            l && this.setOptions({
                transition: l
            })
        }
        relegate() {
            const o = this.getStack();
            return o ? o.relegate(this) : !1
        }
        resetRotation() {
            const {visualElement: o} = this.options;
            if (!o)
                return;
            let l = !1;
            const {latestValues: a} = o;
            if ((a.rotate || a.rotateX || a.rotateY || a.rotateZ) && (l = !0),
            !l)
                return;
            const c = {};
            for (let d = 0; d < Bg.length; d++) {
                const f = "rotate" + Bg[d];
                a[f] && (c[f] = a[f],
                o.setStaticValue(f, 0))
            }
            o.render();
            for (const d in c)
                o.setStaticValue(d, c[d]);
            o.scheduleRender()
        }
        getProjectionStyles(o) {
            var l, a;
            if (!this.instance || this.isSVG)
                return;
            if (!this.isVisible)
                return JT;
            const c = {
                visibility: ""
            }
              , d = this.getTransformTemplate();
            if (this.needsReset)
                return this.needsReset = !1,
                c.opacity = "",
                c.pointerEvents = ol(o == null ? void 0 : o.pointerEvents) || "",
                c.transform = d ? d(this.latestValues, "") : "none",
                c;
            const f = this.getLead();
            if (!this.projectionDelta || !this.layout || !f.target) {
                const _ = {};
                return this.options.layoutId && (_.opacity = this.latestValues.opacity !== void 0 ? this.latestValues.opacity : 1,
                _.pointerEvents = ol(o == null ? void 0 : o.pointerEvents) || ""),
                this.hasProjected && !Qr(this.latestValues) && (_.transform = d ? d({}, "") : "none",
                this.hasProjected = !1),
                _
            }
            const p = f.animationValues || f.latestValues;
            this.applyTransformsToTarget(),
            c.transform = zg(this.projectionDeltaWithTransform, this.treeScale, p),
            d && (c.transform = d(p, c.transform));
            const {x: h, y: m} = this.projectionDelta;
            c.transformOrigin = `${h.origin * 100}% ${m.origin * 100}% 0`,
            f.animationValues ? c.opacity = f === this ? (a = (l = p.opacity) !== null && l !== void 0 ? l : this.latestValues.opacity) !== null && a !== void 0 ? a : 1 : this.preserveOpacity ? this.latestValues.opacity : p.opacityExit : c.opacity = f === this ? p.opacity !== void 0 ? p.opacity : "" : p.opacityExit !== void 0 ? p.opacityExit : 0;
            for (const _ in $l) {
                if (p[_] === void 0)
                    continue;
                const {correct: x, applyTo: v} = $l[_]
                  , w = c.transform === "none" ? p[_] : x(p[_], f);
                if (v) {
                    const y = v.length;
                    for (let b = 0; b < y; b++)
                        c[v[b]] = w
                } else
                    c[_] = w
            }
            return this.options.layoutId && (c.pointerEvents = f === this ? ol(o == null ? void 0 : o.pointerEvents) || "" : "none"),
            c
        }
        clearSnapshot() {
            this.resumeFrom = this.snapshot = void 0
        }
        resetTree() {
            this.root.nodes.forEach(o => {
                var l;
                return (l = o.currentAnimation) === null || l === void 0 ? void 0 : l.stop()
            }
            ),
            this.root.nodes.forEach(Hg),
            this.root.sharedNodes.clear()
        }
    }
}
function eP(e) {
    e.updateLayout()
}
function tP(e) {
    var t;
    const n = ((t = e.resumeFrom) === null || t === void 0 ? void 0 : t.snapshot) || e.snapshot;
    if (e.isLead() && e.layout && n && e.hasListeners("didUpdate")) {
        const {layoutBox: r, measuredBox: i} = e.layout
          , {animationType: s} = e.options
          , o = n.source !== e.layout.source;
        s === "size" ? en(f => {
            const p = o ? n.measuredBox[f] : n.layoutBox[f]
              , h = qt(p);
            p.min = r[f].min,
            p.max = p.min + h
        }
        ) : Ow(s, n.layoutBox, r) && en(f => {
            const p = o ? n.measuredBox[f] : n.layoutBox[f]
              , h = qt(r[f]);
            p.max = p.min + h,
            e.relativeTarget && !e.currentAnimation && (e.isProjectionDirty = !0,
            e.relativeTarget[f].max = e.relativeTarget[f].min + h)
        }
        );
        const l = Qi();
        fo(l, r, n.layoutBox);
        const a = Qi();
        o ? fo(a, e.applyTransform(i, !0), n.measuredBox) : fo(a, r, n.layoutBox);
        const c = !Iw(l);
        let d = !1;
        if (!e.resumeFrom) {
            const f = e.getClosestProjectingParent();
            if (f && !f.resumeFrom) {
                const {snapshot: p, layout: h} = f;
                if (p && h) {
                    const m = Ze();
                    po(m, n.layoutBox, p.layoutBox);
                    const _ = Ze();
                    po(_, r, h.layoutBox),
                    Lw(m, _) || (d = !0),
                    f.options.layoutRoot && (e.relativeTarget = _,
                    e.relativeTargetOrigin = m,
                    e.relativeParent = f)
                }
            }
        }
        e.notifyListeners("didUpdate", {
            layout: r,
            snapshot: n,
            delta: a,
            layoutDelta: l,
            hasLayoutChanged: c,
            hasRelativeTargetChanged: d
        })
    } else if (e.isLead()) {
        const {onExitComplete: r} = e.options;
        r && r()
    }
    e.options.transition = void 0
}
function nP(e) {
    Jr.totalNodes++,
    e.parent && (e.isProjecting() || (e.isProjectionDirty = e.parent.isProjectionDirty),
    e.isSharedProjectionDirty || (e.isSharedProjectionDirty = !!(e.isProjectionDirty || e.parent.isProjectionDirty || e.parent.isSharedProjectionDirty)),
    e.isTransformDirty || (e.isTransformDirty = e.parent.isTransformDirty))
}
function rP(e) {
    e.isProjectionDirty = e.isSharedProjectionDirty = e.isTransformDirty = !1
}
function iP(e) {
    e.clearSnapshot()
}
function Hg(e) {
    e.clearMeasurements()
}
function sP(e) {
    e.isLayoutDirty = !1
}
function oP(e) {
    const {visualElement: t} = e.options;
    t && t.getProps().onBeforeLayoutMeasure && t.notify("BeforeLayoutMeasure"),
    e.resetTransform()
}
function Gg(e) {
    e.finishAnimation(),
    e.targetDelta = e.relativeTarget = e.target = void 0,
    e.isProjectionDirty = !0
}
function aP(e) {
    e.resolveTargetDelta()
}
function lP(e) {
    e.calcProjection()
}
function cP(e) {
    e.resetRotation()
}
function uP(e) {
    e.removeLeadSnapshot()
}
function Wg(e, t, n) {
    e.translate = Fe(t.translate, 0, n),
    e.scale = Fe(t.scale, 1, n),
    e.origin = t.origin,
    e.originPoint = t.originPoint
}
function qg(e, t, n, r) {
    e.min = Fe(t.min, n.min, r),
    e.max = Fe(t.max, n.max, r)
}
function dP(e, t, n, r) {
    qg(e.x, t.x, n.x, r),
    qg(e.y, t.y, n.y, r)
}
function fP(e) {
    return e.animationValues && e.animationValues.opacityExit !== void 0
}
const pP = {
    duration: .45,
    ease: [.4, 0, .1, 1]
}
  , Yg = e => typeof navigator < "u" && navigator.userAgent.toLowerCase().includes(e)
  , Kg = Yg("applewebkit/") && !Yg("chrome/") ? Math.round : We;
function Xg(e) {
    e.min = Kg(e.min),
    e.max = Kg(e.max)
}
function mP(e) {
    Xg(e.x),
    Xg(e.y)
}
function Ow(e, t, n) {
    return e === "position" || e === "preserve-aspect" && !Zd(Vg(t), Vg(n), .2)
}
const hP = Mw({
    attachResizeListener: (e, t) => Wn(e, "resize", t),
    measureScroll: () => ({
        x: document.documentElement.scrollLeft || document.body.scrollLeft,
        y: document.documentElement.scrollTop || document.body.scrollTop
    }),
    checkIsScrollRoot: () => !0
})
  , ju = {
    current: void 0
}
  , Aw = Mw({
    measureScroll: e => ({
        x: e.scrollLeft,
        y: e.scrollTop
    }),
    defaultParent: () => {
        if (!ju.current) {
            const e = new hP({});
            e.mount(window),
            e.setOptions({
                layoutScroll: !0
            }),
            ju.current = e
        }
        return ju.current
    }
    ,
    resetTransform: (e, t) => {
        e.style.transform = t !== void 0 ? t : "none"
    }
    ,
    checkIsScrollRoot: e => window.getComputedStyle(e).position === "fixed"
})
  , gP = {
    pan: {
        Feature: IT
    },
    drag: {
        Feature: RT,
        ProjectionNode: Aw,
        MeasureLayout: jw
    }
}
  , _P = /var\((--[a-zA-Z0-9-_]+),? ?([a-zA-Z0-9 ()%#.,-]+)?\)/;
function vP(e) {
    const t = _P.exec(e);
    if (!t)
        return [, ];
    const [,n,r] = t;
    return [n, r]
}
function rf(e, t, n=1) {
    const [r,i] = vP(e);
    if (!r)
        return;
    const s = window.getComputedStyle(t).getPropertyValue(r);
    if (s) {
        const o = s.trim();
        return vw(o) ? parseFloat(o) : o
    } else
        return Wd(i) ? rf(i, t, n + 1) : i
}
function yP(e, {...t}, n) {
    const r = e.current;
    if (!(r instanceof Element))
        return {
            target: t,
            transitionEnd: n
        };
    n && (n = {
        ...n
    }),
    e.values.forEach(i => {
        const s = i.get();
        if (!Wd(s))
            return;
        const o = rf(s, r);
        o && i.set(o)
    }
    );
    for (const i in t) {
        const s = t[i];
        if (!Wd(s))
            continue;
        const o = rf(s, r);
        o && (t[i] = o,
        n || (n = {}),
        n[i] === void 0 && (n[i] = s))
    }
    return {
        target: t,
        transitionEnd: n
    }
}
const wP = new Set(["width", "height", "top", "left", "right", "bottom", "x", "y", "translateX", "translateY"])
  , Dw = e => wP.has(e)
  , xP = e => Object.keys(e).some(Dw)
  , Qg = e => e === ki || e === te
  , Jg = (e, t) => parseFloat(e.split(", ")[t])
  , Zg = (e, t) => (n, {transform: r}) => {
    if (r === "none" || !r)
        return 0;
    const i = r.match(/^matrix3d\((.+)\)$/);
    if (i)
        return Jg(i[1], t);
    {
        const s = r.match(/^matrix\((.+)\)$/);
        return s ? Jg(s[1], e) : 0
    }
}
  , bP = new Set(["x", "y", "z"])
  , SP = ta.filter(e => !bP.has(e));
function CP(e) {
    const t = [];
    return SP.forEach(n => {
        const r = e.getValue(n);
        r !== void 0 && (t.push([n, r.get()]),
        r.set(n.startsWith("scale") ? 1 : 0))
    }
    ),
    t.length && e.render(),
    t
}
const gs = {
    width: ({x: e}, {paddingLeft: t="0", paddingRight: n="0"}) => e.max - e.min - parseFloat(t) - parseFloat(n),
    height: ({y: e}, {paddingTop: t="0", paddingBottom: n="0"}) => e.max - e.min - parseFloat(t) - parseFloat(n),
    top: (e, {top: t}) => parseFloat(t),
    left: (e, {left: t}) => parseFloat(t),
    bottom: ({y: e}, {top: t}) => parseFloat(t) + (e.max - e.min),
    right: ({x: e}, {left: t}) => parseFloat(t) + (e.max - e.min),
    x: Zg(4, 13),
    y: Zg(5, 14)
};
gs.translateX = gs.x;
gs.translateY = gs.y;
const kP = (e, t, n) => {
    const r = t.measureViewportBox()
      , i = t.current
      , s = getComputedStyle(i)
      , {display: o} = s
      , l = {};
    o === "none" && t.setStaticValue("display", e.display || "block"),
    n.forEach(c => {
        l[c] = gs[c](r, s)
    }
    ),
    t.render();
    const a = t.measureViewportBox();
    return n.forEach(c => {
        const d = t.getValue(c);
        d && d.jump(l[c]),
        e[c] = gs[c](a, s)
    }
    ),
    e
}
  , EP = (e, t, n={}, r={}) => {
    t = {
        ...t
    },
    r = {
        ...r
    };
    const i = Object.keys(t).filter(Dw);
    let s = []
      , o = !1;
    const l = [];
    if (i.forEach(a => {
        const c = e.getValue(a);
        if (!e.hasValue(a))
            return;
        let d = n[a]
          , f = Fs(d);
        const p = t[a];
        let h;
        if (Vl(p)) {
            const m = p.length
              , _ = p[0] === null ? 1 : 0;
            d = p[_],
            f = Fs(d);
            for (let x = _; x < m && p[x] !== null; x++)
                h ? zp(Fs(p[x]) === h) : h = Fs(p[x])
        } else
            h = Fs(p);
        if (f !== h)
            if (Qg(f) && Qg(h)) {
                const m = c.get();
                typeof m == "string" && c.set(parseFloat(m)),
                typeof p == "string" ? t[a] = parseFloat(p) : Array.isArray(p) && h === te && (t[a] = p.map(parseFloat))
            } else
                f != null && f.transform && (h != null && h.transform) && (d === 0 || p === 0) ? d === 0 ? c.set(h.transform(d)) : t[a] = f.transform(p) : (o || (s = CP(e),
                o = !0),
                l.push(a),
                r[a] = r[a] !== void 0 ? r[a] : t[a],
                c.jump(p))
    }
    ),
    l.length) {
        const a = l.indexOf("height") >= 0 ? window.pageYOffset : null
          , c = kP(t, e, l);
        return s.length && s.forEach( ([d,f]) => {
            e.getValue(d).set(f)
        }
        ),
        e.render(),
        xc && a !== null && window.scrollTo({
            top: a
        }),
        {
            target: c,
            transitionEnd: r
        }
    } else
        return {
            target: t,
            transitionEnd: r
        }
}
;
function TP(e, t, n, r) {
    return xP(t) ? EP(e, t, n, r) : {
        target: t,
        transitionEnd: r
    }
}
const PP = (e, t, n, r) => {
    const i = yP(e, t, r);
    return t = i.target,
    r = i.transitionEnd,
    TP(e, t, n, r)
}
  , sf = {
    current: null
}
  , $w = {
    current: !1
};
function jP() {
    if ($w.current = !0,
    !!xc)
        if (window.matchMedia) {
            const e = window.matchMedia("(prefers-reduced-motion)")
              , t = () => sf.current = e.matches;
            e.addListener(t),
            t()
        } else
            sf.current = !1
}
function NP(e, t, n) {
    const {willChange: r} = t;
    for (const i in t) {
        const s = t[i]
          , o = n[i];
        if (Lt(s))
            e.addValue(i, s),
            Hl(r) && r.add(i);
        else if (Lt(o))
            e.addValue(i, hs(s, {
                owner: e
            })),
            Hl(r) && r.remove(i);
        else if (o !== s)
            if (e.hasValue(i)) {
                const l = e.getValue(i);
                !l.hasAnimated && l.set(s)
            } else {
                const l = e.getStaticValue(i);
                e.addValue(i, hs(l !== void 0 ? l : s, {
                    owner: e
                }))
            }
    }
    for (const i in n)
        t[i] === void 0 && e.removeValue(i);
    return t
}
const e_ = new WeakMap
  , Fw = Object.keys(Fo)
  , RP = Fw.length
  , t_ = ["AnimationStart", "AnimationComplete", "Update", "BeforeLayoutMeasure", "LayoutMeasure", "LayoutAnimationStart", "LayoutAnimationComplete"]
  , IP = Ip.length;
class LP {
    constructor({parent: t, props: n, presenceContext: r, reducedMotionConfig: i, visualState: s}, o={}) {
        this.current = null,
        this.children = new Set,
        this.isVariantNode = !1,
        this.isControllingVariants = !1,
        this.shouldReduceMotion = null,
        this.values = new Map,
        this.features = {},
        this.valueSubscriptions = new Map,
        this.prevMotionValues = {},
        this.events = {},
        this.propEventSubscriptions = {},
        this.notifyUpdate = () => this.notify("Update", this.latestValues),
        this.render = () => {
            this.current && (this.triggerBuild(),
            this.renderInstance(this.current, this.renderState, this.props.style, this.projection))
        }
        ,
        this.scheduleRender = () => Ne.render(this.render, !1, !0);
        const {latestValues: l, renderState: a} = s;
        this.latestValues = l,
        this.baseTarget = {
            ...l
        },
        this.initialValues = n.initial ? {
            ...l
        } : {},
        this.renderState = a,
        this.parent = t,
        this.props = n,
        this.presenceContext = r,
        this.depth = t ? t.depth + 1 : 0,
        this.reducedMotionConfig = i,
        this.options = o,
        this.isControllingVariants = Sc(n),
        this.isVariantNode = Sy(n),
        this.isVariantNode && (this.variantChildren = new Set),
        this.manuallyAnimateOnMount = !!(t && t.current);
        const {willChange: c, ...d} = this.scrapeMotionValuesFromProps(n, {});
        for (const f in d) {
            const p = d[f];
            l[f] !== void 0 && Lt(p) && (p.set(l[f], !1),
            Hl(c) && c.add(f))
        }
    }
    scrapeMotionValuesFromProps(t, n) {
        return {}
    }
    mount(t) {
        this.current = t,
        e_.set(t, this),
        this.projection && !this.projection.instance && this.projection.mount(t),
        this.parent && this.isVariantNode && !this.isControllingVariants && (this.removeFromVariantTree = this.parent.addVariantChild(this)),
        this.values.forEach( (n, r) => this.bindToMotionValue(r, n)),
        $w.current || jP(),
        this.shouldReduceMotion = this.reducedMotionConfig === "never" ? !1 : this.reducedMotionConfig === "always" ? !0 : sf.current,
        this.parent && this.parent.children.add(this),
        this.update(this.props, this.presenceContext)
    }
    unmount() {
        e_.delete(this.current),
        this.projection && this.projection.unmount(),
        tr(this.notifyUpdate),
        tr(this.render),
        this.valueSubscriptions.forEach(t => t()),
        this.removeFromVariantTree && this.removeFromVariantTree(),
        this.parent && this.parent.children.delete(this);
        for (const t in this.events)
            this.events[t].clear();
        for (const t in this.features)
            this.features[t].unmount();
        this.current = null
    }
    bindToMotionValue(t, n) {
        const r = Ci.has(t)
          , i = n.on("change", o => {
            this.latestValues[t] = o,
            this.props.onUpdate && Ne.update(this.notifyUpdate, !1, !0),
            r && this.projection && (this.projection.isTransformDirty = !0)
        }
        )
          , s = n.on("renderRequest", this.scheduleRender);
        this.valueSubscriptions.set(t, () => {
            i(),
            s()
        }
        )
    }
    sortNodePosition(t) {
        return !this.current || !this.sortInstanceNodePosition || this.type !== t.type ? 0 : this.sortInstanceNodePosition(this.current, t.current)
    }
    loadFeatures({children: t, ...n}, r, i, s) {
        let o, l;
        for (let a = 0; a < RP; a++) {
            const c = Fw[a]
              , {isEnabled: d, Feature: f, ProjectionNode: p, MeasureLayout: h} = Fo[c];
            p && (o = p),
            d(n) && (!this.features[c] && f && (this.features[c] = new f(this)),
            h && (l = h))
        }
        if ((this.type === "html" || this.type === "svg") && !this.projection && o) {
            this.projection = new o(this.latestValues,this.parent && this.parent.projection);
            const {layoutId: a, layout: c, drag: d, dragConstraints: f, layoutScroll: p, layoutRoot: h} = n;
            this.projection.setOptions({
                layoutId: a,
                layout: c,
                alwaysMeasureLayout: !!d || f && Ki(f),
                visualElement: this,
                scheduleRender: () => this.scheduleRender(),
                animationType: typeof c == "string" ? c : "both",
                initialPromotionConfig: s,
                layoutScroll: p,
                layoutRoot: h
            })
        }
        return l
    }
    updateFeatures() {
        for (const t in this.features) {
            const n = this.features[t];
            n.isMounted ? n.update() : (n.mount(),
            n.isMounted = !0)
        }
    }
    triggerBuild() {
        this.build(this.renderState, this.latestValues, this.options, this.props)
    }
    measureViewportBox() {
        return this.current ? this.measureInstanceViewportBox(this.current, this.props) : Ze()
    }
    getStaticValue(t) {
        return this.latestValues[t]
    }
    setStaticValue(t, n) {
        this.latestValues[t] = n
    }
    makeTargetAnimatable(t, n=!0) {
        return this.makeTargetAnimatableFromInstance(t, this.props, n)
    }
    update(t, n) {
        (t.transformTemplate || this.props.transformTemplate) && this.scheduleRender(),
        this.prevProps = this.props,
        this.props = t,
        this.prevPresenceContext = this.presenceContext,
        this.presenceContext = n;
        for (let r = 0; r < t_.length; r++) {
            const i = t_[r];
            this.propEventSubscriptions[i] && (this.propEventSubscriptions[i](),
            delete this.propEventSubscriptions[i]);
            const s = t["on" + i];
            s && (this.propEventSubscriptions[i] = this.on(i, s))
        }
        this.prevMotionValues = NP(this, this.scrapeMotionValuesFromProps(t, this.prevProps), this.prevMotionValues),
        this.handleChildMotionValue && this.handleChildMotionValue()
    }
    getProps() {
        return this.props
    }
    getVariant(t) {
        return this.props.variants ? this.props.variants[t] : void 0
    }
    getDefaultTransition() {
        return this.props.transition
    }
    getTransformPagePoint() {
        return this.props.transformPagePoint
    }
    getClosestVariantNode() {
        return this.isVariantNode ? this : this.parent ? this.parent.getClosestVariantNode() : void 0
    }
    getVariantContext(t=!1) {
        if (t)
            return this.parent ? this.parent.getVariantContext() : void 0;
        if (!this.isControllingVariants) {
            const r = this.parent ? this.parent.getVariantContext() || {} : {};
            return this.props.initial !== void 0 && (r.initial = this.props.initial),
            r
        }
        const n = {};
        for (let r = 0; r < IP; r++) {
            const i = Ip[r]
              , s = this.props[i];
            ($o(s) || s === !1) && (n[i] = s)
        }
        return n
    }
    addVariantChild(t) {
        const n = this.getClosestVariantNode();
        if (n)
            return n.variantChildren && n.variantChildren.add(t),
            () => n.variantChildren.delete(t)
    }
    addValue(t, n) {
        n !== this.values.get(t) && (this.removeValue(t),
        this.bindToMotionValue(t, n)),
        this.values.set(t, n),
        this.latestValues[t] = n.get()
    }
    removeValue(t) {
        this.values.delete(t);
        const n = this.valueSubscriptions.get(t);
        n && (n(),
        this.valueSubscriptions.delete(t)),
        delete this.latestValues[t],
        this.removeValueFromRenderState(t, this.renderState)
    }
    hasValue(t) {
        return this.values.has(t)
    }
    getValue(t, n) {
        if (this.props.values && this.props.values[t])
            return this.props.values[t];
        let r = this.values.get(t);
        return r === void 0 && n !== void 0 && (r = hs(n, {
            owner: this
        }),
        this.addValue(t, r)),
        r
    }
    readValue(t) {
        var n;
        return this.latestValues[t] !== void 0 || !this.current ? this.latestValues[t] : (n = this.getBaseTargetFromProps(this.props, t)) !== null && n !== void 0 ? n : this.readValueFromInstance(this.current, t, this.options)
    }
    setBaseTarget(t, n) {
        this.baseTarget[t] = n
    }
    getBaseTarget(t) {
        var n;
        const {initial: r} = this.props
          , i = typeof r == "string" || typeof r == "object" ? (n = Vp(this.props, r)) === null || n === void 0 ? void 0 : n[t] : void 0;
        if (r && i !== void 0)
            return i;
        const s = this.getBaseTargetFromProps(this.props, t);
        return s !== void 0 && !Lt(s) ? s : this.initialValues[t] !== void 0 && i === void 0 ? void 0 : this.baseTarget[t]
    }
    on(t, n) {
        return this.events[t] || (this.events[t] = new Xp),
        this.events[t].add(n)
    }
    notify(t, ...n) {
        this.events[t] && this.events[t].notify(...n)
    }
}
class Vw extends LP {
    sortInstanceNodePosition(t, n) {
        return t.compareDocumentPosition(n) & 2 ? 1 : -1
    }
    getBaseTargetFromProps(t, n) {
        return t.style ? t.style[n] : void 0
    }
    removeValueFromRenderState(t, {vars: n, style: r}) {
        delete n[t],
        delete r[t]
    }
    makeTargetAnimatableFromInstance({transition: t, transitionEnd: n, ...r}, {transformValues: i}, s) {
        let o = XE(r, t || {}, this);
        if (i && (n && (n = i(n)),
        r && (r = i(r)),
        o && (o = i(o))),
        s) {
            YE(this, r, o);
            const l = PP(this, r, o, n);
            n = l.transitionEnd,
            r = l.target
        }
        return {
            transition: t,
            transitionEnd: n,
            ...r
        }
    }
}
function MP(e) {
    return window.getComputedStyle(e)
}
class OP extends Vw {
    constructor() {
        super(...arguments),
        this.type = "html"
    }
    readValueFromInstance(t, n) {
        if (Ci.has(n)) {
            const r = Gp(n);
            return r && r.default || 0
        } else {
            const r = MP(t)
              , i = (Ty(n) ? r.getPropertyValue(n) : r[n]) || 0;
            return typeof i == "string" ? i.trim() : i
        }
    }
    measureInstanceViewportBox(t, {transformPagePoint: n}) {
        return Tw(t, n)
    }
    build(t, n, r, i) {
        Op(t, n, r, i.transformTemplate)
    }
    scrapeMotionValuesFromProps(t, n) {
        return Fp(t, n)
    }
    handleChildMotionValue() {
        this.childSubscription && (this.childSubscription(),
        delete this.childSubscription);
        const {children: t} = this.props;
        Lt(t) && (this.childSubscription = t.on("change", n => {
            this.current && (this.current.textContent = `${n}`)
        }
        ))
    }
    renderInstance(t, n, r, i) {
        Ly(t, n, r, i)
    }
}
class AP extends Vw {
    constructor() {
        super(...arguments),
        this.type = "svg",
        this.isSVGTag = !1
    }
    getBaseTargetFromProps(t, n) {
        return t[n]
    }
    readValueFromInstance(t, n) {
        if (Ci.has(n)) {
            const r = Gp(n);
            return r && r.default || 0
        }
        return n = My.has(n) ? n : Np(n),
        t.getAttribute(n)
    }
    measureInstanceViewportBox() {
        return Ze()
    }
    scrapeMotionValuesFromProps(t, n) {
        return Ay(t, n)
    }
    build(t, n, r, i) {
        Dp(t, n, r, this.isSVGTag, i.transformTemplate)
    }
    renderInstance(t, n, r, i) {
        Oy(t, n, r, i)
    }
    mount(t) {
        this.isSVGTag = $p(t.tagName),
        super.mount(t)
    }
}
const DP = (e, t) => Mp(e) ? new AP(t,{
    enableHardwareAcceleration: !1
}) : new OP(t,{
    enableHardwareAcceleration: !0
})
  , $P = {
    layout: {
        ProjectionNode: Aw,
        MeasureLayout: jw
    }
}
  , FP = {
    ...dT,
    ...Rk,
    ...gP,
    ...$P
}
  , VP = VC( (e, t) => vk(e, t, FP, DP));
function zw() {
    const e = g.useRef(!1);
    return jp( () => (e.current = !0,
    () => {
        e.current = !1
    }
    ), []),
    e
}
function zP() {
    const e = zw()
      , [t,n] = g.useState(0)
      , r = g.useCallback( () => {
        e.current && n(t + 1)
    }
    , [t]);
    return [g.useCallback( () => Ne.postRender(r), [r]), t]
}
class BP extends g.Component {
    getSnapshotBeforeUpdate(t) {
        const n = this.props.childRef.current;
        if (n && t.isPresent && !this.props.isPresent) {
            const r = this.props.sizeRef.current;
            r.height = n.offsetHeight || 0,
            r.width = n.offsetWidth || 0,
            r.top = n.offsetTop,
            r.left = n.offsetLeft
        }
        return null
    }
    componentDidUpdate() {}
    render() {
        return this.props.children
    }
}
function UP({children: e, isPresent: t}) {
    const n = g.useId()
      , r = g.useRef(null)
      , i = g.useRef({
        width: 0,
        height: 0,
        top: 0,
        left: 0
    });
    return g.useInsertionEffect( () => {
        const {width: s, height: o, top: l, left: a} = i.current;
        if (t || !r.current || !s || !o)
            return;
        r.current.dataset.motionPopId = n;
        const c = document.createElement("style");
        return document.head.appendChild(c),
        c.sheet && c.sheet.insertRule(`
          [data-motion-pop-id="${n}"] {
            position: absolute !important;
            width: ${s}px !important;
            height: ${o}px !important;
            top: ${l}px !important;
            left: ${a}px !important;
          }
        `),
        () => {
            document.head.removeChild(c)
        }
    }
    , [t]),
    g.createElement(BP, {
        isPresent: t,
        childRef: r,
        sizeRef: i
    }, g.cloneElement(e, {
        ref: r
    }))
}
const Nu = ({children: e, initial: t, isPresent: n, onExitComplete: r, custom: i, presenceAffectsLayout: s, mode: o}) => {
    const l = Dy(HP)
      , a = g.useId()
      , c = g.useMemo( () => ({
        id: a,
        initial: t,
        isPresent: n,
        custom: i,
        onExitComplete: d => {
            l.set(d, !0);
            for (const f of l.values())
                if (!f)
                    return;
            r && r()
        }
        ,
        register: d => (l.set(d, !1),
        () => l.delete(d))
    }), s ? void 0 : [n]);
    return g.useMemo( () => {
        l.forEach( (d, f) => l.set(f, !1))
    }
    , [n]),
    g.useEffect( () => {
        !n && !l.size && r && r()
    }
    , [n]),
    o === "popLayout" && (e = g.createElement(UP, {
        isPresent: n
    }, e)),
    g.createElement(wc.Provider, {
        value: c
    }, e)
}
;
function HP() {
    return new Map
}
function GP(e) {
    return g.useEffect( () => () => e(), [])
}
const Zr = e => e.key || "";
function WP(e, t) {
    e.forEach(n => {
        const r = Zr(n);
        t.set(r, n)
    }
    )
}
function qP(e) {
    const t = [];
    return g.Children.forEach(e, n => {
        g.isValidElement(n) && t.push(n)
    }
    ),
    t
}
const YP = ({children: e, custom: t, initial: n=!0, onExitComplete: r, exitBeforeEnter: i, presenceAffectsLayout: s=!0, mode: o="sync"}) => {
    const l = g.useContext(Lp).forceRender || zP()[0]
      , a = zw()
      , c = qP(e);
    let d = c;
    const f = g.useRef(new Map).current
      , p = g.useRef(d)
      , h = g.useRef(new Map).current
      , m = g.useRef(!0);
    if (jp( () => {
        m.current = !1,
        WP(c, h),
        p.current = d
    }
    ),
    GP( () => {
        m.current = !0,
        h.clear(),
        f.clear()
    }
    ),
    m.current)
        return g.createElement(g.Fragment, null, d.map(w => g.createElement(Nu, {
            key: Zr(w),
            isPresent: !0,
            initial: n ? void 0 : !1,
            presenceAffectsLayout: s,
            mode: o
        }, w)));
    d = [...d];
    const _ = p.current.map(Zr)
      , x = c.map(Zr)
      , v = _.length;
    for (let w = 0; w < v; w++) {
        const y = _[w];
        x.indexOf(y) === -1 && !f.has(y) && f.set(y, void 0)
    }
    return o === "wait" && f.size && (d = []),
    f.forEach( (w, y) => {
        if (x.indexOf(y) !== -1)
            return;
        const b = h.get(y);
        if (!b)
            return;
        const C = _.indexOf(y);
        let S = w;
        if (!S) {
            const T = () => {
                f.delete(y);
                const k = Array.from(h.keys()).filter(P => !x.includes(P));
                if (k.forEach(P => h.delete(P)),
                p.current = c.filter(P => {
                    const R = Zr(P);
                    return R === y || k.includes(R)
                }
                ),
                !f.size) {
                    if (a.current === !1)
                        return;
                    l(),
                    r && r()
                }
            }
            ;
            S = g.createElement(Nu, {
                key: Zr(b),
                isPresent: !1,
                onExitComplete: T,
                custom: t,
                presenceAffectsLayout: s,
                mode: o
            }, b),
            f.set(y, S)
        }
        d.splice(C, 0, S)
    }
    ),
    d = d.map(w => {
        const y = w.key;
        return f.has(y) ? w : g.createElement(Nu, {
            key: Zr(w),
            isPresent: !0,
            presenceAffectsLayout: s,
            mode: o
        }, w)
    }
    ),
    g.createElement(g.Fragment, null, f.size ? d : d.map(w => g.cloneElement(w)))
}
  , sa = ({children: e, ...t}) => {
    const n = {
        initial: {
            opacity: 0
        },
        in: {
            opacity: 1
        },
        out: {
            opacity: 0
        }
    };
    return u.jsx(VP.div, {
        initial: "initial",
        animate: "in",
        exit: "out",
        transition: {
            duration: 1
        },
        variants: n,
        ...t,
        children: e
    })
}
  , KP = () => {
    const e = Pp()
      , [t,n] = g.useState("")
      , r = g.useRef(null)
      , i = g.useRef(null)
      , s = g.useCallback(a => t !== "" && t === a, [t]);
    yy({
        start: 4.1,
        end: 9.1,
        ref: r
    });
    const o = g.useMemo( () => [{
        id: "illusion_info",
        title: Fn.ILLUSION_INFO,
        routerPath: "/231108/tab/illusion/info",
        styleClass: Vn.router_icon_illusion_info
    }, {
        id: "illusion_benefit",
        title: Fn.ILLUSION_BENEFIT,
        routerPath: "/231108/tab/illusion/benefit",
        styleClass: Vn.router_icon_illusion_benefit
    }, {
        id: "live_benefit",
        title: Fn.LIVE_BENEFIT,
        routerPath: "/231108/tab/live/benefit",
        styleClass: Vn.router_icon_live_benefit
    }, {
        id: "new_content",
        title: Fn.NEW_CONTENT,
        routerPath: "/231108/tab/live/newcontent",
        styleClass: Vn.router_icon_newcontent
    }], [])
      , l = (a, c) => {
        document.body.style.overflow = "hidden",
        i.current && (i.current.style.transformOrigin = `${a.clientX}px ${a.clientY}px`,
        i.current.style.transform = `translate3d(${a.nativeEvent.offsetX}px, ${a.nativeEvent.offsetY}px, 0) scale(2)`),
        setTimeout( () => {
            e.push(c),
            document.body.style.removeProperty("overflow")
        }
        , 900)
    }
    ;
    return u.jsx(sa, {
        children: u.jsxs("section", {
            className: Vn.container,
            ref: i,
            children: [u.jsx("video", {
                muted: !0,
                autoPlay: !0,
                ref: r,
                children: u.jsx("source", {
                    src: Gd.MAIN,
                    type: "video/mp4"
                })
            }), u.jsxs("div", {
                className: Vn.cloud_wrap,
                children: [u.jsx("img", {
                    className: Vn.right_cloud1,
                    src: Fn.CLOUD1,
                    alt: "cloud"
                }), u.jsx("img", {
                    className: Vn.left_cloud1,
                    src: Fn.CLOUD3,
                    alt: "cloud"
                })]
            }), o.map( ({id: a, title: c, routerPath: d, styleClass: f}) => u.jsxs("div", {
                onClick: p => l(p, d),
                className: f,
                onMouseEnter: () => n(a),
                onMouseLeave: () => n(""),
                children: [u.jsx("img", {
                    src: c,
                    alt: a
                }), u.jsx("img", {
                    src: Fn.ROUTER_ICON,
                    alt: "router-icon"
                }), s(a) ? u.jsx("img", {
                    className: Vn.rotate_icon,
                    src: Fn.ROUTER_ICON_HOVER
                }) : u.jsx("img", {
                    src: Fn.ROUTER_ICON_NOT_HOVER
                })]
            }, a))]
        })
    })
}
  , XP = "_bak_button_uqha0_1"
  , QP = {
    bak_button: XP
};
function Tc(e) {
    const [t,n] = g.useState(!1);
    return g.useEffect( () => {
        const r = new Image;
        r.src = e;
        const i = () => {
            r.complete && n(!0)
        }
        ;
        r.complete ? i() : r.onload = i
    }
    , [e]),
    t
}
const JP = "_container_1e0wh_238"
  , ZP = "_fadeIn_1e0wh_1"
  , ej = {
    "a11y-hidden": "_a11y-hidden_1e0wh_192",
    container: JP,
    fadeIn: ZP
}
  , tj = () => u.jsx("div", {
    className: ej.container,
    children: u.jsx("img", {
        src: mC.LOADING,
        alt: "loading"
    })
})
  , nj = "_container_18el2_1"
  , rj = {
    container: nj
}
  , Pc = ({children: e, isLoading: t, header: n, hero: r}) => u.jsx(u.Fragment, {
    children: t ? u.jsx(sa, {
        children: u.jsxs("section", {
            className: rj.container,
            children: [n, u.jsx("img", {
                src: r
            }), e]
        })
    }) : u.jsx(tj, {})
})
  , ij = () => {
    const e = vc()
      , t = Tc(on.ILLUSION_INFO_HERO);
    return u.jsx(Pc, {
        isLoading: t,
        header: e,
        hero: on.ILLUSION_INFO_HERO,
        children: u.jsx("a", {
            href: "https://mir2.mironline.co.kr/dic/dicView?depth1=108&depth2=91&depth3=337",
            target: "_blank",
            children: u.jsx("img", {
                className: QP.bak_button,
                src: on.ILLUSION_INFO_BAK_BUTTON,
                alt: "tab-illusion-info-bak-btn"
            })
        })
    })
}
  , sj = () => {
    const e = vc()
      , t = Tc(on.LIVE_BENEFIT_HERO);
    return u.jsx(Pc, {
        isLoading: t,
        header: e,
        hero: on.LIVE_BENEFIT_HERO
    })
}
  , oj = () => {
    const e = vc()
      , t = Tc(on.LIVE_NEWCONTENT);
    return u.jsx(Pc, {
        isLoading: t,
        header: e,
        hero: on.LIVE_NEWCONTENT
    })
}
;
var me = {};
me.cipher = function(e, t) {
    for (var n = 4, r = t.length / n - 1, i = [[], [], [], []], s = 0; s < 4 * n; s++)
        i[s % 4][Math.floor(s / 4)] = e[s];
    i = me.addRoundKey(i, t, 0, n);
    for (var o = 1; o < r; o++)
        i = me.subBytes(i, n),
        i = me.shiftRows(i, n),
        i = me.mixColumns(i, n),
        i = me.addRoundKey(i, t, o, n);
    i = me.subBytes(i, n),
    i = me.shiftRows(i, n),
    i = me.addRoundKey(i, t, r, n);
    for (var l = new Array(4 * n), s = 0; s < 4 * n; s++)
        l[s] = i[s % 4][Math.floor(s / 4)];
    return l
}
;
me.keyExpansion = function(e) {
    for (var t = 4, n = e.length / 4, r = n + 6, i = new Array(t * (r + 1)), s = new Array(4), o = 0; o < n; o++) {
        var l = [e[4 * o], e[4 * o + 1], e[4 * o + 2], e[4 * o + 3]];
        i[o] = l
    }
    for (var o = n; o < t * (r + 1); o++) {
        i[o] = new Array(4);
        for (var a = 0; a < 4; a++)
            s[a] = i[o - 1][a];
        if (o % n == 0) {
            s = me.subWord(me.rotWord(s));
            for (var a = 0; a < 4; a++)
                s[a] ^= me.rCon[o / n][a]
        } else
            n > 6 && o % n == 4 && (s = me.subWord(s));
        for (var a = 0; a < 4; a++)
            i[o][a] = i[o - n][a] ^ s[a]
    }
    return i
}
;
me.subBytes = function(e, t) {
    for (var n = 0; n < 4; n++)
        for (var r = 0; r < t; r++)
            e[n][r] = me.sBox[e[n][r]];
    return e
}
;
me.shiftRows = function(e, t) {
    for (var n = new Array(4), r = 1; r < 4; r++) {
        for (var i = 0; i < 4; i++)
            n[i] = e[r][(i + r) % t];
        for (var i = 0; i < 4; i++)
            e[r][i] = n[i]
    }
    return e
}
;
me.mixColumns = function(e) {
    for (var t = 0; t < 4; t++) {
        for (var n = new Array(4), r = new Array(4), i = 0; i < 4; i++)
            n[i] = e[i][t],
            r[i] = e[i][t] & 128 ? e[i][t] << 1 ^ 283 : e[i][t] << 1;
        e[0][t] = r[0] ^ n[1] ^ r[1] ^ n[2] ^ n[3],
        e[1][t] = n[0] ^ r[1] ^ n[2] ^ r[2] ^ n[3],
        e[2][t] = n[0] ^ n[1] ^ r[2] ^ n[3] ^ r[3],
        e[3][t] = n[0] ^ r[0] ^ n[1] ^ n[2] ^ r[3]
    }
    return e
}
;
me.addRoundKey = function(e, t, n, r) {
    for (var i = 0; i < 4; i++)
        for (var s = 0; s < r; s++)
            e[i][s] ^= t[n * 4 + s][i];
    return e
}
;
me.subWord = function(e) {
    for (var t = 0; t < 4; t++)
        e[t] = me.sBox[e[t]];
    return e
}
;
me.rotWord = function(e) {
    for (var t = e[0], n = 0; n < 3; n++)
        e[n] = e[n + 1];
    return e[3] = t,
    e
}
;
me.sBox = [99, 124, 119, 123, 242, 107, 111, 197, 48, 1, 103, 43, 254, 215, 171, 118, 202, 130, 201, 125, 250, 89, 71, 240, 173, 212, 162, 175, 156, 164, 114, 192, 183, 253, 147, 38, 54, 63, 247, 204, 52, 165, 229, 241, 113, 216, 49, 21, 4, 199, 35, 195, 24, 150, 5, 154, 7, 18, 128, 226, 235, 39, 178, 117, 9, 131, 44, 26, 27, 110, 90, 160, 82, 59, 214, 179, 41, 227, 47, 132, 83, 209, 0, 237, 32, 252, 177, 91, 106, 203, 190, 57, 74, 76, 88, 207, 208, 239, 170, 251, 67, 77, 51, 133, 69, 249, 2, 127, 80, 60, 159, 168, 81, 163, 64, 143, 146, 157, 56, 245, 188, 182, 218, 33, 16, 255, 243, 210, 205, 12, 19, 236, 95, 151, 68, 23, 196, 167, 126, 61, 100, 93, 25, 115, 96, 129, 79, 220, 34, 42, 144, 136, 70, 238, 184, 20, 222, 94, 11, 219, 224, 50, 58, 10, 73, 6, 36, 92, 194, 211, 172, 98, 145, 149, 228, 121, 231, 200, 55, 109, 141, 213, 78, 169, 108, 86, 244, 234, 101, 122, 174, 8, 186, 120, 37, 46, 28, 166, 180, 198, 232, 221, 116, 31, 75, 189, 139, 138, 112, 62, 181, 102, 72, 3, 246, 14, 97, 53, 87, 185, 134, 193, 29, 158, 225, 248, 152, 17, 105, 217, 142, 148, 155, 30, 135, 233, 206, 85, 40, 223, 140, 161, 137, 13, 191, 230, 66, 104, 65, 153, 45, 15, 176, 84, 187, 22];
me.rCon = [[0, 0, 0, 0], [1, 0, 0, 0], [2, 0, 0, 0], [4, 0, 0, 0], [8, 0, 0, 0], [16, 0, 0, 0], [32, 0, 0, 0], [64, 0, 0, 0], [128, 0, 0, 0], [27, 0, 0, 0], [54, 0, 0, 0]];
me.Ctr = {};
me.Ctr.encrypt = function(e, t, n) {
    var r = 16;
    if (!(n == 128 || n == 192 || n == 256))
        return "";
    e = _s.encode(e),
    t = _s.encode(t);
    for (var i = n / 8, s = new Array(i), o = 0; o < i; o++)
        s[o] = isNaN(t.charCodeAt(o)) ? 0 : t.charCodeAt(o);
    var l = me.cipher(s, me.keyExpansion(s));
    l = l.concat(l.slice(0, i - 16));
    for (var a = new Array(r), c = new Date().getTime(), d = Math.floor(c / 1e3), f = c % 1e3, o = 0; o < 4; o++)
        a[o] = d >>> o * 8 & 255;
    for (var o = 0; o < 4; o++)
        a[o + 4] = f & 255;
    for (var p = "", o = 0; o < 8; o++)
        p += String.fromCharCode(a[o]);
    for (var h = me.keyExpansion(l), m = Math.ceil(e.length / r), _ = new Array(m), x = 0; x < m; x++) {
        for (var v = 0; v < 4; v++)
            a[15 - v] = x >>> v * 8 & 255;
        for (var v = 0; v < 4; v++)
            a[15 - v - 4] = x / 4294967296 >>> v * 8;
        for (var w = me.cipher(a, h), y = x < m - 1 ? r : (e.length - 1) % r + 1, b = new Array(y), o = 0; o < y; o++)
            b[o] = w[o] ^ e.charCodeAt(x * r + o),
            b[o] = String.fromCharCode(b[o]);
        _[x] = b.join("")
    }
    var C = p + _.join("");
    return C = gi.encode(C),
    C
}
;
me.Ctr.decrypt = function(e, t, n) {
    var r = 16;
    if (!(n == 128 || n == 192 || n == 256))
        return "";
    e = gi.decode(e),
    t = _s.encode(t);
    for (var i = n / 8, s = new Array(i), o = 0; o < i; o++)
        s[o] = isNaN(t.charCodeAt(o)) ? 0 : t.charCodeAt(o);
    var l = me.cipher(s, me.keyExpansion(s));
    l = l.concat(l.slice(0, i - 16));
    var a = new Array(8);
    ctrTxt = e.slice(0, 8);
    for (var o = 0; o < 8; o++)
        a[o] = ctrTxt.charCodeAt(o);
    for (var c = me.keyExpansion(l), d = Math.ceil((e.length - 8) / r), f = new Array(d), p = 0; p < d; p++)
        f[p] = e.slice(8 + p * r, 8 + p * r + r);
    e = f;
    for (var h = new Array(e.length), p = 0; p < d; p++) {
        for (var m = 0; m < 4; m++)
            a[15 - m] = p >>> m * 8 & 255;
        for (var m = 0; m < 4; m++)
            a[15 - m - 4] = (p + 1) / 4294967296 - 1 >>> m * 8 & 255;
        for (var _ = me.cipher(a, c), x = new Array(e[p].length), o = 0; o < e[p].length; o++)
            x[o] = _[o] ^ e[p].charCodeAt(o),
            x[o] = String.fromCharCode(x[o]);
        h[p] = x.join("")
    }
    var v = h.join("");
    return v = _s.decode(v),
    v
}
;
var gi = {};
gi.code = "ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz0123456789+/=";
gi.encode = function(e, t) {
    t = typeof t > "u" ? !1 : t;
    var n, r, i, s, o, l, a, c, d = [], f = "", p, h, m, _ = gi.code;
    if (h = t ? e.encodeUTF8() : e,
    p = h.length % 3,
    p > 0)
        for (; p++ < 3; )
            f += "=",
            h += "\0";
    for (p = 0; p < h.length; p += 3)
        n = h.charCodeAt(p),
        r = h.charCodeAt(p + 1),
        i = h.charCodeAt(p + 2),
        s = n << 16 | r << 8 | i,
        o = s >> 18 & 63,
        l = s >> 12 & 63,
        a = s >> 6 & 63,
        c = s & 63,
        d[p / 3] = _.charAt(o) + _.charAt(l) + _.charAt(a) + _.charAt(c);
    return m = d.join(""),
    m = m.slice(0, m.length - f.length) + f,
    m
}
;
gi.decode = function(e, t) {
    t = typeof t > "u" ? !1 : t;
    var n, r, i, s, o, l, a, c, d = [], f, p, h = gi.code;
    p = t ? e.decodeUTF8() : e;
    for (var m = 0; m < p.length; m += 4)
        s = h.indexOf(p.charAt(m)),
        o = h.indexOf(p.charAt(m + 1)),
        l = h.indexOf(p.charAt(m + 2)),
        a = h.indexOf(p.charAt(m + 3)),
        c = s << 18 | o << 12 | l << 6 | a,
        n = c >>> 16 & 255,
        r = c >>> 8 & 255,
        i = c & 255,
        d[m / 4] = String.fromCharCode(n, r, i),
        a == 64 && (d[m / 4] = String.fromCharCode(n, r)),
        l == 64 && (d[m / 4] = String.fromCharCode(n));
    return f = d.join(""),
    t ? f.decodeUTF8() : f
}
;
var _s = {};
_s.encode = function(e) {
    var t = e.replace(/[\u0080-\u07ff]/g, function(n) {
        var r = n.charCodeAt(0);
        return String.fromCharCode(192 | r >> 6, 128 | r & 63)
    });
    return t = t.replace(/[\u0800-\uffff]/g, function(n) {
        var r = n.charCodeAt(0);
        return String.fromCharCode(224 | r >> 12, 128 | r >> 6 & 63, 128 | r & 63)
    }),
    t
}
;
_s.decode = function(e) {
    var t = e.replace(/[\u00e0-\u00ef][\u0080-\u00bf][\u0080-\u00bf]/g, function(n) {
        var r = (n.charCodeAt(0) & 15) << 12 | (n.charCodeAt(1) & 63) << 6 | n.charCodeAt(2) & 63;
        return String.fromCharCode(r)
    });
    return t = t.replace(/[\u00c0-\u00df][\u0080-\u00bf]/g, function(n) {
        var r = (n.charCodeAt(0) & 31) << 6 | n.charCodeAt(1) & 63;
        return String.fromCharCode(r)
    }),
    t
}
;
function n_(e, t) {
    return me.Ctr.encrypt(e, t, 256)
}
const aj = "_level_wrap_txdnv_238"
  , lj = "_level_wrap_not_login_txdnv_252"
  , cj = "_not_open_event_txdnv_274"
  , uj = "_fadeIn_txdnv_1"
  , Ru = {
    "a11y-hidden": "_a11y-hidden_txdnv_192",
    level_wrap: aj,
    level_wrap_not_login: lj,
    not_open_event: cj,
    fadeIn: uj
};
function he(e, t, n, r) {
    const i = "/"
      , s = ".mironline.co.kr";
    let o = `${e}=${t};`;
    if (n) {
        const l = new Date
          , a = new Date(l);
        a.setHours(23, 59, 59, 0),
        a.setDate(l.getDate()),
        o += `expires=${a.toUTCString()};path=${i};domain=${s};`,
        document.cookie = o
    } else
        o += `max-age=${r};path=${i};domain=${s};`,
        document.cookie = o
}
function Ei(e) {
    const t = e + "="
      , n = decodeURIComponent(document.cookie)
      , r = n ? n.split(";") : [];
    if (r.length === 0)
        return "";
    for (let i = 0; i < r.length; i++) {
        let s = r[i];
        for (; s.charAt(0) == " "; )
            s = s.substring(1);
        if (s.indexOf(t) == 0)
            return s.substring(t.length, s.length)
    }
    return ""
}
function dj(e) {
    const t = ".mironline.co.kr";
    document.cookie = `${encodeURIComponent(e)}=; expires=Thu, 01 Jan 1970 00:00:00 UTC; path=/; domain=${t};`
}
const Bw = g.createContext(null);
function fj({children: e}) {
    const [t,n] = g.useState(null);
    return u.jsxs(Bw.Provider, {
        value: t,
        children: [e, u.jsx("div", {
            id: "portal-container",
            ref: r => {
                t !== null || r === null || n(r)
            }
        })]
    })
}
function pj({children: e}) {
    return u.jsx(Bw.Consumer, {
        children: t => t === null ? null : mc.createPortal(e, t)
    })
}
const vt = {
    Provider: fj,
    Consumer: pj
}
  , mj = "_container_pseyq_238"
  , hj = "_fadeIn_pseyq_1"
  , gj = {
    "a11y-hidden": "_a11y-hidden_pseyq_192",
    container: mj,
    fadeIn: hj
}
  , _j = ({children: e}) => u.jsx("div", {
    className: gj.container,
    children: e
})
  , vj = "_container_1ypng_238"
  , yj = "_header_1ypng_253"
  , wj = "_main_1ypng_274"
  , xj = "_search_1ypng_331"
  , bj = "_level_experience_1ypng_351"
  , Sj = "_check_wrap_1ypng_355"
  , Cj = "_check_1ypng_355"
  , kj = "_no_character_1ypng_379"
  , Ej = "_fadeIn_1ypng_1"
  , qr = {
    "a11y-hidden": "_a11y-hidden_1ypng_192",
    container: vj,
    header: yj,
    main: wj,
    search: xj,
    level_experience: bj,
    check_wrap: Sj,
    check: Cj,
    no_character: kj,
    fadeIn: Ej
}
  , Tj = `1. 현재 환영서버 캐릭터의 조회 버튼을 클릭합니다. 
2. 클릭 시 해당 캐릭터를 본서버로 이전 시 예상되는 레벨 정보가 출력됩니다.
3. 캐릭터 레벨 계산기는 대략적인 결과로, 실제 캐릭터 이전 시와 차이가 있을 수 있습니다.
`
  , Pj = ({characterInfo: e=[], onModalClose: t, isEventOpen: n, levelInfoData: r}) => {
    const i = g.useCallback(s => {
        const o = r.filter( ({charName: c}) => c === s)
          , l = o.map( ({applyLevel: c}) => c + "Lv")
          , a = o.map( ({applyExp: c}) => "(" + c + "%)");
        return {
            levels: l,
            exps: a
        }
    }
    , [r]);
    return u.jsx(_j, {
        children: u.jsxs("section", {
            className: qr.container,
            children: [u.jsxs("div", {
                className: qr.header,
                children: [u.jsx("h3", {
                    children: "캐릭터 예상레벨 계산하기"
                }), u.jsx("img", {
                    src: hC.CLOSE,
                    alt: "close",
                    onClick: t
                })]
            }), u.jsxs("article", {
                className: qr.main,
                children: [u.jsx("h4", {
                    children: "[ 캐릭터 예상레벨 계산하기 안내 ]"
                }), u.jsx("pre", {
                    children: Tj
                }), u.jsxs("table", {
                    children: [u.jsx("thead", {
                        children: u.jsxs("tr", {
                            children: [u.jsx("th", {
                                children: "캐릭터"
                            }), u.jsxs("th", {
                                children: ["환영서버 레벨 ", u.jsx("br", {}), " (경험치%)"]
                            }), u.jsxs("th", {
                                children: ["본서버 현재 레벨", u.jsx("br", {}), " (경험치%)"]
                            }), u.jsxs("th", {
                                children: ["본서버 예상 레벨", u.jsx("br", {}), " (경험치%)"]
                            })]
                        })
                    }), u.jsx("tbody", {
                        children: e.map( (s, o) => n === "Y" && u.jsxs("tr", {
                            children: [u.jsx("td", {
                                children: s.charName
                            }), u.jsxs("td", {
                                children: [s.busterLv, "Lv (", s.busterExp, "%)"]
                            }), u.jsxs("td", {
                                children: [s.orgLv, "Lv (", s.orgExp, "%)"]
                            }), u.jsx("td", {
                                children: u.jsxs("span", {
                                    className: qr.level_experience,
                                    children: [i(s.charName).levels, " ", i(s.charName).exps]
                                })
                            })]
                        }, o))
                    })]
                }), e.length === 0 && u.jsx("div", {
                    className: qr.no_character,
                    children: "조회할 캐릭터 정보가 없습니다."
                }), u.jsx("div", {
                    className: qr.check_wrap,
                    children: u.jsx("button", {
                        className: qr.check,
                        onClick: t,
                        children: "확인"
                    })
                })]
            })]
        })
    })
}
  , jj = "/event/hwanyoung/chracter"
  , Nj = "/event/hwanyoung/level"
  , Rj = "/event/comm/info";
function Uw(e, t) {
    return function() {
        return e.apply(t, arguments)
    }
}
const {toString: Ij} = Object.prototype
  , {getPrototypeOf: Qp} = Object
  , jc = (e => t => {
    const n = Ij.call(t);
    return e[n] || (e[n] = n.slice(8, -1).toLowerCase())
}
)(Object.create(null))
  , Sn = e => (e = e.toLowerCase(),
t => jc(t) === e)
  , Nc = e => t => typeof t === e
  , {isArray: bs} = Array
  , zo = Nc("undefined");
function Lj(e) {
    return e !== null && !zo(e) && e.constructor !== null && !zo(e.constructor) && Gt(e.constructor.isBuffer) && e.constructor.isBuffer(e)
}
const Hw = Sn("ArrayBuffer");
function Mj(e) {
    let t;
    return typeof ArrayBuffer < "u" && ArrayBuffer.isView ? t = ArrayBuffer.isView(e) : t = e && e.buffer && Hw(e.buffer),
    t
}
const Oj = Nc("string")
  , Gt = Nc("function")
  , Gw = Nc("number")
  , Rc = e => e !== null && typeof e == "object"
  , Aj = e => e === !0 || e === !1
  , ll = e => {
    if (jc(e) !== "object")
        return !1;
    const t = Qp(e);
    return (t === null || t === Object.prototype || Object.getPrototypeOf(t) === null) && !(Symbol.toStringTag in e) && !(Symbol.iterator in e)
}
  , Dj = Sn("Date")
  , $j = Sn("File")
  , Fj = Sn("Blob")
  , Vj = Sn("FileList")
  , zj = e => Rc(e) && Gt(e.pipe)
  , Bj = e => {
    let t;
    return e && (typeof FormData == "function" && e instanceof FormData || Gt(e.append) && ((t = jc(e)) === "formdata" || t === "object" && Gt(e.toString) && e.toString() === "[object FormData]"))
}
  , Uj = Sn("URLSearchParams")
  , [Hj,Gj,Wj,qj] = ["ReadableStream", "Request", "Response", "Headers"].map(Sn)
  , Yj = e => e.trim ? e.trim() : e.replace(/^[\s\uFEFF\xA0]+|[\s\uFEFF\xA0]+$/g, "");
function oa(e, t, {allOwnKeys: n=!1}={}) {
    if (e === null || typeof e > "u")
        return;
    let r, i;
    if (typeof e != "object" && (e = [e]),
    bs(e))
        for (r = 0,
        i = e.length; r < i; r++)
            t.call(null, e[r], r, e);
    else {
        const s = n ? Object.getOwnPropertyNames(e) : Object.keys(e)
          , o = s.length;
        let l;
        for (r = 0; r < o; r++)
            l = s[r],
            t.call(null, e[l], l, e)
    }
}
function Ww(e, t) {
    t = t.toLowerCase();
    const n = Object.keys(e);
    let r = n.length, i;
    for (; r-- > 0; )
        if (i = n[r],
        t === i.toLowerCase())
            return i;
    return null
}
const ai = ( () => typeof globalThis < "u" ? globalThis : typeof self < "u" ? self : typeof window < "u" ? window : global)()
  , qw = e => !zo(e) && e !== ai;
function of() {
    const {caseless: e} = qw(this) && this || {}
      , t = {}
      , n = (r, i) => {
        const s = e && Ww(t, i) || i;
        ll(t[s]) && ll(r) ? t[s] = of(t[s], r) : ll(r) ? t[s] = of({}, r) : bs(r) ? t[s] = r.slice() : t[s] = r
    }
    ;
    for (let r = 0, i = arguments.length; r < i; r++)
        arguments[r] && oa(arguments[r], n);
    return t
}
const Kj = (e, t, n, {allOwnKeys: r}={}) => (oa(t, (i, s) => {
    n && Gt(i) ? e[s] = Uw(i, n) : e[s] = i
}
, {
    allOwnKeys: r
}),
e)
  , Xj = e => (e.charCodeAt(0) === 65279 && (e = e.slice(1)),
e)
  , Qj = (e, t, n, r) => {
    e.prototype = Object.create(t.prototype, r),
    e.prototype.constructor = e,
    Object.defineProperty(e, "super", {
        value: t.prototype
    }),
    n && Object.assign(e.prototype, n)
}
  , Jj = (e, t, n, r) => {
    let i, s, o;
    const l = {};
    if (t = t || {},
    e == null)
        return t;
    do {
        for (i = Object.getOwnPropertyNames(e),
        s = i.length; s-- > 0; )
            o = i[s],
            (!r || r(o, e, t)) && !l[o] && (t[o] = e[o],
            l[o] = !0);
        e = n !== !1 && Qp(e)
    } while (e && (!n || n(e, t)) && e !== Object.prototype);
    return t
}
  , Zj = (e, t, n) => {
    e = String(e),
    (n === void 0 || n > e.length) && (n = e.length),
    n -= t.length;
    const r = e.indexOf(t, n);
    return r !== -1 && r === n
}
  , eN = e => {
    if (!e)
        return null;
    if (bs(e))
        return e;
    let t = e.length;
    if (!Gw(t))
        return null;
    const n = new Array(t);
    for (; t-- > 0; )
        n[t] = e[t];
    return n
}
  , tN = (e => t => e && t instanceof e)(typeof Uint8Array < "u" && Qp(Uint8Array))
  , nN = (e, t) => {
    const r = (e && e[Symbol.iterator]).call(e);
    let i;
    for (; (i = r.next()) && !i.done; ) {
        const s = i.value;
        t.call(e, s[0], s[1])
    }
}
  , rN = (e, t) => {
    let n;
    const r = [];
    for (; (n = e.exec(t)) !== null; )
        r.push(n);
    return r
}
  , iN = Sn("HTMLFormElement")
  , sN = e => e.toLowerCase().replace(/[-_\s]([a-z\d])(\w*)/g, function(n, r, i) {
    return r.toUpperCase() + i
})
  , r_ = ( ({hasOwnProperty: e}) => (t, n) => e.call(t, n))(Object.prototype)
  , oN = Sn("RegExp")
  , Yw = (e, t) => {
    const n = Object.getOwnPropertyDescriptors(e)
      , r = {};
    oa(n, (i, s) => {
        let o;
        (o = t(i, s, e)) !== !1 && (r[s] = o || i)
    }
    ),
    Object.defineProperties(e, r)
}
  , aN = e => {
    Yw(e, (t, n) => {
        if (Gt(e) && ["arguments", "caller", "callee"].indexOf(n) !== -1)
            return !1;
        const r = e[n];
        if (Gt(r)) {
            if (t.enumerable = !1,
            "writable"in t) {
                t.writable = !1;
                return
            }
            t.set || (t.set = () => {
                throw Error("Can not rewrite read-only method '" + n + "'")
            }
            )
        }
    }
    )
}
  , lN = (e, t) => {
    const n = {}
      , r = i => {
        i.forEach(s => {
            n[s] = !0
        }
        )
    }
    ;
    return bs(e) ? r(e) : r(String(e).split(t)),
    n
}
  , cN = () => {}
  , uN = (e, t) => e != null && Number.isFinite(e = +e) ? e : t
  , Iu = "abcdefghijklmnopqrstuvwxyz"
  , i_ = "0123456789"
  , Kw = {
    DIGIT: i_,
    ALPHA: Iu,
    ALPHA_DIGIT: Iu + Iu.toUpperCase() + i_
}
  , dN = (e=16, t=Kw.ALPHA_DIGIT) => {
    let n = "";
    const {length: r} = t;
    for (; e--; )
        n += t[Math.random() * r | 0];
    return n
}
;
function fN(e) {
    return !!(e && Gt(e.append) && e[Symbol.toStringTag] === "FormData" && e[Symbol.iterator])
}
const pN = e => {
    const t = new Array(10)
      , n = (r, i) => {
        if (Rc(r)) {
            if (t.indexOf(r) >= 0)
                return;
            if (!("toJSON"in r)) {
                t[i] = r;
                const s = bs(r) ? [] : {};
                return oa(r, (o, l) => {
                    const a = n(o, i + 1);
                    !zo(a) && (s[l] = a)
                }
                ),
                t[i] = void 0,
                s
            }
        }
        return r
    }
    ;
    return n(e, 0)
}
  , mN = Sn("AsyncFunction")
  , hN = e => e && (Rc(e) || Gt(e)) && Gt(e.then) && Gt(e.catch)
  , Xw = ( (e, t) => e ? setImmediate : t ? ( (n, r) => (ai.addEventListener("message", ({source: i, data: s}) => {
    i === ai && s === n && r.length && r.shift()()
}
, !1),
i => {
    r.push(i),
    ai.postMessage(n, "*")
}
))(`axios@${Math.random()}`, []) : n => setTimeout(n))(typeof setImmediate == "function", Gt(ai.postMessage))
  , gN = typeof queueMicrotask < "u" ? queueMicrotask.bind(ai) : typeof process < "u" && process.nextTick || Xw
  , M = {
    isArray: bs,
    isArrayBuffer: Hw,
    isBuffer: Lj,
    isFormData: Bj,
    isArrayBufferView: Mj,
    isString: Oj,
    isNumber: Gw,
    isBoolean: Aj,
    isObject: Rc,
    isPlainObject: ll,
    isReadableStream: Hj,
    isRequest: Gj,
    isResponse: Wj,
    isHeaders: qj,
    isUndefined: zo,
    isDate: Dj,
    isFile: $j,
    isBlob: Fj,
    isRegExp: oN,
    isFunction: Gt,
    isStream: zj,
    isURLSearchParams: Uj,
    isTypedArray: tN,
    isFileList: Vj,
    forEach: oa,
    merge: of,
    extend: Kj,
    trim: Yj,
    stripBOM: Xj,
    inherits: Qj,
    toFlatObject: Jj,
    kindOf: jc,
    kindOfTest: Sn,
    endsWith: Zj,
    toArray: eN,
    forEachEntry: nN,
    matchAll: rN,
    isHTMLForm: iN,
    hasOwnProperty: r_,
    hasOwnProp: r_,
    reduceDescriptors: Yw,
    freezeMethods: aN,
    toObjectSet: lN,
    toCamelCase: sN,
    noop: cN,
    toFiniteNumber: uN,
    findKey: Ww,
    global: ai,
    isContextDefined: qw,
    ALPHABET: Kw,
    generateString: dN,
    isSpecCompliantForm: fN,
    toJSONObject: pN,
    isAsyncFn: mN,
    isThenable: hN,
    setImmediate: Xw,
    asap: gN
};
function se(e, t, n, r, i) {
    Error.call(this),
    Error.captureStackTrace ? Error.captureStackTrace(this, this.constructor) : this.stack = new Error().stack,
    this.message = e,
    this.name = "AxiosError",
    t && (this.code = t),
    n && (this.config = n),
    r && (this.request = r),
    i && (this.response = i)
}
M.inherits(se, Error, {
    toJSON: function() {
        return {
            message: this.message,
            name: this.name,
            description: this.description,
            number: this.number,
            fileName: this.fileName,
            lineNumber: this.lineNumber,
            columnNumber: this.columnNumber,
            stack: this.stack,
            config: M.toJSONObject(this.config),
            code: this.code,
            status: this.response && this.response.status ? this.response.status : null
        }
    }
});
const Qw = se.prototype
  , Jw = {};
["ERR_BAD_OPTION_VALUE", "ERR_BAD_OPTION", "ECONNABORTED", "ETIMEDOUT", "ERR_NETWORK", "ERR_FR_TOO_MANY_REDIRECTS", "ERR_DEPRECATED", "ERR_BAD_RESPONSE", "ERR_BAD_REQUEST", "ERR_CANCELED", "ERR_NOT_SUPPORT", "ERR_INVALID_URL"].forEach(e => {
    Jw[e] = {
        value: e
    }
}
);
Object.defineProperties(se, Jw);
Object.defineProperty(Qw, "isAxiosError", {
    value: !0
});
se.from = (e, t, n, r, i, s) => {
    const o = Object.create(Qw);
    return M.toFlatObject(e, o, function(a) {
        return a !== Error.prototype
    }, l => l !== "isAxiosError"),
    se.call(o, e.message, t, n, r, i),
    o.cause = e,
    o.name = e.name,
    s && Object.assign(o, s),
    o
}
;
const _N = null;
function af(e) {
    return M.isPlainObject(e) || M.isArray(e)
}
function Zw(e) {
    return M.endsWith(e, "[]") ? e.slice(0, -2) : e
}
function s_(e, t, n) {
    return e ? e.concat(t).map(function(i, s) {
        return i = Zw(i),
        !n && s ? "[" + i + "]" : i
    }).join(n ? "." : "") : t
}
function vN(e) {
    return M.isArray(e) && !e.some(af)
}
const yN = M.toFlatObject(M, {}, null, function(t) {
    return /^is[A-Z]/.test(t)
});
function Ic(e, t, n) {
    if (!M.isObject(e))
        throw new TypeError("target must be an object");
    t = t || new FormData,
    n = M.toFlatObject(n, {
        metaTokens: !0,
        dots: !1,
        indexes: !1
    }, !1, function(_, x) {
        return !M.isUndefined(x[_])
    });
    const r = n.metaTokens
      , i = n.visitor || d
      , s = n.dots
      , o = n.indexes
      , a = (n.Blob || typeof Blob < "u" && Blob) && M.isSpecCompliantForm(t);
    if (!M.isFunction(i))
        throw new TypeError("visitor must be a function");
    function c(m) {
        if (m === null)
            return "";
        if (M.isDate(m))
            return m.toISOString();
        if (!a && M.isBlob(m))
            throw new se("Blob is not supported. Use a Buffer instead.");
        return M.isArrayBuffer(m) || M.isTypedArray(m) ? a && typeof Blob == "function" ? new Blob([m]) : Buffer.from(m) : m
    }
    function d(m, _, x) {
        let v = m;
        if (m && !x && typeof m == "object") {
            if (M.endsWith(_, "{}"))
                _ = r ? _ : _.slice(0, -2),
                m = JSON.stringify(m);
            else if (M.isArray(m) && vN(m) || (M.isFileList(m) || M.endsWith(_, "[]")) && (v = M.toArray(m)))
                return _ = Zw(_),
                v.forEach(function(y, b) {
                    !(M.isUndefined(y) || y === null) && t.append(o === !0 ? s_([_], b, s) : o === null ? _ : _ + "[]", c(y))
                }),
                !1
        }
        return af(m) ? !0 : (t.append(s_(x, _, s), c(m)),
        !1)
    }
    const f = []
      , p = Object.assign(yN, {
        defaultVisitor: d,
        convertValue: c,
        isVisitable: af
    });
    function h(m, _) {
        if (!M.isUndefined(m)) {
            if (f.indexOf(m) !== -1)
                throw Error("Circular reference detected in " + _.join("."));
            f.push(m),
            M.forEach(m, function(v, w) {
                (!(M.isUndefined(v) || v === null) && i.call(t, v, M.isString(w) ? w.trim() : w, _, p)) === !0 && h(v, _ ? _.concat(w) : [w])
            }),
            f.pop()
        }
    }
    if (!M.isObject(e))
        throw new TypeError("data must be an object");
    return h(e),
    t
}
function o_(e) {
    const t = {
        "!": "%21",
        "'": "%27",
        "(": "%28",
        ")": "%29",
        "~": "%7E",
        "%20": "+",
        "%00": "\0"
    };
    return encodeURIComponent(e).replace(/[!'()~]|%20|%00/g, function(r) {
        return t[r]
    })
}
function Jp(e, t) {
    this._pairs = [],
    e && Ic(e, this, t)
}
const ex = Jp.prototype;
ex.append = function(t, n) {
    this._pairs.push([t, n])
}
;
ex.toString = function(t) {
    const n = t ? function(r) {
        return t.call(this, r, o_)
    }
    : o_;
    return this._pairs.map(function(i) {
        return n(i[0]) + "=" + n(i[1])
    }, "").join("&")
}
;
function wN(e) {
    return encodeURIComponent(e).replace(/%3A/gi, ":").replace(/%24/g, "$").replace(/%2C/gi, ",").replace(/%20/g, "+").replace(/%5B/gi, "[").replace(/%5D/gi, "]")
}
function tx(e, t, n) {
    if (!t)
        return e;
    const r = n && n.encode || wN
      , i = n && n.serialize;
    let s;
    if (i ? s = i(t, n) : s = M.isURLSearchParams(t) ? t.toString() : new Jp(t,n).toString(r),
    s) {
        const o = e.indexOf("#");
        o !== -1 && (e = e.slice(0, o)),
        e += (e.indexOf("?") === -1 ? "?" : "&") + s
    }
    return e
}
class xN {
    constructor() {
        this.handlers = []
    }
    use(t, n, r) {
        return this.handlers.push({
            fulfilled: t,
            rejected: n,
            synchronous: r ? r.synchronous : !1,
            runWhen: r ? r.runWhen : null
        }),
        this.handlers.length - 1
    }
    eject(t) {
        this.handlers[t] && (this.handlers[t] = null)
    }
    clear() {
        this.handlers && (this.handlers = [])
    }
    forEach(t) {
        M.forEach(this.handlers, function(r) {
            r !== null && t(r)
        })
    }
}
const a_ = xN
  , nx = {
    silentJSONParsing: !0,
    forcedJSONParsing: !0,
    clarifyTimeoutError: !1
}
  , bN = typeof URLSearchParams < "u" ? URLSearchParams : Jp
  , SN = typeof FormData < "u" ? FormData : null
  , CN = typeof Blob < "u" ? Blob : null
  , kN = {
    isBrowser: !0,
    classes: {
        URLSearchParams: bN,
        FormData: SN,
        Blob: CN
    },
    protocols: ["http", "https", "file", "blob", "url", "data"]
}
  , Zp = typeof window < "u" && typeof document < "u"
  , EN = (e => Zp && ["ReactNative", "NativeScript", "NS"].indexOf(e) < 0)(typeof navigator < "u" && navigator.product)
  , TN = ( () => typeof WorkerGlobalScope < "u" && self instanceof WorkerGlobalScope && typeof self.importScripts == "function")()
  , PN = Zp && window.location.href || "http://localhost"
  , jN = Object.freeze(Object.defineProperty({
    __proto__: null,
    hasBrowserEnv: Zp,
    hasStandardBrowserEnv: EN,
    hasStandardBrowserWebWorkerEnv: TN,
    origin: PN
}, Symbol.toStringTag, {
    value: "Module"
}))
  , wn = {
    ...jN,
    ...kN
};
function NN(e, t) {
    return Ic(e, new wn.classes.URLSearchParams, Object.assign({
        visitor: function(n, r, i, s) {
            return wn.isNode && M.isBuffer(n) ? (this.append(r, n.toString("base64")),
            !1) : s.defaultVisitor.apply(this, arguments)
        }
    }, t))
}
function RN(e) {
    return M.matchAll(/\w+|\[(\w*)]/g, e).map(t => t[0] === "[]" ? "" : t[1] || t[0])
}
function IN(e) {
    const t = {}
      , n = Object.keys(e);
    let r;
    const i = n.length;
    let s;
    for (r = 0; r < i; r++)
        s = n[r],
        t[s] = e[s];
    return t
}
function rx(e) {
    function t(n, r, i, s) {
        let o = n[s++];
        if (o === "__proto__")
            return !0;
        const l = Number.isFinite(+o)
          , a = s >= n.length;
        return o = !o && M.isArray(i) ? i.length : o,
        a ? (M.hasOwnProp(i, o) ? i[o] = [i[o], r] : i[o] = r,
        !l) : ((!i[o] || !M.isObject(i[o])) && (i[o] = []),
        t(n, r, i[o], s) && M.isArray(i[o]) && (i[o] = IN(i[o])),
        !l)
    }
    if (M.isFormData(e) && M.isFunction(e.entries)) {
        const n = {};
        return M.forEachEntry(e, (r, i) => {
            t(RN(r), i, n, 0)
        }
        ),
        n
    }
    return null
}
function LN(e, t, n) {
    if (M.isString(e))
        try {
            return (t || JSON.parse)(e),
            M.trim(e)
        } catch (r) {
            if (r.name !== "SyntaxError")
                throw r
        }
    return (n || JSON.stringify)(e)
}
const em = {
    transitional: nx,
    adapter: ["xhr", "http", "fetch"],
    transformRequest: [function(t, n) {
        const r = n.getContentType() || ""
          , i = r.indexOf("application/json") > -1
          , s = M.isObject(t);
        if (s && M.isHTMLForm(t) && (t = new FormData(t)),
        M.isFormData(t))
            return i ? JSON.stringify(rx(t)) : t;
        if (M.isArrayBuffer(t) || M.isBuffer(t) || M.isStream(t) || M.isFile(t) || M.isBlob(t) || M.isReadableStream(t))
            return t;
        if (M.isArrayBufferView(t))
            return t.buffer;
        if (M.isURLSearchParams(t))
            return n.setContentType("application/x-www-form-urlencoded;charset=utf-8", !1),
            t.toString();
        let l;
        if (s) {
            if (r.indexOf("application/x-www-form-urlencoded") > -1)
                return NN(t, this.formSerializer).toString();
            if ((l = M.isFileList(t)) || r.indexOf("multipart/form-data") > -1) {
                const a = this.env && this.env.FormData;
                return Ic(l ? {
                    "files[]": t
                } : t, a && new a, this.formSerializer)
            }
        }
        return s || i ? (n.setContentType("application/json", !1),
        LN(t)) : t
    }
    ],
    transformResponse: [function(t) {
        const n = this.transitional || em.transitional
          , r = n && n.forcedJSONParsing
          , i = this.responseType === "json";
        if (M.isResponse(t) || M.isReadableStream(t))
            return t;
        if (t && M.isString(t) && (r && !this.responseType || i)) {
            const o = !(n && n.silentJSONParsing) && i;
            try {
                return JSON.parse(t)
            } catch (l) {
                if (o)
                    throw l.name === "SyntaxError" ? se.from(l, se.ERR_BAD_RESPONSE, this, null, this.response) : l
            }
        }
        return t
    }
    ],
    timeout: 0,
    xsrfCookieName: "XSRF-TOKEN",
    xsrfHeaderName: "X-XSRF-TOKEN",
    maxContentLength: -1,
    maxBodyLength: -1,
    env: {
        FormData: wn.classes.FormData,
        Blob: wn.classes.Blob
    },
    validateStatus: function(t) {
        return t >= 200 && t < 300
    },
    headers: {
        common: {
            Accept: "application/json, text/plain, */*",
            "Content-Type": void 0
        }
    }
};
M.forEach(["delete", "get", "head", "post", "put", "patch"], e => {
    em.headers[e] = {}
}
);
const tm = em
  , MN = M.toObjectSet(["age", "authorization", "content-length", "content-type", "etag", "expires", "from", "host", "if-modified-since", "if-unmodified-since", "last-modified", "location", "max-forwards", "proxy-authorization", "referer", "retry-after", "user-agent"])
  , ON = e => {
    const t = {};
    let n, r, i;
    return e && e.split(`
`).forEach(function(o) {
        i = o.indexOf(":"),
        n = o.substring(0, i).trim().toLowerCase(),
        r = o.substring(i + 1).trim(),
        !(!n || t[n] && MN[n]) && (n === "set-cookie" ? t[n] ? t[n].push(r) : t[n] = [r] : t[n] = t[n] ? t[n] + ", " + r : r)
    }),
    t
}
  , l_ = Symbol("internals");
function zs(e) {
    return e && String(e).trim().toLowerCase()
}
function cl(e) {
    return e === !1 || e == null ? e : M.isArray(e) ? e.map(cl) : String(e)
}
function AN(e) {
    const t = Object.create(null)
      , n = /([^\s,;=]+)\s*(?:=\s*([^,;]+))?/g;
    let r;
    for (; r = n.exec(e); )
        t[r[1]] = r[2];
    return t
}
const DN = e => /^[-_a-zA-Z0-9^`|~,!#$%&'*+.]+$/.test(e.trim());
function Lu(e, t, n, r, i) {
    if (M.isFunction(r))
        return r.call(this, t, n);
    if (i && (t = n),
    !!M.isString(t)) {
        if (M.isString(r))
            return t.indexOf(r) !== -1;
        if (M.isRegExp(r))
            return r.test(t)
    }
}
function $N(e) {
    return e.trim().toLowerCase().replace(/([a-z\d])(\w*)/g, (t, n, r) => n.toUpperCase() + r)
}
function FN(e, t) {
    const n = M.toCamelCase(" " + t);
    ["get", "set", "has"].forEach(r => {
        Object.defineProperty(e, r + n, {
            value: function(i, s, o) {
                return this[r].call(this, t, i, s, o)
            },
            configurable: !0
        })
    }
    )
}
class Lc {
    constructor(t) {
        t && this.set(t)
    }
    set(t, n, r) {
        const i = this;
        function s(l, a, c) {
            const d = zs(a);
            if (!d)
                throw new Error("header name must be a non-empty string");
            const f = M.findKey(i, d);
            (!f || i[f] === void 0 || c === !0 || c === void 0 && i[f] !== !1) && (i[f || a] = cl(l))
        }
        const o = (l, a) => M.forEach(l, (c, d) => s(c, d, a));
        if (M.isPlainObject(t) || t instanceof this.constructor)
            o(t, n);
        else if (M.isString(t) && (t = t.trim()) && !DN(t))
            o(ON(t), n);
        else if (M.isHeaders(t))
            for (const [l,a] of t.entries())
                s(a, l, r);
        else
            t != null && s(n, t, r);
        return this
    }
    get(t, n) {
        if (t = zs(t),
        t) {
            const r = M.findKey(this, t);
            if (r) {
                const i = this[r];
                if (!n)
                    return i;
                if (n === !0)
                    return AN(i);
                if (M.isFunction(n))
                    return n.call(this, i, r);
                if (M.isRegExp(n))
                    return n.exec(i);
                throw new TypeError("parser must be boolean|regexp|function")
            }
        }
    }
    has(t, n) {
        if (t = zs(t),
        t) {
            const r = M.findKey(this, t);
            return !!(r && this[r] !== void 0 && (!n || Lu(this, this[r], r, n)))
        }
        return !1
    }
    delete(t, n) {
        const r = this;
        let i = !1;
        function s(o) {
            if (o = zs(o),
            o) {
                const l = M.findKey(r, o);
                l && (!n || Lu(r, r[l], l, n)) && (delete r[l],
                i = !0)
            }
        }
        return M.isArray(t) ? t.forEach(s) : s(t),
        i
    }
    clear(t) {
        const n = Object.keys(this);
        let r = n.length
          , i = !1;
        for (; r--; ) {
            const s = n[r];
            (!t || Lu(this, this[s], s, t, !0)) && (delete this[s],
            i = !0)
        }
        return i
    }
    normalize(t) {
        const n = this
          , r = {};
        return M.forEach(this, (i, s) => {
            const o = M.findKey(r, s);
            if (o) {
                n[o] = cl(i),
                delete n[s];
                return
            }
            const l = t ? $N(s) : String(s).trim();
            l !== s && delete n[s],
            n[l] = cl(i),
            r[l] = !0
        }
        ),
        this
    }
    concat(...t) {
        return this.constructor.concat(this, ...t)
    }
    toJSON(t) {
        const n = Object.create(null);
        return M.forEach(this, (r, i) => {
            r != null && r !== !1 && (n[i] = t && M.isArray(r) ? r.join(", ") : r)
        }
        ),
        n
    }
    [Symbol.iterator]() {
        return Object.entries(this.toJSON())[Symbol.iterator]()
    }
    toString() {
        return Object.entries(this.toJSON()).map( ([t,n]) => t + ": " + n).join(`
`)
    }
    get[Symbol.toStringTag]() {
        return "AxiosHeaders"
    }
    static from(t) {
        return t instanceof this ? t : new this(t)
    }
    static concat(t, ...n) {
        const r = new this(t);
        return n.forEach(i => r.set(i)),
        r
    }
    static accessor(t) {
        const r = (this[l_] = this[l_] = {
            accessors: {}
        }).accessors
          , i = this.prototype;
        function s(o) {
            const l = zs(o);
            r[l] || (FN(i, o),
            r[l] = !0)
        }
        return M.isArray(t) ? t.forEach(s) : s(t),
        this
    }
}
Lc.accessor(["Content-Type", "Content-Length", "Accept", "Accept-Encoding", "User-Agent", "Authorization"]);
M.reduceDescriptors(Lc.prototype, ({value: e}, t) => {
    let n = t[0].toUpperCase() + t.slice(1);
    return {
        get: () => e,
        set(r) {
            this[n] = r
        }
    }
}
);
M.freezeMethods(Lc);
const xn = Lc;
function Mu(e, t) {
    const n = this || tm
      , r = t || n
      , i = xn.from(r.headers);
    let s = r.data;
    return M.forEach(e, function(l) {
        s = l.call(n, s, i.normalize(), t ? t.status : void 0)
    }),
    i.normalize(),
    s
}
function ix(e) {
    return !!(e && e.__CANCEL__)
}
function Ss(e, t, n) {
    se.call(this, e ?? "canceled", se.ERR_CANCELED, t, n),
    this.name = "CanceledError"
}
M.inherits(Ss, se, {
    __CANCEL__: !0
});
function sx(e, t, n) {
    const r = n.config.validateStatus;
    !n.status || !r || r(n.status) ? e(n) : t(new se("Request failed with status code " + n.status,[se.ERR_BAD_REQUEST, se.ERR_BAD_RESPONSE][Math.floor(n.status / 100) - 4],n.config,n.request,n))
}
function VN(e) {
    const t = /^([-+\w]{1,25})(:?\/\/|:)/.exec(e);
    return t && t[1] || ""
}
function zN(e, t) {
    e = e || 10;
    const n = new Array(e)
      , r = new Array(e);
    let i = 0, s = 0, o;
    return t = t !== void 0 ? t : 1e3,
    function(a) {
        const c = Date.now()
          , d = r[s];
        o || (o = c),
        n[i] = a,
        r[i] = c;
        let f = s
          , p = 0;
        for (; f !== i; )
            p += n[f++],
            f = f % e;
        if (i = (i + 1) % e,
        i === s && (s = (s + 1) % e),
        c - o < t)
            return;
        const h = d && c - d;
        return h ? Math.round(p * 1e3 / h) : void 0
    }
}
function BN(e, t) {
    let n = 0, r = 1e3 / t, i, s;
    const o = (c, d=Date.now()) => {
        n = d,
        i = null,
        s && (clearTimeout(s),
        s = null),
        e.apply(null, c)
    }
    ;
    return [ (...c) => {
        const d = Date.now()
          , f = d - n;
        f >= r ? o(c, d) : (i = c,
        s || (s = setTimeout( () => {
            s = null,
            o(i)
        }
        , r - f)))
    }
    , () => i && o(i)]
}
const Wl = (e, t, n=3) => {
    let r = 0;
    const i = zN(50, 250);
    return BN(s => {
        const o = s.loaded
          , l = s.lengthComputable ? s.total : void 0
          , a = o - r
          , c = i(a)
          , d = o <= l;
        r = o;
        const f = {
            loaded: o,
            total: l,
            progress: l ? o / l : void 0,
            bytes: a,
            rate: c || void 0,
            estimated: c && l && d ? (l - o) / c : void 0,
            event: s,
            lengthComputable: l != null,
            [t ? "download" : "upload"]: !0
        };
        e(f)
    }
    , n)
}
  , c_ = (e, t) => {
    const n = e != null;
    return [r => t[0]({
        lengthComputable: n,
        total: e,
        loaded: r
    }), t[1]]
}
  , u_ = e => (...t) => M.asap( () => e(...t))
  , UN = wn.hasStandardBrowserEnv ? function() {
    const t = /(msie|trident)/i.test(navigator.userAgent)
      , n = document.createElement("a");
    let r;
    function i(s) {
        let o = s;
        return t && (n.setAttribute("href", o),
        o = n.href),
        n.setAttribute("href", o),
        {
            href: n.href,
            protocol: n.protocol ? n.protocol.replace(/:$/, "") : "",
            host: n.host,
            search: n.search ? n.search.replace(/^\?/, "") : "",
            hash: n.hash ? n.hash.replace(/^#/, "") : "",
            hostname: n.hostname,
            port: n.port,
            pathname: n.pathname.charAt(0) === "/" ? n.pathname : "/" + n.pathname
        }
    }
    return r = i(window.location.href),
    function(o) {
        const l = M.isString(o) ? i(o) : o;
        return l.protocol === r.protocol && l.host === r.host
    }
}() : function() {
    return function() {
        return !0
    }
}()
  , HN = wn.hasStandardBrowserEnv ? {
    write(e, t, n, r, i, s) {
        const o = [e + "=" + encodeURIComponent(t)];
        M.isNumber(n) && o.push("expires=" + new Date(n).toGMTString()),
        M.isString(r) && o.push("path=" + r),
        M.isString(i) && o.push("domain=" + i),
        s === !0 && o.push("secure"),
        document.cookie = o.join("; ")
    },
    read(e) {
        const t = document.cookie.match(new RegExp("(^|;\\s*)(" + e + ")=([^;]*)"));
        return t ? decodeURIComponent(t[3]) : null
    },
    remove(e) {
        this.write(e, "", Date.now() - 864e5)
    }
} : {
    write() {},
    read() {
        return null
    },
    remove() {}
};
function GN(e) {
    return /^([a-z][a-z\d+\-.]*:)?\/\//i.test(e)
}
function WN(e, t) {
    return t ? e.replace(/\/?\/$/, "") + "/" + t.replace(/^\/+/, "") : e
}
function ox(e, t) {
    return e && !GN(t) ? WN(e, t) : t
}
const d_ = e => e instanceof xn ? {
    ...e
} : e;
function _i(e, t) {
    t = t || {};
    const n = {};
    function r(c, d, f) {
        return M.isPlainObject(c) && M.isPlainObject(d) ? M.merge.call({
            caseless: f
        }, c, d) : M.isPlainObject(d) ? M.merge({}, d) : M.isArray(d) ? d.slice() : d
    }
    function i(c, d, f) {
        if (M.isUndefined(d)) {
            if (!M.isUndefined(c))
                return r(void 0, c, f)
        } else
            return r(c, d, f)
    }
    function s(c, d) {
        if (!M.isUndefined(d))
            return r(void 0, d)
    }
    function o(c, d) {
        if (M.isUndefined(d)) {
            if (!M.isUndefined(c))
                return r(void 0, c)
        } else
            return r(void 0, d)
    }
    function l(c, d, f) {
        if (f in t)
            return r(c, d);
        if (f in e)
            return r(void 0, c)
    }
    const a = {
        url: s,
        method: s,
        data: s,
        baseURL: o,
        transformRequest: o,
        transformResponse: o,
        paramsSerializer: o,
        timeout: o,
        timeoutMessage: o,
        withCredentials: o,
        withXSRFToken: o,
        adapter: o,
        responseType: o,
        xsrfCookieName: o,
        xsrfHeaderName: o,
        onUploadProgress: o,
        onDownloadProgress: o,
        decompress: o,
        maxContentLength: o,
        maxBodyLength: o,
        beforeRedirect: o,
        transport: o,
        httpAgent: o,
        httpsAgent: o,
        cancelToken: o,
        socketPath: o,
        responseEncoding: o,
        validateStatus: l,
        headers: (c, d) => i(d_(c), d_(d), !0)
    };
    return M.forEach(Object.keys(Object.assign({}, e, t)), function(d) {
        const f = a[d] || i
          , p = f(e[d], t[d], d);
        M.isUndefined(p) && f !== l || (n[d] = p)
    }),
    n
}
const ax = e => {
    const t = _i({}, e);
    let {data: n, withXSRFToken: r, xsrfHeaderName: i, xsrfCookieName: s, headers: o, auth: l} = t;
    t.headers = o = xn.from(o),
    t.url = tx(ox(t.baseURL, t.url), e.params, e.paramsSerializer),
    l && o.set("Authorization", "Basic " + btoa((l.username || "") + ":" + (l.password ? unescape(encodeURIComponent(l.password)) : "")));
    let a;
    if (M.isFormData(n)) {
        if (wn.hasStandardBrowserEnv || wn.hasStandardBrowserWebWorkerEnv)
            o.setContentType(void 0);
        else if ((a = o.getContentType()) !== !1) {
            const [c,...d] = a ? a.split(";").map(f => f.trim()).filter(Boolean) : [];
            o.setContentType([c || "multipart/form-data", ...d].join("; "))
        }
    }
    if (wn.hasStandardBrowserEnv && (r && M.isFunction(r) && (r = r(t)),
    r || r !== !1 && UN(t.url))) {
        const c = i && s && HN.read(s);
        c && o.set(i, c)
    }
    return t
}
  , qN = typeof XMLHttpRequest < "u"
  , YN = qN && function(e) {
    return new Promise(function(n, r) {
        const i = ax(e);
        let s = i.data;
        const o = xn.from(i.headers).normalize();
        let {responseType: l, onUploadProgress: a, onDownloadProgress: c} = i, d, f, p, h, m;
        function _() {
            h && h(),
            m && m(),
            i.cancelToken && i.cancelToken.unsubscribe(d),
            i.signal && i.signal.removeEventListener("abort", d)
        }
        let x = new XMLHttpRequest;
        x.open(i.method.toUpperCase(), i.url, !0),
        x.timeout = i.timeout;
        function v() {
            if (!x)
                return;
            const y = xn.from("getAllResponseHeaders"in x && x.getAllResponseHeaders())
              , C = {
                data: !l || l === "text" || l === "json" ? x.responseText : x.response,
                status: x.status,
                statusText: x.statusText,
                headers: y,
                config: e,
                request: x
            };
            sx(function(T) {
                n(T),
                _()
            }, function(T) {
                r(T),
                _()
            }, C),
            x = null
        }
        "onloadend"in x ? x.onloadend = v : x.onreadystatechange = function() {
            !x || x.readyState !== 4 || x.status === 0 && !(x.responseURL && x.responseURL.indexOf("file:") === 0) || setTimeout(v)
        }
        ,
        x.onabort = function() {
            x && (r(new se("Request aborted",se.ECONNABORTED,e,x)),
            x = null)
        }
        ,
        x.onerror = function() {
            r(new se("Network Error",se.ERR_NETWORK,e,x)),
            x = null
        }
        ,
        x.ontimeout = function() {
            let b = i.timeout ? "timeout of " + i.timeout + "ms exceeded" : "timeout exceeded";
            const C = i.transitional || nx;
            i.timeoutErrorMessage && (b = i.timeoutErrorMessage),
            r(new se(b,C.clarifyTimeoutError ? se.ETIMEDOUT : se.ECONNABORTED,e,x)),
            x = null
        }
        ,
        s === void 0 && o.setContentType(null),
        "setRequestHeader"in x && M.forEach(o.toJSON(), function(b, C) {
            x.setRequestHeader(C, b)
        }),
        M.isUndefined(i.withCredentials) || (x.withCredentials = !!i.withCredentials),
        l && l !== "json" && (x.responseType = i.responseType),
        c && ([p,m] = Wl(c, !0),
        x.addEventListener("progress", p)),
        a && x.upload && ([f,h] = Wl(a),
        x.upload.addEventListener("progress", f),
        x.upload.addEventListener("loadend", h)),
        (i.cancelToken || i.signal) && (d = y => {
            x && (r(!y || y.type ? new Ss(null,e,x) : y),
            x.abort(),
            x = null)
        }
        ,
        i.cancelToken && i.cancelToken.subscribe(d),
        i.signal && (i.signal.aborted ? d() : i.signal.addEventListener("abort", d)));
        const w = VN(i.url);
        if (w && wn.protocols.indexOf(w) === -1) {
            r(new se("Unsupported protocol " + w + ":",se.ERR_BAD_REQUEST,e));
            return
        }
        x.send(s || null)
    }
    )
}
  , KN = (e, t) => {
    let n = new AbortController, r;
    const i = function(a) {
        if (!r) {
            r = !0,
            o();
            const c = a instanceof Error ? a : this.reason;
            n.abort(c instanceof se ? c : new Ss(c instanceof Error ? c.message : c))
        }
    };
    let s = t && setTimeout( () => {
        i(new se(`timeout ${t} of ms exceeded`,se.ETIMEDOUT))
    }
    , t);
    const o = () => {
        e && (s && clearTimeout(s),
        s = null,
        e.forEach(a => {
            a && (a.removeEventListener ? a.removeEventListener("abort", i) : a.unsubscribe(i))
        }
        ),
        e = null)
    }
    ;
    e.forEach(a => a && a.addEventListener && a.addEventListener("abort", i));
    const {signal: l} = n;
    return l.unsubscribe = o,
    [l, () => {
        s && clearTimeout(s),
        s = null
    }
    ]
}
  , XN = KN
  , QN = function*(e, t) {
    let n = e.byteLength;
    if (!t || n < t) {
        yield e;
        return
    }
    let r = 0, i;
    for (; r < n; )
        i = r + t,
        yield e.slice(r, i),
        r = i
}
  , JN = async function*(e, t, n) {
    for await(const r of e)
        yield*QN(ArrayBuffer.isView(r) ? r : await n(String(r)), t)
}
  , f_ = (e, t, n, r, i) => {
    const s = JN(e, t, i);
    let o = 0, l, a = c => {
        l || (l = !0,
        r && r(c))
    }
    ;
    return new ReadableStream({
        async pull(c) {
            try {
                const {done: d, value: f} = await s.next();
                if (d) {
                    a(),
                    c.close();
                    return
                }
                let p = f.byteLength;
                if (n) {
                    let h = o += p;
                    n(h)
                }
                c.enqueue(new Uint8Array(f))
            } catch (d) {
                throw a(d),
                d
            }
        },
        cancel(c) {
            return a(c),
            s.return()
        }
    },{
        highWaterMark: 2
    })
}
  , Mc = typeof fetch == "function" && typeof Request == "function" && typeof Response == "function"
  , lx = Mc && typeof ReadableStream == "function"
  , lf = Mc && (typeof TextEncoder == "function" ? (e => t => e.encode(t))(new TextEncoder) : async e => new Uint8Array(await new Response(e).arrayBuffer()))
  , cx = (e, ...t) => {
    try {
        return !!e(...t)
    } catch {
        return !1
    }
}
  , ZN = lx && cx( () => {
    let e = !1;
    const t = new Request(wn.origin,{
        body: new ReadableStream,
        method: "POST",
        get duplex() {
            return e = !0,
            "half"
        }
    }).headers.has("Content-Type");
    return e && !t
}
)
  , p_ = 64 * 1024
  , cf = lx && cx( () => M.isReadableStream(new Response("").body))
  , ql = {
    stream: cf && (e => e.body)
};
Mc && (e => {
    ["text", "arrayBuffer", "blob", "formData", "stream"].forEach(t => {
        !ql[t] && (ql[t] = M.isFunction(e[t]) ? n => n[t]() : (n, r) => {
            throw new se(`Response type '${t}' is not supported`,se.ERR_NOT_SUPPORT,r)
        }
        )
    }
    )
}
)(new Response);
const eR = async e => {
    if (e == null)
        return 0;
    if (M.isBlob(e))
        return e.size;
    if (M.isSpecCompliantForm(e))
        return (await new Request(e).arrayBuffer()).byteLength;
    if (M.isArrayBufferView(e) || M.isArrayBuffer(e))
        return e.byteLength;
    if (M.isURLSearchParams(e) && (e = e + ""),
    M.isString(e))
        return (await lf(e)).byteLength
}
  , tR = async (e, t) => {
    const n = M.toFiniteNumber(e.getContentLength());
    return n ?? eR(t)
}
  , nR = Mc && (async e => {
    let {url: t, method: n, data: r, signal: i, cancelToken: s, timeout: o, onDownloadProgress: l, onUploadProgress: a, responseType: c, headers: d, withCredentials: f="same-origin", fetchOptions: p} = ax(e);
    c = c ? (c + "").toLowerCase() : "text";
    let[h,m] = i || s || o ? XN([i, s], o) : [], _, x;
    const v = () => {
        !_ && setTimeout( () => {
            h && h.unsubscribe()
        }
        ),
        _ = !0
    }
    ;
    let w;
    try {
        if (a && ZN && n !== "get" && n !== "head" && (w = await tR(d, r)) !== 0) {
            let S = new Request(t,{
                method: "POST",
                body: r,
                duplex: "half"
            }), T;
            if (M.isFormData(r) && (T = S.headers.get("content-type")) && d.setContentType(T),
            S.body) {
                const [k,P] = c_(w, Wl(u_(a)));
                r = f_(S.body, p_, k, P, lf)
            }
        }
        M.isString(f) || (f = f ? "include" : "omit"),
        x = new Request(t,{
            ...p,
            signal: h,
            method: n.toUpperCase(),
            headers: d.normalize().toJSON(),
            body: r,
            duplex: "half",
            credentials: f
        });
        let y = await fetch(x);
        const b = cf && (c === "stream" || c === "response");
        if (cf && (l || b)) {
            const S = {};
            ["status", "statusText", "headers"].forEach(R => {
                S[R] = y[R]
            }
            );
            const T = M.toFiniteNumber(y.headers.get("content-length"))
              , [k,P] = l && c_(T, Wl(u_(l), !0)) || [];
            y = new Response(f_(y.body, p_, k, () => {
                P && P(),
                b && v()
            }
            , lf),S)
        }
        c = c || "text";
        let C = await ql[M.findKey(ql, c) || "text"](y, e);
        return !b && v(),
        m && m(),
        await new Promise( (S, T) => {
            sx(S, T, {
                data: C,
                headers: xn.from(y.headers),
                status: y.status,
                statusText: y.statusText,
                config: e,
                request: x
            })
        }
        )
    } catch (y) {
        throw v(),
        y && y.name === "TypeError" && /fetch/i.test(y.message) ? Object.assign(new se("Network Error",se.ERR_NETWORK,e,x), {
            cause: y.cause || y
        }) : se.from(y, y && y.code, e, x)
    }
}
)
  , uf = {
    http: _N,
    xhr: YN,
    fetch: nR
};
M.forEach(uf, (e, t) => {
    if (e) {
        try {
            Object.defineProperty(e, "name", {
                value: t
            })
        } catch {}
        Object.defineProperty(e, "adapterName", {
            value: t
        })
    }
}
);
const m_ = e => `- ${e}`
  , rR = e => M.isFunction(e) || e === null || e === !1
  , ux = {
    getAdapter: e => {
        e = M.isArray(e) ? e : [e];
        const {length: t} = e;
        let n, r;
        const i = {};
        for (let s = 0; s < t; s++) {
            n = e[s];
            let o;
            if (r = n,
            !rR(n) && (r = uf[(o = String(n)).toLowerCase()],
            r === void 0))
                throw new se(`Unknown adapter '${o}'`);
            if (r)
                break;
            i[o || "#" + s] = r
        }
        if (!r) {
            const s = Object.entries(i).map( ([l,a]) => `adapter ${l} ` + (a === !1 ? "is not supported by the environment" : "is not available in the build"));
            let o = t ? s.length > 1 ? `since :
` + s.map(m_).join(`
`) : " " + m_(s[0]) : "as no adapter specified";
            throw new se("There is no suitable adapter to dispatch the request " + o,"ERR_NOT_SUPPORT")
        }
        return r
    }
    ,
    adapters: uf
};
function Ou(e) {
    if (e.cancelToken && e.cancelToken.throwIfRequested(),
    e.signal && e.signal.aborted)
        throw new Ss(null,e)
}
function h_(e) {
    return Ou(e),
    e.headers = xn.from(e.headers),
    e.data = Mu.call(e, e.transformRequest),
    ["post", "put", "patch"].indexOf(e.method) !== -1 && e.headers.setContentType("application/x-www-form-urlencoded", !1),
    ux.getAdapter(e.adapter || tm.adapter)(e).then(function(r) {
        return Ou(e),
        r.data = Mu.call(e, e.transformResponse, r),
        r.headers = xn.from(r.headers),
        r
    }, function(r) {
        return ix(r) || (Ou(e),
        r && r.response && (r.response.data = Mu.call(e, e.transformResponse, r.response),
        r.response.headers = xn.from(r.response.headers))),
        Promise.reject(r)
    })
}
const dx = "1.7.4"
  , nm = {};
["object", "boolean", "number", "function", "string", "symbol"].forEach( (e, t) => {
    nm[e] = function(r) {
        return typeof r === e || "a" + (t < 1 ? "n " : " ") + e
    }
}
);
const g_ = {};
nm.transitional = function(t, n, r) {
    function i(s, o) {
        return "[Axios v" + dx + "] Transitional option '" + s + "'" + o + (r ? ". " + r : "")
    }
    return (s, o, l) => {
        if (t === !1)
            throw new se(i(o, " has been removed" + (n ? " in " + n : "")),se.ERR_DEPRECATED);
        return n && !g_[o] && (g_[o] = !0,
        console.warn(i(o, " has been deprecated since v" + n + " and will be removed in the near future"))),
        t ? t(s, o, l) : !0
    }
}
;
function iR(e, t, n) {
    if (typeof e != "object")
        throw new se("options must be an object",se.ERR_BAD_OPTION_VALUE);
    const r = Object.keys(e);
    let i = r.length;
    for (; i-- > 0; ) {
        const s = r[i]
          , o = t[s];
        if (o) {
            const l = e[s]
              , a = l === void 0 || o(l, s, e);
            if (a !== !0)
                throw new se("option " + s + " must be " + a,se.ERR_BAD_OPTION_VALUE);
            continue
        }
        if (n !== !0)
            throw new se("Unknown option " + s,se.ERR_BAD_OPTION)
    }
}
const df = {
    assertOptions: iR,
    validators: nm
}
  , or = df.validators;
class Yl {
    constructor(t) {
        this.defaults = t,
        this.interceptors = {
            request: new a_,
            response: new a_
        }
    }
    async request(t, n) {
        try {
            return await this._request(t, n)
        } catch (r) {
            if (r instanceof Error) {
                let i;
                Error.captureStackTrace ? Error.captureStackTrace(i = {}) : i = new Error;
                const s = i.stack ? i.stack.replace(/^.+\n/, "") : "";
                try {
                    r.stack ? s && !String(r.stack).endsWith(s.replace(/^.+\n.+\n/, "")) && (r.stack += `
` + s) : r.stack = s
                } catch {}
            }
            throw r
        }
    }
    _request(t, n) {
        typeof t == "string" ? (n = n || {},
        n.url = t) : n = t || {},
        n = _i(this.defaults, n);
        const {transitional: r, paramsSerializer: i, headers: s} = n;
        r !== void 0 && df.assertOptions(r, {
            silentJSONParsing: or.transitional(or.boolean),
            forcedJSONParsing: or.transitional(or.boolean),
            clarifyTimeoutError: or.transitional(or.boolean)
        }, !1),
        i != null && (M.isFunction(i) ? n.paramsSerializer = {
            serialize: i
        } : df.assertOptions(i, {
            encode: or.function,
            serialize: or.function
        }, !0)),
        n.method = (n.method || this.defaults.method || "get").toLowerCase();
        let o = s && M.merge(s.common, s[n.method]);
        s && M.forEach(["delete", "get", "head", "post", "put", "patch", "common"], m => {
            delete s[m]
        }
        ),
        n.headers = xn.concat(o, s);
        const l = [];
        let a = !0;
        this.interceptors.request.forEach(function(_) {
            typeof _.runWhen == "function" && _.runWhen(n) === !1 || (a = a && _.synchronous,
            l.unshift(_.fulfilled, _.rejected))
        });
        const c = [];
        this.interceptors.response.forEach(function(_) {
            c.push(_.fulfilled, _.rejected)
        });
        let d, f = 0, p;
        if (!a) {
            const m = [h_.bind(this), void 0];
            for (m.unshift.apply(m, l),
            m.push.apply(m, c),
            p = m.length,
            d = Promise.resolve(n); f < p; )
                d = d.then(m[f++], m[f++]);
            return d
        }
        p = l.length;
        let h = n;
        for (f = 0; f < p; ) {
            const m = l[f++]
              , _ = l[f++];
            try {
                h = m(h)
            } catch (x) {
                _.call(this, x);
                break
            }
        }
        try {
            d = h_.call(this, h)
        } catch (m) {
            return Promise.reject(m)
        }
        for (f = 0,
        p = c.length; f < p; )
            d = d.then(c[f++], c[f++]);
        return d
    }
    getUri(t) {
        t = _i(this.defaults, t);
        const n = ox(t.baseURL, t.url);
        return tx(n, t.params, t.paramsSerializer)
    }
}
M.forEach(["delete", "get", "head", "options"], function(t) {
    Yl.prototype[t] = function(n, r) {
        return this.request(_i(r || {}, {
            method: t,
            url: n,
            data: (r || {}).data
        }))
    }
});
M.forEach(["post", "put", "patch"], function(t) {
    function n(r) {
        return function(s, o, l) {
            return this.request(_i(l || {}, {
                method: t,
                headers: r ? {
                    "Content-Type": "multipart/form-data"
                } : {},
                url: s,
                data: o
            }))
        }
    }
    Yl.prototype[t] = n(),
    Yl.prototype[t + "Form"] = n(!0)
});
const ul = Yl;
class rm {
    constructor(t) {
        if (typeof t != "function")
            throw new TypeError("executor must be a function.");
        let n;
        this.promise = new Promise(function(s) {
            n = s
        }
        );
        const r = this;
        this.promise.then(i => {
            if (!r._listeners)
                return;
            let s = r._listeners.length;
            for (; s-- > 0; )
                r._listeners[s](i);
            r._listeners = null
        }
        ),
        this.promise.then = i => {
            let s;
            const o = new Promise(l => {
                r.subscribe(l),
                s = l
            }
            ).then(i);
            return o.cancel = function() {
                r.unsubscribe(s)
            }
            ,
            o
        }
        ,
        t(function(s, o, l) {
            r.reason || (r.reason = new Ss(s,o,l),
            n(r.reason))
        })
    }
    throwIfRequested() {
        if (this.reason)
            throw this.reason
    }
    subscribe(t) {
        if (this.reason) {
            t(this.reason);
            return
        }
        this._listeners ? this._listeners.push(t) : this._listeners = [t]
    }
    unsubscribe(t) {
        if (!this._listeners)
            return;
        const n = this._listeners.indexOf(t);
        n !== -1 && this._listeners.splice(n, 1)
    }
    static source() {
        let t;
        return {
            token: new rm(function(i) {
                t = i
            }
            ),
            cancel: t
        }
    }
}
const sR = rm;
function oR(e) {
    return function(n) {
        return e.apply(null, n)
    }
}
function aR(e) {
    return M.isObject(e) && e.isAxiosError === !0
}
const ff = {
    Continue: 100,
    SwitchingProtocols: 101,
    Processing: 102,
    EarlyHints: 103,
    Ok: 200,
    Created: 201,
    Accepted: 202,
    NonAuthoritativeInformation: 203,
    NoContent: 204,
    ResetContent: 205,
    PartialContent: 206,
    MultiStatus: 207,
    AlreadyReported: 208,
    ImUsed: 226,
    MultipleChoices: 300,
    MovedPermanently: 301,
    Found: 302,
    SeeOther: 303,
    NotModified: 304,
    UseProxy: 305,
    Unused: 306,
    TemporaryRedirect: 307,
    PermanentRedirect: 308,
    BadRequest: 400,
    Unauthorized: 401,
    PaymentRequired: 402,
    Forbidden: 403,
    NotFound: 404,
    MethodNotAllowed: 405,
    NotAcceptable: 406,
    ProxyAuthenticationRequired: 407,
    RequestTimeout: 408,
    Conflict: 409,
    Gone: 410,
    LengthRequired: 411,
    PreconditionFailed: 412,
    PayloadTooLarge: 413,
    UriTooLong: 414,
    UnsupportedMediaType: 415,
    RangeNotSatisfiable: 416,
    ExpectationFailed: 417,
    ImATeapot: 418,
    MisdirectedRequest: 421,
    UnprocessableEntity: 422,
    Locked: 423,
    FailedDependency: 424,
    TooEarly: 425,
    UpgradeRequired: 426,
    PreconditionRequired: 428,
    TooManyRequests: 429,
    RequestHeaderFieldsTooLarge: 431,
    UnavailableForLegalReasons: 451,
    InternalServerError: 500,
    NotImplemented: 501,
    BadGateway: 502,
    ServiceUnavailable: 503,
    GatewayTimeout: 504,
    HttpVersionNotSupported: 505,
    VariantAlsoNegotiates: 506,
    InsufficientStorage: 507,
    LoopDetected: 508,
    NotExtended: 510,
    NetworkAuthenticationRequired: 511
};
Object.entries(ff).forEach( ([e,t]) => {
    ff[t] = e
}
);
const lR = ff;
function fx(e) {
    const t = new ul(e)
      , n = Uw(ul.prototype.request, t);
    return M.extend(n, ul.prototype, t, {
        allOwnKeys: !0
    }),
    M.extend(n, t, null, {
        allOwnKeys: !0
    }),
    n.create = function(i) {
        return fx(_i(e, i))
    }
    ,
    n
}
const tt = fx(tm);
tt.Axios = ul;
tt.CanceledError = Ss;
tt.CancelToken = sR;
tt.isCancel = ix;
tt.VERSION = dx;
tt.toFormData = Ic;
tt.AxiosError = se;
tt.Cancel = tt.CanceledError;
tt.all = function(t) {
    return Promise.all(t)
}
;
tt.spread = oR;
tt.isAxiosError = aR;
tt.mergeConfig = _i;
tt.AxiosHeaders = xn;
tt.formToJSON = e => rx(M.isHTMLForm(e) ? new FormData(e) : e);
tt.getAdapter = ux.getAdapter;
tt.HttpStatusCode = lR;
tt.default = tt;
const cR = tt
  , uR = cR.create({
    baseURL: "https://mir-api.mironline.co.kr"
})
  , Ke = uR;
Ke.interceptors.response.use(e => e.data);
function dR(e) {
    return Ke.post(jj, e)
}
function fR(e) {
    return Ke.post(Nj, e)
}
const Bo = () => {
    g.useEffect( () => {
        const n = e();
        return () => {
            t(n)
        }
    }
    );
    const e = () => {
        const n = window.scrollY;
        return document.body.style.position = "fixed",
        document.body.style.width = "100%",
        document.body.style.top = `-${n}px`,
        document.body.style.overflowY = "scroll",
        n
    }
      , t = n => {
        document.body.style.position = "",
        document.body.style.width = "",
        document.body.style.top = "",
        document.body.style.overflowY = "",
        window.scrollTo(0, n)
    }
}
;
function pR(e, t) {
    const n = new Set;
    return e.filter(r => {
        const i = r[t];
        return n.has(i) ? !1 : (n.add(i),
        !0)
    }
    )
}
function mR(e, t) {
    e(n => {
        const r = [...n, t];
        return pR(r, "charName")
    }
    )
}
function pf(e) {
    return Ke.post(Rj, e)
}
const hR = () => {
    const e = vc()
      , [t,n] = g.useState({
        id: "",
        pw: ""
    })
      , [r,i] = g.useState(!1)
      , [s,o] = g.useState([])
      , [l,a] = g.useState([{
        applyExp: "",
        applyLevel: 0,
        charName: ""
    }])
      , [c,d] = g.useState("")
      , f = Tc(on.ILLUSION_BENEFIT_HERO)
      , p = {}.VITE_RETURN_URL
      , m = `${{}.VITE_LOGIN_HOST}/login/login.asp?UrlType=/main.asp`
      , _ = Ei("MIR_Info");
    g.useEffect( () => {
        pf({
            gameCode: 2,
            eventCode: 20231108
        }).then( ({eventInfo: {eventOpenYn: k}}) => {
            d(k)
        }
        )
    }
    , []);
    const x = g.useMemo( () => ({
        gameCode: 2,
        eventCode: 20231108,
        auth: {
            cusrVal: _
        }
    }), [_])
      , v = T => {
        if (T.preventDefault(),
        t.id !== "" && t.pw !== "") {
            if (t.id.length < 2) {
                alert("로그인 아이디는 2글자 이상 입력하셔야 합니다.");
                return
            }
            if (t.pw.length < 6) {
                alert("암호는 6글자 이상 입력하셔야 합니다.");
                return
            }
            const k = n_(t.id, "ONESTOP123456")
              , P = n_(t.pw, "ONESTOP123456");
            if (n({
                id: "",
                pw: ""
            }),
            k.length > 1 && P.length > 5) {
                sessionStorage.setItem("tab", "3");
                const A = T.currentTarget;
                A.elements.namedItem("id").value = k,
                A.elements.namedItem("pw").value = P,
                A.submit()
            }
        } else {
            alert("잠시 후 이용해 주시기 바랍니다.");
            return
        }
    }
      , w = T => {
        const {name: k, value: P} = T.target;
        n(R => ({
            ...R,
            [k]: P
        }))
    }
      , y = (T, k) => {
        i(T),
        k ? Bo().lockScroll() : Bo().openScroll()
    }
      , C = ms(async () => {
        const {code: T, characterInfo: k, eventInfo: P} = await dR(x);
        if (T === "200" && (d(P.eventOpenYn),
        o(k),
        y(!0, !0)),
        T === "M30003") {
            o([]),
            y(!0, !0);
            return
        }
        for (const {charName: R} of k) {
            const A = {
                gameCode: P.gameCode,
                eventCode: P.eventCode,
                charName: R,
                auth: {
                    cusrVal: _ || ""
                }
            }
              , {code: V, levelInfo: {charName: F, applyLevel: U, applyExp: Q}} = await fR(A);
            V === "200" && (a(Z => [...Z, {
                applyExp: Q,
                applyLevel: U,
                charName: F
            }]),
            mR(a, {
                applyExp: Q,
                applyLevel: U,
                charName: F
            }))
        }
    }
    , 200)
      , S = () => {
        y(!1, !1)
    }
    ;
    return g.useEffect( () => {
        (async () => {
            const T = {
                gameCode: 2,
                eventCode: 20231108
            }
              , {eventInfo: {eventOpenYn: k}} = await pf(T);
            d(k)
        }
        )()
    }
    , []),
    u.jsxs(Pc, {
        isLoading: f,
        header: e,
        hero: on.ILLUSION_BENEFIT_HERO,
        children: [u.jsxs("div", {
            className: `${_ ? Ru.level_wrap : Ru.level_wrap_not_login}`,
            children: [u.jsx("img", {
                src: on.ILLUSION_BENEFIT_LEVEL,
                alt: "level",
                onClick: C
            }), !_ && c === "Y" ? u.jsxs(u.Fragment, {
                children: [u.jsx("h3", {
                    children: "로그인 후 이용이 가능한 서비스 입니다."
                }), u.jsxs("form", {
                    method: "post",
                    action: m,
                    onSubmit: v,
                    id: "frmMemLogin",
                    name: "frmMemLogin",
                    children: [u.jsx("input", {
                        type: "text",
                        id: "id",
                        name: "id",
                        value: t.id,
                        onChange: w,
                        placeholder: "아이디 입력",
                        required: !0
                    }), u.jsx("input", {
                        type: "password",
                        id: "pw",
                        name: "pw",
                        value: t.pw,
                        onChange: w,
                        placeholder: "비밀번호 입력",
                        required: !0
                    }), u.jsx("input", {
                        type: "hidden",
                        name: "returnUrl",
                        id: "returnUrl",
                        value: p
                    }), u.jsx("button", {
                        type: "submit",
                        children: "로그인"
                    })]
                })]
            }) : u.jsx("h3", {
                className: Ru.not_open_event,
                children: "현재 이벤트 기간이 아닙니다."
            })]
        }), r ? u.jsx(vt.Consumer, {
            children: u.jsx(Pj, {
                characterInfo: s,
                onModalClose: S,
                isEventOpen: c,
                levelInfoData: l
            })
        }) : null]
    })
}
  , gR = "_container_kg313_1"
  , _R = "_tab_wrap_kg313_5"
  , vR = "_main_image_kg313_18"
  , yR = "_festival_kg313_22"
  , wR = "_gift_button_kg313_30"
  , xR = "_festival_button_kg313_37"
  , bR = "_gift_height_kg313_44"
  , SR = "_festival_height_kg313_47"
  , jn = {
    container: gR,
    tab_wrap: _R,
    main_image: vR,
    festival: yR,
    gift_button: wR,
    festival_button: xR,
    gift_height: bR,
    festival_height: SR
}
  , im = g.createContext({
    tabState: "gift",
    toggleState: () => {}
})
  , CR = ({children: e}) => {
    const [t,n] = g.useState("gift")
      , r = i => {
        n(i)
    }
    ;
    return u.jsx(im.Provider, {
        value: {
            tabState: t,
            toggleState: r
        },
        children: e
    })
}
  , kR = () => {
    const {tabState: e, toggleState: t} = g.useContext(im);
    if (!document.getElementById("embedim—snow")) {
        const s = (c, d) => Math.floor(Math.random() * (d - c + 1)) + c;
        let o = ".embedim-snow{position: absolute;width: 10px;height: 10px;background: white;border-radius: 50%;margin-top:-10px}"
          , l = "";
        for (let c = 1; c < 100; c++) {
            l += '<i class="embedim-snow"></i>';
            const d = s(0, 1e6) * 1e-4
              , f = s(-1e5, 1e5) * 1e-4
              , p = (s(0, 15e3) * 1e-4).toFixed(2);
            o += `.embedim-snow:nth-child(${c}){opacity:${(s(1, 1e4) * 1e-4).toFixed(2)};transform:translate(${d.toFixed(2)}vw,-10px) scale(${p});animation:fall-${c} ${s(5, 20)}s -${s(5, 35)}s linear infinite;}@keyframes fall-${c}{0%{transform:translate(${(d + f + 20).toFixed(2)}vw,0vh) scale(${p})}to{transform:translate(${(d + f - 30).toFixed(2)}vw, 105vh) scale(${p})}}`
        }
        const a = document.createElement("div");
        a.id = "embedim—snow",
        a.innerHTML = `<style>#embedim—snow{position:fixed;left:0;top:0;bottom:0;width:100vw;height:100vh;overflow:hidden;z-index:9999999;pointer-events:none}${o}</style>${l}`,
        document.body.appendChild(a)
    }
    const r = "https://web-cdn.mironline.co.kr/mir2/2023_event/1220/images/tab-1.jpg"
      , i = "https://web-cdn.mironline.co.kr/mir2/2023_event/1220/images/tab-2.jpg";
    return u.jsx("section", {
        className: jn.container,
        children: u.jsxs("div", {
            className: jn.tab_wrap,
            children: [e === "gift" ? u.jsx("img", {
                className: jn.gift,
                src: r,
                alt: "gift"
            }) : u.jsx("img", {
                className: jn.festival,
                src: i,
                alt: "festival"
            }), u.jsx("button", {
                className: `${jn.gift_button} ${e === "gift" ? jn.gift_height : jn.festival_height}`,
                onClick: () => t("gift"),
                type: "button"
            }), u.jsx("button", {
                className: `${jn.festival_button} ${e === "gift" ? jn.gift_height : jn.festival_height}`,
                onClick: () => t("festival"),
                type: "button"
            })]
        })
    })
}
  , ER = "_container_c2fts_1"
  , TR = {
    container: ER
}
  , sm = g.createContext({
    tabState: "gift",
    setTabState: () => {}
    ,
    isTabClick: !1,
    setIsTabClick: () => {}
})
  , PR = ({children: e}) => {
    const [t,n] = g.useState("gift")
      , [r,i] = g.useState(!1);
    return u.jsx(sm.Provider, {
        value: {
            tabState: t,
            setTabState: n,
            isTabClick: r,
            setIsTabClick: i
        },
        children: e
    })
}
  , jR = () => {
    const e = [{
        id: "gift",
        on: "https://web-cdn.mironline.co.kr/mir2/2024_event/0313/anniversary/images/tab-01-on.png",
        off: "https://web-cdn.mironline.co.kr/mir2/2024_event/0313/anniversary/images/tab-01-off.png",
        alt: "23주년 기념 선물"
    }, {
        id: "marketplace",
        on: "https://web-cdn.mironline.co.kr/mir2/2024_event/0313/anniversary/images/tab-02-on.png",
        off: "https://web-cdn.mironline.co.kr/mir2/2024_event/0313/anniversary/images/tab-02-off.png",
        alt: "신나는 축제장터"
    }, {
        id: "troupe",
        on: "https://web-cdn.mironline.co.kr/mir2/2024_event/0313/anniversary/images/tab-03-on.png",
        off: "https://web-cdn.mironline.co.kr/mir2/2024_event/0313/anniversary/images/tab-03-off.png",
        alt: "미르유랑상단"
    }]
      , {tabState: t, setTabState: n, setIsTabClick: r} = g.useContext(sm)
      , i = s => {
        n(s.currentTarget.id),
        r(!0)
    }
    ;
    return u.jsx("div", {
        className: TR.container,
        children: e.map( ({id: s, on: o, off: l, alt: a}) => u.jsx("button", {
            id: s,
            type: "button",
            onClick: i,
            children: u.jsx("img", {
                src: t === s ? o : l,
                alt: a
            })
        }, s))
    })
}
  , NR = "_container_3dn5f_1"
  , RR = "_float_3dn5f_4"
  , __ = {
    container: NR,
    float: RR
}
  , br = {
    errorMessage: "",
    productList: [{
        productIdx: 0,
        groupIdx: 0,
        productName: "",
        productImage: "",
        productDetail: "",
        productDetailText: "",
        productDisPrice: "",
        productPrice: "",
        productGiveNum: 0,
        itemPayCode: "",
        itemLink: "",
        productLimitedCount: 0,
        productOrderBy: "",
        regDate: "",
        plimitedUseNum: 0,
        plimitedSaleCount: 0,
        userPurchaseCount: 0
    }]
}
  , px = {
    pointLog: {
        totalCount: 0,
        list: [{
            pointContent: "",
            gameChar: "",
            point: 0,
            totalPoint: 0,
            addInfo: "",
            regDate: ""
        }]
    }
}
  , aa = g.createContext({
    swapProduct: br,
    setSwapProduct: () => {}
    ,
    randomProduct: br,
    setRandomProduct: () => {}
    ,
    banner: br,
    setBanner: () => {}
    ,
    bannerBuff: br,
    setBannerBuff: () => {}
    ,
    pointLog: px,
    setPointLog: () => {}
})
  , om = ({children: e}) => {
    const [t,n] = g.useState(br)
      , [r,i] = g.useState(br)
      , [s,o] = g.useState(br)
      , [l,a] = g.useState(br)
      , [c,d] = g.useState(px);
    return u.jsx(aa.Provider, {
        value: {
            swapProduct: t,
            setSwapProduct: n,
            randomProduct: r,
            setRandomProduct: i,
            banner: s,
            setBanner: o,
            bannerBuff: l,
            setBannerBuff: a,
            pointLog: c,
            setPointLog: d
        },
        children: e
    })
}
  , IR = () => (g.useEffect( () => {
    const e = document.documentElement
      , t = window.innerWidth - e.clientWidth;
    e.style.setProperty("min-width", 1920 - t + "px"),
    e.style.setProperty("background", "#02062b")
}
, []),
u.jsx("main", {
    className: __.container,
    children: u.jsx(om, {
        children: u.jsxs(PR, {
            children: [u.jsx(WR, {}), u.jsx(jR, {}), u.jsx(Si, {}), u.jsx("button", {
                type: "button",
                className: __.float,
                onClick: () => window.open("/20240320/dungeon", "_blank"),
                children: u.jsx("img", {
                    src: "https://web-cdn.mironline.co.kr/mir2/2024_event/0313/anniversary/images/float.webp",
                    alt: "floating-banner"
                })
            }), u.jsx(UR, {}), u.jsx(JR, {}), u.jsx(VR, {})]
        })
    })
}))
  , LR = "_container_wkrnf_1"
  , MR = "_header_wrap_wkrnf_8"
  , OR = "_block_today_wkrnf_16"
  , Da = {
    container: LR,
    header_wrap: MR,
    block_today: OR
}
  , AR = () => {
    const e = "https://web-cdn.mironline.co.kr/mir2/2024_event/0313/anniversary/images/mir-2-logo.png"
      , t = "https://web-cdn.mironline.co.kr/mir2/2024_event/0313/anniversary/images/close.png"
      , n = () => {
        he("EvtPage", "EvtPageDone", 1),
        he("evtCode", "20231108", 1),
        window.location.href = "https://mir2.mironline.co.kr/main"
    }
      , r = () => {
        he("EvtPageGoHome", "EvtPageDone", void 0, 2),
        he("evtCode", "20231108", void 0, 2),
        window.location.href = "https://mir2.mironline.co.kr/main"
    }
    ;
    return u.jsx("header", {
        className: Da.container,
        children: u.jsxs("div", {
            className: Da.header_wrap,
            children: [u.jsx("button", {
                type: "button",
                onClick: r,
                children: u.jsx("img", {
                    className: Da.logo,
                    src: e,
                    alt: "logo"
                })
            }), u.jsxs("button", {
                type: "button",
                className: Da.block_today,
                onClick: n,
                children: [u.jsx("span", {
                    children: "오늘 하루 그만보기"
                }), u.jsx("img", {
                    src: t,
                    alt: "x"
                })]
            })]
        })
    })
}
  , DR = "_container_1itpj_238"
  , $R = "_fadeIn_1itpj_1"
  , FR = {
    "a11y-hidden": "_a11y-hidden_1itpj_192",
    container: DR,
    fadeIn: $R
}
  , VR = () => u.jsx("footer", {
    className: FR.container,
    children: u.jsx("p", {
        children: "© ChuanQi IP Co., Ltd. All rights reserved."
    })
})
  , zR = "_container_ldymr_1"
  , BR = {
    container: zR
}
  , UR = () => u.jsx("div", {
    className: BR.container,
    children: u.jsx("img", {
        src: "https://web-cdn.mironline.co.kr/mir2/2024_event/0313/anniversary/images/notice.png",
        alt: "notice"
    })
})
  , HR = "_container_1c1md_1"
  , GR = "_hero_1c1md_6"
  , v_ = {
    container: HR,
    hero: GR
}
  , WR = () => {
    const e = "https://web-cdn.mironline.co.kr/mir2/2024_event/0313/anniversary/images/hero.png"
      , t = "https://web-cdn.mironline.co.kr/mir2/2024_event/0313/anniversary/video/hero-video.mp4";
    return u.jsxs("div", {
        className: v_.container,
        children: [u.jsx(AR, {}), u.jsx("video", {
            autoPlay: !0,
            loop: !0,
            muted: !0,
            playsInline: !0,
            crossOrigin: "anonymous",
            preload: "auto",
            poster: e,
            className: v_.hero,
            children: u.jsx("source", {
                src: t,
                type: "video/mp4"
            })
        })]
    })
}
  , qR = "_container_18143_1"
  , YR = {
    container: qR
}
  , KR = ({tabRef: e}) => u.jsxs("div", {
    ref: e,
    className: YR.container,
    children: [u.jsx("img", {
        src: "https://web-cdn.mironline.co.kr/mir2/2024_event/0313/anniversary/images/tab-1-01.png",
        alt: "tab1-1"
    }), u.jsx("img", {
        src: "https://web-cdn.mironline.co.kr/mir2/2024_event/0313/anniversary/images/tab-1-02.png",
        alt: "tab1-2"
    }), u.jsx("img", {
        src: "https://web-cdn.mironline.co.kr/mir2/2024_event/0313/anniversary/images/tab-1-03.png",
        alt: "tab1-3"
    })]
})
  , XR = "_container_d0qog_1"
  , QR = {
    container: XR
}
  , JR = () => {
    const [e,t] = g.useState(!1)
      , n = () => {
        window.scrollTo({
            top: 0,
            behavior: "smooth"
        })
    }
    ;
    return g.useEffect( () => {
        const r = () => {
            window.scrollY > 500 ? t(!0) : t(!1)
        }
        ;
        return window.addEventListener("scroll", r),
        () => {
            window.removeEventListener("scroll", r)
        }
    }
    , []),
    u.jsx(u.Fragment, {
        children: e && u.jsx("button", {
            type: "button",
            className: QR.container,
            onClick: n,
            children: u.jsx("img", {
                src: "https://web-cdn.mironline.co.kr/mir2/2024_event/0313/anniversary/images/btn-top.png",
                alt: "top"
            })
        })
    })
}
  , ZR = "_container_18143_1"
  , eI = {
    container: ZR
}
  , tI = ({tabRef: e}) => u.jsxs("div", {
    ref: e,
    className: eI.container,
    children: [u.jsx("img", {
        src: "https://web-cdn.mironline.co.kr/mir2/2024_event/0313/anniversary/images/tab-2-01.png",
        alt: "tab2-1"
    }), u.jsx("img", {
        src: "https://web-cdn.mironline.co.kr/mir2/2024_event/0313/anniversary/images/tab-2-02.png",
        alt: "tab2-2"
    })]
})
  , nI = "_container_oale0_1"
  , rI = "_login_wrap_oale0_11"
  , iI = "_login_user_id_pw_wrap_oale0_21"
  , sI = "_login_user_id_oale0_21"
  , oI = "_login_user_pw_oale0_28"
  , aI = "_login_submit_oale0_29"
  , lI = "_login_on_wrap_oale0_69"
  , cI = "_login_on_oale0_69"
  , uI = "_login_user_server_oale0_83"
  , dI = "_login_user_nickname_oale0_84"
  , fI = "_login_user_point_oale0_85"
  , pI = "_login_character_change_btn_oale0_86"
  , mI = "_login_character_change_btn_off_oale0_87"
  , hI = "_login_use_info_btn_oale0_88"
  , gI = "_login_use_info_btn_off_oale0_89"
  , _I = "_product_swap_container_oale0_132"
  , vI = "_product_swap_list_oale0_139"
  , yI = "_product_swap_wrap_oale0_145"
  , wI = "_product_swap_img_wrap_oale0_154"
  , xI = "_product_swap_img_oale0_154"
  , bI = "_product_swap_content_button_wrap_oale0_175"
  , SI = "_product_swap_content_oale0_175"
  , CI = "_product_swap_product_name_oale0_186"
  , kI = "_product_swap_content_point_oale0_192"
  , EI = "_product_swap_change_button_wrap_oale0_206"
  , TI = "_product_random_container_oale0_227"
  , PI = "_product_random_item_get_wrap_oale0_234"
  , jI = "_product_random_price_num_warp_oale0_240"
  , NI = "_product_random_price_num_bg_oale0_243"
  , RI = "_product_random_price_num_bg_off_oale0_246"
  , II = "_product_random_price_num_oale0_240"
  , LI = "_product_random_list_oale0_265"
  , MI = "_product_random_item_wrap_oale0_270"
  , OI = "_product_random_item_name_oale0_282"
  , AI = "_product_random_item_image_oale0_292"
  , DI = "_product_random_item_detail_oale0_300"
  , $I = "_product_random_item_block_oale0_310"
  , fe = {
    container: nI,
    login_wrap: rI,
    login_user_id_pw_wrap: iI,
    login_user_id: sI,
    login_user_pw: oI,
    login_submit: aI,
    login_on_wrap: lI,
    login_on: cI,
    login_user_server: uI,
    login_user_nickname: dI,
    login_user_point: fI,
    login_character_change_btn: pI,
    login_character_change_btn_off: mI,
    login_use_info_btn: hI,
    login_use_info_btn_off: gI,
    product_swap_container: _I,
    product_swap_list: vI,
    product_swap_wrap: yI,
    product_swap_img_wrap: wI,
    product_swap_img: xI,
    product_swap_content_button_wrap: bI,
    product_swap_content: SI,
    product_swap_product_name: CI,
    product_swap_content_point: kI,
    product_swap_change_button_wrap: EI,
    product_random_container: TI,
    product_random_item_get_wrap: PI,
    product_random_price_num_warp: jI,
    product_random_price_num_bg: NI,
    product_random_price_num_bg_off: RI,
    product_random_price_num: II,
    product_random_list: LI,
    product_random_item_wrap: MI,
    product_random_item_name: OI,
    product_random_item_image: AI,
    product_random_item_detail: DI,
    product_random_item_block: $I
};
function am(e) {
    return Ke.post("/event/auth/onestopAuth", e)
}
function FI() {
    return Ke.post("/event/comm/server/getCharServerList", {
        gameCode: 2
    })
}
function y_(e) {
    return Ke.post("/event/auth/member/getCharacter", e)
}
function VI(e) {
    return Ke.post("/event/auth/member/insertCharacter", e)
}
function Oc(e) {
    return Ke.post("/event/auth/onestopAuth/info", e)
}
function zI(e) {
    return Ke.post("/event/auth/member/insertNickName", e)
}
function mo(e) {
    return Ke.post("/event/promotion/getPoint", e)
}
function BI(e) {
    return Ke.post("/event/promotion/itemSwap", e)
}
function UI(e) {
    return Ke.post("/event/promotion/itemRandom", e)
}
const HI = "_modal_wrap_97phk_1"
  , GI = "_modal_bg_wrap_97phk_13"
  , WI = "_modal_detail_97phk_20"
  , qI = "_modal_close_button_97phk_42"
  , $a = {
    modal_wrap: HI,
    modal_bg_wrap: GI,
    modal_detail: WI,
    modal_close_button: qI
}
  , YI = ({productImage: e, productName: t, setIsModalOpen: n, setGetItemRandomClick: r}) => {
    g.useEffect( () => {
        r(!1)
    }
    , []),
    Bo();
    const i = () => {
        n(!1),
        r(!1)
    }
    ;
    return u.jsx("div", {
        className: $a.modal_wrap,
        children: u.jsxs("div", {
            className: $a.modal_bg_wrap,
            children: [u.jsx("img", {
                src: "https://web-cdn.mironline.co.kr/mir3/2018_event/0725/images/get_popup.png",
                alt: "popup"
            }), u.jsxs("div", {
                className: $a.modal_detail,
                children: [u.jsx("img", {
                    src: "https://upload.mironline.co.kr/" + e,
                    alt: t
                }), u.jsx("span", {
                    children: t
                })]
            }), u.jsx("button", {
                className: $a.modal_close_button,
                type: "button",
                onClick: i
            })]
        })
    })
}
  , KI = "_modal_bg_lexzw_1"
  , XI = "_select_wrap_lexzw_7"
  , QI = "_server_select_lexzw_15"
  , JI = "_character_select_lexzw_16"
  , ZI = "_button_wrap_lexzw_31"
  , Bs = {
    modal_bg: KI,
    select_wrap: XI,
    server_select: QI,
    character_select: JI,
    button_wrap: ZI
}
  , mx = {
    serverInfo: [{
        serverCode: 0,
        serverName: "",
        engServerName: ""
    }]
}
  , hx = {
    errorMessage: "",
    cusrVal: "",
    memCharacter: "",
    memCharacterYn: "",
    memServer: "",
    memServerCode: 0,
    nickName: "",
    nickYn: ""
}
  , gx = {
    errorMessage: "",
    userInfo: {
        indeonSeq: 0,
        rowNum: 0,
        userId: "",
        eventCode: 0,
        gameCode: 0,
        gameServer: "",
        gameChar: "",
        gameLevel: "0",
        nextGameLevel: "",
        drawResult: "",
        balanceResult: "",
        regDate: "",
        modiDate: ""
    }
}
  , Ti = g.createContext({
    userInfo: hx,
    setUserInfo: () => {}
    ,
    userPoint: 0,
    setUserPoint: () => {}
    ,
    serverList: mx,
    setServerList: () => {}
    ,
    isFunctionOpen: "",
    setIsFunctionOpen: () => {}
    ,
    indeonUserInfo: gx,
    setIndeonUserInfo: () => {}
})
  , eL = ({children: e}) => {
    const [t,n] = g.useState(hx)
      , [r,i] = g.useState(mx)
      , [s,o] = g.useState(0)
      , [l,a] = g.useState("")
      , [c,d] = g.useState(gx);
    return u.jsx(Ti.Provider, {
        value: {
            userInfo: t,
            setUserInfo: n,
            userPoint: s,
            setUserPoint: o,
            serverList: r,
            setServerList: i,
            isFunctionOpen: l,
            setIsFunctionOpen: a,
            indeonUserInfo: c,
            setIndeonUserInfo: d
        },
        children: e
    })
}
  , tL = "_container_1d0vv_1"
  , nL = {
    container: tL
}
  , Cs = ({children: e}) => u.jsx("div", {
    className: nL.container,
    children: e
})
  , lm = ({memServer: e, memServerCode: t, memCharacter: n, setIsChangeModalOpen: r, memCharacterYn: i}) => {
    const s = Ei("MIR_Info")
      , {setUserInfo: o, serverList: l, setServerList: a} = g.useContext(Ti)
      , [c,d] = g.useState()
      , [f,p] = g.useState({
        serverCode: 0,
        charServerName: "",
        charName: "",
        charJob: "",
        charGender: 0
    })
      , [h,m] = g.useState(!0)
      , _ = async () => {
        const b = await Oc({
            gameCode: 2,
            auth: {
                cusrVal: s
            }
        });
        o(b)
    }
      , x = async b => {
        const {servercode: C, servername: S} = b.target.selectedOptions[0].dataset
          , T = {
            auth: {
                cusrVal: s
            },
            gameCode: 2,
            serverCode: Number(b.currentTarget.value)
        }
          , {code: k, characterList: P} = await y_(T);
        switch (k) {
        case "200":
            p(R => ({
                ...R,
                charName: P[0].charName,
                serverCode: Number(C),
                charServerName: String(S)
            })),
            d({
                characterList: P
            }),
            m(!0);
            break;
        default:
            m(!1),
            d({
                characterList: [{
                    gameCode: 0,
                    charName: "캐릭터 선택",
                    charClass: 0,
                    charLevel: 0,
                    charGender: 0,
                    charServer: 0,
                    charServerName: "",
                    charJob: "",
                    charGenderName: ""
                }]
            });
            break
        }
    }
      , v = b => {
        b.preventDefault(),
        b.currentTarget.value === "캐릭터 선택" ? (alert("캐릭터 정보가 존재하지 않습니다."),
        m(!1)) : m(!0)
    }
      , w = b => {
        const {servercode: C, servername: S, charname: T, charjob: k, chargender: P} = b.target.selectedOptions[0].dataset;
        p({
            serverCode: Number(C),
            charServerName: String(S),
            charName: String(T),
            charJob: String(k),
            charGender: Number(P)
        })
    }
      , y = async () => {
        if (confirm("대표 캐릭터를 설정하시겠습니까?")) {
            const b = {
                auth: {
                    cusrVal: s
                },
                gameCode: 2,
                ...f
            };
            if (!h) {
                alert("캐릭터 정보가 존재하지 않습니다.");
                return
            }
            const {code: C} = await VI(b);
            C === "200" && (alert("대표 캐릭터가 정상적으로 저장되었습니다."),
            await _(),
            r(!1))
        } else
            return
    }
    ;
    return g.useEffect( () => {
        (async () => {
            const b = {
                auth: {
                    cusrVal: s
                },
                gameCode: 2,
                serverCode: t
            }
              , C = FI()
              , S = y_(b)
              , [T,k] = await Promise.all([C, S]);
            a(T),
            d(k),
            p(i === "N" ? {
                serverCode: T.serverInfo[0].serverCode,
                charServerName: T.serverInfo[0].serverName,
                charName: k == null ? void 0 : k.characterList[0].charName,
                charJob: k == null ? void 0 : k.characterList[0].charJob,
                charGender: k == null ? void 0 : k.characterList[0].charGender
            } : {
                serverCode: t,
                charServerName: e,
                charName: n,
                charJob: String(k == null ? void 0 : k.characterList.filter(P => P.charName === n)[0].charJob),
                charGender: Number(k == null ? void 0 : k.characterList.filter(P => P.charName === n)[0].charGender)
            })
        }
        )()
    }
    , []),
    g.useEffect( () => {
        s || (alert("로그인 후 이용이 가능합니다."),
        r(!1))
    }
    , []),
    u.jsx(Cs, {
        children: u.jsxs("div", {
            className: Bs.modal_bg,
            children: [u.jsxs("div", {
                className: Bs.select_wrap,
                children: [u.jsx("select", {
                    name: "server",
                    id: "server",
                    className: Bs.server_select,
                    onChange: x,
                    children: l.serverInfo.map(b => u.jsx("option", {
                        value: b.serverCode,
                        "data-servercode": b.serverCode,
                        "data-servername": b.serverName,
                        selected: b.serverCode === t,
                        children: b.serverName
                    }, b.serverCode))
                }), u.jsx("select", {
                    name: "character",
                    id: "character",
                    className: Bs.character_select,
                    onClick: v,
                    onChange: w,
                    children: c != null && c.characterList ? c == null ? void 0 : c.characterList.map(b => u.jsxs("option", {
                        value: b.charName,
                        "data-servercode": b.charServer,
                        "data-servername": b.charServerName,
                        "data-charname": b.charName,
                        "data-charjob": b.charJob,
                        "data-chargender": b.charGender,
                        selected: b.charName === n,
                        children: [b.charName, b.charJob && u.jsxs(u.Fragment, {
                            children: ["(", b.charJob, "/", b.charLevel, "Lv/", b.charGenderName, ")"]
                        })]
                    }, b.charName)) : null
                })]
            }), u.jsxs("div", {
                className: Bs.button_wrap,
                children: [u.jsx("button", {
                    type: "button",
                    onClick: y,
                    children: u.jsx("img", {
                        src: "https://web-cdn.mironline.co.kr/mir2/2013_grand/popup/btn_confirm.jpg",
                        alt: "confirm"
                    })
                }), u.jsx("button", {
                    type: "button",
                    onClick: () => r(!1),
                    children: u.jsx("img", {
                        src: "https://web-cdn.mironline.co.kr/mir2/2013_grand/popup/btn_cancel.jpg",
                        alt: "cancel"
                    })
                })]
            })]
        })
    })
}
  , rL = "_change_nickname_wrap_1qxwi_1"
  , iL = "_change_nickname_input_wrap_1qxwi_8"
  , sL = "_change_nickname_input_1qxwi_8"
  , oL = "_change_nickname_msg_1qxwi_28"
  , aL = "_change_nickname_button_wrap_1qxwi_34"
  , Us = {
    change_nickname_wrap: rL,
    change_nickname_input_wrap: iL,
    change_nickname_input: sL,
    change_nickname_msg: oL,
    change_nickname_button_wrap: aL
}
  , cm = ({setIsChangeNickNameModalOpen: e}) => {
    const {setUserInfo: t} = g.useContext(Ti)
      , n = Ei("MIR_Info")
      , [r,i] = g.useState("")
      , [s,o] = g.useState("")
      , l = async () => {
        const a = {
            auth: {
                cusrVal: n
            },
            gameCode: 2,
            nickName: r
        }
          , {code: c, cusrVal: d, nickName: f, errorMessage: p} = await zI(a);
        switch (c) {
        case "200":
            o("닉네임 변경이 완료 되었습니다."),
            alert("닉네임 변경이 완료 되었습니다."),
            dj("MIR%5FInfo"),
            he("MIR%5FInfo", d),
            t(h => ({
                ...h,
                nickName: f
            })),
            e(!1);
            break;
        case "M20001":
            o("사용하실 닉네임을 입력해 주세요.");
            break;
        default:
            o(p);
            break
        }
    }
    ;
    return u.jsx(Cs, {
        children: u.jsxs("div", {
            className: Us.change_nickname_wrap,
            children: [u.jsx("img", {
                src: "https://web-cdn.mironline.co.kr/mir2/2013_grand/popup/h1_nick.jpg",
                alt: "title"
            }), u.jsx("div", {
                className: Us.change_nickname_input_wrap,
                children: u.jsx("input", {
                    type: "text",
                    name: "userNick",
                    id: "userNick",
                    placeholder: "웹 닉네임 입력 (영문4~12자, 한글2~12자, 한글/영문/숫자 조합)",
                    className: Us.change_nickname_input,
                    value: r,
                    onChange: a => i(a.target.value),
                    maxLength: 12
                })
            }), u.jsx("div", {
                className: Us.change_nickname_msg,
                children: s || "사용하실 닉네임을 입력해 주세요."
            }), u.jsxs("div", {
                className: Us.change_nickname_button_wrap,
                children: [u.jsx("button", {
                    type: "button",
                    onClick: l,
                    children: u.jsx("img", {
                        src: "https://web-cdn.mironline.co.kr/mir2/2013_grand/popup/btn_confirm.jpg",
                        alt: "확인"
                    })
                }), u.jsx("button", {
                    type: "button",
                    onClick: () => e(!1),
                    children: u.jsx("img", {
                        src: "https://web-cdn.mironline.co.kr/mir2/2013_grand/popup/btn_cancel.jpg",
                        alt: "취소"
                    })
                })]
            })]
        })
    })
}
  , um = () => {
    const {setIsFunctionOpen: e} = g.useContext(Ti)
      , {pathname: t} = bi();
    g.useEffect( () => {
        (async () => {
            try {
                const n = {
                    gameCode: 2,
                    eventCode: Number(t.split("/")[1])
                }
                  , {eventInfo: r} = await pf(n);
                e(r.functionOpen)
            } catch (n) {
                console.error(n)
            }
        }
        )()
    }
    , [])
}
;
function lL(e) {
    return Ke.post("/event/promotion/getPointLog", e)
}
function Mr(e) {
    return Ke.post("/event/promotion/productList", e)
}
const cL = ({tabRef: e}) => {
    um();
    const t = Ei("MIR_Info")
      , n = {
        productIdx: 0,
        groupIdx: 0,
        productName: "",
        productImage: "",
        productDetail: "",
        productDetailText: "",
        productDisPrice: "",
        productPrice: "",
        productGiveNum: 0,
        itemPayCode: "",
        itemLink: "",
        productLimitedCount: 0,
        productOrderBy: "",
        regDate: "",
        plimitedUseNum: 0,
        plimitedSaleCount: 0,
        userPurchaseCount: 0
    }
      , [r,i] = g.useState(!1)
      , [s,o] = g.useState(!1)
      , [l,a] = g.useState(!1)
      , [c,d] = g.useState(!1)
      , [f,p] = g.useState({
        productImage: "",
        productName: ""
    })
      , [h,m] = g.useState(0)
      , _ = 2
      , x = "72"
      , v = "73"
      , w = {
        1: "1회 한정",
        0: "제한없음"
    }
      , {userInfo: y, setUserInfo: b, isFunctionOpen: C} = g.useContext(Ti)
      , {swapProduct: S, setSwapProduct: T, randomProduct: k, setRandomProduct: P} = g.useContext(aa)
      , [R,A] = g.useState({
        userId: "",
        pw: ""
    })
      , V = async () => {
        const O = {
            gameCode: _,
            auth: {
                cusrVal: t
            }
        }
          , {code: B, point: {totalPoint: H}} = await mo(O);
        B === "200" && m(H)
    }
      , F = async O => {
        if (O.preventDefault(),
        !R.userId || !R.pw) {
            alert("아이디 또는 비밀번호를 확인하세요");
            return
        }
        const B = {
            auth: {
                gameCode: _,
                userId: R.userId,
                pw: R.pw
            }
        }
          , {code: H, errorMessage: ue, cusrVal: de, memCharacter: xe, memCharacterYn: ge, memServer: wt, memServerCode: un, nickName: Mt, nickYn: ot} = await am(B);
        switch (H) {
        case "200":
            b({
                errorMessage: ue,
                cusrVal: de,
                memCharacter: xe,
                memCharacterYn: ge,
                memServer: wt,
                memServerCode: un,
                nickName: Mt,
                nickYn: ot
            }),
            T(await Mr({
                gameCode: _,
                groupIdx: x,
                auth: {
                    cusrVal: de
                }
            })),
            he("MIR%5FInfo", de);
            break;
        default:
            alert(ue);
            break
        }
        const ft = {
            gameCode: _,
            auth: {
                cusrVal: de
            }
        }
          , {code: Be, point: {totalPoint: Ot}} = await mo(ft);
        Be === "200" && m(Ot)
    }
      , U = ms(async O => {
        const B = {
            gameCode: _,
            auth: {
                cusrVal: y.cusrVal
            },
            groupIdx: x,
            productIdx: O.target.id
        }
          , {code: H, errorMessage: ue, product: de} = await BI(B);
        switch (H) {
        case "200":
            if (S.productList.filter(xe => xe.productIdx === Number(O.target.id))[0].plimitedUseNum !== 0) {
                const xe = S.productList.findIndex(ge => ge.productIdx === Number(O.target.id));
                S.productList[xe] = de
            }
            alert("아이템 교환이 완료되었습니다.");
            break;
        default:
            alert(ue);
            break
        }
        await V()
    }
    , 300)
      , Q = ms(async () => {
        const O = {
            gameCode: _,
            auth: {
                cusrVal: y.cusrVal
            },
            groupIdx: v
        }
          , {code: B, product: H, errorMessage: ue} = await UI(O);
        switch (B) {
        case "200":
            i(!0),
            p({
                productImage: H.productImage,
                productName: H.productName
            }),
            setTimeout( () => {
                o(!0)
            }
            , 3900);
            break;
        default:
            alert(ue);
            break
        }
        await V()
    }
    , 300)
      , Z = O => {
        const {name: B, value: H} = O.target;
        A(ue => ({
            ...ue,
            [B]: H
        }))
    }
      , L = () => {
        t && (y.nickName ? a(!0) : d(!0))
    }
      , N = () => {
        const H = (window.innerHeight - 550) / 2 + screenY
          , ue = (window.innerWidth - 600) / 2 + screenX;
        let de = "status=no, menubar=no, toolbar=no, resizable=no";
        de += ", width=600, height=550",
        de += ", top=" + H + ", left=" + ue,
        window.open("https://mir2.mironline.co.kr/sales/pointCheck", "popup", de)
    }
      , $ = (O, B) => C === "Y" && t ? O !== 0 && O - B === 0 : !0;
    return g.useEffect( () => {
        (async () => {
            const O = Mr({
                gameCode: _,
                groupIdx: x,
                auth: {
                    cusrVal: t
                },
                viewDateUseYn: "N"
            })
              , B = Mr({
                gameCode: _,
                groupIdx: v,
                viewDateUseYn: "N"
            })
              , [H,ue] = await Promise.all([O, B]);
            T(H),
            P(ue)
        }
        )()
    }
    , []),
    g.useEffect( () => {
        t && (async () => {
            const O = Oc({
                gameCode: _,
                auth: {
                    cusrVal: t
                }
            })
              , B = mo({
                gameCode: _,
                auth: {
                    cusrVal: t
                }
            })
              , [H,ue] = await Promise.all([O, B]);
            m(ue.point.totalPoint),
            b(H)
        }
        )()
    }
    , [t]),
    u.jsxs("div", {
        ref: e,
        className: fe.container,
        children: [u.jsx("img", {
            src: "https://web-cdn.mironline.co.kr/mir2/2024_event/0313/anniversary/images/tab-3-01.png",
            alt: "tab3-1"
        }), u.jsx("img", {
            src: "https://web-cdn.mironline.co.kr/mir2/2024_event/0313/anniversary/images/tab-3-02.png",
            alt: "tab3-2"
        }), u.jsx("img", {
            src: "https://web-cdn.mironline.co.kr/mir2/2024_event/0313/anniversary/images/tab-3-03.png",
            alt: "tab3-3"
        }), t ? u.jsx(u.Fragment, {
            children: u.jsx("div", {
                className: fe.login_on_wrap,
                children: u.jsxs("div", {
                    className: fe.login_on,
                    children: [u.jsx("span", {
                        className: fe.login_user_server,
                        children: y.memServer || "-"
                    }), u.jsx("span", {
                        className: fe.login_user_nickname,
                        children: y.memCharacter || "-"
                    }), u.jsx("button", {
                        type: "button",
                        className: `${C === "Y" ? fe.login_character_change_btn : fe.login_character_change_btn_off}`,
                        onClick: C === "Y" ? L : void 0,
                        children: u.jsx("img", {
                            src: C === "Y" ? "https://web-cdn.mironline.co.kr/mir2/2024_event/0313/anniversary/images/change-character-btn.jpg" : "https://web-cdn.mironline.co.kr/mir2/2024_event/0313/anniversary/images/change-character-btn-dim.jpg",
                            alt: "변경"
                        })
                    }), u.jsx("span", {
                        className: fe.login_user_point,
                        children: h.toLocaleString("ko-KR")
                    }), u.jsx("button", {
                        type: "button",
                        className: `${C === "Y" ? fe.login_use_info_btn : fe.login_use_info_btn_off}`,
                        onClick: C === "Y" ? N : void 0,
                        children: u.jsx("img", {
                            src: C === "Y" ? "https://web-cdn.mironline.co.kr/mir2/2024_event/0313/anniversary/images/use-info-btn.jpg" : "https://web-cdn.mironline.co.kr/mir2/2024_event/0313/anniversary/images/use-info-btn-dim.jpg",
                            alt: "이용내역"
                        })
                    })]
                })
            })
        }) : u.jsx(u.Fragment, {
            children: u.jsx("form", {
                className: fe.login_wrap,
                method: "POST",
                onSubmit: F,
                children: u.jsxs("div", {
                    className: fe.login_user_id_pw_wrap,
                    children: [u.jsx("input", {
                        className: fe.login_user_id,
                        name: "userId",
                        type: "text",
                        placeholder: "아이디 입력",
                        onChange: Z
                    }), u.jsx("input", {
                        className: fe.login_user_pw,
                        name: "pw",
                        type: "password",
                        placeholder: "비밀번호 입력",
                        onChange: Z
                    }), u.jsx("input", {
                        className: fe.login_submit,
                        type: "submit",
                        value: " "
                    })]
                })
            })
        }), u.jsx("div", {
            className: fe.product_swap_container,
            children: u.jsx("div", {
                className: fe.product_swap_list,
                children: S.productList.map( (O, B) => u.jsxs("div", {
                    className: fe.product_swap_wrap,
                    children: [u.jsx("div", {
                        className: fe.product_swap_img_wrap,
                        children: u.jsx("div", {
                            className: fe.product_swap_img,
                            children: u.jsx("img", {
                                src: "https://upload.mironline.co.kr/" + O.productImage,
                                alt: O.productName
                            })
                        })
                    }), u.jsxs("div", {
                        className: fe.product_swap_content_button_wrap,
                        children: [u.jsxs("div", {
                            className: fe.product_swap_content,
                            children: [u.jsxs("div", {
                                className: fe.product_swap_content_point,
                                children: [u.jsx("img", {
                                    src: "https://web-cdn.mironline.co.kr/mir2/2024_event/0313/anniversary/images/point.png",
                                    alt: "point"
                                }), u.jsx("span", {
                                    children: O.productPrice
                                })]
                            }), u.jsx("span", {
                                className: fe.product_swap_product_name,
                                children: O.productName
                            })]
                        }), u.jsxs("div", {
                            className: fe.product_swap_change_button_wrap,
                            children: [u.jsx("button", {
                                type: "button",
                                onClick: U,
                                id: String(O.productIdx),
                                disabled: $(O.plimitedUseNum, O.userPurchaseCount)
                            }), u.jsx("span", {
                                children: w[O.plimitedUseNum]
                            })]
                        })]
                    })]
                }, B))
            })
        }), u.jsxs("div", {
            className: fe.product_random_container,
            children: [u.jsxs("div", {
                className: fe.product_random_item_get_wrap,
                children: [r ? u.jsx("img", {
                    src: "https://web-cdn.mironline.co.kr/mir2/2024_event/0313/anniversary/images/get_item2.gif",
                    alt: "point"
                }) : u.jsx("img", {
                    src: "https://web-cdn.mironline.co.kr/mir3/2018_event/0725/images/get_item_default2.png",
                    alt: "pig"
                }), u.jsxs("div", {
                    className: fe.product_random_price_num_warp,
                    children: [t ? u.jsx("button", {
                        type: "button",
                        onClick: !r && C === "Y" ? Q : void 0,
                        children: u.jsx("img", {
                            className: `${C === "Y" ? fe.product_random_price_num_bg : fe.product_random_price_num_bg_off} `,
                            src: C === "Y" ? "http://web-cdn.mironline.co.kr/mir3/2018_event/0725/images/get_bg1.png" : "http://web-cdn.mironline.co.kr/mir3/2018_event/0725/images/get_bg2.png",
                            alt: "pig"
                        })
                    }) : u.jsx("img", {
                        src: "http://web-cdn.mironline.co.kr/mir3/2018_event/0725/images/get_bg2.png",
                        alt: "pig"
                    }), u.jsxs("div", {
                        className: fe.product_random_price_num,
                        children: [u.jsx("img", {
                            src: "https://web-cdn.mironline.co.kr/mir3/2018_event/0725/images/b1.png",
                            alt: "1"
                        }), u.jsx("img", {
                            src: "https://web-cdn.mironline.co.kr/mir3/2018_event/0725/images/b5.png",
                            alt: "5"
                        }), u.jsx("img", {
                            src: "https://web-cdn.mironline.co.kr/mir3/2018_event/0725/images/b0.png",
                            alt: "0"
                        })]
                    })]
                })]
            }), u.jsx("div", {
                className: fe.product_random_list,
                children: [...k.productList, n, n].map( (O, B) => u.jsx("div", {
                    className: fe.product_random_item_wrap,
                    children: O.productImage ? u.jsxs(u.Fragment, {
                        children: [u.jsx("div", {
                            className: fe.product_random_item_name,
                            children: O.productName
                        }), u.jsx("div", {
                            className: fe.product_random_item_image,
                            children: u.jsx("img", {
                                src: "https://upload.mironline.co.kr/" + O.productImage,
                                alt: O.productName
                            })
                        }), u.jsx("div", {
                            className: fe.product_random_item_detail
                        })]
                    }) : u.jsx("div", {
                        className: fe.product_random_item_block
                    })
                }, B))
            })]
        }), s && u.jsx(vt.Consumer, {
            children: u.jsx(YI, {
                productImage: f.productImage,
                productName: f.productName,
                setIsModalOpen: o,
                setGetItemRandomClick: i
            })
        }), c && u.jsx(vt.Consumer, {
            children: u.jsx(cm, {
                setIsChangeNickNameModalOpen: d
            })
        }), l && u.jsx(vt.Consumer, {
            children: u.jsx(lm, {
                memServer: y.memServer,
                memCharacter: y.memCharacter,
                memServerCode: y.memServerCode,
                memCharacterYn: y.memCharacterYn,
                setIsChangeModalOpen: a
            })
        })]
    })
}
  , uL = "_container_1dadv_1"
  , dL = {
    container: uL
}
  , fL = () => {
    const {tabState: e, isTabClick: t} = g.useContext(sm)
      , n = g.useRef(null);
    g.useEffect( () => {
        n.current && t && n.current.scrollIntoView({
            behavior: "smooth",
            block: "start"
        })
    }
    , [e, t]);
    const r = {
        gift: u.jsx(KR, {
            tabRef: n
        }),
        marketplace: u.jsx(tI, {
            tabRef: n
        }),
        troupe: u.jsx(cL, {
            tabRef: n
        })
    };
    return u.jsx("aside", {
        className: dL.container,
        children: r[e]
    })
}
  , pL = "_footer_zgluu_238"
  , mL = "_fadeIn_zgluu_1"
  , hL = {
    "a11y-hidden": "_a11y-hidden_zgluu_192",
    footer: pL,
    fadeIn: mL
}
  , _x = () => u.jsx("footer", {
    className: hL.footer,
    children: u.jsx("p", {
        children: "© ChuanQi IP Co., Ltd. All Rights Reserved."
    })
})
  , gL = "_header_18j4y_238"
  , _L = "_header_bi_18j4y_246"
  , vL = "_wrap_18j4y_254"
  , yL = "_fadeIn_18j4y_1"
  , Au = {
    "a11y-hidden": "_a11y-hidden_18j4y_192",
    header: gL,
    header_bi: _L,
    wrap: vL,
    fadeIn: yL
}
  , wL = () => {
    const {pathname: e} = bi()
      , t = e === "/231108/main"
      , n = () => {
        he("EvtPage", "EvtPageDone", 1),
        he("evtCode", "20231108", 1),
        window.location.href = "https://mir2.mironline.co.kr/main"
    }
      , r = () => {
        he("EvtPageGoHome", "EvtPageDone", void 0, 2),
        he("evtCode", "20231108", void 0, 2),
        window.location.href = "https://mir2.mironline.co.kr/main"
    }
    ;
    return u.jsx("header", {
        className: Au.header,
        children: u.jsxs("div", {
            className: t ? Au.wrap : void 0,
            children: [u.jsx("a", {
                onClick: r,
                children: u.jsx("img", {
                    className: Au.header_bi,
                    src: gu.LOGO,
                    alt: "header-logo"
                })
            }), t && u.jsxs("div", {
                children: [u.jsx("a", {
                    onClick: r,
                    children: u.jsx("img", {
                        src: gu.HOME,
                        alt: "header-home"
                    })
                }), u.jsxs("a", {
                    onClick: n,
                    children: [u.jsx("img", {
                        src: gu.X,
                        alt: "header-x"
                    }), u.jsx("p", {
                        children: "오늘하루 그만 보기"
                    })]
                })]
            })]
        })
    })
}
  , xL = () => (g.useEffect( () => {
    document.documentElement.style.setProperty("background-color", "#000")
}
),
u.jsxs("main", {
    children: [u.jsx(wL, {}), u.jsx(Si, {}), u.jsx(_x, {})]
}))
  , bL = "_header_tl3wx_238"
  , SL = "_focus_tl3wx_275"
  , CL = "_fadeIn_tl3wx_1"
  , w_ = {
    "a11y-hidden": "_a11y-hidden_tl3wx_192",
    header: bL,
    focus: SL,
    fadeIn: CL
}
  , kL = () => {
    const e = Pp()
      , {pathname: t} = bi()
      , n = [{
        name: "환영서버 안내",
        path: "/231108/tab/illusion/info"
    }, {
        name: "환영서버 혜택",
        path: "/231108/tab/illusion/benefit"
    }, {
        name: "본서버 혜택",
        path: "/231108/tab/live/benefit"
    }, {
        name: "신규 콘텐츠",
        path: "/231108/tab/live/newcontent"
    }]
      , r = g.useMemo( () => n, [])
      , i = g.useMemo( () => {
        const s = r.filter( ({path: o}) => o === t);
        if (s.length > 0) {
            const {name: o} = s[0];
            return o
        }
        return ""
    }
    , [t]);
    return u.jsxs("header", {
        className: w_.header,
        children: [u.jsx("img", {
            src: on.HEADER_BACK,
            alt: "tab-header-back",
            onClick: () => e.push("/231108/main")
        }), u.jsx("ul", {
            children: n.map( ({name: s, path: o}) => u.jsx("li", {
                className: s === i ? w_.focus : void 0,
                children: u.jsx(eC, {
                    to: o,
                    children: s
                })
            }, s))
        })]
    })
}
  , EL = "_container_1rpe8_1"
  , TL = {
    container: EL
}
  , PL = () => {
    const e = `
· 최대 4개의 캐릭터를 환영서버로 이전할 수 있으며 한번 이전한 캐릭터는 삭제가 불가하므로 신중하게 선택해 주시기 바랍니다.
· 이전한 환영서버 캐릭터 이름은 (서버명 앞글자)+캐릭터명으로 구성됩니다. (예: 혈룡서버 미르의전설2 > (혈)미르의전설2)
· 환영서버 시작 시 초기아이템은 환선물함으로 기본 지급됩니다.
· 환영서버에서 구매하여 사용한 환 아이템은 청약철회가 불가능하며, 종료 후 잔여 아이템은 일괄 삭제됩니다.
· 환영서버에서 얻은 아이템, 금전 및 습득한 무공은 본서버로 이전되지 않습니다.
· 환영서버에서 달성한 레벨에 따라, 획득한 경험치의 최대 65%는 종료 후 본서버의 캐릭터로 지급됩니다.
· 환영서버에서 획득한 경험치의 최대 65%가 본서버로 이전되는 것으로, 레벨의 65%가 이전되지 않습니다.
  `;
    return u.jsxs("section", {
        className: TL.container,
        children: [u.jsx("h3", {
            children: "유의사항"
        }), u.jsx("pre", {
            children: e
        })]
    })
}
  , jL = "_top_button_1ebe2_1"
  , NL = {
    top_button: jL,
    "scroll-top": "_scroll-top_1ebe2_1"
};
function vx() {
    const [e,t] = g.useState(0)
      , n = e > 0
      , r = () => {
        t(window.scrollY)
    }
      , i = () => {
        window.scrollTo({
            top: 0,
            left: 0,
            behavior: "smooth"
        })
    }
    ;
    return g.useEffect( () => (window.addEventListener("scroll", r),
    () => {
        window.removeEventListener("scroll", r)
    }
    ), []),
    {
        isScroll: n,
        scrollToTop: i
    }
}
const x_ = e => {
    const r = window.innerWidth
      , i = 180 + (r - 1919) / 2;
    e.current && (r > 1919 ? e.current.style.right = `${i}px` : e.current.style.right = "180px")
}
  , RL = () => {
    const e = g.useRef(null)
      , {isScroll: t, scrollToTop: n} = vx()
      , r = () => x_(e);
    return g.useEffect( () => {
        x_(e)
    }
    ),
    g.useEffect( () => (window.addEventListener("resize", r),
    () => {
        window.removeEventListener("resize", r)
    }
    ), []),
    u.jsxs(Ce.Fragment, {
        children: [u.jsx(Si, {
            context: u.jsx(kL, {})
        }), t && u.jsx("img", {
            className: NL.top_button,
            src: on.ILLUSION_INFO_TOP,
            alt: "top",
            onClick: n,
            ref: e
        }), u.jsx(PL, {}), u.jsx(_x, {})]
    })
}
  , IL = "_container_lb492_1"
  , LL = "_anim1_lb492_1"
  , ML = "_do_not_show_lb492_86"
  , OL = "_logo_lb492_105"
  , AL = "_hero_lb492_109"
  , Fa = {
    container: IL,
    anim1: LL,
    do_not_show: ML,
    logo: OL,
    hero: AL
}
  , DL = () => {
    const e = "https://web-cdn.mironline.co.kr/mir2/2023_event/1220/images/hero.png"
      , t = "https://web-cdn.mironline.co.kr/mir2/2023_event/1220/images/logo.png"
      , n = "https://web-cdn.mironline.co.kr/mir2/2023_event/1220/images/close.png"
      , r = () => {
        he("EvtPageGoHome", "EvtPageDone", void 0, 2),
        he("evtCode", "20231220", void 0, 2),
        window.location.href = "https://mir2.mironline.co.kr/main"
    }
      , i = () => {
        he("EvtPage", "EvtPageDone", 1),
        he("evtCode", "20231220", 1),
        window.location.href = "https://mir2.mironline.co.kr/main"
    }
    ;
    return u.jsxs("div", {
        className: Fa.container,
        children: [u.jsx("img", {
            className: Fa.logo,
            src: t,
            alt: "logo",
            onClick: r
        }), u.jsx("div", {
            className: Fa.do_not_show,
            onClick: i,
            children: u.jsxs("span", {
                children: ["오늘 하루 그만보기", u.jsx("img", {
                    src: n,
                    alt: "x"
                })]
            })
        }), u.jsx("img", {
            className: Fa.hero,
            src: e,
            alt: "hero"
        })]
    })
}
  , $L = "_top_ohrle_1"
  , b_ = {
    top: $L,
    "scroll-top": "_scroll-top_ohrle_1"
}
  , FL = "_container_158rv_1"
  , VL = {
    container: FL
}
  , zL = () => {
    const {tabState: e} = g.useContext(im);
    return u.jsx("div", {
        className: VL.container,
        children: u.jsxs("div", {
            children: [u.jsx("h3", {
                children: "유의사항"
            }), u.jsx("ul", {
                children: e === "gift" ? u.jsxs(u.Fragment, {
                    children: [u.jsx("li", {
                        children: "- 겨울눈사람(영물)의 버프(체력/마력 150 상승)는 최대 14시간 동안 지속되며, 흑옥을 생성하지 않습니다."
                    }), u.jsx("li", {
                        children: "- 전장, 곤륜서버 등으로 이동 또는 접속 종료 시, 증정수 지급 시간이 초기화됩니다."
                    })]
                }) : u.jsxs(u.Fragment, {
                    children: [u.jsx("li", {
                        children: "- 올해에는 배지류 아이템이 사용 불가합니다."
                    }), u.jsx("li", {
                        children: "- 이벤트 종료 시 '금배지 보유 수'로 카운트하기 때문에 이벤트 기간 동안 금배지를 버리지 말고 가방에 보유하고 있어야 합니다."
                    }), u.jsx("li", {
                        children: "- 이벤트 용버프는 접속 종료 시 사라집니다."
                    }), u.jsx("li", {
                        children: "  (※ 같은 유형의 용버프를 중첩 사용할 경우, 기존 용버프는 사라지고 마지막으로 사용한 용버프 효과가 적용됩니다)"
                    })]
                })
            })]
        })
    })
}
  , BL = "_container_uaj3e_1"
  , UL = {
    container: BL
}
  , HL = () => u.jsx("footer", {
    className: UL.container,
    children: "© ChuanQi IP Co., Ltd. All Rights Reserved."
})
  , GL = () => {
    g.useEffect( () => {
        document.documentElement.style.setProperty("background-color", "#002e53")
    }
    );
    const {isScroll: e, scrollToTop: t} = vx()
      , n = "https://web-cdn.mironline.co.kr/mir2/2023_event/1220/images/top-btn.png";
    return u.jsxs("main", {
        className: b_.container,
        children: [e && u.jsx("img", {
            className: b_.top,
            src: n,
            alt: "top",
            onClick: t
        }), u.jsxs(CR, {
            children: [u.jsx(DL, {}), u.jsx(Si, {}), u.jsx(zL, {}), u.jsx(HL, {})]
        })]
    })
}
  , WL = "_container_g3bjp_238"
  , qL = "_fadeIn_g3bjp_1"
  , YL = {
    "a11y-hidden": "_a11y-hidden_g3bjp_192",
    container: WL,
    fadeIn: qL
}
  , KL = () => u.jsx("footer", {
    className: YL.container,
    children: "© ChuanQi IP Co., Ltd. All rights reserved"
})
  , XL = "_container_1m258_238"
  , QL = "_skip_1m258_257"
  , JL = "_fadeIn_1m258_1"
  , S_ = {
    "a11y-hidden": "_a11y-hidden_1m258_192",
    container: XL,
    skip: QL,
    fadeIn: JL
}
  , yx = {
    errorMessage: "",
    list: [{
        indeonSeq: 0,
        rowNum: 0,
        userId: "",
        eventCode: 0,
        gameCode: 0,
        gameServer: "",
        gameChar: "",
        gameLevel: "",
        nextGameLevel: "",
        drawResult: "",
        balanceResult: "",
        regDate: "",
        modiDate: ""
    }]
}
  , Cn = g.createContext({
    navClickSection: "main",
    setNavClickSection: () => {}
    ,
    nowSection: "main",
    setNowSection: () => {}
    ,
    headerType: "intro",
    setHeaderType: () => {}
    ,
    introVideoRef: {
        current: null
    },
    teaserVideoRef: {
        current: null
    },
    mainVideoRef: {
        current: null
    },
    contentRef: {
        current: null
    },
    isPlayClicked: !1,
    setIsPlayClicked: () => {}
    ,
    isMain: !1,
    setIsMain: () => {}
    ,
    swiperIndex: 1,
    setSwiperIndex: () => {}
    ,
    gameRank: yx,
    setGameRank: () => {}
    ,
    drawResultYn: "N",
    setDrawResultYn: () => {}
})
  , ZL = ({children: e}) => {
    const [t,n] = g.useState("main")
      , [r,i] = g.useState("main")
      , [s,o] = g.useState("intro")
      , l = g.useRef(null)
      , a = g.useRef(null)
      , c = g.useRef(null)
      , d = g.useRef(null)
      , [f,p] = g.useState(!1)
      , [h,m] = g.useState(!1)
      , [_,x] = g.useState(1)
      , [v,w] = g.useState(yx)
      , [y,b] = g.useState("N");
    return u.jsx(Cn.Provider, {
        value: {
            navClickSection: t,
            setNavClickSection: n,
            nowSection: r,
            setNowSection: i,
            headerType: s,
            setHeaderType: o,
            introVideoRef: l,
            teaserVideoRef: a,
            mainVideoRef: c,
            contentRef: d,
            isPlayClicked: f,
            setIsPlayClicked: p,
            isMain: h,
            setIsMain: m,
            swiperIndex: _,
            setSwiperIndex: x,
            gameRank: v,
            setGameRank: w,
            drawResultYn: y,
            setDrawResultYn: b
        },
        children: e
    })
}
  , eM = ({type: e}) => {
    const {introVideoRef: t, teaserVideoRef: n, mainVideoRef: r, contentRef: i, setIsPlayClicked: s, setHeaderType: o, setIsMain: l} = g.useContext(Cn)
      , a = () => {
        he("EvtPageGoHome", "EvtPageDone", void 0, 2),
        he("evtCode", "20240417", void 0, 2)
    }
      , c = async () => {
        const f = t.current
          , p = n.current
          , h = r.current
          , m = i.current;
        !f || !p || !m || !h || (f.remove(),
        p.remove(),
        m.remove(),
        s(!0),
        o("main"),
        l(!0),
        await h.play())
    }
      , d = () => {
        he("EvtPage", "EvtPageDone", 1),
        he("evtCode", "20240417", 1),
        window.location.href = "https://mir2.mironline.co.kr/main"
    }
    ;
    return u.jsxs("header", {
        className: S_.container,
        children: [u.jsx("a", {
            href: "https://mir2.mironline.co.kr/main",
            onClick: a,
            children: u.jsx("img", {
                src: "https://web-cdn.mironline.co.kr/mir2/2024_event/0313/images/logo.png",
                alt: "bi_logo"
            })
        }), e === "intro" ? u.jsx("button", {
            type: "button",
            className: S_.skip,
            onClick: c,
            children: "Skip to Content ▶"
        }) : u.jsx("button", {
            type: "button",
            onClick: d,
            children: u.jsx("img", {
                src: "https://web-cdn.mironline.co.kr/mir2/2024_event/0411/images/do_not_show_btn.png",
                alt: "오늘하루그만보기"
            })
        })]
    })
}
  , tM = "_container_14bzm_1"
  , nM = "_main_ul_14bzm_10"
  , rM = "_battle_14bzm_17"
  , iM = "_sub_ul_14bzm_29"
  , sM = "_on_14bzm_39"
  , oM = "_sub_on_14bzm_54"
  , aM = "_off_14bzm_58"
  , lM = "_sub_off_14bzm_86"
  , ar = {
    container: tM,
    main_ul: nM,
    battle: rM,
    sub_ul: iM,
    on: sM,
    sub_on: oM,
    off: aM,
    sub_off: lM
}
  , cM = () => {
    const {nowSection: e, setNavClickSection: t} = g.useContext(Cn)
      , n = i => {
        i.stopPropagation();
        const {type: s, main: o, sub: l} = i.currentTarget.dataset
          , a = String(s === "main" ? o : l);
        t(a === "story|story" ? "story" : a)
    }
      , r = [{
        id: "main",
        label: "MAIN",
        sub: []
    }, {
        id: "story",
        label: "마정석의 비밀",
        sub: [{
            id: "story|story",
            subLabel: "스토리"
        }, {
            id: "story|monster",
            subLabel: "등장 몬스터"
        }, {
            id: "story|update",
            subLabel: "업데이트 내용"
        }]
    }, {
        id: "event",
        label: "인던 오픈 이벤트 [2주]",
        sub: []
    }, {
        id: "battle",
        label: `마정석의 비밀 전투 [4주]
[미니게임]`,
        sub: []
    }];
    return u.jsx("nav", {
        className: ar.container,
        children: u.jsx("ul", {
            className: ar.main_ul,
            children: r.map( ({id: i, label: s, sub: o}) => u.jsx("button", {
                type: "button",
                id: i,
                "data-type": "main",
                "data-main": i,
                onClick: n,
                className: i === "battle" ? ar.battle : void 0,
                children: u.jsxs("li", {
                    children: [u.jsx("span", {
                        className: e.includes(i) ? ar.on : ar.off,
                        children: s
                    }), o.length > 0 && u.jsx("ul", {
                        className: ar.sub_ul,
                        children: o.map( ({id: l, subLabel: a}) => u.jsx("li", {
                            "data-type": "sub",
                            "data-sub": l,
                            onClick: n,
                            className: e.includes(l) ? ar.sub_on : ar.sub_off,
                            children: a
                        }, l))
                    })]
                })
            }, i))
        })
    })
}
  , uM = "_container_1k82o_1"
  , dM = {
    container: uM
}
  , fM = () => {
    const [e,t] = g.useState(!1)
      , n = () => {
        window.scrollTo({
            top: 0,
            behavior: "smooth"
        })
    }
    ;
    return g.useEffect( () => {
        const r = () => {
            window.scrollY > 500 ? t(!0) : t(!1)
        }
        ;
        return window.addEventListener("scroll", r),
        () => {
            window.removeEventListener("scroll", r)
        }
    }
    , []),
    u.jsx(u.Fragment, {
        children: e && u.jsx("button", {
            type: "button",
            className: dM.container,
            onClick: n,
            children: u.jsx("img", {
                src: "https://web-cdn.mironline.co.kr/mir2/2024_event/0313/images/top.png",
                alt: "top"
            })
        })
    })
}
  , pM = "_container_l1i84_1"
  , mM = "_stone_wrap_l1i84_15"
  , hM = "_shine_l1i84_1"
  , gM = "_pen_wrap_l1i84_33"
  , _M = "_book_title_wrap_l1i84_42"
  , vM = "_book_l1i84_42"
  , yM = "_book_swiper_l1i84_62"
  , wM = "_book_chapter_l1i84_66"
  , xM = "_book_chapter_1_l1i84_83"
  , bM = "_book_chapter_3_l1i84_87"
  , SM = "_book_chapter_5_l1i84_104"
  , CM = "_book_chapter_2_l1i84_122"
  , kM = "_book_chapter_4_l1i84_124"
  , EM = "_book_swiper_index_l1i84_171"
  , TM = "_book_swiper_button_wrap_l1i84_182"
  , PM = "_book_custom_prev_button_l1i84_182"
  , jM = "_book_custom_prev_button_off_l1i84_187"
  , NM = "_book_custom_next_button_l1i84_192"
  , $t = {
    container: pM,
    stone_wrap: mM,
    shine: hM,
    pen_wrap: gM,
    book_title_wrap: _M,
    book: vM,
    book_swiper: yM,
    book_chapter: wM,
    book_chapter_1: xM,
    book_chapter_3: bM,
    book_chapter_5: SM,
    book_chapter_2: CM,
    book_chapter_4: kM,
    book_swiper_index: EM,
    book_swiper_button_wrap: TM,
    book_custom_prev_button: PM,
    book_custom_prev_button_off: jM,
    book_custom_next_button: NM
}
  , Ie = (e, t={}) => {
    const [n,r] = g.useState(!1);
    return g.useEffect( () => {
        const {root: i, rootMargin: s="0px", threshold: o=.5} = t
          , l = c => {
            c.forEach(d => {
                d.isIntersecting ? r(!0) : r(!1)
            }
            )
        }
          , a = new IntersectionObserver(l,{
            root: i,
            rootMargin: s,
            threshold: o
        });
        return e && e.current && a.observe(e.current),
        () => {
            a.disconnect()
        }
    }
    , [e, t.root, t.rootMargin, t.threshold]),
    n
}
;
function C_(e) {
    return e !== null && typeof e == "object" && "constructor"in e && e.constructor === Object
}
function dm(e, t) {
    e === void 0 && (e = {}),
    t === void 0 && (t = {}),
    Object.keys(t).forEach(n => {
        typeof e[n] > "u" ? e[n] = t[n] : C_(t[n]) && C_(e[n]) && Object.keys(t[n]).length > 0 && dm(e[n], t[n])
    }
    )
}
const wx = {
    body: {},
    addEventListener() {},
    removeEventListener() {},
    activeElement: {
        blur() {},
        nodeName: ""
    },
    querySelector() {
        return null
    },
    querySelectorAll() {
        return []
    },
    getElementById() {
        return null
    },
    createEvent() {
        return {
            initEvent() {}
        }
    },
    createElement() {
        return {
            children: [],
            childNodes: [],
            style: {},
            setAttribute() {},
            getElementsByTagName() {
                return []
            }
        }
    },
    createElementNS() {
        return {}
    },
    importNode() {
        return null
    },
    location: {
        hash: "",
        host: "",
        hostname: "",
        href: "",
        origin: "",
        pathname: "",
        protocol: "",
        search: ""
    }
};
function Fr() {
    const e = typeof document < "u" ? document : {};
    return dm(e, wx),
    e
}
const RM = {
    document: wx,
    navigator: {
        userAgent: ""
    },
    location: {
        hash: "",
        host: "",
        hostname: "",
        href: "",
        origin: "",
        pathname: "",
        protocol: "",
        search: ""
    },
    history: {
        replaceState() {},
        pushState() {},
        go() {},
        back() {}
    },
    CustomEvent: function() {
        return this
    },
    addEventListener() {},
    removeEventListener() {},
    getComputedStyle() {
        return {
            getPropertyValue() {
                return ""
            }
        }
    },
    Image() {},
    Date() {},
    screen: {},
    setTimeout() {},
    clearTimeout() {},
    matchMedia() {
        return {}
    },
    requestAnimationFrame(e) {
        return typeof setTimeout > "u" ? (e(),
        null) : setTimeout(e, 0)
    },
    cancelAnimationFrame(e) {
        typeof setTimeout > "u" || clearTimeout(e)
    }
};
function Xt() {
    const e = typeof window < "u" ? window : {};
    return dm(e, RM),
    e
}
function IM(e) {
    return e === void 0 && (e = ""),
    e.trim().split(" ").filter(t => !!t.trim())
}
function LM(e) {
    const t = e;
    Object.keys(t).forEach(n => {
        try {
            t[n] = null
        } catch {}
        try {
            delete t[n]
        } catch {}
    }
    )
}
function mf(e, t) {
    return t === void 0 && (t = 0),
    setTimeout(e, t)
}
function Kl() {
    return Date.now()
}
function MM(e) {
    const t = Xt();
    let n;
    return t.getComputedStyle && (n = t.getComputedStyle(e, null)),
    !n && e.currentStyle && (n = e.currentStyle),
    n || (n = e.style),
    n
}
function OM(e, t) {
    t === void 0 && (t = "x");
    const n = Xt();
    let r, i, s;
    const o = MM(e);
    return n.WebKitCSSMatrix ? (i = o.transform || o.webkitTransform,
    i.split(",").length > 6 && (i = i.split(", ").map(l => l.replace(",", ".")).join(", ")),
    s = new n.WebKitCSSMatrix(i === "none" ? "" : i)) : (s = o.MozTransform || o.OTransform || o.MsTransform || o.msTransform || o.transform || o.getPropertyValue("transform").replace("translate(", "matrix(1, 0, 0, 1,"),
    r = s.toString().split(",")),
    t === "x" && (n.WebKitCSSMatrix ? i = s.m41 : r.length === 16 ? i = parseFloat(r[12]) : i = parseFloat(r[4])),
    t === "y" && (n.WebKitCSSMatrix ? i = s.m42 : r.length === 16 ? i = parseFloat(r[13]) : i = parseFloat(r[5])),
    i || 0
}
function Va(e) {
    return typeof e == "object" && e !== null && e.constructor && Object.prototype.toString.call(e).slice(8, -1) === "Object"
}
function AM(e) {
    return typeof window < "u" && typeof window.HTMLElement < "u" ? e instanceof HTMLElement : e && (e.nodeType === 1 || e.nodeType === 11)
}
function Bt() {
    const e = Object(arguments.length <= 0 ? void 0 : arguments[0])
      , t = ["__proto__", "constructor", "prototype"];
    for (let n = 1; n < arguments.length; n += 1) {
        const r = n < 0 || arguments.length <= n ? void 0 : arguments[n];
        if (r != null && !AM(r)) {
            const i = Object.keys(Object(r)).filter(s => t.indexOf(s) < 0);
            for (let s = 0, o = i.length; s < o; s += 1) {
                const l = i[s]
                  , a = Object.getOwnPropertyDescriptor(r, l);
                a !== void 0 && a.enumerable && (Va(e[l]) && Va(r[l]) ? r[l].__swiper__ ? e[l] = r[l] : Bt(e[l], r[l]) : !Va(e[l]) && Va(r[l]) ? (e[l] = {},
                r[l].__swiper__ ? e[l] = r[l] : Bt(e[l], r[l])) : e[l] = r[l])
            }
        }
    }
    return e
}
function za(e, t, n) {
    e.style.setProperty(t, n)
}
function xx(e) {
    let {swiper: t, targetPosition: n, side: r} = e;
    const i = Xt()
      , s = -t.translate;
    let o = null, l;
    const a = t.params.speed;
    t.wrapperEl.style.scrollSnapType = "none",
    i.cancelAnimationFrame(t.cssModeFrameID);
    const c = n > s ? "next" : "prev"
      , d = (p, h) => c === "next" && p >= h || c === "prev" && p <= h
      , f = () => {
        l = new Date().getTime(),
        o === null && (o = l);
        const p = Math.max(Math.min((l - o) / a, 1), 0)
          , h = .5 - Math.cos(p * Math.PI) / 2;
        let m = s + h * (n - s);
        if (d(m, n) && (m = n),
        t.wrapperEl.scrollTo({
            [r]: m
        }),
        d(m, n)) {
            t.wrapperEl.style.overflow = "hidden",
            t.wrapperEl.style.scrollSnapType = "",
            setTimeout( () => {
                t.wrapperEl.style.overflow = "",
                t.wrapperEl.scrollTo({
                    [r]: m
                })
            }
            ),
            i.cancelAnimationFrame(t.cssModeFrameID);
            return
        }
        t.cssModeFrameID = i.requestAnimationFrame(f)
    }
    ;
    f()
}
function bx(e) {
    return e.querySelector(".swiper-slide-transform") || e.shadowRoot && e.shadowRoot.querySelector(".swiper-slide-transform") || e
}
function In(e, t) {
    return t === void 0 && (t = ""),
    [...e.children].filter(n => n.matches(t))
}
function Xl(e) {
    try {
        console.warn(e);
        return
    } catch {}
}
function Uo(e, t) {
    t === void 0 && (t = []);
    const n = document.createElement(e);
    return n.classList.add(...Array.isArray(t) ? t : IM(t)),
    n
}
function DM(e, t) {
    const n = [];
    for (; e.previousElementSibling; ) {
        const r = e.previousElementSibling;
        t ? r.matches(t) && n.push(r) : n.push(r),
        e = r
    }
    return n
}
function $M(e, t) {
    const n = [];
    for (; e.nextElementSibling; ) {
        const r = e.nextElementSibling;
        t ? r.matches(t) && n.push(r) : n.push(r),
        e = r
    }
    return n
}
function Sr(e, t) {
    return Xt().getComputedStyle(e, null).getPropertyValue(t)
}
function hf(e) {
    let t = e, n;
    if (t) {
        for (n = 0; (t = t.previousSibling) !== null; )
            t.nodeType === 1 && (n += 1);
        return n
    }
}
function FM(e, t) {
    const n = [];
    let r = e.parentElement;
    for (; r; )
        t ? r.matches(t) && n.push(r) : n.push(r),
        r = r.parentElement;
    return n
}
function VM(e, t) {
    function n(r) {
        r.target === e && (t.call(e, r),
        e.removeEventListener("transitionend", n))
    }
    t && e.addEventListener("transitionend", n)
}
function k_(e, t, n) {
    const r = Xt();
    return n ? e[t === "width" ? "offsetWidth" : "offsetHeight"] + parseFloat(r.getComputedStyle(e, null).getPropertyValue(t === "width" ? "margin-right" : "margin-top")) + parseFloat(r.getComputedStyle(e, null).getPropertyValue(t === "width" ? "margin-left" : "margin-bottom")) : e.offsetWidth
}
function Ee(e) {
    return (Array.isArray(e) ? e : [e]).filter(t => !!t)
}
let Du;
function zM() {
    const e = Xt()
      , t = Fr();
    return {
        smoothScroll: t.documentElement && t.documentElement.style && "scrollBehavior"in t.documentElement.style,
        touch: !!("ontouchstart"in e || e.DocumentTouch && t instanceof e.DocumentTouch)
    }
}
function Sx() {
    return Du || (Du = zM()),
    Du
}
let $u;
function BM(e) {
    let {userAgent: t} = e === void 0 ? {} : e;
    const n = Sx()
      , r = Xt()
      , i = r.navigator.platform
      , s = t || r.navigator.userAgent
      , o = {
        ios: !1,
        android: !1
    }
      , l = r.screen.width
      , a = r.screen.height
      , c = s.match(/(Android);?[\s\/]+([\d.]+)?/);
    let d = s.match(/(iPad).*OS\s([\d_]+)/);
    const f = s.match(/(iPod)(.*OS\s([\d_]+))?/)
      , p = !d && s.match(/(iPhone\sOS|iOS)\s([\d_]+)/)
      , h = i === "Win32";
    let m = i === "MacIntel";
    const _ = ["1024x1366", "1366x1024", "834x1194", "1194x834", "834x1112", "1112x834", "768x1024", "1024x768", "820x1180", "1180x820", "810x1080", "1080x810"];
    return !d && m && n.touch && _.indexOf(`${l}x${a}`) >= 0 && (d = s.match(/(Version)\/([\d.]+)/),
    d || (d = [0, 1, "13_0_0"]),
    m = !1),
    c && !h && (o.os = "android",
    o.android = !0),
    (d || p || f) && (o.os = "ios",
    o.ios = !0),
    o
}
function Cx(e) {
    return e === void 0 && (e = {}),
    $u || ($u = BM(e)),
    $u
}
let Fu;
function UM() {
    const e = Xt()
      , t = Cx();
    let n = !1;
    function r() {
        const l = e.navigator.userAgent.toLowerCase();
        return l.indexOf("safari") >= 0 && l.indexOf("chrome") < 0 && l.indexOf("android") < 0
    }
    if (r()) {
        const l = String(e.navigator.userAgent);
        if (l.includes("Version/")) {
            const [a,c] = l.split("Version/")[1].split(" ")[0].split(".").map(d => Number(d));
            n = a < 16 || a === 16 && c < 2
        }
    }
    const i = /(iPhone|iPod|iPad).*AppleWebKit(?!.*Safari)/i.test(e.navigator.userAgent)
      , s = r()
      , o = s || i && t.ios;
    return {
        isSafari: n || s,
        needPerspectiveFix: n,
        need3dFix: o,
        isWebView: i
    }
}
function HM() {
    return Fu || (Fu = UM()),
    Fu
}
function GM(e) {
    let {swiper: t, on: n, emit: r} = e;
    const i = Xt();
    let s = null
      , o = null;
    const l = () => {
        !t || t.destroyed || !t.initialized || (r("beforeResize"),
        r("resize"))
    }
      , a = () => {
        !t || t.destroyed || !t.initialized || (s = new ResizeObserver(f => {
            o = i.requestAnimationFrame( () => {
                const {width: p, height: h} = t;
                let m = p
                  , _ = h;
                f.forEach(x => {
                    let {contentBoxSize: v, contentRect: w, target: y} = x;
                    y && y !== t.el || (m = w ? w.width : (v[0] || v).inlineSize,
                    _ = w ? w.height : (v[0] || v).blockSize)
                }
                ),
                (m !== p || _ !== h) && l()
            }
            )
        }
        ),
        s.observe(t.el))
    }
      , c = () => {
        o && i.cancelAnimationFrame(o),
        s && s.unobserve && t.el && (s.unobserve(t.el),
        s = null)
    }
      , d = () => {
        !t || t.destroyed || !t.initialized || r("orientationchange")
    }
    ;
    n("init", () => {
        if (t.params.resizeObserver && typeof i.ResizeObserver < "u") {
            a();
            return
        }
        i.addEventListener("resize", l),
        i.addEventListener("orientationchange", d)
    }
    ),
    n("destroy", () => {
        c(),
        i.removeEventListener("resize", l),
        i.removeEventListener("orientationchange", d)
    }
    )
}
function WM(e) {
    let {swiper: t, extendParams: n, on: r, emit: i} = e;
    const s = []
      , o = Xt()
      , l = function(d, f) {
        f === void 0 && (f = {});
        const p = o.MutationObserver || o.WebkitMutationObserver
          , h = new p(m => {
            if (t.__preventObserver__)
                return;
            if (m.length === 1) {
                i("observerUpdate", m[0]);
                return
            }
            const _ = function() {
                i("observerUpdate", m[0])
            };
            o.requestAnimationFrame ? o.requestAnimationFrame(_) : o.setTimeout(_, 0)
        }
        );
        h.observe(d, {
            attributes: typeof f.attributes > "u" ? !0 : f.attributes,
            childList: typeof f.childList > "u" ? !0 : f.childList,
            characterData: typeof f.characterData > "u" ? !0 : f.characterData
        }),
        s.push(h)
    }
      , a = () => {
        if (t.params.observer) {
            if (t.params.observeParents) {
                const d = FM(t.hostEl);
                for (let f = 0; f < d.length; f += 1)
                    l(d[f])
            }
            l(t.hostEl, {
                childList: t.params.observeSlideChildren
            }),
            l(t.wrapperEl, {
                attributes: !1
            })
        }
    }
      , c = () => {
        s.forEach(d => {
            d.disconnect()
        }
        ),
        s.splice(0, s.length)
    }
    ;
    n({
        observer: !1,
        observeParents: !1,
        observeSlideChildren: !1
    }),
    r("init", a),
    r("destroy", c)
}
var qM = {
    on(e, t, n) {
        const r = this;
        if (!r.eventsListeners || r.destroyed || typeof t != "function")
            return r;
        const i = n ? "unshift" : "push";
        return e.split(" ").forEach(s => {
            r.eventsListeners[s] || (r.eventsListeners[s] = []),
            r.eventsListeners[s][i](t)
        }
        ),
        r
    },
    once(e, t, n) {
        const r = this;
        if (!r.eventsListeners || r.destroyed || typeof t != "function")
            return r;
        function i() {
            r.off(e, i),
            i.__emitterProxy && delete i.__emitterProxy;
            for (var s = arguments.length, o = new Array(s), l = 0; l < s; l++)
                o[l] = arguments[l];
            t.apply(r, o)
        }
        return i.__emitterProxy = t,
        r.on(e, i, n)
    },
    onAny(e, t) {
        const n = this;
        if (!n.eventsListeners || n.destroyed || typeof e != "function")
            return n;
        const r = t ? "unshift" : "push";
        return n.eventsAnyListeners.indexOf(e) < 0 && n.eventsAnyListeners[r](e),
        n
    },
    offAny(e) {
        const t = this;
        if (!t.eventsListeners || t.destroyed || !t.eventsAnyListeners)
            return t;
        const n = t.eventsAnyListeners.indexOf(e);
        return n >= 0 && t.eventsAnyListeners.splice(n, 1),
        t
    },
    off(e, t) {
        const n = this;
        return !n.eventsListeners || n.destroyed || !n.eventsListeners || e.split(" ").forEach(r => {
            typeof t > "u" ? n.eventsListeners[r] = [] : n.eventsListeners[r] && n.eventsListeners[r].forEach( (i, s) => {
                (i === t || i.__emitterProxy && i.__emitterProxy === t) && n.eventsListeners[r].splice(s, 1)
            }
            )
        }
        ),
        n
    },
    emit() {
        const e = this;
        if (!e.eventsListeners || e.destroyed || !e.eventsListeners)
            return e;
        let t, n, r;
        for (var i = arguments.length, s = new Array(i), o = 0; o < i; o++)
            s[o] = arguments[o];
        return typeof s[0] == "string" || Array.isArray(s[0]) ? (t = s[0],
        n = s.slice(1, s.length),
        r = e) : (t = s[0].events,
        n = s[0].data,
        r = s[0].context || e),
        n.unshift(r),
        (Array.isArray(t) ? t : t.split(" ")).forEach(a => {
            e.eventsAnyListeners && e.eventsAnyListeners.length && e.eventsAnyListeners.forEach(c => {
                c.apply(r, [a, ...n])
            }
            ),
            e.eventsListeners && e.eventsListeners[a] && e.eventsListeners[a].forEach(c => {
                c.apply(r, n)
            }
            )
        }
        ),
        e
    }
};
function YM() {
    const e = this;
    let t, n;
    const r = e.el;
    typeof e.params.width < "u" && e.params.width !== null ? t = e.params.width : t = r.clientWidth,
    typeof e.params.height < "u" && e.params.height !== null ? n = e.params.height : n = r.clientHeight,
    !(t === 0 && e.isHorizontal() || n === 0 && e.isVertical()) && (t = t - parseInt(Sr(r, "padding-left") || 0, 10) - parseInt(Sr(r, "padding-right") || 0, 10),
    n = n - parseInt(Sr(r, "padding-top") || 0, 10) - parseInt(Sr(r, "padding-bottom") || 0, 10),
    Number.isNaN(t) && (t = 0),
    Number.isNaN(n) && (n = 0),
    Object.assign(e, {
        width: t,
        height: n,
        size: e.isHorizontal() ? t : n
    }))
}
function KM() {
    const e = this;
    function t(R, A) {
        return parseFloat(R.getPropertyValue(e.getDirectionLabel(A)) || 0)
    }
    const n = e.params
      , {wrapperEl: r, slidesEl: i, size: s, rtlTranslate: o, wrongRTL: l} = e
      , a = e.virtual && n.virtual.enabled
      , c = a ? e.virtual.slides.length : e.slides.length
      , d = In(i, `.${e.params.slideClass}, swiper-slide`)
      , f = a ? e.virtual.slides.length : d.length;
    let p = [];
    const h = []
      , m = [];
    let _ = n.slidesOffsetBefore;
    typeof _ == "function" && (_ = n.slidesOffsetBefore.call(e));
    let x = n.slidesOffsetAfter;
    typeof x == "function" && (x = n.slidesOffsetAfter.call(e));
    const v = e.snapGrid.length
      , w = e.slidesGrid.length;
    let y = n.spaceBetween
      , b = -_
      , C = 0
      , S = 0;
    if (typeof s > "u")
        return;
    typeof y == "string" && y.indexOf("%") >= 0 ? y = parseFloat(y.replace("%", "")) / 100 * s : typeof y == "string" && (y = parseFloat(y)),
    e.virtualSize = -y,
    d.forEach(R => {
        o ? R.style.marginLeft = "" : R.style.marginRight = "",
        R.style.marginBottom = "",
        R.style.marginTop = ""
    }
    ),
    n.centeredSlides && n.cssMode && (za(r, "--swiper-centered-offset-before", ""),
    za(r, "--swiper-centered-offset-after", ""));
    const T = n.grid && n.grid.rows > 1 && e.grid;
    T ? e.grid.initSlides(d) : e.grid && e.grid.unsetSlides();
    let k;
    const P = n.slidesPerView === "auto" && n.breakpoints && Object.keys(n.breakpoints).filter(R => typeof n.breakpoints[R].slidesPerView < "u").length > 0;
    for (let R = 0; R < f; R += 1) {
        k = 0;
        let A;
        if (d[R] && (A = d[R]),
        T && e.grid.updateSlide(R, A, d),
        !(d[R] && Sr(A, "display") === "none")) {
            if (n.slidesPerView === "auto") {
                P && (d[R].style[e.getDirectionLabel("width")] = "");
                const V = getComputedStyle(A)
                  , F = A.style.transform
                  , U = A.style.webkitTransform;
                if (F && (A.style.transform = "none"),
                U && (A.style.webkitTransform = "none"),
                n.roundLengths)
                    k = e.isHorizontal() ? k_(A, "width", !0) : k_(A, "height", !0);
                else {
                    const Q = t(V, "width")
                      , Z = t(V, "padding-left")
                      , L = t(V, "padding-right")
                      , N = t(V, "margin-left")
                      , $ = t(V, "margin-right")
                      , O = V.getPropertyValue("box-sizing");
                    if (O && O === "border-box")
                        k = Q + N + $;
                    else {
                        const {clientWidth: B, offsetWidth: H} = A;
                        k = Q + Z + L + N + $ + (H - B)
                    }
                }
                F && (A.style.transform = F),
                U && (A.style.webkitTransform = U),
                n.roundLengths && (k = Math.floor(k))
            } else
                k = (s - (n.slidesPerView - 1) * y) / n.slidesPerView,
                n.roundLengths && (k = Math.floor(k)),
                d[R] && (d[R].style[e.getDirectionLabel("width")] = `${k}px`);
            d[R] && (d[R].swiperSlideSize = k),
            m.push(k),
            n.centeredSlides ? (b = b + k / 2 + C / 2 + y,
            C === 0 && R !== 0 && (b = b - s / 2 - y),
            R === 0 && (b = b - s / 2 - y),
            Math.abs(b) < 1 / 1e3 && (b = 0),
            n.roundLengths && (b = Math.floor(b)),
            S % n.slidesPerGroup === 0 && p.push(b),
            h.push(b)) : (n.roundLengths && (b = Math.floor(b)),
            (S - Math.min(e.params.slidesPerGroupSkip, S)) % e.params.slidesPerGroup === 0 && p.push(b),
            h.push(b),
            b = b + k + y),
            e.virtualSize += k + y,
            C = k,
            S += 1
        }
    }
    if (e.virtualSize = Math.max(e.virtualSize, s) + x,
    o && l && (n.effect === "slide" || n.effect === "coverflow") && (r.style.width = `${e.virtualSize + y}px`),
    n.setWrapperSize && (r.style[e.getDirectionLabel("width")] = `${e.virtualSize + y}px`),
    T && e.grid.updateWrapperSize(k, p),
    !n.centeredSlides) {
        const R = [];
        for (let A = 0; A < p.length; A += 1) {
            let V = p[A];
            n.roundLengths && (V = Math.floor(V)),
            p[A] <= e.virtualSize - s && R.push(V)
        }
        p = R,
        Math.floor(e.virtualSize - s) - Math.floor(p[p.length - 1]) > 1 && p.push(e.virtualSize - s)
    }
    if (a && n.loop) {
        const R = m[0] + y;
        if (n.slidesPerGroup > 1) {
            const A = Math.ceil((e.virtual.slidesBefore + e.virtual.slidesAfter) / n.slidesPerGroup)
              , V = R * n.slidesPerGroup;
            for (let F = 0; F < A; F += 1)
                p.push(p[p.length - 1] + V)
        }
        for (let A = 0; A < e.virtual.slidesBefore + e.virtual.slidesAfter; A += 1)
            n.slidesPerGroup === 1 && p.push(p[p.length - 1] + R),
            h.push(h[h.length - 1] + R),
            e.virtualSize += R
    }
    if (p.length === 0 && (p = [0]),
    y !== 0) {
        const R = e.isHorizontal() && o ? "marginLeft" : e.getDirectionLabel("marginRight");
        d.filter( (A, V) => !n.cssMode || n.loop ? !0 : V !== d.length - 1).forEach(A => {
            A.style[R] = `${y}px`
        }
        )
    }
    if (n.centeredSlides && n.centeredSlidesBounds) {
        let R = 0;
        m.forEach(V => {
            R += V + (y || 0)
        }
        ),
        R -= y;
        const A = R - s;
        p = p.map(V => V <= 0 ? -_ : V > A ? A + x : V)
    }
    if (n.centerInsufficientSlides) {
        let R = 0;
        m.forEach(V => {
            R += V + (y || 0)
        }
        ),
        R -= y;
        const A = (n.slidesOffsetBefore || 0) + (n.slidesOffsetAfter || 0);
        if (R + A < s) {
            const V = (s - R - A) / 2;
            p.forEach( (F, U) => {
                p[U] = F - V
            }
            ),
            h.forEach( (F, U) => {
                h[U] = F + V
            }
            )
        }
    }
    if (Object.assign(e, {
        slides: d,
        snapGrid: p,
        slidesGrid: h,
        slidesSizesGrid: m
    }),
    n.centeredSlides && n.cssMode && !n.centeredSlidesBounds) {
        za(r, "--swiper-centered-offset-before", `${-p[0]}px`),
        za(r, "--swiper-centered-offset-after", `${e.size / 2 - m[m.length - 1] / 2}px`);
        const R = -e.snapGrid[0]
          , A = -e.slidesGrid[0];
        e.snapGrid = e.snapGrid.map(V => V + R),
        e.slidesGrid = e.slidesGrid.map(V => V + A)
    }
    if (f !== c && e.emit("slidesLengthChange"),
    p.length !== v && (e.params.watchOverflow && e.checkOverflow(),
    e.emit("snapGridLengthChange")),
    h.length !== w && e.emit("slidesGridLengthChange"),
    n.watchSlidesProgress && e.updateSlidesOffset(),
    e.emit("slidesUpdated"),
    !a && !n.cssMode && (n.effect === "slide" || n.effect === "fade")) {
        const R = `${n.containerModifierClass}backface-hidden`
          , A = e.el.classList.contains(R);
        f <= n.maxBackfaceHiddenSlides ? A || e.el.classList.add(R) : A && e.el.classList.remove(R)
    }
}
function XM(e) {
    const t = this
      , n = []
      , r = t.virtual && t.params.virtual.enabled;
    let i = 0, s;
    typeof e == "number" ? t.setTransition(e) : e === !0 && t.setTransition(t.params.speed);
    const o = l => r ? t.slides[t.getSlideIndexByData(l)] : t.slides[l];
    if (t.params.slidesPerView !== "auto" && t.params.slidesPerView > 1)
        if (t.params.centeredSlides)
            (t.visibleSlides || []).forEach(l => {
                n.push(l)
            }
            );
        else
            for (s = 0; s < Math.ceil(t.params.slidesPerView); s += 1) {
                const l = t.activeIndex + s;
                if (l > t.slides.length && !r)
                    break;
                n.push(o(l))
            }
    else
        n.push(o(t.activeIndex));
    for (s = 0; s < n.length; s += 1)
        if (typeof n[s] < "u") {
            const l = n[s].offsetHeight;
            i = l > i ? l : i
        }
    (i || i === 0) && (t.wrapperEl.style.height = `${i}px`)
}
function QM() {
    const e = this
      , t = e.slides
      , n = e.isElement ? e.isHorizontal() ? e.wrapperEl.offsetLeft : e.wrapperEl.offsetTop : 0;
    for (let r = 0; r < t.length; r += 1)
        t[r].swiperSlideOffset = (e.isHorizontal() ? t[r].offsetLeft : t[r].offsetTop) - n - e.cssOverflowAdjustment()
}
const E_ = (e, t, n) => {
    t && !e.classList.contains(n) ? e.classList.add(n) : !t && e.classList.contains(n) && e.classList.remove(n)
}
;
function JM(e) {
    e === void 0 && (e = this && this.translate || 0);
    const t = this
      , n = t.params
      , {slides: r, rtlTranslate: i, snapGrid: s} = t;
    if (r.length === 0)
        return;
    typeof r[0].swiperSlideOffset > "u" && t.updateSlidesOffset();
    let o = -e;
    i && (o = e),
    t.visibleSlidesIndexes = [],
    t.visibleSlides = [];
    let l = n.spaceBetween;
    typeof l == "string" && l.indexOf("%") >= 0 ? l = parseFloat(l.replace("%", "")) / 100 * t.size : typeof l == "string" && (l = parseFloat(l));
    for (let a = 0; a < r.length; a += 1) {
        const c = r[a];
        let d = c.swiperSlideOffset;
        n.cssMode && n.centeredSlides && (d -= r[0].swiperSlideOffset);
        const f = (o + (n.centeredSlides ? t.minTranslate() : 0) - d) / (c.swiperSlideSize + l)
          , p = (o - s[0] + (n.centeredSlides ? t.minTranslate() : 0) - d) / (c.swiperSlideSize + l)
          , h = -(o - d)
          , m = h + t.slidesSizesGrid[a]
          , _ = h >= 0 && h <= t.size - t.slidesSizesGrid[a]
          , x = h >= 0 && h < t.size - 1 || m > 1 && m <= t.size || h <= 0 && m >= t.size;
        x && (t.visibleSlides.push(c),
        t.visibleSlidesIndexes.push(a)),
        E_(c, x, n.slideVisibleClass),
        E_(c, _, n.slideFullyVisibleClass),
        c.progress = i ? -f : f,
        c.originalProgress = i ? -p : p
    }
}
function ZM(e) {
    const t = this;
    if (typeof e > "u") {
        const d = t.rtlTranslate ? -1 : 1;
        e = t && t.translate && t.translate * d || 0
    }
    const n = t.params
      , r = t.maxTranslate() - t.minTranslate();
    let {progress: i, isBeginning: s, isEnd: o, progressLoop: l} = t;
    const a = s
      , c = o;
    if (r === 0)
        i = 0,
        s = !0,
        o = !0;
    else {
        i = (e - t.minTranslate()) / r;
        const d = Math.abs(e - t.minTranslate()) < 1
          , f = Math.abs(e - t.maxTranslate()) < 1;
        s = d || i <= 0,
        o = f || i >= 1,
        d && (i = 0),
        f && (i = 1)
    }
    if (n.loop) {
        const d = t.getSlideIndexByData(0)
          , f = t.getSlideIndexByData(t.slides.length - 1)
          , p = t.slidesGrid[d]
          , h = t.slidesGrid[f]
          , m = t.slidesGrid[t.slidesGrid.length - 1]
          , _ = Math.abs(e);
        _ >= p ? l = (_ - p) / m : l = (_ + m - h) / m,
        l > 1 && (l -= 1)
    }
    Object.assign(t, {
        progress: i,
        progressLoop: l,
        isBeginning: s,
        isEnd: o
    }),
    (n.watchSlidesProgress || n.centeredSlides && n.autoHeight) && t.updateSlidesProgress(e),
    s && !a && t.emit("reachBeginning toEdge"),
    o && !c && t.emit("reachEnd toEdge"),
    (a && !s || c && !o) && t.emit("fromEdge"),
    t.emit("progress", i)
}
const Vu = (e, t, n) => {
    t && !e.classList.contains(n) ? e.classList.add(n) : !t && e.classList.contains(n) && e.classList.remove(n)
}
;
function eO() {
    const e = this
      , {slides: t, params: n, slidesEl: r, activeIndex: i} = e
      , s = e.virtual && n.virtual.enabled
      , o = e.grid && n.grid && n.grid.rows > 1
      , l = f => In(r, `.${n.slideClass}${f}, swiper-slide${f}`)[0];
    let a, c, d;
    if (s)
        if (n.loop) {
            let f = i - e.virtual.slidesBefore;
            f < 0 && (f = e.virtual.slides.length + f),
            f >= e.virtual.slides.length && (f -= e.virtual.slides.length),
            a = l(`[data-swiper-slide-index="${f}"]`)
        } else
            a = l(`[data-swiper-slide-index="${i}"]`);
    else
        o ? (a = t.filter(f => f.column === i)[0],
        d = t.filter(f => f.column === i + 1)[0],
        c = t.filter(f => f.column === i - 1)[0]) : a = t[i];
    a && (o || (d = $M(a, `.${n.slideClass}, swiper-slide`)[0],
    n.loop && !d && (d = t[0]),
    c = DM(a, `.${n.slideClass}, swiper-slide`)[0],
    n.loop && !c === 0 && (c = t[t.length - 1]))),
    t.forEach(f => {
        Vu(f, f === a, n.slideActiveClass),
        Vu(f, f === d, n.slideNextClass),
        Vu(f, f === c, n.slidePrevClass)
    }
    ),
    e.emitSlidesClasses()
}
const dl = (e, t) => {
    if (!e || e.destroyed || !e.params)
        return;
    const n = () => e.isElement ? "swiper-slide" : `.${e.params.slideClass}`
      , r = t.closest(n());
    if (r) {
        let i = r.querySelector(`.${e.params.lazyPreloaderClass}`);
        !i && e.isElement && (r.shadowRoot ? i = r.shadowRoot.querySelector(`.${e.params.lazyPreloaderClass}`) : requestAnimationFrame( () => {
            r.shadowRoot && (i = r.shadowRoot.querySelector(`.${e.params.lazyPreloaderClass}`),
            i && i.remove())
        }
        )),
        i && i.remove()
    }
}
  , zu = (e, t) => {
    if (!e.slides[t])
        return;
    const n = e.slides[t].querySelector('[loading="lazy"]');
    n && n.removeAttribute("loading")
}
  , gf = e => {
    if (!e || e.destroyed || !e.params)
        return;
    let t = e.params.lazyPreloadPrevNext;
    const n = e.slides.length;
    if (!n || !t || t < 0)
        return;
    t = Math.min(t, n);
    const r = e.params.slidesPerView === "auto" ? e.slidesPerViewDynamic() : Math.ceil(e.params.slidesPerView)
      , i = e.activeIndex;
    if (e.params.grid && e.params.grid.rows > 1) {
        const o = i
          , l = [o - t];
        l.push(...Array.from({
            length: t
        }).map( (a, c) => o + r + c)),
        e.slides.forEach( (a, c) => {
            l.includes(a.column) && zu(e, c)
        }
        );
        return
    }
    const s = i + r - 1;
    if (e.params.rewind || e.params.loop)
        for (let o = i - t; o <= s + t; o += 1) {
            const l = (o % n + n) % n;
            (l < i || l > s) && zu(e, l)
        }
    else
        for (let o = Math.max(i - t, 0); o <= Math.min(s + t, n - 1); o += 1)
            o !== i && (o > s || o < i) && zu(e, o)
}
;
function tO(e) {
    const {slidesGrid: t, params: n} = e
      , r = e.rtlTranslate ? e.translate : -e.translate;
    let i;
    for (let s = 0; s < t.length; s += 1)
        typeof t[s + 1] < "u" ? r >= t[s] && r < t[s + 1] - (t[s + 1] - t[s]) / 2 ? i = s : r >= t[s] && r < t[s + 1] && (i = s + 1) : r >= t[s] && (i = s);
    return n.normalizeSlideIndex && (i < 0 || typeof i > "u") && (i = 0),
    i
}
function nO(e) {
    const t = this
      , n = t.rtlTranslate ? t.translate : -t.translate
      , {snapGrid: r, params: i, activeIndex: s, realIndex: o, snapIndex: l} = t;
    let a = e, c;
    const d = h => {
        let m = h - t.virtual.slidesBefore;
        return m < 0 && (m = t.virtual.slides.length + m),
        m >= t.virtual.slides.length && (m -= t.virtual.slides.length),
        m
    }
    ;
    if (typeof a > "u" && (a = tO(t)),
    r.indexOf(n) >= 0)
        c = r.indexOf(n);
    else {
        const h = Math.min(i.slidesPerGroupSkip, a);
        c = h + Math.floor((a - h) / i.slidesPerGroup)
    }
    if (c >= r.length && (c = r.length - 1),
    a === s && !t.params.loop) {
        c !== l && (t.snapIndex = c,
        t.emit("snapIndexChange"));
        return
    }
    if (a === s && t.params.loop && t.virtual && t.params.virtual.enabled) {
        t.realIndex = d(a);
        return
    }
    const f = t.grid && i.grid && i.grid.rows > 1;
    let p;
    if (t.virtual && i.virtual.enabled && i.loop)
        p = d(a);
    else if (f) {
        const h = t.slides.filter(_ => _.column === a)[0];
        let m = parseInt(h.getAttribute("data-swiper-slide-index"), 10);
        Number.isNaN(m) && (m = Math.max(t.slides.indexOf(h), 0)),
        p = Math.floor(m / i.grid.rows)
    } else if (t.slides[a]) {
        const h = t.slides[a].getAttribute("data-swiper-slide-index");
        h ? p = parseInt(h, 10) : p = a
    } else
        p = a;
    Object.assign(t, {
        previousSnapIndex: l,
        snapIndex: c,
        previousRealIndex: o,
        realIndex: p,
        previousIndex: s,
        activeIndex: a
    }),
    t.initialized && gf(t),
    t.emit("activeIndexChange"),
    t.emit("snapIndexChange"),
    (t.initialized || t.params.runCallbacksOnInit) && (o !== p && t.emit("realIndexChange"),
    t.emit("slideChange"))
}
function rO(e, t) {
    const n = this
      , r = n.params;
    let i = e.closest(`.${r.slideClass}, swiper-slide`);
    !i && n.isElement && t && t.length > 1 && t.includes(e) && [...t.slice(t.indexOf(e) + 1, t.length)].forEach(l => {
        !i && l.matches && l.matches(`.${r.slideClass}, swiper-slide`) && (i = l)
    }
    );
    let s = !1, o;
    if (i) {
        for (let l = 0; l < n.slides.length; l += 1)
            if (n.slides[l] === i) {
                s = !0,
                o = l;
                break
            }
    }
    if (i && s)
        n.clickedSlide = i,
        n.virtual && n.params.virtual.enabled ? n.clickedIndex = parseInt(i.getAttribute("data-swiper-slide-index"), 10) : n.clickedIndex = o;
    else {
        n.clickedSlide = void 0,
        n.clickedIndex = void 0;
        return
    }
    r.slideToClickedSlide && n.clickedIndex !== void 0 && n.clickedIndex !== n.activeIndex && n.slideToClickedSlide()
}
var iO = {
    updateSize: YM,
    updateSlides: KM,
    updateAutoHeight: XM,
    updateSlidesOffset: QM,
    updateSlidesProgress: JM,
    updateProgress: ZM,
    updateSlidesClasses: eO,
    updateActiveIndex: nO,
    updateClickedSlide: rO
};
function sO(e) {
    e === void 0 && (e = this.isHorizontal() ? "x" : "y");
    const t = this
      , {params: n, rtlTranslate: r, translate: i, wrapperEl: s} = t;
    if (n.virtualTranslate)
        return r ? -i : i;
    if (n.cssMode)
        return i;
    let o = OM(s, e);
    return o += t.cssOverflowAdjustment(),
    r && (o = -o),
    o || 0
}
function oO(e, t) {
    const n = this
      , {rtlTranslate: r, params: i, wrapperEl: s, progress: o} = n;
    let l = 0
      , a = 0;
    const c = 0;
    n.isHorizontal() ? l = r ? -e : e : a = e,
    i.roundLengths && (l = Math.floor(l),
    a = Math.floor(a)),
    n.previousTranslate = n.translate,
    n.translate = n.isHorizontal() ? l : a,
    i.cssMode ? s[n.isHorizontal() ? "scrollLeft" : "scrollTop"] = n.isHorizontal() ? -l : -a : i.virtualTranslate || (n.isHorizontal() ? l -= n.cssOverflowAdjustment() : a -= n.cssOverflowAdjustment(),
    s.style.transform = `translate3d(${l}px, ${a}px, ${c}px)`);
    let d;
    const f = n.maxTranslate() - n.minTranslate();
    f === 0 ? d = 0 : d = (e - n.minTranslate()) / f,
    d !== o && n.updateProgress(e),
    n.emit("setTranslate", n.translate, t)
}
function aO() {
    return -this.snapGrid[0]
}
function lO() {
    return -this.snapGrid[this.snapGrid.length - 1]
}
function cO(e, t, n, r, i) {
    e === void 0 && (e = 0),
    t === void 0 && (t = this.params.speed),
    n === void 0 && (n = !0),
    r === void 0 && (r = !0);
    const s = this
      , {params: o, wrapperEl: l} = s;
    if (s.animating && o.preventInteractionOnTransition)
        return !1;
    const a = s.minTranslate()
      , c = s.maxTranslate();
    let d;
    if (r && e > a ? d = a : r && e < c ? d = c : d = e,
    s.updateProgress(d),
    o.cssMode) {
        const f = s.isHorizontal();
        if (t === 0)
            l[f ? "scrollLeft" : "scrollTop"] = -d;
        else {
            if (!s.support.smoothScroll)
                return xx({
                    swiper: s,
                    targetPosition: -d,
                    side: f ? "left" : "top"
                }),
                !0;
            l.scrollTo({
                [f ? "left" : "top"]: -d,
                behavior: "smooth"
            })
        }
        return !0
    }
    return t === 0 ? (s.setTransition(0),
    s.setTranslate(d),
    n && (s.emit("beforeTransitionStart", t, i),
    s.emit("transitionEnd"))) : (s.setTransition(t),
    s.setTranslate(d),
    n && (s.emit("beforeTransitionStart", t, i),
    s.emit("transitionStart")),
    s.animating || (s.animating = !0,
    s.onTranslateToWrapperTransitionEnd || (s.onTranslateToWrapperTransitionEnd = function(p) {
        !s || s.destroyed || p.target === this && (s.wrapperEl.removeEventListener("transitionend", s.onTranslateToWrapperTransitionEnd),
        s.onTranslateToWrapperTransitionEnd = null,
        delete s.onTranslateToWrapperTransitionEnd,
        s.animating = !1,
        n && s.emit("transitionEnd"))
    }
    ),
    s.wrapperEl.addEventListener("transitionend", s.onTranslateToWrapperTransitionEnd))),
    !0
}
var uO = {
    getTranslate: sO,
    setTranslate: oO,
    minTranslate: aO,
    maxTranslate: lO,
    translateTo: cO
};
function dO(e, t) {
    const n = this;
    n.params.cssMode || (n.wrapperEl.style.transitionDuration = `${e}ms`,
    n.wrapperEl.style.transitionDelay = e === 0 ? "0ms" : ""),
    n.emit("setTransition", e, t)
}
function kx(e) {
    let {swiper: t, runCallbacks: n, direction: r, step: i} = e;
    const {activeIndex: s, previousIndex: o} = t;
    let l = r;
    if (l || (s > o ? l = "next" : s < o ? l = "prev" : l = "reset"),
    t.emit(`transition${i}`),
    n && s !== o) {
        if (l === "reset") {
            t.emit(`slideResetTransition${i}`);
            return
        }
        t.emit(`slideChangeTransition${i}`),
        l === "next" ? t.emit(`slideNextTransition${i}`) : t.emit(`slidePrevTransition${i}`)
    }
}
function fO(e, t) {
    e === void 0 && (e = !0);
    const n = this
      , {params: r} = n;
    r.cssMode || (r.autoHeight && n.updateAutoHeight(),
    kx({
        swiper: n,
        runCallbacks: e,
        direction: t,
        step: "Start"
    }))
}
function pO(e, t) {
    e === void 0 && (e = !0);
    const n = this
      , {params: r} = n;
    n.animating = !1,
    !r.cssMode && (n.setTransition(0),
    kx({
        swiper: n,
        runCallbacks: e,
        direction: t,
        step: "End"
    }))
}
var mO = {
    setTransition: dO,
    transitionStart: fO,
    transitionEnd: pO
};
function hO(e, t, n, r, i) {
    e === void 0 && (e = 0),
    n === void 0 && (n = !0),
    typeof e == "string" && (e = parseInt(e, 10));
    const s = this;
    let o = e;
    o < 0 && (o = 0);
    const {params: l, snapGrid: a, slidesGrid: c, previousIndex: d, activeIndex: f, rtlTranslate: p, wrapperEl: h, enabled: m} = s;
    if (!m && !r && !i || s.destroyed || s.animating && l.preventInteractionOnTransition)
        return !1;
    typeof t > "u" && (t = s.params.speed);
    const _ = Math.min(s.params.slidesPerGroupSkip, o);
    let x = _ + Math.floor((o - _) / s.params.slidesPerGroup);
    x >= a.length && (x = a.length - 1);
    const v = -a[x];
    if (l.normalizeSlideIndex)
        for (let y = 0; y < c.length; y += 1) {
            const b = -Math.floor(v * 100)
              , C = Math.floor(c[y] * 100)
              , S = Math.floor(c[y + 1] * 100);
            typeof c[y + 1] < "u" ? b >= C && b < S - (S - C) / 2 ? o = y : b >= C && b < S && (o = y + 1) : b >= C && (o = y)
        }
    if (s.initialized && o !== f && (!s.allowSlideNext && (p ? v > s.translate && v > s.minTranslate() : v < s.translate && v < s.minTranslate()) || !s.allowSlidePrev && v > s.translate && v > s.maxTranslate() && (f || 0) !== o))
        return !1;
    o !== (d || 0) && n && s.emit("beforeSlideChangeStart"),
    s.updateProgress(v);
    let w;
    if (o > f ? w = "next" : o < f ? w = "prev" : w = "reset",
    p && -v === s.translate || !p && v === s.translate)
        return s.updateActiveIndex(o),
        l.autoHeight && s.updateAutoHeight(),
        s.updateSlidesClasses(),
        l.effect !== "slide" && s.setTranslate(v),
        w !== "reset" && (s.transitionStart(n, w),
        s.transitionEnd(n, w)),
        !1;
    if (l.cssMode) {
        const y = s.isHorizontal()
          , b = p ? v : -v;
        if (t === 0) {
            const C = s.virtual && s.params.virtual.enabled;
            C && (s.wrapperEl.style.scrollSnapType = "none",
            s._immediateVirtual = !0),
            C && !s._cssModeVirtualInitialSet && s.params.initialSlide > 0 ? (s._cssModeVirtualInitialSet = !0,
            requestAnimationFrame( () => {
                h[y ? "scrollLeft" : "scrollTop"] = b
            }
            )) : h[y ? "scrollLeft" : "scrollTop"] = b,
            C && requestAnimationFrame( () => {
                s.wrapperEl.style.scrollSnapType = "",
                s._immediateVirtual = !1
            }
            )
        } else {
            if (!s.support.smoothScroll)
                return xx({
                    swiper: s,
                    targetPosition: b,
                    side: y ? "left" : "top"
                }),
                !0;
            h.scrollTo({
                [y ? "left" : "top"]: b,
                behavior: "smooth"
            })
        }
        return !0
    }
    return s.setTransition(t),
    s.setTranslate(v),
    s.updateActiveIndex(o),
    s.updateSlidesClasses(),
    s.emit("beforeTransitionStart", t, r),
    s.transitionStart(n, w),
    t === 0 ? s.transitionEnd(n, w) : s.animating || (s.animating = !0,
    s.onSlideToWrapperTransitionEnd || (s.onSlideToWrapperTransitionEnd = function(b) {
        !s || s.destroyed || b.target === this && (s.wrapperEl.removeEventListener("transitionend", s.onSlideToWrapperTransitionEnd),
        s.onSlideToWrapperTransitionEnd = null,
        delete s.onSlideToWrapperTransitionEnd,
        s.transitionEnd(n, w))
    }
    ),
    s.wrapperEl.addEventListener("transitionend", s.onSlideToWrapperTransitionEnd)),
    !0
}
function gO(e, t, n, r) {
    e === void 0 && (e = 0),
    n === void 0 && (n = !0),
    typeof e == "string" && (e = parseInt(e, 10));
    const i = this;
    if (i.destroyed)
        return;
    typeof t > "u" && (t = i.params.speed);
    const s = i.grid && i.params.grid && i.params.grid.rows > 1;
    let o = e;
    if (i.params.loop)
        if (i.virtual && i.params.virtual.enabled)
            o = o + i.virtual.slidesBefore;
        else {
            let l;
            if (s) {
                const p = o * i.params.grid.rows;
                l = i.slides.filter(h => h.getAttribute("data-swiper-slide-index") * 1 === p)[0].column
            } else
                l = i.getSlideIndexByData(o);
            const a = s ? Math.ceil(i.slides.length / i.params.grid.rows) : i.slides.length
              , {centeredSlides: c} = i.params;
            let d = i.params.slidesPerView;
            d === "auto" ? d = i.slidesPerViewDynamic() : (d = Math.ceil(parseFloat(i.params.slidesPerView, 10)),
            c && d % 2 === 0 && (d = d + 1));
            let f = a - l < d;
            if (c && (f = f || l < Math.ceil(d / 2)),
            r && c && i.params.slidesPerView !== "auto" && !s && (f = !1),
            f) {
                const p = c ? l < i.activeIndex ? "prev" : "next" : l - i.activeIndex - 1 < i.params.slidesPerView ? "next" : "prev";
                i.loopFix({
                    direction: p,
                    slideTo: !0,
                    activeSlideIndex: p === "next" ? l + 1 : l - a + 1,
                    slideRealIndex: p === "next" ? i.realIndex : void 0
                })
            }
            if (s) {
                const p = o * i.params.grid.rows;
                o = i.slides.filter(h => h.getAttribute("data-swiper-slide-index") * 1 === p)[0].column
            } else
                o = i.getSlideIndexByData(o)
        }
    return requestAnimationFrame( () => {
        i.slideTo(o, t, n, r)
    }
    ),
    i
}
function _O(e, t, n) {
    t === void 0 && (t = !0);
    const r = this
      , {enabled: i, params: s, animating: o} = r;
    if (!i || r.destroyed)
        return r;
    typeof e > "u" && (e = r.params.speed);
    let l = s.slidesPerGroup;
    s.slidesPerView === "auto" && s.slidesPerGroup === 1 && s.slidesPerGroupAuto && (l = Math.max(r.slidesPerViewDynamic("current", !0), 1));
    const a = r.activeIndex < s.slidesPerGroupSkip ? 1 : l
      , c = r.virtual && s.virtual.enabled;
    if (s.loop) {
        if (o && !c && s.loopPreventsSliding)
            return !1;
        if (r.loopFix({
            direction: "next"
        }),
        r._clientLeft = r.wrapperEl.clientLeft,
        r.activeIndex === r.slides.length - 1 && s.cssMode)
            return requestAnimationFrame( () => {
                r.slideTo(r.activeIndex + a, e, t, n)
            }
            ),
            !0
    }
    return s.rewind && r.isEnd ? r.slideTo(0, e, t, n) : r.slideTo(r.activeIndex + a, e, t, n)
}
function vO(e, t, n) {
    t === void 0 && (t = !0);
    const r = this
      , {params: i, snapGrid: s, slidesGrid: o, rtlTranslate: l, enabled: a, animating: c} = r;
    if (!a || r.destroyed)
        return r;
    typeof e > "u" && (e = r.params.speed);
    const d = r.virtual && i.virtual.enabled;
    if (i.loop) {
        if (c && !d && i.loopPreventsSliding)
            return !1;
        r.loopFix({
            direction: "prev"
        }),
        r._clientLeft = r.wrapperEl.clientLeft
    }
    const f = l ? r.translate : -r.translate;
    function p(v) {
        return v < 0 ? -Math.floor(Math.abs(v)) : Math.floor(v)
    }
    const h = p(f)
      , m = s.map(v => p(v));
    let _ = s[m.indexOf(h) - 1];
    if (typeof _ > "u" && i.cssMode) {
        let v;
        s.forEach( (w, y) => {
            h >= w && (v = y)
        }
        ),
        typeof v < "u" && (_ = s[v > 0 ? v - 1 : v])
    }
    let x = 0;
    if (typeof _ < "u" && (x = o.indexOf(_),
    x < 0 && (x = r.activeIndex - 1),
    i.slidesPerView === "auto" && i.slidesPerGroup === 1 && i.slidesPerGroupAuto && (x = x - r.slidesPerViewDynamic("previous", !0) + 1,
    x = Math.max(x, 0))),
    i.rewind && r.isBeginning) {
        const v = r.params.virtual && r.params.virtual.enabled && r.virtual ? r.virtual.slides.length - 1 : r.slides.length - 1;
        return r.slideTo(v, e, t, n)
    } else if (i.loop && r.activeIndex === 0 && i.cssMode)
        return requestAnimationFrame( () => {
            r.slideTo(x, e, t, n)
        }
        ),
        !0;
    return r.slideTo(x, e, t, n)
}
function yO(e, t, n) {
    t === void 0 && (t = !0);
    const r = this;
    if (!r.destroyed)
        return typeof e > "u" && (e = r.params.speed),
        r.slideTo(r.activeIndex, e, t, n)
}
function wO(e, t, n, r) {
    t === void 0 && (t = !0),
    r === void 0 && (r = .5);
    const i = this;
    if (i.destroyed)
        return;
    typeof e > "u" && (e = i.params.speed);
    let s = i.activeIndex;
    const o = Math.min(i.params.slidesPerGroupSkip, s)
      , l = o + Math.floor((s - o) / i.params.slidesPerGroup)
      , a = i.rtlTranslate ? i.translate : -i.translate;
    if (a >= i.snapGrid[l]) {
        const c = i.snapGrid[l]
          , d = i.snapGrid[l + 1];
        a - c > (d - c) * r && (s += i.params.slidesPerGroup)
    } else {
        const c = i.snapGrid[l - 1]
          , d = i.snapGrid[l];
        a - c <= (d - c) * r && (s -= i.params.slidesPerGroup)
    }
    return s = Math.max(s, 0),
    s = Math.min(s, i.slidesGrid.length - 1),
    i.slideTo(s, e, t, n)
}
function xO() {
    const e = this;
    if (e.destroyed)
        return;
    const {params: t, slidesEl: n} = e
      , r = t.slidesPerView === "auto" ? e.slidesPerViewDynamic() : t.slidesPerView;
    let i = e.clickedIndex, s;
    const o = e.isElement ? "swiper-slide" : `.${t.slideClass}`;
    if (t.loop) {
        if (e.animating)
            return;
        s = parseInt(e.clickedSlide.getAttribute("data-swiper-slide-index"), 10),
        t.centeredSlides ? i < e.loopedSlides - r / 2 || i > e.slides.length - e.loopedSlides + r / 2 ? (e.loopFix(),
        i = e.getSlideIndex(In(n, `${o}[data-swiper-slide-index="${s}"]`)[0]),
        mf( () => {
            e.slideTo(i)
        }
        )) : e.slideTo(i) : i > e.slides.length - r ? (e.loopFix(),
        i = e.getSlideIndex(In(n, `${o}[data-swiper-slide-index="${s}"]`)[0]),
        mf( () => {
            e.slideTo(i)
        }
        )) : e.slideTo(i)
    } else
        e.slideTo(i)
}
var bO = {
    slideTo: hO,
    slideToLoop: gO,
    slideNext: _O,
    slidePrev: vO,
    slideReset: yO,
    slideToClosest: wO,
    slideToClickedSlide: xO
};
function SO(e) {
    const t = this
      , {params: n, slidesEl: r} = t;
    if (!n.loop || t.virtual && t.params.virtual.enabled)
        return;
    const i = () => {
        In(r, `.${n.slideClass}, swiper-slide`).forEach( (f, p) => {
            f.setAttribute("data-swiper-slide-index", p)
        }
        )
    }
      , s = t.grid && n.grid && n.grid.rows > 1
      , o = n.slidesPerGroup * (s ? n.grid.rows : 1)
      , l = t.slides.length % o !== 0
      , a = s && t.slides.length % n.grid.rows !== 0
      , c = d => {
        for (let f = 0; f < d; f += 1) {
            const p = t.isElement ? Uo("swiper-slide", [n.slideBlankClass]) : Uo("div", [n.slideClass, n.slideBlankClass]);
            t.slidesEl.append(p)
        }
    }
    ;
    if (l) {
        if (n.loopAddBlankSlides) {
            const d = o - t.slides.length % o;
            c(d),
            t.recalcSlides(),
            t.updateSlides()
        } else
            Xl("Swiper Loop Warning: The number of slides is not even to slidesPerGroup, loop mode may not function properly. You need to add more slides (or make duplicates, or empty slides)");
        i()
    } else if (a) {
        if (n.loopAddBlankSlides) {
            const d = n.grid.rows - t.slides.length % n.grid.rows;
            c(d),
            t.recalcSlides(),
            t.updateSlides()
        } else
            Xl("Swiper Loop Warning: The number of slides is not even to grid.rows, loop mode may not function properly. You need to add more slides (or make duplicates, or empty slides)");
        i()
    } else
        i();
    t.loopFix({
        slideRealIndex: e,
        direction: n.centeredSlides ? void 0 : "next"
    })
}
function CO(e) {
    let {slideRealIndex: t, slideTo: n=!0, direction: r, setTranslate: i, activeSlideIndex: s, byController: o, byMousewheel: l} = e === void 0 ? {} : e;
    const a = this;
    if (!a.params.loop)
        return;
    a.emit("beforeLoopFix");
    const {slides: c, allowSlidePrev: d, allowSlideNext: f, slidesEl: p, params: h} = a
      , {centeredSlides: m} = h;
    if (a.allowSlidePrev = !0,
    a.allowSlideNext = !0,
    a.virtual && h.virtual.enabled) {
        n && (!h.centeredSlides && a.snapIndex === 0 ? a.slideTo(a.virtual.slides.length, 0, !1, !0) : h.centeredSlides && a.snapIndex < h.slidesPerView ? a.slideTo(a.virtual.slides.length + a.snapIndex, 0, !1, !0) : a.snapIndex === a.snapGrid.length - 1 && a.slideTo(a.virtual.slidesBefore, 0, !1, !0)),
        a.allowSlidePrev = d,
        a.allowSlideNext = f,
        a.emit("loopFix");
        return
    }
    let _ = h.slidesPerView;
    _ === "auto" ? _ = a.slidesPerViewDynamic() : (_ = Math.ceil(parseFloat(h.slidesPerView, 10)),
    m && _ % 2 === 0 && (_ = _ + 1));
    const x = h.slidesPerGroupAuto ? _ : h.slidesPerGroup;
    let v = x;
    v % x !== 0 && (v += x - v % x),
    v += h.loopAdditionalSlides,
    a.loopedSlides = v;
    const w = a.grid && h.grid && h.grid.rows > 1;
    c.length < _ + v ? Xl("Swiper Loop Warning: The number of slides is not enough for loop mode, it will be disabled and not function properly. You need to add more slides (or make duplicates) or lower the values of slidesPerView and slidesPerGroup parameters") : w && h.grid.fill === "row" && Xl("Swiper Loop Warning: Loop mode is not compatible with grid.fill = `row`");
    const y = []
      , b = [];
    let C = a.activeIndex;
    typeof s > "u" ? s = a.getSlideIndex(c.filter(F => F.classList.contains(h.slideActiveClass))[0]) : C = s;
    const S = r === "next" || !r
      , T = r === "prev" || !r;
    let k = 0
      , P = 0;
    const R = w ? Math.ceil(c.length / h.grid.rows) : c.length
      , V = (w ? c[s].column : s) + (m && typeof i > "u" ? -_ / 2 + .5 : 0);
    if (V < v) {
        k = Math.max(v - V, x);
        for (let F = 0; F < v - V; F += 1) {
            const U = F - Math.floor(F / R) * R;
            if (w) {
                const Q = R - U - 1;
                for (let Z = c.length - 1; Z >= 0; Z -= 1)
                    c[Z].column === Q && y.push(Z)
            } else
                y.push(R - U - 1)
        }
    } else if (V + _ > R - v) {
        P = Math.max(V - (R - v * 2), x);
        for (let F = 0; F < P; F += 1) {
            const U = F - Math.floor(F / R) * R;
            w ? c.forEach( (Q, Z) => {
                Q.column === U && b.push(Z)
            }
            ) : b.push(U)
        }
    }
    if (a.__preventObserver__ = !0,
    requestAnimationFrame( () => {
        a.__preventObserver__ = !1
    }
    ),
    T && y.forEach(F => {
        c[F].swiperLoopMoveDOM = !0,
        p.prepend(c[F]),
        c[F].swiperLoopMoveDOM = !1
    }
    ),
    S && b.forEach(F => {
        c[F].swiperLoopMoveDOM = !0,
        p.append(c[F]),
        c[F].swiperLoopMoveDOM = !1
    }
    ),
    a.recalcSlides(),
    h.slidesPerView === "auto" ? a.updateSlides() : w && (y.length > 0 && T || b.length > 0 && S) && a.slides.forEach( (F, U) => {
        a.grid.updateSlide(U, F, a.slides)
    }
    ),
    h.watchSlidesProgress && a.updateSlidesOffset(),
    n) {
        if (y.length > 0 && T) {
            if (typeof t > "u") {
                const F = a.slidesGrid[C]
                  , Q = a.slidesGrid[C + k] - F;
                l ? a.setTranslate(a.translate - Q) : (a.slideTo(C + Math.ceil(k), 0, !1, !0),
                i && (a.touchEventsData.startTranslate = a.touchEventsData.startTranslate - Q,
                a.touchEventsData.currentTranslate = a.touchEventsData.currentTranslate - Q))
            } else if (i) {
                const F = w ? y.length / h.grid.rows : y.length;
                a.slideTo(a.activeIndex + F, 0, !1, !0),
                a.touchEventsData.currentTranslate = a.translate
            }
        } else if (b.length > 0 && S)
            if (typeof t > "u") {
                const F = a.slidesGrid[C]
                  , Q = a.slidesGrid[C - P] - F;
                l ? a.setTranslate(a.translate - Q) : (a.slideTo(C - P, 0, !1, !0),
                i && (a.touchEventsData.startTranslate = a.touchEventsData.startTranslate - Q,
                a.touchEventsData.currentTranslate = a.touchEventsData.currentTranslate - Q))
            } else {
                const F = w ? b.length / h.grid.rows : b.length;
                a.slideTo(a.activeIndex - F, 0, !1, !0)
            }
    }
    if (a.allowSlidePrev = d,
    a.allowSlideNext = f,
    a.controller && a.controller.control && !o) {
        const F = {
            slideRealIndex: t,
            direction: r,
            setTranslate: i,
            activeSlideIndex: s,
            byController: !0
        };
        Array.isArray(a.controller.control) ? a.controller.control.forEach(U => {
            !U.destroyed && U.params.loop && U.loopFix({
                ...F,
                slideTo: U.params.slidesPerView === h.slidesPerView ? n : !1
            })
        }
        ) : a.controller.control instanceof a.constructor && a.controller.control.params.loop && a.controller.control.loopFix({
            ...F,
            slideTo: a.controller.control.params.slidesPerView === h.slidesPerView ? n : !1
        })
    }
    a.emit("loopFix")
}
function kO() {
    const e = this
      , {params: t, slidesEl: n} = e;
    if (!t.loop || e.virtual && e.params.virtual.enabled)
        return;
    e.recalcSlides();
    const r = [];
    e.slides.forEach(i => {
        const s = typeof i.swiperSlideIndex > "u" ? i.getAttribute("data-swiper-slide-index") * 1 : i.swiperSlideIndex;
        r[s] = i
    }
    ),
    e.slides.forEach(i => {
        i.removeAttribute("data-swiper-slide-index")
    }
    ),
    r.forEach(i => {
        n.append(i)
    }
    ),
    e.recalcSlides(),
    e.slideTo(e.realIndex, 0)
}
var EO = {
    loopCreate: SO,
    loopFix: CO,
    loopDestroy: kO
};
function TO(e) {
    const t = this;
    if (!t.params.simulateTouch || t.params.watchOverflow && t.isLocked || t.params.cssMode)
        return;
    const n = t.params.touchEventsTarget === "container" ? t.el : t.wrapperEl;
    t.isElement && (t.__preventObserver__ = !0),
    n.style.cursor = "move",
    n.style.cursor = e ? "grabbing" : "grab",
    t.isElement && requestAnimationFrame( () => {
        t.__preventObserver__ = !1
    }
    )
}
function PO() {
    const e = this;
    e.params.watchOverflow && e.isLocked || e.params.cssMode || (e.isElement && (e.__preventObserver__ = !0),
    e[e.params.touchEventsTarget === "container" ? "el" : "wrapperEl"].style.cursor = "",
    e.isElement && requestAnimationFrame( () => {
        e.__preventObserver__ = !1
    }
    ))
}
var jO = {
    setGrabCursor: TO,
    unsetGrabCursor: PO
};
function NO(e, t) {
    t === void 0 && (t = this);
    function n(r) {
        if (!r || r === Fr() || r === Xt())
            return null;
        r.assignedSlot && (r = r.assignedSlot);
        const i = r.closest(e);
        return !i && !r.getRootNode ? null : i || n(r.getRootNode().host)
    }
    return n(t)
}
function T_(e, t, n) {
    const r = Xt()
      , {params: i} = e
      , s = i.edgeSwipeDetection
      , o = i.edgeSwipeThreshold;
    return s && (n <= o || n >= r.innerWidth - o) ? s === "prevent" ? (t.preventDefault(),
    !0) : !1 : !0
}
function RO(e) {
    const t = this
      , n = Fr();
    let r = e;
    r.originalEvent && (r = r.originalEvent);
    const i = t.touchEventsData;
    if (r.type === "pointerdown") {
        if (i.pointerId !== null && i.pointerId !== r.pointerId)
            return;
        i.pointerId = r.pointerId
    } else
        r.type === "touchstart" && r.targetTouches.length === 1 && (i.touchId = r.targetTouches[0].identifier);
    if (r.type === "touchstart") {
        T_(t, r, r.targetTouches[0].pageX);
        return
    }
    const {params: s, touches: o, enabled: l} = t;
    if (!l || !s.simulateTouch && r.pointerType === "mouse" || t.animating && s.preventInteractionOnTransition)
        return;
    !t.animating && s.cssMode && s.loop && t.loopFix();
    let a = r.target;
    if (s.touchEventsTarget === "wrapper" && !t.wrapperEl.contains(a) || "which"in r && r.which === 3 || "button"in r && r.button > 0 || i.isTouched && i.isMoved)
        return;
    const c = !!s.noSwipingClass && s.noSwipingClass !== ""
      , d = r.composedPath ? r.composedPath() : r.path;
    c && r.target && r.target.shadowRoot && d && (a = d[0]);
    const f = s.noSwipingSelector ? s.noSwipingSelector : `.${s.noSwipingClass}`
      , p = !!(r.target && r.target.shadowRoot);
    if (s.noSwiping && (p ? NO(f, a) : a.closest(f))) {
        t.allowClick = !0;
        return
    }
    if (s.swipeHandler && !a.closest(s.swipeHandler))
        return;
    o.currentX = r.pageX,
    o.currentY = r.pageY;
    const h = o.currentX
      , m = o.currentY;
    if (!T_(t, r, h))
        return;
    Object.assign(i, {
        isTouched: !0,
        isMoved: !1,
        allowTouchCallbacks: !0,
        isScrolling: void 0,
        startMoving: void 0
    }),
    o.startX = h,
    o.startY = m,
    i.touchStartTime = Kl(),
    t.allowClick = !0,
    t.updateSize(),
    t.swipeDirection = void 0,
    s.threshold > 0 && (i.allowThresholdMove = !1);
    let _ = !0;
    a.matches(i.focusableElements) && (_ = !1,
    a.nodeName === "SELECT" && (i.isTouched = !1)),
    n.activeElement && n.activeElement.matches(i.focusableElements) && n.activeElement !== a && n.activeElement.blur();
    const x = _ && t.allowTouchMove && s.touchStartPreventDefault;
    (s.touchStartForcePreventDefault || x) && !a.isContentEditable && r.preventDefault(),
    s.freeMode && s.freeMode.enabled && t.freeMode && t.animating && !s.cssMode && t.freeMode.onTouchStart(),
    t.emit("touchStart", r)
}
function IO(e) {
    const t = Fr()
      , n = this
      , r = n.touchEventsData
      , {params: i, touches: s, rtlTranslate: o, enabled: l} = n;
    if (!l || !i.simulateTouch && e.pointerType === "mouse")
        return;
    let a = e;
    if (a.originalEvent && (a = a.originalEvent),
    a.type === "pointermove" && (r.touchId !== null || a.pointerId !== r.pointerId))
        return;
    let c;
    if (a.type === "touchmove") {
        if (c = [...a.changedTouches].filter(S => S.identifier === r.touchId)[0],
        !c || c.identifier !== r.touchId)
            return
    } else
        c = a;
    if (!r.isTouched) {
        r.startMoving && r.isScrolling && n.emit("touchMoveOpposite", a);
        return
    }
    const d = c.pageX
      , f = c.pageY;
    if (a.preventedByNestedSwiper) {
        s.startX = d,
        s.startY = f;
        return
    }
    if (!n.allowTouchMove) {
        a.target.matches(r.focusableElements) || (n.allowClick = !1),
        r.isTouched && (Object.assign(s, {
            startX: d,
            startY: f,
            currentX: d,
            currentY: f
        }),
        r.touchStartTime = Kl());
        return
    }
    if (i.touchReleaseOnEdges && !i.loop) {
        if (n.isVertical()) {
            if (f < s.startY && n.translate <= n.maxTranslate() || f > s.startY && n.translate >= n.minTranslate()) {
                r.isTouched = !1,
                r.isMoved = !1;
                return
            }
        } else if (d < s.startX && n.translate <= n.maxTranslate() || d > s.startX && n.translate >= n.minTranslate())
            return
    }
    if (t.activeElement && a.target === t.activeElement && a.target.matches(r.focusableElements)) {
        r.isMoved = !0,
        n.allowClick = !1;
        return
    }
    r.allowTouchCallbacks && n.emit("touchMove", a),
    s.previousX = s.currentX,
    s.previousY = s.currentY,
    s.currentX = d,
    s.currentY = f;
    const p = s.currentX - s.startX
      , h = s.currentY - s.startY;
    if (n.params.threshold && Math.sqrt(p ** 2 + h ** 2) < n.params.threshold)
        return;
    if (typeof r.isScrolling > "u") {
        let S;
        n.isHorizontal() && s.currentY === s.startY || n.isVertical() && s.currentX === s.startX ? r.isScrolling = !1 : p * p + h * h >= 25 && (S = Math.atan2(Math.abs(h), Math.abs(p)) * 180 / Math.PI,
        r.isScrolling = n.isHorizontal() ? S > i.touchAngle : 90 - S > i.touchAngle)
    }
    if (r.isScrolling && n.emit("touchMoveOpposite", a),
    typeof r.startMoving > "u" && (s.currentX !== s.startX || s.currentY !== s.startY) && (r.startMoving = !0),
    r.isScrolling || a.type === "touchmove" && r.preventTouchMoveFromPointerMove) {
        r.isTouched = !1;
        return
    }
    if (!r.startMoving)
        return;
    n.allowClick = !1,
    !i.cssMode && a.cancelable && a.preventDefault(),
    i.touchMoveStopPropagation && !i.nested && a.stopPropagation();
    let m = n.isHorizontal() ? p : h
      , _ = n.isHorizontal() ? s.currentX - s.previousX : s.currentY - s.previousY;
    i.oneWayMovement && (m = Math.abs(m) * (o ? 1 : -1),
    _ = Math.abs(_) * (o ? 1 : -1)),
    s.diff = m,
    m *= i.touchRatio,
    o && (m = -m,
    _ = -_);
    const x = n.touchesDirection;
    n.swipeDirection = m > 0 ? "prev" : "next",
    n.touchesDirection = _ > 0 ? "prev" : "next";
    const v = n.params.loop && !i.cssMode
      , w = n.touchesDirection === "next" && n.allowSlideNext || n.touchesDirection === "prev" && n.allowSlidePrev;
    if (!r.isMoved) {
        if (v && w && n.loopFix({
            direction: n.swipeDirection
        }),
        r.startTranslate = n.getTranslate(),
        n.setTransition(0),
        n.animating) {
            const S = new window.CustomEvent("transitionend",{
                bubbles: !0,
                cancelable: !0,
                detail: {
                    bySwiperTouchMove: !0
                }
            });
            n.wrapperEl.dispatchEvent(S)
        }
        r.allowMomentumBounce = !1,
        i.grabCursor && (n.allowSlideNext === !0 || n.allowSlidePrev === !0) && n.setGrabCursor(!0),
        n.emit("sliderFirstMove", a)
    }
    let y;
    if (new Date().getTime(),
    r.isMoved && r.allowThresholdMove && x !== n.touchesDirection && v && w && Math.abs(m) >= 1) {
        Object.assign(s, {
            startX: d,
            startY: f,
            currentX: d,
            currentY: f,
            startTranslate: r.currentTranslate
        }),
        r.loopSwapReset = !0,
        r.startTranslate = r.currentTranslate;
        return
    }
    n.emit("sliderMove", a),
    r.isMoved = !0,
    r.currentTranslate = m + r.startTranslate;
    let b = !0
      , C = i.resistanceRatio;
    if (i.touchReleaseOnEdges && (C = 0),
    m > 0 ? (v && w && !y && r.allowThresholdMove && r.currentTranslate > (i.centeredSlides ? n.minTranslate() - n.slidesSizesGrid[n.activeIndex + 1] : n.minTranslate()) && n.loopFix({
        direction: "prev",
        setTranslate: !0,
        activeSlideIndex: 0
    }),
    r.currentTranslate > n.minTranslate() && (b = !1,
    i.resistance && (r.currentTranslate = n.minTranslate() - 1 + (-n.minTranslate() + r.startTranslate + m) ** C))) : m < 0 && (v && w && !y && r.allowThresholdMove && r.currentTranslate < (i.centeredSlides ? n.maxTranslate() + n.slidesSizesGrid[n.slidesSizesGrid.length - 1] : n.maxTranslate()) && n.loopFix({
        direction: "next",
        setTranslate: !0,
        activeSlideIndex: n.slides.length - (i.slidesPerView === "auto" ? n.slidesPerViewDynamic() : Math.ceil(parseFloat(i.slidesPerView, 10)))
    }),
    r.currentTranslate < n.maxTranslate() && (b = !1,
    i.resistance && (r.currentTranslate = n.maxTranslate() + 1 - (n.maxTranslate() - r.startTranslate - m) ** C))),
    b && (a.preventedByNestedSwiper = !0),
    !n.allowSlideNext && n.swipeDirection === "next" && r.currentTranslate < r.startTranslate && (r.currentTranslate = r.startTranslate),
    !n.allowSlidePrev && n.swipeDirection === "prev" && r.currentTranslate > r.startTranslate && (r.currentTranslate = r.startTranslate),
    !n.allowSlidePrev && !n.allowSlideNext && (r.currentTranslate = r.startTranslate),
    i.threshold > 0)
        if (Math.abs(m) > i.threshold || r.allowThresholdMove) {
            if (!r.allowThresholdMove) {
                r.allowThresholdMove = !0,
                s.startX = s.currentX,
                s.startY = s.currentY,
                r.currentTranslate = r.startTranslate,
                s.diff = n.isHorizontal() ? s.currentX - s.startX : s.currentY - s.startY;
                return
            }
        } else {
            r.currentTranslate = r.startTranslate;
            return
        }
    !i.followFinger || i.cssMode || ((i.freeMode && i.freeMode.enabled && n.freeMode || i.watchSlidesProgress) && (n.updateActiveIndex(),
    n.updateSlidesClasses()),
    i.freeMode && i.freeMode.enabled && n.freeMode && n.freeMode.onTouchMove(),
    n.updateProgress(r.currentTranslate),
    n.setTranslate(r.currentTranslate))
}
function LO(e) {
    const t = this
      , n = t.touchEventsData;
    let r = e;
    r.originalEvent && (r = r.originalEvent);
    let i;
    if (r.type === "touchend" || r.type === "touchcancel") {
        if (i = [...r.changedTouches].filter(C => C.identifier === n.touchId)[0],
        !i || i.identifier !== n.touchId)
            return
    } else {
        if (n.touchId !== null || r.pointerId !== n.pointerId)
            return;
        i = r
    }
    if (["pointercancel", "pointerout", "pointerleave", "contextmenu"].includes(r.type) && !(["pointercancel", "contextmenu"].includes(r.type) && (t.browser.isSafari || t.browser.isWebView)))
        return;
    n.pointerId = null,
    n.touchId = null;
    const {params: o, touches: l, rtlTranslate: a, slidesGrid: c, enabled: d} = t;
    if (!d || !o.simulateTouch && r.pointerType === "mouse")
        return;
    if (n.allowTouchCallbacks && t.emit("touchEnd", r),
    n.allowTouchCallbacks = !1,
    !n.isTouched) {
        n.isMoved && o.grabCursor && t.setGrabCursor(!1),
        n.isMoved = !1,
        n.startMoving = !1;
        return
    }
    o.grabCursor && n.isMoved && n.isTouched && (t.allowSlideNext === !0 || t.allowSlidePrev === !0) && t.setGrabCursor(!1);
    const f = Kl()
      , p = f - n.touchStartTime;
    if (t.allowClick) {
        const C = r.path || r.composedPath && r.composedPath();
        t.updateClickedSlide(C && C[0] || r.target, C),
        t.emit("tap click", r),
        p < 300 && f - n.lastClickTime < 300 && t.emit("doubleTap doubleClick", r)
    }
    if (n.lastClickTime = Kl(),
    mf( () => {
        t.destroyed || (t.allowClick = !0)
    }
    ),
    !n.isTouched || !n.isMoved || !t.swipeDirection || l.diff === 0 && !n.loopSwapReset || n.currentTranslate === n.startTranslate && !n.loopSwapReset) {
        n.isTouched = !1,
        n.isMoved = !1,
        n.startMoving = !1;
        return
    }
    n.isTouched = !1,
    n.isMoved = !1,
    n.startMoving = !1;
    let h;
    if (o.followFinger ? h = a ? t.translate : -t.translate : h = -n.currentTranslate,
    o.cssMode)
        return;
    if (o.freeMode && o.freeMode.enabled) {
        t.freeMode.onTouchEnd({
            currentPos: h
        });
        return
    }
    const m = h >= -t.maxTranslate() && !t.params.loop;
    let _ = 0
      , x = t.slidesSizesGrid[0];
    for (let C = 0; C < c.length; C += C < o.slidesPerGroupSkip ? 1 : o.slidesPerGroup) {
        const S = C < o.slidesPerGroupSkip - 1 ? 1 : o.slidesPerGroup;
        typeof c[C + S] < "u" ? (m || h >= c[C] && h < c[C + S]) && (_ = C,
        x = c[C + S] - c[C]) : (m || h >= c[C]) && (_ = C,
        x = c[c.length - 1] - c[c.length - 2])
    }
    let v = null
      , w = null;
    o.rewind && (t.isBeginning ? w = o.virtual && o.virtual.enabled && t.virtual ? t.virtual.slides.length - 1 : t.slides.length - 1 : t.isEnd && (v = 0));
    const y = (h - c[_]) / x
      , b = _ < o.slidesPerGroupSkip - 1 ? 1 : o.slidesPerGroup;
    if (p > o.longSwipesMs) {
        if (!o.longSwipes) {
            t.slideTo(t.activeIndex);
            return
        }
        t.swipeDirection === "next" && (y >= o.longSwipesRatio ? t.slideTo(o.rewind && t.isEnd ? v : _ + b) : t.slideTo(_)),
        t.swipeDirection === "prev" && (y > 1 - o.longSwipesRatio ? t.slideTo(_ + b) : w !== null && y < 0 && Math.abs(y) > o.longSwipesRatio ? t.slideTo(w) : t.slideTo(_))
    } else {
        if (!o.shortSwipes) {
            t.slideTo(t.activeIndex);
            return
        }
        t.navigation && (r.target === t.navigation.nextEl || r.target === t.navigation.prevEl) ? r.target === t.navigation.nextEl ? t.slideTo(_ + b) : t.slideTo(_) : (t.swipeDirection === "next" && t.slideTo(v !== null ? v : _ + b),
        t.swipeDirection === "prev" && t.slideTo(w !== null ? w : _))
    }
}
function P_() {
    const e = this
      , {params: t, el: n} = e;
    if (n && n.offsetWidth === 0)
        return;
    t.breakpoints && e.setBreakpoint();
    const {allowSlideNext: r, allowSlidePrev: i, snapGrid: s} = e
      , o = e.virtual && e.params.virtual.enabled;
    e.allowSlideNext = !0,
    e.allowSlidePrev = !0,
    e.updateSize(),
    e.updateSlides(),
    e.updateSlidesClasses();
    const l = o && t.loop;
    (t.slidesPerView === "auto" || t.slidesPerView > 1) && e.isEnd && !e.isBeginning && !e.params.centeredSlides && !l ? e.slideTo(e.slides.length - 1, 0, !1, !0) : e.params.loop && !o ? e.slideToLoop(e.realIndex, 0, !1, !0) : e.slideTo(e.activeIndex, 0, !1, !0),
    e.autoplay && e.autoplay.running && e.autoplay.paused && (clearTimeout(e.autoplay.resizeTimeout),
    e.autoplay.resizeTimeout = setTimeout( () => {
        e.autoplay && e.autoplay.running && e.autoplay.paused && e.autoplay.resume()
    }
    , 500)),
    e.allowSlidePrev = i,
    e.allowSlideNext = r,
    e.params.watchOverflow && s !== e.snapGrid && e.checkOverflow()
}
function MO(e) {
    const t = this;
    t.enabled && (t.allowClick || (t.params.preventClicks && e.preventDefault(),
    t.params.preventClicksPropagation && t.animating && (e.stopPropagation(),
    e.stopImmediatePropagation())))
}
function OO() {
    const e = this
      , {wrapperEl: t, rtlTranslate: n, enabled: r} = e;
    if (!r)
        return;
    e.previousTranslate = e.translate,
    e.isHorizontal() ? e.translate = -t.scrollLeft : e.translate = -t.scrollTop,
    e.translate === 0 && (e.translate = 0),
    e.updateActiveIndex(),
    e.updateSlidesClasses();
    let i;
    const s = e.maxTranslate() - e.minTranslate();
    s === 0 ? i = 0 : i = (e.translate - e.minTranslate()) / s,
    i !== e.progress && e.updateProgress(n ? -e.translate : e.translate),
    e.emit("setTranslate", e.translate, !1)
}
function AO(e) {
    const t = this;
    dl(t, e.target),
    !(t.params.cssMode || t.params.slidesPerView !== "auto" && !t.params.autoHeight) && t.update()
}
function DO() {
    const e = this;
    e.documentTouchHandlerProceeded || (e.documentTouchHandlerProceeded = !0,
    e.params.touchReleaseOnEdges && (e.el.style.touchAction = "auto"))
}
const Ex = (e, t) => {
    const n = Fr()
      , {params: r, el: i, wrapperEl: s, device: o} = e
      , l = !!r.nested
      , a = t === "on" ? "addEventListener" : "removeEventListener"
      , c = t;
    !i || typeof i == "string" || (n[a]("touchstart", e.onDocumentTouchStart, {
        passive: !1,
        capture: l
    }),
    i[a]("touchstart", e.onTouchStart, {
        passive: !1
    }),
    i[a]("pointerdown", e.onTouchStart, {
        passive: !1
    }),
    n[a]("touchmove", e.onTouchMove, {
        passive: !1,
        capture: l
    }),
    n[a]("pointermove", e.onTouchMove, {
        passive: !1,
        capture: l
    }),
    n[a]("touchend", e.onTouchEnd, {
        passive: !0
    }),
    n[a]("pointerup", e.onTouchEnd, {
        passive: !0
    }),
    n[a]("pointercancel", e.onTouchEnd, {
        passive: !0
    }),
    n[a]("touchcancel", e.onTouchEnd, {
        passive: !0
    }),
    n[a]("pointerout", e.onTouchEnd, {
        passive: !0
    }),
    n[a]("pointerleave", e.onTouchEnd, {
        passive: !0
    }),
    n[a]("contextmenu", e.onTouchEnd, {
        passive: !0
    }),
    (r.preventClicks || r.preventClicksPropagation) && i[a]("click", e.onClick, !0),
    r.cssMode && s[a]("scroll", e.onScroll),
    r.updateOnWindowResize ? e[c](o.ios || o.android ? "resize orientationchange observerUpdate" : "resize observerUpdate", P_, !0) : e[c]("observerUpdate", P_, !0),
    i[a]("load", e.onLoad, {
        capture: !0
    }))
}
;
function $O() {
    const e = this
      , {params: t} = e;
    e.onTouchStart = RO.bind(e),
    e.onTouchMove = IO.bind(e),
    e.onTouchEnd = LO.bind(e),
    e.onDocumentTouchStart = DO.bind(e),
    t.cssMode && (e.onScroll = OO.bind(e)),
    e.onClick = MO.bind(e),
    e.onLoad = AO.bind(e),
    Ex(e, "on")
}
function FO() {
    Ex(this, "off")
}
var VO = {
    attachEvents: $O,
    detachEvents: FO
};
const j_ = (e, t) => e.grid && t.grid && t.grid.rows > 1;
function zO() {
    const e = this
      , {realIndex: t, initialized: n, params: r, el: i} = e
      , s = r.breakpoints;
    if (!s || s && Object.keys(s).length === 0)
        return;
    const o = e.getBreakpoint(s, e.params.breakpointsBase, e.el);
    if (!o || e.currentBreakpoint === o)
        return;
    const a = (o in s ? s[o] : void 0) || e.originalParams
      , c = j_(e, r)
      , d = j_(e, a)
      , f = e.params.grabCursor
      , p = a.grabCursor
      , h = r.enabled;
    c && !d ? (i.classList.remove(`${r.containerModifierClass}grid`, `${r.containerModifierClass}grid-column`),
    e.emitContainerClasses()) : !c && d && (i.classList.add(`${r.containerModifierClass}grid`),
    (a.grid.fill && a.grid.fill === "column" || !a.grid.fill && r.grid.fill === "column") && i.classList.add(`${r.containerModifierClass}grid-column`),
    e.emitContainerClasses()),
    f && !p ? e.unsetGrabCursor() : !f && p && e.setGrabCursor(),
    ["navigation", "pagination", "scrollbar"].forEach(y => {
        if (typeof a[y] > "u")
            return;
        const b = r[y] && r[y].enabled
          , C = a[y] && a[y].enabled;
        b && !C && e[y].disable(),
        !b && C && e[y].enable()
    }
    );
    const m = a.direction && a.direction !== r.direction
      , _ = r.loop && (a.slidesPerView !== r.slidesPerView || m)
      , x = r.loop;
    m && n && e.changeDirection(),
    Bt(e.params, a);
    const v = e.params.enabled
      , w = e.params.loop;
    Object.assign(e, {
        allowTouchMove: e.params.allowTouchMove,
        allowSlideNext: e.params.allowSlideNext,
        allowSlidePrev: e.params.allowSlidePrev
    }),
    h && !v ? e.disable() : !h && v && e.enable(),
    e.currentBreakpoint = o,
    e.emit("_beforeBreakpoint", a),
    n && (_ ? (e.loopDestroy(),
    e.loopCreate(t),
    e.updateSlides()) : !x && w ? (e.loopCreate(t),
    e.updateSlides()) : x && !w && e.loopDestroy()),
    e.emit("breakpoint", a)
}
function BO(e, t, n) {
    if (t === void 0 && (t = "window"),
    !e || t === "container" && !n)
        return;
    let r = !1;
    const i = Xt()
      , s = t === "window" ? i.innerHeight : n.clientHeight
      , o = Object.keys(e).map(l => {
        if (typeof l == "string" && l.indexOf("@") === 0) {
            const a = parseFloat(l.substr(1));
            return {
                value: s * a,
                point: l
            }
        }
        return {
            value: l,
            point: l
        }
    }
    );
    o.sort( (l, a) => parseInt(l.value, 10) - parseInt(a.value, 10));
    for (let l = 0; l < o.length; l += 1) {
        const {point: a, value: c} = o[l];
        t === "window" ? i.matchMedia(`(min-width: ${c}px)`).matches && (r = a) : c <= n.clientWidth && (r = a)
    }
    return r || "max"
}
var UO = {
    setBreakpoint: zO,
    getBreakpoint: BO
};
function HO(e, t) {
    const n = [];
    return e.forEach(r => {
        typeof r == "object" ? Object.keys(r).forEach(i => {
            r[i] && n.push(t + i)
        }
        ) : typeof r == "string" && n.push(t + r)
    }
    ),
    n
}
function GO() {
    const e = this
      , {classNames: t, params: n, rtl: r, el: i, device: s} = e
      , o = HO(["initialized", n.direction, {
        "free-mode": e.params.freeMode && n.freeMode.enabled
    }, {
        autoheight: n.autoHeight
    }, {
        rtl: r
    }, {
        grid: n.grid && n.grid.rows > 1
    }, {
        "grid-column": n.grid && n.grid.rows > 1 && n.grid.fill === "column"
    }, {
        android: s.android
    }, {
        ios: s.ios
    }, {
        "css-mode": n.cssMode
    }, {
        centered: n.cssMode && n.centeredSlides
    }, {
        "watch-progress": n.watchSlidesProgress
    }], n.containerModifierClass);
    t.push(...o),
    i.classList.add(...t),
    e.emitContainerClasses()
}
function WO() {
    const e = this
      , {el: t, classNames: n} = e;
    !t || typeof t == "string" || (t.classList.remove(...n),
    e.emitContainerClasses())
}
var qO = {
    addClasses: GO,
    removeClasses: WO
};
function YO() {
    const e = this
      , {isLocked: t, params: n} = e
      , {slidesOffsetBefore: r} = n;
    if (r) {
        const i = e.slides.length - 1
          , s = e.slidesGrid[i] + e.slidesSizesGrid[i] + r * 2;
        e.isLocked = e.size > s
    } else
        e.isLocked = e.snapGrid.length === 1;
    n.allowSlideNext === !0 && (e.allowSlideNext = !e.isLocked),
    n.allowSlidePrev === !0 && (e.allowSlidePrev = !e.isLocked),
    t && t !== e.isLocked && (e.isEnd = !1),
    t !== e.isLocked && e.emit(e.isLocked ? "lock" : "unlock")
}
var KO = {
    checkOverflow: YO
}
  , _f = {
    init: !0,
    direction: "horizontal",
    oneWayMovement: !1,
    swiperElementNodeName: "SWIPER-CONTAINER",
    touchEventsTarget: "wrapper",
    initialSlide: 0,
    speed: 300,
    cssMode: !1,
    updateOnWindowResize: !0,
    resizeObserver: !0,
    nested: !1,
    createElements: !1,
    eventsPrefix: "swiper",
    enabled: !0,
    focusableElements: "input, select, option, textarea, button, video, label",
    width: null,
    height: null,
    preventInteractionOnTransition: !1,
    userAgent: null,
    url: null,
    edgeSwipeDetection: !1,
    edgeSwipeThreshold: 20,
    autoHeight: !1,
    setWrapperSize: !1,
    virtualTranslate: !1,
    effect: "slide",
    breakpoints: void 0,
    breakpointsBase: "window",
    spaceBetween: 0,
    slidesPerView: 1,
    slidesPerGroup: 1,
    slidesPerGroupSkip: 0,
    slidesPerGroupAuto: !1,
    centeredSlides: !1,
    centeredSlidesBounds: !1,
    slidesOffsetBefore: 0,
    slidesOffsetAfter: 0,
    normalizeSlideIndex: !0,
    centerInsufficientSlides: !1,
    watchOverflow: !0,
    roundLengths: !1,
    touchRatio: 1,
    touchAngle: 45,
    simulateTouch: !0,
    shortSwipes: !0,
    longSwipes: !0,
    longSwipesRatio: .5,
    longSwipesMs: 300,
    followFinger: !0,
    allowTouchMove: !0,
    threshold: 5,
    touchMoveStopPropagation: !1,
    touchStartPreventDefault: !0,
    touchStartForcePreventDefault: !1,
    touchReleaseOnEdges: !1,
    uniqueNavElements: !0,
    resistance: !0,
    resistanceRatio: .85,
    watchSlidesProgress: !1,
    grabCursor: !1,
    preventClicks: !0,
    preventClicksPropagation: !0,
    slideToClickedSlide: !1,
    loop: !1,
    loopAddBlankSlides: !0,
    loopAdditionalSlides: 0,
    loopPreventsSliding: !0,
    rewind: !1,
    allowSlidePrev: !0,
    allowSlideNext: !0,
    swipeHandler: null,
    noSwiping: !0,
    noSwipingClass: "swiper-no-swiping",
    noSwipingSelector: null,
    passiveListeners: !0,
    maxBackfaceHiddenSlides: 10,
    containerModifierClass: "swiper-",
    slideClass: "swiper-slide",
    slideBlankClass: "swiper-slide-blank",
    slideActiveClass: "swiper-slide-active",
    slideVisibleClass: "swiper-slide-visible",
    slideFullyVisibleClass: "swiper-slide-fully-visible",
    slideNextClass: "swiper-slide-next",
    slidePrevClass: "swiper-slide-prev",
    wrapperClass: "swiper-wrapper",
    lazyPreloaderClass: "swiper-lazy-preloader",
    lazyPreloadPrevNext: 0,
    runCallbacksOnInit: !0,
    _emitClasses: !1
};
function XO(e, t) {
    return function(r) {
        r === void 0 && (r = {});
        const i = Object.keys(r)[0]
          , s = r[i];
        if (typeof s != "object" || s === null) {
            Bt(t, r);
            return
        }
        if (e[i] === !0 && (e[i] = {
            enabled: !0
        }),
        i === "navigation" && e[i] && e[i].enabled && !e[i].prevEl && !e[i].nextEl && (e[i].auto = !0),
        ["pagination", "scrollbar"].indexOf(i) >= 0 && e[i] && e[i].enabled && !e[i].el && (e[i].auto = !0),
        !(i in e && "enabled"in s)) {
            Bt(t, r);
            return
        }
        typeof e[i] == "object" && !("enabled"in e[i]) && (e[i].enabled = !0),
        e[i] || (e[i] = {
            enabled: !1
        }),
        Bt(t, r)
    }
}
const Bu = {
    eventsEmitter: qM,
    update: iO,
    translate: uO,
    transition: mO,
    slide: bO,
    loop: EO,
    grabCursor: jO,
    events: VO,
    breakpoints: UO,
    checkOverflow: KO,
    classes: qO
}
  , Uu = {};
let fm = class zn {
    constructor() {
        let t, n;
        for (var r = arguments.length, i = new Array(r), s = 0; s < r; s++)
            i[s] = arguments[s];
        i.length === 1 && i[0].constructor && Object.prototype.toString.call(i[0]).slice(8, -1) === "Object" ? n = i[0] : [t,n] = i,
        n || (n = {}),
        n = Bt({}, n),
        t && !n.el && (n.el = t);
        const o = Fr();
        if (n.el && typeof n.el == "string" && o.querySelectorAll(n.el).length > 1) {
            const d = [];
            return o.querySelectorAll(n.el).forEach(f => {
                const p = Bt({}, n, {
                    el: f
                });
                d.push(new zn(p))
            }
            ),
            d
        }
        const l = this;
        l.__swiper__ = !0,
        l.support = Sx(),
        l.device = Cx({
            userAgent: n.userAgent
        }),
        l.browser = HM(),
        l.eventsListeners = {},
        l.eventsAnyListeners = [],
        l.modules = [...l.__modules__],
        n.modules && Array.isArray(n.modules) && l.modules.push(...n.modules);
        const a = {};
        l.modules.forEach(d => {
            d({
                params: n,
                swiper: l,
                extendParams: XO(n, a),
                on: l.on.bind(l),
                once: l.once.bind(l),
                off: l.off.bind(l),
                emit: l.emit.bind(l)
            })
        }
        );
        const c = Bt({}, _f, a);
        return l.params = Bt({}, c, Uu, n),
        l.originalParams = Bt({}, l.params),
        l.passedParams = Bt({}, n),
        l.params && l.params.on && Object.keys(l.params.on).forEach(d => {
            l.on(d, l.params.on[d])
        }
        ),
        l.params && l.params.onAny && l.onAny(l.params.onAny),
        Object.assign(l, {
            enabled: l.params.enabled,
            el: t,
            classNames: [],
            slides: [],
            slidesGrid: [],
            snapGrid: [],
            slidesSizesGrid: [],
            isHorizontal() {
                return l.params.direction === "horizontal"
            },
            isVertical() {
                return l.params.direction === "vertical"
            },
            activeIndex: 0,
            realIndex: 0,
            isBeginning: !0,
            isEnd: !1,
            translate: 0,
            previousTranslate: 0,
            progress: 0,
            velocity: 0,
            animating: !1,
            cssOverflowAdjustment() {
                return Math.trunc(this.translate / 2 ** 23) * 2 ** 23
            },
            allowSlideNext: l.params.allowSlideNext,
            allowSlidePrev: l.params.allowSlidePrev,
            touchEventsData: {
                isTouched: void 0,
                isMoved: void 0,
                allowTouchCallbacks: void 0,
                touchStartTime: void 0,
                isScrolling: void 0,
                currentTranslate: void 0,
                startTranslate: void 0,
                allowThresholdMove: void 0,
                focusableElements: l.params.focusableElements,
                lastClickTime: 0,
                clickTimeout: void 0,
                velocities: [],
                allowMomentumBounce: void 0,
                startMoving: void 0,
                pointerId: null,
                touchId: null
            },
            allowClick: !0,
            allowTouchMove: l.params.allowTouchMove,
            touches: {
                startX: 0,
                startY: 0,
                currentX: 0,
                currentY: 0,
                diff: 0
            },
            imagesToLoad: [],
            imagesLoaded: 0
        }),
        l.emit("_swiper"),
        l.params.init && l.init(),
        l
    }
    getDirectionLabel(t) {
        return this.isHorizontal() ? t : {
            width: "height",
            "margin-top": "margin-left",
            "margin-bottom ": "margin-right",
            "margin-left": "margin-top",
            "margin-right": "margin-bottom",
            "padding-left": "padding-top",
            "padding-right": "padding-bottom",
            marginRight: "marginBottom"
        }[t]
    }
    getSlideIndex(t) {
        const {slidesEl: n, params: r} = this
          , i = In(n, `.${r.slideClass}, swiper-slide`)
          , s = hf(i[0]);
        return hf(t) - s
    }
    getSlideIndexByData(t) {
        return this.getSlideIndex(this.slides.filter(n => n.getAttribute("data-swiper-slide-index") * 1 === t)[0])
    }
    recalcSlides() {
        const t = this
          , {slidesEl: n, params: r} = t;
        t.slides = In(n, `.${r.slideClass}, swiper-slide`)
    }
    enable() {
        const t = this;
        t.enabled || (t.enabled = !0,
        t.params.grabCursor && t.setGrabCursor(),
        t.emit("enable"))
    }
    disable() {
        const t = this;
        t.enabled && (t.enabled = !1,
        t.params.grabCursor && t.unsetGrabCursor(),
        t.emit("disable"))
    }
    setProgress(t, n) {
        const r = this;
        t = Math.min(Math.max(t, 0), 1);
        const i = r.minTranslate()
          , o = (r.maxTranslate() - i) * t + i;
        r.translateTo(o, typeof n > "u" ? 0 : n),
        r.updateActiveIndex(),
        r.updateSlidesClasses()
    }
    emitContainerClasses() {
        const t = this;
        if (!t.params._emitClasses || !t.el)
            return;
        const n = t.el.className.split(" ").filter(r => r.indexOf("swiper") === 0 || r.indexOf(t.params.containerModifierClass) === 0);
        t.emit("_containerClasses", n.join(" "))
    }
    getSlideClasses(t) {
        const n = this;
        return n.destroyed ? "" : t.className.split(" ").filter(r => r.indexOf("swiper-slide") === 0 || r.indexOf(n.params.slideClass) === 0).join(" ")
    }
    emitSlidesClasses() {
        const t = this;
        if (!t.params._emitClasses || !t.el)
            return;
        const n = [];
        t.slides.forEach(r => {
            const i = t.getSlideClasses(r);
            n.push({
                slideEl: r,
                classNames: i
            }),
            t.emit("_slideClass", r, i)
        }
        ),
        t.emit("_slideClasses", n)
    }
    slidesPerViewDynamic(t, n) {
        t === void 0 && (t = "current"),
        n === void 0 && (n = !1);
        const r = this
          , {params: i, slides: s, slidesGrid: o, slidesSizesGrid: l, size: a, activeIndex: c} = r;
        let d = 1;
        if (typeof i.slidesPerView == "number")
            return i.slidesPerView;
        if (i.centeredSlides) {
            let f = s[c] ? Math.ceil(s[c].swiperSlideSize) : 0, p;
            for (let h = c + 1; h < s.length; h += 1)
                s[h] && !p && (f += Math.ceil(s[h].swiperSlideSize),
                d += 1,
                f > a && (p = !0));
            for (let h = c - 1; h >= 0; h -= 1)
                s[h] && !p && (f += s[h].swiperSlideSize,
                d += 1,
                f > a && (p = !0))
        } else if (t === "current")
            for (let f = c + 1; f < s.length; f += 1)
                (n ? o[f] + l[f] - o[c] < a : o[f] - o[c] < a) && (d += 1);
        else
            for (let f = c - 1; f >= 0; f -= 1)
                o[c] - o[f] < a && (d += 1);
        return d
    }
    update() {
        const t = this;
        if (!t || t.destroyed)
            return;
        const {snapGrid: n, params: r} = t;
        r.breakpoints && t.setBreakpoint(),
        [...t.el.querySelectorAll('[loading="lazy"]')].forEach(o => {
            o.complete && dl(t, o)
        }
        ),
        t.updateSize(),
        t.updateSlides(),
        t.updateProgress(),
        t.updateSlidesClasses();
        function i() {
            const o = t.rtlTranslate ? t.translate * -1 : t.translate
              , l = Math.min(Math.max(o, t.maxTranslate()), t.minTranslate());
            t.setTranslate(l),
            t.updateActiveIndex(),
            t.updateSlidesClasses()
        }
        let s;
        if (r.freeMode && r.freeMode.enabled && !r.cssMode)
            i(),
            r.autoHeight && t.updateAutoHeight();
        else {
            if ((r.slidesPerView === "auto" || r.slidesPerView > 1) && t.isEnd && !r.centeredSlides) {
                const o = t.virtual && r.virtual.enabled ? t.virtual.slides : t.slides;
                s = t.slideTo(o.length - 1, 0, !1, !0)
            } else
                s = t.slideTo(t.activeIndex, 0, !1, !0);
            s || i()
        }
        r.watchOverflow && n !== t.snapGrid && t.checkOverflow(),
        t.emit("update")
    }
    changeDirection(t, n) {
        n === void 0 && (n = !0);
        const r = this
          , i = r.params.direction;
        return t || (t = i === "horizontal" ? "vertical" : "horizontal"),
        t === i || t !== "horizontal" && t !== "vertical" || (r.el.classList.remove(`${r.params.containerModifierClass}${i}`),
        r.el.classList.add(`${r.params.containerModifierClass}${t}`),
        r.emitContainerClasses(),
        r.params.direction = t,
        r.slides.forEach(s => {
            t === "vertical" ? s.style.width = "" : s.style.height = ""
        }
        ),
        r.emit("changeDirection"),
        n && r.update()),
        r
    }
    changeLanguageDirection(t) {
        const n = this;
        n.rtl && t === "rtl" || !n.rtl && t === "ltr" || (n.rtl = t === "rtl",
        n.rtlTranslate = n.params.direction === "horizontal" && n.rtl,
        n.rtl ? (n.el.classList.add(`${n.params.containerModifierClass}rtl`),
        n.el.dir = "rtl") : (n.el.classList.remove(`${n.params.containerModifierClass}rtl`),
        n.el.dir = "ltr"),
        n.update())
    }
    mount(t) {
        const n = this;
        if (n.mounted)
            return !0;
        let r = t || n.params.el;
        if (typeof r == "string" && (r = document.querySelector(r)),
        !r)
            return !1;
        r.swiper = n,
        r.parentNode && r.parentNode.host && r.parentNode.host.nodeName === n.params.swiperElementNodeName.toUpperCase() && (n.isElement = !0);
        const i = () => `.${(n.params.wrapperClass || "").trim().split(" ").join(".")}`;
        let o = ( () => r && r.shadowRoot && r.shadowRoot.querySelector ? r.shadowRoot.querySelector(i()) : In(r, i())[0])();
        return !o && n.params.createElements && (o = Uo("div", n.params.wrapperClass),
        r.append(o),
        In(r, `.${n.params.slideClass}`).forEach(l => {
            o.append(l)
        }
        )),
        Object.assign(n, {
            el: r,
            wrapperEl: o,
            slidesEl: n.isElement && !r.parentNode.host.slideSlots ? r.parentNode.host : o,
            hostEl: n.isElement ? r.parentNode.host : r,
            mounted: !0,
            rtl: r.dir.toLowerCase() === "rtl" || Sr(r, "direction") === "rtl",
            rtlTranslate: n.params.direction === "horizontal" && (r.dir.toLowerCase() === "rtl" || Sr(r, "direction") === "rtl"),
            wrongRTL: Sr(o, "display") === "-webkit-box"
        }),
        !0
    }
    init(t) {
        const n = this;
        if (n.initialized || n.mount(t) === !1)
            return n;
        n.emit("beforeInit"),
        n.params.breakpoints && n.setBreakpoint(),
        n.addClasses(),
        n.updateSize(),
        n.updateSlides(),
        n.params.watchOverflow && n.checkOverflow(),
        n.params.grabCursor && n.enabled && n.setGrabCursor(),
        n.params.loop && n.virtual && n.params.virtual.enabled ? n.slideTo(n.params.initialSlide + n.virtual.slidesBefore, 0, n.params.runCallbacksOnInit, !1, !0) : n.slideTo(n.params.initialSlide, 0, n.params.runCallbacksOnInit, !1, !0),
        n.params.loop && n.loopCreate(),
        n.attachEvents();
        const i = [...n.el.querySelectorAll('[loading="lazy"]')];
        return n.isElement && i.push(...n.hostEl.querySelectorAll('[loading="lazy"]')),
        i.forEach(s => {
            s.complete ? dl(n, s) : s.addEventListener("load", o => {
                dl(n, o.target)
            }
            )
        }
        ),
        gf(n),
        n.initialized = !0,
        gf(n),
        n.emit("init"),
        n.emit("afterInit"),
        n
    }
    destroy(t, n) {
        t === void 0 && (t = !0),
        n === void 0 && (n = !0);
        const r = this
          , {params: i, el: s, wrapperEl: o, slides: l} = r;
        return typeof r.params > "u" || r.destroyed || (r.emit("beforeDestroy"),
        r.initialized = !1,
        r.detachEvents(),
        i.loop && r.loopDestroy(),
        n && (r.removeClasses(),
        s && typeof s != "string" && s.removeAttribute("style"),
        o && o.removeAttribute("style"),
        l && l.length && l.forEach(a => {
            a.classList.remove(i.slideVisibleClass, i.slideFullyVisibleClass, i.slideActiveClass, i.slideNextClass, i.slidePrevClass),
            a.removeAttribute("style"),
            a.removeAttribute("data-swiper-slide-index")
        }
        )),
        r.emit("destroy"),
        Object.keys(r.eventsListeners).forEach(a => {
            r.off(a)
        }
        ),
        t !== !1 && (r.el && typeof r.el != "string" && (r.el.swiper = null),
        LM(r)),
        r.destroyed = !0),
        null
    }
    static extendDefaults(t) {
        Bt(Uu, t)
    }
    static get extendedDefaults() {
        return Uu
    }
    static get defaults() {
        return _f
    }
    static installModule(t) {
        zn.prototype.__modules__ || (zn.prototype.__modules__ = []);
        const n = zn.prototype.__modules__;
        typeof t == "function" && n.indexOf(t) < 0 && n.push(t)
    }
    static use(t) {
        return Array.isArray(t) ? (t.forEach(n => zn.installModule(n)),
        zn) : (zn.installModule(t),
        zn)
    }
}
;
Object.keys(Bu).forEach(e => {
    Object.keys(Bu[e]).forEach(t => {
        fm.prototype[t] = Bu[e][t]
    }
    )
}
);
fm.use([GM, WM]);
const Tx = ["eventsPrefix", "injectStyles", "injectStylesUrls", "modules", "init", "_direction", "oneWayMovement", "swiperElementNodeName", "touchEventsTarget", "initialSlide", "_speed", "cssMode", "updateOnWindowResize", "resizeObserver", "nested", "focusableElements", "_enabled", "_width", "_height", "preventInteractionOnTransition", "userAgent", "url", "_edgeSwipeDetection", "_edgeSwipeThreshold", "_freeMode", "_autoHeight", "setWrapperSize", "virtualTranslate", "_effect", "breakpoints", "breakpointsBase", "_spaceBetween", "_slidesPerView", "maxBackfaceHiddenSlides", "_grid", "_slidesPerGroup", "_slidesPerGroupSkip", "_slidesPerGroupAuto", "_centeredSlides", "_centeredSlidesBounds", "_slidesOffsetBefore", "_slidesOffsetAfter", "normalizeSlideIndex", "_centerInsufficientSlides", "_watchOverflow", "roundLengths", "touchRatio", "touchAngle", "simulateTouch", "_shortSwipes", "_longSwipes", "longSwipesRatio", "longSwipesMs", "_followFinger", "allowTouchMove", "_threshold", "touchMoveStopPropagation", "touchStartPreventDefault", "touchStartForcePreventDefault", "touchReleaseOnEdges", "uniqueNavElements", "_resistance", "_resistanceRatio", "_watchSlidesProgress", "_grabCursor", "preventClicks", "preventClicksPropagation", "_slideToClickedSlide", "_loop", "loopAdditionalSlides", "loopAddBlankSlides", "loopPreventsSliding", "_rewind", "_allowSlidePrev", "_allowSlideNext", "_swipeHandler", "_noSwiping", "noSwipingClass", "noSwipingSelector", "passiveListeners", "containerModifierClass", "slideClass", "slideActiveClass", "slideVisibleClass", "slideFullyVisibleClass", "slideNextClass", "slidePrevClass", "slideBlankClass", "wrapperClass", "lazyPreloaderClass", "lazyPreloadPrevNext", "runCallbacksOnInit", "observer", "observeParents", "observeSlideChildren", "a11y", "_autoplay", "_controller", "coverflowEffect", "cubeEffect", "fadeEffect", "flipEffect", "creativeEffect", "cardsEffect", "hashNavigation", "history", "keyboard", "mousewheel", "_navigation", "_pagination", "parallax", "_scrollbar", "_thumbs", "virtual", "zoom", "control"];
function vi(e) {
    return typeof e == "object" && e !== null && e.constructor && Object.prototype.toString.call(e).slice(8, -1) === "Object" && !e.__swiper__
}
function ss(e, t) {
    const n = ["__proto__", "constructor", "prototype"];
    Object.keys(t).filter(r => n.indexOf(r) < 0).forEach(r => {
        typeof e[r] > "u" ? e[r] = t[r] : vi(t[r]) && vi(e[r]) && Object.keys(t[r]).length > 0 ? t[r].__swiper__ ? e[r] = t[r] : ss(e[r], t[r]) : e[r] = t[r]
    }
    )
}
function Px(e) {
    return e === void 0 && (e = {}),
    e.navigation && typeof e.navigation.nextEl > "u" && typeof e.navigation.prevEl > "u"
}
function jx(e) {
    return e === void 0 && (e = {}),
    e.pagination && typeof e.pagination.el > "u"
}
function Nx(e) {
    return e === void 0 && (e = {}),
    e.scrollbar && typeof e.scrollbar.el > "u"
}
function Rx(e) {
    e === void 0 && (e = "");
    const t = e.split(" ").map(r => r.trim()).filter(r => !!r)
      , n = [];
    return t.forEach(r => {
        n.indexOf(r) < 0 && n.push(r)
    }
    ),
    n.join(" ")
}
function QO(e) {
    return e === void 0 && (e = ""),
    e ? e.includes("swiper-wrapper") ? e : `swiper-wrapper ${e}` : "swiper-wrapper"
}
function JO(e) {
    let {swiper: t, slides: n, passedParams: r, changedParams: i, nextEl: s, prevEl: o, scrollbarEl: l, paginationEl: a} = e;
    const c = i.filter(P => P !== "children" && P !== "direction" && P !== "wrapperClass")
      , {params: d, pagination: f, navigation: p, scrollbar: h, virtual: m, thumbs: _} = t;
    let x, v, w, y, b, C, S, T;
    i.includes("thumbs") && r.thumbs && r.thumbs.swiper && d.thumbs && !d.thumbs.swiper && (x = !0),
    i.includes("controller") && r.controller && r.controller.control && d.controller && !d.controller.control && (v = !0),
    i.includes("pagination") && r.pagination && (r.pagination.el || a) && (d.pagination || d.pagination === !1) && f && !f.el && (w = !0),
    i.includes("scrollbar") && r.scrollbar && (r.scrollbar.el || l) && (d.scrollbar || d.scrollbar === !1) && h && !h.el && (y = !0),
    i.includes("navigation") && r.navigation && (r.navigation.prevEl || o) && (r.navigation.nextEl || s) && (d.navigation || d.navigation === !1) && p && !p.prevEl && !p.nextEl && (b = !0);
    const k = P => {
        t[P] && (t[P].destroy(),
        P === "navigation" ? (t.isElement && (t[P].prevEl.remove(),
        t[P].nextEl.remove()),
        d[P].prevEl = void 0,
        d[P].nextEl = void 0,
        t[P].prevEl = void 0,
        t[P].nextEl = void 0) : (t.isElement && t[P].el.remove(),
        d[P].el = void 0,
        t[P].el = void 0))
    }
    ;
    i.includes("loop") && t.isElement && (d.loop && !r.loop ? C = !0 : !d.loop && r.loop ? S = !0 : T = !0),
    c.forEach(P => {
        if (vi(d[P]) && vi(r[P]))
            Object.assign(d[P], r[P]),
            (P === "navigation" || P === "pagination" || P === "scrollbar") && "enabled"in r[P] && !r[P].enabled && k(P);
        else {
            const R = r[P];
            (R === !0 || R === !1) && (P === "navigation" || P === "pagination" || P === "scrollbar") ? R === !1 && k(P) : d[P] = r[P]
        }
    }
    ),
    c.includes("controller") && !v && t.controller && t.controller.control && d.controller && d.controller.control && (t.controller.control = d.controller.control),
    i.includes("children") && n && m && d.virtual.enabled ? (m.slides = n,
    m.update(!0)) : i.includes("virtual") && m && d.virtual.enabled && (n && (m.slides = n),
    m.update(!0)),
    i.includes("children") && n && d.loop && (T = !0),
    x && _.init() && _.update(!0),
    v && (t.controller.control = d.controller.control),
    w && (t.isElement && (!a || typeof a == "string") && (a = document.createElement("div"),
    a.classList.add("swiper-pagination"),
    a.part.add("pagination"),
    t.el.appendChild(a)),
    a && (d.pagination.el = a),
    f.init(),
    f.render(),
    f.update()),
    y && (t.isElement && (!l || typeof l == "string") && (l = document.createElement("div"),
    l.classList.add("swiper-scrollbar"),
    l.part.add("scrollbar"),
    t.el.appendChild(l)),
    l && (d.scrollbar.el = l),
    h.init(),
    h.updateSize(),
    h.setTranslate()),
    b && (t.isElement && ((!s || typeof s == "string") && (s = document.createElement("div"),
    s.classList.add("swiper-button-next"),
    s.innerHTML = t.hostEl.constructor.nextButtonSvg,
    s.part.add("button-next"),
    t.el.appendChild(s)),
    (!o || typeof o == "string") && (o = document.createElement("div"),
    o.classList.add("swiper-button-prev"),
    o.innerHTML = t.hostEl.constructor.prevButtonSvg,
    o.part.add("button-prev"),
    t.el.appendChild(o))),
    s && (d.navigation.nextEl = s),
    o && (d.navigation.prevEl = o),
    p.init(),
    p.update()),
    i.includes("allowSlideNext") && (t.allowSlideNext = r.allowSlideNext),
    i.includes("allowSlidePrev") && (t.allowSlidePrev = r.allowSlidePrev),
    i.includes("direction") && t.changeDirection(r.direction, !1),
    (C || T) && t.loopDestroy(),
    (S || T) && t.loopCreate(),
    t.update()
}
function ZO(e, t) {
    e === void 0 && (e = {}),
    t === void 0 && (t = !0);
    const n = {
        on: {}
    }
      , r = {}
      , i = {};
    ss(n, _f),
    n._emitClasses = !0,
    n.init = !1;
    const s = {}
      , o = Tx.map(a => a.replace(/_/, ""))
      , l = Object.assign({}, e);
    return Object.keys(l).forEach(a => {
        typeof e[a] > "u" || (o.indexOf(a) >= 0 ? vi(e[a]) ? (n[a] = {},
        i[a] = {},
        ss(n[a], e[a]),
        ss(i[a], e[a])) : (n[a] = e[a],
        i[a] = e[a]) : a.search(/on[A-Z]/) === 0 && typeof e[a] == "function" ? t ? r[`${a[2].toLowerCase()}${a.substr(3)}`] = e[a] : n.on[`${a[2].toLowerCase()}${a.substr(3)}`] = e[a] : s[a] = e[a])
    }
    ),
    ["navigation", "pagination", "scrollbar"].forEach(a => {
        n[a] === !0 && (n[a] = {}),
        n[a] === !1 && delete n[a]
    }
    ),
    {
        params: n,
        passedParams: i,
        rest: s,
        events: r
    }
}
function e4(e, t) {
    let {el: n, nextEl: r, prevEl: i, paginationEl: s, scrollbarEl: o, swiper: l} = e;
    Px(t) && r && i && (l.params.navigation.nextEl = r,
    l.originalParams.navigation.nextEl = r,
    l.params.navigation.prevEl = i,
    l.originalParams.navigation.prevEl = i),
    jx(t) && s && (l.params.pagination.el = s,
    l.originalParams.pagination.el = s),
    Nx(t) && o && (l.params.scrollbar.el = o,
    l.originalParams.scrollbar.el = o),
    l.init(n)
}
function t4(e, t, n, r, i) {
    const s = [];
    if (!t)
        return s;
    const o = a => {
        s.indexOf(a) < 0 && s.push(a)
    }
    ;
    if (n && r) {
        const a = r.map(i)
          , c = n.map(i);
        a.join("") !== c.join("") && o("children"),
        r.length !== n.length && o("children")
    }
    return Tx.filter(a => a[0] === "_").map(a => a.replace(/_/, "")).forEach(a => {
        if (a in e && a in t)
            if (vi(e[a]) && vi(t[a])) {
                const c = Object.keys(e[a])
                  , d = Object.keys(t[a]);
                c.length !== d.length ? o(a) : (c.forEach(f => {
                    e[a][f] !== t[a][f] && o(a)
                }
                ),
                d.forEach(f => {
                    e[a][f] !== t[a][f] && o(a)
                }
                ))
            } else
                e[a] !== t[a] && o(a)
    }
    ),
    s
}
const n4 = e => {
    !e || e.destroyed || !e.params.virtual || e.params.virtual && !e.params.virtual.enabled || (e.updateSlides(),
    e.updateProgress(),
    e.updateSlidesClasses(),
    e.parallax && e.params.parallax && e.params.parallax.enabled && e.parallax.setTranslate())
}
;
function Ql() {
    return Ql = Object.assign ? Object.assign.bind() : function(e) {
        for (var t = 1; t < arguments.length; t++) {
            var n = arguments[t];
            for (var r in n)
                Object.prototype.hasOwnProperty.call(n, r) && (e[r] = n[r])
        }
        return e
    }
    ,
    Ql.apply(this, arguments)
}
function Ix(e) {
    return e.type && e.type.displayName && e.type.displayName.includes("SwiperSlide")
}
function Lx(e) {
    const t = [];
    return Ce.Children.toArray(e).forEach(n => {
        Ix(n) ? t.push(n) : n.props && n.props.children && Lx(n.props.children).forEach(r => t.push(r))
    }
    ),
    t
}
function r4(e) {
    const t = []
      , n = {
        "container-start": [],
        "container-end": [],
        "wrapper-start": [],
        "wrapper-end": []
    };
    return Ce.Children.toArray(e).forEach(r => {
        if (Ix(r))
            t.push(r);
        else if (r.props && r.props.slot && n[r.props.slot])
            n[r.props.slot].push(r);
        else if (r.props && r.props.children) {
            const i = Lx(r.props.children);
            i.length > 0 ? i.forEach(s => t.push(s)) : n["container-end"].push(r)
        } else
            n["container-end"].push(r)
    }
    ),
    {
        slides: t,
        slots: n
    }
}
function i4(e, t, n) {
    if (!n)
        return null;
    const r = d => {
        let f = d;
        return d < 0 ? f = t.length + d : f >= t.length && (f = f - t.length),
        f
    }
      , i = e.isHorizontal() ? {
        [e.rtlTranslate ? "right" : "left"]: `${n.offset}px`
    } : {
        top: `${n.offset}px`
    }
      , {from: s, to: o} = n
      , l = e.params.loop ? -t.length : 0
      , a = e.params.loop ? t.length * 2 : t.length
      , c = [];
    for (let d = l; d < a; d += 1)
        d >= s && d <= o && c.push(t[r(d)]);
    return c.map( (d, f) => Ce.cloneElement(d, {
        swiper: e,
        style: i,
        key: d.props.virtualIndex || d.key || `slide-${f}`
    }))
}
function ho(e, t) {
    return typeof window > "u" ? g.useEffect(e, t) : g.useLayoutEffect(e, t)
}
const N_ = g.createContext(null)
  , s4 = g.createContext(null)
  , Mx = g.forwardRef(function(e, t) {
    let {className: n, tag: r="div", wrapperTag: i="div", children: s, onSwiper: o, ...l} = e === void 0 ? {} : e
      , a = !1;
    const [c,d] = g.useState("swiper")
      , [f,p] = g.useState(null)
      , [h,m] = g.useState(!1)
      , _ = g.useRef(!1)
      , x = g.useRef(null)
      , v = g.useRef(null)
      , w = g.useRef(null)
      , y = g.useRef(null)
      , b = g.useRef(null)
      , C = g.useRef(null)
      , S = g.useRef(null)
      , T = g.useRef(null)
      , {params: k, passedParams: P, rest: R, events: A} = ZO(l)
      , {slides: V, slots: F} = r4(s)
      , U = () => {
        m(!h)
    }
    ;
    Object.assign(k.on, {
        _containerClasses($, O) {
            d(O)
        }
    });
    const Q = () => {
        Object.assign(k.on, A),
        a = !0;
        const $ = {
            ...k
        };
        if (delete $.wrapperClass,
        v.current = new fm($),
        v.current.virtual && v.current.params.virtual.enabled) {
            v.current.virtual.slides = V;
            const O = {
                cache: !1,
                slides: V,
                renderExternal: p,
                renderExternalUpdate: !1
            };
            ss(v.current.params.virtual, O),
            ss(v.current.originalParams.virtual, O)
        }
    }
    ;
    x.current || Q(),
    v.current && v.current.on("_beforeBreakpoint", U);
    const Z = () => {
        a || !A || !v.current || Object.keys(A).forEach($ => {
            v.current.on($, A[$])
        }
        )
    }
      , L = () => {
        !A || !v.current || Object.keys(A).forEach($ => {
            v.current.off($, A[$])
        }
        )
    }
    ;
    g.useEffect( () => () => {
        v.current && v.current.off("_beforeBreakpoint", U)
    }
    ),
    g.useEffect( () => {
        !_.current && v.current && (v.current.emitSlidesClasses(),
        _.current = !0)
    }
    ),
    ho( () => {
        if (t && (t.current = x.current),
        !!x.current)
            return v.current.destroyed && Q(),
            e4({
                el: x.current,
                nextEl: b.current,
                prevEl: C.current,
                paginationEl: S.current,
                scrollbarEl: T.current,
                swiper: v.current
            }, k),
            o && !v.current.destroyed && o(v.current),
            () => {
                v.current && !v.current.destroyed && v.current.destroy(!0, !1)
            }
    }
    , []),
    ho( () => {
        Z();
        const $ = t4(P, w.current, V, y.current, O => O.key);
        return w.current = P,
        y.current = V,
        $.length && v.current && !v.current.destroyed && JO({
            swiper: v.current,
            slides: V,
            passedParams: P,
            changedParams: $,
            nextEl: b.current,
            prevEl: C.current,
            scrollbarEl: T.current,
            paginationEl: S.current
        }),
        () => {
            L()
        }
    }
    ),
    ho( () => {
        n4(v.current)
    }
    , [f]);
    function N() {
        return k.virtual ? i4(v.current, V, f) : V.map( ($, O) => Ce.cloneElement($, {
            swiper: v.current,
            swiperSlideIndex: O
        }))
    }
    return Ce.createElement(r, Ql({
        ref: x,
        className: Rx(`${c}${n ? ` ${n}` : ""}`)
    }, R), Ce.createElement(s4.Provider, {
        value: v.current
    }, F["container-start"], Ce.createElement(i, {
        className: QO(k.wrapperClass)
    }, F["wrapper-start"], N(), F["wrapper-end"]), Px(k) && Ce.createElement(Ce.Fragment, null, Ce.createElement("div", {
        ref: C,
        className: "swiper-button-prev"
    }), Ce.createElement("div", {
        ref: b,
        className: "swiper-button-next"
    })), Nx(k) && Ce.createElement("div", {
        ref: T,
        className: "swiper-scrollbar"
    }), jx(k) && Ce.createElement("div", {
        ref: S,
        className: "swiper-pagination"
    }), F["container-end"]))
});
Mx.displayName = "Swiper";
const Ox = g.forwardRef(function(e, t) {
    let {tag: n="div", children: r, className: i="", swiper: s, zoom: o, lazy: l, virtualIndex: a, swiperSlideIndex: c, ...d} = e === void 0 ? {} : e;
    const f = g.useRef(null)
      , [p,h] = g.useState("swiper-slide")
      , [m,_] = g.useState(!1);
    function x(b, C, S) {
        C === f.current && h(S)
    }
    ho( () => {
        if (typeof c < "u" && (f.current.swiperSlideIndex = c),
        t && (t.current = f.current),
        !(!f.current || !s)) {
            if (s.destroyed) {
                p !== "swiper-slide" && h("swiper-slide");
                return
            }
            return s.on("_slideClass", x),
            () => {
                s && s.off("_slideClass", x)
            }
        }
    }
    ),
    ho( () => {
        s && f.current && !s.destroyed && h(s.getSlideClasses(f.current))
    }
    , [s]);
    const v = {
        isActive: p.indexOf("swiper-slide-active") >= 0,
        isVisible: p.indexOf("swiper-slide-visible") >= 0,
        isPrev: p.indexOf("swiper-slide-prev") >= 0,
        isNext: p.indexOf("swiper-slide-next") >= 0
    }
      , w = () => typeof r == "function" ? r(v) : r
      , y = () => {
        _(!0)
    }
    ;
    return Ce.createElement(n, Ql({
        ref: f,
        className: Rx(`${p}${i ? ` ${i}` : ""}`),
        "data-swiper-slide-index": a,
        onLoad: y
    }, d), o && Ce.createElement(N_.Provider, {
        value: v
    }, Ce.createElement("div", {
        className: "swiper-zoom-container",
        "data-swiper-zoom": typeof o == "number" ? o : void 0
    }, w(), l && !m && Ce.createElement("div", {
        className: "swiper-lazy-preloader"
    }))), !o && Ce.createElement(N_.Provider, {
        value: v
    }, w(), l && !m && Ce.createElement("div", {
        className: "swiper-lazy-preloader"
    })))
});
Ox.displayName = "SwiperSlide";
function o4(e, t, n, r) {
    return e.params.createElements && Object.keys(r).forEach(i => {
        if (!n[i] && n.auto === !0) {
            let s = In(e.el, `.${r[i]}`)[0];
            s || (s = Uo("div", r[i]),
            s.className = r[i],
            e.el.append(s)),
            n[i] = s,
            t[i] = s
        }
    }
    ),
    n
}
function a4(e) {
    let {swiper: t, extendParams: n, on: r, emit: i} = e;
    n({
        navigation: {
            nextEl: null,
            prevEl: null,
            hideOnClick: !1,
            disabledClass: "swiper-button-disabled",
            hiddenClass: "swiper-button-hidden",
            lockClass: "swiper-button-lock",
            navigationDisabledClass: "swiper-navigation-disabled"
        }
    }),
    t.navigation = {
        nextEl: null,
        prevEl: null
    };
    function s(m) {
        let _;
        return m && typeof m == "string" && t.isElement && (_ = t.el.querySelector(m),
        _) ? _ : (m && (typeof m == "string" && (_ = [...document.querySelectorAll(m)]),
        t.params.uniqueNavElements && typeof m == "string" && _ && _.length > 1 && t.el.querySelectorAll(m).length === 1 ? _ = t.el.querySelector(m) : _ && _.length === 1 && (_ = _[0])),
        m && !_ ? m : _)
    }
    function o(m, _) {
        const x = t.params.navigation;
        m = Ee(m),
        m.forEach(v => {
            v && (v.classList[_ ? "add" : "remove"](...x.disabledClass.split(" ")),
            v.tagName === "BUTTON" && (v.disabled = _),
            t.params.watchOverflow && t.enabled && v.classList[t.isLocked ? "add" : "remove"](x.lockClass))
        }
        )
    }
    function l() {
        const {nextEl: m, prevEl: _} = t.navigation;
        if (t.params.loop) {
            o(_, !1),
            o(m, !1);
            return
        }
        o(_, t.isBeginning && !t.params.rewind),
        o(m, t.isEnd && !t.params.rewind)
    }
    function a(m) {
        m.preventDefault(),
        !(t.isBeginning && !t.params.loop && !t.params.rewind) && (t.slidePrev(),
        i("navigationPrev"))
    }
    function c(m) {
        m.preventDefault(),
        !(t.isEnd && !t.params.loop && !t.params.rewind) && (t.slideNext(),
        i("navigationNext"))
    }
    function d() {
        const m = t.params.navigation;
        if (t.params.navigation = o4(t, t.originalParams.navigation, t.params.navigation, {
            nextEl: "swiper-button-next",
            prevEl: "swiper-button-prev"
        }),
        !(m.nextEl || m.prevEl))
            return;
        let _ = s(m.nextEl)
          , x = s(m.prevEl);
        Object.assign(t.navigation, {
            nextEl: _,
            prevEl: x
        }),
        _ = Ee(_),
        x = Ee(x);
        const v = (w, y) => {
            w && w.addEventListener("click", y === "next" ? c : a),
            !t.enabled && w && w.classList.add(...m.lockClass.split(" "))
        }
        ;
        _.forEach(w => v(w, "next")),
        x.forEach(w => v(w, "prev"))
    }
    function f() {
        let {nextEl: m, prevEl: _} = t.navigation;
        m = Ee(m),
        _ = Ee(_);
        const x = (v, w) => {
            v.removeEventListener("click", w === "next" ? c : a),
            v.classList.remove(...t.params.navigation.disabledClass.split(" "))
        }
        ;
        m.forEach(v => x(v, "next")),
        _.forEach(v => x(v, "prev"))
    }
    r("init", () => {
        t.params.navigation.enabled === !1 ? h() : (d(),
        l())
    }
    ),
    r("toEdge fromEdge lock unlock", () => {
        l()
    }
    ),
    r("destroy", () => {
        f()
    }
    ),
    r("enable disable", () => {
        let {nextEl: m, prevEl: _} = t.navigation;
        if (m = Ee(m),
        _ = Ee(_),
        t.enabled) {
            l();
            return
        }
        [...m, ..._].filter(x => !!x).forEach(x => x.classList.add(t.params.navigation.lockClass))
    }
    ),
    r("click", (m, _) => {
        let {nextEl: x, prevEl: v} = t.navigation;
        x = Ee(x),
        v = Ee(v);
        const w = _.target;
        let y = v.includes(w) || x.includes(w);
        if (t.isElement && !y) {
            const b = _.path || _.composedPath && _.composedPath();
            b && (y = b.find(C => x.includes(C) || v.includes(C)))
        }
        if (t.params.navigation.hideOnClick && !y) {
            if (t.pagination && t.params.pagination && t.params.pagination.clickable && (t.pagination.el === w || t.pagination.el.contains(w)))
                return;
            let b;
            x.length ? b = x[0].classList.contains(t.params.navigation.hiddenClass) : v.length && (b = v[0].classList.contains(t.params.navigation.hiddenClass)),
            i(b === !0 ? "navigationShow" : "navigationHide"),
            [...x, ...v].filter(C => !!C).forEach(C => C.classList.toggle(t.params.navigation.hiddenClass))
        }
    }
    );
    const p = () => {
        t.el.classList.remove(...t.params.navigation.navigationDisabledClass.split(" ")),
        d(),
        l()
    }
      , h = () => {
        t.el.classList.add(...t.params.navigation.navigationDisabledClass.split(" ")),
        f()
    }
    ;
    Object.assign(t.navigation, {
        enable: p,
        disable: h,
        update: l,
        init: d,
        destroy: f
    })
}
function Hu(e) {
    return e === void 0 && (e = ""),
    `.${e.trim().replace(/([\.:!+\/])/g, "\\$1").replace(/ /g, ".")}`
}
function l4(e) {
    let {swiper: t, extendParams: n, on: r} = e;
    n({
        a11y: {
            enabled: !0,
            notificationClass: "swiper-notification",
            prevSlideMessage: "Previous slide",
            nextSlideMessage: "Next slide",
            firstSlideMessage: "This is the first slide",
            lastSlideMessage: "This is the last slide",
            paginationBulletMessage: "Go to slide {{index}}",
            slideLabelMessage: "{{index}} / {{slidesLength}}",
            containerMessage: null,
            containerRoleDescriptionMessage: null,
            itemRoleDescriptionMessage: null,
            slideRole: "group",
            id: null
        }
    }),
    t.a11y = {
        clicked: !1
    };
    let i = null, s, o, l = new Date().getTime();
    function a(L) {
        const N = i;
        N.length !== 0 && (N.innerHTML = "",
        N.innerHTML = L)
    }
    function c(L) {
        L === void 0 && (L = 16);
        const N = () => Math.round(16 * Math.random()).toString(16);
        return "x".repeat(L).replace(/x/g, N)
    }
    function d(L) {
        L = Ee(L),
        L.forEach(N => {
            N.setAttribute("tabIndex", "0")
        }
        )
    }
    function f(L) {
        L = Ee(L),
        L.forEach(N => {
            N.setAttribute("tabIndex", "-1")
        }
        )
    }
    function p(L, N) {
        L = Ee(L),
        L.forEach($ => {
            $.setAttribute("role", N)
        }
        )
    }
    function h(L, N) {
        L = Ee(L),
        L.forEach($ => {
            $.setAttribute("aria-roledescription", N)
        }
        )
    }
    function m(L, N) {
        L = Ee(L),
        L.forEach($ => {
            $.setAttribute("aria-controls", N)
        }
        )
    }
    function _(L, N) {
        L = Ee(L),
        L.forEach($ => {
            $.setAttribute("aria-label", N)
        }
        )
    }
    function x(L, N) {
        L = Ee(L),
        L.forEach($ => {
            $.setAttribute("id", N)
        }
        )
    }
    function v(L, N) {
        L = Ee(L),
        L.forEach($ => {
            $.setAttribute("aria-live", N)
        }
        )
    }
    function w(L) {
        L = Ee(L),
        L.forEach(N => {
            N.setAttribute("aria-disabled", !0)
        }
        )
    }
    function y(L) {
        L = Ee(L),
        L.forEach(N => {
            N.setAttribute("aria-disabled", !1)
        }
        )
    }
    function b(L) {
        if (L.keyCode !== 13 && L.keyCode !== 32)
            return;
        const N = t.params.a11y
          , $ = L.target;
        if (!(t.pagination && t.pagination.el && ($ === t.pagination.el || t.pagination.el.contains(L.target)) && !L.target.matches(Hu(t.params.pagination.bulletClass)))) {
            if (t.navigation && t.navigation.prevEl && t.navigation.nextEl) {
                const O = Ee(t.navigation.prevEl);
                Ee(t.navigation.nextEl).includes($) && (t.isEnd && !t.params.loop || t.slideNext(),
                t.isEnd ? a(N.lastSlideMessage) : a(N.nextSlideMessage)),
                O.includes($) && (t.isBeginning && !t.params.loop || t.slidePrev(),
                t.isBeginning ? a(N.firstSlideMessage) : a(N.prevSlideMessage))
            }
            t.pagination && $.matches(Hu(t.params.pagination.bulletClass)) && $.click()
        }
    }
    function C() {
        if (t.params.loop || t.params.rewind || !t.navigation)
            return;
        const {nextEl: L, prevEl: N} = t.navigation;
        N && (t.isBeginning ? (w(N),
        f(N)) : (y(N),
        d(N))),
        L && (t.isEnd ? (w(L),
        f(L)) : (y(L),
        d(L)))
    }
    function S() {
        return t.pagination && t.pagination.bullets && t.pagination.bullets.length
    }
    function T() {
        return S() && t.params.pagination.clickable
    }
    function k() {
        const L = t.params.a11y;
        S() && t.pagination.bullets.forEach(N => {
            t.params.pagination.clickable && (d(N),
            t.params.pagination.renderBullet || (p(N, "button"),
            _(N, L.paginationBulletMessage.replace(/\{\{index\}\}/, hf(N) + 1)))),
            N.matches(Hu(t.params.pagination.bulletActiveClass)) ? N.setAttribute("aria-current", "true") : N.removeAttribute("aria-current")
        }
        )
    }
    const P = (L, N, $) => {
        d(L),
        L.tagName !== "BUTTON" && (p(L, "button"),
        L.addEventListener("keydown", b)),
        _(L, $),
        m(L, N)
    }
      , R = L => {
        o && o !== L.target && !o.contains(L.target) && (s = !0),
        t.a11y.clicked = !0
    }
      , A = () => {
        s = !1,
        requestAnimationFrame( () => {
            requestAnimationFrame( () => {
                t.destroyed || (t.a11y.clicked = !1)
            }
            )
        }
        )
    }
      , V = L => {
        l = new Date().getTime()
    }
      , F = L => {
        if (t.a11y.clicked || new Date().getTime() - l < 100)
            return;
        const N = L.target.closest(`.${t.params.slideClass}, swiper-slide`);
        if (!N || !t.slides.includes(N))
            return;
        o = N;
        const $ = t.slides.indexOf(N) === t.activeIndex
          , O = t.params.watchSlidesProgress && t.visibleSlides && t.visibleSlides.includes(N);
        $ || O || L.sourceCapabilities && L.sourceCapabilities.firesTouchEvents || (t.isHorizontal() ? t.el.scrollLeft = 0 : t.el.scrollTop = 0,
        requestAnimationFrame( () => {
            s || (t.params.loop ? t.slideToLoop(parseInt(N.getAttribute("data-swiper-slide-index")), 0) : t.slideTo(t.slides.indexOf(N), 0),
            s = !1)
        }
        ))
    }
      , U = () => {
        const L = t.params.a11y;
        L.itemRoleDescriptionMessage && h(t.slides, L.itemRoleDescriptionMessage),
        L.slideRole && p(t.slides, L.slideRole);
        const N = t.slides.length;
        L.slideLabelMessage && t.slides.forEach( ($, O) => {
            const B = t.params.loop ? parseInt($.getAttribute("data-swiper-slide-index"), 10) : O
              , H = L.slideLabelMessage.replace(/\{\{index\}\}/, B + 1).replace(/\{\{slidesLength\}\}/, N);
            _($, H)
        }
        )
    }
      , Q = () => {
        const L = t.params.a11y;
        t.el.append(i);
        const N = t.el;
        L.containerRoleDescriptionMessage && h(N, L.containerRoleDescriptionMessage),
        L.containerMessage && _(N, L.containerMessage);
        const $ = t.wrapperEl
          , O = L.id || $.getAttribute("id") || `swiper-wrapper-${c(16)}`
          , B = t.params.autoplay && t.params.autoplay.enabled ? "off" : "polite";
        x($, O),
        v($, B),
        U();
        let {nextEl: H, prevEl: ue} = t.navigation ? t.navigation : {};
        H = Ee(H),
        ue = Ee(ue),
        H && H.forEach(xe => P(xe, O, L.nextSlideMessage)),
        ue && ue.forEach(xe => P(xe, O, L.prevSlideMessage)),
        T() && Ee(t.pagination.el).forEach(ge => {
            ge.addEventListener("keydown", b)
        }
        ),
        Fr().addEventListener("visibilitychange", V),
        t.el.addEventListener("focus", F, !0),
        t.el.addEventListener("focus", F, !0),
        t.el.addEventListener("pointerdown", R, !0),
        t.el.addEventListener("pointerup", A, !0)
    }
    ;
    function Z() {
        i && i.remove();
        let {nextEl: L, prevEl: N} = t.navigation ? t.navigation : {};
        L = Ee(L),
        N = Ee(N),
        L && L.forEach(O => O.removeEventListener("keydown", b)),
        N && N.forEach(O => O.removeEventListener("keydown", b)),
        T() && Ee(t.pagination.el).forEach(B => {
            B.removeEventListener("keydown", b)
        }
        ),
        Fr().removeEventListener("visibilitychange", V),
        t.el && typeof t.el != "string" && (t.el.removeEventListener("focus", F, !0),
        t.el.removeEventListener("pointerdown", R, !0),
        t.el.removeEventListener("pointerup", A, !0))
    }
    r("beforeInit", () => {
        i = Uo("span", t.params.a11y.notificationClass),
        i.setAttribute("aria-live", "assertive"),
        i.setAttribute("aria-atomic", "true")
    }
    ),
    r("afterInit", () => {
        t.params.a11y.enabled && Q()
    }
    ),
    r("slidesLengthChange snapGridLengthChange slidesGridLengthChange", () => {
        t.params.a11y.enabled && U()
    }
    ),
    r("fromEdge toEdge afterInit lock unlock", () => {
        t.params.a11y.enabled && C()
    }
    ),
    r("paginationUpdate", () => {
        t.params.a11y.enabled && k()
    }
    ),
    r("destroy", () => {
        t.params.a11y.enabled && Z()
    }
    )
}
function c4(e) {
    const {effect: t, swiper: n, on: r, setTranslate: i, setTransition: s, overwriteParams: o, perspective: l, recreateShadows: a, getEffectParams: c} = e;
    r("beforeInit", () => {
        if (n.params.effect !== t)
            return;
        n.classNames.push(`${n.params.containerModifierClass}${t}`),
        l && l() && n.classNames.push(`${n.params.containerModifierClass}3d`);
        const f = o ? o() : {};
        Object.assign(n.params, f),
        Object.assign(n.originalParams, f)
    }
    ),
    r("setTranslate", () => {
        n.params.effect === t && i()
    }
    ),
    r("setTransition", (f, p) => {
        n.params.effect === t && s(p)
    }
    ),
    r("transitionEnd", () => {
        if (n.params.effect === t && a) {
            if (!c || !c().slideShadows)
                return;
            n.slides.forEach(f => {
                f.querySelectorAll(".swiper-slide-shadow-top, .swiper-slide-shadow-right, .swiper-slide-shadow-bottom, .swiper-slide-shadow-left").forEach(p => p.remove())
            }
            ),
            a()
        }
    }
    );
    let d;
    r("virtualUpdate", () => {
        n.params.effect === t && (n.slides.length || (d = !0),
        requestAnimationFrame( () => {
            d && n.slides && n.slides.length && (i(),
            d = !1)
        }
        ))
    }
    )
}
function u4(e, t) {
    const n = bx(t);
    return n !== t && (n.style.backfaceVisibility = "hidden",
    n.style["-webkit-backface-visibility"] = "hidden"),
    n
}
function d4(e) {
    let {swiper: t, duration: n, transformElements: r, allSlides: i} = e;
    const {activeIndex: s} = t
      , o = l => l.parentElement ? l.parentElement : t.slides.filter(c => c.shadowRoot && c.shadowRoot === l.parentNode)[0];
    if (t.params.virtualTranslate && n !== 0) {
        let l = !1, a;
        i ? a = r : a = r.filter(c => {
            const d = c.classList.contains("swiper-slide-transform") ? o(c) : c;
            return t.getSlideIndex(d) === s
        }
        ),
        a.forEach(c => {
            VM(c, () => {
                if (l || !t || t.destroyed)
                    return;
                l = !0,
                t.animating = !1;
                const d = new window.CustomEvent("transitionend",{
                    bubbles: !0,
                    cancelable: !0
                });
                t.wrapperEl.dispatchEvent(d)
            }
            )
        }
        )
    }
}
function f4(e) {
    let {swiper: t, extendParams: n, on: r} = e;
    n({
        fadeEffect: {
            crossFade: !1
        }
    }),
    c4({
        effect: "fade",
        swiper: t,
        on: r,
        setTranslate: () => {
            const {slides: o} = t
              , l = t.params.fadeEffect;
            for (let a = 0; a < o.length; a += 1) {
                const c = t.slides[a];
                let f = -c.swiperSlideOffset;
                t.params.virtualTranslate || (f -= t.translate);
                let p = 0;
                t.isHorizontal() || (p = f,
                f = 0);
                const h = t.params.fadeEffect.crossFade ? Math.max(1 - Math.abs(c.progress), 0) : 1 + Math.min(Math.max(c.progress, -1), 0)
                  , m = u4(l, c);
                m.style.opacity = h,
                m.style.transform = `translate3d(${f}px, ${p}px, 0px)`
            }
        }
        ,
        setTransition: o => {
            const l = t.slides.map(a => bx(a));
            l.forEach(a => {
                a.style.transitionDuration = `${o}ms`
            }
            ),
            d4({
                swiper: t,
                duration: o,
                transformElements: l,
                allSlides: !0
            })
        }
        ,
        overwriteParams: () => ({
            slidesPerView: 1,
            slidesPerGroup: 1,
            watchSlidesProgress: !0,
            spaceBetween: 0,
            virtualTranslate: !t.params.cssMode
        })
    })
}
const p4 = ({sectionRef: e}) => {
    const {setNowSection: t, swiperIndex: n, setSwiperIndex: r} = g.useContext(Cn)
      , i = g.useRef(null)
      , s = g.useRef(null)
      , o = Ie(i)
      , l = g.useMemo( () => [{
        id: 1,
        left: "",
        leftImg: "https://web-cdn.mironline.co.kr/mir2/2024_event/0411/images/chapter-1-left-img.png",
        right: "https://web-cdn.mironline.co.kr/mir2/2024_event/0411/images/chapter-1-right-text.png"
    }, {
        id: 2,
        left: "https://web-cdn.mironline.co.kr/mir2/2024_event/0411/images/chapter-2-left-text.png",
        leftImg: "https://web-cdn.mironline.co.kr/mir2/2024_event/0411/images/chapter-2-left-img.png",
        right: "https://web-cdn.mironline.co.kr/mir2/2024_event/0411/images/chapter-2-right-text.png"
    }, {
        id: 3,
        left: "https://web-cdn.mironline.co.kr/mir2/2024_event/0411/images/chapter-3-left-text.png",
        leftImg: "https://web-cdn.mironline.co.kr/mir2/2024_event/0411/images/chapter-3-left-img.png",
        right: "https://web-cdn.mironline.co.kr/mir2/2024_event/0411/images/chapter-3-right-text.png"
    }, {
        id: 4,
        left: "https://web-cdn.mironline.co.kr/mir2/2024_event/0411/images/chapter-4-left-text.png",
        leftImg: "https://web-cdn.mironline.co.kr/mir2/2024_event/0411/images/chapter-4-left-img.png",
        right: "https://web-cdn.mironline.co.kr/mir2/2024_event/0411/images/chapter-4-right-text.png"
    }, {
        id: 5,
        left: "https://web-cdn.mironline.co.kr/mir2/2024_event/0411/images/chapter-5-left-text.png",
        leftImg: "https://web-cdn.mironline.co.kr/mir2/2024_event/0411/images/chapter-5-left-img.png",
        right: "https://web-cdn.mironline.co.kr/mir2/2024_event/0411/images/chapter-5-right-text.png"
    }], [])
      , a = {
        1: "https://web-cdn.mironline.co.kr/mir2/2024_event/0411/images/red-1.png",
        2: "https://web-cdn.mironline.co.kr/mir2/2024_event/0411/images/red-2.png",
        3: "https://web-cdn.mironline.co.kr/mir2/2024_event/0411/images/red-3.png",
        4: "https://web-cdn.mironline.co.kr/mir2/2024_event/0411/images/red-4.png",
        5: "https://web-cdn.mironline.co.kr/mir2/2024_event/0411/images/red-5.png"
    };
    return g.useEffect( () => {
        o && t("story|story")
    }
    , [o]),
    g.useEffect( () => {
        setTimeout( () => {
            s.current && s.current.classList.add($t[`book_chapter_${n}`])
        }
        , 0)
    }
    , [n]),
    u.jsxs("section", {
        className: $t.container,
        ref: e,
        children: [u.jsxs("div", {
            ref: i,
            children: [u.jsxs("div", {
                className: $t.book_title_wrap,
                children: [u.jsx("img", {
                    src: "https://web-cdn.mironline.co.kr/mir2/2024_event/0313/images/story-header.png",
                    alt: "story header"
                }), u.jsx("img", {
                    src: "https://web-cdn.mironline.co.kr/mir2/2024_event/0411/images/story-content.png",
                    alt: "story content"
                })]
            }), u.jsxs("div", {
                className: $t.book,
                children: [u.jsx(Mx, {
                    modules: [f4, a4, l4],
                    navigation: {
                        nextEl: ".custom-next-button",
                        prevEl: ".custom-prev-button"
                    },
                    onSlideChange: c => r(c.realIndex + 1),
                    effect: "fade",
                    className: $t.book_swiper,
                    loop: !0,
                    allowTouchMove: !1,
                    children: l.map( ({id: c, left: d, leftImg: f, right: p}) => u.jsx(Ox, {
                        children: c === n && u.jsxs("div", {
                            className: $t.book_chapter,
                            ref: s,
                            children: [u.jsx("img", {
                                src: f,
                                alt: "left"
                            }), d && u.jsx("img", {
                                src: d,
                                alt: "left"
                            }), u.jsx("img", {
                                src: p,
                                alt: "right"
                            })]
                        })
                    }, c))
                }), u.jsxs("div", {
                    className: $t.book_swiper_index,
                    children: [u.jsx("img", {
                        src: a[n],
                        alt: "page"
                    }), u.jsx("img", {
                        src: "https://web-cdn.mironline.co.kr/mir2/2024_event/0313/images/slash.png",
                        alt: "slash"
                    }), u.jsx("img", {
                        src: "https://web-cdn.mironline.co.kr/mir2/2024_event/0411/images/black-5.png",
                        alt: "black"
                    })]
                }), u.jsxs("div", {
                    className: $t.book_swiper_button_wrap,
                    children: [u.jsx("button", {
                        type: "button",
                        className: `${$t.book_custom_prev_button} ${n === 1 ? $t.book_custom_prev_button_off : null} custom-prev-button`,
                        children: u.jsx("img", {
                            src: "https://web-cdn.mironline.co.kr/mir2/2024_event/0313/images/book-btn-prev.png",
                            alt: "prev"
                        })
                    }), u.jsx("button", {
                        type: "button",
                        className: `${$t.book_custom_next_button} custom-next-button`,
                        children: u.jsx("img", {
                            src: "https://web-cdn.mironline.co.kr/mir2/2024_event/0313/images/book-btn-next.png",
                            alt: "next"
                        })
                    })]
                }), u.jsx("div", {
                    className: $t.pen_wrap,
                    children: u.jsx("img", {
                        src: "https://web-cdn.mironline.co.kr/mir2/2024_event/0313/images/pen.png",
                        alt: "pen"
                    })
                })]
            })]
        }), u.jsxs("div", {
            className: $t.stone_wrap,
            children: [u.jsx("img", {
                src: "https://web-cdn.mironline.co.kr/mir2/2024_event/0313/images/stone-1.png",
                alt: "stone"
            }), u.jsx("img", {
                src: "https://web-cdn.mironline.co.kr/mir2/2024_event/0313/images/stone-2.png",
                alt: "stone"
            }), u.jsx("img", {
                src: "https://web-cdn.mironline.co.kr/mir2/2024_event/0313/images/stone-3.png",
                alt: "stone"
            })]
        })]
    })
}
  , m4 = "_scroll_down_11xtk_238"
  , h4 = "_scroll_down_is_main_11xtk_257"
  , g4 = "_fadeIn_11xtk_1"
  , R_ = {
    "a11y-hidden": "_a11y-hidden_11xtk_192",
    scroll_down: m4,
    "scroll-down": "_scroll-down_11xtk_1",
    scroll_down_is_main: h4,
    fadeIn: g4
}
  , _4 = ({isMain: e}) => {
    const {setNavClickSection: t} = g.useContext(Cn);
    return u.jsx("button", {
        className: `${R_.scroll_down} ${e ? R_.scroll_down_is_main : void 0}`,
        onClick: () => t("story"),
        children: u.jsx("img", {
            src: "https://web-cdn.mironline.co.kr/mir2/2024_event/0313/images/scroll-down.png",
            alt: "scroll_donw"
        })
    })
}
  , v4 = "_container_126az_1"
  , y4 = "_title_wrap_126az_12"
  , w4 = "_login_on_wrap_126az_19"
  , x4 = "_character_wrap_126az_29"
  , b4 = "_character_wrap_with_button_126az_53"
  , S4 = "_character_change_wrap_126az_56"
  , C4 = "_character_server_126az_67"
  , k4 = "_character_user_126az_85"
  , E4 = "_character_change_button_126az_92"
  , T4 = "_battle_warn_126az_104"
  , P4 = "_login_user_point_wrap_126az_110"
  , j4 = "_login_user_point_info_wrap_126az_123"
  , N4 = "_login_user_point_126az_110"
  , R4 = "_point_warn_126az_152"
  , I4 = "_login_off_wrap_126az_158"
  , L4 = "_login_title_126az_167"
  , M4 = "_login_user_id_126az_178"
  , O4 = "_login_user_pw_126az_179"
  , A4 = "_login_submit_126az_207"
  , D4 = "_percentage_button_wrap_126az_215"
  , $4 = "_percentage_button_126az_215"
  , F4 = "_minigame_container_126az_237"
  , V4 = "_minigame_position_wrap_126az_243"
  , z4 = "_minigame_position_game_part_126az_248"
  , B4 = "_minigame_title_126az_253"
  , U4 = "_minigame_card_wrap_126az_259"
  , H4 = "_minigame_card_video_126az_265"
  , G4 = "_fade_126az_1"
  , W4 = "_minigame_card_effect_126az_276"
  , q4 = "_minigame_battle_phase_wrap_126az_281"
  , Y4 = "_minigame_battle_phase_bg_126az_286"
  , K4 = "_minigame_battle_phase_number_126az_306"
  , X4 = "_minigame_battle_phase_step_126az_307"
  , Q4 = "_minigame_battle_phase_number_clear_126az_316"
  , J4 = "_minigame_battle_percentage_info_126az_322"
  , Z4 = "_minigame_battle_buff_wrap_126az_330"
  , eA = "_minigame_battle_buff_setting_wrap_126az_338"
  , tA = "_minigame_battle_buff_toggle_button_126az_342"
  , nA = "_minigame_battle_buff_toggle_126az_342"
  , rA = "_battle_button_wrap_126az_359"
  , iA = "_minigame_position_rank_part_126az_372"
  , sA = "_minigame_update_time_126az_377"
  , oA = "_minigame_top_rank_126az_386"
  , aA = "_minigame_top_rank_position_wrap_126az_395"
  , lA = "_minigame_top_rank_wrap_126az_401"
  , cA = "_minigame_top_rank_alone_wrap_126az_434"
  , uA = "_minigame_rank_table_wrap_126az_447"
  , dA = "_minigame_rank_table_position_wrap_126az_456"
  , fA = "_minigame_rank_table_126az_447"
  , pA = "_minigame_rank_126az_447"
  , mA = "_minigame_my_rank_wrap_126az_499"
  , hA = "_minigame_my_rank_position_wrap_126az_507"
  , gA = "_minigame_my_rank_search_126az_512"
  , _A = "_minigame_my_rank_info_126az_524"
  , vA = "_minigame_my_rank_126az_499"
  , yA = "_minigame_my_name_126az_550"
  , wA = "_minigame_my_step_126az_565"
  , xA = "_minigame_my_time_126az_581"
  , bA = "_minigame_have_no_rank_126az_586"
  , SA = "_minigame_sub_title_126az_598"
  , CA = "_minigame_content_3_126az_603"
  , X = {
    container: v4,
    title_wrap: y4,
    login_on_wrap: w4,
    character_wrap: x4,
    character_wrap_with_button: b4,
    character_change_wrap: S4,
    character_server: C4,
    character_user: k4,
    character_change_button: E4,
    battle_warn: T4,
    login_user_point_wrap: P4,
    login_user_point_info_wrap: j4,
    login_user_point: N4,
    point_warn: R4,
    login_off_wrap: I4,
    login_title: L4,
    login_user_id: M4,
    login_user_pw: O4,
    login_submit: A4,
    percentage_button_wrap: D4,
    percentage_button: $4,
    minigame_container: F4,
    minigame_position_wrap: V4,
    minigame_position_game_part: z4,
    minigame_title: B4,
    minigame_card_wrap: U4,
    minigame_card_video: H4,
    fade: G4,
    minigame_card_effect: W4,
    minigame_battle_phase_wrap: q4,
    minigame_battle_phase_bg: Y4,
    minigame_battle_phase_number: K4,
    minigame_battle_phase_step: X4,
    minigame_battle_phase_number_clear: Q4,
    minigame_battle_percentage_info: J4,
    minigame_battle_buff_wrap: Z4,
    minigame_battle_buff_setting_wrap: eA,
    minigame_battle_buff_toggle_button: tA,
    minigame_battle_buff_toggle: nA,
    battle_button_wrap: rA,
    minigame_position_rank_part: iA,
    minigame_update_time: sA,
    minigame_top_rank: oA,
    minigame_top_rank_position_wrap: aA,
    minigame_top_rank_wrap: lA,
    minigame_top_rank_alone_wrap: cA,
    minigame_rank_table_wrap: uA,
    minigame_rank_table_position_wrap: dA,
    minigame_rank_table: fA,
    minigame_rank: pA,
    minigame_my_rank_wrap: mA,
    minigame_my_rank_position_wrap: hA,
    minigame_my_rank_search: gA,
    minigame_my_rank_info: _A,
    minigame_my_rank: vA,
    minigame_my_name: yA,
    minigame_my_step: wA,
    minigame_my_time: xA,
    minigame_have_no_rank: bA,
    minigame_sub_title: SA,
    minigame_content_3: CA
}
  , kA = "_container_tnw55_1"
  , EA = "_percentage_wrap_tnw55_14"
  , TA = "_percentage_close_button_tnw55_20"
  , PA = "_confirm_button_tnw55_25"
  , Ba = {
    container: kA,
    percentage_wrap: EA,
    percentage_close_button: TA,
    confirm_button: PA
}
  , jA = ({setIsPercentageModalOpen: e}) => u.jsx("div", {
    className: Ba.container,
    children: u.jsxs("div", {
        className: Ba.percentage_wrap,
        children: [u.jsx("button", {
            type: "button",
            className: Ba.percentage_close_button,
            onClick: () => e(!1),
            children: u.jsx("img", {
                src: "https://web-cdn.mironline.co.kr/mir2/2024_event/0411/images/close_button.png",
                alt: "close button"
            })
        }), u.jsx("button", {
            type: "button",
            className: Ba.confirm_button,
            onClick: () => e(!1),
            children: u.jsx("img", {
                src: "https://web-cdn.mironline.co.kr/mir2/2024_event/0411/images/minigame_confirm_btn.png",
                alt: "button"
            })
        })]
    })
});
function NA(e) {
    return Ke.post("/event/indeon/user/info", e)
}
function I_(e) {
    return Ke.post("/event/indeon/totaluser/info", e)
}
function RA(e) {
    return Ke.post("/event/indeon/levelup", e)
}
function L_(e) {
    const t = e;
    let n = "";
    const r = t.length;
    if (e == null || e === "")
        return "";
    const i = /.{1}$/
      , s = /.{2}$/;
    return r < 4 ? n = t.replace(i, "*") : n = t.replace(s, "**"),
    n
}
const IA = "_modal_bg_ikq5n_2"
  , LA = "_modal_close_button_ikq5n_10"
  , MA = "_item_list_table_ikq5n_15"
  , OA = "_pagination_wrap_ikq5n_37"
  , AA = "_pagination_ikq5n_37"
  , DA = "_on_ikq5n_60"
  , Yr = {
    modal_bg: IA,
    modal_close_button: LA,
    item_list_table: MA,
    pagination_wrap: OA,
    pagination: AA,
    on: DA
}
  , Ax = ({setIsPointModalOpen: e}) => {
    const {pointLog: t, setPointLog: n} = g.useContext(aa)
      , [r,i] = g.useState(1)
      , s = Ei("MIR_Info")
      , o = 10
      , l = t.pointLog.totalCount
      , a = Math.ceil(l / o)
      , c = f => {
        const {id: p, value: h} = f.currentTarget;
        switch (p) {
        case "first":
            i(1);
            break;
        case "last":
            i(a);
            break;
        case "prev":
            r > 1 && i(r - 1);
            break;
        case "next":
            r < a && i(r + 1);
            break;
        case "button":
            i(Number(h))
        }
    }
      , d = () => {
        const f = []
          , h = (Math.ceil(r / o) - 1) * o + 1
          , m = Math.min(h + 9, a);
        for (let _ = h; _ <= m; _++)
            f.push(u.jsx("button", {
                type: "button",
                id: "button",
                value: _,
                onClick: c,
                className: Yr.pagination_button,
                children: u.jsx("strong", {
                    className: r === _ ? Yr.on : void 0,
                    children: _
                })
            }, _));
        return f
    }
    ;
    return g.useEffect( () => {
        (async () => {
            const p = await lL({
                gameCode: 2,
                auth: {
                    cusrVal: s
                },
                size: o,
                page: r
            });
            p.code === "200" && n(p)
        }
        )()
    }
    , [s, r]),
    u.jsx(Cs, {
        children: u.jsxs("div", {
            className: Yr.modal_bg,
            children: [u.jsx("button", {
                type: "button",
                onClick: () => e(!1),
                className: Yr.modal_close_button,
                children: u.jsx("img", {
                    src: "https://web-cdn.mironline.co.kr/mir2/2024_event/0411/images/close_button.png",
                    alt: "close"
                })
            }), u.jsxs("table", {
                cellSpacing: "0",
                cellPadding: "0",
                className: Yr.item_list_table,
                children: [u.jsxs("colgroup", {
                    children: [u.jsx("col", {
                        style: {
                            width: "140px"
                        }
                    }), u.jsx("col", {
                        style: {
                            width: "170px"
                        }
                    }), u.jsx("col", {
                        style: {
                            width: "75px"
                        }
                    }), u.jsx("col", {
                        style: {
                            width: "155px"
                        }
                    })]
                }), u.jsx("thead", {
                    children: u.jsxs("tr", {
                        children: [u.jsx("th", {
                            children: "날짜"
                        }), u.jsx("th", {
                            children: "구분"
                        }), u.jsx("th", {
                            children: "포인트"
                        }), u.jsx("th", {
                            children: "내역"
                        })]
                    })
                }), u.jsx("tbody", {
                    children: t.pointLog.list.map( ({regDate: f, addInfo: p, point: h, pointContent: m}, _) => u.jsxs("tr", {
                        children: [u.jsx("td", {
                            children: f
                        }), u.jsx("td", {
                            children: p.split("|")[0] || m
                        }), u.jsx("td", {
                            children: h
                        }), u.jsx("td", {
                            children: p.includes("전투") ? p.split("|")[1] + "단계 전투" : p || m
                        })]
                    }, _))
                })]
            }), u.jsx("div", {
                className: Yr.pagination_wrap,
                children: u.jsxs("div", {
                    className: Yr.pagination,
                    children: [u.jsx("button", {
                        type: "button",
                        id: "first",
                        onClick: c,
                        children: u.jsx("img", {
                            src: "//web-cdn.mironline.co.kr/mir2/2013_grand/contents/ic_first.gif",
                            alt: "처음으로"
                        })
                    }), u.jsx("button", {
                        type: "button",
                        id: "prev",
                        onClick: c,
                        children: u.jsx("img", {
                            src: "//web-cdn.mironline.co.kr/mir2/2013_grand/contents/ic_prev.gif",
                            alt: "이전페이지"
                        })
                    }), t.pointLog.list.length > 0 && d(), u.jsx("button", {
                        type: "button",
                        id: "next",
                        onClick: c,
                        children: u.jsx("img", {
                            src: "//web-cdn.mironline.co.kr/mir2/2013_grand/contents/ic_next.gif",
                            alt: "다음페이지"
                        })
                    }), u.jsx("button", {
                        type: "button",
                        id: "last",
                        onClick: c,
                        children: u.jsx("img", {
                            src: "//web-cdn.mironline.co.kr/mir2/2013_grand/contents/ic_last.gif",
                            alt: "마지막으로"
                        })
                    })]
                })
            })]
        })
    })
}
  , $A = "_modal_bg_win_mp755_1"
  , FA = "_modal_close_button_mp755_7"
  , VA = "_modal_bg_lose_mp755_13"
  , zA = "_modal_btn_mp755_25"
  , Ua = {
    modal_bg_win: $A,
    modal_close_button: FA,
    modal_bg_lose: VA,
    modal_btn: zA
}
  , BA = ({setIsResultModalOpen: e, setIsBattleClick: t}) => {
    const {drawResultYn: n} = g.useContext(Cn)
      , r = () => {
        e(!1),
        t(!1)
    }
    ;
    return u.jsx(Cs, {
        children: u.jsxs("div", {
            className: `${n === "Y" ? Ua.modal_bg_win : Ua.modal_bg_lose}`,
            children: [u.jsx("button", {
                type: "button",
                className: Ua.modal_close_button,
                onClick: r,
                children: u.jsx("img", {
                    src: "https://web-cdn.mironline.co.kr/mir2/2024_event/0411/images/close_button.png",
                    alt: "close button"
                })
            }), u.jsx("button", {
                className: Ua.modal_btn,
                type: "button",
                onClick: r,
                children: u.jsx("img", {
                    src: "https://web-cdn.mironline.co.kr/mir2/2024_event/0411/images/minigame_confirm_btn.png",
                    alt: "confirm"
                })
            })]
        })
    })
}
  , UA = "_buff_button_crfbe_1"
  , HA = "_buff_setting_icon_crfbe_11"
  , GA = "_buff_off_crfbe_46"
  , M_ = {
    buff_button: UA,
    buff_setting_icon: HA,
    buff_off: GA
}
  , WA = ({checked: e, onChange: t, disabled: n}) => u.jsx("fieldset", {
    className: M_.buff_button,
    children: u.jsx("input", {
        id: "buff",
        name: "buff",
        role: "switch",
        type: "checkbox",
        className: `${e ? void 0 : M_.buff_off}`,
        checked: e,
        onChange: t,
        disabled: n
    })
})
  , qA = (e, t, n) => {
    let r = e[t];
    return e[t] = e[n],
    e[n] = r,
    e
}
;
function O_() {
    const e = new Date
      , t = e.getFullYear()
      , n = (e.getMonth() + 1).toString().padStart(2, "0")
      , r = e.getDate().toString().padStart(2, "0")
      , i = e.getHours().toString().padStart(2, "0")
      , s = e.getMinutes().toString().padStart(2, "0");
    return `${t}.${n}.${r} ${i}:${s}`
}
const YA = ({sectionRef: e}) => {
    um();
    const t = 2
      , n = O_()
      , r = Ei("MIR_Info")
      , i = g.useRef(null)
      , s = Ie(i)
      , {userInfo: o, setUserInfo: l, userPoint: a, setUserPoint: c, indeonUserInfo: d, setIndeonUserInfo: f, isFunctionOpen: p} = g.useContext(Ti)
      , {banner: h, setBanner: m, setBannerBuff: _} = g.useContext(aa)
      , {gameRank: x, setGameRank: v, setDrawResultYn: w, setNowSection: y} = g.useContext(Cn)
      , [b,C] = g.useState([])
      , [S,T] = g.useState(!1)
      , [k,P] = g.useState(!1)
      , [R,A] = g.useState(!1)
      , [V,F] = g.useState(!1)
      , [U,Q] = g.useState(!1)
      , [Z,L] = g.useState(!1)
      , [N,$] = g.useState(!1)
      , [O,B] = g.useState(!1)
      , [H,ue] = g.useState({
        userId: "",
        pw: ""
    })
      , [de,xe] = g.useState("")
      , ge = {
        1: u.jsx("img", {
            src: "https://web-cdn.mironline.co.kr/mir2/2024_event/0411/images/1.png",
            alt: "1"
        }),
        2: u.jsx("img", {
            src: "https://web-cdn.mironline.co.kr/mir2/2024_event/0411/images/2.png",
            alt: "2"
        }),
        3: u.jsx("img", {
            src: "https://web-cdn.mironline.co.kr/mir2/2024_event/0411/images/3.png",
            alt: "3"
        })
    }
      , wt = ["card_title_0.png", "card_title_1.png", "card_title_2or5.png", "card_title_3or7.png", "card_title_4or8.png", "card_title_6.png", "card_title_9.png", "card_title_10.png", "card_title_11.png", "card_title_12.png"]
      , un = [{
        id: "0",
        video: "card_0.webm"
    }, {
        id: "1",
        video: "card_1.webm"
    }, {
        id: "2",
        video: "card_2.webm"
    }, {
        id: "3",
        video: "card_3.webm"
    }, {
        id: "4",
        video: "card_4.webm"
    }, {
        id: "5",
        video: "card_5.webm"
    }, {
        id: "6",
        video: "card_6.webm"
    }, {
        id: "7",
        video: "card_7.webm"
    }, {
        id: "8",
        video: "card_8.webm"
    }, {
        id: "9",
        video: "card_9.webm"
    }, {
        id: "10",
        video: "card_10.webm"
    }, {
        id: "11",
        video: "card_11.webm"
    }, {
        id: "12",
        video: "card_12.webm"
    }]
      , Mt = async () => {
        const ae = await NA({
            gameCode: t,
            eventCode: 20240417,
            auth: {
                cusrVal: r
            }
        });
        f(ae),
        O_()
    }
      , ot = async () => {
        const ae = {
            gameCode: t,
            auth: {
                cusrVal: r
            }
        }
          , {code: Me, point: {totalPoint: q}} = await mo(ae);
        Me === "200" && c(q)
    }
      , ft = async () => {
        const ae = await I_({
            gameCode: t,
            eventCode: 20240417,
            rank: 10
        });
        v(ae)
    }
      , Be = ae => {
        const {name: Me, value: q} = ae.target;
        ue(re => ({
            ...re,
            [Me]: q
        }))
    }
      , Ot = async ae => {
        if (ae.preventDefault(),
        !H.userId) {
            alert("아이디를 입력해 주세요.");
            return
        }
        if (!H.pw) {
            alert("비밀번호를 입력해 주세요.");
            return
        }
        if (!H.userId || !H.pw) {
            alert("아이디와 비밀번호를 입력해주세요.");
            return
        }
        const Me = {
            auth: {
                gameCode: t,
                userId: H.userId,
                pw: H.pw
            }
        }
          , {code: q, errorMessage: re, cusrVal: Oe, memCharacter: Ue, memCharacterYn: pt, memServer: kn, memServerCode: Dn, nickName: Qt, nickYn: At} = await am(Me);
        switch (q) {
        case "200":
            l({
                errorMessage: re,
                cusrVal: Oe,
                memCharacter: Ue,
                memCharacterYn: pt,
                memServer: kn,
                memServerCode: Dn,
                nickName: Qt,
                nickYn: At
            }),
            he("MIR%5FInfo", Oe);
            break;
        default:
            alert(re);
            break
        }
    }
      , Gr = () => {
        r && (o.nickName ? T(!0) : P(!0))
    }
      , Pi = ms(async ae => {
        if (!r) {
            alert("로그인이 필요합니다."),
            B(!1);
            return
        }
        B(!0);
        const {id: Me} = ae.target
          , q = {
            gameCode: t,
            eventCode: 20240417,
            auth: {
                cusrVal: r
            },
            levelType: Me,
            pidx: h.productList.filter(re => re.productName === d.userInfo.nextGameLevel)[0].productIdx,
            npcUseYn: U ? "Y" : "N"
        };
        try {
            const re = await RA(q);
            switch (re.code) {
            case "200":
                $(!0),
                setTimeout( () => {
                    $(!1),
                    w(re.draw.drawResultYn),
                    L(!0),
                    f(Oe => ({
                        ...Oe,
                        userInfo: {
                            ...Oe.userInfo,
                            gameLevel: re.draw.gameLevel
                        }
                    })),
                    Promise.all([Mt(), ot(), ft()])
                }
                , 2500);
                break;
            case "M21306":
                alert(re.errorMessage),
                Q(!1);
                break;
            default:
                alert(re.errorMessage);
                break
            }
        } catch (re) {
            throw new Error("error" + re)
        }
        B(!1)
    }
    , 500)
      , dn = ae => {
        if (!r) {
            alert("로그인이 필요합니다.");
            return
        }
        O || (ae.target.checked && alert("버프 ON 설정 시, 전투단계 마다 자동으로 활성화 됩니다."),
        Q(ae.target.checked))
    }
      , ks = () => {
        r && F(!0)
    }
      , Es = async () => {
        await Mt(),
        await ft()
    }
    ;
    return g.useEffect( () => {
        r && (async () => {
            const ae = Oc({
                gameCode: t,
                auth: {
                    cusrVal: r
                }
            })
              , Me = mo({
                gameCode: t,
                auth: {
                    cusrVal: r
                }
            })
              , [q,re] = await Promise.all([ae, Me]);
            c(re.point.totalPoint),
            l(q)
        }
        )()
    }
    , [r]),
    g.useEffect( () => {
        (async () => {
            const ae = await Mr({
                gameCode: t,
                groupIdx: "74"
            })
              , Me = await Mr({
                gameCode: t,
                groupIdx: "75"
            })
              , q = await I_({
                gameCode: t,
                eventCode: 20240417,
                rank: 10
            })
              , [re,Oe,Ue] = await Promise.all([ae, Me, q]);
            m(re),
            _(Oe),
            v(Ue)
        }
        )()
    }
    , []),
    g.useEffect( () => {
        r && (async () => await Mt())()
    }
    , [r]),
    g.useEffect( () => {
        let ae = x.list.slice(0, 3);
        x.list.length === 1 && (ae = x.list),
        x.list.length >= 2 && (ae = qA(ae, 0, 1)),
        C(ae)
    }
    , [x]),
    g.useEffect( () => {
        ( () => {
            const ae = wt.find(Me => Me && d.userInfo && Me.includes(d.userInfo.gameLevel.replace("+", "")));
            xe(String(ae))
        }
        )()
    }
    , [d.userInfo]),
    g.useEffect( () => {
        s && y("battle")
    }
    , [s]),
    u.jsxs("section", {
        className: X.container,
        ref: e,
        children: [u.jsx("div", {
            className: X.title_wrap,
            ref: i,
            children: u.jsx("img", {
                src: "https://web-cdn.mironline.co.kr/mir2/2024_event/0411/images/minigame-title.png",
                alt: "title"
            })
        }), u.jsx("div", {
            children: u.jsx("img", {
                src: "https://web-cdn.mironline.co.kr/mir2/2024_event/0411/images/minigame-con-1.png",
                alt: "미니게임 콘텐츠 1"
            })
        }), u.jsx("div", {
            children: u.jsx("img", {
                src: "https://web-cdn.mironline.co.kr/mir2/2024_event/0411/images/minigame-con-2.png",
                alt: "미니게임 콘텐츠 2"
            })
        }), r ? u.jsxs("div", {
            className: X.login_on_wrap,
            children: [u.jsxs("div", {
                className: X.character_wrap,
                children: [u.jsx("h4", {
                    children: "대표 캐릭터"
                }), u.jsxs("div", {
                    className: X.character_wrap_with_button,
                    children: [u.jsxs("div", {
                        className: X.character_change_wrap,
                        children: [u.jsx("span", {
                            className: X.character_server,
                            children: o.memServer || "-"
                        }), u.jsx("span", {
                            className: X.character_user,
                            children: o.memCharacter || "-"
                        })]
                    }), u.jsx("button", {
                        className: X.character_change_button,
                        type: "button",
                        onClick: Gr,
                        disabled: p === "N",
                        children: "변경"
                    })]
                }), u.jsx("p", {
                    className: X.battle_warn,
                    children: "※ 전투단계 달성 및 순위 보상은 설정한 대표 캐릭터로 지급됩니다."
                })]
            }), u.jsxs("div", {
                className: X.login_user_point_wrap,
                children: [u.jsx("h4", {
                    children: "나의 전투 포인트"
                }), u.jsxs("div", {
                    className: X.login_user_point_info_wrap,
                    children: [u.jsx("p", {
                        className: X.login_user_point,
                        children: a.toLocaleString("ko-KR")
                    }), u.jsx("button", {
                        type: "button",
                        onClick: ks,
                        disabled: p === "N",
                        children: "이용내역"
                    })]
                }), u.jsx("p", {
                    className: X.point_warn,
                    children: "※ 포인트를 모아서 미니게임을 진행할 수 있습니다."
                })]
            })]
        }) : u.jsxs("form", {
            className: X.login_off_wrap,
            method: "POST",
            onSubmit: Ot,
            children: [u.jsx("h4", {
                className: X.login_title,
                children: "로그인"
            }), u.jsxs("div", {
                children: [u.jsx("input", {
                    className: X.login_user_id,
                    name: "userId",
                    type: "text",
                    placeholder: "아이디 입력",
                    onChange: Be,
                    autoComplete: "off",
                    value: H.userId
                }), u.jsx("input", {
                    className: X.login_user_pw,
                    name: "pw",
                    type: "password",
                    placeholder: "비밀번호 입력",
                    onChange: Be,
                    autoComplete: "off",
                    value: H.pw
                }), u.jsx("button", {
                    className: X.login_submit,
                    type: "submit",
                    children: "로그인"
                })]
            })]
        }), u.jsx("div", {
            className: X.minigame_container,
            children: u.jsxs("div", {
                className: X.minigame_position_wrap,
                children: [u.jsxs("div", {
                    className: X.minigame_position_game_part,
                    children: [u.jsx("div", {
                        className: X.minigame_title,
                        children: de === void 0 ? u.jsx("img", {
                            src: `https://web-cdn.mironline.co.kr/mir2/2024_event/0411/images/${de}`,
                            alt: "title"
                        }) : u.jsx("img", {
                            src: "https://web-cdn.mironline.co.kr/mir2/2024_event/0411/images/card_title_0.png",
                            alt: "title"
                        })
                    }), u.jsxs("div", {
                        className: X.minigame_card_wrap,
                        children: [un.map( ({id: ae, video: Me}) => d.userInfo && ae === d.userInfo.gameLevel.replace("+", "") && u.jsx("video", {
                            loop: !0,
                            autoPlay: !0,
                            muted: !0,
                            preload: "auto",
                            className: X.minigame_card_video,
                            children: u.jsx("source", {
                                src: `https://web-cdn.mironline.co.kr/mir2/2024_event/0411/images/${Me || "card_0.webm"}`,
                                type: "video/webm"
                            })
                        }, ae)), d.userInfo === void 0 && u.jsx("video", {
                            loop: !0,
                            autoPlay: !0,
                            muted: !0,
                            preload: "auto",
                            className: X.minigame_card_video,
                            children: u.jsx("source", {
                                src: "https://web-cdn.mironline.co.kr/mir2/2024_event/0411/images/card_0.webm",
                                type: "video/webm"
                            })
                        }), N && u.jsx("div", {
                            className: X.minigame_card_effect,
                            children: u.jsx("img", {
                                src: "https://web-cdn.mironline.co.kr/mir2/2024_event/0411/images/card_effect.webp",
                                alt: "effect"
                            })
                        })]
                    }), u.jsx("div", {
                        className: X.minigame_battle_phase_wrap,
                        children: u.jsxs("div", {
                            className: X.minigame_battle_phase_bg,
                            children: [u.jsx("span", {
                                className: `${X.minigame_battle_phase_number} ${d.userInfo && d.userInfo.gameLevel.replace("+", "") === "12" ? X.minigame_battle_phase_number_clear : void 0}`,
                                children: d.userInfo && d.userInfo.gameLevel.replace("+", "") === "12" ? "Clear" : d.userInfo && d.userInfo.gameLevel.replace("+", "") || "0"
                            }), u.jsx("span", {
                                className: X.minigame_battle_phase_step,
                                children: "단계"
                            }), u.jsx("button", {
                                type: "button",
                                className: X.minigame_battle_percentage_info,
                                onClick: () => A(!0),
                                children: u.jsx("img", {
                                    src: "https://web-cdn.mironline.co.kr/mir2/2024_event/0411/images/percentage_info.png",
                                    alt: "전투 확률정보"
                                })
                            })]
                        })
                    }), u.jsxs("div", {
                        className: X.minigame_battle_buff_wrap,
                        children: [u.jsxs("picture", {
                            children: [u.jsx("source", {
                                srcSet: `https://web-cdn.mironline.co.kr/mir2/2024_event/0411/images/buff_${U ? "on.webp" : "off.png"}`,
                                type: "image/webp"
                            }), u.jsx("img", {
                                src: "https://web-cdn.mironline.co.kr/mir2/2024_event/0411/images/buff_off.png",
                                alt: "버프 off"
                            })]
                        }), u.jsxs("div", {
                            className: X.minigame_battle_buff_setting_wrap,
                            children: [u.jsxs("div", {
                                children: [u.jsx("img", {
                                    src: "https://web-cdn.mironline.co.kr/mir2/2024_event/0411/images/buff_setting.png",
                                    alt: "버프 설정"
                                }), u.jsxs("div", {
                                    className: X.minigame_battle_buff_toggle_button,
                                    children: [u.jsx("img", {
                                        src: "https://web-cdn.mironline.co.kr/mir2/2024_event/0411/images/buff_setting_icon.png",
                                        alt: "버프 설정 아이콘"
                                    }), u.jsx("div", {
                                        className: X.minigame_battle_buff_toggle,
                                        children: u.jsx("img", {
                                            src: "https://web-cdn.mironline.co.kr/mir2/2024_event/0411/images/buff_toggle_info.png",
                                            alt: "버프 토글창"
                                        })
                                    })]
                                })]
                            }), u.jsx(WA, {
                                checked: U,
                                onChange: dn,
                                disabled: d.userInfo && d.userInfo.gameLevel.includes("11") || d.userInfo && d.userInfo.gameLevel.includes("12")
                            })]
                        })]
                    }), u.jsxs("div", {
                        className: X.battle_button_wrap,
                        children: [u.jsx("button", {
                            type: "button",
                            id: "B",
                            onClick: O ? void 0 : Pi,
                            disabled: p === "N",
                            children: u.jsx("img", {
                                id: "B",
                                src: "https://web-cdn.mironline.co.kr/mir2/2024_event/0411/images/45p_battle_button.png",
                                alt: "일반 전투"
                            })
                        }), u.jsx("button", {
                            type: "button",
                            id: "S",
                            onClick: O ? void 0 : Pi,
                            disabled: p === "N",
                            children: u.jsx("img", {
                                id: "S",
                                src: "https://web-cdn.mironline.co.kr/mir2/2024_event/0411/images/450p_battle_button.png",
                                alt: "진기보양단 전투"
                            })
                        })]
                    })]
                }), u.jsxs("div", {
                    className: X.minigame_position_rank_part,
                    children: [u.jsxs("span", {
                        className: X.minigame_update_time,
                        children: ["(최신업데이트: ", n, ")"]
                    }), u.jsx("ul", {
                        className: X.minigame_top_rank,
                        children: u.jsx("div", {
                            className: X.minigame_top_rank_position_wrap,
                            children: b.map( ({gameLevel: ae, userId: Me}, q) => u.jsxs("div", {
                                className: `${X.minigame_top_rank_wrap} ${b.length === 1 ? X.minigame_top_rank_alone_wrap : void 0}`,
                                children: [u.jsx("li", {
                                    children: `${ae.replace("+", "") === "12" ? "Clear" : ae.replace("+", "")}단계` || "-"
                                }, q), u.jsx("li", {
                                    children: L_(Me) || "-"
                                })]
                            }, q))
                        })
                    }), u.jsx("div", {
                        className: X.minigame_rank_table_wrap,
                        children: u.jsx("div", {
                            className: X.minigame_rank_table_position_wrap,
                            children: u.jsx("ul", {
                                className: X.minigame_rank_table,
                                children: x.list.map( ({rowNum: ae, userId: Me, gameLevel: q, modiDate: re}) => u.jsxs("div", {
                                    className: X.minigame_rank,
                                    children: [u.jsx("li", {
                                        children: ge[ae] || ae
                                    }), u.jsx("li", {
                                        children: L_(Me)
                                    }), u.jsx("li", {
                                        children: q.replace("+", "") === "12" ? "Clear" : q.replace("+", "")
                                    }), u.jsx("li", {
                                        children: re.replaceAll("-", ".")
                                    })]
                                }, ae))
                            })
                        })
                    }), u.jsx("div", {
                        className: X.minigame_my_rank_wrap,
                        children: u.jsx("div", {
                            className: X.minigame_my_rank_position_wrap,
                            children: d.userInfo && d.userInfo.rowNum !== 0 && d.userInfo.rowNum <= 50 && r ? u.jsxs(u.Fragment, {
                                children: [u.jsx("button", {
                                    className: X.minigame_my_rank_search,
                                    type: "button",
                                    onClick: Es,
                                    disabled: p === "N"
                                }), u.jsxs("div", {
                                    className: X.minigame_my_rank_info,
                                    children: [u.jsx("span", {
                                        className: X.minigame_my_rank,
                                        children: `${d.userInfo.rowNum}위`
                                    }), u.jsx("span", {
                                        className: X.minigame_my_name,
                                        children: d.userInfo.gameChar
                                    }), u.jsx("span", {
                                        className: X.minigame_my_step,
                                        children: `${d.userInfo.gameLevel.replace("+", "") === "12" ? "Clear" : d.userInfo.gameLevel.replace("+", "")}단계`
                                    }), u.jsx("span", {
                                        className: X.minigame_my_time,
                                        children: d.userInfo.modiDate && d.userInfo.modiDate.replaceAll("-", ".")
                                    })]
                                })]
                            }) : u.jsx("div", {
                                className: X.minigame_have_no_rank,
                                children: u.jsx("p", {
                                    children: "순위 50위 안에 포함되어 있지 않습니다."
                                })
                            })
                        })
                    })]
                })]
            })
        }), u.jsx("div", {
            className: X.minigame_sub_title,
            children: u.jsx("img", {
                src: "https://web-cdn.mironline.co.kr/mir2/2024_event/0411/images/minigame-title-2.png",
                alt: "미니게임 타이틀 2"
            })
        }), u.jsx("div", {
            className: X.minigame_content_3,
            children: u.jsx("img", {
                src: "https://web-cdn.mironline.co.kr/mir2/2024_event/0411/images/minigame-con-3.png",
                alt: "미니게임 콘텐츠 3"
            })
        }), u.jsx("div", {
            children: u.jsx("img", {
                src: "https://web-cdn.mironline.co.kr/mir2/2024_event/0411/images/minigame-con-4.png",
                alt: "미니게임 콘텐츠 4"
            })
        }), k && u.jsx(vt.Consumer, {
            children: u.jsx(cm, {
                setIsChangeNickNameModalOpen: P
            })
        }), S && u.jsx(vt.Consumer, {
            children: u.jsx(lm, {
                memServer: o.memServer,
                memCharacter: o.memCharacter,
                memServerCode: o.memServerCode,
                memCharacterYn: o.memCharacterYn,
                setIsChangeModalOpen: T
            })
        }), R && u.jsx(vt.Consumer, {
            children: u.jsx(jA, {
                setIsPercentageModalOpen: A
            })
        }), V && u.jsx(vt.Consumer, {
            children: u.jsx(Ax, {
                setIsPointModalOpen: F
            })
        }), Z && u.jsx(vt.Consumer, {
            children: u.jsx(BA, {
                setIsResultModalOpen: L,
                setIsBattleClick: B
            })
        })]
    })
}
  , KA = "_container_1yfxr_1"
  , XA = "_observed_wrap_1yfxr_7"
  , QA = "_monster_title_1yfxr_12"
  , JA = "_monster_nahash_1yfxr_18"
  , ZA = "_monster_akasha_1yfxr_23"
  , Hs = {
    container: KA,
    observed_wrap: XA,
    monster_title: QA,
    monster_nahash: JA,
    monster_akasha: ZA
}
  , e3 = ({sectionRef: e}) => {
    const {setNowSection: t} = g.useContext(Cn)
      , n = g.useRef(null)
      , r = Ie(n);
    return g.useEffect( () => {
        r && t("story|monster")
    }
    , [r]),
    u.jsx("section", {
        className: Hs.container,
        ref: e,
        children: u.jsxs("div", {
            className: Hs.observed_wrap,
            ref: n,
            children: [u.jsx("div", {
                className: Hs.monster_title,
                children: u.jsx("img", {
                    src: "https://web-cdn.mironline.co.kr/mir2/2024_event/0411/images/monster-title.png",
                    alt: "title"
                })
            }), u.jsx("div", {
                className: Hs.monster_nahash,
                children: u.jsx("img", {
                    src: "https://web-cdn.mironline.co.kr/mir2/2024_event/0411/images/monster-nahash.png",
                    alt: "나하쉬"
                })
            }), u.jsx("div", {
                className: Hs.monster_akasha,
                children: u.jsx("img", {
                    src: "https://web-cdn.mironline.co.kr/mir2/2024_event/0411/images/monster-akasha.png",
                    alt: "아카샤"
                })
            })]
        })
    })
}
  , t3 = "_container_1bsex_1"
  , n3 = "_observed_wrap_1bsex_9"
  , r3 = "_update_con_first_1bsex_16"
  , i3 = "_update_con_second_1bsex_19"
  , s3 = "_update_baek_button_1bsex_22"
  , o3 = "_on_title_1bsex_38"
  , a3 = "_slideUp_1bsex_1"
  , l3 = "_on_1_1bsex_42"
  , c3 = "_on_2_1bsex_46"
  , lr = {
    container: t3,
    observed_wrap: n3,
    update_con_first: r3,
    update_con_second: i3,
    update_baek_button: s3,
    on_title: o3,
    slideUp: a3,
    on_1: l3,
    on_2: c3
}
  , go = e => {
    const [t,n] = g.useState(!1);
    return g.useEffect( () => {
        const r = s => {
            s.forEach(o => {
                o.isIntersecting && n(!0)
            }
            )
        }
          , i = new IntersectionObserver(r);
        return e && e.current && i.observe(e.current),
        () => {
            i.disconnect()
        }
    }
    , [e]),
    t
}
  , u3 = ({sectionRef: e}) => {
    const {setNowSection: t} = g.useContext(Cn)
      , n = g.useRef(null)
      , r = Ie(n)
      , i = g.useRef(null)
      , s = g.useRef(null)
      , o = g.useRef(null)
      , l = go(i)
      , a = go(s)
      , c = go(o);
    return g.useEffect( () => {
        r && t("story|update")
    }
    , [r]),
    u.jsx("section", {
        className: lr.container,
        ref: e,
        children: u.jsxs("div", {
            className: lr.observed_wrap,
            children: [u.jsx("div", {
                className: `${l ? lr.on_title : void 0}`,
                ref: i,
                children: u.jsx("img", {
                    src: "https://web-cdn.mironline.co.kr/mir2/2024_event/0411/images/update-title.png",
                    alt: "업데이트 타이틀"
                })
            }), u.jsx("div", {
                className: `${lr.update_con_first} ${a ? lr.on_1 : void 0}`,
                ref: n,
                children: u.jsx("div", {
                    ref: s,
                    children: u.jsx("img", {
                        src: "https://web-cdn.mironline.co.kr/mir2/2024_event/0411/images/update-con-1.png",
                        alt: "업데이트 콘텐츠 1"
                    })
                })
            }), u.jsxs("div", {
                className: `${lr.update_con_second} ${c ? lr.on_2 : void 0}`,
                ref: o,
                children: [u.jsx("div", {
                    children: u.jsx("img", {
                        src: "https://web-cdn.mironline.co.kr/mir2/2024_event/0411/images/update-con-2.png",
                        alt: "업데이트 콘텐츠 2"
                    })
                }), u.jsx("a", {
                    className: lr.update_baek_button,
                    href: "https://mir2.mironline.co.kr/dic/dicView?depth1=109&depth2=179&depth3=0",
                    children: u.jsx("img", {
                        src: "https://web-cdn.mironline.co.kr/mir2/2024_event/0411/images/update-baek-button.png",
                        alt: "백과서고 버튼"
                    })
                })]
            })]
        })
    })
}
  , d3 = "_container_zkqf4_1"
  , f3 = "_observe_wrap_zkqf4_9"
  , p3 = "_on_title_zkqf4_18"
  , m3 = "_slideUp_zkqf4_1"
  , h3 = "_on_1_zkqf4_22"
  , Ha = {
    container: d3,
    observe_wrap: f3,
    on_title: p3,
    slideUp: m3,
    on_1: h3
}
  , g3 = ({sectionRef: e}) => {
    const {setNowSection: t} = g.useContext(Cn)
      , n = g.useRef(null)
      , r = Ie(n)
      , i = g.useRef(null)
      , s = g.useRef(null)
      , o = go(i)
      , l = go(s);
    return g.useEffect( () => {
        r && t("event")
    }
    , [r]),
    u.jsx("section", {
        className: Ha.container,
        ref: e,
        children: u.jsxs("div", {
            className: Ha.observe_wrap,
            children: [u.jsx("div", {
                ref: n,
                children: u.jsx("div", {
                    ref: i,
                    className: `${o ? Ha.on_title : void 0}`,
                    children: u.jsx("img", {
                        src: "https://web-cdn.mironline.co.kr/mir2/2024_event/0411/images/event-title.png",
                        alt: "이벤트 타이틀"
                    })
                })
            }), u.jsx("div", {
                ref: s,
                className: `${l ? Ha.on_1 : void 0}`,
                children: u.jsx("img", {
                    src: "https://web-cdn.mironline.co.kr/mir2/2024_event/0411/images/event-con1.png",
                    alt: "이벤트 콘텐츠 1"
                })
            })]
        })
    })
}
  , _3 = "_container_ioxx9_1"
  , v3 = {
    container: _3
}
  , y3 = () => u.jsx("section", {
    className: v3.container,
    children: u.jsx("img", {
        src: "https://web-cdn.mironline.co.kr/mir2/2024_event/0411/images/notice.png",
        alt: "유의사항"
    })
})
  , w3 = "_container_1hbve_2"
  , x3 = {
    container: w3
}
  , b3 = () => u.jsx("main", {
    className: x3.container,
    children: u.jsx(om, {
        children: u.jsx(ZL, {
            children: u.jsxs(sa, {
                children: [u.jsx(cM, {}), u.jsx(Si, {}), u.jsx(KL, {}), u.jsx(fM, {})]
            })
        })
    })
})
  , S3 = "_video_wrapper_1xvak_1"
  , C3 = "_video_inner_1xvak_8"
  , k3 = "_video_1xvak_1"
  , E3 = "_next_section_1xvak_29"
  , T3 = "_next_button_1xvak_36"
  , P3 = "_bounce_1xvak_1"
  , j3 = "_float_button_1xvak_58"
  , Oi = {
    video_wrapper: S3,
    video_inner: C3,
    video: k3,
    next_section: E3,
    next_button: T3,
    bounce: P3,
    float_button: j3
}
  , cn = g.createContext({
    navClickSection: "",
    setNavClickSection: () => {}
    ,
    introVideoRef: {
        current: null
    },
    nowSection: "main",
    setNowSection: () => {}
    ,
    scaleState: {
        currentSpot: "default",
        focus: !1
    },
    setScaleState: () => {}
    ,
    mainModalState: !1,
    setMainModalState: () => {}
    ,
    villageModalState: "",
    setVillageModalState: () => {}
    ,
    currentTab: "smelt",
    setCurrentTab: () => {}
})
  , N3 = ({children: e}) => {
    const t = g.useRef(null)
      , [n,r] = g.useState("")
      , [i,s] = g.useState("main")
      , [o,l] = g.useState({
        currentSpot: "default",
        focus: !1
    })
      , [a,c] = g.useState(!1)
      , [d,f] = g.useState("")
      , [p,h] = g.useState("smelt");
    return u.jsx(cn.Provider, {
        value: {
            introVideoRef: t,
            nowSection: i,
            setNowSection: s,
            navClickSection: n,
            setNavClickSection: r,
            scaleState: o,
            setScaleState: l,
            mainModalState: a,
            setMainModalState: c,
            villageModalState: d,
            setVillageModalState: f,
            currentTab: p,
            setCurrentTab: h
        },
        children: e
    })
}
;
var Dx = {
    exports: {}
}
  , R3 = "SECRET_DO_NOT_PASS_THIS_OR_YOU_WILL_BE_FIRED"
  , I3 = R3
  , L3 = I3;
function $x() {}
function Fx() {}
Fx.resetWarningCache = $x;
var M3 = function() {
    function e(r, i, s, o, l, a) {
        if (a !== L3) {
            var c = new Error("Calling PropTypes validators directly is not supported by the `prop-types` package. Use PropTypes.checkPropTypes() to call them. Read more at http://fb.me/use-check-prop-types");
            throw c.name = "Invariant Violation",
            c
        }
    }
    e.isRequired = e;
    function t() {
        return e
    }
    var n = {
        array: e,
        bigint: e,
        bool: e,
        func: e,
        number: e,
        object: e,
        string: e,
        symbol: e,
        any: e,
        arrayOf: t,
        element: e,
        elementType: e,
        instanceOf: t,
        node: e,
        objectOf: t,
        oneOf: t,
        oneOfType: t,
        shape: t,
        exact: t,
        checkPropTypes: Fx,
        resetWarningCache: $x
    };
    return n.PropTypes = n,
    n
};
Dx.exports = M3();
var O3 = Dx.exports;
const lt = Yo(O3);
var A3 = function e(t, n) {
    if (t === n)
        return !0;
    if (t && n && typeof t == "object" && typeof n == "object") {
        if (t.constructor !== n.constructor)
            return !1;
        var r, i, s;
        if (Array.isArray(t)) {
            if (r = t.length,
            r != n.length)
                return !1;
            for (i = r; i-- !== 0; )
                if (!e(t[i], n[i]))
                    return !1;
            return !0
        }
        if (t.constructor === RegExp)
            return t.source === n.source && t.flags === n.flags;
        if (t.valueOf !== Object.prototype.valueOf)
            return t.valueOf() === n.valueOf();
        if (t.toString !== Object.prototype.toString)
            return t.toString() === n.toString();
        if (s = Object.keys(t),
        r = s.length,
        r !== Object.keys(n).length)
            return !1;
        for (i = r; i-- !== 0; )
            if (!Object.prototype.hasOwnProperty.call(n, s[i]))
                return !1;
        for (i = r; i-- !== 0; ) {
            var o = s[i];
            if (!e(t[o], n[o]))
                return !1
        }
        return !0
    }
    return t !== t && n !== n
};
const D3 = Yo(A3);
var vf = {
    exports: {}
}, Vx;
/**
* @link https://github.com/gajus/sister for the canonical source repository
* @license https://github.com/gajus/sister/blob/master/LICENSE BSD 3-Clause
*/
Vx = function() {
    var e = {}
      , t = {};
    return e.on = function(n, r) {
        var i = {
            name: n,
            handler: r
        };
        return t[n] = t[n] || [],
        t[n].unshift(i),
        i
    }
    ,
    e.off = function(n) {
        var r = t[n.name].indexOf(n);
        r !== -1 && t[n.name].splice(r, 1)
    }
    ,
    e.trigger = function(n, r) {
        var i = t[n], s;
        if (i)
            for (s = i.length; s--; )
                i[s].handler(r)
    }
    ,
    e
}
;
var $3 = Vx
  , yf = {
    exports: {}
}
  , F3 = function(t, n, r) {
    var i = document.head || document.getElementsByTagName("head")[0]
      , s = document.createElement("script");
    typeof n == "function" && (r = n,
    n = {}),
    n = n || {},
    r = r || function() {}
    ,
    s.type = n.type || "text/javascript",
    s.charset = n.charset || "utf8",
    s.async = "async"in n ? !!n.async : !0,
    s.src = t,
    n.attrs && V3(s, n.attrs),
    n.text && (s.text = "" + n.text);
    var o = "onload"in s ? A_ : z3;
    o(s, r),
    s.onload || A_(s, r),
    i.appendChild(s)
};
function V3(e, t) {
    for (var n in t)
        e.setAttribute(n, t[n])
}
function A_(e, t) {
    e.onload = function() {
        this.onerror = this.onload = null,
        t(null, e)
    }
    ,
    e.onerror = function() {
        this.onerror = this.onload = null,
        t(new Error("Failed to load " + this.src), e)
    }
}
function z3(e, t) {
    e.onreadystatechange = function() {
        this.readyState != "complete" && this.readyState != "loaded" || (this.onreadystatechange = null,
        t(null, e))
    }
}
(function(e, t) {
    Object.defineProperty(t, "__esModule", {
        value: !0
    });
    var n = F3
      , r = i(n);
    function i(s) {
        return s && s.__esModule ? s : {
            default: s
        }
    }
    t.default = function(s) {
        var o = new Promise(function(l) {
            if (window.YT && window.YT.Player && window.YT.Player instanceof Function) {
                l(window.YT);
                return
            } else {
                var a = window.location.protocol === "http:" ? "http:" : "https:";
                (0,
                r.default)(a + "//www.youtube.com/iframe_api", function(d) {
                    d && s.trigger("error", d)
                })
            }
            var c = window.onYouTubeIframeAPIReady;
            window.onYouTubeIframeAPIReady = function() {
                c && c(),
                l(window.YT)
            }
        }
        );
        return o
    }
    ,
    e.exports = t.default
}
)(yf, yf.exports);
var B3 = yf.exports
  , wf = {
    exports: {}
}
  , xf = {
    exports: {}
}
  , bf = {
    exports: {}
}
  , Ho = 1e3
  , Go = Ho * 60
  , Wo = Go * 60
  , qo = Wo * 24
  , U3 = qo * 365.25
  , H3 = function(e, t) {
    t = t || {};
    var n = typeof e;
    if (n === "string" && e.length > 0)
        return G3(e);
    if (n === "number" && isNaN(e) === !1)
        return t.long ? q3(e) : W3(e);
    throw new Error("val is not a non-empty string or a valid number. val=" + JSON.stringify(e))
};
function G3(e) {
    if (e = String(e),
    !(e.length > 100)) {
        var t = /^((?:\d+)?\.?\d+) *(milliseconds?|msecs?|ms|seconds?|secs?|s|minutes?|mins?|m|hours?|hrs?|h|days?|d|years?|yrs?|y)?$/i.exec(e);
        if (t) {
            var n = parseFloat(t[1])
              , r = (t[2] || "ms").toLowerCase();
            switch (r) {
            case "years":
            case "year":
            case "yrs":
            case "yr":
            case "y":
                return n * U3;
            case "days":
            case "day":
            case "d":
                return n * qo;
            case "hours":
            case "hour":
            case "hrs":
            case "hr":
            case "h":
                return n * Wo;
            case "minutes":
            case "minute":
            case "mins":
            case "min":
            case "m":
                return n * Go;
            case "seconds":
            case "second":
            case "secs":
            case "sec":
            case "s":
                return n * Ho;
            case "milliseconds":
            case "millisecond":
            case "msecs":
            case "msec":
            case "ms":
                return n;
            default:
                return
            }
        }
    }
}
function W3(e) {
    return e >= qo ? Math.round(e / qo) + "d" : e >= Wo ? Math.round(e / Wo) + "h" : e >= Go ? Math.round(e / Go) + "m" : e >= Ho ? Math.round(e / Ho) + "s" : e + "ms"
}
function q3(e) {
    return Ga(e, qo, "day") || Ga(e, Wo, "hour") || Ga(e, Go, "minute") || Ga(e, Ho, "second") || e + " ms"
}
function Ga(e, t, n) {
    if (!(e < t))
        return e < t * 1.5 ? Math.floor(e / t) + " " + n : Math.ceil(e / t) + " " + n + "s"
}
(function(e, t) {
    t = e.exports = i.debug = i.default = i,
    t.coerce = a,
    t.disable = o,
    t.enable = s,
    t.enabled = l,
    t.humanize = H3,
    t.names = [],
    t.skips = [],
    t.formatters = {};
    var n;
    function r(c) {
        var d = 0, f;
        for (f in c)
            d = (d << 5) - d + c.charCodeAt(f),
            d |= 0;
        return t.colors[Math.abs(d) % t.colors.length]
    }
    function i(c) {
        function d() {
            if (d.enabled) {
                var f = d
                  , p = +new Date
                  , h = p - (n || p);
                f.diff = h,
                f.prev = n,
                f.curr = p,
                n = p;
                for (var m = new Array(arguments.length), _ = 0; _ < m.length; _++)
                    m[_] = arguments[_];
                m[0] = t.coerce(m[0]),
                typeof m[0] != "string" && m.unshift("%O");
                var x = 0;
                m[0] = m[0].replace(/%([a-zA-Z%])/g, function(w, y) {
                    if (w === "%%")
                        return w;
                    x++;
                    var b = t.formatters[y];
                    if (typeof b == "function") {
                        var C = m[x];
                        w = b.call(f, C),
                        m.splice(x, 1),
                        x--
                    }
                    return w
                }),
                t.formatArgs.call(f, m);
                var v = d.log || t.log || console.log.bind(console);
                v.apply(f, m)
            }
        }
        return d.namespace = c,
        d.enabled = t.enabled(c),
        d.useColors = t.useColors(),
        d.color = r(c),
        typeof t.init == "function" && t.init(d),
        d
    }
    function s(c) {
        t.save(c),
        t.names = [],
        t.skips = [];
        for (var d = (typeof c == "string" ? c : "").split(/[\s,]+/), f = d.length, p = 0; p < f; p++)
            d[p] && (c = d[p].replace(/\*/g, ".*?"),
            c[0] === "-" ? t.skips.push(new RegExp("^" + c.substr(1) + "$")) : t.names.push(new RegExp("^" + c + "$")))
    }
    function o() {
        t.enable("")
    }
    function l(c) {
        var d, f;
        for (d = 0,
        f = t.skips.length; d < f; d++)
            if (t.skips[d].test(c))
                return !1;
        for (d = 0,
        f = t.names.length; d < f; d++)
            if (t.names[d].test(c))
                return !0;
        return !1
    }
    function a(c) {
        return c instanceof Error ? c.stack || c.message : c
    }
}
)(bf, bf.exports);
var Y3 = bf.exports;
(function(e, t) {
    t = e.exports = Y3,
    t.log = i,
    t.formatArgs = r,
    t.save = s,
    t.load = o,
    t.useColors = n,
    t.storage = typeof chrome < "u" && typeof chrome.storage < "u" ? chrome.storage.local : l(),
    t.colors = ["lightseagreen", "forestgreen", "goldenrod", "dodgerblue", "darkorchid", "crimson"];
    function n() {
        return typeof window < "u" && window.process && window.process.type === "renderer" ? !0 : typeof document < "u" && document.documentElement && document.documentElement.style && document.documentElement.style.WebkitAppearance || typeof window < "u" && window.console && (window.console.firebug || window.console.exception && window.console.table) || typeof navigator < "u" && navigator.userAgent && navigator.userAgent.toLowerCase().match(/firefox\/(\d+)/) && parseInt(RegExp.$1, 10) >= 31 || typeof navigator < "u" && navigator.userAgent && navigator.userAgent.toLowerCase().match(/applewebkit\/(\d+)/)
    }
    t.formatters.j = function(a) {
        try {
            return JSON.stringify(a)
        } catch (c) {
            return "[UnexpectedJSONParseError]: " + c.message
        }
    }
    ;
    function r(a) {
        var c = this.useColors;
        if (a[0] = (c ? "%c" : "") + this.namespace + (c ? " %c" : " ") + a[0] + (c ? "%c " : " ") + "+" + t.humanize(this.diff),
        !!c) {
            var d = "color: " + this.color;
            a.splice(1, 0, d, "color: inherit");
            var f = 0
              , p = 0;
            a[0].replace(/%[a-zA-Z%]/g, function(h) {
                h !== "%%" && (f++,
                h === "%c" && (p = f))
            }),
            a.splice(p, 0, d)
        }
    }
    function i() {
        return typeof console == "object" && console.log && Function.prototype.apply.call(console.log, console, arguments)
    }
    function s(a) {
        try {
            a == null ? t.storage.removeItem("debug") : t.storage.debug = a
        } catch {}
    }
    function o() {
        var a;
        try {
            a = t.storage.debug
        } catch {}
        return !a && typeof process < "u" && "env"in process && (a = {}.DEBUG),
        a
    }
    t.enable(o());
    function l() {
        try {
            return window.localStorage
        } catch {}
    }
}
)(xf, xf.exports);
var K3 = xf.exports
  , Sf = {
    exports: {}
};
(function(e, t) {
    Object.defineProperty(t, "__esModule", {
        value: !0
    }),
    t.default = ["cueVideoById", "loadVideoById", "cueVideoByUrl", "loadVideoByUrl", "playVideo", "pauseVideo", "stopVideo", "getVideoLoadedFraction", "cuePlaylist", "loadPlaylist", "nextVideo", "previousVideo", "playVideoAt", "setShuffle", "setLoop", "getPlaylist", "getPlaylistIndex", "setOption", "mute", "unMute", "isMuted", "setVolume", "getVolume", "seekTo", "getPlayerState", "getPlaybackRate", "setPlaybackRate", "getAvailablePlaybackRates", "getPlaybackQuality", "setPlaybackQuality", "getAvailableQualityLevels", "getCurrentTime", "getDuration", "removeEventListener", "getVideoUrl", "getVideoEmbedCode", "getOptions", "getOption", "addEventListener", "destroy", "setSize", "getIframe"],
    e.exports = t.default
}
)(Sf, Sf.exports);
var X3 = Sf.exports
  , Cf = {
    exports: {}
};
(function(e, t) {
    Object.defineProperty(t, "__esModule", {
        value: !0
    }),
    t.default = ["ready", "stateChange", "playbackQualityChange", "playbackRateChange", "error", "apiChange", "volumeChange"],
    e.exports = t.default
}
)(Cf, Cf.exports);
var Q3 = Cf.exports
  , kf = {
    exports: {}
}
  , Ef = {
    exports: {}
};
(function(e, t) {
    Object.defineProperty(t, "__esModule", {
        value: !0
    }),
    t.default = {
        BUFFERING: 3,
        ENDED: 0,
        PAUSED: 2,
        PLAYING: 1,
        UNSTARTED: -1,
        VIDEO_CUED: 5
    },
    e.exports = t.default
}
)(Ef, Ef.exports);
var J3 = Ef.exports;
(function(e, t) {
    Object.defineProperty(t, "__esModule", {
        value: !0
    });
    var n = J3
      , r = i(n);
    function i(s) {
        return s && s.__esModule ? s : {
            default: s
        }
    }
    t.default = {
        pauseVideo: {
            acceptableStates: [r.default.ENDED, r.default.PAUSED],
            stateChangeRequired: !1
        },
        playVideo: {
            acceptableStates: [r.default.ENDED, r.default.PLAYING],
            stateChangeRequired: !1
        },
        seekTo: {
            acceptableStates: [r.default.ENDED, r.default.PLAYING, r.default.PAUSED],
            stateChangeRequired: !0,
            timeout: 3e3
        }
    },
    e.exports = t.default
}
)(kf, kf.exports);
var Z3 = kf.exports;
(function(e, t) {
    Object.defineProperty(t, "__esModule", {
        value: !0
    });
    var n = K3
      , r = d(n)
      , i = X3
      , s = d(i)
      , o = Q3
      , l = d(o)
      , a = Z3
      , c = d(a);
    function d(h) {
        return h && h.__esModule ? h : {
            default: h
        }
    }
    var f = (0,
    r.default)("youtube-player")
      , p = {};
    p.proxyEvents = function(h) {
        var m = {}
          , _ = function(T) {
            var k = "on" + T.slice(0, 1).toUpperCase() + T.slice(1);
            m[k] = function(P) {
                f('event "%s"', k, P),
                h.trigger(T, P)
            }
        }
          , x = !0
          , v = !1
          , w = void 0;
        try {
            for (var y = l.default[Symbol.iterator](), b; !(x = (b = y.next()).done); x = !0) {
                var C = b.value;
                _(C)
            }
        } catch (S) {
            v = !0,
            w = S
        } finally {
            try {
                !x && y.return && y.return()
            } finally {
                if (v)
                    throw w
            }
        }
        return m
    }
    ,
    p.promisifyPlayer = function(h) {
        var m = arguments.length > 1 && arguments[1] !== void 0 ? arguments[1] : !1
          , _ = {}
          , x = function(k) {
            m && c.default[k] ? _[k] = function() {
                for (var P = arguments.length, R = Array(P), A = 0; A < P; A++)
                    R[A] = arguments[A];
                return h.then(function(V) {
                    var F = c.default[k]
                      , U = V.getPlayerState()
                      , Q = V[k].apply(V, R);
                    return F.stateChangeRequired || Array.isArray(F.acceptableStates) && F.acceptableStates.indexOf(U) === -1 ? new Promise(function(Z) {
                        var L = function N() {
                            var $ = V.getPlayerState()
                              , O = void 0;
                            typeof F.timeout == "number" && (O = setTimeout(function() {
                                V.removeEventListener("onStateChange", N),
                                Z()
                            }, F.timeout)),
                            Array.isArray(F.acceptableStates) && F.acceptableStates.indexOf($) !== -1 && (V.removeEventListener("onStateChange", N),
                            clearTimeout(O),
                            Z())
                        };
                        V.addEventListener("onStateChange", L)
                    }
                    ).then(function() {
                        return Q
                    }) : Q
                })
            }
            : _[k] = function() {
                for (var P = arguments.length, R = Array(P), A = 0; A < P; A++)
                    R[A] = arguments[A];
                return h.then(function(V) {
                    return V[k].apply(V, R)
                })
            }
        }
          , v = !0
          , w = !1
          , y = void 0;
        try {
            for (var b = s.default[Symbol.iterator](), C; !(v = (C = b.next()).done); v = !0) {
                var S = C.value;
                x(S)
            }
        } catch (T) {
            w = !0,
            y = T
        } finally {
            try {
                !v && b.return && b.return()
            } finally {
                if (w)
                    throw y
            }
        }
        return _
    }
    ,
    t.default = p,
    e.exports = t.default
}
)(wf, wf.exports);
var eD = wf.exports;
(function(e, t) {
    Object.defineProperty(t, "__esModule", {
        value: !0
    });
    var n = typeof Symbol == "function" && typeof Symbol.iterator == "symbol" ? function(f) {
        return typeof f
    }
    : function(f) {
        return f && typeof Symbol == "function" && f.constructor === Symbol && f !== Symbol.prototype ? "symbol" : typeof f
    }
      , r = $3
      , i = c(r)
      , s = B3
      , o = c(s)
      , l = eD
      , a = c(l);
    function c(f) {
        return f && f.__esModule ? f : {
            default: f
        }
    }
    var d = void 0;
    t.default = function(f) {
        var p = arguments.length > 1 && arguments[1] !== void 0 ? arguments[1] : {}
          , h = arguments.length > 2 && arguments[2] !== void 0 ? arguments[2] : !1
          , m = (0,
        i.default)();
        if (d || (d = (0,
        o.default)(m)),
        p.events)
            throw new Error("Event handlers cannot be overwritten.");
        if (typeof f == "string" && !document.getElementById(f))
            throw new Error('Element "' + f + '" does not exist.');
        p.events = a.default.proxyEvents(m);
        var _ = new Promise(function(v) {
            if ((typeof f > "u" ? "undefined" : n(f)) === "object" && f.playVideo instanceof Function) {
                var w = f;
                v(w)
            } else
                d.then(function(y) {
                    var b = new y.Player(f,p);
                    return m.on("ready", function() {
                        v(b)
                    }),
                    null
                })
        }
        )
          , x = a.default.promisifyPlayer(_, h);
        return x.on = m.on,
        x.off = m.off,
        x
    }
    ,
    e.exports = t.default
}
)(vf, vf.exports);
var tD = vf.exports;
const nD = Yo(tD);
var rD = Object.defineProperty
  , iD = Object.defineProperties
  , sD = Object.getOwnPropertyDescriptors
  , D_ = Object.getOwnPropertySymbols
  , oD = Object.prototype.hasOwnProperty
  , aD = Object.prototype.propertyIsEnumerable
  , $_ = (e, t, n) => t in e ? rD(e, t, {
    enumerable: !0,
    configurable: !0,
    writable: !0,
    value: n
}) : e[t] = n
  , Tf = (e, t) => {
    for (var n in t || (t = {}))
        oD.call(t, n) && $_(e, n, t[n]);
    if (D_)
        for (var n of D_(t))
            aD.call(t, n) && $_(e, n, t[n]);
    return e
}
  , Pf = (e, t) => iD(e, sD(t))
  , lD = (e, t, n) => new Promise( (r, i) => {
    var s = a => {
        try {
            l(n.next(a))
        } catch (c) {
            i(c)
        }
    }
      , o = a => {
        try {
            l(n.throw(a))
        } catch (c) {
            i(c)
        }
    }
      , l = a => a.done ? r(a.value) : Promise.resolve(a.value).then(s, o);
    l((n = n.apply(e, t)).next())
}
);
function cD(e, t) {
    var n, r;
    if (e.videoId !== t.videoId)
        return !0;
    const i = ((n = e.opts) == null ? void 0 : n.playerVars) || {}
      , s = ((r = t.opts) == null ? void 0 : r.playerVars) || {};
    return i.start !== s.start || i.end !== s.end
}
function F_(e={}) {
    return Pf(Tf({}, e), {
        height: 0,
        width: 0,
        playerVars: Pf(Tf({}, e.playerVars), {
            autoplay: 0,
            start: 0,
            end: 0
        })
    })
}
function uD(e, t) {
    return e.videoId !== t.videoId || !D3(F_(e.opts), F_(t.opts))
}
function dD(e, t) {
    var n, r, i, s;
    return e.id !== t.id || e.className !== t.className || ((n = e.opts) == null ? void 0 : n.width) !== ((r = t.opts) == null ? void 0 : r.width) || ((i = e.opts) == null ? void 0 : i.height) !== ((s = t.opts) == null ? void 0 : s.height) || e.iframeClassName !== t.iframeClassName || e.title !== t.title
}
var fD = {
    videoId: "",
    id: "",
    className: "",
    iframeClassName: "",
    style: {},
    title: "",
    loading: void 0,
    opts: {},
    onReady: () => {}
    ,
    onError: () => {}
    ,
    onPlay: () => {}
    ,
    onPause: () => {}
    ,
    onEnd: () => {}
    ,
    onStateChange: () => {}
    ,
    onPlaybackRateChange: () => {}
    ,
    onPlaybackQualityChange: () => {}
}
  , pD = {
    videoId: lt.string,
    id: lt.string,
    className: lt.string,
    iframeClassName: lt.string,
    style: lt.object,
    title: lt.string,
    loading: lt.oneOf(["lazy", "eager"]),
    opts: lt.objectOf(lt.any),
    onReady: lt.func,
    onError: lt.func,
    onPlay: lt.func,
    onPause: lt.func,
    onEnd: lt.func,
    onStateChange: lt.func,
    onPlaybackRateChange: lt.func,
    onPlaybackQualityChange: lt.func
}
  , fl = class extends Ce.Component {
    constructor(e) {
        super(e),
        this.destroyPlayerPromise = void 0,
        this.onPlayerReady = t => {
            var n, r;
            return (r = (n = this.props).onReady) == null ? void 0 : r.call(n, t)
        }
        ,
        this.onPlayerError = t => {
            var n, r;
            return (r = (n = this.props).onError) == null ? void 0 : r.call(n, t)
        }
        ,
        this.onPlayerStateChange = t => {
            var n, r, i, s, o, l, a, c;
            switch ((r = (n = this.props).onStateChange) == null || r.call(n, t),
            t.data) {
            case fl.PlayerState.ENDED:
                (s = (i = this.props).onEnd) == null || s.call(i, t);
                break;
            case fl.PlayerState.PLAYING:
                (l = (o = this.props).onPlay) == null || l.call(o, t);
                break;
            case fl.PlayerState.PAUSED:
                (c = (a = this.props).onPause) == null || c.call(a, t);
                break
            }
        }
        ,
        this.onPlayerPlaybackRateChange = t => {
            var n, r;
            return (r = (n = this.props).onPlaybackRateChange) == null ? void 0 : r.call(n, t)
        }
        ,
        this.onPlayerPlaybackQualityChange = t => {
            var n, r;
            return (r = (n = this.props).onPlaybackQualityChange) == null ? void 0 : r.call(n, t)
        }
        ,
        this.destroyPlayer = () => this.internalPlayer ? (this.destroyPlayerPromise = this.internalPlayer.destroy().then( () => this.destroyPlayerPromise = void 0),
        this.destroyPlayerPromise) : Promise.resolve(),
        this.createPlayer = () => {
            if (typeof document > "u")
                return;
            if (this.destroyPlayerPromise) {
                this.destroyPlayerPromise.then(this.createPlayer);
                return
            }
            const t = Pf(Tf({}, this.props.opts), {
                videoId: this.props.videoId
            });
            this.internalPlayer = nD(this.container, t),
            this.internalPlayer.on("ready", this.onPlayerReady),
            this.internalPlayer.on("error", this.onPlayerError),
            this.internalPlayer.on("stateChange", this.onPlayerStateChange),
            this.internalPlayer.on("playbackRateChange", this.onPlayerPlaybackRateChange),
            this.internalPlayer.on("playbackQualityChange", this.onPlayerPlaybackQualityChange),
            (this.props.title || this.props.loading) && this.internalPlayer.getIframe().then(n => {
                this.props.title && n.setAttribute("title", this.props.title),
                this.props.loading && n.setAttribute("loading", this.props.loading)
            }
            )
        }
        ,
        this.resetPlayer = () => this.destroyPlayer().then(this.createPlayer),
        this.updatePlayer = () => {
            var t;
            (t = this.internalPlayer) == null || t.getIframe().then(n => {
                this.props.id ? n.setAttribute("id", this.props.id) : n.removeAttribute("id"),
                this.props.iframeClassName ? n.setAttribute("class", this.props.iframeClassName) : n.removeAttribute("class"),
                this.props.opts && this.props.opts.width ? n.setAttribute("width", this.props.opts.width.toString()) : n.removeAttribute("width"),
                this.props.opts && this.props.opts.height ? n.setAttribute("height", this.props.opts.height.toString()) : n.removeAttribute("height"),
                this.props.title ? n.setAttribute("title", this.props.title) : n.setAttribute("title", "YouTube video player"),
                this.props.loading ? n.setAttribute("loading", this.props.loading) : n.removeAttribute("loading")
            }
            )
        }
        ,
        this.getInternalPlayer = () => this.internalPlayer,
        this.updateVideo = () => {
            var t, n, r, i;
            if (typeof this.props.videoId > "u" || this.props.videoId === null) {
                (t = this.internalPlayer) == null || t.stopVideo();
                return
            }
            let s = !1;
            const o = {
                videoId: this.props.videoId
            };
            if ((n = this.props.opts) != null && n.playerVars && (s = this.props.opts.playerVars.autoplay === 1,
            "start"in this.props.opts.playerVars && (o.startSeconds = this.props.opts.playerVars.start),
            "end"in this.props.opts.playerVars && (o.endSeconds = this.props.opts.playerVars.end)),
            s) {
                (r = this.internalPlayer) == null || r.loadVideoById(o);
                return
            }
            (i = this.internalPlayer) == null || i.cueVideoById(o)
        }
        ,
        this.refContainer = t => {
            this.container = t
        }
        ,
        this.container = null,
        this.internalPlayer = null
    }
    componentDidMount() {
        this.createPlayer()
    }
    componentDidUpdate(e) {
        return lD(this, null, function*() {
            dD(e, this.props) && this.updatePlayer(),
            uD(e, this.props) && (yield this.resetPlayer()),
            cD(e, this.props) && this.updateVideo()
        })
    }
    componentWillUnmount() {
        this.destroyPlayer()
    }
    render() {
        return Ce.createElement("div", {
            className: this.props.className,
            style: this.props.style
        }, Ce.createElement("div", {
            id: this.props.id,
            className: this.props.iframeClassName,
            ref: this.refContainer
        }))
    }
}
  , Ac = fl;
Ac.propTypes = pD;
Ac.defaultProps = fD;
Ac.PlayerState = {
    UNSTARTED: -1,
    ENDED: 0,
    PLAYING: 1,
    PAUSED: 2,
    BUFFERING: 3,
    CUED: 5
};
var mD = Ac;
const hD = "_youtube_wrap_1nbe5_1"
  , gD = {
    youtube_wrap: hD
}
  , _D = ({setIsYoutubeOpen: e}) => {
    Bo();
    const t = document.body.clientWidth / 2.5
      , n = {
        height: t * 9 / 16,
        width: t,
        playerVars: {
            autoplay: 1,
            rel: 0,
            modestbranding: 1,
            showinfo: 0
        }
    };
    return u.jsx(Cs, {
        children: u.jsxs("div", {
            className: gD.youtube_wrap,
            children: [u.jsx("button", {
                type: "button",
                onClick: () => e(!1),
                children: u.jsx("img", {
                    src: "https://web-cdn.mironline.co.kr/mir2/2024_event/0807/images/popup-close-btn.png",
                    alt: "close"
                })
            }), u.jsx(mD, {
                videoId: "6YgKfkEfvx4",
                opts: n
            })]
        })
    })
}
  , vD = ({sectionRef: e}) => {
    const {introVideoRef: t, setNowSection: n, setNavClickSection: r} = g.useContext(cn)
      , [i,s] = g.useState(!1)
      , o = g.useRef(null);
    g.useEffect( () => {
        const a = t.current;
        if (!a)
            return;
        let c;
        const d = () => {
            a.currentTime >= 14 && (a.currentTime = 4),
            c = requestAnimationFrame(d)
        }
        ;
        return d(),
        () => {
            cancelAnimationFrame(c)
        }
    }
    , []);
    const l = Ie(o);
    return g.useEffect( () => {
        l && n("main")
    }
    , [l]),
    u.jsxs("section", {
        className: Oi.video_wrapper,
        ref: e,
        children: [u.jsxs("div", {
            ref: o,
            className: Oi.video_inner,
            children: [u.jsx("video", {
                className: Oi.video,
                autoPlay: !0,
                muted: !0,
                ref: t,
                poster: "https://web-cdn.mironline.co.kr/mir2/2024_event/0807/images/thumbnail.jpg",
                children: u.jsx("source", {
                    src: "https://web-cdn.mironline.co.kr/mir2/2024_event/0807/videos/intro.mp4"
                })
            }), u.jsx("div", {
                className: Oi.next_section,
                children: u.jsx("button", {
                    type: "button",
                    className: Oi.next_button,
                    onClick: () => r("dogyo|story"),
                    children: "SCROLL DOWN"
                })
            })]
        }), u.jsx("button", {
            type: "button",
            className: Oi.float_button,
            onClick: () => s(!0),
            children: "소개 영상 보기"
        }), i && u.jsx(vt.Consumer, {
            children: u.jsx(_D, {
                setIsYoutubeOpen: s
            })
        })]
    })
}
  , yD = "_header_n0pj7_1"
  , wD = "_fadeIn_n0pj7_1"
  , xD = "_header_link_n0pj7_14"
  , bD = "_close_today_n0pj7_22"
  , Gu = {
    header: yD,
    fadeIn: wD,
    header_link: xD,
    close_today: bD
}
  , SD = () => {
    const e = () => {
        he("EvtPageGoHome", "EvtPageDone", void 0, 2),
        he("evtCode", "20240807", void 0, 2)
    }
      , t = () => {
        he("EvtPage", "EvtPageDone", 1),
        he("evtCode", "20240417", 1),
        window.location.href = "https://mir2.mironline.co.kr/main"
    }
    ;
    return u.jsxs("header", {
        className: Gu.header,
        children: [u.jsx("h1", {
            children: u.jsx("a", {
                href: "https://mir2.mironline.co.kr/main",
                className: Gu.header_link,
                onClick: e,
                children: u.jsx("img", {
                    src: "https://web-cdn.mironline.co.kr/mir2/2024_event/0717/images/logo.png",
                    alt: "bi"
                })
            })
        }), u.jsx("div", {
            className: Gu.close_today,
            children: u.jsx("button", {
                type: "button",
                onClick: t,
                children: "오늘 하루 그만 보기"
            })
        })]
    })
}
  , CD = "_story_wrapper_vqfq8_1"
  , kD = "_observer_wrapper_vqfq8_9"
  , ED = "_observe_on_vqfq8_13"
  , TD = "_chieftain_vqfq8_13"
  , PD = "_fadeUpCharactor_vqfq8_1"
  , jD = "_story_frame_vqfq8_16"
  , ND = "_fadeDownCharactor_vqfq8_1"
  , RD = "_restart_vqfq8_42"
  , ID = "_status_vqfq8_51"
  , LD = "_text_wrapper_vqfq8_56"
  , MD = "_speech_text_vqfq8_63"
  , OD = "_first_vqfq8_67"
  , AD = "_fadeOut_vqfq8_1"
  , DD = "_fadeIn_vqfq8_1"
  , $D = "_second_vqfq8_109"
  , FD = "_third_vqfq8_153"
  , VD = "_fourth_vqfq8_201"
  , Ft = {
    story_wrapper: CD,
    observer_wrapper: kD,
    observe_on: ED,
    chieftain: TD,
    fadeUpCharactor: PD,
    story_frame: jD,
    fadeDownCharactor: ND,
    restart: RD,
    status: ID,
    text_wrapper: LD,
    speech_text: MD,
    first: OD,
    fadeOut: AD,
    fadeIn: DD,
    second: $D,
    third: FD,
    fourth: VD
}
  , zD = ({sectionRef: e}) => {
    const {setNowSection: t} = g.useContext(cn)
      , n = g.useRef(null)
      , r = Ie(n, {
        threshold: .3
    })
      , [i,s] = g.useState(!1)
      , [o,l] = g.useState(!1)
      , [a,c] = g.useState(!1)
      , d = [{
        className: Ft.first,
        count: 6
    }, {
        className: Ft.second,
        count: 6
    }, {
        className: Ft.third,
        count: 7
    }, {
        className: Ft.fourth,
        count: 3
    }]
      , f = () => {
        s(!1),
        l(!1),
        setTimeout( () => {
            s(!0)
        }
        , 100),
        setTimeout( () => {
            l(!0)
        }
        , 25e3)
    }
    ;
    return g.useEffect( () => {
        r && (t("dogyo|story"),
        c(!0)),
        r && !i && (s(!0),
        setTimeout( () => {
            l(!0)
        }
        , 25e3))
    }
    , [r]),
    u.jsx("section", {
        className: Ft.story_wrapper,
        ref: e,
        children: u.jsxs("div", {
            className: `${Ft.observer_wrapper} ${a && Ft.observe_on}`,
            ref: n,
            children: [u.jsx("div", {
                className: Ft.chieftain
            }), u.jsx("div", {
                className: Ft.story_frame
            }), d.map( ({className: p, count: h}, m) => u.jsx("div", {
                className: `${Ft.text_wrapper} ${i && p}`,
                children: Array.from({
                    length: h
                }, (_, x) => u.jsx("div", {
                    className: Ft.speech_text
                }, x))
            }, m)), u.jsx("button", {
                type: "button",
                className: `${Ft.restart} ${o && Ft.status}`,
                onClick: f,
                children: u.jsx("img", {
                    src: "https://web-cdn.mironline.co.kr/mir2/2024_event/0807/images/story-restart.png",
                    alt: "재시작"
                })
            })]
        })
    })
}
  , BD = "_container_1chts_1"
  , UD = "_main_ul_1chts_10"
  , HD = "_battle_1chts_17"
  , GD = "_sub_ul_1chts_29"
  , WD = "_sub_on_1chts_48"
  , qD = "_on_1chts_55"
  , YD = "_off_1chts_70"
  , KD = "_sub_off_1chts_98"
  , cr = {
    container: BD,
    main_ul: UD,
    battle: HD,
    sub_ul: GD,
    sub_on: WD,
    on: qD,
    off: YD,
    sub_off: KD
}
  , XD = () => {
    const {nowSection: e, setNavClickSection: t} = g.useContext(cn)
      , n = i => {
        i.stopPropagation();
        const {type: s, main: o, sub: l} = i.currentTarget.dataset
          , a = String(s === "main" ? o : l);
        t(a === "dogyo" ? "dogyo|story" : a)
    }
      , r = [{
        id: "main",
        label: "MAIN",
        sub: []
    }, {
        id: "dogyo",
        label: "어둠의 독요현",
        sub: [{
            id: "dogyo|story",
            subLabel: "스토리"
        }, {
            id: "dogyo|town",
            subLabel: "마을 소개"
        }, {
            id: "dogyo|facility",
            subLabel: "주요 건물소개"
        }]
    }, {
        id: "open-event",
        label: "신규지역 오픈 이벤트",
        sub: []
    }, {
        id: "mana-stone",
        label: "현천마석 이벤트",
        sub: []
    }];
    return u.jsx("nav", {
        className: cr.container,
        children: u.jsx("ul", {
            className: cr.main_ul,
            children: r.map( ({id: i, label: s, sub: o}) => u.jsxs(Ce.Fragment, {
                children: [u.jsxs("button", {
                    type: "button",
                    id: i,
                    "data-type": "main",
                    "data-main": i,
                    onClick: n,
                    className: i === "battle" ? cr.battle : void 0,
                    children: [u.jsx("span", {
                        className: e.includes(i) ? cr.on : cr.off,
                        children: s
                    }), " "]
                }), u.jsx("li", {
                    children: o.length > 0 && u.jsx("ul", {
                        className: cr.sub_ul,
                        children: o.map( ({id: l, subLabel: a}) => u.jsx("li", {
                            className: e.includes(l) ? cr.sub_on : cr.sub_off,
                            children: u.jsx("button", {
                                type: "button",
                                onClick: n,
                                "data-sub": l,
                                "data-type": "sub",
                                children: a
                            })
                        }, l))
                    })
                })]
            }, i))
        })
    })
}
  , QD = "_town_wrapper_1ybuz_1"
  , JD = "_observe_on_1ybuz_6"
  , ZD = "_guard_1ybuz_6"
  , e$ = "_fadeUpSpot_1ybuz_1"
  , t$ = "_medicinal_store_1ybuz_9"
  , n$ = "_drugstore_1ybuz_12"
  , r$ = "_variety_store_1ybuz_15"
  , i$ = "_storage_1ybuz_18"
  , s$ = "_factory_1ybuz_21"
  , o$ = "_smithy_1ybuz_24"
  , a$ = "_spot_item_1ybuz_27"
  , l$ = "_main_button_1ybuz_30"
  , c$ = "_smelt_1ybuz_30"
  , u$ = "_field_1ybuz_33"
  , d$ = "_mine_1ybuz_36"
  , f$ = "_dogyo_map_1ybuz_40"
  , p$ = "_on_1ybuz_46"
  , m$ = "_button_wrapper_1ybuz_50"
  , h$ = "_focus_1ybuz_56"
  , g$ = "_observing_1ybuz_125"
  , _$ = "_sub_button_1ybuz_130"
  , v$ = "_main_spot_list_1ybuz_237"
  , Qe = {
    town_wrapper: QD,
    observe_on: JD,
    guard: ZD,
    fadeUpSpot: e$,
    medicinal_store: t$,
    drugstore: n$,
    variety_store: r$,
    storage: i$,
    factory: s$,
    smithy: o$,
    spot_item: a$,
    main_button: l$,
    smelt: c$,
    field: u$,
    mine: d$,
    dogyo_map: f$,
    on: p$,
    button_wrapper: m$,
    focus: h$,
    observing: g$,
    sub_button: _$,
    main_spot_list: v$
}
  , y$ = ({sectionRef: e}) => {
    const {setNowSection: t, scaleState: n, setScaleState: r, setMainModalState: i, setVillageModalState: s, setCurrentTab: o} = g.useContext(cn)
      , l = g.useRef(null)
      , a = g.useRef(null)
      , [c,d] = g.useState(!1)
      , f = {
        default: {
            posX: 0,
            posY: 0,
            scale: 1
        },
        smelt: {
            posX: -360,
            posY: 80,
            scale: 2
        },
        field: {
            posX: -655,
            posY: -335,
            scale: 2
        },
        mine: {
            posX: -732,
            posY: 435,
            scale: 2
        }
    }
      , p = {
        smithy: "smithy",
        factory: "factory",
        storage: "storage",
        varietyStore: "variety_store",
        drugstore: "drugstore",
        medicinalStore: "medicinal_store",
        guard: "guard"
    }
      , h = _ => {
        r(x => ({
            ...x,
            currentSpot: _,
            focus: !0
        })),
        setTimeout( () => {
            _ !== "default" && (i(!0),
            o(_))
        }
        , 1e3)
    }
    ;
    g.useEffect( () => {
        var _, x, v;
        a.current && (a.current.style.transform = `translate(${(_ = f[n.currentSpot]) == null ? void 0 : _.posX}px, ${(x = f[n.currentSpot]) == null ? void 0 : x.posY}px) scale(${(v = f[n.currentSpot]) == null ? void 0 : v.scale})`)
    }
    , [n]);
    const m = Ie(l, {
        threshold: .3
    });
    return g.useEffect( () => {
        m && (t("dogyo|town"),
        d(!0))
    }
    , [m]),
    u.jsx("section", {
        className: `${Qe.town_wrapper} ${c && Qe.observe_on}`,
        ref: e,
        children: u.jsxs("div", {
            ref: l,
            className: Qe.observing,
            children: [u.jsx("div", {
                className: Qe.dogyo_map,
                ref: a,
                children: u.jsx("video", {
                    autoPlay: !0,
                    muted: !0,
                    loop: !0,
                    className: Qe.map_video,
                    poster: "https://web-cdn.mironline.co.kr/mir2/2024_event/0807/images/village.jpg",
                    children: u.jsx("source", {
                        src: "https://web-cdn.mironline.co.kr/mir2/2024_event/0807/videos/village.mp4",
                        type: "video/mp4"
                    })
                })
            }), u.jsxs("div", {
                className: n.focus ? `${Qe.button_wrapper} ${Qe.focus}` : Qe.button_wrapper,
                children: [u.jsx("button", {
                    type: "button",
                    className: `${Qe.main_button} ${Qe.smelt}`,
                    onClick: () => h("smelt")
                }), u.jsx("button", {
                    type: "button",
                    className: `${Qe.main_button} ${Qe.field}`,
                    onClick: () => h("field")
                }), u.jsx("button", {
                    type: "button",
                    className: `${Qe.main_button} ${Qe.mine}`,
                    onClick: () => h("mine")
                }), Object.keys(p).map( (_, x) => u.jsx("button", {
                    type: "button",
                    className: `${Qe.sub_button} ${Qe[p[_]]}`,
                    onClick: () => s(_)
                }, x)), u.jsxs("ul", {
                    className: Qe.main_spot_list,
                    children: [u.jsx("li", {
                        className: Qe.spot_item,
                        children: u.jsx("button", {
                            type: "button",
                            onClick: () => h("smelt"),
                            children: u.jsx("img", {
                                src: "https://web-cdn.mironline.co.kr/mir2/2024_event/0807/images/spot-item-smelt.png",
                                alt: "smelt"
                            })
                        })
                    }), u.jsx("li", {
                        className: Qe.spot_item,
                        children: u.jsx("button", {
                            type: "button",
                            onClick: () => h("field"),
                            children: u.jsx("img", {
                                src: "https://web-cdn.mironline.co.kr/mir2/2024_event/0807/images/spot-item-field.png",
                                alt: "field"
                            })
                        })
                    }), u.jsx("li", {
                        className: Qe.spot_item,
                        children: u.jsx("button", {
                            type: "button",
                            onClick: () => h("mine"),
                            children: u.jsx("img", {
                                src: "https://web-cdn.mironline.co.kr/mir2/2024_event/0807/images/spot-item-mine.png",
                                alt: "mine"
                            })
                        })
                    })]
                })]
            })]
        })
    })
}
  , w$ = "_facility_wrapper_1jm9a_1"
  , x$ = "_tab_wrapper_1jm9a_5"
  , b$ = "_active_1jm9a_18"
  , S$ = "_facility_list_1jm9a_37"
  , C$ = "_facility_item_1jm9a_42"
  , Zt = {
    facility_wrapper: w$,
    tab_wrapper: x$,
    active: b$,
    facility_list: S$,
    facility_item: C$
}
  , zx = () => {
    const e = "https://web-cdn.mironline.co.kr/mir2/2024_event/0807/images"
      , t = {
        smelt: `${e}/intro-smelt.jpg`,
        field: `${e}/intro-field.jpg`,
        mine: `${e}/intro-mine.jpg`
    }
      , {currentTab: n, setCurrentTab: r} = g.useContext(cn);
    return u.jsxs("div", {
        className: Zt.facility_wrapper,
        children: [u.jsxs("div", {
            className: Zt.tab_wrapper,
            children: [u.jsx("button", {
                type: "button",
                className: n === "smelt" ? `${Zt.tab} ${Zt.active}` : Zt.tab,
                onClick: () => r("smelt"),
                children: u.jsx("img", {
                    src: "https://web-cdn.mironline.co.kr/mir2/2024_event/0807/images/tab-smelt.png",
                    alt: "smelt"
                })
            }), u.jsx("button", {
                type: "button",
                className: n === "field" ? `${Zt.tab} ${Zt.active}` : Zt.tab,
                onClick: () => r("field"),
                children: u.jsx("img", {
                    src: "https://web-cdn.mironline.co.kr/mir2/2024_event/0807/images/tab-field.png",
                    alt: "field"
                })
            }), u.jsx("button", {
                type: "button",
                className: n === "mine" ? `${Zt.tab} ${Zt.active}` : Zt.tab,
                onClick: () => r("mine"),
                children: u.jsx("img", {
                    src: "https://web-cdn.mironline.co.kr/mir2/2024_event/0807/images/tab-mine.png",
                    alt: "mine"
                })
            })]
        }), u.jsx("div", {
            className: Zt.facility_list,
            children: u.jsx("div", {
                children: u.jsx("img", {
                    src: t[n],
                    alt: "village"
                })
            })
        })]
    })
}
  , k$ = "_modal_wrapper_vg1lw_1"
  , E$ = "_fade_in_vg1lw_14"
  , T$ = "_fadeIn_vg1lw_1"
  , P$ = "_fade_out_vg1lw_17"
  , j$ = "_fadeOut_vg1lw_1"
  , N$ = "_close_button_vg1lw_22"
  , R$ = "_modal_contents_vg1lw_29"
  , I$ = "_modal_top_vg1lw_32"
  , L$ = "_village_wrapper_vg1lw_38"
  , M$ = "_overlay_vg1lw_54"
  , O$ = "_village_close_button_vg1lw_64"
  , A$ = "_village_contents_vg1lw_69"
  , D$ = "_image_wrapper_vg1lw_77"
  , Tt = {
    modal_wrapper: k$,
    fade_in: E$,
    fadeIn: T$,
    fade_out: P$,
    fadeOut: j$,
    close_button: N$,
    modal_contents: R$,
    modal_top: I$,
    village_wrapper: L$,
    overlay: M$,
    village_close_button: O$,
    village_contents: A$,
    image_wrapper: D$
}
  , $$ = () => {
    const {setScaleState: e, setMainModalState: t} = g.useContext(cn)
      , n = g.useRef(null)
      , [r,i] = g.useState(!1)
      , [s,o] = g.useState(!1)
      , l = () => {
        n.current && n.current.scrollTo({
            top: 0,
            behavior: "smooth"
        })
    }
      , a = () => {
        i(!1),
        setTimeout( () => {
            t(!1)
        }
        , 500),
        e(c => ({
            ...c,
            currentSpot: "default"
        })),
        setTimeout( () => {
            e(c => ({
                ...c,
                currentSpot: "default",
                focus: !1
            }))
        }
        , 1100)
    }
    ;
    return g.useEffect( () => {
        i(!0)
    }
    , []),
    g.useEffect( () => {
        if (!n.current)
            return;
        const c = () => {
            n.current && (n.current.scrollTop > 0 ? o(!0) : o(!1))
        }
        ;
        n.current.addEventListener("scroll", c)
    }
    , []),
    u.jsxs("div", {
        className: `${Tt.modal_wrapper} ${r ? Tt.fade_in : Tt.fade_out}`,
        ref: n,
        children: [u.jsx("button", {
            type: "button",
            onClick: a,
            className: Tt.close_button,
            children: u.jsx("img", {
                src: "https://web-cdn.mironline.co.kr/mir2/2024_event/0807/images/modal-back.png",
                alt: "back"
            })
        }), u.jsxs("div", {
            className: Tt.modal_contents,
            children: [u.jsx(zx, {}), s && u.jsx("button", {
                type: "button",
                className: Tt.modal_top,
                onClick: l,
                children: u.jsx("img", {
                    src: "https://web-cdn.mironline.co.kr/mir2/2024_event/0807/images/modal-top.png",
                    alt: "back"
                })
            })]
        })]
    })
}
  , F$ = ({sectionRef: e}) => {
    const {setNowSection: t} = g.useContext(cn)
      , n = g.useRef(null)
      , r = Ie(n, {
        threshold: .2
    });
    return g.useEffect( () => {
        r && t("dogyo|facility")
    }
    , [r]),
    u.jsx("section", {
        ref: e,
        children: u.jsx("div", {
            ref: n,
            children: u.jsx(zx, {})
        })
    })
}
  , V$ = () => {
    const {villageModalState: e, setVillageModalState: t} = g.useContext(cn)
      , [n,r] = g.useState(!0)
      , i = "https://web-cdn.mironline.co.kr/mir2/2024_event/0807/images"
      , s = {
        smithy: `${i}/popup-smithy.png`,
        factory: `${i}/popup-factory.png`,
        storage: `${i}/popup-storage.png`,
        varietyStore: `${i}/popup-variety-store.png`,
        drugstore: `${i}/popup-drugstore.png`,
        medicinalStore: `${i}/popup-medicinal-store.png`,
        guard: `${i}/popup-guard.png`
    }
      , o = () => {
        r(!1),
        setTimeout( () => {
            t("")
        }
        , 500)
    }
    ;
    return u.jsxs("div", {
        className: n ? `${Tt.village_wrapper} ${Tt.fade_in}` : `${Tt.village_wrapper} ${Tt.fade_out}`,
        children: [u.jsx("button", {
            className: Tt.overlay,
            onClick: o,
            type: "button"
        }), u.jsxs("div", {
            className: Tt.village_contents,
            children: [u.jsx("button", {
                type: "button",
                className: Tt.village_close_button,
                onClick: o,
                children: u.jsx("img", {
                    src: `${i}/popup-close-btn.png`,
                    alt: "closed"
                })
            }), u.jsx("div", {
                className: Tt.image_wrapper,
                children: u.jsx("img", {
                    src: s[e],
                    alt: e
                })
            })]
        })]
    })
}
  , z$ = "_open_event_wrapper_3qjwc_1"
  , B$ = "_open_event_inner_3qjwc_6"
  , U$ = "_open_event_first_3qjwc_11"
  , H$ = "_observe_on_3qjwc_15"
  , G$ = "_fadeUp_3qjwc_1"
  , W$ = "_open_event_second_3qjwc_19"
  , q$ = "_open_event_third_3qjwc_28"
  , ur = {
    open_event_wrapper: z$,
    open_event_inner: B$,
    open_event_first: U$,
    observe_on: H$,
    fadeUp: G$,
    open_event_second: W$,
    open_event_third: q$
}
  , Y$ = ({sectionRef: e}) => {
    const {setNowSection: t} = g.useContext(cn)
      , [n,r] = g.useState({
        first: !1,
        second: !1,
        third: !1
    })
      , i = g.useRef(null)
      , s = g.useRef(null)
      , o = g.useRef(null)
      , l = g.useRef(null)
      , a = Ie(s, {
        threshold: .3
    })
      , c = Ie(s, {
        threshold: .3
    })
      , d = Ie(o, {
        threshold: .3
    })
      , f = Ie(l, {
        threshold: .3
    });
    return g.useEffect( () => {
        a && t("open-event")
    }
    , [a]),
    g.useEffect( () => {
        c && (r(p => ({
            ...p,
            first: !0
        })),
        t("open-event"))
    }
    , [c]),
    g.useEffect( () => {
        d && (r(p => ({
            ...p,
            second: !0
        })),
        t("open-event"))
    }
    , [d]),
    g.useEffect( () => {
        f && (r(p => ({
            ...p,
            third: !0
        })),
        t("open-event"))
    }
    , [f]),
    u.jsx("section", {
        className: ur.open_event_wrapper,
        ref: e,
        children: u.jsxs("div", {
            ref: i,
            className: ur.open_event_inner,
            children: [u.jsx("div", {
                className: `${ur.open_event_first} ${n.first && ur.observe_on}`,
                ref: s,
                children: u.jsx("img", {
                    src: "https://web-cdn.mironline.co.kr/mir2/2024_event/0807/images/open-event-01.png",
                    alt: "event-01"
                })
            }), u.jsx("div", {
                className: `${ur.open_event_second} ${n.second && ur.observe_on}`,
                ref: o,
                children: u.jsx("img", {
                    src: "https://web-cdn.mironline.co.kr/mir2/2024_event/0807/images/open-event-02.png",
                    alt: "event-02"
                })
            }), u.jsx("div", {
                className: `${ur.open_event_third} ${n.third && ur.observe_on}`,
                ref: l,
                children: u.jsx("img", {
                    src: "https://web-cdn.mironline.co.kr/mir2/2024_event/0807/images/open-event-03.png",
                    alt: "event-03"
                })
            })]
        })
    })
}
  , K$ = "_mana_stone_wrapper_17pzx_1"
  , X$ = "_mana_stone_inner_17pzx_5"
  , Q$ = "_mana_stone_first_17pzx_10"
  , J$ = "_observe_on_17pzx_15"
  , Z$ = "_fadeUp_17pzx_1"
  , eF = "_mana_stone_second_17pzx_19"
  , tF = "_unlogin_17pzx_31"
  , nF = "_userid_17pzx_39"
  , rF = "_password_17pzx_44"
  , iF = "_login_wrapper_17pzx_49"
  , sF = "_login_button_17pzx_54"
  , oF = "_userid_input_17pzx_62"
  , aF = "_on_login_17pzx_70"
  , lF = "_on_login_left_17pzx_80"
  , cF = "_charactor_wrapper_17pzx_84"
  , uF = "_server_name_17pzx_88"
  , dF = "_charactor_name_17pzx_98"
  , fF = "_change_charactor_17pzx_107"
  , pF = "_change_charactor_button_17pzx_110"
  , mF = "_on_login_right_17pzx_117"
  , hF = "_current_point_17pzx_121"
  , gF = "_usage_detail_17pzx_131"
  , _F = "_usage_detail_button_17pzx_135"
  , vF = "_mana_stone_third_17pzx_143"
  , yF = "_exchange_list_17pzx_154"
  , wF = "_exchange_item_17pzx_159"
  , xF = "_exchange_item_image_17pzx_165"
  , bF = "_exchange_item_content_17pzx_173"
  , SF = "_exchange_item_name_17pzx_179"
  , CF = "_exchange_item_price_17pzx_199"
  , kF = "_exchange_item_button_17pzx_205"
  , EF = "_exchange_limit_17pzx_219"
  , TF = "_mana_stone_fourth_17pzx_227"
  , PF = "_draw_wrapper_17pzx_238"
  , jF = "_draw_image_wrapper_17pzx_241"
  , NF = "_draw_button_wrapper_17pzx_256"
  , RF = "_draw_button_17pzx_256"
  , IF = "_product_random_list_17pzx_271"
  , LF = "_product_random_item_wrap_17pzx_276"
  , MF = "_product_random_item_name_17pzx_288"
  , OF = "_product_random_item_image_17pzx_299"
  , AF = "_product_random_item_detail_17pzx_307"
  , DF = "_product_random_item_block_17pzx_316"
  , $F = "_test_17pzx_322"
  , ne = {
    mana_stone_wrapper: K$,
    mana_stone_inner: X$,
    mana_stone_first: Q$,
    observe_on: J$,
    fadeUp: Z$,
    mana_stone_second: eF,
    unlogin: tF,
    userid: nF,
    password: rF,
    login_wrapper: iF,
    login_button: sF,
    userid_input: oF,
    on_login: aF,
    on_login_left: lF,
    charactor_wrapper: cF,
    server_name: uF,
    charactor_name: dF,
    change_charactor: fF,
    change_charactor_button: pF,
    on_login_right: mF,
    current_point: hF,
    usage_detail: gF,
    usage_detail_button: _F,
    mana_stone_third: vF,
    exchange_list: yF,
    exchange_item: wF,
    exchange_item_image: xF,
    exchange_item_content: bF,
    exchange_item_name: SF,
    exchange_item_price: CF,
    exchange_item_button: kF,
    exchange_limit: EF,
    mana_stone_fourth: TF,
    draw_wrapper: PF,
    draw_image_wrapper: jF,
    draw_button_wrapper: NF,
    draw_button: RF,
    product_random_list: IF,
    product_random_item_wrap: LF,
    product_random_item_name: MF,
    product_random_item_image: OF,
    product_random_item_detail: AF,
    product_random_item_block: DF,
    test: $F
};
function Wu(e) {
    return Ke.post("/event/promotion/getPoint", e)
}
function FF(e) {
    return Ke.post("/event/promotion/itemSwap", e)
}
function VF(e) {
    return Ke.post("/event/promotion/itemRandom", e)
}
const zF = "/assets/draw-effect-3abf1356.png"
  , BF = "_modal_wrap_97phk_1"
  , UF = "_modal_bg_wrap_97phk_13"
  , HF = "_modal_detail_97phk_20"
  , GF = "_modal_close_button_97phk_42"
  , Wa = {
    modal_wrap: BF,
    modal_bg_wrap: UF,
    modal_detail: HF,
    modal_close_button: GF
}
  , WF = ({productImage: e, productName: t, setIsModalOpen: n, setGetItemRandomClick: r}) => {
    g.useEffect( () => {
        r(!1)
    }
    , []),
    Bo();
    const i = () => {
        n(!1),
        r(!1)
    }
    ;
    return u.jsx("div", {
        className: Wa.modal_wrap,
        children: u.jsxs("div", {
            className: Wa.modal_bg_wrap,
            children: [u.jsx("img", {
                src: "https://web-cdn.mironline.co.kr/mir3/2018_event/0725/images/get_popup.png",
                alt: "popup"
            }), u.jsxs("div", {
                className: Wa.modal_detail,
                children: [u.jsx("img", {
                    src: "https://upload.mironline.co.kr/" + e,
                    alt: t
                }), u.jsx("span", {
                    children: t
                })]
            }), u.jsx("button", {
                className: Wa.modal_close_button,
                type: "button",
                onClick: i
            })]
        })
    })
}
  , qF = ({sectionRef: e}) => {
    um();
    const {setNowSection: t} = g.useContext(cn)
      , n = g.useRef(null)
      , r = g.useRef(null)
      , i = g.useRef(null)
      , s = g.useRef(null)
      , o = g.useRef(null)
      , [l,a] = g.useState(!1)
      , [c,d] = g.useState(!1)
      , [f,p] = g.useState(!1)
      , [h,m] = g.useState(!1)
      , [_,x] = g.useState(!1)
      , [v,w] = g.useState(!1)
      , [y,b] = g.useState(!1)
      , [C,S] = g.useState({
        productImage: "",
        productName: ""
    })
      , [T,k] = g.useState({
        first: !1,
        second: !1,
        third: !1,
        fourth: !1
    })
      , P = Ie(r, {
        threshold: .2
    })
      , R = Ie(r, {
        threshold: .3
    })
      , A = Ie(i, {
        threshold: .3
    })
      , V = Ie(s, {
        threshold: .3
    })
      , F = Ie(o, {
        threshold: .3
    })
      , U = 2
      , Q = Ei("MIR_Info")
      , Z = "76"
      , L = "77"
      , {swapProduct: N, setSwapProduct: $, randomProduct: O, setRandomProduct: B} = g.useContext(aa)
      , {userInfo: H, setUserInfo: ue, isFunctionOpen: de, userPoint: xe, setUserPoint: ge} = g.useContext(Ti)
      , wt = {
        4: "1일 4회 제한",
        3: "1일 3회 제한",
        2: "1일 2회 제한",
        1: "1일 1회 제한",
        0: "제한없음"
    }
      , [un,Mt] = g.useState([])
      , [ot,ft] = g.useState({
        userId: "",
        pw: ""
    })
      , Be = q => Math.ceil(q / 6) * 6
      , Ot = async () => {
        const q = {
            gameCode: U,
            auth: {
                cusrVal: Q
            }
        }
          , {code: re, point: {totalPoint: Oe}} = await Wu(q);
        re === "200" && ge(Oe)
    }
      , Gr = q => {
        const {name: re, value: Oe} = q.target;
        ft(Ue => ({
            ...Ue,
            [re]: Oe
        }))
    }
      , Pi = async q => {
        if (q.preventDefault(),
        !ot.userId) {
            alert("아이디를 입력해 주세요.");
            return
        }
        if (!ot.pw) {
            alert("비밀번호를 입력해 주세요.");
            return
        }
        if (!ot.userId || !ot.pw) {
            alert("아이디 또는 비밀번호를 확인하세요");
            return
        }
        const re = {
            auth: {
                gameCode: U,
                userId: ot.userId,
                pw: ot.pw
            }
        }
          , {code: Oe, errorMessage: Ue, cusrVal: pt, memCharacter: kn, memCharacterYn: Dn, memServer: Qt, memServerCode: At, nickName: la, nickYn: ji} = await am(re);
        switch (Oe) {
        case "200":
            ue({
                errorMessage: Ue,
                cusrVal: pt,
                memCharacter: kn,
                memCharacterYn: Dn,
                memServer: Qt,
                memServerCode: At,
                nickName: la,
                nickYn: ji
            }),
            $(await Mr({
                gameCode: U,
                groupIdx: Z,
                auth: {
                    cusrVal: pt
                }
            })),
            he("MIR%5FInfo", pt);
            break;
        default:
            alert(Ue);
            break
        }
        const $c = {
            gameCode: U,
            auth: {
                cusrVal: pt
            }
        }
          , {code: En, point: {totalPoint: ca}} = await Wu($c);
        En === "200" && ge(ca)
    }
      , dn = ms(async q => {
        const re = {
            gameCode: U,
            auth: {
                cusrVal: H.cusrVal
            },
            groupIdx: Z,
            productIdx: q.target.id
        }
          , {code: Oe, errorMessage: Ue, product: pt} = await FF(re);
        switch (Oe) {
        case "200":
            if (N.productList.filter(kn => kn.productIdx === Number(q.target.id))[0].plimitedUseNum !== 0) {
                const kn = N.productList.findIndex(Dn => Dn.productIdx === Number(q.target.id));
                N.productList[kn] = pt
            }
            alert("아이템 교환이 완료되었습니다.");
            break;
        default:
            alert(Ue);
            break
        }
        await Ot()
    }
    , 300)
      , ks = async () => {
        if (c || f)
            return;
        const q = {
            gameCode: U,
            auth: {
                cusrVal: H.cusrVal
            },
            groupIdx: L
        }
          , {code: re, product: Oe, errorMessage: Ue} = await VF(q);
        switch (re) {
        case "200":
            d(!0),
            p(!0),
            a(!0),
            S({
                productImage: Oe.productImage,
                productName: Oe.productName
            }),
            setTimeout( () => {
                d(pt => !pt),
                m(!0)
            }
            , 550),
            setTimeout( () => {
                p(!1)
            }
            , 900);
            break;
        default:
            alert(Ue);
            break
        }
        await Ot()
    }
      , Es = () => {
        Q && b(!0)
    }
      , ae = () => {
        Q && (H.nickName ? x(!0) : w(!0))
    }
      , Me = (q, re) => de === "Y" && Q ? q !== 0 && q - re === 0 : !0;
    return g.useEffect( () => {
        R && (k(q => ({
            ...q,
            first: !0
        })),
        t("mana-stone"))
    }
    , [R]),
    g.useEffect( () => {
        A && (k(q => ({
            ...q,
            second: !0
        })),
        t("mana-stone"))
    }
    , [A]),
    g.useEffect( () => {
        V && (k(q => ({
            ...q,
            third: !0
        })),
        t("mana-stone"))
    }
    , [V]),
    g.useEffect( () => {
        F && (k(q => ({
            ...q,
            fourth: !0
        })),
        t("mana-stone"))
    }
    , [F]),
    g.useEffect( () => {
        (async () => {
            const q = Mr({
                gameCode: U,
                groupIdx: Z,
                auth: {
                    cusrVal: Q
                },
                viewDateUseYn: "N"
            })
              , re = Mr({
                gameCode: U,
                groupIdx: L,
                viewDateUseYn: "N"
            })
              , [Oe,Ue] = await Promise.all([q, re]);
            $(Oe),
            B(Ue)
        }
        )()
    }
    , []),
    g.useEffect( () => {
        Q && (async () => {
            const q = Oc({
                gameCode: U,
                auth: {
                    cusrVal: Q
                }
            })
              , re = Wu({
                gameCode: U,
                auth: {
                    cusrVal: Q
                }
            })
              , [Oe,Ue] = await Promise.all([q, re]);
            ge(Ue.point.totalPoint),
            ue(Oe)
        }
        )()
    }
    , [Q]),
    g.useEffect( () => {
        const q = new Array(Be(O.productList.length))
          , re = [...O.productList, ...Array(q.length - O.productList.length).fill(null)];
        Mt(re)
    }
    , [O]),
    g.useEffect( () => {
        P && t("mana-stone")
    }
    , [P]),
    u.jsxs("section", {
        className: ne.mana_stone_wrapper,
        ref: e,
        children: [u.jsxs("div", {
            ref: n,
            className: ne.mana_stone_inner,
            children: [u.jsx("div", {
                className: `${ne.mana_stone_first} ${T.first && ne.observe_on}`,
                ref: r,
                children: u.jsx("img", {
                    src: "https://web-cdn.mironline.co.kr/mir2/2024_event/0807/images/manastone-event-01.png",
                    alt: "event-01"
                })
            }), u.jsx("div", {
                className: `${ne.mana_stone_second} ${T.second && ne.observe_on}`,
                ref: i,
                children: Q ? u.jsxs("div", {
                    className: ne.on_login,
                    children: [u.jsxs("div", {
                        className: ne.on_login_left,
                        children: [u.jsxs("div", {
                            className: ne.charactor_wrapper,
                            children: [u.jsx("div", {
                                className: ne.server_name,
                                children: H.memServer
                            }), u.jsx("div", {
                                className: ne.charactor_name,
                                children: H.memCharacter
                            })]
                        }), u.jsx("div", {
                            className: ne.change_charactor,
                            children: u.jsx("button", {
                                type: "button",
                                className: ne.change_charactor_button,
                                onClick: ae,
                                children: "변경"
                            })
                        })]
                    }), u.jsxs("div", {
                        className: ne.on_login_right,
                        children: [u.jsx("div", {
                            className: ne.current_point,
                            children: xe.toLocaleString("ko-KR")
                        }), u.jsx("div", {
                            className: ne.usage_detail,
                            children: u.jsx("button", {
                                type: "button",
                                className: ne.usage_detail_button,
                                onClick: Es,
                                children: "이용내역"
                            })
                        })]
                    })]
                }) : u.jsxs("form", {
                    className: ne.unlogin,
                    method: "POST",
                    onSubmit: Pi,
                    children: [u.jsx("div", {
                        className: ne.userid,
                        children: u.jsx("input", {
                            name: "userId",
                            type: "text",
                            placeholder: "아이디 입력",
                            className: ne.userid_input,
                            onChange: Gr
                        })
                    }), u.jsx("div", {
                        className: ne.password,
                        children: u.jsx("input", {
                            name: "pw",
                            type: "password",
                            placeholder: "비밀번호 입력",
                            className: ne.userid_input,
                            onChange: Gr
                        })
                    }), u.jsx("div", {
                        className: ne.login_wrapper,
                        children: u.jsx("button", {
                            type: "submit",
                            className: ne.login_button,
                            children: "로그인"
                        })
                    })]
                })
            }), u.jsx("div", {
                className: `${ne.mana_stone_third} ${T.third && ne.observe_on}`,
                ref: s,
                children: u.jsx("ul", {
                    className: ne.exchange_list,
                    children: N.productList && N.productList.map(q => u.jsxs("li", {
                        className: ne.exchange_item,
                        children: [u.jsx("div", {
                            className: ne.exchange_item_image,
                            children: u.jsx("img", {
                                src: `https://upload.mironline.co.kr/${q.productImage}`,
                                alt: q.productName
                            })
                        }), u.jsxs("div", {
                            className: ne.exchange_item_content,
                            children: [u.jsxs("div", {
                                children: [u.jsx("div", {
                                    className: ne.exchange_item_name,
                                    children: Number(q.productPrice).toLocaleString("ko-KR")
                                }), u.jsx("div", {
                                    className: ne.exchange_item_price,
                                    children: q.productName
                                })]
                            }), u.jsxs("div", {
                                className: ne.exchange_item_purchase,
                                children: [u.jsx("button", {
                                    type: "button",
                                    className: ne.exchange_item_button,
                                    id: String(q.productIdx),
                                    onClick: dn,
                                    disabled: Me(q.plimitedUseNum, q.userPurchaseCount),
                                    children: "아이템 교환"
                                }), u.jsx("div", {
                                    className: ne.exchange_limit,
                                    children: wt[q.plimitedUseNum]
                                })]
                            })]
                        })]
                    }, q.productIdx))
                })
            }), u.jsxs("div", {
                className: `${ne.mana_stone_fourth} ${T.fourth && ne.observe_on}`,
                ref: o,
                children: [u.jsxs("div", {
                    className: ne.draw_wrapper,
                    children: [u.jsxs("div", {
                        className: ne.draw_image_wrapper,
                        children: [u.jsx("img", {
                            className: ne.loog_image,
                            src: "https://web-cdn.mironline.co.kr/mir2/2024_event/0807/images/dragon-effect.png",
                            alt: "dragon-effect"
                        }), c && u.jsx("img", {
                            src: zF,
                            alt: "effect"
                        })]
                    }), u.jsx("div", {
                        className: ne.draw_button_wrapper,
                        children: u.jsx("button", {
                            type: "button",
                            className: ne.draw_button,
                            onClick: l ? void 0 : ks,
                            disabled: de === "N" || !Q,
                            children: u.jsx("img", {
                                src: "https://web-cdn.mironline.co.kr/mir2/2024_event/0807/images/draw-button.png",
                                alt: "draw"
                            })
                        })
                    })]
                }), u.jsx("div", {
                    className: ne.product_random_list,
                    children: un.map( (q, re) => u.jsx("div", {
                        className: ne.product_random_item_wrap,
                        children: q != null && q.productImage ? u.jsxs(u.Fragment, {
                            children: [u.jsx("div", {
                                className: ne.product_random_item_name,
                                children: q.productName
                            }), u.jsx("div", {
                                className: ne.product_random_item_image,
                                children: u.jsx("img", {
                                    src: "https://upload.mironline.co.kr/" + q.productImage,
                                    alt: q.productName
                                })
                            }), u.jsx("div", {
                                className: ne.product_random_item_detail
                            })]
                        }) : u.jsx("div", {
                            className: ne.product_random_item_block
                        })
                    }, re))
                })]
            })]
        }), h && u.jsx(vt.Consumer, {
            children: u.jsx(WF, {
                productImage: C.productImage,
                productName: C.productName,
                setIsModalOpen: m,
                setGetItemRandomClick: a
            })
        }), v && u.jsx(vt.Consumer, {
            children: u.jsx(cm, {
                setIsChangeNickNameModalOpen: w
            })
        }), _ && u.jsx(vt.Consumer, {
            children: u.jsx(lm, {
                memServer: H.memServer,
                memCharacter: H.memCharacter,
                memServerCode: H.memServerCode,
                memCharacterYn: H.memCharacterYn,
                setIsChangeModalOpen: x
            })
        }), y && u.jsx(vt.Consumer, {
            children: u.jsx(Ax, {
                setIsPointModalOpen: b
            })
        })]
    })
}
  , YF = "_main_container_1o5vx_1"
  , KF = {
    main_container: YF
}
  , XF = () => u.jsx("footer", {
    children: u.jsx("img", {
        src: "https://web-cdn.mironline.co.kr/mir2/2024_event/0807/images/footer.jpg",
        alt: "footer"
    })
})
  , QF = "_top_container_1rwei_1"
  , JF = "_bounce_1rwei_1"
  , ZF = {
    top_container: QF,
    bounce: JF
}
  , Bx = ({imageLink: e="0717/images/top", posBottom: t="50px", posRight: n="50px"}) => {
    const [r,i] = g.useState(!1)
      , s = () => {
        window.scrollTo({
            top: 0,
            behavior: "smooth"
        })
    }
    ;
    return g.useEffect( () => {
        const o = () => {
            window.scrollY > 500 ? i(!0) : i(!1)
        }
        ;
        return window.addEventListener("scroll", o),
        () => {
            window.removeEventListener("scroll", o)
        }
    }
    , []),
    u.jsx(u.Fragment, {
        children: r && u.jsx("button", {
            type: "button",
            className: ZF.top_container,
            onClick: s,
            style: {
                bottom: t,
                right: n
            },
            children: u.jsx("img", {
                src: `https://web-cdn.mironline.co.kr/mir2/2024_event/${e}.png`,
                alt: "top"
            })
        })
    })
}
  , eV = () => u.jsx("main", {
    className: KF.main_container,
    children: u.jsx(N3, {
        children: u.jsxs(sa, {
            children: [u.jsx(SD, {}), u.jsx(XD, {}), u.jsx(Si, {}), u.jsx(XF, {}), u.jsx(Bx, {
                imageLink: "0807/images/modal-top",
                posBottom: "65px",
                posRight: "3vw"
            })]
        })
    })
})
  , tV = "_video_wrapper_1b6t0_1"
  , nV = "_video_inner_1b6t0_8"
  , rV = "_video_1b6t0_1"
  , iV = "_next_section_1b6t0_30"
  , sV = "_next_button_1b6t0_37"
  , oV = "_bounce_1b6t0_1"
  , aV = "_float_button_1b6t0_59"
  , Gs = {
    video_wrapper: tV,
    video_inner: nV,
    video: rV,
    next_section: iV,
    next_button: sV,
    bounce: oV,
    float_button: aV
}
  , An = g.createContext({
    navClickSection: "",
    setNavClickSection: () => {}
    ,
    introVideoRef: {
        current: null
    },
    nowSection: "main",
    setNowSection: () => {}
    ,
    scaleState: {
        currentSpot: "default",
        focus: !1
    },
    setScaleState: () => {}
    ,
    mainModalState: !1,
    setMainModalState: () => {}
    ,
    currentTab: "mine",
    setCurrentTab: () => {}
    ,
    classSkill: "warrior",
    setClassSkill: () => {}
    ,
    skillModalStatus: {
        currentClass: "warrior",
        skillSeq: "1",
        state: !1
    },
    setSkillModalStatus: () => {}
})
  , lV = ({children: e}) => {
    const t = g.useRef(null)
      , [n,r] = g.useState("")
      , [i,s] = g.useState("main")
      , [o,l] = g.useState({
        currentSpot: "default",
        focus: !1
    })
      , [a,c] = g.useState(!1)
      , [d,f] = g.useState("mine")
      , [p,h] = g.useState("warrior")
      , [m,_] = g.useState({
        currentClass: "warrior",
        skillSeq: "1",
        state: !1
    });
    return u.jsx(An.Provider, {
        value: {
            introVideoRef: t,
            nowSection: i,
            setNowSection: s,
            navClickSection: n,
            setNavClickSection: r,
            scaleState: o,
            setScaleState: l,
            mainModalState: a,
            setMainModalState: c,
            currentTab: d,
            setCurrentTab: f,
            classSkill: p,
            setClassSkill: h,
            skillModalStatus: m,
            setSkillModalStatus: _
        },
        children: e
    })
}
  , cV = ({sectionRef: e}) => {
    const {introVideoRef: t, setNowSection: n, setNavClickSection: r} = g.useContext(An)
      , i = g.useRef(null);
    g.useEffect( () => {
        const o = t.current;
        if (!o)
            return;
        let l;
        const a = () => {
            o.currentTime >= 14 && (o.currentTime = 4),
            l = requestAnimationFrame(a)
        }
        ;
        return a(),
        () => {
            cancelAnimationFrame(l)
        }
    }
    , []);
    const s = Ie(i);
    return g.useEffect( () => {
        s && n("main")
    }
    , [s]),
    u.jsx("section", {
        className: Gs.video_wrapper,
        ref: e,
        children: u.jsxs("div", {
            ref: i,
            className: Gs.video_inner,
            children: [u.jsx("video", {
                className: Gs.video,
                autoPlay: !0,
                muted: !0,
                ref: t,
                poster: "https://web-cdn.mironline.co.kr/mir2/2024_event/0904/images/thumbnail.jpg",
                children: u.jsx("source", {
                    src: "https://web-cdn.mironline.co.kr/mir2/2024_event/0904/videos/intro.mp4"
                })
            }), u.jsx("div", {
                className: Gs.next_section,
                children: u.jsx("button", {
                    type: "button",
                    className: Gs.next_button,
                    onClick: () => r("town"),
                    children: "SCROLL DOWN"
                })
            })]
        })
    })
}
  , uV = "_header_1fzwr_1"
  , dV = "_fadeIn_1fzwr_1"
  , fV = "_header_link_1fzwr_14"
  , pV = "_close_today_1fzwr_22"
  , qu = {
    header: uV,
    fadeIn: dV,
    header_link: fV,
    close_today: pV
}
  , mV = () => {
    const e = () => {
        he("EvtPageGoHome", "EvtPageDone", void 0, 2),
        he("evtCode", "20240904", void 0, 2)
    }
      , t = () => {
        he("EvtPage", "EvtPageDone", 1),
        he("evtCode", "20240904", 1),
        window.location.href = "https://mir2.mironline.co.kr/main"
    }
    ;
    return u.jsxs("header", {
        className: qu.header,
        children: [u.jsx("h1", {
            children: u.jsx("a", {
                href: "https://mir2.mironline.co.kr/main",
                className: qu.header_link,
                onClick: e,
                children: u.jsx("img", {
                    src: "https://web-cdn.mironline.co.kr/mir2/2024_event/0717/images/logo.png",
                    alt: "bi"
                })
            })
        }), u.jsx("div", {
            className: qu.close_today,
            children: u.jsx("button", {
                type: "button",
                onClick: t,
                children: "오늘 하루 그만 보기"
            })
        })]
    })
}
  , hV = "_container_56k5l_1"
  , gV = "_main_ul_56k5l_10"
  , _V = "_battle_56k5l_17"
  , vV = "_sub_ul_56k5l_29"
  , yV = "_sub_on_56k5l_48"
  , wV = "_on_56k5l_55"
  , xV = "_off_56k5l_70"
  , bV = "_sub_off_56k5l_98"
  , dr = {
    container: hV,
    main_ul: gV,
    battle: _V,
    sub_ul: vV,
    sub_on: yV,
    on: wV,
    off: xV,
    sub_off: bV
}
  , SV = () => {
    const {nowSection: e, setNavClickSection: t} = g.useContext(An)
      , n = i => {
        i.stopPropagation();
        const {type: s, main: o, sub: l} = i.currentTarget.dataset
          , a = String(s === "main" ? o : l);
        t(a === "dogyo" ? "dogyo|story" : a)
    }
      , r = [{
        id: "main",
        label: "MAIN",
        sub: []
    }, {
        id: "town",
        label: "마을소개(확장판)",
        sub: []
    }, {
        id: "new-contents",
        label: "신규콘텐츠 소개",
        sub: []
    }, {
        id: "transcend-event",
        label: "인게임 이벤트",
        sub: []
    }];
    return u.jsx("nav", {
        className: dr.container,
        children: u.jsx("ul", {
            className: dr.main_ul,
            children: r.map( ({id: i, label: s, sub: o}) => u.jsxs(Ce.Fragment, {
                children: [u.jsx("button", {
                    type: "button",
                    id: i,
                    "data-type": "main",
                    "data-main": i,
                    onClick: n,
                    className: i === "battle" ? dr.battle : void 0,
                    children: u.jsx("span", {
                        className: e.includes(i) ? dr.on : dr.off,
                        children: s
                    })
                }), u.jsx("li", {
                    children: o.length > 0 && u.jsx("ul", {
                        className: dr.sub_ul,
                        children: o.map( ({id: l, subLabel: a}) => u.jsx("li", {
                            className: e.includes(l) ? dr.sub_on : dr.sub_off,
                            children: u.jsx("button", {
                                type: "button",
                                onClick: n,
                                "data-sub": l,
                                "data-type": "sub",
                                children: a
                            })
                        }, l))
                    })
                })]
            }, i))
        })
    })
}
  , CV = "_town_wrapper_1i961_1"
  , kV = "_observe_on_1i961_6"
  , EV = "_guard_1i961_6"
  , TV = "_fadeUpSpot_1i961_1"
  , PV = "_medicinal_store_1i961_9"
  , jV = "_drugstore_1i961_12"
  , NV = "_variety_store_1i961_15"
  , RV = "_storage_1i961_18"
  , IV = "_factory_1i961_21"
  , LV = "_smithy_1i961_24"
  , MV = "_spot_item_1i961_27"
  , OV = "_main_button_1i961_30"
  , AV = "_mine_1i961_30"
  , DV = "_skill_1i961_33"
  , $V = "_transcend_1i961_36"
  , FV = "_dogyo_map_1i961_40"
  , VV = "_on_1i961_47"
  , zV = "_button_wrapper_1i961_51"
  , BV = "_focus_1i961_57"
  , UV = "_observing_1i961_126"
  , HV = "_sub_button_1i961_131"
  , GV = "_main_spot_list_1i961_200"
  , Je = {
    town_wrapper: CV,
    observe_on: kV,
    guard: EV,
    fadeUpSpot: TV,
    medicinal_store: PV,
    drugstore: jV,
    variety_store: NV,
    storage: RV,
    factory: IV,
    smithy: LV,
    spot_item: MV,
    main_button: OV,
    mine: AV,
    skill: DV,
    transcend: $V,
    dogyo_map: FV,
    on: VV,
    button_wrapper: zV,
    focus: BV,
    observing: UV,
    sub_button: HV,
    main_spot_list: GV
}
  , WV = ({sectionRef: e}) => {
    const {setNowSection: t, scaleState: n, setScaleState: r, setMainModalState: i, setCurrentTab: s, setClassSkill: o} = g.useContext(An)
      , l = g.useRef(null)
      , a = g.useRef(null)
      , [c,d] = g.useState(!1)
      , f = {
        default: {
            posX: 0,
            posY: 0,
            scale: 1
        },
        mine: {
            posX: -732,
            posY: 435,
            scale: 2
        },
        skill: {
            posX: -60,
            posY: 285,
            scale: 2
        },
        transcend: {
            posX: -360,
            posY: 80,
            scale: 2
        }
    }
      , p = {
        smithy: "smithy",
        factory: "factory",
        storage: "storage",
        varietyStore: "variety_store",
        drugstore: "drugstore",
        medicinalStore: "medicinal_store",
        guard: "guard"
    }
      , h = _ => {
        r(x => ({
            ...x,
            currentSpot: _,
            focus: !0
        })),
        setTimeout( () => {
            _ !== "default" && (i(!0),
            s(_),
            o("warrior"))
        }
        , 1e3)
    }
    ;
    g.useEffect( () => {
        var _, x, v;
        a.current && (a.current.style.transform = `translate(${(_ = f[n.currentSpot]) == null ? void 0 : _.posX}px, ${(x = f[n.currentSpot]) == null ? void 0 : x.posY}px) scale(${(v = f[n.currentSpot]) == null ? void 0 : v.scale})`)
    }
    , [n]);
    const m = Ie(l, {
        threshold: .3
    });
    return g.useEffect( () => {
        m && (t("town"),
        d(!0))
    }
    , [m]),
    u.jsx("section", {
        className: `${Je.town_wrapper} ${c && Je.observe_on}`,
        ref: e,
        children: u.jsxs("div", {
            ref: l,
            className: Je.observing,
            children: [u.jsx("div", {
                className: Je.dogyo_map,
                ref: a,
                children: u.jsx("video", {
                    autoPlay: !0,
                    muted: !0,
                    loop: !0,
                    className: Je.map_video,
                    poster: "https://web-cdn.mironline.co.kr/mir2/2024_event/0904/images/village.jpg",
                    children: u.jsx("source", {
                        src: "https://web-cdn.mironline.co.kr/mir2/2024_event/0904/videos/village.mp4",
                        type: "video/mp4"
                    })
                })
            }), u.jsxs("div", {
                className: n.focus ? `${Je.button_wrapper} ${Je.focus}` : Je.button_wrapper,
                children: [u.jsx("button", {
                    type: "button",
                    className: `${Je.main_button} ${Je.mine}`,
                    onClick: () => h("mine")
                }), u.jsx("button", {
                    type: "button",
                    className: `${Je.main_button} ${Je.skill}`,
                    onClick: () => h("skill")
                }), u.jsx("button", {
                    type: "button",
                    className: `${Je.main_button} ${Je.transcend}`,
                    onClick: () => h("transcend")
                }), Object.keys(p).map( (_, x) => u.jsx("button", {
                    type: "button",
                    className: `${Je.sub_button} ${Je[p[_]]}`,
                    disabled: !0
                }, x)), u.jsxs("ul", {
                    className: Je.main_spot_list,
                    children: [u.jsx("li", {
                        className: Je.spot_item,
                        children: u.jsx("button", {
                            type: "button",
                            onClick: () => h("mine"),
                            children: u.jsx("img", {
                                src: "https://web-cdn.mironline.co.kr/mir2/2024_event/0904/images/spot-item-mine.png",
                                alt: "mine"
                            })
                        })
                    }), u.jsx("li", {
                        className: Je.spot_item,
                        children: u.jsx("button", {
                            type: "button",
                            onClick: () => h("skill"),
                            children: u.jsx("img", {
                                src: "https://web-cdn.mironline.co.kr/mir2/2024_event/0904/images/spot-item-skill.png",
                                alt: "field"
                            })
                        })
                    }), u.jsx("li", {
                        className: Je.spot_item,
                        children: u.jsx("button", {
                            type: "button",
                            onClick: () => h("transcend"),
                            children: u.jsx("img", {
                                src: "https://web-cdn.mironline.co.kr/mir2/2024_event/0904/images/spot-item-transcend.png",
                                alt: "smelt"
                            })
                        })
                    })]
                })]
            })]
        })
    })
}
  , qV = "_facility_wrapper_1w2mm_1"
  , YV = "_tab_wrapper_1w2mm_8"
  , KV = "_active_1w2mm_21"
  , XV = "_facility_list_1w2mm_40"
  , QV = "_facility_item_1w2mm_46"
  , JV = "_skill_wrapper_1w2mm_53"
  , ZV = "_style_inner_1w2mm_59"
  , ez = "_class_list_1w2mm_66"
  , tz = "_class_button_1w2mm_74"
  , nz = "_skill_image_1w2mm_80"
  , rz = "_skill_player_list_1w2mm_84"
  , iz = "_skill_player_button_1w2mm_91"
  , _e = {
    facility_wrapper: qV,
    tab_wrapper: YV,
    active: KV,
    facility_list: XV,
    facility_item: QV,
    skill_wrapper: JV,
    style_inner: ZV,
    class_list: ez,
    class_button: tz,
    skill_image: nz,
    skill_player_list: rz,
    skill_player_button: iz
}
  , Ux = () => {
    const e = "https://web-cdn.mironline.co.kr/mir2/2024_event/0904/images"
      , t = {
        mine: `${e}/intro-mine.jpg`,
        skill: `${e}/intro-skill.jpg`,
        transcend: `${e}/intro-transcend.jpg`
    }
      , {currentTab: n, setCurrentTab: r, classSkill: i, setClassSkill: s, setSkillModalStatus: o} = g.useContext(An);
    return u.jsxs("div", {
        className: _e.facility_wrapper,
        children: [u.jsxs("div", {
            className: _e.tab_wrapper,
            children: [u.jsx("button", {
                type: "button",
                className: n === "mine" ? `${_e.tab} ${_e.active}` : _e.tab,
                onClick: () => r("mine"),
                children: u.jsx("img", {
                    src: "https://web-cdn.mironline.co.kr/mir2/2024_event/0904/images/tab-mine.png",
                    alt: "mine"
                })
            }), u.jsx("button", {
                type: "button",
                className: n === "skill" ? `${_e.tab} ${_e.active}` : _e.tab,
                onClick: () => r("skill"),
                children: u.jsx("img", {
                    src: "https://web-cdn.mironline.co.kr/mir2/2024_event/0904/images/tab-skill.png",
                    alt: "skill"
                })
            }), u.jsx("button", {
                type: "button",
                className: n === "transcend" ? `${_e.tab} ${_e.active}` : _e.tab,
                onClick: () => r("transcend"),
                children: u.jsx("img", {
                    src: "https://web-cdn.mironline.co.kr/mir2/2024_event/0904/images/tab-transcend.png",
                    alt: "transcend"
                })
            })]
        }), u.jsx("div", {
            className: _e.facility_list,
            children: n === "skill" ? u.jsx("div", {
                className: _e.skill_wrapper,
                children: u.jsxs("div", {
                    className: _e.style_inner,
                    children: [u.jsxs("ul", {
                        className: _e.class_list,
                        children: [u.jsx("li", {
                            className: _e.class_item,
                            children: u.jsx("button", {
                                type: "button",
                                className: _e.class_button,
                                onClick: () => s("warrior"),
                                children: "전사"
                            })
                        }), u.jsx("li", {
                            className: _e.class_item,
                            children: u.jsx("button", {
                                type: "button",
                                className: _e.class_button,
                                onClick: () => s("mage"),
                                children: "술사"
                            })
                        }), u.jsx("li", {
                            className: _e.class_item,
                            children: u.jsx("button", {
                                type: "button",
                                className: _e.class_button,
                                onClick: () => s("taoist"),
                                children: "도사"
                            })
                        }), u.jsx("li", {
                            className: _e.class_item,
                            children: u.jsx("button", {
                                type: "button",
                                className: _e.class_button,
                                onClick: () => s("assassin"),
                                children: "자객"
                            })
                        }), u.jsx("li", {
                            className: _e.class_item,
                            children: u.jsx("button", {
                                type: "button",
                                className: _e.class_button,
                                onClick: () => s("archer"),
                                children: "궁수"
                            })
                        })]
                    }), u.jsxs("div", {
                        className: _e.skill_image,
                        children: [u.jsxs("ul", {
                            className: _e.skill_player_list,
                            children: [u.jsx("li", {
                                className: _e.skill_player_item,
                                children: u.jsx("button", {
                                    type: "button",
                                    className: _e.skill_player_button,
                                    onClick: () => o({
                                        currentClass: i,
                                        skillSeq: "1",
                                        state: !0
                                    })
                                })
                            }), u.jsx("li", {
                                className: _e.skill_player_item,
                                children: u.jsx("button", {
                                    type: "button",
                                    className: _e.skill_player_button,
                                    onClick: () => o({
                                        currentClass: i,
                                        skillSeq: "2",
                                        state: !0
                                    })
                                })
                            }), u.jsx("li", {
                                className: _e.skill_player_item,
                                children: u.jsx("button", {
                                    type: "button",
                                    className: _e.skill_player_button,
                                    onClick: () => o({
                                        currentClass: i,
                                        skillSeq: "3",
                                        state: !0
                                    })
                                })
                            })]
                        }), u.jsx("img", {
                            src: `https://web-cdn.mironline.co.kr/mir2/2024_event/0904/images/${i}.png`,
                            alt: "skill"
                        })]
                    })]
                })
            }) : u.jsx("div", {
                children: u.jsx("img", {
                    src: t[n],
                    alt: "village"
                })
            })
        })]
    })
}
  , sz = "_modal_wrapper_1fbns_1"
  , oz = "_fade_in_1fbns_14"
  , az = "_fadeIn_1fbns_1"
  , lz = "_fade_out_1fbns_17"
  , cz = "_fadeOut_1fbns_1"
  , uz = "_close_button_1fbns_22"
  , dz = "_modal_contents_1fbns_29"
  , fz = "_modal_top_1fbns_34"
  , pz = "_village_wrapper_1fbns_41"
  , mz = "_overlay_1fbns_57"
  , hz = "_village_close_button_1fbns_67"
  , gz = "_village_contents_1fbns_72"
  , _z = "_image_wrapper_1fbns_80"
  , Ai = {
    modal_wrapper: sz,
    fade_in: oz,
    fadeIn: az,
    fade_out: lz,
    fadeOut: cz,
    close_button: uz,
    modal_contents: dz,
    modal_top: fz,
    village_wrapper: pz,
    overlay: mz,
    village_close_button: hz,
    village_contents: gz,
    image_wrapper: _z
}
  , vz = () => {
    const {setScaleState: e, setMainModalState: t, setClassSkill: n} = g.useContext(An)
      , r = g.useRef(null)
      , [i,s] = g.useState(!1)
      , [o,l] = g.useState(!1)
      , a = () => {
        r.current && r.current.scrollTo({
            top: 0,
            behavior: "smooth"
        })
    }
      , c = () => {
        s(!1),
        setTimeout( () => {
            t(!1)
        }
        , 500),
        e(d => ({
            ...d,
            currentSpot: "default"
        })),
        n("warrior"),
        setTimeout( () => {
            e(d => ({
                ...d,
                currentSpot: "default",
                focus: !1
            }))
        }
        , 1100)
    }
    ;
    return g.useEffect( () => {
        s(!0)
    }
    , []),
    g.useEffect( () => {
        if (!r.current)
            return;
        const d = () => {
            r.current && (r.current.scrollTop > 0 ? l(!0) : l(!1))
        }
        ;
        r.current.addEventListener("scroll", d)
    }
    , []),
    u.jsxs("div", {
        className: `${Ai.modal_wrapper} ${i ? Ai.fade_in : Ai.fade_out}`,
        ref: r,
        children: [u.jsx("button", {
            type: "button",
            onClick: c,
            className: Ai.close_button,
            children: u.jsx("img", {
                src: "https://web-cdn.mironline.co.kr/mir2/2024_event/0807/images/modal-back.png",
                alt: "back"
            })
        }), u.jsxs("div", {
            className: Ai.modal_contents,
            children: [u.jsx(Ux, {}), o && u.jsx("button", {
                type: "button",
                className: Ai.modal_top,
                onClick: a,
                children: u.jsx("img", {
                    src: "https://web-cdn.mironline.co.kr/mir2/2024_event/0807/images/modal-top.png",
                    alt: "back"
                })
            })]
        })]
    })
}
  , yz = ({sectionRef: e}) => {
    const {setNowSection: t} = g.useContext(An)
      , n = g.useRef(null)
      , r = Ie(n, {
        threshold: .2
    });
    return g.useEffect( () => {
        r && t("new-contents")
    }
    , [r]),
    u.jsx("section", {
        ref: e,
        children: u.jsx("div", {
            ref: n,
            children: u.jsx(Ux, {})
        })
    })
}
  , wz = ({sectionRef: e}) => {
    const {setNowSection: t} = g.useContext(An)
      , n = g.useRef(null)
      , r = Ie(n, {
        threshold: .3
    });
    return g.useEffect( () => {
        r && t("transcend-event")
    }
    , [r]),
    u.jsx("section", {
        ref: e,
        children: u.jsx("div", {
            ref: n,
            children: u.jsx("img", {
                src: "https://web-cdn.mironline.co.kr/mir2/2024_event/0904/images/transcend-event.jpg",
                alt: "transcend"
            })
        })
    })
}
  , xz = "_skill_modal_wrap_1p0o5_1"
  , bz = "_skill_video_wrapper_1p0o5_11"
  , Sz = "_overlay_1p0o5_21"
  , Yu = {
    skill_modal_wrap: xz,
    skill_video_wrapper: bz,
    overlay: Sz
}
  , Cz = () => {
    const {skillModalStatus: e, setSkillModalStatus: t, mainModalState: n} = g.useContext(An);
    return g.useEffect( () => {
        const r = document.querySelector("body");
        return r && !n && r.setAttribute("style", "overflow:hidden; background: #000;"),
        () => {
            r && !n && r.setAttribute("style", "overflow:auto; background: #000;")
        }
    }
    , []),
    u.jsx(Cs, {
        children: u.jsxs(u.Fragment, {
            children: [u.jsx("button", {
                type: "button",
                className: Yu.overlay,
                onClick: () => t(r => ({
                    ...r,
                    state: !1
                }))
            }), u.jsxs("div", {
                className: Yu.skill_modal_wrap,
                children: [u.jsx("button", {
                    type: "button",
                    onClick: () => t(r => ({
                        ...r,
                        state: !1
                    })),
                    children: u.jsx("img", {
                        src: "https://web-cdn.mironline.co.kr/mir2/2024_event/0807/images/popup-close-btn.png",
                        alt: "close"
                    })
                }), u.jsx("div", {
                    className: Yu.skill_video_wrapper,
                    children: u.jsx("video", {
                        autoPlay: !0,
                        muted: !0,
                        controls: !0,
                        children: u.jsx("source", {
                            src: `https://web-cdn.mironline.co.kr/mir2/2024_event/0904/videos/${e.currentClass}_${e.skillSeq}.mp4`
                        })
                    })
                })]
            })]
        })
    })
}
  , kz = "_main_container_1o5vx_1"
  , Ez = {
    main_container: kz
}
  , Tz = "_footer_container_1phjc_238"
  , Pz = "_fadeIn_1phjc_1"
  , jz = {
    "a11y-hidden": "_a11y-hidden_1phjc_192",
    footer_container: Tz,
    fadeIn: Pz
}
  , Nz = ({color: e="", font: t}) => u.jsx("footer", {
    className: jz.footer_container,
    style: {
        backgroundColor: e,
        color: t
    },
    children: "© ChuanQi IP Co., Ltd. All rights reserved"
})
  , Rz = () => u.jsx("main", {
    className: Ez.main_container,
    children: u.jsx(lV, {
        children: u.jsxs(sa, {
            children: [u.jsx(mV, {}), u.jsx(SV, {}), u.jsx(Si, {}), u.jsx(Nz, {
                color: "#282828",
                font: "rgba(255, 255, 255, 0.5)"
            }), u.jsx(Bx, {
                imageLink: "0807/images/modal-top",
                posBottom: "65px",
                posRight: "3vw"
            })]
        })
    })
})
  , Iz = "_wrapper_petjt_1"
  , Lz = {
    wrapper: Iz
}
  , Mz = () => {
    const {navClickSection: e, setNavClickSection: t, setNowSection: n, mainModalState: r, skillModalStatus: i} = g.useContext(An)
      , s = g.useRef(null)
      , o = Ie(s)
      , l = document.querySelector("body");
    return g.useEffect( () => {
        window.scrollTo({
            top: 0
        }),
        l && l.setAttribute("style", "background: #000;")
    }
    , []),
    g.useEffect( () => {
        s.current && s.current.scrollIntoView({
            block: "start",
            behavior: "smooth"
        }),
        t("")
    }
    , [e]),
    g.useEffect( () => {
        r ? l == null || l.setAttribute("style", "overflow:hidden; background: #000;") : l == null || l.setAttribute("style", "overflow:auto; background: #000;")
    }
    , [r]),
    g.useEffect( () => {
        o && n("main")
    }
    , [o]),
    u.jsxs(u.Fragment, {
        children: [u.jsxs("div", {
            className: Lz.wrapper,
            children: [u.jsx(cV, {
                sectionRef: e === "main" ? s : null
            }), u.jsx(WV, {
                sectionRef: e === "town" ? s : null
            }), u.jsx(yz, {
                sectionRef: e === "new-contents" ? s : null
            }), u.jsx(wz, {
                sectionRef: e === "transcend-event" ? s : null
            })]
        }), r && u.jsx(vz, {}), i.state && u.jsx(vt.Consumer, {
            children: u.jsx(Cz, {})
        })]
    })
}
  , Oz = "_wrapper_pg4he_1"
  , Az = {
    wrapper: Oz
}
  , Dz = () => {
    const {navClickSection: e, setNavClickSection: t, setNowSection: n, mainModalState: r, villageModalState: i} = g.useContext(cn)
      , s = g.useRef(null)
      , o = Ie(s)
      , l = document.querySelector("body")
      , a = document.querySelector("html");
    return g.useEffect( () => {
        window.scrollTo({
            top: 0
        }),
        l && l.setAttribute("style", "background: #000;")
    }
    , []),
    g.useEffect( () => {
        s.current && s.current.scrollIntoView({
            block: "start",
            behavior: "smooth"
        }),
        t("")
    }
    , [e]),
    g.useEffect( () => {
        r || i.length > 0 ? l == null || l.setAttribute("style", "overflow:hidden; background: #000;") : l == null || l.setAttribute("style", "overflow:auto; background: #000;")
    }
    , [r, i]),
    g.useEffect( () => {
        i.length > 0 ? a == null || a.setAttribute("style", "scrollbar-gutter: stable;") : a == null || a.removeAttribute("style")
    }
    , [i]),
    g.useEffect( () => {
        o && n("main")
    }
    , [o]),
    u.jsxs(u.Fragment, {
        children: [u.jsxs("div", {
            className: Az.wrapper,
            children: [u.jsx(vD, {
                sectionRef: e === "main" ? s : null
            }), u.jsx(zD, {
                sectionRef: e === "dogyo|story" ? s : null
            }), u.jsx(y$, {
                sectionRef: e === "dogyo|town" ? s : null
            }), u.jsx(F$, {
                sectionRef: e === "dogyo|facility" ? s : null
            }), u.jsx(Y$, {
                sectionRef: e === "open-event" ? s : null
            }), u.jsx(qF, {
                sectionRef: e === "mana-stone" ? s : null
            })]
        }), r && u.jsx($$, {}), i.length > 0 && u.jsx(V$, {})]
    })
}
  , $z = "_wrapper_bll7e_1"
  , Fz = "_video_wrapper_bll7e_8"
  , Vz = "_video_bll7e_8"
  , zz = "_header_bll7e_29"
  , Bz = "_fadeIn_bll7e_1"
  , Uz = "_header_link_bll7e_41"
  , Ws = {
    wrapper: $z,
    video_wrapper: Fz,
    video: Vz,
    header: zz,
    fadeIn: Bz,
    header_link: Uz
}
  , Hz = () => {
    const e = g.useRef(null)
      , t = () => {
        he("EvtPageGoHome", "EvtPageDone", void 0, 2),
        he("evtCode", "20240717", void 0, 2)
    }
    ;
    return g.useEffect( () => {
        const n = e.current;
        if (!n)
            return;
        let r;
        const i = () => {
            n.currentTime >= 13 && (n.currentTime = 3),
            r = requestAnimationFrame(i)
        }
        ;
        return i(),
        () => {
            cancelAnimationFrame(r)
        }
    }
    , []),
    u.jsxs("main", {
        className: Ws.wrapper,
        children: [u.jsx("header", {
            className: Ws.header,
            children: u.jsx("h1", {
                children: u.jsx("a", {
                    href: "https://mir2.mironline.co.kr/main",
                    className: Ws.header_link,
                    onClick: t,
                    children: u.jsx("img", {
                        src: "https://web-cdn.mironline.co.kr/mir2/2024_event/0717/images/logo.png",
                        alt: "bi"
                    })
                })
            })
        }), u.jsx("div", {
            className: Ws.video_wrapper,
            children: u.jsx("video", {
                className: Ws.video,
                autoPlay: !0,
                muted: !0,
                ref: e,
                poster: "https://web-cdn.mironline.co.kr/mir2/2024_event/0717/images/thumbnail.jpg",
                children: u.jsx("source", {
                    src: "https://web-cdn.mironline.co.kr/mir2/2024_event/0717/videos/intro.mp4"
                })
            })
        })]
    })
}
  , Gz = "_container_1dcgq_238"
  , Wz = "_do_not_show_1dcgq_250"
  , qz = "_fadeIn_1dcgq_1"
  , V_ = {
    "a11y-hidden": "_a11y-hidden_1dcgq_192",
    container: Gz,
    do_not_show: Wz,
    fadeIn: qz
}
  , Yz = () => {
    const e = () => {
        he("EvtPageGoHome", "EvtPageDone", void 0, 2),
        he("evtCode", "20240717", void 0, 2)
    }
      , t = () => {
        he("EvtPage", "EvtPageDone", 1),
        he("evtCode", "20240717", 1),
        window.location.href = "https://mir2.mironline.co.kr/main"
    }
    ;
    return u.jsxs("header", {
        className: V_.container,
        children: [u.jsx("a", {
            href: "https://mir2.mironline.co.kr/main",
            onClick: e,
            children: u.jsx("img", {
                src: "https://web-cdn.mironline.co.kr/mir2/2024_event/0717/images/logo.png",
                alt: "홈"
            })
        }), u.jsx("button", {
            className: V_.do_not_show,
            type: "button",
            onClick: t,
            children: u.jsx("img", {
                src: "https://web-cdn.mironline.co.kr/mir2/2024_event/0717/images/today.png",
                alt: "오늘하루그만보기"
            })
        })]
    })
}
  , Kz = "_hero_container_1p3z0_1"
  , Xz = {
    hero_container: Kz
}
  , Qz = () => u.jsxs("div", {
    className: Xz.hero_container,
    children: [u.jsx(Yz, {}), u.jsx("img", {
        src: "https://web-cdn.mironline.co.kr/mir2/2024_event/0717/images/main.jpg",
        alt: "hero"
    })]
})
  , Jz = "_main_container_42hti_1"
  , Zz = {
    main_container: Jz
}
  , eB = "_content_container_1a9tu_1"
  , tB = {
    content_container: eB
}
  , Dc = g.createContext({
    tabState: "1",
    setTabState: () => {}
})
  , nB = ({children: e}) => {
    const [t,n] = g.useState("1");
    return u.jsx(Dc.Provider, {
        value: {
            tabState: t,
            setTabState: n
        },
        children: e
    })
}
  , rB = () => {
    const {tabState: e} = g.useContext(Dc);
    return u.jsx("section", {
        className: tB.content_container,
        children: u.jsx("img", {
            src: `https://web-cdn.mironline.co.kr/mir2/2024_event/0717/images/tab0${e}-contents.jpg`,
            alt: "content"
        })
    })
}
  , iB = "_top_container_h68v6_1"
  , sB = "_bounce_h68v6_1"
  , oB = {
    top_container: iB,
    bounce: sB
}
  , aB = () => {
    const [e,t] = g.useState(!1)
      , n = () => {
        window.scrollTo({
            top: 0,
            behavior: "smooth"
        })
    }
    ;
    return g.useEffect( () => {
        const r = () => {
            window.scrollY > 500 ? t(!0) : t(!1)
        }
        ;
        return window.addEventListener("scroll", r),
        () => {
            window.removeEventListener("scroll", r)
        }
    }
    , []),
    u.jsx(u.Fragment, {
        children: e && u.jsx("button", {
            type: "button",
            className: oB.top_container,
            onClick: n,
            children: u.jsx("img", {
                src: "https://web-cdn.mironline.co.kr/mir2/2024_event/0717/images/top.png",
                alt: "top"
            })
        })
    })
}
  , lB = "_footer_container_bja8c_1"
  , cB = {
    footer_container: lB
}
  , uB = () => {
    const {tabState: e} = g.useContext(Dc);
    return u.jsx("footer", {
        className: cB.footer_container,
        children: u.jsx("img", {
            src: `https://web-cdn.mironline.co.kr/mir2/2024_event/0717/images/tab0${e}-footer.jpg`,
            alt: "footer"
        })
    })
}
  , dB = "_tab_container_113j6_1"
  , fB = "_tab_wrap_113j6_11"
  , z_ = {
    tab_container: dB,
    tab_wrap: fB
}
  , pB = () => {
    const {tabState: e, setTabState: t} = g.useContext(Dc)
      , n = [{
        id: "1",
        on: "https://web-cdn.mironline.co.kr/mir2/2024_event/0717/images/tab01-on.jpg",
        off: "https://web-cdn.mironline.co.kr/mir2/2024_event/0717/images/tab01-off.jpg"
    }, {
        id: "2",
        on: "https://web-cdn.mironline.co.kr/mir2/2024_event/0717/images/tab02-on.jpg",
        off: "https://web-cdn.mironline.co.kr/mir2/2024_event/0717/images/tab02-off.jpg"
    }];
    return u.jsx("div", {
        className: z_.tab_container,
        children: n.map( ({id: r, on: i, off: s}) => u.jsxs("div", {
            className: z_.tab_wrap,
            children: [u.jsx("img", {
                src: r === e ? i : s,
                alt: "tab"
            }), u.jsx("button", {
                type: "button",
                id: r,
                onClick: () => t(r)
            }, r)]
        }, r))
    })
}
  , mB = "_float_container_1rdu7_1"
  , hB = "_bounce_1rdu7_1"
  , gB = {
    float_container: mB,
    bounce: hB
}
  , _B = () => u.jsx("div", {
    className: gB.float_container,
    children: u.jsx("a", {
        href: "/20240717/dogyo-hyeon",
        target: "_blank",
        children: u.jsx("img", {
            src: "https://web-cdn.mironline.co.kr/mir2/2024_event/0717/images/float.webp",
            alt: "float"
        })
    })
})
  , vB = () => (g.useEffect( () => {
    const e = document.querySelector("body");
    e && e.setAttribute("style", "background: #007AC3;")
}
, []),
u.jsx(nB, {
    children: u.jsxs("main", {
        className: Zz.main_container,
        children: [u.jsx(Qz, {}), u.jsx(pB, {}), u.jsx(rB, {}), u.jsx(_B, {}), u.jsx(uB, {}), u.jsx(aB, {})]
    })
}))
  , yB = "_container_xxxtl_2"
  , wB = "_transparent_button_xxxtl_10"
  , xB = "_content_wrap_xxxtl_32"
  , bB = "_on_0_xxxtl_49"
  , SB = "_on_1_xxxtl_54"
  , CB = "_on_2_xxxtl_59"
  , kB = "_on_3_xxxtl_64"
  , EB = "_click_button_wrap_xxxtl_70"
  , TB = "_click_bounce_xxxtl_1"
  , PB = "_fade_out_xxxtl_78"
  , jB = "_main_container_xxxtl_91"
  , NB = "_main_video_wrap_xxxtl_97"
  , RB = "_main_video_xxxtl_97"
  , fr = {
    container: yB,
    transparent_button: wB,
    content_wrap: xB,
    on_0: bB,
    on_1: SB,
    on_2: CB,
    on_3: kB,
    click_button_wrap: EB,
    click_bounce: TB,
    fade_out: PB,
    main_container: jB,
    main_video_wrap: NB,
    main_video: RB
}
  , IB = () => {
    const e = ["영웅능침의 이공간에서 부활한 마종은 이내 자취를 감췄으나 네르의 공격은 끊임없이 계속되었다.", '네르들은 곤륜 대침공의 차원문이 되어 준 "용의 궤적"의 기운이 약해지자', '이내 위기에 봉착하였고 빙백신룡의 은신처에서 "마정석"을 도굴하여', "그 해법을 찾아 가려는데..."]
      , t = g.useRef(null)
      , n = g.useRef(null)
      , {navClickSection: r, setNavClickSection: i, setNowSection: s, headerType: o, setHeaderType: l, introVideoRef: a, teaserVideoRef: c, mainVideoRef: d, contentRef: f, isPlayClicked: p, setIsPlayClicked: h, isMain: m, setIsMain: _} = g.useContext(Cn)
      , x = Ie(t)
      , [v,w] = g.useState(!0)
      , y = async () => {
        const b = a.current
          , C = c.current
          , S = d.current
          , T = f.current;
        !b || !C || !T || !S || (b.currentTime = 8.3,
        h(!0),
        w(k => !k),
        T.remove(),
        b.addEventListener("ended", async () => {
            b.remove(),
            await C.play()
        }
        ),
        C.addEventListener("ended", async () => {
            C.remove(),
            l("main"),
            _(!0),
            await S.play();
            const k = document.documentElement
              , P = window.innerWidth - k.clientWidth;
            k.style.setProperty("min-width", 1920 - P + "px")
        }
        ))
    }
    ;
    return g.useEffect( () => {
        const b = a.current;
        if (!b)
            return;
        const S = setInterval( () => {
            v && b.currentTime >= 8 && (b.currentTime = 0)
        }
        , 100);
        return () => {
            clearInterval(S)
        }
    }
    , [v]),
    g.useEffect( () => {
        setTimeout( () => {
            f.current && Array(...f.current.children).forEach( (b, C) => {
                b.classList.add(fr[`on_${C}`])
            }
            )
        }
        , 0)
    }
    , []),
    g.useEffect( () => {
        n.current && n.current.scrollIntoView({
            block: "start"
        }),
        i("")
    }
    , [r]),
    g.useEffect( () => {
        x && s("main")
    }
    , [x]),
    u.jsxs("section", {
        className: `${m ? fr.main_container : fr.container}`,
        children: [u.jsx(eM, {
            type: o
        }), u.jsx("video", {
            autoPlay: !0,
            muted: !0,
            playsInline: !0,
            crossOrigin: "anonymous",
            preload: "auto",
            ref: a,
            poster: "https://web-cdn.mironline.co.kr/mir2/2024_event/0313/images/intro-poster.png",
            children: u.jsx("source", {
                src: "https://web-cdn.mironline.co.kr/mir2/2024_event/0313/video/intro.mp4",
                type: "video/mp4"
            })
        }), !m && u.jsx("button", {
            type: "button",
            className: fr.transparent_button,
            onClick: y,
            disabled: p
        }), u.jsx("ul", {
            className: fr.content_wrap,
            ref: f,
            children: e.map( (b, C) => u.jsx("li", {
                children: b
            }, C))
        }), u.jsx("video", {
            playsInline: !0,
            crossOrigin: "anonymous",
            preload: "auto",
            ref: c,
            children: u.jsx("source", {
                src: "https://web-cdn.mironline.co.kr/mir2/2024_event/0411/video/teaser.mp4",
                type: "video/mp4"
            })
        }), !p && u.jsx("div", {
            className: fr.click_button_wrap,
            children: u.jsx("img", {
                src: "https://web-cdn.mironline.co.kr/mir2/2024_event/0313/images/intro-click.png",
                alt: "click"
            })
        }), u.jsx("div", {
            className: fr.main_video_wrap,
            ref: r === "main" ? n : void 0,
            children: u.jsxs("div", {
                ref: t,
                children: [u.jsx("video", {
                    loop: !0,
                    autoPlay: !0,
                    muted: !0,
                    playsInline: !0,
                    crossOrigin: "anonymous",
                    preload: "auto",
                    ref: d,
                    className: fr.main_video,
                    children: u.jsx("source", {
                        src: "https://web-cdn.mironline.co.kr/mir2/2024_event/0411/video/main-loop.mp4",
                        type: "video/mp4"
                    })
                }), m && u.jsx(_4, {
                    isMain: m
                })]
            })
        }), m && u.jsxs(u.Fragment, {
            children: [u.jsx(p4, {
                sectionRef: r === "story" ? n : null
            }), u.jsx(e3, {
                sectionRef: r === "story|monster" ? n : null
            }), u.jsx(u3, {
                sectionRef: r === "story|update" ? n : null
            }), u.jsx(g3, {
                sectionRef: r === "event" ? n : null
            }), u.jsx(YA, {
                sectionRef: r === "battle" ? n : null
            }), u.jsx(y3, {})]
        })]
    })
}
  , LB = "_error_wrapper_tseox_1"
  , MB = "_img_wrapper_tseox_10"
  , OB = "_error_message_tseox_14"
  , Ku = {
    error_wrapper: LB,
    img_wrapper: MB,
    error_message: OB
}
  , AB = () => u.jsx("div", {
    className: Ku.error_wrapper,
    children: u.jsxs("div", {
        className: Ku.img_wrapper,
        children: [u.jsx("img", {
            src: "https://web-cdn.mironline.co.kr/common/404/img_wemade404.jpg",
            alt: "404"
        }), u.jsxs("p", {
            className: Ku.error_message,
            children: ["The page cannot be found.", u.jsx("br", {}), "Please check the page you requested again."]
        })]
    })
})
  , DB = z2([{
    element: u.jsx(AB, {}),
    path: "*"
}, {
    element: u.jsx(xL, {}),
    children: [{
        path: "/231108/intro",
        element: u.jsx(gC, {})
    }, {
        path: "/231108/main",
        element: u.jsx(KP, {})
    }]
}, {
    path: "/231108/tab",
    element: u.jsx(RL, {}),
    children: [{
        path: "illusion/info",
        element: u.jsx(ij, {})
    }, {
        path: "illusion/benefit",
        element: u.jsx(hR, {})
    }, {
        path: "live/benefit",
        element: u.jsx(sj, {})
    }, {
        path: "live/newcontent",
        element: u.jsx(oj, {})
    }]
}, {
    path: "/20231220/winter",
    element: u.jsx(GL, {}),
    children: [{
        path: "",
        element: u.jsx(kR, {})
    }]
}, {
    path: "/20240417/dungeon",
    element: u.jsx(b3, {}),
    children: [{
        path: "",
        element: u.jsx(IB, {})
    }]
}, {
    path: "/20240313/anniversary",
    element: u.jsx(IR, {}),
    children: [{
        path: "",
        element: u.jsx(fL, {})
    }]
}, {
    path: "/20240717/summer",
    element: u.jsx(vB, {})
}, {
    path: "/20240717/dogyo-hyeon",
    element: u.jsx(Hz, {})
}, {
    path: "20240807/dogyo-hyeon",
    element: u.jsx(eV, {}),
    children: [{
        path: "",
        element: u.jsx(Dz, {})
    }]
}, {
    path: "20240904/dogyo-extended",
    element: u.jsx(Rz, {}),
    children: [{
        path: "",
        element: u.jsx(Mz, {})
    }]
}]);
const $B = document.getElementById("root")
  , FB = Xu.createRoot($B);
FB.render(u.jsx(vt.Provider, {
    children: u.jsx(YP, {
        children: u.jsx(eL, {
            children: u.jsx(om, {
                children: u.jsx(X2, {
                    router: DB
                })
            })
        })
    })
}));
