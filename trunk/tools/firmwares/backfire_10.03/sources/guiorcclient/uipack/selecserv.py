# -*- coding: utf-8 -*-

"""
Module implementing SelecServ.
"""
from PyQt4 import QtCore, QtGui
from PyQt4.QtGui import QDialog
from PyQt4.QtCore import pyqtSignature
from appprog.ffunction import *
from appprog.sockets import Connection
from Ui_selecserv import Ui_Dialog

class SelecServ(QDialog, Ui_Dialog):
    """
    Class documentation goes here.
    """
    def __init__(self, parent = None):
        """
        Constructor
        """
        QDialog.__init__(self, parent)
        self.setupUi(self)
        

    def setup(self, parentClass):
        self.parentClass=parentClass
        self.gData=getDataInstance()
        self.gData.connection=Connection()
    
    def connect(self):
        v=self.gData.connection.connect("127.0.0.1", 5000)
        if v[0]==0:
            self.parentClass.selecservOk()
        else:
            QtGui.QMessageBox.question(self, 'Warning',v[1]) 
        
    @pyqtSignature("")
    def on_btnOk_released(self):
        """
        Slot documentation goes here.
        """
        self.connect()
        #self.parentClass.selecservOk()        
        # TODO: not implemented yet
        # raise NotImplementedError
    
    @pyqtSignature("")
    def on_btnCancel_released(self):
        """
        Slot documentation goes here.
        """
        self.parentClass.selecservCancel()        
        # TODO: not implemented yet
        # raise NotImplementedError
