/*
* Javier Abellan, 20 Jun 2000
*
* Funciones para la apertura de un socket servidor y la conexion con sus
* clientes
*
* MODIFICACIONES:
* 4 Septiembre 2003: A�adida funci�n Abre_Socket_Udp() 
*/

/* Includes del sistema */
#include <sys/types.h>
#include <sys/socket.h>
#include <sys/un.h>
#include <netinet/in.h>
#include <netdb.h>
#include <unistd.h>
#include <errno.h>
#include <stdio.h>
#include <string.h>
#include <arpa/inet.h>

/*
* open socket server AF_INET.
* return socket or <0 error
* params: port and max connections
*   if port = 0 search port and update parameter
*/
int opensocketinet (int pport, int pmaxconn){
        int s;
	struct sockaddr_in bs;
       
	s = socket (AF_INET, SOCK_STREAM, 0);
	if (s == -1) return -2;
	bs.sin_family = AF_INET;
	bs.sin_port = htons(pport);
	bs.sin_addr.s_addr = htonl(INADDR_ANY);
        //bs.sin_addr.s_addr = htonl("127.0.0.1");
	if (bind (s,(struct sockaddr *)&bs, sizeof(bs)) == -1){
		close (s);
		return -3;
	}
	if (listen (s, pmaxconn) == -1){
		close (s);
		return -4;
	}
	return s;
}
/*
* accept client connection
* return client socket or -1
* params socket
*/
int acceptconnection (int psocket){
	int sd, s_aux;
        struct sockaddr_in in;

	s_aux = accept (psocket,(struct sockaddr*) &in, &sd);
	return s_aux;
}

/*
* Lee datos del socket. Supone que se le pasa un buffer con hueco
*	suficiente para los datos. Devuelve el numero de bytes leidos o
* 0 si se cierra fichero o -1 si hay error.
*/
int recvsocket_v (int ps, char *pdata)
{
    int nr = 0;
    nr = recv(ps, pdata, sizeof(pdata) ,0);
    return nr;
}

int recvsocket (int ps, char *pdata, int plen)
{
	int tr = 0;
	int nr = 0;
	if ((ps == -1) || (pdata == NULL) || (plen < 1)) return -1;

	while (tr < plen)
	{
        nr = recv(ps, pdata + tr, plen-tr ,0);
		if (nr > 0){
			tr = tr + nr;
		}else{
			if (nr == 0) break;
			if (nr == -1){
				switch (errno){
					case EINTR:
					case EAGAIN:
						usleep (100);
						break;
					default:
						tr = -1;
                                                break;
				}
			}
		}
	}
	return tr;
}

/*
* Escribe dato en el socket cliente. Devuelve numero de bytes escritos,
* o -1 si hay error.
*/
int Escribe_Socket (int fd, char *Datos, int Longitud)
{
	int Escrito = 0;
	int Aux = 0;

	/*
	* Comprobacion de los parametros de entrada
	*/
	if ((fd == -1) || (Datos == NULL) || (Longitud < 1)){
            printf("err1 Longitud: %i\n",Longitud);
		return -1;
        }

	/*
	* Bucle hasta que hayamos escrito todos los caracteres que nos han
	* indicado.
	*/
	while (Escrito < Longitud)
	{
            
		Aux = write (fd, Datos + Escrito, Longitud - Escrito);
        //printf("caracteres enviados: %i\n", Aux);
		if (Aux > 0)
		{
			/*
			* Si hemos conseguido escribir caracteres, se actualiza la
			* variable Escrito
			*/
			Escrito = Escrito + Aux;
		}
		else
		{
			/*
			* Si se ha cerrado el socket, devolvemos el numero de caracteres
			* leidos.
			* Si ha habido error, devolvemos -1
			*/
			if (Aux == 0)
				return Escrito;
			else
				return -1;
		}
	}

	/*
	* Devolvemos el total de caracteres leidos
	*/
	return Escrito;
}

