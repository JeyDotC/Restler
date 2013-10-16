<?php

namespace Luracast\Restler\ClientSideConnector\Basic;

use Luracast\Restler\ClientSideConnector\iClientBuilder;

/**
 * Description of ClientBuilder
 *
 * @author jguevara
 */
class ClientBuilder implements iClientBuilder {

    public function build($service) {
        
    }

    public function buildRoot() {
        ob_start();
        $this->createFormatters();
        $this->xhrFix();
        ?>
        
        WebServiceClient = function(baseUrl){
            var self = this;
            this.baseUrl = baseUrl;
        };
        WebServiceClient.prototype.request = function(options){
            var self = this;
            var httpClient = new XMLHttpRequest();
            //Begin connection
            httpClient.open((options.method||"GET").toUpperCase(), this.baseUrl + options.uri, true);
            
            //Set data type
            var dataType = "type" in options ? options.type : "application/json";
            httpClient.setRequestHeader("Content-type", dataType);
            httpClient.setRequestHeader("Accept", dataType);
            
            //Setup headers
            if("headers" in options){
                for(var i in options.headers){
                    httpClient.setRequestHeader(i, options.headers[i]);
                }
            }
            
            httpClient.onreadystatechange=function() {
                if (self.httpClient.readyState==4) {
                    if(self.httpClient.status==200 && "success" in options){
                        options.success(Format.get(dataType).decode(httpClient.responseText));
                    }
                }
            }
            //Send request
            "body" in options ?
                httpClient.send(Format.get(dataType).encode(options.body)) : httpClient.send();
            
        };
        <?php
        return ob_get_clean();
    }
    
    protected function createFormatters() {
        ?>Format = {
            formatters:{},
            encode: function(data){},
            decode: function(data){},
            get: function(mime){ return Format.formatters[mime]; },
            register: function(mime, formatter){ Format.formatters[mime] = formatter; }
        };
        <?php
        $this->jsonFormatter();
        $this->xmlFormatter();
    }
    
    private function jsonFormatter() {
         ?>
        JsonFormat = function(){};
        JsonFormat.inheritsFrom(Format);
        JsonFormat.prototype.encode = function(object){
            return JSON.stringify(object);
        };
        JsonFormat.prototype.decode = function(json){
            return JSON.parse(json);
        };
        Format.register("application/json", new JsonFormat());
        <?php
    }
    
