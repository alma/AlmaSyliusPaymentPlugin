(function(h,b){typeof exports=="object"&&typeof module<"u"?b(exports):typeof define=="function"&&define.amd?define(["exports"],b):(h=typeof globalThis<"u"?globalThis:h||self,b((h.Alma=h.Alma||{},h.Alma.InPage={})))})(this,function(h){"use strict";const b=(e="LIVE",t)=>{let o=null,n=!1,a=new Set,i=null;return{setEmbeddedSelector:p=>{o=p},getEmbeddedSelector:()=>o,setIsCheckoutLoaded:p=>{n=p},getIsCheckoutLoaded:()=>n,getEnvironment:()=>e,getCheckoutUrl:()=>t,addMessageToSendLater:p=>{a.add(p)},getAndResetMessagesToSendLater:()=>{const p=Array.from(a);return a=new Set,p},setTranslations:p=>{i=p},getTranslations:()=>i}},r="alma-in-page-modal",_={embedded:"/in-page/embedded/",modal:"/in-page/modal/"},P="v2.7.3",v=e=>{const t=document.getElementById(e.replace("#",""));if(t)return t;L()};function L(){throw new Error("Element not found, please add an id selector for the iframe")}function w(e,t){if(t)return t;switch(e){case"LOCAL":return"http://localhost:1350";case"TEST":case"SANDBOX":return"https://checkout.sandbox.getalma.eu";case"STAGING":return"https://checkout.staging.almapay.com";case"DEV":return"https://checkout.dev.almapay.com";case"LIVE":case"PROD":default:return"https://checkout.getalma.eu"}}function k(e){switch(e){case"LOCAL":return"http://localhost:1337";case"TEST":case"SANDBOX":return"https://api.sandbox.getalma.eu";case"STAGING":return"https://api.staging.almapay.com";case"DEV":return"https://api.dev.almapay.com";case"LIVE":case"PROD":default:return"https://api.getalma.eu"}}function O(e,t){const o=k(t);return fetch(`${o}/v1/checkout/payments/${e}/return-urls`,{headers:{"X-Alma-Agent":`Alma Checkout - InPage/${C()}`}}).then(n=>n.json()).then(n=>{if(t!=="PROD"&&t!=="LIVE")if(n.error_code){let a="";if(n.error_code==="not_found"){const i=k(t);a=`

Please check your payment has been created on the ${t} environment (${i}).`}alert(`Error: ${n.error_code}

${n.message}${a}`)}else n.origin&&n.origin!=="online_in_page"&&alert(`Your payment does not have the proper origin for an in-page payment, you should use "online_in_page". 

Please, check the documentation:
https://docs.almapay.com/docs/integration-in-page`);return{success:n.return_url,failure:n.failure_return_url}})}function B(e,t,o,n){return w(t,n)+"/"+e+o}function T(e,t){return w(e,t)+_.embedded}function C(){const e=P.match(/\d+\.\d+\.\d+/);return e?e[0]:"2.0.0"}function U(e,t,o,n){const a=[],i=s=>{if(s.origin!==w(e,n)||s.data.from!=="checkout")return!1;const c=s.data.payload,f=s.data.domElement;if(s.data.type==="in_page_status"){if((c==="ready_for_payment"||c==="can_open_modal")&&t!==f)return;a.filter(l=>l&&l.eventName===c).forEach(l=>{l&&l.callback()})}else if(s.data.type==="embedded_height"){if(t!==f)return;const l=document.getElementById(`alma-embedded-iframe-${t}`);l&&(l.style.height=`${Math.ceil(c)}px`)}else s.data.type==="translations"?o(c):console.warn("Unknown message type:",s.data.type)};window.addEventListener("message",i,!1);function d(){window.removeEventListener("message",i,!1)}function m(s,c,f){const l=a.findIndex(y=>y&&y.eventName===s&&y.callerName===c);l&&(a[l]=null),a.push({eventName:s,callerName:c,callback:f})}return{onInPageStatusChanged:m,unsubscribe:d}}function I(e,t){var d;if(e.getIsCheckoutLoaded()===!1)return e.addMessageToSendLater(t),!1;const o=e.getEmbeddedSelector();if(!o)return console.error("Can not send message yet, mount() has not been called."),!1;const n=v(o),a=n==null?void 0:n.firstChild,i=w(e.getEnvironment(),e.getCheckoutUrl());return(d=a.contentWindow)==null||d.postMessage(t,i),!0}function R(e){e.getAndResetMessagesToSendLater().forEach(o=>{I(e,o)})}function D(e){const{environment:t,merchantId:o,selector:n,amountInCents:a,installmentsCount:i,deferredDays:d,deferredMonths:m,locale:s,captureMethod:c,style:f,onIntegratedPayButtonClicked:l,checkoutUrl:y}=e;if(!n)return L(),!1;const A=T(t??"PROD",y),u=new URL(A);return u.searchParams.append("merchantId",o),u.searchParams.append("amountInCents",a.toString()),u.searchParams.append("installmentsCount",i.toString()),d&&u.searchParams.append("deferredDays",d.toString()),m&&u.searchParams.append("deferredMonths",m.toString()),u.searchParams.append("locale",s??"FR"),u.searchParams.append("captureMethod",c??"automatic"),u.searchParams.append("domElement",n),u.searchParams.append("style",JSON.stringify(f||null)),u.searchParams.append("inPageVersion",C()),l&&u.searchParams.append("showPayButton","true"),F({selector:n,url:u.toString(),shouldBeHidden:i>4&&!l}),!0}function F({selector:e,url:t,shouldBeHidden:o}){const n=v(e),a=document.createElement("iframe");a.src=t,a.id=`alma-embedded-iframe-${e}`,a.style.width="100%",a.style.height="150px",a.style.transition="height 0.5s",a.style.border="none",a.style.display=o?"none":"block",a.setAttribute("allow","payment"),n==null||n.replaceChildren(a)}const x={orange:"#FA5022",backdrop:"#6C6C6C",white:"#FEFEFE"},j=`
#${r}-element {
  position: fixed;
  z-index: 10337;
  top: 0;
  left: 0;
  bottom: 0;
  right: 0;
  display: flex;
  align-items: center;
  justify-content: center;
  z-index: 9999;
}

#${r}-background {
  background: ${x.backdrop};
  position: absolute;
  width: 100vw;
  height: 100vh;
  opacity: 0.8;
}

#${r}-body {
  background: ${x.white};
  width: 100%;
  max-width: 700px;
  height: calc(100% - 32px);
  bottom: 0;
  position: absolute;
  border-radius: 0;
  box-shadow: 0px 0px 12px 4px rgba(41, 15, 8, 0.04);
  border-radius: 20px 20px 0 0;
  overflow: hidden;
}

#${r}-close {
  position: absolute;
  right: 0;
  top: 0;
  cursor: pointer;
  width: 72px;
  height: 72px;
}

#${r}-close:hover, #${r}-close:focus-visible {
    border-radius: 50%;
    outline: 1px solid ${x.orange};
    outline-offset: -16px;
}

#${r}-logo {
  position: absolute;
  left: 24px;
  top: 16px;
}

#${r}-iframe {
  width: 100%;
  height: 100%;
  border: 0;
}

.alma-fixed-body {
  position: fixed;
  width: 100%;
  overflow: auto;
  height: 100%;
}

.${r}-slideIn {
  animation: ${r}-slideIn 300ms ease-out;
  animation-iteration-count: 1;
}

.${r}-fadeIn {
  animation: ${r}-fadeIn 300ms;
  animation-iteration-count: 1;
}

.${r}-slideOut {
  animation: ${r}-slideOut 300ms ease-in;
  animation-iteration-count: 1;
}

.${r}-fadeOut {
  animation: ${r}-fadeOut 300ms;
  animation-iteration-count: 1;
}

@media (min-width: 600px) {
  #${r}-body {
    width: 80%;
    height: 80%;
    max-height: 700px;
    bottom: initial;
    border-radius: 20px;
  }
}

@keyframes ${r}-slideIn {
  from {
    transform: translateY(300px);
  }

  to {
    transform: translateY(0px);
  }
}

@keyframes ${r}-fadeIn {
  from {
    opacity: 0;
    visibility: hidden;
  }

  to {
    opacity: 1;
    visibility: visible;
  }
}

@keyframes ${r}-slideOut {
  from {
    transform: translateY(0px);
  }

  to {
    transform: translateY(300px);
  }
}

@keyframes ${r}-fadeOut {
  from {
    opacity: 1;
    visibility: visible;
  }

  to {
    opacity: 0;
    visibility: hidden;
  }
}
`,V="data:image/svg+xml,%3csvg%20width='72'%20height='72'%20viewBox='0%200%2072%2072'%20fill='none'%20xmlns='http://www.w3.org/2000/svg'%3e%3cg%20filter='url(%23filter0_d_12596_6540)'%3e%3ccircle%20cx='36'%20cy='36'%20r='20'%20fill='white'/%3e%3c/g%3e%3cpath%20fill-rule='evenodd'%20clip-rule='evenodd'%20d='M29.4697%2029.4697C29.7626%2029.1768%2030.2374%2029.1768%2030.5303%2029.4697L36%2034.9393L41.4697%2029.4697C41.7626%2029.1768%2042.2374%2029.1768%2042.5303%2029.4697C42.8232%2029.7626%2042.8232%2030.2374%2042.5303%2030.5303L37.0607%2036L42.5303%2041.4697C42.8232%2041.7626%2042.8232%2042.2374%2042.5303%2042.5303C42.2374%2042.8232%2041.7626%2042.8232%2041.4697%2042.5303L36%2037.0607L30.5303%2042.5303C30.2374%2042.8232%2029.7626%2042.8232%2029.4697%2042.5303C29.1768%2042.2374%2029.1768%2041.7626%2029.4697%2041.4697L34.9393%2036L29.4697%2030.5303C29.1768%2030.2374%2029.1768%2029.7626%2029.4697%2029.4697Z'%20fill='%231A1A1A'/%3e%3cdefs%3e%3cfilter%20id='filter0_d_12596_6540'%20x='0'%20y='0'%20width='72'%20height='72'%20filterUnits='userSpaceOnUse'%20color-interpolation-filters='sRGB'%3e%3cfeFlood%20flood-opacity='0'%20result='BackgroundImageFix'/%3e%3cfeColorMatrix%20in='SourceAlpha'%20type='matrix'%20values='0%200%200%200%200%200%200%200%200%200%200%200%200%200%200%200%200%200%20127%200'%20result='hardAlpha'/%3e%3cfeMorphology%20radius='4'%20operator='dilate'%20in='SourceAlpha'%20result='effect1_dropShadow_12596_6540'/%3e%3cfeOffset/%3e%3cfeGaussianBlur%20stdDeviation='6'/%3e%3cfeComposite%20in2='hardAlpha'%20operator='out'/%3e%3cfeColorMatrix%20type='matrix'%20values='0%200%200%200%200.160784%200%200%200%200%200.0588235%200%200%200%200%200.0313726%200%200%200%200.04%200'/%3e%3cfeBlend%20mode='normal'%20in2='BackgroundImageFix'%20result='effect1_dropShadow_12596_6540'/%3e%3cfeBlend%20mode='normal'%20in='SourceGraphic'%20in2='effect1_dropShadow_12596_6540'%20result='shape'/%3e%3c/filter%3e%3c/defs%3e%3c/svg%3e";let $=0,g=null;function G(e,t,o,n,a){g=o,document.getElementById(`${r}-wrapper`)&&E(!1),$=window.scrollY,document.body.classList.add("alma-fixed-body"),document.body.scroll(0,$);const i=N(),d=z(),m=Y(),s=X(),c=S(n),f=H(e,t,a);document.body.appendChild(i),i.appendChild(d),d.appendChild(m),d.appendChild(s),s.appendChild(c),s.appendChild(f),i.classList.add(`${r}-fadeIn`),s.classList.add(`${r}-slideIn`),f.focus()}function E(e=!0,t){const o=document.getElementById(`${r}-wrapper`);if(o&&(!e||confirm((g==null?void 0:g.closeModalMessage)??"Are you sure you want to leave the payment page?"))){const n=document.getElementById(`${r}-wrapper`),a=document.getElementById(`${r}-body`);n==null||n.classList.add(`${r}-fadeOut`),a==null||a.classList.add(`${r}-slideOut`),window.setTimeout(()=>{document.body.classList.remove("alma-fixed-body"),document.body.scroll(0,$),o.remove(),t&&t()},300)}}function N(){const e=document.createElement("style");e.innerHTML=j;const t=document.createElement("div");return t.id=`${r}-wrapper`,t.role="dialog",t.ariaModal="true",t.appendChild(e),t}function z(){const e=document.createElement("div");return e.id=`${r}-element`,e}function Y(){const e=document.createElement("div");return e.id=`${r}-background`,e}function X(){const e=document.createElement("div");return e.id=`${r}-body`,e}function H(e,t,o){const n=new URL(B(e,t,_.modal,o));n.searchParams.append("inPageVersion",C());const a=document.createElement("iframe");return a.id=`${r}-iframe`,a.setAttribute("allow","camera *; payment"),a.src=n.toString(),a.title="Alma payment iframe",a}function S(e){const t=document.createElement("img");return t.id=`${r}-close`,t.title=(g==null?void 0:g.closeModalButtonHover)||"Close the alma modal (you'll lose your data)",t.onclick=()=>E(!0,e),t.onkeyup=o=>{o.key==="Enter"&&E(!1,e)},t.src=V,t.tabIndex=0,t}function W(){const e=document.getElementById(`${r}-close`);e==null||e.remove()}function J(e,t,o){const{paymentId:n,onPaymentRejected:a,onPaymentSucceeded:i,onUserCloseModal:d}=e;I(t,{from:"in-page",type:"user_wants_to_pay"});const m={success:null,failure:null};O(n,t.getEnvironment()).then(s=>{m.success=s.success,m.failure=s.failure}),o("can_open_modal","start-payment",()=>{G(n,t.getEnvironment(),t.getTranslations(),d,t.getCheckoutUrl())}),o("3ds_started","start-payment",W),o("3ds_failed","start-payment",()=>{const s=document.getElementById(`${r}-body`);s&&s.appendChild(S(d))}),o("trigger_success_callback","start-payment",()=>{M(i,m.success)}),o("trigger_reject_callback","start-payment",()=>{M(a,m.failure)})}function M(e,t){E(!1),e?e():t&&window.location.assign(t)}function Z(e,t){const o=e.getEmbeddedSelector();if(E(!1),t(),!o)return;const n=v(o);e.setEmbeddedSelector(null),n&&n.firstChild&&n.removeChild(n.firstChild)}function q(e){const t=b(e.environment,e.checkoutUrl),{onInPageStatusChanged:o,unsubscribe:n}=U(t.getEnvironment(),e.selector,t.setTranslations,e.checkoutUrl);return o("embedded_loaded","initialize",()=>{t.setIsCheckoutLoaded(!0),R(t)}),o("ready_for_payment","initialize",()=>{var i;(i=e.onIntegratedPayButtonClicked)==null||i.call(e)}),D(e)&&t.setEmbeddedSelector(e.selector),{startPayment:i=>J(i,t,o),unmount:()=>Z(t,n)}}h.initialize=q,Object.defineProperty(h,Symbol.toStringTag,{value:"Module"})});
//# sourceMappingURL=index.umd.js.map
