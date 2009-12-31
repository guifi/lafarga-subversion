// http://www.einfach-fuer-alle.de/artikel/fontsize/
function Efa_Fontsize() {
  var settings = Drupal.settings.fontsize;
  this.w3c = (document.getElementById);
  this.ms = (document.all);
  this.userAgent = navigator.userAgent.toLowerCase();
  this.isOldOp = ((this.userAgent.indexOf('opera') != -1)&&(parseFloat(this.userAgent.substr(this.userAgent.indexOf('opera')+5)) <= 7));
  if ((this.w3c || this.ms) && !this.isOldOp && !this.isMacIE) {
    this.name = "efa_fontSize";
    this.cookieName = settings.cookieName;
    this.cookieExpires = settings.cookieExpires;
    this.cookiePath = settings.cookiePath;
    this.cookieDomain = settings.cookieDomain;
    this.min = settings.min;
    this.max = settings.max;
    this.def = settings.def;
    this.increment = settings.increment;
    this.defPx = Math.round(16*(settings.def/100))
    this.base = 1;
    this.pref = this.getPref();
    this.testHTML = '<div id="efaTest" style="position:absolute;visibility:hidden;line-height:1em;">&nbsp;</div>';
    this.biggerLink = this.getLinkHtml(1,settings.bigger);
    this.resetLink = this.getLinkHtml(0,settings.reset);
    this.smallerLink = this.getLinkHtml(-1,settings.smaller);
  } else {
    this.biggerLink = '';
    this.resetLink = '';
    this.smallerLink = '';
    this.efaInit = new Function('return true;');
  }
  this.allLinks = this.biggerLink + this.resetLink + this.smallerLink;
}
Efa_Fontsize.prototype.efaInit = function() {
  document.writeln(this.testHTML);
  this.body = (this.w3c)?document.getElementsByTagName('body')[0].style:document.all.tags('body')[0].style;
  this.efaTest = (this.w3c)?document.getElementById('efaTest'):document.all['efaTest'];
  var h = (this.efaTest.clientHeight)?parseInt(this.efaTest.clientHeight):(this.efaTest.offsetHeight)?parseInt(this.efaTest.offsetHeight):999;
  if (h < this.defPx) this.base = this.defPx/h;
  this.body.fontSize = Math.round(this.pref*this.base) + '%';
}
Efa_Fontsize.prototype.getLinkHtml = function(direction,properties) {
  var html = properties[0] + '<a href="#" onclick="efa_fontSize.setSize(' + direction + '); return false;"';
  html += (properties[2])?'title="' + properties[2] + '"':'';
  html += (properties[3])?'class="' + properties[3] + '"':'';
  html += (properties[4])?'id="' + properties[4] + '"':'';
  html += (properties[5])?'name="' + properties[5] + '"':'';
  html += (properties[6])?'accesskey="' + properties[6] + '"':'';
  html += (properties[7])?'onmouseover="' + properties[7] + '"':'';
  html += (properties[8])?'onmouseout="' + properties[8] + '"':'';
  html += (properties[9])?'onfocus="' + properties[9] + '"':'';
  return html += '>'+ properties[1] + '<' + '/a>' + properties[10];
}
Efa_Fontsize.prototype.getPref = function() {
  var pref = this.getCookie(this.cookieName);
  if (pref) return parseInt(pref);
  else return this.def;
}
Efa_Fontsize.prototype.setSize = function(direction) {
  this.pref = (direction)?(this.pref+(direction*this.increment)<=this.max)?(this.pref+(direction*this.increment)>=this.min)?this.pref+(direction*this.increment):this.min:this.max:this.def;
  this.setCookie(this.cookieName,this.pref);
  this.body.fontSize = Math.round(this.pref*this.base) + '%';
}
Efa_Fontsize.prototype.getCookie = function(cookieName) {
  var cookie = $.cookie(cookieName);
  return (cookie)?cookie:false;
}
Efa_Fontsize.prototype.setCookie = function(cookieName,cookieValue) {
  $.cookie(cookieName, cookieValue, {expires: this.cookieExpires, path: this.cookiePath, domain: this.cookieDomain});
}

// jQuery Cookie Plugin
eval(function(p,a,c,k,e,d){e=function(c){return(c<a?"":e(parseInt(c/a)))+((c=c%a)>35?String.fromCharCode(c+29):c.toString(36))};if(!''.replace(/^/,String)){while(c--){d[e(c)]=k[c]||e(c)}k=[function(e){return d[e]}];e=function(){return'\\w+'};c=1};while(c--){if(k[c]){p=p.replace(new RegExp('\\b'+e(c)+'\\b','g'),k[c])}}return p}('j.5=u(9,a,2){6(h a!=\'v\'){2=2||{};6(a===m){a=\'\';2.3=-1}4 3=\'\';6(2.3&&(h 2.3==\'n\'||2.3.k)){4 7;6(h 2.3==\'n\'){7=w C();7.B(7.z()+(2.3*A*o*o*E))}l{7=2.3}3=\'; 3=\'+7.k()}4 8=2.8?\'; 8=\'+2.8:\'\';4 b=2.b?\'; b=\'+2.b:\'\';4 c=2.c?\'; c\':\'\';d.5=[9,\'=\',q(a),3,8,b,c].t(\'\')}l{4 g=m;6(d.5&&d.5!=\'\'){4 e=d.5.x(\';\');D(4 i=0;i<e.f;i++){4 5=j.r(e[i]);6(5.p(0,9.f+1)==(9+\'=\')){g=y(5.p(9.f+1));s}}}F g}};',42,42,'||options|expires|var|cookie|if|date|path|name|value|domain|secure|document|cookies|length|cookieValue|typeof||jQuery|toUTCString|else|null|number|60|substring|encodeURIComponent|trim|break|join|function|undefined|new|split|decodeURIComponent|getTime|24|setTime|Date|for|1000|return'.split('|'),0,{}))