    private function xmlFormatter() {
        ?>
        XmlFormat = function(){};
        XmlFormat.inheritsFrom(Format);
        XmlFormat.prototype.encode = function(o){
            var tab = "    ";
            var toXml = function(v, name, ind) {
                var xml = "";
                if (v instanceof Array) {
                   for (var i=0, n=v.length; i<n; i++) xml += ind + toXml(v[i], name, ind+"\t") + "\n";
                } else if (typeof(v) == "object") {
                   var hasChild = false;
                   xml += ind + "<" + name;
                   for (var m in v) {
                      if (m.charAt(0) == "@")
                         xml += " " + m.substr(1) + "=\"" + v[m].toString() + "\"";
                      else
                         hasChild = true;
                   }
                   xml += hasChild ? ">" : "/>";
                   if (hasChild) {
                      for (var m in v) {
                         if (m == "#text")
                            xml += v[m];
                         else if (m == "#cdata")
                            xml += "<![CDATA[" + v[m] + "]]>";
                         else if (m.charAt(0) != "@")
                            xml += toXml(v[m], m, ind+"\t");
                      }
                      xml += (xml.charAt(xml.length-1)=="\n"?ind:"") + "</" + name + ">";
                   }
                } else {
                   xml += ind + "<" + name + ">" + v.toString() +  "</" + name + ">";
                }
                return xml;
            }, xml="";
            for (var m in o)
               xml += toXml(o[m], m, "");
            return tab ? xml.replace(/\t/g, tab) : xml.replace(/\t|\n/g, "");
        };
        XmlFormat.prototype.decode = function(xml){
            if (window.DOMParser) {
                parser=new DOMParser();
                xml = parser.parseFromString(text, "text/xml");
            } else {
                xml = new ActiveXObject("Microsoft.XMLDOM");
                xml.async=false;
                xml.loadXML(text); 
            } 
            var X = {
            toObj: function(xml) {
               var o = {};
               if (xml.nodeType==1) {   // element node ..
                  if (xml.attributes.length)   // element with attributes  ..
                     for (var i=0; i < xml.attributes.length; i++)
                        o["@"+xml.attributes[i].nodeName] = (xml.attributes[i].nodeValue||"").toString();
                  if (xml.firstChild) { // element has child nodes ..
                     var textChild=0, cdataChild=0, hasElementChild=false;
                     for (var n=xml.firstChild; n; n=n.nextSibling) {
                        if (n.nodeType==1) hasElementChild = true;
                        else if (n.nodeType==3 && n.nodeValue.match(/[^ \f\n\r\t\v]/)) textChild++; // non-whitespace text
                        else if (n.nodeType==4) cdataChild++; // cdata section node
                     }
                     if (hasElementChild) {
                        if (textChild < 2 && cdataChild < 2) { // structured element with evtl. a single text or/and cdata node ..
                           X.removeWhite(xml);
                           for (var n=xml.firstChild; n; n=n.nextSibling) {
                              if (n.nodeType == 3)  // text node
                                 o["#text"] = X.escape(n.nodeValue);
                              else if (n.nodeType == 4)  // cdata node
                                 o["#cdata"] = X.escape(n.nodeValue);
                              else if (o[n.nodeName]) {  // multiple occurence of element ..
                                 if (o[n.nodeName] instanceof Array)
                                    o[n.nodeName][o[n.nodeName].length] = X.toObj(n);
                                 else
                                    o[n.nodeName] = [o[n.nodeName], X.toObj(n)];
                              }
                              else  // first occurence of element..
                                 o[n.nodeName] = X.toObj(n);
                           }
                        }
                        else { // mixed content
                           if (!xml.attributes.length)
                              o = X.escape(X.innerXml(xml));
                           else
                              o["#text"] = X.escape(X.innerXml(xml));
                        }
                     }
                     else if (textChild) { // pure text
                        if (!xml.attributes.length)
                           o = X.escape(X.innerXml(xml));
                        else
                           o["#text"] = X.escape(X.innerXml(xml));
                     }
                     else if (cdataChild) { // cdata
                        if (cdataChild > 1)
                           o = X.escape(X.innerXml(xml));
                        else
                           for (var n=xml.firstChild; n; n=n.nextSibling)
                              o["#cdata"] = X.escape(n.nodeValue);
                     }
                  }
                  if (!xml.attributes.length && !xml.firstChild) o = null;
               }
               else if (xml.nodeType==9) { // document.node
                  o = X.toObj(xml.documentElement);
               }
               else
                  alert("unhandled node type: " + xml.nodeType);
               return o;
            },
            innerXml: function(node) {
               var s = ""
               if ("innerHTML" in node)
                  s = node.innerHTML;
               else {
                  var asXml = function(n) {
                     var s = "";
                     if (n.nodeType == 1) {
                        s += "<" + n.nodeName;
                        for (var i=0; i < n.attributes.length; i++)
                           s += " " + n.attributes[i].nodeName + "=\"" + (n.attributes[i].nodeValue||"").toString() + "\"";
                        if (n.firstChild) {
                           s += ">";
                           for (var c=n.firstChild; c; c=c.nextSibling)
                              s += asXml(c);
                           s += "</"+n.nodeName+">";
                        }
                        else
                           s += "/>";
                     }
                     else if (n.nodeType == 3)
                        s += n.nodeValue;
                     else if (n.nodeType == 4)
                        s += "<![CDATA[" + n.nodeValue + "]]>";
                     return s;
                  };
                  for (var c=node.firstChild; c; c=c.nextSibling)
                     s += asXml(c);
               }
               return s;
            },
            escape: function(txt) {
               return txt.replace(/[\\]/g, "\\\\")
                         .replace(/[\"]/g, '\\"')
                         .replace(/[\n]/g, '\\n')
                         .replace(/[\r]/g, '\\r');
            },
            removeWhite: function(e) {
               e.normalize();
               for (var n = e.firstChild; n; ) {
                  if (n.nodeType == 3) {  // text node
                     if (!n.nodeValue.match(/[^ \f\n\r\t\v]/)) { // pure whitespace text node
                        var nxt = n.nextSibling;
                        e.removeChild(n);
                        n = nxt;
                     }
                     else
                        n = n.nextSibling;
                  }
                  else if (n.nodeType == 1) {  // element node
                     X.removeWhite(n);
                     n = n.nextSibling;
                  }
                  else                      // any other node
                     n = n.nextSibling;
               }
               return e;
            }
         };
         if (xml.nodeType == 9) // document node
            xml = xml.documentElement;
         return X.toObj(X.removeWhite(xml));
      }
        Format.register("application/xml", new XmlFormat());
        <?php
    }


    private function xhrFix() {
        ?>//Fix to allow XMLHttpRequest object instantiation
        if(typeof window.XMLHttpRequest === 'undefined' &&
            typeof window.ActiveXObject === 'function') {
            window.XMLHttpRequest = function() {
                try { return new ActiveXObject('Msxml2.XMLHTTP.6.0'); } catch(e) {}
                try { return new ActiveXObject('Msxml2.XMLHTTP.3.0'); } catch(e) {}
                return new ActiveXObject('Microsoft.XMLHTTP');
            };
        }
        <?php
    }

}
