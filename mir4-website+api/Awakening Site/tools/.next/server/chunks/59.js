"use strict";
exports.id = 59;
exports.ids = [59];
exports.modules = {

/***/ 7059:
/***/ ((__unused_webpack_module, __webpack_exports__, __webpack_require__) => {

/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   Z: () => (__WEBPACK_DEFAULT_EXPORT__)
/* harmony export */ });
/* unused harmony export useSound */
/* harmony import */ var react__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(8038);
/* harmony import */ var react__WEBPACK_IMPORTED_MODULE_0___default = /*#__PURE__*/__webpack_require__.n(react__WEBPACK_IMPORTED_MODULE_0__);


function _extends() {
  _extends = Object.assign || function (target) {
    for (var i = 1; i < arguments.length; i++) {
      var source = arguments[i];

      for (var key in source) {
        if (Object.prototype.hasOwnProperty.call(source, key)) {
          target[key] = source[key];
        }
      }
    }

    return target;
  };

  return _extends.apply(this, arguments);
}

function _objectWithoutPropertiesLoose(source, excluded) {
  if (source == null) return {};
  var target = {};
  var sourceKeys = Object.keys(source);
  var key, i;

  for (i = 0; i < sourceKeys.length; i++) {
    key = sourceKeys[i];
    if (excluded.indexOf(key) >= 0) continue;
    target[key] = source[key];
  }

  return target;
}

function useOnMount(callback) {
  (0,react__WEBPACK_IMPORTED_MODULE_0__.useEffect)(callback, []);
}

function useSound(src, _ref) {
  if (_ref === void 0) {
    _ref = {};
  }

  var _ref2 = _ref,
      _ref2$volume = _ref2.volume,
      volume = _ref2$volume === void 0 ? 1 : _ref2$volume,
      _ref2$playbackRate = _ref2.playbackRate,
      playbackRate = _ref2$playbackRate === void 0 ? 1 : _ref2$playbackRate,
      _ref2$soundEnabled = _ref2.soundEnabled,
      soundEnabled = _ref2$soundEnabled === void 0 ? true : _ref2$soundEnabled,
      _ref2$interrupt = _ref2.interrupt,
      interrupt = _ref2$interrupt === void 0 ? false : _ref2$interrupt,
      onload = _ref2.onload,
      delegated = _objectWithoutPropertiesLoose(_ref2, ["id", "volume", "playbackRate", "soundEnabled", "interrupt", "onload"]);

  var HowlConstructor = react__WEBPACK_IMPORTED_MODULE_0___default().useRef(null);
  var isMounted = react__WEBPACK_IMPORTED_MODULE_0___default().useRef(false);

  var _React$useState = react__WEBPACK_IMPORTED_MODULE_0___default().useState(null),
      duration = _React$useState[0],
      setDuration = _React$useState[1];

  var _React$useState2 = react__WEBPACK_IMPORTED_MODULE_0___default().useState(null),
      sound = _React$useState2[0],
      setSound = _React$useState2[1];

  var handleLoad = function handleLoad() {
    if (typeof onload === 'function') {
      // @ts-ignore
      onload.call(this);
    }

    if (isMounted.current) {
      // @ts-ignore
      setDuration(this.duration() * 1000);
    } // @ts-ignore


    setSound(this);
  }; // We want to lazy-load Howler, since sounds can't play on load anyway.


  useOnMount(function () {
    __webpack_require__.e(/* import() */ 698).then(__webpack_require__.t.bind(__webpack_require__, 4698, 23)).then(function (mod) {
      if (!isMounted.current) {
        var _mod$Howl;

        // Depending on the module system used, `mod` might hold
        // the export directly, or it might be under `default`.
        HowlConstructor.current = (_mod$Howl = mod.Howl) !== null && _mod$Howl !== void 0 ? _mod$Howl : mod["default"].Howl;
        isMounted.current = true;
        new HowlConstructor.current(_extends({
          src: Array.isArray(src) ? src : [src],
          volume: volume,
          rate: playbackRate,
          onload: handleLoad
        }, delegated));
      }
    });
    return function () {
      isMounted.current = false;
    };
  }); // When the `src` changes, we have to do a whole thing where we recreate
  // the Howl instance. This is because Howler doesn't expose a way to
  // tweak the sound

  react__WEBPACK_IMPORTED_MODULE_0___default().useEffect(function () {
    if (HowlConstructor.current && sound) {
      setSound(new HowlConstructor.current(_extends({
        src: Array.isArray(src) ? src : [src],
        volume: volume,
        onload: handleLoad
      }, delegated)));
    } // The linter wants to run this effect whenever ANYTHING changes,
    // but very specifically I only want to recreate the Howl instance
    // when the `src` changes. Other changes should have no effect.
    // Passing array to the useEffect dependencies list will result in
    // ifinite loop so we need to stringify it, for more details check
    // https://github.com/facebook/react/issues/14476#issuecomment-471199055
    // eslint-disable-next-line react-hooks/exhaustive-deps

  }, [JSON.stringify(src)]); // Whenever volume/playbackRate are changed, change those properties
  // on the sound instance.

  react__WEBPACK_IMPORTED_MODULE_0___default().useEffect(function () {
    if (sound) {
      sound.volume(volume);
      sound.rate(playbackRate);
    } // A weird bug means that including the `sound` here can trigger an
    // error on unmount, where the state loses track of the sprites??
    // No idea, but anyway I don't need to re-run this if only the `sound`
    // changes.
    // eslint-disable-next-line react-hooks/exhaustive-deps

  }, [volume, playbackRate]);
  var play = react__WEBPACK_IMPORTED_MODULE_0___default().useCallback(function (options) {
    if (typeof options === 'undefined') {
      options = {};
    }

    if (!sound || !soundEnabled && !options.forceSoundEnabled) {
      return;
    }

    if (interrupt) {
      sound.stop();
    }

    if (options.playbackRate) {
      sound.rate(options.playbackRate);
    }

    sound.play(options.id);
  }, [sound, soundEnabled, interrupt]);
  var stop = react__WEBPACK_IMPORTED_MODULE_0___default().useCallback(function (id) {
    if (!sound) {
      return;
    }

    sound.stop(id);
  }, [sound]);
  var pause = react__WEBPACK_IMPORTED_MODULE_0___default().useCallback(function (id) {
    if (!sound) {
      return;
    }

    sound.pause(id);
  }, [sound]);
  var returnedValue = [play, {
    sound: sound,
    stop: stop,
    pause: pause,
    duration: duration
  }];
  return returnedValue;
}

/* harmony default export */ const __WEBPACK_DEFAULT_EXPORT__ = (useSound);

//# sourceMappingURL=use-sound.esm.js.map


/***/ })

};
;