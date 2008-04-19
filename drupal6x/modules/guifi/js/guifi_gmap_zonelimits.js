var map = null;
var zm = 0; 
var latsgn = 1;
var lgsgn = 1;

if(Drupal.jsEnabled) {
	  $(document).ready(function(){
		xz();
	    }); 
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
      var minx = document.getElementById("edit-minx").value;
      var miny = document.getElementById("edit-miny").value;
      var maxx = document.getElementById("edit-maxx").value;
      var maxy = document.getElementById("edit-maxy").value;
        
      if (overlay) {
        map.removeOverlay(overlay);
        if (minx == point.x)
          document.getElementById("edit-minx").value='';
        if (miny == point.y)
          document.getElementById("edit-miny").value='';
        if (maxx == point.x)
          document.getElementById("edit-maxx").value='';
        if (maxy == point.y)
          document.getElementById("edit-maxy").value='';

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
       
        if ((minx < point.x) || (minx == '')) 
            document.getElementById("edit-minx").value = point.x;
        if ((miny < point.y) || (miny == '')) 
            document.getElementById("edit-miny").value = point.y;
        if ((maxx > point.x) || (maxx == '')) 
            document.getElementById("edit-maxx").value = point.x;
        if ((maxy > point.y) || (maxy == '')) 
            document.getElementById("edit-maxy").value = point.y;

         /*
        document.getElementById("edit-minx").value=point.y;
        document.getElementById("edit-miny").value=ls;
        document.getElementById("edit-maxx").value=lm;
        document.getElementById("edit-maxy").value=ld;

        document.getElementById("lonbox").value=point.x;
        document.getElementById("lonboxm").value=lgs;
        document.getElementById("lonboxmd").value=lgm;
        document.getElementById("lonboxms").value=lgd;
        */
      }
    });
    map.setCenter(new GLatLng(20.0, -10.0), 2);
    map.setMapType(G_NORMAL_MAP);
  }
}

function getxh(){return xh;}