/**
 * Lee un mensaje de un socket udp. Los par�metros que se pasan son:
 * - Descriptor fd del socket del que se quiere leer.
 * - Estructura Remoto, en la que se devolver� los datos del que ha enviado el
 * mensaje que acabamos de leer.
 * - Longitud_Remoto de la estructura anterior. Debe pasarse relleno con el tama�o
 * de Remoto y se devolver� rellena con el tama�o de los datos en Remoto.
 * - Buffer de Datos donde se quiere que aparezca el mensaje.
 * - Longitud_Datos del buffer anterior.
 *
 * Devuelve el n�mero de bytes leidos o -1 en caso de error.
 */
int Lee_Socket_Udp (
	int fd, struct sockaddr *Remoto, socklen_t *Longitud_Remoto,
	char *Datos, int Longitud_Datos)
{
	int Leido = 0;
	int Aux = 0;

	/*
	* Comprobacion de que los parametros de entrada son correctos
	*/
	if ((fd == -1) || (Datos == NULL) || (Longitud_Datos < 1)
			|| (Remoto == NULL) || (Longitud_Remoto == NULL))
	{
		return -1;
	}

	/*
	* Mientras no hayamos leido todos los datos solicitados
	*/
	while (Leido < Longitud_Datos)
	{
		Aux = recvfrom (fd, Datos + Leido, Longitud_Datos - Leido, 0,
			Remoto, Longitud_Remoto);

		if (Aux > 0)
		{
			/*
			* Si hemos conseguido leer datos, incrementamos la variable
			* que contiene los datos leidos hasta el momento
			*/
			Leido = Leido + Aux;
		}
		else
		{
			/*
			* Si read devuelve 0, es que se ha cerrado el socket. Devolvemos
			* los caracteres leidos hasta ese momento
			*/
			if (Aux == 0)
				return Leido;
			if (Aux == -1)
			{
				/*
				* En caso de error, la variable errno nos indica el tipo
				* de error.
				* El error EINTR se produce si ha habido alguna
				* interrupcion del sistema antes de leer ningun dato. No
				* es un error realmente.
				* El error EGAIN significa que el socket no esta disponible
				* de momento, que lo intentemos dentro de un rato.
				* Ambos errores se tratan con una espera de 100 microsegundos
				* y se vuelve a intentar.
				* El resto de los posibles errores provocan que salgamos de
				* la funcion con error.
				*/
				switch (errno)
				{
					case EINTR:
					case EAGAIN:
						usleep (100);
						break;
					default:
						return -1;
				}
			}
		}
	}

	/*
	* Se devuelve el total de los caracteres leidos
	*/
	return Leido;
}

/**
* Escribe dato en el socket cliente. Devuelve numero de bytes escritos,
* o -1 si hay error.
* - fd es el descriptor del socket.
* - Remoto es el destinatario del mensaje, a qui�n se lo queremos enviar.
* - Longitud_Remoto es el tama�o de Remoto en bytes.
* - Datos es el mensaje que queremos enviar.
* - Longitud_Datos es el tama�o del mensaje en bytes.
*/
int Escribe_Socket_Udp (int fd, struct sockaddr *Remoto,
	socklen_t Longitud_Remoto, char *Datos, int Longitud_Datos)
{
	int Escrito = 0;
	int Aux = 0;

	/*
	* Comprobacion de los parametros de entrada
	*/
	if ((fd == -1) || (Datos == NULL) || (Longitud_Datos < 1)
			|| (Remoto == NULL) )
   {
		return -1;
   }

	/*
	* Bucle hasta que hayamos escrito todos los caracteres que nos han
	* indicado.
	*/
	while (Escrito < Longitud_Datos)
	{
		Aux = sendto (fd, Datos + Escrito, Longitud_Datos - Escrito, 0,
			Remoto, Longitud_Remoto);

		if (Aux > 0)
		{
			/*
			* Si hemos conseguido escribir caracteres, se actualiza la
			* variable Escrito
			*/
			Escrito = Escrito + Aux;
		}
		else
		{
			/*
			* Si se ha cerrado el socket, devolvemos el numero de caracteres
			* leidos.
			* Si ha habido error, devolvemos -1
			*/
			if (Aux == 0)
				return Escrito;
			else
         {
				return -1;
         }
		}
	}

	/*
	* Devolvemos el total de caracteres leidos
	*/
	return Escrito;
}


