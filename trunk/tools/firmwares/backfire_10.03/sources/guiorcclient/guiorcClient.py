#!/usr/bin/env python
from PyQt4 import QtCore, QtGui
from appprog.ctrlwindow import CtrlWindow

if __name__ == "__main__":
    import sys
    app = QtGui.QApplication(sys.argv)
    cw = CtrlWindow()
    cw.init()
    sys.exit(app.exec_())


