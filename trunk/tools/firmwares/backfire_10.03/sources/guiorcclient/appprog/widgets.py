# -*- coding: utf-8 -*-

"""
Module implementing widgets.
"""
from PyQt4 import QtCore, QtGui
from PyQt4.QtCore import QPoint
from PyQt4.QtCore import Qt
from PyQt4.QtGui import QMdiSubWindow
from uipack.textwindow import TextWindow
import ffunction
import sys

class OptionMenu:
    def __init__(self, mode, name, title, type):
        self.mode=mode
        self.wid=0
        self.name=name
        self.title=title
        self.type=type
        self.submenu=[]
        self.widget=None
        self.widget_smenu=None
    
    def setType(self, type):
        self.type=type
        if type=="menu":
            self.title=self.title+"..."
        
    def on_widget_released(self):
        if self.type=="menu":
            self.widget_smenu.popup(self.widget.mapToGlobal(QPoint(self.widget.width(), 0)) )
        else:
            vData=ffunction.getDataInstance()
            vData.mainWindow.createSubwindow(self.wid,"appfiles/"+self.name+".xml")
            

class GSubWindow(QMdiSubWindow):
    def setup(self, number, pwindowData):
        self.number=number
        self.state=0
        self.windowData=pwindowData
        self.gData=ffunction.getDataInstance()

    def getNumber(self):
        return self.number
      
    def closeEvent(self, event):
        self.state=0

    def showWindow(self):
        self.show()
        self.state=2

class GswText(GSubWindow):
    def setup(self, number, pWindowData):
        GSubWindow.setup(self, number, pWindowData)
        self.setWidget(TextWindow())
        vw=self.widget().frameGeometry().width()
        vh=self.widget().frameGeometry().height()
        self.resize(vw, vh)
        self.widget().text.setReadOnly(1)
        self.setWindowTitle(self.windowData.window.getKey("title"))
        
        
    def showWindow(self, pmode):
        GSubWindow.showWindow(self)
        if pmode==0:
            self.widget().text.setPlainText("")
        n=self.gData.newMsgOut()
        if n>=0:
            vc=self.gData.MsgBufOut[n]
            vc.setStatus(2)
            vc.setControl(3, 1, self.number, 0, 0, 0)
            vc.setMsg(self.windowData.command.getKey("command"))
            #ffunction.getDataInstance().threadOut.event.set()
            self.gData.threadOut.event.set()
            #v=ffunction.getDataInstance().connection.sendAndReceive(0)
            #self.widget().text.appendPlainText(v)
    
    def msgIn(self, pindex):
        self.widget().text.appendPlainText(self.gData.MsgBufIn[pindex].msg)
        self.gData.MsgBufIn[pindex].setStatus(0)  
