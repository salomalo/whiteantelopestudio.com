/*
 * arrange.js - Drag-drop custom category order
 * Copyright ?? 2008-2010 by Ingenesis Limited
 * Licensed under the GPLv3 {@see license.txt}
 */
jQuery(document).ready(function(){var a=jqnc();a.fn.dragRelatedRows=function(b){var e=a(this),c=e.is("table")?e.find("tbody tr"):e,f=typeof a(document).attr("onselectstart")!="undefined",d={onDrop:function(){}},b=a.extend(d,b);return c.unbind("mousedown.dragrow").bind("mousedown.dragrow",function(n){var m=a(this),i=m.attr("rel"),j=m.attr("class").replace(" alternate","").split(" "),l=j[j.length-1]=="top"?(j.pop()):false,h=j[j.length-1].substr(-6)=="-child"?(j.pop()):l,g=j.slice(0,-1),k=m.add("tr."+i,m.parent());if(a(n.target).is("input,button")||!m.attr("class")){return true}k.not(":hidden").fadeTo("fast",0.4);lastY=n.pageY;a("tr",k.parent()).not(k).bind("mouseenter.dragrow",function(t){var u=a(this),q=u.attr("class").replace(" alternate","").split(" "),s=q[q.length-1]=="top"?(q.pop()):false,p=q[q.length-1].substr(-6)=="-child"?(q.pop()):s,o=q.slice(0,-1),r=o[o.length-1],v=a("tr."+u.attr("rel")+":last",k.parent());if(o.toString()!=g.toString()){return lastY=t.pageY}if(t.pageY>lastY){v.after(k)}else{u.before(k)}lastY=t.pageY});a("body").bind("mouseup.dragrow",function(){k.not(":hidden").fadeTo("fast",1);a("tr",k.parent()).unbind("mouseenter.dragrow");a("body").unbind("mouseup.dragrow");if(f){a(document).unbind("selectstart")}b.onDrop(k)});n.preventDefault();if(f){a(document).bind("selectstart",function(){return false})}return false}).css("cursor","row-resize")};a.fn.arrangeRow=function(q){if(!a(this).is("tr")){return false}if(!a(this).parent().is("tbody")){return false}var d=a(this),c,l,i,r,j,b,m,k,e,p,f,g,s;d.dragRelatedRows({onDrop:h});function o(t){if(t.is("tr")){c=t;cell=false}else{cell=t.parent();c=t.parent().parent()}l=c.find("input[name=id]").val();s=c.find("input[name^=position]").val();i=c.attr("rel");r=c.attr("class").replace(" alternate","").split(" ");j=r[r.length-1]=="top"?(r.pop()):false;b=r[r.length-1].substr(-6)=="-child"?(r.pop()):j;m=c.parent().find("tr."+b);k=m.filter(":last");e=m.not(c);p=r.slice(0,-1);f=p[p.length-1]?p[p.length-1]:j;g=c.parent().find("tr[rel="+f+"]")}d.find("button[name=top]").hover(function(){a(this).toggleClass("hover")}).click(function(){o(a(this));if(s==1){return false}c.parent().find("tr."+i).not(c).remove();c.find("button.collapsing").addClass("closed").css("background-position","-180px top");if(!g.size()){c.insertBefore(c.parent().find("tr:first"))}else{c.insertAfter(g)}h(c)});d.find("button[name=bottom]").hover(function(){a(this).toggleClass("hover")}).click(function(){o(a(this));if(s==m.size()){return false}c.find("button.collapsing").addClass("closed").css("background-position","-180px top");c.parent().find("tr."+i).not(c).remove();c.insertAfter(k);h(c)});d.find("button.collapsing").click(function(y){var t=a(this),z=false,v=false,w=20,A=new Number(t.css("background-position").replace("%","").replace("px","").split(" ").shift()),B=180;o(t);if(t.hasClass("closed")){openedButton=e.find("button.collapsing:not(button.closed)");opened=openedButton.parent().parent();if(opened.size()>0){z=opened.attr("rel");v=opened.parent().find("tr."+z).not("tr[rel="+z+"]").remove();e.find("button.collapsing:not(button.closed)");openedButton.addClass("closed").css("background-position","-180px top")}t.bind("closed",function(){t.removeClass("closed")});function x(){A+=w;t.css("background-position",A+"px top");if(A<0){setTimeout(x,20)}else{t.trigger("closed")}}cell.addClass("updating");a.ajax({url:loadchildren_url+"&action=shopp_category_children&parent="+l,timeout:5000,dataType:"json",success:function(C){cell.removeClass("updating");if(C.length==0){t.remove()}else{if(C instanceof Object){a.each(C,function(){new ProductCategory(this,c)})}else{t.addClass("closed").css("background-position","-180px top")}}},error:function(D,C){cell.removeClass("updating");t.addClass("closed").css("background-position","-180px top");alert(LOAD_ERROR+" ("+C+")")}});return setTimeout(x,20)}t.bind("opened",function(){c.parent().find("tr."+i).not(c).fadeOut("fast").remove();t.addClass("closed")});function u(){A-=w;t.css("background-position",A+"px top");if(Math.abs(A)<B){setTimeout(u,20)}else{t.trigger("opened")}}return setTimeout(u,20)}).hover(function(){a(this).toggleClass("hover")}).css("background-position","-180px top");function h(w){o(w);var t=m.find("input[name^=position]"),v=false,u;t.each(function(y,x){a(x).val(y+1)});v=t.serialize();if(!g.size()){u=w.find("button.collapsing").parent().addClass("updating")}else{u=g.find("button.collapsing").parent().addClass("updating")}a.ajax({url:updates_url+"&action=shopp_category_order",timeout:7000,type:"POST",datatype:"text",data:v,success:function(){u.removeClass("updating")},error:function(y,x){u.removeClass("updating");alert(SAVE_ERROR+" ("+x+")")}})}function n(x,w){var u='<span class="indent">&nbsp;</span>',v=x.uri.split("/"),t="",y=a("tr."+w.attr("rel")+":last",w.parent());v.push(v[v.length-2]+"-child");return a('<tr class="'+v.join(" ")+'" rel="'+x.slug+'"><td>'+u.repeat(v.length-1)+'<button type="button" name="top" class="moveto top">&nbsp;</button><button type="button" name="bottom" class="moveto bottom">&nbsp;</button><a class="row-title" href="'+t+'" title="&quot;'+x.name+'&quot;">'+x.name+'</a><input type="hidden" name="id" value="'+x.id+'" /><input type="hidden" name="position['+x.id+']" value="'+x.priority+'" /></td><th scope="row" width="48"><button type="button" name="collapse" class="collapsing closed">&nbsp;</button></th></tr>').insertAfter(y).arrangeRow()}String.prototype.repeat=function(v){var t="",u=1;for(u=1;u<v;u++){t+=this}return t}};a("#arrange-categories tbody tr").arrangeRow()});