#!/bin/sh

# Control de LEDs de la nano2 per indicar la qualitat de Link
# joan.llopart +A+ guifi.net
#
# minimod de la feina del Xavi Martinez :-)


GP=/usr/bin/gpioctl

# Activem els LEDs
$GP dirout 0
$GP dirout 1
$GP dirout 3
$GP dirout 4
$GP clear 0
$GP clear 1
$GP clear 3
$GP clear 4

L1=0
L2=0
L3=0
L4=0

#Iniciem el loop

while [ 1 ] 
do

# Pillem la qualitat de l'enllac
 QUAL=`awk '/ath0/ {print $3}' /proc/net/wireless`
#Li traiem el punt final
 QUAL=${QUAL%.*}
# Inicialment, tots a 0
 L1T=0
 L2T=0
 L3T=0
 L4T=0
# Comprobem un a un
 if [ $QUAL != 0 ]
 then
  L1T=1
  if [ $QUAL -gt 15 ]
  then
   L2T=1
  fi
  if [ $QUAL -gt 30 ]
  then
   L3T=1
  fi
  if [ $QUAL -gt 45 ]
  then
   L4T=1
  fi
 fi # $QUAL!=0

# Encenem/apaguem LED nomes si hi ha canvi
 if [ $L1 -ne $L1T ]
 then
  if [ $L1T ]
  then
	$GP set 0
  else
	$GP clear 0
  fi
  L1=$L1T
 fi
 if [ $L2 -ne $L2T ]
 then
  if [ $L2T ]
  then
	$GP set 1
  else
	$GP clear 1
  fi

  L2=$L2T
 fi
 if [ $L3 -ne $L3T ]
 then
  if [ $L3T ]
  then
	$GP set 3
  else
	$GP clear 3
  fi

  L3=$L3T
 fi
 if [ $L4 -ne $L4T ]
 then
  if [ $L4T ]
  then
	$GP set 4
  else
	$GP clear 4
  fi
  L4=$L4T
 fi

 sleep 1

done

