import socket
import struct 
import sys
from ffunction import *

class Connection():
    def __init__(self):
        self.socket = socket.socket()
        #self.socket.connect(("127.0.0.1", 5000))
        self.gData=getDataInstance()
        self.labMsg=0xF0F0
        
    def connect(self, ip, port):
        try:
            #self.socket.connect("127.0.0.1", 5000)
            self.socket.connect((ip, port))
        except Exception, e:
            #QtGui.QMessageBox.question(self.gData.activeWindow, 'Warning',formatExceptionInfo1()) 
            return (-1, formatExceptionInfo1())
        else:
            return (0, "")

    def sendAndReceive(self, pNum):
#        v=struct.pack('!hBBBBBB', self.labMsg,3, 1,1, 0, 0, 0) 
#        #print  repr(v)
#        self.socket.send(v)
#        self.socket.send(struct.pack('!i', len(message)))
#        self.socket.send(message)
#        v=self.socket.recv(8)
#        print repr(v)
#        type=struct.unpack('!B',v[2:3])[0]
#        print type
#        stype=struct.unpack('!B',v[3:4])[0]
#        print stype
#        idps=struct.unpack('!B',v[4:5])[0]
#        print idps
#        v=self.socket.recv(4)
#        lon= struct.unpack('!i',v[0:4])[0]
#        print lon
#        k=self.socket.recv(lon)
#        print k
#        return k
        vc=self.gData.MsgBufOut[pNum]
        v=struct.pack('!hBBBBBB', self.labMsg,vc.type, vc.stype,vc.idps, vc.v1, vc.v2, vc.v3) 
        #print  repr(v)
        self.socket.send(v)
        self.socket.send(struct.pack('!i', len(vc.msg)))
        self.socket.send(vc.msg)
        v=self.socket.recv(8)
        print repr(v)
        type=struct.unpack('!B',v[2:3])[0]
        print type
        stype=struct.unpack('!B',v[3:4])[0]
        print stype
        idps=struct.unpack('!B',v[4:5])[0]
        print idps
        v=self.socket.recv(4)
        lon= struct.unpack('!i',v[0:4])[0]
        print lon
        k=self.socket.recv(lon)
        print k
        return k

