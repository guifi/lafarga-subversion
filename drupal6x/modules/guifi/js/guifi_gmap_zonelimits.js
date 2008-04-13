var map = null;
var zm = 0; 
var latsgn = 1;
var lgsgn = 1;

if (isJsEnabled()) {
  addLoadEvent(xz);
} 


function xz() 
{
  if (GBrowserIsCompatible()) {
    map=new GMap2(document.getElementById("map"));
    map.addControl(new GLargeMapControl());
    map.addControl(new GScaleControl()) ;
    map.enableScrollWheelZoom();
    map.addControl(new GOverviewMapControl());
    GEvent.addListener(map, 'click', function(overlay,point) {
      if (overlay) {
        map.removeOverlay(overlay);
        document.getElementById("latbox").value='';
        document.getElementById("latboxm").value='';
        document.getElementById("latboxmd").value='';
        document.getElementById("latboxms").value='';

        document.getElementById("lonbox").value='';
        document.getElementById("lonboxm").value='';
        document.getElementById("lonboxmd").value='';
        document.getElementById("lonboxms").value='';
      } else {
        if (zm == 0) {
                map.setCenter(point,7);
                zm = 1;
        } else {
                map.setCenter(point);
        }

        var latA = Math.abs(Math.round(value=point.y * 1000000.));
        var lonA = Math.abs(Math.round(value=point.x * 1000000.));

        var html = "";
        html += html + "Click point latitude - longitude... " + point;

        var marker = new GMarker(point, {title: html});
        map.addOverlay(marker);

        if(value=point.y < 0) {
                var ls = '-' + Math.floor((latA / 1000000));
        } else {
                var ls = Math.floor((latA / 1000000));
        }

        var lm = Math.floor(((latA/1000000) - Math.floor(latA/1000000)) * 60);
        var ld = ( Math.floor(((((latA/1000000) - Math.floor(latA/1000000)) * 60) - Math.floor(((latA/1000000) - Math.floor(latA/1000000)) * 60)) * 100000) *60/100000 );

        if(value=point.x < 0) {
          var lgs = '-' + Math.floor((lonA / 1000000));
        } else {
          var lgs = Math.floor((lonA / 1000000));
        }

        var lgm = Math.floor(((lonA/1000000) - Math.floor(lonA/1000000)) * 60);
        var lgd = ( Math.floor(((((lonA/1000000) - Math.floor(lonA/1000000)) * 60) - Math.floor(((lonA/1000000) - Math.floor(lonA/1000000)) * 60)) * 100000) *60/100000 );

        document.getElementById("latbox").value=point.y;
        document.getElementById("latboxm").value=ls;
        document.getElementById("latboxmd").value=lm;
        document.getElementById("latboxms").value=ld;

        document.getElementById("lonbox").value=point.x;
        document.getElementById("lonboxm").value=lgs;
        document.getElementById("lonboxmd").value=lgm;
        document.getElementById("lonboxms").value=lgd;
      }
    });
    map.setCenter(new GLatLng(20.0, -10.0), 2);
    map.setMapType(G_NORMAL_MAP);
  }
}

function getxh(){return xh;}

