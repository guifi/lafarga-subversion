#factory function
from appprog.graldata import GralData
import sys
import traceback
     
gData = None

def getDataInstance():
    global gData
    if gData is None:
        gData = GralData()
    return gData


def formatExceptionInfo(level = 6):
    error_type, error_value, trbk = sys.exc_info()
    tb_list = traceback.format_tb(trbk, level)    
    s = "Error: %s \nDescription: %s \nTraceback:" % (error_type.__name__, error_value)
    for i in tb_list:
        s += "\n" + i
    return s
    
def formatExceptionInfo1():
    error_type, error_value, trbk = sys.exc_info()
    s = "Error: %s " % (error_value)
    return s
