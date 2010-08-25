# -*- coding: utf-8 -*-

# Form implementation generated from reading ui file '/home/eduard/Apli/projectes/socors/routerclient/uipack/initial.ui'
#
# Created: Thu Jul 22 23:34:32 2010
#      by: PyQt4 UI code generator 4.6
#
# WARNING! All changes made in this file will be lost!

from PyQt4 import QtCore, QtGui

class Ui_initial(object):
    def setupUi(self, initial):
        initial.setObjectName("initial")
        initial.setWindowModality(QtCore.Qt.WindowModal)
        initial.resize(438, 242)
        initial.setAutoFillBackground(True)
        self.ltitle = QtGui.QLabel(initial)
        self.ltitle.setGeometry(QtCore.QRect(0, 20, 431, 51))
        font = QtGui.QFont()
        font.setPointSize(11)
        font.setWeight(75)
        font.setBold(True)
        self.ltitle.setFont(font)
        self.ltitle.setTextFormat(QtCore.Qt.AutoText)
        self.ltitle.setAlignment(QtCore.Qt.AlignHCenter|QtCore.Qt.AlignTop)
        self.ltitle.setWordWrap(True)
        self.ltitle.setObjectName("ltitle")
        self.BtnOk = QtGui.QPushButton(initial)
        self.BtnOk.setGeometry(QtCore.QRect(170, 180, 85, 27))
        self.BtnOk.setObjectName("BtnOk")
        self.lstatus = QtGui.QLabel(initial)
        self.lstatus.setGeometry(QtCore.QRect(18, 216, 401, 20))
        self.lstatus.setObjectName("lstatus")

        self.retranslateUi(initial)
        QtCore.QMetaObject.connectSlotsByName(initial)

    def retranslateUi(self, initial):
        initial.setWindowTitle(QtGui.QApplication.translate("initial", "guiorc", None, QtGui.QApplication.UnicodeUTF8))
        self.ltitle.setText(QtGui.QApplication.translate("initial", "<!DOCTYPE HTML PUBLIC \"-//W3C//DTD HTML 4.0//EN\" \"http://www.w3.org/TR/REC-html40/strict.dtd\">\n"
"<html><head><meta name=\"qrichtext\" content=\"1\" /><style type=\"text/css\">\n"
"p, li { white-space: pre-wrap; }\n"
"</style></head><body style=\" font-family:\'DejaVu Sans\'; font-size:8pt; font-weight:400; font-style:normal;\">\n"
"<p style=\" margin-top:0px; margin-bottom:0px; margin-left:0px; margin-right:0px; -qt-block-indent:0; text-indent:0px;\"><span style=\" font-size:11pt; font-weight:600;\">GUIORC </span></p>\n"
"<p style=\" margin-top:0px; margin-bottom:0px; margin-left:0px; margin-right:0px; -qt-block-indent:0; text-indent:0px;\"><span style=\" font-size:11pt;\">Graphical User Interface for Open Router Control</span></p></body></html>", None, QtGui.QApplication.UnicodeUTF8))
        self.BtnOk.setText(QtGui.QApplication.translate("initial", "Okis", None, QtGui.QApplication.UnicodeUTF8))
        self.lstatus.setText(QtGui.QApplication.translate("initial", "Status", None, QtGui.QApplication.UnicodeUTF8))


if __name__ == "__main__":
    import sys
    app = QtGui.QApplication(sys.argv)
    initial = QtGui.QWidget()
    ui = Ui_initial()
    ui.setupUi(initial)
    initial.show()
    sys.exit(app.exec_())

