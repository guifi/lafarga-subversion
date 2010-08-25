# -*- coding: utf-8 -*-

"""
Module implementing MainWindow.
"""

from PyQt4.QtGui import QMainWindow
from PyQt4.QtCore import pyqtSignature
from PyQt4 import QtCore, QtGui

from PyQt4.QtCore import *
from PyQt4.QtGui import *
import sys
import os
import xml.dom.minidom
from gridwindow import Form
from Ui_mainwindow import Ui_mainwindow
from appprog.ffunction import *
from appprog.widgets import *
from appprog.graldata import WindowData



class MainWindow(QMainWindow, Ui_mainwindow):
    """
    Class documentation goes here.
    """
    def __init__(self, parent = None):
        """
        Constructor
        """
        QMainWindow.__init__(self, parent)
        self.setupUi(self)
        self.gData=getDataInstance()
        self.windows=[]
        
        n=0
        w=0
        for menu in self.gData.menu:
            if menu.mode=="menu":
                menu.widget = QtGui.QPushButton(self.frameMenu)
                menu.widget.setGeometry(QtCore.QRect(0, n*21, 101, 21))
                menu.widget.setObjectName(menu.name)
                menu.widget.setText( menu.title)
                menu.widget.show()
                menu.widget.released.connect(menu.on_widget_released)
                n=n+1
                if len(menu.submenu)>0:
                    menu.widget_smenu = QtGui.QMenu()
                    menu.widget_smenu.setObjectName(menu.name+"_smenu")    
                    for submenu in menu.submenu:
                        submenu.widget = QtGui.QAction(self)
                        submenu.widget.setText(submenu.title)
                        submenu.widget.triggered.connect(submenu.on_widget_released)
                        menu.widget_smenu.addAction(submenu.widget)
                        if len(submenu.submenu)>0:
                             w=self.createSubmenu(submenu,  w)
                        else:
                            w= w+1
                            submenu.wid= w
                else:
                    w= w+1
                    menu.wid= w
        
        for i in range(0, w):
            self.windows.append(None)
        
        #self.mdiArea.subWindowActivated.connect(self.on_subwindow_activated)
        
#        model=QStandardItemModel()
#        for n in range(10):                   
#            item = QStandardItem('Item %s' % n)
#            check = Qt.Checked 
#            item.setCheckState(check)
#            item.setCheckable(True)
#            model.appendRow(item)
#        #view = QListView()
#        self.listView.setModel(model)
#        self.menu0 = QtGui.QMenu()
#        self.menu0.setObjectName("menu0")
#        self.action01 = QtGui.QAction(self)
#        self.action01.setText("accion 1")
#        self.action01.setObjectName("action01")
#        self.action02= QtGui.QAction(self)
#        self.action02.setText("accion 2")
#        self.action02.setObjectName("action02")
#        self.menu0.addAction(self.action01)
#        self.menu0.addAction(self.action02)
#        #self.btn.setMenu(self.menu0)
#        self.menu0.aboutToShow.connect(self.pepe) 
#        self.layout = QHBoxLayout()
#        self.layout.addStretch(1)
#        self.layout.addWidget(self.toolBu)
#        self.layout.addWidget(self.menu0)

    def createSubmenu(self, menu,  w):                    
        if len(menu.submenu)>0:
            menu.widget_smenu = QtGui.QMenu()
            menu.widget_smenu.setObjectName(menu.name+"_smenu")   
            menu.widget.setMenu(menu.widget_smenu) 
            for submenu in menu.submenu:
                submenu.widget = QtGui.QAction(self)
                submenu.widget.setText(submenu.title)
                submenu.widget.triggered.connect(submenu.on_widget_released)
                menu.widget_smenu.addAction(submenu.widget)
                if len(submenu.submenu)>0:
                    w=self.createSubmenu(submenu,  w)
                else:
                    w= w+1
                    submenu.wid= w
        return  w


    def createSubwindow(self, wid, xmlfile):
        oWindowData=WindowData()
        vret=oWindowData.loadXml(xmlfile)
        if vret==0:
            if self.windows[wid]==None:
                vType=oWindowData.window.getKey("type")
                if vType=="text":
                    subWindow=GswText(self)
                else:
                    vType=""
                    
                if vType != "":
                    subWindow.setup(wid, oWindowData)
                    #subWindow.setAttribute(Qt.WA_DeleteOnClose)
                    self.mdiArea.addSubWindow(subWindow)
                    vRect=self.mdiArea.frameGeometry()
                    subWindow.move((vRect.width()-subWindow.frameGeometry().width())/2, (vRect.height()-subWindow.frameGeometry().height())/2)
                    self.windows[wid]=subWindow
                    subWindow.showWindow(0)
            else:
#                if self.window[wid].widget().isVisible():
#                    self.windows[wid].showWindow(0)
#                else:
                self.windows[wid].showWindow(1)
        else:
            QtGui.QMessageBox.question(self, 'Warning',"Config file not exists")

            
            
            

