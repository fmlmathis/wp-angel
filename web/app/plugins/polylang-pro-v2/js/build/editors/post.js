/******/ (() => { // webpackBootstrap
/******/ 	var __webpack_modules__ = ({

/***/ 20:
/***/ ((__unused_webpack_module, exports, __webpack_require__) => {

"use strict";
/**
 * @license React
 * react-jsx-runtime.production.min.js
 *
 * Copyright (c) Facebook, Inc. and its affiliates.
 *
 * This source code is licensed under the MIT license found in the
 * LICENSE file in the root directory of this source tree.
 */
var f=__webpack_require__(677),k=Symbol.for("react.element"),l=Symbol.for("react.fragment"),m=Object.prototype.hasOwnProperty,n=f.__SECRET_INTERNALS_DO_NOT_USE_OR_YOU_WILL_BE_FIRED.ReactCurrentOwner,p={key:!0,ref:!0,__self:!0,__source:!0};
function q(c,a,g){var b,d={},e=null,h=null;void 0!==g&&(e=""+g);void 0!==a.key&&(e=""+a.key);void 0!==a.ref&&(h=a.ref);for(b in a)m.call(a,b)&&!p.hasOwnProperty(b)&&(d[b]=a[b]);if(c&&c.defaultProps)for(b in a=c.defaultProps,a)void 0===d[b]&&(d[b]=a[b]);return{$$typeof:k,type:c,key:e,ref:h,props:d,_owner:n.current}}exports.Fragment=l;exports.jsx=q;exports.jsxs=q;


/***/ }),

/***/ 848:
/***/ ((module, __unused_webpack_exports, __webpack_require__) => {

"use strict";


if (true) {
  module.exports = __webpack_require__(20);
} else {}


/***/ }),

/***/ 677:
/***/ ((module) => {

module.exports = (function() { return this["React"]; }());

/***/ }),

/***/ 419:
/***/ ((module) => {

module.exports = (function() { return this["lodash"]; }());

/***/ }),

/***/ 631:
/***/ ((module) => {

module.exports = (function() { return this["wp"]["apiFetch"]; }());

/***/ }),

/***/ 959:
/***/ ((module) => {

module.exports = (function() { return this["wp"]["components"]; }());

/***/ }),

/***/ 897:
/***/ ((module) => {

module.exports = (function() { return this["wp"]["compose"]; }());

/***/ }),

/***/ 488:
/***/ ((module) => {

module.exports = (function() { return this["wp"]["coreData"]; }());

/***/ }),

/***/ 987:
/***/ ((module) => {

module.exports = (function() { return this["wp"]["data"]; }());

/***/ }),

/***/ 53:
/***/ ((module) => {

module.exports = (function() { return this["wp"]["editPost"]; }());

/***/ }),

/***/ 601:
/***/ ((module) => {

module.exports = (function() { return this["wp"]["element"]; }());

/***/ }),

/***/ 75:
/***/ ((module) => {

module.exports = (function() { return this["wp"]["i18n"]; }());

/***/ }),

/***/ 860:
/***/ ((module) => {

module.exports = (function() { return this["wp"]["keycodes"]; }());

/***/ }),

/***/ 672:
/***/ ((module) => {

module.exports = (function() { return this["wp"]["notices"]; }());

/***/ }),

/***/ 125:
/***/ ((module) => {

module.exports = (function() { return this["wp"]["plugins"]; }());

/***/ }),

/***/ 933:
/***/ ((module) => {

module.exports = (function() { return this["wp"]["primitives"]; }());

/***/ }),

/***/ 172:
/***/ ((module) => {

module.exports = (function() { return this["wp"]["url"]; }());

/***/ }),

/***/ 942:
/***/ ((module, exports) => {

var __WEBPACK_AMD_DEFINE_ARRAY__, __WEBPACK_AMD_DEFINE_RESULT__;/*!
	Copyright (c) 2018 Jed Watson.
	Licensed under the MIT License (MIT), see
	http://jedwatson.github.io/classnames
*/
/* global define */

(function () {
	'use strict';

	var hasOwn = {}.hasOwnProperty;

	function classNames () {
		var classes = '';

		for (var i = 0; i < arguments.length; i++) {
			var arg = arguments[i];
			if (arg) {
				classes = appendClass(classes, parseValue(arg));
			}
		}

		return classes;
	}

	function parseValue (arg) {
		if (typeof arg === 'string' || typeof arg === 'number') {
			return arg;
		}

		if (typeof arg !== 'object') {
			return '';
		}

		if (Array.isArray(arg)) {
			return classNames.apply(null, arg);
		}

		if (arg.toString !== Object.prototype.toString && !arg.toString.toString().includes('[native code]')) {
			return arg.toString();
		}

		var classes = '';

		for (var key in arg) {
			if (hasOwn.call(arg, key) && arg[key]) {
				classes = appendClass(classes, key);
			}
		}

		return classes;
	}

	function appendClass (value, newClass) {
		if (!newClass) {
			return value;
		}
	
		if (value) {
			return value + ' ' + newClass;
		}
	
		return value + newClass;
	}

	if ( true && module.exports) {
		classNames.default = classNames;
		module.exports = classNames;
	} else if (true) {
		// register as 'classnames', consistent with npm package name
		!(__WEBPACK_AMD_DEFINE_ARRAY__ = [], __WEBPACK_AMD_DEFINE_RESULT__ = (function () {
			return classNames;
		}).apply(exports, __WEBPACK_AMD_DEFINE_ARRAY__),
		__WEBPACK_AMD_DEFINE_RESULT__ !== undefined && (module.exports = __WEBPACK_AMD_DEFINE_RESULT__));
	} else {}
}());


/***/ })

/******/ 	});
/************************************************************************/
/******/ 	// The module cache
/******/ 	var __webpack_module_cache__ = {};
/******/ 	
/******/ 	// The require function
/******/ 	function __webpack_require__(moduleId) {
/******/ 		// Check if module is in cache
/******/ 		var cachedModule = __webpack_module_cache__[moduleId];
/******/ 		if (cachedModule !== undefined) {
/******/ 			return cachedModule.exports;
/******/ 		}
/******/ 		// Create a new module (and put it into the cache)
/******/ 		var module = __webpack_module_cache__[moduleId] = {
/******/ 			// no module.id needed
/******/ 			// no module.loaded needed
/******/ 			exports: {}
/******/ 		};
/******/ 	
/******/ 		// Execute the module function
/******/ 		__webpack_modules__[moduleId](module, module.exports, __webpack_require__);
/******/ 	
/******/ 		// Return the exports of the module
/******/ 		return module.exports;
/******/ 	}
/******/ 	
/************************************************************************/
/******/ 	/* webpack/runtime/compat get default export */
/******/ 	(() => {
/******/ 		// getDefaultExport function for compatibility with non-harmony modules
/******/ 		__webpack_require__.n = (module) => {
/******/ 			var getter = module && module.__esModule ?
/******/ 				() => (module['default']) :
/******/ 				() => (module);
/******/ 			__webpack_require__.d(getter, { a: getter });
/******/ 			return getter;
/******/ 		};
/******/ 	})();
/******/ 	
/******/ 	/* webpack/runtime/define property getters */
/******/ 	(() => {
/******/ 		// define getter functions for harmony exports
/******/ 		__webpack_require__.d = (exports, definition) => {
/******/ 			for(var key in definition) {
/******/ 				if(__webpack_require__.o(definition, key) && !__webpack_require__.o(exports, key)) {
/******/ 					Object.defineProperty(exports, key, { enumerable: true, get: definition[key] });
/******/ 				}
/******/ 			}
/******/ 		};
/******/ 	})();
/******/ 	
/******/ 	/* webpack/runtime/hasOwnProperty shorthand */
/******/ 	(() => {
/******/ 		__webpack_require__.o = (obj, prop) => (Object.prototype.hasOwnProperty.call(obj, prop))
/******/ 	})();
/******/ 	
/************************************************************************/
var __webpack_exports__ = {};
// This entry needs to be wrapped in an IIFE because it needs to be in strict mode.
(() => {
"use strict";

// EXTERNAL MODULE: external {"this":["wp","editPost"]}
var external_this_wp_editPost_ = __webpack_require__(53);
// EXTERNAL MODULE: external {"this":["wp","plugins"]}
var external_this_wp_plugins_ = __webpack_require__(125);
// EXTERNAL MODULE: external {"this":["wp","primitives"]}
var external_this_wp_primitives_ = __webpack_require__(933);
// EXTERNAL MODULE: ./node_modules/react/jsx-runtime.js
var jsx_runtime = __webpack_require__(848);
;// ./js/src/packages/icons/library/duplication.js
/**
 * Duplication icon - admin-page Dashicon.
 */

/**
 * WordPress dependencies
 */


const isPrimitivesComponents = 'undefined' !== typeof wp.primitives;
const duplication = isPrimitivesComponents ? /*#__PURE__*/(0,jsx_runtime.jsx)(external_this_wp_primitives_.SVG, {
  width: "20",
  height: "20",
  xmlns: "http://www.w3.org/2000/svg",
  viewBox: "0 0 20 20",
  children: /*#__PURE__*/(0,jsx_runtime.jsx)(external_this_wp_primitives_.Path, {
    d: "M6 15v-13h10v13h-10zM5 16h8v2h-10v-13h2v11z"
  })
}) : 'admin-page';
/* harmony default export */ const library_duplication = (duplication);
;// ./js/src/packages/icons/library/pencil.js
/**
 * Pencil icon - edit Dashicon.
 */

/**
 * WordPress dependencies
 */


const pencil_isPrimitivesComponents = 'undefined' !== typeof wp.primitives;
const pencil = pencil_isPrimitivesComponents ? /*#__PURE__*/(0,jsx_runtime.jsx)(external_this_wp_primitives_.SVG, {
  width: "20",
  height: "20",
  xmlns: "http://www.w3.org/2000/svg",
  viewBox: "0 0 20 20",
  children: /*#__PURE__*/(0,jsx_runtime.jsx)(external_this_wp_primitives_.Path, {
    d: "M13.89 3.39l2.71 2.72c0.46 0.46 0.42 1.24 0.030 1.64l-8.010 8.020-5.56 1.16 1.16-5.58s7.6-7.63 7.99-8.030c0.39-0.39 1.22-0.39 1.68 0.070zM11.16 6.18l-5.59 5.61 1.11 1.11 5.54-5.65zM8.19 14.41l5.58-5.6-1.070-1.080-5.59 5.6z"
  })
}) : 'edit';
/* harmony default export */ const library_pencil = (pencil);
;// ./js/src/packages/icons/library/plus.js
/**
 * Plus icon - plus Dashicon.
 */

/**
 * WordPress dependencies
 */


const plus_isPrimitivesComponents = 'undefined' !== typeof wp.primitive;
const plus = plus_isPrimitivesComponents ? /*#__PURE__*/(0,jsx_runtime.jsx)(external_this_wp_primitives_.SVG, {
  width: "20",
  height: "20",
  xmlns: "http://www.w3.org/2000/svg",
  viewBox: "0 0 20 20",
  children: /*#__PURE__*/(0,jsx_runtime.jsx)(external_this_wp_primitives_.Path, {
    d: "M17 7v3h-5v5h-3v-5h-5v-3h5v-5h3v5h5z"
  })
}) : 'plus';
/* harmony default export */ const library_plus = (plus);
;// ./js/src/packages/icons/library/synchronization.js
/**
 * Synchronization icon - controls-repeat Dashicon.
 */

/**
 * WordPress dependencies
 */


const synchronization_isPrimitivesComponents = 'undefined' !== typeof wp.primitives;
const synchronization = synchronization_isPrimitivesComponents ? /*#__PURE__*/(0,jsx_runtime.jsx)(external_this_wp_primitives_.SVG, {
  width: "20",
  height: "20",
  xmlns: "http://www.w3.org/2000/svg",
  viewBox: "0 0 20 20",
  children: /*#__PURE__*/(0,jsx_runtime.jsx)(external_this_wp_primitives_.Path, {
    d: "M5 7v3l-2 1.5v-6.5h11v-2l4 3.010-4 2.99v-2h-9zM15 13v-3l2-1.5v6.5h-11v2l-4-3.010 4-2.99v2h9z"
  })
}) : 'controls-repeat';
/* harmony default export */ const library_synchronization = (synchronization);
;// ./js/src/packages/icons/library/translation.js
/**
 * Translation icon - translation Dashicon.
 */

/**
 * WordPress dependencies
 */


const translation_isPrimitivesComponents = 'undefined' !== typeof wp.primitives;
const translation = translation_isPrimitivesComponents ? /*#__PURE__*/(0,jsx_runtime.jsx)(external_this_wp_primitives_.SVG, {
  width: "20",
  height: "20",
  xmlns: "http://www.w3.org/2000/svg",
  viewBox: "0 0 20 20",
  children: /*#__PURE__*/(0,jsx_runtime.jsx)(external_this_wp_primitives_.Path, {
    d: "M11 7H9.49c-.63 0-1.25.3-1.59.7L7 5H4.13l-2.39 7h1.69l.74-2H7v4H2c-1.1 0-2-.9-2-2V5c0-1.1.9-2 2-2h7c1.1 0 2 .9 2 2v2zM6.51 9H4.49l1-2.93zM10 8h7c1.1 0 2 .9 2 2v7c0 1.1-.9 2-2 2h-7c-1.1 0-2-.9-2-2v-7c0-1.1.9-2 2-2zm7.25 5v-1.08h-3.17V9.75h-1.16v2.17H9.75V13h1.28c.11.85.56 1.85 1.28 2.62-.87.36-1.89.62-2.31.62-.01.02.22.97.2 1.46.84 0 2.21-.5 3.28-1.15 1.09.65 2.48 1.15 3.34 1.15-.02-.49.2-1.44.2-1.46-.43 0-1.49-.27-2.38-.63.7-.77 1.14-1.77 1.25-2.61h1.36zm-3.81 1.93c-.5-.46-.85-1.13-1.01-1.93h2.09c-.17.8-.51 1.47-1 1.93l-.04.03s-.03-.02-.04-.03z"
  })
}) : 'translation';
/* harmony default export */ const library_translation = (translation);
;// ./js/src/packages/icons/library/trash.js
/**
 * Trash icon - trash Dashicon.
 */

/**
 * WordPress dependencies
 */


const trash_isPrimitivesComponents = 'undefined' !== typeof wp.primitives;
const trash_trash = trash_isPrimitivesComponents ? /*#__PURE__*/(0,jsx_runtime.jsx)(external_this_wp_primitives_.SVG, {
  width: "20",
  height: "20",
  xmlns: "http://www.w3.org/2000/svg",
  viewBox: "0 0 20 20",
  children: /*#__PURE__*/(0,jsx_runtime.jsx)(external_this_wp_primitives_.Path, {
    d: "M12 4h3c.6 0 1 .4 1 1v1H3V5c0-.6.5-1 1-1h3c.2-1.1 1.3-2 2.5-2s2.3.9 2.5 2zM8 4h3c-.2-.6-.9-1-1.5-1S8.2 3.4 8 4zM4 7h11l-.9 10.1c0 .5-.5.9-1 .9H5.9c-.5 0-.9-.4-1-.9L4 7z"
  })
}) : 'trash';
/* harmony default export */ const library_trash = ((/* unused pure expression or super */ null && (trash_trash)));
;// ./js/src/packages/icons/library/star.js
/**
 * Star icon - star-filled Dashicon.
 */

/**
 * WordPress dependencies
 */


const star_isPrimitivesComponents = 'undefined' !== typeof wp.primitives;
const star_star = star_isPrimitivesComponents ? /*#__PURE__*/(0,jsx_runtime.jsx)(external_this_wp_primitives_.SVG, {
  width: "20",
  height: "20",
  xmlns: "http://www.w3.org/2000/svg",
  viewBox: "0 0 20 20",
  children: /*#__PURE__*/(0,jsx_runtime.jsx)(external_this_wp_primitives_.Path, {
    d: "m10 1 3 6 6 .75-4.12 4.62L16 19l-6-3-6 3 1.13-6.63L1 7.75 7 7z"
  })
}) : 'star-filled';
/* harmony default export */ const library_star = ((/* unused pure expression or super */ null && (star_star)));
;// ./js/src/packages/icons/library/submenu.js
/**
 * Submenu icon
 */

/**
 * WordPress dependencies
 */


const submenu_isPrimitivesComponents = 'undefined' !== typeof wp.primitives;
const SubmenuIcon = () => submenu_isPrimitivesComponents ? /*#__PURE__*/_jsx(SVG, {
  xmlns: "http://www.w3.org/2000/svg",
  width: "12",
  height: "12",
  viewBox: "0 0 12 12",
  fill: "none",
  children: /*#__PURE__*/_jsx(Path, {
    d: "M1.50002 4L6.00002 8L10.5 4",
    strokeWidth: "1.5"
  })
}) : 'submenu';
/* harmony default export */ const submenu = ((/* unused pure expression or super */ null && (SubmenuIcon)));
// EXTERNAL MODULE: external {"this":["wp","components"]}
var external_this_wp_components_ = __webpack_require__(959);
// EXTERNAL MODULE: external {"this":["wp","i18n"]}
var external_this_wp_i18n_ = __webpack_require__(75);
;// ./js/src/packages/icons/library/default-lang.js
/**
 * WordPress dependencies
 */



/**
 * Internal dependencies
 */


const default_lang_DefaultLangIcon = () => /*#__PURE__*/_jsxs(_Fragment, {
  children: [/*#__PURE__*/_jsx(Icon, {
    icon: star,
    className: "pll-default-lang-icon"
  }), /*#__PURE__*/_jsx("span", {
    className: "screen-reader-text",
    children: __('Default language.', 'polylang-pro')
  })]
});
/* harmony default export */ const default_lang = ((/* unused pure expression or super */ null && (default_lang_DefaultLangIcon)));
;// ./js/src/packages/icons/index.js
/**
 * Icons library
 */










;// ./js/src/editors/common/app.js
/**
 * WordPress Dependencies.
 */


/**
 * Internal Dependencies.
 */


const App = ({
  sidebar,
  sidebarName,
  onPromise,
  children
}) => {
  onPromise().then(() => {
    (0,external_this_wp_plugins_.registerPlugin)(sidebarName, {
      icon: library_translation,
      render: sidebar
    });
  }, reason => {
    console.info(reason); // eslint-disable-line no-console
  });
  return /*#__PURE__*/(0,jsx_runtime.jsx)(jsx_runtime.Fragment, {
    children: children
  });
};
/* harmony default export */ const app = (App);
// EXTERNAL MODULE: external {"this":["wp","data"]}
var external_this_wp_data_ = __webpack_require__(987);
// EXTERNAL MODULE: external {"this":["wp","notices"]}
var external_this_wp_notices_ = __webpack_require__(672);
;// ./js/src/editors/common/components/display-notices/index.js
/**
 * WordPress Dependencies.
 */


const {
  stripTags
} = wp.sanitize;
const DisplayNotices = ({
  notices
}) => {
  const {
    createErrorNotice,
    createInfoNotice,
    createSuccessNotice,
    createWarningNotice
  } = (0,external_this_wp_data_.useDispatch)(external_this_wp_notices_.store);
  if (!notices) {
    return null;
  }
  notices.forEach(notice => {
    const noticeOptions = {
      type: 'snackbar',
      explicitDismiss: true
    };
    const message = stripTags(notice.message);
    switch (notice.type) {
      case 'error':
        createErrorNotice(message, noticeOptions);
        break;
      case 'info':
        createInfoNotice(message, noticeOptions);
        break;
      case 'success':
        createSuccessNotice(message, noticeOptions);
        break;
      case 'warning':
        createWarningNotice(message, noticeOptions);
        break;
    }
  });

  /**
   * Renderless component.
   */
  return null;
};
/* harmony default export */ const display_notices = (DisplayNotices);
;// ./js/src/editors/common/components/sidebar/index.js
/**
 * WordPress dependencies
 */


const Sidebar = ({
  SidebarSlot,
  MoreMenuItemSlot,
  sidebarName,
  children
}) => {
  const title = (0,external_this_wp_i18n_.__)('Languages', 'polylang-pro');
  return /*#__PURE__*/(0,jsx_runtime.jsxs)(jsx_runtime.Fragment, {
    children: [/*#__PURE__*/(0,jsx_runtime.jsx)(SidebarSlot, {
      name: sidebarName,
      title: title,
      children: children
    }), /*#__PURE__*/(0,jsx_runtime.jsx)(MoreMenuItemSlot, {
      target: sidebarName,
      children: title
    })]
  });
};
/* harmony default export */ const sidebar = (Sidebar);
// EXTERNAL MODULE: external {"this":["wp","element"]}
var external_this_wp_element_ = __webpack_require__(601);
// EXTERNAL MODULE: external {"this":["wp","url"]}
var external_this_wp_url_ = __webpack_require__(172);
// EXTERNAL MODULE: external "lodash"
var external_lodash_ = __webpack_require__(419);
;// ./js/src/packages/components/library/language-flag.js
/* eslint-disable import/no-extraneous-dependencies */
/**
 * External dependencies.
 */


/**
 * Internal dependencies.
 */


/**
 * Displays a flag icon for a given language.
 *
 * @since 3.1
 * @since 3.2 Now its own component.
 *
 * @param {Object} props          LanguageFlag props.
 * @param {Object} props.language Language object for the flag.
 *
 * @return {React.Component} Flag component.
 */

function language_flag_LanguageFlag({
  language
}) {
  if (!(0,external_lodash_.isNil)(language)) {
    return !(0,external_lodash_.isEmpty)(language.flag_url) ? /*#__PURE__*/(0,jsx_runtime.jsx)("span", {
      className: "pll-select-flag",
      children: /*#__PURE__*/(0,jsx_runtime.jsx)("img", {
        src: language.flag_url,
        alt: language.name,
        title: language.name,
        className: "flag"
      })
    }) : /*#__PURE__*/(0,jsx_runtime.jsxs)("abbr", {
      children: [language.slug, /*#__PURE__*/(0,jsx_runtime.jsx)("span", {
        className: "screen-reader-text",
        children: language.name
      })]
    });
  }
  return /*#__PURE__*/(0,jsx_runtime.jsx)("span", {
    className: "pll-translation-icon",
    children: library_translation
  });
}
/* harmony default export */ const language_flag = (language_flag_LanguageFlag);
;// ./js/src/packages/components/library/language-dropdown.js
/*
 * Internal dependencies.
 */


/**
 * Displays a dropdown to select a language.
 *
 * @since 3.1
 *
 * @param {Object}          props                  LanguageDropdown props.
 * @param {Function}        props.handleChange     Callback to be executed when language changes.
 * @param {React.Component} props.children         Child components to be used as select options.
 * @param {Object}          props.selectedLanguage An object representing a Polylang Language. Default to null.
 * @param {string}          props.defaultValue     Value to be selected if the selected language is not provided. Default to an empty string.
 *
 * @return {Object} A dropdown selector for languages.
 */

function LanguageDropdown({
  handleChange,
  children,
  selectedLanguage = null,
  defaultValue = ''
}) {
  const selectedLanguageSlug = selectedLanguage?.slug ? selectedLanguage.slug : defaultValue;
  return /*#__PURE__*/(0,jsx_runtime.jsxs)("div", {
    id: "select-post-language",
    children: [/*#__PURE__*/(0,jsx_runtime.jsx)(language_flag, {
      language: selectedLanguage
    }), children && /*#__PURE__*/(0,jsx_runtime.jsx)("select", {
      value: selectedLanguageSlug,
      onChange: event => handleChange(event),
      id: "pll_post_lang_choice",
      name: "pll_post_lang_choice",
      className: "post_lang_choice",
      children: children
    })]
  });
}

/**
 * Maps languages objects as options for a <select> tag.
 *
 * @since 3.1
 *
 * @param {Object} props           LanguagesOptionsList props.
 * @param {Object} props.languages An iterable object containing languages objects.
 *
 * @return {Object} A list of <option> tags to be used in a <select> tag.
 */
function LanguagesOptionsList({
  languages
}) {
  return Array.from(languages.values()).map(({
    slug,
    name,
    w3c
  }) => /*#__PURE__*/(0,jsx_runtime.jsx)("option", {
    value: slug,
    lang: w3c,
    children: name
  }, slug));
}

;// ./js/src/packages/components/index.js
/**
 * UI Components Library
 */




;// ./js/src/editors/common/settings.js
/**
 * Module Constants
 */

const settings_MODULE_KEY = 'pll/metabox';
const MODULE_CORE_EDITOR_KEY = 'core/editor';
const settings_MODULE_SITE_EDITOR_KEY = 'core/edit-site';
const MODULE_POST_EDITOR_KEY = 'core/edit-post';
const settings_MODULE_CORE_KEY = 'core';
const DEFAULT_STATE = {
  languages: [],
  selectedLanguage: {},
  translatedPosts: {},
  fromPost: null,
  currentTemplatePart: {}
};
const settings_UNTRANSLATABLE_POST_TYPE = ['wp_template', 'wp_global_styles'];
const settings_POST_TYPE_WITH_TRASH = (/* unused pure expression or super */ null && (['page']));
const settings_TEMPLATE_PART_SLUG_SEPARATOR = '___'; // Its value must be synchronized with its equivalent in PHP @see PLL_FSE_Template_Slug::SEPARATOR.
const settings_TEMPLATE_PART_SLUG_CHECK_LANGUAGE_PATTERN = '[a-z][a-z0-9_-]*'; // Its value must be synchronized with it equivalent in PHP @see PLL_FSE_Template_Slug::SEPARATOR.

;// ./js/src/editors/common/components/language-item/index.js
/**
 * WordPress dependencies
 */



/**
 * Internal dependencies
 */




const language_item_LanguageItem = ({
  language,
  currentPost
}) => {
  var _ref;
  const postType = useSelect(select => select(MODULE_CORE_KEY).getPostType(currentPost.type), [currentPost]);
  return /*#__PURE__*/_jsxs(_Fragment, {
    children: [/*#__PURE__*/_jsx("p", {
      children: /*#__PURE__*/_jsx("strong", {
        children: __('Language', 'polylang-pro')
      })
    }), /*#__PURE__*/_jsxs("div", {
      className: "pll-language-item",
      children: [/*#__PURE__*/_jsx(LanguageFlag, {
        language: language
      }), /*#__PURE__*/_jsx("span", {
        className: "pll-language-name",
        children: language.name
      }), language.is_default && /*#__PURE__*/_jsx(DefaultLangIcon, {})]
    }), language.is_default && /*#__PURE__*/_jsx("div", {
      children: /*#__PURE__*/_jsx("span", {
        className: "pll-metabox-info",
        children: (_ref = 'wp_template_part' === postType?.slug) !== null && _ref !== void 0 ? _ref : __('This template part is used for languages that have not yet been translated.', 'polylang-pro')
      })
    })]
  });
};
/* harmony default export */ const language_item = ((/* unused pure expression or super */ null && (language_item_LanguageItem)));
;// ./js/src/editors/common/components/metaboxes/wrapper/index.js

const MetaboxWrapper = ({
  children
}) => {
  return /*#__PURE__*/(0,jsx_runtime.jsx)("div", {
    className: "components-panel__body is-opened",
    children: /*#__PURE__*/(0,jsx_runtime.jsx)("div", {
      className: "pll-metabox-location",
      children: children
    })
  });
};
/* harmony default export */ const wrapper = (MetaboxWrapper);
;// ./js/src/editors/common/components/not-translatable-notice/index.js
/**
 * WordPress Dependencies.
 */


const NotTranslatableNotice = ({
  postType
}) => {
  if ('wp_template' === postType) {
    return /*#__PURE__*/(0,jsx_runtime.jsx)("div", {
      className: "pll-metabox-error components-notice is-warning",
      children: (0,external_this_wp_i18n_.__)('Templates are not translatable, only template parts are.', 'polylang-pro')
    });
  }
  return /*#__PURE__*/(0,jsx_runtime.jsx)("div", {
    className: "pll-metabox-error components-notice is-warning",
    children: (0,external_this_wp_i18n_.__)('This entity is not translatable.', 'polylang-pro')
  });
};
/* harmony default export */ const not_translatable_notice = (NotTranslatableNotice);
;// ./js/src/editors/common/components/metaboxes/container/index.js
/**
 * WordPress Dependencies.
 */


/**
 * Internal Dependencies.
 */



const container_MetaboxContainer = ({
  isError,
  isAllowedPostType,
  postType,
  children
}) => {
  if (!isAllowedPostType) {
    return /*#__PURE__*/(0,jsx_runtime.jsx)(wrapper, {
      children: /*#__PURE__*/(0,jsx_runtime.jsx)(not_translatable_notice, {
        postType: postType
      })
    });
  }
  if (isError) {
    return /*#__PURE__*/(0,jsx_runtime.jsx)(wrapper, {
      children: /*#__PURE__*/(0,jsx_runtime.jsx)("div", {
        className: "pll-metabox-error components-notice is-error",
        children: (0,external_this_wp_i18n_.__)('Unable to retrieve the content language', 'polylang-pro')
      })
    });
  }
  return /*#__PURE__*/(0,jsx_runtime.jsx)(wrapper, {
    children: children
  });
};
/* harmony default export */ const container = (container_MetaboxContainer);
;// ./js/src/editors/common/components/translations-table/cells/add-or-edit/index.js

const add_or_edit_AddOrEditCell = ({
  children
}) => {
  return /*#__PURE__*/(0,jsx_runtime.jsx)("td", {
    className: "pll-edit-column pll-column-icon",
    children: children
  });
};
/* harmony default export */ const add_or_edit = (add_or_edit_AddOrEditCell);
;// ./js/src/editors/common/components/translations-table/cells/default-language/index.js
/**
 * Internal Dependencies
 */


const default_language_DefaultLanguageCell = ({
  isDefault
}) => {
  return /*#__PURE__*/_jsx("td", {
    className: "pll-default-lang-column pll-column-icon",
    children: isDefault && /*#__PURE__*/_jsx(DefaultLangIcon, {})
  });
};
/* harmony default export */ const default_language = ((/* unused pure expression or super */ null && (default_language_DefaultLanguageCell)));
;// ./js/src/editors/common/components/translations-table/cells/delete/index.js

const delete_DeleteCell = ({
  children
}) => {
  return /*#__PURE__*/_jsx("td", {
    className: "pll-delete-column pll-column-icon",
    children: children
  });
};
/* harmony default export */ const cells_delete = ((/* unused pure expression or super */ null && (delete_DeleteCell)));
;// ./js/src/editors/common/components/translations-table/cells/flag/index.js
/**
 * External dependencies.
 */


const FlagCell = ({
  language
}) => {
  return /*#__PURE__*/(0,jsx_runtime.jsx)("th", {
    className: "pll-language-column",
    children: !(0,external_lodash_.isEmpty)(language.flag) ? /*#__PURE__*/(0,jsx_runtime.jsx)("span", {
      className: "pll-select-flag flag",
      children: /*#__PURE__*/(0,jsx_runtime.jsx)("img", {
        src: language.flag_url,
        alt: language.name,
        title: language.name
      })
    }) : /*#__PURE__*/(0,jsx_runtime.jsxs)("abbr", {
      children: [language.slug, /*#__PURE__*/(0,jsx_runtime.jsx)("span", {
        className: "screen-reader-text",
        children: language.name
      })]
    })
  });
};
/* harmony default export */ const flag = (FlagCell);
;// ./js/src/editors/common/components/translations-table/cells/synchronization/index.js

const SynchronizationCell = ({
  children
}) => {
  return /*#__PURE__*/(0,jsx_runtime.jsx)("td", {
    className: "pll-sync-column pll-column-icon",
    children: children
  });
};
/* harmony default export */ const cells_synchronization = (SynchronizationCell);
;// ./js/src/editors/common/components/translations-table/cells/translation-input/index.js

const translation_input_TranslationInputCell = ({
  children
}) => {
  return /*#__PURE__*/(0,jsx_runtime.jsx)("td", {
    className: "pll-translation-column",
    children: children
  });
};
/* harmony default export */ const translation_input = (translation_input_TranslationInputCell);
;// ./js/src/editors/common/components/translations-table/cells/index.js
/**
 * Cells components for translations table.
 */







;// ./js/src/editors/common/components/buttons/add/index.js
/**
 * WordPress dependencies
 */



/**
 * Internal dependencies
 */


/**
 * Renders a button to add new translation.
 *
 * @param {Object}   props                Component props.
 * @param {Object}   props.language       Language of the new translation.
 * @param {string}   props.href           URL to add a new translation, pass '#' if managed in REST.
 * @param {Function} props.handleAddClick Callback to add a translation, default to null.
 * @return {React.ReactElement} Button component.
 */

const add_AddButton = ({
  language,
  href,
  handleAddClick = null
}) => {
  const accessibilityText = (0,external_this_wp_i18n_.sprintf)(
  // translators: %s is a native language name.
  (0,external_this_wp_i18n_.__)('Add a translation in %s', 'polylang-pro'), language.name);
  return /*#__PURE__*/(0,jsx_runtime.jsx)(external_this_wp_components_.Button, {
    href: href,
    icon: library_plus,
    label: accessibilityText,
    className: `pll-button`,
    onClick: handleAddClick,
    "data-target-language": language.slug // Store the target language to retrieve it through the click event.
    ,
    children: /*#__PURE__*/(0,jsx_runtime.jsx)("span", {
      className: "screen-reader-text",
      children: accessibilityText
    })
  });
};
/* harmony default export */ const add = (add_AddButton);
;// ./js/src/editors/common/components/buttons/delete/index.js
/**
 * WordPress dependencies
 */



/**
 * Internal dependencies
 */


const delete_DeleteButton = ({
  language,
  disabled,
  onClick
}) => {
  const translationScreenReaderText = sprintf(
  // translators: %s is a native language name.
  __('Delete the translation in %s', 'polylang-pro'), language.name);
  return /*#__PURE__*/_jsx(Button, {
    icon: trash,
    label: translationScreenReaderText,
    disabled: disabled,
    className: "pll-button",
    onClick: onClick,
    children: /*#__PURE__*/_jsx("span", {
      className: "screen-reader-text",
      children: translationScreenReaderText
    })
  });
};
/* harmony default export */ const buttons_delete = ((/* unused pure expression or super */ null && (delete_DeleteButton)));
;// ./js/src/editors/common/components/buttons/persisting-user-data/index.js
/**
 * WordPress dependencies
 */




/**
 * Internal dependencies
 */


const PersistingUserDataButton = ({
  id,
  postType,
  userPreferenceName,
  activeLabel,
  inactiveLabel,
  icon
}) => {
  const currentUser = (0,external_this_wp_data_.useSelect)(select => select(settings_MODULE_KEY).getCurrentUser(), []);
  const buttonInitialState = () => {
    if (undefined === currentUser || undefined === currentUser[userPreferenceName] || undefined === currentUser[userPreferenceName][postType]) {
      return false;
    }
    return currentUser[userPreferenceName][postType];
  };
  const [isActive, setIsActive] = (0,external_this_wp_element_.useState)(buttonInitialState);
  const label = isActive ? activeLabel : inactiveLabel;
  const saveStateInUserPreferences = () => {
    /*
     * If the user meta is an empty array, it has never been created.
     * So we convert it as an object to be able to update correctly its value in DB.
     */
    if (undefined === currentUser[userPreferenceName] || Array.isArray(currentUser[userPreferenceName]) && currentUser[userPreferenceName].length === 0) {
      currentUser[userPreferenceName] = {};
    }
    // Updates currentUser in store.
    currentUser[userPreferenceName][postType] = !isActive;
    const data = {};
    data[userPreferenceName] = currentUser[userPreferenceName];
    (0,external_this_wp_data_.dispatch)(settings_MODULE_KEY).setCurrentUser(data, true);
    // Updates component state.
    setIsActive(isActiveState => !isActiveState);
  };
  return /*#__PURE__*/(0,jsx_runtime.jsx)(external_this_wp_components_.Button, {
    id: id,
    className: `pll-button pll-before-post-translations-button ${isActive && `wp-ui-text-highlight`}`,
    onClick: saveStateInUserPreferences,
    icon: icon,
    label: label,
    children: /*#__PURE__*/(0,jsx_runtime.jsx)("span", {
      className: "screen-reader-text",
      children: label
    })
  });
};
/* harmony default export */ const persisting_user_data = (PersistingUserDataButton);
;// ./js/src/editors/common/components/buttons/machine-translation/index.js
/**
 * WordPress dependencies
 */



/**
 * Internal dependencies
 */


const MachineTranslationButton = props => {
  const {
    path_d,
    ...iconProps
  } = props.icon; // eslint-disable-line camelcase

  const iconElement = {
    type: 'svg',
    props: {
      ...iconProps,
      children: /*#__PURE__*/(0,jsx_runtime.jsx)(external_this_wp_primitives_.Path, {
        d: path_d
      }) // eslint-disable-line camelcase
    }
  };
  const newProps = {
    ...props,
    id: 'pll-machine-translation',
    userPreferenceName: `pll_machine_translation_${props.slug}`,
    activeLabel: (0,external_this_wp_i18n_.sprintf)(/* translators: %s is the name of the machine translation service. */
    (0,external_this_wp_i18n_.__)('Deactivate %s machine translation', 'polylang-pro'), props.name),
    inactiveLabel: (0,external_this_wp_i18n_.sprintf)(/* translators: %s is the name of the machine translation service. */
    (0,external_this_wp_i18n_.__)('Activate %s machine translation', 'polylang-pro'), props.name),
    icon: iconElement
  };
  return /*#__PURE__*/(0,jsx_runtime.jsx)(persisting_user_data, {
    ...newProps
  });
};
/* harmony default export */ const machine_translation = (MachineTranslationButton);
;// ./js/src/editors/common/components/buttons/duplicate/index.js
/**
 * WordPress dependencies
 */


/**
 * Internal dependencies
 */



const DuplicateButton = props => {
  const newProps = {
    ...props,
    id: 'pll-duplicate',
    userPreferenceName: 'pll_duplicate_content',
    /* translators: accessibility text */
    activeLabel: (0,external_this_wp_i18n_.__)('Deactivate the content duplication', 'polylang-pro'),
    /* translators: accessibility text */
    inactiveLabel: (0,external_this_wp_i18n_.__)('Activate the content duplication', 'polylang-pro'),
    icon: library_duplication
  };
  return /*#__PURE__*/(0,jsx_runtime.jsx)(persisting_user_data, {
    ...newProps
  });
};
/* harmony default export */ const duplicate = (DuplicateButton);
;// ./js/src/editors/common/components/buttons/edit/index.js
/**
 * WordPress dependencies
 */



/**
 * Internal dependencies
 */


/**
 * Renders a button to edit existing translation.
 *
 * @param {Object}   props                 Component props.
 * @param {Object}   props.language        Language of the existing translation.
 * @param {Function} props.handleEditClick Callback to edit a translation, default to null.
 * @param {string}   props.href            URL to edit a new translation, pass '#' if managed in REST.
 * @return {React.ReactElement} Button component.
 */

const edit_EditButton = ({
  language,
  href,
  handleEditClick = null
}) => {
  const accessibilityText = (0,external_this_wp_i18n_.sprintf)(/* translators: accessibility text, %s is a native language name. For example Deutsch for German or Français for french. */
  (0,external_this_wp_i18n_.__)('Edit the translation in %s', 'polylang-pro'), language.name);
  return /*#__PURE__*/(0,jsx_runtime.jsx)(external_this_wp_components_.Button, {
    href: href,
    icon: library_pencil,
    label: accessibilityText,
    className: `pll-button`,
    onClick: handleEditClick,
    "data-target-language": language.slug // Store the target language to retrieve it through the click event.
    ,
    children: /*#__PURE__*/(0,jsx_runtime.jsx)("span", {
      className: "screen-reader-text",
      children: accessibilityText
    })
  });
};
/* harmony default export */ const edit = (edit_EditButton);
;// ./js/src/editors/common/store/utils.js
/**
 * WordPress Dependencies
 */



/**
 * Internal dependencies
 */



/**
 * Wait for the whole post block editor context has been initialized: current post loaded and languages list initialized.
 */
const isBlockPostEditorContextInitialized = () => {
  if ((0,external_lodash_.isNil)((0,external_this_wp_data_.select)(MODULE_CORE_EDITOR_KEY))) {
    return Promise.reject("Polylang languages panel can't be initialized because block editor isn't fully initialized.");
  }

  // save url params especially when a new translation is creating
  saveURLParams();
  // call to getCurrentUser to force call to resolvers and initialize state
  (0,external_this_wp_data_.select)(settings_MODULE_KEY).getCurrentUser();

  /**
   * Set a promise for waiting for the current post has been fully loaded before making other processes.
   */
  const isCurrentPostLoaded = new Promise(function (resolve) {
    const unsubscribe = (0,external_this_wp_data_.subscribe)(function () {
      const currentPost = (0,external_this_wp_data_.select)(MODULE_CORE_EDITOR_KEY).getCurrentPost();
      if (!(0,external_lodash_.isEmpty)(currentPost)) {
        unsubscribe();
        resolve();
      }
    });
  });

  // Wait for current post has been loaded and languages list initialized.
  return Promise.all([isCurrentPostLoaded, isLanguagesinitialized()]).then(function () {
    // If we come from another post for creating a new one, we have to update translations from the original post.
    const fromPost = (0,external_this_wp_data_.select)(settings_MODULE_KEY).getFromPost();
    if (!(0,external_lodash_.isNil)(fromPost) && !(0,external_lodash_.isNil)(fromPost.id)) {
      const lang = (0,external_this_wp_data_.select)(MODULE_CORE_EDITOR_KEY).getEditedPostAttribute('lang');
      const translations = (0,external_this_wp_data_.select)(MODULE_CORE_EDITOR_KEY).getEditedPostAttribute('translations');
      const translationTable = (0,external_this_wp_data_.select)(MODULE_CORE_EDITOR_KEY).getEditedPostAttribute('translations_table');
      const translatedPosts = getTranslatedPosts(translations, translationTable, lang);
      (0,external_this_wp_data_.dispatch)(MODULE_CORE_EDITOR_KEY).editPost({
        translations: convertMapToObject(translatedPosts)
      });
    }
  });
};

/**
 * Wait for the whole site editor context to be initialized: current template loaded and languages list initialized.
 */
const isSiteEditorContextInitialized = () => {
  // save url params especially when a new translation is creating
  saveURLParams();
  // call to getCurrentUser to force call to resolvers and initialize state
  select(MODULE_KEY).getCurrentUser();

  /**
   * Set a promise to wait for the current template to be fully loaded before making other processes.
   * It allows to see if both Site Editor and Core stores are available (@see getCurrentPostFromDataStore()).
   */
  const isTemplatePartLoaded = new Promise(function (resolve) {
    const unsubscribe = subscribe(function () {
      const store = select(MODULE_SITE_EDITOR_KEY);
      if (store) {
        unsubscribe();
        resolve();
      }
    });
  });
  return Promise.all([isTemplatePartLoaded, isLanguagesinitialized()]);
};

/**
 * Returns a promise fulfilled when the languages list is correctly initialized before making other processes.
 */
const isLanguagesinitialized = () => new Promise(function (resolve) {
  const unsubscribe = (0,external_this_wp_data_.subscribe)(function () {
    const languages = (0,external_this_wp_data_.select)(settings_MODULE_KEY)?.getLanguages();
    if (languages?.size > 0) {
      unsubscribe();
      resolve();
    }
  });
});

/**
 * Save query string parameters from URL. They could be needed after
 * They could be null if they does not exist
 */
function saveURLParams() {
  // Variable window.location.search isn't use directly
  // Function getSearchParams return an URLSearchParams object for manipulating each parameter
  // Each of them are sanitized below
  const searchParams = getSearchParams();
  if (null !== searchParams) {
    (0,external_this_wp_data_.dispatch)(settings_MODULE_KEY).setFromPost({
      id: wp.sanitize.stripTagsAndEncodeText(searchParams.get('from_post')),
      postType: wp.sanitize.stripTagsAndEncodeText(searchParams.get('post_type')),
      newLanguage: wp.sanitize.stripTagsAndEncodeText(searchParams.get('new_lang'))
    });
  }
}
const getEditedPostContextWithLegacy = () => {
  const siteEditorSelector = (0,external_this_wp_data_.select)(settings_MODULE_SITE_EDITOR_KEY);

  /**
   * Return null when called from our apiFetch middleware without a properly loaded store.
   */
  if (!siteEditorSelector) {
    return null;
  }
  const _context = {
    postId: siteEditorSelector.getEditedPostId(),
    postType: siteEditorSelector.getEditedPostType()
  };
  if (siteEditorSelector.hasOwnProperty('getEditedPostContext')) {
    const context = siteEditorSelector.getEditedPostContext();
    return context?.postType && context?.postId ? context : _context;
  }

  /**
   * Backward compatibility with WordPress < 6.3 where `getEditedPostContext()` doesn't exist yet.
   */
  return _context;
};

/**
 * Gets the current post using the Site Editor store and the Core store.
 *
 * @return {object|null} The current post object, `null` if none found.
 */
const utils_getCurrentPostFromDataStore = () => {
  const editedContext = getEditedPostContextWithLegacy();
  return null === editedContext ? null : (0,external_this_wp_data_.select)(settings_MODULE_CORE_KEY).getEntityRecord('postType', editedContext.postType, editedContext.postId);
};
;// ./js/src/editors/common/utils.js
/**
 * WordPress Dependencies
 */




/**
 * Internal dependencies
 */



/**
 * Converts array of object to a map.
 *
 * @param {Array} array Array to convert.
 * @param {*}     key   The key in the object used as key to build the map.
 * @return {Map} Converted array.
 */
function convertArrayToMap(array, key) {
  const arrayMap = new Map();
  array.reduce(function (accumulator, currentValue) {
    accumulator.set(currentValue[key], currentValue);
    return accumulator;
  }, arrayMap);
  return arrayMap;
}

/**
 * Converts map to an associative array.
 *
 * @param {Map} mapToConvert The map to convert.
 * @return {Object} Converted map.
 */
function convertMapToObject(mapToConvert) {
  const object = {};
  mapToConvert.forEach(function (value, key) {
    this[key] = (0,external_lodash_.isBoolean)(value) ? value.toString() : value;
  }, object);
  return object;
}

/**
 * Checks whether the current screen is the site editor.
 * Takes in account if Gutenberg is activated.
 *
 * @return {boolean} True if site editor screen, false otherwise.
 */
function isSiteBlockEditor() {
  return !!(document.getElementById('site-editor') || document.getElementById('edit-site-editor'));
}

/**
 * Returns the post type URL for REST API calls or undefined if the user hasn't the rights.
 *
 * @param {string} name The post type name.
 * @return {string|undefined} URL of the given post type, undefined if not available.
 */
function getPostsUrl(name) {
  const postTypes = (0,external_this_wp_data_.select)('core').getEntitiesConfig('postType');
  const postType = (0,external_lodash_.find)(postTypes, {
    name
  });
  return postType?.baseURL;
}

/**
 * Gets all query string parameters and convert them in a URLSearchParams object.
 *
 * @return {URLSearchParams|null} Search parameters object, null if none.
 */
function getSearchParams() {
  // Variable window.location.search is just read for creating and returning a URLSearchParams object to be able to manipulate it more easily.
  // eslint-disable-next-line prettier/prettier
  if (!(0,external_lodash_.isEmpty)(window.location.search)) {
    // phpcs:ignore WordPressVIPMinimum.JS.Window.location
    return new URLSearchParams(window.location.search); // phpcs:ignore WordPressVIPMinimum.JS.Window.location
  }
  return null;
}

/**
 * Gets selected language.
 *
 * @param {string} lang The post language code.
 * @return {Object|null} The selected language, null otherwise.
 */
function utils_getSelectedLanguage(lang) {
  const languages = (0,external_this_wp_data_.select)(settings_MODULE_KEY).getLanguages();
  // Pick up this language as selected in languages list
  if (languages) {
    return languages.get(lang);
  }
  return null;
}

/**
 * Gets the default language.
 *
 * @return {Object} The default Language.
 */
function getDefaultLanguage() {
  const languages = (0,external_this_wp_data_.select)(settings_MODULE_KEY).getLanguages();
  return Array.from(languages.values()).find(lang => lang.is_default);
}

/**
 * Checks if the given language is the default one.
 *
 * @param {string} lang The language code to compare with.
 * @return {boolean} True if the given language is the default one.
 */
function isDefaultLanguage(lang) {
  return lang === getDefaultLanguage().slug;
}

/**
 * Gets translated posts.
 *
 * @param {Object}                  translations          The translated posts object with language codes as keys and ids as values.
 * @param {Object.<string, Object>} translationsTableData The translations table data with language codes as keys and data object as values.
 * @param {string}                  lang                  The language slug.
 * @return {Map} Map of the translated posts.
 */
function getTranslatedPosts(translations, translationsTableData, lang) {
  const translationsTable = utils_getTranslationsTable(translationsTableData, lang);
  const fromPost = (0,external_this_wp_data_.select)(settings_MODULE_KEY).getFromPost();
  let translatedPosts = new Map(Object.entries([]));
  if (!(0,external_lodash_.isUndefined)(translations)) {
    translatedPosts = new Map(Object.entries(translations));
  }
  // If we come from another post for creating a new one, we have to update translated posts from the original post
  // to be able to update translations attribute of the post
  if (!(0,external_lodash_.isNil)(fromPost) && !(0,external_lodash_.isNil)(fromPost.id)) {
    translationsTable.forEach((translationData, languageSlug) => {
      if (!(0,external_lodash_.isNil)(translationData.translated_post) && !(0,external_lodash_.isNil)(translationData.translated_post.id)) {
        translatedPosts.set(languageSlug, translationData.translated_post.id);
      }
    });
  }
  return translatedPosts;
}

/**
 * Gets synchronized posts.
 *
 * @param {Object.<string, boolean>} syncPosts The synchronized posts object with language codes as keys and boolean values to say if the post is synchronized or not.
 * @return {Map} Map of synchronized posts.
 */
function getSynchronizedPosts(syncPosts) {
  let synchronizedPosts = new Map(Object.entries([]));
  if (!(0,external_lodash_.isUndefined)(syncPosts)) {
    synchronizedPosts = new Map(Object.entries(syncPosts));
  }
  return synchronizedPosts;
}

/**
 * Gets translations table.
 *
 * @param {Object.<string, Object>} translationsTableData The translations table data object with language codes as keys and data object as values.
 * @return {Map} Map of the translation table.
 */
function utils_getTranslationsTable(translationsTableData) {
  let translationsTable = new Map(Object.entries([]));
  // get translations table data from post
  if (!(0,external_lodash_.isUndefined)(translationsTableData)) {
    // Build translations table map with language slug as key
    translationsTable = new Map(Object.entries(translationsTableData));
  }
  return translationsTable;
}

/**
 * Checks if the given request is for saving.
 *
 * @param {Object} options The initial request.
 * @return {boolean} True if the request is for saving.
 */
function isSaveRequest(options) {
  // If data is defined we are in a PUT or POST request method otherwise a GET request method
  // Test options.method property isn't efficient because most of REST request which use fetch API doesn't pass this property.
  // So, test options.data is necessary to know if the REST request is to save data.
  // However test if options.data is undefined isn't sufficient because some REST request pass a null value as the ServerSideRender Gutenberg component.
  if (!(0,external_lodash_.isNil)(options.data)) {
    return true;
  }
  return false;
}

/**
 * Checks if the given request concerns the current post type.
 *
 * Useful when saving a reusable block contained in another post type.
 * Indeed a reusable block is also a post, but its saving request doesn't concern the post currently edited.
 * As we don't know the language of the reusable block when the user triggers the reusable block saving action,
 * we need to pass the current post language to be sure that the reusable block will have a language.
 *
 * @see https://github.com/polylang/polylang/issues/437 - Reusable block has no language when it's saved from another post type editing.
 *
 * @param {Object} options the initial request
 * @return {boolean} True if the request concerns the current post.
 */
function isCurrentPostRequest(options) {
  // Saving translation data is needed only for all post types.
  // It's done by verifying options.path matches with one of baseURL of all post types
  // and compare current post id with this sent in the request.

  // List of post type baseURLs.
  const postTypeURLs = (0,external_lodash_.map)((0,external_this_wp_data_.select)('core').getEntitiesConfig('postType'), (0,external_lodash_.property)('baseURL'));

  // Id from the post currently edited.
  const postId = (0,external_this_wp_data_.select)('core/editor').getCurrentPostId();

  // Id from the REST request.
  // options.data never isNil here because it's already verified before in isSaveRequest() function.
  const id = options.data.id;

  // Return true
  // if REST request baseURL matches with one of the known post type baseURLs
  // and the id from the post currently edited corresponds on the id passed to the REST request
  // Return false otherwise
  return -1 !== postTypeURLs.findIndex(function (element) {
    return new RegExp(`${(0,external_lodash_.escapeRegExp)(element)}`).test(options.path);
  }) && postId === id;
}

/**
 * Checks if the given REST request is for the creation of a new template part translation.
 *
 * @param {Object} options The initial request.
 * @return {boolean} True if the request concerns a template part translation creation.
 */
function isTemplatePartTranslationCreationRequest(options) {
  return 'POST' === options.method && options.path.match(/^\/wp\/v2\/template-parts(?:\/|\?|$)/) && !(0,external_lodash_.isNil)(options.data.from_post) && !(0,external_lodash_.isNil)(options.data.lang);
}

/**
 * Checks if the given REST request is for the creation of a new template part.
 *
 * @param {Object} options The initial request.
 * @return {boolean} True if the request concerns a template part creation.
 */
function isNewTemplatePartCreationRequest(options) {
  return 'POST' === options.method && options.path.match(/^\/wp\/v2\/template-parts(?:\/|\?|$)/) && (0,external_lodash_.isNil)(options.data.from_post) && (0,external_lodash_.isNil)(options.data.lang);
}

/**
 * Adds language as query string parameter to the given request.
 *
 * @param {Object} options         The initial request.
 * @param {string} currentLanguage The language code to add to the request.
 */
function addLanguageToRequest(options, currentLanguage) {
  const hasLangArg = (0,external_this_wp_url_.hasQueryArg)(options.path, 'lang');
  const filterLang = (0,external_lodash_.isUndefined)(options.filterLang) || options.filterLang;
  if (filterLang && !hasLangArg) {
    options.path = (0,external_this_wp_url_.addQueryArgs)(options.path, {
      lang: currentLanguage
    });
  }
}

/**
 * Adds `include_untranslated` parameter to the request.
 *
 * @param {Object} options The initial request.
 * @return {void}
 */
function addIncludeUntranslatedParam(options) {
  options.path = (0,external_this_wp_url_.addQueryArgs)(options.path, {
    include_untranslated: true
  });
}

/**
 * Use addIncludeUntranslatedParam if the given page is a template part page.
 * Or if the template editing mode is enabled inside post editing.
 *
 * @param {Object} options The initial request.
 * @return {void}
 */
function maybeRequireIncludeUntranslatedTemplate(options) {
  const params = new URL(document.location).searchParams;
  const postType = params.get('postType');
  const postId = params.get('postId');
  const isEditingTemplate = (0,external_this_wp_data_.select)(MODULE_POST_EDITOR_KEY)?.isEditingTemplate();
  if ('wp_template_part' === postType && !(0,external_lodash_.isNil)(postId) || isEditingTemplate) {
    addIncludeUntranslatedParam(options);
  }
}

/**
 * Returns true if the given post is a template part, false otherwise.
 *
 * @param {Object} post A post object.
 * @return {boolean} Whether it is a template part or not.
 */
function isTemplatePart(post) {
  return 'wp_template_part' === post.type;
}

/**
 * Returns the current post type considering the Site Editor or Post Editor.
 *
 * @return {string} The current post type.
 */
function getCurrentPostType() {
  if (isSiteBlockEditor()) {
    return (0,external_this_wp_data_.select)(settings_MODULE_SITE_EDITOR_KEY).getEditedPostType();
  }
  return (0,external_this_wp_data_.select)(MODULE_CORE_EDITOR_KEY).getCurrentPostType();
}

/**
 * Returns a regular expression ready to use to perform search and replace.
 *
 * @return {RegExp} The regular expression.
 */
function utils_getLangSlugRegex() {
  let languageCheckPattern = TEMPLATE_PART_SLUG_CHECK_LANGUAGE_PATTERN;
  const languages = select(MODULE_KEY).getLanguages();
  const languageSlugs = Array.from(languages.keys());
  if (!isEmpty(languageSlugs)) {
    languageCheckPattern = languageSlugs.join('|');
  }
  return new RegExp(`${TEMPLATE_PART_SLUG_SEPARATOR}(?:${languageCheckPattern})$`);
}

/**
 * Adds parameters according to the context of the request.
 *
 * @since 3.5
 *
 * @param {APIFetchOptions} options The options of the request.
 * @return {APIFetchOptions} The modified options of the request.
 */
function addParametersToRequest(options) {
  const currentLangSlug = getCurrentLanguageSlug();

  // `POST` or `PUT` request.
  if (isSaveRequest(options)) {
    /**
     * Use default language for new template part that doesn't exist in any language,
     * otherwise use the current language.
     */
    if (isNewTemplatePartCreationRequest(options)) {
      addLanguageToRequest(options, getDefaultLanguage()?.slug);
    }
    if (!isCurrentPostRequest(options) && !isTemplatePartTranslationCreationRequest(options)) {
      addLanguageToRequest(options, currentLangSlug);
    }
    maybeAddLangSuffixToTemplatePart(options, currentLangSlug);
    return options;
  }
  const currentPostType = getCurrentPostType();

  // Current language is set to default when editing templates.
  if ('wp_template' === currentPostType) {
    addLanguageToRequest(options, getDefaultLanguage()?.slug);
  }
  const templatePartListRegex = new RegExp(/^\/wp\/v2\/template-parts\/?(?:\?.*)?$/);

  // Template part list request.
  if (templatePartListRegex.test(options.path)) {
    maybeRequireIncludeUntranslatedTemplate(options);
  }

  // All kinds of requests.
  addLanguageToRequest(options, currentLangSlug);
  return options;
}

/**
 * Gets language from store or a fallback javascript global variable.
 *
 * @return {string} The language slug.
 */
function getCurrentLanguageSlug() {
  if ((0,external_lodash_.isUndefined)((0,external_this_wp_data_.select)(MODULE_CORE_EDITOR_KEY))) {
    // Return ASAP to avoid issues later.
    return pll_block_editor_plugin_settings.lang.slug;
  }

  // Post block editor case.
  const postLanguage = (0,external_this_wp_data_.select)(MODULE_CORE_EDITOR_KEY).getEditedPostAttribute('lang');
  if (!(0,external_lodash_.isUndefined)(postLanguage) && postLanguage) {
    return postLanguage;
  }

  // Returns the default lang if the current location is a template part list
  // and update pll_block_editor_plugin_settings at the same time.
  const params = new URL(document.location).searchParams;
  const postType = params.get('postType');
  const postId = params.get('postId');
  if ('wp_template_part' === postType && (0,external_lodash_.isNil)(postId)) {
    pll_block_editor_plugin_settings.lang = getDefaultLanguage();
    return pll_block_editor_plugin_settings.lang.slug;
  }

  // FSE template editor case.
  const template = utils_getCurrentPostFromDataStore();
  const templateLanguage = template?.lang;
  if (!(0,external_lodash_.isUndefined)(templateLanguage) && templateLanguage) {
    return templateLanguage;
  }

  // For the first requests block editor isn't initialized yet.
  // So language is retrieved from a javascript global variable initialized server-side.
  return pll_block_editor_plugin_settings.lang.slug;
}

/**
 * Adds the language suffix to a template part only during creation.
 *
 * @param {Object} options  Object representing a REST request.
 * @param {string} langSlug The Language slug to add.
 * @return {void}
 */
function maybeAddLangSuffixToTemplatePart(options, langSlug) {
  const restBaseUrl = getPostsUrl('wp_template_part');
  if ((0,external_lodash_.isUndefined)(restBaseUrl)) {
    // The user hasn't the rights to edit template part.
    return;
  }
  const templatePartURLRegExp = new RegExp((0,external_lodash_.escapeRegExp)(restBaseUrl));
  if ('POST' === options.method && templatePartURLRegExp.test(options.path)) {
    const languages = (0,external_this_wp_data_.select)(settings_MODULE_KEY).getLanguages();
    const language = languages.get(langSlug);
    if (!language.is_default) {
      // No suffix for default language.
      const langSuffix = settings_TEMPLATE_PART_SLUG_SEPARATOR + langSlug;
      options.data.slug += langSuffix;
    }
  }
}
// EXTERNAL MODULE: external {"this":["wp","compose"]}
var external_this_wp_compose_ = __webpack_require__(897);
;// ./js/src/editors/common/components/confirmation-modal/index.js
/**
 * Wordpress dependencies
 */






class ConfirmationModal extends external_this_wp_element_.Component {
  constructor() {
    super(...arguments);
    this.confirmButton = (0,external_this_wp_element_.createRef)();
  }
  componentDidMount() {
    this.confirmButton.current.focus();
  }
  render() {
    const {
      idPrefix,
      title,
      updateState,
      handleChange,
      children
    } = this.props;
    return /*#__PURE__*/(0,jsx_runtime.jsxs)(external_this_wp_components_.Modal, {
      title: title,
      className: "confirmBox",
      onRequestClose: updateState,
      shouldCloseOnEsc: false,
      shouldCloseOnClickOutside: false,
      focusOnMount: false,
      children: [children, /*#__PURE__*/(0,jsx_runtime.jsxs)(external_this_wp_components_.ButtonGroup, {
        className: "buttons",
        children: [/*#__PURE__*/(0,jsx_runtime.jsx)(external_this_wp_components_.Button, {
          id: `${idPrefix}_confirm`,
          ref: this.confirmButton,
          isPrimary: true,
          onClick: event => {
            handleChange(event);
            updateState();
          },
          children: (0,external_this_wp_i18n_.__)('OK', 'polylang-pro')
        }), /*#__PURE__*/(0,jsx_runtime.jsx)(external_this_wp_components_.Button, {
          id: `${idPrefix}_cancel`,
          isSecondary: true,
          onClick: () => updateState(),
          children: (0,external_this_wp_i18n_.__)('Cancel', 'polylang-pro')
        })]
      })]
    });
  }
}

/**
 * Control the execution of a component's function with a confirmation modal.
 *
 * @param {string}               idPrefix             Used to identify the modal's buttons. {@see ConfirmationModal.render()}
 * @param {React.Component}      ModalContent         Component which contains the content displayed in the confirmation modal.
 * @param {handleChangeCallback} handleChangeCallback Action triggered when we valid the confirmation modal by clicking the confirmation button.
 *
 * @return {Function} Higher-order component.
 */
const withConfirmation = function (idPrefix, ModalContent, handleChangeCallback) {
  return (0,external_this_wp_compose_.createHigherOrderComponent)(
  /**
   * @function Higher-Order Component
   *
   * @param {React.Component}                             WrappedComponent                        The component which needs a confirmation to change to its new value.
   * @param {string}                                      WrappedComponent.labelConfirmationModal Used for both WrappedComponent and ConfirmationModal titles.
   * @param {WrappedComponent.getChangeValueCallback}     WrappedComponent.getChangeValue
   * @param {WrappedComponent.bypassConfirmationCallback} WrappedComponent.bypassConfirmation
   * @return {React.Component} Component wrapped with a confirmation modal.
   */
  WrappedComponent => {
    class enhanceComponent extends external_this_wp_element_.Component {
      constructor() {
        super(...arguments);
        this.state = {
          isOpen: false,
          changeValue: null
        };
        this.handleChange = this.handleChange.bind(this);
      }
      handleChange(event) {
        let changeValue = WrappedComponent.getChangeValue(event);

        // Process specific case for the template part deletion confirmation.
        const currentPost = this.props.currentPost;
        if (!(0,external_lodash_.isNil)(currentPost)) {
          changeValue = {
            templateId: changeValue,
            currentPost
          };
        }
        if (!(0,external_lodash_.isUndefined)(WrappedComponent.bypassConfirmation) && WrappedComponent.bypassConfirmation(this.props.translationData)) {
          handleChangeCallback(changeValue);
        } else {
          this.setState({
            isOpen: true,
            changeValue
          });
        }
      }
      render() {
        // isDefaultLang property is only available in translationData language which comes from template post type.
        const isDefaultLang = this.props.translationData?.lang.is_default;
        const passThroughProps = this.props;
        const wrappedComponentProps = Object.assign({}, {
          ...passThroughProps
        }, {
          handleChange: this.handleChange
        });
        return /*#__PURE__*/(0,jsx_runtime.jsxs)(external_this_wp_element_.Fragment, {
          children: [/*#__PURE__*/(0,jsx_runtime.jsx)(WrappedComponent, {
            ...wrappedComponentProps
          }), this.state.isOpen && /*#__PURE__*/(0,jsx_runtime.jsx)(ConfirmationModal, {
            title: WrappedComponent.labelConfirmationModal,
            idPrefix: idPrefix,
            handleChange: () => handleChangeCallback(this.state.changeValue),
            updateState: () => this.setState({
              isOpen: false,
              changeValue: null
            }),
            children: /*#__PURE__*/(0,jsx_runtime.jsx)(ModalContent, {
              ...(!(0,external_lodash_.isNil)(isDefaultLang) ? {
                isDefaultLang
              } : {})
            })
          })]
        });
      }
    }
    enhanceComponent.bypassConfirmation = WrappedComponent.bypassConfirmation;
    enhanceComponent.getChangeValue = WrappedComponent.getChangeValue;
    return enhanceComponent;
  }, 'withConfirmation');
};

/**
 * Callback to trigger the action to change the value in the Component wrapped by the withConfirmation HOC.
 *
 * @callback handleChangeCallback
 * @param {string|Object} changeValue The value computed by {@see WrappedComponent.getChangeValueCallback} and could be completed by the withConfirmation HOC handleChange function.
 */

/**
 * Callback to retrieve the value to change from the Component wrapped by the withConfirmation HOC.
 *
 * @callback WrappedComponent.getChangeValueCallback
 * @param {Event} event A DOM triggered by the wrapped component.
 */

/**
 * Optional callback to check whether the Component wrapped by the withConfirmation HOC need to open the confirmation modal or not.
 *
 * @callback WrappedComponent.bypassConfirmationCallback
 * @param {Object} [translationData] A entry which represents the translation of the current post in a language {@see PLL_REST_Post::get_translations_table()}.
 */

/* harmony default export */ const confirmation_modal = (withConfirmation);
;// ./js/src/editors/common/components/buttons/synchronization/index.js
/**
 * WordPress dependencies
 */






/**
 * Internal dependencies
 */





class SynchronizationButton extends external_this_wp_element_.Component {
  /**
   * Manage synchronization with translated posts.
   *
   * @param {string} language The language slug.
   */
  static handleSynchronizationChange(language) {
    const syncPost = (0,external_this_wp_data_.select)(MODULE_CORE_EDITOR_KEY).getEditedPostAttribute('pll_sync_post');
    const synchronizedPosts = getSynchronizedPosts(syncPost);
    if (synchronizedPosts.has(language)) {
      synchronizedPosts.delete(language);
    } else {
      synchronizedPosts.set(language, true);
    }
    // and store the new value.
    (0,external_this_wp_data_.dispatch)(MODULE_CORE_EDITOR_KEY).editPost({
      pll_sync_post: convertMapToObject(synchronizedPosts)
    });

    // simulate a post modification to change status of the publish/update button.
    (0,external_this_wp_data_.dispatch)(MODULE_CORE_EDITOR_KEY).editPost({
      title: (0,external_this_wp_data_.select)(MODULE_CORE_EDITOR_KEY).getEditedPostAttribute('title')
    });
  }
  static bypassConfirmation(translationData) {
    const syncPost = (0,external_this_wp_data_.select)(MODULE_CORE_EDITOR_KEY).getEditedPostAttribute('pll_sync_post');
    const synchronizedPosts = getSynchronizedPosts(syncPost);
    const isSynchronized = !(0,external_lodash_.isEmpty)(synchronizedPosts) && synchronizedPosts.has(translationData.lang.slug);
    const isTranslated = !(0,external_lodash_.isUndefined)(translationData.translated_post) && !(0,external_lodash_.isNil)(translationData.translated_post.id);
    return isSynchronized || !isTranslated;
  }
  static getChangeValue(event) {
    return event.currentTarget.id.match(/\[(.[^[]+)\]/i)[1];
  }
  render() {
    const syncPost = (0,external_this_wp_data_.select)(MODULE_CORE_EDITOR_KEY).getEditedPostAttribute('pll_sync_post');
    const synchronizedPosts = getSynchronizedPosts(syncPost);
    const translationData = this.props.translationData;
    const isSynchronized = !(0,external_lodash_.isEmpty)(synchronizedPosts) && synchronizedPosts.has(translationData.lang.slug);
    const highlightButtonClass = isSynchronized && 'wp-ui-text-highlight';
    const synchronizeButtonText = isSynchronized ? (0,external_this_wp_i18n_.__)("Don't synchronize this post", 'polylang-pro') : (0,external_this_wp_i18n_.__)('Synchronize this post', 'polylang-pro');
    return /*#__PURE__*/(0,jsx_runtime.jsx)(external_this_wp_components_.Button, {
      icon: library_synchronization,
      label: synchronizeButtonText,
      id: `pll_sync_post[${translationData.lang.slug}]`,
      className: `pll-button ${highlightButtonClass}`,
      onClick: event => {
        this.props.handleChange(event);
      },
      children: /*#__PURE__*/(0,jsx_runtime.jsx)("span", {
        className: "screen-reader-text",
        children: synchronizeButtonText
      })
    });
  }
}
SynchronizationButton.labelConfirmationModal = (0,external_this_wp_i18n_.__)('Synchronize this post', 'polylang-pro');
const ModalContent = function () {
  return /*#__PURE__*/(0,jsx_runtime.jsx)("p", {
    children: (0,external_this_wp_i18n_.__)('You are about to overwrite an existing translation. Are you sure you want to proceed?', 'polylang-pro')
  });
};
const SynchronizationButtonWithConfirmation = confirmation_modal('pll_sync_post', ModalContent, SynchronizationButton.handleSynchronizationChange)(SynchronizationButton);
/* harmony default export */ const buttons_synchronization = (SynchronizationButtonWithConfirmation);
;// ./js/src/editors/common/components/buttons/index.js
/**
 * Buttons components.
 */







// EXTERNAL MODULE: ./node_modules/classnames/index.js
var classnames = __webpack_require__(942);
var classnames_default = /*#__PURE__*/__webpack_require__.n(classnames);
;// ./node_modules/dom-scroll-into-view/dist-web/index.js
function _typeof(obj) {
  if (typeof Symbol === "function" && typeof Symbol.iterator === "symbol") {
    _typeof = function (obj) {
      return typeof obj;
    };
  } else {
    _typeof = function (obj) {
      return obj && typeof Symbol === "function" && obj.constructor === Symbol && obj !== Symbol.prototype ? "symbol" : typeof obj;
    };
  }

  return _typeof(obj);
}

function _defineProperty(obj, key, value) {
  if (key in obj) {
    Object.defineProperty(obj, key, {
      value: value,
      enumerable: true,
      configurable: true,
      writable: true
    });
  } else {
    obj[key] = value;
  }

  return obj;
}

function ownKeys(object, enumerableOnly) {
  var keys = Object.keys(object);

  if (Object.getOwnPropertySymbols) {
    var symbols = Object.getOwnPropertySymbols(object);
    if (enumerableOnly) symbols = symbols.filter(function (sym) {
      return Object.getOwnPropertyDescriptor(object, sym).enumerable;
    });
    keys.push.apply(keys, symbols);
  }

  return keys;
}

function _objectSpread2(target) {
  for (var i = 1; i < arguments.length; i++) {
    var source = arguments[i] != null ? arguments[i] : {};

    if (i % 2) {
      ownKeys(source, true).forEach(function (key) {
        _defineProperty(target, key, source[key]);
      });
    } else if (Object.getOwnPropertyDescriptors) {
      Object.defineProperties(target, Object.getOwnPropertyDescriptors(source));
    } else {
      ownKeys(source).forEach(function (key) {
        Object.defineProperty(target, key, Object.getOwnPropertyDescriptor(source, key));
      });
    }
  }

  return target;
}

var RE_NUM = /[\-+]?(?:\d*\.|)\d+(?:[eE][\-+]?\d+|)/.source;

function getClientPosition(elem) {
  var box;
  var x;
  var y;
  var doc = elem.ownerDocument;
  var body = doc.body;
  var docElem = doc && doc.documentElement; // 根据 GBS 最新数据，A-Grade Browsers 都已支持 getBoundingClientRect 方法，不用再考虑传统的实现方式

  box = elem.getBoundingClientRect(); // 注：jQuery 还考虑减去 docElem.clientLeft/clientTop
  // 但测试发现，这样反而会导致当 html 和 body 有边距/边框样式时，获取的值不正确
  // 此外，ie6 会忽略 html 的 margin 值，幸运地是没有谁会去设置 html 的 margin

  x = box.left;
  y = box.top; // In IE, most of the time, 2 extra pixels are added to the top and left
  // due to the implicit 2-pixel inset border.  In IE6/7 quirks mode and
  // IE6 standards mode, this border can be overridden by setting the
  // document element's border to zero -- thus, we cannot rely on the
  // offset always being 2 pixels.
  // In quirks mode, the offset can be determined by querying the body's
  // clientLeft/clientTop, but in standards mode, it is found by querying
  // the document element's clientLeft/clientTop.  Since we already called
  // getClientBoundingRect we have already forced a reflow, so it is not
  // too expensive just to query them all.
  // ie 下应该减去窗口的边框吧，毕竟默认 absolute 都是相对窗口定位的
  // 窗口边框标准是设 documentElement ,quirks 时设置 body
  // 最好禁止在 body 和 html 上边框 ，但 ie < 9 html 默认有 2px ，减去
  // 但是非 ie 不可能设置窗口边框，body html 也不是窗口 ,ie 可以通过 html,body 设置
  // 标准 ie 下 docElem.clientTop 就是 border-top
  // ie7 html 即窗口边框改变不了。永远为 2
  // 但标准 firefox/chrome/ie9 下 docElem.clientTop 是窗口边框，即使设了 border-top 也为 0

  x -= docElem.clientLeft || body.clientLeft || 0;
  y -= docElem.clientTop || body.clientTop || 0;
  return {
    left: x,
    top: y
  };
}

function getScroll(w, top) {
  var ret = w["page".concat(top ? 'Y' : 'X', "Offset")];
  var method = "scroll".concat(top ? 'Top' : 'Left');

  if (typeof ret !== 'number') {
    var d = w.document; // ie6,7,8 standard mode

    ret = d.documentElement[method];

    if (typeof ret !== 'number') {
      // quirks mode
      ret = d.body[method];
    }
  }

  return ret;
}

function getScrollLeft(w) {
  return getScroll(w);
}

function getScrollTop(w) {
  return getScroll(w, true);
}

function getOffset(el) {
  var pos = getClientPosition(el);
  var doc = el.ownerDocument;
  var w = doc.defaultView || doc.parentWindow;
  pos.left += getScrollLeft(w);
  pos.top += getScrollTop(w);
  return pos;
}

function _getComputedStyle(elem, name, computedStyle_) {
  var val = '';
  var d = elem.ownerDocument;
  var computedStyle = computedStyle_ || d.defaultView.getComputedStyle(elem, null); // https://github.com/kissyteam/kissy/issues/61

  if (computedStyle) {
    val = computedStyle.getPropertyValue(name) || computedStyle[name];
  }

  return val;
}

var _RE_NUM_NO_PX = new RegExp("^(".concat(RE_NUM, ")(?!px)[a-z%]+$"), 'i');

var RE_POS = /^(top|right|bottom|left)$/;
var CURRENT_STYLE = 'currentStyle';
var RUNTIME_STYLE = 'runtimeStyle';
var LEFT = 'left';
var PX = 'px';

function _getComputedStyleIE(elem, name) {
  // currentStyle maybe null
  // http://msdn.microsoft.com/en-us/library/ms535231.aspx
  var ret = elem[CURRENT_STYLE] && elem[CURRENT_STYLE][name]; // 当 width/height 设置为百分比时，通过 pixelLeft 方式转换的 width/height 值
  // 一开始就处理了! CUSTOM_STYLE.height,CUSTOM_STYLE.width ,cssHook 解决@2011-08-19
  // 在 ie 下不对，需要直接用 offset 方式
  // borderWidth 等值也有问题，但考虑到 borderWidth 设为百分比的概率很小，这里就不考虑了
  // From the awesome hack by Dean Edwards
  // http://erik.eae.net/archives/2007/07/27/18.54.15/#comment-102291
  // If we're not dealing with a regular pixel number
  // but a number that has a weird ending, we need to convert it to pixels
  // exclude left right for relativity

  if (_RE_NUM_NO_PX.test(ret) && !RE_POS.test(name)) {
    // Remember the original values
    var style = elem.style;
    var left = style[LEFT];
    var rsLeft = elem[RUNTIME_STYLE][LEFT]; // prevent flashing of content

    elem[RUNTIME_STYLE][LEFT] = elem[CURRENT_STYLE][LEFT]; // Put in the new values to get a computed value out

    style[LEFT] = name === 'fontSize' ? '1em' : ret || 0;
    ret = style.pixelLeft + PX; // Revert the changed values

    style[LEFT] = left;
    elem[RUNTIME_STYLE][LEFT] = rsLeft;
  }

  return ret === '' ? 'auto' : ret;
}

var getComputedStyleX;

if (typeof window !== 'undefined') {
  getComputedStyleX = window.getComputedStyle ? _getComputedStyle : _getComputedStyleIE;
}

function each(arr, fn) {
  for (var i = 0; i < arr.length; i++) {
    fn(arr[i]);
  }
}

function isBorderBoxFn(elem) {
  return getComputedStyleX(elem, 'boxSizing') === 'border-box';
}

var BOX_MODELS = ['margin', 'border', 'padding'];
var CONTENT_INDEX = -1;
var PADDING_INDEX = 2;
var BORDER_INDEX = 1;
var MARGIN_INDEX = 0;

function swap(elem, options, callback) {
  var old = {};
  var style = elem.style;
  var name; // Remember the old values, and insert the new ones

  for (name in options) {
    if (options.hasOwnProperty(name)) {
      old[name] = style[name];
      style[name] = options[name];
    }
  }

  callback.call(elem); // Revert the old values

  for (name in options) {
    if (options.hasOwnProperty(name)) {
      style[name] = old[name];
    }
  }
}

function getPBMWidth(elem, props, which) {
  var value = 0;
  var prop;
  var j;
  var i;

  for (j = 0; j < props.length; j++) {
    prop = props[j];

    if (prop) {
      for (i = 0; i < which.length; i++) {
        var cssProp = void 0;

        if (prop === 'border') {
          cssProp = "".concat(prop + which[i], "Width");
        } else {
          cssProp = prop + which[i];
        }

        value += parseFloat(getComputedStyleX(elem, cssProp)) || 0;
      }
    }
  }

  return value;
}
/**
 * A crude way of determining if an object is a window
 * @member util
 */


function isWindow(obj) {
  // must use == for ie8

  /* eslint eqeqeq:0 */
  return obj != null && obj == obj.window;
}

var domUtils = {};
each(['Width', 'Height'], function (name) {
  domUtils["doc".concat(name)] = function (refWin) {
    var d = refWin.document;
    return Math.max( // firefox chrome documentElement.scrollHeight< body.scrollHeight
    // ie standard mode : documentElement.scrollHeight> body.scrollHeight
    d.documentElement["scroll".concat(name)], // quirks : documentElement.scrollHeight 最大等于可视窗口多一点？
    d.body["scroll".concat(name)], domUtils["viewport".concat(name)](d));
  };

  domUtils["viewport".concat(name)] = function (win) {
    // pc browser includes scrollbar in window.innerWidth
    var prop = "client".concat(name);
    var doc = win.document;
    var body = doc.body;
    var documentElement = doc.documentElement;
    var documentElementProp = documentElement[prop]; // 标准模式取 documentElement
    // backcompat 取 body

    return doc.compatMode === 'CSS1Compat' && documentElementProp || body && body[prop] || documentElementProp;
  };
});
/*
 得到元素的大小信息
 @param elem
 @param name
 @param {String} [extra]  'padding' : (css width) + padding
 'border' : (css width) + padding + border
 'margin' : (css width) + padding + border + margin
 */

function getWH(elem, name, extra) {
  if (isWindow(elem)) {
    return name === 'width' ? domUtils.viewportWidth(elem) : domUtils.viewportHeight(elem);
  } else if (elem.nodeType === 9) {
    return name === 'width' ? domUtils.docWidth(elem) : domUtils.docHeight(elem);
  }

  var which = name === 'width' ? ['Left', 'Right'] : ['Top', 'Bottom'];
  var borderBoxValue = name === 'width' ? elem.offsetWidth : elem.offsetHeight;
  var computedStyle = getComputedStyleX(elem);
  var isBorderBox = isBorderBoxFn(elem);
  var cssBoxValue = 0;

  if (borderBoxValue == null || borderBoxValue <= 0) {
    borderBoxValue = undefined; // Fall back to computed then un computed css if necessary

    cssBoxValue = getComputedStyleX(elem, name);

    if (cssBoxValue == null || Number(cssBoxValue) < 0) {
      cssBoxValue = elem.style[name] || 0;
    } // Normalize '', auto, and prepare for extra


    cssBoxValue = parseFloat(cssBoxValue) || 0;
  }

  if (extra === undefined) {
    extra = isBorderBox ? BORDER_INDEX : CONTENT_INDEX;
  }

  var borderBoxValueOrIsBorderBox = borderBoxValue !== undefined || isBorderBox;
  var val = borderBoxValue || cssBoxValue;

  if (extra === CONTENT_INDEX) {
    if (borderBoxValueOrIsBorderBox) {
      return val - getPBMWidth(elem, ['border', 'padding'], which);
    }

    return cssBoxValue;
  }

  if (borderBoxValueOrIsBorderBox) {
    var padding = extra === PADDING_INDEX ? -getPBMWidth(elem, ['border'], which) : getPBMWidth(elem, ['margin'], which);
    return val + (extra === BORDER_INDEX ? 0 : padding);
  }

  return cssBoxValue + getPBMWidth(elem, BOX_MODELS.slice(extra), which);
}

var cssShow = {
  position: 'absolute',
  visibility: 'hidden',
  display: 'block'
}; // fix #119 : https://github.com/kissyteam/kissy/issues/119

function getWHIgnoreDisplay(elem) {
  var val;
  var args = arguments; // in case elem is window
  // elem.offsetWidth === undefined

  if (elem.offsetWidth !== 0) {
    val = getWH.apply(undefined, args);
  } else {
    swap(elem, cssShow, function () {
      val = getWH.apply(undefined, args);
    });
  }

  return val;
}

function css(el, name, v) {
  var value = v;

  if (_typeof(name) === 'object') {
    for (var i in name) {
      if (name.hasOwnProperty(i)) {
        css(el, i, name[i]);
      }
    }

    return undefined;
  }

  if (typeof value !== 'undefined') {
    if (typeof value === 'number') {
      value += 'px';
    }

    el.style[name] = value;
    return undefined;
  }

  return getComputedStyleX(el, name);
}

each(['width', 'height'], function (name) {
  var first = name.charAt(0).toUpperCase() + name.slice(1);

  domUtils["outer".concat(first)] = function (el, includeMargin) {
    return el && getWHIgnoreDisplay(el, name, includeMargin ? MARGIN_INDEX : BORDER_INDEX);
  };

  var which = name === 'width' ? ['Left', 'Right'] : ['Top', 'Bottom'];

  domUtils[name] = function (elem, val) {
    if (val !== undefined) {
      if (elem) {
        var computedStyle = getComputedStyleX(elem);
        var isBorderBox = isBorderBoxFn(elem);

        if (isBorderBox) {
          val += getPBMWidth(elem, ['padding', 'border'], which);
        }

        return css(elem, name, val);
      }

      return undefined;
    }

    return elem && getWHIgnoreDisplay(elem, name, CONTENT_INDEX);
  };
}); // 设置 elem 相对 elem.ownerDocument 的坐标

function setOffset(elem, offset) {
  // set position first, in-case top/left are set even on static elem
  if (css(elem, 'position') === 'static') {
    elem.style.position = 'relative';
  }

  var old = getOffset(elem);
  var ret = {};
  var current;
  var key;

  for (key in offset) {
    if (offset.hasOwnProperty(key)) {
      current = parseFloat(css(elem, key)) || 0;
      ret[key] = current + offset[key] - old[key];
    }
  }

  css(elem, ret);
}

var util = _objectSpread2({
  getWindow: function getWindow(node) {
    var doc = node.ownerDocument || node;
    return doc.defaultView || doc.parentWindow;
  },
  offset: function offset(el, value) {
    if (typeof value !== 'undefined') {
      setOffset(el, value);
    } else {
      return getOffset(el);
    }
  },
  isWindow: isWindow,
  each: each,
  css: css,
  clone: function clone(obj) {
    var ret = {};

    for (var i in obj) {
      if (obj.hasOwnProperty(i)) {
        ret[i] = obj[i];
      }
    }

    var overflow = obj.overflow;

    if (overflow) {
      for (var _i in obj) {
        if (obj.hasOwnProperty(_i)) {
          ret.overflow[_i] = obj.overflow[_i];
        }
      }
    }

    return ret;
  },
  scrollLeft: function scrollLeft(w, v) {
    if (isWindow(w)) {
      if (v === undefined) {
        return getScrollLeft(w);
      }

      window.scrollTo(v, getScrollTop(w));
    } else {
      if (v === undefined) {
        return w.scrollLeft;
      }

      w.scrollLeft = v;
    }
  },
  scrollTop: function scrollTop(w, v) {
    if (isWindow(w)) {
      if (v === undefined) {
        return getScrollTop(w);
      }

      window.scrollTo(getScrollLeft(w), v);
    } else {
      if (v === undefined) {
        return w.scrollTop;
      }

      w.scrollTop = v;
    }
  },
  viewportWidth: 0,
  viewportHeight: 0
}, domUtils);

function scrollIntoView(elem, container, config) {
  config = config || {}; // document 归一化到 window

  if (container.nodeType === 9) {
    container = util.getWindow(container);
  }

  var allowHorizontalScroll = config.allowHorizontalScroll;
  var onlyScrollIfNeeded = config.onlyScrollIfNeeded;
  var alignWithTop = config.alignWithTop;
  var alignWithLeft = config.alignWithLeft;
  var offsetTop = config.offsetTop || 0;
  var offsetLeft = config.offsetLeft || 0;
  var offsetBottom = config.offsetBottom || 0;
  var offsetRight = config.offsetRight || 0;
  allowHorizontalScroll = allowHorizontalScroll === undefined ? true : allowHorizontalScroll;
  var isWin = util.isWindow(container);
  var elemOffset = util.offset(elem);
  var eh = util.outerHeight(elem);
  var ew = util.outerWidth(elem);
  var containerOffset;
  var ch;
  var cw;
  var containerScroll;
  var diffTop;
  var diffBottom;
  var win;
  var winScroll;
  var ww;
  var wh;

  if (isWin) {
    win = container;
    wh = util.height(win);
    ww = util.width(win);
    winScroll = {
      left: util.scrollLeft(win),
      top: util.scrollTop(win)
    }; // elem 相对 container 可视视窗的距离

    diffTop = {
      left: elemOffset.left - winScroll.left - offsetLeft,
      top: elemOffset.top - winScroll.top - offsetTop
    };
    diffBottom = {
      left: elemOffset.left + ew - (winScroll.left + ww) + offsetRight,
      top: elemOffset.top + eh - (winScroll.top + wh) + offsetBottom
    };
    containerScroll = winScroll;
  } else {
    containerOffset = util.offset(container);
    ch = container.clientHeight;
    cw = container.clientWidth;
    containerScroll = {
      left: container.scrollLeft,
      top: container.scrollTop
    }; // elem 相对 container 可视视窗的距离
    // 注意边框, offset 是边框到根节点

    diffTop = {
      left: elemOffset.left - (containerOffset.left + (parseFloat(util.css(container, 'borderLeftWidth')) || 0)) - offsetLeft,
      top: elemOffset.top - (containerOffset.top + (parseFloat(util.css(container, 'borderTopWidth')) || 0)) - offsetTop
    };
    diffBottom = {
      left: elemOffset.left + ew - (containerOffset.left + cw + (parseFloat(util.css(container, 'borderRightWidth')) || 0)) + offsetRight,
      top: elemOffset.top + eh - (containerOffset.top + ch + (parseFloat(util.css(container, 'borderBottomWidth')) || 0)) + offsetBottom
    };
  }

  if (diffTop.top < 0 || diffBottom.top > 0) {
    // 强制向上
    if (alignWithTop === true) {
      util.scrollTop(container, containerScroll.top + diffTop.top);
    } else if (alignWithTop === false) {
      util.scrollTop(container, containerScroll.top + diffBottom.top);
    } else {
      // 自动调整
      if (diffTop.top < 0) {
        util.scrollTop(container, containerScroll.top + diffTop.top);
      } else {
        util.scrollTop(container, containerScroll.top + diffBottom.top);
      }
    }
  } else {
    if (!onlyScrollIfNeeded) {
      alignWithTop = alignWithTop === undefined ? true : !!alignWithTop;

      if (alignWithTop) {
        util.scrollTop(container, containerScroll.top + diffTop.top);
      } else {
        util.scrollTop(container, containerScroll.top + diffBottom.top);
      }
    }
  }

  if (allowHorizontalScroll) {
    if (diffTop.left < 0 || diffBottom.left > 0) {
      // 强制向上
      if (alignWithLeft === true) {
        util.scrollLeft(container, containerScroll.left + diffTop.left);
      } else if (alignWithLeft === false) {
        util.scrollLeft(container, containerScroll.left + diffBottom.left);
      } else {
        // 自动调整
        if (diffTop.left < 0) {
          util.scrollLeft(container, containerScroll.left + diffTop.left);
        } else {
          util.scrollLeft(container, containerScroll.left + diffBottom.left);
        }
      }
    } else {
      if (!onlyScrollIfNeeded) {
        alignWithLeft = alignWithLeft === undefined ? true : !!alignWithLeft;

        if (alignWithLeft) {
          util.scrollLeft(container, containerScroll.left + diffTop.left);
        } else {
          util.scrollLeft(container, containerScroll.left + diffBottom.left);
        }
      }
    }
  }
}

/* harmony default export */ const dist_web = (scrollIntoView);
//# sourceMappingURL=index.js.map

// EXTERNAL MODULE: external {"this":["wp","keycodes"]}
var external_this_wp_keycodes_ = __webpack_require__(860);
// EXTERNAL MODULE: external {"this":["wp","apiFetch"]}
var external_this_wp_apiFetch_ = __webpack_require__(631);
var external_this_wp_apiFetch_default = /*#__PURE__*/__webpack_require__.n(external_this_wp_apiFetch_);
;// ./js/src/editors/common/components/translations-table/input/input-change.js
/**
 * WordPress dependencies
 */



/**
 * Internal dependencies
 */


const onInputChange = ({
  value,
  post = null,
  translatedPosts,
  translationData,
  language
}) => {
  if ((0,external_lodash_.isEmpty)(post)) {
    translationData.translated_post = {
      id: null,
      title: value
    };
    translationData.links = {
      add_link: translationData.links.add_link
    };
    // unlink translation
    translatedPosts.delete(language.slug);
  } else {
    translatedPosts.set(language.slug, post.id);
    translationData.translated_post = {
      id: post.id,
      title: post.title.raw
    };
    translationData.block_editor = {
      edit_link: post.block_editor.edit_link
    };
    translationData.caps = post.caps;
  }
  // update translations table in store
  (0,external_this_wp_data_.dispatch)(MODULE_CORE_EDITOR_KEY).editPost({
    translations: convertMapToObject(translatedPosts)
  });
  // simulate a post modification to change status of the publish/update button
  (0,external_this_wp_data_.dispatch)(MODULE_CORE_EDITOR_KEY).editPost({
    title: (0,external_this_wp_data_.select)(MODULE_CORE_EDITOR_KEY).getEditedPostAttribute('title')
  });
};
/* harmony default export */ const input_change = (onInputChange);
;// ./js/src/editors/common/components/translations-table/input/index.js
/**
 * External dependencies
 */




/**
 * WordPress dependencies
 */









/**
 * Internal dependencies
 */



// Since TranslationInput is rendered in the context of other inputs, but should be
// considered a separate modal node, prevent keyboard events from propagating
// as being considered from the input.

const stopEventPropagation = event => event.stopPropagation();
class TranslationInput extends external_this_wp_element_.Component {
  constructor() {
    super(...arguments);
    this.onChange = this.onChange.bind(this);
    this.onKeyDown = this.onKeyDown.bind(this);
    this.bindListNode = this.bindListNode.bind(this);
    this.updateSuggestions = (0,external_lodash_.debounce)(this.updateSuggestions.bind(this), 500);
    this.suggestionNodes = [];
    this.state = {
      posts: [],
      showSuggestions: false,
      selectedSuggestion: null
    };
  }
  componentDidUpdate() {
    const {
      showSuggestions,
      selectedSuggestion
    } = this.state;
    // only have to worry about scrolling selected suggestion into view
    // when already expanded
    if (showSuggestions && selectedSuggestion !== null && !this.scrollingIntoView) {
      this.scrollingIntoView = true;
      dist_web(this.suggestionNodes[selectedSuggestion], this.listNode, {
        onlyScrollIfNeeded: true
      });
      setTimeout(() => {
        this.scrollingIntoView = false;
      }, 100);
    }
  }
  componentWillUnmount() {
    delete this.suggestionsRequest;
  }
  bindListNode(ref) {
    this.listNode = ref;
  }
  bindSuggestionNode(index) {
    return ref => {
      this.suggestionNodes[index] = ref;
    };
  }
  updateSuggestions(value, noControl = false) {
    // Show the suggestions after typing at least 2 characters
    // and also for URLs
    if (value.length < 2 && !noControl) {
      this.setState({
        showSuggestions: false,
        selectedSuggestion: null,
        loading: false
      });
      return;
    }
    this.setState({
      selectedSuggestion: null,
      loading: true
    });
    const postId = (0,external_this_wp_data_.select)(MODULE_CORE_EDITOR_KEY).getCurrentPostId();
    const postType = (0,external_this_wp_data_.select)(MODULE_CORE_EDITOR_KEY).getCurrentPostType();
    const postLanguageSlug = (0,external_this_wp_data_.select)(MODULE_CORE_EDITOR_KEY).getEditedPostAttribute('lang');
    const translationLanguageSlug = this.props.translationData.lang.slug; // language for the suggestion
    const request = external_this_wp_apiFetch_default()({
      path: (0,external_this_wp_url_.addQueryArgs)('/pll/v1/untranslated-posts', {
        search: value,
        include: postId,
        untranslated_in: postLanguageSlug,
        lang: translationLanguageSlug,
        type: postType,
        context: 'edit'
      })
    });
    request.then(posts => {
      // A fetch Promise doesn't have an abort option. It's mimicked by
      // comparing the request reference in on the instance, which is
      // reset or deleted on subsequent requests or unmounting.
      if (this.suggestionsRequest !== request) {
        return;
      }
      this.setState({
        posts,
        showSuggestions: true,
        loading: false
      });
      if (!!posts.length) {
        this.props.debouncedSpeak((0,external_this_wp_i18n_.sprintf)(/* translators: accessibility text. %d is a number of posts. */
        (0,external_this_wp_i18n_._n)('%d result found, use up and down arrow keys to navigate.', '%d results found, use up and down arrow keys to navigate.', posts.length, 'polylang-pro'), posts.length), 'assertive');
      } else {
        /* translators: accessibility text */
        this.props.debouncedSpeak((0,external_this_wp_i18n_.__)('No results.', 'polylang-pro'), 'assertive');
      }
    }).catch(() => {
      if (this.suggestionsRequest === request) {
        this.setState({
          loading: false
        });
      }
    });
    this.suggestionsRequest = request;
  }
  onChange(event) {
    const inputValue = event.target.value;
    const translatedPosts = this.props.translatedPosts;
    const translationData = this.props.translationData;
    const language = this.props.translationData.lang;
    input_change({
      value: inputValue,
      translatedPosts,
      translationData,
      language
    });
    this.updateSuggestions(inputValue);
  }
  onKeyDown(event) {
    const {
      showSuggestions,
      selectedSuggestion,
      posts,
      loading
    } = this.state;
    let inputValue = event.target.value;
    let doUpdateSuggestions = false;

    // If the suggestions are not shown or loading, we shouldn't handle the arrow keys
    // We shouldn't preventDefault to allow block arrow keys navigation
    if (!showSuggestions || !posts.length || loading) {
      switch (event.keyCode) {
        case external_this_wp_keycodes_.SPACE:
          const {
            ctrlKey,
            shiftKey,
            altKey,
            metaKey
          } = event;
          if (ctrlKey && !(shiftKey || altKey || metaKey)) {
            inputValue = '';
            doUpdateSuggestions = true;
          }
          break;
        case external_this_wp_keycodes_.BACKSPACE:
          if ((0,external_lodash_.isEmpty)(inputValue)) {
            doUpdateSuggestions = true;
          }
          break;
      }
      if (doUpdateSuggestions) {
        this.updateSuggestions(inputValue, true);
      }
      return;
    }
    switch (event.keyCode) {
      case external_this_wp_keycodes_.UP:
        {
          event.stopPropagation();
          event.preventDefault();
          const previousIndex = !selectedSuggestion ? posts.length - 1 : selectedSuggestion - 1;
          this.setState({
            selectedSuggestion: previousIndex
          });
          break;
        }
      case external_this_wp_keycodes_.DOWN:
        {
          event.stopPropagation();
          event.preventDefault();
          const nextIndex = selectedSuggestion === null || selectedSuggestion === posts.length - 1 ? 0 : selectedSuggestion + 1;
          this.setState({
            selectedSuggestion: nextIndex
          });
          break;
        }
      case external_this_wp_keycodes_.ENTER:
        {
          if (this.state.selectedSuggestion !== null) {
            event.stopPropagation();
            const post = this.state.posts[this.state.selectedSuggestion];
            this.selectLink(post);
          }
          break;
        }
      case external_this_wp_keycodes_.ESCAPE:
        {
          event.stopPropagation();
          this.setState({
            selectedSuggestion: null,
            showSuggestions: false
          });
          break;
        }
    }
  }
  selectLink(post) {
    const translationData = this.props.translationData;
    const translatedPosts = this.props.translatedPosts;
    const language = this.props.translationData.lang;
    input_change({
      value: post.title.raw,
      post,
      translatedPosts,
      translationData,
      language
    });
    this.setState({
      selectedSuggestion: null,
      showSuggestions: false
    });
  }
  render() {
    const {
      value = '',
      autoFocus = true,
      instanceId,
      translationData
    } = this.props;
    const language = translationData.lang;
    const {
      showSuggestions,
      posts,
      selectedSuggestion,
      loading
    } = this.state;
    const currentUserCanAddOrEdit = translationData.caps.edit || translationData.caps.add;
    return /*#__PURE__*/(0,jsx_runtime.jsxs)(jsx_runtime.Fragment, {
      children: [/*#__PURE__*/(0,jsx_runtime.jsx)("label", {
        className: "screen-reader-text",
        htmlFor: `tr_lang_${translationData.lang.slug}`,
        children: /* translators: accessibility text */(0,external_this_wp_i18n_.__)('Translation', 'polylang-pro')
      }), /*#__PURE__*/(0,jsx_runtime.jsxs)("div", {
        className: "translation-input",
        children: [/*#__PURE__*/(0,jsx_runtime.jsx)("input", {
          lang: language.w3c,
          dir: language.is_rtl ? 'rtl' : 'ltr',
          style: {
            direction: language.is_rtl ? 'rtl' : 'ltr'
          },
          autoFocus: autoFocus // eslint-disable-line jsx-a11y/no-autofocus
          ,
          disabled: !currentUserCanAddOrEdit,
          type: "text",
          "aria-label": /* translators: accessibility text */(0,external_this_wp_i18n_.__)('URL', 'polylang-pro'),
          required: true,
          value: value,
          onChange: this.onChange,
          onInput: stopEventPropagation,
          placeholder: (0,external_this_wp_i18n_.__)('Start typing the post title', 'polylang-pro'),
          onKeyDown: this.onKeyDown,
          role: "combobox",
          "aria-expanded": showSuggestions,
          "aria-autocomplete": "list",
          "aria-owns": `translation-input-suggestions-${instanceId}`,
          "aria-activedescendant": selectedSuggestion !== null ? `translation-input-suggestion-${instanceId}-${selectedSuggestion}` : undefined
        }), loading && /*#__PURE__*/(0,jsx_runtime.jsx)(external_this_wp_components_.Spinner, {})]
      }), showSuggestions && !!posts.length && /*#__PURE__*/(0,jsx_runtime.jsx)(external_this_wp_components_.Popover, {
        position: "bottom",
        noArrow: true,
        focusOnMount: false,
        children: /*#__PURE__*/(0,jsx_runtime.jsx)("div", {
          className: "translation-input__suggestions",
          id: `translation-input-suggestions-${instanceId}`,
          ref: this.bindListNode,
          role: "listbox",
          children: posts.map((post, index) => /*#__PURE__*/(0,jsx_runtime.jsx)("button", {
            role: "option",
            tabIndex: "-1",
            id: `translation-input-suggestion-${instanceId}-${index}`,
            ref: this.bindSuggestionNode(index),
            className: classnames_default()('translation-input__suggestion', {
              'is-selected': index === selectedSuggestion
            }),
            onClick: () => this.selectLink(post),
            "aria-selected": index === selectedSuggestion,
            children: post.title.raw || (0,external_this_wp_i18n_.__)('(no title)', 'polylang-pro')
          }, post.id))
        })
      })]
    });
  }
}
/* harmony default export */ const input = ((0,external_this_wp_components_.withSpokenMessages)((0,external_this_wp_compose_.withInstanceId)(TranslationInput)));
;// ./js/src/editors/common/components/translations-table/rows/index.js
/**
 * Internal dependencies.
 */


const rows_TranslationRow = ({
  language,
  children
}) => {
  return /*#__PURE__*/(0,jsx_runtime.jsxs)(jsx_runtime.Fragment, {
    children: [/*#__PURE__*/(0,jsx_runtime.jsx)(flag, {
      language: language
    }), children]
  });
};
/* harmony default export */ const rows = (rows_TranslationRow);
;// ./js/src/editors/common/components/translations-table/wrapper/index.js
/**
 * WordPress dependencies
 */


const wrapper_TranslationsTableWrapper = ({
  children
}) => {
  return /*#__PURE__*/(0,jsx_runtime.jsxs)("div", {
    id: "post-translations",
    className: "translations",
    children: [/*#__PURE__*/(0,jsx_runtime.jsx)("p", {
      children: /*#__PURE__*/(0,jsx_runtime.jsx)("strong", {
        children: (0,external_this_wp_i18n_.__)('Translations', 'polylang-pro')
      })
    }), /*#__PURE__*/(0,jsx_runtime.jsx)("table", {
      children: /*#__PURE__*/(0,jsx_runtime.jsx)("tbody", {
        children: children
      })
    })]
  });
};
/* harmony default export */ const translations_table_wrapper = (wrapper_TranslationsTableWrapper);
;// ./js/src/editors/common/components/translations-table/post-editor/index.js
/**
 * WordPress dependencies
 */


/**
 * Internal dependencies
 */








const PostEditorTranslationsTable = ({
  selectedLanguage,
  translationsTable
}) => {
  const translations = (0,external_this_wp_data_.select)(MODULE_CORE_EDITOR_KEY).getEditedPostAttribute('translations');
  const translatedPosts = getTranslatedPosts(translations, translationsTable, selectedLanguage.slug);
  const table = Array.from(translationsTable.values()).map(translationData => {
    // Don't display current post in the translation table.
    if (selectedLanguage.slug === translationData.lang.slug) {
      return null;
    }
    const isTranslated = undefined !== translationData.translated_post?.id && null !== translationData.translated_post?.id;
    const currentUserCanEdit = translationData.caps.edit;
    const currentUserCanCreate = translationData.caps.add;
    const addEditButton = () => {
      if (isTranslated && currentUserCanEdit) {
        return /*#__PURE__*/(0,jsx_runtime.jsx)(edit, {
          href: decodeURI(translationData.block_editor.edit_link),
          language: translationData.lang
        });
      } else if (currentUserCanCreate) {
        return /*#__PURE__*/(0,jsx_runtime.jsx)(add, {
          href: decodeURI(translationData.links.add_link),
          language: translationData.lang
        });
      }
      return null;
    };
    return /*#__PURE__*/(0,jsx_runtime.jsx)("tr", {
      children: /*#__PURE__*/(0,jsx_runtime.jsxs)(rows, {
        language: translationData.lang,
        children: [/*#__PURE__*/(0,jsx_runtime.jsx)(add_or_edit, {
          children: addEditButton()
        }), /*#__PURE__*/(0,jsx_runtime.jsx)(cells_synchronization, {
          children: translationData.can_synchronize && /*#__PURE__*/(0,jsx_runtime.jsx)(buttons_synchronization, {
            translationData: translationData
          })
        }), /*#__PURE__*/(0,jsx_runtime.jsx)(translation_input, {
          children: /*#__PURE__*/(0,jsx_runtime.jsx)(input, {
            id: `htr_lang_${translationData.lang.slug}`,
            autoFocus: false // eslint-disable-line jsx-a11y/no-autofocus
            ,
            translatedPosts: translatedPosts,
            translationData: translationData,
            value: undefined !== translationData.translated_post?.title ? translationData.translated_post?.title : ''
          })
        })]
      })
    }, translationData.lang.slug);
  });
  return /*#__PURE__*/(0,jsx_runtime.jsx)(translations_table_wrapper, {
    children: table
  });
};
/* harmony default export */ const post_editor = (PostEditorTranslationsTable);
;// ./js/src/editors/common/components/delete-modal-body/index.js
/**
 * WordPress dependencies
 */


const delete_modal_body_DeleteModalBody = ({
  isDefaultLang
}) => {
  const defaultLangText = () => {
    if (!isDefaultLang) {
      return null;
    }
    return /*#__PURE__*/_jsxs("p", {
      children: [__('You are about to delete an entity in the default language.', 'polylang-pro'), /*#__PURE__*/_jsx("br", {}), __('This will delete its customizations and all its corresponding translations.', 'polylang-pro')]
    });
  };
  return /*#__PURE__*/_jsxs(_Fragment, {
    children: [defaultLangText(), /*#__PURE__*/_jsx("p", {
      children: __('Are you sure you want to delete this translation?', 'polylang-pro')
    })]
  });
};
/* harmony default export */ const delete_modal_body = ((/* unused pure expression or super */ null && (delete_modal_body_DeleteModalBody)));
// EXTERNAL MODULE: external {"this":["wp","coreData"]}
var external_this_wp_coreData_ = __webpack_require__(488);
;// ./js/src/editors/common/components/delete-with-confirmation/use-delete-post.js
/**
 * WordPress dependencies
 *
 */





const use_delete_post_useDeletePost = () => {
  const {
    deleteEntityRecord
  } = useDispatch(coreStore);
  const {
    createSuccessNotice,
    createErrorNotice
  } = useDispatch(noticesStore);
  const handleDelete = async (postId, postType) => {
    try {
      const forceDelete = !POST_TYPE_WITH_TRASH.includes(postType);
      await deleteEntityRecord('postType', postType, postId, {
        force: forceDelete
      }, {
        throwOnError: true
      });
      createSuccessNotice(__('The translation has been deleted.', 'polylang-pro'), {
        type: 'snackbar'
      });
    } catch (error) {
      createErrorNotice(sprintf(/* translators: %s: Error message describing why the post could not be deleted. */
      __('Unable to delete the translation. %s', 'polylang-pro'), error?.message), {
        type: 'snackbar'
      });
    }
  };
  return {
    handleDelete
  };
};
/* harmony default export */ const use_delete_post = ((/* unused pure expression or super */ null && (use_delete_post_useDeletePost)));
;// ./js/src/editors/common/components/delete-with-confirmation/maybe-redirect.js
/**
 * WordPress dependencies
 *
 */

const maybe_redirect_maybeRedirect = (postLang, postType) => {
  if (!postLang.is_default || 'page' === postType) {
    return;
  }
  const newUrl = window.location.origin + wp.sanitize.stripTags(window.location.pathname); // phpcs:ignore WordPressVIPMinimum.JS.Window.location

  const queryString = {};
  switch (postType) {
    case 'wp_navigation':
      queryString.path = '/navigation';
      break;
    case 'wp_block':
      queryString.path = '/patterns';
      queryString.categoryType = postType;
      break;
    case 'wp_template_part':
      queryString.path = '/' + postType + '/all';
      break;
  }
  location.href = addQueryArgs(newUrl, queryString); // eslint-disable-line no-undef
};
/* harmony default export */ const maybe_redirect = ((/* unused pure expression or super */ null && (maybe_redirect_maybeRedirect)));
;// ./js/src/editors/common/components/delete-with-confirmation/index.js
/**
 * WordPress Dependencies.
 *
 */




/**
 * Internal Dependencies.
 */





const delete_with_confirmation_DeleteWithConfirmation = ({
  translationData,
  postType,
  onDeleteSuccess
}) => {
  const [isOpen, setOpen] = useState(false);
  const openModal = () => setOpen(true);
  const closeModal = () => setOpen(false);
  const isTranslated = undefined !== translationData.translated_post?.id && null !== translationData.translated_post?.id;
  const canTrash = translationData.caps.delete;
  const {
    handleDelete
  } = useDeletePost();
  const _handleDelete = () => {
    const postId = 'wp_template_part' === postType && undefined !== translationData.template ? translationData.template.id : translationData.translated_post.id;
    handleDelete(postId, postType).then(() => onDeleteSuccess());
    closeModal();
    maybeRedirect(translationData.lang, postType);
  };
  return /*#__PURE__*/_jsxs(_Fragment, {
    children: [/*#__PURE__*/_jsx(DeleteButton, {
      onClick: openModal,
      language: translationData.lang,
      disabled: !isTranslated || !canTrash
    }), isOpen && /*#__PURE__*/_jsxs(Modal, {
      title: "Delete",
      onRequestClose: closeModal,
      children: [/*#__PURE__*/_jsx(DeleteModalBody, {
        isDefaultLang: translationData.lang.is_default && 'page' !== postType // No message for default language deletion with a page.
      }), /*#__PURE__*/_jsxs("div", {
        role: "group",
        className: "components-button-group buttons",
        children: [/*#__PURE__*/_jsx(Button, {
          variant: "tertiary",
          onClick: closeModal,
          type: "button",
          children: __('Cancel', 'polylang-pro')
        }), /*#__PURE__*/_jsx("span", {
          children: "\xA0"
        }), /*#__PURE__*/_jsx(Button, {
          variant: "primary",
          onClick: _handleDelete,
          type: "submit",
          children: __('Delete', 'polylang-pro')
        })]
      })]
    })]
  });
};
/* harmony default export */ const delete_with_confirmation = ((/* unused pure expression or super */ null && (delete_with_confirmation_DeleteWithConfirmation)));
;// ./js/src/editors/common/components/translations-table/site-editor/use-create-translation.js
/**
 * WordPress dependencies
 */





/**
 * Internal Dependencies.
 */

const use_create_translation_useCreateTranslation = () => {
  const {
    saveEntityRecord
  } = useDispatch(coreStore);
  const {
    createSuccessNotice,
    createErrorNotice
  } = useDispatch(noticesStore);
  const handleCreateTranslation = async (language, post) => {
    const data = {
      title: post.title.raw,
      content: post.content.raw,
      lang: language,
      from_post: post.id,
      translations: post.translations,
      status: post.status
    };
    if ('wp_template_part' === post.type) {
      const langSlugRegex = getLangSlugRegex();
      const newSlug = post.slug.replace(langSlugRegex, '');
      const translationsData = {
        [post.lang]: post.wp_id
      };
      data.slug = newSlug;
      data.area = post.area;
      data.from_post = post.wp_id;
      data.translations = translationsData;
    }
    try {
      const translation = await saveEntityRecord('postType', post.type, data, {
        throwOnError: true
      });
      createSuccessNotice(__('The translation is created, you will be redirected.', 'polylang-pro'), {
        type: 'snackbar'
      });
      const editLink = translation.translations_table[language]?.site_editor.edit_link;
      if (undefined !== editLink) {
        location.href = editLink; // eslint-disable-line no-undef
      }
    } catch (error) {
      const errorMessage = error.message && error.code !== 'unknown_error' ? error.message : __('An error occurred while creating the translation.', 'polylang-pro');
      createErrorNotice(errorMessage, {
        type: 'snackbar'
      });
    }
  };
  return {
    handleCreateTranslation
  };
};
/* harmony default export */ const use_create_translation = ((/* unused pure expression or super */ null && (use_create_translation_useCreateTranslation)));
;// ./js/src/editors/common/components/translations-table/site-editor/index.js
/**
 * Internal Dependencies.
 */







const site_editor_SiteEditorTranslationsTable = ({
  translationsTable,
  currentPost,
  translationsTableDispatch
}) => {
  const {
    handleCreateTranslation
  } = useCreateTranslation();
  const table = Array.from(translationsTable.values()).map(translationData => {
    // Don't display current post in the translation table.
    if (currentPost?.lang === translationData.lang.slug) {
      return null;
    }
    function onDeleteSuccess() {
      translationsTableDispatch({
        type: 'remove_translation',
        lang: translationData.lang.slug
      });
    }
    const isTranslated = undefined !== translationData.translated_post?.id && null !== translationData.translated_post?.id;
    const currentUserCanEdit = translationData.caps.edit;
    const currentUserCanCreate = 'wp_template_part' === currentPost.type && !currentPost.wp_id ||
    // Template Parts translation can be created from a file.
    translationData.caps.add;
    const addEditButton = () => {
      if (isTranslated && currentUserCanEdit) {
        return /*#__PURE__*/_jsx(EditButton, {
          href: decodeURI(translationData.site_editor.edit_link),
          language: translationData.lang
        });
      } else if (currentUserCanCreate) {
        const _handleCreateTranslation = () => {
          handleCreateTranslation(translationData.lang.slug, currentPost);
        };
        return /*#__PURE__*/_jsx(AddButton, {
          href: `#`,
          language: translationData.lang,
          handleAddClick: _handleCreateTranslation
        });
      }
      return null;
    };
    return /*#__PURE__*/_jsx("tr", {
      children: /*#__PURE__*/_jsxs(TranslationRow, {
        language: translationData.lang,
        children: [/*#__PURE__*/_jsx(TranslationInputCell, {
          children: /*#__PURE__*/_jsx("span", {
            className: "pll-translation-language",
            children: translationData.lang.name
          })
        }), /*#__PURE__*/_jsx(AddOrEditCell, {
          children: addEditButton()
        }), /*#__PURE__*/_jsx(DeleteCell, {
          children: /*#__PURE__*/_jsx(DeleteWithConfirmation, {
            translationData: translationData,
            postType: currentPost.type,
            onDeleteSuccess: onDeleteSuccess
          })
        }), /*#__PURE__*/_jsx(DefaultLanguageCell, {
          isDefault: translationData.lang.is_default
        })]
      })
    }, translationData.lang.slug);
  });
  return /*#__PURE__*/_jsx(TranslationsTableWrapper, {
    children: table
  });
};
/* harmony default export */ const site_editor = ((/* unused pure expression or super */ null && (site_editor_SiteEditorTranslationsTable)));
;// ./js/src/editors/common/components/translations-table/index.js
/**
 * Translations table components.
 */



;// ./js/src/editors/common/components/metaboxes/site-editor/index.js
/**
 * WordPress Dependencies.
 */



/**
 * Internal Dependencies.
 */








const SiteEditorMetabox = () => {
  const [translationTable, tableDispatch] = useReducer(translationTableReducer, new Map());
  const [currentPost, setCurrentPost] = useState({});
  const [selectedLanguage, setSelectedLanguage] = useState({});
  const [currentPostType, setCurrentPostType] = useState('');
  useEffect(() => {
    let currentType;
    // Global Styles screen doesn't provide `wp_global_style` as current edited post type.
    if ('/wp_global_styles' === wp.sanitize.stripTagsAndEncodeText(getQueryArg(window.location.href, 'path') // phpcs:ignore WordPressVIPMinimum.JS.Window.location
    )) {
      currentType = 'wp_global_styles';
    }
    // Template context can return a page. So, we need to check post type from the URL.
    if ('wp_template' === wp.sanitize.stripTagsAndEncodeText(getQueryArg(window.location.href, 'postType') // phpcs:ignore WordPressVIPMinimum.JS.Window.location
    )) {
      currentType = 'wp_template';
    }
    if (currentType) {
      setCurrentPostType(currentType);
      return;
    }
    const post = getCurrentPostFromDataStore();
    setCurrentPost(post);
    setCurrentPostType(post?.type);
    const language = getSelectedLanguage(post?.lang);
    setSelectedLanguage(language);
    tableDispatch({
      type: 'set_table',
      table: getTranslationsTable(post?.translations_table)
    });
  }, [setCurrentPost, setCurrentPostType, setSelectedLanguage, tableDispatch]);
  return /*#__PURE__*/_jsxs(MetaboxContainer, {
    isError: !selectedLanguage,
    isAllowedPostType: !UNTRANSLATABLE_POST_TYPE.includes(currentPostType),
    postType: currentPostType,
    children: [/*#__PURE__*/_jsx(LanguageItem, {
      language: selectedLanguage,
      currentPost: currentPost
    }), /*#__PURE__*/_jsx(SiteEditorTranslationsTable, {
      translationsTable: translationTable,
      currentPost: currentPost,
      translationsTableDispatch: tableDispatch
    })]
  });
};
/* harmony default export */ const metaboxes_site_editor = ((/* unused pure expression or super */ null && (SiteEditorMetabox)));
;// ./js/src/editors/common/components/switcher/utils.js
/**
 * WordPress dependencies
 */




/**
 * Internal dependencies
 */



/**
 * Persists language change.
 *
 * @param {Object} newLanguage         New language.
 * @param {Object} newTranslationsData New translations data.
 * @return {Promise} Save post promise.
 */
const saveLanguageChange = (newLanguage, newTranslationsData) => {
  return Promise.allSettled([(0,external_this_wp_data_.dispatch)(MODULE_CORE_EDITOR_KEY).editPost(newTranslationsData), (0,external_this_wp_data_.dispatch)(MODULE_CORE_EDITOR_KEY).savePost(),
  // Need to save post to recalculate permalink.
  forceLanguageSave(newLanguage.slug)]).then(() => {
    (0,external_this_wp_data_.dispatch)(settings_MODULE_CORE_KEY).invalidateResolutionForStore();
  });
};

/**
 * Returns a new map of translations data for the given language.
 *
 * @param {Object} newLanguage New language.
 * @return {Object} New translations data map.
 */
const getNewTranslationsDataMap = newLanguage => {
  const oldLanguageSlug = (0,external_this_wp_data_.select)(MODULE_CORE_EDITOR_KEY).getEditedPostAttribute('lang');
  const postId = (0,external_this_wp_data_.select)(MODULE_CORE_EDITOR_KEY).getCurrentPostId();
  const languages = (0,external_this_wp_data_.select)(settings_MODULE_KEY).getLanguages();
  const oldSelectedLanguage = languages.get(oldLanguageSlug);
  const syncPost = (0,external_this_wp_data_.select)(MODULE_CORE_EDITOR_KEY).getEditedPostAttribute('pll_sync_post');
  const synchronizedPosts = getSynchronizedPosts(syncPost);
  const translationsTableData = (0,external_this_wp_data_.select)(MODULE_CORE_EDITOR_KEY).getEditedPostAttribute('translations_table');
  const translations = (0,external_this_wp_data_.select)(MODULE_CORE_EDITOR_KEY).getEditedPostAttribute('translations');
  const translatedPosts = getTranslatedPosts(translations, translationsTableData, oldSelectedLanguage.slug);
  const translationsTable = utils_getTranslationsTable(translationsTableData, oldSelectedLanguage.slug);
  // The translated post of the previous selected language must be deleted.
  translatedPosts.delete(oldSelectedLanguage.slug);
  // Replace translated post for the new language.
  translatedPosts.set(newLanguage.slug, postId);
  // The current post is synchronized itself and synchronization must be deleted for the previous language.
  // to ensure it will not be synchronized with the new language.
  synchronizedPosts.delete(oldSelectedLanguage.slug);
  // Update translations table.
  // Add old selected language data - only data needed just to update visually the metabox.
  const oldTranslationData = translationsTable.get(oldSelectedLanguage.slug);
  translationsTable.set(oldSelectedLanguage.slug, {
    can_synchronize: oldTranslationData.can_synchronize,
    lang: oldTranslationData.lang,
    links: {
      add_link: oldTranslationData.links.add_link
    },
    caps: oldTranslationData.caps,
    site_editor: oldTranslationData.site_editor,
    block_editor: oldTranslationData.block_editor
  });
  // Update some new language data from the old selected language data.
  const newTranslationData = translationsTable.get(newLanguage.slug);
  translationsTable.set(newLanguage.slug, {
    can_synchronize: newTranslationData.can_synchronize,
    lang: newTranslationData.lang,
    links: newTranslationData.links,
    translated_post: oldTranslationData.translated_post,
    caps: newTranslationData.caps,
    site_editor: newTranslationData.site_editor,
    block_editor: newTranslationData.block_editor
  });

  // Update the global javascript variable
  pll_block_editor_plugin_settings.lang = newLanguage;
  return {
    lang: newLanguage.slug,
    pll_sync_post: convertMapToObject(synchronizedPosts),
    translations: convertMapToObject(translatedPosts),
    translations_table: convertMapToObject(translationsTable)
  };
};

/**
 * Tells whether the edited post is empty.
 *
 * @return {boolean} True if the edited post is empty, false otherwise.
 */
const isEditedPostEmpty = () => {
  const editor = (0,external_this_wp_data_.select)(MODULE_CORE_EDITOR_KEY);
  return !editor.getEditedPostAttribute('title') && !editor.getEditedPostContent() && !editor.getEditedPostAttribute('excerpt');
};

/**
 * Forces the save of the post in the new language even if no content has been written.
 * So the post metadata are correctly updated (e.g.: Attachable Medias).
 *
 * @since 3.0
 *
 * @param {string} lang A language slug.
 * @return {Promise} Save post promise.
 */
const forceLanguageSave = lang => {
  const editor = (0,external_this_wp_data_.select)(MODULE_CORE_EDITOR_KEY);
  if (isEditedPostEmpty()) {
    return external_this_wp_apiFetch_default()({
      path: (0,external_this_wp_url_.addQueryArgs)(`wp/v2/posts/${editor.getCurrentPostId()}`, {
        lang
      }),
      method: 'POST'
    });
  }
  return Promise.reject('Force save not required.');
};
;// ./js/src/editors/common/components/switcher/index.js
/**
 * WordPress dependencies
 */






/**
 * Internal dependencies
 */





const Switcher = ({
  tableDispatch
}) => {
  const {
    createSuccessNotice,
    createErrorNotice
  } = (0,external_this_wp_data_.useDispatch)(external_this_wp_notices_.store);
  const languages = (0,external_this_wp_data_.select)(settings_MODULE_KEY).getLanguages();
  const lang = languages.get((0,external_this_wp_data_.select)(MODULE_CORE_EDITOR_KEY).getEditedPostAttribute('lang'));
  const [isOpen, setOpen] = (0,external_this_wp_element_.useState)(false);
  const [selectedLang, setSelectedLang] = (0,external_this_wp_element_.useState)(lang);
  const prevLangRef = (0,external_this_wp_element_.useRef)(selectedLang);
  (0,external_this_wp_element_.useEffect)(() => {
    prevLangRef.current = lang;
  }, [lang]);
  const openModal = e => {
    setSelectedLang(languages.get(e.target.value));
    if (isEditedPostEmpty()) {
      confirmChange(e.target.value);
      return;
    }
    setOpen(true);
  };
  const closeModal = () => setOpen(false);
  const confirmChange = () => {
    closeModal();
    if (!selectedLang) {
      createErrorNotice((0,external_this_wp_i18n_.__)('Failed to save selected language', 'polylang-pro'), {
        type: 'snackbar'
      });
      return;
    }
    const newTranslationsData = getNewTranslationsDataMap(selectedLang);
    saveLanguageChange(selectedLang, newTranslationsData).then(() => {
      tableDispatch({
        type: 'set_table',
        table: utils_getTranslationsTable(newTranslationsData.translations_table)
      });
      createSuccessNotice((0,external_this_wp_i18n_.__)('Language changed', 'polylang-pro'), {
        type: 'snackbar'
      });
      document.dispatchEvent(new CustomEvent('onPostLangChoice', {
        detail: {
          lang: selectedLang
        }
      }));
    });
  };
  const abortChange = () => {
    setSelectedLang(prevLangRef.current);
    closeModal();
  };
  return /*#__PURE__*/(0,jsx_runtime.jsxs)(jsx_runtime.Fragment, {
    children: [/*#__PURE__*/(0,jsx_runtime.jsx)("p", {
      children: /*#__PURE__*/(0,jsx_runtime.jsx)("strong", {
        children: (0,external_this_wp_i18n_.__)('Language', 'polylang-pro')
      })
    }), /*#__PURE__*/(0,jsx_runtime.jsx)("label", {
      className: "screen-reader-text",
      htmlFor: "pll_post_lang_choice",
      children: (0,external_this_wp_i18n_.__)('Language', 'polylang-pro')
    }), /*#__PURE__*/(0,jsx_runtime.jsx)(LanguageDropdown, {
      selectedLanguage: selectedLang,
      handleChange: openModal,
      children: /*#__PURE__*/(0,jsx_runtime.jsx)(LanguagesOptionsList, {
        languages: languages
      })
    }), isOpen && /*#__PURE__*/(0,jsx_runtime.jsxs)(external_this_wp_components_.Modal, {
      title: (0,external_this_wp_i18n_.__)('Change language', 'polylang-pro'),
      onRequestClose: abortChange,
      children: [/*#__PURE__*/(0,jsx_runtime.jsx)("p", {
        children: (0,external_this_wp_i18n_.__)('Are you sure you want to change the language of the current content?', 'polylang-pro')
      }), /*#__PURE__*/(0,jsx_runtime.jsxs)("div", {
        role: "group",
        className: "components-button-group buttons",
        children: [/*#__PURE__*/(0,jsx_runtime.jsx)(external_this_wp_components_.Button, {
          variant: "tertiary",
          onClick: abortChange,
          type: "button",
          children: (0,external_this_wp_i18n_.__)('Cancel', 'polylang-pro')
        }), /*#__PURE__*/(0,jsx_runtime.jsx)("span", {
          children: "\xA0"
        }), /*#__PURE__*/(0,jsx_runtime.jsx)(external_this_wp_components_.Button, {
          variant: "primary",
          onClick: confirmChange,
          type: "submit",
          children: (0,external_this_wp_i18n_.__)('Change', 'polylang-pro')
        })]
      })]
    })]
  });
};
/* harmony default export */ const switcher = (Switcher);
;// ./js/src/editors/common/components/metaboxes/translations-table-reducer/index.js
/**
 * Reducer for the translations table.
 *
 * @param {Map}    state  The current state.
 * @param {Object} action The action to perform.
 * @return {Map} The new state.
 */
const translations_table_reducer_translationTableReducer = (state, action) => {
  switch (action.type) {
    case 'remove_translation':
      const removedTranslation = state.get(action.lang);
      delete removedTranslation.translated_post;
      return new Map(state);
    case 'set_table':
      return action.table;
    default:
      return state;
  }
};
;// ./js/src/editors/common/components/metaboxes/post-editor/index.js
/**
 * WordPress Dependencies.
 */



/**
 * Internal Dependencies.
 */








const PostEditorMetabox = () => {
  const [translationsTable, tableDispatch] = (0,external_this_wp_element_.useReducer)(translations_table_reducer_translationTableReducer, new Map());
  const {
    currentPost,
    currentPostType,
    selectedLanguage,
    isAllowedPostType
  } = (0,external_this_wp_data_.useSelect)(select => {
    const post = select(MODULE_CORE_EDITOR_KEY).getCurrentPost();
    const type = select(MODULE_CORE_EDITOR_KEY).getCurrentPostType();
    const lang = select(MODULE_CORE_EDITOR_KEY).getEditedPostAttribute('lang');
    const translationsData = select(MODULE_CORE_EDITOR_KEY).getEditedPostAttribute('translations_table');
    const isAllowed = !settings_UNTRANSLATABLE_POST_TYPE.includes(post?.type);
    const language = utils_getSelectedLanguage(lang);
    const table = utils_getTranslationsTable(translationsData, lang);
    tableDispatch({
      type: 'set_table',
      table
    });
    return {
      currentPost: post,
      currentPostType: type,
      selectedLanguage: language,
      isAllowedPostType: isAllowed
    };
  }, [tableDispatch]);
  const machineTranslation = pll_block_editor_plugin_settings?.machine_translation;
  return /*#__PURE__*/(0,jsx_runtime.jsxs)(container, {
    isError: !selectedLanguage,
    isAllowedPostType: isAllowedPostType,
    postType: currentPost?.type,
    children: [/*#__PURE__*/(0,jsx_runtime.jsx)(switcher, {
      selectedLanguage: selectedLanguage,
      tableDispatch: tableDispatch
    }), /*#__PURE__*/(0,jsx_runtime.jsx)(duplicate, {
      postType: currentPostType
    }), machineTranslation?.isActive && /*#__PURE__*/(0,jsx_runtime.jsx)(machine_translation, {
      postType: currentPostType,
      slug: machineTranslation.slug,
      name: machineTranslation.name,
      icon: machineTranslation.icon
    }), /*#__PURE__*/(0,jsx_runtime.jsx)(post_editor, {
      translationsTable: translationsTable,
      selectedLanguage: selectedLanguage
    })]
  });
};
/* harmony default export */ const metaboxes_post_editor = (PostEditorMetabox);
;// ./js/src/editors/common/components/metaboxes/index.js
/**
 * Metabox components.
 */



;// ./js/src/editors/common/store/index.js
/**
 * WordPress Dependencies
 */



/**
 * Internal dependencies
 */


const actions = {
  setLanguages(languages) {
    return {
      type: 'SET_LANGUAGES',
      languages
    };
  },
  setCurrentUser(currentUser, save = false) {
    return {
      type: 'SET_CURRENT_USER',
      currentUser,
      save
    };
  },
  setFromPost(fromPost) {
    return {
      type: 'SET_FROM_POST',
      fromPost
    };
  },
  fetchFromAPI(options) {
    return {
      type: 'FETCH_FROM_API',
      options
    };
  }
};
const store = (0,external_this_wp_data_.createReduxStore)(settings_MODULE_KEY, {
  reducer(state = DEFAULT_STATE, action) {
    switch (action.type) {
      case 'SET_LANGUAGES':
        return {
          ...state,
          languages: action.languages
        };
      case 'SET_CURRENT_USER':
        if (action.save) {
          updateCurrentUser(action.currentUser).then(currentUser => {
            action.currentUser = currentUser;
            return {
              ...state,
              currentUser: action.currentUser
            };
          });
        }
        return {
          ...state,
          currentUser: action.currentUser
        };
      case 'SET_FROM_POST':
        return {
          ...state,
          fromPost: action.fromPost
        };
      case 'SET_CURRENT_TEMPLATE_PART':
        return {
          ...state,
          currentTemplatePart: action.currentTemplatePart
        };
      default:
        return state;
    }
  },
  selectors: {
    getLanguages(state) {
      return state.languages;
    },
    getCurrentUser(state) {
      return state.currentUser;
    },
    getFromPost(state) {
      return state.fromPost;
    }
  },
  actions,
  controls: {
    FETCH_FROM_API(action) {
      return external_this_wp_apiFetch_default()({
        ...action.options
      });
    }
  },
  resolvers: {
    *getLanguages() {
      const path = '/pll/v1/languages';
      const languages = yield actions.fetchFromAPI({
        path,
        filterLang: false
      });
      return actions.setLanguages(convertArrayToMap(languages, 'slug'));
    },
    *getCurrentUser() {
      const path = '/wp/v2/users/me';
      const currentUser = yield actions.fetchFromAPI({
        path,
        filterLang: true
      });
      return actions.setCurrentUser(currentUser);
    }
  }
});
(0,external_this_wp_data_.register)(store);

/**
 * Save current user when it is wondered.
 *
 * @param {Object} currentUser
 * @return {Object} The current user updated.
 */
function updateCurrentUser(currentUser) {
  return Promise.resolve(external_this_wp_apiFetch_default()({
    path: '/wp/v2/users/me',
    data: currentUser,
    method: 'POST'
  }));
}
;// ./js/src/editors/common/element/index.js
/**
 * WordPress Dependencies.
 */
 // eslint-disable-line import/named

/**
 * Renders a React node in the browser DOM with a polyfill for WordPress < 6.2
 * where React 18 wasn't available yet.
 *
 * @param {Object} reactNode The React node to render.
 */
const renderWithLegacy = reactNode => {
  const _root = document.createElement('div');
  _root.id = 'pll-root';
  const root = document.body.appendChild(_root);
  if (external_this_wp_element_.createRoot) {
    (0,external_this_wp_element_.createRoot)(root).render(reactNode);
  } else {
    // Backward compatibility with WordPress < 6.2.
    (0,external_this_wp_element_.render)(reactNode, root);
  }
};
;// ./js/src/packages/middlewares/filter-path.js
/**
 * Filters requests for translatable entities.
 * This logic is shared across all Polylang plugins.
 *
 * @since 3.5
 *
 * @param {APIFetchOptions}                           options
 * @param {Array}                                     filteredRoutes
 * @param {function(APIFetchOptions):APIFetchOptions} filter
 * @return {APIFetchOptions} Modified REST request options.
 */
const filterPathMiddleware = (options, filteredRoutes, filter) => {
  const cleanPath = options.path.split('?')[0].replace(/^\/+|\/+$/g, ''); // Get path without query parameters and trim '/'.

  return Object.values(filteredRoutes).find(path => cleanPath === path) ? filter(options) : options;
};
/* harmony default export */ const filter_path = (filterPathMiddleware);
;// ./js/src/packages/middlewares/editors-requests-filter.js
/**
 * WordPress dependencies.
 */


/*
 * Internal dependencies.
 */


/**
 * Safely filters requests for translatable entities in block editor type screens.
 * Ensures that `pllFilteredRoutes` has been well defined on server side and
 * that the filtered request is a REST one.
 *
 * @param {function(APIFetchOptions):APIFetchOptions} filterCallback
 */
const editorsRequestsFilter = filterCallback => {
  external_this_wp_apiFetch_default().use((options, next) => {
    /*
     * If options.url is defined, this is not a REST request but a direct call to post.php for legacy metaboxes.
     * If `filteredRoutes` is not defined, return early.
     */
    if ('undefined' !== typeof options.url || 'undefined' === typeof pllFilteredRoutes) {
      return next(options);
    }
    return next(filter_path(options, pllFilteredRoutes, filterCallback));
  });
};
/* harmony default export */ const editors_requests_filter = (editorsRequestsFilter);
;// ./js/src/packages/middlewares/index.js
/**
 * Available Middlewares.
 */



;// ./js/src/editors/post/index.js
/**
 * Import styles
 */


/**
 * WordPress Dependencies.
 */


/**
 * Internal Dependencies.
 */










editors_requests_filter(addParametersToRequest);
const sidebarName = 'polylang-sidebar';
const settingsErrors = pll_block_editor_plugin_settings?.machine_translation?.errors; // eslint-disable-line no-undef, camelcase

renderWithLegacy(/*#__PURE__*/(0,jsx_runtime.jsx)(app, {
  sidebar: () => {
    return /*#__PURE__*/(0,jsx_runtime.jsx)(sidebar, {
      SidebarSlot: external_this_wp_editPost_.PluginSidebar,
      MoreMenuItemSlot: external_this_wp_editPost_.PluginSidebarMoreMenuItem,
      sidebarName: sidebarName,
      children: /*#__PURE__*/(0,jsx_runtime.jsx)(metaboxes_post_editor, {})
    });
  },
  sidebarName: sidebarName,
  onPromise: isBlockPostEditorContextInitialized,
  children: undefined !== settingsErrors && /*#__PURE__*/(0,jsx_runtime.jsx)(display_notices, {
    notices: settingsErrors
  })
}));
})();

this["polylang-pro"] = __webpack_exports__;
/******/ })()
;