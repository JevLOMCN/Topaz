"use strict";
(() => {
var exports = {};
exports.id = 164;
exports.ids = [164];
exports.modules = {

/***/ 7534:
/***/ ((__unused_webpack_module, __webpack_exports__, __webpack_require__) => {

// ESM COMPAT FLAG
__webpack_require__.r(__webpack_exports__);

// EXPORTS
__webpack_require__.d(__webpack_exports__, {
  config: () => (/* binding */ config),
  "default": () => (/* binding */ next_route_loaderpage_2Fsitemap_xml_absolutePagePath_private_next_pages_2Fsitemap_xml_tsx_preferredRegion_middlewareConfig_e30_3D_),
  getServerSideProps: () => (/* binding */ next_route_loaderpage_2Fsitemap_xml_absolutePagePath_private_next_pages_2Fsitemap_xml_tsx_preferredRegion_middlewareConfig_e30_3D_getServerSideProps),
  getStaticPaths: () => (/* binding */ getStaticPaths),
  getStaticProps: () => (/* binding */ getStaticProps),
  reportWebVitals: () => (/* binding */ reportWebVitals),
  routeModule: () => (/* binding */ routeModule),
  unstable_getServerProps: () => (/* binding */ unstable_getServerProps),
  unstable_getServerSideProps: () => (/* binding */ unstable_getServerSideProps),
  unstable_getStaticParams: () => (/* binding */ unstable_getStaticParams),
  unstable_getStaticPaths: () => (/* binding */ unstable_getStaticPaths),
  unstable_getStaticProps: () => (/* binding */ unstable_getStaticProps)
});

// NAMESPACE OBJECT: ./src/pages/sitemap.xml.tsx
var sitemap_xml_namespaceObject = {};
__webpack_require__.r(sitemap_xml_namespaceObject);
__webpack_require__.d(sitemap_xml_namespaceObject, {
  "default": () => (sitemap_xml),
  getServerSideProps: () => (getServerSideProps)
});

// EXTERNAL MODULE: ./node_modules/next/dist/server/future/route-modules/pages/module.js
var pages_module = __webpack_require__(3185);
var module_default = /*#__PURE__*/__webpack_require__.n(pages_module);
// EXTERNAL MODULE: ./node_modules/next/dist/build/webpack/loaders/next-route-loader/helpers.js
var helpers = __webpack_require__(7182);
;// CONCATENATED MODULE: ./src/pages/sitemap.xml.tsx
function generateSiteMap(config, pages) {
    return `<?xml version="1.0" encoding="UTF-8"?>
   <urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">
     <url>
       <loc>https://${config.host}</loc>
        <lastmod>${config.lastMod}</lastmod>
        <changefreq>daily</changefreq>
     </url>
     ${pages.map((page)=>{
        return `
       <url>
          <link rel="canonical" href="${page}" />
          <loc>${page}</loc>
       </url>
     `;
    }).join("")}
   </urlset>
 `;
}
function SiteMap() {}
async function getServerSideProps({ req, res }) {
    const host = req.headers.host ?? "mir4tools";
    const lastMod = new Date().toISOString();
    const pages = [
        "home",
        "xp"
    ];
    const config = {
        host,
        lastMod
    };
    const sitemap = generateSiteMap(config, pages);
    res.setHeader("Content-Type", "text/xml");
    res.write(sitemap);
    res.end();
    return {
        props: {}
    };
}
/* harmony default export */ const sitemap_xml = (SiteMap);

;// CONCATENATED MODULE: ./node_modules/next/dist/build/webpack/loaders/next-route-loader/index.js?page=%2Fsitemap.xml&absolutePagePath=private-next-pages%2Fsitemap.xml.tsx&preferredRegion=&middlewareConfig=e30%3D!

        // Next.js Route Loader
        
        

        // Import the userland code.
        

        // Re-export the component (should be the default export).
        /* harmony default export */ const next_route_loaderpage_2Fsitemap_xml_absolutePagePath_private_next_pages_2Fsitemap_xml_tsx_preferredRegion_middlewareConfig_e30_3D_ = ((0,helpers/* hoist */.l)(sitemap_xml_namespaceObject, "default"));

        // Re-export methods.
        const getStaticProps = (0,helpers/* hoist */.l)(sitemap_xml_namespaceObject, "getStaticProps")
        const getStaticPaths = (0,helpers/* hoist */.l)(sitemap_xml_namespaceObject, "getStaticPaths")
        const next_route_loaderpage_2Fsitemap_xml_absolutePagePath_private_next_pages_2Fsitemap_xml_tsx_preferredRegion_middlewareConfig_e30_3D_getServerSideProps = (0,helpers/* hoist */.l)(sitemap_xml_namespaceObject, "getServerSideProps")
        const config = (0,helpers/* hoist */.l)(sitemap_xml_namespaceObject, "config")
        const reportWebVitals = (0,helpers/* hoist */.l)(sitemap_xml_namespaceObject, "reportWebVitals")

        // Re-export legacy methods.
        const unstable_getStaticProps = (0,helpers/* hoist */.l)(sitemap_xml_namespaceObject, "unstable_getStaticProps")
        const unstable_getStaticPaths = (0,helpers/* hoist */.l)(sitemap_xml_namespaceObject, "unstable_getStaticPaths")
        const unstable_getStaticParams = (0,helpers/* hoist */.l)(sitemap_xml_namespaceObject, "unstable_getStaticParams")
        const unstable_getServerProps = (0,helpers/* hoist */.l)(sitemap_xml_namespaceObject, "unstable_getServerProps")
        const unstable_getServerSideProps = (0,helpers/* hoist */.l)(sitemap_xml_namespaceObject, "unstable_getServerSideProps")

        // Create and export the route module that will be consumed.
        const options = {"definition":{"kind":"PAGES","page":"/sitemap.xml","pathname":"/sitemap.xml","bundlePath":"","filename":""}}
        const routeModule = new (module_default())({ ...options, userland: sitemap_xml_namespaceObject })
        
        
    

/***/ }),

/***/ 3076:
/***/ ((module) => {

module.exports = require("next/dist/server/future/route-modules/route-module.js");

/***/ }),

/***/ 3100:
/***/ ((module) => {

module.exports = require("next/dist/server/render.js");

/***/ })

};
;

// load runtime
var __webpack_require__ = require("../webpack-runtime.js");
__webpack_require__.C(exports);
var __webpack_exec__ = (moduleId) => (__webpack_require__(__webpack_require__.s = moduleId))
var __webpack_exports__ = __webpack_require__.X(0, [722], () => (__webpack_exec__(7534)));
module.exports = __webpack_exports__;

})();