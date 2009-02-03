var map = null;
var oGMark = null; //GMarker

if(Drupal.jsEnabled) {
	  $(document).ready(function(){
		xz();
	    }); 
	}
	
var icon_start;
var oNode;
var newNode; //initial point
var lat2;
var lon2;
var marker;
var point; //end point
var pLine;

function xz() 
{
  if (GBrowserIsCompatible()) {
    map=new GMap2(document.getElementById("map"));
    if (map.getSize().height >= 300)
      map.addControl(new GLargeMapControl());
    else
      map.addControl(new GSmallMapControl());
    if (map.getSize().width >= 500) {
      map.addControl(new GScaleControl()) ;
      map.addControl(new GOverviewMapControl());
  	   map.addControl(new GMapTypeControl());
    }
    map.enableScrollWheelZoom();
    
    icon_start = new GIcon();
    icon_start.image = document.getElementById("edit-jspath").value+'marker_start.png';
    icon_start.shadow = '';
    icon_start.iconSize = new GSize(32, 32);
    icon_start.shadowSize = new GSize(6, 20);
    icon_start.iconAnchor = new GPoint(6, 20);
    icon_start.dragCrossImage = '';
    
	 var layer1 = new GWMSTileLayer(map, new GCopyrightCollection("guifi.net"),1,17);
    layer1.baseURL=document.getElementById("guifi-wms").value;
    layer1.layers="Nodes,Links";
    layer1.mercZoomLevel = 0;
    layer1.opacity = 1.0;

    var myMapTypeLayers=[G_SATELLITE_MAP.getTileLayers()[0],layer1];
    var myCustomMapType = new GMapType(myMapTypeLayers, 
    		G_NORMAL_MAP.getProjection(), "guifi.net", G_SATELLITE_MAP);

    map.addMapType(myCustomMapType);
    
    newNode = new GLatLng(document.getElementById("lat").value, 
			 document.getElementById("lon").value);
    
    map.setCenter(newNode, 13);
    
    
    GEvent.addListener(map, "click", function(marker, point) {
      initialPosition(point);
    });

    oNode = new GMarker(newNode,{icon: icon_start});
    map.addOverlay(oNode);
    map.setMapType(myCustomMapType);

    if (document.getElementById("lon2").value != "NA") {
      lon2 = document.getElementById("lon2").value;      
      if (document.getElementById("lat2").value != "NA") {
        lat2 = document.getElementById("lat2").value;
      }
      point = new GLatLng(lat2,lon2);
      initialPosition(point);
      var bounds = new GLatLngBounds();
      bounds = pLine.getBounds();
      map.setCenter(bounds.getCenter(),map.getBoundsZoomLevel(bounds)); 
	 }
	
    
  }
}

function initialPosition(ppoint) {
  map.clearOverlays();
  point=ppoint;  //save point in global var
  oGMark = null;  //init global var
  var dNode = new GMarker(point);
  document.getElementById("profile").src =
    "http://www.heywhatsthat.com/bin/profile.cgi?"+
    "axes=1&curvature=1&metric=1&groundrelative=1&"+
    "src=guifi.net&"+
    "pt0="+document.getElementById("lat").value+","+document.getElementById("lon").value+
    ",ff0000,9"+
    "&pt1="+point.y+","+point.x+
    ",00c000,9";   
  pLine = new GPolyline([newNode,point],"#ff0000", 5);
  map.addOverlay(dNode);
  map.addOverlay(pLine);   
  map.addOverlay(oNode);
  document.getElementById('tdistance').innerHTML=Math.round(GCDistance_js(newNode.y,newNode.x,point.y,point.x)*1000)/1000;
  document.getElementById('tazimut').innerHTML=Math.round(GCAzimuth_js(newNode.y,newNode.x,point.y,point.x)*100)/100;
}
function profileclick(event){
    var oProfile=document.getElementById("profile");
    var pointClic=coord_relativ(event,oProfile);
    //var nLat=parseFloat(document.getElementById("lat").value);
    //var nLon=parseFloat(document.getElementById("lon").value);
    var nLat=newNode.y;
    var nLon=newNode.x;
    var nLat2=point.y;
    var nLon2=point.x;
    var nDistance = GCDistance_js(nLat,nLon,nLat2,nLon2);
    var nNewDistance=(pointClic.x-29)*nDistance/(oProfile.width-29);
    var nAzimut=GCAzimuth_js(nLat,nLon,nLat2,nLon2)
    //alert('Distancia:'+nNewDistance+'  Azimut:'+nAzimut);
    var pointNew=getDestPoint(nLat,nLon,nNewDistance,nAzimut);
    //alert(' inici:'+nLat+'  '+nLon+'    newpoint:'+pointNew.lat+'  '+pointNew.lon+'   final:'+nLat2+'  '+nLon2);
    var pointGMark=new GLatLng(pointNew.lat,pointNew.lon);
    if(oGMark!=null){
        map.removeOverlay(oGMark);
    };
    oGMark=new GMarker(pointGMark);
    map.addOverlay(oGMark);
}

