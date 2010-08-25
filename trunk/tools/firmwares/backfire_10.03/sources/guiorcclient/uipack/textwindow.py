# -*- coding: utf-8 -*-

"""
Module implementing textWindow.
"""

from PyQt4.QtGui import QWidget
from PyQt4.QtCore import pyqtSignature

from Ui_textwindow import Ui_textWindow

class TextWindow(QWidget, Ui_textWindow):
    """
    Class documentation goes here.
    """
    def __init__(self, parent = None):
        """
        Constructor
        """
        QWidget.__init__(self, parent)
        self.setupUi(self)
