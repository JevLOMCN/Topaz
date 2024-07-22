"use strict";var m=Object.getOwnPropertySymbols;var B=Object.prototype.hasOwnProperty,V=Object.prototype.propertyIsEnumerable;var I=(e,n)=>{var r={};for(var s in e)B.call(e,s)&&n.indexOf(s)<0&&(r[s]=e[s]);if(e!=null&&m)for(var s of m(e))n.indexOf(s)<0&&V.call(e,s)&&(r[s]=e[s]);return r};const t=require("vue"),C=require("./Icon.js"),l=require("./constants.js"),w=require("./useModelWrapper.cjs"),S=require("./useSplitAttributes.cjs"),T=require("./useFieldData.cjs"),q=require("./_plugin-vue_export-helper.js");require("./useComputedDirection.cjs");require("./useComputedLanguage.cjs");require("./useComputedDisabled.cjs");const h=l.makeStringTypeValidator(l.TextInputTypes),M=l.makeStringTypeValidator(l.ValidationStatusTypes),K=t.defineComponent({name:"CdxTextInput",components:{CdxIcon:C.CdxIcon},inheritAttrs:!1,expose:["focus","blur"],props:{modelValue:{type:[String,Number],default:""},inputType:{type:String,default:"text",validator:h},status:{type:String,default:"default",validator:M},disabled:{type:Boolean,default:!1},startIcon:{type:[String,Object],default:void 0},endIcon:{type:[String,Object],default:void 0},clearable:{type:Boolean,default:!1}},emits:["update:modelValue","keydown","input","change","focus","blur","clear"],setup(e,{emit:n,attrs:r}){const s=r.id,{computedDisabled:a,computedStatus:c,computedInputId:i}=T(t.toRef(e,"disabled"),t.toRef(e,"status"),s),u=t.inject(l.FieldDescriptionIdKey,void 0),d=w(t.toRef(e,"modelValue"),n),p=t.computed(()=>e.clearable&&!!d.value&&!a.value),f=t.computed(()=>({"cdx-text-input--has-start-icon":!!e.startIcon,"cdx-text-input--has-end-icon":!!e.endIcon,"cdx-text-input--clearable":p.value,["cdx-text-input--status-".concat(c.value)]:!0})),{rootClasses:b,rootStyle:v,otherAttrs:x}=S(r,f),g=t.computed(()=>{const y=x.value,{id:o}=y;return I(y,["id"])}),k=t.computed(()=>({"cdx-text-input__input--has-value":!!d.value}));return{computedInputId:i,descriptionId:u,wrappedModel:d,isClearable:p,rootClasses:b,rootStyle:v,otherAttrsMinusId:g,inputClasses:k,computedDisabled:a,onClear:o=>{d.value="",n("clear",o)},onInput:o=>{n("input",o)},onChange:o=>{n("change",o)},onKeydown:o=>{(o.key==="Home"||o.key==="End")&&!o.ctrlKey&&!o.metaKey||n("keydown",o)},onFocus:o=>{n("focus",o)},onBlur:o=>{n("blur",o)},cdxIconClear:C.Y3}},methods:{focus(){this.$refs.input.focus()},blur(){this.$refs.input.blur()}}});const F=["id","type","aria-describedby","disabled"];function $(e,n,r,s,a,c){const i=t.resolveComponent("cdx-icon");return t.openBlock(),t.createElementBlock("div",{class:t.normalizeClass(["cdx-text-input",e.rootClasses]),style:t.normalizeStyle(e.rootStyle)},[t.withDirectives(t.createElementVNode("input",t.mergeProps({id:e.computedInputId,ref:"input","onUpdate:modelValue":n[0]||(n[0]=u=>e.wrappedModel=u),class:["cdx-text-input__input",e.inputClasses]},e.otherAttrsMinusId,{type:e.inputType,"aria-describedby":e.descriptionId,disabled:e.computedDisabled,size:"1",onInput:n[1]||(n[1]=(...u)=>e.onInput&&e.onInput(...u)),onChange:n[2]||(n[2]=(...u)=>e.onChange&&e.onChange(...u)),onFocus:n[3]||(n[3]=(...u)=>e.onFocus&&e.onFocus(...u)),onBlur:n[4]||(n[4]=(...u)=>e.onBlur&&e.onBlur(...u)),onKeydown:n[5]||(n[5]=(...u)=>e.onKeydown&&e.onKeydown(...u))}),null,16,F),[[t.vModelDynamic,e.wrappedModel]]),e.startIcon?(t.openBlock(),t.createBlock(i,{key:0,icon:e.startIcon,class:"cdx-text-input__icon-vue cdx-text-input__start-icon"},null,8,["icon"])):t.createCommentVNode("",!0),e.endIcon?(t.openBlock(),t.createBlock(i,{key:1,icon:e.endIcon,class:"cdx-text-input__icon-vue cdx-text-input__end-icon"},null,8,["icon"])):t.createCommentVNode("",!0),e.isClearable?(t.openBlock(),t.createBlock(i,{key:2,icon:e.cdxIconClear,class:"cdx-text-input__icon-vue cdx-text-input__clear-icon",onMousedown:n[6]||(n[6]=t.withModifiers(()=>{},["prevent"])),onClick:e.onClear},null,8,["icon","onClick"])):t.createCommentVNode("",!0)],6)}const A=q._export_sfc(K,[["render",$]]);module.exports=A;