/*
 * Calcula la coordenada relativa de un clic respecte a les coordenades del contenidor
 */
function coord_relativ(event,oProfile){
    if (window.ActiveXObject) {  //for ie
        pos_x = event.offsetX;
        pos_y = event.offsetY;
    } else { //for Firefox
        var top = 0, left = 0;
        var elm = oProfile;
        while (elm) {
            left += elm.offsetLeft;
            top += elm.offsetTop;
            elm = elm.offsetParent;
        }
        pos_x = event.pageX - left;
        pos_y = event.pageY - top;
    }
    return {x:pos_x,y:pos_y}
}

/*
 * Movable Type Scripts
 * calculate destination point given start point, initial bearing (deg) and distance (km)
 * see http://williams.best.vwh.net/avform.htm#LL
 * original modified
 */
function getDestPoint(lat,lon,d,brng) {
  var DE2RA = 0.01745329252;
  var RA2DE = 57.2957795129;
  var R = 6371; // earth's mean radius in km
  var lat1 = lat * DE2RA;
  var lon1 = lon * DE2RA;
  brng = brng * DE2RA;
  var lat2 = Math.asin( Math.sin(lat1)*Math.cos(d/R) + 
                        Math.cos(lat1)*Math.sin(d/R)*Math.cos(brng) );
  var lon2 = lon1 + Math.atan2(Math.sin(brng)*Math.sin(d/R)*Math.cos(lat1), 
                               Math.cos(d/R)-Math.sin(lat1)*Math.sin(lat2));
  lon2 = (lon2+Math.PI)%(2*Math.PI) - Math.PI;  // normalise to -180...+180
  if (isNaN(lat2) || isNaN(lon2)) return null;
  lat2 *= RA2DE;
  lon2 *= RA2DE;
  return {lat:lat2,lon:lon2}
}

/*
 * GeoCalc
 * funcio de php pasada a javascript
 */
function GCDistance_js(pLat1, pLon1, pLat2, pLon2) {  
    var DE2RA = 0.01745329252;
    var AVG_ERAD = 6371.0;
    var nLat1 = pLat1 * DE2RA;
    var nLon1 = pLon1 * DE2RA;
    var nLat2 = pLat2 * DE2RA;
    var nLon2 = pLon2 * DE2RA;
    var d = Math.sin(nLat1)*Math.sin(nLat2) + Math.cos(nLat1)*Math.cos(nLat2)*Math.cos(nLon1 - nLon2);
    return (AVG_ERAD * Math.acos(d));
}

/*
 * GeoCalc
 * funcio de php pasada a javascript
 */
function GCAzimuth_js(plat1, plon1, plat2, plon2) {  //GeoCalc
    var DE2RA = 0.01745329252;
    var RA2DE = 57.2957795129;
    var result = 0.0;
    var ilat1 = Math.floor(0.50 + plat1 * 360000.0);
    var ilat2 = Math.floor(0.50 + plat2 * 360000.0);
    var ilon1 = Math.floor(0.50 + plon1 * 360000.0);
    var ilon2 = Math.floor(0.50 + plon2 * 360000.0);

    var lat1 = plat1 * DE2RA;
    var lon1 = plon1 * DE2RA;
    var lat2 = plat2 * DE2RA;
    var lon2 = plon2 * DE2RA;

    if ((ilat1 == ilat2) && (ilon1 == ilon2)) {
      return result;
    }
    else if (ilat1 == ilat2) {
      if (ilon1 > ilon2)
        result = 90.0;
      else
        result = 270.0;
    }
    else if (ilon1 == ilon2) {
      if (ilat1 > ilat2)
        result = 180.0;
    }
    else {
      var c = Math.acos(Math.sin(lat2)*Math.sin(lat1) + Math.cos(lat2)*Math.cos(lat1)*Math.cos((lon2-lon1)));
      var A = Math.asin(Math.cos(lat2)*Math.sin((lon2-lon1))/Math.sin(c));
      result = (A * RA2DE);


      if ((ilat2 > ilat1) && (ilon2 > ilon1)) {
        result = result;
      }
      else if ((ilat2 < ilat1) && (ilon2 < ilon1)) {
        result = 180.0 - result;
      }
      else if ((ilat2 < ilat1) && (ilon2 > ilon1)) {
        result = 180.0 - result;
      }
      else if ((ilat2 > ilat1) && (ilon2 < ilon1)) {
        result += 360.0;
      }
    }

    return result;
}


