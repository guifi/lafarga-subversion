# -*- coding: utf-8 -*-

# Form implementation generated from reading ui file '/home/eduard/Apli/projectes/socors/routerclient/uipack/textwindow.ui'
#
# Created: Sat Jul 24 19:17:02 2010
#      by: PyQt4 UI code generator 4.6
#
# WARNING! All changes made in this file will be lost!

from PyQt4 import QtCore, QtGui

class Ui_textWindow(object):
    def setupUi(self, textWindow):
        textWindow.setObjectName("textWindow")
        textWindow.resize(400, 300)
        self.horizontalLayout = QtGui.QHBoxLayout(textWindow)
        self.horizontalLayout.setSpacing(0)
        self.horizontalLayout.setContentsMargins(0, 2, 0, 0)
        self.horizontalLayout.setObjectName("horizontalLayout")
        self.text = QtGui.QPlainTextEdit(textWindow)
        self.text.setObjectName("text")
        self.horizontalLayout.addWidget(self.text)

        self.retranslateUi(textWindow)
        QtCore.QMetaObject.connectSlotsByName(textWindow)

    def retranslateUi(self, textWindow):
        textWindow.setWindowTitle(QtGui.QApplication.translate("textWindow", "Form", None, QtGui.QApplication.UnicodeUTF8))


if __name__ == "__main__":
    import sys
    app = QtGui.QApplication(sys.argv)
    textWindow = QtGui.QWidget()
    ui = Ui_textWindow()
    ui.setupUi(textWindow)
    textWindow.show()
    sys.exit(app.exec_())

