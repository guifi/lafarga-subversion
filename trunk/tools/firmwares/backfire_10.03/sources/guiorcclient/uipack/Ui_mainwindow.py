# -*- coding: utf-8 -*-

# Form implementation generated from reading ui file '/home/eduard/Apli/projectes/socors/routerclient/uipack/mainwindow.ui'
#
# Created: Sat Jul 24 00:55:04 2010
#      by: PyQt4 UI code generator 4.6
#
# WARNING! All changes made in this file will be lost!

from PyQt4 import QtCore, QtGui

class Ui_mainwindow(object):
    def setupUi(self, mainwindow):
        mainwindow.setObjectName("mainwindow")
        mainwindow.resize(800, 600)
        mainwindow.setMinimumSize(QtCore.QSize(640, 480))
        self.centralwidget = QtGui.QWidget(mainwindow)
        self.centralwidget.setObjectName("centralwidget")
        self.horizontalLayout = QtGui.QHBoxLayout(self.centralwidget)
        self.horizontalLayout.setObjectName("horizontalLayout")
        self.frameMenu = QtGui.QFrame(self.centralwidget)
        sizePolicy = QtGui.QSizePolicy(QtGui.QSizePolicy.Fixed, QtGui.QSizePolicy.Preferred)
        sizePolicy.setHorizontalStretch(0)
        sizePolicy.setVerticalStretch(0)
        sizePolicy.setHeightForWidth(self.frameMenu.sizePolicy().hasHeightForWidth())
        self.frameMenu.setSizePolicy(sizePolicy)
        self.frameMenu.setMinimumSize(QtCore.QSize(101, 63))
        self.frameMenu.setFrameShape(QtGui.QFrame.Panel)
        self.frameMenu.setFrameShadow(QtGui.QFrame.Sunken)
        self.frameMenu.setObjectName("frameMenu")
        self.horizontalLayout.addWidget(self.frameMenu)
        self.mdiArea = QtGui.QMdiArea(self.centralwidget)
        sizePolicy = QtGui.QSizePolicy(QtGui.QSizePolicy.Preferred, QtGui.QSizePolicy.Preferred)
        sizePolicy.setHorizontalStretch(0)
        sizePolicy.setVerticalStretch(0)
        sizePolicy.setHeightForWidth(self.mdiArea.sizePolicy().hasHeightForWidth())
        self.mdiArea.setSizePolicy(sizePolicy)
        self.mdiArea.setAutoFillBackground(True)
        self.mdiArea.setViewMode(QtGui.QMdiArea.SubWindowView)
        #self.mdiArea.setDocumentMode(True)
        self.mdiArea.setObjectName("mdiArea")
        self.subwindow = QtGui.QWidget(self.mdiArea)
        self.subwindow.setObjectName("subwindow")
        self.horizontalLayout.addWidget(self.mdiArea)
        mainwindow.setCentralWidget(self.centralwidget)
        self.toolMenu = QtGui.QToolBar(mainwindow)
        self.toolMenu.setMovable(False)
        self.toolMenu.setFloatable(False)
        self.toolMenu.setObjectName("toolMenu")
        mainwindow.addToolBar(QtCore.Qt.TopToolBarArea, self.toolMenu)

        self.retranslateUi(mainwindow)
        QtCore.QMetaObject.connectSlotsByName(mainwindow)

    def retranslateUi(self, mainwindow):
        mainwindow.setWindowTitle(QtGui.QApplication.translate("mainwindow", "MainWindow", None, QtGui.QApplication.UnicodeUTF8))
        self.subwindow.setWindowTitle(QtGui.QApplication.translate("mainwindow", "Subwindow", None, QtGui.QApplication.UnicodeUTF8))
        self.toolMenu.setWindowTitle(QtGui.QApplication.translate("mainwindow", "toolBar", None, QtGui.QApplication.UnicodeUTF8))


if __name__ == "__main__":
    import sys
    app = QtGui.QApplication(sys.argv)
    mainwindow = QtGui.QMainWindow()
    ui = Ui_mainwindow()
    ui.setupUi(mainwindow)
    mainwindow.show()
    sys.exit(app.exec_())

