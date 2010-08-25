/* 
 * File:   main.c
 * Author: eduard
 *
 * Created on 20 / juny / 2010, 19:38
 */

#include <stdio.h>
#include <stdlib.h>
#include <unistd.h>
#include <pthread.h>
#include "socketserver.h"

#define PORT_CONN 5000

void *threadCommand(void *pparam);
void *threadSendOut(void *pparam);

/*
 * 
 */
int main(int argc, char** argv) {

    int s,s_aux;
    /*
    s=execCommand();
    printf("final programa %i \n",s);
    exit(s);
	*/
    s = opensocketinet(PORT_CONN,1);
    if(s < 0){
        printf("ERROR: El socket no se ha creado correctamente! error: %d\n",s);
        exit (-1);
    }
    printf("\n\aServidor ACTIVO escuchando en el puerto: %i socket: %i\n",PORT_CONN,s);
    s_aux = acceptconnection(s);
    printf("\nConexión socket: %i\n",s_aux);
    controlmessage(s,s_aux);
    close (s_aux);
    close (s);
    //return (EXIT_SUCCESS);
}

sc_in *q_in(int mode){
    static sc_in bufIn[256];
    int i;
    if (mode==1){
        for(i=0;i<256;i++){
        	bufIn[i].status=0;
        	bufIn[i].lenmsg=0;
        	bufIn[i].msg=NULL;
        }
    }
    return bufIn;
}
sc_out *q_out(int mode){
    static sc_in bufOut[256];
    int i;
    if (mode==1){
        for(i=0;i<256;i++){
        	bufOut[i].status=0;
        	bufOut[i].msg=NULL;
        }
    }
    return bufOut;
}


int controlmessage(int s, int s_aux){
	int i;
    int nbufIn;
    int verr;
    pthread_t idthreadOut;
    sc_in *bufIn;
    sc_out *bufOut;
    bufIn=q_in(1);
    bufOut=q_out(1);

    verr = pthread_create(&idthreadOut, NULL, threadSendOut, &s_aux);
	if(verr!=0){
		printf("Error create thread %i \n",verr);
		return (-1);
	}

    while(1){
        printf("esperando recepción\n");
        nbufIn = readmsg(s_aux, bufIn);
        if (nbufIn<0){
            printf("Error al recibir %i\n",nbufIn);
            break;
        }else{
            procmsg(s_aux, &nbufIn);
            printf("status: %i \n",bufIn[nbufIn].status);
        }
    }
    return 0;
}

int readmsg (int ps, sc_in *pbufIn){
    int nr;
    sc_header vh;
    int lenmsg;
	int i;
	int ret=0;

    for(i=0;i<256;i++){
    	if (pbufIn[i].status==0){
    		break;
    	}
    }
    if(i==256){
    	printf("buffer in is full");
    	ret = -1;
    }else{
		nr = recvsocket(ps, &vh, sizeof(vh));
		if(nr>0){
			pbufIn[i].status=1;
			bytecpy(pbufIn[i].control,vh.control,6);
			pbufIn[i].lenmsg=0;
			printf("recv cab. %i index: %i type: %i stype: %i idPs: %i \n",nr,i,vh.control[0],vh.control[1],vh.control[2]);
			if((vh.control[0]==3) || (vh.control[0]==1)){
				nr = recvsocket(ps, (char *)&lenmsg, sizeof(int));
				if(nr>0){
					lenmsg=ntohl(lenmsg);
					if (lenmsg > 0){
						pbufIn[i].msg = (char *)malloc (lenmsg+1);
						nr = recvsocket(ps,pbufIn[i].msg,lenmsg);
						if(nr>0){
							pbufIn[i].msg[nr]=0;
							pbufIn[i].lenmsg=nr;
							ret=i;
							printf("recv msg len_message: %i message: %s \n",nr,pbufIn[i].msg);
						}else ret=-5;
					}else ret=-4;
				}else ret=-3;
			}
		}else ret=-2;
	}
    return ret;
}

int sendmsg (int ps, char *pcontrol, char *pmsg){
    int nr;
    sc_header vh;
    int lenmsg;

    vh.label[0]=0xF0;
    vh.label[1]=0xF0;
    bytecpy(vh.control,pcontrol,6);
    lenmsg=len(pmsg);

    nr = Escribe_Socket(ps, &vh, sizeof(vh));
    if(nr>0){
       	lenmsg=htonl(lenmsg);
        nr = Escribe_Socket(ps, (char *)&lenmsg, sizeof(int));
        if(nr>0){
         	nr = Escribe_Socket(ps,pmsg,len(pmsg));
        }
    }
    if(nr == -1){
        printf("Error envio cabecera\n");
        return -1;
    }

    printf("env %i type: %i stype: %i idPs: %i message: %s \n",nr,vh.control[0],vh.control[1],vh.control[2],pmsg);
    return nr;
}

int len(char *s){
    int i;
    for(i=0;*(s+i);i++);
    return i;
}

int freeQIn(int pnbufIn,int pstatus){
	if(pstatus==0){
		if (q_in(0)[pnbufIn].msg != NULL){
			free (q_in(0)[pnbufIn].msg);
			q_in(0)[pnbufIn].msg = NULL;
		}
		q_in(0)[pnbufIn].status=0;
	}else{
		q_in(0)[pnbufIn].status=pstatus;
	}
}

