# -*- coding: utf-8 -*-

"""
Module implementing CtrlWindow.
"""

from uipack.mainwindow import MainWindow
from uipack.initial import Initial
from uipack.selecserv import SelecServ
from appprog.ffunction import getDataInstance
from threading import Thread
import struct 
from PyQt4.QtCore import *

class CtrlWindow(object):
    """
    Class documentation goes here.
    """
    def __init__(self):
        """
        Constructor
        """
        self.gData=getDataInstance()
        
    def init(self):
        self.gData.threadOut.setEvent()
        self.gData.threadOut.thread = ThreadOut(self.gData,  self.gData.threadOut)
        self.gData.threadOut.thread.start()
        #self.gData.threadIn.setEvent()
        self.gData.threadIn.thread = ThreadIn(self.gData,  self.gData.threadIn)
        #self.gData.threadIn.thread.start()
        self.initialShow()
    
    def initialShow(self):
        self.uiInitial = Initial()
        self.uiInitial.init(self)
        self.uiInitial.show()
        
    def initialOk(self):
        self.uiInitial.close()
        self.uiSelecServ= SelecServ()
        self.gData.activeWindow=self.uiSelecServ
        self.uiSelecServ.setup(self)
        self.uiSelecServ.show()               
 
    def selecservOk(self):
        self.uiSelecServ.close()
        self.uiMainWindow = MainWindow()
        self.gData.setMainWindow(self.uiMainWindow)
        self.gData.activeWindow=self.uiMainWindow
        #self.gData.threadIn.event.set()
        self.gData.threadIn.thread.start()
        self.uiMainWindow.show()  
        self.gData.threadOut.status==2  
        self.gData.threadOut.event.set() 
        
    def selecservCancel(self):
        self.uiSelecServ.close()       
        


class ThreadOut(Thread):
    def __init__(self, pData, pthreadData):
        Thread.__init__(self)
        self.data=pData
        self.threadData=pthreadData
 
    def run(self):
        self.threadData.status=1
        while(1):
            print("thread out")
            self.threadData.event.clear()
            self.threadData.event.wait()
            if self.threadData.status==2:
                break
            elif self.threadData.status==1:
                n=len(self.data.MsgBufOut)
                for x in range(n):
                    if self.data.MsgBufOut[x].status==2:
                        vc=self.data.MsgBufOut[x]
                        v=struct.pack('!HBBBBBB', 0xF0F0,vc.type, vc.stype,vc.idps, vc.v1, vc.v2, vc.v3) 
                        #print  repr(v)
                        self.data.connection.socket.send(v)
                        self.data.connection.socket.send(struct.pack('!i', len(vc.msg)))
                        self.data.connection.socket.send(vc.msg)
                        self.data.MsgBufOut[x].setStatus(0)
        
        print "final thread out"

class ThreadIn_(Thread):
    def __init__(self, pData, pthreadData):
        Thread.__init__(self)
        self.data=pData
        self.threadData=pthreadData
 
    def run(self):
        self.threadData.status=1
        self.threadData.event.wait()
        while(1):
            if self.threadData.status==2:
                break
            elif self.threadData.status==1:
                print("thread in")
                v=self.data.connection.socket.recv(8)
                type=struct.unpack('!B',v[2:3])[0]
                stype=struct.unpack('!B',v[3:4])[0]
                idps=struct.unpack('!B',v[4:5])[0]
                v=self.data.connection.socket.recv(4)
                lon= struct.unpack('!i',v[0:4])[0]
                msg=self.data.connection.socket.recv(lon)                
                
                x=self.data.newMsgIn()
                self.data.MsgBufIn[x].setControl(type, stype, idps, 0, 0, 0)
                self.data.MsgBufIn[x].setMsg(msg)
                self.data.MsgBufIn[x].setStatus(2)
                
                self.data.mainWindow.windows[idps].msgIn(x)
                
        print "final thread out"
 
class ThreadIn(QThread):
    def __init__(self, pData, pthreadData):
        QThread.__init__(self)
        self.data=pData
        self.threadData=pthreadData
 
    def run(self):
        self.threadData.status=1
        #self.threadData.event.wait()
        while(1):
            if self.threadData.status==2:
                break
            elif self.threadData.status==1:
                print("thread in")
                v=self.data.connection.socket.recv(8)
                type=struct.unpack('!B',v[2:3])[0]
                stype=struct.unpack('!B',v[3:4])[0]
                idps=struct.unpack('!B',v[4:5])[0]
                v=self.data.connection.socket.recv(4)
                lon= struct.unpack('!i',v[0:4])[0]
                msg=self.data.connection.socket.recv(lon)                
                
                x=self.data.newMsgIn()
                self.data.MsgBufIn[x].setControl(type, stype, idps, 0, 0, 0)
                self.data.MsgBufIn[x].setMsg(msg)
                self.data.MsgBufIn[x].setStatus(2)
                
                self.data.mainWindow.windows[idps].msgIn(x)
                
        print "final thread out"
