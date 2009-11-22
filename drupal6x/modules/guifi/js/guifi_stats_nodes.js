/*
 * Created on 23/10/2009 by Eduard
 *
 * functions for stats nodes
 */


function guifi_stats_chart01(){   //growth_chart
      document.getElementById("plot").innerHTML='<img src="/guifi/stats/chart01/0">';
}

function guifi_stats_chart02(){   //annualincrement_chart
       document.getElementById("plot").innerHTML='<img src="/guifi/stats/chart02/0">';
}

function guifi_stats_chart03(){   //monthlyaverage_chart
       document.getElementById("plot").innerHTML='<img src="/guifi/stats/chart03/0">';
}

function guifi_stats_chart04(){    //lastyear_chart
       document.getElementById("plot").innerHTML='<img src="/guifi/stats/chart04/0">';
}

function guifi_stats_chart05(nmonths){    //Nodes per month, average of 6 months
       document.getElementById("plot").innerHTML='<img src="/guifi/stats/chart05/'+nmonths+'">';
}

