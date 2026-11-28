import{u as d,j as e,L as m}from"./app-CWiVxDO1.js";import{a as t,c as n,B as h}from"./app-logo-icon-BpyBcXr6.js";import{S as y,H as u}from"./layout-CjNx83-K.js";import{A as x}from"./app-layout-CEQWFoST.js";import"./index-BXrL47zx.js";import"./index-Cv4ZfEd3.js";/**
 * @license lucide-react v0.475.0 - ISC
 *
 * This source code is licensed under the ISC license.
 * See the LICENSE file in the root directory of this source tree.
 */const g=[["rect",{width:"20",height:"14",x:"2",y:"3",rx:"2",key:"48i651"}],["line",{x1:"8",x2:"16",y1:"21",y2:"21",key:"1svkeh"}],["line",{x1:"12",x2:"12",y1:"17",y2:"21",key:"vw1qmm"}]],k=t("Monitor",g);/**
 * @license lucide-react v0.475.0 - ISC
 *
 * This source code is licensed under the ISC license.
 * See the LICENSE file in the root directory of this source tree.
 */const j=[["path",{d:"M12 3a6 6 0 0 0 9 9 9 9 0 1 1-9-9Z",key:"a7tn18"}]],b=t("Moon",j);/**
 * @license lucide-react v0.475.0 - ISC
 *
 * This source code is licensed under the ISC license.
 * See the LICENSE file in the root directory of this source tree.
 */const f=[["circle",{cx:"12",cy:"12",r:"4",key:"4exip2"}],["path",{d:"M12 2v2",key:"tus03m"}],["path",{d:"M12 20v2",key:"1lh1kg"}],["path",{d:"m4.93 4.93 1.41 1.41",key:"149t6j"}],["path",{d:"m17.66 17.66 1.41 1.41",key:"ptbguv"}],["path",{d:"M2 12h2",key:"1t8f8n"}],["path",{d:"M20 12h2",key:"1q8mjw"}],["path",{d:"m6.34 17.66-1.41 1.41",key:"1m8zz5"}],["path",{d:"m19.07 4.93-1.41 1.41",key:"1shlcs"}]],A=t("Sun",f);function M({className:c="",...o}){const{appearance:s,updateAppearance:i}=d(),r=[{value:"light",icon:A,label:"Light"},{value:"dark",icon:b,label:"Dark"},{value:"system",icon:k,label:"System"}];return e.jsx("div",{className:n("inline-flex gap-1 rounded-lg bg-neutral-100 p-1 dark:bg-neutral-800",c),...o,children:r.map(({value:a,icon:p,label:l})=>e.jsxs(h,{type:"button",onClick:()=>i(a),variant:s===a?"secondary":"ghost",size:"sm",className:n("px-3.5",s===a&&"shadow-xs"),children:[e.jsx(p,{className:"h-4 w-4"}),e.jsx("span",{children:l})]},a))})}const v=[{title:"Appearance settings",href:"/settings/appearance"}];function q(){return e.jsxs(x,{breadcrumbs:v,children:[e.jsx(m,{title:"Appearance settings"}),e.jsx(y,{children:e.jsxs("div",{className:"space-y-6",children:[e.jsx(u,{title:"Appearance settings",description:"Update your account's appearance settings"}),e.jsx(M,{})]})})]})}export{q as default};
