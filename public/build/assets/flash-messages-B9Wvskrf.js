import{K as p,r as c,j as r}from"./app-DlzXAPRm.js";import{a}from"./yumyum-images-ogaRer6Q.js";import{X as x}from"./sheet-BPlR3UFG.js";/**
 * @license lucide-react v0.475.0 - ISC
 *
 * This source code is licensed under the ISC license.
 * See the LICENSE file in the root directory of this source tree.
 */const b=[["circle",{cx:"12",cy:"12",r:"10",key:"1mglay"}],["path",{d:"m9 12 2 2 4-4",key:"dzmm74"}]],g=a("CircleCheck",b);/**
 * @license lucide-react v0.475.0 - ISC
 *
 * This source code is licensed under the ISC license.
 * See the LICENSE file in the root directory of this source tree.
 */const k=[["circle",{cx:"12",cy:"12",r:"10",key:"1mglay"}],["path",{d:"m15 9-6 6",key:"1uzhvr"}],["path",{d:"m9 9 6 6",key:"z0biqf"}]],u=a("CircleX",k);/**
 * @license lucide-react v0.475.0 - ISC
 *
 * This source code is licensed under the ISC license.
 * See the LICENSE file in the root directory of this source tree.
 */const h=[["path",{d:"m21.73 18-8-14a2 2 0 0 0-3.48 0l-8 14A2 2 0 0 0 4 21h16a2 2 0 0 0 1.73-3",key:"wmoenq"}],["path",{d:"M12 9v4",key:"juzpu7"}],["path",{d:"M12 17h.01",key:"p32p05"}]],y=a("TriangleAlert",h),f={success:{container:"border-emerald-200 bg-emerald-50 text-emerald-900 dark:border-emerald-900 dark:bg-emerald-950 dark:text-emerald-100",icon:g},error:{container:"border-rose-200 bg-rose-50 text-rose-900 dark:border-rose-900 dark:bg-rose-950 dark:text-rose-100",icon:u},warning:{container:"border-amber-200 bg-amber-50 text-amber-900 dark:border-amber-900 dark:bg-amber-950 dark:text-amber-100",icon:y}};function v(){const{flash:s}=p().props,[i,t]=c.useState([]);c.useEffect(()=>{t([])},[s.success,s.error,s.warning]);const o=["success","error","warning"].map(e=>({tone:e,message:s[e]})).filter(e=>!!e.message&&!i.includes(e.tone));return o.length===0?null:r.jsx("div",{className:"flex flex-col gap-2 pt-4",role:"status","aria-live":"polite",children:o.map(({tone:e,message:n})=>{const{container:l,icon:d}=f[e];return r.jsxs("div",{className:`flex items-start gap-3 rounded-lg border px-4 py-3 text-sm ${l}`,children:[r.jsx(d,{className:"mt-0.5 size-4 shrink-0"}),r.jsx("p",{className:"flex-1",children:n}),r.jsx("button",{type:"button",onClick:()=>t(m=>[...m,e]),className:"rounded p-0.5 opacity-60 transition-opacity hover:opacity-100","aria-label":"Dismiss",children:r.jsx(x,{className:"size-4"})})]},e)})})}export{v as F};
