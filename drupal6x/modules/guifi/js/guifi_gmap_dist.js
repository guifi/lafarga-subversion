var map = null;

if(Drupal.jsEnabled) {
	  $(document).ready(function(){
		xz();
	    }); 
	}

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
    
	 var layer1 = new GWMSTileLayer(map, new GCopyrightCollection("guifi.net"),1,17);
    layer1.baseURL=document.getElementById("guifi-wms").value;
    layer1.layers="Nodes,Links";
    layer1.mercZoomLevel = 0;
    layer1.opacity = 0.65;

    var myMapTypeLayers=[G_SATELLITE_MAP.getTileLayers()[0],layer1];
    var myCustomMapType = new GMapType(myMapTypeLayers, 
    		G_NORMAL_MAP.getProjection(), "guifi.net", G_SATELLITE_MAP);

    map.addMapType(myCustomMapType);	
	
    var newNode = new GLatLng(document.getElementById("lat").value, 
			 document.getElementById("lon").value);
    
    map.setCenter(newNode, 13);
    
    GEvent.addListener(map, "click", function(marker, point) {
           var bounds = new GLatLngBounds();
//           map.removeOverlay(pLine);
//           map.removeOverlay(marcador);
        
   	     var dNode = new GMarker(point);
//   	     GEvent.addListener(marcador, 'dragend', function() { updatepLine() ; }) ;

  	          
   		  map.addOverlay(dNode);
/*  	        dNode.openInfoWindowHtml("<div height: 160; width: 800>"+
  	          "<img src=http://www.heywhatsthat.com/bin/profile.cgi?"+
  	          "axes=1&curvature=1&metric=1&"+
  	          "pt0="+document.getElementById("lat").value+","+document.getElementById("lon").value+
  	          ",ff0000"+
  	          "&pt1="+point.y+","+point.x+
  	          ",00c000></div>"); */
  	        document.getElementById("profile").src =
  	          "http://www.heywhatsthat.com/bin/profile.cgi?"+
  	          "axes=1&curvature=1&metric=1&"+
  	          "pt0="+document.getElementById("lat").value+","+document.getElementById("lon").value+
  	          ",ff0000"+
  	          "&pt1="+point.y+","+point.x+
  	          ",00c000";   
           var pLine = new GPolyline([newNode,point],"#ff0000", 10);
   		  map.addOverlay(pLine);
//   	     map.setCenter(point);
   	     
//   	     bounds.extend(point);
//           bounds.extend(newNode);
//           map.setZoom(map.getBoundsZoomLevel(bounds));
           
 // map.setCenter(new GLatLng(x,y),Math.abs(90/x));
  
  map.setCenter(new GLatLng(x,y));
    });

    var oNode = new GMarker(newNode);
    map.addOverlay(oNode);
    map.setMapType(myCustomMapType);
    
  }
}

function updatePolyline()
{
 var bounds = new GLatLngBounds();
	
 if (pLine)
 {
  map.removeOverlay(pLine);
 }

 // Check for moved center...

 if ( marker_move.getPoint() != marker_move.savePoint )
 {
  var x = marker_move.getPoint().lat() - marker_move.savePoint.lat() ;
  var y = marker_move.getPoint().lng() - marker_move.savePoint.lng() ;
  marker_SW.setPoint( new GLatLng( marker_SW.getPoint().lat() + x, marker_SW.getPoint().lng() + y) ) ;
  marker_NE.setPoint( new GLatLng( marker_NE.getPoint().lat() + x, marker_NE.getPoint().lng() + y) ) ;

 } else						// Center not moved so move center
 {
  var x = (marker_SW.getPoint().lat() + marker_NE.getPoint().lat()) / 2 ;
  var y = (marker_NE.getPoint().lng() + marker_SW.getPoint().lng()) / 2 ;
  marker_move.setPoint( new GLatLng(x,y) ) ;
 // map.setCenter(new GLatLng(x,y),Math.abs(90/x));
  
  map.setCenter(new GLatLng(x,y));
 }

 marker_move.savePoint = marker_move.getPoint() ;			// Save for later

 var points = [
      marker_NE.getPoint(),
      new GLatLng(marker_SW.getPoint().lat(), marker_NE.getPoint().lng()),
      marker_SW.getPoint(),
      new GLatLng(marker_NE.getPoint().lat(), marker_SW.getPoint().lng()),
      marker_NE.getPoint()];
 border = new GPolyline(points, "#66000");
 
 document.getElementById("edit-miny").value = marker_SW.getPoint().lat();
 document.getElementById("edit-minx").value = marker_SW.getPoint().lng();
 document.getElementById("edit-maxy").value = marker_NE.getPoint().lat();
 document.getElementById("edit-maxx").value = marker_NE.getPoint().lng();

 map.addOverlay(border);
 bounds.extend(marker_SW.getPoint());
 bounds.extend(marker_NE.getPoint());
 map.setZoom(map.getBoundsZoomLevel(bounds)); 
 
// map.setCenter(new GLatLng(20.0, -10.0), 2)

}




