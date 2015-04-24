// http://cmlenz.github.io/jquery-iframe-transport/
(function(e,t){"use strict";e.ajaxPrefilter(function(e,t,n){if(e.iframe){e.originalURL=e.url;return"iframe"}});e.ajaxTransport("iframe",function(t,n,r){function l(){u.each(function(t,n){var r=e(n);r.data("clone").replaceWith(r)});i.remove();s.one("load",function(){s.remove()});s.attr("src","javascript:false;")}var i=null,s=null,o="iframe-"+e.now(),u=e(t.files).filter(":file:enabled"),a=null,f=null;t.dataTypes.shift();t.data=n.data;if(u.length){i=e("<form enctype='multipart/form-data' method='post'></form>").hide().attr({action:t.originalURL,target:o});if(typeof t.data==="string"&&t.data.length>0){e.error("data must not be serialized")}e.each(t.data||{},function(t,n){if(e.isPlainObject(n)){t=n.name;n=n.value}e("<input type='hidden' />").attr({name:t,value:n}).appendTo(i)});e("<input type='hidden' value='IFrame' name='X-Requested-With' />").appendTo(i);if(t.dataTypes[0]&&t.accepts[t.dataTypes[0]]){f=t.accepts[t.dataTypes[0]]+(t.dataTypes[0]!=="*"?", */*; q=0.01":"")}else{f=t.accepts["*"]}e("<input type='hidden' name='X-HTTP-Accept'>").attr("value",f).appendTo(i);a=u.after(function(t){var n=e(this),r=n.clone().prop("disabled",true);n.data("clone",r);return r}).next();u.appendTo(i);return{send:function(t,n){s=e("<iframe src='javascript:false;' name='"+o+"' id='"+o+"' style='display:none'></iframe>");s.one("load",function(){s.one("load",function(){var e=this.contentWindow?this.contentWindow.document:this.contentDocument?this.contentDocument:this.document,t=e.documentElement?e.documentElement:e.body,r=t.getElementsByTagName("textarea")[0],i=r&&r.getAttribute("data-type")||null,s=r&&r.getAttribute("data-status")||200,o=r&&r.getAttribute("data-statusText")||"OK",u={html:t.innerHTML,text:i?r.value:t?t.textContent||t.innerText:null};l();n(s,o,u,i?"Content-Type: "+i:null)});i[0].submit()});e("body").append(i,s)},abort:function(){if(s!==null){s.unbind("load").attr("src","javascript:false;");l()}}}}})})(jQuery)