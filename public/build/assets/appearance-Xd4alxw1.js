import{u as l,j as e,L as m}from"./app-CCUuFgDM.js";import{a as t,c as n,B as h,C as x,b as y,f as u}from"./card-BwnQDJ9f.js";import{S as g,H as k}from"./layout-DbAPPqYo.js";import{A as j}from"./app-layout-C7n6BHVh.js";import"./index-CsqcLU8c.js";import"./index-lJJ0JPc-.js";import"./app-logo-BctgGUEB.js";import"./users-round-CRr4YlpR.js";/**
 * @license lucide-react v0.475.0 - ISC
 *
 * This source code is licensed under the ISC license.
 * See the LICENSE file in the root directory of this source tree.
 */const b=[["rect",{width:"20",height:"14",x:"2",y:"3",rx:"2",key:"48i651"}],["line",{x1:"8",x2:"16",y1:"21",y2:"21",key:"1svkeh"}],["line",{x1:"12",x2:"12",y1:"17",y2:"21",key:"vw1qmm"}]],f=t("Monitor",b);/**
 * @license lucide-react v0.475.0 - ISC
 *
 * This source code is licensed under the ISC license.
 * See the LICENSE file in the root directory of this source tree.
 */const A=[["path",{d:"M12 3a6 6 0 0 0 9 9 9 9 0 1 1-9-9Z",key:"a7tn18"}]],M=t("Moon",A);/**
 * @license lucide-react v0.475.0 - ISC
 *
 * This source code is licensed under the ISC license.
 * See the LICENSE file in the root directory of this source tree.
 */const N=[["circle",{cx:"12",cy:"12",r:"4",key:"4exip2"}],["path",{d:"M12 2v2",key:"tus03m"}],["path",{d:"M12 20v2",key:"1lh1kg"}],["path",{d:"m4.93 4.93 1.41 1.41",key:"149t6j"}],["path",{d:"m17.66 17.66 1.41 1.41",key:"ptbguv"}],["path",{d:"M2 12h2",key:"1t8f8n"}],["path",{d:"M20 12h2",key:"1q8mjw"}],["path",{d:"m6.34 17.66-1.41 1.41",key:"1m8zz5"}],["path",{d:"m19.07 4.93-1.41 1.41",key:"1shlcs"}]],v=t("Sun",N);function C({className:r="",...o}){const{appearance:s,updateAppearance:c}=l(),i=[{value:"light",icon:v,label:"Light"},{value:"dark",icon:M,label:"Dark"},{value:"system",icon:f,label:"System"}];return e.jsx("div",{className:n("inline-flex gap-1 rounded-lg bg-neutral-100 p-1 dark:bg-neutral-800",r),...o,children:i.map(({value:a,icon:p,label:d})=>e.jsxs(h,{type:"button",onClick:()=>c(a),variant:s===a?"secondary":"ghost",size:"sm",className:n("px-3.5",s===a&&"shadow-xs"),children:[e.jsx(p,{className:"h-4 w-4"}),e.jsx("span",{children:d})]},a))})}const L=[{title:"Appearance settings",href:"/settings/appearance"}];function $(){return e.jsxs(j,{breadcrumbs:L,children:[e.jsx(m,{title:"Appearance settings"}),e.jsx(g,{children:e.jsxs(x,{className:"rounded-2xl",children:[e.jsx(y,{children:e.jsx(k,{title:"Appearance settings",description:"Update your account's appearance settings"})}),e.jsx(u,{className:"pt-0",children:e.jsx(C,{})})]})})]})}export{$ as default};