void *threadCommand(void *pparam){
	int *vi = (int *)pparam;;
	int p;
	int ret;
	p=*vi;
	printf("vi %i\n",vi);
	printf("*vi %i\n",*vi);
	printf("p %i\n",p);
	ret=execCommand(p);
	freeQIn(p,0);
}

int execCommand(int pnbufIn){
	char s_out[1025];
	int nr;
	int pid;
	char vend;
	int status;
	int fd[2]; /*descriptores para el pipe*/
	int lenmsg;
	char *bufmsg[1024];
	char *vargs[64];
	printf("indice %i \n",pnbufIn);
	printf("copiando %s\n",q_in(0)[pnbufIn].msg);
	strcpy(bufmsg,q_in(0)[pnbufIn].msg);
	printf("parse %s\n",bufmsg);
	parse(bufmsg, vargs);
	printf("%s\n",*vargs);
	printf("creando pipe\n");
	pipe(&fd[0]);
	if ((pid = fork())<0){
		printf("Error open fork\n");
		return(-1);
	}
	if(pid==0){
		printf("pipe\n");
		close(fd[0]);
		dup2(fd[1],1);
		close(fd[1]);
		//execl("/bin/ls","ls","-l",0);
		printf("%s\n",*vargs);
		execv(*vargs, vargs);
		printf("Error execute command\n");
		return(-1);
	} else {
		close(fd[1]);
		do {
			nr=recvPipe(fd[0],s_out,1024);
			if(nr>0){
				if(nr>=1024){
					vend=1;
				}else{
					vend=0;
				}
				printf("exec ejecutado....\n");
				writeQOut(q_in(0)[pnbufIn].control, s_out, nr, &vend);
			}
			printf("receive pipe %i\n",nr);
		} while(nr>=1024);
		close(fd[0]);
		while (wait(&status) != pid){
			printf("child status: %i \n",status);
		}
		printf("Fin del padre\n");
		return 0;
	}
}

int recvPipe (int pp, char *pdata, int plen)
{
	int tr = 0;
	int nr = 0;
	if ((pp == -1) || (pdata == NULL) || (plen < 1)) return -1;
	while (tr < plen)
	{
		//printf("Esperando leer %i    ",tr);
        nr = read(pp, pdata + tr, plen-tr );
        //printf("Caracteres leidos %i \n",nr);
		if (nr > 0){
			tr = tr + nr;
		}else{
			*(pdata+tr)=0;
			break;
		}
	}
	return tr;
}

int writeQOut(char *pcontrol, char *pdata, int plen, char *pend){
	int i;
	sc_out *bufOut;
	printf("Escribiendo cola \n");
	bufOut=q_out(0);
    for(i=0;i<256;i++){
    	if (bufOut[i].status==0){
    		break;
    	}
    }
    if(i==256){
    	printf("buffer out is full");
    	return (-1);
    }
    bufOut[i].control[0]=3;
    bufOut[i].control[1]=1;
    bufOut[i].control[2]=pcontrol[2];
    bufOut[i].control[3]=pend;
    fillzero(bufOut,4,2);
	bufOut[i].msg = (char *)malloc (plen+1);
	strcpy(bufOut[i].msg,pdata);
    bufOut[i].status=1;

	printf("salida: %i \n%s ***\n",plen,bufOut[i].msg,pdata);
	return (0);
}

int procmsg(int ps, int *pnbufIn){
    int verr;
    sc_in *bufIn;
    bufIn=q_in(0);
    switch (bufIn[*pnbufIn].control[1]==1){
        case 1:{
           //nr = sendmsg(ps,pcontrol,pmsg);
        	printf("param %i \n",*pnbufIn);
        	verr = pthread_create(&(bufIn[*pnbufIn].idthread), NULL, threadCommand, pnbufIn);
        	if(verr!=0){
        		printf("Error create thread %i \n",verr);
        		return (-1);
        	}
            //nr=execCommand(pnbufIn);
            //nr=sendBufOut(ps);
            break;
        }
        case 2:{
            break;
        }
    }

}

void *threadSendOut(void *pparam){
	int *vi = (int *)pparam;
	int v;
	v=sendBufOut(*vi);
}

int sendBufOut(int ps){
	int i;
	int nr=0;

	sc_out *bufOut;
	bufOut=q_out(0);
	while(1){
		for(i=0;i<256;i++){
			if (bufOut[i].status==1){
				break;
			}
		}
		if(i==256){
			//printf("buffer empty");
		}else{
			nr = sendmsg(ps,bufOut[i].control,bufOut[i].msg);
			if (nr > 0){
				if (bufOut[i].msg != NULL){
					free (bufOut[i].msg);
					bufOut[i].msg = NULL;
				}
				bufOut[i].status=0;
			}else{
				printf("empty message");
			}

			//printf("send: %i \n",nr);
		}
	}
}


int bytecpy(char *pend,char *pbegin, int plen){
	int i=0;
	while(i<plen){
		pend[i]=pbegin[i];
		i++;
	}
	return 0;
}
int fillzero(char *pchars, int pinit, int plen){
	int i=0;
	while(i<plen){
		pchars[pinit+i]=0;
		i++;
	}
	return 0;
}
parse(char *buf, char **args)
{
	while (*buf != (char) NULL)
	{
		printf("parse buf %s\n",buf);
		while ( (*buf == ' ') || (*buf == '\t') )
			*buf++ = (char) NULL;

		*args++ = buf;

		while ((*buf != (char) NULL) && (*buf != ' ') && (*buf != '\t'))
			buf++;
	}
	*args = (char) NULL;
}







