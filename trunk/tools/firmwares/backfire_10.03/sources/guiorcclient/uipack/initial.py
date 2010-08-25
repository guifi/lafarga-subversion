# -*- coding: utf-8 -*-

"""
Module implementing Initial.
"""

from PyQt4.QtGui import QWidget
from PyQt4.QtCore import pyqtSignature

from Ui_initial import Ui_initial
from appprog.ffunction import *

class Initial(QWidget, Ui_initial):
    """
    Class documentation goes here.
    """
    def __init__(self, parent = None):
        """
        Constructor
        """
        QWidget.__init__(self, parent)
        self.setupUi(self)
        self.printStatus("")
        self.nData=0
        self.gData=getDataInstance()
       
        
    def init(self, parentClass):
        self.parentClass=parentClass
        self.loadInitData(self.nData+1)
        
    @pyqtSignature("")
    def on_BtnOk_released(self):
        """
        Slot documentation goes here.
        """
        self.parentClass.initialOk()
        # TODO: not implemented yet
        #raise NotImplementedError
    
    def loadInitData(self, pData):
        self.nData=pData
        if pData==1:
            self.printStatus("Loading menu ...")
            self.gData.loadMenu()
            self.printStatus("")
            
    def printStatus(self, text):
        self.lstatus.setText(text)
        
