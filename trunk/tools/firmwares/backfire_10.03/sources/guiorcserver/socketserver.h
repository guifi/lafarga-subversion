/**
 *
 */
#ifndef _SOCKETSERVER_H
#define _SOCKETSERVER_H

typedef struct sc_header{
    char label[2]; //0xF0F0
    char control[6];  	//[0] type: 0 SystemShort, 1 SystemVar, 2 AppShort, 3 AppVar
						//[1] stype: type 3 recv:  1 command
						//[2] id client process
} sc_header;

typedef struct sc_out{
	char status;	  //0 free  1 not free
    char control[6];  //[0] type: 0 SystemShort, 1 SystemVar, 2 AppShort, 3 AppVar
                      //[1] stype: type 3:  1 response command
                      //[2] id client process
					  //[3] send:   0 end response  1 part of response
    char *msg;
} sc_out;

typedef struct sc_in{
	char status;	  //0 free  1 working  2 memory
    char control[6];
    char *msg;
    int lenmsg;
    pthread_t idthread;
} sc_in;

#endif