/**
 * Rellena una estructura sockaddr_in con los datos que se le pasan. Esta estrutura es
 * �til para el envio o recepci�n de mensajes por sockets Udp o para abrir conexiones.
 * Se le pasa el host. Puede ser NULL (para abrir socket servidor Udp o para recepci�n de
 * mensajes de cualquier host).
 * Se le pasa el servicio. Puede ser NULL (para abrir socket cliente Udp).
 * Se le pasa una estructura sockaddr_in que devolver� rellena.
 * Se le pasa una Longitud. Debe contener el tama�o de la estructura sockaddr_in y
 * devolver� el tama�o de la estructura una vez rellena.
 * Devuelve -1 en caso de error.
 */
int Dame_Direccion_Udp (char *Host, char *Servicio, struct sockaddr_in *Servidor,
   int *Longitud_Servidor)
{
   struct servent *Puerto;
   struct hostent *Maquina;

   /* Comprobaci�n de par�metros */
   if (Servidor == NULL) return -1;

   /* Relleno del primer campo de la estructura */
   Servidor->sin_family = AF_INET;

   /* Si nos han pasado un host ... */
   if (Host != NULL)
   {
      /* ... obtenemos la direcci�n del host y la ponemos en la estructura */
      Maquina = gethostbyname (Host);
      if (Maquina == NULL)
         return -1;

      Servidor->sin_addr.s_addr = ((struct in_addr *)(Maquina->h_addr))->s_addr;
   }
   else
      /* Si no nos han pasado un host, ponemos cualquier host. */
      Servidor->sin_addr.s_addr = INADDR_ANY;

   /* Si servicio en NULL, hacemos que el puerto lo eliga el sistema operativo
    libremente.*/
   if (Servicio == NULL)
      Servidor->sin_port = 0;
   else
   {
      /* Si el servicio no es NULL, lo obtenemos. */
      Puerto = getservbyname (Servicio, "udp");
      if (Puerto == NULL)
         return -1;
      Servidor->sin_port = Puerto->s_port;
   }
}



/**
 * Abre un socket inet de udp.
 * Se le pasa el nombre de servicio del socket al que debe atender.
 * Devuelve el descriptor del socket abierto o -1 si ha habido alg�n error.
 */
int Abre_Socket_Udp (char *Servicio)
{
	struct sockaddr_in Direccion;
	struct servent *Puerto = NULL;
	int Descriptor;

	/*
	* se abre el socket
	*/
	Descriptor = socket (AF_INET, SOCK_DGRAM, 0);
	if (Descriptor == -1)
	{
	 	return -1;
	}

	/*
	* Se obtiene el servicio del fichero /etc/services
	*/
	Puerto = getservbyname (Servicio, "udp");
	if (Puerto == NULL)
	{
		return -1;
	}

	/*
	* Se rellenan los campos de la estructura Direccion, necesaria
	* para la llamada a la funcion bind() y se llama a esta.
	*/
	Direccion.sin_family = AF_INET;
	Direccion.sin_port = Puerto->s_port;
	Direccion.sin_addr.s_addr = INADDR_ANY; 

	if (bind (
			Descriptor, 
			(struct sockaddr *)&Direccion, 
			sizeof (Direccion)) == -1)
	{
		close (Descriptor);
		return -1;
	}

	/*
	* Se devuelve el descriptor del socket servidor
	*/
	return Descriptor;
}
