RL_LIBRARY_VERSION = 5.1

SHELL = /bin/sh
RM = rm -f

srcdir = .
VPATH = .
top_srcdir = readline-5.1/
BUILD_DIR = .

DESTDIR =

DEFS = -DHAVE_CONFIG_H
CC = gcc
CFLAGS = -g -O
LOCAL_CFLAGS =  -DREADLINE_LIBRARY -DRL_LIBRARY_VERSION='"$(RL_LIBRARY_VERSION)"'
CPPFLAGS = 

INCLUDES = -I$(srcdir) -I$(top_srcdir) -I..

CCFLAGS  = $(DEFS) $(LOCAL_CFLAGS) $(CPPFLAGS) $(INCLUDES) $(CFLAGS)
LDFLAGS = -g -L.. 

PURIFY = 

READLINE_LIB = readline-5.1/libreadline.a
HISTORY_LIB = readline-5.1/libhistory.a

TERMCAP_LIB = -ltermcap

.c.o:
	${RM} $@
	$(CC) $(CCFLAGS) -c $<

EXECUTABLES = cliorc 
OBJECTS = cliorc.o 

all: $(EXECUTABLES)
everything: all

cliorc: cliorc.o $(READLINE_LIB)
	$(PURIFY) $(CC) $(LDFLAGS) -o $@ cliorc.o $(READLINE_LIB) $(TERMCAP_LIB) `pkg-config --cflags --libs lua5.1`

clean mostlyclean:
	$(RM) $(OBJECTS)
	$(RM) $(EXECUTABLES) *.exe

distclean maintainer-clean: clean
	$(RM) Makefile

cliorc.o: cliorc.c

cliorc.o: $(top_srcdir)/readline.h

