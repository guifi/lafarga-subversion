# -*- coding: utf-8 -*-

"""
Module implementing GralData.
"""
import xml.dom.minidom
from appprog.widgets import OptionMenu
from threading import Thread, Event
import os

class GralData:
    """
    Class documentation goes here.
    """
    def __init__(self):
        """
        Constructor
        """
        self.menu=[]
        self.moptions=[]
        self.mainWindow=None
        self.connection=None
        self.activeWindow=None
        self.MsgBufOut=[]
        self.MsgBufIn=[]
        self.threadOut=None
        self.threadIn=None
        
        for i in range(256):
            self.MsgBufOut.append(MsgBuf())
            self.MsgBufIn.append(MsgBuf())
            
        self.threadOut=ThreadData()
        self.threadIn=ThreadData()
     
    def loadMenu(self):
        oxml=xml.dom.minidom.parse("appfiles/menu.xml")
        gralMenu=oxml.getElementsByTagName("gralmenu")
        optionNodes=gralMenu.item(0).getElementsByTagName("option")
        numOptions=len(optionNodes)
        for i in range(numOptions):
            option=optionNodes.item(i)
            optionAttr=option.attributes
            name=optionAttr.getNamedItem("name").nodeValue
            title=optionAttr.getNamedItem("title").nodeValue
            vop=OptionMenu("menu", name, title, "window")
            self.menu.append(vop) 
            self.moptions.append(vop)
        subMenus=oxml.getElementsByTagName("submenu")
        numSubMenus=len(subMenus)
        
        for i in range(numSubMenus):
            subMenu=subMenus.item(i)
            subMenuAttr=subMenu.attributes
            smName=subMenuAttr.getNamedItem("name").nodeValue
            omenu=None
            for m in self.moptions:
                if m.name == smName:
                    omenu=m
                    break

            if omenu!=None:
                omenu.setType("menu")
                optionNodes=subMenu.getElementsByTagName("option")
                numOptions=len(optionNodes)
                for j in range(numOptions):
                    option=optionNodes.item(j)
                    optionAttr=option.attributes
                    name=optionAttr.getNamedItem("name").nodeValue
                    title=optionAttr.getNamedItem("title").nodeValue
                    type=optionAttr.getNamedItem("type").nodeValue
                    vop=OptionMenu("smenu", name, title, type)
                    omenu.submenu.append(vop) 
                    self.moptions.append(vop)
                    
    def setMainWindow(self, mainwindow):
        self.mainWindow=mainwindow
        
    def newMsgOut(self):
        n=len(self.MsgBufOut)
        for x in range(n):
            if self.MsgBufOut[x].status==0:
                self.MsgBufOut[x].status=1
                break
                
        if x>=n:
            x=-1
            
        return x

    def newMsgIn(self):
        n=len(self.MsgBufIn)
        for x in range(n):
            if self.MsgBufIn[x].status==0:
                self.MsgBufIn[x].status=1
                break
                
        if x>=n:
            x=-1
            
        return x


class WindowData(object):
    def __init__(self):
        """
        Constructor
        """
        #self.window={"type":3, "title", ""}
        self.window=DicVar()
        self.window.setKey("type", None)
        self.window.setKey("title", "")
        self.command=DicVar()
        self.command.setKey("name", "")
        self.command.setKey("type", "")
        self.command.setKey("stype", "")
        self.command.setKey("command", "")
        

    def loadXml(self, pXmlFile):            
        if os.path.exists(pXmlFile) and os.path.isfile(pXmlFile):
            oxml=xml.dom.minidom.parse(pXmlFile)
            vWindow=oxml.getElementsByTagName("window")
            vWindowAttr=vWindow.item(0).attributes
            self.setProperty(self.window, vWindowAttr, "type")
            self.setProperty(self.window, vWindowAttr, "title")
            vCommansCab=oxml.getElementsByTagName("commands")
            vCommans=vCommansCab.item(0).getElementsByTagName("command")
            vCommanAttr=vCommans.item(0).attributes
            self.setProperty(self.command, vCommanAttr, "name")
            self.setProperty(self.command, vCommanAttr, "type")
            self.setProperty(self.command, vCommanAttr, "stype")
            self.setProperty(self.command, vCommanAttr, "command")
            return 0        
        else:
            return -1
            
    def setProperty(self, pDicVar, pNodeAttr, pKey):
        if pNodeAttr.get(pKey) is not None:
            pDicVar.setKey(pKey,pNodeAttr.getNamedItem(pKey).nodeValue )
            return 1
        else:
            return 0
            

class DicVar(object):
    def __init__(self):
        self.data={}
        
    def setKey(self, pkey, pvalue):
        self.data[pkey]=pvalue

    def getKey(self, pkey):
        return self.data[pkey]


class MsgBuf(object):
    def __init__(self):
        self.status=0
        self.type=0
        self.stype=0
        self.idps=0
        self.v1=0
        self.v2=0
        self.v3=0
        self.msg=""
        
    def setStatus(self, pstatus):
        self.status=pstatus
        
    def setControl(self, ptype, pstype, pidps, pv1, pv2, pv3):   
        self.type=ptype
        self.stype=pstype
        self.idps=pidps
        self.v1=pv1
        self.v2=pv2
        self.v3=pv3
    
    def setMsg(self, pMsg):
        self.msg=pMsg


class ThreadData():
    def __init__(self):
        self.event=None
        self.status=0
        self.thread=None
        
    def setEvent(self):
        self.event=Event()
    
    
    
    
