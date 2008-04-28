var map = null;

if(Drupal.jsEnabled) {
	  $(document).ready(function(){
		xz();
	    }); 
	}
	
var icon_start;
var oNode;
var newNode;
//var marker;
//var point;
var lat2;
var lon2;
var marker;
var point;
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
    icon_start.image = '/modules/guifi/js/marker_start.png';
    icon_start.shadow = '';
    icon_start.iconSize = new GSize(32, 32);
    icon_start.shadowSize = new GSize(6, 20);
    icon_start.iconAnchor = new GPoint(6, 20);
    icon_start.dragCrossImage = '';
    
	 var layer1 = new GWMSTileLayer(map, new GCopyrightCollection("guifi.net"),1,17);
    layer1.baseURL=document.getElementById("guifi-wms").value;
    layer1.layers="Nodes,Links";
    layer1.mercZoomLevel = 0;
    layer1.opacity = 0.65;

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

function initialPosition(point) {
  map.clearOverlays();
  var dNode = new GMarker(point);
  document.getElementById("profile").src =
    "http://www.heywhatsthat.com/bin/profile.cgi?"+
    "axes=1&curvature=0&metric=1&"+
    "pt0="+document.getElementById("lat").value+","+document.getElementById("lon").value+
    ",ff0000"+
    "&pt1="+point.y+","+point.x+
    ",00c000";   
  pLine = new GPolyline([newNode,point],"#ff0000", 5);
  map.addOverlay(dNode);
  map.addOverlay(pLine);   
  map.addOverlay(oNode);
}



